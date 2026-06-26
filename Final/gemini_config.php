<?php
// For local demo, paste your key below.
// Before pushing to GitHub, remove the key and use environment variables.

define('GEMINI_API_KEY', getenv('GEMINI_API_KEY') ?: 'PASTE_YOUR_GEMINI_API_KEY_HERE');

// If this model gives an error, change it to the model shown in your Google AI Studio account.
define('GEMINI_MODEL', 'gemini-2.0-flash');
?>