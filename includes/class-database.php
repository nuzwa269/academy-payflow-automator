<?php
/**
 * Database Schema Creator
 * Handles creation of custom tables on plugin activation
 */

class APFA_Database {

    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $prefix = $wpdb->prefix . 'apfa_';

        // Students Table
        $students_table = $prefix . 'students';
        $students_sql = "CREATE TABLE IF NOT EXISTS $students_table (
            ID BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            User_ID BIGINT UNSIGNED,
            Full_Name VARCHAR(255) NOT NULL,
            Phone VARCHAR(20) NOT NULL UNIQUE,
            Course_Name VARCHAR(255),
            Total_Fee DECIMAL(10, 2),
            Balance DECIMAL(10, 2),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (ID),
            INDEX (User_ID),
            INDEX (Phone)
        ) $charset_collate;";

        // Bank Logs Table
        $bank_logs_table = $prefix . 'bank_logs';
        $bank_logs_sql = "CREATE TABLE IF NOT EXISTS $bank_logs_table (
            ID BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            Raw_SMS LONGTEXT,
            Sender_ID VARCHAR(50),
            Trx_ID VARCHAR(100) NOT NULL UNIQUE,
            Amount DECIMAL(10, 2),
            Date_Time DATETIME,
            Status ENUM('Unmatched', 'Matched') DEFAULT 'Unmatched',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (ID),
            INDEX (Trx_ID),
            INDEX (Status)
        ) $charset_collate;";

        // Fee Submissions Table
        $submissions_table = $prefix . 'fee_submissions';
        $submissions_sql = "CREATE TABLE IF NOT EXISTS $submissions_table (
            ID BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            Student_Phone VARCHAR(20),
            Trx_ID VARCHAR(100),
            Submitted_Amount DECIMAL(10, 2),
            Status ENUM('Pending', 'Verified', 'Rejected') DEFAULT 'Pending',
            Receipt_Image LONGTEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (ID),
            FOREIGN KEY (Student_Phone) REFERENCES $students_table(Phone),
            INDEX (Trx_ID),
            INDEX (Status)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $students_sql );
        dbDelta( $bank_logs_sql );
        dbDelta( $submissions_sql );
    }
}
