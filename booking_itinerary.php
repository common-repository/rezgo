<?php 
	// This is the booking itinerary page
	require('rezgo/include/page_header.php');
	
	// start a new instance of RezgoSite
	$site = new RezgoSite();
	
	// grab and decode the trans_num if it was set
	$trans_num = $site->decode(sanitize_text_field($_REQUEST['trans_num']));

	// send the user home if they shouldn't be here
	if (!$trans_num) {
		$site->sendTo($site->base."/itinerary-not-found");
	}

	// empty the cart
	$site->clearCart();
	
	$site->setMetaTags('<meta name="robots" content="noindex, nofollow">');
?>

<?php echo $site->getTemplate('frame_header'); ?>

<?php echo $site->getTemplate('booking_itinerary'); ?>

<?php echo $site->getTemplate('frame_footer'); ?>