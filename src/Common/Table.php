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

    protected $type;

    protected $options = [];

    /**
     * constructor
     *
     * @param string $tableName
     * @param mixed $data
     */
    public function __construct(string $tableName,DataParser $parser,string $type=null,array $options = []){
        $this->name     = $tableName;

        $this->data     = $parser->getData();
        $this->optiosn  = $options;

        if(!is_null($type)){
            $this->type = $type;
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
}


?>