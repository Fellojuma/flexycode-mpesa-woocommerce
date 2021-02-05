<?php 
require('../../../../wp-load.php');
if(isset($_POST['pay'])){

    session_start();
    // echo json_encode(['success'=>false,'message'=>'An error occured. Please try again later']);
    // exit();

    $pay = $_POST['pay'];
	if($pay=='start'){
        // $url = 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
        $url = $_SESSION['token_url'];
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        $key =$_SESSION['mpesa_consumer_key'];
        $secret = $_SESSION['mpesa_consumer_secret'];
        $credentials = base64_encode($key.':'.$secret);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Basic '.$credentials)); //setting a custom header
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $curl_response = curl_exec($curl);
        $data = json_decode($curl_response,true);
        curl_close($curl);
        //var_dump($curl_response);
        $token =  $data['access_token'];
		
		//$token = '';
        // $url = 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
        $url = $_SESSION['stk_url'];
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json','Authorization:Bearer '.$token)); //setting custom header

        $passkey = $_SESSION['mpesa_passkey'];
        $paybill = $_SESSION['mpesa_shortcode'];

        $phone = trim($_POST['phone']);
        //$student = $_POST['student'];
        //$member = Members::findOrFail($student);
        $phone = '254'.substr($phone,-9);

        date_default_timezone_set('Africa/Nairobi');
        $date = new \DateTime('now');
        $date->setTimezone(new \DateTimeZone('UTC'));
        $str_server_now = $date->format('YmdHis');
        date_default_timezone_set('UTC');
        $timestamp =  $str_server_now;
        
        // $amount = $_POST['amount'];
        $order_id = $_POST['orderId'];
        $order = new WC_Order ( $order_id );
        $amount = (int)$order->order_total;


        $curl_post_data = array(
            //Fill in the request parameters with valid values
            'BusinessShortCode' => $paybill,
            'Password' => base64_encode($paybill.$passkey.$timestamp),
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            //'Amount' => $_POST['amount'],
            'Amount' => $amount,
            //'Amount' => 1,
            'PartyA' => $phone,
            'PartyB' => $paybill,
            'PhoneNumber' => $phone,
            'CallBackURL' => plugins_url('/flexycode-mpesa-woocommerce/includes/callback.php'),
            'AccountReference' => $order_id,
            'TransactionDesc' => 'Flexycode Woocommerce'
        );

        $data_string = json_encode($curl_post_data);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);

        $curl_response = curl_exec($curl);

        //dd($curl_response);

        $json = json_decode($curl_response,true);
        if(isset($json['errorCode']))
        {
            echo json_encode(['success'=>false,'message'=>$json['errorMessage']]);
			exit();
        }
        elseif(isset($json['ResponseCode'])){
            
				global $wpdb;
				 $tablename = $wpdb->prefix.'flexycode_mpesa';
				 $sdata = array(
						'order_id' => $order_id, 
						'merchant_request_id' => $json['MerchantRequestID'],
						'checkout_request_id' => $json['CheckoutRequestID'], 
						'transaction_time' => date('Y-m-d H:i:s'),
						'phone_number' => $phone);
						
				$wpdb->insert( $tablename,$sdata ,
					array( '%s', '%s', '%s', '%s','%s' ) 
				);
			
			echo json_encode(['success'=>true,'message'=>$json['CustomerMessage'],'m_id'=>$json['MerchantRequestID'],'c_id' =>$json['CheckoutRequestID']]);
			exit();
        }
        else{
            echo json_encode(['success'=>false,'message'=>'An error occured. Please try again later ']);
			exit();
        }
		
	}
	elseif($pay=='poll'){
		$MerchantRequestID = $_POST['m_id'];
        $CheckoutRequestID = $_POST['c_id'];
		global $wpdb;
		$tablename = $wpdb->prefix.'flexycode_mpesa';
		$rowcount = $wpdb->get_results("SELECT id,result_code,result_desc FROM ".$tablename." WHERE merchant_request_id = '".$MerchantRequestID."' AND checkout_request_id='".$CheckoutRequestID."'");
			
		//var_dump($rowcount);	
        //if($mpesa->ResultCode!= null)
        $data = [];
		if(count($rowcount)>0 && !empty($rowcount{0}->result_desc))
        {
            $rowcount{0}->result_code == 0 ? $code = 'O' : $code = $rowcount{0}->result_code;
            $message = $rowcount{0}->result_desc;
            $success = true;
            $order = new WC_Order($rowcount{0}->order_id);
            if (!empty($order)) {
                $order->update_status( 'processing' );
            }
            
        }
        else{
            $message = 'Waiting for transaction. Check your phone and input your M-PESA pin';
            $success = false;
            $code = null;
        }
        echo json_encode(['success'=>$success,'message'=>$message,'code'=>$code,'data'=>$data]);
		exit();
	}

}

// function successData($id,$wpdb){

//     $data = [];    
//     $tablename = $wpdb->prefix.'flexycode_mpesa';
// 	$rowcount = $wpdb->get_results("SELECT * FROM ".$tablename." WHERE  id='".$id."'");
// 	if(count($rowcount)>0 && !empty($rowcount{0}->order_id))
//     {
//         // $data['firstname'] = ucfirst($rowcount{0}->firstname);
//         // $data['uni'] = $rowcount{0}->uni_id.' '.$rowcount{0}->camp_id;
//         $order = new WC_Order($rowcount{0}->order_id);

// 		if (!empty($order)) {
// 			$order->update_status( 'processing' );
// 		}
//     }
//     // return $data;
    
// }