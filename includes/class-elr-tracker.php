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

	protected static function build_where( $filters ) {
		global $wpdb;
		$where  = array( '1=1' );
		$values = array();

		if ( ! empty( $filters['link_id'] ) ) {
			$where[]  = 'link_id = %d';
			$values[] = (int) $filters['link_id'];
		}
		if ( ! empty( $filters['country'] ) ) {
			$where[]  = 'country = %s';
			$values[] = strtoupper( substr( (string) $filters['country'], 0, 2 ) );
		}
		if ( ! empty( $filters['with_referrer'] ) ) {
			$where[] = "referrer <> ''";
		}
		if ( ! empty( $filters['rule'] ) ) {
			$rule = (string) $filters['rule'];
			if ( 'default' === $rule ) {
				$where[] = 'rule_id IS NULL';
			} elseif ( 'any_rule' === $rule ) {
				$where[] = 'rule_id IS NOT NULL';
			} elseif ( 0 === strpos( $rule, 'r:' ) ) {
				$rule_id = (int) substr( $rule, 2 );
				if ( $rule_id > 0 ) {
					$where[]  = 'rule_id = %d';
					$values[] = $rule_id;
				}
			}
		}
		if ( ! empty( $filters['q'] ) ) {
			$like     = '%' . $wpdb->esc_like( (string) $filters['q'] ) . '%';
			$where[]  = '(ip_address LIKE %s OR referrer LIKE %s OR browser LIKE %s OR os LIKE %s OR device_type LIKE %s OR country_name LIKE %s OR city LIKE %s OR user_agent LIKE %s OR destination LIKE %s)';
			$values[] = $like; $values[] = $like; $values[] = $like; $values[] = $like;
			$values[] = $like; $values[] = $like; $values[] = $like; $values[] = $like;
			$values[] = $like;
		}

		return array( implode( ' AND ', $where ), $values );
	}

	public static function query_clicks( $filters = array(), $limit = 100 ) {
		global $wpdb;
		list( $where, $values ) = self::build_where( $filters );
		$sql = 'SELECT * FROM ' . ELR_Database::clicks_table() . " WHERE $where ORDER BY clicked_at DESC LIMIT %d";
		$values[] = max( 1, (int) $limit );
		return $wpdb->get_results( $wpdb->prepare( $sql, $values ) );
	}

	public static function count_clicks( $filters = array() ) {
		global $wpdb;
		list( $where, $values ) = self::build_where( $filters );
		$sql = 'SELECT COUNT(*) FROM ' . ELR_Database::clicks_table() . " WHERE $where";
		return (int) ( empty( $values ) ? $wpdb->get_var( $sql ) : $wpdb->get_var( $wpdb->prepare( $sql, $values ) ) );
	}

	public static function daily_counts( $filters = array(), $days = 30 ) {
		global $wpdb;
		$days = max( 1, min( 365, (int) $days ) );

		$filters['_since'] = gmdate( 'Y-m-d 00:00:00', strtotime( '-' . ( $days - 1 ) . ' days' ) );
		list( $where, $values ) = self::build_where( $filters );
		$where   .= ' AND clicked_at >= %s';
		$values[] = $filters['_since'];

		$sql   = 'SELECT DATE(clicked_at) AS day, COUNT(*) AS hits FROM ' . ELR_Database::clicks_table()
			. " WHERE $where GROUP BY day ORDER BY day ASC";
		$rows  = $wpdb->get_results( $wpdb->prepare( $sql, $values ) );
		$by_day = array();
		if ( is_array( $rows ) ) {
			foreach ( $rows as $r ) {
				$by_day[ (string) $r->day ] = (int) $r->hits;
			}
		}

		$series = array();
		for ( $i = $days - 1; $i >= 0; $i-- ) {
			$day = gmdate( 'Y-m-d', strtotime( '-' . $i . ' days' ) );
			$series[] = array( 'day' => $day, 'hits' => isset( $by_day[ $day ] ) ? (int) $by_day[ $day ] : 0 );
		}
		return $series;
	}

	public static function distinct_countries( $filters = array() ) {
		global $wpdb;
		unset( $filters['country'] );
		list( $where, $values ) = self::build_where( $filters );
		$sql = 'SELECT DISTINCT country, country_name FROM ' . ELR_Database::clicks_table()
			. " WHERE $where AND country <> '' ORDER BY country_name ASC";
		return empty( $values ) ? $wpdb->get_results( $sql ) : $wpdb->get_results( $wpdb->prepare( $sql, $values ) );
	}

	public static function summary_filtered( $filters = array() ) {
		global $wpdb;
		list( $where, $values ) = self::build_where( $filters );
		$table = ELR_Database::clicks_table();

		$country_sql = "SELECT country_name, country, COUNT(*) AS hits FROM $table WHERE $where AND country <> '' GROUP BY country_name, country ORDER BY hits DESC LIMIT 10";
		$device_sql  = "SELECT device_type, COUNT(*) AS hits FROM $table WHERE $where GROUP BY device_type ORDER BY hits DESC";
		$browser_sql = "SELECT browser, COUNT(*) AS hits FROM $table WHERE $where GROUP BY browser ORDER BY hits DESC";

		if ( empty( $values ) ) {
			$by_country = $wpdb->get_results( $country_sql );
			$by_device  = $wpdb->get_results( $device_sql );
			$by_browser = $wpdb->get_results( $browser_sql );
		} else {
			$by_country = $wpdb->get_results( $wpdb->prepare( $country_sql, $values ) );
			$by_device  = $wpdb->get_results( $wpdb->prepare( $device_sql, $values ) );
			$by_browser = $wpdb->get_results( $wpdb->prepare( $browser_sql, $values ) );
		}

		return array(
			'countries' => (array) $by_country,
			'devices'   => (array) $by_device,
			'browsers'  => (array) $by_browser,
		);
	}

	public static function breakdown_by_rule( $filters = array() ) {
		global $wpdb;
		list( $where, $values ) = self::build_where( $filters );
		$clicks_table = ELR_Database::clicks_table();
		$rules_table  = ELR_Database::rules_table();

		$sql = "SELECT c.rule_id, r.rule_type, r.match_value, COUNT(*) AS hits
				FROM $clicks_table c
				LEFT JOIN $rules_table r ON c.rule_id = r.id
				WHERE $where
				GROUP BY c.rule_id, r.rule_type, r.match_value
				ORDER BY hits DESC";

		$rows = empty( $values )
			? $wpdb->get_results( $sql )
			: $wpdb->get_results( $wpdb->prepare( $sql, $values ) );

		return (array) $rows;
	}

	public static function rules_for_ids( $ids ) {
		global $wpdb;
		$ids = array_values( array_filter( array_map( 'intval', (array) $ids ) ) );
		if ( empty( $ids ) ) {
			return array();
		}
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT id, link_id, rule_type, match_value FROM ' . ELR_Database::rules_table()
				. " WHERE id IN ($placeholders)",
				$ids
			)
		);
		$by_id = array();
		foreach ( (array) $rows as $r ) {
			$by_id[ (int) $r->id ] = $r;
		}
		return $by_id;
	}

	public static function active_rule_counts_by_link() {
		global $wpdb;
		$rows = $wpdb->get_results(
			'SELECT link_id, COUNT(*) AS n FROM ' . ELR_Database::rules_table() . ' WHERE is_active = 1 GROUP BY link_id'
		);
		$out = array();
		foreach ( (array) $rows as $r ) {
			$out[ (int) $r->link_id ] = (int) $r->n;
		}
		return $out;
	}
}
