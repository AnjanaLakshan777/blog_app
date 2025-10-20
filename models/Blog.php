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
     * Create a new blog post
     */
    public function create($user_id, $title, $content) {
        $query = "INSERT INTO {$this->table} (user_id, title, content) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return false;
        
        $stmt->bind_param("iss", $user_id, $title, $content);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /**
     * Get all blog posts with author info, ordered by newest first
     */
    public function readAll() {
        $query = "SELECT b.id, b.title, b.content, b.created_at, u.username 
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
        $query = "SELECT b.id, b.title, b.content, b.created_at, u.username 
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
    public function update($id, $user_id, $title, $content) {
        $query = "UPDATE {$this->table} 
                  SET title = ?, content = ? 
                  WHERE id = ? AND user_id = ?";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return false;
        
        $stmt->bind_param("ssii", $title, $content, $id, $user_id);
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
        $query = "DELETE FROM {$this->table} WHERE id = ? AND user_id = ?";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return false;
        
        $stmt->bind_param("ii", $id, $user_id);
        $ok = $stmt->execute();
        $affected = $this->conn->affected_rows;
        $stmt->close();
        
        if ($ok === false) return false;
        return $affected;
    }
}
?>