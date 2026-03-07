<?php
/**
 * Downloads the latest analytics.js from Google and saves locally.
 *
 * @package Whippet
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$remote_url = 'https://www.google-analytics.com/analytics.js';
$local_file = dirname( dirname( __FILE__ ) ) . '/cache/local-ga.js';

$response = wp_remote_get( $remote_url, array( 'timeout' => 15 ) );

if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
	if ( file_exists( $local_file ) ) {
		readfile( $local_file );
	}
	return;
}

$body = wp_remote_retrieve_body( $response );

echo $body;

$cache_dir = dirname( $local_file );
if ( ! is_dir( $cache_dir ) ) {
	wp_mkdir_p( $cache_dir );
}

if ( is_writable( $cache_dir ) || is_writable( $local_file ) ) {
	file_put_contents( $local_file, $body, LOCK_EX );
}
