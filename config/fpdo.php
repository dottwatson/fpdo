<?php
return [
    /*
    |---------------------------------------------------------------
    |  THE DEFAULT CHARSET
    |---------------------------------------------------------------
    |
    | The default charset is used on tables (and databases) where it is not defined
    | This affects all columns
    |
    */
    'default_charset' => 'utf8mb4',

    /*
    |---------------------------------------------------------------
    |  THE DEFAULT COLLATION
    |---------------------------------------------------------------
    |
    | The default collation is used on tables (and databases) where it is not defined
    | This affects all columns
    |
    */
    'default_collation' => 'utf8mb4_general_ci',




    /*
    |---------------------------------------------------------------
    |  THE DUMP SECTION
    |---------------------------------------------------------------
    |
    | Using \FpdoFpdo::dump you can choise how many insert rows will be used
    | on a single insert statement. This is the only parametere you have, all others
    | ar demanded to the developer
    |
    */
    'dump'=>[
        'insert_limit'=>5
    ],





    /*
    |---------------------------------------------------------------
    |  THE SLOW QUERIY LOG
    |---------------------------------------------------------------
    |
    | The slow query log consists of SQL statements that take more than long_query_time seconds to execute
    |
    */
    'slow_query_log' => false,

    /*
    |---------------------------------------------------------------
    |  THE SLOW QUERIY LOG TIME
    |---------------------------------------------------------------
    |
    | Expressed in seconds. set it to -1 for EVER log each query and its execution time
    |
    */
    'long_query_time' => 1000,


    
    /*
    |---------------------------------------------------------------
    |  THE LOG CHANNEL
    |---------------------------------------------------------------
    |
    | According with your logging configuration, select your logging channel
    |
    */
    'log_channel' => 'stack',


    
    /*
    |---------------------------------------------------------------
    |  THE READING LOGS FOR TABLES
    |---------------------------------------------------------------
    |
    | The slow read log consists of retrieving data from table::read() that take more than long_read_time seconds to execute
    |
    */
    'slow_read_log' => true,

    /*
    |---------------------------------------------------------------
    |  THE READING LOGS TIME
    |---------------------------------------------------------------
    |
    | Expressed in seconds. set it to -1 for EVER log each reading time to populate tables
    |
    */
    'long_read_time' => 10,


    /*
    |---------------------------------------------------------------
    |  THE WRITE ON END PARAMETER
    |---------------------------------------------------------------
    |
    | The writing logic is a special  setting that makes system more fast and it is implemented for avoid long time writing process
    | Basically when a record is inserted/updated and deleted, and the table needs to be saved on the original source,
    | it performs the same operation for each record modified; so it can takes long execution time. Whith this parametere set to true,
    | each modification stays in memory, and written on th end of execution. 
    | Remember that if you want to use this table for different threads, the other thread can see an old content of the table.  
    |
    */
    'write_on_end'  => true,


    /*
    |---------------------------------------------------------------
    |  THE WRITING LOGS FOR TABLES
    |---------------------------------------------------------------
    |
    | The slow write log consists of writing data in table::write() that take more than long_write_time seconds to execute
    |
    */
    'slow_write_log'  => true,

    /*
    |---------------------------------------------------------------
    |  THE WRITING LOGS TIME
    |---------------------------------------------------------------
    |
    | Expressed in seconds. set it to -1 for EVER log each writing time to save tables
    |
    */
    'long_write_time' => 10,

    /*
    |---------------------------------------------------------------
    |  EXTENSIONS
    |---------------------------------------------------------------
    |
    | Extend fpdo with your custom functions set
    | Here are registered the json suite, and PHP suite
    |
    */
    'extensions' => [
        \Vimeo\MysqlEngine\Processor\Expression\JsonFunctionEvaluator::class,
        \Vimeo\MysqlEngine\Processor\Expression\PhpFunctionEvaluator::class,
    ]

];