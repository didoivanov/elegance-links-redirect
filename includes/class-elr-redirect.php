<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ELR_Redirect {

	const QUERY_VAR         = 'elr_slug';
	const ACTIVE_SLUGS_OPT  = 'elr_active_slugs';

	public static function register_rewrites() {
		add_rewrite_tag( '%' . self::QUERY_VAR . '%', '([^&/]+)' );

		$slugs = self::get_active_slugs();
		foreach ( $slugs as $slug ) {
			$slug = (string) $slug;
			if ( '' === $slug ) {
				continue;
			}
			$pattern = '^' . preg_quote( $slug, '#' ) . '/?$';
			add_rewrite_rule(
				$pattern,
				'index.php?' . self::QUERY_VAR . '=' . rawurlencode( $slug ),
				'top'
			);
		}
	}

	public static function get_active_slugs() {
		$slugs = get_option( self::ACTIVE_SLUGS_OPT, array() );
		if ( ! is_array( $slugs ) ) {
			return array();
		}
		return array_values( array_filter( array_map( 'strval', $slugs ) ) );
	}

	public static function rebuild_active_slugs() {
		global $wpdb;
		$rows = $wpdb->get_col(
			'SELECT slug FROM ' . ELR_Database::links_table() . ' WHERE is_active = 1'
		);
		$slugs = array();
		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$row = (string) $row;
				if ( '' !== $row ) {
					$slugs[] = $row;
				}
			}
		}
		update_option( self::ACTIVE_SLUGS_OPT, $slugs, false );
		return $slugs;
	}

	public static function query_vars( $vars ) {
		$vars[] = self::QUERY_VAR;
		return $vars;
	}

	public static function maybe_handle() {
		$slug = get_query_var( self::QUERY_VAR );
		if ( empty( $slug ) ) {
			return;
		}

		$link = self::find_link_by_slug( $slug );
		if ( ! $link ) {
			return;
		}

		$context = self::build_context();
		$match   = self::resolve_destination( $link, $context );

		if ( ! empty( $link->track_clicks ) ) {
			ELR_Tracker::record( $link, $match['destination'], $context, $match['rule_id'] );
		}

		$status = self::normalize_status( $match['redirect_type'] );
		nocache_headers();
		wp_redirect( $match['destination'], $status, 'Elegance Links Redirect' );
		exit;
	}

	protected static function find_link_by_slug( $slug ) {
		global $wpdb;
		$slug = trim( (string) $slug, '/' );
		if ( '' === $slug ) {
			return null;
		}
		return $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . ELR_Database::links_table() . ' WHERE slug = %s AND is_active = 1 LIMIT 1',
				$slug
			)
		);
	}

	protected static function build_context() {
		$ip         = ELR_Geolocation::get_client_ip();
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';
		$referrer   = isset( $_SERVER['HTTP_REFERER'] ) ? (string) $_SERVER['HTTP_REFERER'] : '';

		return array(
			'ip'         => $ip,
			'geo'        => ELR_Geolocation::lookup( $ip ),
			'device'     => ELR_Device_Detector::detect( $user_agent ),
			'user_agent' => $user_agent,
			'referrer'   => $referrer,
		);
	}

	protected static function resolve_destination( $link, $context ) {
		$rules = self::get_rules_for_link( $link->id );
		foreach ( $rules as $rule ) {
			if ( self::rule_matches( $rule, $context ) ) {
				return array(
					'destination'   => esc_url_raw( $rule->target_url ),
					'redirect_type' => (int) $rule->redirect_type,
					'rule_id'       => (int) $rule->id,
				);
			}
		}

		return array(
			'destination'   => esc_url_raw( $link->target_url ),
			'redirect_type' => (int) $link->redirect_type,
			'rule_id'       => null,
		);
	}

	protected static function get_rules_for_link( $link_id ) {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM ' . ELR_Database::rules_table() . ' WHERE link_id = %d AND is_active = 1 ORDER BY priority ASC, id ASC',
				$link_id
			)
		);
	}

	protected static function rule_matches( $rule, $context ) {
		$type  = (string) $rule->rule_type;
		$value = strtolower( trim( (string) $rule->match_value ) );

		if ( 'country' === $type ) {
			$country = isset( $context['geo']['country'] ) ? strtolower( $context['geo']['country'] ) : '';
			if ( '' === $country ) {
				return false;
			}
			$allowed = array_filter( array_map( 'trim', explode( ',', $value ) ) );
			return in_array( $country, $allowed, true );
		}

		if ( 'device' === $type ) {
			$device  = isset( $context['device']['device'] ) ? strtolower( $context['device']['device'] ) : '';
			$allowed = array_filter( array_map( 'trim', explode( ',', $value ) ) );
			return in_array( $device, $allowed, true );
		}

		return apply_filters( 'elr_rule_matches', false, $rule, $context );
	}

	protected static function normalize_status( $code ) {
		$code    = (int) $code;
		$allowed = array( 301, 302, 303, 307, 308 );
		return in_array( $code, $allowed, true ) ? $code : 307;
	}
}
