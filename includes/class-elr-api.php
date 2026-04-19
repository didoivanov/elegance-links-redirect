<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ELR_API {

	const NAMESPACE_V1 = 'elr/v1';
	const OPT_ENABLED  = 'elr_api_enabled';
	const OPT_TOKEN    = 'elr_api_token';
	const MAX_LIMIT    = 5000;
	const DEFAULT_LIMIT = 1000;

	public static function boot() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	public static function is_enabled() {
		return (bool) get_option( self::OPT_ENABLED, 0 );
	}

	public static function set_enabled( $enabled ) {
		update_option( self::OPT_ENABLED, $enabled ? 1 : 0 );
	}

	public static function get_token() {
		$token = get_option( self::OPT_TOKEN, '' );
		return is_string( $token ) ? $token : '';
	}

	public static function generate_token() {
		$token = wp_generate_password( 40, false, false );
		update_option( self::OPT_TOKEN, $token );
		return $token;
	}

	public static function revoke_token() {
		delete_option( self::OPT_TOKEN );
	}

	public static function api_url() {
		$token = self::get_token();
		if ( '' === $token ) {
			return '';
		}
		return rest_url( self::NAMESPACE_V1 . '/clicks/' . $token );
	}

	public static function register_routes() {
		register_rest_route(
			self::NAMESPACE_V1,
			'/clicks/(?P<token>[A-Za-z0-9]+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'handle_clicks' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'limit'     => array( 'default' => self::DEFAULT_LIMIT, 'sanitize_callback' => 'absint' ),
					'after_id'  => array( 'default' => 0, 'sanitize_callback' => 'absint' ),
					'before_id' => array( 'default' => 0, 'sanitize_callback' => 'absint' ),
				),
			)
		);
	}

	public static function handle_clicks( $request ) {
		if ( ! self::is_enabled() ) {
			return new WP_Error( 'elr_api_disabled', 'API is not enabled.', array( 'status' => 404 ) );
		}

		$supplied = (string) $request['token'];
		$stored   = self::get_token();
		if ( '' === $stored || ! hash_equals( $stored, $supplied ) ) {
			return new WP_Error( 'elr_api_forbidden', 'Invalid token.', array( 'status' => 403 ) );
		}

		$limit     = (int) $request->get_param( 'limit' );
		$after_id  = (int) $request->get_param( 'after_id' );
		$before_id = (int) $request->get_param( 'before_id' );

		if ( $limit <= 0 ) {
			$limit = self::DEFAULT_LIMIT;
		}
		if ( $limit > self::MAX_LIMIT ) {
			$limit = self::MAX_LIMIT;
		}

		global $wpdb;
		$clicks = ELR_Database::clicks_table();
		$links  = ELR_Database::links_table();
		$rules  = ELR_Database::rules_table();

		$where  = array( '1=1' );
		$values = array();
		$order  = 'DESC';

		if ( $after_id > 0 ) {
			$where[]  = 'c.id > %d';
			$values[] = $after_id;
			$order    = 'ASC';
		} elseif ( $before_id > 0 ) {
			$where[]  = 'c.id < %d';
			$values[] = $before_id;
		}

		$where_sql = implode( ' AND ', $where );

		$total_sql = "SELECT COUNT(*) FROM $clicks c WHERE $where_sql";
		$total     = empty( $values )
			? (int) $wpdb->get_var( $total_sql )
			: (int) $wpdb->get_var( $wpdb->prepare( $total_sql, $values ) );

		$rows_values   = $values;
		$rows_values[] = $limit;
		$rows_sql      = "SELECT c.*, l.slug AS link_slug, l.title AS link_title, r.rule_type AS r_type, r.match_value AS r_match
							FROM $clicks c
							LEFT JOIN $links l ON l.id = c.link_id
							LEFT JOIN $rules r ON r.id = c.rule_id
							WHERE $where_sql
							ORDER BY c.id $order
							LIMIT %d";
		$rows = $wpdb->get_results( $wpdb->prepare( $rows_sql, $rows_values ) );

		$data = array();
		foreach ( (array) $rows as $row ) {
			$rule_id = isset( $row->rule_id ) && $row->rule_id ? (int) $row->rule_id : null;
			$data[]  = array(
				'id'           => (int) $row->id,
				'link_id'      => (int) $row->link_id,
				'link_slug'    => (string) $row->link_slug,
				'link_title'   => (string) $row->link_title,
				'rule_id'      => $rule_id,
				'rule_type'    => (string) $row->r_type,
				'rule_match'   => (string) $row->r_match,
				'ip_address'   => (string) $row->ip_address,
				'country'      => (string) $row->country,
				'country_name' => (string) $row->country_name,
				'city'         => (string) $row->city,
				'device_type'  => (string) $row->device_type,
				'browser'      => (string) $row->browser,
				'os'           => (string) $row->os,
				'user_agent'   => (string) $row->user_agent,
				'referrer'     => (string) $row->referrer,
				'destination'  => (string) $row->destination,
				'clicked_at'   => (string) $row->clicked_at,
			);
		}

		return rest_ensure_response( array(
			'total_matching' => $total,
			'returned'       => count( $data ),
			'limit'          => $limit,
			'first_id'       => ! empty( $data ) ? (int) $data[0]['id'] : null,
			'last_id'        => ! empty( $data ) ? (int) $data[ count( $data ) - 1 ]['id'] : null,
			'clicks'         => $data,
		) );
	}
}
