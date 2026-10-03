<?php
/**
 * 前台 SEO 标签输出。
 *
 * @package MornRain\MornSeoMetaKit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 在 <head> 输出 title / description / OG / Twitter / canonical / hreflang。
 */
class Morn_SEO_Meta_Kit_Frontend {

	/**
	 * 注册钩子。
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'wp_head', array( __CLASS__, 'render' ), 1 );
		add_filter( 'document_title_parts', array( __CLASS__, 'filter_document_title' ), 20, 1 );
		add_filter( 'document_title_separator', array( __CLASS__, 'filter_separator' ) );
	}

	/**
	 * 接管 WordPress 默认 title 输出，改由本插件统一渲染。
	 *
	 * @param array $parts 标题各部分。
	 * @return array
	 */
	public static function filter_document_title( $parts ) {
		if ( is_admin() ) {
			return $parts;
		}

		$context = morn_seo_meta_kit_get_context();

		// 首页与文章页由本插件完整接管，其他归档页保留核心行为。
		if ( is_front_page() || is_singular() ) {
			return array( 'title' => $context['title'] );
		}

		return $parts;
	}

	/**
	 * 使用自定义分隔符。
	 *
	 * @param string $separator 默认分隔符。
	 * @return string
	 */
	public static function filter_separator( $separator ) {
		$settings = morn_seo_meta_kit_get_settings();
		$sep      = isset( $settings['title_separator'] ) ? (string) $settings['title_separator'] : '';

		return '' === $sep ? $separator : $sep;
	}

	/**
	 * 输出全部标签。
	 *
	 * @return void
	 */
	public static function render() {
		$settings = morn_seo_meta_kit_get_settings();
		$context  = morn_seo_meta_kit_get_context();

		// 移除核心与其它插件的重复输出，避免双份 title/description。
		remove_action( 'wp_head', '_wp_render_title_tag', 1 );
		remove_action( 'wp_head', 'wp_robots', 1 );
		remove_action( 'wp_head', 'rel_canonical' );

		echo self::get_tags( $settings, $context );
	}

	/**
	 * 构造所有 SEO 标签的 HTML 字符串。
	 *
	 * @param array $settings 插件设置。
	 * @param array $context  SEO 上下文。
	 * @return string
	 */
	public static function get_tags( $settings, $context ) {
		$tags = array();

		// title。
		$title = $context['title'];

		if ( is_front_page() ) {
			$title = morn_seo_meta_kit_render_template( $settings['home_title_format'], array( 'title' => get_bloginfo( 'name' ) ) );
		}

		if ( '' !== trim( (string) $title ) ) {
			$tags[] = '<title>' . esc_html( $title ) . '</title>';
		}

		// description。
		if ( ! empty( $settings['description_enabled'] ) && '' !== trim( (string) $context['description'] ) ) {
			$tags[] = '<meta name="description" content="' . esc_attr( $context['description'] ) . '" />';
		}

		// keywords。
		if ( ! empty( $settings['keywords_enabled'] ) && '' !== trim( (string) $context['keywords'] ) ) {
			$tags[] = '<meta name="keywords" content="' . esc_attr( $context['keywords'] ) . '" />';
		}

		// robots。
		$robots = self::get_robots( $settings, $context );

		if ( '' !== $robots ) {
			$tags[] = '<meta name="robots" content="' . esc_attr( $robots ) . '" />';
		}

		// canonical。
		if ( ! empty( $settings['canonical_enabled'] ) ) {
			$canonical = self::get_canonical( $settings, $context );

			if ( '' !== $canonical ) {
				$tags[] = '<link rel="canonical" href="' . esc_url( $canonical ) . '" />';
			}
		}

		// Open Graph。
		if ( ! empty( $settings['og_enabled'] ) ) {
			$tags = array_merge( $tags, self::get_og_tags( $settings, $context ) );
		}

		// hreflang。
		if ( ! empty( $settings['hreflang_enabled'] ) ) {
			$alternates = self::get_hreflang( $settings, $context );

			foreach ( $alternates as $hreflang ) {
				$tags[] = '<link rel="alternate" hreflang="' . esc_attr( $hreflang['lang'] ) . '" href="' . esc_url( $hreflang['url'] ) . '" />';
			}
		}

		/**
		 * 过滤最终输出的 SEO 标签数组。
		 *
		 * @param array $tags    标签 HTML 数组。
		 * @param array $context SEO 上下文。
		 * @param array $settings 插件设置。
		 */
		$tags = apply_filters( 'morn_seo_meta_kit_head_tags', $tags, $context, $settings );

		return implode( "\n", $tags ) . "\n";
	}

