<?php
namespace Fpdo\Definitions;

use Illuminate\Config\Repository;

abstract class Database{

    
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $collation    = 'utf8mb4_unicode_ci';
    /**
     * @var string
     */
    protected $charset      = 'utf8mb4';
    /**
     * @var string
     */
    protected $prefix       = '';
    /**
     * @var boolean
     */
    protected $prefix_index = true;
    /**
     * @var boolean
     */
    protected $strict       = true;

    /**
     * database options
     *
     * @var \Illuminate\Config\Repository
     */
    protected $options = [];

    /**
     * the tabls instances
     *
     * @var array<\Fpdo\Definitions\Table>
     */
    protected $tablesInstances = [];


    /**
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        
        $options        = array_merge_recursive($this->options,$options);
        $this->options  = new Repository($options); 
        
        $this->charset      = config('fpdo.default_charset','utf8mb4');
        $this->collation    = config('fpdo.default_collation','utf8mb4_general_ci');
    }


    /**
     * @return string
     */
    public function getCollation()
    {
        return $this->collation ?? config('fpdo.default_charset','utf8mb4');
    }

    /**
     * @return string
     */
    public function getCharset()
    {
        return $this->charset ?? config('fpdo.default_charset','utf8mb4');
    }

    /**
     * @return string
     */
    public function getPrefix($default = null)
    {
        return $this->prefix ?? $default;
    }

    /**
     * @return string
     */
    public function getPrefixIndex($default = null)
    {
        return $this->prefix_index ?? $default;
    }

    /**
     * @return bool
     */
    public function getStrict($default = null)
    {
        return $this->strict ?? $default;
    }


    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * return tables definition
     *
     * @return array<string>
     */
    public function tables()
    {
        return [];
    }


    /**
     * @return mixed
     */
    public function getOption(string $name,$default = null)
    {
        return $this->options->get($name, $default);
    }

    /**
     * @return void
     */
    public function setOption(string $name,$value)
    {
        $this->options->set($name,$value);
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options->all();
    }

    /**
     * return all tables instances
     *
     * @return array<\Fpdo\Definitions\Table>
     */
    public function getTablesInstances()
    {
        foreach($this->tables() as $tableClass){
            $name = (new $tableClass())->getName();
            if(!isset($this->tablesInstances[$name])){
                $instance = new $tableClass($this);
                $this->tablesInstances[$instance->getName()] = $instance;
            }
        }

        return $this->tablesInstances;
    }

    /**
     * drop the table
     *
     * @param string $name
     * @return void
     */
    public function dropTable(string $name)
    {
        if(isset($this->tablesInstances[$name])){
            unset($this->tablesInstances[$name]);
        }
    }
}