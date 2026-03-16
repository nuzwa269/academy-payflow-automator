/**
 * Academy PayFlow — Admin JavaScript
 */
jQuery(document).ready(function ($) {
    'use strict';

    var nonce   = (typeof apfaAdmin !== 'undefined') ? apfaAdmin.nonce    : '';
    var ajaxUrl = (typeof apfaAdmin !== 'undefined') ? apfaAdmin.ajax_url : ajaxurl;

    /* ============================================================
       ADMISSION FORM — Enroll New Student
    ============================================================ */
    $('#apfa-admission-form').on('submit', function (e) {
        e.preventDefault();
        var $form = $(this);
        var $msg  = $('#apfa-admission-msg');
        var $btn  = $form.find('[type="submit"]');

        $msg.html('');
        $btn.prop('disabled', true).text('Enrolling…');

        $.post(ajaxUrl, {
            action:      'apfa_admin_add_student',
            nonce:       nonce,
            full_name:   $('#adm_full_name').val(),
            phone:       $('#adm_phone').val(),
            course_name: $('#adm_course').val(),
            total_fee:   $('#adm_total_fee').val(),
            user_id:     $('#adm_user_id').val(),
        }, function (res) {
            if (res.success) {
                $msg.html('<span class="apfa-success">✓ ' + res.data.message + '</span>');
                $form[0].reset();
                // Reload page after short delay to show new student in table
                setTimeout(function () { location.reload(); }, 1200);
            } else {
                $msg.html('<span class="apfa-error">✗ ' + (res.data || 'Error') + '</span>');
            }
        }).fail(function () {
            $msg.html('<span class="apfa-error">✗ Server error. Please try again.</span>');
        }).always(function () {
            $btn.prop('disabled', false).text('Enroll Student');
        });
    });

    /* ============================================================
       DELETE STUDENT
    ============================================================ */
    $(document).on('click', '.apfa-delete-student', function () {
        var $btn = $(this);
        var id   = $btn.data('id');

        if (!confirm('Delete this student? This cannot be undone.')) {
            return;
        }

        $btn.prop('disabled', true).text('Deleting…');

        $.post(ajaxUrl, {
            action:     'apfa_admin_delete_student',
            nonce:      nonce,
            student_id: id,
        }, function (res) {
            if (res.success) {
                $('#student-row-' + id).fadeOut(300, function () { $(this).remove(); });
            } else {
                alert('Error: ' + (res.data || 'Could not delete student.'));
                $btn.prop('disabled', false).text('Delete');
            }
        }).fail(function () {
            alert('Server error.');
            $btn.prop('disabled', false).text('Delete');
        });
    });

    /* ============================================================
       VERIFY / REJECT SUBMISSION
    ============================================================ */
    $(document).on('click', '.apfa-verify-submission, .apfa-reject-submission', function () {
        var $btn   = $(this);
        var id     = $btn.data('id');
        var status = $btn.data('status');

        if (!confirm('Set this submission to "' + status + '"?')) {
            return;
        }

        $btn.prop('disabled', true);

        $.post(ajaxUrl, {
            action:        'apfa_admin_update_submission',
            nonce:         nonce,
            submission_id: id,
            status:        status,
        }, function (res) {
            if (res.success) {
                // Replace the action buttons with the new status text
                var colors = { Verified: '#10b981', Rejected: '#ef4444' };
                var color  = colors[status] || '#6b7280';
                var badge  = '<span style="background:' + color + ';color:#fff;padding:2px 8px;border-radius:12px;font-size:12px;">' + status + '</span>';
                $('.sub-status-cell-' + id).html(badge);
                $btn.closest('td').html(status);
            } else {
                alert('Error: ' + (res.data || 'Could not update.'));
                $btn.prop('disabled', false);
            }
        }).fail(function () {
            alert('Server error.');
            $btn.prop('disabled', false);
        });
    });
});