	/**
	 * 计算 robots 指令。
	 *
	 * @param array $settings 插件设置。
	 * @param array $context  SEO 上下文。
	 * @return string
	 */
	private static function get_robots( $settings, $context ) {
		if ( '' !== trim( (string) $context['robots'] ) ) {
			return $context['robots'];
		}

		if ( ! empty( $settings['noindex'] ) ) {
			return 'noindex, follow';
		}

		if ( ! empty( $context['noindex_post'] ) ) {
			return 'noindex, follow';
		}

		if ( ! empty( $settings['noindex_archives'] ) && ( is_archive() || is_search() ) ) {
			return 'noindex, follow';
		}

		if ( is_paged() && is_front_page() && is_home() ) {
			return 'noindex, follow';
		}

		// 尊重 WordPress 自身的公开性设置。
		if ( '1' !== (string) get_option( 'blog_public' ) ) {
			return 'noindex, nofollow';
		}

		return '';
	}

	/**
	 * 计算 canonical 地址。
	 *
	 * @param array $settings 插件设置。
	 * @param array $context  SEO 上下文。
	 * @return string
	 */
	private static function get_canonical( $settings, $context ) {
		$canonical = $context['canonical'];

		if ( '' === trim( (string) $canonical ) ) {
			return '';
		}

		if ( ! empty( $settings['canonical_strip_paging'] ) && is_paged() ) {
			$canonical = self::remove_query_args( $canonical, array( 'paged', 'page' ) );
		}

		// 去掉常见跟踪参数，保证 canonical 唯一。
		$canonical = self::remove_query_args(
			$canonical,
			array( 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'gclid', 'fbclid', 'ref' )
		);

		return $canonical;
	}

	/**
	 * 从 URL 移除指定查询参数。
	 *
	 * @param string $url    URL。
	 * @param array  $params 要移除的参数名。
	 * @return string
	 */
	private static function remove_query_args( $url, $params ) {
		$parts = wp_parse_url( (string) $url );

		if ( empty( $parts ) || empty( $parts['query'] ) ) {
			return $url;
		}

		parse_str( $parts['query'], $query );

		foreach ( $params as $param ) {
			unset( $query[ $param ] );
		}

		if ( empty( $query ) ) {
			return rtrim( (string) $url, '?' );
		}

		$base = ( isset( $parts['scheme'] ) ? $parts['scheme'] . '://' : '' )
			. ( isset( $parts['host'] ) ? $parts['host'] : '' )
			. ( isset( $parts['port'] ) ? ':' . $parts['port'] : '' )
			. ( isset( $parts['path'] ) ? $parts['path'] : '' );

		return $base . '?' . http_build_query( $query );
	}

	/**
	 * 构造 Open Graph 与 Twitter Card 标签。
	 *
	 * @param array $settings 插件设置。
	 * @param array $context  SEO 上下文。
	 * @return array
	 */
	private static function get_og_tags( $settings, $context ) {
		$tags    = array();
		$site    = get_bloginfo( 'name' );
		$site_og = '' !== trim( (string) $settings['og_site_name'] ) ? $settings['og_site_name'] : $site;
		$image   = self::get_share_image( $settings, $context );

		$tags[] = '<meta property="og:locale" content="' . esc_attr( $settings['og_locale'] ) . '" />';
		$tags[] = '<meta property="og:type" content="' . esc_attr( $context['type'] ? $context['type'] : $settings['og_type'] ) . '" />';
		$tags[] = '<meta property="og:title" content="' . esc_attr( $context['title'] ) . '" />';
		$tags[] = '<meta property="og:site_name" content="' . esc_attr( $site_og ) . '" />';
		$tags[] = '<meta property="og:url" content="' . esc_url( $context['canonical'] ) . '" />';

		if ( '' !== trim( (string) $context['description'] ) ) {
			$tags[] = '<meta property="og:description" content="' . esc_attr( $context['description'] ) . '" />';
		}

		if ( '' !== $image ) {
			$tags[] = '<meta property="og:image" content="' . esc_url( $image ) . '" />';

			$width = self::get_image_size( $settings, $context, 'width' );
			$height = self::get_image_size( $settings, $context, 'height' );

			if ( $width ) {
				$tags[] = '<meta property="og:image:width" content="' . esc_attr( (string) $width ) . '" />';
			}
			if ( $height ) {
				$tags[] = '<meta property="og:image:height" content="' . esc_attr( (string) $height ) . '" />';
			}
			$alt = self::get_image_alt( $settings, $context );
			if ( '' !== $alt ) {
				$tags[] = '<meta property="og:image:alt" content="' . esc_attr( $alt ) . '" />';
			}
		}

		if ( 'article' === $context['type'] ) {
			if ( ! empty( $context['published'] ) ) {
				$tags[] = '<meta property="article:published_time" content="' . esc_attr( $context['published'] ) . '" />';
			}
			if ( ! empty( $context['modified'] ) ) {
				$tags[] = '<meta property="article:modified_time" content="' . esc_attr( $context['modified'] ) . '" />';
			}
			if ( ! empty( $context['author'] ) ) {
				$tags[] = '<meta property="article:author" content="' . esc_attr( $context['author'] ) . '" />';
			}
		}

		// Twitter Card。
		$card = 'summary';
		if ( '' !== $image && 'summary' !== $settings['twitter_card'] ) {
			$card = 'summary_large_image';
		}

		$tags[] = '<meta name="twitter:card" content="' . esc_attr( $card ) . '" />';
		$tags[] = '<meta name="twitter:title" content="' . esc_attr( $context['title'] ) . '" />';

		if ( '' !== trim( (string) $context['description'] ) ) {
			$tags[] = '<meta name="twitter:description" content="' . esc_attr( $context['description'] ) . '" />';
		}

		if ( '' !== $image ) {
			$tags[] = '<meta name="twitter:image" content="' . esc_url( $image ) . '" />';
		}

		$twitter = trim( (string) $settings['og_twitter_site'] );

		if ( '' !== $twitter ) {
			$twitter = '@' . ltrim( $twitter, '@' );
			$tags[]  = '<meta name="twitter:site" content="' . esc_attr( $twitter ) . '" />';
		}

		return $tags;
	}

