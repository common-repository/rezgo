<?php    
    require('rezgo/include/page_header.php');
    $site = new RezgoSite();
    $company = $site->getCompanyDetails();
    
    if ($_REQUEST['rezgoAction'] == 'square_card_init') {
    
        $res = $site->getPublicPayment($_REQUEST['amount'], ['square_action' => 'square_card_init']);
    
        echo json_encode($res);
        
    }
    
    if ($_REQUEST['rezgoAction'] == 'square_payment') {
        
        $res = $site->getPublicPayment($_REQUEST['amount'], ['square_action' => 'square_payment']);
    
        echo json_encode($res);
        
    }
    