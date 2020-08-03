<?php 
namespace Fpdo\Parser;

use Fpdo\Common\Resource;
use Fpdo\Common\DataParser;

class CsvParser extends DataParser{
    protected $parameters = [];

    protected $defaultParameters = [
        'delimiter'     => ';',
        'enclosure'     => '"',
        'header_columns'=> false,
        'columns'       => []
    ];

    public function __construct($subject,array $parameters = []){
        $sourceData = Resource::acquire($subject);

        $this->parameters   = array_merge($this->defaultParameters,$parameters);
        $this->data         = $this->parseContents($sourceData);
        
        // $this->makeQueryBuilder($queryBuilderData); 
    }


    private function parseContents(string $data = ''){
        $arrayRows      = [];
        $headerColumns  = [];
        $rowCnt         = 0;
        $prevLineEnding = ini_get('auto_detect_line_endings');
        
        ini_set('auto_detect_line_endings',TRUE);
        
        $handle = tmpfile();
        fwrite($handle,$data);
        fseek($handle, 0);

        while (($dataRow = fgetcsv($handle, 1000,$this->parameters['delimiter'],$this->parameters['enclosure'])) !== FALSE) {
            if($this->parameters['header_columns'] == true && $rowCnt == 0){
                $headerColumns = $dataRow;
            }
            else{
                $currentColumns = $this->makeNumberedColumns($dataRow);
                $currentColumns = array_replace($currentColumns,$headerColumns);

                if($this->parameters['columns']){
                    foreach($this->parameters['columns'] as $key=>$columnName){
                        if(!array_key_exists($key,$currentColumns)){
                            $currentColumns[$key] = 'Column'.$key;
                        }
                        elseif( (string)$columnName != '' ){
                            $currentColumns[$key] = $columnName;
                        }
                    }
                }

                if(count($currentColumns) > count($dataRow)){
                    foreach($currentColumns as $cIndex=>$cName){
                        if(!array_key_exists($cIndex,$dataRow)){
                            $dataRow[$cIndex] = '';
                        }
                    }
                }
                elseif(count($currentColumns) < count($dataRow)){
                    foreach($dataRow as $dIndex=>$dValue){
                        if(!array_key_exists($dIndex,$currentColumns)){
                            $currentColumns[$dIndex] = 'Column'.$dIndex;
                        }
                    }
                }


                $arrayRows[] = array_combine($currentColumns,$dataRow);
            }

            $rowCnt++;
        }
        fclose($handle);
        
        ini_set('auto_detect_line_endings',$prevLineEnding);
        
        return $arrayRows;
    }


    private function makeNumberedColumns(array $data){
        $out = [];
        foreach($data as $k=>$v){
            $out[]= "Column{$k}";
        }
    
        return $out;
    }


}
