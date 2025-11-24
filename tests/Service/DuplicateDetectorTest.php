<?php

namespace App\Tests\Service;

use App\Entity\Contact;
use App\Entity\Company;
use App\Entity\Mail;
use App\Entity\PhoneNumber;
use App\Entity\Property;
use App\Entity\PropertyModel;
use App\Service\DuplicateDetector;
use App\Repository\ContactRepository;
use App\Repository\CompanyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DuplicateDetectorTest extends KernelTestCase
{
    private ?DuplicateDetector $duplicateDetector;
    private ?EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $this->duplicateDetector = $container->get(DuplicateDetector::class);
        $this->em = $container->get(EntityManagerInterface::class);
    }

    public function testDetectExactDuplicateByEmail(): void
    {
        // Créer un contact avec un email
        $contact = new Contact();
        $contact->setSource('test');
        $this->em->persist($contact);

        $mail = new Mail();
        $mail->setEmail('test@example.com');
        $mail->setType('professionnel');
        $mail->setContact($contact);
        $this->em->persist($mail);

        $this->em->flush();

        // Tester la détection avec les mêmes données
        $result = $this->duplicateDetector->detectContactDuplicates([
            'email' => 'test@example.com',
            'nom' => 'Dupont'
        ]);

        $this->assertTrue($result['is_duplicate'], 'Should detect duplicate by email');
        $this->assertCount(1, $result['exact_duplicates'], 'Should find exactly one duplicate');
        $this->assertEquals('high', $result['confidence'], 'Confidence should be high');

        // Nettoyage
        $this->em->remove($mail);
        $this->em->remove($contact);
        $this->em->flush();
    }

    public function testDetectExactDuplicateByPhone(): void
    {
        // Créer un contact avec un téléphone
        $contact = new Contact();
        $contact->setSource('test');
        $this->em->persist($contact);

        $phone = new PhoneNumber();
        $phone->setNumber('+33612345678');
        $phone->setType('mobile');
        $phone->setContact($contact);
        $this->em->persist($phone);

        $this->em->flush();

        // Tester la détection avec le même numéro
        $result = $this->duplicateDetector->detectContactDuplicates([
            'telephone' => '06 12 34 56 78',
            'nom' => 'Martin'
        ]);

        $this->assertTrue($result['is_duplicate'], 'Should detect duplicate by phone');
        $this->assertGreaterThan(0, count($result['exact_duplicates']), 'Should find duplicates');

        // Nettoyage
        $this->em->remove($phone);
        $this->em->remove($contact);
        $this->em->flush();
    }

    public function testDetectSimilarContacts(): void
    {
        // Créer un contact
        $contact = new Contact();
        $contact->setSource('test');
        $this->em->persist($contact);

        // Ajouter une propriété nom
        $propertyModel = new PropertyModel();
        $propertyModel->setLabel('nom');
        $propertyModel->setType(PropertyModel::TYPE_TEXT);
        $propertyModel->setIdentifier(true);
        $this->em->persist($propertyModel);

        $property = new Property();
        $property->setValue('Jean Dupont');
        $property->setPropertyModel($propertyModel);
        $property->setContact($contact);
        $this->em->persist($property);

        $this->em->flush();

        // Tester avec un nom similaire
        $result = $this->duplicateDetector->detectContactDuplicates([
            'nom' => 'Jean Dupond', // Légère différence
            'prenom' => 'Jean'
        ]);

        // Devrait détecter une similarité
        $this->assertTrue($result['has_similar'] || $result['is_duplicate'], 'Should detect similar contact');

        // Nettoyage
        $this->em->remove($property);
        $this->em->remove($propertyModel);
        $this->em->remove($contact);
        $this->em->flush();
    }

    public function testDetectCompanyDuplicateByName(): void
    {
        // Créer une entreprise
        $company = new Company();
        $company->setSource('test');
        $this->em->persist($company);

        $propertyModel = new PropertyModel();
        $propertyModel->setLabel('nom');
        $propertyModel->setType(PropertyModel::TYPE_TEXT);
        $this->em->persist($propertyModel);

        $property = new Property();
        $property->setValue('Acme Corporation');
        $property->setPropertyModel($propertyModel);
        $property->setCompany($company);
        $this->em->persist($property);

        $this->em->flush();

        // Tester avec le même nom
        $result = $this->duplicateDetector->detectCompanyDuplicates([
            'company' => 'Acme Corporation'
        ]);

        $this->assertTrue($result['is_duplicate'] || $result['has_similar'], 'Should detect company duplicate');

        // Nettoyage
        $this->em->remove($property);
        $this->em->remove($propertyModel);
        $this->em->remove($company);
        $this->em->flush();
    }

    public function testDetectCompanyVariations(): void
    {
        // Créer une entreprise avec une forme juridique
        $company = new Company();
        $company->setSource('test');
        $this->em->persist($company);

        $propertyModel = new PropertyModel();
        $propertyModel->setLabel('nom');
        $propertyModel->setType(PropertyModel::TYPE_TEXT);
        $this->em->persist($propertyModel);

        $property = new Property();
        $property->setValue('Acme SAS');
        $property->setPropertyModel($propertyModel);
        $property->setCompany($company);
        $this->em->persist($property);

        $this->em->flush();

        // Tester avec une variation de la forme juridique
        $result = $this->duplicateDetector->detectCompanyDuplicates([
            'company' => 'Acme SA'
        ]);

        // Devrait détecter une forte similarité
        $this->assertTrue($result['has_similar'] || $result['is_duplicate'], 'Should detect company variation');

        if ($result['has_similar'] && !empty($result['similar_companies'])) {
            $this->assertGreaterThan(0.8, $result['similar_companies'][0]['score'], 'Similarity score should be high');
        }

        // Nettoyage
        $this->em->remove($property);
        $this->em->remove($propertyModel);
        $this->em->remove($company);
        $this->em->flush();
    }

    public function testNoFalsePositives(): void
    {
        // Tester avec des données complètement différentes
        $result = $this->duplicateDetector->detectContactDuplicates([
            'nom' => 'ContactUnique' . uniqid(),
            'email' => 'unique' . uniqid() . '@example.com'
        ]);

        $this->assertFalse($result['is_duplicate'], 'Should not detect false duplicates');
        $this->assertEmpty($result['exact_duplicates'], 'Should not find any exact duplicates');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->duplicateDetector = null;
        $this->em = null;
    }
}
