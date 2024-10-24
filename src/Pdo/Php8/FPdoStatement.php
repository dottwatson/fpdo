<?php
namespace Fpdo\Pdo\Php8;


class FPdoStatement extends \PDOStatement
{
    use \Fpdo\FpdoStatementTrait;


    /**
     * Overriding execute method to add query logging
     * @param ?array $params
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function execute(?array $params = null)
    {
        return $this->universalExecute($params);
    }

    /**
     * @param  int $fetch_style
     * @param  mixed      $args
     */
    public function fetchAll(int $fetch_style = -123, ...$args) : array
    {
        return $this->universalFetchAll($fetch_style, ...$args);
    }

    /**
     * @param  int $fetch_style
     * @param  mixed      $args
     */
    public function setFetchMode(int $mode, ...$args) : bool
    {
        if($mode === \PDO::FETCH_OBJ){
            $mode = \PDO::FETCH_CLASS;
        }

        return $this->universalSetFetchMode($mode, ...$args);
    }

    /**
     * @psalm-taint-sink callable $class
     *
     * @template T
     * @param    class-string<T>|null $class
     * @param    array|null $ctorArgs
     * @return   false|T
     */
    #[\ReturnTypeWillChange]
    public function fetchObject(?string $class = \stdClass::class, ?array $ctorArgs = null)
    {
        return $this->universalFetchObject($class, $ctorArgs);
    }


}
