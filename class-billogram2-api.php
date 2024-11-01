<?php
//error_reporting(1);
/* Include the Billogram API library */
use Billogram\Api as BillogramAPI;
use Billogram\Api\Exceptions\ObjectNotFoundError;
function autoload($className)
{
	$className = ltrim($className, '\\');
    $fileName  = '';
    $namespace = '';
    if ($lastNsPos = strrpos($className, '\\')) {
        $namespace = substr($className, 0, $lastNsPos);
        $className = substr($className, $lastNsPos + 1);
        $fileName  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) .
            DIRECTORY_SEPARATOR;
    }
    $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';    
	$fileName = dirname(__FILE__).'/'.$fileName;
	
	if(file_exists($fileName))
    	include $fileName;
}
spl_autoload_register('autoload');
class WCB_API{

    /** @public String base URL */
    public $api_url;
    
    /** @public String base URL */
    public $billogram_mode;

    /** @public Client Secret token */
    public $client_secret = 'AQV9TbDU1k';

    /** @public String Authorization code */
    public $authorization_code;

    /** @public String Access token */
    public $access_token;

    /** @public String api key */
    public $api_key;

    /** @public String license key */
    public $license_key;

    /** @public String local key data */
    public $localkeydata;

    /**
     *
     */
    function __construct() {

        $options = get_option('woocommerce_billogram_general_settings');
        $localkeydata = get_option('local_key_billogram_plugin');
        //$this->api_url = "https://api.billogram.se/3/";
        if($options['billogram-mode']=='Sandbox')
        {
            $this->api_url = "https://sandbox.billogram.com/api/v2";
        }
        else
        {
            $this->api_url = "https://billogram.com/api/v2";
        }
             
        $this->authorization_code = $options['authorization_code'];
        $this->license_key = $options['license-key'];
        $this->api_key = $options['api-key'];
        $this->billogram_mode = $options['billogram-mode'];
        $this->access_token = get_option('billogram_access_token');
        $this->localkeydata = $localkeydata;
        if(!$this->access_token){
            $this->login();
        }
    }

    /**
     * Builds url
     *
     * @access public
     * @param mixed $urlAppendix
     * @return string
     */
    private function build_url($urlAppendix){
        //return $this->api_url.'/'.$urlAppendix;
        return $urlAppendix;
    }

    /**
     * Creates a HttpRequest and appends the given XML to the request and sends it to Billogram
     *
     * @access public
     * @return bool
     */
    public function create_api_validation_request(){
		return true;
        //logthis("LISCENSE VALIDATION");
        if(!isset($this->license_key)){
            return false;
        }
		
        $apiUsername = $this->api_key;
        $apiPassword = $this->authorization_code;
        $identifier = 'Bilogram API Customer';
        $apiBaseUrl = $this->api_url;
        $api = new BillogramAPI($apiUsername, $apiPassword, $identifier, $apiBaseUrl);
        
        if($api){
            return true;
        }
        return false;
    }

