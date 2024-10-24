<?php
namespace Fpdo\Pdo;

use Fpdo\Definitions\Database as DatabaseDefinition;
use Fpdo\Server;
use Vimeo\MysqlEngine\Server as MysqlEngineServer;
use Illuminate\Support\Str;

trait FpdoTrait{
    protected $databaseDefinition;
    public $informationSchema;


    /**
     * @param array<string>  $options
     */
    public function __construct(string $dsn, string $username = '', string $passwd = '', array $options = [],DatabaseDefinition $databaseDefinition = null)
    {
        $dsn = \Nyholm\Dsn\DsnParser::parse($dsn);
        $host = $dsn->getHost();

        if (preg_match('/dbname=([a-zA-Z0-9_]+);/', $host, $matches)) {
            $this->databaseName = $matches[1];
        }

        // do a quick check for this string – hacky but fast
        $this->strict_mode = \array_key_exists(\PDO::MYSQL_ATTR_INIT_COMMAND, $options)
            && \strpos($options[\PDO::MYSQL_ATTR_INIT_COMMAND], 'STRICT_ALL_TABLES');


        $this->server  = MysqlEngineServer::getOrCreate('primary');
        
        if($databaseDefinition){
            $this->databaseDefinition   = $databaseDefinition;
        }
    }

    public function getServer() :MysqlEngineServer
    {
        if(!$this->server){
            $this->server = MysqlEngineServer::getOrCreate('primary');
        }
        
        return $this->server;
    }

    public function getDatabaseDefinition()
    {
        return $this->databaseDefinition;
    }

    public function buildInformaitonSchema()
    {
        $dsn        = "fpdo:host=127.0.0.1;dbname=information_schema;";
        $instance   = new static($dsn,Str::random(8),Str::random(8),[]);

        $queriesLines = file(__DIR__.'/../../resources/information_schema.stub');

        $queries = [];
        $query = '';
        foreach($queriesLines as $line){
            if(strpos($line,'--') === 0){
                continue;                
            }
            
            if(trim($line) == ');'){
                $line = ') DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4;';
                $query.=$line;
                $queries[] = preg_replace_callback('/`([^`]+)`/',function($matches) {
                        return '`'.strtolower($matches[1]).'`';
                    },trim($query));

                $query='';
            }
            else{
                $query.=$line;
            }
        }

        foreach($queries as $query){
            $instance->query($query);
        }

        $this->informationSchema = $instance;

        return $instance;
    }


}