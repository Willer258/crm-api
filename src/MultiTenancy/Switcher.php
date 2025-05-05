<?php


namespace App\MultiTenancy;

use App\Utils\CryptoJsAes;
use App\Utils\Sanitizer;
use Predis\ClientInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;


class Switcher
{
    /** @var ConnectionWrapper */
    private $connection;
    /** @var RequestStack */
    private $requestStack;
    /** @var ManagerRegistry */
    private $registry;
    /** @var ClientInterface */
    private $redis;

    const COMMAND_TENANT = __DIR__ . '/../../tenant.txt';

    const DEFAULT_TENANT = "DEMO";
    const TENANT_COOKIE_NAME = "zone";
    const CURRENT_TENANT_REDIS_KEY = "current_tenant";
    const ACCEPTED = ['ci', 'sn'];
    // const ACCEPTED_WITH_NAMES = [
    //     'ci' => ['iso' => 'ci', 'name' => 'Côte d\'Ivoire'],
    //     'sn' => ['iso' => 'sn', 'name' => 'Sénégal']
    //     ];

    /** @var ParameterBagInterface */
    private $bag;

    /**
     * TenantSwitcher constructor.
     * @param ConnectionWrapper $connection
     * @param RequestStack $requestStack
     * @param ManagerRegistry $registry
     */
    public function __construct(Connection $connection, RequestStack $requestStack, ManagerRegistry $registry, ParameterBagInterface $bag, private Sanitizer $sanitizer, private LoggerInterface $logger, private Zone $zone)
    {
        // dd($connection);
        $this->connection = $connection;
        $this->requestStack = $requestStack;
        $this->registry = $registry;
        // $this->redis = $redis;
        $this->bag = $bag;

    }

    public static function getCommandTenant()
    {
        if (!file_exists(self::COMMAND_TENANT)) {
            file_put_contents(self::COMMAND_TENANT, '');
        }
        return file_get_contents(self::COMMAND_TENANT);
    }

    public function switchTenant($tenant = null)
    {
        if (null !== $tenant) {
            $this->setTenant($tenant);
        }
        $t = $tenant ?? $this->getTenant();
        // dd($t);
        $this->setTenantDatabase($t);
    }

    /**
     * @param $tenant
     */
    public function setTenantDatabase(string $tenant)
    {
        // $this->registry->getManager()->;
        $connection = $this->connection;
        // dump($connection->getParams());
        $params = $this->connection->getParams();
        $this->zone->checkCurrent();
        $zone = $this->zone->getCurrent();
        // dump($params['dbname']);

//        dd($zone);
        $params['dbname'] = $zone . '_' . $params['dbname'];

//        dd($zone);
//        if(!$zone){
//            echo 'Zone not set';
//            exit;
//            throw new \Exception('Error',500);
//        }
//        dd($params);
        // dump('zone '.$zone);
        // dump('params');
        // dump($params);
        // $this->connection->changeDatabase($params['host'], $params['port'] ?? "3306", $params['user'], $params['password'], $params['dbname']);
        // dd($this->registry->getConnection());
        $this->registry->getConnection()->changeDatabase($params['host'], $params['port'] ?? "3306", $params['user'], $params['password'], $params['dbname']);
        $this->registry->getConnection()->getConfiguration()->setSQLLogger(null);
        // $manager->getConnection()->getConfiguration()->setSQLLogger(null);

        // $this->redis->set(self::CURRENT_TENANT_REDIS_KEY, $tenant);
    }


    public function getTenant(): string
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            if ($request->cookies->has(self::TENANT_COOKIE_NAME)) {
//            $fromCookie = utf8_encode($request->cookies->get(self::TENANT_COOKIE_NAME));
                $fromCookie = mb_convert_encoding($request->cookies->get(self::TENANT_COOKIE_NAME), 'UTF-8');
                $tenant = json_decode($fromCookie, true) ?? $fromCookie;
                // dd($tenant);
                if (is_array($tenant) && array_key_exists('iso', $tenant)) {
                    $tenant = $tenant['iso'];
                }
                if (null === $tenant) {
                    $tenant = self::DEFAULT_TENANT;
                    $this->setTenant($tenant);
                }
            } else {
                $tenant = self::DEFAULT_TENANT;
                $this->setTenant($tenant);
            }
            return $tenant;
        }else{
            return $this->zone->getCurrent();
        }
    }

    public function setTenant(string $tenant)
    {

        // $this->checkTenant($tenant);
        $host = $this->requestStack->getCurrentRequest()->headers->get('host');
        $topDomain = $host;
        $parts = explode('.', $host);

        if (count($parts) > 2) {
            $topDomain = $parts[count($parts) - 2] . '.' . $parts[count($parts) - 1];
        }
        // dd($tenant);

        // $secure = $this->bag->get('env') === 'dev' ? 0 : 1;

        // setcookie(self::TENANT_COOKIE_NAME, json_encode(self::ACCEPTED_WITH_NAMES[$tenant] ), time() + 24 * 60 * 60 * 1000, "/", $topDomain, $secure);
    }

    public function getCommandLineTenant()
    {
        if (file_exists(self::COMMAND_TENANT)) {
            $tenant = file_get_contents(self::COMMAND_TENANT);
            return $tenant;
        }
        return null;
    }

    public function switchCommandLineTenant(string $tenant = null)
    {
        if (null !== $tenant) {
            $this->setCommandLineTenant($tenant);
        }

        $this->setTenantDatabase($tenant ?? $this->getCommandLineTenant());
    }

    public function setCommandLineTenant($tenant)
    {
        // $this->checkTenant($tenant);
        file_put_contents(self::COMMAND_TENANT, $tenant);
        // $this->redis->set(self::TENANT_COOKIE_NAME, $tenant);
    }


    private function isTenantAccepted($tenant): bool
    {
        return in_array($tenant, self::ACCEPTED);
    }
}
