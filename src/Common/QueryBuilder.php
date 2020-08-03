<?php 
namespace Fpdo\Common;

use Collery\Collery;
use Fpdo\Common\Resource;
use Fpdo\Common\Table;
use Fpdo\Exception\QueryBuilderException;

class QueryBuilder extends Collery{

    protected $resolvedJoins        = false;
    protected $withJoinedFields     = [];
    protected $joinerResources      = [];
    protected $joinerResourcesInfo  = [];
    protected $having               = [];
    protected $then;

    

    /**
     * getting prepared data
     * This overrides the original prepare method to apply joins rules and
     * append extra data required via the `with` method.
     * Objects are not allowed
     *
     * @return array
     * @throws ConditionNotAllowedException
     */
    protected function prepare(){
        //prevent sorting on original result set (it will be perfomed at the end)
        $originalSortings   = $this->sorters;
        $this->sorters      = [];
        
        //if no select, select all first level items in the array
        if(!$this->select){
            $this->select = '*';
        }
        
        parent::prepare();
        
        $data = $this->resultSet;

        if($data && $this->joinerResources && $this->resolvedJoins == false){
            $resultData = [];
            foreach($data as $result){
                //assuming the record is able to be included in the resultset
                $allowed = true;

                foreach($this->joinerResources as $tableName=>$tableInfo){
                    foreach($tableInfo['rules'] as $ruleGroup){
                        //create a query from static dataset based on table data
                        $joinedQueryBuilder = new static($tableInfo['data'],$this->separator);

                        //apply on joined table the rules (where etc...)
                        call_user_func_array($ruleGroup['closure'],[$joinedQueryBuilder,$result]);
                    
                        //execute query
                        $joinedDataResults = $joinedQueryBuilder->get();

                        //if is left join or join with return data
                        if( $ruleGroup['isLeftJoin'] || (!$ruleGroup['isLeftJoin'] &&  count($joinedDataResults) > 0) ){
                            $result = $this->extendDataJoin($tableName,$result,$joinedDataResults);
                        }
                        else{
                            $allowed = false;
                        }
                    }
                }
                if($allowed == true){
                    $resultData[] = $result;
                }
            }
            $data = $resultData;
            $this->resultSet = $data;
        }
        
        $this->resolvedJoins = true;
        
        $this->applyHaving();
        
        if($this->then){
            $newResultSet = call_user_func($this->then,$this->resultSet);
        
            if(is_array($newResultSet)){
                $this->resultSet = $newResultSet; 
            }
        }


        $this->sorters = $originalSortings;
        if($this->sorters){
            $this->sortResultset();
        }

    }

    /**
     * Apply having rules on surrent resultset
     * The having uses only the where clausole
     *
     * @return void
     */
    protected function applyHaving(){
        if($this->having){
            $tmp = (new Collery($this->resultSet))->select('*');
            foreach($this->having as $conditions){
                $tmp->where(...$conditions);
            }
    
            $this->resultSet = $tmp->get();
        }
    }



    /**
     * Remove previouse setted conditions 
     *
     * @return self
     */
    protected function reset(){
        parent::reset();

        $this->resolvedJoins        = false;
        $this->withJoinedFields     = [];
        $this->joinerResources      = [];
        $this->joinerResourcesInfo  = [];
        $this->having               = [];
        $this->then                 = null;

        return $this;
    }