	/**
	 * 解析分享图地址优先级：文章特色图 > 自定义 URL > 媒体库。
	 *
	 * @param array $settings 插件设置。
	 * @param array $context  SEO 上下文。
	 * @return string
	 */
	private static function get_share_image( $settings, $context ) {
		if ( ! empty( $context['image'] ) ) {
			return $context['image'];
		}

		if ( ! empty( $settings['og_image_custom'] ) ) {
			return $settings['og_image_custom'];
		}

		if ( ! empty( $settings['og_image_id'] ) ) {
			$url = wp_get_attachment_image_url( (int) $settings['og_image_id'], 'full' );

			if ( $url ) {
				return $url;
			}
		}

		return '';
	}

	/**
	 * 获取分享图尺寸。
	 *
	 * @param array  $settings 插件设置。
	 * @param array  $context  SEO 上下文。
	 * @param string $key      width 或 height。
	 * @return int
	 */
	private static function get_image_size( $settings, $context, $key ) {
		$attachment_id = 0;

		if ( ! empty( $context['post_id'] ) && has_post_thumbnail( (int) $context['post_id'] ) ) {
			$attachment_id = (int) get_post_thumbnail_id( (int) $context['post_id'] );
		} elseif ( ! empty( $settings['og_image_id'] ) ) {
			$attachment_id = (int) $settings['og_image_id'];
		}

		if ( ! $attachment_id ) {
			return 0;
		}

		$meta = wp_get_attachment_metadata( $attachment_id );

		if ( ! is_array( $meta ) || empty( $meta['width'] ) || empty( $meta['height'] ) ) {
			return 0;
		}

		return 'width' === $key ? (int) $meta['width'] : (int) $meta['height'];
	}

	/**
	 * 获取分享图替代文本。
	 *
	 * @param array $settings 插件设置。
	 * @param array $context  SEO 上下文。
	 * @return string
	 */
	private static function get_image_alt( $settings, $context ) {
		$attachment_id = 0;

		if ( ! empty( $context['post_id'] ) && has_post_thumbnail( (int) $context['post_id'] ) ) {
			$attachment_id = (int) get_post_thumbnail_id( (int) $context['post_id'] );
		} elseif ( ! empty( $settings['og_image_id'] ) ) {
			$attachment_id = (int) $settings['og_image_id'];
		}

		if ( ! $attachment_id ) {
			return '';
		}

		$alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );

		return is_string( $alt ) ? $alt : '';
	}

	/**
	 * 构造 hreflang 链接列表。
	 *
	 * @param array $settings 插件设置。
	 * @param array $context  SEO 上下文。
	 * @return array
	 */
	private static function get_hreflang( $settings, $context ) {
		$url = $context['canonical'];

		if ( '' === trim( (string) $url ) ) {
			return array();
		}

		$lang = trim( (string) $settings['hreflang_default'] );

		if ( '' === $lang ) {
			$lang = str_replace( '-', '_', get_bloginfo( 'language' ) );
		}

		$alternates = array(
			array(
				'lang' => $lang,
				'url'  => $url,
			),
		);

		/**
		 * 过滤 hreflang 链接列表，便于多语言插件追加其他语言版本。
		 *
		 * @param array  $alternates 链接数组，每项含 lang 与 url。
		 * @param array  $context    SEO 上下文。
		 * @param string $lang       当前语言代码。
		 */
		$alternates = apply_filters( 'morn_seo_meta_kit_hreflang', $alternates, $context, $lang );

		$clean = array();

		foreach ( (array) $alternates as $item ) {
			if ( empty( $item['lang'] ) || empty( $item['url'] ) ) {
				continue;
			}
			$clean[] = array(
				'lang' => sanitize_text_field( $item['lang'] ),
				'url'  => esc_url_raw( $item['url'] ),
			);
		}

		return $clean;
	}
}
