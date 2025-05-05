<?php


namespace App\MultiTenancy;

use Symfony\Component\HttpFoundation\RequestStack;

class Zone
{

    private $current = 'wiassur';

    public function __construct(private RequestStack $requestStack)
    {
        $this->checkCurrent();
    }


    public function checkCurrent()
    {
        // dd($this->requestStack->getCurrentRequest()->headers);
        if ($this->requestStack->getCurrentRequest()) {
            $zone = $this->requestStack->getCurrentRequest()->headers->get('zone');
            if (!empty($this->requestStack->getCurrentRequest()->headers->get('zone'))) {
                $zone = $this->requestStack->getCurrentRequest()->headers->get('zone');

                 if(str_contains($zone,',')){
                     $zone = explode(',',$zone)[0];
                 }
//                dump('zone set from post '.$zone);
                $this->current = $zone;
            }
            if (!empty($this->requestStack->getCurrentRequest()->query->get('zone'))) {
                $zone = $this->requestStack->getCurrentRequest()->query->get('zone');
                if(str_contains($zone,',')){
                    $zone = explode(',',$zone)[0];
                }
//                dump('zone set from post '.$zone);
                $this->current = $zone;
            }
        }
        $sapi_type = php_sapi_name();
        if ($sapi_type === 'cli') {
            $tenant = file_get_contents(Switcher::COMMAND_TENANT);
            // dump('zone set from file');
            // dd($tenant);
//             dd($zone);
            $this->current = $tenant;
        }
        // dump('zone checked '.$this->current);
    }

    public function getCurrent()
    {
        return $this->current;
    }

    public function setCurrent($zone)
    {
        $this->current = $zone;
    }
}
