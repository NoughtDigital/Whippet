<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function whippet_images_settings_webp() {
	$enable_cdn = get_option( 'whippet_images_enable_cdn' );
	$cdn_url    = add_query_arg(
		array(
			'page'         => 'whippet',
			'whippet_ltab' => 'cdn',
		),
		admin_url( 'tools.php' )
	) . '#lazyload';
	?>
	<div class="wa-card-body">
		<?php if ( ! $enable_cdn ) : ?>
			<p class="wa-card-status is-error">
				<?php esc_html_e( 'CDN must be enabled for WebP images.', 'whippet' ); ?>
			</p>
			<p class="description">
				<?php
				printf(
					/* translators: %s: CDN settings URL */
					wp_kses(
						__( 'Turn on the CDN under the <a href="%s">CDN</a> tab first. WebP conversion is delivered through the CDN when the browser supports it.', 'whippet' ),
						array( 'a' => array( 'href' => array() ) )
					),
					esc_url( $cdn_url )
				);
				?>
			</p>
		<?php else : ?>
			<p class="wa-card-status is-ok">
				<?php esc_html_e( 'WebP is active via the CDN.', 'whippet' ); ?>
			</p>
			<p class="description">
				<?php esc_html_e( 'Images are converted to WebP on the fly when the browser supports it. No extra configuration is required.', 'whippet' ); ?>
			</p>
		<?php endif; ?>
	</div>
	<?php
}
