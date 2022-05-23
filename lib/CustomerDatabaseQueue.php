<?php

namespace App\lib;


use Illuminate\Database\Connection;
use Illuminate\Queue\DatabaseQueue;
use Illuminate\Queue\Jobs\DatabaseJob;
use Illuminate\Queue\Jobs\DatabaseJobRecord;
use Illuminate\Queue\Queue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use PDO;

class CustomerDatabaseQueue extends DatabaseQueue
{
    /**
     * Create an array to insert for the given job.
     *
     * @param  string|null  $queue
     * @param  string  $payload
     * @param  int  $availableAt
     * @param  int  $attempts
     * @return array
     */
    protected function buildDatabaseRecord($queue, $payload, $availableAt, $attempts = 0)
    {
        $obj = json_decode($payload,true);
        $command = isset($obj['data']['command']) ? unserialize($obj['data']['command']) :[];
        $job_name = $command->name??'';
        $key1 = $command->key1??'';
        $key2 = $command->key2??'';


        return [
            'queue' => $queue,
            'attempts' => $attempts,
            'reserved_at' => null,
            'available_at' => $availableAt,
            'created_at' => $this->currentTime(),
            'payload' => $payload,
            'job_name'=>$job_name,
            'key1'=>$key1,
            'key2'=>$key2,
        ];
    }

    /**
     *
     * @param $queue
     * @param $job
     * @return CustomerDatabaseJob
     */
    protected function marshalJob($queue, $job)
    {
        $job = $this->markJobAsReserved($job);

        return new CustomerDatabaseJob(
            $this->container, $this, $job, $this->connectionName, $queue
        );
    }
}
