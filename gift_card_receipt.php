<?php 
	// This is the gift card page
	require('rezgo/include/page_header.php');

	// start a new instance of RezgoSite
	$site = new RezgoSite();

	if (isset($_REQUEST['parent_url'])) {
		$site->base = '/' . $site->requestStr('parent_url');
	}

	// only show once after purchase
	if (!$_REQUEST['card'] || $_REQUEST['card'] == '') {
		$site->sendTo($site->base."/gift-card");
	}
?>

<?php echo $site->getTemplate('frame_header'); ?>

<?php echo $site->getTemplate('gift_card_receipt'); ?>

<?php echo $site->getTemplate('frame_footer'); ?>