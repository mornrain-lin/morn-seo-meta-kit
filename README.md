# Morn SEO Meta Kit

轻量级 SEO 元信息与社交卡片插件。为 WordPress 输出规范化的标题、描述、Open Graph、Twitter Card、canonical、hreflang 与 JSON-LD 结构化数据，并附带精简 XML 站点地图与 robots.txt 增强。

零外部资源、零远程请求、零第三方依赖。所有功能均在本地完成。

- 版本：1.0.0
- 需要 WordPress：6.0+
- 需要 PHP：7.4+
- 测试至：WordPress 6.6
- 许可证：MIT

## 特性

- **标题模板**：可视化模板变量，支持页面层级（`%page%`）拼接面包屑式标题。
- **描述生成**：优先使用自定义描述，其次摘要，最后自动截取正文；UTF-8 安全的字符级截断，不产生乱码。
- **社交卡片**：Open Graph 全套标签（含图片尺寸与替代文本）+ Twitter Card，文章特色图优先作为分享图。
- **canonical**：自动剥离分页参数与 utm/gclid/fbclid 等跟踪参数，保证 URL 唯一性。
- **hreflang**：开箱输出自引用 hreflang，可通过过滤器追加多语言版本。
- **JSON-LD**：Organization / WebSite / BreadcrumbList / Article / Author Person，以 `@graph` 统一输出，搜索引擎友好。
- **文章级 SEO 框**：自定义标题、描述、焦点关键词、noindex、站点地图排除，附原生 JS 实时评分（标题长度、描述长度、关键词分布）。
- **精简站点地图**：`/sitemap-seo.xml`，输出 `lastmod`、`changefreq`、`priority`，自动排除密码保护、noindex、手动排除的内容。
- **robots.txt 增强**：追加可配置 Disallow 规则与 Sitemap 行，遵守 WordPress 公开性设置。
- **性能友好**：站点地图 XML 缓存 6 小时，文章变更时自动失效；JS 使用 `defer` 且不依赖 jQuery。

## 安装

1. 将 `morn-seo-meta-kit` 目录上传到 `wp-content/plugins/`。
2. 在后台「插件」中启用「Morn SEO Meta Kit」。
3. 启用后会自动注册 `/sitemap-seo.xml` 重写规则（激活钩子中已自动刷新）。
4. 进入后台菜单「SEO 元信息」进行配置。

若使用 nginx 且重写规则未生效，手动加入：

```nginx
location ~ ^/sitemap-seo\.xml$ {
    rewrite ^/sitemap-seo\.xml$ /index.php?morn_seo_sitemap=1 last;
}
```

## 配置说明

后台路径：**SEO 元信息 → SEO 元信息工具**（顶层菜单，`manage_options` 权限）。

### 标题与描述

| 设置项 | 默认值 | 说明 |
| --- | --- | --- |
| 文章标题格式 | `%title% %sep% %site%` | 文章/页面的 `<title>` 模板。可用变量见下表。 |
| 首页标题格式 | `%title% %sep% %site%` | 站点首页的 `<title>` 模板。 |
| 标题分隔符 | `\|` | 替换 `%sep%`，最多 3 个字符。 |
| 输出 meta description | 启用 | 关闭后不输出 description 标签。 |
| 描述最大长度（字符） | `155` | 自动截断长度，范围 20~320。 |
| 输出 meta keywords | 关闭 | 开启后输出文章级焦点关键词。多数搜索引擎已忽略此标签。 |
| 全站 noindex | 关闭 | 开启后全站 `noindex, follow`，后台会显示黄色警告。**请谨慎使用。** |
| 归档页 noindex | 关闭 | 开启后分类/标签/日期归档页输出 noindex。 |
| 输出 canonical | 启用 | 关闭后完全不输出 canonical 标签。 |
| canonical 移除分页参数 | 启用 | 从 canonical 中移除 `paged`、`page` 参数。 |

**标题模板变量**

| 变量 | 含义 |
| --- | --- |
| `%title%` | 当前内容标题 |
| `%site%` | 站点标题 |
| `%sep%` | 自定义分隔符 |
| `%page%` | 页面层级（父页面标题链） |
| `%excerpt%` | 摘要 |
| `%category%` | 主分类名 |
| `%tag%` | 首个标签名 |
| `%author%` | 作者显示名 |
| `%year%` / `%day%` / `%month%` | 当前日期 |
| `%post_id%` | 文章 ID |
| `%search%` | 搜索关键词 |

