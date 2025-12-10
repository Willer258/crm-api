<?php


namespace App\Managers;

use App\Entity\Level;
use App\Entity\Subject;
use App\Entity\SubjectCategory;
use Doctrine\Common\Collections\ArrayCollection;
use Ramsey\Uuid\Uuid;
use App\Utils\Sanitizer;
use Psr\Log\LoggerInterface;
use App\Interfaces\ManagerInterface;
use App\Repository\SchoolRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ServiceManager implements ManagerInterface
{


    public function __construct(
        private RequestStack          $requestStack,
        private ParameterBagInterface $bag,
        private HttpClientInterface   $http,
        private LoggerInterface       $logger,
        private  Security $security
    )
    {
    }


    public function get($service, $path, $debug = false)
    {
        $key = $this->bag->get('SERVICE_API_KEY');
        $now = new \DateTime();
        $timestamp = $now->getTimestamp();
        // $service = strtoupper($service . '_URL');
        try {
            $service = $this->bag->get(strtoupper($service . '_URL'));
        } catch (\Throwable $e) {
            $service = $this->bag->get(strtoupper($service . '_API_URL'));
        }
        $url = $service . 'service/' . $timestamp . '/' . $path;
        $prefix = $this->bag->get('SERVICE_PREFIX');
        $suffix = $this->bag->get('SERVICE_SUFFIX');
        $token = sha1($prefix . $key . $timestamp . $suffix);

        $roles = [];
        if ($this->security->getUser()) {
            $roles = $this->security->getUser()->getRoles();
        }
        if ($debug) {
            $this->logger->critical('url ' . $url . ', token ' . $token);
        }
        $response = $this->http->request('GET', $url, ['headers' => ['X-AUTH-TOKEN' => $token, 'roles' => $roles]]);
//        echo $response->getContent(false);
//        exit;
        // $error =  $response->getInfo();
//         dd($response->getContent(false));
        $response = $response->getContent(false);
        $content = json_decode($response, true);
//        dd($content);
        return $content;
    }

    public function post($service, $path, $body, $debug = false)
    {
        $key = $this->bag->get('SERVICE_API_KEY');
        $now = new \DateTime();
        $timestamp = $now->getTimestamp();
        try {
            $service = $this->bag->get(strtoupper($service . '_URL'));
        } catch (\Throwable $e) {
            $service = $this->bag->get(strtoupper($service . '_API_URL'));
        }
        $url = $service . 'service/' . $timestamp . '/' . $path;
        $prefix = $this->bag->get('SERVICE_PREFIX');
        $suffix = $this->bag->get('SERVICE_SUFFIX');
        $token = sha1($prefix . $key . $timestamp . $suffix);

        if ($debug) {
            $this->logger->critical('url ' . $url . ', token ' . $token . ', body ' . json_encode($body));
        }
        $roles = [];
        if ($this->security->getUser()) {
            $roles = $this->security->getUser()->getRoles();
        }
        $response = $this->http->request('POST', $url, ['headers' => ['X-AUTH-TOKEN' => $token, 'roles' => $roles], 'body' => json_encode($body)]);
//        echo $response->getContent(false);
//exit;
        $content = json_decode($response->getContent(false), true);
        return $content;
        // } catch (\Throwable $e) {
        //     dd($e);
        // }
    }

    public function postFile($service, $path, $filePath)
    {

//        $key = $this->bag->get('SERVICE_API_KEY');
//        $now = new \DateTime();
//        $timestamp = $now->getTimestamp();
//        try {
//            $service = $this->bag->get(strtoupper($service . '_URL'));
//        } catch (\Throwable $e) {
//            $service = $this->bag->get(strtoupper($service . '_API_URL'));
//        }
//        $url = $service . 'service/' . $timestamp . '/' . $path;
//        $prefix = '*~LOr3m';
//        $sufix = 'iPsnM#$';
//        $token = sha1($prefix . $key . $timestamp . $sufix);
//        $zone = $this->zone->getCurrent();
////        $cFile = curl_file_create($filePath);
////        $post = array('signature' => $cFile);
//        // try {
////        $this->logger->critical('url ' . $url . ', token ' . $token . ', zone ' . $zone . ', body ' . json_encode($body));
//        $data = [
//            'file' => DataPart::fromPath($filePath),
//        ];
//
//        $formData = new FormDataPart($data);
//        $response = $this->http->request('POST', $url, ['headers' => ['X-AUTH-TOKEN' => $token, 'Zone' => $zone,'Content-Type'=> 'multipart/form-data'],
//            'body' => ['signature' => fopen($filePath, 'r')]
//        ]);
//        echo $response->getContent(false);
//        exit;
//        $content = json_decode($response->getContent(false), true);
//        return $content;


        $key = $this->bag->get('SERVICE_API_KEY');
        $now = new \DateTime();
        $timestamp = $now->getTimestamp();
        try {
            $service = $this->bag->get(strtoupper($service . '_URL'));
        } catch (\Throwable $e) {
            $service = $this->bag->get(strtoupper($service . '_API_URL'));
        }
        $url = $service . 'service/' . $timestamp . '/' . $path;
        $prefix = $this->bag->get('SERVICE_PREFIX');
        $suffix = $this->bag->get('SERVICE_SUFFIX');
        $token = sha1($prefix . $key . $timestamp . $suffix);

        $cFile = curl_file_create($filePath);
        $post = array('signature' => $cFile);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'X-AUTH-TOKEN: ' . $token,
        ]);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    public function getGroups(): array
    {
        return [];
    }
}
