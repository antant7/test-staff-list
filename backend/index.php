<?php

// Handle URLs like index.php?route=/api/staff without htaccess
// This allows the backend folder to serve as document root while maintaining Symfony structure

// Check if route parameter is provided
if (isset($_GET['route'])) {
    // Use the route parameter as REQUEST_URI
    $requestUri = $_GET['route'];
    
    // Ensure route starts with /
    if (substr($requestUri, 0, 1) !== '/') {
        $requestUri = '/' . $requestUri;
    }
    
    // Remove route parameter from query string to avoid conflicts
    unset($_GET['route']);
    
    // Rebuild QUERY_STRING without route parameter
    $queryString = http_build_query($_GET);
    $_SERVER['QUERY_STRING'] = $queryString;
    
    // Set REQUEST_URI with query string if present
    if (!empty($queryString)) {
        $requestUri .= '?' . $queryString;
    }
} else {
    // Default behavior - get the request URI
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
    
    // Remove the script name from the URI if present
    if (isset($_SERVER['SCRIPT_NAME'])) {
        $scriptName = dirname($_SERVER['SCRIPT_NAME']);
        if ($scriptName !== '/' && strpos($requestUri, $scriptName) === 0) {
            $requestUri = substr($requestUri, strlen($scriptName));
        }
    }
}

// Set the correct script name for Symfony
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/public/index.php';
$_SERVER['REQUEST_URI'] = $requestUri;

// Include the actual Symfony front controller
require_once __DIR__ . '/public/index.php';