### 社交卡片

| 设置项 | 默认值 | 说明 |
| --- | --- | --- |
| 启用 Open Graph | 启用 | 关闭后不输出任何 OG 标签。 |
| 默认 OG 类型 | `website` | 可选 `website` / `article` / `profile` / `book`。文章页会自动改为 `article`。 |
| OG 站点名称 | 空 | 留空则使用 WordPress 站点标题。 |
| OG 语言 | `zh_CN` | `og:locale` 值。 |
| 默认分享图（媒体库） | 未选择 | 点击「选择图片」从媒体库挑选。 |
| 默认分享图 URL | 空 | 填写的 URL **优先于**媒体库选择。 |
| Twitter @用户名 | 空 | 填 `@yourname` 或 `yourname`，自动补全 `@`。 |
| Twitter Card 类型 | `summary_large_image` | 可选 `summary` / `summary_large_image`。有分享图时自动使用大图卡。 |

**分享图优先级**：文章特色图 > 自定义 URL > 媒体库默认图。

### 多语言 hreflang

| 设置项 | 默认值 | 说明 |
| --- | --- | --- |
| 启用 hreflang | 关闭 | 开启后输出当前页面的自引用 hreflang。 |
| 默认语言代码 | 空 | 留空则使用 WordPress 站点语言（如 `zh_CN`）。 |

若站点已安装多语言插件，通过 `morn_seo_meta_kit_hreflang` 过滤器追加其他语言版本。

### JSON-LD 结构化数据

| 设置项 | 默认值 | 说明 |
| --- | --- | --- |
| 启用 JSON-LD 输出 | 启用 | 总开关。 |
| 输出 Organization | 启用 | 含 name、url、logo、description。 |
| 输出 WebSite | 启用 | 含 SearchAction（站内搜索）。 |
| 输出 BreadcrumbList | 启用 | 首页不输出。 |
| 输出 Article | 启用 | 含 headline、datePublished/Modified、author、image、articleSection。 |
| 输出 Author Person | 启用 | 关闭后 Article 的 author 仅引用 `@id`。 |
| 组织 Logo（媒体库） | 未选择 | 作为 Organization 的 logo。 |

### 站点地图与 robots.txt

| 设置项 | 默认值 | 说明 |
| --- | --- | --- |
| 启用精简站点地图 | 启用 | 关闭后 `/sitemap-seo.xml` 返回 404。 |
| 站点地图包含的文章类型 | `post`, `page` | 可勾选任意公开文章类型。 |
| 增强 robots.txt | 启用 | 追加 Disallow 与 Sitemap 行。 |
| 附加 Disallow 规则 | `/wp-admin/`<br>`/wp-admin/admin-ajax.php` | 每行一条。**只接受以 `/` 开头的路径**，其他内容会被自动纠正，防止注入任意 robots 指令。最多 30 行。 |

站点地图访问地址：`https://你的域名/sitemap-seo.xml`

**changefreq 计算规则**（按最后修改时间距今天数）

| 距今 | changefreq | priority |
| --- | --- | --- |
| ≤ 2 天 | `daily` | 0.9 |
| ≤ 14 天 | `weekly` | 0.8 |
| ≤ 60 天 | `monthly` | 0.6 |
| > 60 天 | `yearly` | 0.4 |

首页固定为 `changefreq=daily`、`priority=1.0`；普通页面为 `priority=0.6`；站点首页页面为 `priority=1.0`。

### 文章编辑页 SEO 框

出现在文章与页面编辑页右侧「SEO 元信息」面板：

- **自定义标题**：留空则使用站点标题模板。最多 200 字符。
- **自定义描述**：留空则自动取摘要或正文。最多 320 字符。
- **焦点关键词**：多个关键词用英文逗号分隔。
- **实时评分**：标题建议 10~60 字符，描述建议 50~155 字符，关键词应出现在标题中。
- **此页面设为 noindex**：单篇不索引，同时从站点地图中排除。
- **从站点地图中排除此内容**：仅影响站点地图，不影响索引。

## Hook 列表

### 动作（do_action）

本插件不定义新的 `do_action`。它挂接在以下核心动作上：

