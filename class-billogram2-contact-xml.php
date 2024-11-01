<?php
include_once("class-billogram2-xml.php");

class WCB_Contact_XML_Document extends WCB_XML_Document{

    /**
     *
     */
    function __construct() {
        parent::__construct();
    }

    /**
     * Creates an XML representation of an order
     *
     * @access public
     * @param mixed $arr
     * @return mixed
     */
    public function create($arr){
        $contact = array();
        if($arr->get_billing_company()){
            $contact['name'] =  $arr->get_billing_company();
        }else{
            $contact['name'] = $arr->get_billing_first_name() . " " . $arr->get_billing_last_name();
        }
        //logthis('Contact name:'. $contact['name'])
        $contact['contact']['name'] = $arr->get_billing_first_name() . " " . $arr->get_billing_last_name();
        $contact['contact']['email'] = $arr->get_billing_email();
        $contact['contact']['phone'] = $arr->get_billing_phone();
        $contact['address']['street_address'] = $arr->get_billing_address_1();
        $contact['address']['zipcode'] = $arr->get_billing_postcode();
        $contact['address']['city'] = $arr->get_billing_city();
		$contact['address']['country'] = $arr->get_billing_country();
        $contact['delivery_address']['street_address'] = $arr->get_shipping_address_1();
        $contact['delivery_address']['zipcode'] = $arr->get_shipping_postcode();
        $contact['delivery_address']['city'] = $arr->get_shipping_city();
		$contact['delivery_address']['country'] = $arr->get_shipping_country();
        if($arr->get_billing_company()){
            $contact['company_type'] = 'business';
        }else{
            $contact['company_type'] = 'individual';
        }
    
        //$contact['PriceList'] = 'A';
        //$root = 'Customer';
        //return $this->generate($root, $contact);
        return $contact;
    }
    
    public function update($arr,$custome_no){
        $contact = array();
        $contact['customer_no'] = $custome_no;
        if($arr->get_billing_company()){
            $contact['name'] =  $arr->get_billing_company();
        }else{
            $contact['name'] = $arr->get_billing_first_name() . " " . $arr->get_billing_last_name();
        }
        $contact['contact']['name'] = $arr->get_billing_first_name() . " " . $arr->get_billing_last_name();
        $contact['contact']['email'] = $arr->get_billing_email();
        $contact['contact']['phone'] = $arr->get_billing_phone();
        $contact['address']['street_address'] = $arr->get_billing_address_1();
        $contact['address']['zipcode'] = $arr->get_billing_postcode();
        $contact['address']['city'] = $arr->get_billing_city();
        $contact['address']['country'] = $arr->get_billing_country();
        $contact['delivery_address']['street_address'] = $arr->get_shipping_address_1();
        $contact['delivery_address']['zipcode'] = $arr->get_shipping_postcode();
        $contact['delivery_address']['city'] = $arr->get_shipping_city();
        $contact['delivery_address']['country'] = $arr->get_shipping_country();
        if($arr->get_billing_company()){
            $contact['company_type'] = 'business';
        }else{
            $contact['company_type'] = 'individual';
        }
        //$contact['PriceList'] = 'A';
        //$root = 'Customer';
        //return $this->generate($root, $contact);
        return $contact;
    }
}