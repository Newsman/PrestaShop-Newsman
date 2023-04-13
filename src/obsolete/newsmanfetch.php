<?php

    require(dirname(__FILE__) . '/config/config.inc.php');

    include(dirname(__FILE__) . '/modules/newsman/lib/Client.php');

    include(dirname(__FILE__).'/init.php');

    //@ini_set('display_errors', 'on');
    //@error_reporting(E_ALL | E_STRICT);

    $_userId = Configuration::get('NEWSMAN_USER_ID');
    $_apikey = Configuration::get('NEWSMAN_API_KEY');
    $_mapping = Configuration::get('NEWSMAN_MAPPING');

    if(empty($_userId) || empty($_apikey) || empty($_mapping)){
        http_response_code(403);
        header('Content-Type: application/json');
        echo Tools::jsonEncode("Newsman Plugin setup incomplete");
        return;
    }

    $apikey = (empty($_GET["apikey"])) ? "" : $_GET["apikey"];
    $newsman = (empty($_GET["newsman"])) ? "" : $_GET["newsman"];
    $start = (!empty($_GET["start"]) && $_GET["start"] >= 0) ? $_GET["start"] : 1;
    $limit = (empty($_GET["limit"])) ? 1000 : $_GET["limit"];

    if(!is_numeric($start) || !is_numeric($limit))
    {
        http_response_code(403);
        header('Content-Type: application/json');
        $status = array(
            "status" => "start & limit must be numeric"
        );
        echo json_encode($status);
        return;
    }

    $cronLast = (empty($_GET["cronlast"])) ? "" : $_GET["cronlast"];
    if(!empty($cronLast))
        $cronLast = ($cronLast == "true") ? true : false;
    $startLimit = "";
    $order_id = (empty($_GET["order_id"])) ? "" : " WHERE id_order='" . $_GET["order_id"] . "'";
    $product_id = (empty($_GET["product_id"])) ? "" : $_GET["product_id"];
    $debug = (empty($_GET["debug"])) ? "" : $_GET["debug"];
    if(!empty($debug))
        $debug = ($debug == "true") ? true : false;

    if(!empty($start) && $start >= 0 && !empty($limit))
        $startLimit .= " LIMIT {$limit} OFFSET {$start}";

    if (!empty($newsman) && !empty($apikey) || $newsman == "getCart.json") {
        $apikey = "";
        $currApiKey = $_apikey;

        if($newsman != "getCart.json")
        {
            $apikey = $_GET["apikey"];

            if ($apikey != $currApiKey) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo Tools::jsonEncode(403);
                return;
            }
        }

        switch ($_GET["newsman"]) {
            case "getCart.json":

                //if ((bool)$_POST["post"] == true) {                       

                    $cart = Context::getContext()->cart->getProducts();                                           
                                
                    $prod = array();

                    foreach ( $cart as $cart_item ) {                                 
                   
                            $prod[] = array(
                                "id" => $cart_item["id_product"],
                                "name" => $cart_item["name"],
                                "price" => $cart_item["price"],						
                                "quantity" => $cart_item["cart_quantity"]
                            );							
                                                
                        }		                        

                        header('Content-Type: application/json');
                        echo json_encode($prod, JSON_PRETTY_PRINT);  
                        exit;
                    return;
                //}

            break;
            case "orders.json":

                $ordersObj = array();   

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

                    $q = 'SELECT * FROM `' . _DB_PREFIX_ . 'product` c LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` `cg` ON cg.id_product=c.id_product WHERE c.id_product=' . $product_id . '';               
                }
                else{                
                    $q = 'SELECT * FROM `' . _DB_PREFIX_ . 'product` c LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` `cg` ON cg.id_product=c.id_product ' . $startLimit;
                }
                
                $products = Db::getInstance()->executeS($q);
                $productsJson = array();     
		$productsDuplicatedId = array();

                foreach ($products as $prod) {
        
		    if(in_array($prod["id_product"], $productsDuplicatedId))
			continue;

       		    $productsDuplicatedId[] = $prod["id_product"];

                    $url = Context::getContext()->link->getProductLink($prod["id_product"]);                       

                    $link = new Link();
                    $product_url = $link->getProductLink($prod["id_product"]);

                    $image = Product::getCover($prod["id_product"]);
                    $image = new Image($image['id_image']);
                    $image_url = _PS_BASE_URL_._THEME_PROD_DIR_.$image->getExistingImgPath().".jpg";	                  

                    $qty = Product::getQuantity($prod["id_product"]);								                              

                    $_prod = new Product((int)$prod["id_product"]);
                    $price = $_prod->getPrice();			
                    $price = number_format($price, 2);	
                    
                    $price_old = $_prod->getPrice();
                    $price_old = number_format($price_old, 2);
                                
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

                    if(!empty($product_id))
                    {
			break;
		    }				

                }

                header('Content-Type: application/json');
                echo json_encode($productsJson, JSON_PRETTY_PRINT);
                return;

                break;

            case "customers.json":

                $q = 'SELECT * FROM ' . _DB_PREFIX_ . 'customer WHERE newsletter=1' . $startLimit;

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

                $q = 'SELECT * FROM ' . _DB_PREFIX_ . 'newsletter WHERE active=1' . $startLimit;

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
                
                $q = 'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'customer WHERE newsletter=1';

                $data = Db::getInstance()->executeS($q);            
                $cNewsletter = $data[0]["COUNT(*)"];      

                $q = 'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'newsletter WHERE active=1';

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
                           
                $version = array(
                "version" => "Prestashop " . _PS_VERSION_
                );               

                header('Content-Type: application/json');
                echo json_encode($version, JSON_PRETTY_PRINT);            

            break;

            case "cron.json":
            
            $cron = $_GET["cron"];

            if(empty($cron))
            {
                http_response_code(403);
                header('Content-Type: application/json');
                echo Tools::jsonEncode("Empty cron method");
                return;
            }

            $batchSize = 9000;
                    
            $_mapping = array(json_decode($_mapping));
            $list_id = $_mapping[0]->list;
            $segment_id = !empty($_mapping[0]->segment) ? array($_mapping[0]->segment) : null;

            $client = new Newsman_Client($_userId, $apikey);

            switch($cron){
                
                    case "newsletter":
                
                    try {
                    
                        if($cronLast)
                        {
                            //Get latest
                            $q = 'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'newsletter WHERE active=1';

                            $data = Db::getInstance()->executeS($q);            
                            $newsletter = $data[0]["COUNT(*)"];

                            $start = $newsletter - $limit;

                            if($start < 1)
                            {
                                $start = 0;
                            }                    

                            $startLimit = " LIMIT {$limit} OFFSET {$start}";                   
                        }
                        
                        $q = 'SELECT * FROM ' . _DB_PREFIX_ . 'newsletter WHERE active=1' . $startLimit;
                
                        $wp_subscribers = Db::getInstance()->executeS($q);

                        $subs = array();
            
                        if(!empty($wp_subscribers))
                        {
                            die('not empty');
                            foreach ($wp_subscribers as $users) {
                                $subs[] = array(
                                    "email" => $users["email"],
                                    "firstname" => $users["firstname"],
                                    "lastname" => $users["lastname"]
                                );

                                if ((count($subs) % $batchSize) == 0) {
                                    _importData($subs, $list_id, $segment_id, $client, "CRON Sync prestashop " . _PS_VERSION_ . " newsletter active", "newsletter");
                                }
                                
                            }

                            if (count($subs) > 0) {
                                _importData($subs, $list_id, $segment_id, $client, "CRON Sync prestashop " . _PS_VERSION_ . " newsletter active", "newsletter");
                            }
                        }

                        //emailsubscription

                        if($cronLast)
                        {
                            //Get latest
                            $q = 'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'emailsubscription WHERE active=1';

                            $data = Db::getInstance()->executeS($q);            
                            $newsletter = $data[0]["COUNT(*)"];

                            $start = $newsletter - $limit;

                            if($start < 1)
                            {
                                $start = 0;
                            }                    

                            $startLimit = " LIMIT {$limit} OFFSET {$start}";                   
                        }
                        
                        $q = 'SELECT * FROM ' . _DB_PREFIX_ . 'emailsubscription WHERE active=1' . $startLimit;
              
                        $wp_subscribers = Db::getInstance()->executeS($q);

                        if(!empty($wp_subscribers))
                        {
                            $subs = array();
                
                            foreach ($wp_subscribers as $users) {
                                $subs[] = array(
                                    "email" => $users["email"]
                                );

                                if ((count($subs) % $batchSize) == 0) {
                                    _importData($subs, $list_id, $segment_id, $client, "CRON Sync prestashop " . _PS_VERSION_ . " newsletter active", "newsletter");
                                }
                                
                            }

                            if (count($subs) > 0) {
                                _importData($subs, $list_id, $segment_id, $client, "CRON Sync prestashop " . _PS_VERSION_ . " newsletter active", "newsletter");
                            }
                        }

                        //emailsubscription
                
                        $status = array(
                            "status" => "success"
                            );
                
                        header('Content-Type: application/json');
                        echo json_encode($status, JSON_PRETTY_PRINT); 
                        return;
                        
                    }
                    catch(Exception $e)
                    {
                        $status = array(
                           "status" => _DB_PREFIX_ . 'newsletter or emailsubscription table doesn\'t exist, successfully imported'   
                        );
                        
                        header('Content-Type: application/json');
                        echo json_encode($status, JSON_PRETTY_PRINT); 
                        return;
                    }

                    break;

                    case "customers_newsletter":

                        if($cronLast)
                        {
                            //Get latest                    
                            $q = 'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'customer WHERE newsletter=1';

                            $data = Db::getInstance()->executeS($q);            
                            $cNewsletter = $data[0]["COUNT(*)"];   

                            $start = $cNewsletter - $limit;

                            if($start < 1)
                            {
                                $start = 1;
                            }                    
            
                            $startLimit = " LIMIT {$limit} OFFSET {$start}";
                        }                                                

                        $q = 'SELECT * FROM ' . _DB_PREFIX_ . 'customer WHERE newsletter=1' . $startLimit;              

                        $wp_cust = Db::getInstance()->executeS($q);
                        
                        $custs = array();
            
                        foreach ($wp_cust as $users) {
            
                            $custs[] = array(
                                "email" => $users["email"],
                                "firstname" => $users["firstname"],
                                "lastname" => $users["lastname"]
                            );

                            if ((count($custs) % $batchSize) == 0) {
                            _importData($custs, $list_id, $segment_id, $client, "CRON Sync prestashop " . _PS_VERSION_ . " customers with newsletter", "customers_newsletter");
                            }
                            
                        }                
                    
                        if (count($custs) > 0) {
                            _importData($custs, $list_id, $segment_id, $client, "CRON Sync prestashop " . _PS_VERSION_ . " customers with newsletter", "customers_newsletter");
                        }                                       

                        $status = array(
                            "status" => "success"
                            );
                
                        header('Content-Type: application/json');
                        echo json_encode($status, JSON_PRETTY_PRINT); 
                        return;

                    break;

            }

            $status = array(
                "status" => "method does not exist"
                );

            header('Content-Type: application/json');
            echo json_encode($status, JSON_PRETTY_PRINT); 
            return;

            break;
        }
    } else {
        http_response_code(403);
        header('Content-Type: application/json');
        echo Tools::jsonEncode(403);
    }

    function safeForCsvCRON($str)
    {
        return '"' . str_replace('"', '""', $str) . '"';
    }

    function _importData(&$data, $list, $segments = null, $client, $source, $type)
    {
        $csv = "";

        switch($type)
        {
            case "newsletter":

                $csv = '"email","firstname","lastname","source"' . PHP_EOL;

            break;

            case "customers_newsletter":

                $csv = '"email","firstname","lastname","source"' . PHP_EOL;

            break;

        }

        foreach ($data as $_dat) {
            $csv .= sprintf(
                "%s,%s,%s,%s",
                safeForCsvCRON($_dat["email"]),
                safeForCsvCRON($_dat["firstname"]),
                safeForCsvCRON($_dat["lastname"]),
                safeForCsvCRON($source)
            );
            $csv .= PHP_EOL;
        }

        $ret = null;
        try {
            if (is_array($segments) && count($segments) > 0) {
                $ret = $client->import->csv($list, $segments, $csv);
            } else {
                $ret = $client->import->csv($list, array(), $csv);    
            }

            if ($ret == "") {
                throw new Exception("Import failed");
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        $data = array();
    }
