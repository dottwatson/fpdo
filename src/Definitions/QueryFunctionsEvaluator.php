<?php
namespace Fpdo\Definitions;

use Vimeo\MysqlEngine\FakePdoInterface;
use Vimeo\MysqlEngine\Processor\Expression\Evaluator;
use Vimeo\MysqlEngine\Processor\QueryResult;
use Vimeo\MysqlEngine\Processor\Scope;
use Vimeo\MysqlEngine\Query\Expression\FunctionExpression;
use Vimeo\MysqlEngine\Processor\ProcessorException;



abstract class QueryFunctionsEvaluator{

    /**
     * @var \Vimeo\MysqlEngine\FakePdoInterface
     */
    protected static $connection;

    /**
     * @var \Vimeo\MysqlEngine\Processor\Scope
     */
    protected static $scope;

    /**
     * @var \Vimeo\MysqlEngine\Query\Expression\FunctionExpression
     */
    protected static $expression;

    /**
     * @var array
     */
    protected static $row;

    /**
     * @var \Vimeo\MysqlEngine\Processor\QueryResult
     */
    protected static $result;

    /**
     * the evaluator processor
     *
     * @param FakePdoInterface $conn current connection
     * @param Scope $scope current scope
     * @param FunctionExpression $expr the expression passed
     * @param array $row the row to working on
     * @param QueryResult $result the result
     * @return mixed
     */
    public static function evaluate(
        FakePdoInterface $conn,
        Scope $scope,
        FunctionExpression $expr,
        array $row,
        QueryResult $result
    ){
        static::$connection = $conn;
        static::$scope      = $scope;
        static::$row        = $row;
        static::$result     = $result;
        static::$expression = $expr;

        $fn     = static::getFunctionName();
        $params = static::getParams();

        return static::handle($fn,$params);

    }

    /**
     * get current connection 
     *
     * @return \Vimeo\MysqlEngine\FakePdoInterface
     */
    public static function getConnection()
    {
        return static::$connection;
    }
    

    /**
     * get current connection 
     *
     * @return \Vimeo\MysqlEngine\Query\Expression\FunctionExpression
     */
    public static function getExpression()
    {
        return static::$expression;
    }

    /**
     * get current function name
     *
     * @return string
     */
    public static function getFunctionName()
    {
        return static::$expression->functionName;
    }

    /**
     * get expression args
     *
     * @return array
     */
    public function getParams()
    {
        $params = [];
        foreach(static::$expression->args as $arg){
            $params[$arg->name] = Evaluator::evaluate(
                static::$connection,
                static::$scope,
                $arg,
                static::$row,
                static::$result
            );
        }

        return $params;
    }


    /**
     * handle the funcition name and function arguments
     *
     * @param string $functionName
     * @param array $params
     * @return mixed
     */
    abstract static function handle(string $fn,array $params);

}

