<?php
/**
 * Frontend Dashboard
 */

class APFA_Frontend_Dashboard {

    public function __construct() {
        add_action( 'template_redirect', array( $this, 'hijack_portal_page' ) );
        add_shortcode( 'apfa_main_dashboard', array( $this, 'render_dashboard' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    public function hijack_portal_page() {
        global $post;
        
        if ( is_page( 'payflow-portal' ) && ! is_admin() ) {
            include APFA_PLUGIN_DIR . 'public/dashboard-template-new.php';
            exit;
        }
    }

    public function render_dashboard() {
        ob_start();
        include APFA_PLUGIN_DIR . 'public/dashboard-template-new.php';
        return ob_get_clean();
    }

    public function enqueue_assets() {
        if ( is_page( 'payflow-portal' ) ) {
            wp_enqueue_style( 'apfa-dashboard', APFA_PLUGIN_URL . 'assets/style.css', array(), APFA_VERSION );
            
            wp_enqueue_script( 'apfa-datatables', 'https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js', array( 'jquery' ), '1.13.1', true );
            wp_enqueue_style( 'apfa-datatables', 'https://cdn.datatables.net/1.13.1/css/jquery.dataTables.min.css' );
            wp_enqueue_script( 'apfa-dashboard', APFA_PLUGIN_URL . 'assets/script.js', array( 'jquery', 'apfa-datatables' ), APFA_VERSION, true );

            wp_localize_script( 'apfa-dashboard', 'apfaConfig', array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'apfa_nonce' ),
                'i18n'     => array(
                    'payment_submitted' => __( 'Payment Submitted', 'apfa' ),
                    'payment_success'   => __( 'Your payment has been submitted for verification.', 'apfa' ),
                )
            ) );
        }
    }
}
