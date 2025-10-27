<?php

namespace App\DataFixtures;

use App\Entity\ItemType;
use App\Entity\PropertyModel;
use App\Entity\Tag;
use App\Entity\Pipeline;
use App\Entity\PipelineStep;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class InitFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // 1. ItemTypes
        $companyType = new ItemType();
        $companyType->setCode('company')->setDescription('Entreprise');
        $manager->persist($companyType);

        $contactType = new ItemType();
        $contactType->setCode('contact')->setDescription('Contact');
        $manager->persist($contactType);

        // 2. PropertyModels pour chaque ItemType
        // Pour entreprise
        $companyName = new PropertyModel();
        $companyName->setLabel("Nom de l'entreprise")->setType('text')->setIdentifier(true)->setItemType($companyType)->setClass('companyName');
        $manager->persist($companyName);

        $companySize = new PropertyModel();
        $companySize->setLabel("Taille de l'entreprise")->setType('text')->setIdentifier(false)->setItemType($companyType);
        $manager->persist($companySize);

        $companySector = new PropertyModel();
        $companySector->setLabel("Secteur d’activité")->setType('text')->setIdentifier(false)->setItemType($companyType);
        $manager->persist($companySector);

        $siret = new PropertyModel();
        $siret->setLabel('SIRET')->setType('text')->setIdentifier(false)->setItemType($companyType)->setClass('siret');
        $manager->persist($siret);

        // Pour contact
        $lastName = new PropertyModel();
        $lastName->setLabel('Nom')->setType('text')->setIdentifier(true)->setItemType($contactType)->setClass('lastName');
        $manager->persist($lastName);

        $firstName = new PropertyModel();
        $firstName->setLabel('Prénoms')->setType('text')->setIdentifier(false)->setItemType($contactType)->setClass('firstName');
        $manager->persist($firstName);

        // 3. Tags avec couleurs spécifiques (statuts)
        $tags = [
            ['label' => 'Chaud', 'code' => 'chaud', 'description' => 'Contact très intéressé', 'color' => '#FF4136'],
            ['label' => 'Froid', 'code' => 'froid', 'description' => 'Contact peu réactif', 'color' => '#7FDBFF'],
            ['label' => 'Intéressé', 'code' => 'interesse', 'description' => 'Contact potentiellement intéressé', 'color' => '#2ECC40'],
        ];
        foreach ($tags as $t) {
            $tag = new Tag();
            $tag->setLabel($t['label'])->setCode($t['code'])->setDescription($t['description'])->setColor($t['color']);
            $manager->persist($tag);
        }

        // 4. Pipeline et PipelineStep avec couleurs
        $pipeline = new Pipeline();
        $pipeline->setName('Ventes')->setDescription('Pipeline principal');
        $manager->persist($pipeline);

        $steps = [
            ['name' => 'Prospection', 'description' => 'Premier contact', 'color' => '#0074D9'],
            ['name' => 'Qualification', 'description' => 'Vérification des besoins', 'color' => '#FF851B'],
            ['name' => 'Proposition', 'description' => 'Envoi d\'une offre', 'color' => '#2ECC40'],
            ['name' => 'Négociation', 'description' => 'Discussion des modalités', 'color' => '#B10DC9'],
        ];
        foreach ($steps as $i => $s) {
            $step = new PipelineStep();
            $step->setName($s['name'])
                 ->setDescription($s['description'])
                 ->setPipeline($pipeline)
                 ->setRanking($i+1)
                 ->setColor($s['color']);
            $manager->persist($step);
        }

        $manager->flush();
    }
}
