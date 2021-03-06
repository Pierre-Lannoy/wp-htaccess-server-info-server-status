# Changelog
All notable changes to **Apache Status & Info** are documented in this *changelog*.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and **Apache Status & Info** adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
- Full integration with PerfOps.One suite.
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


