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
     * Amazon MWS version
     */
    const MWS_VERSION = '2010-10-01';
    
    /**
     * Signature method
     */
    const SIGNATURE_METHOD = 'HmacSHA256';
    
    /**
     * Current Amazon MWS signature version
     */
    const SIGNATURE_VERSION = '2';
    
    /**
     * The module version
     * 
     * @access private
     * @var string
     */
    private $_application_version;
    
    /**
     * Seller ID
     * 
     * @access private
     * @var string
     */
    private $_seller_id;
    
    /**
     * AWS/MWS Access Key
     * 
     * @access private
     * @var string
     */
    private $_access_key;
    
    /**
     * AWS/MWS Secret Key
     * 
     * @access private
     * @var string
     */
    private $_secret_key;
    
    /**
     * Data to be sent in URL
     * 
     * @access private
     * @var array
     */
    private $_data;
    
    /**
     * The Amazon server to send the request to
     * 
     * @access private
     * @var string
     */
    private $_end_point;
    
    /**
     * The service url portion of the request
     * 
     * @access private
     * @var string
     */
    private $_service_url;
        
    /**
     * The URL we are requesting
     *  
     * @access private
     * @var string
     */
    private $_request_url;
    
    /**
     * Data returned from HTTP request
     * 
     * @access private
     * @var string
     */
    private $_content;
    
    /**
     * Information returned from CURL
     * 
     * @access private
     * @var array
     */
    private $_response;

    /**
     * Assigns all properties required to make the request to Amazon using shipping options
     * Constructs the request URL
     * 
     * @access private
     * @param Db_ActiveRecord $host_obj
     * @param string $service
     * @param array $data 
     */
    public function tdamazonfulfill_request( Db_ActiveRecord $host_obj, $service = 'fulfill', $data = array() )
    {
        $this->_application_version = '';
        $this->_seller_id = $host_obj->seller_id;
        $this->_access_key = $host_obj->access_key_id;
        $this->_secret_key = $host_obj->secret_access_key;
        $this->_end_point = $host_obj->end_point;
        
        $this->_data = $data;
        
        switch ( $service )
        {
            case 'inventory':
                $this->_service_url = '/FulfillmentInventory/'.self::MWS_VERSION;
                $this->_request_url = "https://{$this->_end_point}{$this->_service_url}";
                $this->_generate_url();
            break;
            default:
                $this->_service_url = '/FulfillmentOutboundShipment/'.self::MWS_VERSION;
        }
        $this->_request_url = "https://{$this->_end_point}{$this->_service_url}";
        $this->_generate_url();
     }
    
    /**
     * Performs the CURL request and stores the response and the status code
     * 
     * @access public
     */
    public function request()
    {
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $this->_request_url );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $ch, CURLOPT_POST, 1 );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query($this->_data) );
        curl_setopt( $ch, CURLOPT_USERAGENT, $this->_user_agent() );
        $this->_content = curl_exec($ch);
        $this->_response = curl_getinfo($ch);
        
        curl_close($ch);
    }     
   
    /**
     * If the status code is 200 it returns the content of the response
     * 
     * @access public
     * @return string
     */
    public function get_content()
    {
        if ( $this->_response['http_code'] == '200' ) {
            return $this->_content;
        }
        return false;
    }   
    
    /**
     * Generates the URL that we will be requesting
     * This involves:
     *  - Adding the API credentials
     *  - Generating a signature
     * 
     * @access private
     */
    private function _generate_url() 
    {
        $this->data['AWSAccessKeyId'] = $this->_access_key;
        $this->data['SellerId'] = $this->_seller_id;
        $this->data['SignatureVersion'] = self::SIGNATURE_VERSION;
        $this->data['SignatureMethod'] = self::SIGNATURE_METHOD;
        $this->data['Version'] = self::MWS_VERSION;       
        $this->data['ReponseGroup'] = 'Basic';
        $this->data['Timestamp'] = gmdate("Y-m-d\TH:i:s.\\0\\0\\0\\Z", time());
        $query = http_build_query($this->_data);
        $http_request = 'POST';
        $http_request .= "\n";
        $http_request .= $this->_end_point;
        $http_request .= "\n";
        $http_request .= $this->_service_url;
        uksort( $this->_data, 'strcmp' ) ;
        $http_request .= "\n";
        $http_request .= $this->_get_parameters_as_string($this->_data);
        /**
         * Generate sha256 HAMAC
         */
         $this->_data['Signature'] = base64_encode(hash_hmac( 'sha256', $http_request, $this->_secret_key, true ));
    }
       
    /**
     * Generates a user agent string compliant with Amazons API Spec
     * 
     * @access private
     * @return string
     */
    private function _user_agent()
    {
        $ua = 'x-amazon-user-agent: ';
        $ua = $ua.self::APPLICATION_NAME;
        $ua = $ua.'/'.$this->_application_version;
        $ua = $ua.' (Language=PHP)';
        return $ua;
    }
    
    /**
     * Converts the array of key => values into a valid URL request
     * Taken from Amazon's PHP code
     * 
     * @access private
     * @param array $parameters
     * @return string
     */
    private function _get_parameters_as_string(array $parameters)
    {
        $query_parameters = array();
        foreach ( $parameters as $key => $value ) {
            if (!is_null($key) && $key !=='' && !is_null($value) && $value!=='')
            {
                $query_parameters[] = $key . '=' . $this->_urlencode($value);
            }
        }
        return implode('&', $query_parameters);
    }
    
    /**
     * Encodes the URL so its compliant with what the Amazon API requires
     * Take from Amazon's PHP code
     * 
     * @access private
     * @param string $value
     * @return string
     */
    private function _urlencode($value) {
        return str_replace('%7E', '~', rawurlencode($value));
    }

}