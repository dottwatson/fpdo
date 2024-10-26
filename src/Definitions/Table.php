<?php
namespace Fpdo\Definitions;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use \Illuminate\Database\Connection;
use Illuminate\Database\Schema\Grammars\MySqlGrammar;
use Vimeo\MysqlEngine\DataIntegrity;
use Illuminate\Support\Facades\Log;


abstract class Table{
    
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string|null
     */
    protected $collation;

    /**
     * @var string|null
     */
    protected $charset;

    /**
     * @var \Fpdo\Definitions\Database
     */
    protected $databaseDefinition;


    /**
     * @var boolean
     */
    protected $ready = false;

    /**
     * @var array
     */
    public $writeOnEndData = [];
    public $writeOnEndConnection;

    /**
     * if table has modified in insert/update/delete
     *
     * @var boolean
     */
    public $isDirty = false;

    public function __construct(Database $databaseDefinition = null)
    {
        if($databaseDefinition){
            $this->databaseDefinition = $databaseDefinition;
        }
    }



    /**
     * define the table schema using blueprint
     *
     * @param Blueprint $table
     * @return void
     */
    abstract public function define(Blueprint $table);

    /**
     * get Table name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * get Table collation
     *
     * @return string|null
     */
    public function getCollation()
    {
        return $this->collation ?? $this->databaseDefinition->getCollation();
    }

    /**
     * get Table charset
     *
     * @return string|null
     */
    public function getCharset()
    {
        return $this->charset ?? $this->databaseDefinition->getCharset();
    }
    
    /**
     * write data on source
     *
     * @param \Fpdo\Pdo\Php7\FPdo|\Fpdo\Pdo\Php8\FPdo $conn
     * @return mixed
     */
    public function read($conn)
    {
        $start = microtime(true);


        if(!$this->ready){
            $this->ready            = true;
            $data                   = $this->reader();
            $server                 = $conn->getServer();
            $database               = $conn->getDatabaseDefinition();
            $table_definition       = $conn->getServer()->getTableDefinition($conn->getDatabaseName(), $this->name);
            $autoIncrementColumn    = null;
            $autoIncrementQuery     = $conn->query("SELECT * FROM `information_schema`.`columns` WHERE table_schema = '{$database->getName()}' AND table_name = '{$this->name}' AND extra = 'auto_increment'");
            
            
            while($res = $autoIncrementQuery->fetch()){
                $autoIncrementColumn = $res['column_name'];
                break;
            }


            foreach($data as &$row){
                $values = [];
                $sql = [];
                foreach($row as $rowKey=>$rowValue){
                    $tmpValue = json_decode($rowValue,true);
                    if(json_last_error() === JSON_ERROR_NONE){
                        $rowValue= $tmpValue;
                    }

                    $sql[] = "{$rowKey}=:{$rowKey}";
                    $values[$rowKey] = "{$rowKey}='".addslashes($rowValue)."'";
                }

                $parsed_query   = \Vimeo\MysqlEngine\Parser\SQLParser::parse("INSERT INTO `{$this->name}` SET ".implode(", ",$values));
                $affectedRows   = \Vimeo\MysqlEngine\Processor\InsertProcessor::process(
                    $conn,
                    new \Vimeo\MysqlEngine\Processor\Scope([]),
                    $parsed_query
                );
            }

            $executionTime = microtime(true)-$start;
            if( config('fpdo.slow_read_log',false) == true && 
            (config('fpdo.long_read_time',-1) == -1 || config('fpdo.long_read_time',-1) <= $executionTime )
            ){
                Log::channel(config('fpdo.log_channel','stack'))->info("Fpdo slow read");
                Log::channel(config('fpdo.log_channel','stack'))->info("Seconds: {$executionTime}");
                Log::channel(config('fpdo.log_channel','stack'))->info("Database : {$database->getName()}");
                Log::channel(config('fpdo.log_channel','stack'))->info("Table : {$this->name} ".static::class);
            }
        }
    }
    
    /**
     * write data on source
     *
     * @param \Fpdo\Pdo\Php7\FPdo|\Fpdo\Pdo\Php8\FPdo $conn
     * @param array $data (The data to be written instead the table stored data)
     * @return mixed
     */
    public function write($conn,array $data = null,bool $atEnd = false)
    {

        $start      = microtime(true);
        $server     = $conn->getServer();
        $database   = $conn->getDatabaseDefinition();
        $data       = $data ?? $server->getTable($database->getName(),$this->name);

        if($data){
            $data = array_values($data);
        }

        if($atEnd){
            $this->writeOnEndData       = $data;
            return;
        }

        $allowWriting = false;

        $result = $this->writer($data);

        $executionTime = microtime(true)-$start;



        if( config('fpdo.slow_write_log',false) == true && 
        (config('fpdo.long_write_time',-1) == -1 || config('fpdo.long_write_time',-1) <= $executionTime )
        ){
            Log::channel(config('fpdo.log_channel','stack'))->info("Fpdo slow write");
            Log::channel(config('fpdo.log_channel','stack'))->info("Seconds: {$executionTime}");
            Log::channel(config('fpdo.log_channel','stack'))->info("Database : {$database->getName()}");
            Log::channel(config('fpdo.log_channel','stack'))->info("Table : {$this->name} ".static::class);
        }

        return $result;
    }

    /**
     * Table is already loaded?
     *
     * @return bool
     */
    public function ready()
    {
        return $this->ready == true;
    }

    /**
     * read the resource
     *
     * @return mixed
     */
    abstract protected function reader();


    /**
     * write on the resource
     *
     * @param array|null $data
     * @return mixed
     */
    abstract protected function writer(array $data= null);

    /**
     * generate schema for table creation
     *
     * @return string
     */
    public function getCreate(Connection $connection)
    {
        $that       = $this;
        $command    = new \Illuminate\Support\Fluent([]);
        $blueprint  = new Blueprint($this->name,function(Blueprint $table) use ($that){
            $that->define($table);
        });

        if(!$blueprint->charset){
            $blueprint->charset(config('fpdo.default_charset','utf8mb4'));
        }

        if(!$blueprint->collation){
            $blueprint->collation(config('fpdo.default_charset','utf8mb4_general_ci'));
        }


        return (new MySqlGrammar)->compileCreate($blueprint,$command,$connection);
    }


}