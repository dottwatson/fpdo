<?php
namespace Fpdo\Pdo\Php8;

use Fpdo\Pdo\FpdoTrait;
use Vimeo\MysqlEngine\Php8\FakePdo;

class FPdo extends FakePdo{
    use FpdoTrait;

    /**
     * @var Server
     */
    private $server;

    /**
     * @var ?\PDO
     */
    private $real = null;

    /**
     * @param string $statement
     * @param array $options
     * @return FakePdoStatement
     */
    #[\ReturnTypeWillChange]
    public function prepare($statement, array $options = [])
    {
        return new FPdoStatement($this, $statement, $this->real);
    }

}
