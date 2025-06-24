<?php

// MongoDB Connection Error Handler
function handleMongoError($message, $errorType = 'General', $code = null) {
    $timestamp = date('Y-m-d H:i:s');
    $errorLog = "[{$timestamp}] MongoDB {$errorType} Error: {$message}";
    if ($code) $errorLog .= " (Code: {$code})";
    
    // Log error to file
    error_log($errorLog);
    
    // Display error based on environment
    if (ini_get('display_errors')) {
        echo "<div style='color: red; padding: 10px; border: 1px solid red; margin: 10px 0;'>";
        echo "<strong>MongoDB {$errorType} Error:</strong><br>";
        echo htmlspecialchars($message);
        if ($code) echo "<br><strong>Error Code:</strong> " . $code;
        echo "</div>";
    } else {
        echo "<div style='color: red; padding: 10px;'>Database connection failed. Please try again later.</div>";
    }
}

// Try to load environment utilities with error handling
try {
    if (!defined('UTILS_PATH')) {
        throw new Exception("UTILS_PATH constant is not defined");
    }
    
    $envUtilPath = UTILS_PATH . '/envSetter.util.php';
    
    if (!file_exists($envUtilPath)) {
        throw new Exception("envSetter.util.php not found at: " . $envUtilPath);
    }
    
    if (!is_readable($envUtilPath)) {
        throw new Exception("envSetter.util.php is not readable at: " . $envUtilPath);
    }
    
    require_once $envUtilPath;
    
} catch (Exception $e) {
    handleMongoError("Failed to load environment utilities: " . $e->getMessage(), "Configuration");
    exit;
} catch (Error $e) {
    handleMongoError("PHP Error loading environment utilities: " . $e->getMessage(), "Configuration");
    exit;
}

// Validate MongoDB configuration
try {
    if (!isset($mongoConfig)) {
        throw new Exception("MongoDB configuration variable \$mongoConfig is not defined");
    }
    
    if (!is_array($mongoConfig)) {
        throw new Exception("MongoDB configuration must be an array");
    }
    
    if (!isset($mongoConfig['uri']) || empty($mongoConfig['uri'])) {
        throw new Exception("MongoDB URI is not configured or empty");
    }
    
    // Validate URI format (basic check)
    if (!filter_var($mongoConfig['uri'], FILTER_VALIDATE_URL) && 
        !preg_match('/^mongodb(\+srv)?:\/\//', $mongoConfig['uri'])) {
        throw new Exception("Invalid MongoDB URI format");
    }
    
} catch (Exception $e) {
    handleMongoError($e->getMessage(), "Configuration");
    exit;
}

// Check if MongoDB extension is loaded
if (!extension_loaded('mongodb')) {
    handleMongoError("MongoDB PHP extension is not installed or loaded", "Extension");
    exit;
}

// Attempt MongoDB connection with comprehensive error handling
try {
    // Create MongoDB manager with timeout options
    $options = [
        'connectTimeoutMS' => 5000,  // 5 second timeout
        'serverSelectionTimeoutMS' => 5000,
        'socketTimeoutMS' => 10000
    ];
    
    // Merge user options if provided
    if (isset($mongoConfig['options']) && is_array($mongoConfig['options'])) {
        $options = array_merge($options, $mongoConfig['options']);
    }
    
    $mongo = new MongoDB\Driver\Manager($mongoConfig['uri'], $options);
    
    // Test connection with ping command
    $command = new MongoDB\Driver\Command(["ping" => 1]);
    $cursor = $mongo->executeCommand("admin", $command);
    
    // Verify ping response
    $response = $cursor->toArray();
    if (empty($response) || !isset($response[0]->ok) || $response[0]->ok != 1) {
        throw new Exception("Ping command failed - server did not respond correctly");
    }
    
    echo "<div style='color: green; padding: 10px; border: 1px solid green; margin: 10px 0;'>";
    echo "✅ Connected to MongoDB successfully!<br>";
    echo "<strong>URI:</strong> " . preg_replace('/\/\/([^:]+):([^@]+)@/', '//***:***@', $mongoConfig['uri']) . "<br>";
    echo "<strong>Server Response:</strong> OK<br>";
    echo "<strong>Connection Time:</strong> " . date('Y-m-d H:i:s');
    echo "</div>";
    
} catch (MongoDB\Driver\Exception\InvalidArgumentException $e) {
    handleMongoError("Invalid connection arguments: " . $e->getMessage(), "Connection", $e->getCode());
} catch (MongoDB\Driver\Exception\ConnectionTimeoutException $e) {
    handleMongoError("Connection timeout: " . $e->getMessage(), "Timeout", $e->getCode());
} catch (MongoDB\Driver\Exception\AuthenticationException $e) {
    handleMongoError("Authentication failed: " . $e->getMessage(), "Authentication", $e->getCode());
} catch (MongoDB\Driver\Exception\SSLConnectionException $e) {
    handleMongoError("SSL connection failed: " . $e->getMessage(), "SSL", $e->getCode());
} catch (MongoDB\Driver\Exception\ConnectionException $e) {
    handleMongoError("Connection failed: " . $e->getMessage(), "Connection", $e->getCode());
} catch (MongoDB\Driver\Exception\RuntimeException $e) {
    handleMongoError("Runtime error: " . $e->getMessage(), "Runtime", $e->getCode());
} catch (MongoDB\Driver\Exception\Exception $e) {
    handleMongoError("MongoDB driver error: " . $e->getMessage(), "Driver", $e->getCode());
} catch (Exception $e) {
    handleMongoError("Unexpected error: " . $e->getMessage(), "Unexpected", $e->getCode());
} catch (Error $e) {
    handleMongoError("PHP Fatal Error: " . $e->getMessage(), "Fatal");
} catch (Throwable $e) {
    handleMongoError("Critical error: " . $e->getMessage(), "Critical");
}

// Optional: Test a simple database operation
try {
    if (isset($mongo)) {
        // Test database access (optional)
        $testDb = $mongoConfig['database'] ?? 'test';
        $command = new MongoDB\Driver\Command(["listCollections" => 1]);
        $cursor = $mongo->executeCommand($testDb, $command);
        
        echo "<div style='color: blue; padding: 5px; margin: 5px 0;'>";
        echo "📁 Database '{$testDb}' is accessible";
        echo "</div>";
    }
} catch (Exception $e) {
    // Don't fail completely, just warn
    echo "<div style='color: orange; padding: 5px; margin: 5px 0;'>";
    echo "⚠️ Warning: Could not access database operations: " . htmlspecialchars($e->getMessage());
    echo "</div>";
}