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
		wp_enqueue_style(
			'whippet-admin',
			WHIPPET_URL . 'dist/css/admin.css',
			array(),
			WHIPPET_VERSION
		);
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

