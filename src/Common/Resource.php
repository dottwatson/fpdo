<?php 
namespace Fpdo\Common;

use Fpdo\Exception\ResourceException;
use LaLit\XML2Array;


/**
 * Class to handle files, urls, objects or resources
 */
class Resource{
    
    /**
     * Check if local file and available
     * An url and available
     * An object and object type
     * A Resource
     *
     * @return void
     */
    public static function getType($subject){
        if(is_resource($subject) && get_resource_type($subject) == 'stream'){
            return ['type'=>'stream','contents'=>stream_get_contents($subject)];
        }
        elseif(is_object($subject) && is_a($subject,'SimpleXMLElement',false)){
            return ['type'=>'xml','contents'=>$subject->asXML()];
        }
        elseif(is_object($subject)){
            return ['type'=>'object','contents'=>json_encode($subject)];
        }
        elseif(is_scalar($subject)){
            if(is_file($subject) && is_readable($subject)){
                return ['type'=>'file','contents'=>file_get_contents($subject)];
            }
            if(filter_var($subject,FILTER_VALIDATE_URL)){
                $urlInfo = parse_url($subject,PHP_URL_SCHEME);
                switch(strtolower($urlInfo)){
                    case 'http':
                    case 'https':
                    case 'ftp':
                        return ['type'=>'url','contents'=>file_get_contents($subject)];
                    break;
                    default:
                        return ['type'=>null,'contents'=>$subject];
                    break;
                }            
            }
            
            return ['type'=>'string','contents'=>(string)$subject];
        }
        elseif(is_array($subject)){
            return ['type'=>'array','contents'=>$subject];
        }
    } 


    /**
     * Acquire the primitive resource and covert it into parsable data
     *
     * @param mixed $subject
     * @return mixed
     */
    public static function acquire($subject){
        $info = self::getType($subject);
        
        if(!$info['type']){
            throw new ResourceException("invalid resource type for {$subject}");
        }

        if($info['type'] == 'xml'){
            return XML2Array::createArray($info['contents']);
        }
        
        
        return $info['contents'];
    }



}

?>