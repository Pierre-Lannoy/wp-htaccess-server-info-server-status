# Apache Status & Info
[![version](https://badgen.net/github/release/Pierre-Lannoy/wp-htaccess-server-info-server-status/)](https://wordpress.org/plugins/htaccess-server-info-server-status/)
[![php](https://badgen.net/badge/php/7.1+/green)](https://wordpress.org/plugins/htaccess-server-info-server-status/)
[![wordpress](https://badgen.net/badge/wordpress/5.0+/green)](https://wordpress.org/plugins/htaccess-server-info-server-status/)
[![license](https://badgen.net/github/license/Pierre-Lannoy/wp-htaccess-server-info-server-status/)](/license.txt)

**Apache Status & Info** is a WordPress plugin which generates specific rewrite rules for [server-info](https://httpd.apache.org/docs/2.4/en/mod/mod_info.html) and [server-status](https://httpd.apache.org/docs/current/en/mod/mod_status.html) Apache modules.

When you want to use [server-info](https://httpd.apache.org/docs/2.4/en/mod/mod_info.html) and/or [server-status](https://httpd.apache.org/docs/current/en/mod/mod_status.html) with Apache server, you must modify (right in your `.htaccess` file) the rewrite rules generated by WordPress. And you must do it each time WordPress modify and regenerates the rules.
**Apache Status & Info** do it for you, without you having to intervene. Just activate it and that's it!

See [WordPress directory page](https://wordpress.org/plugins/htaccess-server-info-server-status/).

## Installation

### WordPress method (recommended)

1. From your WordPress dashboard, visit _Plugins | Add New_.
2. Search for 'Apache Status & Info'.
3. Click on the 'Install Now' button.

You can now activate **Apache Status & Info** from your _Plugins_ page.
 
## Contributions

If you find bugs, have good ideas to make this plugin better, you're welcome to submit issues or PRs in this [GitHub repository](https://github.com/Pierre-Lannoy/wp-htaccess-server-info-server-status).

Before submitting an issue or a pull request, please read the [contribution guidelines](CONTRIBUTING.md).

> ⚠️ The `master` branch is the current development state of the plugin. If you want a stable, production-ready version, please pick the last official [release](https://github.com/Pierre-Lannoy/wp-htaccess-server-info-server-status/releases).

## Smoke tests
[![WP compatibility](https://plugintests.com/plugins/htaccess-server-info-server-status/wp-badge.svg)](https://plugintests.com/plugins/htaccess-server-info-server-status/latest)
[![PHP compatibility](https://plugintests.com/plugins/htaccess-server-info-server-status/php-badge.svg)](https://plugintests.com/plugins/htaccess-server-info-server-status/latest)