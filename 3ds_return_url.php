<?php 
	require('rezgo/include/page_header.php'); ?>
<!DOCTYPE html>
<html>
    <head>
        <title>Stripe 3DS View</title>
    </head>
    <body>

        <script>
			let url = window.location.protocol + '//' + window.location.hostname + '/' + decodeURIComponent( '<?php echo rawurlencode( (string) $_REQUEST['wp_slug'] ); ?>/' );
			fetch('<?php echo rawurlencode( (string) $_REQUEST['wp_slug'] ); ?>/log?type=stripe&action=' + escape('returned from 3DS CHALLENGE 2') + '&source=<?php echo $_REQUEST['stripe_trace']; ?>&short=' + escape(url));
			parent.parent.postMessage('3DS-authentication-complete', url);
        </script>

    </body>
</html>