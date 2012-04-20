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
}

?>
