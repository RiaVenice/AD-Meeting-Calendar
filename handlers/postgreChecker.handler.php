<?php
require_once UTILS_PATH . '/envSetter.util.php';

// PostgreSQL Error Handler Function
function handlePostgreSQLConnection($config) {
    try {
        // Check if PostgreSQL extension is loaded
        if (!extension_loaded('pgsql')) {
            throw new Exception('PostgreSQL extension (pgsql) is not installed or enabled in PHP');
        }
        
        // Check if pg_connect function exists
        if (!function_exists('pg_connect')) {
            throw new Exception('pg_connect function is not available. Please install php-pgsql extension');
        }
        
        $host = $config['host']; 
        $port = $config['port'];
        $username = $config['user'];
        $password = $config['pass'];
        $dbname = $config['db'];
        
        $conn_string = "host=$host port=$port dbname=$dbname user=$username password=$password";
        
        // Attempt connection with error suppression
        $dbconn = @pg_connect($conn_string);
        
        if (!$dbconn) {
            $error = pg_last_error() ?: 'Unknown connection error';
            throw new Exception("Connection failed: $error");
        }
        
        // Test the connection
        $result = @pg_query($dbconn, 'SELECT version()');
        if (!$result) {
            $error = pg_last_error($dbconn) ?: 'Connection test failed';
            pg_close($dbconn);
            throw new Exception("Connection test failed: $error");
        }
        
        $version = pg_fetch_row($result);
        pg_free_result($result);
        
        echo "<div style='color: green; background: #d4edda; padding: 10px; border-radius: 3px; margin: 10px 0;'>";
        echo "✔️ PostgreSQL Connection Successfully Established<br>";
        echo "📍 Host: $host:$port<br>";
        echo "🗄️ Database: $dbname<br>";
        echo "👤 User: $username<br>";
        echo "🔧 Version: " . $version[0] . "<br>";
        echo "⏰ Connected at: " . date('Y-m-d H:i:s');
        echo "</div>";
        
        pg_close($dbconn);
        return true;
        
    } catch (Exception $e) {
        $errorType = categorizePostgreSQLError($e->getMessage());
        $solutions = getPostgreSQLSolutions($errorType);
        
        echo "<div style='color: #721c24; background: #f8d7da; padding: 10px; border-radius: 3px; margin: 10px 0;'>";
        echo "❌ PostgreSQL Connection Failed<br>";
        echo "<strong>Error:</strong> " . $e->getMessage() . "<br>";
        echo "<strong>Type:</strong> $errorType<br>";
        echo "<strong>Time:</strong> " . date('Y-m-d H:i:s');
        echo "</div>";
        
        echo "<div style='background: #fff3cd; color: #856404; padding: 10px; border-radius: 3px; margin: 10px 0;'>";
        echo "<strong>💡 Possible Solutions:</strong><br>";
        echo "<ul style='margin: 5px 0; padding-left: 20px;'>";
        foreach ($solutions as $solution) {
            echo "<li>$solution</li>";
        }
        echo "</ul>";
        echo "</div>";
        
        return false;
    }
}

function categorizePostgreSQLError($message) {
    if (strpos($message, 'extension') !== false || strpos($message, 'pg_connect') !== false) {
        return 'EXTENSION_NOT_INSTALLED';
    } elseif (strpos($message, 'Connection refused') !== false || strpos($message, 'could not connect') !== false) {
        return 'CONNECTION_REFUSED';
    } elseif (strpos($message, 'authentication') !== false || strpos($message, 'password') !== false) {
        return 'AUTHENTICATION_FAILED';
    } elseif (strpos($message, 'database') !== false && strpos($message, 'does not exist') !== false) {
        return 'DATABASE_NOT_FOUND';
    } elseif (strpos($message, 'timeout') !== false) {
        return 'CONNECTION_TIMEOUT';
    } elseif (strpos($message, 'host') !== false) {
        return 'HOST_UNREACHABLE';
    } else {
        return 'GENERAL_ERROR';
    }
}

function getPostgreSQLSolutions($errorType) {
    $solutions = [
        'EXTENSION_NOT_INSTALLED' => [
            '🐧 Ubuntu/Debian: <code>sudo apt-get install php-pgsql</code>',
            '🎩 CentOS/RHEL: <code>sudo yum install php-pgsql</code>',
            '🪟 Windows: Enable <code>extension=pgsql</code> in php.ini',
            '🐳 Docker: Add <code>RUN docker-php-ext-install pgsql pdo_pgsql</code> to Dockerfile',
            '🔄 Restart your web server after installation'
        ],
        'CONNECTION_REFUSED' => [
            '🔍 Check if PostgreSQL server is running',
            '🔧 Verify host and port configuration in your environment',
            '🐳 For Docker: Use <code>host.docker.internal</code> instead of localhost',
            '🛡️ Check firewall settings',
            '📋 Verify PostgreSQL is listening on the specified port'
        ],
        'AUTHENTICATION_FAILED' => [
            '🔐 Verify username and password are correct',
            '📝 Check PostgreSQL pg_hba.conf configuration',
            '👤 Ensure user exists and has proper permissions',
            '🔑 Try connecting with psql command line tool first'
        ],
        'DATABASE_NOT_FOUND' => [
            '🗄️ Create the database if it doesn\'t exist',
            '✏️ Verify database name spelling in configuration',
            '👤 Check if user has access to the database',
            '📋 List available databases with <code>\\l</code> in psql'
        ],
        'HOST_UNREACHABLE' => [
            '🌐 Check network connectivity to the host',
            '🐳 For Docker containers: Verify container networking',
            '🔧 Check if host address is correct',
            '📡 Test with ping or telnet to the host:port'
        ],
        'CONNECTION_TIMEOUT' => [
            '⏱️ Increase connection timeout settings',
            '🌐 Check network latency and stability',
            '🛡️ Verify firewall isn\'t blocking the connection'
        ],
        'GENERAL_ERROR' => [
            '📋 Check PostgreSQL server logs for details',
            '🔧 Verify all connection parameters',
            '🧪 Test connection manually using psql',
            '🔄 Restart PostgreSQL service if needed'
        ]
    ];
    
    return $solutions[$errorType] ?? $solutions['GENERAL_ERROR'];
}

// Execute the connection test
echo "<div style='font-family: Arial, sans-serif; margin: 20px; padding: 20px; border: 1px solid #ddd; border-radius: 5px;'>";
echo "<h3>🐘 PostgreSQL Connection Status</h3>";

$connectionSuccess = handlePostgreSQLConnection($pgConfig);

if (!$connectionSuccess) {
    echo "<div style='margin-top: 15px; padding: 10px; background: #e7f3ff; border-left: 4px solid #2196F3;'>";
    echo "<strong>🔧 Debug Info:</strong><br>";
    echo "Host: " . ($pgConfig['host'] ?? 'Not set') . "<br>";
    echo "Port: " . ($pgConfig['port'] ?? 'Not set') . "<br>";
    echo "Database: " . ($pgConfig['db'] ?? 'Not set') . "<br>";
    echo "Username: " . ($pgConfig['user'] ?? 'Not set') . "<br>";
    echo "Password: " . (isset($pgConfig['pass']) ? (strlen($pgConfig['pass']) > 0 ? '[SET]' : '[EMPTY]') : '[NOT SET]') . "<br>";
    echo "PHP Version: " . phpversion() . "<br>";
    echo "PostgreSQL Extension: " . (extension_loaded('pgsql') ? '✅ Loaded' : '❌ Not Loaded');
    echo "</div>";
}

echo "</div>";
?>