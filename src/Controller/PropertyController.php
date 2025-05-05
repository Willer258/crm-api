<?php

namespace App\Controller;

use App\Managers\PropertyManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/property', name: 'app_property_')]
final class PropertyController extends AbstractController
{

    #[Route('/list', name: 'list')]
    public function listProperties(): Response
    {
        return $this->render('property/index.html.twig', [
            'controller_name' => 'PropertyController',
        ]);
    }

    #[Route('/delete/{id}', name: 'delete')]
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
