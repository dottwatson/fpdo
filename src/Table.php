<?php
namespace Fpdo;

use Illuminate\Config\Repository;

class Table{
    protected $name;
    protected $columns;
    protected $options = [];

    protected $loaded = false;
    protected $connection;
    protected $database;


    public function __construct(string $name, array $options = [])
    {
        $this->name     = $name;
        $this->options  = new Repository($options);        
    }

    public function getName()
    {
        return $this->name;
    }

    public function getDatabase(){
        return $this->database;
    }

    public function getColumns()
    {
        return $this->columns;
    }

}