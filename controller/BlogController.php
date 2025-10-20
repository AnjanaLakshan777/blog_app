<?php
/**
 * Blog Controller
 * Handles CRUD operations for blog posts with ownership validation
 */

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Blog.php';

header("Content-Type: application/json; charset=UTF-8");

if (!isset($conn) || !$conn) {
    echo json_encode(["status" => "error", "message" => "Database connection not available"]);
    exit;
}

$blog = new Blog($conn);
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Helper to get current logged-in user ID
function current_user_id() {
    return isset($_SESSION['user']) && isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;
}

// CREATE - Create new blog post
if ($action === 'create' && $method === 'POST') {
    $user_id = current_user_id();
    if (!$user_id) {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Unauthorized. Please log in."]);
        exit;
    }

    $data = json_decode(file_get_contents("php://input"), true);
    $title = trim($data['title'] ?? '');
    $content = trim($data['content'] ?? '');

    if ($title === '' || $content === '') {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Title and content are required."]);
        exit;
    }

    $ok = $blog->create($user_id, $title, $content);
    if ($ok) {
        echo json_encode(["status" => "success", "message" => "Blog created successfully."]);
    } else {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Failed to create blog."]);
    }
    exit;
}

// READ ALL - Get all blogs for listing
if ($action === 'readAll' && $method === 'GET') {
    $rows = $blog->readAll();
    echo json_encode(["status" => "success", "blogs" => $rows]);
    exit;
}

// READ SINGLE - Get specific blog by ID
if ($action === 'readSingle' && $method === 'GET') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Blog ID is required."]);
        exit;
    }
    
    $row = $blog->readSingle($id);
    if ($row) {
        echo json_encode(["status" => "success", "blog" => $row]);
    } else {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "Blog not found."]);
    }
    exit;
}

// UPDATE - Update existing blog (owner only)
if ($action === 'update' && $method === 'POST') {
    $user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    if (!$user_id) {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Unauthorized. Please log in."]);
        exit;
    }

    $data = json_decode(file_get_contents("php://input"), true);
    $id = isset($data['id']) ? (int)$data['id'] : 0;
    $title = trim($data['title'] ?? '');
    $content = trim($data['content'] ?? '');

    if ($id <= 0 || $title === '' || $content === '') {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "All fields are required."]);
        exit;
    }

    $affected = $blog->update($id, $user_id, $title, $content);
    if ($affected === false) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Update failed (server error)."]);
    } elseif ($affected === 0) {
        http_response_code(403);
        echo json_encode(["status" => "error", "message" => "Unauthorized to update this blog."]);
    } else {
        echo json_encode(["status" => "success", "message" => "Blog updated successfully."]);
    }
    exit;
}

// DELETE - Delete blog (owner only)
if ($action === 'delete' && $method === 'POST') {
    $user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    if (!$user_id) {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Unauthorized. Please log in."]);
        exit;
    }

    $data = json_decode(file_get_contents("php://input"), true);
    $id = isset($data['id']) ? (int)$data['id'] : 0;
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Blog ID required."]);
        exit;
    }

    $affected = $blog->delete($id, $user_id);
    if ($affected === false) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Delete failed (server error)."]);
    } elseif ($affected === 0) {
        http_response_code(403);
        echo json_encode(["status" => "error", "message" => "Unauthorized to delete this blog."]);
    } else {
        echo json_encode(["status" => "success", "message" => "Blog deleted successfully."]);
    }
    exit;
}

// Default response for invalid requests
http_response_code(400);
echo json_encode(["status" => "error", "message" => "Invalid request."]);
exit;
?>