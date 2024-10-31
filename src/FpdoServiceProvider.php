<?php
namespace Fpdo;

use Illuminate\Support\ServiceProvider;
use Fpdo\Connection as FpdoConnector;
use Illuminate\Database\Connection;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Event;
use Vimeo\MysqlEngine\Php7\FakePdo;

class FpdoServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     */
    public function boot()
    {
        $this->publishes([
            realpath(__DIR__.'/../config/fpdo.php') => config_path('fpdo.php'),
        ],'fpdo-config');        
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->mergeConfigFrom(
            realpath( __DIR__.'/../config/fpdo.php'),'fpdo'
        );  
        
        $this->commands([
            \Fpdo\Console\Commands\FpdoMakeDatabase::class,
            \Fpdo\Console\Commands\FpdoMakeTable::class,
        ]);        

        
        /**
         * Extends the function set available in the queries
         */
        foreach(config('fpdo.extensions',[]) as $extensionClass){
            \Vimeo\MysqlEngine\Processor\Expression\FunctionEvaluator::extends($extensionClass);
        }


        /**
         * Register the driver into the system, so it will be available globally
         */
        Connection::resolverFor('fpdo',function($connection, $connectionName, $prefix, &$config){

            $databaseObject = $config['database'];

            /**
             * @var \Fpdo\Definitions\Database
             */
            $databaseDefinition = (new $databaseObject);

            $config['collation']    = $databaseDefinition->getCollation();
            $config['charset']      = $databaseDefinition->getCharset();
            $config['prefix']       = $databaseDefinition->getPrefix();
            $config['prefix_index'] = $databaseDefinition->getPrefixIndex();
            $config['strict']       = $databaseDefinition->getStrict();
            $config['options']      = $databaseDefinition->getOptions();

            $dsn        = "fpdo:host=127.0.0.1;dbname={$databaseDefinition->getName()};";
            $pdoCls     = '\\Fpdo\\Pdo\\Php'.PHP_MAJOR_VERSION.'\\FPdo';

            /**
             * Due to its nature, the database credentials are not necessaire.
             * So we use fake username and password to accomplish PDO
             */ 
            $instance = new $pdoCls($dsn,Str::random(8),Str::random(8),[],$databaseDefinition);

            foreach($config['options'] as $optName=>$optVal){
                $instance->setAttribute($optName,$optVal);
            }
            
            $instance->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);

            $connector = new FpdoConnector($instance,$databaseDefinition,$config);
            
            /**
             * Force the pdo reader
             */
            $connector->setReadPdo($instance);

            /**
             * Initialize the information_schema
             */
            $instance->buildInformaitonSchema();

            /**
             * Here we read the database definition tables
             * and we create the CREATE TABLE statement for create tables
             * and we execute it.
             */
            foreach($databaseDefinition->getTablesInstances() as  $tableInstance){
                $name           = $tableInstance->getName();
                $creationQuery  = $tableInstance->getCreate($connector);
                $instance->query($creationQuery);

            }
            
            return $connector;
        });

    }
}
