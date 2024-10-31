<?php 
	// any new page must start with the page_header, it will include the correct files
	// so that the rezgo parser classes and functions will be available to your templates
	require('rezgo/include/page_header.php');

	// start a new instance of RezgoSite
	$site = new RezgoSite();

	$search = isset($_REQUEST['search']) ? $_REQUEST['search'] : '';
	$rezgo_search = isset($_COOKIE['rezgo_search']) ? $_COOKIE['rezgo_search'] : '';

	if (isset($_REQUEST['parent_url'])) {
			$site->base = '/' . $site->requestStr('parent_url');
	}

	if ($search == 'restore' && $rezgo_search) {
		$site->sendTo($_COOKIE['rezgo_search']);
	}

	// some code to handle the pagination
	if (!isset($_REQUEST['pg'])) {
		$_REQUEST['pg'] = 1;
	}
	
	$start = (intval($_REQUEST['pg']) - 1) * REZGO_RESULTS_PER_PAGE;

	// we only want 11 responses, starting at our page number times item number
	$site->setTourLimit(REZGO_RESULTS_PER_PAGE + 1, $start);
?>

<?php echo $site->getTemplate('frame_header'); ?>

<?php echo $site->getTemplate('index'); ?>

<?php echo $site->getTemplate('frame_footer'); ?>