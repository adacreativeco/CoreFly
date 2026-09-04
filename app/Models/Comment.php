<?php

namespace App\Models;

use App\Core\Model;

class Comment extends Model
{
    protected $table = 'comments';

    public function findByPostId($postId) {
        $sql = "SELECT c.*, u.full_name as author_name, u.avatar_url as author_avatar 
                FROM comments c 
                JOIN users u ON c.author_id = u.id
                WHERE c.post_id = :post_id
                ORDER BY c.created_at ASC";
        return $this->db->fetchAll($sql, ['post_id' => $postId]);
    }
}
