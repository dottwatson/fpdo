## What is fpdo?

Fpdo is a versatile tool that allows you to cross-reference data from different sources simply by using the SQL queries you use daily for your MySQL database. Each source is handled as a classic SQL table, described with the blueprints used by Laravel. The package is based on a modified version of https://github.com/vimeo/php-mysql-engine, the core engine of the entire tool.

## Installation

    composer require dottwatson/fpdo

for the configuration go to section [configuration](#configuration)

## Creating a Database

First, let's create a connection in Laravel's database configuration file:  
config/database.php

```php
    <?php
    
    return [
        
        ...
        
        'connections' => [
            
            ...
             
            'test_fpdo' => [
                'driver' => 'fpdo',
                'database' => \App\FpdoDatabases\TestFpdo\Database::class,
                'options' => [
                   PDO::ATTR_CASE => PDO::CASE_LOWER //optional
                ]
            ],
        ],
        
        ...
    ];
```

In this way, we define the database in the classic Laravel manner, except that the actual database will be defined through a dedicated class. 
In this example, I create a folder in the system that will host our database definition in app\FpdoDatabases.

    app --
    	|--FpdoDatabases
    		|--TestFpdo
    			|--Database.php
    			|--Tables
    				|--Categories.php
    				|--Products.php

Source of app/FpdoDatabases/TestFpdo/Database.php:

```php
<?php
namespace App\FpdoDatabases\TestFpdo;

use Fpdo\Definitions\Database as FpdoDatabaseDefinition;

class Database extends FpdoDatabaseDefinition{

    public $name            = 'testfpdo';
    protected $collation    = 'utf8mb4_unicode_ci';
    protected $prefix       = '';
    protected $prefix_index = true;
    protected $strict       = true;
    protected $options      = [];

    public function tables()
    {
        return [
            \App\FpdoDatabases\TestFpdo\Tables\Products::class
        ];
    }
}
```

Now let's define a database table. In this example, we use a JSON file in our storage:
app/FpdoDatabases/TestFpdo/Tables/Products.php

```php
<?php
namespace App\FpdoDatabases\TestFpdo\Tables;

use Fpdo\Definitions\Table;
use Illuminate\Database\Schema\Blueprint;

class Products extends Table {
    protected $name = 'products';

    public function define(Blueprint $table)
    {
        $table->increments('id');
        $table->string('title');
        $table->string('description')->nullable();
        $table->decimal('price');
        $table->decimal('discountPercentage');
        $table->decimal('rating');
        $table->integer('stock')->default(0);
        $table->string('brand')->nullable();
        $table->string('category');
    }

    protected function reader()
    {
        $file = storage_path('app/catalog/products.json');
        $data = json_decode(file_get_contents($file), true);
        return $data;
    }

    protected function writer(array $data = null)
    {
        $str = json_encode($data, JSON_PRETTY_PRINT);
        $file = storage_path('app/catalog/products.json');
        file_put_contents($file, $str);
    }
}
```

In this example, `reader` is executed to read the data and must return an array of key-value pairs of all data records. `writer` is responsible for writing data wherever needed (in this example, it rewrites the same JSON file). However, if you don’t need to write data, `writer` can remain empty but must always be present (due to the abstract class model).

**Notes**: To optimize resources, all data from the tables will be loaded only when needed. This means that if you configure 20 tables, but only query 2, the data from the tables concerned will be loaded. This is called *lazy loading*.

That's it! Everything else remains the same for your application, such as models, relationships, direct queries, and so on.

## Limitations

Basic limitations are described in https://github.com/vimeo/php-mysql-engine. However, the following features have been implemented:
`BOOLEAN COLUMN` - Boolean column alias of tinyint  
`JSON COLUMN` - The JSON column, of course!  
`JSON FUNCTIONS` - The full set of JSON functions as described in https://dev.mysql.com/doc/refman/8.4/en/json-function-reference.html, except for `JSON_TABLE`, `JSON_VALUE`, `MEMBER_OF`.  

**Note:** JSON functions do not support the ** selector.

Additionally, two functions have been implemented for direct query interaction with PHP:
`PHP_CALL`: Accepts at least one parameter representing the function name, or a JSON array with the class and method names. All other parameters will be passed to the callable.

```sql
SELECT * FROM table WHERE PHP_CALL('["myClassName","myMethod"]',table.field,'mytest',1,3,4.52) = 1
```

or 

```sql
SELECT * FROM table WHERE PHP_CALL('my_function_name',table.field,'mytest',1,3,4.52) = 1
```


`PHP_EVAL`: Executes PHP code.

```sql
SELECT * FROM table WHERE PHP_EVAL('return 10/5;') = 2
```

## Custom SQL Functions

You can extend fpdo with custom functions to use in your queries as if they were native functions.  

Creating a function evaluator:
```php
<?php
namespace  My\FpdoFunctionsExtensions;
use Fpdo\Definitions\QueryFunctionsEvaluator;
use Vimeo\MysqlEngine\Processor\ProcessorException;

class Greetings extends QueryFunctionsEvaluator {

    public static function handle(string $functionName, array $params)
    {
        if ($functionName == 'GREETINGS') {
            if (count($params) < 1) {
                return new ProcessorException("Function " . $functionName . " expects 1 parameter");
            }
            return 'Hello ' . $params[0];
        } else {
            return new ProcessorException("Function " . $functionName . " not implemented yet");
        }
    }
}
```
adding it in the config/fpdo.php

```php
<?php
return [
	...
	'extensions' => [
		...
		My\FpdoFunctionsExtensions\Greetings::class,
		...
	]
]
```
and its usage
```sql
SELECT table.*,GREETINGS(table.name) as greetings FROM table
```


## Configuration

To have the configuration file in the `config` folder of your application:

```bash
php artisan vendor:publish --provider="Fpdo\FpdoServiceProvider" --tag=fpdo-config
```

Below is a table describing the parameters:

| Parameter          | Value Type | Explanation                                                                                                                                                                                                                                                                                                                                                                       |
|--------------------|------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| default_charset    | string     | The default charset used for tables (and databases) where it is not otherwise defined.                                                                                                                                                                                                                                                                                            |
| default_collation  | string     | The default collation used for tables (and databases) where it is not otherwise defined.                                                                                                                                                                                                                                                                                          |
| slow_query_log     | boolean    | The slow query log consists of SQL statements that take more than `long_query_time` seconds to execute.                                                                                                                                                                                                                                                                            |
| long_query_time    | int        | Expressed in seconds. Set to -1 to log every query and its execution time.                                                                                                                                                                                                                                                                                                        |
| log_channel        | string     | According to your logging configuration, select your logging channel.                                                                                                                                                                                                                                                                                                             |
| slow_read_log      | boolean    | The slow read log consists of data retrieval from `table::read()` that takes more than `long_read_time` seconds to execute.                                                                                                                                                                                                                                                        |
| long_read_time     | int        | Expressed in seconds. Set to -1 to log each read operation's time to populate tables.                                                                                                                                                                                                                                                                                             |
| write_on_end       | boolean    | The writing logic is a special setting that makes the system faster by avoiding long write processes. When a record is inserted, updated, or deleted, the table needs to be saved to the original source, which takes time if done for each modified record. With this parameter set to `true`, modifications stay in memory and are written at the end of execution. **If enabled, any fatal error during execution will result in all in-memory data being lost!** |
| slow_write_log     | boolean    | The slow write log consists of data writes from `table::write()` that take more than `long_write_time` seconds to execute.                                                                                                                                                                                                                                                        |
| long_write_time    | int        | Expressed in seconds. Set to -1 to log each write operation's time to save tables.                                                                                                                                                                                                                                                                                                |
| extensions         | array      | Extend fpdo with your custom function set. Use class names.


That's it!

If you like this package, consider to buy me a ☕[coffee](https://www.paypal.com/donate/?business=RVJ6GPQ6JFR98&no_recurring=0&item_name=Thank%20you%20for%20your%20support!%20If,%20like%20me,%20you%20believe%20in%20the%20opensource,%20this%20will%20help%20us%20make%20it%20even%20more%20exciting.&currency_code=EUR)
