# laravelCustomerQueue
laravel 数据库队列扩展,数据表额外增加自定义任务名称,关键字key1,key2方便搜索

1.将lib 复制到app文件夹下面
2.config/app.php providers下面 注释掉 Illuminate\Queue\CustomerQueueServiceProvider::class这行,添加\App\lib\CustomerQueueServiceProvider::class,
![image](https://user-images.githubusercontent.com/20874631/169750671-e94b4502-5d3a-4f19-85e6-3df54f3794a7.png)
3.config/queue.php的connections下面添加
```
'customer' => [
            'driver' => 'customer',
            'table' => 'customer_jobs',
            'queue' => 'default',
            'retry_after' => 90,
            'after_commit' => false,
        ],

```


``` sql
create table failed_jobs
(
    id         bigint unsigned auto_increment
        primary key,
    uuid       varchar(255)                          not null,
    connection text                                  not null,
    queue      text                                  not null,
    payload    longtext                              not null,
    exception  longtext                              not null,
    failed_at  timestamp   default CURRENT_TIMESTAMP not null,
    job_name   varchar(20) default ''                null,
    key1       varchar(40) default ''                null,
    key2       varchar(40) default ''                null
)
    collate = utf8mb4_unicode_ci;
    
    
    create table customer_jobs
(
    id           bigint unsigned auto_increment
        primary key,
    queue        varchar(191)           not null,
    job_name     varchar(20) default '' not null,
    key1         varchar(40) default '' not null,
    key2         varchar(40) default '' not null,
    payload      longtext               not null,
    attempts     tinyint unsigned       not null,
    reserved_at  int unsigned           null,
    available_at int unsigned           not null,
    created_at   int unsigned           not null
)
    collate = utf8mb4_unicode_ci;

create index jobs_queue_index
    on customer_jobs (queue);


```

