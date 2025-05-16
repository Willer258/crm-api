<?php

namespace App\Managers;

use App\Entity\Property;
use App\Entity\PropertyModel;
use Doctrine\Persistence\ManagerRegistry;

class PropertyManager extends Manager
{


     public function __construct(private ManagerRegistry $registry) {}

    public function edit($data) : ?Property
    {
        $property = null;
        if (isset($data['id'])) {
            $property = $this->registry->getManager()->getRepository(Property::class)->findOneBy(['id' => $data['id']]);
        }
        if (!($property instanceof Property)) {
            $property = new Property();
        }
        if (isset($data['modelId'])) {
            $propertyModel = $this->registry->getManager()->getRepository(PropertyModel::class)->findOneBy(['id' => $data['modelId']]);
            $property->setPropertyModel($propertyModel);
        }
        $value = $data['value'] ?? null;
        if ($value === '' || $value === null) {
            if ($property->getId()) {
                $this->delete($property->getId(), false);
            }
            return null;
        }
    
        // Affectation de la valeur
        $property->setValue($value);       
        $this->registry->getManager()->persist($property);

        return $property;
    }



    public function delete($idProperty , $flush = true)
    {
        $property = null;

            $property = $this->registry->getManager()->getRepository(Property::class)->findOneBy(['id' => $idProperty]);
        


        if ($property instanceof Property) {

            $this->registry->getManager()->remove($property);
            
            if ($flush) {
                $this->registry->getManager()->flush();
            }

            return true;
        }

      

      

    }

    public function getGroups(): array
    {
        return ['property_model_list'];
    }
}
