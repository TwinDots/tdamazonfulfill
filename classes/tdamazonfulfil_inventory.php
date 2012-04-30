<?php

/**
 * Used for updating inventory information
 *
 * @author Matthew Caddoo
 */
class tdamazonfulfil_inventory
{
    /**
     * Large sync function that syncs inventory
     */
    public static function sync()
    {
        $products = Db_DbHelper::queryArray('SELECT id, sku FROM shop_products WHERE x_amazon_fulfil = 1 AND track_inventory = 1');
        if ( !empty($products) ) {
            $data = array();
            // Construct data to send to Amazon to get back some quantitys 
            $data['Action'] = 'ListInventorySupply';
            $count = 1;
            foreach ( $products as $product ) {
                $data["SellerSkus.member.$count"] = $product['sku'];
                $count++;
            }
            $shipping_method = Db_DbHelper::scalar('SELECT config_data FROM shop_shipping_options WHERE class_name = "tdamazonfulfil_amazon_shipping"');
            $shipping_params = tdamazonfulfil_params::get_params($shipping_method, array(
                        'seller_id',
                        'access_key_id',
                        'secret_access_key',
                        'end_point',
                        'packing_note'
                            )
            );

            $request = new tdamazonfulfil_request($shipping_params['seller_id'], $shipping_params['access_key_id'],
                            $shipping_params['secret_access_key'], $shipping_params['end_point'], 'inventory', $data);

            $request->request();
            if ( $request->get_content() ) {
                $model = new tdamazonfulfil_model($request->get_content(), $request->get_request_url());
                if ( $model->has_errors() ) {
                    self::log('error', 'Couldnt get inventory from Amazon');
                } else {
                    $stock_count = $model->get_stock_count($products);
                    foreach ( $stock_count as $sku => $stock ) {
                        if ( $stock == 'ERROR' ) {
                            self::log('error', 'Item with SKU:'.$sku.' Could not be synced');
                        } else {
                            Db_DbHelper::query('UPDATE shop_products SET in_stock = '.$stock.' WHERE sku = "'.$sku.'"');
                        }
                    }
                }
            } else {
                self::log('error', 'Couldnt sync inventory');
            }
        }
    }

    /**
     * Logs messages
     * 
     * @access private
     * @param string $type
     * @param string $message 
     */
    private static function log($type, $message)
    {
        traceLog("$type: $message",'amazon_fulfillment');
    }

}

