<!DOCTYPE html>
<html lang="<?php echo get_locale(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php _e('PayFlow Portal - Academy Fee Dashboard', 'apfa'); ?></title>
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
                <a href="#transactions" class="nav-link" data-section="submit-fee" onclick="switchSection(event, 'submit-fee')">Transactions</a>
                <a href="#reports" class="nav-link" data-section="history" onclick="switchSection(event, 'history')">Reports</a>
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
                            <h2>Recent Transactions</h2>
                        </div>
                        <div class="card-body">
                            <table class="transaction-table" id="recent-transactions-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Description</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Receipt</th>
                                    </tr>
                                </thead>
                                <tbody id="recent-transactions">
                                    <!-- AJAX Data -->
                                </tbody>
                            </table>
                            <div class="view-all-container">
                                <a href="#" class="view-all-link" onclick="switchSection(event, 'history')">View All ></a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="col-right">
                    <!-- Submit Fee Card -->
                    <div class="card-white">
                        <div class="card-title">
                            <h2>Submit Fee</h2>
                        </div>
                        <div class="card-body">
                            <form id="fee-submission-form" class="fee-form">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Student Name</label>
                                        <input type="text" id="student-name-readonly" class="form-control" readonly>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Student ID</label>
                                        <input type="text" id="student-id-readonly" class="form-control" readonly>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Fee Type</label>
                                        <select class="form-control">
                                            <option>Select Fee Type</option>
                                            <option>Tuition Fee</option>
                                            <option>Library Charges</option>
                                            <option>Exam Fee</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Amount</label>
                                        <input type="number" id="amount" name="amount" class="form-control" placeholder="Enter Amount" step="0.01" required>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Upload Receipt</label>
                                        <div class="file-upload-box">
                                            <input type="file" id="receipt-image" name="receipt_image" class="file-input" accept="image/*" required>
                                            <span class="file-text">Choose File <br> <small>No file chosen</small></span>
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-blue btn-full">Submit Payment</button>
                            </form>
                        </div>
                    </div>

                    <!-- Receipt Preview Card -->
                    <div class="card-white receipt-card">
                        <div class="card-title">
                            <h2>Receipt Preview</h2>
                        </div>
                        <div class="receipt-box">
                            <div class="receipt-header">
                                <h3>PAYFLOW ACADEMY</h3>
                                <p>RECEIPT</p>
                            </div>

                            <div class="receipt-detail">
                                <p><strong>Student:</strong> <span id="receipt-student">Ali Khan</span></p>
                                <p><strong>ID:</strong> <span id="receipt-id">123456</span></p>
                                <p><strong>Date:</strong> <span id="receipt-date">20/01/2024</span></p>
                            </div>

                            <div class="receipt-items">
                                <div class="receipt-item">
                                    <span>Tuition Fee</span>
                                    <span id="receipt-amount">$500.00</span>
                                </div>
                                <div class="receipt-item">
                                    <span>Library Charges</span>
                                    <span>$30.00</span>
                                </div>
                            </div>

                            <div class="receipt-total">
                                <strong>Total Amount:</strong>
                                <strong id="receipt-total">$530.00</strong>
                            </div>

                            <div class="receipt-footer">
                                <p>Paid via: Credit Card</p>
                                <p class="thank-you"><em>Thank you!</em></p>
                                <p class="signature">Authorized Signature</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- History Section -->
        <section id="history" class="dashboard-section">
            <div class="section-header">
                <h1>Payment History</h1>
                <p>All your submitted payments</p>
            </div>
            <div class="card-white">
                <div class="card-body">
                    <table class="transaction-table" id="payment-history">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Receipt</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- AJAX Data -->
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- Profile Section -->
        <section id="profile" class="dashboard-section">
            <div class="section-header">
                <h1>Student Profile</h1>
                <p>Your course information</p>
            </div>
            <div class="card-white">
                <div class="card-body profile-body">
                    <div class="profile-grid">
                        <div class="profile-field">
                            <label>Student Name</label>
                            <p id="profile-name">-</p>
                        </div>
                        <div class="profile-field">
                            <label>Phone Number</label>
                            <p id="profile-phone">-</p>
                        </div>
                        <div class="profile-field">
                            <label>Course Name</label>
                            <p id="profile-course">-</p>
                        </div>
                        <div class="profile-field">
                            <label>Total Fee</label>
                            <p id="profile-total-fee">PKR 0</p>
                        </div>
                        <div class="profile-field">
                            <label>Remaining Balance</label>
                            <p id="profile-balance">PKR 0</p>
                        </div>
                        <div class="profile-field">
                            <label>Enrollment Date</label>
                            <p id="profile-enrolled">-</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Success Modal -->
<div id="success-modal" class="modal">
    <div class="modal-box">
        <div class="modal-icon">✓</div>
        <h2>Payment Submitted</h2>
        <p>Your payment has been submitted for verification</p>
        <button class="btn btn-blue" onclick="closeModal()">Done</button>
    </div>
</div>

<?php wp_footer(); ?>

<script>
function switchSection(e, section) {
    e.preventDefault();
    document.querySelectorAll('.dashboard-section').forEach(s => s.classList.remove('active'));
    document.getElementById(section).classList.add('active');
    
    document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
    event.target.classList.add('active');
}

function closeModal() {
    document.getElementById('success-modal').classList.remove('show');
}
</script>

</body>
</html>
