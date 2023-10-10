<?php

require dirname(__FILE__) . '/config/config.inc.php';

include dirname(__FILE__) . '/modules/newsmanapp/lib/Client.php';

include dirname(__FILE__) . '/init.php';

// @ini_set('display_errors', 'on');
// @error_reporting(E_ALL | E_STRICT);

$_userId = Configuration::get('NEWSMAN_USER_ID');
$_apikey = Configuration::get('NEWSMAN_API_KEY');
$_mapping = Configuration::get('NEWSMAN_MAPPING');

if (empty($_userId) || empty($_apikey) || empty($_mapping)) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode('Newsman Plugin setup incomplete');
    exit;
}

$apikey = empty(Tools::getValue('apikey'))
    ? ''
    : Tools::getValue('apikey');
$newsman = empty(Tools::getValue('newsman'))
    ? ''
    : Tools::getValue('newsman');
$start =
    !empty(Tools::getValue('start')) && Tools::getValue('start') >= 0
        ? Tools::getValue('start')
        : 1;
$limit = empty(Tools::getValue('limit'))
    ? 1000
    : Tools::getValue('limit');

if (!is_numeric($start) || !is_numeric($limit)) {
    http_response_code(403);
    header('Content-Type: application/json');
    $status = [
        'status' => 'start & limit must be numeric',
    ];
    echo json_encode($status);
    exit;
}

$cronLast = empty(Tools::getValue('cronlast'))
    ? ''
    : Tools::getValue('cronlast');
if (!empty($cronLast)) {
    $cronLast = $cronLast == 'true' ? true : false;
}
$startLimit = '';
$order_id = empty(Tools::getValue('order_id'))
    ? ''
    : Tools::getValue('order_id');
$product_id = empty(Tools::getValue('product_id'))
    ? ''
    : Tools::getValue('product_id');
$debug = empty(Tools::getValue('debug'))
    ? ''
    : Tools::getValue('debug');
if (!empty($debug)) {
    $debug = $debug == 'true' ? true : false;
}

if (!empty($start) && $start >= 0 && !empty($limit)) {
    $startLimit .= " LIMIT {$limit} OFFSET {$start}";
}