    /**
     * Creates a HttpRequest and appends the given XML to the request and sends it For license key
     *
     * @access public
     * @return bool
     */
    public function create_license_validation_request($localkey=''){
        //logthis("LISCENSE VALIDATION");
        if(!isset($this->license_key)){
            return false;
        }
        $licensekey = $this->license_key;
        // -----------------------------------
        //  -- Configuration Values --
        // -----------------------------------
        // Enter the url to your WHMCS installation here
		$whmcsurl = 'http://whmcs.onlineforce.net/';
        // Must match what is specified in the MD5 Hash Verification field
        // of the licensing product that will be used with this check.
        $licensing_secret_key = 'ak4762';
        // The number of days to wait between performing remote license checks
        $localkeydays = 15;
        // The number of days to allow failover for after local key expiry
        $allowcheckfaildays = 5;

        // -----------------------------------
        //  -- Do not edit below this line --
        // -----------------------------------

        $check_token = time() . md5(mt_rand(1000000000, 9999999999) . $licensekey);
        $checkdate = date("Ymd");
        $domain = $_SERVER['SERVER_NAME'];
        $usersip = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : $_SERVER['LOCAL_ADDR'];
        $dirpath = dirname(__FILE__);
        $verifyfilepath = 'modules/servers/licensing/verify.php';
        $localkeyvalid = false;
        if ($localkey) {
            $localkey = str_replace("\n", '', $localkey); # Remove the line breaks
            $localdata = substr($localkey, 0, strlen($localkey) - 32); # Extract License Data
            $md5hash = substr($localkey, strlen($localkey) - 32); # Extract MD5 Hash
            if ($md5hash == md5($localdata . $licensing_secret_key)) {
                $localdata = strrev($localdata); # Reverse the string
                $md5hash = substr($localdata, 0, 32); # Extract MD5 Hash
                $localdata = substr($localdata, 32); # Extract License Data
                $localdata = base64_decode($localdata);
                $localkeyresults = unserialize($localdata);
                $originalcheckdate = $localkeyresults['checkdate'];
                if ($md5hash == md5($originalcheckdate . $licensing_secret_key)) {
                    $localexpiry = date("Ymd", mktime(0, 0, 0, date("m"), date("d") - $localkeydays, date("Y")));
                    if ($originalcheckdate > $localexpiry) {
                        $localkeyvalid = true;
                        $results = $localkeyresults;
                        $validdomains = explode(',', $results['validdomain']);
                        if (!in_array($_SERVER['SERVER_NAME'], $validdomains)) {
                            $localkeyvalid = false;
                            $localkeyresults['status'] = "Invalid";
                            $results = array();
                        }
                        $validips = explode(',', $results['validip']);
                        if (!in_array($usersip, $validips)) {
                            $localkeyvalid = false;
                            $localkeyresults['status'] = "Invalid";
                            $results = array();
                        }
                        $validdirs = explode(',', $results['validdirectory']);
                        if (!in_array($dirpath, $validdirs)) {
                            $localkeyvalid = false;
                            $localkeyresults['status'] = "Invalid";
                            $results = array();
                        }
                    }
                }
            }
        }
        if (!$localkeyvalid) {
            $postfields = array(
                'licensekey' => $licensekey,
                'domain' => $domain,
                'ip' => $usersip,
                'dir' => $dirpath,
            );
            if ($check_token) $postfields['check_token'] = $check_token;
            $query_string = '';
            foreach ($postfields AS $k=>$v) {
                $query_string .= $k.'='.urlencode($v).'&';
            }
            if (function_exists('curl_exec')) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $whmcsurl . $verifyfilepath);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $query_string);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                $data = curl_exec($ch);
                curl_close($ch);
            } else {
                $fp = fsockopen($whmcsurl, 80, $errno, $errstr, 5);
                if ($fp) {
                    $newlinefeed = "\r\n";
                    $header = "POST ".$whmcsurl . $verifyfilepath . " HTTP/1.0" . $newlinefeed;
                    $header .= "Host: ".$whmcsurl . $newlinefeed;
                    $header .= "Content-type: application/x-www-form-urlencoded" . $newlinefeed;
                    $header .= "Content-length: ".@strlen($query_string) . $newlinefeed;
                    $header .= "Connection: close" . $newlinefeed . $newlinefeed;
                    $header .= $query_string;
                    $data = '';
                    @stream_set_timeout($fp, 20);
                    @fputs($fp, $header);
                    $status = @socket_get_status($fp);
                    while (!@feof($fp)&&$status) {
                        $data .= @fgets($fp, 1024);
                        $status = @socket_get_status($fp);
                    }
                    @fclose ($fp);
                }
            }
            if (!$data) {
                $localexpiry = date("Ymd", mktime(0, 0, 0, date("m"), date("d") - ($localkeydays + $allowcheckfaildays), date("Y")));
                if ($originalcheckdate > $localexpiry) {
                    $results = $localkeyresults;
                } else {
                    $results = array();
                    $results['status'] = "Invalid";
                    $results['description'] = "Remote Check Failed";
                    return $results;
                }
            } else {
                preg_match_all('/<(.*?)>([^<]+)<\/\\1>/i', $data, $matches);
                $results = array();
                foreach ($matches[1] AS $k=>$v) {
                    $results[$v] = $matches[2][$k];
                }
            }
            if (!is_array($results)) {
                die("Invalid License Server Response");
            }
            if (isset($results['md5hash'])) {
                if ($results['md5hash'] != md5($licensing_secret_key . $check_token)) {
                    $results['status'] = "Invalid";
                    $results['description'] = "MD5 Checksum Verification Failed";
                    return $results;
                }
            }
            if ($results['status'] == "Active") {
                $results['checkdate'] = $checkdate;
                $data_encoded = serialize($results);
                $data_encoded = base64_encode($data_encoded);
                $data_encoded = md5($checkdate . $licensing_secret_key) . $data_encoded;
                $data_encoded = strrev($data_encoded);
                $data_encoded = $data_encoded . md5($data_encoded . $licensing_secret_key);
                $data_encoded = wordwrap($data_encoded, 80, "\n", true);
                $results['localkey'] = $data_encoded;
            }
            $results['remotecheck'] = true;
        }
        unset($postfields,$data,$matches,$whmcsurl,$licensing_secret_key,$checkdate,$usersip,$localkeydays,$allowcheckfaildays,$md5hash);
        return $results;
        //return true;
    }

    /**
     * Creates a HttpRequest for creation of an invoice and appends the given XML to the request and sends it to Billogram
     *
     * @access public
     * @param mixed $xml
     * @return bool
     *//*
    public function create_invoice_request($xml){

        return $this->make_post_request($this->build_url("invoices"), $xml);
    }
    */
    /**
     * Creates a HttpRequest for setting an invoice with given id as bookkeot and sends it to Billogram
     *
     * @access public
     * @param mixed $id
     * @return bool
     *//*
    public function create_invoice_bookkept_request($id){
        logthis("SET INVOICE AS BOOKKEPT REQUEST");
        return $this->make_put_request($this->build_url("invoices/". $id . "/bookkeep"));
    }
    */
    /**
     * Creates a HttpRequest creation of an order and appends the given XML to the request and sends it to Billogram
     *
     * @access public
     * @param mixed $xml
     * @return bool
     */
    public function create_order_request($xml){
        logthis("CREATE ORDER REQUEST");
        //return $this->make_post_request($this->build_url("orders"), $xml);
        return $this->make_post_request("billogram", $xml);
    }

    /**
     * Creates a HttpRequest updating an order and appends the given XML to the request and sends it to Billogram
     *
     * @access public
     * @param mixed $xml
     * @param int $orderId
     * @return bool
     */
    public function update_order_request($xml, $orderId){
        logthis("UPDATE ORDER REQUEST");
        return $this->make_put_request($this->build_url("orders/". $orderId), $xml);
    }

    /**
     * Creates a HttpRequest creation of an orderinvoice and appends the given XML to the request and sends it to Billogram
     *
     * @access public
     * @param mixed $documentNumber
     * @return bool
     */
    public function create_order_invoice_request($documentNumber, $InvoiceSendMethod){
        logthis("CREATE INVOICE REQUEST");
        return $this->make_put_request($this->build_url("billogram/invoice"), $documentNumber, $InvoiceSendMethod);
    }
	
	/**
     * Creates a HttpRequest Credit of an Invoice
     *
     * @access public
     * @param mixed $invoice_id
     * @return bool
     */
    public function create_credit_invoice_request($invoice_id, $amount){
        logthis("CREDIT INVOICE REQUEST");
        return $this->make_credit_invoice($invoice_id, $amount);
    }
    
    /**
     * Creates the HttpRequest creation of a contact/customer and appends the given XML to the request and sends it to Billogram
     *
     * @access public
     * @param mixed $xml
     * @return bool
     */
    public function create_customer_request($xml){
        logthis("CREATE CONTACT PRICE REQUEST");
        //return $this->make_post_request($this->build_url("customers"), $xml);
        return $this->make_post_request("customers", $xml);
    }

    /**
     * Creates a HttpRequest for creation of a product and appends the given XML to the request and sends it to Billogram
     *
     * @access public
     * @param mixed $xml
     * @return bool
     */
    public function create_product_request($xml){
        logthis("CREATE PRODUCT REQUEST");
        return $this->make_post_request("items", $xml);
    }

    /**
     * Creates a HttpRequest for creation of product(for given sku)
     * price and appends the given XML to the request and sends it to Billogram
     *
     * @access public
     * @param mixed $xml
     * @return bool
     */
    public function create_product_price_request($xml){
        logthis("CREATE PRODUCT PRICE REQUEST");
        return $this->make_post_request($this->build_url("prices/"), $xml);
    }

    /**
     * Creates a HttpRequest for fetching all customer and appends the given XML to the request and sends it to Billogram
     *
     * @access public
     * @return bool
     */
    public function get_customers(){
        logthis("GET CUSTOMER REQUEST");
        //return $this->make_get_request($this->build_url("customers"));
        return $this->make_get_request("customers");
              
    }

    /**
     * Creates a HttpRequest for fetching all customerand appends the given XML to the request and sends it to Billogram
     *
     * @access public
     * @return bool
     */
    public function get_inventory(){
        logthis("GET INVENTORY REQUEST");
        return $this->make_get_request($this->build_url("articles"));
    }
	
	/**
     * Creates a HttpRequest for fetching invoice data using ID
	 *
     * @access public
     * @return bool
     */
    public function get_invoice($billogramID){
        logthis("GET INVOICE DATA");
        return $this->make_get_invoice($billogramID);
    }

    /**
     * Creates a HttpRequest and appends the given XML to the request and sends it to Billogram
     *
     * @access public
     * @return bool
     */
    public function login(){

        if(!$this->localkeydata){
            return false;
        }

        //logthis("LOGIN");
        //logthis($this->authorization_code);
        //logthis($this->client_secret);
        $headers = array(
            'Accept: application/xml',
            'Authorization-Code: '.$this->authorization_code,
            'Client-Secret: '.$this->client_secret,
            'Content-Type: application/xml',
        );
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
        curl_setopt ($ch, CURLOPT_URL, $this->api_url);
        curl_setopt ($ch, CURLOPT_TIMEOUT,60);
        curl_setopt ($ch, CURLOPT_VERBOSE,0);
        curl_setopt ($ch, CURLOPT_POST, 0);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);

        $data = curl_exec($ch);
        $arrayData = json_decode(json_encode(simplexml_load_string($data)), true);
        $this->access_token = $arrayData['AccessToken'];
        if($this->access_token){
            update_option( 'billogram_access_token', $this->access_token, '', 'yes' );
        }
        //logthis(print_r($arrayData, true));
        curl_close($ch);
        return false;
    }

    /**
     * Makes GET request
     *
     * @access private
     * @param mixed $url
     * @return string
     */
    private function make_get_request($url){

        if(!$this->localkeydata){
            return false;
        }

        $apiUsername = $this->api_key;
        $apiPassword = $this->authorization_code;
        $identifier = 'Bilogram API Customer';
        $apiBaseUrl = $this->api_url;
        $api = new BillogramAPI($apiUsername, $apiPassword, $identifier, $apiBaseUrl);
        $customersQuery = $api->$url->query()->order(
            'created_at',
            'asc'
        );
        $totalPages = $customersQuery->totalPages();
        for ($page = 1; $page <= $totalPages; $page++) {
            $customersArray = $customersQuery->getPage($page);
            /* Loop over the customersArray and do something with the customers here. */
        }
        error_log(print_r($customersArray, true));

        //Send error to plugapi
        if (array_key_exists("Error",$customersArray)){
            error_log("BILLOGRAM ERROR");
            $this->post_error($customersArray['Message']);
        }
        return $customersArray;
    }
	
	/**
     * Makes GET request to fetch billogram
     *
     * @access private
     * @param mixed $url
     * @return string
     */
    private function make_get_invoice($billogramID){

        if(!$this->localkeydata){
            return false;
        }

        $apiUsername = $this->api_key;
        $apiPassword = $this->authorization_code;
        $identifier = 'Bilogram API Customer';
        $apiBaseUrl = $this->api_url;
        $api = new BillogramAPI($apiUsername, $apiPassword, $identifier, $apiBaseUrl);
        $billogram = $api->billogram->get($billogramID);
        

        //Send error to plugapi
        if (array_key_exists("Error",$billogram)){
            logthis("BILLOGRAM ERROR");
        }
        return $billogram;
    }
	
	/**
     * Makes GET request to fetch billogram
     *
     * @access private
     * @param mixed $url
     * @return string
     */
    private function make_credit_invoice($billogramID, $amount){

        if(!$this->localkeydata){
            return false;
        }

        $apiUsername = $this->api_key;
        $apiPassword = $this->authorization_code;
        $identifier = 'Bilogram API Credit';
        $apiBaseUrl = $this->api_url;
        $api = new BillogramAPI($apiUsername, $apiPassword, $identifier, $apiBaseUrl);
		$billogramObject = $api->billogram->get($billogramID);
        $billogramObject->creditAmount($amount);
        

        //Send error to plugapi
        if (array_key_exists("Error",$billogram)){
            logthis("BILLOGRAM ERROR");
        }
        return $billogramObject;
    }

    /**
    * Makes POST request
    *
    * @access private
    * @param mixed $url
    * @param mixed $xml
    * @return string
    */
    private function make_post_request($url,$xml){
        
        if(!$this->localkeydata){
            return false;
        }
        /*$options = get_option('woocommerce_billogram_general_settings');*/
        $apiUsername = $this->api_key;
        $apiPassword = $this->authorization_code;
        $identifier = 'Bilogram API Create';
        $apiBaseUrl = $this->api_url;
        try{
            $api = new BillogramAPI($apiUsername, $apiPassword, $identifier, $apiBaseUrl);
        
            //logthis("XML:".print_r($xml, true));
            //logthis($api);
            $arrayData = $api->$url->create($xml);
            //logthis($arrayData);
            /*if($options['activate-orders']=='Skicka faktura')
            {
                $arrayData->send('Email');
            }*/
            //$arrayData->send('Email');
            //logthis("Billogram:".print_r($arrayData, true));

            //Send error to plugapi
            if (array_key_exists("Error",$arrayData)){
                logthis("BILLOGRAM ERROR");
                $this->post_error($arrayData['Message']);
            }

            return $arrayData;
        }catch (Exception $exception){
            logthis($exception->getMessage());
            logthis($arrayData);
            return false;
         }
    }

    /**
     * Makes PUT request
     *
     * @access private
     * @param mixed $url
     * @param mixed $xml
	 * @param string $InvoiceSendMethod
     * @return string
     */
    private function make_put_request($url,$xml = NULL, $InvoiceSendMethod = NULL){

        if(!$this->localkeydata){
            return false;
        }
        
        $url = explode("/",$url);
        $apiUsername = $this->api_key;
        $apiPassword = $this->authorization_code;
        $identifier = 'Bilogram API Update Customer';
        $apiBaseUrl = $this->api_url;
		$api = new BillogramAPI($apiUsername, $apiPassword, $identifier, $apiBaseUrl);
		$options = get_option('woocommerce_billogram_general_settings');
		
        if($url[0]=="billogram")
        {
			if($InvoiceSendMethod == 'email'){
				logthis('------------Skapa faktura och skicka som epost-------------');
				$xml->send('Email');
				$array_data = 'true';
			}
			elseif($InvoiceSendMethod == 'letter'){
				logthis('------------Skapa faktura och skicka som brev-------------');
				$xml->send('Letter');
				$array_data = 'true';
			}
            elseif ($InvoiceSendMethod == 'email+letter') {
                logthis('------------Skapa fakturor och skicka som e-post och brev-------------');
                $xml->send('Email+Letter');
                $array_data = 'true';
            }
        }
        else if($url[0]=="items")
        {

            do {
                $array_data = '';
                $e = '';
                try {
                    $array_data = $api->$url[0]->get($url[1]);
                    break;
                } catch (ObjectNotFoundError $e) { // PDF has not been created yet.
                    break;
                    }
                } while (true);

                if($e)
                {
                    $array_data = $this->make_post_request('items',$xml);
                }
                else
                {
                    $array_data->update($xml);
                }
            
        }
        else
        {
            $array_data = $api->$url[0]->get($url[1]);
            $array_data->update($xml);
        }
        //logthis(print_r($array_data, true));

        //Send error to plugapi
        if (array_key_exists("Error",$array_data)){
            logthis("BILLOGRAM ERROR");
            $this->post_error($array_data['Message']);
        }

        return $array_data;
    }
	
	/**
     * Makes GET request
     *
     * @access private
     * @param mixed $url
     * @return array
     */
    private function fetch_settings_request($url){

        if(!$this->localkeydata){
            return false;
        }

        $identifier = 'Bilogram API Settings';
        $settingsURL = $this->api_url.'/'.$url;
		
		logthis($this->api_key);
		logthis($this->authorization_code);
		$auth = base64_encode($this->api_key . ":" . $this->authorization_code);
		logthis($auth);
        
		$headers = array(
            'Authorization: Basic ' . $auth,
            'Content-Type: application/json',
        );
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
        curl_setopt ($ch, CURLOPT_URL, $settingsURL);
        curl_setopt ($ch, CURLOPT_TIMEOUT,60);
        curl_setopt ($ch, CURLOPT_VERBOSE,0);
        curl_setopt ($ch, CURLOPT_POST, 0);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);

        $data = curl_exec($ch);
        $settings = json_decode($data, true);
		return $settings;
    }

    /**
     * Creates a HttpRequest for an update of a customer and appends the given XML to the request and sends it to Billogram
     *
     * @access public
     * @param mixed $xml
     * @param mixed $customerNumber
     * @return bool
     */
    public function update_customer_request($xml, $customerNumber){
        logthis("UPDATE CUSTOMER REQUEST");
        //return $this->make_put_request($this->build_url("customers/" . $customerNumber), $xml);
        return $this->make_put_request($this->build_url("customers/" . $customerNumber), $xml);
    }

    /**
     * Creates a HttpRequest for an update of a product and appends the given XML to the request and sends it to Billogram
     *
     * @access public
     * @param mixed $xml
     * @param mixed $sku
     * @return bool
     */
    public function update_product_request($xml, $sku){
        logthis("UPDATE PRODUCT REQUEST");
        return $this->make_put_request($this->build_url("items/" . $sku), $xml);
    }

    /**
     * Creates a HttpRequest for an update of product(for given sku)
     * price and appends the given XML to the request and sends it to Billogram
     *
     * @access public
     * @param mixed $xml
     * @param mixed $sku
     * @return bool
     */
    public function update_product_price_request($xml, $sku){
        logthis("UPDATE PRICE REQUEST");
        return $this->make_put_request($this->build_url("prices/A/" . $sku . "/0"), $xml);
    }
	
	
	/**
     * Fetches BillogramAPI settings containing Company name to validate the Billogram API account.
     * @access public
     * @return sting (settings company name)
     */
    public function fetch_settings(){
        logthis("FETCHING SETTINGS");
        return $this->fetch_settings_request("settings");
    }


    private function post_error($message){
        if(!$this->localkeydata){
            return false;
        }

        $fields = array(
            'license_key' => $this->license_key,
            'message' => $message,
        );

        $ch = curl_init();
        $url = "http://plugapi.consuasor.se/api_post.php";
        curl_setopt ($ch, CURLOPT_URL, $url);
        curl_setopt ($ch, CURLOPT_TIMEOUT,60);
        curl_setopt ($ch, CURLOPT_VERBOSE,0);
        curl_setopt ($ch, CURLOPT_POST, 1);
        curl_setopt ($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);

        $data = curl_exec($ch);
        curl_close($ch);
        logthis($data);
    }
}