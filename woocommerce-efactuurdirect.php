<?php
/**
 * Plugin Name:       efactuurdirect for WooCommerce
 * Plugin URI:        https://www.efactuurdirect.nl
 * Description:       WooCommerce extension for efactuurdirect. Create contacts and invoices from WooCommerce orders.
 * Version:           1.1.3
 * Author:            efactuurdirect
 * Text Domain:       woo-efactuurdirect
 * License:           GPLv2 or later
 */
if (!defined('ABSPATH')) {
    exit;
}

set_include_path(plugin_dir_path(__FILE__)."/includes".PATH_SEPARATOR.plugin_dir_path(__FILE__)."/includes/library");

include_once( ABSPATH.'wp-admin/includes/plugin.php' );
include_once(ABSPATH.'wp-includes/option.php');

if (is_plugin_active('woocommerce/woocommerce.php')) {
    function wooefd_add_woocommerce_efactuurdirect_integration($integrations)
    {
        require_once('includes/class-wc-efactuurdirect.php');
        require_once('includes/library/EfdApiToolkit.php');
        require_once('includes/library/EfdApiToolkitGateway.php');
        $integrations[] = 'WC_efactuurdirect';
        return $integrations;
    }
    add_filter('woocommerce_integrations', 'wooefd_add_woocommerce_efactuurdirect_integration');

    function woocommerce_efactuurdirect_load_textdomain()
    {
        $locale = apply_filters('plugin_locale', get_locale(), 'woo-efactuurdirect');
        load_textdomain('woo-efactuurdirect', trailingslashit(WP_LANG_DIR)."testplugin/woo-efactuurdirect-$locale.mo");
        load_textdomain('woo-efactuurdirect', dirname(__FILE__)."/languages/woo-efactuurdirect-$locale.mo");
    }

    function woocommerce_efactuurdirect_init()
    {
        woocommerce_efactuurdirect_load_textdomain();

        function woocommerce_efactuurdirect_action_links($links)
        {
            global $woocommerce;
            if (version_compare($woocommerce->version, '2.2', '>=')) {
                $settings_url = admin_url('admin.php?page=wc-settings&tab=integration&section=efactuurdirect');
            } else {
                $settings_url = admin_url('admin.php?page=woocommerce_settings&tab=integration&section=efactuurdirect');
            }
            $plugin_links = array(
                '<a href="'.$settings_url.'">'.__('Settings', 'woocommerce').'</a>',
            );
            return array_merge($plugin_links, $links);
        }
        add_filter('plugin_action_links_'.plugin_basename(__FILE__), 'woocommerce_efactuurdirect_action_links');
    }
    add_action('plugins_loaded', 'woocommerce_efactuurdirect_init');
	if(!get_option('efd_plugin_start')){
		add_option('efd_plugin_start',time(),'','yes');
	}
}

function woocommerce_efactuurdirect_deactivate()
{
    global $wpdb;
    $table_name = $wpdb->prefix."efactuurdirect_links";
    $sql        = "DROP TABLE IF EXISTS $table_name;";
    $wpdb->query($sql);
    delete_option("efactuurdirect_table_db_version");
}
//register_deactivation_hook(__FILE__, 'woocommerce_efactuurdirect_deactivate');

function efactuurdirect_table_install()
{
    global $wpdb;
    global $efactuurdirect_table_install;
    $table_name = $wpdb->prefix."efactuurdirect_links";
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $sql = "CREATE TABLE ".$table_name." (
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
        add_option("efactuurdirect_table_db_version", "1.0");
    }
}
register_activation_hook(__FILE__, 'efactuurdirect_table_install');
?>