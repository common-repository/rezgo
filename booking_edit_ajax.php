<?php 
	// This script handles the booking edit requests made via ajax by booking_edit.php
	
	require('rezgo/include/page_header.php');

	// start a new instance of RezgoSite
	$site = new RezgoSite('secure');

	$item = $site->getTours('t=uid&q='.$_REQUEST['item'].'&d='.date('Y-m-d', (int)$_REQUEST['date']) , 0);
	$booking = $site->getBookings('q='.$_REQUEST['trans_num'].'&a=forms');
	$pax = $_REQUEST['pax'];
	$trans_num = $_REQUEST['trans_num'];

	$response = '';
	$pax_obj = (object) [];

	if ($_REQUEST['rezgoAction'] == 'edit_date_time') {

		$response = $site->editDateTime();

	} elseif ($_REQUEST['rezgoAction'] == 'change_option') {

		$response = $site->changeBookingOption();

	} elseif ($_REQUEST['rezgoAction'] == 'update_primary_form') {

		$response = $site->updateBookingPrimaryForm();

	} elseif ($_REQUEST['rezgoAction'] == 'update_group_form') {

		$response = $site->updateBookingGroupForm();
		
	} elseif ($_REQUEST['rezgoAction'] == 'update_pax') {

		$response = $site->updateBookingPax( 0, 'update_pax' );

	} elseif ($_REQUEST['rezgoAction'] == 'remove_pax') {

		$response = $site->updateBookingPax( 0, 'remove_pax' );

	} elseif ($_REQUEST['rezgoAction'] == 'cancel_booking') {

		$response = $site->cancelBooking();

	} elseif ($_REQUEST['rezgoAction'] == 'booking_edit_contact') {
		
		$response = $site->bookingEditContact();

	} elseif ($_REQUEST['rezgoAction'] == 'get_edit_status') {

		$site->getEditStatus($trans_num);

	} elseif ($_REQUEST['rezgoAction'] == 'reset_status') {

		$site->setCookie('booking_edit_status', '');
	
	}

	if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
		// ajax response if we requested this page correctly
		echo json_encode($response);		
	} else {
		// if, for some reason, the ajax form submit failed, then we want to handle the user anyway
		die ('Something went wrong.');
	}
?>