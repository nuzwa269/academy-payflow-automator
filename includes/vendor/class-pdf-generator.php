<?php
/**
 * PDF Receipt Generator
 * Outputs a styled HTML receipt page optimised for browser Print-to-PDF.
 */

class APFA_PDF_Generator {

    /**
     * Generate and stream an HTML receipt page.
     *
     * The response is a self-contained HTML document with embedded CSS.
     * Students can use the browser's built-in Print → Save as PDF feature to
     * obtain a PDF copy.  The filename hint in the Content-Disposition header
     * encourages browsers that support it to pre-fill the save-dialog name.
     *
     * @param int    $submission_id  Primary key from apfa_fee_submissions.
     * @param string $language       'en' or 'ur' – controls RTL text direction.
     */
    public static function download_receipt( $submission_id, $language = 'en' ) {
        global $wpdb;
        $prefix = $wpdb->prefix . 'apfa_';

        $submission = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$prefix}fee_submissions WHERE ID = %d",
            $submission_id
        ) );

        if ( ! $submission ) {
            wp_die( esc_html__( 'Submission not found.', 'apfa' ) );
        }

        $student = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$prefix}students WHERE Phone = %s",
            $submission->Student_Phone
        ) );

        $is_rtl      = ( 'ur' === $language );
        $dir_attr    = $is_rtl ? 'rtl' : 'ltr';
        $trx_slug    = preg_replace( '/[^A-Za-z0-9_-]/', '', $submission->Trx_ID );
        $filename    = 'Receipt_' . $trx_slug . '.html';
        $site_name   = esc_html( get_bloginfo( 'name' ) ?: 'PayFlow Academy' );
        $student_name  = $student ? esc_html( $student->Full_Name )   : '—';
        $student_phone = $student ? esc_html( $student->Phone )        : esc_html( $submission->Student_Phone );
        $course_name   = $student ? esc_html( $student->Course_Name )  : '—';
        $trx_id        = esc_html( $submission->Trx_ID );
        $amount        = number_format( (float) $submission->Submitted_Amount, 2 );
        $status        = esc_html( $submission->Status );
        $date_str      = esc_html( date_i18n( 'd-M-Y H:i', strtotime( $submission->created_at ) ) );

        // Output headers BEFORE any content.
        header( 'Content-Type: text/html; charset=UTF-8' );
        header( 'Content-Disposition: inline; filename="' . $filename . '"' );
        header( 'X-Content-Type-Options: nosniff' );

        // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '<!DOCTYPE html>
<html lang="' . esc_attr( $language ) . '" dir="' . esc_attr( $dir_attr ) . '">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payment Receipt — ' . $site_name . '</title>
<style>
    *{box-sizing:border-box;margin:0;padding:0;}
    body{font-family:"Segoe UI",Arial,sans-serif;background:#f3f4f6;color:#1f2937;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}
    .receipt{background:#fff;width:480px;max-width:100%;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,.12);overflow:hidden;}
    .receipt-header{background:linear-gradient(135deg,#2563eb,#1e40af);color:#fff;padding:32px 28px 24px;text-align:center;}
    .receipt-header .logo{font-size:36px;margin-bottom:8px;}
    .receipt-header h1{font-size:22px;font-weight:700;letter-spacing:.5px;}
    .receipt-header p{font-size:13px;opacity:.85;margin-top:4px;}
    .status-badge{display:inline-block;background:rgba(255,255,255,.2);border:1.5px solid rgba(255,255,255,.6);color:#fff;padding:4px 18px;border-radius:20px;font-size:13px;font-weight:600;margin-top:12px;}
    .status-badge.verified{background:#10b981;border-color:#10b981;}
    .receipt-body{padding:28px;}
    .section-label{font-size:11px;text-transform:uppercase;letter-spacing:1px;color:#6b7280;margin-bottom:10px;font-weight:600;}
    .detail-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:20px;}
    .detail-item label{display:block;font-size:11px;color:#9ca3af;margin-bottom:2px;}
    .detail-item span{font-size:14px;font-weight:500;color:#111827;}
    .amount-block{background:#f0fdf4;border:1.5px solid #bbf7d0;border-radius:8px;padding:16px 20px;display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;}
    .amount-block .label{font-size:14px;color:#065f46;font-weight:500;}
    .amount-block .value{font-size:24px;font-weight:700;color:#059669;}
    .divider{border:none;border-top:1px dashed #e5e7eb;margin:4px 0 20px;}
    .receipt-footer{background:#f9fafb;border-top:1px solid #e5e7eb;padding:18px 28px;text-align:center;font-size:12px;color:#9ca3af;}
    .receipt-footer strong{color:#4b5563;}
    .print-hint{font-size:12px;color:#6b7280;text-align:center;padding:12px;background:#fffbeb;border-top:1px solid #fde68a;}
    @media print{
        body{background:#fff;padding:0;}
        .receipt{box-shadow:none;border-radius:0;width:100%;}
        .print-hint{display:none;}
    }
</style>
</head>
<body>
<div class="receipt">
    <div class="receipt-header">
        <div class="logo">💳</div>
        <h1>' . $site_name . '</h1>
        <p>Official Fee Payment Receipt</p>
        <span class="status-badge ' . ( 'Verified' === $submission->Status ? 'verified' : '' ) . '">' . $status . '</span>
    </div>

    <div class="receipt-body">
        <p class="section-label">Student Information</p>
        <div class="detail-grid">
            <div class="detail-item">
                <label>Full Name</label>
                <span>' . $student_name . '</span>
            </div>
            <div class="detail-item">
                <label>Phone</label>
                <span>' . $student_phone . '</span>
            </div>
            <div class="detail-item">
                <label>Course</label>
                <span>' . $course_name . '</span>
            </div>
            <div class="detail-item">
                <label>Date &amp; Time</label>
                <span>' . $date_str . '</span>
            </div>
        </div>

        <hr class="divider">

        <p class="section-label">Transaction Details</p>
        <div class="detail-grid" style="margin-bottom:16px;">
            <div class="detail-item">
                <label>Transaction ID</label>
                <span>' . $trx_id . '</span>
            </div>
            <div class="detail-item">
                <label>Receipt #</label>
                <span>APFA-' . esc_html( $submission_id ) . '</span>
            </div>
        </div>

        <div class="amount-block">
            <span class="label">Amount Paid</span>
            <span class="value">PKR ' . $amount . '</span>
        </div>
    </div>

    <div class="receipt-footer">
        <p>Powered by <strong>Academy PayFlow</strong> &nbsp;|&nbsp; Designed by Coach Pro AI</p>
    </div>

    <div class="print-hint">💡 To save as PDF: use your browser\'s <strong>Print → Save as PDF</strong> option.</div>
</div>
</body>
</html>';
        // phpcs:enable

        exit;
    }
}
