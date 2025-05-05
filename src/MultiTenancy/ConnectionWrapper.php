<?php


namespace App\MultiTenancy;
use Doctrine\DBAL\Connection;



class ConnectionWrapper extends Connection
{

    public function changeDatabase(string $host,string $port,string $user,string $password,string $dbName)
    {
        $params = $this->getParams();
        // dd($params);
        if ($this->isConnected())
            $this->close();
        $params['url'] = "mysql://".$user.":".$password."@".$host.":".$port."/".$dbName;
        $params['host'] = $host;
        $params['port'] = $port;
        $params['dbname'] = $dbName;
        $params['user'] = $user;
        $params['password'] = $password;
        parent::__construct(
            $params,
            $this->_driver,
            $this->_config,
            $this->_eventManager
        );
    }

    public function changeUrl(string $url)
    {
        $params = $this->getParams();
        if ($this->isConnected())
            $this->close();
        $params['url'] = $url;
        parent::__construct(
            $params,
            $this->_driver,
            $this->_config,
            $this->_eventManager
        );
    }
}
