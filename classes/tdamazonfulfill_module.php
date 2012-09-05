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
        Backend::$events->addEvent('shop:onOrderBeforeStatusChanged', $this, 'fulfill_order');

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
        $form->add_form_field('x_amazon_fulfill')->tab('Amazon Fulfillment')
                ->comment('Enable Amazon Fulfillment on this product, the SKU has to match Amazons in order for this to work')
                ->renderAs(frm_onoffswitcher);

        $form->add_form_field('x_amazon_sku')->tab('Amazon Fulfillment')
                ->comment('Enter an amazon SKU number for this product if it differs from the Lemonstand SKU')
                ->renderAs(frm_text);

        $form->add_form_field('x_amazon_fulfill_last_sync')->tab('Amazon Fulfillment')
                ->renderAs(frm_text)->disabled();
    }

    /**
     * Extend the model
     * 
     * @access public
     */
    public function extend_product_model($model)
    {
        $model->define_column('x_amazon_fulfill', 'Amazon Fulfillment');
        $model->define_column('x_amazon_sku', 'Amazon SKU');
        $model->define_column('x_amazon_fulfill_last_sync', 'Time of last sync with Amazon');
    }

    /**
     * Adds an access point for us to run cron inventory updates
     * 
     * @access public
     * @return array
     */
    public function register_access_points()
    {
        return array('inventory'=>'update_amazon_inventory');
    }

    public function update_amazon_inventory()
    {
        tdamazonfulfill_inventory::sync();
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
    public function fulfill_order($order, $new_status_id, $prev_status_id, $comments, $send_notifications)
    {
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
                        $fulfill = new tdamazonfulfill_fulfill($order);
                        if ( $fulfill->has_error() ) {
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
