<?php

class wooefdAddedFunction
{
	function populateContactDetailsFromBilingDate($order)
    {
        global $woocommerce;        
        if (version_compare($woocommerce->version, '3.0', '>=')) {
            $contact_details = array(
                'company_name'  => trim($order->get_billing_company()),
                'firstname'     => trim($order->get_billing_first_name()),
                'lastname'      => trim($order->get_billing_last_name()),
                'address1'      => trim($order->get_billing_address_1()),
                'address2'      => trim($order->get_billing_address_2()),
                'zipcode'       => trim($order->get_billing_postcode()),
                'city'          => trim($order->get_billing_city()),
                'country'       => trim($order->get_billing_country()),
                'email'         => trim($order->get_billing_email()),
                'phone'         => trim($order->get_billing_phone())
            );
        } else {
            $contact_details = array(
                'company_name'  => trim($order->billing_company),
                'firstname'     => trim($order->billing_first_name),
                'lastname'      => trim($order->billing_last_name),
                'address1'      => trim($order->billing_address_1),
                'address2'      => trim($order->billing_address_2),
                'zipcode'       => trim($order->billing_postcode),
                'city'          => trim($order->billing_city),
                'country'       => trim($order->billing_country),
                'email'         => trim($order->billing_email),
                'phone'         => trim($order->billing_phone)
            );
        }
		return $contact_details;
    }

    function getActualInvoiceId($order)
    {
        global $woocommerce;

        if (version_compare($woocommerce->version, '3.0', '>=')) {
            $invoice_id = trim(get_post_meta($order->get_order_number(), 'efactuurdirect_invoice_id', true));
        } else {
            $invoice_id = trim(get_post_meta($order->id, 'efactuurdirect_invoice_id', true));
        }
        return $invoice_id;
    }

    function getActualOrderId($order)
    {
        global $woocommerce;

        if (version_compare($woocommerce->version, '3.0', '>=')) {
            $order_id = $order->get_order_number();
        } else {
            $order_id = $order->id;
        }
        return $order_id;
    }

	function getPluginStartData(){
		return (get_option('efd_plugin_start'));
	}
	
	function getOrderData($order){
		global $woocommerce;
		if (version_compare($woocommerce->version, '3.0', '>=')) {
            $invoice_date = strtotime($order->get_date_created());
        } else {
            $invoice_date =  strtotime($order->order_date);
        }
        return $invoice_date;
	}
	
    function getActualOrderDateCreated($order)
    {
        global $woocommerce;

        if (version_compare($woocommerce->version, '3.0', '>=')) {
            $invoice_date = date('d-m-Y', strtotime($order->get_date_created()));
        } else {
            $invoice_date = date('d-m-Y', strtotime($order->order_date));
        }
        return $invoice_date;
    }

    function getActualOrderCurency($order)
    {
        global $woocommerce;

        if (version_compare($woocommerce->version, '3.0', '>=')) {
            $currency = $order->get_currency();
        } else {
            $currency = $order->get_order_currency();
        }
        return $currency;
    }

    function getCustomerIdFromOrder($order)
    {
        global $woocommerce;

        if (version_compare($woocommerce->version, '3.0', '>=')) {
            $customer_id = $order->get_customer_id();
        } else {
            $customer_id = $order->customer_id;
        }
        return $customer_id;
    }

    function getBillingContryFromOrder($order)
    {
        global $woocommerce;

        if (version_compare($woocommerce->version, '3.0', '>=')) {
            $billing_country = $order->get_billing_country();
        } else {
            $billing_country = $order->billing_country;
        }
        return $billing_country;
    }

    function getInvoiceIdByOrderIDFromDb($order_id){
        global $wpdb;
        $table_name = $wpdb->prefix."efactuurdirect_links";
        $comment    = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE type= 'invoice' AND wp_id = '%d'", $order_id), ARRAY_A);
        $efactuurdirect_id   = $comment['efactuurdirect_id'];
        return($efactuurdirect_id);
    }

    function getInvoiceStatusFromDB($order_id,$invoice_id)
    {
        global $wpdb;

        $table_name = $wpdb->prefix."efactuurdirect_links";
        $comment    = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE type= 'invoice' AND efactuurdirect_id = %d", $invoice_id), ARRAY_A);
        $status     = $comment['status'];
        if(!isset($status)){
            if($order_id&&$invoice_id){
                global $wpdb;
                $wpdb->insert($table_name, array(
                'time' => time(),
                'wp_id' => $order_id,
                'efactuurdirect_id' => $invoice_id,
                'status'=>0,
                'type' => 'invoice',
            ));
            }
        $status=0;
        }
    return $status;
    }

    function getOrderStatusSendFromDbByOrderId($id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix."efactuurdirect_links";
        $comment    = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE type= 'orderstatus' AND wp_id = %d", $id), ARRAY_A);
        if (isset($comment['efactuurdirect_id'])) {
            $efactuurdirect_id = $comment['efactuurdirect_id'];
        } else {
            $efactuurdirect_id = false;
        }
        return($efactuurdirect_id);
    }

