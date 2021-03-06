=== WP Lynx ===
Contributors: mtekk
Tags: Links, Facebook, Open Graph, post, posts
Requires at least: 3.9
Tested up to: 4.3
Stable tag: 1.0.0
WP Lynx allows you to mimic Facebook's wall links in your WordPress posts.

== Description ==

WP Lynx allows you to create stylized links akin to those in Facebook. These stylized links, known as lynx prints, can contain a thumbnail image of any of the images on the linked page, plus a short excerpt from the page. The images are automatically cached on your server (no hot-linking), and the content is intelligently scraped.

Note: version 1.0.0 of this plugin requires PHP5.3 or newer.

* English - default -

Don't see your language on the list? Feel free to translate WP Lynx and send John Havlik the translations.

== Installation ==

Please visit [WP Lynx's](http://mtekk.us/code/wp-lynx/#installation "WP Lynx's project page's installation section.") project page for installation and usage instructions.

== Screenshots ==
1. This screenshot shows the Edit Post page with two Lynx Prints in the post and the Add Lynx Print button
2. This screenshot shows the Add Lynx Print screen with two Lynx Prints in queue to be added to the current post

== Changelog ==
= 1.0.0 =
* Behavior change: Requires PHP 5.3 or newer.
* Behavior change: Requires WordPress 3.8 or newer.
* New feature: New Lynx Print adding interface, a la the new media interface in WordPress 3.5.
* New feature: Allow users to configure number of redirects followed by the scaping engine.
* New feature: Migrate to the latest mtekk_adminKit.
* New feature: Reorganized/written core plugin files.
* New feature: Include minified scripts and styles for non-development environments, see SCRIPT_DEBUG.
* Bug fix: Added workaround which should make it difficult for new Lynx Prints to be inserted within existing Lynx Prints in a post.
= 0.6.0 =
* New feature: Added support for site thumbnails using Snapito!
* New feature: Migrated to latest mtekk_adminKit, brings new tab style to settings page.
* New feature: Simplified and reorganized the settings page.
* Bug fix: Fixed some PHP warnings from `llynx_scrape` when the scraped site did not behave as expected.
* Bug fix: Fixed PHP warnings from `llynx_scrape` when the site contains img tags without `src` fields.
= 0.5.0 =
* New feature: Support for Open Graph protocol og:image and og:description.
* New feature: Added a warning message on the Lynx Print adding screen alerting to thumbnails being disabled due to directory permission issues.
* Bug fix: Improved content scraping algorithms to handle newlines in odd places (mid tag).
* Bug fix: Fixed issue with the Show/Hide links not working correctly.
= 0.4.0 =
* Behavior change: Improved content filtering, should reduce the chances that garbage is selected as paragraph stubs.
* Behavior change: Included style is now a separate CSS file rather than an inline CSS file.
* Behavior change: Import/Export/Reset tab moved under the admin bar Help menu.
* New feature: More useful Help menu, utilizing the new WordPress 3.3 Help menu.
* Bug fix: Tabs on the settings page are now rounded for all modern browsers, including Firefox, Chrome, and IE9.
* Bug fix: Tabs on the settings page are now remembered between setting saves (including multiple saves from within the same tab).
= 0.3.0 =
* New feature: Templated Lynx Prints now available.
* New feature: Tinyurl URL shortening now available.
* New feature: TinyMCE button added when in full screen editing mode.
* New feature: Added Help tab to Lynx Print adding screen.
* New feature: Any whitespace character may be used to separate multiple URLs.
* Bug fix: Fixed issue where PHP warnings would arise if the remote server did not return the expected data range.
* Bug fix: Permissions issues for the WordPress Upload directory are handled more gracefully.
= 0.2.1 =
* Bug fix: Installer now works properly for first time users.
= 0.2.0 =
* New feature: Lynx Prints are now styled in the WordPress visual editor.
* New feature: Users are now warned if settings are out of date, allowed to do a one click settings migration.
* New feature: Can now undo setting saves, resets, and imports.
* Bug fix: Blank images should no longer load as thumbnail candidates.
= 0.1.3 =
* Bug fix: Improved CURL Error reporting.
* Bug fix: Fixed issue with non UTF-8 or ASCII encoded sites.
* Bug fix: Fixed issue with CURL and PHP in safe_mode.
= 0.1.2 =
* Initial Public Release

== Upgrade Notice ==

= 1.0.0 =
This version requires PHP5.3 or newer, do not update if still on PHP5.2. This version introduces a newly rewritten Lynx Print adding interface.
