<?php

namespace SpamWall\Admin;

use SpamWall\Utils\EncryptionHelper;
use SpamWall\Utils\OptionKey;

class Settings
{
    const SETTINGS_PAGE_SLUG = 'spam-wall-settings';

    /**
     * Initialize the Settings page hooks.
     */
    public function init()
    {
        add_action('admin_menu', [$this, 'addSettingsPage']);
        add_action('admin_init', [$this, 'registerSettings']);
    }

    /**
     * Add settings page under the "Settings" menu.
     */
    public function addSettingsPage()
    {
        add_options_page(
            'Spam Wall Settings', // Page title
            'Spam Wall', // Menu title
            'manage_options', // Capability
            self::SETTINGS_PAGE_SLUG, // Menu slug
            [$this, 'createSettingsPage'] // Callback function
        );
    }

    /**
     * Register settings, section, and fields.
     */
    public function registerSettings()
    {
        // Register the API key setting with a custom sanitization callback
        register_setting(self::SETTINGS_PAGE_SLUG, OptionKey::OPENAI_API_KEY, [
            'sanitize_callback' => [$this, 'sanitizeApiKey']
        ]);

        // Register the model selection setting
        register_setting(self::SETTINGS_PAGE_SLUG, OptionKey::MODEL_PREFERENCE);

        add_settings_section(
            'spam_wall_api_settings', // ID
            'OpenAI API Settings', // Title
            null, // Callback
            self::SETTINGS_PAGE_SLUG // Page
        );

        // API key field
        add_settings_field(
            OptionKey::OPENAI_API_KEY, // ID
            'OpenAI API Key', // Title
            [$this, 'apiKeyFieldCallback'], // Callback
            self::SETTINGS_PAGE_SLUG, // Page
            'spam_wall_api_settings' // Section
        );

        // Model preference field
        add_settings_field(
            OptionKey::MODEL_PREFERENCE, // ID
            'GPT Model Preference', // Title
            [$this, 'modelPreferenceFieldCallback'], // Callback
            self::SETTINGS_PAGE_SLUG, // Page
            'spam_wall_api_settings' // Section
        );
    }

    /**
     * Settings page content.
     */
    public function createSettingsPage()
    {
        ?>
        <div class="wrap">
            <h2>Spam Wall Settings</h2>
            <form action="options.php" method="POST">
                <?php
                settings_fields(self::SETTINGS_PAGE_SLUG);
                do_settings_sections(self::SETTINGS_PAGE_SLUG);
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Display the API key input field.
     */
    public function apiKeyFieldCallback()
    {
        $encrypted_api_key = get_option(OptionKey::OPENAI_API_KEY);
        $api_key = EncryptionHelper::decrypt($encrypted_api_key);
        echo '<input type="text" name="' . OptionKey::OPENAI_API_KEY . '" value="' . esc_attr($api_key) . '" class="regular-text">';
    }

    /**
     * Display the model preference select field.
     */
    public function modelPreferenceFieldCallback()
    {
        $model_preference = get_option(OptionKey::MODEL_PREFERENCE, 'gpt-3.5-turbo-0125');  // Default to GPT-3.5 for lower costs
        ?>
        <select name="<?php echo OptionKey::MODEL_PREFERENCE; ?>">
            <option value="gpt-3.5-turbo-0125" <?php selected($model_preference, 'gpt-3.5-turbo-0125'); ?>>GPT-3.5 Turbo (Lower Costs)</option>
            <option value="gpt-4-0125-preview" <?php selected($model_preference, 'gpt-4-0125-preview'); ?>>GPT-4 Turbo (Better Performance)</option>
        </select>
        <?php
    }

    /**
     * Sanitizes and encrypts the API key before saving it.
     * 
     * @param string $api_key The submitted API key.
     * @return string The sanitized and potentially encrypted API key.
     */
    public function sanitizeApiKey($api_key)
    {
        // Sanitize the API key
        $sanitized_api_key = sanitize_text_field($api_key);
        // Encrypt the API key if encryption is set up
        return EncryptionHelper::encrypt($sanitized_api_key);
    }
}
