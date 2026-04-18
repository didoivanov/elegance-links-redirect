<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ELR_Admin {

	const CAPABILITY = 'manage_options';
	const MENU_SLUG  = 'elr-links';

	public static function boot() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_post_elr_save_link', array( __CLASS__, 'handle_save_link' ) );
		add_action( 'admin_post_elr_delete_link', array( __CLASS__, 'handle_delete_link' ) );
		add_action( 'admin_post_elr_save_rule', array( __CLASS__, 'handle_save_rule' ) );
		add_action( 'admin_post_elr_delete_rule', array( __CLASS__, 'handle_delete_rule' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	public static function register_menu() {
		add_menu_page(
			__( 'Elegance Links', 'elegance-links-redirect' ),
			__( 'Elegance Links', 'elegance-links-redirect' ),
			self::CAPABILITY,
			self::MENU_SLUG,
			array( __CLASS__, 'render_list_page' ),
			'dashicons-admin-links',
			58
		);
		add_submenu_page(
			self::MENU_SLUG,
			__( 'All Links', 'elegance-links-redirect' ),
			__( 'All Links', 'elegance-links-redirect' ),
			self::CAPABILITY,
			self::MENU_SLUG,
			array( __CLASS__, 'render_list_page' )
		);
		add_submenu_page(
			self::MENU_SLUG,
			__( 'Add New Link', 'elegance-links-redirect' ),
			__( 'Add New', 'elegance-links-redirect' ),
			self::CAPABILITY,
			'elr-link-edit',
			array( __CLASS__, 'render_edit_page' )
		);
		add_submenu_page(
			null,
			__( 'Link Stats', 'elegance-links-redirect' ),
			__( 'Link Stats', 'elegance-links-redirect' ),
			self::CAPABILITY,
			'elr-link-stats',
			array( __CLASS__, 'render_stats_page' )
		);
	}

	public static function enqueue_assets( $hook ) {
		if ( strpos( (string) $hook, 'elr' ) === false && strpos( (string) $hook, self::MENU_SLUG ) === false ) {
			return;
		}
		wp_enqueue_style(
			'elr-admin',
			ELR_PLUGIN_URL . 'admin/assets/css/admin.css',
			array(),
			ELR_VERSION
		);
	}

	public static function render_list_page() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}
		global $wpdb;
		$links = $wpdb->get_results( 'SELECT * FROM ' . ELR_Database::links_table() . ' ORDER BY created_at DESC' );
		$home  = trailingslashit( home_url() );
		include ELR_PLUGIN_DIR . 'admin/views/links-list.php';
	}

	public static function render_edit_page() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}
		global $wpdb;
		$link_id = isset( $_GET['link_id'] ) ? (int) $_GET['link_id'] : 0;
		$link    = null;
		$rules   = array();
		if ( $link_id > 0 ) {
			$link = $wpdb->get_row(
				$wpdb->prepare( 'SELECT * FROM ' . ELR_Database::links_table() . ' WHERE id = %d', $link_id )
			);
			if ( $link ) {
				$rules = $wpdb->get_results(
					$wpdb->prepare(
						'SELECT * FROM ' . ELR_Database::rules_table() . ' WHERE link_id = %d ORDER BY priority ASC, id ASC',
						$link_id
					)
				);
			}
		}
		$home = trailingslashit( home_url() );
		include ELR_PLUGIN_DIR . 'admin/views/link-form.php';
	}

	public static function render_stats_page() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}
		global $wpdb;
		$link_id = isset( $_GET['link_id'] ) ? (int) $_GET['link_id'] : 0;
		$link    = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM ' . ELR_Database::links_table() . ' WHERE id = %d', $link_id )
		);
		if ( ! $link ) {
			echo '<div class="wrap"><h1>' . esc_html__( 'Link not found', 'elegance-links-redirect' ) . '</h1></div>';
			return;
		}
		$clicks  = ELR_Tracker::recent_for_link( $link_id, 100 );
		$summary = ELR_Tracker::summary_for_link( $link_id );
		$home    = trailingslashit( home_url() );
		include ELR_PLUGIN_DIR . 'admin/views/stats.php';
	}

	public static function handle_save_link() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'elegance-links-redirect' ) );
		}
		check_admin_referer( 'elr_save_link' );

		global $wpdb;
		$id         = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		$slug_raw   = isset( $_POST['slug'] ) ? (string) wp_unslash( $_POST['slug'] ) : '';
		$slug       = self::sanitize_slug( $slug_raw );
		$title      = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		$target     = isset( $_POST['target_url'] ) ? esc_url_raw( wp_unslash( $_POST['target_url'] ) ) : '';
		$type       = isset( $_POST['redirect_type'] ) ? (int) $_POST['redirect_type'] : 301;
		$nofollow   = isset( $_POST['nofollow'] ) ? 1 : 0;
		$track      = isset( $_POST['track_clicks'] ) ? 1 : 0;
		$is_active  = isset( $_POST['is_active'] ) ? 1 : 0;

		if ( '' === $slug ) {
			self::redirect_with_notice( 'error', __( 'Slug is required and may only contain letters, numbers, dashes, and underscores.', 'elegance-links-redirect' ) );
		}
		if ( '' === $target || ! filter_var( $target, FILTER_VALIDATE_URL ) ) {
			self::redirect_with_notice( 'error', __( 'A valid target URL is required.', 'elegance-links-redirect' ) );
		}
		if ( ! in_array( $type, array( 301, 302, 303, 307, 308 ), true ) ) {
			$type = 301;
		}

		$data = array(
			'slug'          => $slug,
			'title'         => $title,
			'target_url'    => $target,
			'redirect_type' => $type,
			'nofollow'      => $nofollow,
			'track_clicks'  => $track,
			'is_active'     => $is_active,
			'updated_at'    => current_time( 'mysql' ),
		);

		$table = ELR_Database::links_table();

		$existing = $wpdb->get_var(
			$wpdb->prepare( "SELECT id FROM $table WHERE slug = %s AND id <> %d", $slug, $id )
		);
		if ( $existing ) {
			self::redirect_with_notice( 'error', __( 'That slug is already in use.', 'elegance-links-redirect' ) );
		}

		if ( $id > 0 ) {
			$wpdb->update( $table, $data, array( 'id' => $id ) );
		} else {
			$data['created_at'] = current_time( 'mysql' );
			$wpdb->insert( $table, $data );
			$id = (int) $wpdb->insert_id;
		}

		update_option( 'elr_flush_rewrite', 1 );
		self::redirect_with_notice( 'success', __( 'Link saved.', 'elegance-links-redirect' ), array( 'page' => 'elr-link-edit', 'link_id' => $id ) );
	}

	public static function handle_delete_link() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'elegance-links-redirect' ) );
		}
		check_admin_referer( 'elr_delete_link' );

		global $wpdb;
		$id = isset( $_REQUEST['link_id'] ) ? (int) $_REQUEST['link_id'] : 0;
		if ( $id > 0 ) {
			$wpdb->delete( ELR_Database::links_table(), array( 'id' => $id ) );
			$wpdb->delete( ELR_Database::rules_table(), array( 'link_id' => $id ) );
			$wpdb->delete( ELR_Database::clicks_table(), array( 'link_id' => $id ) );
		}
		update_option( 'elr_flush_rewrite', 1 );
		self::redirect_with_notice( 'success', __( 'Link deleted.', 'elegance-links-redirect' ) );
	}

	public static function handle_save_rule() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'elegance-links-redirect' ) );
		}
		check_admin_referer( 'elr_save_rule' );

		global $wpdb;
		$link_id    = isset( $_POST['link_id'] ) ? (int) $_POST['link_id'] : 0;
		$rule_id    = isset( $_POST['rule_id'] ) ? (int) $_POST['rule_id'] : 0;
		$rule_type  = isset( $_POST['rule_type'] ) ? sanitize_key( wp_unslash( $_POST['rule_type'] ) ) : '';
		$match      = isset( $_POST['match_value'] ) ? sanitize_text_field( wp_unslash( $_POST['match_value'] ) ) : '';
		$target     = isset( $_POST['target_url'] ) ? esc_url_raw( wp_unslash( $_POST['target_url'] ) ) : '';
		$type       = isset( $_POST['redirect_type'] ) ? (int) $_POST['redirect_type'] : 301;
		$priority   = isset( $_POST['priority'] ) ? (int) $_POST['priority'] : 10;
		$is_active  = isset( $_POST['is_active'] ) ? 1 : 0;

		if ( ! in_array( $rule_type, array( 'country', 'device' ), true ) ) {
			self::redirect_with_notice( 'error', __( 'Unsupported rule type.', 'elegance-links-redirect' ), array( 'page' => 'elr-link-edit', 'link_id' => $link_id ) );
		}
		if ( $link_id <= 0 || '' === $match || '' === $target || ! filter_var( $target, FILTER_VALIDATE_URL ) ) {
			self::redirect_with_notice( 'error', __( 'Rule requires a match value and a valid target URL.', 'elegance-links-redirect' ), array( 'page' => 'elr-link-edit', 'link_id' => $link_id ) );
		}
		if ( ! in_array( $type, array( 301, 302, 303, 307, 308 ), true ) ) {
			$type = 301;
		}
		if ( 'country' === $rule_type ) {
			$parts = array_filter( array_map( 'trim', explode( ',', strtoupper( $match ) ) ) );
			$match = implode( ',', $parts );
		} elseif ( 'device' === $rule_type ) {
			$parts = array_filter( array_map( 'trim', explode( ',', strtolower( $match ) ) ) );
			$allowed = array( 'desktop', 'mobile', 'tablet', 'bot' );
			$parts = array_values( array_intersect( $parts, $allowed ) );
			if ( empty( $parts ) ) {
				self::redirect_with_notice( 'error', __( 'Device rule must target desktop, mobile, tablet, or bot.', 'elegance-links-redirect' ), array( 'page' => 'elr-link-edit', 'link_id' => $link_id ) );
			}
			$match = implode( ',', $parts );
		}

		$data = array(
			'link_id'       => $link_id,
			'rule_type'     => $rule_type,
			'match_value'   => $match,
			'target_url'    => $target,
			'redirect_type' => $type,
			'priority'      => $priority,
			'is_active'     => $is_active,
		);

		if ( $rule_id > 0 ) {
			$wpdb->update( ELR_Database::rules_table(), $data, array( 'id' => $rule_id ) );
		} else {
			$wpdb->insert( ELR_Database::rules_table(), $data );
		}

		self::redirect_with_notice( 'success', __( 'Rule saved.', 'elegance-links-redirect' ), array( 'page' => 'elr-link-edit', 'link_id' => $link_id ) );
	}

	public static function handle_delete_rule() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'elegance-links-redirect' ) );
		}
		check_admin_referer( 'elr_delete_rule' );

		global $wpdb;
		$rule_id = isset( $_REQUEST['rule_id'] ) ? (int) $_REQUEST['rule_id'] : 0;
		$link_id = isset( $_REQUEST['link_id'] ) ? (int) $_REQUEST['link_id'] : 0;
		if ( $rule_id > 0 ) {
			$wpdb->delete( ELR_Database::rules_table(), array( 'id' => $rule_id ) );
		}
		self::redirect_with_notice( 'success', __( 'Rule deleted.', 'elegance-links-redirect' ), array( 'page' => 'elr-link-edit', 'link_id' => $link_id ) );
	}

	protected static function sanitize_slug( $slug ) {
		$slug = strtolower( trim( (string) $slug ) );
		$slug = trim( $slug, '/' );
		$slug = preg_replace( '#[^a-z0-9\-_]+#', '', $slug );
		return (string) $slug;
	}

	protected static function redirect_with_notice( $type, $message, $extra = array() ) {
		$args = array_merge(
			array(
				'page'        => self::MENU_SLUG,
				'elr_notice'  => $type,
				'elr_message' => rawurlencode( $message ),
			),
			$extra
		);
		wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
		exit;
	}

	public static function render_notice() {
		if ( empty( $_GET['elr_notice'] ) || empty( $_GET['elr_message'] ) ) {
			return;
		}
		$type    = 'error' === $_GET['elr_notice'] ? 'notice-error' : 'notice-success';
		$message = rawurldecode( (string) $_GET['elr_message'] );
		echo '<div class="notice ' . esc_attr( $type ) . ' is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
	}
}
