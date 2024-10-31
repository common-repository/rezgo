<?php 
	// This is the waiver modal
	require('rezgo/include/page_header.php');

	// start a new instance of RezgoSite
	$site = isset($_REQUEST['sec']) ? new RezgoSite('secure') : new RezgoSite();

	if (isset($_REQUEST['parent_url'])) {
		$site->base = '/' . $site->requestStr('parent_url');
	}

	// Page title
	$site->setPageTitle(isset($_REQUEST['title']) ? sanitize_text_field($_REQUEST['title']) : 'Waiver');
?>

<?php echo $site->getTemplate('frame_header'); ?>

<?php echo $site->getTemplate('modal'); ?>

<?php echo $site->getTemplate('frame_footer'); ?>
