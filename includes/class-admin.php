<?php
/**
 * 后台设置页。
 *
 * @package MornRain\MornSeoMetaKit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 设置页控制器（Settings API）。
 */
class Morn_SEO_Meta_Kit_Admin {

	/**
	 * 插件设置分组名。
	 */
	const GROUP = 'morn_seo_meta_kit_group';

	/**
	 * 设置页 slug。
	 */
	const PAGE = 'morn-seo-meta-kit';

	/**
	 * 注册钩子。
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_action( 'admin_notices', array( __CLASS__, 'render_notices' ) );
	}

	/**
	 * 注册设置菜单。
	 *
	 * @return void
	 */
	public static function add_menu() {
		add_menu_page(
			__( 'SEO 元信息工具', 'morn-seo-meta-kit' ),
			__( 'SEO 元信息', 'morn-seo-meta-kit' ),
			'manage_options',
			self::PAGE,
			array( __CLASS__, 'render_page' ),
			'dashicons-chart-area',
			58
		);
	}

	/**
	 * 注册设置项与字段。
	 *
	 * @return void
	 */
	public static function register_settings() {
		register_setting(
			self::GROUP,
			MORN_SEO_META_KIT_OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => array(),
			)
		);

		// 标题与描述。
		add_settings_section(
			'sec_title',
			__( '标题与描述', 'morn-seo-meta-kit' ),
			array( __CLASS__, 'render_sec_title' ),
			self::PAGE
		);

		self::field( 'title_format', __( '文章标题格式', 'morn-seo-meta-kit' ), 'text', 'sec_title' );
		self::field( 'home_title_format', __( '首页标题格式', 'morn-seo-meta-kit' ), 'text', 'sec_title' );
		self::field( 'title_separator', __( '标题分隔符', 'morn-seo-meta-kit' ), 'text', 'sec_title' );
		self::field( 'description_enabled', __( '输出 meta description', 'morn-seo-meta-kit' ), 'checkbox', 'sec_title' );
		self::field( 'description_length', __( '描述最大长度（字符）', 'morn-seo-meta-kit' ), 'number', 'sec_title' );
		self::field( 'keywords_enabled', __( '输出 meta keywords', 'morn-seo-meta-kit' ), 'checkbox', 'sec_title' );
		self::field( 'noindex', __( '全站 noindex（请谨慎）', 'morn-seo-meta-kit' ), 'checkbox', 'sec_title' );
		self::field( 'noindex_archives', __( '归档页 noindex', 'morn-seo-meta-kit' ), 'checkbox', 'sec_title' );
		self::field( 'canonical_enabled', __( '输出 canonical', 'morn-seo-meta-kit' ), 'checkbox', 'sec_title' );
		self::field( 'canonical_strip_paging', __( 'canonical 移除分页参数', 'morn-seo-meta-kit' ), 'checkbox', 'sec_title' );

		// 社交卡片。
		add_settings_section(
			'sec_social',
			__( '社交卡片（Open Graph / Twitter）', 'morn-seo-meta-kit' ),
			array( __CLASS__, 'render_sec_social' ),
			self::PAGE
		);

		self::field( 'og_enabled', __( '启用 Open Graph', 'morn-seo-meta-kit' ), 'checkbox', 'sec_social' );
		self::field( 'og_type', __( '默认 OG 类型', 'morn-seo-meta-kit' ), 'select_og', 'sec_social' );
		self::field( 'og_site_name', __( 'OG 站点名称（留空用站点标题）', 'morn-seo-meta-kit' ), 'text', 'sec_social' );
		self::field( 'og_locale', __( 'OG 语言', 'morn-seo-meta-kit' ), 'text', 'sec_social' );
		self::field( 'og_image_id', __( '默认分享图（媒体库）', 'morn-seo-meta-kit' ), 'image', 'sec_social' );
		self::field( 'og_image_custom', __( '默认分享图 URL（优先于媒体库）', 'morn-seo-meta-kit' ), 'url', 'sec_social' );
		self::field( 'og_twitter_site', __( 'Twitter @用户名', 'morn-seo-meta-kit' ), 'text', 'sec_social' );
		self::field( 'twitter_card', __( 'Twitter Card 类型', 'morn-seo-meta-kit' ), 'select_twitter', 'sec_social' );

		// hreflang。
		add_settings_section(
			'sec_hreflang',
			__( '多语言 hreflang', 'morn-seo-meta-kit' ),
			array( __CLASS__, 'render_sec_hreflang' ),
			self::PAGE
		);

