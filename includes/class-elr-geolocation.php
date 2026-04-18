<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ELR_Geolocation {

	const CACHE_GROUP = 'elr_geo';
	const CACHE_TTL   = DAY_IN_SECONDS;

	public static function get_client_ip() {
		$headers = array( 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR' );
		foreach ( $headers as $header ) {
			if ( empty( $_SERVER[ $header ] ) ) {
				continue;
			}
			$value = (string) $_SERVER[ $header ];
			if ( 'HTTP_X_FORWARDED_FOR' === $header ) {
				$parts = explode( ',', $value );
				$value = trim( $parts[0] );
			}
			if ( filter_var( $value, FILTER_VALIDATE_IP ) ) {
				return $value;
			}
		}
		return '';
	}

	public static function lookup( $ip ) {
		$empty = array(
			'country'      => '',
			'country_name' => '',
			'city'         => '',
		);

		if ( empty( $ip ) || ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return $empty;
		}

		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) === false ) {
			return $empty;
		}

		$cache_key = 'ip_' . md5( $ip );
		$cached    = get_transient( 'elr_' . $cache_key );
		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		$provider = apply_filters( 'elr_geo_provider', 'ip-api' );
		$result   = $empty;

		if ( 'ip-api' === $provider ) {
			$response = wp_remote_get(
				'http://ip-api.com/json/' . rawurlencode( $ip ) . '?fields=status,country,countryCode,city',
				array( 'timeout' => 3 )
			);
			if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
				$body = json_decode( wp_remote_retrieve_body( $response ), true );
				if ( is_array( $body ) && isset( $body['status'] ) && 'success' === $body['status'] ) {
					$result = array(
						'country'      => isset( $body['countryCode'] ) ? strtoupper( substr( $body['countryCode'], 0, 2 ) ) : '',
						'country_name' => isset( $body['country'] ) ? (string) $body['country'] : '',
						'city'         => isset( $body['city'] ) ? (string) $body['city'] : '',
					);
				}
			}
		}

		$result = apply_filters( 'elr_geo_lookup_result', $result, $ip );
		set_transient( 'elr_' . $cache_key, $result, self::CACHE_TTL );

		return $result;
	}
}
