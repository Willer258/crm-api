<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-super-admin',
    description: 'Create a super admin user for backoffice access'
)]
class CreateSuperAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::OPTIONAL, 'Admin email address')
            ->addArgument('password', InputArgument::OPTIONAL, 'Admin password')
            ->addOption('code', 'c', InputOption::VALUE_REQUIRED, 'User code (unique identifier)')
            ->setHelp('This command creates a super admin user with ROLE_SUPER_ADMIN');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Ensure database exists and schema is created
        $this->ensureDatabaseSetup($io);

        // Get email from argument or ask interactively
        $email = $input->getArgument('email');
        if (!$email) {
            $email = $io->ask('Admin email address', 'admin@crm.com');
        }

        // Check if user already exists
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            $io->warning("User with email '{$email}' already exists.");

            $updateRoles = $io->confirm('Do you want to update this user to ROLE_SUPER_ADMIN?', false);
            if ($updateRoles) {
                $roles = $existingUser->getRoles();
                if (!in_array('ROLE_SUPER_ADMIN', $roles)) {
                    $roles[] = 'ROLE_SUPER_ADMIN';
                    $existingUser->setRoles($roles);
                    $this->entityManager->flush();
                    $io->success("User '{$email}' has been granted ROLE_SUPER_ADMIN!");
                } else {
                    $io->info("User '{$email}' already has ROLE_SUPER_ADMIN.");
                }
            }

            return Command::SUCCESS;
        }

        // Get password from argument or ask interactively
        $password = $input->getArgument('password');
        if (!$password) {
            $password = $io->askHidden('Admin password (min 6 characters)');

            if (strlen($password) < 6) {
                $io->error('Password must be at least 6 characters long.');
                return Command::FAILURE;
            }

            $confirmPassword = $io->askHidden('Confirm password');
            if ($password !== $confirmPassword) {
                $io->error('Passwords do not match.');
                return Command::FAILURE;
            }
        }

        // Get or generate code
        $code = $input->getOption('code');
        if (!$code) {
            $code = 'ADMIN-' . strtoupper(substr(md5($email), 0, 8));
        }

        // Create new user
        $user = new User();
        $user->setEmail($email);
        $user->setCode($code);
        $user->setUuid(Uuid::uuid4());
        $user->setRoles(['ROLE_SUPER_ADMIN']);

        // Hash password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        // Persist user
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success('Super admin user created successfully!');
        $io->table(
            ['Field', 'Value'],
            [
                ['Email', $email],
                ['Code', $code],
                ['UUID', $user->getUuid()],
                ['Roles', implode(', ', $user->getRoles())],
            ]
        );

        $io->note([
            'You can now login at: http://localhost:8000/admin/login',
            "Email: {$email}",
            "Password: [the password you entered]"
        ]);

        return Command::SUCCESS;
    }

    /**
     * Ensure database and schema exist before creating user
     */
    private function ensureDatabaseSetup(SymfonyStyle $io): void
    {
        $connection = $this->entityManager->getConnection();

        try {
            // Try to connect to the database
            $connection->executeQuery('SELECT 1');
            $io->note('Database connection successful');
        } catch (\Exception $e) {
            // Database doesn't exist, try to create it
            $io->warning('Database does not exist. Creating database...');

            try {
                // Get database name from connection params
                $params = $connection->getParams();
                $dbName = $params['dbname'] ?? 'crm-api';

                // Connect without database name
                $tmpConnection = \Doctrine\DBAL\DriverManager::getConnection([
                    'driver' => $params['driver'] ?? 'pdo_mysql',
                    'host' => $params['host'] ?? '127.0.0.1',
                    'port' => $params['port'] ?? 3306,
                    'user' => $params['user'] ?? 'root',
                    'password' => $params['password'] ?? '',
                ]);

                // Create database
                $tmpConnection->executeStatement(sprintf('CREATE DATABASE IF NOT EXISTS `%s`', $dbName));
                $tmpConnection->close();

                $io->success("Database '{$dbName}' created successfully!");

                // Reconnect to the new database
                if (!$connection->isConnected()) {
                    $connection->connect();
                }
            } catch (\Exception $createException) {
                $io->error('Failed to create database: ' . $createException->getMessage());
                throw $createException;
            }
        }

        // Now ensure schema exists
        try {
            // Check if user table exists
            $schemaManager = $connection->createSchemaManager();
            $tables = $schemaManager->listTableNames();

            if (!in_array('user', $tables)) {
                $io->warning('Database schema not found. Creating schema...');

                // Use Doctrine to create schema
                $metadatas = $this->entityManager->getMetadataFactory()->getAllMetadata();
                $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
                $schemaTool->createSchema($metadatas);

                $io->success('Database schema created successfully!');
            } else {
                $io->note('Database schema already exists');
            }
        } catch (\Exception $schemaException) {
            $io->error('Failed to create schema: ' . $schemaException->getMessage());
            throw $schemaException;
        }
    }
}
