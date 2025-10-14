<?php
class Blog {
    private $conn;
    private $table = 'blogs';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($user_id, $title, $content) {
        $query = "INSERT INTO {$this->table} (user_id, title, content) VALUES (:user_id, :title, :content)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':content', $content);
        return $stmt->execute();
    }

    public function readAll() {
        $query = "SELECT b.*, u.username FROM {$this->table} b JOIN users u ON b.user_id = u.id ORDER BY b.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function update($id, $user_id, $title, $content) {
        $query = "UPDATE {$this->table} SET title = :title, content = :content WHERE id = :id AND user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':content', $content);
        return $stmt->execute();
    }

    public function delete($id, $user_id) {
        $query = "DELETE FROM {$this->table} WHERE id = :id AND user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':user_id', $user_id);
        return $stmt->execute();
    }
}
?>
