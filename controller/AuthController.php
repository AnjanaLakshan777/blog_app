<?php
/**
 * Authentication Controller
 * Handles user registration, login, profile updates, and logout
 */

// Include CORS configuration FIRST
require_once __DIR__ . '/../config/cors.php';
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);


session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';

$user = new User($conn);
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'register':
        $data = json_decode(file_get_contents("php://input"), true);
        echo json_encode($user->register($data));
        break;

    case 'login':
        $data = json_decode(file_get_contents("php://input"), true);
        $result = $user->login($data['email'], $data['password']);
        echo json_encode($result);
        break;
    
    case 'checkAuth':
        // Check if user is logged in
        if (isset($_SESSION['user'])) {
            echo json_encode([
                'success' => true,
                'user' => $_SESSION['user']
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Not authenticated'
            ]);
        }
        break;

    case 'updateProfile':
        // Check authentication
        if (!isset($_SESSION['user'])) { 
            echo json_encode(['success' => false, 'message' => 'Unauthorized']); 
            break; 
        }
        $data = json_decode(file_get_contents("php://input"), true);
        echo json_encode($user->updateProfile($_SESSION['user']['id'], $data));
        break;

    case 'uploadImage':
        // Check authentication
        if (!isset($_SESSION['user'])) { 
            echo json_encode(['success' => false, 'message' => 'Unauthorized']); 
            break; 
        }
        echo json_encode($user->uploadProfileImage($_SESSION['user']['id'], $_FILES['image']));
        break;

    case 'logout':
        session_destroy();
        echo json_encode(['success' => true, 'message' => 'Logged out']);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}