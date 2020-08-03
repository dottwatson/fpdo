<?php 
namespace Fpdo;

use Fpdo\Common\Resource;
use Fpdo\Common\Table;
use Fpdo\Common\QueryBuilder;

use Fpdo\Parser\JsonParser;
use Fpdo\Parser\CsvParser;
use Fpdo\Parser\XmlParser;
use Fpdo\Parser\ArrayParser;


class Fpdo{
    const FPDO_CSV_FORMAT       = 'csv';
    const FPDO_JSON_FORMAT      = 'json';
    const FPDO_ARRAY_FORMAT     = 'array';
    const FPDO_XML_FORMAT       = 'xml';

    /**
     * Shortcut for table in json format
     *
     * @param string $tableName
     * @param mixed $data
     * @param array $options
     * @return Table
     */
    public static function jsonTable(string $tableName,$data = [],array $options = []){
        return self::table($tableName,$data,self::FPDO_JSON_FORMAT,$options);
    }

    /**
     * Shortcut for table in json format
     *
     * @param string $tableName
     * @param mixed $data
     * @param array $options
     * @return Table
     */
    public static function csvTable(string $tableName,$data = [],array $options = []){
        return self::table($tableName,$data,self::FPDO_CSV_FORMAT,$options);
    }

    /**
     * Shortcut for table in xml format
     *
     * @param string $tableName
     * @param mixed $data
     * @param array $options
     * @return Table
     */
    public static function xmlTable(string $tableName,$data = [],array $options = []){
        return self::table($tableName,$data,self::FPDO_XML_FORMAT,$options);
    }

    /**
     * Shortcut for table in array format
     *
     * @param string $tableName
     * @param mixed $data
     * @param array $options
     * @return Table
     */
    public static function arrayTable(string $tableName,$data = [],array $options = []){
        return self::table($tableName,$data,self::FPDO_ARRAY_FORMAT,$options);
    }

    /**
     * Create a table from a source
     *
     * @param string $tableName
     * @param mixed $data
     * @param string $type the type of source
     * @param array $options
     * @return Table
     */
    public static function table(string $tableName,$data = [],string $type=null,array $options = []){
        switch($type){
            case self::FPDO_JSON_FORMAT:
                $tableData = new JsonParser($data,$options);
            break;
            case self::FPDO_CSV_FORMAT:
                $tableData = new CsvParser($data,$options);
            break;
            case self::FPDO_XML_FORMAT:
                $tableData = new XmlParser($data,$options);
            break;
            case self::FPDO_ARRAY_FORMAT:
            default:
                $tableData = new ArrayParser($data,$options);
            break;
        }
        
        $table = new Table($tableName,$tableData,$type,$options);
        return $table;
    }

    /**
     * start a query from registered table/source
     *
     * @param mixed $subject
     * @return QueryBuilder
     */
    public static function from($subject){
        if(is_object($subject) && is_a($subject,Table::class)){
            $tableData = $subject->getData();
        }
        else{
            $tableData = Resource::acquire($subject);
        }
        
        $builder = new QueryBuilder($tableData);

        return $builder;
    }
}

?>