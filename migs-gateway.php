<?php
/*
Plugin Name: MIGS - WooCommerce Gateway
Description: Extends WooCommerce by Adding the MIGS Gateway.
Version: 1.0
Author: Infoway
*/

if(!defined('MIGS_PLUGIN_PATH'))
    define('MIGS_PLUGIN_PATH', dirname(__FILE__));
if(!defined('MIGS_PLUGIN_URL'))
    define('MIGS_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include our Gateway Class and register Payment Gateway with WooCommerce
add_action( 'plugins_loaded', 'ps_migs_init', 0 );
function ps_migs_init() {
    // If the parent WC_Payment_Gateway class doesn't exist
    // it means WooCommerce is not installed on the site
    // so do nothing
    if ( ! class_exists( 'WC_Payment_Gateway' ) ) return;
     
    // If we made it this far, then include our Gateway Class
    include_once( MIGS_PLUGIN_PATH . '/migs.php' );
    include_once( MIGS_PLUGIN_PATH . '/migs_class.php' );
 
    // Now that we have successfully included our class,
    // Lets add it too WooCommerce
    add_filter( 'woocommerce_payment_gateways', 'migs_gateway' );
    function migs_gateway( $methods ) {
        $methods[] = 'MIGS';
        return $methods;
    }
} 

// Add custom action links
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'ps_migs_action_links' );
function ps_migs_action_links( $links ) {
    $plugin_links = array(
        '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout' ) . '">' . __( 'Settings', 'migs' ) . '</a>',
    );
 
    // Merge our new link with the default ones
    return array_merge( $plugin_links, $links );    
}
