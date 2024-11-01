<?php
include_once("class-billogram2-xml.php");
include_once("class-billogram2-product-xml.php");
class WCB_Order_XML_Document extends WCB_XML_Document{
    /**
     *
     */
    function __construct() {
        parent::__construct();
    }
    /**
     * Creates a n XML representation of an Order
     *
     * @access public
     * @param mixed $arr
     * @param $customerNumber
     * @return mixed
     */
    public function create($arr, $customerNumber){
    	//logthis("Create order:");
    	//logthis($arr);

        $order_options = get_option('woocommerce_billogram_order_settings');
        $options = get_option('woocommerce_billogram_general_settings');
		
		//Add for woosubscription support
		if (class_exists("WC_Subscriptions_Order") && WC_Subscriptions_Order::order_contains_subscription( $arr ))
			$subscription = WC_Subscriptions_Order::order_contains_subscription( $arr );
		
        //$root = 'Order';
        $signKey = uniqid();
        $siteurl = admin_url('admin-ajax.php').'?action=billogram_callback';
		//$siteurl = plugins_url( '/woocommerce-billogram-integration/billogram-callback.php' );
		//logthis("siteurl: ". $siteurl);

        $wc_date = $arr->get_date_created();
       
        $order['invoice_date'] = $wc_date->date('Y-m-d');
        //$order['due_date'] = date("Y-m-d", strtotime($arr->order_date ." +15 day") );
		if($order_options['due-days'] != ''){
			$order['due_days'] = $order_options['due-days'];
		}
        $order['currency'] = 'SEK';
        if($arr->get_billing_company()){
            $order['customer']['name'] =  $arr->get_billing_company();
        }else{
            $order['customer']['name'] = $arr->get_billing_first_name() . " " . $arr->get_billing_last_name();
        }
        $order['customer']['customer_no'] = $customerNumber;
        $order['customer']['phone'] = $arr->get_billing_phone();
        $order['customer']['address']['street_address'] = $arr->get_billing_address_1();
        $order['customer']['address']['city'] = $arr->get_billing_city();
        /*$order['customer']['address']['country'] = 'SE';*/
        /*$order['customer']['address']['country'] = $this->countries[$arr->billing_country];*/
        $order['customer']['address']['zipcode'] = $arr->get_billing_postcode();
        $order['customer']['email'] = $arr->get_billing_email();
        //$order['currency'] = $arr->get_order_currency();
		if($order_options['admin-fee'] != ''){
			$order['invoice_fee'] = $order_options['admin-fee'];
		}
        
        $invoicerows = array();
        //loop all items
        $index = 0;
        //logthis("items:");
        //logthis($arr->get_items());
        foreach($arr->get_items() as $item_id => $item){
            /*$key = "items" . $index;*/
			$invoicerow = array();
			//logthis('item:');
			//logthis($item);

            //fetch product
            $item = $item->get_data();
            //logthis($item);
            
            //if variable product there might be a different SKU
            if(empty($item['variation_id'])){
				$productId = $item['product_id'];
				 $invoicerow['title'] = (strlen($item['name']) > 40) ? substr($item['name'],0,36).'...' : $item['name'];
                 //$description = $item['name'];
				 $productDesc = strip_tags(get_post($productId)->post_content);
				 $description = (strlen($productDesc) > 200) ? substr($productDesc,0,196).'...' : $productDesc;
            }
            else{
                 $productId = $item['variation_id'];
				 $product_xml = new WCB_Product_XML_Document();
				 //logthis($item['item_meta']);
				 //$item_variation = $product_xml->get_product_meta($item['item_meta']);
				 $item_variation = $item['meta_data'][0]->value;
                 //$_product  = apply_filters( 'woocommerce_order_item_product', $arr->get_product_from_item( $item ), $item );
                 //$item_meta = new WC_Order_Item_Meta( $item['item_meta'], $_product );
                 //$description = $item['name'].' - '.$item_meta->display($flat = true, $return = true);
				 
				 $invoicerow['title'] = (strlen($item['name']. ' ('. $item_variation . ')') > 40) ? substr($item['name']. ' ('. $item_variation . ')',0,36).'...' : $item['name']. ' ('. $item_variation.')';
				 $productDesc = strip_tags(get_post($productId)->post_content);
				 $description = (strlen('('. $item_variation. ')'. $productDesc) > 200) ? substr('('. $item_variation. ')'. $productDesc,0,196).'...' : '('. $item_variation. ')'. $productDesc;
            }
			
			$product = wc_get_product($productId);

			//logthis("Product:". $productId);
			//logthis($product);

			$invoicerow['description'] = $description;
			
			$invoicerow['price'] =  round($item['subtotal']/$item['quantity'], 2);
				
			$discount = $product->get_regular_price() - $product->get_sale_price();
			if($product->is_on_sale()){
				$invoicerow['discount'] = round($item['quantity']*($discount), 2);
			}else{
				$invoicerow['discount'] = 0;
			}
			
			if(class_exists('WC_Subscriptions_Order')){
				if(WC_Subscriptions_Product::is_subscription( $productId )){
					if(WC_Subscriptions_Product::get_trial_length( $productId ) > 0 && $discount > $invoicerow['price']){
						logthis('Order line item is synced with price "0", because order line is a subscription with a free trial period!');
						$invoicerow['discount'] = 0;
					}
					$price_string = html_entity_decode(strip_tags(WC_Subscriptions_Product::get_price_string($productId)));
					
					if($product->is_on_sale()){
						$price = $product->get_sale_price();
					}else{
						$price = $product->get_regular_price();
					}
					
					$invoicerow['description'] = substr_replace($price_string, $price.html_entity_decode(get_woocommerce_currency_symbol()), 0, 2).' '.$invoicerow['description'];
					$invoicerow['description'] = (strlen($invoicerow['description']) > 200) ? substr($invoicerow['description'],0,196).'...' : $invoicerow['description'];
				}

			}
			
			
			$tax = wc_get_price_including_tax($product) - wc_get_price_excluding_tax($product);
			if($tax > 0){
				$taxper = round($tax*100/wc_get_price_excluding_tax($product));
				$invoicerow['vat'] = $taxper;
			}else{
				$invoicerow['vat'] = 0;
			}
			$invoicerow['count'] = $item['quantity'];			
			
			/*$index += 1;*/
            $invoicerows[] = $invoicerow;
        }


		if ($arr->get_total_shipping() > 0 ) {
			$invoicerowShipping = array();
			$invoicerowShipping['title'] = 'Shipping and Handling: '.$arr->get_shipping_method() ;
			$invoicerowShipping['price'] = $arr->get_total_shipping();
			$tax = $arr->get_shipping_tax();
            $taxper = round($tax*100/$arr->get_total_shipping());
			//echo $taxper; die();
			$invoicerowShipping['vat'] = $taxper;
			$invoicerowShipping['count'] = 1;
			//$invoicerowShipping['unit'] = 'unit';
			$invoicerows[] = $invoicerowShipping;
		}
		if (count( WC()->cart->applied_coupons ) > 0 ) {
			//logthis(WC()->cart->applied_coupons);
			$invoicerowDiscount = array();
			foreach (WC()->cart->applied_coupons as $code ) {
				logthis('Coupon type: '.wc_get_coupon_type($code));
				$invoicerowDiscount['title'] = 'Coupon: '.$code;
				$coupounAmount = WC()->cart->coupon_discount_amounts[ $code ];
				$coupounTaxAmount = WC()->cart->coupon_discount_tax_amounts[ $code ];
				$invoicerowDiscount['price'] = -round($coupounAmount, 2);
				//$invoicerowDiscount['price'] = 0;
				$tax = $coupounTaxAmount;
				if($tax > 0)
            		$taxper = round($tax*100/$coupounAmount);
				else
					$taxper = 0;
            	$invoicerowDiscount['vat'] = $taxper;
				//$invoicerowDiscount['vat'] = 0;
				$invoicerowDiscount['count'] = 1;
				//$invoicerow['unit'] = 'unit';
				$invoicerows[] = $invoicerowDiscount;
			}
		}
		
        $order['items'] = $invoicerows;
        $order['callbacks']['sign_key'] = $signKey;
        $order['callbacks']['url'] = $siteurl;
        $order['info']['order_no'] = $arr->id;
        $order['info']['order_date'] = substr($arr->order_date, 0, 10);
        $order['info']['delivery_date'] = NULL;
        
		//logthis($order);
		
        return $order;
    }
	
	
	
