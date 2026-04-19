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
		add_filter( 'site_transient_update_plugins', array( __CLASS__, 'inject_update' ) );
		add_filter( 'plugins_api', array( __CLASS__, 'plugin_info' ), 10, 3 );
		add_filter( 'upgrader_source_selection', array( __CLASS__, 'fix_source_dir' ), 10, 4 );
		add_filter( 'plugin_row_meta', array( __CLASS__, 'plugin_row_meta' ), 10, 2 );
		add_action( 'admin_post_elr_check_updates', array( __CLASS__, 'handle_check_updates' ) );
	}

	public static function plugin_basename() {
		return plugin_basename( ELR_PLUGIN_FILE );
	}

	public static function plugin_slug() {
		return dirname( self::plugin_basename() );
	}

	protected static function github_get( $path ) {
		return wp_remote_get(
			'https://api.github.com/repos/' . self::GITHUB_OWNER . '/' . self::GITHUB_REPO . $path,
			array(
				'timeout' => 10,
				'headers' => array(
					'Accept'     => 'application/vnd.github+json',
					'User-Agent' => 'Elegance-Links-Redirect/' . ELR_VERSION,
				),
			)
		);
	}

	public static function get_latest_release( $force = false ) {
		if ( ! $force ) {
			$cached = get_site_transient( self::CACHE_KEY );
			if ( false !== $cached ) {
				return is_array( $cached ) && ! empty( $cached ) ? $cached : null;
			}
		}

		$best = self::pick_latest_tag();
		if ( ! $best ) {
			set_site_transient( self::CACHE_KEY, array(), 15 * MINUTE_IN_SECONDS );
			return null;
		}

		$release_body = '';
		$assets       = array();
		$release_resp = self::github_get( '/releases/tags/' . rawurlencode( $best['tag'] ) );
		if ( ! is_wp_error( $release_resp ) && 200 === (int) wp_remote_retrieve_response_code( $release_resp ) ) {
			$release = json_decode( wp_remote_retrieve_body( $release_resp ), true );
			if ( is_array( $release ) ) {
				$release_body = ! empty( $release['body'] ) ? (string) $release['body'] : '';
				$assets       = ! empty( $release['assets'] ) && is_array( $release['assets'] ) ? $release['assets'] : array();
			}
		}

		$data = array(
			'tag_name'     => $best['tag'],
			'version'      => $best['version'],
			'zipball_url'  => 'https://api.github.com/repos/' . self::GITHUB_OWNER . '/' . self::GITHUB_REPO . '/zipball/refs/tags/' . rawurlencode( $best['tag'] ),
			'body'         => $release_body,
			'assets'       => $assets,
		);

		set_site_transient( self::CACHE_KEY, $data, self::CACHE_TTL );
		return $data;
	}

	protected static function pick_latest_tag() {
		$resp = self::github_get( '/tags?per_page=100' );
		if ( is_wp_error( $resp ) || 200 !== (int) wp_remote_retrieve_response_code( $resp ) ) {
			return null;
		}
		$tags = json_decode( wp_remote_retrieve_body( $resp ), true );
		if ( ! is_array( $tags ) ) {
			return null;
		}

		$best = null;
		foreach ( $tags as $tag ) {
			if ( empty( $tag['name'] ) ) {
				continue;
			}
			$name    = (string) $tag['name'];
			$version = ltrim( $name, 'vV' );
			if ( ! preg_match( '/^\d+\.\d+(\.\d+)?/', $version ) ) {
				continue;
			}
			if ( ! $best || version_compare( $version, $best['version'], '>' ) ) {
				$best = array( 'tag' => $name, 'version' => $version );
			}
		}
		return $best;
	}

	protected static function remote_version( $release ) {
		if ( ! is_array( $release ) ) {
			return '';
		}
		if ( ! empty( $release['version'] ) ) {
			return (string) $release['version'];
		}
		if ( ! empty( $release['tag_name'] ) ) {
			return ltrim( (string) $release['tag_name'], 'vV' );
		}
		return '';
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
		if ( ! is_object( $transient ) ) {
			$transient = new stdClass();
		}
		if ( ! isset( $transient->response ) || ! is_array( $transient->response ) ) {
			$transient->response = array();
		}
		if ( ! isset( $transient->no_update ) || ! is_array( $transient->no_update ) ) {
			$transient->no_update = array();
		}

		$release = self::get_latest_release();
		if ( empty( $release ) ) {
			return $transient;
		}

		$remote = self::remote_version( $release );
		if ( '' === $remote ) {
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

		if ( version_compare( $remote, ELR_VERSION, '>' ) ) {
			$transient->response[ $file ] = $info;
			unset( $transient->no_update[ $file ] );
		} else {
			$transient->no_update[ $file ] = $info;
			unset( $transient->response[ $file ] );
		}

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

	public static function plugin_row_meta( $links, $file ) {
		if ( $file !== self::plugin_basename() ) {
			return $links;
		}
		$url     = wp_nonce_url(
			add_query_arg( 'action', 'elr_check_updates', admin_url( 'admin-post.php' ) ),
			'elr_check_updates'
		);
		$links[] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Check for updates', 'elegance-links-redirect' ) . '</a>';
		return $links;
	}

	public static function handle_check_updates() {
		if ( ! current_user_can( 'update_plugins' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'elegance-links-redirect' ) );
		}
		check_admin_referer( 'elr_check_updates' );

		delete_site_transient( self::CACHE_KEY );
		delete_site_transient( 'update_plugins' );
		if ( function_exists( 'wp_update_plugins' ) ) {
			wp_update_plugins();
		}

		$referer  = wp_get_referer();
		$redirect = $referer ? $referer : admin_url( 'plugins.php' );
		wp_safe_redirect( $redirect );
		exit;
	}
}