| Hook | 回调 | 优先级 | 用途 |
| --- | --- | --- | --- |
| `plugins_loaded` | `morn_seo_meta_kit_boot` | 10 | 加载文本域并启动所有模块。 |
| `plugins_loaded` | `morn_seo_meta_kit_install` | 5 | 版本号变化时执行安装/升级逻辑（每版仅一次）。 |
| `init` | `Morn_SEO_Meta_Kit_Sitemap::register_rewrites` | 10 | 注册 `/sitemap-seo.xml` 重写规则。 |
| `template_redirect` | `Morn_SEO_Meta_Kit_Sitemap::maybe_render` | 0 | 输出站点地图并终止请求。 |
| `wp_head` | `Morn_SEO_Meta_Kit_Frontend::render` | 1 | 输出 title/description/OG/canonical/hreflang。 |
| `wp_head` | `Morn_SEO_Meta_Kit_Schema::render` | 20 | 输出 JSON-LD。 |
| `admin_menu` | `Morn_SEO_Meta_Kit_Admin::add_menu` | 10 | 注册后台菜单。 |
| `admin_init` | `Morn_SEO_Meta_Kit_Admin::register_settings` | 10 | 注册设置项。 |
| `add_meta_boxes` | `Morn_SEO_Meta_Kit_Metabox::add_meta_box` | 10 | 注册 SEO 元框。 |
| `save_post` | `Morn_SEO_Meta_Kit_Metabox::save` | 10 | 保存文章级 SEO 字段。 |
| `save_post` | `Morn_SEO_Meta_Kit_Cleanup::flush_cache` | 10 | 站点地图缓存失效。 |
| `switch_theme` | `Morn_SEO_Meta_Kit_Cleanup::flush_cache` | 10 | 切换主题时失效缓存。 |

### 过滤器（apply_filters）

| Hook | 签名 | 说明 |
| --- | --- | --- |
| `morn_seo_meta_kit_settings` | `apply_filters( 'morn_seo_meta_kit_settings', array $settings )` | 过滤合并默认值后的全部设置。 |
| `morn_seo_meta_kit_render_template` | `apply_filters( 'morn_seo_meta_kit_render_template', string $output, string $template, array $extra )` | 过滤标题模板渲染结果。 |
| `morn_seo_meta_kit_head_tags` | `apply_filters( 'morn_seo_meta_kit_head_tags', array $tags, array $context, array $settings )` | 过滤最终 `<head>` 标签数组（每项为一个完整标签 HTML）。 |
| `morn_seo_meta_kit_hreflang` | `apply_filters( 'morn_seo_meta_kit_hreflang', array $alternates, array $context, string $lang )` | 追加多语言 hreflang。 |
| `morn_seo_meta_kit_schema_graph` | `apply_filters( 'morn_seo_meta_kit_schema_graph', array $graph, array $context )` | 追加 JSON-LD 节点。 |
| `morn_seo_meta_kit_schema_data` | `apply_filters( 'morn_seo_meta_kit_schema_data', array $data, array $context )` | 过滤完整 JSON-LD 数据（`@context` + `@graph`）。 |
| `morn_seo_meta_kit_sitemap_urls` | `apply_filters( 'morn_seo_meta_kit_sitemap_urls', array $urls, array $types )` | 追加/过滤站点地图 URL 条目。 |
| `morn_seo_meta_kit_sitemap_xml` | `apply_filters( 'morn_seo_meta_kit_sitemap_xml', string $xml, array $urls )` | 过滤完整站点地图 XML 文本。 |
| `morn_seo_meta_kit_robots_lines` | `apply_filters( 'morn_seo_meta_kit_robots_lines', array $lines, string $output, bool $public )` | 追加 robots.txt 指令行。 |
| `morn_seo_meta_kit_metabox_post_types` | `apply_filters( 'morn_seo_meta_kit_metabox_post_types', array $post_types )` | 过滤 SEO 元框适用的文章类型。 |

### 使用示例

追加自定义 JSON-LD 节点：

```php
add_filter( 'morn_seo_meta_kit_schema_graph', function ( $graph, $context ) {
    $graph[] = array(
        '@type' => 'WebPage',
        '@id'   => home_url( '/#webpage' ),
        'url'   => home_url( '/' ),
        'name'  => get_bloginfo( 'name' ),
    );

    return $graph;
}, 10, 2 );
```

