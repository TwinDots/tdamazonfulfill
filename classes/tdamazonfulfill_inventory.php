<?php

/**
 * Used for updating inventory information
 *
 * @author Matthew Caddoo
 */
class tdamazonfulfill_inventory
{
    /**
     * Large sync function that syncs inventory
     */
    public static function sync()
    {
        self::log('Sync:','Sync started');
        $sync_count = 0;
        $fail_count = 0;
        $products = Db_DbHelper::queryArray('SELECT id, sku, x_amazon_sku FROM shop_products WHERE x_amazon_fulfill = 1 AND track_inventory = 1');
        if ( !empty($products) ) {
            $data = array();
            // Construct data to send to Amazon to get back some quantitys 
            $data['Action'] = 'ListInventorySupply';
            $count = 1;
            foreach ( $products as $product ) {
                if ( !empty($product['x_amazon_sku']) ) {
                    $data["SellerSkus.member.$count"] = $product['x_amazon_sku'];
                } else {
                    $data["SellerSkus.member.$count"] = $product['sku'];
                }
                $count++;
            }
            $shipping_method = Db_DbHelper::scalar('SELECT config_data FROM shop_shipping_options WHERE class_name = "tdamazonfulfill_amazon_shipping"');
            $shipping_params = tdamazonfulfill_params::get_params($shipping_method, array(
                        'seller_id',
                        'access_key_id',
                        'secret_access_key',
                        'end_point',
                        'packing_note'
                            )
            );

            $request = new tdamazonfulfill_request($shipping_params['seller_id'], $shipping_params['access_key_id'],
                $shipping_params['secret_access_key'], $shipping_params['end_point'], 'inventory', $data);

            $request->request();

            if ( $request->get_content() ) {
                $model = new tdamazonfulfill_model($request->get_content(), $request->get_request_url());
                if ( $model->has_errors() ) {
                    self::log('error', 'Couldnt get inventory from Amazon');
                } else {
                    $stock_count = $model->get_stock_count($products);
                    foreach ( $stock_count as $sku => $stock ) {
                        if ( $stock == 'ERROR' ) {
                            $fail_count++;
                            self::log('error', 'Item with SKU:'.$sku.' Could not be synced');
                        } else {
                            $sync_count++;

                            Db_DbHelper::query('UPDATE shop_products SET in_stock = '.$stock.' WHERE sku = "'.$sku.'" OR x_amazon_sku = "'.$sku.'"');
                        }
                    }
                }
            } else {
                self::log('error', 'Couldnt sync inventory');
            }
        }
        self::log('Sync:',"Sync ended, products synced: $sync_count, products failed: $fail_count");
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

