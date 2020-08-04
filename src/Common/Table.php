<?php 
namespace Fpdo\Common;

use Fpdo\Common\Resource;
use Fpdo\Common\DataParser;


class Table{

    /**
     * the tablename
     *
     * @var string
     */
    protected $name;

    /**
     * The data of this table
     *
     * @var array
     */
    protected $data;

    /**
     * The table type
     *
     * @var string
     */
    protected $type;

    /**
     * The table options
     *
     * @var array
     */
    protected $options = [];

    /**
     * The query builder array keys separator
     *
     * @var string
     */
    protected $separator = '.';

    /**
     * constructor
     *
     * @param string $tableName
     * @param mixed $data
     * @param string $type
     * @param string|array $options
     */
    public function __construct(string $tableName,DataParser $parser,string $type=null,$options = []){
        $this->name     = $tableName;
        $this->data     = $parser->getData();

        if(!is_null($type)){
            $this->type = $type;
        }

        if(is_string($options)){
            $this->separator = $options;
        }
        elseif(is_iterable($options)){
            $this->options  = $options;
        }
    }

    /**
     * returns table name
     *
     * @return string
     */
    public function getName(){
        return $this->name;
    }

    /**
     * returns tablel data
     *
     * @return array
     */
    public function getData(){
        return [
            "{$this->getName()}"=>$this->data
        ];
    }


    public function getSeparator(){
        return $this->separator;
    }
}


?>