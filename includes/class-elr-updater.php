<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ELR_Updater {

	const GITHUB_OWNER = 'didoivanov';
	const GITHUB_REPO  = 'elegance-links-redirect';
	const CACHE_KEY    = 'elr_github_release';
	const CACHE_TTL    = 21600; // 6 hours

	public static function boot() {
		add_filter( 'pre_set_site_transient_update_plugins', array( __CLASS__, 'inject_update' ) );
		add_filter( 'plugins_api', array( __CLASS__, 'plugin_info' ), 10, 3 );
		add_filter( 'upgrader_source_selection', array( __CLASS__, 'fix_source_dir' ), 10, 4 );
	}

	public static function plugin_basename() {
		return plugin_basename( ELR_PLUGIN_FILE );
	}

	public static function plugin_slug() {
		return dirname( self::plugin_basename() );
	}

	public static function get_latest_release( $force = false ) {
		if ( ! $force ) {
			$cached = get_site_transient( self::CACHE_KEY );
			if ( false !== $cached ) {
				return is_array( $cached ) ? $cached : null;
			}
		}

		$response = wp_remote_get(
			'https://api.github.com/repos/' . self::GITHUB_OWNER . '/' . self::GITHUB_REPO . '/releases/latest',
			array(
				'timeout' => 10,
				'headers' => array(
					'Accept'     => 'application/vnd.github+json',
					'User-Agent' => 'Elegance-Links-Redirect/' . ELR_VERSION,
				),
			)
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			set_site_transient( self::CACHE_KEY, array(), 15 * MINUTE_IN_SECONDS );
			return null;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $data ) || empty( $data['tag_name'] ) ) {
			set_site_transient( self::CACHE_KEY, array(), 15 * MINUTE_IN_SECONDS );
			return null;
		}

		set_site_transient( self::CACHE_KEY, $data, self::CACHE_TTL );
		return $data;
	}

	protected static function remote_version( $release ) {
		if ( ! is_array( $release ) || empty( $release['tag_name'] ) ) {
			return '';
		}
		return ltrim( (string) $release['tag_name'], 'vV' );
	}

	protected static function download_url( $release ) {
		if ( ! is_array( $release ) ) {
			return '';
		}
		if ( ! empty( $release['assets'] ) && is_array( $release['assets'] ) ) {
			foreach ( $release['assets'] as $asset ) {
				if ( ! empty( $asset['name'] ) && substr( (string) $asset['name'], -4 ) === '.zip' && ! empty( $asset['browser_download_url'] ) ) {
					return (string) $asset['browser_download_url'];
				}
			}
		}
		return ! empty( $release['zipball_url'] ) ? (string) $release['zipball_url'] : '';
	}

	public static function inject_update( $transient ) {
		if ( ! is_object( $transient ) || empty( $transient->checked ) ) {
			return $transient;
		}

		$release = self::get_latest_release();
		if ( empty( $release ) ) {
			return $transient;
		}

		$remote = self::remote_version( $release );
		if ( '' === $remote || version_compare( $remote, ELR_VERSION, '<=' ) ) {
			return $transient;
		}

		$file = self::plugin_basename();
		$info = (object) array(
			'id'            => $file,
			'slug'          => self::plugin_slug(),
			'plugin'        => $file,
			'new_version'   => $remote,
			'url'           => 'https://github.com/' . self::GITHUB_OWNER . '/' . self::GITHUB_REPO,
			'package'       => self::download_url( $release ),
			'icons'         => array(),
			'banners'       => array(),
			'banners_rtl'   => array(),
			'tested'        => '6.9',
			'requires_php'  => '7.2',
			'compatibility' => new stdClass(),
		);

		$transient->response[ $file ] = $info;
		return $transient;
	}

	public static function plugin_info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action ) {
			return $result;
		}
		if ( ! isset( $args->slug ) || self::plugin_slug() !== $args->slug ) {
			return $result;
		}

		$release = self::get_latest_release();
		if ( empty( $release ) ) {
			return $result;
		}

		$remote   = self::remote_version( $release );
		$body     = ! empty( $release['body'] ) ? wp_kses_post( (string) $release['body'] ) : '';
		$sections = array(
			'description' => __( 'Cloaked pretty link redirects with geo/device rules and click tracking.', 'elegance-links-redirect' ),
			'changelog'   => '' !== $body ? wpautop( $body ) : '',
		);

		return (object) array(
			'name'          => 'Elegance Links Redirect',
			'slug'          => self::plugin_slug(),
			'version'       => $remote,
			'author'        => '<a href="https://github.com/' . esc_attr( self::GITHUB_OWNER ) . '">didoivanov</a>',
			'homepage'      => 'https://github.com/' . self::GITHUB_OWNER . '/' . self::GITHUB_REPO,
			'requires'      => '5.5',
			'tested'        => '6.9',
			'requires_php'  => '7.2',
			'sections'      => $sections,
			'download_link' => self::download_url( $release ),
		);
	}

	public static function fix_source_dir( $source, $remote_source, $upgrader, $hook_extra = array() ) {
		global $wp_filesystem;

		if ( empty( $hook_extra['plugin'] ) ) {
			return $source;
		}
		if ( dirname( (string) $hook_extra['plugin'] ) !== self::plugin_slug() ) {
			return $source;
		}
		if ( ! is_object( $wp_filesystem ) ) {
			return $source;
		}

		$desired = trailingslashit( $remote_source ) . self::plugin_slug() . '/';
		if ( trailingslashit( $source ) === $desired ) {
			return $source;
		}
		if ( $wp_filesystem->exists( $desired ) ) {
			$wp_filesystem->delete( $desired, true );
		}
		if ( ! $wp_filesystem->move( $source, $desired, true ) ) {
			return $source;
		}
		return $desired;
	}
}
