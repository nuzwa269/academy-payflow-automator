(function($) {
    'use strict';

    const LanguageSwitcher = {
        init: function() {
            this.currentLanguage = this.getStoredLanguage();
            this.bindEvents();
            this.applyLanguage(this.currentLanguage);
        },

        bindEvents: function() {
            $(document).on('click', '.apfa-lang-btn', this.switchLanguage.bind(this));
        },

        switchLanguage: function(e) {
            e.preventDefault();
            const lang = $(e.currentTarget).data('lang');
            this.currentLanguage = lang;
            localStorage.setItem('apfa_language', lang);
            this.applyLanguage(lang);
            location.reload();
        },

        getStoredLanguage: function() {
            return localStorage.getItem('apfa_language') || 'en';
        },

        applyLanguage: function(lang) {
            if (lang === 'ur') {
                $('body').addClass('apfa-urdu').attr('dir', 'rtl');
                $('html').attr('lang', 'ur');
            } else {
                $('body').removeClass('apfa-urdu').attr('dir', 'ltr');
                $('html').attr('lang', 'en');
            }
        }
    };

    $(document).ready(function() {
        LanguageSwitcher.init();
    });

})(jQuery);
