<?php

namespace App\lib;

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

        $payloadData = json_decode($payload, true);
        $uuid = $payloadData['uuid'];
        $data =  unserialize($payloadData['data']['command']??'');
        if(is_object($data) ){
            $job_name = $data->name?? '';
            $key1 = $data->key1 ?? '';
            $key2 = $data->key2 ?? '';
        }else{
            $job_name = $key1 = $key2 = '';
        }




        $this->getTable()->insert([
            'uuid'       => $uuid,
            'connection' => $connection,
            'queue'      => $queue,
            'job_name'   => $job_name,
            'key1'       => $key1,
            'key2'       => $key2,
            'payload'    => $payload,
            'exception'  => (string)mb_convert_encoding($exception, 'UTF-8'),
            'failed_at'  => Date::now(),
        ]);

        return $uuid;
    }

}
