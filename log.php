<?php
    // this file is used to record arbitrary log data from the user flow
    
    require('rezgo/include/page_header.php');
    
    $rezgo = new RezgoSite('secure');

    $stripped_request = array();
    foreach ($_REQUEST as $key => $value) {
        // only log vars
        if (strpos($key, '||log')){
            $replace = array('||log');
            $key = str_replace($replace, '', $key);
            $stripped_request[$key] = $value;
        }
    }
    // remove extra WP request vars
    foreach ($stripped_request as $key => $value) {
        if ($key == 'pagename' || $key == 'mode'){
            unset($stripped_request[$key]);
        }
    }
    
    $rezgo->log([
        'type' => $stripped_request['type'],
        'source' => $stripped_request['source'],
        'id' => $stripped_request['id'],
        'action' => $stripped_request['action'],
        'short' => $stripped_request['short'],
        'long' => $stripped_request['long']
    ]);
    