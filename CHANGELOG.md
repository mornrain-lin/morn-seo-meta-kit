# Changelog

本项目遵循 [Semantic Versioning](https://semver.org/lang/zh-CN/)。

## [1.0.0] - 2026-10-02

### 新增

- 标题模板系统，支持 `%title%`、`%site%`、`%sep%`、`%page%`、`%excerpt%`、`%category%`、`%tag%`、`%author%`、`%year%`、`%day%`、`%month%`、`%post_id%`、`%search%` 变量。
- meta description 输出，支持自定义长度上限（20~320 字符）与 UTF-8 安全的智能截断。
- 可选的 meta keywords 输出。
- Open Graph 全套标签：locale、type、title、site_name、url、description、image、image:width/height/alt、article:published_time/modified_time/author。
- Twitter Card 输出，支持 `summary` 与 `summary_large_image`，可选 `@用户名`。
- 分享图三级优先级：文章特色图 > 自定义 URL > 媒体库默认图。
- canonical 输出，自动剥离分页参数与常见跟踪参数（utm_*、gclid、fbclid、ref）。
- hreflang 自引用输出，支持多语言插件扩展。
- JSON-LD 结构化数据：Organization、WebSite（含 SearchAction）、BreadcrumbList、Article、Author Person，以 `@graph` 形式统一输出。
- 文章/页面编辑页 SEO 元框：自定义标题、描述、焦点关键词、noindex、站点地图排除。
- 编辑页实时评分（原生 JS）：标题长度、描述长度、关键词是否出现在标题中。
- 精简 XML 站点地图 `/sitemap-seo.xml`，仅输出 post/page（可配置），自动排除密码保护、noindex、手动排除的内容。
- 站点地图 XML 缓存（6 小时 transient），文章变更时通过版本号自动失效。
- robots.txt 增强：追加可配置 Disallow 规则与 Sitemap 行。
- `nocache_headers` 与 `X-Robots-Tag: noindex` 头，站点设为不公开时返回 403。
- 后台设置页基于 Settings API，全字段白名单校验。
- 站点地图采用完整重写规则输出，激活时自动刷新重写规则。

### 安全

- 所有设置项经 `sanitize_callback` 白名单校验，枚举值限定在允许列表内。
- robots 规则仅接受以 `/` 开头的路径形式，阻断任意指令注入。
- 文章元框保存执行 nonce 校验与 `edit_post` 权限校验。
- 所有输出使用 `esc_html()` / `esc_attr()` / `esc_url()` / `esc_textarea()` 转义。
- JSON-LD 使用 `JSON_HEX_TAG` 编码，尖括号与 `&` 不会破坏脚本块。
- 卸载脚本仅清理本插件自有选项与 5 个自有元字段，不触碰文章与附件。

### 兼容

- PHP 7.4 ~ 8.3 语法兼容，未使用 enum、readonly、构造器属性提升、命名参数等 PHP 8 独有特性。
- WordPress 6.0 ~ 6.6。
- 零外部资源：不引用任何 CDN、远程 API 或第三方脚本。
