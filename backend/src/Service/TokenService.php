<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;

class TokenService
{
    private string $secret;

    public function __construct(string $appSecret)
    {
        $this->secret = $appSecret;
    }

    /**
     * Génère un token HMAC signé pour l'utilisateur
     */
    public function generateToken(int $userId, string $email, string $role): string
    {
        $payload = base64_encode(json_encode([
            'id'    => $userId,
            'email' => $email,
            'role'  => $role,
            'exp'   => time() + (30 * 24 * 3600), // 30 jours
            'iat'   => time(),
        ]));

        $signature = hash_hmac('sha256', $payload, $this->secret);

        return $payload . '.' . $signature;
    }

    /**
     * Valide un token et retourne le payload ou null si invalide
     */
    public function validateToken(string $token): ?array
    {
        $parts = explode('.', $token, 2);
        if (count($parts) !== 2) {
            return null;
        }

        [$payload, $signature] = $parts;

        $expectedSignature = hash_hmac('sha256', $payload, $this->secret);

        if (!hash_equals($expectedSignature, $signature)) {
            return null;
        }

        $decoded = base64_decode($payload, true);
        if ($decoded === false) {
            return null;
        }

        $data = json_decode($decoded, true);

        if (!$data || !isset($data['exp'], $data['id'], $data['role'])) {
            return null;
        }

        if ($data['exp'] < time()) {
            return null;
        }

        return $data;
    }

    /**
     * Extrait et valide le token depuis l'en-tête Authorization d'une requête
     */
    public function extractFromRequest(Request $request): ?array
    {
        $authHeader = $request->headers->get('Authorization', '');
        if (!str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }

        $token = substr($authHeader, 7);
        if (empty($token)) {
            return null;
        }

        return $this->validateToken($token);
    }
}
