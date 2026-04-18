<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ELR_Database {

	public static function links_table() {
		global $wpdb;
		return $wpdb->prefix . ELR_TABLE_LINKS;
	}

	public static function rules_table() {
		global $wpdb;
		return $wpdb->prefix . ELR_TABLE_RULES;
	}

	public static function clicks_table() {
		global $wpdb;
		return $wpdb->prefix . ELR_TABLE_CLICKS;
	}

	public static function install( $network_wide = false ) {
		if ( is_multisite() && $network_wide ) {
			foreach ( self::get_all_site_ids() as $site_id ) {
				switch_to_blog( $site_id );
				self::install_site();
				restore_current_blog();
			}
			update_site_option( 'elr_db_version', ELR_VERSION );
			return;
		}

		self::install_site();
	}

	public static function install_site() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$links  = self::links_table();
		$rules  = self::rules_table();
		$clicks = self::clicks_table();

		$sql_links = "CREATE TABLE $links (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			slug VARCHAR(191) NOT NULL,
			title VARCHAR(255) NOT NULL DEFAULT '',
			target_url TEXT NOT NULL,
			redirect_type SMALLINT UNSIGNED NOT NULL DEFAULT 301,
			nofollow TINYINT(1) NOT NULL DEFAULT 0,
			track_clicks TINYINT(1) NOT NULL DEFAULT 1,
			is_active TINYINT(1) NOT NULL DEFAULT 1,
			hits BIGINT UNSIGNED NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY slug (slug)
		) $charset_collate;";

		$sql_rules = "CREATE TABLE $rules (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			link_id BIGINT UNSIGNED NOT NULL,
			rule_type VARCHAR(32) NOT NULL,
			match_value VARCHAR(191) NOT NULL,
			target_url TEXT NOT NULL,
			redirect_type SMALLINT UNSIGNED NOT NULL DEFAULT 301,
			priority INT NOT NULL DEFAULT 10,
			is_active TINYINT(1) NOT NULL DEFAULT 1,
			PRIMARY KEY  (id),
			KEY link_id (link_id),
			KEY rule_type (rule_type)
		) $charset_collate;";

		$sql_clicks = "CREATE TABLE $clicks (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			link_id BIGINT UNSIGNED NOT NULL,
			rule_id BIGINT UNSIGNED DEFAULT NULL,
			ip_address VARCHAR(45) NOT NULL DEFAULT '',
			country VARCHAR(2) NOT NULL DEFAULT '',
			country_name VARCHAR(128) NOT NULL DEFAULT '',
			city VARCHAR(128) NOT NULL DEFAULT '',
			device_type VARCHAR(16) NOT NULL DEFAULT '',
			browser VARCHAR(64) NOT NULL DEFAULT '',
			os VARCHAR(64) NOT NULL DEFAULT '',
			user_agent TEXT NOT NULL,
			referrer TEXT NOT NULL,
			destination TEXT NOT NULL,
			clicked_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY link_id (link_id),
			KEY clicked_at (clicked_at),
			KEY country (country),
			KEY device_type (device_type)
		) $charset_collate;";

		dbDelta( $sql_links );
		dbDelta( $sql_rules );
		dbDelta( $sql_clicks );

		if ( class_exists( 'ELR_Redirect' ) ) {
			ELR_Redirect::rebuild_active_slugs();
		}

		update_option( 'elr_db_version', ELR_VERSION );
		update_option( 'elr_flush_rewrite', 1 );
	}

	public static function uninstall_site() {
		global $wpdb;
		$tables = array( self::links_table(), self::rules_table(), self::clicks_table() );
		foreach ( $tables as $table ) {
			$wpdb->query( "DROP TABLE IF EXISTS $table" );
		}
		delete_option( 'elr_db_version' );
	}

	public static function on_new_site( $site ) {
		$site_id = is_object( $site ) && isset( $site->blog_id ) ? (int) $site->blog_id : (int) $site;
		if ( $site_id <= 0 ) {
			return;
		}
		switch_to_blog( $site_id );
		self::install_site();
		restore_current_blog();
	}

	public static function on_delete_site( $site ) {
		$site_id = is_object( $site ) && isset( $site->blog_id ) ? (int) $site->blog_id : (int) $site;
		if ( $site_id <= 0 ) {
			return;
		}
		switch_to_blog( $site_id );
		self::uninstall_site();
		restore_current_blog();
	}

	public static function maybe_upgrade_current_site() {
		$installed = get_option( 'elr_db_version' );
		if ( $installed === ELR_VERSION ) {
			return;
		}
		self::install_site();
	}

	protected static function get_all_site_ids() {
		if ( ! is_multisite() ) {
			return array( get_current_blog_id() );
		}
		$sites = get_sites( array( 'fields' => 'ids', 'number' => 0, 'deleted' => 0, 'archived' => 0, 'spam' => 0 ) );
		return array_map( 'intval', (array) $sites );
	}
}
