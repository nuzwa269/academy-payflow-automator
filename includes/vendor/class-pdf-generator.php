<?php
/**
 * PDF Receipt Generator using FPDF
 */

class APFA_PDF_Generator {

    /**
     * Generate and download receipt PDF
     */
    public static function download_receipt( $submission_id, $language = 'en' ) {
        global $wpdb;
        $prefix = $wpdb->prefix . 'apfa_';

        $submission = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$prefix}fee_submissions WHERE ID = %d",
            $submission_id
        ) );

        if ( ! $submission ) {
            wp_die( 'Submission not found' );
        }

        $student = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$prefix}students WHERE Phone = %s",
            $submission->Student_Phone
        ) );

        // For now, output as simple text/HTML
        // TODO: Implement FPDF library integration
        header( 'Content-Type: text/plain' );
        header( 'Content-Disposition: attachment; filename="Receipt_' . $submission->Trx_ID . '.txt"' );

        echo "===== ACADEMY PAYFLOW RECEIPT =====\n";
        echo "Student: " . $student->Full_Name . "\n";
        echo "Phone: " . $student->Phone . "\n";
        echo "Course: " . $student->Course_Name . "\n";
        echo "Transaction ID: " . $submission->Trx_ID . "\n";
        echo "Amount: PKR " . number_format( $submission->Submitted_Amount, 2 ) . "\n";
        echo "Status: " . $submission->Status . "\n";
        echo "Date: " . date( 'd-M-Y H:i', strtotime( $submission->created_at ) ) . "\n";
        echo "\nPowered by Academy PayFlow | Designed by Coach Pro AI\n";

        exit;
    }
}