		self::field( 'hreflang_enabled', __( '启用 hreflang', 'morn-seo-meta-kit' ), 'checkbox', 'sec_hreflang' );
		self::field( 'hreflang_default', __( '默认语言代码', 'morn-seo-meta-kit' ), 'text', 'sec_hreflang' );

		// 结构化数据。
		add_settings_section(
			'sec_schema',
			__( 'JSON-LD 结构化数据', 'morn-seo-meta-kit' ),
			array( __CLASS__, 'render_sec_schema' ),
			self::PAGE
		);

		self::field( 'schema_enabled', __( '启用 JSON-LD 输出', 'morn-seo-meta-kit' ), 'checkbox', 'sec_schema' );
		self::field( 'schema_organization', __( '输出 Organization', 'morn-seo-meta-kit' ), 'checkbox', 'sec_schema' );
		self::field( 'schema_website', __( '输出 WebSite（含搜索）', 'morn-seo-meta-kit' ), 'checkbox', 'sec_schema' );
		self::field( 'schema_breadcrumb', __( '输出 BreadcrumbList', 'morn-seo-meta-kit' ), 'checkbox', 'sec_schema' );
		self::field( 'schema_article', __( '输出 Article', 'morn-seo-meta-kit' ), 'checkbox', 'sec_schema' );
		self::field( 'schema_author', __( '输出 Author Person', 'morn-seo-meta-kit' ), 'checkbox', 'sec_schema' );
		self::field( 'schema_logo_id', __( '组织 Logo（媒体库）', 'morn-seo-meta-kit' ), 'image', 'sec_schema' );

		// 站点地图与 robots。
		add_settings_section(
			'sec_map',
			__( '站点地图与 robots.txt', 'morn-seo-meta-kit' ),
			array( __CLASS__, 'render_sec_map' ),
			self::PAGE
		);

