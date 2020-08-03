<?php 
namespace Fpdo\Parser;

use Fpdo\Common\Resource;
use Fpdo\Common\DataParser;


class XmlParser extends DataParser{
    public function __construct($subject){
        $dataSource = Resource::acquire($subject);
        if(is_file($dataSource)){
            $xmlData = simplexml_load_file($dataSource);
        }
        else{
            $xmlData = simplexml_load_string($dataSource);
        }

        $data = json_decode(json_encode($xmlData),true);

        $this->data = array_shift($data);
    }
}


?>