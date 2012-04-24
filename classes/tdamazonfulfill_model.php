<?php
/**
 * Used for processing the XML returned from Amazon
 * It's a pretty limited version of what Amazon offer it just suits the requirements of this module
 *  - Get shipping quote
 *  - Get stock count
 *  - Fulfillment request result
 * 
 * @author Matthew Caddoo
 */
class tdamazonfulfill_model
{
    /**
     * XML string returned from Amazon
     * 
     * @access private
     * @var string
     */
    private $_xml_string;
    
    /**
     * XML Array
     * 
     * @access private
     * @var array
     */
    private $_xml_array;
    
    /**
     * Takes the XML string and processes it
     * 
     * @access public
     * @param array $data
     */
    public function tdamazonfulfill_model( $data )
    {
        
    }
    
    /**
     * If there are any errors 
     * These are either Simple XML errors or Amazon errors
     * 
     * @access public
     * @return bool
     */
    public function has_error()
    {
    
        return false;
    }
    
    /**
     * Gets a list of the errors
     * 
     * @access public
     * @return array
     */
    public function get_errors()
    {
        return array();
    }
    
    /**
     * Retrieves the shipping quote if applicable
     * 
     * @access public
     * @param string $shipping_speed
     * @param bool $include_fulfil
     * @return string
     */
    public function get_shipping_quote( $shipping_speed = 'Expedited', $include_fulfil = false )
    {
        
        return 0;
    }
    
    /**
     * Gets the stock count for all items and returns array with the item ID as the key
     * 
     * @access public
     * @return array
     */
    public function get_stock_count()
    {
        return array();        
    }
    
    /**
     * Takes the xml string and converts it into an array
     * 
     * @access private
     * @return array
     */
    private function _parse_xml_into_array()
    {
        return array();
    }
    
    
    
}
