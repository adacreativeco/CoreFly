<?php

namespace App\Models;

use App\Core\Model;

class Post extends Model
{
    protected $table = 'posts';
    
    public function findAllWithRelations($conditions = [], $page = 1, $limit = 20, $orderBy = 'created_at', $orderDir = 'DESC') {
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT p.*, u.full_name as author_name, u.avatar_url as author_avatar 
                FROM posts p 
                JOIN users u ON p.author_id = u.id";
        
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', array_map(fn($k) => "p.$k = :$k", array_keys($conditions)));
        }
        
        $sql .= " ORDER BY p.$orderBy $orderDir LIMIT $limit OFFSET $offset";
        
        return $this->db->fetchAll($sql, $conditions);
    }

    public function findWithRelations($id) {
        $sql = "SELECT p.*, u.full_name as author_name, u.avatar_url as author_avatar 
                FROM posts p 
                JOIN users u ON p.author_id = u.id
                WHERE p.id = :id";
        return $this->db->fetch($sql, ['id' => $id]);
    }
}