if (
    (!empty($newsman) && !empty($apikey)) ||
    $newsman == 'getCart.json'
) {
    $apikey = '';
    $currApiKey = $_apikey;

    if ($newsman != 'getCart.json') {
        $apikey = Tools::getValue('apikey');

        if ($apikey != $currApiKey) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(403);
            exit;
        }
    }

    switch (Tools::getValue('newsman')) {
        case 'getCart.json':
            $cart = Context::getContext()->cart->getProducts();

            $prod = [];

            foreach ($cart as $cart_item) {
                $prod[] = [
                    'id' => $cart_item['id_product'],
                    'name' => $cart_item['name'],
                    'price' => $cart_item['price'],
                    'quantity' => $cart_item['cart_quantity'],
                ];
            }

            header('Content-Type: application/json');
            echo json_encode($prod, JSON_PRETTY_PRINT);
            exit;
        case 'orders.json':
            $ordersObj = [];

            if (!empty($order_id)) {
                $startLimit = null;
            }

            $orders = Db::getInstance()->executeS(
                'SELECT * FROM ' .
                    _DB_PREFIX_ .
                    'orders' .
                    " WHERE id_order='" . pSQL($order_id) . "'" .
                    $startLimit
            );

            foreach ($orders as $item) {
                $dbq = new DbQuery();
                $q = $dbq
                    ->select('*')
                    ->from('order_detail', 'c')
                    ->where('id_order=' . $item['id_order']);

                $products = Db::getInstance()->executeS($q->build());
                $productsJson = [];

                $dbq = new DbQuery();
                $q = $dbq
                    ->select('*')
                    ->from('customer', 'c')
                    ->where('id_customer=' . $item['id_customer']);
                $cust = Db::getInstance()->executeS($q->build());

                foreach ($cust as $_cust) {
                    $cust = $_cust;
                }

                foreach ($products as $prod) {
                    if ($prod['product_id'] == 0) {
                        continue;
                    }

                    $url = Context::getContext()->link->getProductLink(
                        $prod['product_id']
                    );

                    $link = new Link();
                    $product_url = $link->getProductLink(
                        $prod['product_id']
                    );

                    $image = Product::getCover($prod['product_id']);
                    $image = new Image($image['id_image']);
                    $image_url =
                        _PS_BASE_URL_ .
                        _THEME_PROD_DIR_ .
                        $image->getExistingImgPath() .
                        '.jpg';

                        //new image scrapping
                        $link = new Link();
                        $image_url = 'https://' . $link->getImageLink($prod['link_rewrite'], $imageC['id_image'], 'home_default');

                    $qty = Product::getQuantity($prod['product_id']);

                    $_prod = new Product((int) $prod['product_id']);
                    $price = $_prod->getPrice();

                    $price_old = $_prod->getPrice();

                    $discount = 0;
                    $reduction_type = null;

                    $reductions = Db::getInstance()->executeS(
                        '
                    SELECT reduction, reduction_type
                    FROM `' .
                            _DB_PREFIX_ .
                            'specific_price`
                    WHERE id_product = ' .
                            $prod['id_product'] .
                            ''
                    );

                    foreach ($reductions as $reduction) {
                        $discount = $reduction['reduction'];
                        $reduction_type = $reduction['reduction_type'];
                    }

                    if ($reduction_type == 'percentage') {
                        $discount = (int) substr($discount, 2, 2);
                        $discount = 100 / (100 - $discount);
                        $price_old = $discount * $price_old;
                    } elseif ($reduction_type == 'amount') {
                        $price_old = $price_old + $discount;
                    } else {
                        $price_old = 0;
                    }

                    if ($price_old == $price) {
                        $price_old = 0;
                    }

                    $productsJson[] = [
                        'id' => $prod['product_id'],
                        'name' => $prod['name'],
                        'stock_quantity' => $qty,
                        'price' => $price,
                        'price_old' => $price_old,
                        'image_url' => $image_url,
                        'url' => $url,
                    ];
                }

                $date = new DateTime($item['date_add']);
                $date = $date->getTimestamp();

                $status = '';

                switch ($item['current_state']) {
                    case '0':
                        $status = 'Awaiting bank wire payment';
                        break;
                    case '1':
                        $status =
                            'Awaiting Cash On Delivery validation';
                        break;
                    case '2':
                        $status = 'Awaiting check payment';
                        break;
                    case '3':
                        $status = 'Canceled';
                        break;
                    case '4':
                        $status = 'Delivered';
                        break;
                    case '5':
                        $status = 'On backorder (not paid)';
                        break;
                    case '6':
                        $status = 'On backorder (paid)';
                        break;
                    case '7':
                        $status = 'Payment accepted';
                        break;
                    case '8':
                        $status = 'Payment error';
                        break;
                    case '9':
                        $status = 'Processing in progress';
                        break;
                    case '10':
                        $status = 'Refunded';
                        break;
                    case '11':
                        $status = 'Remote payment accepted';
                        break;
                    case '12':
                        $status = 'Shipped';
                        break;
                }

                $ordersObj[] = [
                    'order_no' => $item['id_order'],
                    'date' => $date,
                    'status' => $status,
                    'lastname' => $cust['firstname'],
                    'firstname' => $cust['firstname'],
                    'email' => $cust['email'],
                    'phone' => '',
                    'state' => '',
                    'city' => '',
                    'address' => '',
                    'discount' => '',
                    'discount_code' => '',
                    'shipping' => '',
                    'fees' => 0,
                    'rebates' => 0,
                    'total' => (float) $item['total_paid'],
                    'products' => $productsJson,
                ];
            }

            header('Content-Type: application/json');
            echo json_encode($ordersObj, JSON_PRETTY_PRINT);
            exit;

        case 'products.json':
            $dbq = new DbQuery();
            $q = null;

                    if (!empty($product_id)) {
                        $startLimit = null;

                        $q =
                            'SELECT * FROM `' .
                            _DB_PREFIX_ .
                            'product` c INNER JOIN `' .
                            _DB_PREFIX_ .
                            'product_lang` `cg` ON cg.id_product=c.id_product ' .
                            'WHERE c.id_product=' . pSQL($product_id) .
                            '  AND cg.name!=\'\' AND active=\'1\'';
                    } else {
                        $q =
                            'SELECT * FROM `' .
                            _DB_PREFIX_ .
                            'product` c LEFT JOIN `' .
                            _DB_PREFIX_ .
                            'product_lang` `cg` ON cg.id_product=c.id_product WHERE cg.name!=\'\' AND active=\'1\'' .
                            $startLimit;
                    }

            $products = Db::getInstance()->executeS($q);
            $productsJson = [];
            $productsDuplicatedId = [];

            foreach ($products as $prod) {
                if (
                    in_array($prod['id_product'], $productsDuplicatedId)
                ) {
                    continue;
                }

                $productsDuplicatedId[] = $prod['id_product'];

                $url = Context::getContext()->link->getProductLink(
                    $prod['id_product']
                );

                $link = new Link();
                $product_url = $link->getProductLink(
                    $prod['id_product']
                );

                $image = Product::getCover($prod['id_product']);
                $image = new Image($image['id_image']);
                $image_url =
                    _PS_BASE_URL_ .
                    _THEME_PROD_DIR_ .
                    $image->getExistingImgPath() .
                    '.jpg';

                $qty = Product::getQuantity($prod['id_product']);

                $_prod = new Product((int) $prod['id_product']);
                $price = $_prod->getPrice();
                $price = number_format($price, 2);

                $price_old = $_prod->getPrice();
                $price_old = number_format($price_old, 2);

                $discount = 0;
                $reduction_type = null;

                $reductions = Db::getInstance()->executeS(
                    '
                SELECT reduction, reduction_type
                FROM `' .
                        _DB_PREFIX_ .
                        'specific_price`
                WHERE id_product = ' .
                        $prod['id_product'] .
                        ''
                );

                foreach ($reductions as $reduction) {
                    $discount = $reduction['reduction'];
                    $reduction_type = $reduction['reduction_type'];
                }

                if ($reduction_type == 'percentage') {
                    $discount = (int) substr($discount, 2, 2);
                    $discount = 100 / (100 - $discount);
                    $price_old = $discount * $price_old;
                } elseif ($reduction_type == 'amount') {
                    $price_old = $price_old + $discount;
                } else {
                    $price_old = 0;
                }

                if ($price_old == $price) {
                    $price_old = 0;
                }

                $productsJson[] = [
                    'id' => $prod['id_product'],
                    'name' => $prod['name'],
                    'stock_quantity' => $qty,
                    'price' => $price,
                    'price_old' => $price_old,
                    'image_url' => $image_url,
                    'url' => $url,
                ];

                if (!empty($product_id)) {
                    break;
                }
            }

            header('Content-Type: application/json');
            echo json_encode($productsJson, JSON_PRETTY_PRINT);
            exit;

        case 'customers.json':
            $q =
                'SELECT * FROM ' .
                _DB_PREFIX_ .
                'customer WHERE newsletter=1' .
                $startLimit;

            $wp_cust = Db::getInstance()->executeS($q);

            $custs = [];

            foreach ($wp_cust as $users) {
                $custs[] = [
                    'email' => $users['email'],
                    'firstname' => $users['firstname'],
                    'lastname' => $users['lastname'],
                ];
            }

            header('Content-Type: application/json');
            echo json_encode($custs, JSON_PRETTY_PRINT);
            exit;

        case 'subscribers.json':
            $q =
                'SELECT * FROM ' .
                _DB_PREFIX_ .
                'newsletter WHERE active=1' .
                $startLimit;

            $wp_subscribers = Db::getInstance()->executeS($q);

            $subs = [];

            foreach ($wp_subscribers as $users) {
                $subs[] = [
                    'email' => $users['email'],
                    'firstname' => $users['firstname'],
                    'lastname' => $users['lastname'],
                ];
            }

            header('Content-Type: application/json');
            echo json_encode($subs, JSON_PRETTY_PRINT);
            exit;

        case 'count.json':
            $q =
                'SELECT COUNT(*) FROM ' .
                _DB_PREFIX_ .
                'customer WHERE newsletter=1';

            $data = Db::getInstance()->executeS($q);
            $cNewsletter = $data[0]['COUNT(*)'];

            $q =
                'SELECT COUNT(*) FROM ' .
                _DB_PREFIX_ .
                'newsletter WHERE active=1';

            $data = Db::getInstance()->executeS($q);
            $newsletter = $data[0]['COUNT(*)'];

            $json = [
                'customers_newsletter' => $cNewsletter,
                'newsletter' => $newsletter,
            ];

            header('Content-Type: application/json');
            echo json_encode($json, JSON_PRETTY_PRINT);
            exit;

        case 'version.json':
            $version = [
                'version' => 'Prestashop ' . _PS_VERSION_,
            ];

            header('Content-Type: application/json');
            echo json_encode($version, JSON_PRETTY_PRINT);
            exit;

        case 'cron.json':
            $cron = Tools::getValue('cron');

            if (empty($cron)) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode('Empty cron method');
                exit;
            }

            $batchSize = 9000;

            $_mapping = [json_decode($_mapping)];
            $list_id = $_mapping[0]->list;
            $segment_id = !empty($_mapping[0]->segment)
                ? [$_mapping[0]->segment]
                : null;

            $client = new Newsman_Client($_userId, $apikey);

            switch ($cron) {
                case 'newsletter':
                    try {
                        if ($cronLast) {
                            // Get latest
                            $q =
                                'SELECT COUNT(*) FROM ' .
                                _DB_PREFIX_ .
                                'newsletter WHERE active=1';

                            $data = Db::getInstance()->executeS($q);
                            $newsletter = $data[0]['COUNT(*)'];

                            $start = $newsletter - $limit;

                            if ($start < 1) {
                                $start = 0;
                            }

                            $startLimit = " LIMIT {$limit} OFFSET {$start}";
                        }

                        $q =
                            'SELECT * FROM ' .
                            _DB_PREFIX_ .
                            'newsletter WHERE active=1' .
                            $startLimit;

                        $wp_subscribers = Db::getInstance()->executeS(
                            $q
                        );

                        $subs = [];

                        if (!empty($wp_subscribers)) {
                            foreach ($wp_subscribers as $users) {
                                $subs[] = [
                                    'email' => $users['email'],
                                    'firstname' => $users['firstname'],
                                    'lastname' => $users['lastname'],
                                ];

                                if (count($subs) % $batchSize == 0) {
                                    _importData(
                                        $subs,
                                        $list_id,
                                        $client,
                                        'CRON Sync prestashop ' .
                                            _PS_VERSION_ .
                                            ' newsletter active',
                                        'newsletter',
                                        $segment_id
                                    );
                                }
                            }

                            if (count($subs) > 0) {
                                _importData(
                                    $subs,
                                    $list_id,
                                    $client,
                                    'CRON Sync prestashop ' .
                                        _PS_VERSION_ .
                                        ' newsletter active',
                                    'newsletter',
                                    $segment_id
                                );
                            }
                        }

                        // emailsubscription

                        if ($cronLast) {
                            // Get latest
                            $q =
                                'SELECT COUNT(*) FROM ' .
                                _DB_PREFIX_ .
                                'emailsubscription WHERE active=1';

                            $data = Db::getInstance()->executeS($q);
                            $newsletter = $data[0]['COUNT(*)'];

                            $start = $newsletter - $limit;

                            if ($start < 1) {
                                $start = 0;
                            }

                            $startLimit = " LIMIT {$limit} OFFSET {$start}";
                        }

                        $q =
                            'SELECT * FROM ' .
                            _DB_PREFIX_ .
                            'emailsubscription WHERE active=1' .
                            $startLimit;

                        $wp_subscribers = Db::getInstance()->executeS(
                            $q
                        );

                        if (!empty($wp_subscribers)) {
                            $subs = [];

                            foreach ($wp_subscribers as $users) {
                                $subs[] = [
                                    'email' => $users['email'],
                                ];

                                if (count($subs) % $batchSize == 0) {
                                    _importData(
                                        $subs,
                                        $list_id,
                                        $client,
                                        'CRON Sync prestashop ' .
                                            _PS_VERSION_ .
                                            ' newsletter active',
                                        'newsletter',
                                        $segment_id
                                    );
                                }
                            }

                            if (count($subs) > 0) {
                                _importData(
                                    $subs,
                                    $list_id,
                                    $client,
                                    'CRON Sync prestashop ' .
                                        _PS_VERSION_ .
                                        ' newsletter active',
                                    'newsletter',
                                    $segment_id
                                );
                            }
                        }

                        // emailsubscription

                        $status = [
                            'status' => 'success',
                        ];

                        header('Content-Type: application/json');
                        echo json_encode($status, JSON_PRETTY_PRINT);
                        exit;
                    } catch (Exception $e) {
                        $status = [
                            'status' => _DB_PREFIX_ .
                                'newsletter or emailsubscription table doesn\'t exist, successfully imported',
                        ];

                        header('Content-Type: application/json');
                        echo json_encode($status, JSON_PRETTY_PRINT);
                        exit;
                    }

                case 'customers_newsletter':
                    if ($cronLast) {
                        // Get latest
                        $q =
                            'SELECT COUNT(*) FROM ' .
                            _DB_PREFIX_ .
                            'customer WHERE newsletter=1';

                        $data = Db::getInstance()->executeS($q);
                        $cNewsletter = $data[0]['COUNT(*)'];

                        $start = $cNewsletter - $limit;

                        if ($start < 1) {
                            $start = 1;
                        }

                        $startLimit = " LIMIT {$limit} OFFSET {$start}";
                    }

                    $q =
                        'SELECT * FROM ' .
                        _DB_PREFIX_ .
                        'customer WHERE newsletter=1' .
                        $startLimit;

                    $wp_cust = Db::getInstance()->executeS($q);

                    $custs = [];

                    foreach ($wp_cust as $users) {
                        $custs[] = [
                            'email' => $users['email'],
                            'firstname' => $users['firstname'],
                            'lastname' => $users['lastname'],
                        ];

                        if (count($custs) % $batchSize == 0) {
                            _importData(
                                $custs,
                                $list_id,
                                $client,
                                'CRON Sync prestashop ' .
                                    _PS_VERSION_ .
                                    ' customers with newsletter',
                                'customers_newsletter',
                                $segment_id
                            );
                        }
                    }

                    if (count($custs) > 0) {
                        _importData(
                            $custs,
                            $list_id,
                            $client,
                            'CRON Sync prestashop ' .
                                _PS_VERSION_ .
                                ' customers with newsletter',
                            'customers_newsletter',
                            $segment_id
                        );
                    }

                    $status = [
                        'status' => 'success',
                    ];

                    header('Content-Type: application/json');
                    echo json_encode($status, JSON_PRETTY_PRINT);
                    exit;
            }

            $status = [
                'status' => 'method does not exist',
            ];

            header('Content-Type: application/json');
            echo json_encode($status, JSON_PRETTY_PRINT);
            exit;
    }
} else {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(403);
    exit;
}

 function safeForCsvCRON($str)
    {
        return '"' . str_replace('"', '""', $str) . '"';
    }

    function _importData(
        &$data,
        $list,
        $client,
        $source,
        $type,
        $segments = null
    ) {
        $csv = '';

        switch ($type) {
            case 'newsletter':
                $csv = '"email","firstname","lastname","source"' . PHP_EOL;

                break;

            case 'customers_newsletter':
                $csv = '"email","firstname","lastname","source"' . PHP_EOL;

                break;
        }

        foreach ($data as $_dat) {
            $csv .= sprintf(
                '%s,%s,%s,%s',
                safeForCsvCRON($_dat['email']),
                safeForCsvCRON($_dat['firstname']),
                safeForCsvCRON($_dat['lastname']),
                safeForCsvCRON($source)
            );
            $csv .= PHP_EOL;
        }

        $ret = null;
        try {
            if (is_array($segments) && count($segments) > 0) {
                $ret = $client->import->csv($list, $segments, $csv);
            } else {
                $ret = $client->import->csv($list, [], $csv);
            }

            if ($ret == '') {
                throw new Exception('Import failed');
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        $data = [];
    }
