<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ELR_Plugin {

	public static function boot() {
		add_action( 'init', array( 'ELR_Redirect', 'register_rewrites' ) );
		add_filter( 'query_vars', array( 'ELR_Redirect', 'query_vars' ) );
		add_action( 'template_redirect', array( 'ELR_Redirect', 'maybe_handle' ), 0 );

		add_action( 'init', array( __CLASS__, 'maybe_flush_rewrites' ), 20 );
		add_action( 'admin_init', array( 'ELR_Database', 'maybe_upgrade_current_site' ) );

		if ( is_multisite() ) {
			add_action( 'wp_initialize_site', array( 'ELR_Database', 'on_new_site' ), 20 );
			add_action( 'wp_uninitialize_site', array( 'ELR_Database', 'on_delete_site' ), 1 );
		}

		if ( is_admin() ) {
			ELR_Admin::boot();
		}
	}

	public static function on_activate( $network_wide = false ) {
		ELR_Database::install( (bool) $network_wide );
	}

	public static function maybe_flush_rewrites() {
		if ( get_option( 'elr_flush_rewrite' ) ) {
			flush_rewrite_rules( false );
			delete_option( 'elr_flush_rewrite' );
		}
	}

	public static function on_deactivate() {
		flush_rewrite_rules( false );
	}
}
