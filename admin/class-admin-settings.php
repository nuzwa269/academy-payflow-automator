<?php
/**
 * Admin Settings Page
 */

class APFA_Admin_Settings {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    public function add_admin_menu() {
        add_menu_page(
            __( 'Academy PayFlow', 'apfa' ),
            __( 'Academy PayFlow', 'apfa' ),
            'manage_options',
            'apfa-settings',
            array( $this, 'render_settings_page' ),
            'dashicons-money',
            30
        );
    }

    public function register_settings() {
        register_setting( 'apfa-settings-group', 'apfa_webhook_secret' );
        register_setting( 'apfa-settings-group', 'apfa_bank_name' );
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e( 'Academy PayFlow Settings', 'apfa' ); ?></h1>
            
            <form method="POST" action="options.php">
                <?php settings_fields( 'apfa-settings-group' ); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="webhook_secret"><?php _e( 'Webhook Secret Key', 'apfa' ); ?></label></th>
                        <td>
                            <input type="text" name="apfa_webhook_secret" value="<?php echo esc_attr( get_option( 'apfa_webhook_secret' ) ); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="bank_name"><?php _e( 'Bank Name', 'apfa' ); ?></label></th>
                        <td>
                            <select name="apfa_bank_name">
                                <option value="Meezan" <?php selected( get_option( 'apfa_bank_name' ), 'Meezan' ); ?>>Meezan Bank</option>
                                <option value="Easypaisa" <?php selected( get_option( 'apfa_bank_name' ), 'Easypaisa' ); ?>>Easypaisa</option>
                                <option value="JazzCash" <?php selected( get_option( 'apfa_bank_name' ), 'JazzCash' ); ?>>JazzCash</option>
                            </select>
                        </td>
                    </tr>
                </table>

                <div style="background: #f5f5f5; padding: 15px; border-radius: 5px; margin-top: 20px;">
                    <h3><?php _e( 'Webhook URL', 'apfa' ); ?></h3>
                    <p><?php _e( 'Use this URL to configure your SMS forwarder app:', 'apfa' ); ?></p>
                    <code style="display: block; background: white; padding: 10px; border-radius: 3px; margin-top: 10px; word-break: break-all;"><?php echo esc_url( rest_url( 'apfa/v1/webhook/sms' ) ); ?></code>
                </div>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
