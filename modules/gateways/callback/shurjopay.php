<?php
    # Required File Includes
    include("../../../init.php");
    include("../../../includes/functions.php");
    include("../../../includes/gatewayfunctions.php");
    include("../../../includes/invoicefunctions.php");
    
    $gatewaymodule = "shurjopay"; # Enter your gateway module name here replacing template

    $GATEWAY = getGatewayVariables($gatewaymodule);

    // var_dump($GATEWAY);exit;
    if (!$GATEWAY["type"]) die("Module Not Activated"); # Checks gateway module is active before accepting callback
    if (!isset($_POST)) die("No Post Data To Validate!");
    

	$response_encrypted = $_POST['spdata'];
    $systemurl = $GATEWAY['systemurl'];
    
    // echo $GATEWAY["testMode"];exit;

	if($response_encrypted != "")
	{
		if ($GATEWAY["testMode"] == "on") 
		{
           
			$shurjopay_decryption_url = 'https://shurjotest.com/merchant/decrypt.php';
			$payment_url = $shurjopay_decryption_url.'?data='.$response_encrypted;
			$ch = curl_init();  
			curl_setopt($ch,CURLOPT_URL,$payment_url);
			curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);    
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			$response_decrypted = curl_exec($ch);
			curl_close ($ch);
	    } 
	    else 
	    {
	        $shurjopay_decryption_url = 'https://shurjopay.com/merchant/decrypt.php';
			$payment_url = $shurjopay_decryption_url.'?data='.$response_encrypted;
			$ch = curl_init();  
			curl_setopt($ch,CURLOPT_URL,$payment_url);
			curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);    
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			$response_decrypted = curl_exec($ch);
			curl_close ($ch);
	    }
	    
		$data = simplexml_load_string($response_decrypted) or die("Error: Cannot create object");

		//Retrieve data returned from payment gateway callback  		
		$transactionId = $returnID = $data->txID;
		$spliteID = explode('_',$returnID);
		$invoiceid = $spliteID[1];
		$bank_tx_id = $data->bankTxID;
		$bank_status = $data->bankTxStatus;
		$sp_code = $data->spCode;
		$sp_code_des = $data->spCodeDes;
		$txnAmount = $data->txnAmount;
		$sp_payment_option = $data->paymentOption;

		$orderData = mysql_fetch_assoc(select_query('tblinvoices', 'total', array("id" => $invoiceid)));
		$order_amount = $orderData['total'];

		if( $data->spCode	 == '000' && ($order_amount == $txnAmount))
		{
			 $status = 'success';
		}	
		else
		{
			 $status = 'failed';
		}
			
		
		$invoiceid = checkCbInvoiceID($invoiceid,$GATEWAY["name"]); # Checks invoice ID is a valid invoice number or ends processing

        $orderStatus = mysql_fetch_assoc(select_query('tblinvoices', 'status', array("id" => $invoiceid)));        
		if($orderStatus['status'] == "Paid")
		{
		    logTransaction($GATEWAY["name"],  array("Gateway Response" => $_POST, "Validation Response" => json_decode($results, true), "Response" => "Already Paid"), "Successful"); # Save to Gateway Log: name, data array, status
		
	        header("Location: ".$systemurl."/clientarea.php?action=services"); /* Redirect browser */	        
		    exit();
		}
		
		checkCbTransID($transactionId); # Checks transaction number isn't already in the database and ends processing if it does
		
		if ($status=="success") 
		{
			$fee = 0;
		    addInvoicePayment($invoiceid, $transactionId, $txnAmount, $fee, $gatewaymodule);
		    logTransaction($GATEWAY["name"], $_POST, "Successful"); # Save to Gateway Log: name, data array, status		 
	        header("Location: ".$systemurl."/viewinvoice.php?id=".$invoiceid); /* Redirect browser */	  
		    exit();
		    
		} 
		else 
		{
		    logTransaction($GATEWAY["name"], $_POST, "Unsuccessful"); # Save to Gateway Log: name, data array, status    
	        header("Location: ".$systemurl."/viewinvoice.php?id=".$invoiceid); /* Redirect browser */	 
		    exit();
		}

	}	
	else
	{
			logTransaction($GATEWAY["name"], $_POST, "Unsuccessful"); # Save to Gateway Log: name, data array, status
	        header("Location: ".$systemurl."/clientarea.php?action=services"); /* Redirect browser */
	    	exit();
	}

 
?>
