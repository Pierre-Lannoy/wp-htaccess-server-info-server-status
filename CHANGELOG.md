# Changelog
All notable changes to **Apache Status & Info** are documented in this *changelog*.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and **Apache Status & Info** adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [3.0.0] - 2024-05-28

### Added
- [BC] To enable installation on more heterogeneous platforms, the plugin now adapts its internal logging mode to already loaded libraries.

### Changed
- Updated DecaLog SDK from version 4.1.0 to version 5.0.0.

### Fixed
- PHP error with some plugins like Woocommerce Paypal Payments.

## [2.15.0] - 2024-05-07

### Changed
- The plugin now adapts its requirements to the PSR-3 loaded version.

## [2.14.2] - 2024-05-04

### Fixed
- PHP error when DecaLog is not installed.

## [2.14.1] - 2024-05-04

### Changed
- Updated DecaLog SDK from version 3.0.0 to version 4.1.0.
- Minimal required WordPress version is now 6.2.

## [2.14.0] - 2024-03-02

### Added
- Compatibility with WordPress 6.5.

### Changed
- Minimal required WordPress version is now 6.1.
- Minimal required PHP version is now 8.1.

## [2.13.0] - 2023-10-25

### Added
- Compatibility with WordPress 6.4.

### Fixed
- Typos in `CHANGELOG.md` file.

## [2.12.0] - 2023-07-12

### Added
- Compatibility with WordPress 6.3.

### Changed
- The color for `shmop` test in Site Health is now gray to not worry to much about it (was previously orange).

## [2.11.1] - 2023-03-02

