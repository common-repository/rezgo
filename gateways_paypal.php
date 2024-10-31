<?php

    require('rezgo/include/page_header.php');
    $site = new RezgoSite();
    $company = $site->getCompanyDetails();
	$amount = sanitize_text_field($_REQUEST['amount']);
        
    $res = $site->getPublicPayment($amount, null, 'paypal_get_order');

    echo json_encode(['url' => (string)$res['url'], 'checkout' => (string)$res['checkout']]);
