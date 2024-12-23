=== Apache Status & Info ===
Contributors: PierreLannoy, hosterra
Tags: apache, htaccess, server-status, server-info, stackdriver
Requires at least: 6.2
Requires PHP: 8.1
Tested up to: 6.7
Stable tag: 3.1.0
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Apache server-info and server-status monitoring right in your WordPress admin.

== Description ==
When you want to use [server-info](https://httpd.apache.org/docs/2.4/en/mod/mod_info.html) and/or [server-status](https://httpd.apache.org/docs/current/en/mod/mod_status.html) with Apache server, you must modify (right in your `.htaccess` file) the rewrite rules generated by WordPress. And you must do it each time WordPress modify and regenerates the rules.
**Apache Status & Info** do it for you, without you having to intervene. Just activate it and that's it!

In addition to this, **Apache Status & Info** allows you to monitor your Apache configuration and settings, right in your WordPress admin (see screenshots for example).

> **Apache Status & Info** is part of [PerfOps One](https://perfops.one/), a suite of free and open source WordPress plugins dedicated to observability and operations performance.

**Apache Status & Info** is a free and open source plugin for WordPress. It integrates many other free and open source works (as-is or modified). Please, see 'about' tab in the plugin settings to see the details.

= Support =

This plugin is free and provided without warranty of any kind. Use it at your own risk, I'm not responsible for any improper use of this plugin, nor for any damage it might cause to your site. Always backup all your data before installing a new plugin.

Anyway, I'll be glad to help you if you encounter issues when using this plugin. Just use the support section of this plugin page.

= Privacy =

This plugin, as any piece of software, is neither compliant nor non-compliant with privacy laws and regulations. It is your responsibility to use it with respect for the personal data of your users and applicable laws.

This plugin doesn't set any cookie in the user's browser.

This plugin doesn't handle personally identifiable information (PII).

= Donation =

If you like this plugin or find it useful and want to thank me for the work done, please consider making a donation to [La Quadrature Du Net](https://www.laquadrature.net/en) or the [Electronic Frontier Foundation](https://www.eff.org/) which are advocacy groups defending the rights and freedoms of citizens on the Internet. By supporting them, you help the daily actions they perform to defend our fundamental freedoms!

== Installation ==

= From your WordPress dashboard =

1. Visit 'Plugins > Add New'.
2. Search for 'Apache Status & Info'.
3. Click on the 'Install Now' button.
4. Activate Apache Status & Info.

= From WordPress.org =

1. Download Apache Status & Info.
2. Upload the `htaccess-server-info-server-status` directory to your `/wp-content/plugins/` directory, using your favorite method (ftp, sftp, scp, etc...).
3. Activate Apache Status & Info from your Plugins page.

= Once Activated =

1. Visit 'PerfOps One > Control Center > Apache Status & Info' in the left-hand menu of your WP Admin to adjust settings.
2. Enjoy!

== Frequently Asked Questions ==

= What are the requirements for this plugin to work? =

You need **WordPress 5.2** and at least **PHP 7.2**.

= Can this plugin work on multisite? =

No. Rewrite rules are not fully supported by WordPress multisite.

= Where can I get support? =

Support is provided via the official [support page](https://wordpress.org/support/plugin/htaccess-server-info-server-status).

= Where can I report a bug? =
 
You can report bugs and suggest ideas via the [GitHub issue tracker](https://github.com/Pierre-Lannoy/wp-htaccess-server-info-server-status/issues) of the plugin.

== Changelog ==

Please, see [full changelog](https://perfops.one/apache-status-info-changelog).

== Upgrade Notice ==

== Screenshots ==

1. Settings Page
2. Live Status Insights
3. Apache Settings, Modules And Hooks.
4. Apache Effective Configuration Files