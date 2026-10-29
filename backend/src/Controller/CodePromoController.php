<?php

namespace App\Controller;

use App\Entity\CodePromo;
use App\Service\TokenService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/code-promo', name: 'api_code_promo_')]
class CodePromoController extends SecuredApiController
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em, TokenService $tokenService)
    {
        parent::__construct($tokenService);
        $this->em = $em;
    }

    // ─── Valider un code (public) ──────────────────────────────────────────────

    #[Route('/validate', name: 'validate', methods: ['POST'])]
    public function validate(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $code = trim($data['code'] ?? '');

        if (!$code) {
            return new JsonResponse(['valid' => false, 'message' => 'Code vide'], 400);
        }

        $promo = $this->em->getRepository(CodePromo::class)->findOneBy(['code' => $code, 'actif' => true]);

        if (!$promo) {
            return new JsonResponse(['valid' => false, 'message' => 'Code promo invalide ou désactivé']);
        }

        return new JsonResponse([
            'valid'   => true,
            'remise'  => (float) $promo->getRemise(),
            'code'    => $promo->getCode(),
        ]);
    }

    // ─── Liste (admin) ─────────────────────────────────────────────────────────

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $auth = $this->requireAdmin($request);
        if ($auth instanceof JsonResponse) return $auth;

        $promos = $this->em->getRepository(CodePromo::class)->findBy([], ['dateCreation' => 'DESC']);

        $data = array_map(fn(CodePromo $p) => [
            'id'             => $p->getId(),
            'code'           => $p->getCode(),
            'remise'         => (float) $p->getRemise(),
            'actif'          => $p->isActif(),
            'date_creation'  => $p->getDateCreation()->format('Y-m-d H:i:s'),
        ], $promos);

        return new JsonResponse($data);
    }

    // ─── Créer (admin) ────────────────────────────────────────────────────────

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $auth = $this->requireAdmin($request);
        if ($auth instanceof JsonResponse) return $auth;

        $data = json_decode($request->getContent(), true);

        $code   = strtoupper(trim($data['code'] ?? ''));
        $remise = (float) ($data['remise'] ?? 0);

        if (!$code) {
            return new JsonResponse(['error' => 'Le code est requis'], 400);
        }
        if ($remise <= 0 || $remise > 100) {
            return new JsonResponse(['error' => 'La remise doit être entre 1 et 100'], 400);
        }

        $existing = $this->em->getRepository(CodePromo::class)->findOneBy(['code' => $code]);
        if ($existing) {
            return new JsonResponse(['error' => 'Ce code existe déjà'], 400);
        }

        $promo = new CodePromo();
        $promo->setCode($code);
        $promo->setRemise((string) $remise);
        $promo->setActif($data['actif'] ?? true);

        $this->em->persist($promo);
        $this->em->flush();

        return new JsonResponse([
            'id'            => $promo->getId(),
            'code'          => $promo->getCode(),
            'remise'        => (float) $promo->getRemise(),
            'actif'         => $promo->isActif(),
            'date_creation' => $promo->getDateCreation()->format('Y-m-d H:i:s'),
        ], 201);
    }

    // ─── Modifier (admin) ─────────────────────────────────────────────────────

    #[Route('/{id}', name: 'update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $auth = $this->requireAdmin($request);
        if ($auth instanceof JsonResponse) return $auth;

        $promo = $this->em->getRepository(CodePromo::class)->find($id);
        if (!$promo) {
            return new JsonResponse(['error' => 'Code promo introuvable'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['remise'])) {
            $remise = (float) $data['remise'];
            if ($remise <= 0 || $remise > 100) {
                return new JsonResponse(['error' => 'La remise doit être entre 1 et 100'], 400);
            }
            $promo->setRemise((string) $remise);
        }
        if (isset($data['actif'])) {
            $promo->setActif((bool) $data['actif']);
        }
        if (isset($data['code'])) {
            $newCode = strtoupper(trim($data['code']));
            $existing = $this->em->getRepository(CodePromo::class)->findOneBy(['code' => $newCode]);
            if ($existing && $existing->getId() !== $id) {
                return new JsonResponse(['error' => 'Ce code existe déjà'], 400);
            }
            $promo->setCode($newCode);
        }

        $this->em->flush();

        return new JsonResponse([
            'id'            => $promo->getId(),
            'code'          => $promo->getCode(),
            'remise'        => (float) $promo->getRemise(),
            'actif'         => $promo->isActif(),
            'date_creation' => $promo->getDateCreation()->format('Y-m-d H:i:s'),
        ]);
    }

    // ─── Supprimer (admin) ────────────────────────────────────────────────────

    #[Route('/{id}', name: 'delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id, Request $request): JsonResponse
    {
        $auth = $this->requireAdmin($request);
        if ($auth instanceof JsonResponse) return $auth;

        $promo = $this->em->getRepository(CodePromo::class)->find($id);
        if (!$promo) {
            return new JsonResponse(['error' => 'Code promo introuvable'], 404);
        }

        $this->em->remove($promo);
        $this->em->flush();

        return new JsonResponse(['message' => 'Code promo supprimé']);
    }
}
