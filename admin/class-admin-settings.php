<?php
/**
 * Admin Settings Page
 */

class APFA_Admin_Settings {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

        // AJAX handlers for admin student management
        add_action( 'wp_ajax_apfa_admin_add_student', array( $this, 'ajax_add_student' ) );
        add_action( 'wp_ajax_apfa_admin_get_students', array( $this, 'ajax_get_students' ) );
        add_action( 'wp_ajax_apfa_admin_delete_student', array( $this, 'ajax_delete_student' ) );
        add_action( 'wp_ajax_apfa_admin_update_submission', array( $this, 'ajax_update_submission' ) );
        add_action( 'wp_ajax_apfa_admin_get_stats', array( $this, 'ajax_get_stats' ) );
    }

    public function enqueue_admin_assets( $hook ) {
        if ( strpos( $hook, 'apfa' ) === false ) {
            return;
        }
        wp_enqueue_script(
            'apfa-admin',
            APFA_PLUGIN_URL . 'assets/admin.js',
            array( 'jquery' ),
            APFA_VERSION,
            true
        );
        wp_localize_script( 'apfa-admin', 'apfaAdmin', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'apfa_admin_nonce' ),
        ) );
        wp_enqueue_style( 'apfa-admin-style', APFA_PLUGIN_URL . 'assets/admin.css', array(), APFA_VERSION );
    }

    public function add_admin_menu() {
        add_menu_page(
            __( 'Academy PayFlow', 'apfa' ),
            __( 'Academy PayFlow', 'apfa' ),
            'manage_options',
            'apfa-settings',
            array( $this, 'render_dashboard_page' ),
            'dashicons-money',
            30
        );

        add_submenu_page(
            'apfa-settings',
            __( 'Dashboard', 'apfa' ),
            __( 'Dashboard', 'apfa' ),
            'manage_options',
            'apfa-settings',
            array( $this, 'render_dashboard_page' )
        );

        add_submenu_page(
            'apfa-settings',
            __( 'Students', 'apfa' ),
            __( 'Students', 'apfa' ),
            'manage_options',
            'apfa-students',
            array( $this, 'render_students_page' )
        );

        add_submenu_page(
            'apfa-settings',
            __( 'Fee Submissions', 'apfa' ),
            __( 'Fee Submissions', 'apfa' ),
            'manage_options',
            'apfa-submissions',
            array( $this, 'render_submissions_page' )
        );

        add_submenu_page(
            'apfa-settings',
            __( 'Bank Logs', 'apfa' ),
            __( 'Bank Logs', 'apfa' ),
            'manage_options',
            'apfa-bank-logs',
            array( $this, 'render_bank_logs_page' )
        );

        add_submenu_page(
            'apfa-settings',
            __( 'Reports', 'apfa' ),
            __( 'Reports', 'apfa' ),
            'manage_options',
            'apfa-reports',
            array( $this, 'render_reports_page' )
        );

        add_submenu_page(
            'apfa-settings',
            __( 'Settings', 'apfa' ),
            __( 'Settings', 'apfa' ),
            'manage_options',
            'apfa-options',
            array( $this, 'render_settings_page' )
        );
    }

    public function register_settings() {
        register_setting( 'apfa-settings-group', 'apfa_webhook_secret' );
        register_setting( 'apfa-settings-group', 'apfa_bank_name' );
    }

    /* ============================================================
       AJAX HANDLERS
    ============================================================ */

    public function ajax_add_student() {
        check_ajax_referer( 'apfa_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Unauthorized', 'apfa' ) );
        }

        global $wpdb;
        $prefix = $wpdb->prefix . 'apfa_';

        $full_name   = isset( $_POST['full_name'] ) ? sanitize_text_field( $_POST['full_name'] ) : '';
        $phone       = isset( $_POST['phone'] ) ? sanitize_text_field( $_POST['phone'] ) : '';
        $course_name = isset( $_POST['course_name'] ) ? sanitize_text_field( $_POST['course_name'] ) : '';
        $total_fee   = isset( $_POST['total_fee'] ) ? floatval( $_POST['total_fee'] ) : 0;
        $user_id     = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : 0;

        if ( empty( $full_name ) || empty( $phone ) ) {
            wp_send_json_error( __( 'Full Name and Phone are required', 'apfa' ) );
        }

        $existing = $wpdb->get_var( $wpdb->prepare(
            "SELECT ID FROM {$prefix}students WHERE Phone = %s",
            $phone
        ) );
        if ( $existing ) {
            wp_send_json_error( __( 'A student with this phone number already exists', 'apfa' ) );
        }

        $inserted = $wpdb->insert(
            $prefix . 'students',
            array(
                'User_ID'     => $user_id > 0 ? $user_id : null,
                'Full_Name'   => $full_name,
                'Phone'       => $phone,
                'Course_Name' => $course_name,
                'Total_Fee'   => $total_fee,
                'Balance'     => $total_fee,
            ),
            array( '%d', '%s', '%s', '%s', '%f', '%f' )
        );

        if ( ! $inserted ) {
            wp_send_json_error( __( 'Failed to add student', 'apfa' ) );
        }

        wp_send_json_success( array(
            'message'    => __( 'Student added successfully', 'apfa' ),
            'student_id' => $wpdb->insert_id,
        ) );
    }

    public function ajax_get_students() {
        check_ajax_referer( 'apfa_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Unauthorized', 'apfa' ) );
        }

        global $wpdb;
        $prefix   = $wpdb->prefix . 'apfa_';
        $students = $wpdb->get_results( "SELECT * FROM {$prefix}students ORDER BY created_at DESC" );
        wp_send_json_success( $students );
    }

    public function ajax_delete_student() {
        check_ajax_referer( 'apfa_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Unauthorized', 'apfa' ) );
        }

        global $wpdb;
        $prefix     = $wpdb->prefix . 'apfa_';
        $student_id = isset( $_POST['student_id'] ) ? intval( $_POST['student_id'] ) : 0;

        if ( ! $student_id ) {
            wp_send_json_error( __( 'Invalid student ID', 'apfa' ) );
        }

        $deleted = $wpdb->delete( $prefix . 'students', array( 'ID' => $student_id ), array( '%d' ) );
        if ( ! $deleted ) {
            wp_send_json_error( __( 'Failed to delete student', 'apfa' ) );
        }

        wp_send_json_success( array( 'message' => __( 'Student deleted', 'apfa' ) ) );
    }

    public function ajax_update_submission() {
        check_ajax_referer( 'apfa_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Unauthorized', 'apfa' ) );
        }

        global $wpdb;
        $prefix        = $wpdb->prefix . 'apfa_';
        $submission_id = isset( $_POST['submission_id'] ) ? intval( $_POST['submission_id'] ) : 0;
        $status        = isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : '';

        if ( ! $submission_id || ! in_array( $status, array( 'Pending', 'Verified', 'Rejected' ), true ) ) {
            wp_send_json_error( __( 'Invalid data', 'apfa' ) );
        }

        $submission = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$prefix}fee_submissions WHERE ID = %d",
            $submission_id
        ) );

        if ( ! $submission ) {
            wp_send_json_error( __( 'Submission not found', 'apfa' ) );
        }

        $wpdb->update(
            $prefix . 'fee_submissions',
            array( 'Status' => $status ),
            array( 'ID' => $submission_id ),
            array( '%s' ),
            array( '%d' )
        );

        // Deduct balance when manually verifying (only if student exists and balance won't go negative)
        if ( 'Verified' === $status && 'Verified' !== $submission->Status ) {
            $student = $wpdb->get_row( $wpdb->prepare(
                "SELECT ID, Balance FROM {$prefix}students WHERE Phone = %s",
                $submission->Student_Phone
            ) );
            if ( $student ) {
                $new_balance = max( 0, (float) $student->Balance - (float) $submission->Submitted_Amount );
                $wpdb->update(
                    $prefix . 'students',
                    array( 'Balance' => $new_balance ),
                    array( 'ID' => $student->ID ),
                    array( '%f' ),
                    array( '%d' )
                );
            }
        }

        wp_send_json_success( array( 'message' => __( 'Status updated', 'apfa' ) ) );
    }

    public function ajax_get_stats() {
        check_ajax_referer( 'apfa_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Unauthorized', 'apfa' ) );
        }

        global $wpdb;
        $prefix = $wpdb->prefix . 'apfa_';

        $total_revenue  = $wpdb->get_var( "SELECT SUM(Submitted_Amount) FROM {$prefix}fee_submissions WHERE Status = 'Verified'" ) ?: 0;
        $pending_count  = $wpdb->get_var( "SELECT COUNT(*) FROM {$prefix}fee_submissions WHERE Status = 'Pending'" ) ?: 0;
        $verified_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$prefix}fee_submissions WHERE Status = 'Verified'" ) ?: 0;
        $student_count  = $wpdb->get_var( "SELECT COUNT(*) FROM {$prefix}students" ) ?: 0;

        wp_send_json_success( array(
            'total_revenue'  => $total_revenue,
            'pending_count'  => $pending_count,
            'verified_count' => $verified_count,
            'student_count'  => $student_count,
        ) );
    }

    /* ============================================================
       RENDER: ADMIN DASHBOARD
    ============================================================ */

    public function render_dashboard_page() {
        global $wpdb;
        $prefix = $wpdb->prefix . 'apfa_';

        $total_revenue  = $wpdb->get_var( "SELECT SUM(Submitted_Amount) FROM {$prefix}fee_submissions WHERE Status = 'Verified'" ) ?: 0;
        $pending_count  = $wpdb->get_var( "SELECT COUNT(*) FROM {$prefix}fee_submissions WHERE Status = 'Pending'" ) ?: 0;
        $verified_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$prefix}fee_submissions WHERE Status = 'Verified'" ) ?: 0;
        $student_count  = $wpdb->get_var( "SELECT COUNT(*) FROM {$prefix}students" ) ?: 0;

        $recent_submissions = $wpdb->get_results(
            "SELECT fs.*, s.Full_Name FROM {$prefix}fee_submissions fs
             LEFT JOIN {$prefix}students s ON s.Phone = fs.Student_Phone
             ORDER BY fs.created_at DESC LIMIT 10"
        );

        $portal_url = get_permalink( get_page_by_path( 'payflow-portal' ) );
        ?>
        <div class="wrap apfa-admin-wrap">
            <h1>🏦 <?php _e( 'Academy PayFlow — Admin Dashboard', 'apfa' ); ?></h1>

            <div class="apfa-stats-grid">
                <div class="apfa-stat-card apfa-stat-blue">
                    <span class="dashicons dashicons-groups"></span>
                    <div>
                        <div class="stat-value"><?php echo esc_html( $student_count ); ?></div>
                        <div class="stat-label"><?php _e( 'Total Students', 'apfa' ); ?></div>
                    </div>
                </div>
                <div class="apfa-stat-card apfa-stat-green">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <div>
                        <div class="stat-value">PKR <?php echo esc_html( number_format( $total_revenue, 0 ) ); ?></div>
                        <div class="stat-label"><?php _e( 'Total Revenue', 'apfa' ); ?></div>
                    </div>
                </div>
                <div class="apfa-stat-card apfa-stat-orange">
                    <span class="dashicons dashicons-clock"></span>
                    <div>
                        <div class="stat-value"><?php echo esc_html( $pending_count ); ?></div>
                        <div class="stat-label"><?php _e( 'Pending Requests', 'apfa' ); ?></div>
                    </div>
                </div>
                <div class="apfa-stat-card apfa-stat-teal">
                    <span class="dashicons dashicons-saved"></span>
                    <div>
                        <div class="stat-value"><?php echo esc_html( $verified_count ); ?></div>
                        <div class="stat-label"><?php _e( 'Verified Payments', 'apfa' ); ?></div>
                    </div>
                </div>
            </div>

            <div class="apfa-quick-links" style="margin-bottom:20px;">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=apfa-students' ) ); ?>" class="button button-primary">
                    <?php _e( '+ Add Student', 'apfa' ); ?>
                </a>
                <?php if ( $portal_url ) : ?>
                <a href="<?php echo esc_url( $portal_url ); ?>" class="button" target="_blank">
                    <?php _e( 'View PayFlow Portal ↗', 'apfa' ); ?>
                </a>
                <?php endif; ?>
            </div>

            <h2><?php _e( 'Recent Submissions', 'apfa' ); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e( 'Student', 'apfa' ); ?></th>
                        <th><?php _e( 'Phone', 'apfa' ); ?></th>
                        <th><?php _e( 'Trx ID', 'apfa' ); ?></th>
                        <th><?php _e( 'Amount', 'apfa' ); ?></th>
                        <th><?php _e( 'Status', 'apfa' ); ?></th>
                        <th><?php _e( 'Date', 'apfa' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php if ( $recent_submissions ) : ?>
                    <?php foreach ( $recent_submissions as $sub ) : ?>
                        <tr>
                            <td><?php echo esc_html( $sub->Full_Name ?: '—' ); ?></td>
                            <td><?php echo esc_html( $sub->Student_Phone ); ?></td>
                            <td><code><?php echo esc_html( $sub->Trx_ID ); ?></code></td>
                            <td>PKR <?php echo esc_html( number_format( $sub->Submitted_Amount, 2 ) ); ?></td>
                            <td><?php echo $this->status_badge( $sub->Status ); ?></td>
                            <td><?php echo esc_html( date_i18n( 'd M Y', strtotime( $sub->created_at ) ) ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr><td colspan="6" style="text-align:center;"><?php _e( 'No submissions yet.', 'apfa' ); ?></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /* ============================================================
       RENDER: STUDENTS PAGE (with Admission Form)
    ============================================================ */

    public function render_students_page() {
        global $wpdb;
        $prefix   = $wpdb->prefix . 'apfa_';
        $students = $wpdb->get_results( "SELECT * FROM {$prefix}students ORDER BY created_at DESC" );
        $wp_users = get_users( array( 'fields' => array( 'ID', 'user_login', 'display_name' ) ) );
        ?>
        <div class="wrap apfa-admin-wrap">
            <h1>👩‍🎓 <?php _e( 'Students — Admission & Management', 'apfa' ); ?></h1>

            <div class="apfa-two-col">
                <!-- Admission Form -->
                <div class="apfa-card">
                    <h2><?php _e( 'Enroll New Student (Admission Form)', 'apfa' ); ?></h2>
                    <form id="apfa-admission-form">
                        <table class="form-table">
                            <tr>
                                <th><label for="adm_full_name"><?php _e( 'Full Name *', 'apfa' ); ?></label></th>
                                <td><input type="text" id="adm_full_name" name="full_name" class="regular-text" required></td>
                            </tr>
                            <tr>
                                <th><label for="adm_phone"><?php _e( 'Phone Number *', 'apfa' ); ?></label></th>
                                <td><input type="text" id="adm_phone" name="phone" class="regular-text" placeholder="03001234567" required></td>
                            </tr>
                            <tr>
                                <th><label for="adm_course"><?php _e( 'Course Name', 'apfa' ); ?></label></th>
                                <td><input type="text" id="adm_course" name="course_name" class="regular-text"></td>
                            </tr>
                            <tr>
                                <th><label for="adm_total_fee"><?php _e( 'Total Fee (PKR)', 'apfa' ); ?></label></th>
                                <td><input type="number" id="adm_total_fee" name="total_fee" class="regular-text" step="0.01" min="0" value="0"></td>
                            </tr>
                            <tr>
                                <th><label for="adm_user_id"><?php _e( 'Link WordPress User', 'apfa' ); ?></label></th>
                                <td>
                                    <select id="adm_user_id" name="user_id" class="regular-text">
                                        <option value=""><?php _e( '— No WP User —', 'apfa' ); ?></option>
                                        <?php foreach ( $wp_users as $user ) : ?>
                                            <option value="<?php echo esc_attr( $user->ID ); ?>">
                                                <?php echo esc_html( $user->display_name . ' (' . $user->user_login . ')' ); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                        </table>
                        <div id="apfa-admission-msg" style="margin:10px 0;"></div>
                        <button type="submit" class="button button-primary"><?php _e( 'Enroll Student', 'apfa' ); ?></button>
                    </form>
                </div>

                <!-- Student List -->
                <div class="apfa-card apfa-card-full">
                    <h2><?php _e( 'Enrolled Students', 'apfa' ); ?></h2>
                    <table class="wp-list-table widefat fixed striped" id="apfa-students-table">
                        <thead>
                            <tr>
                                <th><?php _e( 'ID', 'apfa' ); ?></th>
                                <th><?php _e( 'Full Name', 'apfa' ); ?></th>
                                <th><?php _e( 'Phone', 'apfa' ); ?></th>
                                <th><?php _e( 'Course', 'apfa' ); ?></th>
                                <th><?php _e( 'Total Fee', 'apfa' ); ?></th>
                                <th><?php _e( 'Balance', 'apfa' ); ?></th>
                                <th><?php _e( 'Enrolled', 'apfa' ); ?></th>
                                <th><?php _e( 'Actions', 'apfa' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ( $students ) : ?>
                            <?php foreach ( $students as $student ) : ?>
                                <tr id="student-row-<?php echo esc_attr( $student->ID ); ?>">
                                    <td><?php echo esc_html( $student->ID ); ?></td>
                                    <td><?php echo esc_html( $student->Full_Name ); ?></td>
                                    <td><?php echo esc_html( $student->Phone ); ?></td>
                                    <td><?php echo esc_html( $student->Course_Name ); ?></td>
                                    <td>PKR <?php echo esc_html( number_format( $student->Total_Fee, 2 ) ); ?></td>
                                    <td>PKR <?php echo esc_html( number_format( $student->Balance, 2 ) ); ?></td>
                                    <td><?php echo esc_html( date_i18n( 'd M Y', strtotime( $student->created_at ) ) ); ?></td>
                                    <td>
                                        <button class="button button-small apfa-delete-student"
                                            data-id="<?php echo esc_attr( $student->ID ); ?>">
                                            <?php _e( 'Delete', 'apfa' ); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr><td colspan="8" style="text-align:center;"><?php _e( 'No students enrolled yet.', 'apfa' ); ?></td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }

    /* ============================================================
       RENDER: FEE SUBMISSIONS PAGE
    ============================================================ */

    public function render_submissions_page() {
        global $wpdb;
        $prefix      = $wpdb->prefix . 'apfa_';
        $submissions = $wpdb->get_results(
            "SELECT fs.*, s.Full_Name FROM {$prefix}fee_submissions fs
             LEFT JOIN {$prefix}students s ON s.Phone = fs.Student_Phone
             ORDER BY fs.created_at DESC"
        );
        ?>
        <div class="wrap apfa-admin-wrap">
            <h1>💸 <?php _e( 'Fee Submissions', 'apfa' ); ?></h1>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e( 'ID', 'apfa' ); ?></th>
                        <th><?php _e( 'Student', 'apfa' ); ?></th>
                        <th><?php _e( 'Phone', 'apfa' ); ?></th>
                        <th><?php _e( 'Trx ID', 'apfa' ); ?></th>
                        <th><?php _e( 'Amount', 'apfa' ); ?></th>
                        <th><?php _e( 'Status', 'apfa' ); ?></th>
                        <th><?php _e( 'Receipt', 'apfa' ); ?></th>
                        <th><?php _e( 'Date', 'apfa' ); ?></th>
                        <th><?php _e( 'Actions', 'apfa' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php if ( $submissions ) : ?>
                    <?php foreach ( $submissions as $sub ) : ?>
                        <tr id="submission-row-<?php echo esc_attr( $sub->ID ); ?>">
                            <td><?php echo esc_html( $sub->ID ); ?></td>
                            <td><?php echo esc_html( $sub->Full_Name ?: '—' ); ?></td>
                            <td><?php echo esc_html( $sub->Student_Phone ); ?></td>
                            <td><code><?php echo esc_html( $sub->Trx_ID ); ?></code></td>
                            <td>PKR <?php echo esc_html( number_format( $sub->Submitted_Amount, 2 ) ); ?></td>
                            <td class="sub-status-cell-<?php echo esc_attr( $sub->ID ); ?>"><?php echo $this->status_badge( $sub->Status ); ?></td>
                            <td>
                                <?php if ( $sub->Receipt_Image ) : ?>
                                    <a href="<?php echo esc_url( $sub->Receipt_Image ); ?>" target="_blank"><?php _e( 'View', 'apfa' ); ?></a>
                                <?php else : ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html( date_i18n( 'd M Y H:i', strtotime( $sub->created_at ) ) ); ?></td>
                            <td>
                                <?php if ( 'Pending' === $sub->Status ) : ?>
                                    <button class="button button-small apfa-verify-submission" style="background:#10b981;color:#fff;border-color:#10b981;"
                                        data-id="<?php echo esc_attr( $sub->ID ); ?>" data-status="Verified">
                                        <?php _e( 'Verify', 'apfa' ); ?>
                                    </button>
                                    <button class="button button-small apfa-reject-submission" style="background:#ef4444;color:#fff;border-color:#ef4444;margin-left:4px;"
                                        data-id="<?php echo esc_attr( $sub->ID ); ?>" data-status="Rejected">
                                        <?php _e( 'Reject', 'apfa' ); ?>
                                    </button>
                                <?php else : ?>
                                    <?php echo esc_html( $sub->Status ); ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr><td colspan="9" style="text-align:center;"><?php _e( 'No submissions yet.', 'apfa' ); ?></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /* ============================================================
       RENDER: BANK LOGS PAGE
    ============================================================ */

    public function render_bank_logs_page() {
        global $wpdb;
        $prefix   = $wpdb->prefix . 'apfa_';
        $bank_logs = $wpdb->get_results( "SELECT * FROM {$prefix}bank_logs ORDER BY created_at DESC" );
        ?>
        <div class="wrap apfa-admin-wrap">
            <h1>🏦 <?php _e( 'Bank SMS Logs', 'apfa' ); ?></h1>
            <p><?php _e( 'Webhook URL for SMS Forwarder App:', 'apfa' ); ?>
               <code><?php echo esc_url( rest_url( 'apfa/v1/webhook/sms' ) ); ?></code>
            </p>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e( 'ID', 'apfa' ); ?></th>
                        <th><?php _e( 'Sender', 'apfa' ); ?></th>
                        <th><?php _e( 'Trx ID', 'apfa' ); ?></th>
                        <th><?php _e( 'Amount', 'apfa' ); ?></th>
                        <th><?php _e( 'Status', 'apfa' ); ?></th>
                        <th><?php _e( 'Date/Time', 'apfa' ); ?></th>
                        <th><?php _e( 'Raw SMS', 'apfa' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php if ( $bank_logs ) : ?>
                    <?php foreach ( $bank_logs as $log ) : ?>
                        <tr>
                            <td><?php echo esc_html( $log->ID ); ?></td>
                            <td><?php echo esc_html( $log->Sender_ID ); ?></td>
                            <td><code><?php echo esc_html( $log->Trx_ID ); ?></code></td>
                            <td>PKR <?php echo esc_html( number_format( $log->Amount, 2 ) ); ?></td>
                            <td><?php echo $this->status_badge( $log->Status ); ?></td>
                            <td><?php echo esc_html( date_i18n( 'd M Y H:i', strtotime( $log->Date_Time ) ) ); ?></td>
                            <td style="max-width:300px;word-break:break-word;">
                                <small><?php echo esc_html( wp_trim_words( $log->Raw_SMS, 20 ) ); ?></small>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr><td colspan="7" style="text-align:center;"><?php _e( 'No bank logs yet.', 'apfa' ); ?></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /* ============================================================
       RENDER: REPORTS PAGE
    ============================================================ */

    public function render_reports_page() {
        global $wpdb;
        $prefix = $wpdb->prefix . 'apfa_';

        // Monthly revenue for current year
        $current_year   = date( 'Y' );
        $monthly_data   = $wpdb->get_results( $wpdb->prepare(
            "SELECT MONTH(created_at) as month, SUM(Submitted_Amount) as total
             FROM {$prefix}fee_submissions
             WHERE Status = 'Verified' AND YEAR(created_at) = %d
             GROUP BY MONTH(created_at)
             ORDER BY month",
            $current_year
        ) );

        $monthly_labels  = array();
        $monthly_values  = array();
        $months_map      = array(
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec',
        );
        foreach ( $monthly_data as $row ) {
            $monthly_labels[] = $months_map[ (int) $row->month ];
            $monthly_values[] = floatval( $row->total );
        }

        // Payment method distribution
        $bank_stats = $wpdb->get_results(
            "SELECT Sender_ID, COUNT(*) as count, SUM(Amount) as total
             FROM {$prefix}bank_logs WHERE Status = 'Matched'
             GROUP BY Sender_ID"
        );

        // Top students by payments
        $top_students = $wpdb->get_results(
            "SELECT s.Full_Name, s.Phone, s.Course_Name,
                    SUM(fs.Submitted_Amount) as paid,
                    s.Total_Fee
             FROM {$prefix}fee_submissions fs
             JOIN {$prefix}students s ON s.Phone = fs.Student_Phone
             WHERE fs.Status = 'Verified'
             GROUP BY fs.Student_Phone
             ORDER BY paid DESC
             LIMIT 10"
        );
        ?>
        <div class="wrap apfa-admin-wrap">
            <h1>📊 <?php _e( 'Financial Reports', 'apfa' ); ?></h1>

            <div class="apfa-two-col">
                <div class="apfa-card">
                    <h2><?php echo esc_html( sprintf( __( 'Monthly Revenue %s', 'apfa' ), $current_year ) ); ?></h2>
                    <?php if ( $monthly_data ) : ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php _e( 'Month', 'apfa' ); ?></th>
                                    <th><?php _e( 'Revenue (PKR)', 'apfa' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ( $monthly_data as $row ) : ?>
                                    <tr>
                                        <td><?php echo esc_html( $months_map[ (int) $row->month ] . ' ' . $current_year ); ?></td>
                                        <td>PKR <?php echo esc_html( number_format( $row->total, 2 ) ); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else : ?>
                        <p><?php _e( 'No verified payments yet.', 'apfa' ); ?></p>
                    <?php endif; ?>
                </div>

                <div class="apfa-card">
                    <h2><?php _e( 'Payment Method Distribution', 'apfa' ); ?></h2>
                    <?php if ( $bank_stats ) : ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php _e( 'Bank/Service', 'apfa' ); ?></th>
                                    <th><?php _e( 'Transactions', 'apfa' ); ?></th>
                                    <th><?php _e( 'Total (PKR)', 'apfa' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ( $bank_stats as $stat ) : ?>
                                    <tr>
                                        <td><?php echo esc_html( $stat->Sender_ID ?: 'Unknown' ); ?></td>
                                        <td><?php echo esc_html( $stat->count ); ?></td>
                                        <td>PKR <?php echo esc_html( number_format( $stat->total, 2 ) ); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else : ?>
                        <p><?php _e( 'No matched bank logs yet.', 'apfa' ); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="apfa-card" style="margin-top:20px;">
                <h2><?php _e( 'Top Paying Students', 'apfa' ); ?></h2>
                <?php if ( $top_students ) : ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e( 'Name', 'apfa' ); ?></th>
                                <th><?php _e( 'Phone', 'apfa' ); ?></th>
                                <th><?php _e( 'Course', 'apfa' ); ?></th>
                                <th><?php _e( 'Paid (PKR)', 'apfa' ); ?></th>
                                <th><?php _e( 'Total Fee (PKR)', 'apfa' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $top_students as $st ) : ?>
                                <tr>
                                    <td><?php echo esc_html( $st->Full_Name ); ?></td>
                                    <td><?php echo esc_html( $st->Phone ); ?></td>
                                    <td><?php echo esc_html( $st->Course_Name ); ?></td>
                                    <td>PKR <?php echo esc_html( number_format( $st->paid, 2 ) ); ?></td>
                                    <td>PKR <?php echo esc_html( number_format( $st->Total_Fee, 2 ) ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <p><?php _e( 'No data yet.', 'apfa' ); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /* ============================================================
       RENDER: SETTINGS PAGE
    ============================================================ */

    public function render_settings_page() {
        ?>
        <div class="wrap apfa-admin-wrap">
            <h1>⚙️ <?php _e( 'Academy PayFlow Settings', 'apfa' ); ?></h1>
            
            <form method="POST" action="options.php">
                <?php settings_fields( 'apfa-settings-group' ); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="webhook_secret"><?php _e( 'Webhook Secret Key', 'apfa' ); ?></label></th>
                        <td>
                            <input type="password" name="apfa_webhook_secret" id="webhook_secret"
                                value="<?php echo esc_attr( get_option( 'apfa_webhook_secret' ) ); ?>" class="regular-text" autocomplete="off" />
                            <p class="description"><?php _e( 'Used to authenticate incoming SMS webhook requests.', 'apfa' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="bank_name"><?php _e( 'Default Bank/Service', 'apfa' ); ?></label></th>
                        <td>
                            <select name="apfa_bank_name" id="bank_name">
                                <option value="Meezan" <?php selected( get_option( 'apfa_bank_name' ), 'Meezan' ); ?>>Meezan Bank</option>
                                <option value="Easypaisa" <?php selected( get_option( 'apfa_bank_name' ), 'Easypaisa' ); ?>>Easypaisa</option>
                                <option value="JazzCash" <?php selected( get_option( 'apfa_bank_name' ), 'JazzCash' ); ?>>JazzCash</option>
                            </select>
                        </td>
                    </tr>
                </table>

                <div style="background:#f5f5f5;padding:15px;border-radius:5px;margin-top:20px;">
                    <h3><?php _e( 'SMS Webhook URL', 'apfa' ); ?></h3>
                    <p><?php _e( 'Configure your SMS forwarder app to POST to this URL:', 'apfa' ); ?></p>
                    <code style="display:block;background:#fff;padding:10px;border-radius:3px;margin-top:10px;word-break:break-all;">
                        <?php echo esc_url( rest_url( 'apfa/v1/webhook/sms' ) ); ?>
                    </code>
                    <p style="margin-top:10px;"><?php _e( 'Expected JSON fields: message, sender, trx_id, amount, timestamp', 'apfa' ); ?></p>
                </div>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /* ============================================================
       HELPER
    ============================================================ */

    private function status_badge( $status ) {
        $colors = array(
            'Pending'   => '#f59e0b',
            'Verified'  => '#10b981',
            'Matched'   => '#10b981',
            'Rejected'  => '#ef4444',
            'Unmatched' => '#6b7280',
        );
        $color = isset( $colors[ $status ] ) ? $colors[ $status ] : '#6b7280';
        return '<span style="background:' . esc_attr( $color ) . ';color:#fff;padding:2px 8px;border-radius:12px;font-size:12px;">'
            . esc_html( $status ) . '</span>';
    }
}
