<?php

/*
 * Amazon fulfillment module
 *
 * @author Matthew Caddoo
 */

class tdamazonfulfill_module extends Core_ModuleBase
{

    /**
     * Module information
     * 
     * @access protected
     * @return Core_ModuleInfo 
     */
    protected function createModuleInfo()
    {
        return new Core_ModuleInfo(
                        'Amazon Fulfillment',
                        'Adds Amazon Fulfillment shipping option to your store and allows
                 you to select products to be fulfilled by Amazon',
                        'Twindots',
                        'http://www.twindots.co.uk'
        );
    }

    /**
     * Extend the product form and model to allow us to flag products to be fulfilled by amazon
     * 
     * @access public
     */
    public function subscribeEvents()
    {
        /**
         * Add fulfillment option for products
         */
        Backend::$events->addEvent('shop:onExtendProductForm', $this, 'extend_product_form');
        Backend::$events->addEvent('shop:onExtendProductModel', $this, 'extend_product_model');

        /**
         * Listen for order status changes so we can send the fulfillment reques to Amazon once the order is paid
         */
        Backend::$events->addEvent('shop:onOrderBeforeStatusChanged', $this, 'fulfil_order');

        /**
         * Add the extra log file to track errors
         */
        Backend::$events->addEvent('core:onInitialize', $this, 'initialize');
    }

    /**
     * Adds fulfillment log
     */
    public function initialize()
    {
        Phpr::$traceLog->addListener('amazon_fulfillment', PATH_APP . '/logs/amazon_fulfillment.txt');
    }

    /**
     * Extend the form
     * 
     * @access public 
     */
    public function extend_product_form($form)
    {
        $form->add_form_field('x_amazon_fulfil')->tab('Amazon Fulfillment')
                ->comment('Enable Amazon Fulfillment on this product, the SKU has to match Amazons in order for this to work')
                ->renderAs(frm_onoffswitcher);
    }

    /**
     * Extend the model
     * 
     * @access public
     */
    public function extend_product_model($model)
    {
        $model->define_column('x_amazon_fulfil', 'Amazon Fulfillment');
    }

    /**
     * Adds an access point for us to run cron inventory updates
     * 
     * @access public
     * @return array
     */
    public function register_access_points()
    {
        //return array('tdamazon_fulfill_update_inv','update_inventory');
    }

    public function update_inventory()
    {
        
    }

    /**
     * Sends request to Amazon
     * 
     * @access public
     * @param Shop_Order $order
     * @param int $new_status_id
     * @param int $prev_status_id
     * @param array $comments
     * @param array $send_notifications
     * @return bool
     */
    public function fulfil_order($order, $new_status_id, $prev_status_id, $comments, $send_notifications)
    {
        traceLog('Attempting to process order: '.$order->id, 'amazon_fulfillment');

        /**
         * If the order is being changed to paid
         */
        if ( $new_status_id == 2  ) {
            if ( $order ) {
                $shipping_option = $order->shipping_method;

                $shipping_params = tdamazonfulfill_params::get_params($shipping_option->config_data, array(
                            'fulfill_success_status',
                            'fulfill_unsuccess_status'
                                )
                );
                if ( $shipping_type = $order->shipping_method->get_shippingtype_object() ) {
                    if ( get_class($shipping_type) == 'tdamazonfulfill_amazon_shipping' ) { // if shipping method is ours
                        $fulfil = new tdamazonfulfill_fulfil($order);
                        if ( $fulfil->has_error() ) {
                            $new_status = $shipping_params['fulfill_unsuccess_status'];
                            $order->status = $new_status;
                            $order->save();
                            Shop_OrderStatusLog::create_record($new_status, $order, $comments, $send_notifications);
                            Phpr::$session->flash['error'] = 'Unable to send order to Amazon, please contact us for assitance';
                        } else {
                            $new_status = $shipping_params['fulfill_success_status'];
                            $order->status = $new_status;
                            $order->save();
                            Shop_OrderStatusLog::create_record($new_status, $order, $comments, $send_notifications);
                        }
                        return false;
                    }
                }
            }
        }
    }

}
