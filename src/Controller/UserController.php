<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Ramsey\Uuid\Uuid;

#[Route('/user', name: 'app_user_')]
final class UserController extends AbstractController
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    #[Route('/list', name: 'list', methods: ['POST'], options: ['description' => 'Liste tous les utilisateurs'])]
    public function list(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        // Pagination
        $page = $data['pagination']['page'] ?? 1;
        $limit = $data['pagination']['limit'] ?? 25;
        $offset = ($page - 1) * $limit;

        // Récupérer utilisateurs
        $qb = $this->userRepository->createQueryBuilder('u');

        // Filtres
        if (!empty($data['search'])) {
            $qb->where('u.email LIKE :search')
               ->setParameter('search', '%' . $data['search'] . '%');
        }

        $users = $qb->setMaxResults($limit)
                    ->setFirstResult($offset)
                    ->getQuery()
                    ->getResult();

        $total = $this->userRepository->count([]);

        return $this->json([
            'status' => 'success',
            'users' => $users,
            'page' => $page,
            'limit' => $limit,
            'total' => $total
        ], 200, [], ['groups' => 'userManagement']);
    }

    #[Route('/info/{id}', name: 'info', methods: ['GET'], options: ['description' => 'Affiche les informations d\'un utilisateur'])]
    public function info(int $id): JsonResponse
    {
        $user = $this->userRepository->find($id);

        if (!$user) {
            return $this->json([
                'status' => 'error',
                'message' => 'Utilisateur non trouvé'
            ], 404);
        }

        return $this->json([
            'status' => 'success',
            'user' => $user
        ], 200, [], ['groups' => 'userManagement']);
    }

    #[Route('/create', name: 'create', methods: ['POST'], options: ['description' => 'Crée un nouvel utilisateur'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['email']) || empty($data['password'])) {
            return $this->json([
                'status' => 'error',
                'message' => 'Email et mot de passe requis'
            ], 400);
        }

        // Vérifier si l'email existe déjà
        $existingUser = $this->userRepository->findOneBy(['email' => $data['email']]);
        if ($existingUser) {
            return $this->json([
                'status' => 'error',
                'message' => 'Un utilisateur avec cet email existe déjà'
            ], 409);
        }

        $user = new User();
        $user->setEmail($data['email']);
        $user->setCode($data['code'] ?? uniqid('user_'));
        $user->setUuid(Uuid::uuid4());

        // Hash le mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        // Rôles
        $roles = $data['roles'] ?? ['ROLE_USER'];
        $user->setRoles($roles);

        // Godfather (parrain)
        if (isset($data['godfather'])) {
            $user->setGodfather($data['godfather']);
        }

        $em = $this->managerRegistry->getManager();
        $em->persist($user);
        $em->flush();

        return $this->json([
            'status' => 'success',
            'message' => 'Utilisateur créé avec succès',
            'user' => $user
        ], 201, [], ['groups' => 'userManagement']);
    }

    #[Route('/edit/{id}', name: 'edit', methods: ['PUT'], options: ['description' => 'Modifie un utilisateur'])]
    public function edit(int $id, Request $request): JsonResponse
    {
        $user = $this->userRepository->find($id);

        if (!$user) {
            return $this->json([
                'status' => 'error',
                'message' => 'Utilisateur non trouvé'
            ], 404);
        }

        $data = json_decode($request->getContent(), true);

        // Mise à jour email
        if (isset($data['email'])) {
            // Vérifier si le nouvel email n'est pas déjà utilisé
            $existingUser = $this->userRepository->findOneBy(['email' => $data['email']]);
            if ($existingUser && $existingUser->getId() !== $id) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Cet email est déjà utilisé'
                ], 409);
            }
            $user->setEmail($data['email']);
        }

        // Mise à jour rôles
        if (isset($data['roles'])) {
            $user->setRoles($data['roles']);
        }

        // Mise à jour code
        if (isset($data['code'])) {
            $user->setCode($data['code']);
        }

        // Mise à jour godfather
        if (isset($data['godfather'])) {
            $user->setGodfather($data['godfather']);
        }

        $em = $this->managerRegistry->getManager();
        $em->flush();

        return $this->json([
            'status' => 'success',
            'message' => 'Utilisateur modifié avec succès',
            'user' => $user
        ], 200, [], ['groups' => 'userManagement']);
    }

    #[Route('/change-password/{id}', name: 'change_password', methods: ['POST'], options: ['description' => 'Change le mot de passe d\'un utilisateur'])]
    public function changePassword(int $id, Request $request): JsonResponse
    {
        $user = $this->userRepository->find($id);

        if (!$user) {
            return $this->json([
                'status' => 'error',
                'message' => 'Utilisateur non trouvé'
            ], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (empty($data['newPassword'])) {
            return $this->json([
                'status' => 'error',
                'message' => 'Nouveau mot de passe requis'
            ], 400);
        }

        // Optionnel : vérifier l'ancien mot de passe
        if (isset($data['oldPassword'])) {
            if (!$this->passwordHasher->isPasswordValid($user, $data['oldPassword'])) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Ancien mot de passe incorrect'
                ], 400);
            }
        }

        // Hash le nouveau mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword($user, $data['newPassword']);
        $user->setPassword($hashedPassword);

        $em = $this->managerRegistry->getManager();
        $em->flush();

        return $this->json([
            'status' => 'success',
            'message' => 'Mot de passe modifié avec succès'
        ], 200);
    }

    #[Route('/delete/{id}', name: 'delete', methods: ['DELETE'], options: ['description' => 'Supprime un utilisateur'])]
    public function delete(int $id): JsonResponse
    {
        $user = $this->userRepository->find($id);

        if (!$user) {
            return $this->json([
                'status' => 'error',
                'message' => 'Utilisateur non trouvé'
            ], 404);
        }

        // Empêcher la suppression de son propre compte
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        if ($currentUser && $currentUser->getId() === $id) {
            return $this->json([
                'status' => 'error',
                'message' => 'Vous ne pouvez pas supprimer votre propre compte'
            ], 403);
        }

        $em = $this->managerRegistry->getManager();
        $em->remove($user);
        $em->flush();

        return $this->json([
            'status' => 'success',
            'message' => 'Utilisateur supprimé avec succès'
        ], 200);
    }

    #[Route('/profile', name: 'profile', methods: ['GET'], options: ['description' => 'Récupère le profil de l\'utilisateur connecté'])]
    public function profile(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json([
                'status' => 'error',
                'message' => 'Utilisateur non authentifié'
            ], 401);
        }

        return $this->json([
            'status' => 'success',
            'user' => $user
        ], 200, [], ['groups' => 'userManagement']);
    }

    #[Route('/profile/edit', name: 'profile_edit', methods: ['PUT'], options: ['description' => 'Modifie le profil de l\'utilisateur connecté'])]
    public function editProfile(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json([
                'status' => 'error',
                'message' => 'Utilisateur non authentifié'
            ], 401);
        }

        $data = json_decode($request->getContent(), true);

        // Mise à jour email
        if (isset($data['email'])) {
            $existingUser = $this->userRepository->findOneBy(['email' => $data['email']]);
            if ($existingUser && $existingUser->getId() !== $user->getId()) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Cet email est déjà utilisé'
                ], 409);
            }
            $user->setEmail($data['email']);
        }

        $em = $this->managerRegistry->getManager();
        $em->flush();

        return $this->json([
            'status' => 'success',
            'message' => 'Profil modifié avec succès',
            'user' => $user
        ], 200, [], ['groups' => 'userManagement']);
    }
}
