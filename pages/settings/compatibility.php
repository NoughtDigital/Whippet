<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function whippet_pages_compatibility() {

  $url = home_url();
  $parsed = wp_parse_url( $url );
  $url_protocol = isset( $parsed['scheme'] ) ? $parsed['scheme'] : '';

  $response = wp_remote_get( $url, array( 'timeout' => 10, 'sslverify' => false ) );
  $cache_control_header = '';
  if ( ! is_wp_error( $response ) ) {
      $cache_control_header = wp_remote_retrieve_header( $response, 'cache-control' );
  }

  ?>

  <h3>HTTPS Check</h3>
  <?php 
    if($url_protocol === "https")
      echo '<p style="color:green">✅ Your website is served over HTTPS</p>';
    else 
      echo '<p style="color:red">❌ Your website is not served over HTTPS</p>';
  ?>
  

  <h3>Cache-Control Check</h3>
  <?php
    if(strpos($cache_control_header, 'no-store') === false)
      echo '<p style="color:green">✅ "no-store" is not found in your Cache-Control response header</p>';
    else {
      echo '<p style="color:red">❌ "no-store" is found in your Cache-Control response header. Please contact your hosting provider to remove this.</p>';
      echo "<p>Current Cache-Control: <code>{$cache_control_header}</code></p>";
      echo "<p>Suggested Cache-Control: <code>no-cache, must-revalidate, max-age=0</code></p>";
    }
}
?>