=== Elegance Links Redirect ===
Contributors: didoivanov
Tags: link cloaking, pretty links, redirect, 301, click tracking, geo redirect, device redirect
Requires at least: 5.5
Tested up to: 6.5
Requires PHP: 7.2
Stable tag: 1.0.1
License: GPLv2 or later

Cloak ugly URLs behind pretty slugs (like /go or /play), send 301/302/307/308 redirects, branch by country or device, and track every click.

== Description ==

Elegance Links Redirect lets you create short, branded links on your own domain and send visitors wherever you want. It is a lightweight alternative to PrettyLinks with first-class support for dynamic, conditional redirects.

Features:

* Pretty slug URLs such as `https://yoursite.com/go` or `/play`.
* Configurable redirect status: 301, 302, 303, 307, 308.
* Dynamic rules that branch by visitor country (ISO 3166-1 alpha-2) or device type (desktop / mobile / tablet / bot).
* Click tracking with IP address, country, city, device, browser, OS, referrer, destination URL and timestamp.
* Per-link stats dashboard with top countries, devices and browsers.
* Full WordPress multisite support: each site gets its own isolated set of tables (links, rules, clicks). Network-activation provisions tables on every existing site, and tables are automatically created on newly created sites and dropped when a site is deleted.

== Installation ==

1. Upload the `elegance-links-redirect` folder to `wp-content/plugins/`.
2. Activate "Elegance Links Redirect" in the Plugins menu.
3. Visit **Elegance Links → Add New** to create your first pretty link.
4. Make sure your WordPress permalinks are not set to "Plain" (pretty permalinks are required for slug routing).

== Privacy ==

The plugin stores IP addresses and user agent data of visitors who click tracked links. The default geolocation provider is the public ip-api.com service; swap it via the `elr_geo_provider` / `elr_geo_lookup_result` filters if you prefer a self-hosted database.

== Changelog ==

= 1.0.1 =
* Fix fatal deprecation chain on every wp-admin page on PHP 8 / WP 6.9 (add_submenu_page was being called with null parent).
* Reject slugs that collide with WP reserved paths, existing posts/pages/CPTs, taxonomy terms, or author nicenames.
* Register a dedicated rewrite rule per active slug instead of a catch-all, so ELR no longer intercepts unrelated URLs such as /wp-json or sitemaps.
* Link Stats menu now works when clicked directly and shows an overview of all links with hit counts.
* Edit an existing dynamic redirect rule from the rules table (previously delete-only).
* Country/device autocomplete on the rule Match Value field (ISO 3166-1 alpha-2 codes and desktop/mobile/tablet/bot).

= 1.0.0 =
* Initial release.
