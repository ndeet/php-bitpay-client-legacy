<?php
/**
 * Copyright (c) 2014-2015 BitPay
 *
 * 003 - Creating Invoices
 *
 * Requirements:
 *   - Account on https://test.bitpay.com
 *   - Basic PHP Knowledge
 *   - Private and Public keys from 001.php
 *   - Token value obtained from 002.php
 */
require './vendor/autoload.php';
extract($_POST);
$output = [];

$my_apikey = 'Ypv4XdtQ35usY1L7I5aT2CY9tGFDWYyY';

if($my_apikey != $app_api_key):
    $output['status'] = 'error';
    $output['msg'] = "You must send an API key.";
    echo json_encode($output);
    die();
endif;


// See 002.php for explanation
$storageEngine = new \Bitpay\Storage\EncryptedFilesystemStorage('YourTopSecretPassword'); // Password may need to be updated if you changed it
if($_SERVER['HTTP_HOST'] == 'local.bitpayapi.com'):
    $privateKey    = $storageEngine->load('/bitpay_temp/bitpay.pri');
    $publicKey     = $storageEngine->load('/bitpay_temp/bitpay.pub');
else:
    $privateKey    = $storageEngine->load('./bpkey/bitpay.pri');
    $publicKey     = $storageEngine->load('./bpkey/bitpay.pub');
endif;

$client        = new \Bitpay\Client\Client();
$network       = new \Bitpay\Network\Testnet();
//$network       = new \Bitpay\Network\Livenet();
$adapter       = new \Bitpay\Client\Adapter\CurlAdapter();
$client->setPrivateKey($privateKey);
$client->setPublicKey($publicKey);
$client->setNetwork($network);
$client->setAdapter($adapter);
// ---------------------------

/**
 * The last object that must be injected is the token object.
 */
$token = new \Bitpay\Token();
$token->setToken('25kxVACiKAURUhyJeMkYD2'); // UPDATE THIS VALUE

/**
 * Token object is injected into the client
 */
$client->setToken($token);

/**
 * This is where we will start to create an Invoice object, make sure to check
 * the InvoiceInterface for methods that you can use.
 */
$invoice = new \Bitpay\Invoice();

$buyer = new \Bitpay\Buyer();
$buyer
    ->setEmail($buyer_email);

// Add the buyers info to invoice
$invoice->setBuyer($buyer);

/**
 * Item is used to keep track of a few things
 */

$item = new \Bitpay\Item();
$item
    ->setCode($sku)
    ->setDescription($description)
    ->setPrice($price);
$invoice->setItem($item);

/**
 * BitPay supports multiple different currencies. Most shopping cart applications
 * and applications in general have defined set of currencies that can be used.
 * Setting this to one of the supported currencies will create an invoice using
 * the exchange rate for that currency.
 *
 * @see https://test.bitpay.com/bitcoin-exchange-rates for supported currencies
 */
$invoice->setCurrency(new \Bitpay\Currency($currency));

// Configure the rest of the invoice
$invoice
    ->setOrderId(uniqid().'-'.$sku)
    // You will receive IPN's at this URL, should be HTTPS for security purposes!
    ->setNotificationUrl('https://store.example.com/bitpay/callback');


/**
 * Updates invoice with new information such as the invoice id and the URL where
 * a customer can view the invoice.
 */

try {
    $client->createInvoice($invoice);
    $output['status'] = 'success';
    $output['msg'] = 'Creating invoice at BitPay now';
    $output['invoice_id'] = $invoice->getId();
    $output['invoice_url'] = $invoice->getUrl();
    echo json_encode($output);
    
} catch (\Exception $e) {
    #echo "Exception occured: " . $e->getMessage().PHP_EOL;
    $request  = $client->getRequest();
    $response = $client->getResponse();
    #echo (string) $request.PHP_EOL.PHP_EOL.PHP_EOL;
    #echo (string) $response.PHP_EOL.PHP_EOL;
    $output['status'] = 'error';
    $output['msg'] = "Exception occured: " . $e->getMessage();
    exit(1); // We do not want to continue if something went wrong
}