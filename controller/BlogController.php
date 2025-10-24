<?php
/**
 * Blog Controller
 * Handles CRUD operations for blog posts with ownership validation
 */

// Include CORS configuration FIRST (before session_start)
require_once __DIR__ . '/../config/cors.php';

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Blog.php';

if (!isset($conn) || !$conn) {
    echo json_encode(["success" => false, "message" => "Database connection not available"]);
    exit;
}

$blog = new Blog($conn);
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Helper to get current logged-in user ID
function current_user_id() {
    return isset($_SESSION['user']) && isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;
}

// CREATE - Create new blog post (with optional image)
if ($action === 'create' && $method === 'POST') {
    $user_id = current_user_id();
    if (!$user_id) {
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "Unauthorized. Please log in."]);
        exit;
    }

    // Check if this is multipart/form-data (with image) or JSON (without image)
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (strpos($contentType, 'multipart/form-data') !== false) {
        // Handle form data with image
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $blog_image = null;

        // Upload image if provided
        if (isset($_FILES['blog_image']) && $_FILES['blog_image']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = $blog->uploadBlogImage($_FILES['blog_image']);
            if ($uploadResult['status'] === 'error') {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => $uploadResult['message']]);
                exit;
            }
            $blog_image = $uploadResult['path'];
        }
    } else {
        // Handle JSON data without image
        $data = json_decode(file_get_contents("php://input"), true);
        $title = trim($data['title'] ?? '');
        $content = trim($data['content'] ?? '');
        $blog_image = null;
    }

    if ($title === '' || $content === '') {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Title and content are required."]);
        exit;
    }

    $result = $blog->create($user_id, $title, $content, $blog_image);
    if ($result) {
        $blogId = $conn->lastInsertId(); // Get the last inserted ID
        echo json_encode([
            "success" => true, 
            "message" => "Blog created successfully.", 
            "blog" => ["id" => $blogId],
            "blog_image" => $blog_image
        ]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Failed to create blog."]);
    }
    exit;
}

// READ ALL - Get all blogs for listing
if ($action === 'readAll' && $method === 'GET') {
    $rows = $blog->readAll();
    echo json_encode(["success" => true, "blogs" => $rows]);
    exit;
}

// READ SINGLE - Get specific blog by ID
if ($action === 'readSingle' && $method === 'GET') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Blog ID is required."]);
        exit;
    }
    
    $row = $blog->readSingle($id);
    if ($row) {
        echo json_encode(["success" => true, "blog" => $row]);
    } else {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "Blog not found."]);
    }
    exit;
}

// UPDATE - Update existing blog (owner only, with optional new image)
if ($action === 'update' && $method === 'POST') {
    $user_id = current_user_id();
    if (!$user_id) {
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "Unauthorized. Please log in."]);
        exit;
    }

    // Check if this is multipart/form-data (with image) or JSON (without image)
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (strpos($contentType, 'multipart/form-data') !== false) {
        // Handle form data with potential new image
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $blog_image = null;

        // Upload new image if provided
        if (isset($_FILES['blog_image']) && $_FILES['blog_image']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = $blog->uploadBlogImage($_FILES['blog_image']);
            if ($uploadResult['status'] === 'error') {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => $uploadResult['message']]);
                exit;
            }
            $blog_image = $uploadResult['path'];
        }
    } else {
        // Handle JSON data without image
        $data = json_decode(file_get_contents("php://input"), true);
        $id = isset($data['id']) ? (int)$data['id'] : 0;
        $title = trim($data['title'] ?? '');
        $content = trim($data['content'] ?? '');
        $blog_image = null;
    }

    if ($id <= 0 || $title === '' || $content === '') {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "All fields are required."]);
        exit;
    }

    $affected = $blog->update($id, $user_id, $title, $content, $blog_image);
    if ($affected === false) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Update failed (server error)."]);
    } elseif ($affected === 0) {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "Unauthorized to update this blog."]);
    } else {
        echo json_encode(["success" => true, "message" => "Blog updated successfully."]);
    }
    exit;
}

// DELETE - Delete blog (owner only)
if ($action === 'delete' && $method === 'POST') {
    $user_id = current_user_id();
    if (!$user_id) {
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "Unauthorized. Please log in."]);
        exit;
    }

    $data = json_decode(file_get_contents("php://input"), true);
    $id = isset($data['id']) ? (int)$data['id'] : 0;
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Blog ID required."]);
        exit;
    }

    $affected = $blog->delete($id, $user_id);
    if ($affected === false) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Delete failed (server error)."]);
    } elseif ($affected === 0) {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "Unauthorized to delete this blog."]);
    } else {
        echo json_encode(["success" => true, "message" => "Blog deleted successfully."]);
    }
    exit;
}

// Default response for invalid requests
http_response_code(400);
echo json_encode(["success" => false, "message" => "Invalid request."]);
exit;
?>