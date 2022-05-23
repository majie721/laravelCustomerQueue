<?php

namespace App\lib;

use Illuminate\Container\Container;
use Illuminate\Queue\DatabaseQueue;
use Illuminate\Queue\Jobs\DatabaseJob;

class CustomerDatabaseJob extends DatabaseJob
{
    public function __construct(Container $container, CustomerDatabaseQueue $database, $job, $connectionName, $queue)
    {
        $this->job = $job;
        $this->queue = $queue;
        $this->database = $database;
        $this->container = $container;
        $this->connectionName = $connectionName;
    }
}
