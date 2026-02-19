<?php

namespace App\Repository;

use App\Entity\Workspace;
use App\Service\WorkspaceResolver;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Base repository class that automatically filters entities by workspace
 * All entity repositories should extend this class to ensure workspace isolation
 */
abstract class WorkspaceAwareRepository extends ServiceEntityRepository
{
    protected WorkspaceResolver $workspaceResolver;

    public function __construct(
        ManagerRegistry $registry,
        string $entityClass,
        WorkspaceResolver $workspaceResolver
    ) {
        parent::__construct($registry, $entityClass);
        $this->workspaceResolver = $workspaceResolver;
    }

    /**
     * Get the current workspace
     */
    protected function getCurrentWorkspace(): ?Workspace
    {
        return $this->workspaceResolver->getCurrentWorkspace();
    }

    /**
     * Get the current workspace ID
     */
    protected function getCurrentWorkspaceId(): ?int
    {
        return $this->workspaceResolver->getCurrentWorkspaceId();
    }

    /**
     * Create a query builder with automatic workspace filtering
     *
     * @param string $alias The alias for the entity (e.g., 'c' for Contact)
     * @param string|null $indexBy The index to use
     * @return QueryBuilder
     */
    public function createQueryBuilder($alias, $indexBy = null): QueryBuilder
    {
        $qb = parent::createQueryBuilder($alias, $indexBy);

        // Automatically add workspace filter if workspace is set
        $workspaceId = $this->getCurrentWorkspaceId();

        if ($workspaceId !== null) {
            $qb->andWhere($alias . '.workspace = :workspace')
               ->setParameter('workspace', $workspaceId);
        }

        return $qb;
    }

    /**
     * Override find() to ensure workspace filtering
     *
     * @param mixed $id
     * @param int|null $lockMode
     * @param int|null $lockVersion
     * @return object|null
     */
    public function find($id, $lockMode = null, $lockVersion = null): ?object
    {
        $workspaceId = $this->getCurrentWorkspaceId();

        if ($workspaceId === null) {
            // No workspace context, return null for security
            return null;
        }

        $entity = parent::find($id, $lockMode, $lockVersion);

        // Verify the entity belongs to the current workspace
        if ($entity && method_exists($entity, 'getWorkspace')) {
            $entityWorkspace = $entity->getWorkspace();

            if (!$entityWorkspace || $entityWorkspace->getId() !== $workspaceId) {
                // Entity belongs to a different workspace, return null
                return null;
            }
        }

        return $entity;
    }

    /**
     * Override findOneBy() to ensure workspace filtering
     */
    public function findOneBy(array $criteria, ?array $orderBy = null): ?object
    {
        $workspaceId = $this->getCurrentWorkspaceId();

        if ($workspaceId !== null) {
            $criteria['workspace'] = $workspaceId;
        }

        return parent::findOneBy($criteria, $orderBy);
    }

    /**
     * Override findBy() to ensure workspace filtering
     */
    public function findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null): array
    {
        $workspaceId = $this->getCurrentWorkspaceId();

        if ($workspaceId !== null) {
            $criteria['workspace'] = $workspaceId;
        }

        return parent::findBy($criteria, $orderBy, $limit, $offset);
    }

    /**
     * Override findAll() to ensure workspace filtering
     */
    public function findAll(): array
    {
        $workspaceId = $this->getCurrentWorkspaceId();

        if ($workspaceId !== null) {
            return parent::findBy(['workspace' => $workspaceId]);
        }

        return parent::findAll();
    }

    /**
     * Check if workspace context is available
     */
    protected function hasWorkspace(): bool
    {
        return $this->workspaceResolver->hasWorkspace();
    }

    /**
     * Create a query builder WITHOUT automatic workspace filtering
     * Use this only when you explicitly need to query across workspaces
     * (e.g., for admin functions, migrations, etc.)
     *
     * @param string $alias
     * @param string|null $indexBy
     * @return QueryBuilder
     */
    protected function createQueryBuilderWithoutWorkspaceFilter($alias, $indexBy = null): QueryBuilder
    {
        return parent::createQueryBuilder($alias, $indexBy);
    }
}
