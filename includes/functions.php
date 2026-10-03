<?php
/**
 * 插件通用辅助函数。
 *
 * @package MornRain\MornSeoMetaKit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 读取插件设置并与默认值合并。
 *
 * @return array
 */
function morn_seo_meta_kit_get_settings() {
	$defaults = array(
		// 标题与描述。
		'title_format'          => '%title% %sep% %site%',
		'title_separator'       => '|',
		'home_title_format'     => '%title% %sep% %site%',
		'description_enabled'   => 1,
		'description_length'    => 155,
		'keywords_enabled'      => 0,
		'noindex'               => 0,
		'noindex_archives'      => 0,
		'canonical_enabled'     => 1,
		'canonical_strip_paging'=> 1,
		// Open Graph。
		'og_enabled'            => 1,
		'og_type'               => 'website',
		'og_image_id'           => 0,
		'og_image_custom'       => '',
		'og_locale'             => 'zh_CN',
		'og_site_name'          => '',
		'og_twitter_site'       => '',
		'twitter_card'          => 'summary_large_image',
		// hreflang。
		'hreflang_enabled'      => 0,
		'hreflang_default'      => '',
		// 结构化数据。
		'schema_enabled'        => 1,
		'schema_organization'   => 1,
		'schema_website'        => 1,
		'schema_breadcrumb'     => 1,
		'schema_article'        => 1,
		'schema_author'         => 1,
		'schema_logo_id'        => 0,
		// 站点地图。
		'sitemap_enabled'       => 1,
		'sitemap_post_types'    => array( 'post', 'page' ),
		// robots.txt。
		'robots_enabled'        => 1,
		'robots_disallow'       => '/wp-admin/\n/wp-admin/admin-ajax.php',
	);

	$saved = get_option( MORN_SEO_META_KIT_OPTION, array() );

	if ( ! is_array( $saved ) ) {
		$saved = array();
	}

	/**
	 * 过滤插件设置。
	 *
	 * @param array $settings 合并默认值后的设置。
	 */
	return apply_filters( 'morn_seo_meta_kit_settings', array_merge( $defaults, $saved ) );
}

/**
 * 读取单个设置项。
 *
 * @param string $key     设置键名。
 * @param mixed  $default 未设置时的返回值。
 * @return mixed
 */
function morn_seo_meta_kit_get_setting( $key, $default = null ) {
	$settings = morn_seo_meta_kit_get_settings();

	if ( ! array_key_exists( $key, $settings ) ) {
		return $default;
	}

	return $settings[ $key ];
}

/**
 * 清理模板变量并替换为实际内容。
 *
 * 支持变量：%title% %site% %sep% %page% %excerpt% %category% %tag% %author% %year% %day% %month% %post_id% %search%
 *
 * @param string $template 模板字符串。
 * @param array  $extra    额外变量，键为不含 % 的变量名。
 * @return string
 */
function morn_seo_meta_kit_render_template( $template, $extra = array() ) {
	$settings = morn_seo_meta_kit_get_settings();
	$sep     = isset( $settings['title_separator'] ) && '' !== $settings['title_separator'] ? $settings['title_separator'] : '|';

	$title = isset( $extra['title'] ) ? $extra['title'] : '';
	$vars  = array(
		'%title%'    => $title,
		'%site%'     => get_bloginfo( 'name' ),
		'%sep%'      => $sep,
		'%page%'     => '',
		'%excerpt%'  => '',
		'%category%' => '',
		'%tag%'      => '',
		'%author%'   => '',
		'%year%'     => gmdate( 'Y' ),
		'%day%'      => '',
		'%month%'    => '',
		'%post_id%'  => '',
		'%search%'   => '',
	);

	foreach ( $extra as $key => $value ) {
		$token = '%' . trim( (string) $key, '%' ) . '%';
		if ( array_key_exists( $token, $vars ) && is_scalar( $value ) ) {
			$vars[ $token ] = (string) $value;
		}
	}

	$output = strtr( (string) $template, $vars );

	// 折叠多余空白与孤立分隔符。
	$output = preg_replace( '/\s+/u', ' ', $output );
	$output = preg_replace( '/\s*\|\s*(\|\s*)+/u', ' | ', (string) $output );
	$output = trim( (string) $output, " |-\t\n\r\0\x0B" );

	/**
	 * 过滤模板渲染结果。
	 *
	 * @param string $output   渲染后的字符串。
	 * @param string $template 原始模板。
	 * @param array  $extra    额外变量。
	 */
	return apply_filters( 'morn_seo_meta_kit_render_template', $output, $template, $extra );
}

