<?php
/**
 * Authentication Controller
 * Handles user registration, login, profile updates, and logout
 */

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
        echo json_encode($user->login($data['email'], $data['password']));
        break;

    case 'updateProfile':
        // Check authentication
        if (!isset($_SESSION['user'])) { 
            echo json_encode(['status'=>'error','message'=>'Unauthorized']); 
            break; 
        }
        $data = json_decode(file_get_contents("php://input"), true);
        echo json_encode($user->updateProfile($_SESSION['user']['id'], $data));
        break;

    case 'uploadImage':
        // Check authentication
        if (!isset($_SESSION['user'])) { 
            echo json_encode(['status'=>'error','message'=>'Unauthorized']); 
            break; 
        }
        echo json_encode($user->uploadProfileImage($_SESSION['user']['id'], $_FILES['image']));
        break;

    case 'logout':
        session_destroy();
        echo json_encode(['status'=>'success','message'=>'Logged out']);
        break;

    default:
        echo json_encode(['status'=>'error','message'=>'Invalid action']);
}