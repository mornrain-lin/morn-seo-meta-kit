<?php
/**
 * JSON-LD 结构化数据输出。
 *
 * @package MornRain\MornSeoMetaKit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 生成 Organization / WebSite / Breadcrumb / Article / Author 的 JSON-LD。
 */
class Morn_SEO_Meta_Kit_Schema {

	/**
	 * 注册钩子。
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'wp_head', array( __CLASS__, 'render' ), 20 );
	}

	/**
	 * 输出 JSON-LD。
	 *
	 * @return void
	 */
	public static function render() {
		$settings = morn_seo_meta_kit_get_settings();

		if ( empty( $settings['schema_enabled'] ) ) {
			return;
		}

		$context = morn_seo_meta_kit_get_context();
		$graph   = array();

		if ( ! empty( $settings['schema_organization'] ) ) {
			$org = self::get_organization();
			if ( $org ) {
				$graph[] = $org;
			}
		}

		if ( ! empty( $settings['schema_website'] ) ) {
			$website = self::get_website();
			if ( $website ) {
				$graph[] = $website;
			}
		}

		if ( ! empty( $settings['schema_breadcrumb'] ) && ! empty( $context['breadcrumb'] ) && ! is_front_page() ) {
			$breadcrumb = self::get_breadcrumb( $context['breadcrumb'] );
			if ( $breadcrumb ) {
				$graph[] = $breadcrumb;
			}
		}

		if ( ! empty( $settings['schema_article'] ) && is_singular() ) {
			$article = self::get_article( $context );
			if ( $article ) {
				$graph[] = $article;
			}
		}

		$graph = apply_filters( 'morn_seo_meta_kit_schema_graph', $graph, $context );

		if ( empty( $graph ) ) {
			return;
		}

		$data = array(
			'@context' => 'https://schema.org',
			'@graph'   => $graph,
		);

		/**
		 * 过滤完整的 JSON-LD 数据。
		 *
		 * @param array $data    待输出数据。
		 * @param array $context 当前 SEO 上下文。
		 */
		$data = apply_filters( 'morn_seo_meta_kit_schema_data', $data, $context );

		// JSON_HEX_TAG / JSON_HEX_AMP 保证数据中即使出现尖括号与 & 也不会破坏脚本块。
		$json = wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP );

		if ( false === $json || '' === $json ) {
			return;
		}

		echo '<script type="application/ld+json">' . $json . '</script>' . "\n";
	}

	/**
	 * 构造 Organization 节点。
	 *
	 * @return array|null
	 */
	private static function get_organization() {
		$settings = morn_seo_meta_kit_get_settings();
		$name     = get_bloginfo( 'name' );

		if ( '' === trim( (string) $name ) ) {
			return null;
		}

		$node = array(
			'@type' => 'Organization',
			'@id'   => home_url( '/#organization' ),
			'name'  => $name,
			'url'   => home_url( '/' ),
		);

		if ( ! empty( $settings['og_image_id'] ) ) {
			$logo = wp_get_attachment_image_url( (int) $settings['og_image_id'], 'full' );
			if ( $logo ) {
				$node['logo'] = array(
					'@type' => 'ImageObject',
					'url'   => $logo,
				);
			}
		}

		if ( ! empty( $settings['og_image_custom'] ) ) {
			$node['logo'] = array(
				'@type' => 'ImageObject',
				'url'   => $settings['og_image_custom'],
			);
		}

		$desc = get_bloginfo( 'description' );
		if ( $desc ) {
			$node['description'] = $desc;
		}

		return $node;
	}

	/**
	 * 构造 WebSite 节点。
	 *
	 * @return array|null
	 */
	private static function get_website() {
		$name = get_bloginfo( 'name' );

		if ( '' === trim( (string) $name ) ) {
			return null;
		}

		$node = array(
			'@type'           => 'WebSite',
			'@id'             => home_url( '/#website' ),
			'name'            => $name,
			'url'             => home_url( '/' ),
			'publisher'       => array( '@id' => home_url( '/#organization' ) ),
			'inLanguage'      => get_bloginfo( 'language' ),
			'potentialAction' => array(
				'@type'       => 'SearchAction',
				'target'      => array(
					'@type'       => 'EntryPoint',
					'urlTemplate' => home_url( '/?s={search_term_string}' ),
				),
				'query-input' => 'required name=search_term_string',
			),
		);

		return $node;
	}

	/**
	 * 构造 BreadcrumbList 节点。
	 *
	 * @param array $items 面包屑数组。
	 * @return array|null
	 */
	private static function get_breadcrumb( $items ) {
		$items = array_values( array_filter( (array) $items ) );

		if ( count( $items ) < 2 ) {
			return null;
		}

		$elements = array();
		$position = 1;

		foreach ( $items as $item ) {
			if ( empty( $item['name'] ) || empty( $item['url'] ) ) {
				continue;
			}
			$elements[] = array(
				'@type'    => 'ListItem',
				'position' => $position,
				'name'     => $item['name'],
				'item'     => $item['url'],
			);
			$position++;
		}

		if ( count( $elements ) < 2 ) {
			return null;
		}

		return array(
			'@type'           => 'BreadcrumbList',
			'@id'             => home_url( '/#breadcrumb' ),
			'itemListElement' => $elements,
		);
	}

	/**
	 * 构造 Article 节点。
	 *
	 * @param array $context SEO 上下文。
	 * @return array|null
	 */
	private static function get_article( $context ) {
		if ( empty( $context['post_id'] ) ) {
			return null;
		}

		$post = get_post( (int) $context['post_id'] );

		if ( ! $post instanceof WP_Post ) {
			return null;
		}

		$node = array(
			'@type'            => 'article',
			'@id'              => get_permalink( $post->ID ) . '#article',
			'headline'         => get_the_title( $post->ID ),
			'url'              => get_permalink( $post->ID ),
			'mainEntityOfPage' => array(
				'@type' => 'WebPage',
				'@id'   => get_permalink( $post->ID ),
			),
			'publisher'        => array( '@id' => home_url( '/#organization' ) ),
			'inLanguage'       => get_bloginfo( 'language' ),
		);

		if ( ! empty( $context['description'] ) ) {
			$node['description'] = $context['description'];
		}

		if ( ! empty( $context['published'] ) ) {
			$node['datePublished'] = $context['published'];
		}

		if ( ! empty( $context['modified'] ) ) {
			$node['dateModified'] = $context['modified'];
		}

		$author_id = (int) $post->post_author;
		if ( $author_id && ! empty( $context['author'] ) ) {
			$author = array(
				'@type' => 'Person',
				'@id'   => get_author_posts_url( $author_id ) . '#author',
				'name'  => $context['author'],
				'url'   => get_author_posts_url( $author_id ),
			);

			$settings = morn_seo_meta_kit_get_settings();
			if ( ! empty( $settings['schema_author'] ) ) {
				$node['author'] = $author;
			} else {
				$node['author'] = array( '@id' => $author['@id'] );
			}
		}

		if ( ! empty( $context['image'] ) ) {
			$node['image'] = array( $context['image'] );
		}

		$terms = get_the_terms( $post->ID, 'category' );
		if ( is_array( $terms ) && ! empty( $terms ) ) {
			$node['articleSection'] = wp_list_pluck( $terms, 'name' );
		}

		$tags = get_the_terms( $post->ID, 'post_tag' );
		if ( is_array( $tags ) && ! empty( $tags ) ) {
			$node['keywords'] = implode( ', ', wp_list_pluck( $tags, 'name' ) );
		}

		return $node;
	}
}
