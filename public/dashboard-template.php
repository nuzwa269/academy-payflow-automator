<!DOCTYPE html>
<html lang="<?php echo get_locale(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php _e('PayFlow Portal - Academy Fee Dashboard', 'apfa'); ?></title>
    <?php wp_head(); ?>
</head>
<body class="apfa-portal-body">

<div class="apfa-container">
    
    <!-- Sidebar -->
    <aside class="apfa-sidebar">
        <div class="apfa-sidebar-header">
            <h2>PayFlow</h2>
            <div class="apfa-lang-selector">
                <button class="apfa-lang-btn" data-lang="en" title="English">🇬🇧</button>
                <button class="apfa-lang-btn" data-lang="ur" title="Urdu">🇵🇰</button>
            </div>
        </div>
        <nav class="apfa-nav">
            <a href="#dashboard" class="apfa-nav-item active" data-section="dashboard">
                <span class="icon">📊</span> <span class="label"><?php _e('Dashboard', 'apfa'); ?></span>
            </a>
            <a href="#submit-fee" class="apfa-nav-item" data-section="submit-fee">
                <span class="icon">💳</span> <span class="label"><?php _e('Submit Fee', 'apfa'); ?></span>
            </a>
            <a href="#history" class="apfa-nav-item" data-section="history">
                <span class="icon">📜</span> <span class="label"><?php _e('History', 'apfa'); ?></span>
            </a>
            <a href="#profile" class="apfa-nav-item" data-section="profile">
                <span class="icon">👤</span> <span class="label"><?php _e('Profile', 'apfa'); ?></span>
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="apfa-main">
        
        <!-- Dashboard Section -->
        <section id="dashboard" class="apfa-section active">
            <div class="apfa-header">
                <h1><?php _e('Welcome Back', 'apfa'); ?></h1>
                <p class="apfa-subtitle"><?php _e('Monitor your academy fee payment status', 'apfa'); ?></p>
            </div>

            <!-- Stats Cards -->
            <div class="apfa-stats">
                <div class="apfa-stat-card glassmorphic">
                    <div class="stat-icon">💰</div>
                    <div class="stat-content">
                        <p class="stat-label"><?php _e('Total Fee', 'apfa'); ?></p>
                        <h3 class="stat-value" id="total-fee">PKR 0</h3>
                    </div>
                </div>
                <div class="apfa-stat-card glassmorphic">
                    <div class="stat-icon">✅</div>
                    <div class="stat-content">
                        <p class="stat-label"><?php _e('Verified Payments', 'apfa'); ?></p>
                        <h3 class="stat-value" id="verified-amount">PKR 0</h3>
                    </div>
                </div>
                <div class="apfa-stat-card glassmorphic">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-content">
                        <p class="stat-label"><?php _e('Pending Balance', 'apfa'); ?></p>
                        <h3 class="stat-value" id="pending-balance">PKR 0</h3>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="apfa-card">
                <h2><?php _e('Recent Transactions', 'apfa'); ?></h2>
                <table class="apfa-table" id="recent-transactions">
                    <thead>
                        <tr>
                            <th><?php _e('Date', 'apfa'); ?></th>
                            <th><?php _e('Transaction ID', 'apfa'); ?></th>
                            <th><?php _e('Amount', 'apfa'); ?></th>
                            <th><?php _e('Status', 'apfa'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Loaded via AJAX -->
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Submit Fee Section -->
        <section id="submit-fee" class="apfa-section">
            <div class="apfa-header">
                <h1><?php _e('Submit Your Payment', 'apfa'); ?></h1>
                <p class="apfa-subtitle"><?php _e('Enter your transaction details', 'apfa'); ?></p>
            </div>

            <div class="apfa-card">
                <form id="fee-submission-form" class="apfa-form">
                    <div class="form-group">
                        <label for="trx-id"><?php _e('Transaction ID', 'apfa'); ?> <span class="required">*</span></label>
                        <input type="text" id="trx-id" name="trx_id" placeholder="e.g., TXN123456" required>
                    </div>

                    <div class="form-group">
                        <label for="amount"><?php _e('Amount (PKR)', 'apfa'); ?> <span class="required">*</span></label>
                        <input type="number" id="amount" name="amount" placeholder="0.00" step="0.01" required>
                    </div>

                    <div class="form-group">
                        <label for="receipt-image"><?php _e('Receipt Screenshot', 'apfa'); ?> <span class="required">*</span></label>
                        <div class="file-upload">
                            <input type="file" id="receipt-image" name="receipt_image" accept="image/*" required>
                            <span class="file-label"><?php _e('Click to upload or drag & drop', 'apfa'); ?></span>
                        </div>
                    </div>

                    <button type="submit" class="apfa-btn apfa-btn-primary"><?php _e('Submit Payment', 'apfa'); ?></button>
                </form>
            </div>
        </section>

        <!-- History Section -->
        <section id="history" class="apfa-section">
            <div class="apfa-header">
                <h1><?php _e('Payment History', 'apfa'); ?></h1>
                <p class="apfa-subtitle"><?php _e('All your submitted payments', 'apfa'); ?></p>
            </div>

            <div class="apfa-card">
                <table class="apfa-table" id="payment-history">
                    <thead>
                        <tr>
                            <th><?php _e('Date', 'apfa'); ?></th>
                            <th><?php _e('Amount', 'apfa'); ?></th>
                            <th><?php _e('Status', 'apfa'); ?></th>
                            <th><?php _e('Receipt', 'apfa'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Loaded via AJAX -->
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Profile Section -->
        <section id="profile" class="apfa-section">
            <div class="apfa-header">
                <h1><?php _e('Student Profile', 'apfa'); ?></h1>
                <p class="apfa-subtitle"><?php _e('Your course information', 'apfa'); ?></p>
            </div>

            <div class="apfa-card">
                <div class="profile-info">
                    <p><strong><?php _e('Name', 'apfa'); ?>:</strong> <span id="profile-name">-</span></p>
                    <p><strong><?php _e('Phone', 'apfa'); ?>:</strong> <span id="profile-phone">-</span></p>
                    <p><strong><?php _e('Course', 'apfa'); ?>:</strong> <span id="profile-course">-</span></p>
                    <p><strong><?php _e('Enrolled', 'apfa'); ?>:</strong> <span id="profile-enrolled">-</span></p>
                </div>
            </div>
        </section>

    </main>

</div>

<!-- Success Modal -->
<div id="success-modal" class="apfa-modal">
    <div class="apfa-modal-content">
        <span class="apfa-close">&times;</span>
        <div class="modal-icon">✅</div>
        <h2 id="modal-title"><?php _e('Payment Submitted', 'apfa'); ?></h2>
        <p id="modal-message"><?php _e('Your payment has been submitted for verification.', 'apfa'); ?></p>
        <button class="apfa-btn apfa-btn-primary" onclick="location.reload()"><?php _e('Done', 'apfa'); ?></button>
    </div>
</div>

<!-- Footer -->
<footer class="apfa-footer">
    <p><?php _e('Powered by Academy PayFlow | Designed by Coach Pro AI', 'apfa'); ?></p>
</footer>

<?php wp_footer(); ?>

</body>
</html>
