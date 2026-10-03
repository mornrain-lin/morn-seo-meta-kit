<?php
/**
 * 卸载清理脚本。
 *
 * 仅删除本插件自身创建的选项与文章级SEO 元字段。
 * 不删除任何文章、页面、附件或用户数据。
 *
 * @package MornRain\MornSeoMetaKit
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// 插件设置主选项。
delete_option( 'morn_seo_meta_kit_settings' );

// 站点地图版本号与安装版本标记。
delete_option( 'morn_seo_meta_kit_settings_sitemap_version' );
delete_option( 'morn_seo_meta_kit_version' );

// 站点地图 XML 缓存（清理所有带前缀的 transient）。
global $wpdb;

$morn_cache_like = $wpdb->esc_like( '_transient_morn_seo_meta_kit_sitemap_xml' ) . '%';
$morn_timeout_like = $wpdb->esc_like( '_transient_timeout_morn_seo_meta_kit_sitemap_xml' ) . '%';

$morn_sql = $wpdb->prepare(
	"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
	$morn_cache_like,
	$morn_timeout_like
);

$morn_rows = $wpdb->get_col( $morn_sql );

if ( is_array( $morn_rows ) ) {
	foreach ( $morn_rows as $morn_option_name ) {
		delete_option( $morn_option_name );
	}
}

// 文章级 SEO 元字段（仅插件自身创建的 5 个键）。
$morn_meta_keys = array(
	'_morn_seo_title',
	'_morn_seo_description',
	'_morn_seo_keywords',
	'_morn_seo_noindex',
	'_morn_seo_exclude_sitemap',
);

$morn_meta_like = implode( ', ', array_fill( 0, count( $morn_meta_keys ), '%s' ) );

$morn_meta_sql = $wpdb->prepare(
	"DELETE FROM {$wpdb->postmeta} WHERE meta_key IN ( {$morn_meta_like} )", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- 占位符已由 prepare 生成。
	$morn_meta_keys
);

$wpdb->query( $morn_meta_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- 上面已 prepare。

// 清理重写规则。
$morn_seo_meta_kit_rewrite = get_option( 'rewrite_rules' );

if ( is_array( $morn_seo_meta_kit_rewrite ) ) {
	foreach ( $morn_seo_meta_kit_rewrite as $morn_rule => $morn_target ) {
		if ( false !== strpos( $morn_target, 'morn_seo_sitemap' ) ) {
			unset( $morn_seo_meta_kit_rewrite[ $morn_rule ] );
		}
	}

	update_option( 'rewrite_rules', $morn_seo_meta_kit_rewrite );
}
