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
     * XML object
     * 
     * @access private
     * @var SimpleXMLElement
     */
    private $_xml_obj;
    
    /**
     * A list of errors
     * XML Load errors are under the key 'XML'
     * Amazon errorsare under the key 'AMAZON'
     * 
     * @access private
     * @var array
     */
    private $_errors;
    
    /**
     * The namespace of the XML (usually the request URL)
     * 
     * @access private
     * @var string
     */
    private $_namespace;
    
    /**
     * Takes the XML string and processes it
     * 
     * @access public
     * @param array $data
     */
    public function tdamazonfulfill_model( $data, $namespace )
    {
        $this->_xml_string = $data;
        $this->_namespace = $namespace;
        $this->_xml_obj = $this->_parse_xml_into_obj();
        $this->_get_amazon_errors();
    }
    
    /**
     * If there are any errors 
     * These are either Simple XML errors or Amazon errors
     * 
     * @access public
     * @return bool
     */
    public function has_errors()
    {
        return count($this->_errors);
    }
    
    /**
     * Gets a list of the errors
     * 
     * @access public
     * @return array
     */
    public function get_errors()
    {
        return $this->_errors;
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
        if ( $this->_xml_obj->xpath('//GetFulfillmentPreviewResult/FulfillmentPreviews/member[descendant::ShippingSpeedCategory="'.$shipping_speed.'"]') ) {
            $member = $this->_xml_obj->xpath('//member[descendant::ShippingSpeedCategory="'.$shipping_speed.'"]');
            $member = $member[0];
            if ( isset( $member->EstimatedFees) ) {
                $total = '0.00';
                if ( $include_fulfil ) {
                    $fulfil_fee = $member->xpath('.//EstimatedFees/member[descendant::Name="FBAPerOrderFulfillmentFee"]');
                    $total = $total + (string)$fulfil_fee[0]->Amount->Value; 
                }
                $delivery_fee = $member->xpath('.//EstimatedFees/member[descendant::Name="FBATransportationFee"]');
                $total = $total + (string)$delivery_fee[0]->Amount->Value; 
                
                return $total;
            }
        }
        return null;
    }
    
    /**
     * Gets the stock count for all items and returns array with the item ID as the key
     * 
     * @access public
     * @todo Add support for expected delivery date
     * @param array $products
     * @return array
     */
    public function get_stock_count( $products )
    {
        $data = array();
        foreach ( $products as $product ) {
            $sku = $product['sku'];
            $member = $this->_xml_obj->xpath('.//InventorySupplyList/member[descendant::SellerSKU="'.$sku.'"]');
            if ( $member && isset($member[0]->ASIN) ) {
                $stock = strip_tags($member[0]->InStockSupplyQuantity->asXML());
                $data[$sku] = $stock;
            } else {
                $data[$sku] = 'ERROR';
            }
        }
        return $data;        
    }
    
    /**
     * Gets any errors Amazon returns and adds them to our error collection
     * 
     * @access private
     */
    private function _get_amazon_errors()
    {
        if ( $this->_xml_obj ) {
            if ( isset($this->_xml_obj->Error) ) {
                foreach ( $this->_xml_obj->Error as $error ) {
                    if ( isset($error->Type, $error->Code, $error->Message) ) {
                        $this->_errors['AMAZON'][] = array(
                            'type' => strip_tags($error->Type->asXML()),
                            'code' => strip_tags($error->Code->asXML()),
                            'message' => strip_tags($error->Message->asXML())
                        );
                    }
                }
            }
        }
    }
    
    /**
     * Takes the xml string and converts it into an object
     * 
     * @access private
     * @return SimpleXMLElement
     */
    private function _parse_xml_into_obj()
    {
        if ( $this->_xml_string ) {
            libxml_use_internal_errors(true);
            $this->_xml_string = str_replace("xmlns=", "ns=", $this->_xml_string); 
            $sxe = simplexml_load_string($this->_xml_string);
            if ( !$sxe ) {
                // If we can't load the xml record it
                foreach ( libxml_get_errors() as $error ) {
                    $this->_errors['XML'][] = $error->message;
                }
                return false;
            } else {
                // Otherwise we are all good
                return $sxe;
            }
        }
    }
}
