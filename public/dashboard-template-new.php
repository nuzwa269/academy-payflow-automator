<!DOCTYPE html>
<html lang="<?php echo esc_attr( get_locale() ); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php _e( 'PayFlow Portal - Academy Fee Dashboard', 'apfa' ); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php wp_head(); ?>
</head>
<body class="apfa-portal-body">

<div class="apfa-wrapper">
    <!-- Top Header/Navigation Bar -->
    <header class="apfa-header">
        <div class="header-container">
            <div class="logo">
                <span class="logo-icon">💳</span>
                <span class="logo-text">PayFlow</span>
            </div>

            <nav class="main-nav">
                <a href="#dashboard" class="nav-link active" data-section="dashboard" onclick="switchSection(event, 'dashboard')">Dashboard</a>
                <a href="#transactions" class="nav-link" data-section="history" onclick="switchSection(event, 'history')">Transactions</a>
                <a href="#reports" class="nav-link" data-section="profile" onclick="switchSection(event, 'profile')">Reports</a>
            </nav>

            <div class="header-actions">
                <div class="language-toggle">
                    <button class="lang-btn" data-lang="ur" title="اردو">🇵🇰</button>
                    <button class="lang-btn" data-lang="en" title="English">🇬🇧</button>
                </div>
                <button class="notification-icon">
                    <i class="fas fa-bell"></i>
                </button>
                <div class="user-avatar">👤</div>
            </div>
        </div>
    </header>

    <div class="content-wrapper">
        <!-- Dashboard Section -->
        <section id="dashboard" class="dashboard-section active">
            <div class="dashboard-grid">
                <!-- Left Column -->
                <div class="col-left">
                    <div class="card-white">
                        <div class="card-title">
                            <h2><?php _e( 'Recent Transactions', 'apfa' ); ?></h2>
                        </div>
                        <div class="card-body">
                            <table class="transaction-table" id="recent-transactions-table">
                                <thead>
                                    <tr>
                                        <th><?php _e( 'Date', 'apfa' ); ?></th>
                                        <th><?php _e( 'Trx ID', 'apfa' ); ?></th>
                                        <th><?php _e( 'Amount', 'apfa' ); ?></th>
                                        <th><?php _e( 'Status', 'apfa' ); ?></th>
                                        <th><?php _e( 'Receipt', 'apfa' ); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="recent-transactions">
                                    <!-- Loaded via AJAX -->
                                </tbody>
                            </table>
                            <div class="view-all-container">
                                <a href="#" class="view-all-link" onclick="switchSection(event, 'history')"><?php _e( 'View All >', 'apfa' ); ?></a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="col-right">
                    <!-- Submit Fee Card -->
                    <div class="card-white">
                        <div class="card-title">
                            <h2><?php _e( 'Submit Fee', 'apfa' ); ?></h2>
                        </div>
                        <div class="card-body">
                            <form id="fee-submission-form" class="fee-form">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label><?php _e( 'Student Name', 'apfa' ); ?></label>
                                        <input type="text" id="student-name-readonly" class="form-control" readonly placeholder="—">
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label><?php _e( 'Phone Number', 'apfa' ); ?></label>
                                        <input type="text" id="student-phone-readonly" name="student_phone" class="form-control" readonly placeholder="—">
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label><?php _e( 'Transaction ID (Trx ID)', 'apfa' ); ?> <span style="color:red">*</span></label>
                                        <input type="text" id="trx-id" name="trx_id" class="form-control" placeholder="<?php esc_attr_e( 'e.g. TRX123456789', 'apfa' ); ?>" required>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label><?php _e( 'Fee Type', 'apfa' ); ?></label>
                                        <select class="form-control" name="fee_type">
                                            <option><?php _e( 'Select Fee Type', 'apfa' ); ?></option>
                                            <option><?php _e( 'Tuition Fee', 'apfa' ); ?></option>
                                            <option><?php _e( 'Library Charges', 'apfa' ); ?></option>
                                            <option><?php _e( 'Exam Fee', 'apfa' ); ?></option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label><?php _e( 'Amount (PKR)', 'apfa' ); ?> <span style="color:red">*</span></label>
                                        <input type="number" id="amount" name="amount" class="form-control" placeholder="<?php esc_attr_e( 'Enter Amount', 'apfa' ); ?>" step="0.01" min="1" required>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label><?php _e( 'Upload Receipt', 'apfa' ); ?></label>
                                        <div class="file-upload-box">
                                            <input type="file" id="receipt-image" name="receipt_image" class="file-input" accept="image/*">
                                            <span class="file-text"><?php _e( 'Choose File', 'apfa' ); ?><br><small id="file-name"><?php _e( 'No file chosen', 'apfa' ); ?></small></span>
                                        </div>
                                    </div>
                                </div>

                                <input type="hidden" name="nonce" id="apfa-nonce" value="<?php echo esc_attr( wp_create_nonce( 'apfa_nonce' ) ); ?>">
                                <div id="fee-form-msg" style="margin:8px 0;"></div>
                                <button type="submit" class="btn btn-blue btn-full"><?php _e( 'Submit Payment', 'apfa' ); ?></button>
                            </form>
                        </div>
                    </div>

                    <!-- Receipt Preview Card -->
                    <div class="card-white receipt-card">
                        <div class="card-title">
                            <h2><?php _e( 'Receipt Preview', 'apfa' ); ?></h2>
                        </div>
                        <div class="receipt-box">
                            <div class="receipt-header">
                                <h3>PAYFLOW ACADEMY</h3>
                                <p><?php _e( 'RECEIPT', 'apfa' ); ?></p>
                            </div>

                            <div class="receipt-detail">
                                <p><strong><?php _e( 'Student:', 'apfa' ); ?></strong> <span id="receipt-student">—</span></p>
                                <p><strong><?php _e( 'Phone:', 'apfa' ); ?></strong> <span id="receipt-phone">—</span></p>
                                <p><strong><?php _e( 'Date:', 'apfa' ); ?></strong> <span id="receipt-date"><?php echo esc_html( date_i18n( 'd/m/Y' ) ); ?></span></p>
                            </div>

                            <div class="receipt-items">
                                <div class="receipt-item">
                                    <span id="receipt-fee-type"><?php _e( 'Fee', 'apfa' ); ?></span>
                                    <span id="receipt-amount">PKR 0.00</span>
                                </div>
                            </div>

                            <div class="receipt-total">
                                <strong><?php _e( 'Total Amount:', 'apfa' ); ?></strong>
                                <strong id="receipt-total">PKR 0.00</strong>
                            </div>

                            <div class="receipt-footer">
                                <p id="receipt-trx"><?php _e( 'Trx ID: —', 'apfa' ); ?></p>
                                <p class="thank-you"><em><?php _e( 'Thank you!', 'apfa' ); ?></em></p>
                                <p class="signature"><?php _e( 'Authorized Signature', 'apfa' ); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- History Section -->
        <section id="history" class="dashboard-section">
            <div class="section-header">
                <h1><?php _e( 'Payment History', 'apfa' ); ?></h1>
                <p><?php _e( 'All your submitted payments', 'apfa' ); ?></p>
            </div>
            <div class="card-white">
                <div class="card-body">
                    <table class="transaction-table" id="payment-history">
                        <thead>
                            <tr>
                                <th><?php _e( 'Date', 'apfa' ); ?></th>
                                <th><?php _e( 'Trx ID', 'apfa' ); ?></th>
                                <th><?php _e( 'Amount', 'apfa' ); ?></th>
                                <th><?php _e( 'Status', 'apfa' ); ?></th>
                                <th><?php _e( 'Receipt', 'apfa' ); ?></th>
                            </tr>
                        </thead>
                        <tbody id="history-tbody">
                            <!-- Loaded via AJAX -->
                        </tbody>
                    </table>
                    <div id="history-empty" style="display:none;text-align:center;padding:20px;color:#6b7280;">
                        <?php _e( 'No payment submissions yet.', 'apfa' ); ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- Profile Section -->
        <section id="profile" class="dashboard-section">
            <div class="section-header">
                <h1><?php _e( 'Student Profile', 'apfa' ); ?></h1>
                <p><?php _e( 'Your course information', 'apfa' ); ?></p>
            </div>
            <div class="card-white">
                <div class="card-body profile-body">
                    <div id="profile-not-linked" style="display:none;text-align:center;padding:20px;color:#6b7280;">
                        <p><?php _e( 'Your account is not linked to a student record. Please contact admin.', 'apfa' ); ?></p>
                    </div>
                    <div class="profile-grid" id="profile-grid">
                        <div class="profile-field">
                            <label><?php _e( 'Student Name', 'apfa' ); ?></label>
                            <p id="profile-name">—</p>
                        </div>
                        <div class="profile-field">
                            <label><?php _e( 'Phone Number', 'apfa' ); ?></label>
                            <p id="profile-phone">—</p>
                        </div>
                        <div class="profile-field">
                            <label><?php _e( 'Course Name', 'apfa' ); ?></label>
                            <p id="profile-course">—</p>
                        </div>
                        <div class="profile-field">
                            <label><?php _e( 'Total Fee', 'apfa' ); ?></label>
                            <p id="profile-total-fee">PKR 0</p>
                        </div>
                        <div class="profile-field">
                            <label><?php _e( 'Remaining Balance', 'apfa' ); ?></label>
                            <p id="profile-balance">PKR 0</p>
                        </div>
                        <div class="profile-field">
                            <label><?php _e( 'Enrollment Date', 'apfa' ); ?></label>
                            <p id="profile-enrolled">—</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Footer -->
    <footer class="apfa-portal-footer">
        <p>Powered by Academy PayFlow | Designed by Coach Pro AI</p>
    </footer>
</div>

<!-- Success Modal -->
<div id="success-modal" class="modal">
    <div class="modal-box">
        <div class="modal-icon">✓</div>
        <h2><?php _e( 'Payment Submitted', 'apfa' ); ?></h2>
        <p><?php _e( 'Your payment has been submitted for verification.', 'apfa' ); ?></p>
        <button class="btn btn-blue" onclick="closeModal('success-modal')"><?php _e( 'Done', 'apfa' ); ?></button>
    </div>
</div>

<!-- Error Modal -->
<div id="error-modal" class="modal">
    <div class="modal-box">
        <div class="modal-icon" style="background:#ef4444;">✗</div>
        <h2><?php _e( 'Submission Failed', 'apfa' ); ?></h2>
        <p id="error-modal-msg"></p>
        <button class="btn btn-blue" onclick="closeModal('error-modal')"><?php _e( 'Close', 'apfa' ); ?></button>
    </div>
</div>

<?php wp_footer(); ?>

</body>
</html>
