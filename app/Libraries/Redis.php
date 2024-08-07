<?php

namespace App\Libraries;

use Predis\Client;

class Redis
{
    public function config()
    {
        $client = new Client([
            'scheme'   => 'tcp',
            'host'     => 'localhost',
            'port'     => 6379,
            'database' => 1
        ]);

        return $client;
    }
}
