<?php
/**
 * SMS Webhook Listener & AJAX Handlers
 */

class APFA_Webhook_Listener {

    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_webhook_endpoint' ) );
        add_action( 'wp_ajax_apfa_submit_fee', array( $this, 'handle_fee_submission' ) );
        add_action( 'wp_ajax_nopriv_apfa_submit_fee', array( $this, 'handle_fee_submission' ) );
        // nopriv variants return a proper JSON error instead of WordPress's raw "0" response
        add_action( 'wp_ajax_apfa_get_student_data', array( $this, 'get_student_data' ) );
        add_action( 'wp_ajax_nopriv_apfa_get_student_data', array( $this, 'get_student_data' ) );
        add_action( 'wp_ajax_apfa_get_transactions', array( $this, 'get_transactions' ) );
        add_action( 'wp_ajax_nopriv_apfa_get_transactions', array( $this, 'get_transactions' ) );
        // download_receipt requires login to enforce ownership
        add_action( 'wp_ajax_apfa_download_receipt', array( $this, 'download_receipt' ) );
    }

    public function register_webhook_endpoint() {
        register_rest_route( 'apfa/v1', '/webhook/sms', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'handle_sms_webhook' ),
            'permission_callback' => array( $this, 'verify_webhook_secret' ),
        ) );
    }

    /**
     * Validate the optional webhook secret header.
     * If no secret is configured in Settings, all requests are allowed through.
     */
    public function verify_webhook_secret( WP_REST_Request $request ) {
        $saved_secret = get_option( 'apfa_webhook_secret', '' );
        if ( empty( $saved_secret ) ) {
            return true;
        }
        $provided = $request->get_header( 'X-Webhook-Secret' );
        return hash_equals( $saved_secret, (string) $provided );
    }

    public function handle_sms_webhook( WP_REST_Request $request ) {
        global $wpdb;
        $prefix = $wpdb->prefix . 'apfa_';

        $data = $request->get_json_params();
        
        $raw_sms    = isset( $data['message'] ) ? sanitize_text_field( $data['message'] ) : '';
        $sender_id  = isset( $data['sender'] ) ? sanitize_text_field( $data['sender'] ) : '';
        $trx_id     = isset( $data['trx_id'] ) ? sanitize_text_field( $data['trx_id'] ) : '';
        $amount     = isset( $data['amount'] ) ? floatval( $data['amount'] ) : 0;
        $date_time  = isset( $data['timestamp'] ) ? sanitize_text_field( $data['timestamp'] ) : current_time( 'mysql' );

        $wpdb->insert(
            $prefix . 'bank_logs',
            array(
                'Raw_SMS'   => $raw_sms,
                'Sender_ID' => $sender_id,
                'Trx_ID'    => $trx_id,
                'Amount'    => $amount,
                'Date_Time' => $date_time,
                'Status'    => 'Unmatched',
            ),
            array( '%s', '%s', '%s', '%f', '%s', '%s' )
        );

        $this->match_transactions( $trx_id, $amount );

        return new WP_REST_Response( array( 'success' => true ), 200 );
    }

    private function match_transactions( $trx_id, $amount ) {
        global $wpdb;
        $prefix = $wpdb->prefix . 'apfa_';

        $submission = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$prefix}fee_submissions WHERE Trx_ID = %s AND Submitted_Amount = %f",
            $trx_id,
            $amount
        ) );

        if ( $submission ) {
            $wpdb->update(
                $prefix . 'fee_submissions',
                array( 'Status' => 'Verified' ),
                array( 'ID' => $submission->ID ),
                array( '%s' ),
                array( '%d' )
            );

            $wpdb->update(
                $prefix . 'bank_logs',
                array( 'Status' => 'Matched' ),
                array( 'Trx_ID' => $trx_id ),
                array( '%s' ),
                array( '%s' )
            );

            $wpdb->query( $wpdb->prepare(
                "UPDATE {$prefix}students SET Balance = Balance - %f WHERE Phone = %s",
                $amount,
                $submission->Student_Phone
            ) );

            do_action( 'apfa_payment_verified', $submission->ID );

            $this->notify_student_verified( $submission->ID );
        }
    }

    /**
     * Send a confirmation email to the student when their payment is verified.
     *
     * @param int $submission_id Primary key from apfa_fee_submissions.
     */
    private function notify_student_verified( $submission_id ) {
        global $wpdb;
        $prefix = $wpdb->prefix . 'apfa_';

        $submission = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$prefix}fee_submissions WHERE ID = %d",
            $submission_id
        ) );

        if ( ! $submission ) {
            return;
        }

        // Look up the WordPress user linked to this student record.
        $student = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$prefix}students WHERE Phone = %s",
            $submission->Student_Phone
        ) );

        if ( ! $student || ! $student->User_ID ) {
            return;
        }

        $user = get_userdata( (int) $student->User_ID );
        if ( ! $user || ! $user->user_email ) {
            return;
        }

        $site_name = get_bloginfo( 'name' );
        $amount    = number_format( (float) $submission->Submitted_Amount, 2 );

        /* translators: Email subject — 1: site name */
        $subject = sprintf( __( '[%s] Your Payment Has Been Verified', 'apfa' ), $site_name );

        $body  = sprintf( __( 'Dear %s,', 'apfa' ), sanitize_text_field( $student->Full_Name ) ) . "\n\n";
        $body .= __( 'Great news! Your fee payment has been successfully verified.', 'apfa' ) . "\n\n";
        $body .= __( 'Payment Details:', 'apfa' ) . "\n";
        $body .= sprintf( __( '  • Transaction ID : %s', 'apfa' ), $submission->Trx_ID ) . "\n";
        $body .= sprintf( __( '  • Amount         : PKR %s', 'apfa' ), $amount ) . "\n";
        $body .= sprintf( __( '  • Status         : %s', 'apfa' ), __( 'Verified', 'apfa' ) ) . "\n\n";
        $body .= sprintf(
            /* translators: URL to the PayFlow portal */
            __( 'You can view and download your receipt from the portal: %s', 'apfa' ),
            home_url( '/payflow-portal/' )
        ) . "\n\n";
        $body .= sprintf( __( 'Thank you,\n%s', 'apfa' ), $site_name );

        wp_mail( $user->user_email, $subject, $body );
    }

    public function handle_fee_submission() {
        check_ajax_referer( 'apfa_nonce', 'nonce' );
        global $wpdb;
        $prefix = $wpdb->prefix . 'apfa_';

        $student_phone  = isset( $_POST['student_phone'] ) ? sanitize_text_field( $_POST['student_phone'] ) : '';
        $trx_id         = isset( $_POST['trx_id'] ) ? sanitize_text_field( $_POST['trx_id'] ) : '';
        $amount         = isset( $_POST['amount'] ) ? floatval( $_POST['amount'] ) : 0;

        if ( empty( $student_phone ) || empty( $trx_id ) || $amount <= 0 ) {
            wp_send_json_error( __( 'Invalid input data', 'apfa' ) );
        }

        // Prevent duplicate Transaction ID submissions.
        $duplicate = $wpdb->get_var( $wpdb->prepare(
            "SELECT ID FROM {$prefix}fee_submissions WHERE Trx_ID = %s",
            $trx_id
        ) );
        if ( $duplicate ) {
            wp_send_json_error( __( 'This Transaction ID has already been submitted.', 'apfa' ) );
        }

        $receipt_image = '';
        if ( ! empty( $_FILES['receipt_image'] ) ) {
            $receipt_image = $this->upload_receipt_image();
            if ( ! $receipt_image ) {
                wp_send_json_error( __( 'Failed to upload receipt image', 'apfa' ) );
            }
        }

        $inserted = $wpdb->insert(
            $prefix . 'fee_submissions',
            array(
                'Student_Phone'    => $student_phone,
                'Trx_ID'           => $trx_id,
                'Submitted_Amount' => $amount,
                'Receipt_Image'    => $receipt_image,
                'Status'           => 'Pending',
            ),
            array( '%s', '%s', '%f', '%s', '%s' )
        );

        if ( ! $inserted ) {
            wp_send_json_error( __( 'Failed to submit fee', 'apfa' ) );
        }

        $submission_id = $wpdb->insert_id;

        // Notify admin about the new pending submission.
        $this->notify_admin_pending( $student_phone, $trx_id, $amount );

        wp_send_json_success( array(
            'message'       => __( 'Fee submitted successfully', 'apfa' ),
            'submission_id' => $submission_id,
        ) );
    }

    /**
     * Send an admin email alert for a newly submitted (Pending) fee entry.
     *
     * @param string $student_phone Phone number identifying the student.
     * @param string $trx_id        Transaction ID provided by the student.
     * @param float  $amount        Submitted amount.
     */
    private function notify_admin_pending( $student_phone, $trx_id, $amount ) {
        $admin_email = get_option( 'admin_email' );
        if ( ! $admin_email ) {
            return;
        }

        $site_name    = get_bloginfo( 'name' );
        $amount_fmt   = number_format( (float) $amount, 2 );
        $submissions_url = admin_url( 'admin.php?page=apfa-submissions' );

        /* translators: Email subject — 1: site name */
        $subject = sprintf( __( '[%s] New Fee Submission Pending Review', 'apfa' ), $site_name );

        $body  = __( 'A new fee submission has been received and requires your review.', 'apfa' ) . "\n\n";
        $body .= __( 'Submission Details:', 'apfa' ) . "\n";
        $body .= sprintf( __( '  • Phone          : %s', 'apfa' ), $student_phone ) . "\n";
        $body .= sprintf( __( '  • Transaction ID : %s', 'apfa' ), $trx_id ) . "\n";
        $body .= sprintf( __( '  • Amount         : PKR %s', 'apfa' ), $amount_fmt ) . "\n\n";
        $body .= sprintf(
            /* translators: URL to the admin submissions page */
            __( 'Review and verify this submission here: %s', 'apfa' ),
            $submissions_url
        ) . "\n\n";
        $body .= sprintf( __( 'Thank you,\n%s', 'apfa' ), $site_name );

        wp_mail( $admin_email, $subject, $body );
    }

    private function upload_receipt_image() {
        if ( empty( $_FILES['receipt_image'] ) ) {
            return false;
        }

        $file = $_FILES['receipt_image'];
        
        $allowed_types = array( 'image/jpeg', 'image/png', 'image/gif' );
        if ( ! in_array( $file['type'], $allowed_types ) ) {
            return false;
        }

        if ( $file['size'] > 5 * 1024 * 1024 ) {
            return false;
        }

        $upload = wp_upload_bits(
            basename( $file['name'] ),
            null,
            file_get_contents( $file['tmp_name'] )
        );

        return isset( $upload['url'] ) ? $upload['url'] : false;
    }

    public function get_student_data() {
        check_ajax_referer( 'apfa_nonce', 'nonce' );
        global $wpdb;
        $prefix = $wpdb->prefix . 'apfa_';

        $current_user_id = get_current_user_id();
        if ( ! $current_user_id ) {
            wp_send_json_error( __( 'Not logged in', 'apfa' ) );
        }

        $student = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$prefix}students WHERE User_ID = %d", $current_user_id )
        );

        if ( ! $student ) {
            wp_send_json_error( __( 'Student not found', 'apfa' ) );
        }

        $verified_amount = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(Submitted_Amount) FROM {$prefix}fee_submissions WHERE Student_Phone = %s AND Status = 'Verified'",
                $student->Phone
            )
        );

        wp_send_json_success( array(
            'id'               => $student->ID,
            'full_name'        => $student->Full_Name,
            'phone'            => $student->Phone,
            'course_name'      => $student->Course_Name,
            'total_fee'        => $student->Total_Fee,
            'balance'          => $student->Balance,
            'verified_amount'  => $verified_amount ?: 0,
            'created_at'       => $student->created_at,
        ) );
    }

    public function get_transactions() {
        check_ajax_referer( 'apfa_nonce', 'nonce' );
        global $wpdb;
        $prefix = $wpdb->prefix . 'apfa_';

        $current_user_id = get_current_user_id();
        if ( ! $current_user_id ) {
            wp_send_json_error( __( 'Not logged in', 'apfa' ) );
        }

        $student = $wpdb->get_row(
            $wpdb->prepare( "SELECT Phone FROM {$prefix}students WHERE User_ID = %d", $current_user_id )
        );

        if ( ! $student ) {
            wp_send_json_error( __( 'Student not found', 'apfa' ) );
        }

        $submissions = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$prefix}fee_submissions WHERE Student_Phone = %s ORDER BY created_at DESC",
                $student->Phone
            )
        );

        wp_send_json_success( array_map( function( $sub ) {
            return array(
                'id'        => $sub->ID,
                'date'      => date( 'd M Y', strtotime( $sub->created_at ) ),
                'trx_id'    => $sub->Trx_ID,
                'amount'    => $sub->Submitted_Amount,
                'status'    => $sub->Status,
                'receipt'   => $sub->Receipt_Image,
            );
        }, $submissions ) );
    }

    public function download_receipt() {
        check_ajax_referer( 'apfa_nonce', 'nonce' );

        $current_user_id = get_current_user_id();
        if ( ! $current_user_id ) {
            wp_send_json_error( __( 'Not logged in', 'apfa' ) );
        }

        $submission_id = isset( $_GET['submission_id'] ) ? intval( $_GET['submission_id'] ) : 0;
        $language      = isset( $_GET['lang'] ) ? sanitize_text_field( $_GET['lang'] ) : 'en';

        if ( ! $submission_id ) {
            wp_send_json_error( __( 'Invalid submission ID', 'apfa' ) );
        }

        global $wpdb;
        $prefix = $wpdb->prefix . 'apfa_';

        // Verify the submission belongs to the current user's student record
        $student = $wpdb->get_row( $wpdb->prepare(
            "SELECT Phone FROM {$prefix}students WHERE User_ID = %d", $current_user_id
        ) );
        if ( $student ) {
            $owns = $wpdb->get_var( $wpdb->prepare(
                "SELECT ID FROM {$prefix}fee_submissions WHERE ID = %d AND Student_Phone = %s",
                $submission_id,
                $student->Phone
            ) );
            if ( ! $owns && ! current_user_can( 'manage_options' ) ) {
                wp_send_json_error( __( 'Access denied', 'apfa' ) );
            }
        } elseif ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Student record not found', 'apfa' ) );
        }

        require_once APFA_PLUGIN_DIR . 'includes/vendor/class-pdf-generator.php';
        APFA_PDF_Generator::download_receipt( $submission_id, $language );
    }
}
