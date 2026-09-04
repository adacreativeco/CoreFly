<?php

namespace App\Controllers;

use App\Models\Post;
use App\Models\Comment;
use App\Services\AuthService;

class WorkspaceController
{
    private $postModel;
    private $commentModel;
    private $authService;
    private $currentUser;

    public function __construct()
    {
        $this->postModel = new Post();
        $this->commentModel = new Comment();
        $this->authService = new AuthService();
    }

    private function authenticate()
    {
        $headers = [];
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        }

        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $token = str_replace('Bearer ', '', $authHeader);

        if (empty($token)) {
             error_log("No token provided in headers: " . print_r($headers, true) . " or SERVER: " . print_r($_SERVER, true));
             http_response_code(401);
             echo json_encode(['error' => 'Unauthorized: No token']);
             exit;
        }
        
        $payload = $this->authService->verifyToken($token);
        if (!$payload) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized: Invalid token']);
            exit;
        }
        $this->currentUser = $payload;
        return $payload;
    }

    public function getFeed($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'] ?? $user['tenant_id'];

        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? 20;
        $type = $_GET['type'] ?? null;

        $conditions = ['tenant_id' => $tenantId];
        if ($type) {
            $conditions['post_type'] = $type;
        }

        $posts = $this->postModel->findAllWithRelations($conditions, $page, $limit);
        
        // Enrich with comments count if needed, though comment_count column exists
        $this->json(['data' => $posts]);
    }

    public function getPost($params)
    {
        $this->authenticate();
        $postId = $params['postId'];
        
        $post = $this->postModel->findWithRelations($postId);
        if (!$post) {
            $this->json(['error' => 'Post not found'], 404);
            return;
        }

        $comments = $this->commentModel->findByPostId($postId);
        $post['comments'] = $comments;

        // Increment view count
        $this->postModel->update($postId, ['view_count' => $post['view_count'] + 1]);

        $this->json(['data' => $post]);
    }

    public function createPost($params)
    {
        $user = $this->authenticate();
        $data = json_decode(file_get_contents('php://input'), true);
        $tenantId = $params['tenantId'] ?? $user['tenant_id'];

        if (empty($data['title']) || empty($data['content'])) {
            $this->json(['error' => 'Title and content required'], 400);
            return;
        }

        $post = $this->postModel->create([
            'tenant_id' => $tenantId,
            'author_id' => $user['id'],
            'title' => $data['title'],
            'content' => $data['content'],
            'post_type' => $data['post_type'] ?? 'general'
        ]);

        $this->json(['data' => $post], 201);
    }

    public function addComment($params)
    {
        $user = $this->authenticate();
        $postId = $params['postId'];
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['content'])) {
            $this->json(['error' => 'Content required'], 400);
            return;
        }

        $comment = $this->commentModel->create([
            'post_id' => $postId,
            'author_id' => $user['id'],
            'content' => $data['content'],
            'parent_id' => $data['parent_id'] ?? null
        ]);

        // Update post comment count
        $post = $this->postModel->find($postId);
        $this->postModel->update($postId, ['comment_count' => $post['comment_count'] + 1]);

        $this->json(['data' => $comment], 201);
    }

    private function json($data, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
    }
}
