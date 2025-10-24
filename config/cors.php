<?php
/**
 * CORS Configuration
 * Handles Cross-Origin Resource Sharing for API requests
 */

// Allowed origins (add your frontend URLs here)
$allowed_origins = [
    'http://localhost',
    'http://localhost:3000',
    'http://localhost:8000',
    'http://127.0.0.1',
    'http://127.0.0.1:5500',
    'http://localhost:5500',
    'null' // For local file:// protocol testing
];

// Get the origin of the request
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

// Check if origin is allowed
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    // For development, allow all origins
    header('Access-Control-Allow-Origin: *');
}

// Allowed HTTP methods
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

// Allowed headers
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Allow credentials (cookies, sessions)
header('Access-Control-Allow-Credentials: true');

// Set content type
header('Content-Type: application/json; charset=UTF-8');

// Cache preflight requests for 1 hour
header('Access-Control-Max-Age: 3600');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}