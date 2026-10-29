<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/contact', name: 'api_contact_')]
class ContactController extends AbstractController
{
    #[Route('', name: 'send', methods: ['POST'])]
    public function send(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $name    = trim($data['name'] ?? '');
        $email   = trim($data['email'] ?? '');
        $message = trim($data['message'] ?? '');

        if (empty($name) || empty($email) || empty($message)) {
            return new JsonResponse(['error' => 'Tous les champs sont requis'], 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(['error' => 'Adresse email invalide'], 400);
        }

        if (mb_strlen($message) < 10) {
            return new JsonResponse(['error' => 'Le message est trop court (minimum 10 caractères)'], 400);
        }

        // Sanitiser les entrées pour le log
        $safeName    = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $safeEmail   = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
        $safeMessage = htmlspecialchars(mb_substr($message, 0, 1000), ENT_QUOTES, 'UTF-8');

        // Enregistrer le message en log (emails non configurés)
        error_log(sprintf(
            '[CONTACT] Nouveau message - De: %s <%s> | Message: %s',
            $safeName,
            $safeEmail,
            $safeMessage
        ));

        return new JsonResponse([
            'message' => 'Votre message a bien été reçu. Nous vous répondrons dans les plus brefs délais.'
        ]);
    }
}
