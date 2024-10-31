<?php 
$trans_num = $site->decode($_REQUEST['trans_num']);

// send the user home if they shouldn't be here
if(!$trans_num) $site->sendTo("/".$current_wp_page."/itinerary-not-found");

$itinerary = $site->getItinerary($trans_num);
$itinerary_array = json_decode($itinerary);

if (!$itinerary_array->bookings) { 

	$site->sendTo($site->base."/complete/".$_REQUEST['trans_num']);
	
 } else { 

	$company = $site->getCompanyDetails();
	$bookings = $itinerary_array->bookings;

	$date_range = $checkin_times = [];

	$overview = $highlights = $itinerary = $pick_up = $drop_off = $bring = $inclusions = $exclusions = $checkin = $description = $description_name = [];

	$lat = $lon = $zoom = $map_type = $location_name = $location_address = $city = $country = $state = [];

	$com_ids;
	$booking_index = 1;
	$booking_index_two = 1;

	foreach ($bookings as $booking) {

		if ($booking->availability_type != 'product') {
			// only add dates
			if (preg_match('~[0-9]+~', $booking->date)) {
				$date_range[] = $booking->date;
			}
		}

		// get checkin times
		if ($booking->item_id) {
			$item = $site->getTours('t=uid&q='.$booking->item_id, 0);
			if ((string) $item->checkin_offset != '') {
				$checkin_times[$booking->item_id] = date("g:i A", strtotime($booking->time) - $item->checkin_offset);
			} elseif ((string) $item->checkin_time != '') {
				$checkin_times[$booking->item_id] = (string) $item->checkin_time;
			}
		}

		if ($booking->com) {
			$com_ids .= $booking->com . ',';
		}
		$booking_index++;
	}

	if ($date_range) {
		$start_date = date($company->date_format, min($date_range));
		$end_date = date($company->date_format, max($date_range));

		$start_day = date('l', min($date_range));
		$end_day = date('l', max($date_range));
	}
		
	// same day bookings
	$same_day = $start_date == $end_date;

	$customer_name = ucfirst($itinerary_array->contact->first_name) .' '.  ucfirst($itinerary_array->contact->last_name);
	$customer_address = $itinerary_array->contact->address_1;
	$customer_email = $itinerary_array->contact->email_address;
	$customer_phone = $itinerary_array->contact->phone_number;
	
	// get associated data w/ com_ids
	$items = $site->getTours('t=com&q='.$com_ids);
	foreach ($items as $item) {
		$com = (int)$item->com;

		$overview[$com] = (string)$item->details->overview;
		$highlights[$com] = (string)$item->details->highlights;
		$itinerary[$com] = (string)$item->details->itinerary;
		$pick_up[$com] = (string)$item->details->pick_up;
		$drop_off[$com] = (string)$item->details->drop_off;
		$bring[$com] = (string)$item->details->bring;
		$inclusions[$com] = (string)$item->details->inclusions;
		$exclusions[$com] = (string)$item->details->exclusions;
		$checkin[$com] = (string)$item->details->checkin;
		$description_name[$com] = (string)$item->details->description_name;
		$description[$com] = (string)$item->details->description;

		if($item->lat != '' && $item->lon != '') {
			$lat[$com] = (string)$item->lat;
			$lon[$com] = (string)$item->lon;
			$zoom[$com] = (string)$item->zoom;
			$map_type[$com] = (string)$item->map_type;
			
			$location_name[$com] = (string)$item->location_name;
			$location_address[$com] = (string)$item->location_address;
			$city[$com] = (string)$item->city;
			$country[$com] = (string)$item->country;
			$state[$com] = (string)$item->state;
		}
	}
?>

<html>
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="robots" content="noindex, nofollow">
	<title>Booking Itinerary - <?php echo $trans_num; ?></title>

	<?php
	rezgo_plugin_scripts_and_styles();
	wp_print_scripts();
	wp_print_styles();
	?>

	<?php if($site->exists($site->getStyles())) { ?><style><?php echo $site->getStyles(); ?></style><?php } ?>

	<style>
		/* ensure that the print version has all the information laid out nicely */
		@media print{
			@page {
				size: 330mm 427mm;
				margin: 14mm;
			}
		}
	</style>

</head>

<body style="background-color: #FFF;">

	<div class="container-fluid rezgo-container rezgo-itinerary-container">

		<div class="flex-row justify-between col-reverse itinerary-header-container">
			<div class="flex-col">
				<h2 class="rezgo-itinerary-header"><?php echo $customer_name != ' ' ? esc_html($customer_name ."'s ") : ''; 	?>Itinerary &mdash; <span class="transnum"><?php echo esc_html($trans_num); ?></span></h2>
				<?php if ($date_range) { ?>
					<h4 class="rezgo-itinerary-subheader">
						<?php if (!$same_day) { ?>

							<?php if ($end_day) { ?>
								<span class="hidden-xs">
								From </span>
								<span class="subheader-date-start"><?php echo esc_html($start_day . ', ' . $start_date); ?></span> 
								to 
								<span class="subheader-date-start"><?php echo esc_html($end_day . ', ' . $end_date); ?></span> 
							<?php } ?> 

						<?php } else { ?>

							<?php if ($start_day) { ?>
								<span class="hidden-xs">
								For </span>
								<span class="subheader-date-start"><?php echo esc_html($start_day . ', ' . $start_date); ?></span> 
							<?php } ?> 

						<?php } ?> 
					</h4>
				<?php } ?> 

			</div>

			<!-- <div class="flex-col">
				<div class="itinerary-header-logo">
					<img src="<?php echo esc_html('https://'.$_SERVER['HTTP_HOST'].'/'.$site->path); ?>/img/rezgo-logo.svg" alt="Rezgo Logo">
				</div>
			</div> -->
		</div>

		<hr>

		<div class="flex-row itinerary-header-container">

			<?php if ($customer_name != ' ' || $itinerary_array->contact->address_1) { ?>
				<div class="flex-col main-recipient-details">
					<div class="header-itinerary-info">
						<div class="name-value-container">
							<span id="recipient-name" class="value"><?php echo esc_html($customer_name); ?></span>
						</div>

						<div class="name-value-container">
							<span class="value">
								<?php echo esc_html($itinerary_array->contact->address_1); ?>
								<?php echo ($site->exists($itinerary_array->contact->address_2)) ? '<br>'.esc_html($itinerary_array->contact->address_2) : ''; ?>
								<?php echo ($site->exists($itinerary_array->contact->city)) ? '<br>'.esc_html($itinerary_array->contact->city) : ''; ?>
								<?php echo ($site->exists($itinerary_array->contact->stateprov)) ? esc_html($itinerary_array->contact->stateprov) : ''; ?>
								<?php echo ($site->exists($itinerary_array->contact->postal_code)) ? '<br>'.esc_html($itinerary_array->contact->postal_code) : ''; ?>
								<?php echo esc_html($itinerary_array->contact->country); ?>
							</span>
						</div>

					</div>
				</div>
			<?php } ?>

			<?php if ($start_date || $end_date) { ?>
				<div class="flex-col sub-recipient-details">

				<?php if ($start_date) { ?>
					<div class="name-value-container">
						<span class="name">Start Date</span> 
						<span class="value"><?php echo esc_html($start_date); ?> <?php echo $start_time ? esc_html('at '.$start_time) : ''; ?></span>
					</div>
				<?php } ?> 

				<?php if ($end_date) { ?>
					<div class="name-value-container">
						<span class="name">End Date</span> 
						<span class="value"><?php echo esc_html($end_date); ?></span>
					</div>
				<?php } ?> 

				</div>
			<?php } ?> 


			<?php if ($customer_email || $customer_phone) { ?>
				<div class="flex-col sub-recipient-details">

				<?php if ($customer_email) { ?>
					<div class="name-value-container">
						<span class="name">Email</span> 
						<span class="value"><?php echo esc_html($customer_email); ?></span>
					</div>
				<?php } ?>

				<?php if ($customer_phone) { ?>
					<div class="name-value-container">
						<span class="name">Phone #</span> 
						<span class="value"><?php echo esc_html($customer_phone); ?></span>
					</div>
				<?php } ?>

				</div>
			<?php } ?>
			
		</div>

		<div class="grouped-itinerary">

			<div class="row">
				<div class="col-xs-12">
					<div class="itinerary-overview-container">

						<h3 class="subheader">Itinerary Summary</h3>

						<div class="overview-table-container">
							
							<div class="overview-table header flex-row">
								<span class="cell overview-tour-header">Name</span>
								<span class="cell overview-date-header">Booked For</span>
								<span class="cell overview-checkin-header">Check-In Time</span>
							</div>

							<div id="summary-overview-table" class="overview-table">
								<?php foreach ($bookings as $booking) { 
									$checkin_time = '';
									if ($booking->show == 'booking') {
										if ($checkin_times[$booking->item_id]) {
											$checkin_time = $checkin_times[$booking->item_id];
										} else {
											$checkin_time = $booking->time;
										}
									} else {
										if ($booking->time) $checkin_time = $booking->time;
									}
								?>
								<div class="cell-row item-overview-<?php echo $booking->com; ?>">
									<span class="cell detail overview-tour-detail"><?php echo $booking->item_name ? $booking->item_name : $booking->name; ?></span>

										<span class="visible-xs cell overview-date-header">Booked For</span>

										<span class="cell detail overview-date-detail">
											<?php if ($booking->availability_type != 'product') { ?>
												<?php echo $booking->date_formatted !== 'open' ? date('l', $booking->date). ', ' .$booking->date_formatted : 'Open Date'; ?> 
												<span class="overview-time-detail">
													<?php echo $booking->time ? 'at ' .strtoupper($booking->time) : ''; ?>
												</span>
											<?php } else { ?>
												<span class="overview-time-detail"></span>
											<?php } ?>
										</span> 

										<span class="visible-xs cell overview-checkin-header">Check-In</span>
										<span class="cell detail overview-checkin-detail"><?php echo strtoupper($checkin_time); ?></span>

										<hr class="visible-xs mobile-separator">
									</div>
								<?php } ?>
							</div>

						</div>

					</div>

					<div class="itinerary-details-container">

						<h3 class="subheader">Itinerary Details</h3>

						<?php foreach ($bookings as $booking) {

							$passenger_str = ''; 
							if (count((array)$booking->passengers) >= 1 ) {
								$passenger_str .= $booking->adult_num ? $booking->adult_num . ' ' . $booking->adult_label.'<br>' : '';
								$passenger_str .= $booking->child_num ? $booking->child_num . ' ' . $booking->child_label.'<br>' : ''; 
								$passenger_str .= $booking->senior_num ? $booking->senior_num . ' ' . $booking->senior_label.'<br>' : ''; 
								$passenger_str .= $booking->price4_num ? $booking->price4_num . ' ' . $booking->price4_label.'<br>' : ''; 
								$passenger_str .= $booking->price5_num ? $booking->price5_num . ' ' . $booking->price5_label.'<br>' : ''; 
								$passenger_str .= $booking->price6_num ? $booking->price6_num . ' ' . $booking->price6_label.'<br>' : ''; 
								$passenger_str .= $booking->price7_num ? $booking->price7_num . ' ' . $booking->price7_label.'<br>' : ''; 
								$passenger_str .= $booking->price8_num ? $booking->price8_num . ' ' . $booking->price8_label.'<br>' : ''; 
								$passenger_str .= $booking->price9_num ? $booking->price9_num . ' ' . $booking->price9_label.'<br>' : ''; 
							}

							$booking_date = $booking->date_formatted !== 'open' ? date('l', $booking->date). ', ' .$booking->date_formatted : 'Open Date'; 
							$booking_time = strtoupper($booking->time); 
							$booking_name = $booking->item_name ? $booking->item_name : $booking->name; ?>

								<div class="itinerary-item">
									<?php if ($booking->availability_type != 'product') { ?>
										<div class="itinerary-booked-for">
											<i class="far fa-clock"></i>&nbsp; 
											<span class="date"><?php echo esc_html($booking_date); ?> </span> 
											<?php if ($booking_time) { ?> 
												<span class="time"> at <?php echo $booking_time ? esc_html($booking_time) : ''; ?></span>
											<?php } ?>
										</div>
									<?php } ?>

									<div class="flex-row align-start single-itinerary-details-row <?php echo $booking->availability_type == 'product' ? 'product' : ''; ?>">
										<div class="flex-100">
											<div class="itinerary-item-name">
												<span class="item-name"><?php echo esc_html($booking_name); ?></span>

												<?php if ($booking->option_name) { ?> 
													<div class="itinerary-item-desc">
														<span class="item-desc"><?php echo esc_html($booking->option_name); ?></span>
													</div>
												<?php } ?>

												<?php if ($booking->notes) { ?> 
													<div class="itinerary-item-desc">
														<span class="item-desc"><?php echo esc_html($booking->notes); ?></span>
													</div>
												<?php } ?>

												<?php if ($booking->fields) { ?>
													<div class="flex-row provider-fields-row">
														<?php foreach ($booking->fields as $field) { ?>
															<div class="itinerary-provider-fields">
																<span class="name"><?php echo esc_html($field->label); ?></span>
																<span class="value"><?php echo esc_html($field->text); ?></span>
															</div>
														<?php } ?>
													</div>
												<?php } ?>
											</div>
										</div>

										<div class="details-row">

											<?php if ($passenger_str) { ?>
												<div class="name-value-container guests-container half">
													<span class="name guests-name">
														<span>Guests</span>
													</span>
													<span class="value guests-value">
														<?php echo wp_kses($passenger_str, ALLOWED_HTML); ?>
													</span>
												</div>
											<?php } ?>

											<?php 
												$details_checkin_time = '';
												if ($booking->show == 'booking') {
													if ($checkin_times[$booking->item_id]) {
														$details_checkin_time = $checkin_times[$booking->item_id];
													} else {
														$details_checkin_time = $booking->time;
													}
												} else {
													if ($booking->time) $details_checkin_time = $booking->time;
												}
											?>

											<?php if ($details_checkin_time) { ?>
												<div class="name-value-container checkin-container half">
													<span class="name checkin-name">
														<span>Check-In Time</span>
													</span>
													<span class="value checkin-value">
														<?php echo esc_html($details_checkin_time); ?>
													</span>
												</div>
											<?php } ?>

											<?php if ($booking->overall_total > 0) { ?>
												<div class="name-value-container cost-container half">
													<span class="name cost-name">
														<span>Cost</span>
													</span>
													<span class="value cost-value">
														<?php echo esc_html($site->formatCurrency($booking->overall_total, $company)); ?>
													</span>
												</div>
											<?php } ?>

											<?php if ($booking->product_qty > 0) { ?>
												<div class="name-value-container qty-container half">
													<span class="name qty-name">
															<span>Qty</span>
													</span>
													<span class="value qty-value">
														<?php echo esc_html($booking->product_qty); ?>
													</span>
												</div>
											<?php } ?>

											<?php if ($booking->type) { ?>
												<div class="name-value-container type-container half">
													<span class="name type-name">
														<span>Type</span>
													</span>
													<span class="value type-value">
														<?php echo esc_html(ucwords($booking->type)); ?>
													</span>
												</div>
											<?php } ?>
										</div>

										<div class="itinerary-details">

											<?php if ((string) $booking->pickup->name != '') { ?>
												<?php $pickup_detail = $site->getPickupItem((string) $booking->item_id, (int) $booking->pickup->id); ?>
												<div id="rezgo-receipt-pickup" class="name-value-container itinerary-pickup-info">
													<span class="name"><i class="far fa-info-square"></i>Pick Up Information</span>
													<div class="flex-table-info indent">
														<h4><?php echo esc_html($booking->pickup->name); ?> at <?php echo esc_html($booking->pickup->time); ?></h4>
														<div class="row" style="margin: auto 0;">
															<?php if($site->exists($pickup_detail->lat) && $site->exists($pickup_detail->location_address)) {  ?>
																<i class="far fa-map-marker"></i>&nbsp;
																<a class="underline-link" href="https://www.google.com/maps/place/<?php echo urlencode($pickup_detail->lat.','.$pickup_detail->lon); ?>" target="_blank"><?php echo esc_html($pickup_detail->location_address); ?></a><br>
															<?php } ?>
															
															<?php
																if($site->exists($pickup_detail->lat) && GOOGLE_API_KEY) { 
																
																	if(!$site->exists($pickup_detail->zoom)) { $map_zoom = 8; } else { $map_zoom = $pickup_detail->zoom; }

																	if($pickup_detail->map_type != '') { 
																		$embed_type = strtolower($pickup_detail->map_type); 
																		if ( $embed_type == 'hybrid' ) { $embed_type = 'satellite'; }
																	} else { 
																		$embed_type = 'roadmap'; 
																	} 
																
																	echo '
																		<div class="col-sm-12 rezgo-pickup-receipt-data">
																		<div class="rezgo-pickup-map" id="rezgo-pickup-map">
																			<iframe width="100%" height="372" frameborder="0" style="border:0;margin-bottom:0;margin-top:-130px;" src="https://www.google.com/maps/embed/v1/place?key='.GOOGLE_API_KEY.'&maptype='.esc_attr($embed_type).'&q='.esc_attr($pickup_detail->lat).','.esc_attr($pickup_detail->lon).'&center='.esc_attr($pickup_detail->lat).','.esc_attr($pickup_detail->lon).'&zoom='.esc_attr($map_zoom).'"></iframe>
																		</div>
																		</div>
																	';
																		
																}

																if($pickup_detail->media) { 

																	echo '
																		<div class="col-xs-12 rezgo-pickup-receipt-data">
																		<img src="'.esc_attr($pickup_detail->media->image[0]->path).'" alt="'.esc_attr($pickup_detail->media->image[0]->caption).'" style="max-width:100%;"> 
																		<div class="rezgo-pickup-caption">'.esc_html($pickup_detail->media->image[0]->caption).'</div>
																		</div>
																	';				
																
																}

																echo '
																	<div class="col-xs-12 rezgo-pickup-receipt-data">
																';				
													
																if( ($site->exists($pickup_detail->pick_up)) ) {
																	echo '<label>Pick Up</label> '.wp_kses($pickup_detail->pick_up, ALLOWED_HTML).'';
																}

																if( ($site->exists($pickup_detail->drop_off)) ) {
																	echo '<label>Drop Off</label> '.wp_kses($pickup_detail->drop_off, ALLOWED_HTML).'';
																}	

																echo '
																	</div>
																';	
															?>
														</div>
													</div>
												</div>
											<?php } ?>

											<?php if ($itinerary[$booking->com]) { ?>
												<div class="name-value-container itinerary-itinerary">
													<span class="name"><i class="far fa-clipboard-list"></i>Itinerary</span>
													<span class="value">
														<?php echo wp_kses($itinerary[$booking->com], ALLOWED_HTML); ?>
													</span>
												</div>
											<?php } ?>

											<?php if ($pick_up[$booking->com]) { ?>
												<div class="name-value-container itinerary-pick-up">
													<span class="name"><i class="far fa-map-marker"></i>Pick Up</span>
													<span class="value">
														<?php echo wp_kses($pick_up[$booking->com], ALLOWED_HTML); ?>
													</span>
												</div>
											<?php } ?>

											<?php if ($drop_off[$booking->com]) { ?>
												<div class="name-value-container itinerary-drop-off">
													<span class="name"><i class="far fa-location-arrow"></i>Drop Off</span>
													<span class="value">
														<?php echo wp_kses($drop_off[$booking->com], ALLOWED_HTML); ?>
													</span>
												</div>
											<?php } ?>

											<?php if ($bring[$booking->com]) { ?>
												<div class="name-value-container itinerary-bring">
													<span class="name"><i class="far fa-suitcase"></i>Bring</span>
													<span class="value">
														<?php echo wp_kses($bring[$booking->com], ALLOWED_HTML); ?>
													</span>
												</div>
											<?php } ?>

											<?php if ($inclusions[$booking->com]) { ?>
												<div class="name-value-container itinerary-inclusions">
													<span class="name"><i class="far fa-check-circle"></i>Inclusions</span>
													<span class="value">
														<?php echo wp_kses($inclusions[$booking->com], ALLOWED_HTML); ?>
													</span>
												</div>
											<?php } ?>

											<?php if ($exclusions[$booking->com]) { ?>
												<div class="name-value-container itinerary-exclusions">
													<span class="name"><i class="far fa-ban"></i>Exclusions</span>
													<span class="value">
														<?php echo wp_kses($exclusions[$booking->com], ALLOWED_HTML); ?>
													</span>
												</div>
											<?php } ?>

											<?php if ($checkin[$booking->com]) { ?>
												<div class="name-value-container itinerary-checkin">
													<span class="name"><i class="far fa-ticket"></i>Checkin</span>
													<span class="value">
														<?php echo wp_kses($checkin[$booking->com], ALLOWED_HTML); ?>
													</span>
												</div>
											<?php } ?>

											<?php if ($description[$booking->com]) { ?>
												<div class="name-value-container itinerary-custom">
													<span class="name"><i class="far fa-info-square"></i><?php echo $description_name[$com]; ?></span>
													<span class="value">
														<?php echo wp_kses($description[$booking->com], ALLOWED_HTML); ?>
													</span>
												</div>
											<?php } ?>

											<?php if($lat[$booking->com] != '' && $lon[$booking->com] != '' && GOOGLE_API_KEY) { ?>
										
												<?php 
													
													if (!$site->exists($zoom[$booking->com])) { 
														$map_zoom = 6; 
													} else { 
														$map_zoom = $zoom[$booking->com]; 
													}
													
													if ($map_type[$booking->com] == 'ROADMAP') {
														$embed_type = 'roadmap';
													} else {
														$embed_type = 'satellite';
													} 
													
												?>

												<div class="name-value-container itinerary-map">
													<span class="name"><i class="far fa-map-marker-alt"></i>Map</span>
													<span class="value">
														<?php if ($location_name[$booking->com]) { ?>
															<div id="rezgo-receipt-map-location">
																<strong><i class="far fa-comment"></i><?php echo esc_html($location_name[$booking->com]); ?></strong>	
																<br>
																<i class="far fa-location-arrow"></i><?php echo esc_html($location_address[$booking->com]); ?>
															</div>
														<?php } ?>
														<div class="rezgo-map" id="rezgo-receipt-map">
															<iframe width="100%" height="500" frameborder="0" style="border:0;margin-bottom:0;margin-top:-125px;" src="https://www.google.com/maps/embed/v1/place?key=<?php echo GOOGLE_API_KEY; ?>&maptype=<?php echo esc_attr($embed_type); ?>&q=<?php echo esc_attr($lat[$booking->com]); ?>,<?php echo esc_attr($lon[$booking->com]); ?>&center=<?php echo esc_attr($lat[$booking->com]); ?>,<?php echo esc_attr($lon[$booking->com]); ?>&zoom=<?php echo esc_attr($map_zoom); ?>"></iframe>
														</div>
													</span>
												</div>
											<?php } ?>

										</div>
									</div>
								</div>
						<?php $booking_index_two++; } ?> 
					</div>
				</div>
			</div>
		</div>
	</div>

</body>
</html>

<?php } // if ($itinerary_array->bookings) ?>