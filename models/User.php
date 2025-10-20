<?php
/**
 * User Model
 * Handles user registration, authentication, and profile management
 */
class User {
    private $conn;
    
    public function __construct($db) { 
        $this->conn = $db; 
    }

    /**
     * Register new user with hashed password
     */
    public function register($data) {
        try {
            $stmt = $this->conn->prepare("INSERT INTO users(first_name,middle_name,last_name,username,email,password,role) VALUES(?,?,?,?,?,?,?)");
            $stmt->bind_param("sssssss",
                $data['first_name'],
                $data['middle_name'],
                $data['last_name'],
                $data['username'],
                $data['email'],
                password_hash($data['password'], PASSWORD_BCRYPT),
                $data['role']
            );
            
            if ($stmt->execute()) {
                return ['status' => 'success', 'message' => 'User registered successfully'];
            }
            return ['status' => 'error', 'message' => 'Registration failed'];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Login user - verify credentials and create session
     */
    public function login($email, $password) {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user'] = $user;
                $_SESSION['user_id'] = (int)$user['id'];
                return ['status' => 'success', 'message' => 'Login successful'];
            }
        }
        return ['status' => 'error', 'message' => 'Invalid email or password'];
    }

    /**
     * Update user profile information
     */
    public function updateProfile($id, $data) {
        $stmt = $this->conn->prepare("UPDATE users SET first_name=?, middle_name=?, last_name=?, pronouns=?, bio=?, role=? WHERE id=?");
        $stmt->bind_param("ssssssi",
            $data['first_name'],
            $data['middle_name'],
            $data['last_name'],
            $data['pronouns'],
            $data['bio'],
            $data['role'],
            $id
        );
        
        return $stmt->execute()
            ? ['status' => 'success', 'message' => 'Profile updated']
            : ['status' => 'error', 'message' => 'Update failed'];
    }

    /**
     * Upload profile image with validation
     * Validates file type and size, generates unique filename
     */
    public function uploadProfileImage($id, $file) {
        $targetDir = __DIR__ . '/../' . $_ENV['UPLOAD_PATH'];
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        // Validate file type
        $allowed = explode(',', $_ENV['ALLOWED_IMAGE_TYPES']);
        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExt, $allowed)) {
            return ['status'=>'error','message'=>'Invalid file type'];
        }
        
        // Validate file size
        if ($file['size'] > $_ENV['MAX_IMAGE_SIZE']) {
            return ['status'=>'error','message'=>'File too large'];
        }

        // Generate unique filename and move file
        $fileName = uniqid() . "." . $fileExt;
        $targetFile = $targetDir . $fileName;

        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
            // Update database with image path
            $stmt = $this->conn->prepare("UPDATE users SET profile_image=? WHERE id=?");
            $path = $_ENV['UPLOAD_PATH'] . $fileName;
            $stmt->bind_param("si", $path, $id);
            $stmt->execute();
            return ['status'=>'success','message'=>'Image uploaded','path'=>$path];
        }
        return ['status'=>'error','message'=>'Upload failed'];
    }
}