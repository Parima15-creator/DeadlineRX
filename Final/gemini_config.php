<?php
$localConfig = __DIR__ . '/gemini_config_local.php';

if (file_exists($localConfig)) {
    require_once $localConfig;
} else {
    define('GEMINI_API_KEY', getenv('GEMINI_API_KEY') ?: 'PASTE_YOUR_GEMINI_API_KEY_HERE');
}

define('GEMINI_MODEL', 'gemini-2.0-flash');
?>