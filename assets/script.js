/**
 * Academy PayFlow Automator - Main Script
 * Handles core functionality and integrations
 */

console.log('PayFlow Automator script loaded!');

(function ($) {
    'use strict';

    // Config injected by wp_localize_script
    var AJAX_URL = (typeof apfaConfig !== 'undefined') ? apfaConfig.ajax_url : '';
    var NONCE    = (typeof apfaConfig !== 'undefined') ? apfaConfig.nonce    : '';

    // Configuration
    var CONFIG = {
        debug: true,
        animationDuration: 300,
    };

    // Utility functions
    var Utils = {
        log: function (message, data) {
            if (CONFIG.debug && window.console) {
                console.log('[PayFlow] ' + message, data || '');
            }
        },

        error: function (message, data) {
            if (window.console) {
                console.error('[PayFlow Error] ' + message, data || '');
            }
        },

        formatCurrency: function (amount) {
            return 'PKR ' + parseFloat(amount).toLocaleString('en-PK', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        formatDate: function (dateStr) {
            if (!dateStr) return '—';
            var d = new Date(dateStr);
            if (isNaN(d.getTime())) return dateStr;
            return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
        },
    };

    // Storage Manager
    var Storage = {
        set: function (key, value) {
            try { localStorage.setItem('apfa_' + key, JSON.stringify(value)); } catch (e) {}
        },
        get: function (key) {
            try { var v = localStorage.getItem('apfa_' + key); return v ? JSON.parse(v) : null; } catch (e) { return null; }
        },
    };

    /* ============================================================
       NAVIGATION
    ============================================================ */
    var Navigation = {
        activeSection: 'dashboard',

        init: function () {
            this.bindEvents();
            var saved = Storage.get('active_section');
            if (saved) { this.switchTo(saved); }
        },

        bindEvents: function () {
            $(document).on('click', '.nav-link', function (e) {
                e.preventDefault();
                var section = $(this).data('section');
                if (section) { Navigation.switchTo(section); }
            });
        },

        switchTo: function (section) {
            if (section === this.activeSection) return;
            $('#' + this.activeSection).removeClass('active');
            var $next = $('#' + section);
            if ($next.length) {
                $next.addClass('active');
                this.activeSection = section;
                Storage.set('active_section', section);
                window.scrollTo(0, 0);
            }
        },
    };

    /* ============================================================
       LANGUAGE
    ============================================================ */
    var Language = {
        current: Storage.get('language') || 'en',

        init: function () {
            this.apply(this.current);
            $(document).on('click', '.lang-btn', function (e) {
                e.preventDefault();
                Language.set($(this).data('lang'));
            });
        },

        set: function (lang) {
            this.current = lang;
            this.apply(lang);
            Storage.set('language', lang);
        },

        apply: function (lang) {
            if (lang === 'ur') {
                $('body').addClass('apfa-urdu').attr('dir', 'rtl');
                $('html').attr('lang', 'ur');
            } else {
                $('body').removeClass('apfa-urdu').attr('dir', 'ltr');
                $('html').attr('lang', 'en');
            }
        },
    };

    /* ============================================================
       STUDENT DATA — Load from server
    ============================================================ */
    var StudentData = {
        data: null,

        load: function () {
            if (!AJAX_URL) return;
            // Disable submit until student data loads
            $('#fee-submission-form [type="submit"]').prop('disabled', true).text('Loading…');
            $.post(AJAX_URL, { action: 'apfa_get_student_data', nonce: NONCE }, function (res) {
                if (res.success) {
                    StudentData.data = res.data;
                    StudentData.populate(res.data);
                    // Re-enable submit if student is linked
                    if (res.data && res.data.phone) {
                        $('#fee-submission-form [type="submit"]').prop('disabled', false).text('Submit Payment');
                    } else {
                        $('#fee-submission-form [type="submit"]').text('Account not linked — contact admin');
                    }
                } else {
                    Utils.log('Student not found or not logged in');
                    $('#fee-submission-form [type="submit"]').text('Log in to submit payment');
                }
            }).fail(function () {
                Utils.error('Failed to load student data');
                $('#fee-submission-form [type="submit"]').prop('disabled', false).text('Submit Payment');
            });
        },

        populate: function (student) {
            // Fee form
            $('#student-name-readonly').val(student.full_name || '');
            $('#student-phone-readonly').val(student.phone    || '');

            // Receipt preview
            $('#receipt-student').text(student.full_name || '—');
            $('#receipt-phone').text(student.phone    || '—');

            // Profile section
            $('#profile-name').text(student.full_name   || '—');
            $('#profile-phone').text(student.phone      || '—');
            $('#profile-course').text(student.course_name || '—');
            $('#profile-total-fee').text(Utils.formatCurrency(student.total_fee  || 0));
            $('#profile-balance').text(Utils.formatCurrency(student.balance    || 0));
            $('#profile-enrolled').text(Utils.formatDate(student.created_at   || ''));

            if (student.full_name) {
                $('#profile-not-linked').hide();
                $('#profile-grid').show();
            } else {
                $('#profile-not-linked').show();
                $('#profile-grid').hide();
            }
        },
    };

    /* ============================================================
       TRANSACTIONS — Load from server
    ============================================================ */
    var Transactions = {
        load: function () {
            if (!AJAX_URL) return;
            $.post(AJAX_URL, { action: 'apfa_get_transactions', nonce: NONCE }, function (res) {
                if (res.success) {
                    Transactions.render(res.data);
                } else {
                    $('#history-empty').show();
                }
            }).fail(function () {
                Utils.error('Failed to load transactions');
            });
        },

        statusBadge: function (status) {
            var colors = { Pending: '#f59e0b', Verified: '#10b981', Rejected: '#ef4444' };
            var color  = colors[status] || '#6b7280';
            return '<span style="background:' + color + ';color:#fff;padding:2px 8px;border-radius:12px;font-size:12px;">' + status + '</span>';
        },

        render: function (submissions) {
            var rows     = '';
            var recentRows = '';
            var count    = 0;

            if (!submissions || submissions.length === 0) {
                $('#history-empty').show();
                return;
            }

            $.each(submissions, function (i, sub) {
                var receiptCells = '';

                if ('Verified' === sub.status) {
                    // Build the download URL for the HTML receipt.
                    var downloadUrl = AJAX_URL
                        + '?action=apfa_download_receipt'
                        + '&submission_id=' + encodeURIComponent(sub.id)
                        + '&nonce=' + encodeURIComponent(NONCE);
                    receiptCells = '<a href="' + downloadUrl + '" target="_blank" '
                        + 'style="background:#10b981;color:#fff;padding:3px 10px;border-radius:6px;font-size:12px;text-decoration:none;">'
                        + '⬇ Receipt</a>';
                } else if (sub.receipt) {
                    receiptCells = '<a href="' + sub.receipt + '" target="_blank">View</a>';
                } else {
                    receiptCells = '—';
                }

                var row = '<tr>' +
                    '<td>' + sub.date + '</td>' +
                    '<td><code>' + (sub.trx_id || '—') + '</code></td>' +
                    '<td>' + Utils.formatCurrency(sub.amount) + '</td>' +
                    '<td>' + Transactions.statusBadge(sub.status) + '</td>' +
                    '<td>' + receiptCells + '</td>' +
                    '</tr>';

                rows += row;
                if (count < 5) {
                    recentRows += row;
                    count++;
                }
            });

            $('#history-tbody').html(rows);
            $('#recent-transactions').html(recentRows);
            $('#history-empty').hide();
        },
    };

    /* ============================================================
       FEE SUBMISSION FORM
    ============================================================ */
    var Form = {
        init: function () {
            $('#fee-submission-form').on('submit', Form.handleSubmit);

            // Update receipt preview when amount changes
            $('#amount').on('input', function () {
                var amount = parseFloat($(this).val()) || 0;
                $('#receipt-amount').text('PKR ' + amount.toFixed(2));
                $('#receipt-total').text('PKR ' + amount.toFixed(2));
            });

            // Update receipt preview when fee type changes
            $('[name="fee_type"]').on('change', function () {
                var selected = $(this).val();
                if (selected && selected !== 'Select Fee Type') {
                    $('#receipt-fee-type').text(selected);
                }
            });

            // Update receipt trx_id preview
            $('#trx-id').on('input', function () {
                $('#receipt-trx').text('Trx ID: ' + ($(this).val() || '—'));
            });

            // File name display
            $('#receipt-image').on('change', function () {
                var file = this.files[0];
                $('#file-name').text(file ? file.name : 'No file chosen');
            });
        },

        handleSubmit: function (e) {
            e.preventDefault();
            var $form = $(this);
            var $btn  = $form.find('[type="submit"]');
            var $msg  = $('#fee-form-msg');

            var studentPhone = $('#student-phone-readonly').val().trim();
            var trxId        = $('#trx-id').val().trim();
            var amount       = parseFloat($('#amount').val()) || 0;

            if (!studentPhone) {
                $msg.html('<span class="apfa-error">Please log in with a linked student account.</span>');
                return;
            }
            if (!trxId) {
                $msg.html('<span class="apfa-error">Transaction ID is required.</span>');
                return;
            }
            if (amount <= 0) {
                $msg.html('<span class="apfa-error">Please enter a valid amount.</span>');
                return;
            }

            // Build FormData from scratch to avoid duplicate field keys
            var formData = new FormData();
            formData.append('action', 'apfa_submit_fee');
            formData.append('nonce', NONCE || $('#apfa-nonce').val());
            formData.append('student_phone', studentPhone);
            formData.append('trx_id', trxId);
            formData.append('amount', amount);
            var receiptFile = $('#receipt-image')[0].files[0];
            if (receiptFile) {
                formData.append('receipt_image', receiptFile);
            }

            $btn.prop('disabled', true).text('Submitting…');
            $msg.html('');

            $.ajax({
                url:         AJAX_URL,
                type:        'POST',
                data:        formData,
                processData: false,
                contentType: false,
                success: function (res) {
                    if (res.success) {
                        $('#success-modal').addClass('show');
                        $form[0].reset();
                        $('#file-name').text('No file chosen');
                        $msg.html('');
                        // Reload transactions
                        Transactions.load();
                    } else {
                        var errMsg = res.data || 'Submission failed.';
                        $('#error-modal-msg').text(errMsg);
                        $('#error-modal').addClass('show');
                    }
                },
                error: function () {
                    $msg.html('<span class="apfa-error">Server error. Please try again.</span>');
                },
                complete: function () {
                    $btn.prop('disabled', false).text('Submit Payment');
                },
            });
        },
    };

    /* ============================================================
       MODALS
    ============================================================ */
    var Modal = {
        init: function () {
            $(document).on('click', '.modal', function (e) {
                if (e.target === this) { $(this).removeClass('show'); }
            });
        },
    };

    /* ============================================================
       INITIALISE
    ============================================================ */
    function init() {
        Utils.log('Initializing PayFlow Automator');
        Language.init();
        Navigation.init();
        Form.init();
        Modal.init();
        StudentData.load();
        Transactions.load();
        Utils.log('PayFlow Automator initialized successfully');
    }

    $(document).ready(init);

    /* ============================================================
       GLOBAL HELPERS (used inline by template)
    ============================================================ */
    window.switchSection = function (e, section) {
        if (e && e.preventDefault) e.preventDefault();
        Navigation.switchTo(section);
    };

    window.closeModal = function (modalId) {
        $('#' + modalId).removeClass('show');
    };

    window.changeLang = function (e, lang) {
        if (e && e.preventDefault) e.preventDefault();
        Language.set(lang);
    };

    // Expose for debugging
    window.PayFlow = { Utils: Utils, Storage: Storage, Navigation: Navigation, Form: Form, Modal: Modal };

}(jQuery));

console.log('PayFlow Automator - All systems ready! ✓');

