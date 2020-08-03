<?php 
namespace Fpdo\Common;

use Fpdo\Common\QueryBuilder;
use Fpdo\Exception\QueryBuilderException;


abstract class DataParser{
    protected $data = [];
    
    public function getData(){
        return $this->data;
    }
}
?>