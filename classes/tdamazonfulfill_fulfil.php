<?php
/**
 * Takes an order from Lemon Stand and sends it to Amazon to be fulfilled
 * 
 * @author Matthew Caddoo
 */
class tdamazonfulfill_fulfil
{
    /**
     * Fulfillment Policy
     */
    const FULFILLMENT_POLICY = 'FillOrKill';
    
    /**
     * If there is an error
     * 
     * @access public
     * @var bool
     */
    public $error = true;
    
    /**
     * Lemon Stand order
     * 
     * @access private
     * @var Shop_Order
     */
    private $_order;
    
    /**
     * Assign variables
     * 
     * @access public
     * @param Shop_Order $order 
     */
    public function tdamazonfulfill_fulfil( Shop_Order $order )
    {
        $this->_order = $order;
        $data = $this->_construct_data();

        // Not really sure about this part...
        $shipping_option = $this->_order->shipping_method;      
        
        $shipping_params =  tdamazonfulfill_params::get_params($shipping_option->config_data, array(
                'seller_id',
                'access_key_id',
                'secret_access_key',
                'end_point',
                'packing_note'
            )
        );                
        
        $data['DisplayableOrderComment'] = $shipping_params['packing_note'];

        $request = new tdamazonfulfill_request( $shipping_params['seller_id'], $shipping_params['access_key_id'],
                $shipping_params['secret_access_key'], $shipping_params['end_point'], 'fulfil', $data );
        
        $request->request();
        
        if ( $request->get_content() ) {
            $model = new tdamazonfulfill_model($request->get_content(), $request->get_request_url());
            if ( $model->has_errors() ) {
                traceLog(print_r($model->get_errors(), true), 'amazon_fulfillment');
                $this->error = true;
                return null;
            } else {
                $this->error = false;
            }
        }
    }
    
    /**
     * If there is an error
     *  
     * @access public
     * @return bool
     */
    public function has_error()
    {
        return $this->error;
    }    
    
    /**
     * Constructs the array of options ready to be sent to Amazon
     * 
     * @access private
     * @return array
     */
    private function _construct_data()
    {
        /**
         * Create the data array ready to send to Amazon
         */
        $data = array(
            'Action' => 'CreateFulfillmentOrder',
            'ShippingSpeedCategory' => $this->_order->shipping_sub_option,
            'SellerFulfillmentOrderId' => $this->_order->id,
            'ShippingSpeedCategory' => $this->_order->shipping_sub_option,
            'DisplayableOrderId' => $this->_order->id,
            'DisplayableOrderDateTime' => gmdate("Y-m-d\TH:i:s.\\0\\0\\0\\Z", intval($this->_order->order_datetime->format('%s'))),
            'DestinationAddress.Name' => 'N/A',
            'DestinationAddress.Line1' => $this->_order->shipping_street_addr,
            'DestinationAddress.City' => $this->_order->shipping_city,
            'DestinationAddress.StateOrProvinceCode' => $this->_order->shipping_state->code,
            'DestinationAddress.PostalCode' => $this->_order->shipping_zip,
            'DestinationAddress.CountryCode' => $this->_order->shipping_country->code,
            'DestinationAddress.PhoneNumber' => $this->_order->shipping_phone,
            'FulfillmentPothelicy' => self::FULFILLMENT_POLICY       
        );
        
        /**
         * Add order items to array
         */
        $items = $this->_order->items;
        
        $count = 1;
        foreach ( $items as $id => $item ) {
            $data["Items.member.$count.DisplayableComment"] = $item->product_name;
            $data["Items.member.$count.PerUnitDeclaredValue.Value"] = $item->single_price - $item->discount;
            $data["Items.member.$count.PerUnitDeclaredValue.CurrencyCode"] = Shop_CurrencySettings::get()->code;
            $data["Items.member.$count.Quantity"] = $item->quantity;
            $data["Items.member.$count.SellerFulfillmentOrderItemId"] = $id;
            $data["Items.member.$count.SellerSKU"] = $item->product_sku;
            $count++;
        }   
        return $data;
    }
}