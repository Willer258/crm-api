<?php

namespace App\Managers;

use App\Entity\ItemType;
use App\Entity\Property;
use App\Entity\PropertyModel;
use Doctrine\Persistence\ManagerRegistry;
use App\Managers\PropertyManager;

class PropertyModelManager extends Manager
{


     public function __construct(private ManagerRegistry $registry , private PropertyManager $propertyManager) {}

    public function edit($data) : ?PropertyModel
    {

        $propertyModel = null;

        if (isset($data['id'])) {
            $propertyModel = $this->registry->getManager()->getRepository(PropertyModel::class)->findOneBy(['id' => $data['id']]);
        }

        if (!($propertyModel instanceof PropertyModel)) {
            $propertyModel = new PropertyModel();
        }

        if (isset($data['label'])) {
            $propertyModel->setLabel($data['label']);
        }

        if (isset($data['identifier'])) {
            $propertyModel->setIdentifier($data['identifier']);
        } else {
            $propertyModel->setIdentifier(false);
        }

        if (isset($data['type'])) {
            $propertyModel->setType($data['type']);
        }

        if (isset($data['itemType'])) {
            $itemType = $this->registry->getManager()->getRepository(ItemType::class)->findOneBy(['code' => $data['itemType']]);
            if ($itemType instanceof ItemType) {
                $propertyModel->setItemType($itemType);
            }else if($itemType === null){
                $itemType = $this->registry->getManager()->getRepository(ItemType::class)->findOneBy(['id' => $data['itemType']]);
                if ($itemType instanceof ItemType) {
                    $propertyModel->setItemType($itemType);
                }
            }
        }

        if (isset($data['class'])) {
            $propertyModel->setClass($data['class']);
        }

      
       
        $this->registry->getManager()->persist($propertyModel);
        $this->registry->getManager()->flush();

        return $propertyModel;
    }

    public function getGroups(): array
    {
        return [];
    }
}
