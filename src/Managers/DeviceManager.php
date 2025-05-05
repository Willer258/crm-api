<?php


namespace App\Managers;


use App\Entity\Device;
use App\Interfaces\ManagerInterface;
use DeviceDetector\DeviceDetector;
use DeviceDetector\Parser\Device\AbstractDeviceParser;

class DeviceManager extends Manager implements ManagerInterface
{
    private $device = null;

    public function getCurrent(): Device
    {
        if ($this->device instanceof Device) {
            return $this->device;
        }
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        $dd = new DeviceDetector($userAgent);
        $dd->parse();
        $browser = trim($dd->getClient('name') . ' ' . $dd->getClient('version'));
        $mac = exec('getmac');
        $mac = strtok($mac, ' ');
        if (!json_encode($mac)) {
            $mac = null;
        }

        $device = $this->em->getRepository(Device::class)->findOneBy(['mac' => $mac]);
        if (!$device instanceof Device) {
            $device = new Device();
            $typeCode = $dd->getDevice();
            $types = AbstractDeviceParser::getAvailableDeviceTypes();
            $type = $dd->getClient('type');
            foreach ($types as $key => $item) {
                if ($item === $typeCode) {
                    $type = $key;
                }
            }
            $os = trim($dd->getOs('name') . ' ' . $dd->getOs('version') . ' ' . $dd->getOs('platform'));
            $device->setType($type);
            $device->setMac($mac)->setOs($os)->setBrand($dd->getBrandName())->setModel($dd->getModel())->setBrowser($browser);$this->em->persist($device);
//            $this->em->getUnitOfWork()->commit($device);
        }
//        dd($device);
        $this->device = $device;
        return $device;
    }


    public function getGroups(): array
    {
        return [];
    }
}
