<?php
//Create Table for M-PESA Transactions

global $wpdb;

global $trx_db_version;

$trx_db_version = '1.0';

$table_name = $wpdb->prefix .'flexycode_mpesa';

$charset_collate = $wpdb->get_charset_collate();

$sql = "CREATE TABLE IF NOT EXISTS $table_name (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    order_id varchar(150) DEFAULT '' NULL,
    phone_number varchar(150) DEFAULT '' NULL,
    transaction_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
    merchant_request_id varchar(150) DEFAULT '' NULL,
    checkout_request_id varchar(150) DEFAULT '' NULL,
    result_code varchar(150) DEFAULT '' NULL,
    result_desc varchar(150) DEFAULT '' NULL,
    processing_status varchar(20) DEFAULT '0' NULL,
    trans_id varchar(150) DEFAULT '' NULL,
    trans_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
    trans_phone varchar(150) DEFAULT '' NULL,
    PRIMARY KEY  (id)
) $charset_collate;";

require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
dbDelta( $sql );
add_option( 'trx_db_version', $trx_db_version );		