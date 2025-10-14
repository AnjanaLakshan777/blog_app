<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../config/database.php';

$db = (new Database())->connect();
$user = new User($db);

header("Content-Type: application/json");

$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents("php://input"), true);

if ($method == 'POST' && $_GET['action'] == 'register') {
    if ($user->register($data['username'], $data['email'], $data['password'])) {
        echo json_encode(["message" => "User registered successfully"]);
    } else {
        echo json_encode(["message" => "Registration failed"]);
    }
}

if ($method == 'POST' && $_GET['action'] == 'login') {
    $loginUser = $user->login($data['username'], $data['password']);
    if ($loginUser) {
        echo json_encode(["message" => "Login successful", "user" => $loginUser]);
    } else {
        echo json_encode(["message" => "Invalid credentials"]);
    }
}
?>
