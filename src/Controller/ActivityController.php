<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Activity;
use App\Repository\ActivityRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Managers\ActivityManager;
use PhpParser\Node\Expr\Instanceof_;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/activity', name: 'app_activity')]
final class ActivityController extends AbstractController
{

    #[Route('/list', name: 'activity_index', methods: ['GET'], options: ['description' => 'Liste toutes les activités avec filtres'])]
    public function index(Request $request, ActivityRepository $activityRepository, SerializerInterface $serializer): JsonResponse
    {
        $filters = json_decode($request->getContent(), true) ?: [];
        $activities = $activityRepository->findByFilters($filters);
        $json = $serializer->serialize($activities, 'json', ['groups' => ['activity:read']]);
        return new JsonResponse($json, 200, [], true);
    }

    #[Route('/calendar', name: 'activity_calendar', methods: ['POST'], options: ['description' => 'Liste les activités entre deux dates, groupées par date d\'exécution'])]
    public function calendar(Request $request, ActivityRepository $activityRepository, SerializerInterface $serializer): JsonResponse
    {
        $startDate = $request->query->get('startDate');
        $endDate = $request->query->get('endDate');

        $data = json_decode($request->getContent(), true);

        $data['managers'][] = $this->getUser()->getUserIdentifier();
       
        // Valeurs par défaut : mois courant
        if (!$startDate || !$endDate) {
            $now = new \DateTimeImmutable();
            if (!$startDate) {
                $startDate = $now->modify('first day of this month')->format('Y-m-d');
            }
            if (!$endDate) {
                $endDate = $now->modify('last day of this month')->format('Y-m-d');
            }
        }
        $dealId = $request->query->get('deal');
        $results = $activityRepository->findByDateRangeGrouped($startDate, $endDate, $dealId , $data);
        // Sérialiser chaque groupe de date
        $calendar = [];
        foreach ($results as $date => $activities) {
            $calendar[$date] = json_decode($serializer->serialize($activities, 'json', ['groups' => ['activity:read']]));
        }
        return $this->json($calendar);
    }

    #[Route('/{id}', name: 'activity_show', requirements: ['id' => '\d+'], methods: ['GET'], options: ['description' => 'Affiche le détail d\'une activité'])]
    public function show(int $id, ActivityRepository $activityRepository, SerializerInterface $serializer): JsonResponse
    {
        $activity = $activityRepository->find($id);
        if (!$activity) {
            return new JsonResponse(['status' => 'error', 'message' => 'Activité non trouvée'], 404);
        }
        $json = $serializer->serialize($activity, 'json', ['groups' => ['activity:read']]);
        return new JsonResponse($json, 200, [], true);
    }

    #[Route('/edit', name: 'activity_edit', methods: ['POST'], options: ['description' => 'Crée une nouvelle activité'])]
    public function edit(Request $request, ActivityManager $activityManager, ActivityRepository $activityRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $activity = null;
        if (isset($data['id'])) {
            $activity = $activityRepository->findOneBy(['id' => $data['id']]);
        }

        if (!$activity instanceof Activity) {
            $activity = new Activity();
        }

        $activity = $activityManager->updateFromArray($activity, $data);
        return $this->json(['status' => 'success', 'activity' => $activity], 200, [], ['groups' => ['contact:info' , 'userManagement', 'infos']]);
    }



    #[Route('/delete/{id}', name: 'activity_delete', requirements: ['id' => '\d+'], methods: ['DELETE'], options: ['description' => 'Supprime une activité'])]
    public function delete($id, ActivityManager $activityManager): JsonResponse
    {
        if ($id) {
            $activityManager->delete($id);
        }
        return $this->json(['status' => 'success', 'message' => 'Activité supprimée'], 200);
    }
}
