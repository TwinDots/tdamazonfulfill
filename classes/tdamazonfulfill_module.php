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
        Backend::$events->addEvent('shop:onExtendProductForm', $this, 'extend_product_form');
        Backend::$events->addEvent('shop:onExtendProductModel', $this, 'extend_product_model');
    }
    
    /**
     * Extend the form
     * 
     * @access public 
     */
    public function extend_product_form( $form )
    {
        $form->add_form_field('x_amazon_fulfill')->tab('Amazon Fulfillment')
                ->comment('Enable Amazon Fulfillment on this product, the SKU has to match Amazons in order for this to work')
                ->renderAs(frm_onoffswitcher);
    }
    
    /**
     * Extend the model
     * 
     * @access public
     */
    public function extend_product_model( $model )
    {
        $model->define_column('x_amazon_fulfill', 'Amazon Fulfillment');
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
}

?>
