<?php
    
    // this script handles generating payment intent IDs based on the stripe secret key
    
    require('rezgo/include/page_header.php');
    
    $site = new RezgoSite();
    $company = $site->getCompanyDetails();
	$amount = sanitize_text_field($_REQUEST['amount']);

    $stripe_amount = round($amount, 2) * 100;

    if ($_REQUEST['rezgoAction'] == 'stripe_create') {
    
        $res = $site->getPublicPayment($amount, ['stripe_action' => 'create']);
    
        echo json_encode($res);
    }
    
    // update if gift card is applied/removed
    if ($_REQUEST['rezgoAction'] == 'stripe_update_total') {
    
        $payment_id = sanitize_text_field($_REQUEST['payment_id']) ?? '';
    
        $res = $site->getPublicPayment($amount, ['stripe_action' => 'update', 'payment_id' => $payment_id]);
        
        echo json_encode($res);
    }
    