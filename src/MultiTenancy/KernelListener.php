<?php


namespace App\MultiTenancy;


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


    public function onKernelRequest(){
        $this->switcher->switchTenant();

    }

}
