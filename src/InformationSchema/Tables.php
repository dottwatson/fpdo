<?php
namespace Fpdo\InformationSchema;

use \Vimeo\MysqlEngine\FakePdoInterface;
use \Vimeo\MysqlEngine\Query\CreateQuery;


class Tables{

    /**
     * @var \Vimeo\MysqlEngine\FakePdoInterface $conn
     */
    protected $connection;

    /**
     * @var string
     */
    public $name;


    /**
     * @var \Vimeo\MysqlEngine\Query\CreateQuery
     */
    public $query;

    /**
     * @var array<\Fpdo\InformationSchema\Columns>
     */
    protected $columns = [];

    /**
     * 
     * @param \Fpdo\Pdo\Php7\FPdo|\Fpdo\Pdo\Php8\FPdo $connection
     * @param string $databaseName
     * @param CreateQuery $query
     */
    public function __construct($connection,CreateQuery $query)
    {
        $this->connection   = $connection;
        $this->query        = $query;

        foreach($this->query->fields as $index=>$field){
            $this->columns[$field->name] = new Columns($connection,$this,$field,$index+1);
        }

    }

    /**
     * return the cretion query object
     *
     * @return \Vimeo\MysqlEngine\Query\CreateQuery
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @return string
     */
    public function getDatabaseName()
    {
        return $this->connection->getDatabaseName();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->query->name;
    }

    /**
     * @return string|mixed
     */
    public function getCharset(string $default = null)
    {
        return $this->query->props['CHARSET'] ?? $default;
    }

    /**
     * @return string|mixed
     */
    public function getCollation(string $default = null)
    {
        return $this->query->props['COLLATE'] ?? $default;
    }


    /**
     * Return array of table info for information_schema.tables
     *
     * @return array
     */
    public function getRowData()
    {
        return [
            'table_schema'      => $this->getDatabaseName(),
            'table_name'        => $this->getName(),
            'table_type'        => 'BASE TABLE',
            'row_format'        => 'Dynamic',
            'table_rows'        => 0,
            'table_collation'   => $this->getCollation(),
            'auto_increment'    => 1
        ];
    }

    /**
     * pupulate the table
     *
     * @return void
     */
    public function populate()
    {
        foreach($this->columns as $columnsRow){
            $columnsRow->populate();
        }
    }
}