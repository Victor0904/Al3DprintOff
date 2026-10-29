<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class EncryptionService
{
    private string $encryptionKey;
    private string $cipher = 'AES-256-CBC';

    // Longueur minimale d'une donnée chiffrée valide en base64 (IV 16 bytes + 1 bloc 16 bytes = 32 bytes → ~44 chars)
    private const MIN_ENCRYPTED_LENGTH = 44;

    public function __construct(ParameterBagInterface $params = null)
    {
        try {
            $key = $params ? $params->get('app.encryption_key') : null;
        } catch (\Exception $e) {
            $key = null;
        }

        $this->encryptionKey = $key ?: $this->generateDefaultKey();
    }

    /**
     * Chiffre une donnée sensible
     */
    public function encrypt(string $data): string
    {
        if (empty($data)) {
            return $data;
        }

        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, $this->cipher, $this->encryptionKey, 0, $iv);

        if ($encrypted === false) {
            throw new \RuntimeException('Erreur lors du chiffrement des données');
        }

        return base64_encode($iv . $encrypted);
    }

    /**
     * Déchiffre une donnée sensible.
     * Tente avec la clé courante, puis la clé legacy en fallback.
     */
    public function decrypt(string $encryptedData): string
    {
        if (empty($encryptedData)) {
            return $encryptedData;
        }

        if (!$this->isEncrypted($encryptedData)) {
            return $encryptedData;
        }

        // Tentative avec la clé courante
        $result = $this->decryptWithKey($encryptedData, $this->encryptionKey);
        if ($result !== false) {
            return $result;
        }

        // Fallback : clé legacy (données chiffrées avant le changement de clé)
        $legacyKey = $this->generateDefaultKey();
        if ($legacyKey !== $this->encryptionKey) {
            $result = $this->decryptWithKey($encryptedData, $legacyKey);
            if ($result !== false) {
                return $result;
            }
        }

        // Retourner la valeur originale si le déchiffrement échoue plutôt que de planter
        return $encryptedData;
    }

    /**
     * Tentative de déchiffrement avec une clé spécifique.
     * Retourne le texte déchiffré ou false en cas d'échec.
     */
    private function decryptWithKey(string $encryptedData, string $key): string|false
    {
        try {
            $data = base64_decode($encryptedData, true);

            if ($data === false || strlen($data) < 17) {
                return false;
            }

            $iv        = substr($data, 0, 16);
            $encrypted = substr($data, 16);

            $decrypted = openssl_decrypt($encrypted, $this->cipher, $key, 0, $iv);

            if ($decrypted === false) {
                return false;
            }

            // Vérification basique : le résultat doit être du texte UTF-8 valide
            if (!mb_check_encoding($decrypted, 'UTF-8')) {
                return false;
            }

            return $decrypted;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Chiffre un tableau de données sensibles
     */
    public function encryptArray(array $data, array $sensitiveFields = []): array
    {
        $defaultSensitiveFields = [
            'adresse', 'telephone', 'code_postal', 'ville',
            'adresse2', 'code_postal2', 'ville2',
            'adresse3', 'code_postal3', 'ville3',
        ];
        $fieldsToEncrypt = empty($sensitiveFields) ? $defaultSensitiveFields : $sensitiveFields;

        foreach ($fieldsToEncrypt as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                $data[$field] = $this->encrypt($data[$field]);
            }
        }

        return $data;
    }

    /**
     * Déchiffre un tableau de données sensibles
     */
    public function decryptArray(array $data, array $sensitiveFields = []): array
    {
        $defaultSensitiveFields = [
            'adresse', 'telephone', 'code_postal', 'ville',
            'adresse2', 'code_postal2', 'ville2',
            'adresse3', 'code_postal3', 'ville3',
        ];
        $fieldsToDecrypt = empty($sensitiveFields) ? $defaultSensitiveFields : $sensitiveFields;

        foreach ($fieldsToDecrypt as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                try {
                    $data[$field] = $this->decrypt($data[$field]);
                } catch (\Exception $e) {
                    // Laisser la valeur originale en cas d'erreur inattendue
                    error_log("EncryptionService - Erreur déchiffrement du champ '$field': " . $e->getMessage());
                }
            }
        }

        return $data;
    }

    /**
     * Génère la clé de chiffrement par défaut (legacy)
     */
    private function generateDefaultKey(): string
    {
        return hash('sha256', $_SERVER['APP_LEGACY_ENCRYPTION_KEY'] ?? $_ENV['APP_LEGACY_ENCRYPTION_KEY'] ?? '', true);
    }

    /**
     * Vérifie si une valeur ressemble à des données chiffrées.
     * Une donnée chiffrée valide fait au moins 44 caractères (base64 de 32 bytes).
     */
    public function isEncrypted(string $data): bool
    {
        if (strlen($data) < self::MIN_ENCRYPTED_LENGTH) {
            return false;
        }

        $decoded = base64_decode($data, true);
        return $decoded !== false && strlen($decoded) >= 17;
    }
}