/**
 * 获取当前请求的 SEO 上下文数据。
 *
 * @return array
 */
function morn_seo_meta_kit_get_context() {
	$context = array(
		'title'    => '',
		'description' => '',
		'keywords'  => '',
		'canonical' => '',
		'robots'    => '',
		'type'      => 'website',
		'image'     => '',
		'published' => '',
		'modified'  => '',
		'author'    => '',
		'post_id'   => 0,
		'breadcrumb' => array(),
	);

	if ( is_singular() ) {
		$post_id = get_queried_object_id();
		$post    = get_post( $post_id );

		if ( $post instanceof WP_Post ) {
			$meta_title = get_post_meta( $post_id, '_morn_seo_title', true );
			$meta_desc  = get_post_meta( $post_id, '_morn_seo_description', true );
			$meta_kw    = get_post_meta( $post_id, '_morn_seo_keywords', true );
			$meta_noindex = get_post_meta( $post_id, '_morn_seo_noindex', true );

			$settings    = morn_seo_meta_kit_get_settings();
			$site        = get_bloginfo( 'name' );
			$sep         = $settings['title_separator'];
			$page_prefix = '';

			if ( is_singular( 'post' ) ) {
				$page_prefix = (string) get_the_title( $post_id );
			} elseif ( is_page() ) {
				$ancestors   = array_reverse( (array) get_post_ancestors( $post_id ) );
				$page_titles = array();
				foreach ( $ancestors as $ancestor_id ) {
					$page_titles[] = get_the_title( $ancestor_id );
				}
				$page_titles[] = get_the_title( $post_id );
				$page_prefix   = implode( ' ' . $sep . ' ', $page_titles );
			}

			$context['title'] = '' !== $meta_title
				? $meta_title
				: morn_seo_meta_kit_render_template(
					$settings['title_format'],
					array(
						'title' => get_the_title( $post_id ),
						'page'  => $page_prefix,
					)
				);

			$raw_desc = '' !== $meta_desc ? $meta_desc : $post->post_excerpt;
			if ( '' === trim( (string) $raw_desc ) ) {
				$raw_desc = $post->post_content;
			}
			$context['description'] = morn_seo_meta_kit_truncate(
				wp_strip_all_tags( strip_shortcodes( (string) $raw_desc ) ),
				(int) $settings['description_length']
			);

			$context['keywords']      = is_string( $meta_kw ) ? $meta_kw : '';
			$context['type']          = 'article';
			$context['published']     = get_post_time( 'c', true, $post_id );
			$context['modified']      = get_post_modified_time( 'c', true, $post_id );
			$context['author']        = get_the_author_meta( 'display_name', (int) $post->post_author );
			$context['post_id']       = (int) $post_id;
			$context['canonical']     = get_permalink( $post_id );
			$context['noindex_post']  = ! empty( $meta_noindex );

			$thumb = get_the_post_thumbnail_url( $post_id, 'full' );
			if ( $thumb ) {
				$context['image'] = $thumb;
			}

			$context['breadcrumb'] = array(
				array(
					'name' => get_bloginfo( 'name' ),
					'url'  => home_url( '/' ),
				),
				array(
					'name' => get_the_title( $post_id ),
					'url'  => get_permalink( $post_id ),
				),
			);
		}
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$term = get_queried_object();

		if ( $term instanceof WP_Term ) {
			$settings             = morn_seo_meta_kit_get_settings();
			$context['title']     = $term->name;
			$context['description'] = $term->description ? wp_strip_all_tags( $term->description ) : '';
			$context['canonical'] = get_term_link( $term );
			$context['type']      = 'website';
			$context['breadcrumb'] = array(
				array(
					'name' => get_bloginfo( 'name' ),
					'url'  => home_url( '/' ),
				),
				array(
					'name' => $term->name,
					'url'  => get_term_link( $term ),
				),
			);
		}
	} elseif ( is_search() ) {
		/* translators: %s: 搜索关键词。 */
		$context['title'] = sprintf( __( '搜索：%s', 'morn-seo-meta-kit' ), get_search_query() );
		$context['description'] = '';
		$context['robots'] = 'noindex,follow';
	} elseif ( is_author() ) {
		$author_id = (int) get_query_var( 'author' );
		$context['title']     = get_the_author_meta( 'display_name', $author_id );
		$context['description'] = wp_strip_all_tags( get_the_author_meta( 'description', $author_id ) );
		$context['canonical'] = get_author_posts_url( $author_id );
	} elseif ( is_home() && ! is_front_page() ) {
		$context['title']     = single_post_title( '', false );
		$context['description'] = get_bloginfo( 'description' );
		$context['canonical'] = get_permalink( get_option( 'page_for_posts' ) );
	} elseif ( is_404() ) {
		$context['title']       = __( '页面未找到', 'morn-seo-meta-kit' );
		$context['description'] = __( '您访问的内容不存在或已被移动。', 'morn-seo-meta-kit' );
		$context['robots']      = 'noindex,follow';
	} else {
		$context['title']       = get_bloginfo( 'name' );
		$context['description'] = get_bloginfo( 'description' );
		$context['canonical']   = home_url( '/' );
	}

	if ( '' === $context['canonical'] ) {
		$context['canonical'] = home_url( add_query_arg( array() ) );
	}

	return $context;
}

