(function($) {
    'use strict';

    const APFA = {
        init: function() {
            this.bindEvents();
            this.loadStudentData();
            this.loadTransactions();
        },

        bindEvents: function() {
            // Navigation
            $(document).on('click', '.apfa-nav-item', this.switchSection.bind(this));
            
            // Fee Submission
            $(document).on('submit', '#fee-submission-form', this.submitFee.bind(this));
            
            // Modal Close
            $(document).on('click', '.apfa-close', this.closeModal.bind(this));
            $(document).on('click', '.apfa-modal', this.closeModalOnBackdrop.bind(this));
        },

        switchSection: function(e) {
            e.preventDefault();
            const section = $(e.currentTarget).data('section');
            
            $('.apfa-section').removeClass('active');
            $('.apfa-nav-item').removeClass('active');
            
            $(`#${section}`).addClass('active');
            $(e.currentTarget).addClass('active');
        },

        loadStudentData: function() {
            const self = this;
            $.ajax({
                url: apfaConfig.ajax_url,
                type: 'POST',
                data: {
                    action: 'apfa_get_student_data',
                    nonce: apfaConfig.nonce
                },
                success: function(response) {
                    if (response.success) {
                        const data = response.data;
                        $('#total-fee').text(`PKR ${parseFloat(data.total_fee).toFixed(2)}`);
                        $('#verified-amount').text(`PKR ${parseFloat(data.verified_amount).toFixed(2)}`);
                        $('#pending-balance').text(`PKR ${parseFloat(data.balance).toFixed(2)}`);
                        
                        // Update profile
                        $('#profile-name').text(data.full_name);
                        $('#profile-phone').text(data.phone);
                        $('#profile-course').text(data.course_name);
                        $('#profile-enrolled').text(data.created_at);
                    }
                },
                error: function() {
                    console.error('Failed to load student data');
                }
            });
        },

        loadTransactions: function() {
            $.ajax({
                url: apfaConfig.ajax_url,
                type: 'POST',
                data: {
                    action: 'apfa_get_transactions',
                    nonce: apfaConfig.nonce
                },
                success: function(response) {
                    if (response.success) {
                        const rows = response.data.map(transaction => [
                            transaction.date,
                            transaction.trx_id,
                            `PKR ${parseFloat(transaction.amount).toFixed(2)}`,
                            `<span class="status-badge ${transaction.status.toLowerCase()}">${transaction.status}</span>`
                        ]);
                        
                        if ($.fn.dataTable.isDataTable('#recent-transactions')) {
                            $('#recent-transactions').DataTable().destroy();
                        }
                        
                        $('#recent-transactions').DataTable({
                            data: rows,
                            columnDefs: [{ targets: 0, width: '20%' }],
                            paging: true,
                            pageLength: 5,
                            language: {
                                "emptyTable": "No transactions yet",
                                "info": "Showing _START_ to _END_ of _TOTAL_ transactions",
                                "infoEmpty": "Showing 0 to 0 of 0 transactions"
                            }
                        });

                        // Also populate history
                        const historyRows = response.data.map(transaction => [
                            transaction.date,
                            `PKR ${parseFloat(transaction.amount).toFixed(2)}`,
                            `<span class="status-badge ${transaction.status.toLowerCase()}">${transaction.status}</span>`,
                            transaction.receipt ? `<a href="${transaction.receipt}" target="_blank" class="apfa-btn apfa-btn-small">View</a>` : '-'
                        ]);

                        if ($.fn.dataTable.isDataTable('#payment-history')) {
                            $('#payment-history').DataTable().destroy();
                        }

                        $('#payment-history').DataTable({
                            data: historyRows,
                            paging: true,
                            pageLength: 10,
                            language: {
                                "emptyTable": "No payment history",
                                "info": "Showing _START_ to _END_ of _TOTAL_ payments",
                                "infoEmpty": "Showing 0 to 0 of 0 payments"
                            }
                        });
                    }
                },
                error: function() {
                    console.error('Failed to load transactions');
                }
            });
        },

        submitFee: function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'apfa_submit_fee');
            formData.append('nonce', apfaConfig.nonce);

            // Get current user phone
            const studentPhone = $('#profile-phone').text();
            formData.append('student_phone', studentPhone);

            $.ajax({
                url: apfaConfig.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        APFA.showModal(
                            apfaConfig.i18n.payment_submitted,
                            apfaConfig.i18n.payment_success
                        );
                        $('#fee-submission-form')[0].reset();
                        setTimeout(() => APFA.loadTransactions(), 2000);
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                }
            });
        },

        showModal: function(title, message) {
            $('#modal-title').text(title);
            $('#modal-message').text(message);
            $('#success-modal').addClass('show');
        },

        closeModal: function() {
            $('#success-modal').removeClass('show');
        },

        closeModalOnBackdrop: function(e) {
            if (e.target.id === 'success-modal') {
                this.closeModal();
            }
        }
    };

    $(document).ready(function() {
        APFA.init();
    });

})(jQuery);