    function getEfdIdFromDB($id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix."efactuurdirect_links";
        $comment    = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE type= 'invoice' AND wp_id = %d", $id), ARRAY_A);
        if (isset($comment['efactuurdirect_id'])) {
            $efactuurdirect_id = $comment['efactuurdirect_id'];
        } else {
            $efactuurdirect_id = false;
        }
        return($efactuurdirect_id);
    }

    function updateInvoiceStatusInDB($order_id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix."efactuurdirect_links";
        $status     = $wpdb->update($table_name, array('status' => 1), array('wp_id' => $order_id,'type'=>'invoice'));
        return $status;
    }

    function insertInvoiceIdWooEfd($id, $order_id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix."efactuurdirect_links";
        $wpdb->insert($table_name, array(
            'time' => time(),
            'wp_id' => $order_id,
            'efactuurdirect_id' => $id,
            'status'=>1,
            'type' => 'invoice',
            )
        );

    }

    function addNewLinkWpIdEfdId($customer_id, $efactuurdirect_id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix."efactuurdirect_links";
        $wpdb->insert($table_name, array(
            'time'    => time(),
            'wp_id  ' => $customer_id,
            'efactuurdirect_id'  => $efactuurdirect_id,
            'type'    => 'client',
            )
        );
    }


    function setColor($color_number)
    {
        $color_array = array(
            '1' => 'GRAY',
            '2' => 'GREEN',
            '3' => 'ORANGE',
            '4' => 'BLUE',
            '5' => 'RED',
        );
        if (isset($color_array[$color_number])) {
            $color = $color_array[$color_number];
        } else {
            $color = 'GRAY';
        }
        return $color;
    }

    function sendEfdaction()
    {
        $efactuurdirect_reaction = array('create_concept' => __('Create concept invoice', 'woo-efactuurdirect'),
            'create_concept_alredy_paid' => __('Create concept already paid invoice', 'woo-efactuurdirect'),
            'send_invoice' => __('Send invoice', 'woo-efactuurdirect'),
            'send_invoice_alredy_paid' => __('Send already paid invoice', 'woo-efactuurdirect'),
        );
        return $efactuurdirect_reaction;
    }

    function get_woocomerce_order_statuses()
    {
        $wocomerceStatuses = array('manually' => __('Manually', 'woo-efactuurdirect'));
        if (!function_exists('wc_get_order_statuses')) {
            require_once '/includes/wc-order-functions.php';
        }
        $wc_default_orders = wc_get_order_statuses();
        foreach ($wc_default_orders as $key => $status) {
            $r_key                     = str_replace('wc-', '', $key);
            $wocomerceStatuses[$r_key] = $status;
        }
        return $wocomerceStatuses;
    }

    function getInvoiceInclExclTax()
    {
        $woocommerce_prices_include_tax = get_option('woocommerce_prices_include_tax');
        if ($woocommerce_prices_include_tax == 'yes') {
            $is_incl_tax = 1;
        } else {
            $is_incl_tax = 0;
        }
        return $is_incl_tax;
    }

    /*function getInvoiceAlredyPaid($settings)
    {
        if ($settings['efactuurdirect_invoice_option'] == 'create_concept_alredy_paid' || $settings['efactuurdirect_invoice_option'] == 'send_invoice_alredy_paid') {
			$payd_option 	= 	(int)$settings['efactuurdirect_payment_option'];
			if($payd_option==0){
				$paymeth_array 	= get_site_transient('efactuurdirect_paymeth');
				reset($paymeth_array);
				$payd_option = key($paymeth_array);
			}
            $already_paid 	= $payd_option;
        } else {
            $already_paid = 0;
        }
        return $already_paid;
    }*/

    function getDateToInvoice($order,$settings){
         if ($settings['invoice_date'] == 'order_date') {
             $invoice_date = date('d-m-Y', time());
        } else {
          $invoice_date = false;
        }
        return $invoice_date;
    }


    function getEfdApiErrorStatus($id){
        global $wpdb;
        $table_name = $wpdb->prefix."efactuurdirect_links";
        $comment    = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE type= 'efdapierror' AND wp_id = %d", $id), ARRAY_A);
        if (isset($comment['status'])) {
            $api_error_status = $comment['status'];
        } else {
            $api_error_status = 0;
        }
        return($api_error_status);
    }


    function getEfdApiError($id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix."efactuurdirect_links";
        $comment    = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE type= 'efdapierror' AND wp_id = %d", $id), ARRAY_A);
        if (isset($comment['id'])) {
            $status = $comment['id'];
        } else {
            $status = 0;
        }
        return($status);
    }

    function insertUpdateEfdApiError($order_id,$status){
        $efd_api_error = $this->getEfdApiError($order_id);
        if($efd_api_error!=0){
            global $wpdb;
            $table_name = $wpdb->prefix."efactuurdirect_links";
            $status     = $wpdb->update($table_name, array('status' => $status), array('wp_id' => $order_id,'type'=>'efdapierror'));
        }else{
            global $wpdb;
            $table_name = $wpdb->prefix."efactuurdirect_links";
            $wpdb->insert($table_name, array(
                'time' => time(),
                'wp_id' => $order_id,
                'efactuurdirect_id' => 0,
                'status'=>$status,
                'type' => 'efdapierror',
            )
            );
        }

    }
}
?>