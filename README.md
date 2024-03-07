# Spam Wall
Spam Wall is a WordPress plugin that harnesses the power of OpenAI's GPT models to intelligently filter and classify comments on WordPress sites, distinguishing between genuine interactions (ham) and spam with remarkable accuracy. This plugin offers a modern, AI-driven approach to maintaining clean and engaging comment sections, ensuring that valuable discussions are not buried under unwanted spam. It's an essential tool for website owners seeking to enhance user engagement and protect their site's integrity without manual intervention.

### Secure API Key Encryption

To enhance the security of your OpenAI API key within the Spam Wall plugin, the plugin offers an optional encryption feature. This feature requires you to define a custom encryption key in your WordPress site's `wp-config.php` file. Follow the instructions below to set up encryption for your API key:

#### Setting up Encryption

1. **Generate a Strong Encryption Key**:
  - Use a reliable password generator or the WordPress secret key generator (https://api.wordpress.org/secret-key/1.1/salt/) to create a strong, unique key. Your key should be long (ideally at least 32 characters) and a mix of uppercase and lowercase letters, numbers, and symbols.

2. **Add the Encryption Key to `wp-config.php`**:
  - Locate your `wp-config.php` file in the root directory of your WordPress installation.
  - Open it in a text editor and add the following line above the `/* That's all, stop editing! Happy publishing. */` comment:
    ```php
    define('SPAM_WALL_ENCRYPTION_KEY', 'your_unique_key_here');
    ```
  - Replace `'your_unique_key_here'` with the encryption key you generated.

#### Important Considerations

- Once you've set up the `SPAM_WALL_ENCRYPTION_KEY`, it's crucial not to change or remove it without proper planning. Altering this key will render any encrypted data, particularly your OpenAI API key, undecryptable with the new or absent key. 

- If you must change or remove the `SPAM_WALL_ENCRYPTION_KEY` for any reason such as it being compromised, remember to re-save the API key from the Spam Wall plugin settings afterward. This ensures that your API key is either re-encrypted with the new key or stored without encryption if the key is removed.