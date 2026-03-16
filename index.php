<?php
/**
 * Academy PayFlow Automator - Index File
 * Security: Prevent direct access to plugin directory
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit( 'Direct access not permitted.' );
}

/**
 * This file is a security measure
 * It prevents directory listing and direct access
 */

// Load the main plugin file
require_once dirname( dirname( __FILE__ ) ) . '/academy-payflow-automator.php';
