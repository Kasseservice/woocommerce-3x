<?php

// if uninstall.php is not called by WordPress, die
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

global $wpdb;
$table = $wpdb->prefix . "duell_sync_logs";


// drop a table
$wpdb->query("DROP TABLE IF EXISTS $table");

// for site options in Multisite
//delete_site_option('duellintegration_client_number');
//delete_site_option('duellintegration_client_token');
//delete_site_option('duellintegration_department_token');
unregister_setting('duellintegration', 'duellintegration_client_number');
unregister_setting('duellintegration', 'duellintegration_client_token');
unregister_setting('duellintegration', 'duellintegration_department_token');
?>