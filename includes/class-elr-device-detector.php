<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ELR_Device_Detector {

	public static function detect( $user_agent = null ) {
		if ( null === $user_agent ) {
			$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';
		}

		$ua = strtolower( $user_agent );

		$device = 'desktop';
		if ( preg_match( '/ipad|tablet|playbook|silk|kindle/', $ua ) ) {
			$device = 'tablet';
		} elseif ( preg_match( '/mobile|iphone|ipod|android.*mobile|blackberry|opera mini|iemobile|windows phone/', $ua ) ) {
			$device = 'mobile';
		} elseif ( preg_match( '/bot|spider|crawl|slurp|mediapartners|facebookexternalhit/', $ua ) ) {
			$device = 'bot';
		}

		$browser = 'Unknown';
		if ( strpos( $ua, 'edg/' ) !== false ) {
			$browser = 'Edge';
		} elseif ( strpos( $ua, 'opr/' ) !== false || strpos( $ua, 'opera' ) !== false ) {
			$browser = 'Opera';
		} elseif ( strpos( $ua, 'chrome/' ) !== false && strpos( $ua, 'chromium' ) === false ) {
			$browser = 'Chrome';
		} elseif ( strpos( $ua, 'firefox/' ) !== false ) {
			$browser = 'Firefox';
		} elseif ( strpos( $ua, 'safari/' ) !== false ) {
			$browser = 'Safari';
		} elseif ( strpos( $ua, 'msie' ) !== false || strpos( $ua, 'trident' ) !== false ) {
			$browser = 'Internet Explorer';
		}

		$os = 'Unknown';
		if ( strpos( $ua, 'windows nt' ) !== false ) {
			$os = 'Windows';
		} elseif ( strpos( $ua, 'mac os x' ) !== false ) {
			$os = 'macOS';
		} elseif ( strpos( $ua, 'android' ) !== false ) {
			$os = 'Android';
		} elseif ( strpos( $ua, 'iphone' ) !== false || strpos( $ua, 'ipad' ) !== false || strpos( $ua, 'ipod' ) !== false ) {
			$os = 'iOS';
		} elseif ( strpos( $ua, 'linux' ) !== false ) {
			$os = 'Linux';
		}

		return array(
			'device'  => $device,
			'browser' => $browser,
			'os'      => $os,
		);
	}
}
