=== Spam Wall ===
Contributors: ihenetudan
Tags: comments, spam, ai, openai, gpt, filter, security
Requires at least: 5.3
Tested up to: 6.4.3
Requires PHP: 7.2
Stable tag: 1.0.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.en.html

Spam Wall is a WordPress plugin that leverages OpenAI's GPT models to intelligently classify comments as spam or ham, enhancing comment section integrity.

== Description ==

Spam Wall harnesses the power of OpenAI's GPT models to intelligently filter and classify comments on WordPress sites, distinguishing between genuine interactions (ham) and spam with remarkable accuracy. This AI-driven approach ensures that valuable discussions are not overshadowed by unwanted spam, maintaining a clean and engaging comment section. It is an indispensable tool for website owners aiming to boost user engagement and safeguard their site's integrity without the need for manual moderation.

== Installation ==

1. Upload the `spam-wall` folder to the `/wp-content/plugins/` directory or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Use the Settings->Spam Wall screen to configure the plugin.

== Frequently Asked Questions ==

= How do I generate a strong encryption key? =

You can use a reliable password generator or the WordPress secret key generator (https://api.wordpress.org/secret-key/1.1/salt/) to create a robust, unique key.

= What should I do if I need to change the encryption key? =

If the `SPAM_WALL_ENCRYPTION_KEY` needs to be changed, ensure to re-save your OpenAI API key in the Spam Wall plugin settings after updating the key to re-encrypt the API key with the new encryption key or remove encryption if the key is deleted.

== Changelog ==

= 1.0.0 =
- Initial release.

== Upgrade Notice ==

= 1.0.0 =
Initial Release

== License ==

This plugin is licensed under the GPL v3 or later.
