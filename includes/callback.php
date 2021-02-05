<?php
$datac = file_get_contents('php://input');

include_once '../../../../wp-load.php';
global $wpdb;
$tablename = $wpdb->prefix.'flexycode_mpesa';

//$data = '{"Body":{"stkCallback":{"MerchantRequestID":"18143-3310100-1","CheckoutRequestID":"ws_CO_DMZ_494039711_24052019235340366","ResultCode":0,"ResultDesc":"The service request is processed successfully.","CallbackMetadata":{"Item":[{"Name":"Amount","Value":1.00},{"Name":"MpesaReceiptNumber","Value":"ND21XOFU1N"},{"Name":"Balance"},{"Name":"TransactionDate","Value":20190402222243},{"Name":"PhoneNumber","Value":254720108418}]}}}}';
file_put_contents('monitor.log', $datac . "\n", FILE_APPEND);

$data = json_decode($datac,true);

$ResultCode = $data["Body"]["stkCallback"]["ResultCode"];
$ResultDesc = $data["Body"]["stkCallback"]["ResultDesc"];
$MerchantRequestID = $data["Body"]["stkCallback"]["MerchantRequestID"];
$CheckoutRequestID = $data["Body"]["stkCallback"]["CheckoutRequestID"];



$wpdb->update($tablename,array('result_code'=> $ResultCode,'result_desc'=>$ResultDesc),
	array('checkout_request_id'=>$CheckoutRequestID),
	array( '%s', '%s'),
	array('%s'));
		



if($ResultCode == 0){
	$items = $data["Body"]["stkCallback"]["CallbackMetadata"]['Item'];

	if(is_array($items))
	{
		$receipt = $amount = $Balance = $TransactionDate = $PhoneNumber = null;
		foreach($items as $item)
		{
			$item['Name'] == 'Amount' ? isset($item['Value']) ? $amount = $item['Value'] : '' : '';
			$item['Name'] == 'MpesaReceiptNumber' ? isset($item['Value']) ? $receipt = $item['Value'] : '' : '';
			$item['Name'] == 'Balance' ? isset($item['Value']) ? $Balance = $item['Value'] : '' : '';
			$item['Name'] == 'TransactionDate' ? isset($item['Value']) ? $TransactionDate = $item['Value'] : '' : '';
			$item['Name'] == 'PhoneNumber' ? isset($item['Value']) ? $PhoneNumber = $item['Value'] : '' : '';
        }
        
        $wpdb->update($tablename,array('trans_time'=> date('Y-m-d H:i:s',strtotime($TransactionDate)),'trans_id'=>$receipt, 'trans_phone' => $PhoneNumber),
            array('checkout_request_id'=>$CheckoutRequestID),
            array( '%s', '%s', '%s'),
			array('%s'));
		        
        //  $tablename2 = $wpdb->prefix.'mpesa_payments';
		//  $sdata = array(
		// 	'TransID'=>$receipt,
		// 	'TransTime'=>date('Y-m-d H:i:s',strtotime($TransactionDate)),
		// 	'TransAmount'=>$amount,
		// 	'MSISDN'=>$PhoneNumber,
		// 	'ThirdPartyTransID'=>$CheckoutRequestID,
		// 	'BillRefNumber'=>$MerchantRequestID,
		// 	'card_id'=>'1', 
		// 	'stk_id' => '-', 
		// 	'referal_status'=>'Pending'
		// );
		
		// $wpdb->insert( $tablename2,$sdata ,
		// 			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ) 
		// 		);

	}

}



?>
