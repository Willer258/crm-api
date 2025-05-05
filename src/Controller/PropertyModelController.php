<?php

namespace App\Controller;

use App\Entity\PropertyModel;
use App\Managers\PropertyModelManager;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/property/model', name: 'app_property_model_')]
final class PropertyModelController extends AbstractController
{
    public function __construct(private ManagerRegistry $managerRegistry) {}
    #[Route('/list', name: 'list' , methods: ['GET'] , options: ['description'=> 'Liste des modeles de proprietes'])]
    public function getPropertiesModel(): Response
    {
        $propertiesModel = $this->managerRegistry->getRepository(PropertyModel::class)->findAll();
        return $this->json(['status' => 'success', 'propertiesModel' => $propertiesModel], 200, [], ['groups' => 'property_model:list']);
    }

    #[Route('/edit', name: 'edit' , options: ['description'=> 'Modifier/Creer un modele de propriete'])]
    public function editModelProperty(Request $request , PropertyModelManager $propertyModelManager): Response
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data)) {
            return $this->json(['status' => 'error', 'message' => 'Invalid data'], 400);
        }
        $propertyModel = $propertyModelManager->edit($data);
      
        if ($propertyModel instanceof PropertyModel) {
            return $this->json(['status' => 'success', 'propertyModel' => $propertyModel], 200, [], ['groups' => 'property_model:edit']);
        }

        return $this->json(['status' => 'error', 'message' => 'Property model not found'], 404);
    }


    #[Route('/delete/{id}', name: 'delete', options: ['description'=> 'Supprimer un modele de propriete si il n\'est pas lie a une propriete'])]
    public function deleteModelProperty($id): Response
    {
      
        $propertyModel = $this->managerRegistry->getRepository(PropertyModel::class)->findOneBy(['id' => $id]);
        if ($propertyModel instanceof PropertyModel) {

            if ($propertyModel->getProperties()->count() > 0) {
                return $this->json(['status' => 'error', 'message' => 'Des proprietes sont liees au modele vous ne pouvez pas les supprimer'], 400);
            }
            $this->managerRegistry->getManager()->remove($propertyModel);
            $this->managerRegistry->getManager()->flush();
            return $this->json(['status' => 'success', 'message' => 'Property model deleted'], 200);
        }
      
        return $this->json(['status' => 'error', 'message' => 'Property model not found'], 404);

    }
}
