<?php

namespace SpamWall\Utils;

/**
 * Classs responsible for encrypting and decrypting sensitive data 
 * such as API keys before storing it in the database.
 */
class EncryptionHelper
{

    /**
     * Encrypts a value using SPAM_WALL_ENCRYPTION_KEY if available.
     * 
     * @param string $value Value to encrypt.
     * @return string Encrypted value, or original value if encryption is not set up.
     */
    public function encrypt($value)
    {
        if (!extension_loaded('openssl') || !defined('SPAM_WALL_ENCRYPTION_KEY')) {
            // Encryption not available or key not defined, return orignal value
            return $value;
        }

        $method = 'aes-256-cbc';
        $key = substr(hash('sha256', SPAM_WALL_ENCRYPTION_KEY, true), 0, 32);
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($method));

        $encrypted = openssl_encrypt($value, $method, $key, 0, $iv);
        if ($encrypted === false) {
            return $value;  // Fallback to original value on encryption failure
        }

        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypts a value using SPAM_WALL_ENCRYPTION_KEY if available.
     * 
     * @param string $encryptedValue Value to decrypt.
     * @return string Decrypted value, or original value if decryption is not set up or fails.
     */
    public function decrypt($encryptedValue)
    {
        if (!extension_loaded('openssl') || !defined('SPAM_WALL_ENCRYPTION_KEY')) {
            return $encryptedValue;
        }

        $method = 'aes-256-cbc';
        $key = substr(hash('sha256', SPAM_WALL_ENCRYPTION_KEY, true), 0, 32);
        $data = base64_decode($encryptedValue);
        if ($data === false) {
            return $encryptedValue;
        }

        $ivLength = openssl_cipher_iv_length($method);
        if (strlen($data) < $ivLength) {
            return $encryptedValue;
        }

        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);

        $decrypted = openssl_decrypt($encrypted, $method, $key, 0, $iv);
        return $decrypted !== false ? $decrypted : $encryptedValue;
    }
}
