<?php
/**
 * 文章编辑页 SEO 设置框。
 *
 * @package MornRain\MornSeoMetaKit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SEO 元框：读写文章级 SEO 字段。
 */
class Morn_SEO_Meta_Kit_Metabox {

	/**
	 * 元框 ID。
	 */
	const BOX_ID = 'morn-seo-meta-box';

	/**
	 * 支持的文章类型。
	 *
	 * @var array
	 */
	private static $post_types = array( 'post', 'page' );

	/**
	 * 注册钩子。
	 *
	 * @return void
	 */
	public static function init() {
		add_filter( 'morn_seo_meta_kit_metabox_post_types', array( __CLASS__, 'filter_post_types' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_box' ) );
		add_action( 'save_post', array( __CLASS__, 'save' ), 10, 2 );
	}

	/**
	 * 提供可过滤的文章类型列表。
	 *
	 * @param array $post_types 文章类型。
	 * @return array
	 */
	public static function filter_post_types( $post_types ) {
		$types = get_post_types( array( 'public' => true ), 'names' );
		unset( $types['attachment'] );

		return array_values( $types );
	}

	/**
	 * 获取实际使用的文章类型。
	 *
	 * @return array
	 */
	private static function get_post_types() {
		/**
		 * 过滤 SEO 元框适用的文章类型。
		 *
		 * @param array $post_types 文章类型 slug 列表。
		 */
		$types = apply_filters( 'morn_seo_meta_kit_metabox_post_types', self::$post_types );

		return array_values( array_filter( array_map( 'sanitize_key', (array) $types ) ) );
	}

	/**
	 * 注册元框。
	 *
	 * @return void
	 */
	public static function add_meta_box() {
		foreach ( self::get_post_types() as $post_type ) {
			add_meta_box(
				self::BOX_ID,
				__( 'SEO 元信息', 'morn-seo-meta-kit' ),
				array( __CLASS__, 'render' ),
				$post_type,
				'normal',
				'high'
			);
		}
	}

	/**
	 * 渲染元框。
	 *
	 * @param WP_Post $post 当前文章。
	 * @return void
	 */
	public static function render( $post ) {
		wp_nonce_field( 'morn_seo_meta_kit_save', 'morn_seo_meta_kit_nonce' );

		$title       = get_post_meta( $post->ID, '_morn_seo_title', true );
		$description = get_post_meta( $post->ID, '_morn_seo_description', true );
		$keywords    = get_post_meta( $post->ID, '_morn_seo_keywords', true );
		$noindex     = get_post_meta( $post->ID, '_morn_seo_noindex', true );
		$exclude     = get_post_meta( $post->ID, '_morn_seo_exclude_sitemap', true );
		?>
		<div class="morn-seo-box">
			<p class="morn-field">
				<label for="morn-seo-title"><strong><?php echo esc_html__( '自定义标题', 'morn-seo-meta-kit' ); ?></strong></label>
				<input type="text" id="morn-seo-title" name="morn_seo_title" value="<?php echo esc_attr( (string) $title ); ?>" class="widefat" maxlength="200" />
				<span class="morn-counter" data-target="morn-seo-title" data-min="10" data-max="60">0</span>
				<span class="description"><?php echo esc_html__( '留空则使用站点标题模板。', 'morn-seo-meta-kit' ); ?></span>
			</p>

			<p class="morn-field">
				<label for="morn-seo-desc"><strong><?php echo esc_html__( '自定义描述', 'morn-seo-meta-kit' ); ?></strong></label>
				<textarea id="morn-seo-desc" name="morn_seo_description" rows="3" class="widefat" maxlength="320"><?php echo esc_textarea( (string) $description ); ?></textarea>
				<span class="morn-counter" data-target="morn-seo-desc" data-min="50" data-max="155">0</span>
				<span class="description"><?php echo esc_html__( '留空则自动截取摘要或正文。', 'morn-seo-meta-kit' ); ?></span>
			</p>

			<p class="morn-field">
				<label for="morn-seo-keywords"><strong><?php echo esc_html__( '焦点关键词', 'morn-seo-meta-kit' ); ?></strong></label>
				<input type="text" id="morn-seo-keywords" name="morn_seo_keywords" value="<?php echo esc_attr( (string) $keywords ); ?>" class="widefat" />
				<span class="description"><?php echo esc_html__( '多个关键词用英文逗号分隔。', 'morn-seo-meta-kit' ); ?></span>
			</p>

			<div class="morn-score" id="morn-seo-score">
				<div class="morn-score-row">
					<span class="morn-score-label"><?php echo esc_html__( '标题长度', 'morn-seo-meta-kit' ); ?></span>
					<span class="morn-score-bar"><i data-bar="title"></i></span>
				</div>
				<div class="morn-score-row">
					<span class="morn-score-label"><?php echo esc_html__( '描述长度', 'morn-seo-meta-kit' ); ?></span>
					<span class="morn-score-bar"><i data-bar="desc"></i></span>
				</div>
				<div class="morn-score-row">
					<span class="morn-score-label"><?php echo esc_html__( '关键词分布', 'morn-seo-meta-kit' ); ?></span>
					<span class="morn-score-hint" data-hint="keyword"><?php echo esc_html__( '未填写焦点关键词', 'morn-seo-meta-kit' ); ?></span>
				</div>
			</div>

			<p class="morn-field">
				<label>
					<input type="checkbox" name="morn_seo_noindex" value="1" <?php checked( ! empty( $noindex ) ); ?> />
					<?php echo esc_html__( '此页面设为 noindex（不索引）', 'morn-seo-meta-kit' ); ?>
				</label>
			</p>
			<p class="morn-field">
				<label>
					<input type="checkbox" name="morn_seo_exclude_sitemap" value="1" <?php checked( ! empty( $exclude ) ); ?> />
					<?php echo esc_html__( '从站点地图中排除此内容', 'morn-seo-meta-kit' ); ?>
				</label>
			</p>
		</div>
		<?php
	}

	/**
	 * 保存元框数据。
	 *
	 * @param int     $post_id 文章 ID。
	 * @param WP_Post $post    文章对象。
	 * @return void
	 */
	public static function save( $post_id, $post ) {
		// 自动草稿与修订不处理。
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}
		if ( ! isset( $_POST['morn_seo_meta_kit_nonce'] ) ) {
			return;
		}
		$nonce = sanitize_text_field( wp_unslash( $_POST['morn_seo_meta_kit_nonce'] ) );
		if ( ! wp_verify_nonce( $nonce, 'morn_seo_meta_kit_save' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$text_fields = array(
			'morn_seo_title'       => '_morn_seo_title',
			'morn_seo_description' => '_morn_seo_description',
			'morn_seo_keywords'    => '_morn_seo_keywords',
		);

		foreach ( $text_fields as $field => $meta_key ) {
			if ( ! isset( $_POST[ $field ] ) ) {
				continue;
			}
			$raw  = wp_unslash( $_POST[ $field ] );
			$raw  = is_scalar( $raw ) ? sanitize_text_field( (string) $raw ) : '';
			$meta = get_post_meta( $post_id, $meta_key, true );

			if ( '' === $raw ) {
				delete_post_meta( $post_id, $meta_key );
			} elseif ( (string) $meta !== $raw ) {
				update_post_meta( $post_id, $meta_key, $raw );
			}
		}

		$bool_fields = array(
			'morn_seo_noindex'           => '_morn_seo_noindex',
			'morn_seo_exclude_sitemap'   => '_morn_seo_exclude_sitemap',
		);

		foreach ( $bool_fields as $field => $meta_key ) {
			if ( ! isset( $_POST[ $field ] ) ) {
				delete_post_meta( $post_id, $meta_key );
			} else {
				$raw = sanitize_text_field( wp_unslash( $_POST[ $field ] ) );
				if ( '1' === $raw ) {
					update_post_meta( $post_id, $meta_key, '1' );
				}
			}
		}
	}
}
