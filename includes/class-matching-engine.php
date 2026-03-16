<?php
/**
 * Transaction Matching Engine
 */

class APFA_Matching_Engine {

    /**
     * Match bank transaction to fee submission
     */
    public static function match_transaction( $trx_id, $amount ) {
        global $wpdb;
        $prefix = $wpdb->prefix . 'apfa_';

        // Find matching fee submission
        $submission = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$prefix}fee_submissions WHERE Trx_ID = %s AND Submitted_Amount = %f AND Status = 'Pending'",
            $trx_id,
            $amount
        ) );

        if ( ! $submission ) {
            return false;
        }

        // Update submission status
        $wpdb->update(
            $prefix . 'fee_submissions',
            array( 'Status' => 'Verified' ),
            array( 'ID' => $submission->ID )
        );

        // Deduct from student balance
        $wpdb->query( $wpdb->prepare(
            "UPDATE {$prefix}students SET Balance = Balance - %f WHERE Phone = %s",
            $amount,
            $submission->Student_Phone
        ) );

        return true;
    }
}
