<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ELR_Tracker {

	public static function record( $link, $destination, $context, $rule_id = null ) {
		global $wpdb;

		$data = array(
			'link_id'      => (int) $link->id,
			'rule_id'      => $rule_id ? (int) $rule_id : null,
			'ip_address'   => isset( $context['ip'] ) ? substr( (string) $context['ip'], 0, 45 ) : '',
			'country'      => isset( $context['geo']['country'] ) ? substr( (string) $context['geo']['country'], 0, 2 ) : '',
			'country_name' => isset( $context['geo']['country_name'] ) ? substr( (string) $context['geo']['country_name'], 0, 128 ) : '',
			'city'         => isset( $context['geo']['city'] ) ? substr( (string) $context['geo']['city'], 0, 128 ) : '',
			'device_type'  => isset( $context['device']['device'] ) ? substr( (string) $context['device']['device'], 0, 16 ) : '',
			'browser'      => isset( $context['device']['browser'] ) ? substr( (string) $context['device']['browser'], 0, 64 ) : '',
			'os'           => isset( $context['device']['os'] ) ? substr( (string) $context['device']['os'], 0, 64 ) : '',
			'user_agent'   => isset( $context['user_agent'] ) ? (string) $context['user_agent'] : '',
			'referrer'     => isset( $context['referrer'] ) ? (string) $context['referrer'] : '',
			'destination'  => (string) $destination,
			'clicked_at'   => current_time( 'mysql' ),
		);

		$wpdb->insert( ELR_Database::clicks_table(), $data );

		$wpdb->query(
			$wpdb->prepare(
				'UPDATE ' . ELR_Database::links_table() . ' SET hits = hits + 1 WHERE id = %d',
				$link->id
			)
		);
	}

	public static function count_for_link( $link_id ) {
		global $wpdb;
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM ' . ELR_Database::clicks_table() . ' WHERE link_id = %d',
				$link_id
			)
		);
	}

	public static function recent_for_link( $link_id, $limit = 50 ) {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM ' . ELR_Database::clicks_table() . ' WHERE link_id = %d ORDER BY clicked_at DESC LIMIT %d',
				$link_id,
				$limit
			)
		);
	}

	public static function summary_for_link( $link_id ) {
		global $wpdb;
		$table = ELR_Database::clicks_table();

		$by_country = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT country_name, country, COUNT(*) AS hits FROM $table WHERE link_id = %d AND country_name <> '' GROUP BY country_name, country ORDER BY hits DESC LIMIT 10",
				$link_id
			)
		);
		$by_device  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT device_type, COUNT(*) AS hits FROM $table WHERE link_id = %d GROUP BY device_type ORDER BY hits DESC",
				$link_id
			)
		);
		$by_browser = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT browser, COUNT(*) AS hits FROM $table WHERE link_id = %d GROUP BY browser ORDER BY hits DESC",
				$link_id
			)
		);

		return array(
			'countries' => $by_country,
			'devices'   => $by_device,
			'browsers'  => $by_browser,
		);
	}
}
