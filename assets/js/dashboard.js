/**
 * Academy PayFlow - Frontend Dashboard
 * Main JavaScript functionality
 */

(function($) {
    'use strict';

    const APFA = {
        // Configuration
        config: {
            ajax_url: typeof apfaConfig !== 'undefined' ? apfaConfig.ajax_url : '',
            nonce: typeof apfaConfig !== 'undefined' ? apfaConfig.nonce : '',
        },

        // Initialize on page load
        init: function() {
            console.log('APFA Dashboard initialized');
            this.bindEvents();
            this.loadStudentData();
            this.loadTransactions();
            this.setupFileUpload();
        },

        // Bind all events
        bindEvents: function() {
            // Navigation
            $(document).on('click', '.nav-link', this.switchSection.bind(this));
            
            // Form submission
            $(document).on('submit', '#fee-submission-form', this.submitFee.bind(this));
            
            // Modal close
            $(document).on('click', '.modal-close', this.closeModal.bind(this));
            
            // Amount input change - update receipt
            $(document).on('keyup', '#amount', this.updateReceipt.bind(this));

            // Language toggle
            $(document).on('click', '.lang-btn', this.changeLang.bind(this));
        },

        // Switch between sections
        switchSection: function(e) {
            const section = $(e.target).data('section');
            if (!section) return;

            // Hide all sections
            $('.dashboard-section').removeClass('active');
            $('.nav-link').removeClass('active');

            // Show selected section
            $('#' + section).addClass('active');
            $(e.target).addClass('active');

            // Scroll to top
            window.scrollTo(0, 0);
        },

        // Load student data via AJAX
        loadStudentData: function() {
            const self = this;
            
            if (!this.config.ajax_url) {
                console.log('No AJAX URL configured');
                this.setDefaultData();
                return;
            }

            $.ajax({
                url: this.config.ajax_url,
                type: 'POST',
                data: {
                    action: 'apfa_get_student_data',
                    nonce: this.config.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.displayStudentData(response.data);
                    } else {
                        console.error('Error loading student data:', response);
                        self.setDefaultData();
                    }
                },
                error: function(error) {
                    console.error('AJAX error:', error);
                    self.setDefaultData();
                }
            });
        },

        // Display student data
        displayStudentData: function(data) {
            $('#student-name-readonly').val(data.full_name || '-');
            $('#student-id-readonly').val(data.phone || '-');
            $('#profile-name').text(data.full_name || '-');
            $('#profile-phone').text(data.phone || '-');
            $('#profile-course').text(data.course_name || '-');
            $('#profile-total-fee').text('PKR ' + this.formatNumber(data.total_fee) || 'PKR 0');
            $('#profile-balance').text('PKR ' + this.formatNumber(data.balance) || 'PKR 0');
            $('#profile-enrolled').text(this.formatDate(data.created_at) || '-');

            // Update receipt preview
            $('#receipt-student').text(data.full_name || 'Student Name');
            $('#receipt-id').text(data.phone || 'ID');
        },

        // Set default data (fallback)
        setDefaultData: function() {
            $('#student-name-readonly').val('Ali Khan');
            $('#student-id-readonly').val('123456');
            $('#profile-name').text('Ali Khan');
            $('#profile-phone').text('0300-1234567');
            $('#profile-course').text('Web Development');
            $('#profile-total-fee').text('PKR 50,000');
            $('#profile-balance').text('PKR 0');
            $('#profile-enrolled').text('20 Jan 2024');
        },

        // Load transactions
        loadTransactions: function() {
            const self = this;

            if (!this.config.ajax_url) {
                console.log('No AJAX URL configured for transactions');
                return;
            }

            $.ajax({
                url: this.config.ajax_url,
                type: 'POST',
                data: {
                    action: 'apfa_get_transactions',
                    nonce: this.config.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.displayTransactions(response.data);
                    } else {
                        console.error('Error loading transactions:', response);
                    }
                },
                error: function(error) {
                    console.error('AJAX error:', error);
                }
            });
        },

        // Display transactions in table
        displayTransactions: function(data) {
            const tbody = $('#recent-transactions');
            tbody.empty();

            if (data.length === 0) {
                tbody.append('<tr><td colspan="5" style="text-align: center; color: #6b7280;">No transactions yet</td></tr>');
                return;
            }

            data.slice(0, 5).forEach(function(transaction) {
                const statusBadge = '<span class="status-badge status-' + transaction.status.toLowerCase() + '">' + transaction.status + '</span>';
                const receiptLink = transaction.receipt ? '<a href="' + transaction.receipt + '" target="_blank" class="link">View</a>' : '-';
                
                const row = '<tr>' +
                    '<td>' + transaction.date + '</td>' +
                    '<td>Payment</td>' +
                    '<td>$' + parseFloat(transaction.amount).toFixed(2) + '</td>' +
                    '<td>' + statusBadge + '</td>' +
                    '<td>' + receiptLink + '</td>' +
                    '</tr>';
                
                tbody.append(row);
            });
        },

        // Setup file upload
        setupFileUpload: function() {
            const fileInput = document.getElementById('receipt-image');
            if (fileInput) {
                fileInput.addEventListener('change', function(e) {
                    const fileName = e.target.files[0]?.name || 'No file chosen';
                    const fileNameElement = document.getElementById('file-name');
                    if (fileNameElement) {
                        fileNameElement.textContent = fileName;
                    }
                });
            }
        },

        // Submit fee form
        submitFee: function(e) {
            e.preventDefault();

            const amount = $('#amount').val();
            const trxId = $('#amount').attr('placeholder') || 'TXN' + Date.now();

            if (!amount || amount <= 0) {
                alert('Please enter a valid amount');
                return;
            }

            // For demo purposes - show success modal
            this.showModal();
            
            // Reset form
            $('#fee-submission-form')[0].reset();
            $('#file-name').textContent = 'No file chosen';

            // Load transactions again
            setTimeout(() => this.loadTransactions(), 1000);
        },

        // Show success modal
        showModal: function() {
            const modal = document.getElementById('success-modal');
            if (modal) {
                modal.classList.add('show');
                
                // Auto close after 3 seconds
                setTimeout(() => {
                    this.closeModal();
                }, 3000);
            }
        },

        // Close modal
        closeModal: function() {
            const modal = document.getElementById('success-modal');
            if (modal) {
                modal.classList.remove('show');
            }
        },

        // Update receipt preview
        updateReceipt: function(e) {
            const amount = parseFloat($(e.target).val()) || 0;
            const total = amount + 30; // Add library charges
            
            $('#receipt-amount').text('$' + amount.toFixed(2));
            $('#receipt-total').text('$' + total.toFixed(2));
        },

        // Change language
        changeLang: function(e) {
            e.preventDefault();
            const lang = $(e.target).data('lang');
            localStorage.setItem('apfa_language', lang);
            
            if (lang === 'ur') {
                $('body').addClass('apfa-urdu');
                $('body').attr('dir', 'rtl');
                $('html').attr('lang', 'ur');
            } else {
                $('body').removeClass('apfa-urdu');
                $('body').attr('dir', 'ltr');
                $('html').attr('lang', 'en');
            }
        },

        // Format currency
        formatNumber: function(num) {
            if (!num) return '0';
            return parseFloat(num).toLocaleString();
        },

        // Format date
        formatDate: function(dateStr) {
            if (!dateStr) return '-';
            const date = new Date(dateStr);
            return date.toLocaleDateString('en-GB', { 
                day: '2-digit', 
                month: 'short', 
                year: 'numeric' 
            });
        }
    };

    // Initialize when DOM is ready
    $(document).ready(function() {
        APFA.init();
    });

})(jQuery);

// Global functions for inline onclick handlers
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

function changeLang(e, lang) {
    e.preventDefault();
    localStorage.setItem('apfa_language', lang);
    if (lang === 'ur') {
        document.body.classList.add('apfa-urdu');
        document.body.dir = 'rtl';
    } else {
        document.body.classList.remove('apfa-urdu');
        document.body.dir = 'ltr';
    }
}
