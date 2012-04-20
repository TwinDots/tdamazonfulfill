<?php
/**
 * Used for constructing query string for Amazon and getting and processing results
 *
 * @author Matthew Caddoo
 */
class tdamazonfulfill_request
{
    /**
     * The name of the application (this module)
     */
    const APPLICATION_NAME = 'tdamazonfulfill';
    
    /**
     * The module version
     * 
     * @access private
     * @var string
     */
    private $application_version;
    
    /**
     * Seller ID
     * 
     * @access private
     * @var string
     */
    private $seller_id;
    
    /**
     * AWS/MWS Access Key
     * 
     * @access private
     * @var string
     */
    private $access_key;
    
    /**
     * AWS/MWS Secret Key
     * 
     * @access private
     * @var string
     */
    private $secret_key;
    
    /**
     * The URL we are requesting
     *  
     * @access private
     * @var string
     */
    private $request_url;
    
    public function tdamazonfulfill_request( $service = 'fulfill', $data )
    {
        switch ( $service )
        {
            case 'inventory':
                $this->request_url = '';
            break;
            default:
                $this->request_url = '';
        }
    }
    
    private function ss()
    {
        
    }
    
    private function generate_request()
    {
        
    }
}