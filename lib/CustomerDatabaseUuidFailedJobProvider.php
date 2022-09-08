<?php

namespace App\lib;

use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Queue\Failed\DatabaseUuidFailedJobProvider;
use Illuminate\Support\Facades\Date;

class CustomerDatabaseUuidFailedJobProvider extends DatabaseUuidFailedJobProvider
{



    /**
     * Log a failed job into storage.
     *
     * @param string $connection
     * @param string $queue
     * @param string $payload
     * @param \Throwable $exception
     * @return string|null
     */
    public function log($connection, $queue, $payload, $exception)
    {

        $payloadData = json_decode($payload);

        $this->getTable()->insert([
            'uuid'       => $payloadData->uuid,
            'connection' => $connection,
            'queue'      => $queue,
            'job_name'   => $payloadData->data->queueName,
            'key1'       => $payloadData->data->key1,
            'key2'       => $payloadData->data->key2,
            'payload'    => $payload,
            'exception'  => (string)mb_convert_encoding($exception, 'UTF-8'),
            'failed_at'  => Date::now(),
        ]);

        return $payloadData->uuid;
    }

}
