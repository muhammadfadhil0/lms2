<?php
/**
 * Global API Keys Integration
 * Fungsi global untuk mengintegrasikan API keys dengan sistem yang sudah ada
 */

// Function to get API key for Pingo Chat
function getPingoApiKey() {
    require_once __DIR__ . '/api-keys-helper.php';
    
    // Try to get pingo_chat specific key first, then fallback to groq
    $pingoKey = ApiKeysHelper::getApiKeyForService('pingo_chat');
    if ($pingoKey) {
        return $pingoKey;
    }
    
    return ApiKeysHelper::getApiKeyForService('groq');
}

// Function to get Groq API key
function getGroqApiKey() {
    require_once __DIR__ . '/api-keys-helper.php';
    return ApiKeysHelper::getApiKeyForService('groq');
}

// Function to get OpenAI API key
function getOpenAiApiKey() {
    require_once __DIR__ . '/api-keys-helper.php';
    return ApiKeysHelper::getApiKeyForService('openai');
}

// Function to get any API key by service name
function getApiKey($serviceName) {
    require_once __DIR__ . '/api-keys-helper.php';
    return ApiKeysHelper::getApiKeyForService($serviceName);
}

// Function to get API configuration for a service
function getApiConfig($serviceName) {
    require_once __DIR__ . '/api-keys-logic.php';
    
    try {
        $apiKeysLogic = new ApiKeysLogic();
        $config = $apiKeysLogic->getApiKeyByService($serviceName);
        
        if ($config && $config['is_active']) {
            return $config;
        }
        
        return null;
    } catch (Exception $e) {
        error_log("Error getting API config for $serviceName: " . $e->getMessage());
        return null;
    }
}

// Auto-update Pingo config with latest active keys
function updatePingoConfigFromDatabase() {
    require_once __DIR__ . '/api-keys-helper.php';
    return ApiKeysHelper::generatePingoConfigUpdate();
}

// Validate if API service is available and configured
function isApiServiceAvailable($serviceName) {
    $apiKey = getApiKey($serviceName);
    return !empty($apiKey);
}

// Get all available API services
function getAvailableApiServices() {
    require_once __DIR__ . '/api-keys-logic.php';
    
    try {
        $apiKeysLogic = new ApiKeysLogic();
        $apiKeys = $apiKeysLogic->getAllApiKeys();
        
        $available = [];
        foreach ($apiKeys as $key) {
            if ($key['is_active']) {
                $available[$key['service_name']] = $key['service_label'];
            }
        }
        
        return $available;
    } catch (Exception $e) {
        error_log("Error getting available API services: " . $e->getMessage());
        return [];
    }
}
?>