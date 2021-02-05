jQuery(function(){

    var getHost = window.location.protocol + "//" + window.location.host;
    
		
	jQuery('.startpayment').click(function(){
		
		var data1 =  { phone:jQuery("#phone_number").val(), amount: jQuery('.total_to_pay').val(), pay:'start', orderId: jQuery('.my_order_id').val() };
		jQuery.post(the_validate_link.ajaxurl, data1, function(msg) {
		
				console.log(msg);
				var obj = JSON.parse(msg);
				//alert(obj.ProcessCheckoutResponse.return.CheckoutRequestID);
				switch(obj.success)
				{
					case true:
						jQuery('.return-message-paybill').removeClass('pay_false').addClass('pay_true').html(obj.message);
						return PolCallback(obj.m_id,obj.c_id);
						break;

					case false:
						jQuery('.return-message-paybill').removeClass('pay_true').addClass('pay_'+obj.success).html(obj.message);
						return false;
						break;

					default:
						jQuery('.return-message-paybill').removeClass('pay_true').addClass('pay_false').html('An error ocurred');
						return false;
				}


			

		});
	});

       

    function PolCallback(m_id,c_id)
    {
        var myVar;
        var data3 = { m_id: m_id, c_id:c_id,pay:'poll' }
        myVar = setInterval(function(){
            jQuery.post(the_validate_link.ajaxurl, data3, function(msg) {
            
                console.log(msg);
                var obj = JSON.parse(msg);
                    jQuery('.return-message-paybill').removeClass('pay_true').addClass('pay_false').html(obj.message);
                    if(obj.success == true)
                    {
                        if(obj.code == 'O'){
                            jQuery('.return-message-paybill').removeClass('pay_false').addClass('pay_true').html(obj.message);
							// jQuery('#flexSuccess').show();
							window.location.href = getHost + '/checkout/order-received/'+ jQuery('.my_order_id').val() +'/?key=' + jQuery('.my_order_key').val();
                        }
                        clearInterval(myVar);
                    }

            });
        }, 3000);
    }



});