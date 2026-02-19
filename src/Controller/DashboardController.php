<?php

namespace App\Controller;

use App\Entity\Deal;
use App\Entity\Tag;
use App\Entity\Contact;
use App\Entity\Activity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/dashboard', name: 'dashboard_')]
final class DashboardController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    /**
     * Endpoint pour les KPIs du CRM Dashboard
     * Retourne les statistiques des deals, contacts, et activités
     */
    #[Route('/crm-kpis', name: 'crm_kpis', methods: ['POST'], options: ['description' => 'Retourne les KPIs du CRM Dashboard'])]
    public function crmKpis(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $startDate = isset($data['startDate']) ? new \DateTime($data['startDate']) : new \DateTime('first day of this month');
        $endDate = isset($data['endDate']) ? new \DateTime($data['endDate'] . ' 23:59:59') : new \DateTime('last day of this month 23:59:59');

        $dealRepo = $this->em->getRepository(Deal::class);
        $contactRepo = $this->em->getRepository(Contact::class);
        $activityRepo = $this->em->getRepository(Activity::class);

        // Statistiques des deals
        $totalDeals = $dealRepo->getCount();
        $wonDeals = $dealRepo->getWonCount();
        $lostDeals = $dealRepo->getLostCount();
        $openDeals = $dealRepo->getCount(['filters' => ['status' => 'open']]);

        // Statistiques des contacts
        $totalContacts = $contactRepo->getCount();

        // Statistiques des activités pour la période
        $activitiesStats = $this->getActivitiesStats($startDate, $endDate);

        // Deals par statut pour le graphique
        $dealsByStatus = [
            ['status' => 'open', 'count' => $openDeals, 'label' => 'En cours'],
            ['status' => 'win', 'count' => $wonDeals, 'label' => 'Gagnées'],
            ['status' => 'lost', 'count' => $lostDeals, 'label' => 'Perdues'],
        ];

        // Valeur totale des deals (si le champ value existe)
        $totalValue = $this->getDealsValue();

        return $this->json([
            'status' => 'success',
            'deals' => [
                'total' => $totalDeals,
                'open' => $openDeals,
                'won' => $wonDeals,
                'lost' => $lostDeals,
                'byStatus' => $dealsByStatus,
                'value' => $totalValue,
            ],
            'contacts' => [
                'total' => $totalContacts,
            ],
            'activities' => $activitiesStats,
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * Endpoint pour les statistiques clients vs prospects
     * Différencie par les tags des contacts
     */
    #[Route('/clients-prospects', name: 'clients_prospects', methods: ['POST'], options: ['description' => 'Retourne les statistiques clients vs prospects'])]
    public function clientsProspects(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $startDate = isset($data['startDate']) ? new \DateTime($data['startDate']) : null;
        $endDate = isset($data['endDate']) ? new \DateTime($data['endDate'] . ' 23:59:59') : null;
        $managers = $data['managers'] ?? [];

        // Récupérer les comptages par tag (filtré par managers responsables)
        $tagStats = $this->getContactsByTag($startDate, $endDate, $managers);

        // Chercher les tags "client" et "prospect"
        $clients = 0;
        $prospects = 0;
        $others = 0;

        foreach ($tagStats as $stat) {
            $tagCode = strtolower($stat['code'] ?? '');
            $tagLabel = strtolower($stat['label'] ?? '');

            if (str_contains($tagCode, 'client') || str_contains($tagLabel, 'client')) {
                $clients += $stat['count'];
            } elseif (str_contains($tagCode, 'prospect') || str_contains($tagLabel, 'prospect')) {
                $prospects += $stat['count'];
            } else {
                $others += $stat['count'];
            }
        }

        // Total des contacts sans tag (filtré par managers responsables)
        $contactsWithoutTag = $this->getContactsWithoutTag($startDate, $endDate, $managers);

        return $this->json([
            'status' => 'success',
            'clients' => $clients,
            'prospects' => $prospects + $contactsWithoutTag, // Considérer les contacts sans tag comme prospects
            'others' => $others,
            'byTag' => $tagStats,
            'total' => $clients + $prospects + $others + $contactsWithoutTag,
        ]);
    }

    /**
     * Endpoint pour les statistiques des deals par pipeline/étape
     */
    #[Route('/deals-by-pipeline', name: 'deals_by_pipeline', methods: ['POST'], options: ['description' => 'Retourne les deals groupés par pipeline et étape'])]
    public function dealsByPipeline(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $qb = $this->em->createQueryBuilder();
        $qb->select('COUNT(d.id) as count', 's.id as stepId', 's.name as stepName', 'p.id as pipelineId', 'p.name as pipelineName')
            ->from(Deal::class, 'd')
            ->leftJoin('d.step', 's')
            ->leftJoin('s.pipeline', 'p')
            ->where('d.removeAt IS NULL')
            ->andWhere('d.status IS NULL') // Seulement les deals ouverts
            ->groupBy('s.id', 'p.id');

        $results = $qb->getQuery()->getResult();

        // Organiser par pipeline
        $pipelines = [];
        foreach ($results as $row) {
            $pipelineId = $row['pipelineId'];
            if (!isset($pipelines[$pipelineId])) {
                $pipelines[$pipelineId] = [
                    'id' => $pipelineId,
                    'name' => $row['pipelineName'],
                    'steps' => [],
                    'total' => 0,
                ];
            }
            $pipelines[$pipelineId]['steps'][] = [
                'id' => $row['stepId'],
                'name' => $row['stepName'],
                'count' => (int) $row['count'],
            ];
            $pipelines[$pipelineId]['total'] += (int) $row['count'];
        }

        return $this->json([
            'status' => 'success',
            'pipelines' => array_values($pipelines),
        ]);
    }

    /**
     * Endpoint pour les activités à venir (prochains jours)
     */
    #[Route('/upcoming-activities', name: 'upcoming_activities', methods: ['POST'], options: ['description' => 'Retourne les activités à venir'])]
    public function upcomingActivities(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $days = $data['days'] ?? 7;
        $limit = min($data['limit'] ?? 50, 100);

        $now = new \DateTime();
        $endDate = (clone $now)->modify("+{$days} days");

        $qb = $this->em->createQueryBuilder();
        $qb->select('a')
            ->from(Activity::class, 'a')
            ->where('a.removeAt IS NULL')
            ->andWhere('a.performed = false OR a.performed IS NULL')
            ->andWhere('a.startDate >= :now')
            ->andWhere('a.startDate <= :endDate')
            ->setParameter('now', $now)
            ->setParameter('endDate', $endDate)
            ->orderBy('a.startDate', 'ASC')
            ->setMaxResults($limit);

        // Filtrer par manager si l'utilisateur est connecté
        if ($this->getUser()) {
            $qb->andWhere('a.manager LIKE :manager')
                ->setParameter('manager', '%' . $this->getUser()->getUserIdentifier() . '%');
        }

        $activities = $qb->getQuery()->getResult();

        return $this->json([
            'status' => 'success',
            'activities' => $activities,
            'count' => count($activities),
        ], 200, [], ['groups' => ['activity:read', 'userManagement']]);
    }

    /**
     * Statistiques des activités pour une période donnée
     */
    private function getActivitiesStats(\DateTime $startDate, \DateTime $endDate): array
    {
        $qb = $this->em->createQueryBuilder();

        // Total des activités dans la période
        $qb->select('COUNT(a.id) as total')
            ->from(Activity::class, 'a')
            ->where('a.removeAt IS NULL')
            ->andWhere('a.startDate >= :start')
            ->andWhere('a.startDate <= :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate);

        $total = $qb->getQuery()->getSingleScalarResult();

        // Activités réalisées
        $qb2 = $this->em->createQueryBuilder();
        $qb2->select('COUNT(a.id) as performed')
            ->from(Activity::class, 'a')
            ->where('a.removeAt IS NULL')
            ->andWhere('a.performed = true')
            ->andWhere('a.startDate >= :start')
            ->andWhere('a.startDate <= :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate);

        $performed = $qb2->getQuery()->getSingleScalarResult();

        // Activités en retard (date passée mais non réalisées)
        $qb3 = $this->em->createQueryBuilder();
        $qb3->select('COUNT(a.id) as overdue')
            ->from(Activity::class, 'a')
            ->where('a.removeAt IS NULL')
            ->andWhere('(a.performed = false OR a.performed IS NULL)')
            ->andWhere('a.startDate < :now')
            ->setParameter('now', new \DateTime());

        $overdue = $qb3->getQuery()->getSingleScalarResult();

        return [
            'total' => (int) $total,
            'performed' => (int) $performed,
            'pending' => (int) $total - (int) $performed,
            'overdue' => (int) $overdue,
        ];
    }

    /**
     * Contacts groupés par tag (filtrés par managers responsables)
     */
    private function getContactsByTag(?\DateTime $startDate, ?\DateTime $endDate, array $managers = []): array
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('COUNT(c.id) as count', 't.id as tagId', 't.label', 't.code', 't.color')
            ->from(Contact::class, 'c')
            ->leftJoin('c.tags', 't')
            ->where('c.removeAt IS NULL')
            ->andWhere('t.id IS NOT NULL')
            ->groupBy('t.id');

        if ($startDate) {
            $qb->andWhere('c.createdAt >= :start')
                ->setParameter('start', $startDate);
        }
        if ($endDate) {
            $qb->andWhere('c.createdAt <= :end')
                ->setParameter('end', $endDate);
        }

        // Filtrer par managers responsables si spécifiés
        if (!empty($managers)) {
            $qb->andWhere('c.manager IN (:managers)')
                ->setParameter('managers', $managers);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Nombre de contacts sans tag (filtrés par managers responsables)
     */
    private function getContactsWithoutTag(?\DateTime $startDate, ?\DateTime $endDate, array $managers = []): int
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('COUNT(DISTINCT c.id)')
            ->from(Contact::class, 'c')
            ->leftJoin('c.tags', 't')
            ->where('c.removeAt IS NULL')
            ->andWhere('t.id IS NULL');

        if ($startDate) {
            $qb->andWhere('c.createdAt >= :start')
                ->setParameter('start', $startDate);
        }
        if ($endDate) {
            $qb->andWhere('c.createdAt <= :end')
                ->setParameter('end', $endDate);
        }

        // Filtrer par managers responsables si spécifiés
        if (!empty($managers)) {
            $qb->andWhere('c.manager IN (:managers)')
                ->setParameter('managers', $managers);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Valeur totale des deals
     */
    private function getDealsValue(): array
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('SUM(d.value) as total', 'd.status')
            ->from(Deal::class, 'd')
            ->where('d.removeAt IS NULL')
            ->groupBy('d.status');

        $results = $qb->getQuery()->getResult();

        $values = [
            'total' => 0,
            'open' => 0,
            'won' => 0,
            'lost' => 0,
        ];

        foreach ($results as $row) {
            $status = $row['status'] ?? 'open';
            $amount = (float) ($row['total'] ?? 0);

            if ($status === Deal::STATUS_WIN) {
                $values['won'] = $amount;
            } elseif ($status === Deal::STATUS_LOST) {
                $values['lost'] = $amount;
            } else {
                $values['open'] = $amount;
            }
            $values['total'] += $amount;
        }

        return $values;
    }
}
