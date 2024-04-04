<?php

namespace SpamWall\Admin;

use SpamWall\Utils\EncryptionHelper;
use SpamWall\Utils\OptionKey;

/** 
 * Handles the settings page for the plugin.
 * 
 * @package SpamWall
 */
class Settings
{
    const SETTINGS_PAGE_SLUG = 'spam-wall-settings';

    /**
     * The EncryptionHelper instance.
     * 
     * @var EncryptionHelper
     */
    private $encryptionHelper;

    /**
     * Constructor for the Settings class.
     * Initializes the EncryptionHelper instance.
     */
    public function __construct(EncryptionHelper $encryptionHelper)
    {
        $this->encryptionHelper = $encryptionHelper;
    }

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
            'Spam Wall Settings',
            'Spam Wall',
            'manage_options',
            self::SETTINGS_PAGE_SLUG,
            [$this, 'createSettingsPage']
        );
    }

    /**
     * Register settings, section, and fields.
     */
    public function registerSettings()
    {
        register_setting(self::SETTINGS_PAGE_SLUG, OptionKey::OPENAI_API_KEY, [
            'sanitize_callback' => [$this, 'sanitizeApiKey']
        ]);

        register_setting(self::SETTINGS_PAGE_SLUG, OptionKey::MODEL_PREFERENCE);

        add_settings_section(
            'spam_wall_api_settings',
            'OpenAI API Settings',
            null,
            self::SETTINGS_PAGE_SLUG
        );

        add_settings_field(
            OptionKey::OPENAI_API_KEY,
            'OpenAI API Key',
            [$this, 'apiKeyFieldCallback'],
            self::SETTINGS_PAGE_SLUG,
            'spam_wall_api_settings'
        );

        add_settings_field(
            OptionKey::MODEL_PREFERENCE,
            'GPT Model Preference',
            [$this, 'modelPreferenceFieldCallback'],
            self::SETTINGS_PAGE_SLUG,
            'spam_wall_api_settings'
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
        $api_key = $this->encryptionHelper->decrypt($encrypted_api_key);
        echo '<input type="password" name="' . esc_attr(OptionKey::OPENAI_API_KEY) .
            '" value="' . esc_attr($api_key) . '" class="regular-text">';
    }

    /**
     * Display the model preference select field.
     */
    public function modelPreferenceFieldCallback()
    {
        $model_preference = get_option(OptionKey::MODEL_PREFERENCE, 'gpt-3.5-turbo-0125');
        ?>
        <select name="<?php echo esc_attr(OptionKey::MODEL_PREFERENCE); ?>">
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
        $sanitized_api_key = sanitize_text_field($api_key);
        return $this->encryptionHelper->encrypt($sanitized_api_key);
    }
}
