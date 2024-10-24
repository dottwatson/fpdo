<?php
namespace Fpdo\InformationSchema;

use \Vimeo\MysqlEngine\Query\CreateColumn;
use \Fpdo\InformationSchema\Tables;

class Columns{

    protected $connection;
    protected $table;

    protected $tableName;
    protected $databaseName;

    protected $createColumn;
    protected $index;
    
    /**
     *
     * @param \Fpdo\Pdo\Php7\FPdo|\Fpdo\Pdo\Php8\FPdo $connection
     * @param Tables $table
     * @param CreateColumn $createColumn
     * @param integer $index
     */
    public function __construct($connection,Tables $table,CreateColumn $createColumn,int $index = 1)
    {
        $this->connection   = $connection;
        $this->table        = $table;
        $this->createColumn = $createColumn;
        $this->databaseName = $connection->getDatabaseName();
        $this->tableName    = $table->getName();
        $this->index        = $index;
    }


    /**
     * creates the array of data to store in the information_schema.columns
     *
     * @param integer $index
     * @param CreateColumn $column
     * @return array
     */
    protected function getRowData()
    {
        $dataType = strtolower($this->createColumn->type->type);
        $data = [
            'table_catalog'     => 'def',
            'table_schema'      => $this->databaseName,
            'table_name'        => $this->tableName,
            'ordinal_position'  => $this->index,
            'column_name'       => $this->createColumn->name,
            'is_nullable'       => $this->createColumn->type->null ? 'YES':'NO',
            'column_default'    => $this->createColumn->default,
            'data_type'         => $dataType,
            'column_key'        => (is_array($this->createColumn->more) && in_array('PRIMARY KEY',$this->createColumn->more))?'PRI':'',
            'privileges'        => 'select,insert,update,references',
            'extra'             => $this->createColumn->auto_increment ? 'auto_increment':''
        ];

        switch($dataType){
            case 'varchar':
                $data['character_maximum_length']   = $this->createColumn->type->length ?? 255;
                $data['character_octet_length']     = mb_strlen(str_pad('',$data['character_maximum_length'],'X'));
                $data['character_set_name']         = $this->createColumn->type->character_set ?? $this->table->getCharset();
                $data['collation_name']             = $this->createColumn->type->collation ?? $this->table->getCollation();
                $data['column_type']                = 'varchar('.$data['character_maximum_length'].')';
            break;
            case 'bigint':
                $data['column_default']             = $this->createColumn->default ?? 0;
                $data['numeric_precision']          = $this->createColumn->type->length ?? 20;
                $data['numeric_scale']              = '0';
                $data['column_type']                = 'bigint('.$data['numeric_precision'].')'.($this->createColumn->type->unsigned?' unsigned':'');
            break;
            case 'longtext':
                $data['character_maximum_length']   = '4294967295';
                $data['character_octet_length']     = '4294967295';
                $data['character_set_name']         = $this->createColumn->type->character_set ?? $this->table->getCharset();
                $data['collation_name']             = $this->createColumn->type->collation ?? $this->table->getCollation();
                $data['column_type']                = 'longtext';
            break;
            case 'datetime':
                $data['datetime_precision']         = 0;
                $data['column_type']                = 'datetime';
            break;
            case 'int':
                $data['column_default']             = $this->createColumn->default ?? 0;
                $data['numeric_precision']          = $this->createColumn->type->length ?? 20;
                $data['numeric_scale']              = '0';
                $data['column_type']                = 'int('.$data['numeric_precision'].')'.($this->createColumn->type->unsigned?' unsigned':'');
            break;
            case 'tinyint':
                $data['column_default']             = $this->createColumn->default ?? 0;
                $data['numeric_precision']          = $this->createColumn->type->length ?? 3;
                $data['numeric_scale']              = '0';
                $data['column_type']                = 'tinyint('.($data['numeric_precision'] == 3 ?1:$data['numeric_precision']).')'.($this->createColumn->type->unsigned?' unsigned':'');
            break;
            case 'decimal':
                $data['column_default']             = $this->createColumn->default;
                $data['numeric_precision']          = $this->createColumn->type->length ?? 12;
                $data['numeric_scale']              = $this->createColumn->type->decimals ?? 2;
                $data['column_type']                = "decimal({$data['numeric_precision']},{$data['numeric_scale']})".($this->createColumn->type->unsigned?' unsigned':'');
            break;
            case 'double':
                $data['column_default']             = $this->createColumn->default;
                $data['numeric_precision']          = $this->createColumn->type->length ?? 12;
                $data['numeric_scale']              = $this->createColumn->type->decimals;
                $length                             = ($this->createColumn->type->length)?"({$this->createColumn->type->length}%)":'';
                $length                             = ($this->createColumn->type->decimals)?str_replace('%',",{$this->createColumn->type->decimals}",$length):$length;
                $data['column_type']                = 'double'.$length.($this->createColumn->type->unsigned?' unsigned':'');
            break;
            case 'date':
                $data['column_type']                = 'date';
            break;
            case 'time':
                $data['datetime_precision']         = $this->createColumn->type->length;
                $data['column_type']                = 'date'.($this->createColumn->type->length?"({$this->createColumn->type->length})":'');
            break;
            case 'timestamp':
                $data['datetime_precision']         = 0;
                $data['column_type']                = 'timestamp';
            break;
            case 'float':
                $data['column_default']             = $this->createColumn->default;
                $data['numeric_precision']          = $this->createColumn->type->length ?? 12;
                $data['numeric_scale']              = $this->createColumn->type->decimals;
                $length                             = ($this->createColumn->type->length)?"({$this->createColumn->type->length}%)":'';
                $length                             = ($this->createColumn->type->decimals)?str_replace('%',",{$this->createColumn->type->decimals}",$length):str_replace('%',"",$length);
                $data['column_type']                = 'double'.$length.($this->createColumn->type->unsigned?' unsigned':'');
            break;
            case 'text':
                $data['character_maximum_length']   = '65535';
                $data['character_octet_length']     = '65535';
                $data['character_set_name']         = $this->createColumn->type->character_set ?? $this->table->getCharset();
                $data['collation_name']             = $this->createColumn->type->collation ?? $this->table->getCollation();
                $data['column_type']                = 'text';
            break;
            case 'mediumtext':
                $data['character_maximum_length']   = '16777215';
                $data['character_octet_length']     = '16777215';
                $data['character_set_name']         = $this->createColumn->type->character_set ?? $this->table->getCharset();
                $data['collation_name']             = $this->createColumn->type->collation ?? $this->table->getCollation();
                $data['column_type']                = 'mediumtext';
            break;
            case 'smallint':
                $data['column_default']             = $this->createColumn->default ?? 0;
                $data['numeric_precision']          = $this->createColumn->type->length ?? 3;
                $data['numeric_scale']              = '0';
                $data['column_type']                = 'tinyint('.($data['numeric_precision'] == 3 ?1:$data['numeric_precision']).')'.($this->createColumn->type->unsigned?' unsigned':'');
            break;
            case 'enum':
                $maximum_lenght_value = 0;
                $values = [];
                foreach($this->createColumn->type->values as $value){
                    if(strlen($value) > $maximum_lenght_value){
                        $maximum_lenght_value = $value;
                    }
                    $values[] = "'".addslashes($value)."'";
                }

                $data['character_set_name']         = $this->createColumn->type->character_set ?? $this->table->getCharset();
                $data['collation_name']             = $this->createColumn->type->collation ?? $this->table->getCollation();
                $data['character_maximum_length']   = strlen($maximum_lenght_value);
                $data['character_octet_length']     = mb_strlen(str_pad('',$data['character_maximum_length'],'X'));
                $data['column_type']                = 'enum('.implode(',',$values).')';
            break;
            case 'varbinary':
                $data['character_maximum_length']   = $this->createColumn->type->length ?? 32;
                $data['character_octet_length']     = mb_strlen(str_pad('',$data['character_maximum_length'],'X'));
                $data['column_type']                = 'varbinary('.$data['character_maximum_length'].')';
            break;
            case 'mediumint':
                $data['column_default']             = $this->createColumn->default;
                $data['numeric_precision']          = $this->createColumn->type->length ?? 7;
                $data['numeric_scale']              = '0';
                $data['column_type']                = 'mediumint('.$data['numeric_precision'].')'.($this->createColumn->type->unsigned?' unsigned':'');
            break;
            case 'char':
                $data['character_maximum_length']   = $this->createColumn->type->length ?? 1;
                $data['character_octet_length']     = mb_strlen(str_pad('',$data['character_maximum_length'],'X'));
                $data['character_set_name']         = $this->createColumn->type->character_set ?? $this->table->getCharset();
                $data['collation_name']             = $this->createColumn->type->collation ?? $this->table->getCollation();
                $data['column_type']                = 'char('.$data['character_maximum_length'].')';
            break;
            case 'tinytext':
                $data['character_maximum_length']   = '255';
                $data['character_octet_length']     = '255';
                $data['character_set_name']         = $this->createColumn->type->character_set ?? $this->table->getCharset();
                $data['collation_name']             = $this->createColumn->type->collation ?? $this->table->getCollation();
                $data['column_type']                = 'tinytext';
            break;
            case 'blob':
                $data['character_maximum_length']   = '65535';
                $data['character_octet_length']     = '65535';
                $data['character_set_name']         = $this->createColumn->type->character_set ?? $this->table->getCharset();
                $data['collation_name']             = $this->createColumn->type->collation ?? $this->table->getCollation();
                $data['column_type']                = 'blob';
            break;
            case 'json':
                $data['column_type']                = 'json';
            break;
            case 'year':
                $data['column_default']             = $this->createColumn->default;
                $data['column_type']                = 'year(4)';
            break;
            case 'bit':
                $data['numeric_precision']         = $this->createColumn->type->length;
                $data['column_type']               = 'bit'.($this->createColumn->type->length?"({$this->createColumn->type->length})":'');
            break;
            case 'set':
                $maximum_lenght_value = 0;
                $values = [];
                foreach($this->createColumn->type->values as $value){
                    if(strlen($value) > $maximum_lenght_value){
                        $maximum_lenght_value = $value;
                    }
                    $values[] = "'".addslashes($value)."'";
                }

                $data['character_set_name']         = $this->createColumn->type->character_set ?? $this->table->getCharset();
                $data['collation_name']             = $this->createColumn->type->collation ?? $this->table->getCollation();
                $data['character_maximum_length']   = $maximum_lenght_value;
                $data['character_octet_length']     = mb_strlen(str_pad('',$data['character_maximum_length'],'X'));
                $data['column_type']                = 'enum('.implode(',',$values).')';
            break;
            case 'longblob':
                $data['character_maximum_length']   = '4294967295';
                $data['character_octet_length']     = '4294967295';
                $data['column_type']                = 'longblob';
            break;
        }

        return $data;
    }


    public function populate()
    {
        $data           = $this->getRowData();

        $sql            = 'INSERT INTO `information_schema`.`columns` SET ';
        $placeHolders   = [];
        $values         = [];
        
        foreach($data as $key=>$value){
            $placeHolders[]     = "{$key} = :{$key}";
            $values[":{$key}"]  = $value;
        }

        $query  = $sql.implode(",",$placeHolders);
        $stmt   = $this->connection->prepare($query);

        $stmt->execute($values);
    }

}