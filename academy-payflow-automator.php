<?php
/**
 * Plugin Name: Academy PayFlow Automator
 * Plugin URI: https://github.com/nuzwa269/academy-payflow-automator
 * Description: Automated academy fee management with SMS webhook integration, transaction matching, and PDF receipts
 * Version: 1.0.0
 * Author: Coach Pro AI
 * Author URI: https://coachproai.com
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: apfa
 * Domain Path: /languages
 * Requires at least: 5.9
 * Requires PHP: 7.4
 * Tested up to: 6.4
 * Network: false
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit( 'Direct access not permitted.' );
}

// Define plugin constants
if ( ! defined( 'APFA_PLUGIN_DIR' ) ) {
    define( 'APFA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'APFA_PLUGIN_URL' ) ) {
    define( 'APFA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'APFA_VERSION' ) ) {
    define( 'APFA_VERSION', '1.0.0' );
}

if ( ! defined( 'APFA_PLUGIN_BASENAME' ) ) {
    define( 'APFA_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
}

/**
 * Load plugin text domain for translations
 */
add_action( 'plugins_loaded', function() {
    load_plugin_textdomain( 'apfa', false, dirname( APFA_PLUGIN_BASENAME ) . '/languages' );
}, 9 );

/**
 * Load required files
 */
$files = array(
    'includes/class-database.php',
    'includes/class-activator.php',
    'includes/class-deactivator.php',
    'includes/class-webhook-listener.php',
    'includes/class-matching-engine.php',
    'includes/vendor/class-pdf-generator.php',
    'admin/class-admin-settings.php',
    'public/class-frontend-dashboard.php',
);

foreach ( $files as $file ) {
    if ( file_exists( APFA_PLUGIN_DIR . $file ) ) {
        require_once APFA_PLUGIN_DIR . $file;
    }
}

/**
 * Register plugin activation hook
 */
register_activation_hook( __FILE__, array( 'APFA_Activator', 'activate' ) );

/**
 * Register plugin deactivation hook
 */
register_deactivation_hook( __FILE__, array( 'APFA_Deactivator', 'deactivate' ) );

/**
 * Initialize plugin on WordPress init hook
 */
add_action( 'init', function() {
    // Initialize admin
    if ( is_admin() ) {
        new APFA_Admin_Settings();
    }

    // Initialize frontend
    if ( ! is_admin() || defined( 'DOING_AJAX' ) ) {
        new APFA_Frontend_Dashboard();
    }

    // Initialize webhook listener
    new APFA_Webhook_Listener();
}, 10 );

/**
 * Display admin notice if WordPress version is too old
 */
add_action( 'admin_notices', function() {
    global $wp_version;
    if ( version_compare( $wp_version, '5.9', '<' ) ) {
        echo '<div class="notice notice-error"><p>';
        esc_html_e( 'Academy PayFlow Automator requires WordPress 5.9 or higher.', 'apfa' );
        echo '</p></div>';
    }
    
    if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
        echo '<div class="notice notice-error"><p>';
        esc_html_e( 'Academy PayFlow Automator requires PHP 7.4 or higher.', 'apfa' );
        echo '</p></div>';
    }
} );

/**
 * Add plugin action links
 */
add_filter( 'plugin_action_links_' . APFA_PLUGIN_BASENAME, function( $links ) {
    $settings_link = '<a href="' . admin_url( 'admin.php?page=apfa-settings' ) . '">' .
        esc_html__( 'Settings', 'apfa' ) . '</a>';
    
    array_unshift( $links, $settings_link );
    
    return $links;
} );
