<?php
namespace Fpdo\Pdo\Php7;

use Fpdo\Pdo\FpdoTrait;
use Vimeo\MysqlEngine\Php7\FakePdo;

class FPdo extends FakePdo
{
    use FpdoTrait;


    /**
     * @param string $statement
     * @param array $options
     * @return FakePdoStatement
     */
    public function prepare($statement, $options = [])
    {
        $stmt = new FPdoStatement($this, $statement, $this->real);
        if ($this->defaultFetchMode) {
            $stmt->setFetchMode($this->defaultFetchMode);
        }
        return $stmt;
    }

}
