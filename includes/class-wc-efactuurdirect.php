<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

class WC_efactuurdirect extends WC_Integration
{
    public $efactuurdirect_api              = false;
    public static $log                      = false;
    public static $log_enabled              = false;
    public $efactuurdirect_table_db_version = '1.1';
    private $addedFunction;

    function __construct()
    {
        $this->id                 = 'efactuurdirect';
        $this->method_title       = __('Efactuurdirect', 'woo-efactuurdirect');
        $this->method_description = __('Create contacts and invoices from WooCommerce orders.', 'woo-efactuurdirect').'<br/>';
        $this->init_settings();
		include_once("additionalFunctions.php");
        $this->addedFunction      = new wooefdAddedFunction();
        self::$log_enabled = ('yes' === $this->get_option('debug', 'no'));
        
		if (is_admin()) {
            $this->init_form_fields();
            add_action('woocommerce_update_options_integration_'.$this->id, array($this, 'process_admin_options'));
			add_action('woocommerce_admin_order_data_after_order_details', array($this, 'output_efactuurdirectstatus'));
            add_action('woocommerce_admin_order_actions_end', array($this, 'wooefd_add_listing_actions'));
            add_action('admin_enqueue_scripts', array($this, 'wooefd_load_admin_scripts'));
            add_action('wp_ajax_generate_wcefactuurdirect', array($this, 'wooefd_process_request_ajax'));
            add_action('admin_print_footer_scripts', 'wooefd_javascript_action', 99);
            add_action('wp_ajax_my_action', 'ajax_handler');
            //add_action('restrict_manage_posts', array($this,'wooefd_efd_edit_form_top_orders'));
            add_action('woocommerce_before_checkout_form', array($this,'efd_edit_form_top_order'));
			add_action('update_option_woo-efactuurdirect_settings', function( $option_name, $old_value, $value ) {
					header('Location: '.sanitize_url($_SERVER['REQUEST_URI']));
			}, 10, 3);
            if (isset($this->settings['auto_invoice']) && $this->settings['auto_invoice'] == 'manually') {
                add_filter('woocommerce_order_actions', array($this, 'generate_invoice_action'));
                add_action('woocommerce_order_action_efactuurdirect_invoice', array($this, 'generate_invoice'));
            }
			add_action( 'save_post_shop_order', 'wooefd_check_if_save_order', 10, 4 );
		}
		if (isset($this->settings['auto_invoice']) && $this->settings['auto_invoice'] != 'manually') {
            add_action('woocommerce_order_status_'.$this->settings['auto_invoice'], array($this, 'generate_invoice_without_notices'));
			foreach($this->addedFunction->get_woocomerce_order_statuses() as $system_status => $status_name){
				if($system_status != $this->settings['auto_invoice']){
					$add_special_action = 'woocommerce_order_status_'.$this->settings['auto_invoice'].'_to_'.$system_status;
					add_action($add_special_action, array($this, 'generate_invoice_without_notices'));
				}
			}
		}
		if (isset($this->settings['add_additional_info_for_order']) && $this->settings['add_additional_info_for_order'] == '1') {
                add_action('woocommerce_checkout_after_customer_details','wooefd_add_custom_invoceremark_textline',10,6);
            }
		add_filter('woocommerce_default_address_fields', 'wooefd_require_fields');
        add_filter( 'woocommerce_after_calculate_totals', 'wooefd_woocommerce_cart_subtotal',      99, 3 );
        add_action( 'woocommerce_order_status_changed', array($this,'wooefd_order_change_status') );
		//add_action( 'woocommerce_process_product_meta', 'wc_custom_save_custom_fields' );
		add_action('woocommerce_checkout_update_order_meta','wooefd_checkout_update_order_meta' , 10, 2);
	
		function wooefd_add_custom_invoceremark_textline(){
		?>
			<p class="form-row form-row-wide address-field" id="invoice_remark" data-priority="50">
				<label for="invoice_remark" class=""><?php echo esc_html(__( 'Invoice Remark', 'woo-efactuurdirect' ));?></label>
				<textarea name="invoice_remark" class="input-text " id="invoice_remark" rows="2" cols="5"></textarea>
			</p>
			<p class="form-row form-row-wide address-field" id="invoice_remark" data-priority="50">
				<label for="invoice_textline" class=""><?php echo esc_html(__( 'Invoice Textline', 'woo-efactuurdirect' ));?></label>
				<textarea name="invoice_textline" class="input-text " id="invoice_textline" rows="2" cols="5"></textarea>
			</p>
		<?php
		}
		
		function wooefd_check_if_save_order($post_id, $post, $update){  //add meta fields invoice_remark and invoice_textline to order. Order was created by admin.
			$invoice_remark = get_post_meta($post_id,'invoice_remark',true);
			if($invoice_remark == ''){
				add_post_meta($post_id,'invoice_remark',' ');
			}
			$invoice_textline = get_post_meta($post_id,'invoice_textline',true);
			if($invoice_textline == ''){
				add_post_meta($post_id,'invoice_textline',' ');
			}
		}
		
		function wooefd_checkout_update_order_meta( $order_id, $posted){
			$order = wc_get_order( $order_id );
			if ( ! empty( $_POST['invoice_remark'] ) ) {
				update_post_meta( $order_id, 'invoice_remark', wp_kses_post($_POST['invoice_remark']) );
			}else{
				update_post_meta( $order_id, 'invoice_remark', ' ' );
			}
			
			if ( ! empty( $_POST['invoice_textline'] ) ) {
				update_post_meta( $order_id, 'invoice_textline', wp_kses_post($_POST['invoice_textline']) );
			}else{
				update_post_meta( $order_id, 'invoice_textline', ' ' );
			}
		}
		
		function wooefd_woocommerce_cart_subtotal($calc){
            $round_tax_at_subtotal = get_option('woocommerce_tax_round_at_subtotal');
            if($round_tax_at_subtotal == 'yes'){
                $taxes		   = $calc->taxes;
                $tax_total_old = $calc->tax_total;
                $total		   = $calc->total;
                $new_tax_total = 0;
                $tmp_tax	   =array();
                foreach ($taxes as $key => $tax){
                    $tmp_tax[$key] = round($tax, 2);
                    $new_tax_total = $new_tax_total+round($tax, 2);
                }
                $calc->total     = $total-$tax_total_old+$new_tax_total;
                $calc->taxes     = $tmp_tax;
                $calc->tax_total = $new_tax_total;
            }
            return $calc;
        }

        function wooefd_showInvoice($id){
            $file_name = 'pdf_to_preview'.'_'.$id;
            $invoice_pdf_content = get_site_transient($file_name);
            $content             = 'Content-Disposition: attachment; filename=invoice_for_order_'.$id.'.pdf';
            header("Content-type: application/x-msdownload", true, 200);
            header($content);
            header("Pragma: no-cache");
            header("Expires: 0");
            echo base64_decode($invoice_pdf_content);
            exit();
        }

        if (isset($_GET['getefactuurdirectpdf'])) {
            wooefd_showInvoice(intval($_GET['getefactuurdirectpdf']));
            exit();
        }

        function wooefd_javascript_action(){
            ?>
            <script type="text/javascript" >
                jQuery(document).ready(function ($) {
                    var url 	   = window.location.search;
                    var shop_order = url.indexOf('post_type=shop_order');
                    var edit_order = url.indexOf('post=');
                    if(shop_order ==1 || edit_order == 1)chechEfdApiStatus();
                    jQuery('.wait_response').each(function () {
                        var element_id = this.id;
                        var id_number  = element_id.split('_');
                        id_number      = id_number[1];
                        checkReadyToDownload(id_number);
                    });
                  var efactur_id=jQuery("#list-table [value='efactuurdirect_invoice_id']").parent().parent().attr('id');
				  if(efactur_id != undefined){
                  $('#'+efactur_id+'-value').attr('readonly',true);
				  var efactur_id=jQuery("#list-table [value='invoice_remark']").parent().parent().attr('id');
                  $('#'+efactur_id+'-value').attr('readonly',true);
				  var efactur_id=jQuery("#list-table [value='invoice_textline']").parent().parent().attr('id');
                  $('#'+efactur_id+'-value').attr('readonly',true);
				  }
                });

                function SendAjaxReqest(request, id, nonce) {                    
                    var data = {
                        action: 'generate_wcefactuurdirect',
                        request: request,
                        id: id,
                        _wpnonce: nonce
                    };
                    jQuery('.proportion').addClass('block_img');
                    if (request == 'download') {
                        jQuery('#downOrd_' + id).removeClass('fa-download');
                        jQuery('#downOrd_' + id).removeClass('fa_down');
                        jQuery('#downOrd_' + id).addClass('fa-refresh');
                        jQuery('#downOrd_' + id).addClass('fa-spin');
                        jQuery('#downOrd_' + id).addClass('fa_rol');
                    }
                    if (request == 'createinvoice') {
                        jQuery('#createOrd_' + id).removeClass('fa-file-pdf-o');
                        jQuery('#createOrd_' + id).addClass('fa-refresh');
                        jQuery('#createOrd_' + id).addClass('fa-spin');
                    }
                    jQuery.post(ajaxurl, data, function (response) {
                        jQuery('.proportion').removeClass('block_img');
                        if (request == 'download' && response!='error') {
                            document.location = '/?getefactuurdirectpdf=' + response;
                            jQuery('#downOrd_' + id).removeClass('fa_rol');
                            jQuery('#downOrd_' + id).removeClass('fa-spin');
                            jQuery('#downOrd_' + id).removeClass('fa-refresh');
                            jQuery('#downOrd_' + id).addClass('fa-download');
							jQuery('#downOrd_' + id).addClass('fa_down');
                        }else if(request == 'download' && response=='error'){
                            alert('<?php echo esc_html(__('Check you efactuurdirect API settings:', 'woo-efactuurdirect'));?>');
                            jQuery('#downOrd_' + id).removeClass('fa_rol');
                            jQuery('#downOrd_' + id).removeClass('fa-spin');
                            jQuery('#downOrd_' + id).removeClass('fa-refresh');
                            jQuery('#downOrd_' + id).addClass('fa-download');
							jQuery('#downOrd_' + id).addClass('fa_down');
                        }
                        if (request == 'createinvoice' && response!='error') {
                            location.reload();
                        }else if(request == 'createinvoice' && response=='error'){
                            alert('<?php echo esc_html(__('Check you efactuurdirect API settings:', 'woo-efactuurdirect'));?>');
                            jQuery('#createOrd_' + id).removeClass('fa_rol');
                            jQuery('#createOrd_' + id).removeClass('fa-spin');
                            jQuery('#createOrd_' + id).removeClass('fa-refresh');
                            jQuery('#createOrd_' + id).addClass('fa-exclamation-circle');
                        }
                    });
                }

                function checkReadyToDownload(id) {
                    var data = {
                        action: 'generate_wcefactuurdirect',
                        request: 'checkInvoiceStatus',
                        id: id
                    };
                    setTimeout(function () {
                        jQuery.post(ajaxurl, data, function (response) {
                            if (response != '0') {
                                if (jQuery('#downOrd_' + id).hasClass('orderpage')) {
                                    jQuery('.headercomment').html(response);
                                }
                                jQuery('#downOrd_' + id).removeClass('wait_response');
                                jQuery('#downOrd_' + id).removeClass('fa_rol');
                                jQuery('#downOrd_' + id).removeClass('fa-spin');
                                jQuery('#downOrd_' + id).removeClass('fa-refresh');
                                jQuery('#downOrd_' + id).addClass('fa-download');
								jQuery('#downOrd_' + id).addClass('fa_down');
                            } else {
                                checkReadyToDownload(id);
                            }
                        });
                    }, 15000);
                }

                function chechEfdApiStatus(){
                    var data = {
                        action: 'generate_wcefactuurdirect',
                        request: 'checkApiStatus',
                        id:0,
                    };

                    setInterval(function() {
                        jQuery.post(ajaxurl, data, function (response) {
                            response=JSON.parse(response);
                            jQuery('#efd_api_status_order').html(response.order_list);
                            jQuery('#efd_api_status_order_list').html(response.edit_order);
                        });
                    }, 30000);
			}

            </script>
            <?php
        }

        function wooefd_require_fields($fields){
            $fields['address_1']['required']    = true;
            $fields['address_2']['required']    = true;
            $fields['address_2']['label']       = __('House Number', 'woo-efactuurdirect');
            $fields['address_2']['placeholder'] = __('Number', 'woo-efactuurdirect');
            $fields['phone']['required']        = true;
            $fields['city']['required']         = true;
            $fields['postcode']['required']     = true;
            $fields['country']['required']      = true;
            unset($fields['state']);
            return $fields;
        }
        $this->updateDB();
    }

