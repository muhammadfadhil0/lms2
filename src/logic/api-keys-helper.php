<?php
/**
 * API Keys Helper Functions
 * Fungsi bantuan untuk mengelola API keys dan enkripsi
 */

class ApiKeysHelper {
    private static $encryption_key;
    
    public static function init() {
        // Use a consistent key for encryption - in production, store this securely
        self::$encryption_key = hash('sha256', 'lms_api_keys_encryption_2025');
    }
    
    /**
     * Encrypt API key for storage
     */
    public static function encryptApiKey($apiKey) {
        self::init();
        
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($apiKey, 'AES-256-CBC', self::$encryption_key, 0, $iv);
        return base64_encode($encrypted . '::' . $iv);
    }
    
    /**
     * Decrypt API key for use
     */
    public static function decryptApiKey($encryptedKey) {
        self::init();
        
        try {
            $data = base64_decode($encryptedKey);
            if (strpos($data, '::') === false) {
                // Key is not encrypted (legacy), return as-is
                return $encryptedKey;
            }
            
            list($encrypted, $iv) = explode('::', $data, 2);
            return openssl_decrypt($encrypted, 'AES-256-CBC', self::$encryption_key, 0, $iv);
        } catch (Exception $e) {
            // If decryption fails, return original (might be unencrypted legacy key)
            return $encryptedKey;
        }
    }
    
    /**
     * Update Pingo Chat config file with new API key
     */
    public static function updatePingoConfig($serviceName, $apiKey) {
        try {
            $configPath = __DIR__ . '/../pingo/config.php';
            
            if (!file_exists($configPath)) {
                return [
                    'success' => false,
                    'message' => 'File konfigurasi Pingo tidak ditemukan'
                ];
            }
            
            $configContent = file_get_contents($configPath);
            $updated = false;
            
            // Update based on service type
            switch ($serviceName) {
                case 'groq':
                case 'pingo_chat':
                    // Update GROQ_API_KEY
                    $pattern = "/define\('GROQ_API_KEY',\s*'[^']*'\);/";
                    $replacement = "define('GROQ_API_KEY', '" . addslashes($apiKey) . "');";
                    
                    $newContent = preg_replace($pattern, $replacement, $configContent);
                    
                    if ($newContent !== $configContent) {
                        file_put_contents($configPath, $newContent);
                        $updated = true;
                    }
                    break;
            }
            
            if ($updated) {
                return [
                    'success' => true,
                    'message' => 'Konfigurasi Pingo berhasil diperbarui'
                ];
            } else {
                return [
                    'success' => true,
                    'message' => 'Tidak ada perubahan diperlukan'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error updating config: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get API key for specific service (decrypted)
     */
    public static function getApiKeyForService($serviceName) {
        require_once 'api-keys-logic.php';
        
        try {
            $apiKeysLogic = new ApiKeysLogic();
            $apiKey = $apiKeysLogic->getApiKeyByService($serviceName);
            
            if ($apiKey && $apiKey['is_active']) {
                return self::decryptApiKey($apiKey['api_key']);
            }
            
            return null;
        } catch (Exception $e) {
            error_log("Error getting API key for service $serviceName: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Validate API key format
     */
    public static function validateApiKeyFormat($serviceName, $apiKey) {
        switch ($serviceName) {
            case 'groq':
            case 'pingo_chat':
            case 'groq_vision':
                // Groq API keys start with 'gsk_' followed by 48+ characters
                return preg_match('/^gsk_[a-zA-Z0-9]{48,}$/', $apiKey);
            case 'openai':
                // OpenAI API keys start with 'sk-' followed by 48+ characters  
                return preg_match('/^sk-[a-zA-Z0-9]{48,}$/', $apiKey);
            case 'anthropic':
                return preg_match('/^sk-ant-[a-zA-Z0-9\-_]{90,}$/', $apiKey);
            default:
                return strlen($apiKey) > 10; // Basic validation for custom services
        }
    }
    
    /**
     * Generate config update script for Pingo
     */
    public static function generatePingoConfigUpdate() {
        $groqKey = self::getApiKeyForService('groq');
        $pingoKey = self::getApiKeyForService('pingo_chat');
        
        // Use pingo_chat key if available, otherwise use groq key
        $keyToUse = $pingoKey ?: $groqKey;
        
        if ($keyToUse) {
            return self::updatePingoConfig('groq', $keyToUse);
        }
        
        return [
            'success' => false,
            'message' => 'Tidak ada API key Groq atau Pingo Chat yang aktif'
        ];
    }
}

// Auto-update pingo config when this file is included
if (defined('AUTO_UPDATE_PINGO_CONFIG') && AUTO_UPDATE_PINGO_CONFIG) {
    ApiKeysHelper::generatePingoConfigUpdate();
}
?>