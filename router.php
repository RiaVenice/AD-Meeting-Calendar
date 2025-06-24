<?php

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Custom error handler function
function handleRouterError($message, $file = null, $line = null) {
    $errorLog = "Router Error: " . $message;
    if ($file) $errorLog .= " in file: " . $file;
    if ($line) $errorLog .= " on line: " . $line;
    
    // Log error to file (optional)
    error_log($errorLog);
    
    // Display user-friendly error page
    http_response_code(500);
    echo "<!DOCTYPE html>";
    echo "<html><head><title>Application Error</title></head><body>";
    echo "<h1>Application Error</h1>";
    echo "<p>Sorry, there was an error loading the application.</p>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($message) . "</p>";
    if (ini_get('display_errors')) {
        echo "<p><strong>Debug Info:</strong></p>";
        echo "<pre>" . htmlspecialchars($errorLog) . "</pre>";
    }
    echo "</body></html>";
    exit;
}

// Try to load bootstrap.php with error handling
try {
    $bootstrapPath = __DIR__ . '/bootstrap.php';  // Fixed: DIR should be DIR
    
    if (!file_exists($bootstrapPath)) {
        throw new Exception("bootstrap.php file not found at: " . $bootstrapPath);
    }
    
    if (!is_readable($bootstrapPath)) {
        throw new Exception("bootstrap.php file is not readable at: " . $bootstrapPath);
    }
    
    require $bootstrapPath;
    
    // Check if BASE_PATH was defined in bootstrap
    if (!defined('BASE_PATH')) {
        throw new Exception("BASE_PATH constant not defined in bootstrap.php");
    }
    
} catch (Exception $e) {
    handleRouterError("Failed to load bootstrap.php: " . $e->getMessage(), FILE, LINE);
} catch (Error $e) {
    handleRouterError("PHP Error in bootstrap.php: " . $e->getMessage(), $e->getFile(), $e->getLine());
}

// Handle built-in PHP server static file serving
if (php_sapi_name() === 'cli-server') {
    try {
        $urlPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        if ($urlPath === false) {
            throw new Exception("Invalid URL path in REQUEST_URI: " . $_SERVER['REQUEST_URI']);
        }
        
        $file = BASE_PATH . $urlPath;
        
        // Security check: prevent directory traversal
        $realBasePath = realpath(BASE_PATH);
        $realFilePath = realpath($file);
        
        if ($realFilePath && strpos($realFilePath, $realBasePath) === 0 && is_file($file)) {
            return false; // Let PHP serve the static file
        }
        
    } catch (Exception $e) {
        handleRouterError("Error processing static file request: " . $e->getMessage(), FILE, LINE);
    }
}

// Try to load main application with error handling
try {
    $indexPath = BASE_PATH . '/index.php';
    
    if (!file_exists($indexPath)) {
        throw new Exception("index.php file not found at: " . $indexPath);
    }
    
    if (!is_readable($indexPath)) {
        throw new Exception("index.php file is not readable at: " . $indexPath);
    }
    
    require $indexPath;
    
} catch (Exception $e) {
    handleRouterError("Failed to load index.php: " . $e->getMessage(), FILE, LINE);
} catch (Error $e) {
    handleRouterError("PHP Error in index.php: " . $e->getMessage(), $e->getFile(), $e->getLine());
} catch (Throwable $e) {
    handleRouterError("Unexpected error: " . $e->getMessage(), $e->getFile(), $e->getLine());
}