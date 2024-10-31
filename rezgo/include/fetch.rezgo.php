<?php
	// This file handles the fetching of external files (like the Rezgo XML)
	// The input is $url and the output should be returned with $result.

	$method = $post ? 'POST' : 'GET';
	$body = $post ? $post : '';
	
	$options = array(
		'method'     => $method,
		'body'		 => $body,
		'timeout'     => '60',
    	'redirection' => '30',
		'sslverify'   => false,
    	'data_format' => 'body',
	);
	$result = wp_remote_post($url, $options);

	if( !is_wp_error( $result ) ) {
		$result = $result['body'];
	} else {
		return;
	}
?>