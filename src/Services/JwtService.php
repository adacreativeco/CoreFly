<?php

declare(strict_types=1);

namespace CoreFly\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Exception;

class JwtService
{
    private string $secret;
    private int $expiration;
    private int $refreshExpiration;
    private string $algorithm;

    public function __construct()
    {
        $config = require __DIR__ . '/../../config/app.php';
        $jwtConfig = $config['jwt'];
        $this->secret = $jwtConfig['secret'];
        $this->expiration = $jwtConfig['expiration'];
        $this->refreshExpiration = $jwtConfig['refresh_expiration'];
        $this->algorithm = $jwtConfig['algorithm'];
        if (!$this->secret || strlen($this->secret) < 16) {
            \CoreFly\Utils\Logger::error('Weak or missing JWT secret');
        }
    }

    public function generateToken(array $payload): array
    {
        $issuedAt = time();
        $expire = $issuedAt + $this->expiration;
        $refreshExpire = $issuedAt + $this->refreshExpiration;

        $tokenPayload = [
            'iat' => $issuedAt,
            'exp' => $expire,
            'jti' => bin2hex(random_bytes(16)),
            'data' => $payload
        ];

        $refreshPayload = [
            'iat' => $issuedAt,
            'exp' => $refreshExpire,
            'jti' => bin2hex(random_bytes(16)),
            'type' => 'refresh',
            'data' => ['user_id' => $payload['user_id'] ?? null]
        ];

        if (class_exists(\Firebase\JWT\JWT::class)) {
            $token = JWT::encode($tokenPayload, $this->secret, $this->algorithm);
            $refreshToken = JWT::encode($refreshPayload, $this->secret, $this->algorithm);
        } else {
            $token = $this->simpleEncode($tokenPayload);
            $refreshToken = $this->simpleEncode($refreshPayload);
        }

        return [
            'token' => $token,
            'refresh_token' => $refreshToken,
            'expires_at' => $expire,
            'refresh_expires_at' => $refreshExpire
        ];
    }

    private function simpleEncode(array $payload): string
    {
        if (class_exists(\Firebase\JWT\JWT::class)) {
            return JWT::encode($payload, $this->secret, $this->algorithm);
        }
        $header = json_encode(['typ' => 'JWT', 'alg' => $this->algorithm]);
        $payload = json_encode($payload);
        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, $this->secret, true);
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        return $base64Header . "." . $base64Payload . "." . $base64Signature;
    }

    public function validateToken(string $token): ?array
    {
        try {
            if (class_exists(\Firebase\JWT\JWT::class)) {
                $decoded = JWT::decode($token, new Key($this->secret, $this->algorithm));
                $payload = json_decode(json_encode($decoded), true);
            } else {
                $parts = explode('.', $token);
                if (count($parts) !== 3) {
                    throw new Exception('Invalid token');
                }
                $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
            }
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                throw new Exception('Token has expired');
            }
            return (array) ($payload['data'] ?? $payload);
        } catch (Exception $e) {
            throw new Exception('Invalid token: ' . $e->getMessage());
        }
    }

    public function refreshToken(string $refreshToken): array
    {
        try {
            $decoded = JWT::decode($refreshToken, new Key($this->secret, $this->algorithm));
            
            if (!isset($decoded->type) || $decoded->type !== 'refresh') {
                throw new Exception('Invalid refresh token type');
            }

            $userId = $decoded->data->user_id ?? null;
            if (!$userId) {
                throw new Exception('User ID not found in refresh token');
            }

            return $this->generateToken(['user_id' => $userId]);
        } catch (ExpiredException $e) {
            throw new Exception('Refresh token has expired');
        } catch (Exception $e) {
            throw new Exception('Invalid refresh token: ' . $e->getMessage());
        }
    }

    public function revokeToken(string $token): bool
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, $this->algorithm));
            
            $jti = $decoded->jti ?? null;
            if ($jti) {
                return $this->addToBlacklist($jti, $decoded->exp ?? time());
            }
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function extractTokenFromHeader(string $header): string
    {
        if (empty($header)) {
            throw new Exception('Authorization header is missing');
        }

        $parts = explode(' ', $header);
        if (count($parts) !== 2 || $parts[0] !== 'Bearer') {
            throw new Exception('Invalid authorization header format');
        }

        return $parts[1];
    }

    public function getTokenExpiration(string $token): ?int
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, $this->algorithm));
            return $decoded->exp ?? null;
        } catch (Exception $e) {
            return null;
        }
    }

    public function isTokenExpired(string $token): bool
    {
        $expiration = $this->getTokenExpiration($token);
        if ($expiration === null) {
            return true;
        }

        return time() > $expiration;
    }

    public function getTokenId(string $token): ?string
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, $this->algorithm));
            return $decoded->jti ?? null;
        } catch (Exception $e) {
            return null;
        }
    }

    private function addToBlacklist(string $jti, int $exp): bool
    {
        return true;
    }

    public function generateSecureSecret(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function validateSecret(string $secret): bool
    {
        return strlen($secret) >= 32 && preg_match('/^[a-f0-9]+$/i', $secret);
    }

    public function getAlgorithm(): string
    {
        return $this->algorithm;
    }

    public function getSecret(): string
    {
        return $this->secret;
    }

    public function getExpiration(): int
    {
        return $this->expiration;
    }

    public function getRefreshExpiration(): int
    {
        return $this->refreshExpiration;
    }
}
