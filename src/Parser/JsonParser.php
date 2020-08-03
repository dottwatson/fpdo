<?php 
namespace Fpdo\Parser;

use Fpdo\Common\Resource;
use Fpdo\Common\DataParser;
use Fpdo\Exception\DataParserException;

class JsonParser extends DataParser{

    public function __construct($subject){
        $dataSource = Resource::acquire($subject);
        $data       = json_decode($dataSource,true);

        if(json_last_error() !== JSON_ERROR_NONE){
            throw new DataParserException(json_last_error_msg());
        }

        $this->data = $data;
    }

}

?>