		self::field( 'sitemap_enabled', __( '启用精简站点地图', 'morn-seo-meta-kit' ), 'checkbox', 'sec_map' );
		self::field( 'sitemap_post_types', __( '站点地图包含的文章类型', 'morn-seo-meta-kit' ), 'post_types', 'sec_map' );
		self::field( 'robots_enabled', __( '增强 robots.txt', 'morn-seo-meta-kit' ), 'checkbox', 'sec_map' );
		self::field( 'robots_disallow', __( '附加 Disallow 规则（每行一条）', 'morn-seo-meta-kit' ), 'textarea', 'sec_map' );
	}

	/**
	 * 注册单个设置字段。
	 *
	 * @param string $key   设置键名。
	 * @param string $label 字段标签。
	 * @param string $type  字段类型。
	 * @param string $section 所属分组。
	 * @return void
	 */
	private static function field( $key, $label, $type, $section ) {
		add_settings_field(
			'morn_field_' . $key,
			$label,
			array( __CLASS__, 'render_field' ),
			self::PAGE,
			$section,
			array(
				'key'  => $key,
				'type' => $type,
				'label' => $label,
			)
		);
	}

	/**
	 * 渲染设置页。
	 *
	 * @return void
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( '您没有权限访问此页面。', 'morn-seo-meta-kit' ) );
		}
		?>
		<div class="wrap morn-seo-wrap">
			<h1><?php echo esc_html__( 'SEO 元信息工具', 'morn-seo-meta-kit' ); ?></h1>
			<p class="description">
				<?php
				printf(
					/* translators: %s: 站点地图 URL。 */
					esc_html__( '精简站点地图地址：%s', 'morn-seo-meta-kit' ),
					'<code>' . esc_html( home_url( '/sitemap-seo.xml' ) ) . '</code>'
				);
				?>
			</p>
			<form action="options.php" method="post">
				<?php
				settings_fields( self::GROUP );
				do_settings_sections( self::PAGE );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * 各分组的说明文案。
	 *
	 * @return void
	 */
	public static function render_sec_title() {
		echo '<p class="description">' . esc_html__( '控制浏览器标题与 meta 描述的输出。可用变量：%title% %site% %sep% %page% %excerpt% %category% %tag% %author% %year%。', 'morn-seo-meta-kit' ) . '</p>';
	}

	/**
	 * 社交卡片分组说明。
	 *
	 * @return void
	 */
	public static function render_sec_social() {
		echo '<p class="description">' . esc_html__( '文章若设置了特色图片，将优先使用特色图片作为分享图。', 'morn-seo-meta-kit' ) . '</p>';
	}

	/**
	 * hreflang 分组说明。
	 *
	 * @return void
	 */
	public static function render_sec_hreflang() {
		echo '<p class="description">' . esc_html__( '启用后输出当前页面的自引用 hreflang。若站点安装了多语言插件，请在下方钩子中补充其他语言版本。', 'morn-seo-meta-kit' ) . '</p>';
	}

	/**
	 * 结构化数据分组说明。
	 *
	 * @return void
	 */
	public static function render_sec_schema() {
		echo '<p class="description">' . esc_html__( 'JSON-LD 以 application/ld+json 输出在 <head>，不依赖任何外部脚本。', 'morn-seo-meta-kit' ) . '</p>';
	}

	/**
	 * 站点地图分组说明。
	 *
	 * @return void
	 */
	public static function render_sec_map() {
		echo '<p class="description">' . esc_html__( '站点地图为独立精简实现，仅输出已发布且未设置 noindex 的内容。', 'morn-seo-meta-kit' ) . '</p>';
	}

	/**
	 * 渲染字段控件。
	 *
	 * @param array $args 字段参数。
	 * @return void
	 */
	public static function render_field( $args ) {
		$settings = morn_seo_meta_kit_get_settings();
		$key      = $args['key'];
		$type     = $args['type'];
		$name     = MORN_SEO_META_KIT_OPTION . '[' . $key . ']';
		$id       = 'morn-field-' . $key;
		$value    = isset( $settings[ $key ] ) ? $settings[ $key ] : '';

		switch ( $type ) {
			case 'checkbox':
				?>
				<label for="<?php echo esc_attr( $id ); ?>">
					<input type="checkbox" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="1" <?php checked( 1, (int) $value ); ?> />
					<?php echo esc_html__( '启用', 'morn-seo-meta-kit' ); ?>
				</label>
				<?php
				break;

			case 'select_og':
				?>
				<select id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>">
					<option value="website" <?php selected( 'website', (string) $value ); ?>><?php echo esc_html__( 'website', 'morn-seo-meta-kit' ); ?></option>
					<option value="article" <?php selected( 'article', (string) $value ); ?>><?php echo esc_html__( 'article', 'morn-seo-meta-kit' ); ?></option>
					<option value="profile" <?php selected( 'profile', (string) $value ); ?>><?php echo esc_html__( 'profile', 'morn-seo-meta-kit' ); ?></option>
					<option value="book" <?php selected( 'book', (string) $value ); ?>><?php echo esc_html__( 'book', 'morn-seo-meta-kit' ); ?></option>
				</select>
				<?php
				break;

			case 'select_twitter':
				?>
				<select id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>">
					<option value="summary" <?php selected( 'summary', (string) $value ); ?>><?php echo esc_html__( 'summary', 'morn-seo-meta-kit' ); ?></option>
					<option value="summary_large_image" <?php selected( 'summary_large_image', (string) $value ); ?>><?php echo esc_html__( 'summary_large_image', 'morn-seo-meta-kit' ); ?></option>
				</select>
				<?php
				break;

			case 'post_types':
				$selected = is_array( $value ) ? $value : array();
				$types    = get_post_types( array( 'public' => true ), 'objects' );
				?>
				<fieldset class="morn-checks">
					<?php foreach ( $types as $slug => $obj ) : ?>
						<label>
							<input type="checkbox" name="<?php echo esc_attr( $name ); ?>[]" value="<?php echo esc_attr( $slug ); ?>" <?php checked( in_array( $slug, $selected, true ) ); ?> />
							<?php echo esc_html( $obj->labels->name ); ?>
						</label><br />
					<?php endforeach; ?>
				</fieldset>
				<?php
				break;

			case 'image':
				$attachment_id = (int) $value;
				$preview       = $attachment_id ? wp_get_attachment_image_url( $attachment_id, 'medium' ) : '';
				?>
				<div class="morn-image-field">
					<input type="hidden" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( (string) $attachment_id ); ?>" />
					<div class="morn-image-preview" id="<?php echo esc_attr( $id ); ?>-preview">
						<?php if ( $preview ) : ?>
							<img src="<?php echo esc_url( $preview ); ?>" alt="" style="max-width:160px;height:auto;" />
						<?php else : ?>
							<span class="morn-image-empty"><?php echo esc_html__( '未选择图片', 'morn-seo-meta-kit' ); ?></span>
						<?php endif; ?>
					</div>
					<button type="button" class="button" data-morn-image-select="<?php echo esc_attr( $id ); ?>"><?php echo esc_html__( '选择图片', 'morn-seo-meta-kit' ); ?></button>
					<button type="button" class="button" data-morn-image-clear="<?php echo esc_attr( $id ); ?>"><?php echo esc_html__( '移除', 'morn-seo-meta-kit' ); ?></button>
				</div>
				<?php
				break;

			case 'textarea':
				?>
				<textarea id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" rows="5" cols="50" class="large-text code"><?php echo esc_textarea( (string) $value ); ?></textarea>
				<?php
				break;

			case 'number':
				?>
				<input type="number" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( (string) $value ); ?>" min="20" max="320" step="1" class="small-text" />
				<?php
				break;

			case 'url':
				?>
				<input type="url" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( (string) $value ); ?>" class="regular-text" placeholder="https://" />
				<?php
				break;

			default:
				?>
				<input type="text" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( (string) $value ); ?>" class="regular-text" />
				<?php
				break;
		}
	}

	/**
	 * 校验设置数据。
	 *
	 * @param mixed $input 原始输入。
	 * @return array
	 */
	public static function sanitize( $input ) {
		$old     = morn_seo_meta_kit_get_settings();
		$input   = is_array( $input ) ? $input : array();
		$clean   = $old;

		// 文本项。
		$text_fields = array(
			'title_format'           => array( 'sanitize_text_field', 200 ),
			'home_title_format'      => array( 'sanitize_text_field', 200 ),
			'title_separator'        => array( 'sanitize_text_field', 10 ),
			'og_type'                => array( 'sanitize_key', 20 ),
			'og_site_name'           => array( 'sanitize_text_field', 100 ),
			'og_locale'              => array( 'sanitize_text_field', 20 ),
			'og_twitter_site'        => array( 'sanitize_text_field', 30 ),
			'twitter_card'           => array( 'sanitize_key', 30 ),
			'hreflang_default'       => array( 'sanitize_text_field', 20 ),
		);

		foreach ( $text_fields as $key => $spec ) {
			if ( ! isset( $input[ $key ] ) ) {
				continue;
			}
			$raw         = is_scalar( $input[ $key ] ) ? (string) $input[ $key ] : '';
			$clean[ $key ] = call_user_func( $spec[0], $raw );
		}

		// 分隔符允许常见符号。
		if ( isset( $input['title_separator'] ) ) {
			$sep = is_scalar( $input['title_separator'] ) ? (string) $input['title_separator'] : '';
			$sep = trim( $sep );
			$sep = substr( $sep, 0, 3 );
			$clean['title_separator'] = '' === $sep ? '|' : $sep;
		}

		// 白名单枚举。
		$og_types       = array( 'website', 'article', 'profile', 'book' );
		$twitter_cards  = array( 'summary', 'summary_large_image' );
		$clean['og_type']      = in_array( $clean['og_type'], $og_types, true ) ? $clean['og_type'] : 'website';
		$clean['twitter_card'] = in_array( $clean['twitter_card'], $twitter_cards, true ) ? $clean['twitter_card'] : 'summary_large_image';

		// 复选框。
		$checkboxes = array(
			'description_enabled',
			'keywords_enabled',
			'noindex',
			'noindex_archives',
			'canonical_enabled',
			'canonical_strip_paging',
			'og_enabled',
			'hreflang_enabled',
			'schema_enabled',
			'schema_organization',
			'schema_website',
			'schema_breadcrumb',
			'schema_article',
			'schema_author',
			'sitemap_enabled',
			'robots_enabled',
		);

		foreach ( $checkboxes as $key ) {
			$clean[ $key ] = empty( $input[ $key ] ) ? 0 : 1;
		}

		// 数字。
		$clean['description_length'] = isset( $input['description_length'] )
			? max( 20, min( 320, absint( $input['description_length'] ) ) )
			: $old['description_length'];

		$clean['og_image_id']    = isset( $input['og_image_id'] ) ? absint( $input['og_image_id'] ) : 0;
		$clean['schema_logo_id'] = isset( $input['schema_logo_id'] ) ? absint( $input['schema_logo_id'] ) : 0;

		// URL。
		if ( isset( $input['og_image_custom'] ) ) {
			$url                       = esc_url_raw( trim( (string) $input['og_image_custom'] ) );
			$clean['og_image_custom'] = $url;
		}

		// 文章类型。
		if ( isset( $input['sitemap_post_types'] ) && is_array( $input['sitemap_post_types'] ) ) {
			$allowed = array_keys( get_post_types( array( 'public' => true ), 'names' ) );
			$types   = array();
			foreach ( $input['sitemap_post_types'] as $type ) {
				$type = sanitize_key( (string) $type );
				if ( in_array( $type, $allowed, true ) ) {
					$types[] = $type;
				}
			}
			$clean['sitemap_post_types'] = $types;
		} else {
			$clean['sitemap_post_types'] = array();
		}

		// robots 规则。
		if ( isset( $input['robots_disallow'] ) ) {
			$raw  = is_scalar( $input['robots_disallow'] ) ? (string) $input['robots_disallow'] : '';
			$lines = preg_split( '/\r\n|\r|\n/', $raw );
			$out   = array();
			foreach ( (array) $lines as $line ) {
				$line = trim( $line );
				if ( '' === $line ) {
					continue;
				}
				// 只保留路径形式，避免注入任意指令。
				if ( '/' !== substr( $line, 0, 1 ) ) {
					$line = '/' . ltrim( $line, '/' );
				}
				$out[] = substr( $line, 0, 200 );
				if ( count( $out ) >= 30 ) {
					break;
				}
			}
			$clean['robots_disallow'] = implode( "\n", $out );
		}

		return $clean;
	}

	/**
	 * 加载后台资源。
	 *
	 * @param string $hook 当前后台页标识。
	 * @return void
	 */
	public static function enqueue( $hook ) {
		$is_settings = ( 'toplevel_page_' . self::PAGE === $hook );
		$is_editor   = in_array( $hook, array( 'post.php', 'post-new.php' ), true );

		if ( ! $is_settings && ! $is_editor ) {
			return;
		}

		wp_enqueue_media();

		wp_enqueue_style(
			'morn-seo-meta-kit-admin',
			MORN_SEO_META_KIT_URL . 'assets/css/admin.css',
			array(),
			MORN_SEO_META_KIT_VERSION
		);

		wp_enqueue_script(
			'morn-seo-meta-kit-admin',
			MORN_SEO_META_KIT_URL . 'assets/js/admin.js',
			array(),
			MORN_SEO_META_KIT_VERSION,
			true
		);

		$strings = array(
			'selectImage'    => __( '选择分享图', 'morn-seo-meta-kit' ),
			'useImage'       => __( '使用此图片', 'morn-seo-meta-kit' ),
			'titleTooLong'   => __( '标题偏长', 'morn-seo-meta-kit' ),
			'titleTooShort'  => __( '标题偏短', 'morn-seo-meta-kit' ),
			'descTooLong'    => __( '描述偏长', 'morn-seo-meta-kit' ),
			'descTooShort'   => __( '描述偏短', 'morn-seo-meta-kit' ),
			'noKeyword'      => __( '未填写焦点关键词', 'morn-seo-meta-kit' ),
			'keywordOk'      => __( '关键词已出现在标题中', 'morn-seo-meta-kit' ),
			'keywordMissing' => __( '关键词未出现在标题中', 'morn-seo-meta-kit' ),
			'bodyText'       => __( '正文摘要：', 'morn-seo-meta-kit' ),
		);

		$json = wp_json_encode( $strings );

		if ( false !== $json ) {
			// 用内联 JSON 替代 wp_localize_script，保留字符串类型且支持 JSON_HEX_TAG。
			wp_add_inline_script(
				'morn-seo-meta-kit-admin',
				'window.mornSeoMetaKitAdmin = ' . $json . ';',
				'before'
			);
		}

		if ( $is_editor ) {
			wp_enqueue_script(
				'morn-seo-meta-kit-score',
				MORN_SEO_META_KIT_URL . 'assets/js/seo-score.js',
				array(),
				MORN_SEO_META_KIT_VERSION,
				true
			);
		}
	}

	/**
	 * 输出后台提示。
	 *
	 * @return void
	 */
	public static function render_notices() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'toplevel_page_' . self::PAGE !== $screen->id ) {
			return;
		}

		$settings = morn_seo_meta_kit_get_settings();

		if ( $settings['noindex'] ) {
			echo '<div class="notice notice-warning"><p>' . esc_html__( '您已开启全站 noindex，搜索引擎将不再收录本站。请尽快关闭。', 'morn-seo-meta-kit' ) . '</p></div>';
		}
	}
}