    /**
     * Extends the record with joined data
     *
     * @param string $tableName
     * @param array $resultData
     * @param array $joinedData
     * @return array
     */
    protected function extendDataJoin($tableName,$resultData,$joinedData=[]){

        if($this->withJoinedFields){
            foreach($this->withJoinedFields as $joinedFieldName => $joinedFieldNameAlias){
                $fieldNames = explode('.',trim($joinedFieldName),2);
                if($fieldNames[0] != $tableName){
                    continue;
                }

                if(!isset($fieldNames[1])){
                    $fieldNames[1] = '*';
                }

                $field2Search       = $fieldNames[1];
                $field2Implement    = $joinedFieldNameAlias;

                if($joinedData){
                    foreach($joinedData as $joinedDataRow){
                        if($field2Search == '*'){
                            $rowData = $joinedDataRow;
                        }
                        else{
                            $rowData = (new static($joinedDataRow))->select($field2Search)->get();
                        }
    
                        if(strpos($field2Implement,'.') !== false){
                            $tmpKeys    = explode('.',$field2Implement);
                            $tmpArray   = [];
                            $referenced = &$tmpArray;
                            while($tmpKey = array_shift($tmpKeys)){
                                //if count of tmpKeys == 0 then is a value assegnation
                                //else is an array creation for the result
                                if(count($tmpKeys)){
                                    $referenced = (!isset($referenced[$tmpKey]))?[]:$referenced[$tmpKey];
                                    $referenced = &$referenced[$tmpKey];
                                }
                                else{
                                    $referenced[$tmpKey][] = $rowData;
                                }
                            }
                            $resultData = array_merge_recursive($resultData,$tmpArray);
                        }
                        else{
                            if(!isset($resultData[$field2Implement])){
                                $resultData[$field2Implement] = [];
                            }
                            $resultData[$field2Implement][] = $rowData;
                        }
                    }
                }
                else{
                    if(strpos($field2Implement,'.') !== false){
                        $tmpKeys    = explode('.',$field2Implement);
                        $tmpArray   = [];
                        $referenced = &$tmpArray;
                        while($tmpKey = array_shift($tmpKeys)){
                            //if count of tmpKeys == 0 then is a value assegnation
                            //else is an array creation for the result
                            if(count($tmpKeys)){
                                $referenced = (!isset($referenced[$tmpKey]))?[]:$referenced[$tmpKey];
                                $referenced = &$referenced[$tmpKey];
                            }
                            else{
                                $referenced[$tmpKey] = [];
                            }
                        }
                        $resultData = array_merge_recursive($resultData,$tmpArray);
                    }
                    else{
                        $resultData[$field2Implement] = [];
                    }
                }
            }
        }
        return $resultData;
    }
    

    /**
     * Include extra data in resultset, in conjunction with join
     *
     * @param array $fields
     * @return self
     */
    public function with($fields=[]){
        $fields = is_array($fields)?$fields:[$fields];


        foreach($fields as $fieldKey=>$fieldName){
            $joinFieldKey = (is_numeric($fieldKey))?$fieldName:$fieldKey;
            $this->withJoinedFields[$joinFieldKey] = $fieldName;
        }

        return $this;
    }


    /**
     * Join a table. 
     * If join resultset = 0 then the row is skipped in the final resultset 
     *
     * @param array|Table $resource [$resourceAlias,$resource] or a Table
     * @param callable $closure
     * @param boolean $leftJoin
     * @return self
     */
    public function join($resourceArray,callable $closure,$leftJoin = false){
        if(is_object($resourceArray) && is_a($resourceArray,Table::class)){
            $tableName = $resourceArray->getName();
            $tableData = $resourceArray->getData();
        }
        elseif(is_array($resourceArray)){
            $resourceArray = array_values($resourceArray);
            if(count($resourceArray) < 2 ){
                throw new QueryBuilderException("Undefined table data");
            }

        
            $tableName = (string)$resourceArray[0];
            $tableData = Resource::acquire($resourceArray[1]);
        }
        
        if(isset($this->joinerResources[$tableName])){
            throw new QueryBuilderException("{$tableName} is already used as alias");
        }

        $this->joinerResources[$tableName] = [
            'data'=>$tableData,
            'rules'=>[
                ['closure'=>$closure,'isLeftJoin'=>$leftJoin]
            ]
        ];
       
        return $this;
    }

    /**
     * Left joina  table. If no results for the join, the result is not skipped (as Left explains itself)
     *
     * @param mixed $table
     * @param callable $closure
     * @return self
     */
    public function leftJoin($resource,callable $closure){
        return $this->join($resource,$closure,true);
    }

    /**
     * Alias of join
     *
     * @param array $table
     * @param callable $closure
     * @return self
     */
    public function innerJoin($resource,callable $closure){
        return $this->join($resource,$closure);
    }


    /**
     * Apply where rules on resulset
     *
     * @return self
     */
    public function having(){
        $args = func_get_args();
        $this->having[]=$args;
        return $this;
    }


    /**
     * This is the very end filter
     *
     * @param string|\Closure|array $callBack
     * @return void
     */
    public function then($callBack){
        if(!is_callable($callBack)){
            throw new QueryBuilderException("the `then` method requires a valid callback");
        }

        $this->then = $callBack;

        return $this;
    }


}

?>