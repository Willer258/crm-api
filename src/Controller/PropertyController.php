<?php

namespace App\Controller;

use App\Managers\PropertyManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
