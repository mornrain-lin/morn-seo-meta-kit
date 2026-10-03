<?php
/**
 * robots.txt 增强与清理逻辑。
 *
 * @package MornRain\MornSeoMetaKit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 追加 robots.txt 规则并处理清理钩子。
 */
class Morn_SEO_Meta_Kit_Cleanup {

	/**
	 * 注册钩子。
	 *
	 * @return void
	 */
	public static function init() {
		add_filter( 'robots_txt', array( __CLASS__, 'filter_robots_txt' ), 10, 2 );
		add_action( 'save_post', array( __CLASS__, 'flush_cache' ) );
		add_action( 'deleted_post', array( __CLASS__, 'flush_cache' ) );
		add_action( 'switch_theme', array( __CLASS__, 'flush_cache' ) );
	}

	/**
	 * 追加 Disallow 与 Sitemap 行。
	 *
	 * @param string $output robots.txt 原始内容。
	 * @param bool   $public 是否允许索引。
	 * @return string
	 */
	public static function filter_robots_txt( $output, $public ) {
		$settings = morn_seo_meta_kit_get_settings();

		if ( empty( $settings['robots_enabled'] ) ) {
			return $output;
		}

		$lines = array();

		$rules = isset( $settings['robots_disallow'] ) ? (string) $settings['robots_disallow'] : '';
		$rules = preg_split( '/\r\n|\r|\n/', $rules );

		foreach ( (array) $rules as $rule ) {
			$rule = trim( (string) $rule );

			if ( '' === $rule || 0 !== strpos( $rule, '/' ) ) {
				continue;
			}

			$lines[] = 'Disallow: ' . $rule;
		}

		if ( ! empty( $settings['noindex'] ) || ( defined( 'MORN_SEO_META_KIT_FORCE_NOINDEX' ) && MORN_SEO_META_KIT_FORCE_NOINDEX ) ) {
			$lines[] = 'Disallow: /';
		}

		$map_url = home_url( '/sitemap-seo.xml' );

		/**
		 * 过滤追加到 robots.txt 的行。
		 *
		 * @param array  $lines    指令行数组。
		 * @param string $output   原始 robots.txt 内容。
		 * @param bool   $public   是否允许索引。
		 */
		$lines = apply_filters( 'morn_seo_meta_kit_robots_lines', $lines, $output, $public );

		if ( empty( $lines ) ) {
			return $output;
		}

		$extra  = "\n" . implode( "\n", $lines ) . "\n";
		$extra .= "\nSitemap: " . $map_url . "\n";

		return rtrim( (string) $output ) . "\n" . $extra;
	}

	/**
	 * 内容变更后清理站点地图缓存与版本号。
	 *
	 * @return void
	 */
	public static function flush_cache() {
		delete_transient( Morn_SEO_Meta_Kit_Sitemap::CACHE_KEY );

		// 递增版本号，使旧版本键自然失效。
		$version = (int) get_option( MORN_SEO_META_KIT_OPTION . '_sitemap_version', 0 );
		update_option( MORN_SEO_META_KIT_OPTION . '_sitemap_version', $version + 1, false );
	}
}
