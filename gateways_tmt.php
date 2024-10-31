<?php
    
    // this script handles generating payment keys from the public API path for TMT
    
    require('rezgo/include/page_header.php');
    $site = new RezgoSite();
    $company = $site->getCompanyDetails();

	if (REZGO_CAPTCHA_PRIV_KEY) {

		$recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';
		$recaptcha_secret = REZGO_CAPTCHA_PRIV_KEY;
		$recaptcha_response = sanitize_text_field($_REQUEST['recaptcha_response']);
		$recaptcha_threshold = 0.6;
		
		$recaptcha = file_get_contents($recaptcha_url . '?secret=' . $recaptcha_secret . '&response=' . $recaptcha_response);
		$recaptcha = json_decode($recaptcha, 1);
        
		if ($recaptcha['score'] >= $recaptcha_threshold || $recaptcha['error-codes'][0] == 'browser-error') {
            
            $res = $site->getPublicPayment($_REQUEST['amount']);
            echo json_encode($res);
            
		} else {

			// we don't need to log the recaptcha string
			unset($_POST['recaptcha_response']);
			$log_data = json_encode($_POST);

			$site->log([
				'cid' => REZGO_CID, 
				'date' => date('Y-m-d H:i:s'),
				'type' => 'Wordpress TMT',
				'action' => 'failed recaptcha',
				'short' => json_encode($recaptcha),
				'long' => $log_data,
			]);
			
		}

	} else {

		$res = $site->getPublicPayment($_REQUEST['amount']);
		echo json_encode($res);

	}
    