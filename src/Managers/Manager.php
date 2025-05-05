<?php

namespace App\Managers;

use App\Entity\User;
use App\Utils\Sanitizer;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
// use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\SerializerInterface;

class Manager
{

    /** @var EntityManagerInterface */
    protected $em;
    /** @var Sanitizer */
    protected $sanitizer;
    /** @var SerializerInterface */
    protected $serializer;
    // /** @var Security */
    protected $security;
    /** @var */
//    protected $currency;
//    /** @var UrlGeneratorInterface */
//    protected $router;
//    /** @var MailerInterface  */
//    protected $mailer;
//    /** @var LoggerInterface  */
//    protected $logger;
//    /** @var ParameterBagInterface  */
//    protected $parameters;
//    /** @var HttpClientInterface  */
//    protected $client;
//    /** @var UserPasswordHasherInterface  */
//    protected $hasher;

    public function __construct(EntityManagerInterface $em, Sanitizer $sanitizer, SerializerInterface $serializer
    )
    {
        $this->em = $em;
        $this->sanitizer = $sanitizer;
        $this->serializer = $serializer;
        // $this->security = $security;
//        $this->router = $router;
//        $this->mailer = $mailer;
//        $this->logger = $logger;
//        $this->parameters = $bag;
//        $this->client = $client;
//        $this->hasher = $hasher;
    }


}
