<?php
/*
 * Amazon fulfillment module
 * 
 * Shipping
 * 
 * Class adds a new shipping method called Amazon fulfillment
 *
 * @author Matthew Caddoo
 */
class tdamazonfulfill_amazon_shipping extends Shop_ShippingType
{
    /**
     * Contains all config option values
     * 
     * @access private
     * @var ActiveRecord
     */
    private $host;
    
    /**
     * Shipping module information
     * 
     * @access public
     * @return array
     */
    public function get_info()
    {
        return array(
            'name' => 'Amazon Fulfillment',
            'description' => 'Allows you to get shipping + fulfillment quotes from Amazon'
        );
    }
    
    /**
     * Create the module configuration form
     * 
     * @access public
     * @param ActiveRecord $host_obj
     * @param string $context 
     */
    public function build_config_ui( $host_obj, $context=null )
    {
        $this->host = $host_obj;
        if ( $context !== 'preview' ) {
            /**
             * Access key ID
             */
            $host_obj->add_field('access_key_id', 'Access key ID', 'left')
                    ->tab('API Credentials')->comment('Amazon Fulfillment key', 'above')
                    ->renderAs(frm_text)->validation()->required('Please specify access key ID');
            /**
             * Secret Access Key
             */
            $host_obj->add_field('secret_access_key', 'Secret access key', 'right')
                    ->tab('API Credentials')->comment('Amazon secret access key', 'above')
                    ->renderAs(frm_text)->validation()->required('Please specify a secret access key');     
            /**
             * Seller ID
             */
            $host_obj->add_field('seller_id', 'Your seller ID', 'left')
                    ->tab('API Credentials')
                    ->renderAs(frm_text)->validation()->required('Please specify a seller ID');        
            
            /**
             * Application Name
             */
            $host_obj->add_field('marketplace_id', 'Your marketplace ID', 'right')
                    ->tab('API Credentials')
                    ->renderAs(frm_text)->validation()->required('Please specify a marketplace ID');
            
            /** 
             * MWS end point URL
             */
            $host_obj->add_field('end_point', 'Which Amazon server to use', 'left')
                    ->tab('API Credentials')
                    ->renderAs(frm_dropdown)->validation()->required('Please specify a Amazon server'); 
            
        }
                
        /**
         * Order status if fulfillment successful 
         */
        $host_obj->add_field('fulfill_success_status', 'Order status is fulfillment successful', 'left')
                    ->tab('Fulfillment Options')->renderAs(frm_dropdown)->validation()->required('Please specify an order status if fulfillment is successful');
        
        /**
         * Order status if fulfillment unsuccessful 
         */
        $host_obj->add_field('fulfill_unsuccess_status', 'Order status if fulfillment is unsuccessful', 'right')
                    ->tab('Fulfillment Options')->renderAs(frm_dropdown)->validation()->required('Please specify an order status if fulfillment is unsuccesful');        
        
        /**
         * Configurable shipping speeds
         */
        $host_obj->add_field('shipping_type', 'Available shipping options', 'left')
                    ->tab('Fulfillment Options')->renderAs(frm_checkboxlist);
        
        /**
         * Should fulfillment cost be added to shipping quote?
         */
        $host_obj->add_field('add_fulfillment', 'Add fulfillment quote to shipping quote?', 'right')
                    ->tab('Fulfillment Options')->comment('Setting this to true will pass the fulfillment charge
                         from Amazon onto your customers')->renderAs(frm_checkbox);
    }
    
    /**
     * Get current state of end point
     * 
     * @access public
     * @param int $value
     * @return bool
     */
    public function get_end_point_option_state($value = 1)
    {
        return in_array($value, $this->get_end_point_options());
    }
    
    /**
     * Get possible options for successful fulfillments
     * 
     * @access public
     * @return array
     */
    public function get_end_point_options( $current_key_value = -1 )
    {
        $options = array(
            'mws.amazonservices.com' => 'US',
            'mws.amazonservices.co.uk' => 'UK',
            'mws.amazonservices.de' => 'Germany',
            'mws.amazonservices.fr' => 'France',
            'mws.amazonservices.jp' => 'Japan',
            'mws.amazonservices.com.cn' => 'China'
        );
        
        if ($current_key_value == -1)
            return $options;

        return array_key_exists($current_key_value, $options) ? $options[$current_key_value] : null;
    }    
        
    /**
     * Get current state of fulfillment success status
     * 
     * @access public
     * @param int $value
     * @return bool
     */
    public function get_fulfill_success_status_option_state($value = 1)
    {
        return in_array($value, $this->get_fulfill_success_status_options());
    }
    
    /**
     * Get possible options for successful fulfillments
     * 
     * @access public
     * @return array
     */
    public function get_fulfill_success_status_options( $current_key_value = -1 )
    {
        if ($current_key_value == -1)
            return Shop_OrderStatus::create()->order('name')->find_all()->as_array('name', 'id');

        return Shop_OrderStatus::create()->find($current_key_value)->name;
    }    
    
   /**
     * Get current state of fulfillment unsuccess status
     * 
     * @access public
     * @param int $value
     * @return bool
     */
    public function get_fulfill_unsuccess_status_option_state($value = 1)
    {
        return in_array($value, $this->get_fulfill_unsuccess_status_options());
    }
    
    /**
     * Get possible options for unsuccessful fulfillments
     * 
     * @access public
     * @return array
     */
    public function get_fulfill_unsuccess_status_options( $current_key_value = -1 )
    {
        if ($current_key_value == -1)
            return Shop_OrderStatus::create()->order('name')->find_all()->as_array('name', 'id');

        return Shop_OrderStatus::create()->find($current_key_value)->name;
    } 
    
    /**
     * Get current state of shipping speed
     * 
     * @access public
     * @param int $value
     * @return bool
     */
    public function get_shipping_type_option_state($value = 1)
    {
        return is_array($this->host->shipping_type) && in_array($value, $this->host->shipping_type);
    }
    
    /**
     * Get possible options for shipping speeds
     * 
     * @access public
     * @return array
     */
    public function get_shipping_type_options( $current_key_value = -1 )
    {
        $options = array(
            'STANDARD' => 'Standard',
            'EXPEDITED' => 'Expedited',
            'PRIORITY' => 'Priority'
        );
        
        if ($current_key_value == -1)
            return $options;

        return array_key_exists($current_key_value, $options) ? $options[$current_key_value] : null;
    }
    
    /**
     * Any additional validation rules here
     * 
     * @access public
     * @param ActiveRecord $host_obj 
     */
    public function validate_config_on_save($host_obj)
    {
        
    } 
    
    /**
     * Get a shipping quote from Amazon
     * 
     * @access public
     * @param array $parameters
     * @return 
     */
    public function get_quote( $parameters )
    {
        extract( $parameters );
        return 1;
    }      
}

?>
