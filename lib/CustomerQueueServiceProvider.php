<?php

namespace App\lib;

use Illuminate\Queue\Failed\NullFailedJobProvider;
use Illuminate\Queue\QueueServiceProvider;

class CustomerQueueServiceProvider extends QueueServiceProvider
{
    /**
     * Register the connectors on the queue manager.
     *
     * @param  \Illuminate\Queue\QueueManager  $manager
     * @return void
     */
    public function registerConnectors($manager)
    {
        foreach (['Null', 'Sync', 'Database', 'Redis', 'Beanstalkd','CustomerDatabase', 'Sqs'] as $connector) {
            $this->{"register{$connector}Connector"}($manager);
        }
    }


    /**
     * 自定义数据库队列
     * @param $manager
     * @return void
     */
    protected function registerCustomerDatabaseConnector($manager)
    {
        $manager->addConnector('customer', function () {
            return new CustomerDatabaseConnector($this->app['db']);
        });
    }


    /**
     * 覆写失败任务
     *
     * @param  array  $config
     * @return \Illuminate\Queue\Failed\DatabaseUuidFailedJobProvider
     */
    protected function databaseUuidFailedJobProvider($config)
    {
        return new CustomerDatabaseUuidFailedJobProvider(
            $this->app['db'], $config['database'], $config['table']
        );
    }



    /**
     * Register the failed job services.
     *
     * @return void
     */
    protected function registerFailedJobServices()
    {
        $this->app->singleton('queue.failer', function ($app) {
            $config = $app['config']['queue.failed'];

            if (array_key_exists('driver', $config) &&
                (is_null($config['driver']) || $config['driver'] === 'null')) {
                return new NullFailedJobProvider;
            }

            if (isset($config['driver']) && $config['driver'] === 'dynamodb') {
                return $this->dynamoFailedJobProvider($config);
            } elseif (isset($config['driver']) && $config['driver'] === 'database-uuids') {
                $config['table'] = $app['config']['queue.connections']['customer']['failed_table'];
                if(!$config['table']){
                    throw new \RuntimeException("customer.queue.failed_table config error");
                }
                return $this->databaseUuidFailedJobProvider($config);
            } elseif (isset($config['table'])) {
                return $this->databaseFailedJobProvider($config);
            } else {
                return new NullFailedJobProvider;
            }
        });
    }



}
