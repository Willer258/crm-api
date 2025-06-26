<?php

namespace App\Controller;

use App\Entity\Property;
use App\Managers\PropertyManager;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/property', name: 'app_property_', options: ['description' => 'Gestion des propriétés'])]
final class PropertyController extends AbstractController
{

    #[Route('/list', name: 'list', methods: ['GET'], options: ['description' => 'Liste toutes les propriétés'])]
    public function listProperties(): Response
    {
        return $this->render('property/index.html.twig', [
            'controller_name' => 'PropertyController',
        ]);
    }

    #[Route('/edit', name: 'edit', methods: ['POST'], options: ['description' => 'Edite une propriété'])]
    public function editProperty(PropertyManager $propertyManager,Request $request , ManagerRegistry $managerRegistry): Response
    {
        $data = json_decode($request->getContent(), true);
        $property = $propertyManager->edit( $data);
        if ($property instanceof Property) {
           
            $managerRegistry->getManager()->persist($property);
            $managerRegistry->getManager()->flush();
        }

        if ($property instanceof Property) {
            return $this->json(['status' => 'success', 'property' => $property], 200, [], ['groups' => 'contact:info']);
        }

        return $this->json(['status' => 'error', 'message' => 'Impossible de créer ou modifier la propriété'], 500);
    }

    #[Route('/delete/{id}', name: 'delete', options: ['description' => 'Supprime une propriété'])]
    public function deleteProperty(PropertyManager $propertyManager, $id): Response
    {

        $property = $propertyManager->delete($id);


        if ($property == true) {
            return $this->json(['status' => 'success', 'message' => 'Property  deleted'], 200);
        } else {
            return $this->json(['status' => 'success', 'message' => 'Property  not deletd'], 400);
        }
    }
}
