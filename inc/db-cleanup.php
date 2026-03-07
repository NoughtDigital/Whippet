<?php
/**
 * Whippet DB Cleanup
 *
 * Cleans up WordPress database: post revisions, auto-drafts, trashed posts,
 * spam/trashed comments, expired transients, and orphaned post meta.
 *
 * Supports:
 *  - Preview-before-run (count rows without deleting)
 *  - WP-Cron scheduling (daily / weekly / monthly)
 *  - Manual AJAX run
 *
 * @package Whippet
 */

namespace Whippet;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Database cleanup class.
 */
class DbCleanup {

	/** @var array Plugin options. */
	private $options;

	/**
	 * Constructor — registers AJAX actions and manages scheduled cleanup.
	 */
	public function __construct() {
		$this->options = (array) get_option( 'whippet_options', array() );

		// AJAX handlers.
		add_action( 'wp_ajax_whippet_run_db_cleanup',     array( $this, 'ajax_run_cleanup' ) );
		add_action( 'wp_ajax_whippet_preview_db_cleanup', array( $this, 'ajax_preview_cleanup' ) );

		// WP-Cron event.
		add_action( 'whippet_db_cleanup_cron', array( $this, 'run_cleanup_tasks' ) );

		// Manage schedule whenever options are updated.
		add_action( 'update_option_whippet_options', array( $this, 'on_options_updated' ), 10, 2 );

		// Bootstrap schedule on load (in case cron was lost after a site move, etc.).
		$this->sync_schedule( $this->options );
	}

	// -------------------------------------------------------------------------
	// AJAX handlers
	// -------------------------------------------------------------------------

	/**
	 * AJAX: preview — return row counts without deleting anything.
	 */
	public function ajax_preview_cleanup() {
		check_ajax_referer( 'whippet_db_cleanup_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'whippet' ) ) );
		}

		$counts = $this->count_cleanup_rows();
		$total  = array_sum( $counts );

		wp_send_json_success(
			array(
				'counts'  => $counts,
				'total'   => $total,
				/* translators: %d: number of rows */
				'message' => sprintf(
					_n( '%d row can be cleaned up.', '%d rows can be cleaned up.', $total, 'whippet' ),
					$total
				),
			)
		);
	}

	/**
	 * AJAX: run cleanup and return a summary.
	 */
	public function ajax_run_cleanup() {
		check_ajax_referer( 'whippet_db_cleanup_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'whippet' ) ) );
		}

		$results = $this->run_cleanup_tasks();
		$total   = array_sum( $results );

		update_option( 'whippet_db_cleanup_last_run', array(
			'time'    => time(),
			'total'   => $total,
			'results' => $results,
		) );

		wp_send_json_success(
			array(
				'results' => $results,
				'total'   => $total,
				/* translators: %d: number of database rows deleted */
				'message' => sprintf(
					_n( '%d database row deleted.', '%d database rows deleted.', $total, 'whippet' ),
					$total
				),
			)
		);
	}

	// -------------------------------------------------------------------------
	// Core cleanup (also called by WP-Cron)
	// -------------------------------------------------------------------------

	/**
	 * Run all cleanup tasks and return counts of deleted rows.
	 *
	 * @return int[] Associative array of deleted row counts per category.
	 */
	public function run_cleanup_tasks() {
		$results = array(
			'revisions'        => $this->delete_revisions(),
			'auto_drafts'      => $this->delete_auto_drafts(),
			'trashed_posts'    => $this->delete_trashed_posts(),
			'spam_comments'    => $this->delete_spam_comments(),
			'trashed_comments' => $this->delete_trashed_comments(),
			'transients'       => $this->delete_expired_transients(),
			'orphaned_meta'    => $this->delete_orphaned_postmeta(),
		);

		update_option( 'whippet_db_cleanup_last_run', array(
			'time'    => time(),
			'total'   => array_sum( $results ),
			'results' => $results,
		) );

		return $results;
	}

	// -------------------------------------------------------------------------
	// Preview (COUNT only — no deletions)
	// -------------------------------------------------------------------------