	public function wooefd_efd_edit_form_top_orders(){
        $show_string=$this->getApiStatus();
        $show_string=json_decode($show_string,1);
        echo '<div id="efd_api_status_order" style="float:right;margin-left:10px;margin-top:-8px;">'.$show_string['order_list'].'</div>';
    }

    function wooefd_order_change_status($id){
		$order = new WC_Order( $id );
        $order_status= $order->status ;
		if(isset($this->settings['auto_invoice']) && $this->settings['auto_invoice'] != 'manually' && $order_status==$this->settings['auto_invoice']){
			$this->generate_invoice_without_notices($id);
		}
	}
	
    public function wooefd_process_request_ajax(){
        if(!is_user_logged_in()) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'woo-efactuurdirect'));
        }check_ajax_referer( $_POST['request'].'_'.$_POST['id'] );
        if(!isset($_POST['request']) || !isset($_POST['id']) ) {
            wp_die(__('No parameters specified.', 'woo-efactuurdirect'));
        }        
        $request  = sanitize_text_field($_POST['request']);
        $order_id = (int) $_POST['id'];
        if ($request == 'download') {
            usleep( 50000 );
            $busy = get_site_transient('busy_download');	
            while($busy){ 
                sleep (1);
                $busy = get_site_transient('busy_download');
            }
            $pdf       = $this->getPdfFromEfd($order_id);
            set_site_transient('busy_download',true,1);
            if($pdf!='error'){
            $file='pdf_to_preview_'.$order_id;
            set_site_transient($file, $pdf, 0);
            echo esc_html($order_id);}else{
              echo  'error';
            }
            exit();
        }
        if ($request == 'createinvoice') {
            usleep( 50000 );
            $result=$this->generate_invoice_without_notices($order_id);
            echo $result;
            exit();
        }
        if ($request == 'checkInvoiceStatus') {
            usleep( 50000 );
            $invoce_status = $this->getInvoiceStatusFromEFD($order_id);
            echo $invoce_status;
            exit();
        }
        if ($request == 'checkApiStatus') {
            $api_status = $this->getApiStatus();
            echo $api_status;
            exit();
        }
        exit();
    }

    public function wooefd_load_admin_scripts(){
        wp_register_style('woo-efactuurdirect', plugins_url( 'css/plugin.css', __FILE__ ));
        wp_enqueue_style('woo-efactuurdirect');
        wp_register_style('woo-efactuurdirect-glicons', plugins_url( 'glicons/css/font-awesome.min.css', __FILE__ ));
        wp_enqueue_style('woo-efactuurdirect-glicons');
    }

    public function wooefd_add_listing_actions($order){
	    global $woocommerce;        
        if (version_compare($woocommerce->version, '3.3', '>=')) {
			$button_class="a_button"; 
		}else{
			$button_class="button"; 
		}
		$auto_invoice = $this->settings['auto_invoice'];
        $order_status = $order->get_status();
        $order_id      = $this->addedFunction->getActualOrderId($order);
        $invoice_id   = $this->addedFunction->getActualInvoiceId($order);
    	if(!$invoice_id){
            $invoice_id= $this->addedFunction->getInvoiceIdByOrderIDFromDb($order_id);
            if($invoice_id)add_post_meta($order_id, 'efactuurdirect_invoice_id', $invoice_id, true);
        }
    	if ($auto_invoice !== 'manually' && $order_status == $auto_invoice && !$invoice_id) {
			$getOrderData 		= $this->addedFunction->getOrderData($order);
			$getPluginStartData = $this->addedFunction->getPluginStartData();
			if($getPluginStartData < $getOrderData){
					$this->addedFunction->insertUpdateEfdApiError($order_id,0);
					$this->generate_invoice($order);
			}
			
        }
    	$invoice_id    = $this->addedFunction->getActualInvoiceId($order);
        $invoce_status = $this->addedFunction->getInvoiceStatusFromDB($order_id,$invoice_id);
        $efd_api_error = $this->addedFunction->getEfdApiErrorStatus($order_id);
    	if ($invoice_id && $invoce_status == 1) {
            ?>
            <a href="javascript:void(0);" class="<?php echo esc_html($button_class);?> tips proportion"  alt="<?php
            esc_attr_e('Download', 'woo-efactuurdirect');
            ?>" onClick="SendAjaxReqest('download', '<?php echo esc_html($order->get_order_number()); ?>', '<?php echo wp_create_nonce( 'download_'.esc_html($order->get_order_number()) )?>');" data-tip="<?php echo esc_html(__('Download', 'woo-efactuurdirect')); ?>">
				<i id="downOrd_<?php echo esc_html($order_id) ?>" class="fa fa-download fa_down"></i>
			</a>
               <?php
		} elseif ($invoice_id && $invoce_status == 0) {
               ?>
            <a href="javascript:void(0);" class="<?php echo $button_class;?> tips proportion"  alt="<?php
            esc_attr_e('Download', 'woo-efactuurdirect');
               ?>" onClick="SendAjaxReqest('download', '<?php echo esc_html($order->get_order_number()); ?>', '<?php echo wp_create_nonce( 'download_'.esc_html($order->get_order_number()) )?>');" data-tip="<?php echo esc_html(__('Download', 'woo-efactuurdirect')); ?>"><i id="downOrd_<?php echo esc_html($order_id) ?>" class="fa fa-spin  fa-refresh fa_down wait_response"></i></a>
               <?php
		} elseif (isset($this->settings['auto_invoice']) && $this->settings['auto_invoice'] == 'manually'&&$efd_api_error==0) {
               ?>
            <a href="javascript:void(0);" class="<?php echo $button_class;?> tips proportion"  alt="<?php
            esc_attr_e('Invoice', 'woo-efactuurdirect');
               ?>" onClick="SendAjaxReqest('createinvoice', '<?php echo esc_html($order->get_order_number()); ?>', '<?php echo wp_create_nonce( 'createinvoice_'.esc_html($order->get_order_number()) )?>');" data-tip="<?php
               echo $this->addedFunction->sendEfdaction()[$this->settings['efactuurdirect_invoice_option']];
               ?>"><i id="createOrd_<?php echo esc_html($order_id) ?>" class="fa fa-file-pdf-o fa_inv"></i></a>
               <?php
		}elseif($efd_api_error!=0){
              ?>
            <a href="javascript:void(0);" class="<?php echo $button_class;?> tips proportion"  alt="<?php
            esc_attr_e('Invoice', 'woo-efactuurdirect');
               ?>" onClick="SendAjaxReqest('createinvoice', '<?php echo esc_html($order->get_order_number()); ?>', '<?php echo wp_create_nonce( 'createinvoice_'.esc_html($order->get_order_number()) )?>');" data-tip="<?php
               echo _e('Invoice is not created, click to retry.', 'woo-efactuurdirect');
               //echo _e($this->addedFunction->sendEfdaction()[$this->settings['efactuurdirect_invoice_option']], 'woo-efactuurdirect');
               ?>"><i id="createOrd_<?php echo esc_html($order_id) ?>" class="fa fa-exclamation-circle"></i></a>
               <?php
        }
	}

	public static function log($message){
		if (self::$log_enabled) {
			if (empty(self::$log)) {
				self::$log = new WC_Logger();
			}
			self::$log->add('woo-efactuurdirect', $message);
		}
	}

	function load_api_connector(){
        if ($this->efactuurdirect_api !== false) {
			return $this->efactuurdirect_api;
        }
      
        
        $adapter='curl';
        if($this->settings['connection']=='soket'){
            $adapter='soket';
        }
        
    	if (isset($this->settings['efactuurdirect_user']) && $this->settings['efactuurdirect_user'] && isset($this->settings['efactuurdirect_usubdomain']) && $this->settings['efactuurdirect_usubdomain']
			&& isset($this->settings['efactuurdirect_apikey']) && $this->settings['efactuurdirect_apikey']) {
				include_once("EfdApiToolkit.php");
				try {
					$user_subdomain=$this->getUserSubdomainFromRoute($this->settings['efactuurdirect_usubdomain']);
                    if($adapter=='curl'){
                        $this->efactuurdirect_api = new EfdApiToolkit($user_subdomain, $this->settings['efactuurdirect_user'], html_entity_decode($this->settings['efactuurdirect_apikey']));   /* Required, object, Simple of api connection */
                    }else{
                        $this->efactuurdirect_api = new EfdApiToolkit($user_subdomain, $this->settings['efactuurdirect_user'], html_entity_decode($this->settings['efactuurdirect_apikey']),'soket');   /* Required, object, Simple of api connection */
                    }
                    return $this->efactuurdirect_api;
				} catch (Exception $e) {
					$this->log("Can not find EFD-api library: ".$e->getMessage());
					return false;
				}
		}
	}

	function generate_invoice_action($actions){
		global $theorder;
		global $woocommerce;
		$order_id = $this->addedFunction->getActualInvoiceId($theorder);
		if (!$order_id) {
			$action_text                       = $this->addedFunction->sendEfdaction()[$this->settings['efactuurdirect_invoice_option']];
			$actions['efactuurdirect_invoice'] = $action_text;
		}
		return $actions;
	}

    function invoice_woo_order_id_efactuurdirect($order_id){   
		$order = new WC_Order( $order_id );
        $invoice_id =  $this->addedFunction->getActualInvoiceId($order);
        if(!$invoice_id) {
            $invoice_id= $this->addedFunction->getInvoiceIdByOrderIDFromDb($order_id);
            if($invoice_id)add_post_meta($order_id, 'efactuurdirect_invoice_id', $invoice_id, true);
        }
        if ($invoice_id) {
                try {
                    $invoice     = $this->efactuurdirect_api->getInvoiceByID($invoice_id);
                    usleep( 50000 );
                } catch (Exception $exc) {
                    $invoice     = false;
                }
                if ($invoice) {
                    return $invoice;
                }
            
        }
        return false;
    }

    function output_efactuurdirectstatus($order){
        $order_id   = $this->addedFunction->getActualOrderId($order);
        $auto_invoice = $this->settings['auto_invoice'];
        $invoice_id   = $this->addedFunction->getActualInvoiceId($order);
        $efd_api_error = $this->addedFunction->getEfdApiErrorStatus($order_id);
        if(!$invoice_id){
            $invoice_id= $this->addedFunction->getInvoiceIdByOrderIDFromDb($order_id);
            if($invoice_id)add_post_meta($order_id, 'efactuurdirect_invoice_id', $invoice_id, true);
        }
        $order_status = $order->get_status();
        if ($auto_invoice !== 'manually' && $order_status == $auto_invoice && !$invoice_id && $efd_api_error!=0) {
                $this->generate_invoice($order);
         }
         
        $invoice_id = $this->addedFunction->getActualInvoiceId($order);
        $order_id   = $this->addedFunction->getActualOrderId($order);
        $invoice       = $this->invoice_woo_order_id_efactuurdirect($order_id);
        $invoce_status = $this->addedFunction->getInvoiceStatusFromDB($order_id,$invoice_id);
        if($invoce_status==0){
            $tmp_total=0;
            foreach ($order->get_items() as $key=>$item) {
                $tmp_total=$tmp_total+round( $item->get_total_tax(),2);
            }
            $preview_tax=round($tmp_total+(float)$order->get_shipping_tax(),2);
			
			
            if($preview_tax!=round($order->get_total_tax(),2)){
                $order->add_order_note(__('Warning: The order total is not identical to the invoice total.', 'woo-efactuurdirect'));
            }
        }
           
           $show_string=$this->getApiStatus();
           $show_string= json_decode($show_string,1);
           $show_string=$show_string['edit_order'];
        ?>
        <p class="form-field form-field-wide wc-customer-user"><span><?php _e('Efactuurdirect invoice:', 'woo-efactuurdirect'); ?></span></br></p>
        <p class="form-field form-field-wide wc-customer-user" id="efd_api_status_order_list"><?php echo $show_string; ?></p>
        <?php
        if ($invoice_id) {
            if ($invoice && !isset($invoice['error'])) {
                $array_option   = $this->addedFunction->sendEfdaction();
                $invoice_option = $this->settings['efactuurdirect_invoice_option'];
                $action_text    = $array_option[$invoice_option];
                $show_status    = __('Status:', 'woo-efactuurdirect');
                if (isset($invoice['statuses'])) {
                    foreach ($invoice['statuses']as $status) {
                        $show_status = $show_status.' <span style="font-weight: bold; color: '.$this->addedFunction->setColor($status['status_color']).'">'.$status['status'].'</span>';
                    }
                }
                echo '<p class="form-field form-field-wide wc-customer-user">';
                if (isset($invoice['id']) && $invoce_status == 1) {
                    echo '<div class="show_invoice_status"><span class="headercomment">'.$show_status.'</span>';
                    ?>
                    <a href="javascript:void(0);" class="button tips proportion"  style="margin-left: 5px;margin-top: -5px;"alt="<?php
                    esc_attr_e('Invoice', 'woo-efactuurdirect');
                    ?>" onClick="SendAjaxReqest('download', '<?php echo esc_html($order_id) ?>', '<?php echo wp_create_nonce( 'download_'.esc_html($order->get_order_number()) )?>');" data-tip="<?php echo esc_html(__('Download', 'woo-efactuurdirect')); ?>"><i  id="downOrd_<?php echo esc_html($order->get_order_number()); ?>" class="fa fa-download fa_down"></i></a></div></p>
                       <?php
                   } elseif (isset($invoice['id']) && $invoce_status == 0) {
                       echo '<div class="show_invoice_status"><span class="headercomment">'.esc_html(__('Preparing invoice ', 'woo-efactuurdirect')).'</span>';
                       ?>
                    <a href="javascript:void(0);" class="button tips proportion" style="margin-left: 5px;margin-top: -5px;"  alt="<?php
                    esc_attr_e('Download', 'woo-efactuurdirect');
                       ?>" onClick="SendAjaxReqest('download', '<?php echo esc_html($order->get_order_number()); ?>', '<?php echo wp_create_nonce( 'download_'.esc_html($order->get_order_number()) )?>');" data-tip="<?php echo esc_html(__('Download', 'woo-efactuurdirect')); ?>"><i id="downOrd_<?php echo esc_html($order_id) ?>" class="fa fa-spin  fa-refresh fa_down wait_response orderpage"></i></a></div></p>
                       <?php
                   } else {
                       echo '<i>'.esc_html(__('Concept', 'woo-efactuurdirect')).'</i>';
                   }
               } else {
                   _e('The invoice could not be loaded. Please check if the invoice is not deleted in efactuurdirect and the API connection is working.');
               }
        } else {
            if (isset($this->settings['auto_invoice']) && $this->settings['auto_invoice'] == 'manually'&&$efd_api_error==0):
                ?>
                <p class="form-field form-field-wide wc-customer-user"><div>
                    <?php $this->addedFunction->sendEfdaction()[$this->settings['efactuurdirect_invoice_option']]; ?>
                    <a href="javascript:void(0);" class="button tips proportion" style="margin-left: 5px;margin-top: -5px;" alt="<?php esc_attr_e('Invoice', 'woo-efactuurdirect'); ?>" onClick="SendAjaxReqest('createinvoice', '<?php echo esc_html($order->get_order_number()); ?>', '<?php echo wp_create_nonce( 'createinvoice_'.esc_html($order->get_order_number()) )?>');" data-tip="<?php
                    echo $this->addedFunction->sendEfdaction()[$this->settings['efactuurdirect_invoice_option']];
                    ?>"><i id="createOrd_<?php echo esc_html($order_id) ?>" class="fa fa-file-pdf-o fa_inv"></i></a></div></p>
                <?php
            elseif($efd_api_error!=0):
                ?>
                <p class="form-field form-field-wide wc-customer-user"><div>
                    <?php
                    _e('Invoice is not created, click to retry.', 'woo-efactuurdirect');?>
                    <a href="javascript:void(0);" class="button tips proportion" style="margin-left: 5px;margin-top: -5px;" alt="<?php esc_attr_e('Invoice_error', 'woo-efactuurdirect'); ?>" onClick="SendAjaxReqest('createinvoice', '<?php echo esc_html($order->get_order_number()); ?>', '<?php echo wp_create_nonce( 'createinvoice_'.esc_html($order->get_order_number()) )?>');" data-tip="<?php
                    echo $this->addedFunction->sendEfdaction()[$this->settings['efactuurdirect_invoice_option']];
                    ?>"><i id="createOrd_<?php echo esc_html($order_id) ?>" class="fa fa-exclamation-circle fa_inv"></i></a></div></p>
                <?php
            else:
                echo esc_html(__('The invoice is not created', 'woo-efactuurdirect'));
            endif;
        }
        ?>
        </p>
        <div class="clear"></div>
        <?php
	}

    function init_form_fields(){
        $this->form_fields = array(
            'efactuurdirect_user'   => array(
                'title'             => __('Username', 'woo-efactuurdirect'),
                'description'       => __('Your username in efactuurdirect', 'woo-efactuurdirect'),
                'type'              => 'text'
            ),
            'efactuurdirect_usubdomain' => array(
                'title'             => __('Subdomain', 'woo-efactuurdirect'),
                'description'       => __('Your efactuurdirect account subdomain: https://<b>[subdomain]</b>.efactuurdirect.nl', 'woo-efactuurdirect'),
                'type'              => 'text'
            ),
            'efactuurdirect_apikey' => array(
                'title'             => __('API key', 'woo-efactuurdirect'),
                'description'       => __('Your efactuurdirect API key', 'woo-efactuurdirect'),
                'type'              => 'text',
            ),
            'auto_invoice' => array(
                'title'             => __('Automatically create invoice', 'woo-efactuurdirect'),
                'description'       => __('Select at which status an invoice will be created', 'woo-efactuurdirect'),
                'type'              => 'select',
                'desc_tip'          => true,
                'default'           => 'none',
                'options'           => $this->addedFunction->get_woocomerce_order_statuses()
            ),
			'efactuurdirect_invoice_option' => array(
                'title'             => __('Invoice type', 'woo-efactuurdirect'),
                'description'       => __('Select the invoice type to be used in efactuurdirect', 'woo-efactuurdirect'),
                'type'              => 'select',
                'desc_tip'          => true,
                'default'           => 'createconcept',
                'options'           => $this->addedFunction->sendEfdaction()
            ),
			'efactuurdirect_payment_option' => array(
                'title'             => __('Payment option', 'woo-efactuurdirect'),
                'description'       => __('Select payment method for already paid invoices', 'woo-efactuurdirect'),
                'type'              => 'select',
                'desc_tip'          => true,
                'options'           => $this->getPaymentMethods()
            ),
			'add_additional_info_for_order'          => array(
                'title'             => __('Add custom fields for invoice remark and textline at checkout', 'woo-efactuurdirect'),
                'type'              => 'select',
                'desc_tip'          => true,
                'options'           => array(
                    '1' 			=> __('Yes', 'woo-efactuurdirect'),
                    '0'            	=> __('No', 'woo-efactuurdirect')),
                'default'           => '0',
            ),
            'invoice_date'          => array(
                'title'             => __('Invoice date', 'woo-efactuurdirect'),
                'description'       => __('Select the invoice date to be used', 'woo-efactuurdirect'),
                'type'              => 'select',
                'desc_tip'          => true,
                'options'           => array(
                    'invoice_generate_date' => __('Invoice send date', 'woo-efactuurdirect'),
                    'order_date'            => __('Order date', 'woo-efactuurdirect')),
                'default'           => 'invoice_generate_date',
            ),
            'uom_id' => array(
                'title'             => __('Product unit of measure', 'woo-efactuurdirect'),
                'description'       => __('Select the unit of measure to be used for products', 'woo-efactuurdirect'),
                'type' => 'select',
                'desc_tip'          => true,
                'options'           => $this->get_uom()
            ),
            'zero_tax' => array(
                'title'             => __('Zero rate tax', 'woo-efactuurdirect'),
                'description'       => __('Select the vat value to be used in efactuurdirect for zero rate tax', 'woo-efactuurdirect'),
                'type'              => 'select',
                'desc_tip'          => true,
                'options'           => $this->get_tax_zero()
            ),
            'connection'          => array(
                'title'             => __('API connection method', 'woo-efactuurdirect'),
                'type'              => 'select',
                'desc_tip'          => true,
                'options'           => array(
                    'curl'          => __('cURL', 'woo-efactuurdirect'),
                    'soket'         => __('Socket', 'woo-efactuurdirect')),
                'default'           => 'curl',
            ),
        );
    }

    function generate_hidden_html($k, $v){
        ?>
        <tr style="display:none;">
            <th scope="row">Hidden</th>
            <td>
                <input type="hidden" name="woo-efactuurdirect_<?php echo esc_html($k); ?>" id="woo-efactuurdirect_<?php echo esc_html($k); ?>" value="<?php echo esc_html($this->get_option($k)); ?>">
            </td>
        </tr>
        <?php
    }

    function admin_options(){
        echo '<h3>' . esc_html($this->method_title) . '</h3>';
        echo isset($this->method_description) ? wpautop($this->method_description) : '';
        echo '<p>'.esc_html(__('Connection status:', 'woo-efactuurdirect')).' ';
        
        $check_api = $this->check_api_credential();
        if (!isset($check_api['error'])) {
            echo '<span style="color:#009900;"><b>'.esc_html(__('OK', 'woo-efactuurdirect')).'</b></span><br/>';
        } else {
            if ($check_api['error'] == 'Invalid API key') {
                echo '<span style="color:#FF0000;"><b>'.esc_html(__('ERROR (Invalid username or API key)', 'woo-efactuurdirect')).'</b></span></p>';
            }
            if ($check_api['error'] == 'Please check domain') {
                echo '<span style="color:#FF0000;"><b>'.esc_html(__('ERROR (Connection error)', 'woo-efactuurdirect')).'</b></span></p>';
            }
        }
        $round_tax_at_subtotal= get_option('woocommerce_tax_round_at_subtotal');
        if($round_tax_at_subtotal!='yes'){
            echo'<p><span style="color:#FF0000;"><b>'.esc_html(__('Please enable: "Round tax at subtotal level, instead of rounding per line" at Tax setting to use correct vat calculation for invoices.' , 'woo-efactuurdirect')).'</b></span></p> ';
        }
        echo '<table class="form-table">';
        $this->generate_settings_html();
        echo '</table>';
        echo '<div><input type="hidden" name="section" value="'.$this->id.'" /></div>';
    }

    function get_tax(){
        $default = array(
            '' => __('Default', 'woo-efactuurdirect'),
        );
        if (!$this->load_api_connector()) {
            return $default;
        }
        try {
            $results = $this->efactuurdirect_api->getTaxesList();
        } catch (Exception $e) {
            $results = false;
        }
        if (!$results || isset($results['error'])) {
            return $default;
        } else {
            $tmp_tax_array = array();
            $tax_array     = array();
            $tax_value     = array();
            $name_tax_zero = array();
            foreach ($results as $result) {
                if ($result['outcoming'] == 1 && $result['active'] == 1) {
                    if ($result['lowhigh'] == 2) {
                        if ($result['value'] > 0) {
                            $tax_array[$result['id']] = $result['label'];
                        }
                    } else {
                        if ($result['value'] > 0) {
                            $tmp_tax_array[$result['id']] = $result['label'];
                        }
                    }
                    $tax_value[$result['id']]     = $result['value'];
                    if ($result['value'] == 0) $name_tax_zero[$result['id']] = $result['label'];
                }
            }
            foreach ($tmp_tax_array as $key => $tax) {
                $tax_array[$key] = $tax;
            }
            set_site_transient('efactuurdirect_tax', $tax_array, 3 * 60 * 60);
            set_site_transient('efactuurdirect_tax_full', $tax_value, 3 * 60 * 60);
            set_site_transient('name_tax_zero', $name_tax_zero, 3 * 60 * 60);
            return $tax_array;
        }
    }

    function get_tax_zero(){
        $efactuurdirect_tax = get_site_transient('name_tax_zero');
        if (!$efactuurdirect_tax) {
            $this->get_tax();
            $efactuurdirect_tax = get_site_transient('name_tax_zero');
        }
        if (!is_array($efactuurdirect_tax)) $efactuurdirect_tax = array('' => __('Default', 'woo-efactuurdirect'));
        return($efactuurdirect_tax);
    }

    function get_uom(){
        $default = array('' => __('Default', 'woo-efactuurdirect'));
        if (!$this->load_api_connector()) {
            return $default;
        }

        $uomList = get_site_transient("efactuurdirect_uom");
        if($uomList){
            return $uomList;
        } 

        try {
            
            $results = $this->efactuurdirect_api->getUomList();
        } catch (Exception $e) {
            $results = false;
        }
        if (!$results || isset($results['error'])) {
            return $default;
        } else {
            $uom_array = array();
			foreach ($results as $result) {
                $uom_array[$result['id']] = $result['name'];
            }
            set_site_transient('efactuurdirect_uom', $uom_array, 3 * 60 * 60);
            return $uom_array;
        }
    }
	
	 function getPaymentMethods(){
        

        $paymentMethods = get_site_transient("efactuurdirect_paymeth");
        if($paymentMethods){
            return $paymentMethods;
        }
        
        $default = array('' => __('Default', 'woo-efactuurdirect'));
        if (!$this->load_api_connector()) {
            return $default;
        }

        try {
            $results = $this->efactuurdirect_api->getPaymentMethods();
        } catch (Exception $e) {
            $results = false;
        }
        if (!$results || isset($results['error'])) {
            return $default;
        } else {
            $paymeth_array = array();
			foreach ($results as $id=>$result) {
                $paymeth_array[$id] = $result;
            }
            set_site_transient('efactuurdirect_paymeth', $paymeth_array, 3 * 60 * 60);
            return $paymeth_array;
        }
    }

    public function efactuurdirect_contact_create_update($order){
        usleep( 50000 );
        if (!$this->load_api_connector()) {
            return false;
        }
        $order_id = $this->addedFunction->getActualOrderId($order);
        $contact_details = $this->addedFunction->populateContactDetailsFromBilingDate($order);
		$customer_id              = $this->addedFunction->getCustomerIdFromOrder($order);
        $efactuurdirect_client_id = 0;//$this->getClientIdFromLocalDB($customer_id);
         
        if ($efactuurdirect_client_id == 0) {
            $efactuurdirect_client = $this->checkEfdIdByContactDetails($contact_details,$order_id);
            $efactuurdirect_client_id = $efactuurdirect_client['id'];
            
            if( $efactuurdirect_client['error']==""){
                $efactuurrdirect_error='';
            }else{
                $efactuurrdirect_error=$efactuurdirect_client['error'];
            }
        }

		if ($customer_id != 0 && $efactuurrdirect_error=='') {
            $efactuurdirect_client_id = $this->create_update_registred_wooefactuurdirect_client(get_userdata($customer_id), get_user_meta($customer_id), $customer_id, $efactuurdirect_client_id,$contact_details);
        } elseif ($customer_id == 0 && $efactuurdirect_client_id == 0 &&$efactuurrdirect_error=='' ) {
            $this->log('Created Efactuurdirect contact for '.$contact_details['firstname'].' '.$contact_details['lastname']);
            $efactuurdirect_client_id = $this->create_unregistred_efactuurdirect_client($contact_details);
        } else {
            if($efactuurrdirect_error=='')
                $efactuurdirect_id = $this->create_update_registred_wooefactuurdirect_client(get_userdata($customer_id), get_user_meta($customer_id), $customer_id, $efactuurdirect_client_id,$contact_details);
        }
         usleep( 50000 );
        if((isset($efactuurdirect_client_id['error']))||$efactuurrdirect_error!=''){
            if(isset($efactuurdirect_client_id['error'])){
                $error=print_r($efactuurdirect_client_id['error'],1);
                $error.=$efactuurrdirect_error;
            }else{
               $error=$efactuurrdirect_error;
            }            
            if($message=='Error can not create User next errors is: Api error.'){
                $order->add_order_note(__('Efactuurdirect authentication error.', 'woo-efactuurdirect'));
            }else{                
                $order->add_order_note('Error can not create User next errors is: '.$error);
            }
            
        }
        usleep( 50000 );
        return $efactuurdirect_client_id;
    }

    public function create_unregistred_efactuurdirect_client($contact_details){
        usleep( 50000 );
        $efactuurdirect_id = false;
        $user_data         = array(
            'username'      => $contact_details['firstname'].' '.$contact_details['lastname'],
            'firstname'     => $contact_details['firstname'],
            'lastname'      => $contact_details['lastname'],
            'email'         => $contact_details['email'],
            'city'          => $contact_details['city'],
            'housenr'       => $contact_details['address2'],
            'country'       => $contact_details['country'],
            'zip'           => $contact_details['zipcode'],
            'street'        => $contact_details['address1'],
            'phone'         => $contact_details['phone'],
        );
        if ($user_data['housenr'] == '') {
            $full_adress = $this->splitAdress($user_data['street']);
            $user_data['street']=$full_adress['street'];
            $user_data['housenr']=$full_adress['housenumber'];

        }
        if (isset($contact_details['company_name'])&&$contact_details['company_name']!='') $user_data['company'] = $contact_details['company_name'];
        if (!$this->load_api_connector()) {
            return false;
        } else {
            $user_data['send_method'] = 1;
            try{
                $efactuurdirect_id        = $this->efactuurdirect_api->addContact($user_data);
                usleep( 50000 );
            }
            catch (Exception $e) {
                return array('error'=>'Add contact is failed.');
                return;
            }
            if (isset($efactuurdirect_id['status']) && $efactuurdirect_id['status'] == 'added'){
              $efactuurdirect_id        = $efactuurdirect_id['id'];
            } else{
                $error_list='';
                 if(is_array($efactuurdirect_id['error'])){
                foreach ($efactuurdirect_id['error'] as $error){
                $error_list.=  $error['value'];}
                }else{
                    $error_list.=$efactuurdirect_id['error'];
                }
                return array('error'=>$error_list);
            }
        }

        
        return $efactuurdirect_id;
    }

    public function checkEfdIdByContactDetails($contact_details,$order_id){
        usleep( 50000 );
        $efactuurdirect_client['error']='';
        $prepare_request = array(
            'firstname'     => $contact_details['firstname'],
            'lastname'      => $contact_details['lastname'],
            'email'         => $contact_details['email'],
            'city'          => $contact_details['city'],
            'country'       => $contact_details['country'],
            'zip'           => $contact_details['zipcode'],
            'del'           => 0,
        );
        
        if (!$this->load_api_connector()) {
            $result['error']='Api error.';
			} else {
                try{
                    $result = $this->efactuurdirect_api->searchContact($prepare_request, 0);
                    usleep( 50000 );
                }
                catch (Exception $e){
                $this->addedFunction->insertUpdateEfdApiError($order_id,'1');
                $result['error']='Api error.';
				}
            }

        if (isset($result['error'])) {
			if($result['error']=='No contacts found for this criteria'){
            $efactuurdirect_client['error'] = "";}
            else{
              $efactuurdirect_client['error']= $result['error'];
            }
            $efactuurdirect_client['id']=0;
        } else {
            $result = $result['results'];
            if (count($result) > 0) {
                $efactuurdirect_client['id'] = $result[0]['id'];
            } else {
                $efactuurdirect_client['id'] = 0;
                $efactuurdirect_client['error']='';
            }
        }
        return $efactuurdirect_client;
    }

    function getClientIdFromLocalDB($cusromer_id){
        $efactuurdirect_id = 0;
        global $wpdb;
        $table_name        = $wpdb->prefix."efactuurdirect_links";
        $comment           = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE type= 'client' AND wp_id = %d", $cusromer_id), ARRAY_A);
        if (isset($comment['efactuurdirect_id'])) $efactuurdirect_id = $comment['efactuurdirect_id'];
        return($efactuurdirect_id);
    }

    function create_update_registred_wooefactuurdirect_client($client_data, $client_details, $customer_id, $efactuurdirect_id = 0,$contact_details)
    {   
       usleep( 50000 );
        $busy = get_site_transient('creation_is_busy_client');
        while($busy){ 
            sleep (1);
            $busy = get_site_transient('creation_is_busy_client');
        }
        set_site_transient('creation_is_busy_client',true,1);
        $user_data = array(
            'username' => $client_details['nickname'][0],
            'firstname' => $contact_details['firstname'],
            'lastname' => $contact_details['lastname'],
            'email' => $contact_details['email'],
            'city' => $contact_details['city'],
            'country' => $contact_details['country'],
            'zip' => $contact_details['zipcode'],
            'street' => $contact_details['address1'],
			'housenr'=>$contact_details['address2'],
        );
        
        if($user_data['firstname']==''){
            $user_data['firstname']=$client_details[''];
        }
        
        if ($user_data['housenr']==''&&isset($client_details['billing_address_2'][0]) && $client_details['billing_address_2'][0] != '') {
             $user_data['housenr'] = $client_details['billing_address_2'][0];
        }

        if (isset($client_details['billing_company'][0])&& $client_details['billing_company'][0] != '') {
            $user_data['company'] = $client_details['billing_company'][0];
        }

        if(!isset($user_data['housenr'])){
            $full_adress = $this->splitAdress($client_details['billing_address_1'][0]);
            $user_data['street']=$full_adress['street'];
            $user_data['housenr']=$full_adress['housenumber'];
        }

        if ($efactuurdirect_id == 0) {
            if (!$this->load_api_connector()) {
                return false;
            } else {
                $user_data['send_method'] = 1;
                try{
                    $efactuurdirect_id        = $this->efactuurdirect_api->addContact($user_data);
                    usleep( 50000 );
                }
                catch (Exception $e) {
                    $this->log("Error saving invoice");
                    do_action('woo-efactuurdirect_generate_invoice_error', $order, 'The generated invoice could not be saved');
                    return;
                }
                if (isset($efactuurdirect_id['status']) && $efactuurdirect_id['status'] == 'added') {
                    $efactuurdirect_id = $efactuurdirect_id['id'];
                }else{
                    $error_list='';
                    foreach ($efactuurdirect_id['error'] as $error){
                        $error_list.=  $error['value'];
                    }
                    return array('error'=>$error_list);
                }

                if ($customer_id != 0) {
                    $this->addedFunction->addNewLinkWpIdEfdId($customer_id, $efactuurdirect_id);
                }
            }
        } else {
            if (!$this->load_api_connector()) {
                return false;
            } else {
                $user_data['id']   = $efactuurdirect_id;
                $efactuurdirect_id = $this->efactuurdirect_api->updateContact($user_data);
                usleep( 50000 );
                if (isset($efactuurdirect_id['status']) && $efactuurdirect_id['status'] == 'updated') {
                    $efactuurdirect_id = $efactuurdirect_id['id'];
                }else{
                    $error_list='';
                    foreach ($efactuurdirect_id['error'] as $error){
                        $error_list.=  $error['value'];
                    }
                    return array('error'=>$error_list);
                }
            }
        }
        return $efactuurdirect_id;
    }

    function check_api_credential(){
        $result = false;
        if (!$this->load_api_connector()) {
            return false;
        } else {
            if (method_exists($this->efactuurdirect_api, 'getCheckAccess')) {
                try {
                    $result = $this->efactuurdirect_api->getCheckAccess();
                    usleep( 50000 );
                } catch (Exception $e) {
                    $result['error'] = 'Please check domain';
                    return $result;
                }

                if (isset($result['error'])) {
                    return $result;
                } else {
                    return true;
                }
            } else {
                $result['error'] = 'Please check domain';
                return $result;
            }
        }
    }

    function generate_invoice_without_notices($order_id){
        usleep( 50000 ); 
        $this->addedFunction->insertUpdateEfdApiError($order_id,0);
        $order = new WC_Order($order_id);
		if ($order) {
            $response=$this->generate_invoice($order);
        }
		return $response;
    }

    function get_order_items($order){
        global $woocommerce;
		global $product;
        $wc_tax      = new WC_Tax();
        $items              = array();
        $taxes_array        = get_site_transient('efactuurdirect_tax_full');
        $taxes_array_values = get_site_transient('efactuurdirect_tax_full');
        $zero_tax           = get_site_transient('name_tax_zero');
        
		if (!$taxes_array || !$zero_tax) {
            $this->get_tax();
            $taxes_array        = get_site_transient('efactuurdirect_tax_full');
            $taxes_array_values = get_site_transient('efactuurdirect_tax_full');
            $zero_tax           = get_site_transient('name_tax_zero');
        }
        
		if(!$this->settings['zero_tax']){
            $this->settings['zero_tax']=key($zero_tax);
        }
		
		$custom_fields = get_post_custom();
		
		foreach ($order->get_items() as $item) {
		
			$product_name = $item->get_name();
			$product_data = $item->get_data();
			
			$_product    = apply_filters('woocommerce_order_item_product', $order->get_product_from_item($item), $item);
			$add_name_to_description = $_product->get_attribute( 'add_name_to_description' );
            
            if($add_name_to_description){
				$contact_details        = $this->addedFunction->populateContactDetailsFromBilingDate($order);
				$additional_description = $contact_details['firstname'].' '.$contact_details['lastname'];
            }
            
            $description=str_replace('&#8211;', '-',$item['name']);
            $tax_percent = 0.0;
            $line_tax    = 0.0;
            $line_tax    = round($order->get_line_tax($item), 2);
            $tax_percent = $this->get_rounded_tax_rate(100.0 * ($line_tax / $order->get_line_total($item)), $order);
            
            if ($tax_percent != 0) {
                $get_vat_id = array_search($tax_percent, $taxes_array_values);
            } else {
                $get_vat_id = $this->settings['zero_tax'];
            }

            if ($get_vat_id) {
                $tax_rate_id = $get_vat_id;
            } else {
                $this->add_admin_notice(sprintf(__('Tax rate of %.2f percent is not available in efactuurdirect', 'woo-efactuurdirect'), $tax_percent), true);
                return false;
            }

            $uom_array = get_site_transient('efactuurdirect_uom');
            
            if (!$uom_array) {
                $get_uom_array = $this->get_uom();
            }

            if (!isset($this->settings['uom_id']) || $this->settings['uom_id'] == 0 || !isset($uom_array[$this->settings['uom_id']])) {
                $tmp                   = array_keys($uom_array)[0];
                $efactuurdirect_uom_id = $tmp;
            } else {
                if(!$this->settings['uom_id']){
                    $this->settings['uom_id']=key($zero_tax);
                }
                $efactuurdirect_uom_id = $this->settings['uom_id'];
            }

            $taxes_array = get_site_transient('efactuurdirect_tax');
            
            if (!$taxes_array) {
                $taxes_array = $this->get_tax();
            }

            $woocommerce_calc_taxes = get_option('woocommerce_calc_taxes');
            
            if ($woocommerce_calc_taxes == 'yes') {
                if (!isset($this->settings['tax_id']) || $this->settings['tax_id'] == 0 || !isset($taxes_array[$this->settings['tax_id']])) {
                    $tmp                      = array_keys($taxes_array)[0];
                    $efactuurdirect_taxe_rate = $tmp;
                } else {
                    $efactuurdirect_taxe_rate = $this->settings['tax_id'];
                }
            } else {
                $efactuurdirect_taxe_rate = $zero_tax[$this->settings['zero_tax']];
                $get_vat_id               = $this->settings['zero_tax'];
            }
			
            if ($efactuurdirect_taxe_rate == '') {
                $this->log('Cannot find Efactuurdirect tax rate for '.$item['name']);
                $this->add_admin_notice(sprintf(__('Efactuurdirect error', 'woo-efactuurdirect'), $item['name']), true);
                do_action('woo-efactuurdirect_generate_invoice_error', $order, 'Cannot find tax rate for '.$item['name']);
                return false;
            }

            $woocommerce_prices_include_tax = get_option('woocommerce_prices_include_tax');
            $total = $order->get_line_total($item);
            $subtotal = ($order->get_item_subtotal($item))*$item['qty'];
            $discount_value=0;
            if($subtotal>$total){
                $discount_value=$subtotal-$total;
                $total=$subtotal;
            }
            if ($woocommerce_prices_include_tax == 'yes') {
                $prise = ($total + $line_tax) / (float) $item['qty'];
            } else {
                $prise = ($total) / (float) $item['qty'];
            }
            
            $items[] = [
                'quantity'          => $item['qty'],
                'name'              => $description,
                'price'             => $prise,
                'tax_id'            => $get_vat_id,
                'uom'               => $efactuurdirect_uom_id,
                'discount_type'     => 1,
                'discount_value'    => $discount_value];
			
		}
        $shiping_totals = 0;
        $shipping       = $order->get_items('shipping');
		
		foreach( $order->get_items( 'shipping' ) as $item_id => $shipping_item_obj ){
			$shipping_item_data = $shipping_item_obj->get_data();
			$get_total_tax=$shipping_item_data['total_tax'];
			$get_total=$shipping_item_data['total'];
		}
		
        if($get_total>0){
            $tax_percent = 0.0;
            $line_tax    = 0.0;
            $tax_percent = $this->get_rounded_tax_rate(100.0 * ($get_total_tax / $get_total), $order);

            if ($tax_percent != 0) {
                $get_vat_id = array_search($tax_percent, $taxes_array_values);
            } else {
                $get_vat_id = $this->settings['zero_tax'];
            }

            if ($get_vat_id) {
                $tax_rate_id = $get_vat_id;
            } else {
                $this->add_admin_notice(sprintf(__('Tax rate of %.2f percent is not available in efactuurdirect', 'woo-efactuurdirect'), $tax_percent), true);
                return false;
            }
        
			$uom_array = get_site_transient('efactuurdirect_uom');
            if (!$uom_array) {
                $get_uom_array = $this->get_uom();
            }
			
            if (!isset($this->settings['uom_id']) || $this->settings['uom_id'] == 0 || !isset($uom_array[$this->settings['uom_id']])) {
                $tmp                   = array_keys($uom_array)[0];
                $efactuurdirect_uom_id = $tmp;
            } else {
                if(!$this->settings['uom_id']){
                    $this->settings['uom_id']=key($zero_tax);
                }
				
				$efactuurdirect_uom_id = $this->settings['uom_id'];
			}
			
            $woocommerce_prices_include_tax = get_option('woocommerce_prices_include_tax');
            
			if ($woocommerce_prices_include_tax == 'yes') {
                $prise = ($get_total + $get_total_tax) ;
            } else {
                $prise = ($get_total);
            }

			$items[] = array('quantity' => 1,
				'name' => 'Verzendkosten',
				'price' => $prise,
				'tax_id' => $get_vat_id,
				'uom' => $efactuurdirect_uom_id,
				'discount_type'=>1,
				'discount_value'=>0);
			}
		return $items;
    }

    function generate_invoice($order){
        global $woocommerce;
        $busy = get_site_transient('creation_is_busy_g');
        while($busy){ 
            sleep (1);
            $busy = get_site_transient('creation_is_busy_g');
        }
        set_site_transient('creation_is_busy_g',true,1);
        
        usleep( 50000 );
        
        $order_id = $this->addedFunction->getActualOrderId($order);
        $this->log("Generating invoice for order ".$order_id);
        
        $invoice_id   = $this->addedFunction->getActualInvoiceId($order);
        usleep( 50000 );
        if(!$invoice_id){
            $invoice_id = $this->addedFunction->getInvoiceIdByOrderIDFromDb($order_id);
            if($invoice_id){
                add_post_meta($order_id, 'efactuurdirect_invoice_id', $invoice_id, true);
            }
        }

        if ($invoice_id) {
            $this->log("Invoice generation was broke: order already has efactuurdirect invoice");
             $this->add_admin_notice('Invoice generation was broke: order already has efactuurdirect invoice.', true);
            return;
        }

        if (!$this->load_api_connector()) {
            $this->add_admin_notice(__('Cannot connect to the efactuurdirect API', 'woo-efactuurdirect'), true);
            return;
        }

        $invoice = array();
        $invoice_date = $this->addedFunction->getDateToInvoice($order, $this->settings);
        if($invoice_date){
            $invoice['invoice_date'] = $invoice_date;
        }

        $invoice['currency']    = $this->addedFunction->getActualOrderCurency($order);
        $invoice['contact_id']  = $this->efactuurdirect_contact_create_update($order);
        $api_errors             =$this->addedFunction->getEfdApiErrorStatus($order_id);
        
        if ($api_errors==0){
        
            if (!$invoice['contact_id']) {
                $this->log("Cancel invoice generation: error finding or creating Efactuurdirect contact");
                $this->add_admin_notice('Cannot find or create Efactuurdirect contact. Please check your Efactuurdirect API settings.', true);
                do_action('woo-efactuurdirect_generate_invoice_error', $order, 'Cannot find or create Efactuurdirect contact');
                return;
            }

            $invoice                 = apply_filters('woo-efactuurdirect_invoice', $invoice, $order);
            $invoice['products']     = $this->get_order_items($order);
            $invoice['is_incl_tax']  = $this->addedFunction->getInvoiceInclExclTax();
		    $invoice['already_paid'] = 0;
		
		    if( $this->settings['efactuurdirect_invoice_option'] == 'create_concept_alredy_paid' || $this->settings['efactuurdirect_invoice_option'] == 'send_invoice_alredy_paid') {
			    $paymeth_array = get_site_transient('efactuurdirect_paymeth');
            
                if (!$paymeth_array) {
                    $paymeth_array = $this->getPaymentMethods();
                }

		        if (!isset($this->settings['efactuurdirect_payment_option']) || $this->settings['efactuurdirect_payment_option'] == 0 || !isset($paymeth_array[$this->settings['efactuurdirect_payment_option']])) {
                    $invoice['already_paid']	= array_keys($paymeth_array)[0];
				    $this->settings['efactuurdirect_payment_option'] = $invoice['already_paid'];
                }else{
				    $invoice['already_paid']=$this->settings['efactuurdirect_payment_option'];
			    } 
		    }
		
		    $invoice_textline = get_post_meta($order_id,'invoice_textline',true);
        
            if($invoice_textline != '' && $invoice_textline != ' '){
		        $invoice['products'][count($invoice['products'])] = array('is_comment_line'=> 1,'description'=>$invoice_textline);
		    }
		
		    $invoice_remark = get_post_meta($order_id,'invoice_remark',true);
		    if($invoice_remark != '' && $invoice_remark != ' '){
			    $invoice['invoice_remark'] = $invoice_remark;
		    }
	
            if(isset($invoice['contact_id']['error'])){
                $order->add_order_note('Invoice error:'.$invoice['contact_id']['error']);
            }else{

                $identical_number= base64_encode(home_url().';'.$invoice['contact_id'].';'.$order_id);
                
                $check_if_exist_invoice_for_this_request = $this->efactuurdirect_api->getInvoicesList(array('external_id'=>$identical_number),"0","1");
                usleep( 50000 );
                if(isset($check_if_exist_invoice_for_this_request['Code'])&&$check_if_exist_invoice_for_this_request['Code']==5){
                    $invoice['external_id']=$identical_number;
                }else{
                    $this->add_admin_notice("Error creating invoice. For this order invoice already created.".$check_if_exist_invoice_for_this_request['results']['0']['id'], true);
                    add_post_meta($order_id, 'efactuurdirect_invoice_id', $check_if_exist_invoice_for_this_request['results']['0']['id'], true);
                    $this->addedFunction->insertInvoiceIdWooEfd($check_if_exist_invoice_for_this_request['results']['0']['id'], $order_id);
                    return;
                }
				
                try 
				{
                    usleep( 50000 );
                    $saved_invoice = $this->efactuurdirect_api->addInvoice($invoice);
                
                } catch (Exception $e) {
                    $this->log("Error saving invoice");
                    $order->add_order_note(__('Invoice error: Error saving.', 'woo-efactuurdirect'));
                    do_action('woo-efactuurdirect_generate_invoice_error', $order, 'The generated invoice could not be saved');
                    return;
                }

                if (isset($saved_invoice['status']) && $saved_invoice['status'] == 'added'){
                    $this->addedFunction->insertInvoiceIdWooEfd($saved_invoice['id'], $order_id);
                    if ($this->settings['efactuurdirect_invoice_option'] == 'send_invoice' || $this->settings['efactuurdirect_invoice_option'] == 'send_invoice_alredy_paid') {
                        $busy = get_site_transient('creation_is_busy_sIN');
                        while($busy){ 
                            sleep (1);
                            $busy = get_site_transient('creation_is_busy_sIN');
                        }
                        set_site_transient('creation_is_busy_sIN',true,1);
                        
                        $this->efactuurdirect_api->sendInvoice($saved_invoice['id']);
                        usleep( 50000 );
                    }
                }else{
                    $order->add_order_note(__('Error saving invoice: ', 'woo-efactuurdirect').print_r($saved_invoice['error'],1));
                    return;
                }

                $this->log("Invoice sended successfully");
                add_post_meta($order_id, 'efactuurdirect_invoice_id', $saved_invoice['id'], true);
                $order->add_order_note(__('Invoice created', 'woo-efactuurdirect'));
            }
        }else{
            return 'error';
        }
    }

    function get_rounded_tax_rate($unrounded_rate, $order){
        $wc_tax           = new WC_Tax();
        $rounding_error   = 5.0;
        $rounded_tax_rate = round($unrounded_rate);
        foreach ($wc_tax->find_rates(array('country' => $this->addedFunction->getBillingContryFromOrder($order))) as $tax) {
            if (abs($unrounded_rate - $tax['rate']) < $rounding_error) {
                $rounding_error   = abs($unrounded_rate - $tax['rate']);
                $rounded_tax_rate = round($tax['rate'], 1);
                if ($rounding_error < 0.3) break;
            }
        }
        return $rounded_tax_rate;
    }

    private function getPdfFromEfd($id){
        $invoice_id        = $this->addedFunction->getInvoiceIdByOrderIDFromDb($id);
        $efactuurdirect_id = (int) $invoice_id;
        if (!$this->load_api_connector()) {
            return;
        }
        try {
        $invoice_pdf_content = $this->efactuurdirect_api->getInvoicePdf($efactuurdirect_id);
        $invoice_pdf_content = $invoice_pdf_content['content'];
        } catch (Exception $ex) {

            $invoice_pdf_content ='error';
        }
        return($invoice_pdf_content);
    }

    private function updateDB(){
        $installed_db_ver = get_option("efactuurdirect_table_db_version");
        if ($installed_db_ver != $this->efactuurdirect_table_db_version) {
            global $wpdb;
            $table_name = $wpdb->prefix."efactuurdirect_links";
            $sql        = "CREATE TABLE ".$table_name." (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                	`time` INT(11) NOT NULL DEFAULT '0',
                    `wp_id` INT(11) NULL DEFAULT NULL,
                    `efactuurdirect_id` INT(11) NULL DEFAULT NULL,
                	`type` VARCHAR(50) NULL DEFAULT NULL,
                    `status` TINYINT(1) NULL DEFAULT '0',
                	UNIQUE INDEX `id` (`id`)
                    );";

            require_once(ABSPATH.'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            update_option("efactuurdirect_table_db_version", $this->efactuurdirect_table_db_version);
        }
    }

    private function getApiStatus(){
        session_write_close();
        $busy = get_site_transient('creation_is_busy_api');	 
        while($busy){ 
            sleep (1);
            $busy = get_site_transient('creation_is_busy_api');
        }
        $check_api = $this->check_api_credential();
        set_site_transient('creation_is_busy_api',true,1);
        
        if (!isset($check_api['error'])) {
            $show_string_order_list = '<p hidden="true">'.esc_html(__('Efactuurdirect connection status:', 'woo-efactuurdirect')).' ';
            $show_string_order_list .= '<b><a  style="color:#009900;" href="/wp-admin/admin.php?page=wc-settings&tab=integration&section=efactuurdirect">'.esc_html(__('OK', 'woo-efactuurdirect')).'</a></b></p><br/>';
            $show_string_edit_order = '<span hidden="true">'.esc_html(__('Efactuurdirect connection status:', 'woo-efactuurdirect')).' ';
            $show_string_edit_order .= '<b><a  style="color:#009900;" href="/wp-admin/admin.php?page=wc-settings&tab=integration&section=efactuurdirect">'.esc_html(__('OK', 'woo-efactuurdirect')).'</a></b></span><br/>';
        } else {
            if ($check_api['error'] == 'Invalid API key') {
                $show_string_order_list = '<p>'.esc_html(__('Efactuurdirect connection status:', 'woo-efactuurdirect')).' ';
                $show_string_order_list .= '<b><a  style="color:#FF0000;" href="/wp-admin/admin.php?page=wc-settings&tab=integration&section=efactuurdirect">'.esc_html(__('ERROR (Invalid username or API key)', 'woo-efactuurdirect')).'</a</b></p>';
                $show_string_edit_order = '<span>'.esc_html(__('Efactuurdirect connection status:', 'woo-efactuurdirect')).' ';
                $show_string_edit_order .= '<b><a  style="color:#FF0000;" href="/wp-admin/admin.php?page=wc-settings&tab=integration&section=efactuurdirect">'.esc_html(__('ERROR (Invalid username or API key)', 'woo-efactuurdirect')).'</a</span>';

            }
            if ($check_api['error'] == 'Please check domain') {
                $show_string_order_list = '<p>'.esc_html(__('Efactuurdirect connection status:', 'woo-efactuurdirect')).' ';
                $show_string_order_list .= '<b><a style="color:#FF0000;" href="/wp-admin/admin.php?page=wc-settings&tab=integration&section=efactuurdirect">'.esc_html(__('ERROR (Invalid subdomain)', 'woo-efactuurdirect')).'</a></b></p>';
                $show_string_edit_order = '<span>'.esc_html(__('Efactuurdirect connection status:', 'woo-efactuurdirect')).' ';
                $show_string_edit_order .= '<b><a style="color:#FF0000;" href="/wp-admin/admin.php?page=wc-settings&tab=integration&section=efactuurdirect">'.esc_html(__('ERROR (Invalid subdomain)', 'woo-efactuurdirect')).'</a></span>';
            }
        }

        $return_array=array('order_list'=>$show_string_order_list,'edit_order'=>$show_string_edit_order);
        $return_jason=json_encode($return_array );
        return $return_jason;
    }

    private function getInvoiceStatusFromEFD($order_id){
        $status  = 0;
        $invoice = $this->invoice_woo_order_id_efactuurdirect($order_id);
        if ($this->settings['efactuurdirect_invoice_option'] == 'send_invoice' || $this->settings['efactuurdirect_invoice_option'] == 'send_invoice_alredy_paid') {
            if ($invoice['sent'] == 1 && $invoice['pending'] == 0) {
                $this->addedFunction->updateInvoiceStatusInDB($order_id);
                $status      = 1;
                $show_status = __('Status:', 'woo-efactuurdirect');
                if (isset($invoice['statuses']))
                        foreach ($invoice['statuses']as $status) {
                        $show_status = $show_status.' <span style="font-weight: bold; color: '.$this->addedFunction->setColor($status['status_color']).'">'.$status['status'].'</span>';
                    }
                $status = $show_status;
            }
        } elseif ($this->settings['efactuurdirect_invoice_option'] == 'create_concept' || $this->settings['efactuurdirect_invoice_option'] == 'create_concept_alredy_paid') {
            $this->addedFunction->updateInvoiceStatusInDB($order_id);
            $show_status = __('Status:', 'woo-efactuurdirect');
            if (isset($invoice['statuses']))
                    foreach ($invoice['statuses']as $status) {
                    $show_status = $show_status.' <span style="font-weight: bold; color: '.$this->addedFunction->setColor($status['status_color']).'">'.$status['status'].'</span>';
                }
            $status = $show_status;
        }
        return $status;
    }

    function add_admin_notice($msg, $error=false){
        $notices = get_option('woo-efactuurdirect_deferred_admin_notices', array('updated'=>'', 'error'=>''));
        if ($error) {
            $notices['error'] = $msg;
        } else {
            $notices['updated'] = $msg;
        }
        update_option('woo-efactuurdirect_deferred_admin_notices', $notices);
    }

    private function  splitAdress($adress){
		$street = '';
		$housenumber = '';
		$flag=0;
        for($i=0;$i<strlen($adress);$i++){
			if(!preg_match("/[^0-9]/i", $adress[$i])){
				$flag=1;
			}

			if($flag==0)
				$street .= $adress[$i];
			else
				$housenumber .= $adress[$i];
		}
		return array('street'=>trim($street),'housenumber'=>trim($housenumber));
	}

    private function getUserSubdomainFromRoute($userRoute){
        if(strpos($userRoute,'.')){
            $userRoute = trim($userRoute, '/');
            $urlParts = parse_url($userRoute);
            if(isset($urlParts['host'])){
            $tmp=explode('.',$urlParts['host']);
        }else{
            $tmp=explode('.',$userRoute);
        }
        $userRoute=$tmp[0];
        }
        return $userRoute;
    }

  
}