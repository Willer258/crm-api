<?php

namespace App\Managers;

use Psr\Log\LoggerInterface;

class VersionManager
{

    private $default = [
        "forms" => "1.0.0",
        "engines" => "1.0.0",
        "branches" => "1.0.0",
        "insurers" => "1.0.0",
        "dataTypes" => '1.0.0',
        "paymentMethods" => '1.0.0',
    ];

    const FORMS = 'forms';
    const ENGINES = 'engines';
    const BRANCHES = 'branches';
    const INSURERS = 'insurers';
    const DATA_TYPES = 'dataTypes';
    const PAYMENT_METHODS = 'paymentMethods';

    private $version;

    private $path = __DIR__ . '/../../version.json';

    public function __construct(private LoggerInterface $logger)
    {
        $this->path = __DIR__ . '/../../version.json';
        if (file_exists($this->path)) {
            $this->version = json_decode(file_get_contents($this->path), true);
        } else {
            $this->init();
        }
    }

    public function init()
    {
        $this->version = $this->default;
        foreach ($this->version as $key => $item) {
            $this->version[$key] = $this->format();
        }
        $this->save();
    }

    public function update($api)
    {
        $this->logger->debug('Update version ' . $api);
        $this->version[$api] = $this->format();
        $this->save();
    }

    public function format()
    {
        $datetime = new \Datetime();
        return $datetime->format('d-m-Y H:i:s');
    }

    public function getVersions()
    {

    }

    public function save()
    {
        file_put_contents($this->path, json_encode($this->version));
    }
}