### Fixed
- [SEC002] CSRF vulnerability / [CVE-2023-27444](https://www.cve.org/CVERecord?id=CVE-2023-27444) (thanks to [Mika](https://patchstack.com/database/researcher/5ade6efe-f495-4836-906d-3de30c24edad) from [Patchstack](https://patchstack.com)).

## [2.11.0] - 2023-02-24

The developments of PerfOps One suite, of which this plugin is a part, is now sponsored by [Hosterra](https://hosterra.eu).

Hosterra is a web hosting company I founded in late 2022 whose purpose is to propose web services operating in a European data center that is water and energy efficient and ensures a first step towards GDPR compliance.

This sponsoring is a way to keep PerfOps One plugins suite free, open source and independent.

### Added
- Compatibility with WordPress 6.2.

### Changed
- Improved loading by removing unneeded jQuery references in public rendering (thanks to [Kishorchand](https://github.com/Kishorchandth)).

### Fixed
- In some edge-cases, detecting IP may produce PHP deprecation warnings (thanks to [YR Chen](https://github.com/stevapple)).

## [2.10.0] - 2022-10-06

### Added
- Compatibility with WordPress 6.1.

## [2.9.0] - 2022-04-20

### Added
- Compatibility with WordPress 6.0.

### Changed
- Site Health page now presents a much more realistic test about object caching.
- Updated DecaLog SDK from version 2.0.2 to version 3.0.0.

### Removed
- Donate link in `readme.txt` as it was outdated.

## [2.8.1] - 2022-01-17

### Fixed
- The Site Health page may launch deprecated tests.

## [2.8.0] - 2022-01-17

### Added
- Compatibility with PHP 8.1.

### Changed
- Updated DecaLog SDK from version 2.0.0 to version 2.0.2.
- Updated PerfOps One library from 2.2.1 to 2.2.2.
- Refactored cache mechanisms to fully support Redis and Memcached.
- Improved bubbles display when width is less than 500px (thanks to [Pat Ol](https://profiles.wordpress.org/pasglop/)).
- The tables headers have now a better contrast (thanks to [Paul Bonaldi](https://profiles.wordpress.org/bonaldi/)).

### Fixed
- Object caching method may be wrongly detected in Site Health status (thanks to [freshuk](https://profiles.wordpress.org/freshuk/)).
- The console menu may display an empty screen (thanks to [Renaud Pacouil](https://www.laboiteare.fr)).
- There may be name collisions with internal APCu cache.

## [2.7.0] - 2021-12-07

### Added
- Compatibility with WordPress 5.9.
- New button in settings to install recommended plugins.
- The available hooks (filters and actions) are now described in `HOOKS.md` file.

### Changed
- Improved update process on high-traffic sites to avoid concurrent resources accesses.
- Better publishing frequency for metrics.
- Updated labels and links in plugins page.
- Updated the `README.md` file.

### Fixed
- Country translation with i18n module may be wrong.
- There's typos in `CHANGELOG.md`.

## [2.6.0] - 2021-09-07

### Added
- It's now possible to hide the main PerfOps One menu via the `poo_hide_main_menu` filter or each submenu via the `poo_hide_analytics_menu`, `poo_hide_consoles_menu`, `poo_hide_insights_menu`, `poo_hide_tools_menu`, `poo_hide_records_menu` and `poo_hide_settings_menu` filters (thanks to [Jan Thiel](https://github.com/JanThiel)).

### Changed
- Updated DecaLog SDK from version 1.2.0 to version 2.0.0.

### Fixed
- There may be name collisions for some functions if version of WordPress is lower than 5.6.
- The main PerfOps One menu is not hidden when it doesn't contain any items (thanks to [Jan Thiel](https://github.com/JanThiel)).
- In some very special conditions, the plugin may be in the default site language rather than the user's language.
- The PerfOps One menu builder is not compatible with Admin Menu Editor plugin (thanks to [dvokoun](https://wordpress.org/support/users/dvokoun/)).

## [2.5.1] - 2021-08-11

### Changed
- New redesigned UI for PerfOps One plugins management and menus (thanks to [Loïc Antignac](https://github.com/webaxones), [Paul Bonaldi](https://profiles.wordpress.org/bonaldi/), [Axel Ducoron](https://github.com/aksld), [Laurent Millet](https://profiles.wordpress.org/wplmillet/), [Samy Rabih](https://github.com/samy) and [Raphaël Riehl](https://github.com/raphaelriehl) for their invaluable help).

### Fixed
- In some conditions, the plugin may be in the default site language rather than the user's language.

## [2.5.0] - 2021-06-22

### Added
- Compatibility with WordPress 5.8.
- Integration with DecaLog SDK.

## [2.4.0] - 2021-02-24

### Added
- Compatibility with WordPress 5.7.

## [2.3.0] - 2021-01-11

### Added
- New "insights": Apache live status (when `server-status` is enabled).
- New "insights": Apache effective configuration files (when `server-info` is enabled).
- New "insights": Apache server settings, modules and hooks information (when `server-info` is enabled).

### Changed
- Consistent reset for settings.
- Improved translation loading.

### Fixed
- In Site Health section, Opcache status may be wrong (or generates PHP warnings) if OPcache API usage is restricted.

## [2.2.0] - 2020-11-23

### Added
- New Site Health "info" section about shared memory.
- Compatibility with WordPress 5.6.

### Changed
- Improvement in the way roles are detected.
- The positions of PerfOps menus are pushed lower to avoid collision with other plugins (thanks to [Loïc Antignac](https://github.com/webaxones)).
- Improved layout for language indicator.
- Admin notices are now set to "don't display" by default.
- Improved IP detection  (thanks to [Ludovic Riaudel](https://github.com/lriaudel)).
- Improved changelog readability.
- The integrated markdown parser is now [Markdown](https://github.com/cebe/markdown) from Carsten Brandt.
- Prepares PerfOps menus to future 5.6 version of WordPress.

### Fixed
- [SEC001] User may be wrongly detected in XML-RPC or Rest API calls.
- The admin dashboard may wrongly display a statistics link.
- The remote IP can be wrongly detected when behind some types of reverse-proxies.
- With Firefox, some links are unclickable in the Control Center (thanks to [Emil1](https://wordpress.org/support/users/milouze/)).
- When site is in english and a user choose another language for herself/himself, menu may be stuck in english.
- Some typos in `CHANGELOG.md`.

### Removed
- Parsedown as integrated markdown parser.

## [2.1.2] - 2020-06-29

### Changed
- Full compatibility with PHP 7.4.
- Automatic switching between memory and transient when a cache plugin is installed without a properly configured Redis / Memcached.

## [2.1.1] - 2020-05-15

### Fixed
- When all options are disabled, some pages may be wrongly rendered as 404 page.
- When used for the first time, settings checkboxes may remain checked after being unchecked.
- There's an error while activating the plugin when the server is Microsoft IIS with Windows 10.
- With Microsoft Edge, some layouts may be ugly.

## [2.1.0] - 2020-04-12

### Added
- Compatibility with [DecaLog](https://wordpress.org/plugins/decalog/) early loading feature.

### Changed
- Information about plugin options has been improved in Site Health feature.
- The settings page have now the standard WordPress style.
- Better styling in "PerfOps Settings" page.
- In site health "info" tab, the boolean are now clearly displayed.

### Fixed
- Some strings are not translatable.

## [2.0.2] - 2020-03-03

### Changed
- The libraries description have been updated.
- Some sentences are more clear, now.

### Fixed
- There's some typos in the settings page.
- The `CHANGELOG.md` contains some typos.
- The `readme.txt` contains some typos.

## [2.0.1] - 2020-03-03

### Fixed
- An unneeded item appears in the admin menu.

## [2.0.0] - 2020-03-03

### Added
- It's now possible to independently activate server-status or server-info rules.
- Full integration with PerfOps One suite.
- Full compatibility with [APCu Manager](https://wordpress.org/plugins/apcu-manager/).
- Compatibility with WordPress 5.4.
- New menu (in the left admin bar) for accessing features: "PerfOps Settings".

### Changed
- New logo and new name for the plugin: "Apache Status & Info".

### Removed
- Compatibility with WordPress versions prior to 5.2.
- GitHub `.wordpress-org` directory from WordPress releases.

## [1.2.0] - 2019-09-06

### Added
- Support for [DecaLog](https://wordpress.org/plugins/decalog/).
- Links to users' support and GitHub project in plugins list.

### Changed
- The plugin license is now GPLv3.
- The plugin is now fully [open sourced on GitHub](https://github.com/Pierre-Lannoy/wp-htaccess-server-info-server-status).

### Removed
- Compatibility with WordPress older than 5.0.

## [1.1.4] - 2019-04-28

### Fixed
- Full compatibility with WordPress 5.2.

## [1.1.3] - 2019-02-26

### Fixed
- Typos in version matching.

## [1.1.2] - 2019-02-25

### Fixed
- Full compatibility with WordPress 5.1.

## [1.1.1] - 2018-11-02

### Fixed
- Full compatibility with WordPress 5.0.

## [1.1.0] - 2018-10-24

### Added
- Support for non-root rewrite bases (thanks to Guillaume Bedleem).

## [1.0.0] - 2018-10-22

Initial release