	/**
     * Creates a n XML representation of an Order
     *
     * @access public
     * @param mixed $arr
     * @param $customerNumber
     * @return mixed
     */
    public function create_scheduled_subscription($amount_to_charge, $arr, $customerNumber){

        $order_options = get_option('woocommerce_billogram_order_settings');
        $options = get_option('woocommerce_billogram_general_settings');
		
        //$root = 'Order';
        $signKey = uniqid();
        $siteurl = admin_url('admin-ajax.php').'?action=billogram_callback_subscription';
		//$siteurl = plugins_url( '/woocommerce-billogram-integration/billogram-callback.php' );
		//logthis("siteurl: ". $siteurl);
       
	   
	   	$order['invoice_date'] = date('Y-m-d');
		//$order['invoice_date'] = WC_Subscriptions_Order::get_next_payment_date( $arr, $product_id, $from_date = '' );
	   
        //$order['invoice_date'] = substr($arr->order_date, 0, 10);
        //$order['due_date'] = date("Y-m-d", strtotime($arr->order_date ." +15 day") );
		if($order_options['due-days'] != ''){
			$order['due_days'] = $order_options['due-days'];
		}
        $order['currency'] = 'SEK';
        if($arr->billing_company){
            $order['customer']['name'] =  $arr->get_billing_company();
        }else{
            $order['customer']['name'] = $arr->get_billing_first_name() . " " . $arr->get_billing_last_name();
        }
        $order['customer']['customer_no'] = $customerNumber;
        $order['customer']['phone'] = $arr->get_billing_phone();
        $order['customer']['address']['street_address'] = $arr->get_billing_address_1();
        $order['customer']['address']['city'] = $arr->get_billing_city();
        /*$order['customer']['address']['country'] = 'SE';*/
        /*$order['customer']['address']['country'] = $this->countries[$arr->billing_country];*/
        $order['customer']['address']['zipcode'] = $arr->get_billing_postcode();
        $order['customer']['email'] = $arr->get_billing_email();
        //$order['currency'] = $arr->get_order_currency();
		if($order_options['admin-fee'] != ''){
			$order['invoice_fee'] = $order_options['admin-fee'];
		}
        
        $invoicerows = array();
        //loop all items
        $index = 0;
		//logthis('Order object: ');
		//logthis($arr);
        foreach($arr->get_items() as $item_id => $item){
            /*$key = "items" . $index;*/
			
			//logthis('Order item: ');
			//logthis($item)       

			$item = $item->get_data();     
            
            //if variable product there might be a different SKU
            if(empty($item['variation_id'])){
                 $productId = $item['product_id'];
                 //$description = $item['name'];
            }
            else{
                 $productId = $item['variation_id'];
                 //$_product  = apply_filters( 'woocommerce_order_item_product', $arr->get_product_from_item( $item ), $item );
                 //$item_meta = new WC_Order_Item_Meta( $item['item_meta'], $_product );
                 $item_variation = $item['meta_data'][0]->value;
                 //$description = $item['name'].' - '.$item_meta->display($flat = true, $return = true);
            }
			
			//logthis('Item Product ID');
			//logthis($productId);
			
			if(WC_Subscriptions_Product::get_price( $productId ) != '' ){
				$invoicerow = array();
				$product = new WC_Product($productId);
				
				//logthis('Item Product');
				//logthis($product);

				$productDesc = strip_tags(get_post($productId)->post_content);
				$description = (strlen($productDesc) > 200) ? substr($productDesc,0,196).'...' : $productDesc;
				$invoicerow['title'] = (strlen($item['name']) > 40) ? substr($item['name'],0,36).'...' : $item['name'];
				if($product->post->post_type == 'product_variation' && $product->post->post_parent != 0){
					$attr = wc_get_product_variation_attributes($product->id);
					//logthis($attr);
					$attribute = '';
					foreach($attr as $key => $value){
						$attribute .= $value.'-';
					}
					$parentProductTitle = explode('#', $product->get_title());
					$title = $attribute.$parentProductTitle[1];
					$invoicerow['title'] = (strlen($title) > 40) ? substr($title,0,36).'...' : $title;
				}

				$invoicerow['price'] = round($item['subtotal']/$item['quantity'], 2);
				
				$discount = $item['subtotal']-$item['total'];
				if($product->is_on_sale() || $discount > 0){
					$invoicerow['discount'] = round($item['quantity']*($discount), 2);
				}else{
					$invoicerow['discount'] = 0;
				}
				$invoicerow['description'] = $description;
				
				$price_string = html_entity_decode(strip_tags(WC_Subscriptions_Product::get_price_string($productId)));	
				
				$invoicerow['description'] .= substr_replace($price_string, $invoicerow['price'].html_entity_decode(get_woocommerce_currency_symbol()), 0, 2).' '.$invoicerow['description'];
				$invoicerow['description'] = (strlen($invoicerow['description']) > 200) ? substr($invoicerow['description'],0,196).'...' : $invoicerow['description'];
				
				$tax = wc_get_price_including_tax($product) - wc_get_price_excluding_tax($product);
				if($tax > 0){
					$taxper = round($tax*100/wc_get_price_excluding_tax($product));
					$invoicerow['vat'] = $taxper;
				}else{
					$invoicerow['vat'] = 0;
				}
				$invoicerow['count'] = $item['qty'];
										
				
				/*$index += 1;*/
				$invoicerows[] = $invoicerow;
			}
        }
		if ($arr->get_total_shipping() > 0 ) {
			$invoicerowShipping = array();
			$invoicerowShipping['title'] = 'Shipping and Handling: '.$arr->get_shipping_method() ;
			$invoicerowShipping['price'] = round($arr->get_total_shipping(), 2);
			$tax = $arr->get_shipping_tax();
            $taxper = round($tax*100/$arr->get_total_shipping());
			//echo $taxper; die();
			$invoicerowShipping['vat'] = $taxper;
			$invoicerowShipping['count'] = 1;
			//$invoicerowShipping['unit'] = 'unit';
			$invoicerows[] = $invoicerowShipping;
		}
		if (count( WC()->cart->applied_coupons ) > 0 ) {
			$invoicerowDiscount = array();
			foreach (WC()->cart->applied_coupons as $code ) {
				$invoicerowDiscount['title'] = 'Coupon: '.$code;
				$coupounAmount = WC()->cart->coupon_discount_amounts[ $code ];
				$coupounTaxAmount = WC()->cart->coupon_discount_tax_amounts[ $code ];
				//$invoicerowDiscount['price'] = -$coupounAmount;
				$invoicerowDiscount['price'] = 0;
				$tax = $coupounTaxAmount;
            	$taxper = round($tax*100/$coupounAmount);
            	//$invoicerowDiscount['vat'] = $taxper;
				$invoicerowDiscount['vat'] = 0;
				$invoicerowDiscount['count'] = 1;
				//$invoicerow['unit'] = 'unit';
				$invoicerows[] = $invoicerowDiscount;
			}
		}
		
        $order['items'] = $invoicerows;
        $order['callbacks']['sign_key'] = $signKey;
        $order['callbacks']['url'] = $siteurl;
        $order['info']['order_no'] = $arr->id;
        $order['info']['order_date'] = substr($arr->order_date, 0, 10);
        $order['info']['delivery_date'] = NULL;
        
		//logthis($order);
		
        return $order;
    }
}