<?php
/**
 * Plugin Name: Academy PayFlow Automator
 * Plugin URI: https://github.com/nuzwa269/academy-payflow-automator
 * Description: Automated academy fee management with SMS webhook integration
 * Version: 1.0.0
 * Author: Coach Pro AI
 * License: GPL-2.0+
 * Text Domain: apfa
 * Domain Path: /languages
 * Requires at least: 5.9
 * Requires PHP: 7.4
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit( 'Direct access not permitted.' );
}

// Define constants
define( 'APFA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'APFA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'APFA_VERSION', '1.0.0' );
define( 'APFA_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Enable error logging
if ( ! defined( 'WP_DEBUG' ) ) {
    define( 'WP_DEBUG', true );
}
if ( ! defined( 'WP_DEBUG_LOG' ) ) {
    define( 'WP_DEBUG_LOG', true );
}

// Load text domain
add_action( 'plugins_loaded', function() {
    load_plugin_textdomain( 'apfa', false, dirname( APFA_PLUGIN_BASENAME ) . '/languages' );
}, 9 );

// Load required files
$required_files = array(
    'includes/class-database.php',
    'includes/class-activator.php',
    'includes/class-deactivator.php',
    'includes/class-webhook-listener.php',
    'includes/class-matching-engine.php',
    'includes/vendor/class-pdf-generator.php',
    'admin/class-admin-settings.php',
    'public/class-frontend-dashboard.php',
);

foreach ( $required_files as $file ) {
    $filepath = APFA_PLUGIN_DIR . $file;
    if ( file_exists( $filepath ) ) {
        require_once $filepath;
    } else {
        error_log( 'APFA: Missing file - ' . $file );
    }
}

// Register activation/deactivation
register_activation_hook( __FILE__, array( 'APFA_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'APFA_Deactivator', 'deactivate' ) );

// Initialize plugin
add_action( 'init', function() {
    try {
        if ( is_admin() ) {
            new APFA_Admin_Settings();
        }
        
        if ( ! is_admin() || defined( 'DOING_AJAX' ) ) {
            new APFA_Frontend_Dashboard();
        }
        
        new APFA_Webhook_Listener();
    } catch ( Exception $e ) {
        error_log( 'APFA Error: ' . $e->getMessage() );
    }
}, 10 );

// Security headers
add_filter( 'plugin_action_links_' . APFA_PLUGIN_BASENAME, function( $links ) {
    $settings_link = '<a href="' . admin_url( 'admin.php?page=apfa-settings' ) . '">' . __( 'Settings', 'apfa' ) . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
} );
