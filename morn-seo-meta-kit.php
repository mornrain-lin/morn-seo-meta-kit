<?php
/**
 * Plugin Name: Morn SEO Meta Kit
 * Plugin URI: https://github.com/mornrain/morn-seo-meta-kit
 * Description: 轻量级 SEO 元信息与社交卡片插件。提供标题/描述模板、Open Graph、Twitter Card、canonical、hreflang、JSON-LD 结构化数据、文章级 SEO 框、精简 XML 站点地图与 robots.txt 增强。零外部资源、零远程请求。
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Tested up to: 6.6
 * Author: MornRain
 * Author URI: https://github.com/mornrain
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 * Text Domain: morn-seo-meta-kit
 * Domain Path: /languages
 *
 * @package MornRain\MornSeoMetaKit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 插件主版本号。
 */
define( 'MORN_SEO_META_KIT_VERSION', '1.0.0' );

/**
 * 插件主文件路径。
 */
define( 'MORN_SEO_META_KIT_FILE', __FILE__ );

/**
 * 插件目录路径（末尾带斜杠）。
 */
define( 'MORN_SEO_META_KIT_DIR', plugin_dir_path( __FILE__ ) );

/**
 * 插件目录 URL（末尾带斜杠）。
 */
define( 'MORN_SEO_META_KIT_URL', plugin_dir_url( __FILE__ ) );

/**
 * 插件选项名。
 */
define( 'MORN_SEO_META_KIT_OPTION', 'morn_seo_meta_kit_settings' );

require_once MORN_SEO_META_KIT_DIR . 'includes/functions.php';
require_once MORN_SEO_META_KIT_DIR . 'includes/class-cleanup.php';
require_once MORN_SEO_META_KIT_DIR . 'includes/class-admin.php';
require_once MORN_SEO_META_KIT_DIR . 'includes/class-metabox.php';
require_once MORN_SEO_META_KIT_DIR . 'includes/class-schema.php';
require_once MORN_SEO_META_KIT_DIR . 'includes/class-sitemap.php';
require_once MORN_SEO_META_KIT_DIR . 'includes/class-frontend.php';

/**
 * 启动插件。
 *
 * @return void
 */
function morn_seo_meta_kit_boot() {
	// 加载文本域。
	load_plugin_textdomain( 'morn-seo-meta-kit', false, dirname( plugin_basename( MORN_SEO_META_KIT_FILE ) ) . '/languages' );

	Morn_SEO_Meta_Kit_Cleanup::init();
	Morn_SEO_Meta_Kit_Admin::init();
	Morn_SEO_Meta_Kit_Metabox::init();
	Morn_SEO_Meta_Kit_Schema::init();
	Morn_SEO_Meta_Kit_Sitemap::init();
	Morn_SEO_Meta_Kit_Frontend::init();
}
add_action( 'plugins_loaded', 'morn_seo_meta_kit_boot' );

/**
 * 激活钩子：注册重写规则并刷新。
 *
 * @return void
 */
function morn_seo_meta_kit_activate() {
	Morn_SEO_Meta_Kit_Sitemap::register_rewrites();
	flush_rewrite_rules();

	morn_seo_meta_kit_install();
}
register_activation_hook( __FILE__, 'morn_seo_meta_kit_activate' );

/**
 * 安装 / 升级例程：仅在版本号变化时执行一次。
 *
 * 通过独立的版本选项判断，避免每次请求都跑安装逻辑。
 *
 * @return void
 */
function morn_seo_meta_kit_install() {
	$installed = get_option( 'morn_seo_meta_kit_version', '' );

	if ( MORN_SEO_META_KIT_VERSION === $installed ) {
		return;
	}

	// 首次安装：初始化站点地图缓存版本号。
	if ( '' === $installed ) {
		add_option( MORN_SEO_META_KIT_OPTION . '_sitemap_version', 1, '', false );
	}

	update_option( 'morn_seo_meta_kit_version', MORN_SEO_META_KIT_VERSION, false );
}
add_action( 'plugins_loaded', 'morn_seo_meta_kit_install', 5 );

/**
 * 停用钩子：刷新重写规则。
 *
 * @return void
 */
function morn_seo_meta_kit_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'morn_seo_meta_kit_deactivate' );
