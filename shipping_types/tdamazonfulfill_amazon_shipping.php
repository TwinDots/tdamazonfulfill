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
    public function build_config_ui($host_obj, $context=null)
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
        $host_obj->add_field('allowed_methods', 'Available shipping options', 'left')
                ->tab('Fulfillment Options')->renderAs(frm_checkboxlist);

        /**
         * Should fulfillment cost be added to shipping quote?
         */
        $host_obj->add_field('add_fulfillment', 'Add fulfillment quote to shipping quote?', 'right')
                ->tab('Fulfillment Options')->comment('Setting this to true will pass the fulfillment charge
                         from Amazon onto your customers')->renderAs(frm_onoffswitcher);
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
    public function get_end_point_options($current_key_value = -1)
    {
        $options = array(
            'mws.amazonservices.ca' => 'Canada',
            'mws.amazonservices.co.cn' => 'China',
            'mws-eu.amazonservices.com' => 'Germany',
            'mws-eu.amazonservices.com' => 'Spain',
            'mws-eu.amazonservices.com' => 'France',
            'mws-eu.amazonservices.com' => 'Italy',
            'mws.amazonservices.jp' => 'Japan',
            'mws-eu.amazonsevices.com' => 'UK',
            'mws.amazonservices.com' => 'US'
        );

        if ( $current_key_value == -1 )
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
    public function get_fulfill_success_status_options($current_key_value = -1)
    {
        if ( $current_key_value == -1 )
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
    public function get_fulfill_unsuccess_status_options($current_key_value = -1)
    {
        if ( $current_key_value == -1 )
            return Shop_OrderStatus::create()->order('name')->find_all()->as_array('name', 'id');

        return Shop_OrderStatus::create()->find($current_key_value)->name;
    }

     /**
     * Gets a list of available shipping types
     * 
     * @access protected
     * @return array
     */
    protected function get_service_list()
    {
        $services = array(
            'USA' => array(
                '01' => 'Standard',
                '02' => 'Expedited',
                '03' => 'Priority'
            ),
            'DEFAULT' => array(
                '01' => 'Standard',
                '02' => 'Expedited',
                '03' => 'Priority'
            )
        );

        $shipping_params = Shop_ShippingParams::get();
        $country_code = $shipping_params->country ? $shipping_params->country->code_3 : 'DEFAULT';
        if ( !array_key_exists($country_code, $services) )
            $country_code = 'DEFAULT';

        return $services[$country_code];
    }
    
    /**
     * Get current state of shipping speed
     * 
     * @access public
     * @param int $value
     * @return bool
     */
    public function get_allowed_methods_option_state($value = 1)
    {
        return is_array($this->host->allowed_methods) && in_array($value, $this->host->allowed_methods);
    }

    /** 
     * Get possible options for shipping speeds
     * 
     * @access public
     * @return array
     */
    public function get_allowed_methods_options($current_key_value = -1)
    {
        $options = $this->get_service_list();

        if ( $current_key_value == -1 )
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
        /**
         * We are going to make sure we can connect to Amazon here, it will be hell
         * if we assume everything is ok
         */
    }

    public function list_enabled_options($host_obj)
    {
        $result = array();

        $options = $this->get_shipping_type_options();
        foreach ( $options as $option_id => $option_name ) {
            $result[] = array('id' => $option_id, 'name' => $option_name);
        }

        return $result;
    }

    /**
     * Get a shipping quote from Amazon
     * 
     * @access public
     * @param array $parameters
     * @return 
     */
    public function get_quote($parameters)
    {
        $shipping_info = Shop_CheckoutData::get_shipping_info();
        
        $allowed_methods = $parameters['host_obj']->allowed_methods;
        $all_methods = $this->get_service_list();
        
        /**
         * Prepare data to send to Amazon
         */
        $data = array(
            'Action' => 'GetFulfillmentPreview',
            'Address.Name' => 'n/a', // Amazone require this but we don't have this / I don't know what it is
            'Address.Line1' => $shipping_info->street_address,
            'Address.City' => $parameters['city'],
            'Address.StateOrProvinceCode' => Shop_CountryState::find_by_id($parameters['state_id'])->code,
            'Address.PostalCode' => $parameters['zip'],
            'Address.CountryCode' => Shop_Country::find_by_id($parameters['country_id'])->code
        );
        $count = 1;
        /**
         * We take the all or nothing approach here, we only want Amazon as an option if all products are eligible for fulfillment
         */
        foreach ( $parameters['cart_items'] as $item ) {
            if ( $item->product->x_amazon_fulfil ) {
                $data["Items.member.$count.Quantity"] = $item->quantity;
                $data["Items.member.$count.SellerFulfillmentOrderItemId"] = $count;
                $data["Items.member.$count.SellerSKU"] = $item->product->sku;
                $count++;
            } else {
                return null;
            }
        }

        /**
         * Create a new request to amazon
         */
        $request = new tdamazonfulfill_request($parameters['host_obj'], 'fulfill', $data);
        $request->request();

        /**
         * If we actually get anything back from Amazon
         */
        if ( $content = $request->get_content() ) {
            /**
             * Load the XML so we can look at it
             */
            $model = new tdamazonfulfill_model($content, $request->get_request_url());

            if ( $model->has_errors() ) {
                traceLog(print_r($model->get_errors(), true), 'amazon_fulfillment');
                return null;
            } else {
                /**
                 * Get quote
                 */
                $result = array();
                print_r($allowed_methods);
                foreach ( $allowed_methods as $id ) {
                    $result[$all_methods[$id]] = array(
                        'id' => $id,
                        'quote' => $model->get_shipping_quote($all_methods[$id], $parameters['host_obj']->add_fulfillment)
                    );
                }
                if ( !empty($result) )
                    return $result;
                else   
                    return null;
            }
        }
        return null;
    }

}