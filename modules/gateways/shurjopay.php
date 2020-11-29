<?php
/**
 * WHMCS Sample Payment Gateway Module
 *
 * Shurjopay Payment Gateway modules allow you to integrate payment solutions with the
 * WHMCS platform.
 *
 * This sample file demonstrates how a payment gateway module for WHMCS should
 * be structured and all supported functionality it can contain.
 *
 * Within the module itself, all functions must be prefixed with the module
 * filename, followed by an underscore, and then the function name. For this
 * example file, the filename is "gatewaymodule" and therefore all functions
 * begin "shurjopay_".
 *
 * If your module or third party API does not support a given function, you
 * should not define that function within your module. Only the _config
 * function is required.
 *
 * For more information, please refer to the online documentation.
 *
 * @see https://developers.whmcs.com/payment-gateways/
 *
 * @copyright Copyright (c) WHMCS Limited 2017
 * @license http://www.whmcs.com/license/ WHMCS Eula
 */

	if (!defined("WHMCS")) {
		die("This file cannot be accessed directly");
	}
	

/**
 * Define module related meta data.
 *
 * Values returned here are used to determine module related capabilities and
 * settings.
 *
 * @see https://developers.whmcs.com/payment-gateways/meta-data-params/
 *
 * @return array
 */
	function shurjopay_MetaData()
	{
		return array(
			'DisplayName' => 'ShurjoPay',
			'APIVersion' => '1.1', // Use API Version 1.1
			'DisableLocalCredtCardInput' => true,
			'TokenisedStorage' => false,
		);
	}

/**
 * Define gateway configuration options.
 *
 * The fields you define here determine the configuration options that are
 * presented to administrator users when activating and configuring your
 * payment gateway module for use.
 *
 * Supported field types include:
 * * text
 * * password
 * * yesno
 * * dropdown
 * * radio
 * * textarea
 *
 * Examples of each field type and their possible configuration parameters are
 * provided in the sample function below.
 *
 * @return array
 */
	function shurjopay_config()
	{
		return array(
			// the friendly display name for a payment gateway should be
			// defined here for backwards compatibility
			'FriendlyName' => array(
				'Type' => 'System',
				'Value' => 'ShurjoPay',
			),
			// a text field type allows for single line text input
			'merchantName' => array(
				'FriendlyName' => 'Merchant Name/ID',
				'Type' => 'text',
				'Size' => '25',
				'Default' => '',
				'Description' => 'Enter your merchant ID here',
			),
			// a password field type allows for masked text input
			'merchantPassword' => array(
				'FriendlyName' => 'Merchant Password',
				'Type' => 'password',
				'Size' => '25',
				'Default' => '',
				'Description' => 'Enter Merchant Password here',
			),
			'uniqeID' => array(
				'FriendlyName' => 'Unique ID',
				'Type' => 'text',
				'Size' => '25',
				'Default' => '',
				'Description' => 'Enter your unique id prefix here',
			),
			'returnUrl' => array(
				'FriendlyName' => 'Merchant Return/Callback Url',
				'Type' => 'text',
				'Size' => '25',
				'Default' => '',
				'Description' => 'Enter your Return/Callback Url here',
			),
			'merchantIP' => array(
				'FriendlyName' => 'Merchant IP',
				'Type' => 'text',
				'Size' => '25',
				'Default' => '',
				'Description' => 'Enter your merchant ip here',
			),
			// the yesno field type displays a single checkbox option
			'testMode' => array(
				'FriendlyName' => 'Test Mode',
				'Type' => 'yesno',
				'Description' => 'Tick to enable test mode',
			),

		);
	}

/**
 * Payment link.
 *
 * Required by third party payment gateway modules only.
 *
 * Defines the HTML output displayed on an invoice. Typically consists of an
 * HTML form that will take the user to the payment gateway endpoint.
 *
 * @param array $params Payment Gateway Module Parameters
 *
 * @see https://developers.whmcs.com/payment-gateways/third-party-gateway/
 *
 * @return string
 */
	function shurjopay_link($params)
	{
		// Gateway Configuration Parameters
		$merchantName = $params['merchantName'];
		$merchantPassword = $params['merchantPassword'];
		$isLive = $params['testMode'];    
		$uniqeID = $params['uniqeID'];
		$returnUrl = $params['returnUrl'];
		$merchantIP = get_client_ip();
		
		 // System Parameters
		$companyName = $params['companyname'];
		$systemUrl = $params['systemurl'];
		$returnUrl = $params['returnurl'];
		$langPayNow = $params['langpaynow'];
		$moduleDisplayName = $params['name'];
		$moduleName = $params['paymentmethod'];
		$whmcsVersion = $params['whmcsVersion'];
		

		// Invoice Parameters
		$invoiceId = $params['invoiceid'];
		$description = $params["description"];
		$amount = $params['amount'];
		$currencyCode = $params['currency'];
		$returnUrl          = $systemUrl . 'viewinvoice.php?id=' . $invoiceId;
		$callback_url = $systemUrl . 'modules/gateways/callback/' . $moduleName . '.php';
		$postfields['return_url'] = $returnUrl;
			
		$uniq_transaction_key=$uniqeID.time().'_'.$invoiceId;

		$form_data = 'spdata=<?xml version="1.0" encoding="utf-8"?>
						<shurjoPay><merchantName>'.$merchantName.'</merchantName>
						<merchantPass>'.$merchantPassword.'</merchantPass>
						<userIP>'.$merchantIP.'</userIP>
						<uniqID>'.$uniq_transaction_key.'</uniqID>
						<totalAmount>'.$amount.'</totalAmount>
						<paymentOption>shurjopay</paymentOption>
						<returnURL>'.$callback_url.'</returnURL></shurjoPay>';


		// check if pay now button pressed				
		if( isset($_POST['spdata']) && !empty($_POST['spdata']))
		{
			curl_request_post($isLive, $form_data);
			exit;
		}

        $code = '';
		$code ='<form method="post" action="">';
		$code.="<input type='hidden' name='spdata' value='".$form_data."' />";
		$code.='<input class="btn btn-default" type="submit" value="Pay Now" />';
		$code.='</form>';
		
		return $code;


	}

	// cURL requesting to shurjopay gateway
	function curl_request_post($isLive,$payload) 
	{
	   
		$ch = curl_init();
		if($isLive == 'on')
		{
			$url = "https://shurjotest.com/sp-data.php";
		}
		else
		{
			$url = "https://shurjopay.com/sp-data.php";
		}
		
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_POST, 1);
		curl_setopt($ch,CURLOPT_POSTFIELDS,$payload);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch,CURLOPT_CONNECTTIMEOUT ,5);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);	
		$response = curl_exec($ch);
		curl_close ($ch);
		echo $response;		
	    
	}

	function get_client_ip()
	{
	    $ipaddress = '';
	    if (isset($_SERVER['HTTP_CLIENT_IP']))
	        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
	    else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
	        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
	    else if (isset($_SERVER['HTTP_X_FORWARDED']))
	        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
	    else if (isset($_SERVER['HTTP_FORWARDED_FOR']))
	        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
	    else if (isset($_SERVER['HTTP_FORWARDED']))
	        $ipaddress = $_SERVER['HTTP_FORWARDED'];
	    else if (isset($_SERVER['REMOTE_ADDR']))
	        $ipaddress = $_SERVER['REMOTE_ADDR'];
	    else
	        $ipaddress = '127.0.0.1';
	    return $ipaddress;
	}

