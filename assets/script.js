/**
 * Academy PayFlow Automator - Main Script
 * Handles core functionality and integrations
 */

console.log('PayFlow Automator script loaded!');

(function() {
    'use strict';

    // Configuration
    const CONFIG = {
        debug: true,
        animationDuration: 300,
    };

    // Utility functions
    const Utils = {
        log: function(message, data) {
            if (CONFIG.debug) {
                console.log('[PayFlow] ' + message, data || '');
            }
        },
        
        error: function(message, data) {
            console.error('[PayFlow Error] ' + message, data || '');
        },

        formatCurrency: function(amount, currency = 'PKR') {
            return currency + ' ' + parseFloat(amount).toLocaleString();
        },

        formatDate: function(dateStr) {
            if (!dateStr) return '-';
            const date = new Date(dateStr);
            return date.toLocaleDateString('en-GB', {
                day: '2-digit',
                month: 'short',
                year: 'numeric'
            });
        },

        getQueryParam: function(param) {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get(param);
        },

        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    };

    // Storage Manager
    const Storage = {
        set: function(key, value) {
            try {
                localStorage.setItem('apfa_' + key, JSON.stringify(value));
                Utils.log('Storage set: ' + key);
            } catch (e) {
                Utils.error('Storage set failed', e);
            }
        },

        get: function(key) {
            try {
                const value = localStorage.getItem('apfa_' + key);
                return value ? JSON.parse(value) : null;
            } catch (e) {
                Utils.error('Storage get failed', e);
                return null;
            }
        },

        remove: function(key) {
            try {
                localStorage.removeItem('apfa_' + key);
                Utils.log('Storage removed: ' + key);
            } catch (e) {
                Utils.error('Storage remove failed', e);
            }
        },

        clear: function() {
            try {
                const keys = Object.keys(localStorage);
                keys.forEach(key => {
                    if (key.startsWith('apfa_')) {
                        localStorage.removeItem(key);
                    }
                });
                Utils.log('Storage cleared');
            } catch (e) {
                Utils.error('Storage clear failed', e);
            }
        }
    };

    // Theme Manager
    const Theme = {
        current: Storage.get('theme') || 'light',

        init: function() {
            this.apply(this.current);
            this.bindEvents();
        },

        bindEvents: function() {
            document.querySelectorAll('[data-theme]').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const theme = e.target.dataset.theme;
                    this.set(theme);
                });
            });
        },

        set: function(theme) {
            this.current = theme;
            this.apply(theme);
            Storage.set('theme', theme);
            Utils.log('Theme changed to: ' + theme);
        },

        apply: function(theme) {
            if (theme === 'dark') {
                document.body.classList.add('dark-mode');
                document.documentElement.setAttribute('data-theme', 'dark');
            } else {
                document.body.classList.remove('dark-mode');
                document.documentElement.setAttribute('data-theme', 'light');
            }
        }
    };

    // Navigation Manager
    const Navigation = {
        activeSection: 'dashboard',

        init: function() {
            this.bindEvents();
            this.restoreActive();
        },

        bindEvents: function() {
            document.querySelectorAll('.nav-link').forEach(link => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    const section = link.dataset.section;
                    if (section) {
                        this.switchTo(section);
                    }
                });
            });
        },

        switchTo: function(section) {
            if (section === this.activeSection) return;

            // Hide current section
            const current = document.getElementById(this.activeSection);
            if (current) {
                current.classList.remove('active');
            }

            // Show new section
            const next = document.getElementById(section);
            if (next) {
                next.classList.add('active');
                this.activeSection = section;
                Storage.set('active_section', section);
                Utils.log('Section switched to: ' + section);
                window.scrollTo(0, 0);
            }
        },

        restoreActive: function() {
            const saved = Storage.get('active_section');
            if (saved) {
                this.switchTo(saved);
            }
        }
    };

    // Language Manager
    const Language = {
        current: Storage.get('language') || 'en',

        init: function() {
            this.apply(this.current);
            this.bindEvents();
        },

        bindEvents: function() {
            document.querySelectorAll('.lang-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const lang = btn.dataset.lang;
                    this.set(lang);
                });
            });
        },

        set: function(lang) {
            this.current = lang;
            this.apply(lang);
            Storage.set('language', lang);
            Utils.log('Language changed to: ' + lang);
        },

        apply: function(lang) {
            if (lang === 'ur') {
                document.body.classList.add('apfa-urdu');
                document.body.dir = 'rtl';
                document.documentElement.lang = 'ur';
            } else {
                document.body.classList.remove('apfa-urdu');
                document.body.dir = 'ltr';
                document.documentElement.lang = 'en';
            }
        }
    };

    // Form Manager
    const Form = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            const form = document.getElementById('fee-submission-form');
            if (form) {
                form.addEventListener('submit', this.handleSubmit.bind(this));
            }

            // File input
            const fileInput = document.getElementById('receipt-image');
            if (fileInput) {
                fileInput.addEventListener('change', this.handleFileChange.bind(this));
            }

            // Amount input
            const amountInput = document.getElementById('amount');
            if (amountInput) {
                amountInput.addEventListener('input', this.handleAmountChange.bind(this));
            }
        },

        handleSubmit: function(e) {
            e.preventDefault();
            Utils.log('Form submitted');

            const form = e.target;
            const amount = form.querySelector('#amount').value;
            const file = form.querySelector('#receipt-image').files[0];

            if (!amount || amount <= 0) {
                this.showError('Please enter a valid amount');
                return;
            }

            if (!file) {
                this.showError('Please select a receipt image');
                return;
            }

            this.showSuccess('Payment submitted successfully');
            form.reset();
            document.getElementById('file-name').textContent = 'No file chosen';

            // Reset after 2 seconds
            setTimeout(() => {
                form.reset();
            }, 2000);
        },

        handleFileChange: function(e) {
            const file = e.target.files[0];
            const fileName = file ? file.name : 'No file chosen';
            const fileNameElement = document.getElementById('file-name');
            if (fileNameElement) {
                fileNameElement.textContent = fileName;
            }
            Utils.log('File selected: ' + fileName);
        },

        handleAmountChange: function(e) {
            const amount = parseFloat(e.target.value) || 0;
            const receiptAmount = document.getElementById('receipt-amount');
            const receiptTotal = document.getElementById('receipt-total');
            
            if (receiptAmount) {
                receiptAmount.textContent = '$' + amount.toFixed(2);
            }
            if (receiptTotal) {
                receiptTotal.textContent = '$' + (amount + 30).toFixed(2);
            }
        },

        showError: function(message) {
            alert('Error: ' + message);
            Utils.error(message);
        },

        showSuccess: function(message) {
            const modal = document.getElementById('success-modal');
            if (modal) {
                modal.classList.add('show');
                Utils.log(message);
                
                setTimeout(() => {
                    modal.classList.remove('show');
                }, 3000);
            }
        }
    };

    // Modal Manager
    const Modal = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Close modal when clicking on close button
            document.querySelectorAll('[data-close-modal]').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const modal = e.target.closest('.modal');
                    if (modal) {
                        this.close(modal);
                    }
                });
            });

            // Close modal when clicking outside
            document.querySelectorAll('.modal').forEach(modal => {
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) {
                        this.close(modal);
                    }
                });
            });
        },

        close: function(modal) {
            modal.classList.remove('show');
            Utils.log('Modal closed');
        }
    };

    // Notification Manager
    const Notification = {
        show: function(message, type = 'info', duration = 3000) {
            const notification = document.createElement('div');
            notification.className = 'notification notification-' + type;
            notification.textContent = message;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'error' ? '#ef4444' : type === 'success' ? '#10b981' : '#2563eb'};
                color: white;
                padding: 15px 20px;
                border-radius: 6px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                z-index: 9999;
                animation: slideIn 0.3s ease;
            `;
            
            document.body.appendChild(notification);

            setTimeout(() => {
                notification.remove();
            }, duration);

            Utils.log('Notification shown: ' + message);
        }
    };

    // Analytics
    const Analytics = {
        init: function() {
            this.trackPageView();
            this.bindEvents();
        },

        trackPageView: function() {
            Utils.log('Page viewed: ' + window.location.pathname);
        },

        trackEvent: function(category, action, label) {
            Utils.log('Event tracked', {
                category: category,
                action: action,
                label: label
            });
        },

        bindEvents: function() {
            document.querySelectorAll('[data-track]').forEach(el => {
                el.addEventListener('click', (e) => {
                    const track = el.dataset.track;
                    if (track) {
                        this.trackEvent('user_interaction', 'click', track);
                    }
                });
            });
        }
    };

    // Performance Monitor
    const Performance = {
        init: function() {
            this.logMetrics();
        },

        logMetrics: function() {
            if (window.performance && window.performance.timing) {
                const timing = window.performance.timing;
                const loadTime = timing.loadEventEnd - timing.navigationStart;
                const connectTime = timing.responseEnd - timing.requestStart;
                
                Utils.log('Performance Metrics', {
                    loadTime: loadTime + 'ms',
                    connectTime: connectTime + 'ms'
                });
            }
        }
    };

    // Initialize app
    const App = {
        init: function() {
            Utils.log('Initializing PayFlow Automator');
            
            Theme.init();
            Language.init();
            Navigation.init();
            Form.init();
            Modal.init();
            Analytics.init();
            Performance.init();
            
            Utils.log('PayFlow Automator initialized successfully');
        }
    };

    // Start app when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            App.init();
        });
    } else {
        App.init();
    }

    // Expose to global for debugging
    window.PayFlow = {
        Utils: Utils,
        Storage: Storage,
        Theme: Theme,
        Language: Language,
        Navigation: Navigation,
        Form: Form,
        Modal: Modal,
        Notification: Notification,
        Analytics: Analytics
    };

})();

// Global helper functions
window.switchSection = function(e, section) {
    e.preventDefault();
    const PayFlow = window.PayFlow;
    if (PayFlow && PayFlow.Navigation) {
        PayFlow.Navigation.switchTo(section);
    }
};

window.closeModal = function(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('show');
    }
};

window.changeLang = function(e, lang) {
    e.preventDefault();
    const PayFlow = window.PayFlow;
    if (PayFlow && PayFlow.Language) {
        PayFlow.Language.set(lang);
    }
};

console.log('PayFlow Automator - All systems ready! ✓');