/**
 * 按字符长度截断文本，避免破坏中文字符。
 *
 * @param string $text  原文。
 * @param int    $limit 目标长度（字符数）。
 * @return string
 */
function morn_seo_meta_kit_truncate( $text, $limit = 155 ) {
	$text = trim( preg_replace( '/\s+/u', ' ', (string) $text ) );

	if ( $limit < 10 ) {
		return $text;
	}

	// 无 mbstring 时退化为字节截断，并按 UTF-8 边界回退避免乱码。
	if ( function_exists( 'mb_strlen' ) ) {
		if ( mb_strlen( $text, 'UTF-8' ) <= $limit ) {
			return $text;
		}

		$cut = mb_substr( $text, 0, $limit, 'UTF-8' );

		return rtrim( $cut, " \t\n\r\0\x0B,，。；;、-–—" ) . '…';
	}

	if ( strlen( $text ) <= $limit ) {
		return $text;
	}

	$cut = substr( $text, 0, $limit );

	// 回退到合法的 UTF-8 序列起点。
	while ( '' !== $cut && ord( $cut[ strlen( $cut ) - 1 ] ) >= 0x80 && ord( $cut[ strlen( $cut ) - 1 ] ) <= 0xBF ) {
		$cut = substr( $cut, 0, -1 );
	}

	return rtrim( $cut, " \t\n\r\0\x0B,，。；;、-–—" ) . '…';
}

/**
 * XML 文本转义。
 *
 * 与 HTML 转义不同，XML 中不需要把单引号转成实体，
 * 且必须剥离 XML 1.0 禁止的控制字符，否则文档无法解析。
 *
 * @param string $text 原始文本。
 * @return string
 */
function morn_seo_meta_kit_esc_xml( $text ) {
	$text = (string) $text;

	// 移除 XML 1.0 不允许的控制字符（保留 \t \n \r）。
	$text = preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $text );

	return htmlspecialchars( $text, ENT_XML1 | ENT_QUOTES, 'UTF-8' );
}

/**
 * 判断字符串是否以指定前缀开头。
 *
 * @param string $haystack 待检查字符串。
 * @param string $needle   前缀。
 * @return bool
 */
function morn_seo_meta_kit_starts_with( $haystack, $needle ) {
	if ( '' === $needle ) {
		return true;
	}

	return 0 === strpos( (string) $haystack, (string) $needle );
}
