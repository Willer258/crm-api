<?php

namespace App\Listeners;

use App\Entity\Mapping;
use App\Entity\Platform;
use App\Entity\Question;
use App\Entity\Section;
use App\Entity\Step;
use App\Entity\Survey;
use App\Entity\User;
use App\Managers\LocationManager;
use App\Managers\ServiceManager;
use App\Managers\VersionManager;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\SecurityBundle\Security as SecurityBundleSecurity;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserEvent
{
    /** @var User */
    private $user;
    private $token;
    /** @var \Symfony\Component\HttpFoundation\Request|null */
    private $request;
    /** @var Security */
    private $security;
    /** @var LocationManager */
    private $locationManager;

    private $updates = [
        VersionManager::FORMS => false
    ];

    public function __construct(
        TokenStorageInterface $tokenStorage,
        RequestStack $stack,
        SecurityBundleSecurity $security,
        LocationManager $locationManager,
        private VersionManager $versionManager,
        private ServiceManager $serviceManager,
        private LoggerInterface $logger
    ) {
        $this->token = $tokenStorage;
        $this->locationManager = $locationManager;
        $this->request = $stack->getCurrentRequest();
        $this->security = $security;
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        $this->getUser();
        $entity = $args->getObject();
        if ($this->user instanceof User && method_exists($entity, 'setCreateBy')) {
            if (!$entity->getCreateBy()) {
                $entity->setCreateBy($this->user);
            }
        }

        $this->checkUpdateFormVersion($entity);

        if (method_exists($entity, 'setCreatedAt')) {
            if (!$entity->getCreatedAt()) {
                $entity->setCreatedAt(new \DateTime());
            }
        }

        if ($this->user instanceof User && method_exists($entity, 'setUpdateBy')) {
            $entity->setUpdateBy($this->user);
        }
        if (method_exists($entity, 'setUpdatedAt')) {
            $entity->setUpdatedAt(new \DateTime());
        }

        if (method_exists($entity, 'setUuid')) {
            $uuid = Uuid::uuid4();
            if (!$entity->getUuid()) {
                $entity->setUuid($uuid);
            }
        }

        if (method_exists($entity, 'setCreatedFromIp') && $this->request) {
            $entity->setCreatedFromIp($this->locationManager->getIp());
        }


        $this->checkUserAccess($entity);
    }


    public function postPersist($args)
    {
        //        dd($args);
    }

    public function checkUpdateFormVersion($entity)
    {
        //        dd($entity);
    
    }

    //    public function preRemove(PreRemoveEventArgs $args){
    //
    //        $this->checkUpdateFormVersion($args->getObject());
    //    }

    public function postFlush(PostFlushEventArgs $args)
    {
        $apis = [];
        foreach ($this->updates as $api => $update) {
            if ($update) {
                $apis[] = $api;
            }
        }
        if (count($apis) > 0) {
            // $this->logger->debug('UPDATE FORM API');
            $this->serviceManager->post('core', 'update/apis/versions', ['apis' => $apis]);
        }
    }

    public function preUpdate(PreUpdateEventArgs $args)
    {
        $this->getUser();
        $entity = $args->getObject();

        //        dd($entity);
        $this->checkUpdateFormVersion($entity);

        if ($this->user instanceof User && method_exists($entity, 'setUpdateBy')) {
            $entity->setUpdateBy($this->user);
        }
        if (method_exists($entity, 'setUpdatedAt')) {
            $entity->setUpdatedAt(new \DateTime());
        }
        if (method_exists($entity, 'setUpdatedFromIp') && $this->request) {
            $entity->setUpdatedFromIp($this->locationManager->getIp());
        }
        $this->checkUserAccess($entity);
    }

    public function preFlush(PreFlushEventArgs $args)
    {

        //        /** @var UnitOfWork $uow */
        //        $uow = $args->getEntityManager()->getUnitOfWork();
        //        dd($uow->getScheduledEntityUpdates());



        $this->getUser();
        if (!$this->user instanceof User) {
            return;
        }
    
        $em = $args->getObjectManager();
        $uow = $em->getUnitOfWork();
    
        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if (
                method_exists($entity, 'getRemovedAt') &&
                method_exists($entity, 'setRemoveBy') &&
                $entity->getRemovedAt() instanceof \DateTimeInterface &&
                $entity->getRemoveBy() === null
            ) {
                $entity->setRemoveBy($this->user);
    
                $meta = $em->getClassMetadata(get_class($entity));
                $uow->recomputeSingleEntityChangeSet($meta, $entity);
            }
        }
    }

    public function preSoftDelete($args)
    {
        //        dump('$presoft');
        //        dd($args);
    }

    public function preRemove(LifecycleEventArgs $args)
    {
        $this->getUser();
        $entity = $args->getObject();
        $this->checkUpdateFormVersion($args->getObject());
        if ($this->user instanceof User && method_exists($entity, 'setRemoveBy')) {
            $entity->setRemoveBy($this->user);
        }

        $this->checkUserAccess($entity);
    }

    public function getUser()
    {
        if ($this->token->getToken()) {
            $this->user = $this->token->getToken()->getUser();
        }
        return $this->user;
    }

    public function checkUserAccess($entity)
    {
        //        if (method_exists($entity, 'getAgency')) {
        // if ($this->getUser()->getAgency() !== $entity->getAgency()) {
        //     throw new ExceptionApi('Vous n\'êtes pas autorisé à accéder à cette ressource');
        // }
        //        }
    }
}
