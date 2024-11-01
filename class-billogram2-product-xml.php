<?php
include_once("class-billogram2-xml.php");
class WCB_Product_XML_Document extends WCB_XML_Document{
    /**
     *
     */
    function __construct() {
        parent::__construct();
    }
    /**
     * Creates a XML representation of a Product
     *
     * @access public
     * @param mixed $product
     * @return mixed
     */
    public function create($product){
        //$root = 'Article';
        $productNode = array();
        $sku = $product->get_sku();
        $tax = wc_get_price_including_tax($product) - wc_get_price_excluding_tax($product);
        $taxper = wc_get_price_excluding_tax($product) != 0? round($tax*100/wc_get_price_excluding_tax($product)) : 0;
        if($sku){
            $productNode['item_no'] = $sku;
        }
        //logthis($product);
       //logthis($product->get_type());
        //logthis($product->get_parent_id());
		if(strpos($product->get_type(), 'variation') !== false && $product->get_parent_id() != 0){
			$attr = wc_get_product_variation_attributes($product->get_id());
			//logthis($attr);
			$attribute = '';
			foreach($attr as $key => $value){
				$attribute .= $value;
			}
            //logthis($product->get_title());
			$parentProductTitle = $product->get_title();
			$title = $parentProductTitle .' - '. $attribute;
			$productNode['title'] = (strlen($title) > 40) ? substr($title,0,36).'...' : $title;
            //logthis($productNode['title']);
		}else{
			 $productNode['title'] = (strlen($product->get_title()) > 40) ? substr($product->get_title(),0,36).'...' : $product->get_title();
		}
        //$productNode['title'] = (strlen($product->get_title()) > 40) ? substr($product->get_title(),0,36).'...' : $product->get_title();
        $productDesc = strip_tags(get_post($product->get_id())->post_content);
        $productNode['description'] = (strlen($productDesc) > 200) ? substr($productDesc,0,196).'...' : $productDesc;
        $productNode['price'] = wc_get_price_excluding_tax($product) ? round(wc_get_price_excluding_tax($product), 2) : $product->get_regular_price();
        $productNode['vat'] = $taxper;
        //$productNode['QuantityInStock'] = $product->get_stock_quantity();
        $productNode['unit'] = 'unit';
        //$productNode['ArticleNumber'] = $product->get_sku();
        return $productNode;
        //return $this->generate($root, $productNode);
    }
    /**
     * Updates a XML representation of a Product
     *
     * @access public
     * @param mixed $product
     * @return mixed
     */
    public function update($product){		

        //$root = 'Article';
        $productNode = array();
        
        $tax = wc_get_price_including_tax($product) - wc_get_price_excluding_tax($product);
        $taxper = wc_get_price_excluding_tax($product) != 0? round($tax*100/wc_get_price_excluding_tax($product)) : 0;
		if(strpos($product->get_type(), 'variation') !== false && $product->get_parent_id() != 0){
			$attr = wc_get_product_variation_attributes($product->get_id());
			//logthis($attr);
			$attribute = '';
			foreach($attr as $key => $value){
				$attribute .= $value.'-';
			}
			$parentProductTitle = explode('#', $product->get_title());
			$title = $attribute.$parentProductTitle[1];
			$productNode['title'] = (strlen($title) > 40) ? substr($title,0,36).'...' : $title;
		}else{
			 $productNode['title'] = (strlen($product->get_title()) > 40) ? substr($product->get_title(),0,36).'...' : $product->get_title();
		}
		$productDesc = strip_tags(get_post($product->get_id())->post_content);
        $productNode['description'] = (strlen($productDesc) > 200) ? substr($productDesc,0,196).'...' : $productDesc;
        $productNode['price'] = $product->get_price_excluding_tax() ? round($product->get_price_excluding_tax(), 2) : $product->get_regular_price();
        $productNode['vat'] = $taxper;
        //$productNode['ArticleNumber'] = $product->get_sku();
        //$productNode['QuantityInStock'] = $product->get_stock_quantity();
        $productNode['unit'] = 'unit';
        return $productNode;
        //return $this->generate($root, $productNode);
    }

    /**
     * Creates a XML representation of a Product
     *
     * @access public
     * @param mixed $product
     * @return mixed
     */
    public function create_price($product){

        $root = 'Price';
        $options = get_option('woocommerce_billogram_general_settings');
        $price = array();

        if(!isset($meta['pricelist_id'])){
            $price['PriceList'] = 'A';
        }
        else{
            $price['PriceList'] = $meta['pricelist_id'];
        }
        if($options['activate-vat'] == 'on'){
            $price['Price'] = wc_get_price_including_tax($product) ? wc_get_price_including_tax($product) : $product->get_regular_price();
            logthis('YES');
        }
        else{
            $price['Price'] = wc_get_price_including_tax($product) ? wc_get_price_including_tax($product) : $product->get_regular_price();
            logthis('NO');
        }
        $price['ArticleNumber'] = $product->get_sku();
        $price['FromQuantity'] = 1;

        return $this->generate($root, $price);
    }

    /**
     * Creates a XML representation of a Product
     *
     * @access public
     * @param mixed $product
     * @return mixed
     */
    public function update_price($product){

        $root = 'Price';
        $price = array();

        $options = get_option('woocommerce_billogram_general_settings');
        if($options['activate-vat'] == 'on'){
            $price['Price'] = wc_get_price_including_tax($product) ? wc_get_price_including_tax($product) : $product->get_regular_price();
            logthis('YES');
        }
        else{
            $price['Price'] = wc_get_price_including_tax($product) ? wc_get_price_including_tax($product) : $product->get_regular_price();
            logthis('NO');
        }

        return $this->generate($root, $price);
    }
	
	
	/**
	* Create product meta key value in well formated text, added in 2.1
	* @access public
	* @param mixed $order
	* @return formated product meta key, value text.
	*/
	public function get_product_meta($metadata){
		//logthis($metadata);
		$attribute = array();
		foreach ( $metadata as $meta_key => $meta_value ) {
			// Skip hidden core fields
			//logthis('item meta:');
			//logthis($meta_value);
			if ( in_array( $meta_key, apply_filters( 'woocommerce_hidden_order_itemmeta', array(
				'_qty',
				'_tax_class',
				'_product_id',
				'_variation_id',
				'_line_subtotal',
				'_line_subtotal_tax',
				'_line_total',
				'_line_tax',
				'_line_tax_data',
			) ) ) ) {
				continue;
			}
	
			// Skip serialised meta
			if ( is_serialized( $meta_value ) ) {
				continue;
			}
	
			// Get attribute data
			if ( taxonomy_exists( wc_sanitize_taxonomy_name( $meta_key ) ) ) {
				$term               = get_term_by( 'slug', $meta_value[0], wc_sanitize_taxonomy_name( $meta_key ) );
				$meta_key   = wc_attribute_label( wc_sanitize_taxonomy_name( $meta_key ) );
				$meta_value[0] = isset( $term->name ) ? $term->name : $meta_value[0];
			} else {
				$meta_key   = apply_filters( 'woocommerce_attribute_label', wc_attribute_label( $meta_key, $_product ), $meta_key );
			}
	
			array_push($attribute, ' '.$meta_key . ': ' . $meta_value[0]);
		}
		//logthis('attribute');
		//logthis(explode(',', $attribute));
		return implode(',', $attribute);
	}
}