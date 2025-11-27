<?php

namespace App\Managers;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Ramsey\Uuid\Uuid;

class UserManager extends Manager
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        $this->em = $em;
    }

    /**
     * Crée un nouvel utilisateur
     */
    public function createUser(array $data): User
    {
        // Validation
        if (empty($data['email'])) {
            throw new \InvalidArgumentException('Email requis');
        }

        if (empty($data['password'])) {
            throw new \InvalidArgumentException('Mot de passe requis');
        }

        // Vérifier unicité email
        $existingUser = $this->userRepository->findOneBy(['email' => $data['email']]);
        if ($existingUser) {
            throw new \RuntimeException('Un utilisateur avec cet email existe déjà');
        }

        $user = new User();
        $user->setEmail($data['email']);
        $user->setCode($data['code'] ?? uniqid('user_'));
        $user->setUuid(Uuid::uuid4());

        // Hash password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        // Rôles
        $roles = $data['roles'] ?? ['ROLE_USER'];
        $user->setRoles($roles);

        // Godfather
        if (isset($data['godfather'])) {
            $user->setGodfather($data['godfather']);
        }

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    /**
     * Met à jour un utilisateur
     */
    public function updateUser(User $user, array $data): User
    {
        // Mise à jour email
        if (isset($data['email']) && $data['email'] !== $user->getEmail()) {
            $existingUser = $this->userRepository->findOneBy(['email' => $data['email']]);
            if ($existingUser && $existingUser->getId() !== $user->getId()) {
                throw new \RuntimeException('Cet email est déjà utilisé');
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

        $this->em->flush();

        return $user;
    }

    /**
     * Change le mot de passe d'un utilisateur
     */
    public function changePassword(User $user, string $newPassword, ?string $oldPassword = null): void
    {
        // Vérifier ancien password si fourni
        if ($oldPassword !== null) {
            if (!$this->passwordHasher->isPasswordValid($user, $oldPassword)) {
                throw new \RuntimeException('Ancien mot de passe incorrect');
            }
        }

        // Valider le nouveau mot de passe
        if (strlen($newPassword) < 8) {
            throw new \InvalidArgumentException('Le mot de passe doit contenir au moins 8 caractères');
        }

        $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);

        $this->em->flush();
    }

    /**
     * Supprime un utilisateur
     */
    public function deleteUser(User $user): void
    {
        $this->em->remove($user);
        $this->em->flush();
    }

    /**
     * Vérifie si un email existe déjà
     */
    public function emailExists(string $email, ?int $excludeUserId = null): bool
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            return false;
        }

        if ($excludeUserId !== null && $user->getId() === $excludeUserId) {
            return false;
        }

        return true;
    }

    /**
     * Valide les données d'un utilisateur
     */
    public function validateUserData(array $data, bool $isCreation = true): array
    {
        $errors = [];

        // Email
        if ($isCreation && empty($data['email'])) {
            $errors['email'] = 'Email requis';
        } elseif (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email invalide';
        }

        // Password (uniquement à la création)
        if ($isCreation) {
            if (empty($data['password'])) {
                $errors['password'] = 'Mot de passe requis';
            } elseif (strlen($data['password']) < 8) {
                $errors['password'] = 'Le mot de passe doit contenir au moins 8 caractères';
            }
        }

        // Rôles
        if (isset($data['roles'])) {
            $allowedRoles = ['ROLE_USER', 'ROLE_MANAGER', 'ROLE_ADMIN', 'ROLE_CUSTOMER', 'ROLE_PRE_AUTH'];
            foreach ($data['roles'] as $role) {
                if (!in_array($role, $allowedRoles)) {
                    $errors['roles'] = "Rôle invalide: $role";
                    break;
                }
            }
        }

        return $errors;
    }

    /**
     * Recherche des utilisateurs
     */
    public function searchUsers(string $query, int $limit = 10): array
    {
        return $this->userRepository->createQueryBuilder('u')
            ->where('u.email LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