	/**
	 * Count rows that would be deleted, without actually deleting them.
	 *
	 * @return int[]
	 */
	private function count_cleanup_rows() {
		global $wpdb;
		$now = time();

		return array(
			'revisions'        => (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				"SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type = 'revision'"
			),
			'auto_drafts'      => (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				"SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_status = 'auto-draft'"
			),
			'trashed_posts'    => (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				"SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_status = 'trash'"
			),
			'spam_comments'    => (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				"SELECT COUNT(comment_ID) FROM {$wpdb->comments} WHERE comment_approved = 'spam'"
			),
			'trashed_comments' => (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				"SELECT COUNT(comment_ID) FROM {$wpdb->comments} WHERE comment_approved = 'trash'"
			),
			'transients'       => (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value < %d",
					'_transient_timeout_%',
					$now
				)
			),
			'orphaned_meta'    => (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				"SELECT COUNT(meta_id) FROM {$wpdb->postmeta} pm LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE p.ID IS NULL"
			),
		);
	}

	// -------------------------------------------------------------------------
	// Cleanup tasks
	// -------------------------------------------------------------------------

	private function delete_revisions() {
		global $wpdb;
		$ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'revision'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return $this->delete_posts_by_ids( $ids );
	}

	private function delete_auto_drafts() {
		global $wpdb;
		$ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_status = 'auto-draft'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return $this->delete_posts_by_ids( $ids );
	}

	private function delete_trashed_posts() {
		global $wpdb;
		$ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_status = 'trash'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return $this->delete_posts_by_ids( $ids );
	}

	private function delete_spam_comments() {
		global $wpdb;
		$deleted = $wpdb->query( "DELETE FROM {$wpdb->comments} WHERE comment_approved = 'spam'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$this->delete_orphaned_commentmeta();
		return (int) $deleted;
	}

	private function delete_trashed_comments() {
		global $wpdb;
		$deleted = $wpdb->query( "DELETE FROM {$wpdb->comments} WHERE comment_approved = 'trash'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$this->delete_orphaned_commentmeta();
		return (int) $deleted;
	}

	private function delete_expired_transients() {
		global $wpdb;
		$now = time();

		$deleted = $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value < %d",
				'_transient_timeout_%',
				$now
			)
		);

		// Remove value rows whose timeout rows we just deleted.
		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			"DELETE FROM {$wpdb->options}
			WHERE option_name LIKE '_transient_%'
			AND option_name NOT LIKE '_transient_timeout_%'
			AND REPLACE(option_name, '_transient_', '_transient_timeout_')
				NOT IN (SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_%')"
		);

		return (int) $deleted;
	}

	private function delete_orphaned_postmeta() {
		global $wpdb;
		$deleted = $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			"DELETE pm FROM {$wpdb->postmeta} pm
			LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			WHERE p.ID IS NULL"
		);
		return (int) $deleted;
	}

	// -------------------------------------------------------------------------
	// WP-Cron schedule management
	// -------------------------------------------------------------------------

	/**
	 * Keep the WP-Cron event in sync with the saved schedule option.
	 *
	 * @param array $options Plugin options array.
	 */
	private function sync_schedule( $options ) {
		$wanted   = ! empty( $options['db_cleanup_schedule'] ) ? $options['db_cleanup_schedule'] : '';
		$existing = wp_get_scheduled_event( 'whippet_db_cleanup_cron' );

		if ( $wanted ) {
			if ( ! $existing || $existing->schedule !== $wanted ) {
				wp_clear_scheduled_hook( 'whippet_db_cleanup_cron' );
				wp_schedule_event( time() + HOUR_IN_SECONDS, $wanted, 'whippet_db_cleanup_cron' );
			}
		} else {
			if ( $existing ) {
				wp_clear_scheduled_hook( 'whippet_db_cleanup_cron' );
			}
		}
	}

	/**
	 * React to whippet_options being updated.
	 *
	 * @param mixed $old_value Previous value.
	 * @param mixed $new_value New value.
	 */
	public function on_options_updated( $old_value, $new_value ) {
		$this->options = (array) $new_value;
		$this->sync_schedule( $this->options );
	}

	// -------------------------------------------------------------------------
	// Private helpers
	// -------------------------------------------------------------------------

	private function delete_posts_by_ids( $ids ) {
		if ( empty( $ids ) ) {
			return 0;
		}
		$count = 0;
		foreach ( $ids as $id ) {
			if ( wp_delete_post( (int) $id, true ) ) {
				$count++;
			}
		}
		return $count;
	}

	private function delete_orphaned_commentmeta() {
		global $wpdb;
		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			"DELETE cm FROM {$wpdb->commentmeta} cm
			LEFT JOIN {$wpdb->comments} c ON c.comment_ID = cm.comment_id
			WHERE c.comment_ID IS NULL"
		);
	}
}

new DbCleanup();
