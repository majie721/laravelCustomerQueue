<?php

namespace App\lib;

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



}
