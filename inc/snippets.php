<?php
/**
 * Whippet Snippet Manager
 *
 * Flat-file code snippets (PHP, CSS, JS, HTML) with condition rules.
 *
 * @package Whippet
 */

namespace Whippet;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SnippetManager {

	const SAFE_MODE_COOKIE = 'whippet_safe_mode';

	private static $instance = null;
	private $delay_bootstrap_printed = false;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'init', array( $this, 'handle_admin_actions' ) );
		add_action( 'init', array( $this, 'handle_safe_mode_toggle' ), 1 );
		add_action( 'init', array( $this, 'run_php_snippets' ), 50 );
		add_action( 'wp_head', array( $this, 'output_head_snippets' ), 99 );
		add_action( 'wp_body_open', array( $this, 'output_body_snippets' ), 10 );
		add_action( 'wp_footer', array( $this, 'output_footer_snippets' ), 99 );
		add_action( 'admin_notices', array( $this, 'render_admin_notices' ) );
	}

	public function handle_safe_mode_toggle() {
		if ( ! is_admin() ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( isset( $_GET['whippet_safe_mode'] ) ) {
			$enabled = '1' === (string) $_GET['whippet_safe_mode'];
			$ttl     = time() + HOUR_IN_SECONDS;
			setcookie( self::SAFE_MODE_COOKIE, $enabled ? '1' : '0', $ttl, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
			$_COOKIE[ self::SAFE_MODE_COOKIE ] = $enabled ? '1' : '0';
			add_settings_error(
				'whippet_snippets',
				'whippet_snippet_safe_mode',
				$enabled ? __( 'Safe mode enabled for 1 hour.', 'whippet' ) : __( 'Safe mode disabled.', 'whippet' ),
				'success'
			);
		}
	}

	public function is_safe_mode() {
		return isset( $_COOKIE[ self::SAFE_MODE_COOKIE ] ) && '1' === $_COOKIE[ self::SAFE_MODE_COOKIE ];
	}

	private function get_storage_dir() {
		$uploads = wp_upload_dir();
		return trailingslashit( $uploads['basedir'] ) . 'whippet-snippets';
	}

	private function get_storage_url() {
		$uploads = wp_upload_dir();
		return trailingslashit( $uploads['baseurl'] ) . 'whippet-snippets';
	}

	private function get_snippets_file_path() {
		$file = 'snippets.json';
		if ( is_multisite() ) {
			$file = 'snippets-' . get_current_blog_id() . '.json';
		}
		return trailingslashit( $this->get_storage_dir() ) . $file;
	}

	private function ensure_storage_ready() {
		$dir = $this->get_storage_dir();
		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
		}
		$asset_dir = trailingslashit( $dir ) . 'assets';
		if ( ! file_exists( $asset_dir ) ) {
			wp_mkdir_p( $asset_dir );
		}
	}

	private function normalize_snippet( $snippet ) {
		$defaults = array(
			'id'                => wp_generate_uuid4(),
			'title'             => '',
			'description'       => '',
			'tags'              => array(),
			'type'              => 'js',
			'location'          => 'footer',
			'enabled'           => true,
			'code'              => '',
			'priority'          => 10,
			'delivery'          => 'inline',
			'minify'            => false,
			'defer'             => false,
			'async'             => false,
			'delay_interaction' => false,
			'preload'           => false,
			'conditions'        => array(),
			'condition_mode'    => 'all',
			'updated_at'        => time(),
		);
		$snippet = wp_parse_args( (array) $snippet, $defaults );

		$snippet['id']          = sanitize_key( (string) $snippet['id'] );
		$snippet['title']       = sanitize_text_field( $snippet['title'] );
		$snippet['description'] = sanitize_text_field( $snippet['description'] );
		$snippet['type']        = in_array( $snippet['type'], array( 'php', 'css', 'js', 'html' ), true ) ? $snippet['type'] : 'js';
		$snippet['location']    = in_array( $snippet['location'], array( 'head', 'body', 'footer' ), true ) ? $snippet['location'] : 'footer';
		$snippet['priority']    = absint( $snippet['priority'] );
		$snippet['delivery']    = 'file' === $snippet['delivery'] ? 'file' : 'inline';
		$snippet['condition_mode'] = 'any' === $snippet['condition_mode'] ? 'any' : 'all';
		$snippet['enabled']     = ! empty( $snippet['enabled'] );
		$snippet['minify']      = ! empty( $snippet['minify'] );
		$snippet['defer']       = ! empty( $snippet['defer'] );
		$snippet['async']       = ! empty( $snippet['async'] );
		$snippet['delay_interaction'] = ! empty( $snippet['delay_interaction'] );
		$snippet['preload']     = ! empty( $snippet['preload'] );
		$snippet['updated_at']  = time();

		if ( ! is_array( $snippet['conditions'] ) ) {
			$snippet['conditions'] = array_filter( array_map( 'trim', explode( "\n", (string) $snippet['conditions'] ) ) );
		}
		$snippet['conditions'] = array_values( array_filter( array_map( 'sanitize_text_field', $snippet['conditions'] ) ) );

		if ( ! is_array( $snippet['tags'] ) ) {
			$snippet['tags'] = array_filter( array_map( 'trim', explode( ',', (string) $snippet['tags'] ) ) );
		}
		$snippet['tags'] = array_values( array_filter( array_map( 'sanitize_text_field', $snippet['tags'] ) ) );

		$snippet['code'] = (string) $snippet['code'];
		return $snippet;
	}

	public function load_snippets() {
		$this->ensure_storage_ready();
		$file = $this->get_snippets_file_path();
		if ( ! file_exists( $file ) ) {
			return array();
		}
		$content = file_get_contents( $file );
		if ( false === $content || '' === trim( $content ) ) {
			return array();
		}
		$data = json_decode( $content, true );
		if ( ! is_array( $data ) ) {
			return array();
		}

		$snippets = array();
		foreach ( $data as $snippet ) {
			$snippets[] = $this->normalize_snippet( $snippet );
		}
		return $snippets;
	}

	public function export_snippets() {
		return $this->load_snippets();
	}

	public function import_snippets( $snippets ) {
		if ( ! is_array( $snippets ) ) {
			return false;
		}
		$normalized = array();
		foreach ( $snippets as $snippet ) {
			$normalized[] = $this->normalize_snippet( $snippet );
		}
		return $this->save_snippets( $normalized );
	}

	private function save_snippets( $snippets ) {
		$this->ensure_storage_ready();
		$file = $this->get_snippets_file_path();
		$body = wp_json_encode( array_values( $snippets ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		return false !== file_put_contents( $file, $body );
	}

	private function minify_css( $code ) {
		$code = preg_replace( '#/\*.*?\*/#s', '', $code );
		$code = preg_replace( '/\s+/', ' ', $code );
		$code = str_replace( array( '; ', ': ', ' {', '{ ', ' }', '} ', ', ' ), array( ';', ':', '{', '{', '}', '}', ',' ), $code );
		return trim( $code );
	}

	private function minify_js( $code ) {
		$code = preg_replace( '#/\*.*?\*/#s', '', $code );
		$code = preg_replace( '#^\s*//.*$#m', '', $code );
		$code = preg_replace( '/\s+/', ' ', $code );
		return trim( $code );
	}

	private function validate_php_syntax( $code ) {
		try {
			eval( 'if(false){' . $code . '}' );
		} catch ( \Throwable $e ) {
			return $e->getMessage();
		}
		return '';
	}

	public function handle_admin_actions() {
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( empty( $_POST['whippet_snippets_action'] ) ) {
			return;
		}
		if ( empty( $_POST['whippet_snippets_nonce'] ) || ! wp_verify_nonce( $_POST['whippet_snippets_nonce'], 'whippet_snippets_nonce' ) ) {
			return;
		}

		$action   = sanitize_key( wp_unslash( $_POST['whippet_snippets_action'] ) );
		$snippets = $this->load_snippets();

		if ( 'save_snippet' === $action ) {
			$raw      = isset( $_POST['snippet'] ) ? (array) wp_unslash( $_POST['snippet'] ) : array();
			$snippet  = $this->normalize_snippet( $raw );
			$has_code = '' !== trim( $snippet['code'] );
			if ( '' === $snippet['title'] || ! $has_code ) {
				add_settings_error( 'whippet_snippets', 'whippet_snippet_invalid', __( 'Title and code are required.', 'whippet' ), 'error' );
				return;
			}

			if ( 'php' === $snippet['type'] ) {
				$error = $this->validate_php_syntax( $snippet['code'] );
				if ( $error ) {
					add_settings_error( 'whippet_snippets', 'whippet_snippet_php_error', sprintf( __( 'PHP syntax error: %s', 'whippet' ), $error ), 'error' );
					return;
				}
			}

			$updated = false;
			foreach ( $snippets as $index => $existing ) {
				if ( $existing['id'] === $snippet['id'] ) {
					$snippets[ $index ] = $snippet;
					$updated = true;
					break;
				}
			}
			if ( ! $updated ) {
				$snippets[] = $snippet;
			}
			$this->save_snippets( $snippets );
			add_settings_error( 'whippet_snippets', 'whippet_snippet_saved', __( 'Snippet saved.', 'whippet' ), 'success' );
		}

		if ( 'delete_snippet' === $action ) {
			$id = isset( $_POST['snippet_id'] ) ? sanitize_key( wp_unslash( $_POST['snippet_id'] ) ) : '';
			if ( $id ) {
				$snippets = array_values(
					array_filter(
						$snippets,
						function( $snippet ) use ( $id ) {
							return $snippet['id'] !== $id;
						}
					)
				);
				$this->save_snippets( $snippets );
				add_settings_error( 'whippet_snippets', 'whippet_snippet_deleted', __( 'Snippet deleted.', 'whippet' ), 'success' );
			}
		}

		if ( 'import_snippets' === $action && ! empty( $_FILES['snippets_file']['tmp_name'] ) ) {
			$file = sanitize_text_field( wp_unslash( $_FILES['snippets_file']['tmp_name'] ) );
			if ( is_uploaded_file( $file ) ) {
				$json = file_get_contents( $file );
				$data = json_decode( (string) $json, true );
				if ( is_array( $data ) ) {
					$imported = array();
					foreach ( $data as $snippet ) {
						$imported[] = $this->normalize_snippet( $snippet );
					}
					$this->save_snippets( $imported );
					add_settings_error( 'whippet_snippets', 'whippet_snippet_imported', __( 'Snippets imported.', 'whippet' ), 'success' );
				}
			}
		}

		if ( 'bulk_action' === $action ) {
			$bulk = isset( $_POST['bulk_action'] ) ? sanitize_key( wp_unslash( $_POST['bulk_action'] ) ) : '';
			$ids  = isset( $_POST['snippet_ids'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['snippet_ids'] ) ) : array();
			if ( ! empty( $ids ) && 'export' === $bulk ) {
				$selected = array_values(
					array_filter(
						$snippets,
						function( $snippet ) use ( $ids ) {
							return in_array( $snippet['id'], $ids, true );
						}
					)
				);
				nocache_headers();
				header( 'Content-Type: application/json; charset=utf-8' );
				header( 'Content-Disposition: attachment; filename=whippet-snippets-selected-' . gmdate( 'Y-m-d' ) . '.json' );
				echo wp_json_encode( $selected, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
				exit;
			}
			if ( ! empty( $ids ) && in_array( $bulk, array( 'enable', 'disable', 'delete' ), true ) ) {
				$new_snippets = array();
				foreach ( $snippets as $snippet ) {
					$selected = in_array( $snippet['id'], $ids, true );
					if ( 'delete' === $bulk && $selected ) {
						continue;
					}
					if ( $selected && in_array( $bulk, array( 'enable', 'disable' ), true ) ) {
						$snippet['enabled'] = 'enable' === $bulk;
					}
					$new_snippets[] = $snippet;
				}
				$this->save_snippets( $new_snippets );
				add_settings_error( 'whippet_snippets', 'whippet_snippet_bulk', __( 'Bulk action completed.', 'whippet' ), 'success' );
			}
		}
	}

	public function render_admin_notices() {
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		settings_errors( 'whippet_snippets' );
	}

	private function evaluate_condition( $rule ) {
		$rule = trim( $rule );
		if ( '' === $rule ) {
			return true;
		}
		if ( 'is_front_page' === $rule ) {
			return is_front_page();
		}
		if ( 'is_home' === $rule ) {
			return is_home();
		}
		if ( 'is_singular' === $rule ) {
			return is_singular();
		}
		if ( 'user_logged_in' === $rule ) {
			return is_user_logged_in();
		}
		if ( 'user_logged_out' === $rule ) {
			return ! is_user_logged_in();
		}
		if ( 0 === strpos( $rule, 'is_page:' ) ) {
			$slug = trim( substr( $rule, 8 ) );
			return '' !== $slug ? is_page( $slug ) : false;
		}
		if ( 0 === strpos( $rule, 'post_type:' ) ) {
			$post_type = trim( substr( $rule, 10 ) );
			return '' !== $post_type ? is_singular( $post_type ) : false;
		}
		if ( 0 === strpos( $rule, 'url_contains:' ) ) {
			$needle  = trim( substr( $rule, 13 ) );
			$request = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
			return '' !== $needle && false !== strpos( $request, $needle );
		}
		return false;
	}

	private function conditions_match( $snippet ) {
		if ( empty( $snippet['conditions'] ) ) {
			return true;
		}
		$results = array_map( array( $this, 'evaluate_condition' ), $snippet['conditions'] );
		if ( 'any' === $snippet['condition_mode'] ) {
			return in_array( true, $results, true );
		}
		return ! in_array( false, $results, true );
	}

	private function get_sorted_snippets_by_type_and_location( $type, $location ) {
		$snippets = array_filter(
			$this->load_snippets(),
			function( $snippet ) use ( $type, $location ) {
				if ( ! $snippet['enabled'] || $snippet['type'] !== $type ) {
					return false;
				}
				if ( 'php' !== $type && $snippet['location'] !== $location ) {
					return false;
				}
				return $this->conditions_match( $snippet );
			}
		);
		usort(
			$snippets,
			function( $a, $b ) {
				return (int) $a['priority'] <=> (int) $b['priority'];
			}
		);
		return $snippets;
	}

	public function run_php_snippets() {
		if ( is_admin() || $this->is_safe_mode() ) {
			return;
		}
		$snippets = $this->get_sorted_snippets_by_type_and_location( 'php', 'head' );
		if ( empty( $snippets ) ) {
			return;
		}

		$all = $this->load_snippets();
		$changed = false;

		foreach ( $snippets as $snippet ) {
			try {
				eval( $snippet['code'] );
			} catch ( \Throwable $e ) {
				foreach ( $all as $index => $candidate ) {
					if ( $candidate['id'] === $snippet['id'] ) {
						$all[ $index ]['enabled'] = false;
						$changed = true;
						add_option( 'whippet_snippet_last_error', $e->getMessage(), '', false );
					}
				}
			}
		}

		if ( $changed ) {
			$this->save_snippets( $all );
		}
	}

	private function write_asset_file( $snippet ) {
		$this->ensure_storage_ready();
		$ext = 'js' === $snippet['type'] ? 'js' : 'css';
		$dir = trailingslashit( $this->get_storage_dir() ) . 'assets';
		$src = trailingslashit( $this->get_storage_url() ) . 'assets';
		$path = trailingslashit( $dir ) . $snippet['id'] . '.' . $ext;
		$url  = trailingslashit( $src ) . $snippet['id'] . '.' . $ext;
		$code = $snippet['code'];
		if ( $snippet['minify'] ) {
			$code = 'js' === $snippet['type'] ? $this->minify_js( $code ) : $this->minify_css( $code );
		}
		file_put_contents( $path, $code );
		return array( $path, $url );
	}

	private function render_delayed_js_bootstrap() {
		if ( $this->delay_bootstrap_printed ) {
			return;
		}
		$this->delay_bootstrap_printed = true;
		echo '<script>(function(){var fired=false;function run(){if(fired)return;fired=true;document.querySelectorAll("script[type=\'application/whippet-delayed\']").forEach(function(node){var s=document.createElement("script");if(node.dataset.src){s.src=node.dataset.src;}else{s.text=node.textContent;}if(node.dataset.defer==="1"){s.defer=true;}if(node.dataset.async==="1"){s.async=true;}document.body.appendChild(s);node.remove();});}["click","keydown","touchstart","scroll","mousemove"].forEach(function(evt){window.addEventListener(evt,run,{once:true,passive:true});});})();</script>';
	}

	private function output_snippets_for_location( $location ) {
		if ( is_admin() || $this->is_safe_mode() ) {
			return;
		}

		$css = $this->get_sorted_snippets_by_type_and_location( 'css', $location );
		$js  = $this->get_sorted_snippets_by_type_and_location( 'js', $location );
		$html = $this->get_sorted_snippets_by_type_and_location( 'html', $location );

		foreach ( $css as $snippet ) {
			if ( 'file' === $snippet['delivery'] ) {
				list( $path, $url ) = $this->write_asset_file( $snippet );
				$ver = file_exists( $path ) ? (string) filemtime( $path ) : (string) time();
				if ( $snippet['preload'] ) {
					echo '<link rel="preload" as="style" href="' . esc_url( add_query_arg( 'ver', $ver, $url ) ) . '" onload="this.onload=null;this.rel=\'stylesheet\'">';
				} else {
					echo '<link rel="stylesheet" href="' . esc_url( add_query_arg( 'ver', $ver, $url ) ) . '">';
				}
			} else {
				$code = $snippet['minify'] ? $this->minify_css( $snippet['code'] ) : $snippet['code'];
				echo "<style>\n" . $code . "\n</style>\n";
			}
		}

		foreach ( $html as $snippet ) {
			echo $snippet['code'] . "\n";
		}

		foreach ( $js as $snippet ) {
			if ( 'file' === $snippet['delivery'] ) {
				list( $path, $url ) = $this->write_asset_file( $snippet );
				$ver   = file_exists( $path ) ? (string) filemtime( $path ) : (string) time();
				$src   = esc_url( add_query_arg( 'ver', $ver, $url ) );
				$defer = $snippet['defer'] ? ' defer' : '';
				$async = $snippet['async'] ? ' async' : '';
				if ( $snippet['delay_interaction'] ) {
					$this->render_delayed_js_bootstrap();
					echo '<script type="application/whippet-delayed" data-src="' . $src . '" data-defer="' . ( $snippet['defer'] ? '1' : '0' ) . '" data-async="' . ( $snippet['async'] ? '1' : '0' ) . '"></script>';
				} else {
					echo '<script src="' . $src . '"' . $defer . $async . '></script>';
				}
			} else {
				$code = $snippet['minify'] ? $this->minify_js( $snippet['code'] ) : $snippet['code'];
				if ( $snippet['delay_interaction'] ) {
					$this->render_delayed_js_bootstrap();
					echo '<script type="application/whippet-delayed" data-defer="' . ( $snippet['defer'] ? '1' : '0' ) . '" data-async="' . ( $snippet['async'] ? '1' : '0' ) . '">' . $code . '</script>';
				} else {
					$defer = $snippet['defer'] ? ' defer' : '';
					$async = $snippet['async'] ? ' async' : '';
					echo '<script' . $defer . $async . '>' . $code . '</script>';
				}
			}
		}
	}

	public function output_head_snippets() {
		$this->output_snippets_for_location( 'head' );
	}

	public function output_body_snippets() {
		$this->output_snippets_for_location( 'body' );
	}

	public function output_footer_snippets() {
		$this->output_snippets_for_location( 'footer' );
	}
}

SnippetManager::instance();

function whippet_render_snippets_manager() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$manager      = SnippetManager::instance();
	$snippets     = $manager->load_snippets();
	$safe_mode_on = $manager->is_safe_mode();
	$edit_id      = isset( $_GET['snippet_edit'] ) ? sanitize_key( wp_unslash( $_GET['snippet_edit'] ) ) : '';
	$current      = null;

	foreach ( $snippets as $snippet ) {
		if ( $snippet['id'] === $edit_id ) {
			$current = $snippet;
			break;
		}
	}
	$is_editing = null !== $current;

	if ( ! $current ) {
		$current = array(
			'id'                => wp_generate_uuid4(),
			'title'             => '',
			'description'       => '',
			'tags'              => array(),
			'type'              => 'js',
			'location'          => 'footer',
			'enabled'           => true,
			'code'              => '',
			'priority'          => 10,
			'delivery'          => 'inline',
			'minify'            => false,
			'defer'             => false,
			'async'             => false,
			'delay_interaction' => false,
			'preload'           => false,
			'conditions'        => array(),
			'condition_mode'    => 'all',
		);
	}
	$snippet_tab  = isset( $_GET['whippet_stab'] ) ? sanitize_key( wp_unslash( $_GET['whippet_stab'] ) ) : 'editor';
	if ( ! in_array( $snippet_tab, array( 'editor', 'list' ), true ) ) {
		$snippet_tab = 'editor';
	}
	$new_url    = add_query_arg(
		array(
			'page'          => 'whippet',
			'whippet_stab'  => 'editor',
		),
		admin_url( 'tools.php' )
	);
	?>
	<div style="padding:1.25rem;">
		<p style="font-size:.875rem;color:#64748b;">
			<?php esc_html_e( 'Flat-file snippets stored in uploads/whippet-snippets for fast, zero-query frontend loading. On multisite, each site uses its own snippet file.', 'whippet' ); ?>
		</p>

		<?php if ( 'editor' === $snippet_tab ) : ?>
			<div class="whippet-snippet-overview" style="display:flex;flex-wrap:wrap;align-items:center;justify-content:flex-end;gap:.75rem;margin:.75rem 0 1rem;">
				<a class="button button-secondary" href="<?php echo esc_url( add_query_arg( array( 'page' => 'whippet', 'whippet_safe_mode' => $safe_mode_on ? '0' : '1' ), admin_url( 'tools.php' ) ) . '#snippets' ); ?>">
					<?php echo $safe_mode_on ? esc_html__( 'Disable Safe Mode', 'whippet' ) : esc_html__( 'Enable Safe Mode (1 hour)', 'whippet' ); ?>
				</a>
			</div>
		<?php endif; ?>

		<?php if ( 'editor' === $snippet_tab ) : ?>
			<div class="whippet-snippet-section-head" style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;margin:0 0 1rem;">
				<div>
					<h3 style="margin:0 0 .35rem;font-size:1rem;">
						<?php echo $is_editing ? esc_html__( 'Edit Snippet', 'whippet' ) : esc_html__( 'Add New Snippet', 'whippet' ); ?>
					</h3>
					<p style="margin:0;color:#64748b;font-size:.8125rem;">
						<?php echo $is_editing ? esc_html__( 'Update the snippet below, then save your changes.', 'whippet' ) : esc_html__( 'Create a new snippet and choose where and when it should run.', 'whippet' ); ?>
					</p>
				</div>
				<?php if ( $is_editing ) : ?>
					<a class="button" href="<?php echo esc_url( $new_url . '#snippets' ); ?>"><?php esc_html_e( 'Start New Snippet', 'whippet' ); ?></a>
				<?php endif; ?>
			</div>

			<form method="post" class="whippet-snippet-editor-form" style="margin-bottom:1.25rem;">
				<?php wp_nonce_field( 'whippet_snippets_nonce', 'whippet_snippets_nonce' ); ?>
				<input type="hidden" name="whippet_snippets_action" value="save_snippet">
				<input type="hidden" name="snippet[id]" value="<?php echo esc_attr( $current['id'] ); ?>">
				<table class="form-table" role="presentation">
					<tbody>
					<tr>
						<th><label for="whippet-snippet-title"><?php esc_html_e( 'Title', 'whippet' ); ?></label></th>
						<td><input id="whippet-snippet-title" name="snippet[title]" type="text" class="regular-text" value="<?php echo esc_attr( $current['title'] ); ?>"></td>
					</tr>
					<tr>
						<th><label for="whippet-snippet-description"><?php esc_html_e( 'Description', 'whippet' ); ?></label></th>
						<td><input id="whippet-snippet-description" name="snippet[description]" type="text" class="regular-text" value="<?php echo esc_attr( $current['description'] ); ?>"></td>
					</tr>
					<tr>
						<th><label for="whippet-snippet-tags"><?php esc_html_e( 'Tags', 'whippet' ); ?></label></th>
						<td><input id="whippet-snippet-tags" name="snippet[tags]" type="text" class="regular-text" value="<?php echo esc_attr( implode( ', ', (array) $current['tags'] ) ); ?>"><p class="description"><?php esc_html_e( 'Comma-separated.', 'whippet' ); ?></p></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Type', 'whippet' ); ?></th>
						<td>
							<select name="snippet[type]">
								<?php foreach ( array( 'php' => 'PHP', 'css' => 'CSS', 'js' => 'JS', 'html' => 'HTML' ) as $key => $label ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current['type'], $key ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Location', 'whippet' ); ?></th>
						<td>
							<select name="snippet[location]">
								<option value="head" <?php selected( $current['location'], 'head' ); ?>><?php esc_html_e( 'Head', 'whippet' ); ?></option>
								<option value="body" <?php selected( $current['location'], 'body' ); ?>><?php esc_html_e( 'Body Open', 'whippet' ); ?></option>
								<option value="footer" <?php selected( $current['location'], 'footer' ); ?>><?php esc_html_e( 'Footer', 'whippet' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Priority', 'whippet' ); ?></th>
						<td><input name="snippet[priority]" type="number" min="1" value="<?php echo esc_attr( (string) $current['priority'] ); ?>"></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Delivery', 'whippet' ); ?></th>
						<td>
							<select name="snippet[delivery]">
								<option value="inline" <?php selected( $current['delivery'], 'inline' ); ?>><?php esc_html_e( 'Inline', 'whippet' ); ?></option>
								<option value="file" <?php selected( $current['delivery'], 'file' ); ?>><?php esc_html_e( 'File', 'whippet' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Conditions', 'whippet' ); ?></th>
						<td>
							<select name="snippet[condition_mode]">
								<option value="all" <?php selected( $current['condition_mode'], 'all' ); ?>><?php esc_html_e( 'Match all rules', 'whippet' ); ?></option>
								<option value="any" <?php selected( $current['condition_mode'], 'any' ); ?>><?php esc_html_e( 'Match any rule', 'whippet' ); ?></option>
							</select>
							<textarea name="snippet[conditions]" rows="5" class="large-text code" placeholder="is_front_page&#10;url_contains:/checkout"><?php echo esc_textarea( implode( "\n", (array) $current['conditions'] ) ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Rule examples: is_front_page, is_home, is_singular, is_page:contact, post_type:product, url_contains:/shop, user_logged_in, user_logged_out', 'whippet' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><label for="whippet-snippet-code"><?php esc_html_e( 'Code', 'whippet' ); ?></label></th>
						<td><textarea id="whippet-snippet-code" name="snippet[code]" rows="12" class="large-text code"><?php echo esc_textarea( $current['code'] ); ?></textarea></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Optimisations', 'whippet' ); ?></th>
						<td>
							<label style="margin-right:1rem;"><input type="checkbox" name="snippet[enabled]" value="1" <?php checked( ! empty( $current['enabled'] ) ); ?>> <?php esc_html_e( 'Enabled', 'whippet' ); ?></label>
							<label style="margin-right:1rem;"><input type="checkbox" name="snippet[minify]" value="1" <?php checked( ! empty( $current['minify'] ) ); ?>> <?php esc_html_e( 'Minify', 'whippet' ); ?></label>
							<label style="margin-right:1rem;"><input type="checkbox" name="snippet[defer]" value="1" <?php checked( ! empty( $current['defer'] ) ); ?>> <?php esc_html_e( 'Defer (JS)', 'whippet' ); ?></label>
							<label style="margin-right:1rem;"><input type="checkbox" name="snippet[async]" value="1" <?php checked( ! empty( $current['async'] ) ); ?>> <?php esc_html_e( 'Async (JS)', 'whippet' ); ?></label>
							<label style="margin-right:1rem;"><input type="checkbox" name="snippet[delay_interaction]" value="1" <?php checked( ! empty( $current['delay_interaction'] ) ); ?>> <?php esc_html_e( 'Delay until interaction (JS)', 'whippet' ); ?></label>
							<label><input type="checkbox" name="snippet[preload]" value="1" <?php checked( ! empty( $current['preload'] ) ); ?>> <?php esc_html_e( 'Preload (CSS file)', 'whippet' ); ?></label>
						</td>
					</tr>
					</tbody>
				</table>
				<p class="whippet-snippet-editor-actions">
					<button type="submit" class="button button-primary"><?php echo $is_editing ? esc_html__( 'Save Changes', 'whippet' ) : esc_html__( 'Save Snippet', 'whippet' ); ?></button>
					<a class="button" href="<?php echo esc_url( add_query_arg( array( 'page' => 'whippet', 'whippet_stab' => 'list' ), admin_url( 'tools.php' ) ) . '#snippets' ); ?>"><?php esc_html_e( 'View Snippets', 'whippet' ); ?></a>
				</p>
			</form>
		<?php else : ?>
			<div class="whippet-snippet-section-head" style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;margin:0 0 1rem;">
				<div>
					<h3 style="margin:0 0 .35rem;font-size:1rem;"><?php esc_html_e( 'Saved Snippets', 'whippet' ); ?></h3>
					<p style="margin:0;color:#64748b;font-size:.8125rem;"><?php esc_html_e( 'Manage your saved snippets, export them, or jump into editing from here.', 'whippet' ); ?></p>
				</div>
			</div>

			<div class="whippet-snippet-toolbar-grid">
				<div class="whippet-snippet-import-bar">
					<div class="whippet-snippet-toolbar-copy">
						<strong><?php esc_html_e( 'Import or export', 'whippet' ); ?></strong>
						<span><?php esc_html_e( 'Bring in snippets from JSON or export your current library.', 'whippet' ); ?></span>
					</div>
					<form method="post" enctype="multipart/form-data" class="whippet-snippet-import-form">
						<?php wp_nonce_field( 'whippet_snippets_nonce', 'whippet_snippets_nonce' ); ?>
						<input type="hidden" name="whippet_snippets_action" value="import_snippets">
						<input id="whippet-snippets-file" type="file" name="snippets_file" accept=".json" required class="screen-reader-text">
						<label for="whippet-snippets-file" class="button button-secondary"><?php esc_html_e( 'Choose file', 'whippet' ); ?></label>
						<span id="whippet-snippets-file-name" class="whippet-snippets-file-name"><?php esc_html_e( 'No file chosen', 'whippet' ); ?></span>
						<button class="button button-secondary" type="submit"><?php esc_html_e( 'Import JSON', 'whippet' ); ?></button>
					</form>
					<a class="button button-secondary whippet-snippet-export" href="<?php echo esc_url( add_query_arg( array( 'page' => 'whippet', 'whippet_snippets_export' => '1' ), admin_url( 'tools.php' ) ) ); ?>"><?php esc_html_e( 'Export All Snippets', 'whippet' ); ?></a>
				</div>
			</div>
			<script>
			(function(){
				var input = document.getElementById('whippet-snippets-file');
				var nameEl = document.getElementById('whippet-snippets-file-name');
				if (!input || !nameEl) return;
				input.addEventListener('change', function(){
					var fileName = input.files && input.files.length ? input.files[0].name : '<?php echo esc_js( __( 'No file chosen', 'whippet' ) ); ?>';
					nameEl.textContent = fileName;
				});
			})();
			</script>

			<?php if ( empty( $snippets ) ) : ?>
				<div class="whippet-snippet-empty-state">
					<strong><?php esc_html_e( 'No snippets yet', 'whippet' ); ?></strong>
					<p><?php esc_html_e( 'Create your first snippet with the button above, or import an existing JSON file to get started.', 'whippet' ); ?></p>
				</div>
			<?php else : ?>
				<form method="post" class="whippet-snippet-bulk-panel">
					<?php wp_nonce_field( 'whippet_snippets_nonce', 'whippet_snippets_nonce' ); ?>
					<input type="hidden" name="whippet_snippets_action" value="bulk_action">
					<div class="whippet-snippet-toolbar-copy">
						<strong><?php esc_html_e( 'Bulk actions', 'whippet' ); ?></strong>
						<span><?php esc_html_e( 'Select snippets below, then enable, disable, export, or delete them in one go.', 'whippet' ); ?></span>
					</div>
					<div class="whippet-snippet-bulk-actions">
						<select name="bulk_action">
							<option value=""><?php esc_html_e( 'Bulk actions', 'whippet' ); ?></option>
							<option value="enable"><?php esc_html_e( 'Enable', 'whippet' ); ?></option>
							<option value="disable"><?php esc_html_e( 'Disable', 'whippet' ); ?></option>
							<option value="export"><?php esc_html_e( 'Export', 'whippet' ); ?></option>
							<option value="delete"><?php esc_html_e( 'Delete', 'whippet' ); ?></option>
						</select>
						<button class="button button-secondary" type="submit"><?php esc_html_e( 'Apply', 'whippet' ); ?></button>
					</div>
					<div class="whippet-snippet-table-wrap">
						<table class="widefat striped">
							<thead>
								<tr>
									<td style="width:24px;"><input type="checkbox" onclick="document.querySelectorAll('.whippet-snippet-check').forEach(cb=>cb.checked=this.checked);"></td>
									<th><?php esc_html_e( 'Snippet', 'whippet' ); ?></th>
									<th><?php esc_html_e( 'Type', 'whippet' ); ?></th>
									<th><?php esc_html_e( 'Location', 'whippet' ); ?></th>
									<th><?php esc_html_e( 'Priority', 'whippet' ); ?></th>
									<th><?php esc_html_e( 'Status', 'whippet' ); ?></th>
									<th><?php esc_html_e( 'Actions', 'whippet' ); ?></th>
								</tr>
							</thead>
							<tbody>
							<?php foreach ( $snippets as $snippet ) : ?>
								<tr>
									<td><input class="whippet-snippet-check" type="checkbox" name="snippet_ids[]" value="<?php echo esc_attr( $snippet['id'] ); ?>"></td>
									<td>
										<strong><?php echo esc_html( $snippet['title'] ?: __( '(Untitled)', 'whippet' ) ); ?></strong>
										<?php if ( ! empty( $snippet['description'] ) ) : ?>
											<div style="color:#64748b;"><?php echo esc_html( $snippet['description'] ); ?></div>
										<?php endif; ?>
									</td>
									<td><?php echo esc_html( strtoupper( $snippet['type'] ) ); ?></td>
									<td><?php echo esc_html( ucfirst( $snippet['location'] ) ); ?></td>
									<td><?php echo esc_html( (string) $snippet['priority'] ); ?></td>
									<td><span class="whippet-snippet-status <?php echo ! empty( $snippet['enabled'] ) ? 'is-enabled' : 'is-disabled'; ?>"><?php echo ! empty( $snippet['enabled'] ) ? esc_html__( 'Enabled', 'whippet' ) : esc_html__( 'Disabled', 'whippet' ); ?></span></td>
									<td>
										<div class="whippet-snippet-row-actions">
											<a class="button button-small" href="<?php echo esc_url( add_query_arg( array( 'page' => 'whippet', 'whippet_stab' => 'editor', 'snippet_edit' => $snippet['id'] ), admin_url( 'tools.php' ) ) . '#snippets' ); ?>"><?php esc_html_e( 'Edit', 'whippet' ); ?></a>
											<a class="button button-small" href="<?php echo esc_url( add_query_arg( array( 'page' => 'whippet', 'whippet_snippet_export_id' => $snippet['id'] ), admin_url( 'tools.php' ) ) ); ?>"><?php esc_html_e( 'Export', 'whippet' ); ?></a>
											<form method="post" class="whippet-snippet-inline-form">
												<?php wp_nonce_field( 'whippet_snippets_nonce', 'whippet_snippets_nonce' ); ?>
												<input type="hidden" name="whippet_snippets_action" value="delete_snippet">
												<input type="hidden" name="snippet_id" value="<?php echo esc_attr( $snippet['id'] ); ?>">
												<button class="button button-small" type="submit" onclick="return confirm('<?php echo esc_js( __( 'Delete this snippet?', 'whippet' ) ); ?>');"><?php esc_html_e( 'Delete', 'whippet' ); ?></button>
											</form>
										</div>
									</td>
								</tr>
							<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</form>
			<?php endif; ?>
		<?php endif; ?>
	</div>
	<?php
}

add_action(
	'admin_init',
	function() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( empty( $_GET['page'] ) || 'whippet' !== $_GET['page'] ) {
			return;
		}
		$manager  = SnippetManager::instance();
		$snippets = $manager->load_snippets();
		if ( ! empty( $_GET['whippet_snippet_export_id'] ) ) {
			$snippet_id = sanitize_key( wp_unslash( $_GET['whippet_snippet_export_id'] ) );
			$snippets = array_values(
				array_filter(
					$snippets,
					function( $snippet ) use ( $snippet_id ) {
						return $snippet['id'] === $snippet_id;
					}
				)
			);
		} elseif ( empty( $_GET['whippet_snippets_export'] ) ) {
			return;
		}
		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=whippet-snippets-' . gmdate( 'Y-m-d' ) . '.json' );
		echo wp_json_encode( $snippets, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
		exit;
	}
);
