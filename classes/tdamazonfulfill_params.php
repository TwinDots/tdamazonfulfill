<?php
/**
 * Used for getting parameters from LS modules
 * 
 * Not sure if this is even need but had terrible trouble finding the right way to get this data
 *
 * @author Matthew Caddoo
 */
class tdamazonfulfill_params
{
    /**
     * Opens XML loops through data and retrieves data
     * Accepts array of keys or just single ones
     *  
     * @access public
     * @param string $xml
     * @param string/array $params
     * @return string/array
     */
    public static function get_params( $xml, $params )
    {
        $xml_obj = new SimpleXMLElement($xml);
        $output = array();
        
        if ( !is_array($params) ) {
            $params = array($params);
        }

        if ( $xml_obj ) {
            foreach ( $params as $param ) {
                // Search for field node by child node id
                $field = $xml_obj->xpath('//field[descendant::id="'.$param.'"]');
                if ( is_object($field[0]) ) {
                    $value = strip_tags($field[0]->value->asXML());
                    $base64 = base64_decode($value);
                    $unserialized = unserialize($base64);
                    $output[$param] = $unserialized;
                }
            }
        }
        
        if ( count($output) == 1 ) {
            return reset($output);
        } else {
            return $output;
        }
    }
}

