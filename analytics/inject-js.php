<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function whippet_analytics_inject_js() {

  $id = sanitize_text_field( get_option( 'whippet_analytics_id' ) );
  $method = sanitize_text_field( get_option( 'whippet_analytics_method' ) );
  $disable_on_login = get_option( 'whippet_analytics_disable_on_login' );

  if ( empty( $id ) ) {
	return;
  }
  if ( $disable_on_login && current_user_can( 'manage_options' ) ) {
	return;
  }

  $escaped_id = esc_js( $id );

  if ( 'minimal-analytics' === $method ) {
    $local_js = esc_url( plugins_url( 'js/minimal-analytics.js', __FILE__ ) );
    echo "<script>window.GA_ID='" . $escaped_id . "'</script>";
    echo "<script src='" . $local_js . "' defer></script>";
  } else {
    $local_js = esc_url( plugins_url( 'js/gtag.js', __FILE__ ) );
    echo "<script>window.GA_ID='" . $escaped_id . "'</script>";
    echo "<script src='" . $local_js . "' defer></script>";
    echo "<script>window.dataLayer = window.dataLayer || [];function gtag(){dataLayer.push(arguments);}gtag('js', new Date());gtag('config', '" . $escaped_id . "');</script>";
  }
}

add_action( 'wp_print_footer_scripts', 'whippet_analytics_inject_js');
