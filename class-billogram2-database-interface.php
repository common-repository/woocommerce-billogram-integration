<?php
class WCB_Database_Interface{

    /**
     *
     */
    function __construct() {
    }

    /**
     * Creates a n XML representation of a n Order
     *
     * @access public
     * @internal param mixed $arr
     * @return mixed
     */
    public function read_unsynced_orders(){
        global $wpdb;
		return $wpdb->get_results("SELECT * from wcb_orders WHERE synced = 0");
    }
	
	 /**
     * Creates a n XML representation of a n Order
     *
     * @access public
     * @internal param mixed $arr
     * @return bool
     */
    public function is_synced_order($id = NULL){
        global $wpdb;
		if($id){
			$order = $wpdb->get_results("SELECT * from wcb_orders WHERE synced = 1 AND order_id = ".$id, ARRAY_A);
			logthis("is_synced_order");
			if(!empty($order)){
				return true;
			}else{
				return false;
			}
		}
		else{
        	
		}
    }

    /**
     * Sets an order to synced
     *
     * @access public
     * @param int $orderId
     * @return bool
     */
    public function set_as_synced($orderId){
        global $wpdb;
        $wpdb->query("UPDATE wcb_orders SET synced = 1 WHERE order_id = ".$orderId);
        return true;

    }
	
	/**
     * Sets an subscription order to synced
     *
     * @access public
     * @param int $orderId
     * @return bool
     */
    public function set_as_synced_subscription($orderId){
        global $wpdb;
        $wpdb->query("UPDATE wcb_orders SET synced = 1 WHERE order_id = ".$orderId." AND invoice_id = 0 AND invoice_no = 0 AND ocr_number = 0");
        return true;

    }

     /**
     * Sets an Product SKU
     *
     * @access public
     * @param int $orderId
     * @return bool
     */
    public function set_product_sku($productId,$sku){
        global $wpdb;
        $wpdb->query("INSERT INTO wcb_products VALUES (NULL, ".$productId.", '".$sku."')");
        return true;

    }

    /**
     * Writes an update product SKU in database
     *
     * @access public
     * @param $customerId
     * @param $customerNumber
     * @return bool
     */
    public function update_product_sku($productId,$sku){
        global $wpdb;
        $wpdb->query("UPDATE wcb_products SET product_sku = '". $sku ."' WHERE product_id = ".$productId);
        return true;
    }

    /**
     * get an Product SKU
     *
     * @access public
     * @param int $orderId
     * @return bool
     */
    public function get_product_sku($productId){
        global $wpdb;
        return $wpdb->get_results("SELECT * from wcb_products WHERE product_id = '". $productId ."'");
    
    }

    /**
     * Writes an unsynced order to the database
     *
     * @access public
     * @param int $orderId
     * @return bool
     */
    public function create_unsynced_order($orderId){
        global $wpdb;
        $wpdb->query("INSERT INTO wcb_orders VALUES (NULL, ".$orderId.", '', 0, 0, 0)");
        return true;
    }

    /**
     * Creates a customer
     *
     * @access public
     * @param $email
     * @return bool
     */
    public function create_customer($email){
        global $wpdb;
        $wpdb->query("INSERT INTO wcb_customers VALUES (NULL, 0,'".$email."')");
        return $wpdb->insert_id;
    }

    /**
     * Creates a customer
     *
     * @access public
     * @param $customer
     * @return bool
     */
    public function create_existing_customer($customer){
        global $wpdb;
        if(!$customer->customer_no){
            return;
        }
        if($customer->contact->email && $customer->customer_no){

                $wpdb->query("INSERT INTO wcb_customers VALUES (NULL, '".$customer->customer_no."', '".$customer->contact->email."')");
                return $wpdb->insert_id;
            }
        
    }

    /**
     * Gets customer by email
     *
     * @access public
     * @param $email
     * @return bool
     */
    public function get_customer_by_email($email){
        global $wpdb;
        return $wpdb->get_results("SELECT * from wcb_customers WHERE email = '". $email ."'");
    }

    /**
     * Writes an unsynced order to the database
     *
     * @access public
     * @param $customerId
     * @param $customerNumber
     * @return bool
     */
    public function update_customer($customerId, $customerNumber){
        global $wpdb;
        $wpdb->query("UPDATE wcb_customers SET customer_number = '". $customerNumber ."' WHERE id = ".$customerId);
        return true;
    }

    public function reset_database(){
        global $wpdb;
        $wpdb->query("DELETE FROM wcb_customers;");
        return true;
    }
}