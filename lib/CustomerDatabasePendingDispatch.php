<?php

namespace App\lib;

use Illuminate\Foundation\Bus\PendingDispatch;

class CustomerDatabasePendingDispatch extends PendingDispatch
{
    public function __construct($job)
    {
        $this->job = $job;
    }
}
