<?php
/**
 * Plugin Activator
 * Handles setup on plugin activation
 */

class APFA_Activator {

    public static function activate() {
        // Create database tables
        APFA_Database::create_tables();

        // Create PayFlow Portal Page
        self::create_portal_page();

        // Set redirect flag
        set_transient( 'apfa_activation_redirect', true, 30 );
    }

    private static function create_portal_page() {
        // Check if page already exists
        $page = get_page_by_path( 'payflow-portal' );
        if ( $page ) {
            return;
        }

        // Create new page
        $page_data = array(
            'post_title'    => __( 'PayFlow Portal', 'apfa' ),
            'post_content'  => '[apfa_main_dashboard]',
            'post_status'   => 'publish',
            'post_type'     => 'page',
            'post_name'     => 'payflow-portal',
        );

        wp_insert_post( $page_data );
    }
}

// Handle activation redirect
add_action( 'admin_init', function() {
    if ( get_transient( 'apfa_activation_redirect' ) ) {
        delete_transient( 'apfa_activation_redirect' );
        wp_redirect( admin_url( 'admin.php?page=apfa-settings' ) );
        exit;
    }
} );
