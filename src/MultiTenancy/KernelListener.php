<?php


namespace App\MultiTenancy;

use Symfony\Component\HttpKernel\Event\RequestEvent;


class KernelListener
{
    /**
     * @var Switcher
     */
    private $switcher;

    /**
     * KernelListener constructor.
     * @param Switcher $switcher
     */
    public function __construct(Switcher $switcher)
    {
        $this->switcher = $switcher;
    }


    public function onKernelRequest($event = null){
        // Skip tenant switching for admin routes (super admin backoffice)
        if ($event && $event instanceof RequestEvent && $event->getRequest()) {
            $path = $event->getRequest()->getPathInfo();

            // Exclude admin backoffice routes from tenant switching
            if (str_starts_with($path, '/admin')) {
                return;
            }
        }

        $this->switcher->switchTenant();

    }

}
