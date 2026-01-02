<?php
/**
 * Assets Class
 *
 * @package Whippet
 */

namespace Whippet;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle asset enqueuing
 */
class Assets {

	/**
	 * Whippet admin pages
	 *
	 * @var array
	 */
	private $admin_pages = array(
		'tools_page_whippet',
		'tools_page_whippet-analytics',
		'tools_page_whippet-import-export',
		'tools_page_whippet-tutorials',
	);

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		// Always load font preconnect on admin pages
		add_action( 'admin_head', array( $this, 'add_font_preconnect' ), 1 );
	}

	/**
	 * Enqueue admin assets
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( ! in_array( $hook, $this->admin_pages, true ) ) {
			return;
		}

		$this->enqueue_styles();
		$this->enqueue_scripts();
	}

	/**
	 * Enqueue admin styles
	 */
	private function enqueue_styles() {
		// Enqueue Figtree font from Google Fonts (only once)
		if ( ! wp_style_is( 'whippet-figtree-font', 'enqueued' ) ) {
			wp_enqueue_style(
				'whippet-figtree-font',
				'https://fonts.googleapis.com/css2?family=Figtree:ital,wght@0,300..900;1,300..900&display=swap',
				array(),
				null
			);
		}

		wp_enqueue_style(
			'whippet-admin',
			WHIPPET_URL . 'dist/css/style.css',
			array( 'whippet-figtree-font' ),
			WHIPPET_VERSION
		);
	}

	/**
	 * Add preconnect links for Google Fonts
	 */
	public function add_font_preconnect() {
		echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
		echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
		echo '<style id="whippet-figtree-inline">' . "\n";
		echo '.whippet-admin, #whippet, .whippet-panel, .whippet-form, .whippet-wrapper, [class*="whippet"], [id*="whippet"], .whippet-admin *, #whippet *, .whippet-panel *, .whippet-form *, .whippet-wrapper *, [class*="whippet"] *, [id*="whippet"] * { font-family: "Figtree", ui-sans-serif, system-ui, sans-serif !important; }' . "\n";
		echo 'code, pre, .g-regex textarea { font-family: "SF Mono", Monaco, "Cascadia Code", "Roboto Mono", Consolas, "Courier New", monospace !important; }' . "\n";
		echo '</style>' . "\n";
	}

	/**
	 * Enqueue admin scripts
	 */
	private function enqueue_scripts() {
		// Alpine.js for tabs functionality
		wp_enqueue_script(
			'alpinejs',
			'https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js',
			array(),
			'3.0.0',
			true
		);
		
		wp_script_add_data( 'alpinejs', 'defer', true );

		// Custom admin scripts if needed
		if ( file_exists( WHIPPET_PATH . 'dist/js/app.js' ) ) {
			wp_enqueue_script(
				'whippet-admin',
				WHIPPET_URL . 'dist/js/app.js',
				array( 'jquery' ),
				WHIPPET_VERSION,
				true
			);

			// Localize script with data
			wp_localize_script(
				'whippet-admin',
				'whippetData',
				array(
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'whippet-admin-nonce' ),
				)
			);
		}
	}
}

