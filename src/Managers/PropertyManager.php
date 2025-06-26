<?php

namespace App\Managers;

use App\Entity\Company;
use App\Entity\Contact;
use App\Entity\ItemType;
use App\Entity\Property;
use App\Entity\PropertyModel;
use Doctrine\Persistence\ManagerRegistry;

class PropertyManager extends Manager
{


     public function __construct(private ManagerRegistry $registry) {}

    public function edit($data, $itemType = null , $flush = false ) : ?Property
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
            if ($propertyModel instanceof PropertyModel) {
              if ( $itemType instanceof ItemType && $itemType->getCode() !== $propertyModel->getItemType()->getCode()) {
                throw new \Exception('Le type de propriété ne correspond pas au type d\'item');
              }
                $property->setPropertyModel($propertyModel);
            }
        }else{
            throw new \Exception('Le model de propriété n\'est pas defini');
        }
        

        $value = $data['value'] ?? null;
        if ($value === '' || $value === null) {
            if ($property->getId()) {
                $this->delete($property->getId(), false);
            }
            return null;
        }
        $property->setValue($value);


        if (isset($data['companyId'])) {
            $company = $this->registry->getManager()->getRepository(Company::class)->findOneBy(['id' => $data['companyId']]);
            if ($company instanceof Company) {
                $property->setCompany($company);
            }
        }
        if (isset($data['contactId'])) {
            $contact = $this->registry->getManager()->getRepository(Contact::class)->findOneBy(['id' => $data['contactId']]);
            if ($contact instanceof Contact) {
                $property->setContact($contact);
            }
        }
       
        
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
