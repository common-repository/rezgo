<?php 
	// This is the booking receipt page
	require('rezgo/include/page_header.php');
	
	// start a new instance of RezgoSite
	$site = new RezgoSite();

	if (isset($_REQUEST['parent_url'])) {
		$site->base = '/' . $site->requestStr('parent_url');
	}

	// grab and decode the trans_num if it was set
	$trans_num = $site->decode(sanitize_text_field($_REQUEST['trans_num']));

	// get type of request to reroute user to a specific URL
	$type = $_REQUEST['type'] ?? '';
	
	// send the user home if they shouldn't be here
	if (!$trans_num) {
		$site->sendTo($site->base."/booking-not-found");
	}
?>

<?php echo $site->getTemplate('frame_header'); ?>

	<?php 
		if ($type == 'date') {
			echo $site->getTemplate('booking_edit/booking_edit_date_time'); 
		} elseif ($type == 'primary') {
			echo $site->getTemplate('booking_edit/booking_edit_primary_forms'); 
		} elseif ($type == 'group') {
			echo $site->getTemplate('booking_edit/booking_edit_group_forms'); 
		} else {
			echo $site->getTemplate('booking_edit/booking_edit'); 
		}
	?>

<?php echo $site->getTemplate('frame_footer'); ?>
