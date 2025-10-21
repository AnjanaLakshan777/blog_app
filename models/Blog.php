<?php
/**
 * Blog Model
 * Handles database operations for blog posts using prepared statements
 */
class Blog {
    private $conn;
    private $table = 'blogs';

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Create a new blog post with optional image
     */
    public function create($user_id, $title, $content, $blog_image = null) {
        $query = "INSERT INTO {$this->table} (user_id, title, content, blog_image) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return false;
        
        $stmt->bind_param("isss", $user_id, $title, $content, $blog_image);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /**
     * Get all blog posts with author info, ordered by newest first
     */
    public function readAll() {
        $query = "SELECT b.id, b.title, b.content, b.blog_image, b.created_at, u.username 
                  FROM {$this->table} b 
                  JOIN users u ON b.user_id = u.id 
                  ORDER BY b.created_at DESC";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return [];
        
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = $res->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    /**
     * Get a single blog post by ID with author info
     */
    public function readSingle($id) {
        $query = "SELECT b.id, b.title, b.content, b.blog_image, b.created_at, u.username 
                  FROM {$this->table} b 
                  JOIN users u ON b.user_id = u.id 
                  WHERE b.id = ?";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return null;
        
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        return $row ? $row : null;
    }

    /**
     * Update blog post - only owner can update
     * Returns affected rows (0 if not owner)
     */
    public function update($id, $user_id, $title, $content, $blog_image = null) {
        // If blog_image is provided, update it; otherwise keep existing
        if ($blog_image !== null) {
            $query = "UPDATE {$this->table} 
                      SET title = ?, content = ?, blog_image = ? 
                      WHERE id = ? AND user_id = ?";
            $stmt = $this->conn->prepare($query);
            if (!$stmt) return false;
            $stmt->bind_param("sssii", $title, $content, $blog_image, $id, $user_id);
        } else {
            $query = "UPDATE {$this->table} 
                      SET title = ?, content = ? 
                      WHERE id = ? AND user_id = ?";
            $stmt = $this->conn->prepare($query);
            if (!$stmt) return false;
            $stmt->bind_param("ssii", $title, $content, $id, $user_id);
        }
        
        $ok = $stmt->execute();
        $affected = $this->conn->affected_rows;
        $stmt->close();
        
        if ($ok === false) return false;
        return $affected;
    }

    /**
     * Delete blog post - only owner can delete
     * Returns affected rows (0 if not owner)
     */
    public function delete($id, $user_id) {
        // Get blog image path before deleting
        $blog = $this->readSingle($id);
        
        $query = "DELETE FROM {$this->table} WHERE id = ? AND user_id = ?";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return false;
        
        $stmt->bind_param("ii", $id, $user_id);
        $ok = $stmt->execute();
        $affected = $this->conn->affected_rows;
        $stmt->close();
        
        // Delete image file if deletion was successful
        if ($ok && $affected > 0 && $blog && $blog['blog_image']) {
            $imagePath = __DIR__ . '/../' . $blog['blog_image'];
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
        
        if ($ok === false) return false;
        return $affected;
    }

    /**
     * Upload blog image with validation
     * Returns image path or error array
     */
    public function uploadBlogImage($file) {
        $targetDir = __DIR__ . '/../' . $_ENV['BLOG_IMAGE_PATH'];
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        // Validate file type
        $allowed = explode(',', $_ENV['ALLOWED_BLOG_IMAGE_TYPES']);
        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExt, $allowed)) {
            return ['status'=>'error','message'=>'Invalid file type'];
        }
        
        // Validate file size
        if ($file['size'] > $_ENV['MAX_BLOG_IMAGE_SIZE']) {
            return ['status'=>'error','message'=>'File too large'];
        }

        // Generate unique filename and move file
        $fileName = uniqid() . "." . $fileExt;
        $targetFile = $targetDir . $fileName;

        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
            return ['status'=>'success', 'path'=>$_ENV['BLOG_IMAGE_PATH'] . $fileName];
        }
        return ['status'=>'error','message'=>'Upload failed'];
    }
}
?>