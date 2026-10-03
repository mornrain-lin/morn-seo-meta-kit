<?php
/**
 * 精简 XML 站点地图（/sitemap-seo.xml）。
 *
 * @package MornRain\MornSeoMetaKit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 输出独立的精简站点地图。
 */
class Morn_SEO_Meta_Kit_Sitemap {

	/**
	 * 站点地图查询变量名。
	 */
	const QUERY_VAR = 'morn_seo_sitemap';

	/**
	 * 站点地图 XML 缓存键。
	 */
	const CACHE_KEY = 'morn_seo_meta_kit_sitemap_xml';

	/**
	 * 注册钩子。
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_rewrites' ) );
		add_filter( 'query_vars', array( __CLASS__, 'add_query_var' ) );
		add_action( 'template_redirect', array( __CLASS__, 'maybe_render' ), 0 );
	}

	/**
	 * 注册重写规则。
	 *
	 * @return void
	 */
	public static function register_rewrites() {
		add_rewrite_rule( '^sitemap-seo\.xml$', 'index.php?' . self::QUERY_VAR . '=1', 'top' );
	}

	/**
	 * 追加查询变量。
	 *
	 * @param array $vars 已有查询变量。
	 * @return array
	 */
	public static function add_query_var( $vars ) {
		$vars[] = self::QUERY_VAR;

		return $vars;
	}

