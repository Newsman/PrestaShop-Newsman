<?php

require(dirname(__FILE__) . '/config/config.inc.php');

$_apikey = Configuration::get('NEWSMAN_API_KEY');

$apikey = (empty($_GET["apikey"])) ? "" : $_GET["apikey"];
$newsman = (empty($_GET["newsman"])) ? "" : $_GET["newsman"];

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

            $dbq = new DbQuery();
            $q = $dbq->select('*')
                ->from('orders', 'c');

            $ordersObj = array();

            $orders = Db::getInstance()->executeS($q->build());

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
                    $productsJson[] = array(
                        "id" => $prod['product_id'],
                        "name" => $prod['product_name'],
                        "quantity" => $prod['product_quantity'],
                        "price" => $prod['product_price']
                    );
                }

                $ordersObj[] = array(
                    "order_no" => $item["id_order"],
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
                    "total" => $item["total_paid"],
                    "products" => $productsJson
                );
            }

            header('Content-Type: application/json');
            echo json_encode($ordersObj, JSON_PRETTY_PRINT);
            return;

            break;

        case "products.json":

            $dbq = new DbQuery();
            $q = $dbq->select('*')
                ->from('product', 'c')
                ->leftJoin('product_lang', 'cg', 'cg.id_product=c.id_product');

            $products = Db::getInstance()->executeS($q->build());
            $productsJson = array();

            foreach ($products as $prod) {
                $productsJson[] = array(
                    "id" => $prod["id_product"],
                    "name" => $prod["name"],
                    "stock_quantity" => $prod["quantity"],
                    "price" => $prod["price"]
                );
            }

            header('Content-Type: application/json');
            echo json_encode($productsJson, JSON_PRETTY_PRINT);
            return;

            break;

        case "customers.json":
            $dbq = new DbQuery();
            $q = $dbq->select('*')
                ->from('customer', 'c');

            $wp_cust = Db::getInstance()->executeS($q->build());

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

            $dbq = new DbQuery();
            $q = $dbq->select('*')
                ->from('newsletter', 'c')
                ->where('active=1');

            $wp_subscribers = Db::getInstance()->executeS($q->build());

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
    }
} else {
    http_response_code(403);
    header('Content-Type: application/json');
    echo Tools::jsonEncode(403);
}