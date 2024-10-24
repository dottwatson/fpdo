<?php 

namespace Fpdo;

use Fpdo\Exceptions\FpdoException;
use Illuminate\Database\MySqlConnection as BaseConnection;
use Illuminate\Support\Facades\DB;
use Vimeo\MysqlEngine\Parser\CreateTableParser;
use Vimeo\MysqlEngine\Processor\CreateProcessor;
use Fpdo\TableData;
use \Fpdo\Definitions\Database as DatabaseDefinition;

class Connection extends BaseConnection{

    /**
     * the process is using this connection
     *
     * @var \Fpdo\SharedMemeory\Process
     */
    protected $process;

    /**
     * The Fpdo
     *
     * @var \Vimeo\MysqlEngine\FakePdoInterface
     */
    protected $pdo;

    /**
     * The server
     *
     * @var \Vimeo\MysqlEngine\ServerInterface
     */
    protected $server;

    /**
     * The database definition
     *
     * @var \Fpdo\Definitions\Database
     */
    protected $databaseDefinition;

    /**
     * Create a new database connection instance.
     *
     * @param  \PDO|\Closure  $pdo
     * @param  \Fpdo\Definitions\Database  $database
     * @param  array  $config
     * @return void
     */
    public function __construct($pdo, DatabaseDefinition $database, array $config = [])
    {
        parent::__construct($pdo,$database->getName(),$database->getPrefix(),$config);
        $this->pdo                  = $pdo;
        $this->server               = $this->getPdo()->getServer();
        $this->databaseDefinition   = $database;
    }
   
    /**
     * returns current pdo
     *
     * @return \VImeo\MysqlEngine\\Php7\Fpdo|\Fpdo\Php8\Fpdo
     */
    public function getPdo()
    {
        return $this->pdo;
    }

    /**
     * return current server
     *
     * @return \Fpdo\Server
     */
    public function getServer()
    {
        return $this->server;
    }



    public function save($tables = null)
    {
        $this->server->saveOutputTable($this->pdo,$tables);       
    }


    /**
     * @return \Fpdo\Definitions\Database
     */
    public function getDatabaseDefinition()
    {
        return $this->databaseDefinition;
    }

    public function __destruct()
    {
        $instances   = $this->getDatabaseDefinition()->getTablesInstances();

        foreach($instances as $instance){
            if($instance->isDirty){
                $instance->write($this,null);
            }
        }
    }
}