	/**
	 * 判断当前请求是否为站点地图，若为是则输出并终止。
	 *
	 * @return void
	 */
	public static function maybe_render() {
		$settings = morn_seo_meta_kit_get_settings();

		if ( empty( $settings['sitemap_enabled'] ) ) {
			return;
		}

		if ( ! get_query_var( self::QUERY_VAR ) ) {
			return;
		}

		if ( is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			return;
		}

		// 站点地图必须是公开可见的。
		if ( '' !== get_option( 'blog_public' ) && '1' !== (string) get_option( 'blog_public' ) ) {
			status_header( 403 );
			nocache_headers();
			header( 'Content-Type: text/plain; charset=utf-8' );
			echo esc_html__( '本站已设置为不公开索引，站点地图不可用。', 'morn-seo-meta-kit' );
			exit;
		}
		nocache_headers();
		header( 'Content-Type: application/xml; charset=' . get_bloginfo( 'charset' ) );
		header( 'X-Robots-Tag: noindex, follow', true );

		echo self::get_xml(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- XML 内容已内部转义。
		exit;
	}

	/**
	 * 生成站点地图 XML 文本。
	 *
	 * @return string
	 */
	public static function get_xml() {
		$version = (int) get_option( MORN_SEO_META_KIT_OPTION . '_sitemap_version', 0 );
		$cache_key = self::CACHE_KEY . '_' . $version;
		$cached    = get_transient( $cache_key );

		if ( is_string( $cached ) && '' !== $cached ) {
			return $cached;
		}

		$settings = morn_seo_meta_kit_get_settings();
		$types    = ! empty( $settings['sitemap_post_types'] ) ? (array) $settings['sitemap_post_types'] : array( 'post', 'page' );

		$urls = array(
			array(
				'loc'        => home_url( '/' ),
				'lastmod'    => self::get_lastmod(),
				'changefreq' => 'daily',
				'priority'   => '1.0',
			),
		);

		foreach ( $types as $post_type ) {
			$entries = self::get_entries( $post_type );

			foreach ( $entries as $entry ) {
				$urls[] = $entry;
			}
		}

		/**
		 * 过滤站点地图 URL 列表。
		 *
		 * @param array $urls URL 条目数组。
		 * @param array $types 包含的文章类型。
		 */
		$urls = apply_filters( 'morn_seo_meta_kit_sitemap_urls', $urls, $types );

		$xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

		foreach ( $urls as $url ) {
			if ( empty( $url['loc'] ) ) {
				continue;
			}
			$xml .= "\t<url>\n";
			$xml .= "\t\t<loc>" . morn_seo_meta_kit_esc_xml( $url['loc'] ) . "</loc>\n";
			if ( ! empty( $url['lastmod'] ) ) {
				$xml .= "\t\t<lastmod>" . morn_seo_meta_kit_esc_xml( gmdate( 'c', (int) $url['lastmod'] ) ) . "</lastmod>\n";
			}
			if ( ! empty( $url['changefreq'] ) ) {
				$xml .= "\t\t<changefreq>" . morn_seo_meta_kit_esc_xml( $url['changefreq'] ) . "</changefreq>\n";
			}
			if ( isset( $url['priority'] ) ) {
				$xml .= "\t\t<priority>" . morn_seo_meta_kit_esc_xml( number_format( (float) $url['priority'], 1 ) ) . "</priority>\n";
			}
			$xml .= "\t</url>\n";
		}

		$xml .= '</urlset>' . "\n";

		// 缓存 6 小时，文章变更时由 Cleanup 递增版本号使其失效。
		set_transient( $cache_key, $xml, 6 * HOUR_IN_SECONDS );

		/**
		 * 过滤完整站点地图 XML。
		 *
		 * @param string $xml  XML 文本。
		 * @param array  $urls URL 条目数组。
		 */
		return apply_filters( 'morn_seo_meta_kit_sitemap_xml', $xml, $urls );
	}

	/**
	 * 获取某文章类型的已发布内容条目。
	 *
	 * @param string $post_type 文章类型。
	 * @return array
	 */
	private static function get_entries( $post_type ) {
		$post_type_object = get_post_type_object( $post_type );

		if ( ! $post_type_object || ! $post_type_object->public ) {
			return array();
		}

		$query = new WP_Query(
			array(
				'post_type'              => $post_type,
				'post_status'            => 'publish',
				'posts_per_page'         => 2000,
				'orderby'                => 'modified',
				'order'                  => 'DESC',
				'ignore_sticky_posts'    => true,
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		if ( ! $query->have_posts() ) {
			return array();
		}

		$is_front = 'page' === $post_type && (int) get_option( 'page_on_front' ) > 0;
		$entries  = array();

		foreach ( $query->posts as $post ) {
			// 密码保护内容不入图。
			if ( post_password_required( $post ) ) {
				continue;
			}
			// 单篇 noindex 排除。
			if ( get_post_meta( $post->ID, '_morn_seo_noindex', true ) ) {
				continue;
			}
			// 手动排除。
			if ( get_post_meta( $post->ID, '_morn_seo_exclude_sitemap', true ) ) {
				continue;
			}

			$loc = $is_front ? home_url( '/' ) : get_permalink( $post );

			if ( ! $loc ) {
				continue;
			}

			$entries[] = array(
				'loc'        => $loc,
				'lastmod'    => (int) get_post_modified_time( 'U', true, $post ),
				'changefreq' => self::get_changefreq( $post->post_date_gmt ),
				'priority'   => self::get_priority( $post->post_type, $post ),
			);
		}

		wp_reset_postdata();

		return $entries;
	}

	/**
	 * 依据发布时间距今天数计算 changefreq。
	 *
	 * @param string $date_gmt GMT 发布时间。
	 * @return string
	 */
	private static function get_changefreq( $date_gmt ) {
		$timestamp = strtotime( $date_gmt . ' UTC' );

		if ( ! $timestamp ) {
			return 'monthly';
		}

		$days = (int) floor( ( time() - $timestamp ) / DAY_IN_SECONDS );

		if ( $days <= 2 ) {
			return 'daily';
		}
		if ( $days <= 14 ) {
			return 'weekly';
		}
		if ( $days <= 60 ) {
			return 'monthly';
		}

		return 'yearly';
	}

	/**
	 * 计算 priority。
	 *
	 * @param string    $post_type 文章类型。
	 * @param \WP_Post  $post      文章对象。
	 * @return float
	 */
	private static function get_priority( $post_type, $post ) {
		if ( 'page' === $post_type ) {
			$front = (int) get_option( 'page_on_front' );
			if ( $front && $front === (int) $post->ID ) {
				return 1.0;
			}
			return 0.6;
		}

		$days = (int) floor( ( time() - strtotime( $post->post_date_gmt . ' UTC' ) ) / DAY_IN_SECONDS );

		if ( $days <= 2 ) {
			return 0.9;
		}
		if ( $days <= 14 ) {
			return 0.8;
		}
		if ( $days <= 60 ) {
			return 0.6;
		}

		return 0.4;
	}

	/**
	 * 站点最近更新时间。
	 *
	 * @return int
	 */
	private static function get_lastmod() {
		global $wpdb;

		$latest = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_modified_gmt FROM {$wpdb->posts} WHERE post_status = %s AND post_type IN ( %s, %s ) ORDER BY post_modified_gmt DESC LIMIT 1",
				'publish',
				'post',
				'page'
			)
		);

		$timestamp = $latest ? strtotime( $latest . ' UTC' ) : time();

		return (int) max( $timestamp, time() - YEAR_IN_SECONDS );
	}
}
