<?php


namespace App\Managers;


use App\Entity\Location;
use App\Interfaces\ManagerInterface;
use App\Utils\Sanitizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security as SecurityBundleSecurity;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class LocationManager extends Manager implements ManagerInterface
{

    /** @var DeviceManager */
    public $deviceManager;

    public $ip = null;
    /** @var HttpClientInterface */
    private $client;

    public function __construct(
        EntityManagerInterface $em,
        Sanitizer $sanitizer,
        SerializerInterface $serializer,
        SecurityBundleSecurity $security,
        DeviceManager $deviceManager,
        HttpClientInterface $client
    ) {
        parent::__construct($em, $sanitizer, $serializer, $security);
        $this->deviceManager = $deviceManager;
        $this->client = $client;
    }

    public function getCurrent()
    {
        $device = $this->deviceManager->getCurrent();
        $ip = $this->getIp();
        //        dd($ip);
        $exist = $this->em->getRepository(Location::class)->findOneBy(['ip' => $ip, 'device' => $device]);
        if ($exist instanceof Location) {
            return $exist;
        }
        $location = null;

        //        dd($ip);
        try {
            $location = $this->getFromFreeGeoIp();
            //            dump($location);
            $location->setDevice($device);
            $this->em->persist($location);
        } catch (\Throwable $exception) {
            //            dd($exception);
        }
        if (!$location instanceof Location) {
            try {
                $location = $this->getFromAbstractAPI();
                $location->setDevice($device);
                $this->em->persist($location);
            } catch (\Throwable $exception) {
                //                dd($exception);
            }
        }
        if ($location instanceof Location) {
            $location->setIp($this->getIp());
        }

        return $location;
    }


    public function getIp()
    {
        if ($this->ip) {
            return $this->ip;
        }
        $ipaddress = '';
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        } else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else if (isset($_SERVER['HTTP_X_FORWARDED'])) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        } else if (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        } else if (isset($_SERVER['HTTP_FORWARDED'])) {
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        } else if (isset($_SERVER['REMOTE_ADDR'])) {
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        } else {
            $ipaddress = 'UNKNOWN';
        }
        if ($ipaddress === '127.0.0.1') {
            try {
                $ipaddress = file_get_contents('https://api.ipify.org');
            } catch (\Throwable $e) {
            }
        }
        $this->ip = $ipaddress;
        return $ipaddress;
    }

    static public function getIpAddress()
    {

        $ipaddress = '';
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        } else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else if (isset($_SERVER['HTTP_X_FORWARDED'])) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        } else if (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        } else if (isset($_SERVER['HTTP_FORWARDED'])) {
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        } else if (isset($_SERVER['REMOTE_ADDR'])) {
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        } else {
            $ipaddress = 'UNKNOWN';
        }
        if ($ipaddress === '127.0.0.1') {
            try {
                $ipaddress = file_get_contents('https://api.ipify.org');
            } catch (\Throwable $e) {
            }
        }
        return $ipaddress;
    }


    public function getFromAbstractAPI(): Location
    {
        $ch = curl_init();
        $apiKey = 'e1f6d3c630ca49ffac26f4d2ef81bf68';
        $ip = $this->getIp();
        curl_setopt($ch, CURLOPT_URL, 'https://ipgeolocation.abstractapi.com/v1/?api_key=' . $apiKey . '&ip_address=' . $ip);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $data = json_decode(curl_exec($ch), true);

        curl_close($ch);
        //        dd(json_decode($data, true));

        $location = new Location();
        $s = $this->sanitizer;
        $location->setCity($s->string($data['city']));
        $location->setCountry($s->string($data['country']));
        $location->setCountryCode($s->string($data['country_code']));
        $location->setRegion($s->string($data['region']));
        $location->setVpn($s->string($data['security']['is_vpn']));
        $location->setCompany($location['connection']['organization_name']);
        //        dd($location);
        return $location;
    }

    public function getFromFreeGeoIp(): Location
    {
        $ip = $this->getIp();
        $apiKey = '8e4bf0a0-9e2a-11ec-a411-f32470c1a1ba';

        $url = "https://api.freegeoip.app/json/$ip?apikey=$apiKey";
        $response = $this->client->request('GET', $url);
        $data = json_decode($response->getContent(), true);
        //        dd($data);
        $location = new Location();
        $s = $this->sanitizer;
        //        dd($data);

        $location->setCity($s->string($data['city']));
        $location->setCountry($s->string($data['country_name']));
        $location->setCountryCode($s->string($data['country_code']));
        $location->setRegion($s->string($data['region_name']));
        return $location;
    }

    public
    function getGroups(): array
    {
       return [];
    }
}
