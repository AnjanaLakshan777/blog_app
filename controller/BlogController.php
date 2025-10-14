<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Blog.php';

$db = (new Database())->connect();
$blog = new Blog($db);

header("Content-Type: application/json");

$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents("php://input"), true);

if ($method == 'POST' && $_GET['action'] == 'create') {
    if ($blog->create($data['user_id'], $data['title'], $data['content'])) {
        echo json_encode(["message" => "Blog created successfully"]);
    } else {
        echo json_encode(["message" => "Blog creation failed"]);
    }
}

if ($method == 'GET' && $_GET['action'] == 'read') {
    $result = $blog->readAll();
    $blogs = $result->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($blogs);
}

if ($method == 'PUT' && $_GET['action'] == 'update') {
    if ($blog->update($data['id'], $data['user_id'], $data['title'], $data['content'])) {
        echo json_encode(["message" => "Blog updated successfully"]);
    } else {
        echo json_encode(["message" => "Update failed"]);
    }
}

if ($method == 'DELETE' && $_GET['action'] == 'delete') {
    if ($blog->delete($data['id'], $data['user_id'])) {
        echo json_encode(["message" => "Blog deleted successfully"]);
    } else {
        echo json_encode(["message" => "Delete failed"]);
    }
}
?>
