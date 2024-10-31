<?php 
	// This script handles the booking requests made via ajax by book.php
	require('rezgo/include/page_header.php');

	// start a new instance of RezgoSite
	$site = new RezgoSite('secure');

	// save form data
	if ($_POST['rezgoAction'] == 'giftCardPayment'){
		session_start();
		unset($_SESSION['gift-card']);
		
		$key_value = [];
		$data = explode('&', urldecode($_POST['formData']));
		foreach ($data as $k => $v) {
			$key_value[] = explode('=', $v);
		}
		foreach ($key_value as $k => $v) {
			$_SESSION['gift-card'][$v[0]] = $v[1];
		}
	}

	// return total amount due in correct currency format
	if ($_POST['rezgoAction'] == 'formatCurrency'){
		$result = (object)[];

		$company = $site->getCompanyDetails();
		$amount = sanitize_text_field($_POST['amount']);
		$result = $site->formatCurrency($amount, $company);
		
		echo $result;
	}
	
	// return line items to calculate estimated total
	if ($_POST['rezgoAction'] == 'getOverallTotal'){

		$total = sanitize_text_field((float)$_POST['total']);
		$pax_obj = array_map('sanitize_text_field', $_POST['pax_obj']);
		$pax_request = '';

		foreach ($pax_obj as $pax => $amt) {
			if ($amt > 0) {
				$pax_request .= ('&'.$pax.'='.$amt);
			}
		}
		
		$gift_item = $site->getTours('t=uid&q='.$_POST['option'].'&d='.$_POST['date'].$pax_request);
		if ((float)$gift_item[0]->sub_total != 0) {
			$overall_total = $gift_item[0]->overall_total;
		} else {
			$overall_total = 0;
		}
		echo $overall_total;
	}

	$recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';
	$recaptcha_secret = REZGO_CAPTCHA_PRIV_KEY;
	$recaptcha_response = sanitize_text_field($_POST['recaptcha_response'] ?? '');
	$recaptcha_threshold = 0.75;

	// Verify captcha on 2nd Step
	if ( $_POST['rezgoAction'] == 'addGiftCard' &&
		 $_SERVER['REQUEST_METHOD'] === 'POST' &&
		 REZGO_CAPTCHA_PRIV_KEY
		) {

		// Make and decode POST request:
		$options = array(
			'timeout'     => '45',
			'redirection' => '30',
			'sslverify'   => false,
			'data_format' => 'body',
		);

		$recaptcha = wp_remote_get($recaptcha_url . '?secret=' . $recaptcha_secret . '&response=' . $recaptcha_response, $options);
		$recaptcha = json_decode($recaptcha['body']);

		$result = (object)[];
		$result->response = array();

		// Take action based on the score returned, or if a payment ID was sent
        // this is needed so the SCA validation can re-submit this request
		if ($recaptcha->score >= $recaptcha_threshold || $_POST['payment_id']) {

			if ($_POST['rezgoAction'] == 'addGiftCard') {

				// if honeypot field is filled
				if ( $_POST['rezgo_confirm_id'] ) {
					// This is a bot. Simulate purchase
					
					// randomly pick from array
					$random_card = FAKE_GIFT_CARDS[array_rand(FAKE_GIFT_CARDS)];

					$result->response = 1;
					$result->card = $random_card;
					$json = json_encode((array)$result); 
					echo '|||' . $json;
					return;
				} 
				else {
					//proceed as usual
					$result = $site->sendGiftOrder($_POST);
				}

				if ($result->status == 'Card created') {
					session_start();
					$result->card = $site->encode($result->card);

					$result->response = 1;
				}
				else {
                    
                    if($result->sca_required) {
    
                        $result->response = 8;
                        $result->message = '3DS verification is needed to continue';
                        $result->url = (string) $result->sca_url;
                        $result->post = (string) $result->sca_post;
                        $result->pass = (string) $result->sca_pass;
                        $result->direct = (string) $result->sca_direct;
                        
                    } else {

                        // this booking failed, send a status code back to the requesting page
                        if($result->message == 'Availability Error' || $result->mesage == 'Fatal Error') {
                            $result->response = 2;
                        } elseif($result->message == 'Payment Declined' || $result->message == 'Invalid Card Checksum' || $result->message == 'Invalid Card Expiry') {
                            $result->response = 3;
                        } elseif($result->message == 'Account Error') {
                            // hard system error, no commit requests are allowed if there is no valid payment method
                            $result->response = 5;
                        } else {
                            $result->response = 4;
                        }
    
                    }
					               
				}

				$json = json_encode((array)$result); 
				echo '|||' . $json;
			}
		} 
		else {
			// fail recaptcha 
			$result->response = 6;
			$json = json_encode((array)$result); 
			echo '|||' . $json;
		}
	}
	else if (!REZGO_CAPTCHA_PRIV_KEY) {

		if ($_POST['rezgoAction'] == 'addGiftCard') {

			// if honeypot field is filled
			if ( $_POST['rezgo_confirm_id'] ) {
				// This is a bot. Simulate purchase

				// randomly pick from array
				$random_card = FAKE_GIFT_CARDS[array_rand(FAKE_GIFT_CARDS)];

				$result->response = 1;
				$result->card = $random_card;

				$json = json_encode((array)$result); 
				echo '|||' . $json;
				return;
			} 
			else {
				//proceed as usual
				$result = $site->sendGiftOrder($_POST);
			}

			if ($result->status == 'Card created') {
				session_start();
				$result->card = $site->encode($result->card);

				$result->response = 1;
			}
			else {

				if($result->sca_required) {
    
					$result->response = 8;
					$result->message = '3DS verification is needed to continue';
					$result->url = (string) $result->sca_url;
					$result->post = (string) $result->sca_post;
					$result->pass = (string) $result->sca_pass;
					$result->direct = (string) $result->sca_direct;
                        
                } else {
                        
					// this booking failed, send a status code back to the requesting page
					if($result->message == 'Availability Error' || $result->mesage == 'Fatal Error') {
					    $result->response = 2;
					} elseif($result->message == 'Payment Declined' || $result->message == 'Invalid Card Checksum' || $result->message == 'Invalid Card Expiry') {
					    $result->response = 3;
					} elseif($result->message == 'Account Error') {
					    // hard system error, no commit requests are allowed if there is no valid payment method
					    $result->response = 5;
					} else {
					    $result->response = 4;
					}
    
                }
			}

			$json = json_encode((array)$result); 
			echo '|||' . $json;
		}
	}

	if ($_POST['rezgoAction'] == 'getGiftCard') {
		$result = $site->getGiftCard($_POST['gcNum']);

        if (isset($result->card)) {
			$result->card->status = 1;
			$result->card->number = $site->cardFormat($result->card->number);
		} 
		else {
			$result->card->status = 0;
		}

		echo '|||' . json_encode($result->card);
	}
?>