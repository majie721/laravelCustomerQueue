<?php

namespace App\lib;


use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Database\Connection;
use Illuminate\Queue\DatabaseQueue;
use Illuminate\Queue\Jobs\DatabaseJob;
use Illuminate\Queue\Jobs\DatabaseJobRecord;
use Illuminate\Queue\Queue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PDO;

class CustomerDatabaseQueue extends DatabaseQueue
{

    public function __construct(Connection $database, $table, $default = 'default', $retryAfter = 60, $dispatchAfterCommit = false,public string $log_table = '')
    {
        parent::__construct($database, $table, $default, $retryAfter, $dispatchAfterCommit);
    }


    /**
     * Create a payload for an object-based queue handler.
     *
     * @param  object  $job
     * @param  string  $queue
     * @return array
     */
    protected function createObjectPayload($job, $queue)
    {
        [$key1,$key2] = $this->getJobKey($job);
        $displayName =  $this->getDisplayName($job);
        $payload = $this->withCreatePayloadHooks($queue, [
            'uuid' => (string) Str::uuid(),
            'displayName' => $displayName,
            'job' => 'Illuminate\Queue\CallQueuedHandler@call',
            'maxTries' => $job->tries ?? null,
            'maxExceptions' => $job->maxExceptions ?? null,
            'failOnTimeout' => $job->failOnTimeout ?? false,
            'backoff' => $this->getJobBackoff($job),
            'timeout' => $job->timeout ?? null,
            'retryUntil' => $this->getJobExpiration($job),
            'data' => [
                'commandName' => $job,
                'command' => $job,
                'key1'=>$key1,
                'key2'=>$key2,
                'queueName' => $this->queueName($displayName),
                "retryDelay"=>$job->retryDelay??0,
                "retryFactor"=>$job->retryFactor??0,
                "maxDelay"=>$job->maxDelay??0,
            ],
        ]);

        $command = $this->jobShouldBeEncrypted($job) && $this->container->bound(Encrypter::class)
            ? $this->container[Encrypter::class]->encrypt(serialize(clone $job))
            : serialize(clone $job);

        return array_merge($payload, [
            'data' => array_merge($payload['data'], [
                'commandName' => get_class($job),
                'command' => $command,
            ]),
        ]);
    }


    public function getJobKey($job){
       if(method_exists($job, 'getJobKey')){
           return $job->getJobKey();
       }
       return  ['',''];
    }

    public function queueName($displayName){
        return substr(strrchr($displayName,"\\"),1);
    }

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
        $obj = json_decode($payload);
        return [
            'queue' => $queue,
            'attempts' => $attempts,
            'reserved_at' => null,
            'available_at' => $this->availableTime($availableAt,$attempts,$obj->data),
            'created_at' => $this->currentTime(),
            'payload' => $payload,
            'job_name'=> $obj->data->queueName,
            'key1'=>$obj->data->key1,
            'key2'=>$obj->data->key2,
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

    public function availableTime($availableAt,$attempts,$jobData)
    {
        if($attempts){
            $retryDelay  = $jobData->retryDelay??0;
            $retryFactor = $jobData->retryFactor??0;
            $maxDelay    = $jobData->maxDelay??0;
            $delayTime = $retryDelay + ($attempts-1)*$retryFactor;
            $maxDelay &&  $delayTime = min($delayTime,$maxDelay);
            $availableAt =  $availableAt+$delayTime;
        }

//        $date = date('Y-m-d H:i:s');
//        $availableAtD = date('Y-m-d H:i:s',$availableAt);
//        echo "---$date---$availableAtD---$attempts".PHP_EOL;
        return $availableAt;
    }


    public function deleteReserved($queue, $id)
    {
        $this->database->transaction(function () use ($id) {
            if ($model =  $this->database->table($this->table)->lockForUpdate()->find($id)) {
                $logData = [
                    'queue'=>$model->queue,
                    'job_name'=>$model->job_name,
                    'key1'=>$model->key1,
                    'key2'=>$model->key2,
                    'payload'=>$model->payload,
                    'attempts'=>$model->attempts,
                    'reserved_at'=>$model->reserved_at,
                    'available_at'=>$model->available_at,
                    'created_at'=>$model->created_at,
                    'completed_at'=>Date::now()
                ];
                $this->database->table($this->log_table)->insert($logData);
                $this->database->table($this->table)->where('id', $id)->delete();
            }
        });
    }



    /**
     * Delete a reserved job from the reserved queue and release it.
     *
     * @param  string  $queue
     * @param  \Illuminate\Queue\Jobs\DatabaseJob  $job
     * @param  int  $delay
     * @return void
     */
    public function deleteAndRelease($queue, $job, $delay,)
    {

        $jobRecord =  $job->getJobRecord();
        $this->database->transaction(function () use ($queue, $job, $delay,$jobRecord) {
            if ($this->database->table($this->table)->lockForUpdate()->find($job->getJobId())) {
                $updateData =  $this->buildDatabaseRecord(
                    $this->getQueue($queue), $jobRecord->payload, $this->availableAt($delay), $jobRecord->attempts
                );
                $this->database->table($this->table)->where('id',$job->getJobId())->update($updateData);
            }

           // $this->release($queue, $job->getJobRecord(), $delay);
        });
    }

}