为多语言站点追加 hreflang：

```php
add_filter( 'morn_seo_meta_kit_hreflang', function ( $alternates, $context ) {
    if ( is_singular() ) {
        $alternates[] = array(
            'lang' => 'en',
            'url'  => 'https://example.com/en/' . get_post_field( 'post_name', get_queried_object_id() ),
        );
    }

    return $alternates;
}, 10, 2 );
```

## FAQ

**Q：标题不生效，或出现两个 `<title>`？**
A：本插件在 `wp_head` 优先级 1 移除核心的 `_wp_render_title_tag`、`wp_robots` 与 `rel_canonical`，避免重复输出。若仍有重复，说明其它 SEO 插件也在输出，请关闭它们的 title 功能。

**Q：站点地图 404？**
A：确认「启用精简站点地图」已开启，然后到「设置 → 固定链接」点一次「保存」以刷新重写规则。nginx 用户还需添加上文给出的 rewrite 配置。

**Q：为什么我的自定义标题没显示？**
A：确认文章编辑页的「自定义标题」不为空。本插件对首页与文章页完全接管标题输出；分类、标签等归档页保留 WordPress 核心行为，通过 `%title%` 变量控制格式。

**Q：站点地图里为什么少了某些文章？**
A：以下内容会被自动排除：密码保护文章、草稿/待发布、设置了 noindex 的文章、勾选了「从站点地图中排除」的文章。

**Q：`og_image_custom` 填了没生效？**
A：文章设置了特色图片时，特色图片优先级高于自定义 URL。取消文章特色图片后才会使用自定义 URL。

**Q：会和其他 SEO 插件冲突吗？**
A：会。本插件与 Yoast、Rank Math 等插件功能重叠，同时启用会导致 title/description/OG 重复输出。建议只启用其一。

**Q：站点设为「不公开索引」后站点地图会怎样？**
A：返回 HTTP 403 并提示站点不可公开索引，同时前端输出 `noindex, nofollow`。

## 目录说明

```
morn-seo-meta-kit/
├── morn-seo-meta-kit.php      # 主文件：插件头、启动、激活/停用钩子
├── uninstall.php              # 卸载清理（仅清理本插件自有数据）
├── README.md
├── LICENSE                    # MIT
├── CHANGELOG.md
├── .gitignore
├── .gitattributes
├── assets/
│   ├── css/admin.css          # 后台样式（评分条、字数计数器、媒体选择器）
│   └── js/
│       ├── admin.js           # 媒体库选择（基于 wp.media）
│       └── seo-score.js       # 编辑页实时评分（原生 JS）
└── includes/
    ├── functions.php          # 设置读取、模板渲染、上下文构建、UTF-8 安全截断
    ├── class-admin.php        # Settings API 设置页、字段渲染、sanitize、资源加载
    ├── class-metabox.php      # 文章级 SEO 元框与保存
    ├── class-schema.php       # JSON-LD 结构化数据
    ├── class-sitemap.php      # /sitemap-seo.xml 生成与缓存
    ├── class-cleanup.php      # robots.txt 增强与缓存失效
    └── class-frontend.php     # 前台 head 标签输出
```

**存储的选项**

| 选项名 | 类型 | 说明 |
| --- | --- | --- |
| `morn_seo_meta_kit_settings` | array | 插件全部设置。 |
| `morn_seo_meta_kit_settings_sitemap_version` | integer | 站点地图缓存版本号，自动维护。 |

**文章元字段**

`_morn_seo_title`、`_morn_seo_description`、`_morn_seo_keywords`、`_morn_seo_noindex`、`_morn_seo_exclude_sitemap`

## 卸载说明

在后台「插件」中点击「删除」并确认卸载时，`uninstall.php` 会执行以下清理：

- 删除选项 `morn_seo_meta_kit_settings`
- 删除选项 `morn_seo_meta_kit_settings_sitemap_version`
- 删除所有 `_transient_morn_seo_meta_kit_sitemap_xml*` 缓存
- 删除 5 个文章元字段（见上表）
- 移除 `/sitemap-seo.xml` 对应的重写规则

**不会删除**：任何文章、页面、附件、用户、评论或其他插件的数据。

## License

MIT License
Copyright (c) 2026 MornRain

详见 [LICENSE](LICENSE)。
