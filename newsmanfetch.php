<?php

require(dirname(__FILE__) . '/config/config.inc.php');

include(dirname(__FILE__).'/init.php');

//@ini_set('display_errors', 'on');
//@error_reporting(E_ALL | E_STRICT);

$_apikey = Configuration::get('NEWSMAN_API_KEY');

$apikey = (empty($_GET["apikey"])) ? "" : $_GET["apikey"];
$newsman = (empty($_GET["newsman"])) ? "" : $_GET["newsman"];
$start = (!empty($_GET["start"]) && $_GET["start"] >= 0) ? $_GET["start"] : 0;
$limit = (empty($_GET["limit"])) ? 10 : $_GET["limit"];
$startLimit = "";
$order_id = (empty($_GET["order_id"])) ? "" : " WHERE id_order='" . $_GET["order_id"] . "'";
$product_id = (empty($_GET["product_id"])) ? "" : $_GET["product_id"];
$debug = (empty($_GET["debug"])) ? "" : $_GET["debug"];
if(!empty($debug))
	$debug = ($debug == "true") ? true : false;

if(!empty($start) && $start >= 0 && !empty($limit))
    $startLimit .= " LIMIT {$limit} OFFSET {$start}";

if (!empty($newsman) && !empty($apikey)) {
    $apikey = $_GET["apikey"];
    $currApiKey = $_apikey;

    if ($apikey != $currApiKey) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo Tools::jsonEncode(403);
        return;
    }

    switch ($_GET["newsman"]) {
        case "orders.json":

            $ordersObj = array();

            /*$dbq = new DbQuery();
            $q = $dbq->select('*')
                ->from('orders', 'c');           

            $orders = Db::getInstance()->executeS($q->build());*/

            if(!empty($order_id))
                $startLimit = null;

            $orders = Db::getInstance()->executeS('SELECT * FROM '._DB_PREFIX_.'orders' . $order_id . $startLimit);

            foreach ($orders as $item) {
                $dbq = new DbQuery();
                $q = $dbq->select('*')
                    ->from('order_detail', 'c')
                    ->where('id_order=' . $item["id_order"]);

                $products = Db::getInstance()->executeS($q->build());
                $productsJson = array();

                $dbq = new DbQuery();
                $q = $dbq->select('*')
                    ->from('customer', 'c')
                    ->where('id_customer=' . $item["id_customer"]);
                $cust = Db::getInstance()->executeS($q->build());

                foreach ($cust as $_cust) {
                    $cust = $_cust;
                }

                foreach ($products as $prod) {
				
					if($prod["product_id"] == 0)
						continue;
					
                   /*
                   $url = Context::getContext()->link->getProductLink($prod["product_id"]);                       

                    $link = new Link();
                    $product_url = $link->getProductLink($prod["product_id"]);
                    $image = Product::getCover($prod["product_id"]);
                    $image = new Image($image['id_image']);
                    $image_url = _PS_BASE_URL_._THEME_PROD_DIR_.$image->getExistingImgPath().".jpg";	
					
                    $productsJson[] = array(
                        "id" => $prod['product_id'],
                        "name" => $prod['product_name'],
                        "stock_quantity" => (int)$prod['product_quantity'],
                        "price" => (float)$prod['product_price'],
                        "price_old" => 0,
                        "image_url" => $image_url,
                        "url" => $url

                    );
                    */

                    $url = Context::getContext()->link->getProductLink($prod["product_id"]);                       

                    $link = new Link();
                    $product_url = $link->getProductLink($prod["product_id"]);
    
                    $image = Product::getCover($prod["product_id"]);
                    $image = new Image($image['id_image']);
                    $image_url = _PS_BASE_URL_._THEME_PROD_DIR_.$image->getExistingImgPath().".jpg";	                  
    
                    $qty = Product::getQuantity($prod["product_id"]);								                              
    
                    $_prod = new Product((int)$prod["product_id"]);
                    $price = $_prod->getPrice();
                    
                    $price_old = $_prod->getPrice();
                                 
                    $discount = 0;
    
                    $reductions = DB::getInstance()->executeS('
                    SELECT reduction
                    FROM `'._DB_PREFIX_.'specific_price`
                    WHERE id_product = '.$prod["product_id"].''
                    );
            
                    foreach($reductions as $reduction){                
                        $discount = $reduction['reduction'];
                    }
                    
                    $discount = (int)substr($discount, 2, 2);
                    $discount = 100 / (100 - $discount);                				
                    $price_old = $discount * $price_old;		
                    
                    if($price_old == $price)
                    {
                        $price_old = 0;
                    }
                    
                    $productsJson[] = array(
                        "id" => $prod["product_id"],
                        "name" => $prod["name"],
                        "stock_quantity" => $qty,
                        "price" => $price,
                        "price_old" => $price_old,
                        "image_url" => $image_url,
                        "url" => $url
                    );

                }

                $date = new DateTime($item["date_add"]);
                $date = $date->getTimestamp(); 

                $status = "";

                switch($item["current_state"]){
                    case "0":
                        $status = "Awaiting bank wire payment";
                    break;
                    case "1":
                        $status = "Awaiting Cash On Delivery validation";
                    break;
                    case "2":
                        $status = "Awaiting check payment";
                    break;
                    case "3":
                        $status = "Canceled";
                    break;
                    case "4":
                        $status = "Delivered";
                    break;
                    case "5":
                        $status = "On backorder (not paid)";
                    break;
                    case "6":
                        $status = "On backorder (paid)";
                    break;
                    case "7":
                        $status = "Payment accepted";
                    break;
                    case "8":
                        $status = "Payment error";
                    break;
                    case "9":
                        $status = "Processing in progress";
                    break;
                    case "10":
                        $status = "Refunded";
                    break;
                    case "11":
                        $status = "Remote payment accepted";
                    break;
                    case "12":
                        $status = "Shipped";
                    break;
                }
               
                $ordersObj[] = array(
                    "order_no" => $item["id_order"],
                    "date" => $date,
                    "status" => $status,
                    "lastname" => $cust["firstname"],
                    "firstname" => $cust["firstname"],
                    "email" => $cust["email"],
                    "phone" => "",
                    "state" => "",
                    "city" => "",
                    "address" => "",
                    "discount" => "",
                    "discount_code" => "",
                    "shipping" => "",
                    "fees" => 0,
                    "rebates" => 0,
                    "total" => (float)$item["total_paid"],
                    "products" => $productsJson
                );
            }

            header('Content-Type: application/json');
            echo json_encode($ordersObj, JSON_PRETTY_PRINT);
            return;

            break;

        case "products.json":

            $dbq = new DbQuery();
            $q = null;

            if(!empty($product_id))
            {
                $startLimit = null;

                $q = 'SELECT * FROM ' . _DB_PREFIX_ . 'product c LEFT JOIN `ps_product_lang` `cg` ON cg.id_product=c.id_product WHERE c.id_product=' . $product_id . '';

                /*
                $q = $dbq->select('*')
                    ->from('product', 'c')
                    ->where('id_product=' . $product_id)
                    ->leftJoin('product_lang', 'cg', 'cg.id_product=c.id_product');
                */               
            }
            else{                
                /*
                $q = $dbq->select('*')
                ->from('product', 'c')                
                ->leftJoin('product_lang', 'cg', 'cg.id_product=c.id_product');
                */

                $q = 'SELECT * FROM ' . _DB_PREFIX_ . 'product c LEFT JOIN `ps_product_lang` `cg` ON cg.id_product=c.id_product ' . $startLimit;
            }
            
            $products = Db::getInstance()->executeS($q);
            $productsJson = array();     

            foreach ($products as $prod) {
      
                $url = Context::getContext()->link->getProductLink($prod["id_product"]);                       

                $link = new Link();
                $product_url = $link->getProductLink($prod["id_product"]);

                $image = Product::getCover($prod["id_product"]);
                $image = new Image($image['id_image']);
                $image_url = _PS_BASE_URL_._THEME_PROD_DIR_.$image->getExistingImgPath().".jpg";	                  

                $qty = Product::getQuantity($prod["id_product"]);								                              

                $_prod = new Product((int)$prod["id_product"]);
                $price = $_prod->getPrice();			
				$price = ceil($price);	
				
				$price_old = $_prod->getPrice();
				$price_old = ceil($price_old);
				             
				$discount = 0;
                $reduction_type = null;

                $reductions = DB::getInstance()->executeS('
                SELECT reduction, reduction_type
                FROM `'._DB_PREFIX_.'specific_price`
                WHERE id_product = '.$prod["id_product"].''
                );
        
                foreach($reductions as $reduction){                
                    $discount = $reduction['reduction'];
                    $reduction_type = $reduction["reduction_type"];
                }
                
                if($reduction_type == "percentage")
                {
                    $discount = (int)substr($discount, 2, 2);
                    $discount = 100 / (100 - $discount);                				
                    $price_old = $discount * $price_old;		
                }
                elseif($reduction_type == "amount")
                {
                    $price_old = $price_old + $discount;
                }
                else{
                    $price_old = 0;
                }
				
				if($price_old == $price)
                {
                    $price_old = 0;
                }
				
                $productsJson[] = array(
                    "id" => $prod["id_product"],
                    "name" => $prod["name"],
                    "stock_quantity" => $qty,
                    "price" => $price,
                    "price_old" => $price_old,
                    "image_url" => $image_url,
                    "url" => $url
                );
            }

            header('Content-Type: application/json');
            echo json_encode($productsJson, JSON_PRETTY_PRINT);
            return;

            break;

        case "customers.json":
            
            /*
            $dbq = new DbQuery();
            $q = $dbq->select('*')
                ->from('customer', 'c');
            */

            $q = 'SELECT * FROM ' . _DB_PREFIX_ . 'customer' . $startLimit;

            $wp_cust = Db::getInstance()->executeS($q);

            $custs = array();

            foreach ($wp_cust as $users) {

                $custs[] = array(
                    "email" => $users["email"],
                    "firstname" => $users["firstname"],
                    "lastname" => $users["lastname"]
                );
            }

            header('Content-Type: application/json');
            echo json_encode($custs, JSON_PRETTY_PRINT);
            return;

            break;

        case "subscribers.json":

            /*
            $dbq = new DbQuery();
            $q = $dbq->select('*')
                ->from('newsletter', 'c')
                ->where('active=1');
            */

            $q = 'SELECT * FROM ' . _DB_PREFIX_ . 'newsletter' . $startLimit;

            $wp_subscribers = Db::getInstance()->executeS($q);

            $subs = array();

            foreach ($wp_subscribers as $users) {
                $subs[] = array(
                    "email" => $users["email"],
                    "firstname" => $users["firstname"],
                    "lastname" => $users["lastname"]
                );
            }

            header('Content-Type: application/json');
            echo json_encode($subs, JSON_PRETTY_PRINT);
            return;

            break;

        case "count.json":
            
            $q = 'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'customer WHERE newsletter=1' . $startLimit;

            $data = Db::getInstance()->executeS($q);            
            $cNewsletter = $data[0]["COUNT(*)"];

            $q = 'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'newsletter' . $startLimit;

            $data = Db::getInstance()->executeS($q);            
            $newsletter = $data[0]["COUNT(*)"];

            $json = array(
                "customers_newsletter" => $cNewsletter,
                "newsletter" => $newsletter
            );

            header('Content-Type: application/json');
            echo json_encode($json, JSON_PRETTY_PRINT);     

        break;
        case "version.json":

            header('Content-Type: application/json');
            echo json_encode(_PS_VERSION_, JSON_PRETTY_PRINT);            

        break;
    }
} else {
    http_response_code(403);
    header('Content-Type: application/json');
    echo Tools::jsonEncode(403);
}