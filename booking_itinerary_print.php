<?php 
	// This is the printable version of booking_itinerary.php
	require('rezgo/include/page_header.php');
	
	// start a new instance of RezgoSite
	$site = new RezgoSite();
	
	// grab and decode the trans_num if it was set
	$trans_num = $site->decode(sanitize_text_field($_REQUEST['trans_num']));
	
	// send the user home if they shouldn't be here
	if (!$trans_num) {
		$site->sendTo($site->base."/itinerary-not-found");
	}
	
	$site->setMetaTags('<meta name="robots" content="noindex, nofollow">');
?>

<?php if (strlen($trans_num) == 16) { ?>
	<?php echo $site->getTemplate('booking_itinerary_print.php'); ?>
<?php } ?>
