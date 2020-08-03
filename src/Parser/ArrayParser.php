<?php 
namespace Fpdo\Parser;

use Fpdo\Common\Resource;
use Fpdo\Common\DataParser;

class ArrayParser extends DataParser{

    public function __construct($subject){
        $sourceData = Resource::acquire($subject);

        $this->data = $sourceData;
    }

}

?>