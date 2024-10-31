<?php 
if (isset($_REQUEST['trans_num'])) {
	$trans_num = $site->decode(sanitize_text_field($_REQUEST['trans_num']));
}
if (isset($_REQUEST['parent_url'])) {
	$site->base = '/' . $site->requestStr('parent_url');
}

// send the user home if they shouldn't be here
if(!$trans_num) $site->sendTo($site->base."/itinerary-not-found:empty");

if (REZGO_WORDPRESS) $site->setTimeZone();

$itinerary = $site->getItinerary($trans_num);
$itinerary_array = json_decode($itinerary);

if (!isset($itinerary_array->bookings)) { ?>

   	<div class="rezgo-bookings-not-found">
		<h3 id="bookings-not-found-header">Bookings not found</h3>
		<h3 id="bookings-not-found-subheader">Sorry, there were no valid bookings found in this itinerary.</h3>

		<img id="bookings-not-found-img" src="<?php echo $site->path; ?>/img/bookings_not_found.svg" alt="Bookings Not Found">

        <br>

        <a class="underline-link" href="<?php echo $site->base."/complete/".$trans_num; ?>"><i class="fas fa-arrow-left" style="margin-right:5px;"></i> Return to Booking Receipt</a>
    </div>

<?php } else { 

	$company = $site->getCompanyDetails();
	$bookings = $itinerary_array->bookings;

	$date_range = $checkin_times = [];

	$overview = $highlights = $itinerary = $pick_up = $drop_off = $bring = $inclusions = $exclusions = $checkin = $description = $description_name = [];

	$lat = $lon = $zoom = $map_type = $location_name = $location_address = $city = $country = $state = [];

	$com_ids = $start_time = '';
	$item_ids = '';
	$booking_index = 1;

	foreach ($bookings as $booking) {

		if (!isset($booking->availability_type) || $booking->availability_type != 'product') {
			// only add dates
			if (preg_match('~[0-9]+~', $booking->date)) {
				$date_range[] = $booking->date;
			}
		}

		if (isset($booking->item_id)) {
			$item_ids .= $booking->item_id . ',';
		}
		
	}
	
	if($item_ids) {
	
		$items = $site->getTours('t=uid&q='.$item_ids.'&a=show_hidden,show_invalid');

		foreach ($items as $item) {
			
			$item_id = (int)$item->uid;
	
			$overview[$item_id] = (string)$item->details->overview;
			$highlights[$item_id] = (string)$item->details->highlights;
			$itinerary[$item_id] = (string)$item->details->itinerary;
			$pick_up[$item_id] = (string)$item->details->pick_up;
			$drop_off[$item_id] = (string)$item->details->drop_off;
			$bring[$item_id] = (string)$item->details->bring;
			$inclusions[$item_id] = (string)$item->details->inclusions;
			$exclusions[$item_id] = (string)$item->details->exclusions;
			$checkin[$item_id] = (string)$item->details->checkin;
			$description_name[$item_id] = (string)$item->details->description_name;
			$description[$item_id] = (string)$item->details->description;
	
			if($item->lat != '' && $item->lon != '') {
				$lat[$item_id] = (string)$item->lat;
				$lon[$item_id] = (string)$item->lon;
				$zoom[$item_id] = (string)$item->zoom;
				$map_type[$item_id] = (string)$item->map_type;
				
				$location_name[$item_id] = (string)$item->location_name;
				$location_address[$item_id] = (string)$item->location_address;
				$city[$item_id] = (string)$item->city;
				$country[$item_id] = (string)$item->country;
				$state[$item_id] = (string)$item->state;
			}
			
			if ((string) $item->checkin_offset != '') {
				$checkin_times[$item_id]['offset'] = (string) $item->checkin_offset;
			} elseif ((string) $item->checkin_time != '') {
				$checkin_times[$item_id]['time'] = (string) $item->checkin_time;
			}
		
		}
	
	}

	if (!empty($date_range)) {
		$start_date = date($company->date_format, min($date_range));
		$end_date = date($company->date_format, max($date_range));

		$start_day = date('l', min($date_range));
		$end_day = date('l', max($date_range));
			
		// same day bookings
		$same_day = $start_date == $end_date;
	}
		
	$customer_name = ucfirst($itinerary_array->contact->first_name) .' '.  ucfirst($itinerary_array->contact->last_name);
	$customer_address = $itinerary_array->contact->address_1;
	$customer_email = $itinerary_array->contact->email_address;
	$customer_phone = $itinerary_array->contact->phone_number;

?>

	<div class="container-fluid rezgo-container rezgo-itinerary-container">

		<div class="col-reverse itinerary-header-container">
			<div class="flex-col">
				<h2 class="rezgo-itinerary-header"><?php echo $customer_name != ' ' ? esc_html($customer_name ."'s ") : ''; 	?>Itinerary &mdash; <span class="transnum"><?php echo esc_html($trans_num); ?></span></h2>
				<?php if ($date_range) { ?>
					<h4 class="rezgo-itinerary-subheader">
						<?php if (!$same_day) { ?>

							<?php if ($end_day) { ?>
								<span class="d-none d-sm-inline-block">
								From </span>
								<span class="subheader-date-start"><?php echo esc_html($start_day . ', ' . $start_date); ?></span> 
								to 
								<span class="subheader-date-start"><?php echo esc_html($end_day . ', ' . $end_date); ?></span> 
							<?php } ?> 

						<?php } else { ?>

							<?php if ($start_day) { ?>
								<span class="d-none d-sm-inline-block">
								For </span>
								<span class="subheader-date-start"><?php echo esc_html($start_day . ', ' . $start_date); ?></span> 
							<?php } ?> 

						<?php } ?> 
					</h4>
				<?php } ?> 

				<?php 
					if (REZGO_LITE_CONTAINER) { 
						$itinerary_print_link = 'https://'.$domain.'.'.$role.'rezgo.com/itinerary/'.$site->encode($trans_num).'/print';
					} elseif (REZGO_WORDPRESS) { 
						$itinerary_print_link = esc_js($site->base).'/itinerary/'.esc_js($site->encode($trans_num)).'/print';
					} else {
						$itinerary_print_link = esc_js($site->base).'/itinerary/'.$site->encode($trans_num).'/print';
					}
				?>
				<button class="rezgo-print-itinerary" onclick="window.open('<?php echo $itinerary_print_link; ?>', '_blank'); return false;">
					<i class="far fa-print"></i>&nbsp;&nbsp;Print Itinerary
				</button>
			</div>

			<!-- <div class="flex-col">
				<div class="itinerary-header-logo">
					<img src="<?php echo $site->path;?>/img/rezgo-logo.svg" alt="">
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

			<?php if (isset($start_date) || isset($end_date)) { ?>
				<div class="flex-col sub-recipient-details">

				<?php if (isset($start_date)) { ?>
					<div class="name-value-container">
						<span class="name">Start Date</span> 
						<span class="value"><?php echo esc_html($start_date); ?> <?php echo $start_time ? esc_html('at '.$start_time) : ''; ?></span>
					</div>
				<?php } ?> 

				<?php if (isset($end_date)) { ?>
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
				<div class="col-12">
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
									// For regular items: show start time as check in time if there is no check in time for item.
									// For custom items: show start time as check in time
									// Leave it blank instead of N/A for items without start time/check in time.

									if ($booking->show == 'booking') {
										
										if(isset($checkin_times[$booking->item_id]['offset'])) {
											$checkin_time = date("g:i A", strtotime($booking->time) - $checkin_times[$booking->item_id]['offset']);
										} elseif(isset($checkin_times[$booking->item_id]['time'])) {
											$checkin_time = $checkin_times[$booking->item_id]['time'];
										} else {
											$checkin_time = $booking->time;
										}
										
									} else {
										if ($booking->time) $checkin_time = $booking->time;
									}
								?>									
								<div class="cell-row item-overview-<?php echo $booking->item_id; ?>">

									<span class="cell detail overview-tour-detail"><?php echo $booking->item_name ?? $booking->name; ?></span>

										<span class="d-block d-md-none cell overview-date-header">Booked For</span>

										<span class="cell detail overview-date-detail">

											<?php if (!isset($booking->availability_type)) { ?> 

												<span class="rezgo-itinerary-booked-for-date-<?php echo esc_attr($booking->item_id); ?>">
													<?php echo $booking->date_formatted !== 'open' ? date('l', $booking->date). ', ' .$booking->date_formatted : 'Open Date'; ?>
												</span>
												
											<?php } else { ?>
												
												<?php if ($booking->availability_type != 'product') { ?> 

													<span class="rezgo-itinerary-booked-for-date-<?php echo esc_attr($booking->item_id); ?>">
														<?php echo $booking->date_formatted !== 'open' ? date('l', $booking->date). ', ' .$booking->date_formatted : 'Open Date'; ?>
													</span>

												<?php } ?>
											<?php } ?>

											<span class="overview-time-detail rezgo-itinerary-booked-for-time-<?php echo esc_attr($booking->item_id); ?>">
												<?php echo $booking->time ? 'at ' .strtoupper($booking->time) : ''; ?>
											</span>
										</span> 

										<span class="d-block d-md-none cell overview-checkin-header <?php if(!$checkin_time) {?> d-none <?php } ?>">Check-In</span>
										<span class="cell detail overview-checkin-detail"><?php echo strtoupper($checkin_time); ?></span>

										<hr class="d-block d-md-none mobile-separator">
									</div>
								<?php } ?>
							</div>

						</div>

					</div>

					<div class="itinerary-details-container">

						<h3 class="subheader">Itinerary Details</h3>

						<?php foreach ($bookings as $booking) {

							$passenger_str = ''; 
							if (isset($booking->passengers)){
								if (count((array)$booking->passengers) >= 1 ) {
									$passenger_str .= isset($booking->adult_num) ? $booking->adult_num . ' ' . $booking->adult_label.'<br>' : '';
									$passenger_str .= isset($booking->child_num) ? $booking->child_num . ' ' . $booking->child_label.'<br>' : ''; 
									$passenger_str .= isset($booking->senior_num) ? $booking->senior_num . ' ' . $booking->senior_label.'<br>' : ''; 
									$passenger_str .= isset($booking->price4_num) ? $booking->price4_num . ' ' . $booking->price4_label.'<br>' : ''; 
									$passenger_str .= isset($booking->price5_num) ? $booking->price5_num . ' ' . $booking->price5_label.'<br>' : ''; 
									$passenger_str .= isset($booking->price6_num) ? $booking->price6_num . ' ' . $booking->price6_label.'<br>' : ''; 
									$passenger_str .= isset($booking->price7_num) ? $booking->price7_num . ' ' . $booking->price7_label.'<br>' : ''; 
									$passenger_str .= isset($booking->price8_num) ? $booking->price8_num . ' ' . $booking->price8_label.'<br>' : ''; 
									$passenger_str .= isset($booking->price9_num) ? $booking->price9_num . ' ' . $booking->price9_label.'<br>' : ''; 
								}
							}

							$booking_date = $booking->date_formatted !== 'open' ? date('l', $booking->date). ', ' .$booking->date_formatted : 'Open Date'; 
							$booking_time = strtoupper($booking->time); 
							$booking_name = $booking->item_name ?? $booking->name; 
							?>

								<div class="itinerary-item item-details-<?php echo $booking->com; ?>">
									<?php if (!isset($booking->availability_type)) { ?> 

										<div class="itinerary-booked-for">
											<i class="far fa-clock"></i>&nbsp; 
											<span class="date rezgo-itinerary-booked-for-date-<?php echo esc_attr($booking->item_id); ?>"><?php echo esc_html($booking_date); ?> </span> 

											<?php if ($booking_time) { ?> 
												<span class="time rezgo-itinerary-booked-for-time-<?php echo esc_attr($booking->item_id); ?>"> at <?php echo $booking_time ? esc_html($booking_time) : ''; ?></span>
											<?php } ?>
										</div>
										
									<?php } else { ?>
										
										<?php if ($booking->availability_type != 'product') { ?> 

											<div class="itinerary-booked-for">
												<i class="far fa-clock"></i>&nbsp; 
												<span class="date rezgo-itinerary-booked-for-date-<?php echo esc_attr($booking->item_id); ?>"><?php echo esc_html($booking_date); ?> </span> 

												<?php if ($booking_time) { ?> 
													<span class="time rezgo-itinerary-booked-for-time-<?php echo esc_attr($booking->item_id); ?>"> at <?php echo $booking_time ? esc_html($booking_time) : ''; ?></span>
												<?php } ?>
											</div>

										<?php } ?>
									<?php } ?>

									<div class="flex-row align-start single-itinerary-details-row <?php echo isset($booking->availability_type) && $booking->availability_type == 'product' ? 'product' : ''; ?>">
										<div class="flex-100">
											<div class="itinerary-item-name">
												<span class="item-name"><?php echo esc_html($booking_name); ?></span>

												<?php if (isset($booking->option_name)) { ?> 
													<div class="itinerary-item-desc">
														<span class="item-desc"><?php echo esc_html($booking->option_name); ?></span>
													</div>
												<?php } ?>

												<?php if (isset($booking->notes)) { ?> 
													<div class="itinerary-item-desc">
														<span class="item-desc"><?php echo esc_html($booking->notes); ?></span>
													</div>
												<?php } ?>

												<?php if (isset($booking->fields)) { ?>
													<div class="flex-row">
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
													
													if(isset($checkin_times[$booking->item_id]['offset'])) {
														$details_checkin_time = date("g:i A", strtotime($booking->time) - $checkin_times[$booking->item_id]['offset']);
													} elseif(isset($checkin_times[$booking->item_id]['time'])) {
														$details_checkin_time = $checkin_times[$booking->item_id]['time'];
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

											<?php if (isset($booking->overall_total) && $booking->overall_total > 0) { ?>
												<div class="name-value-container cost-container half">
													<span class="name cost-name">
														<span>Cost</span>
													</span>
													<span class="value cost-value">
														<?php echo esc_html($site->formatCurrency($booking->overall_total, $company)); ?>
													</span>
												</div>
											<?php } ?>

											<?php if (isset($booking->product_qty) && $booking->product_qty > 0) { ?>
												<div class="name-value-container qty-container half">
													<span class="name qty-name">
															<span>Qty</span>
													</span>
													<span class="value qty-value">
														<?php echo esc_html($booking->product_qty); ?>
													</span>
												</div>
											<?php } ?>

											<?php if (isset($booking->type)) { ?>
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

											<?php if (isset($booking->pickup->name) && (string) $booking->pickup->name != '') { ?>
												<?php $pickup_detail = $site->getPickupItem((string) $booking->item_id, (int) $booking->pickup->id); ?>
												<div id="rezgo-receipt-pickup" class="name-value-container itinerary-pickup-info">
													<span class="name"><i class="far fa-info-square"></i>Pick Up Information</span>
													<div class="flex-table-info indent">
														<h4><?php echo esc_html($booking->pickup->name); ?> at <?php echo esc_html($booking->pickup->time); ?></h4>
														
															<?php if($site->exists($pickup_detail->lat) && $site->exists($pickup_detail->location_address)) {  ?>
																<i class="far fa-map-marker"></i>&nbsp;
																<a class="underline-link" href="https://www.google.com/maps/place/<?php echo urlencode($pickup_detail->lat.','.$pickup_detail->lon); ?>" target="_blank"><?php echo esc_html($pickup_detail->location_address); ?></a><br>
															<?php } ?>

														<div class="row" style="margin: auto 0;">
															
															<?php
																if($site->exists($pickup_detail->lat) && GOOGLE_API_KEY && !REZGO_CUSTOM_DOMAIN) { 
																
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
																		<div class="col-12 rezgo-pickup-receipt-data">
																		<img src="'.esc_attr($pickup_detail->media->image[0]->path).'" alt="'.esc_attr($pickup_detail->media->image[0]->caption).'" style="max-width:100%;"> 
																		<div class="rezgo-pickup-caption">'.esc_html($pickup_detail->media->image[0]->caption).'</div>
																		</div>
																	';				
																
																}

																echo '
																	<div class="col-12 rezgo-pickup-receipt-data">
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

											<?php if (isset($itinerary[$booking->item_id ?? '']) && !empty($itinerary[$booking->item_id])) { ?>
												<div class="name-value-container itinerary-itinerary">
													<span class="name"><i class="far fa-clipboard-list"></i>Itinerary</span>
													<span class="value">
														<?php echo wp_kses($itinerary[$booking->item_id], ALLOWED_HTML); ?>
													</span>
												</div>
											<?php } ?>

											<?php if (isset($pick_up[$booking->item_id ?? '']) && !empty($pick_up[$booking->item_id])) { ?>
												<div class="name-value-container itinerary-pick-up">
													<span class="name"><i class="far fa-map-marker"></i>Pick Up</span>
													<span class="value">
														<?php echo wp_kses($pick_up[$booking->item_id], ALLOWED_HTML); ?>
													</span>
												</div>
											<?php } ?>

											<?php if (isset($drop_off[$booking->item_id ?? '']) && !empty($drop_off[$booking->item_id])) { ?>
												<div class="name-value-container itinerary-drop-off">
													<span class="name"><i class="far fa-location-arrow"></i>Drop Off</span>
													<span class="value">
														<?php echo wp_kses($drop_off[$booking->item_id], ALLOWED_HTML); ?>
													</span>
												</div>
											<?php } ?>

											<?php if (isset($bring[$booking->item_id ?? '']) && !empty($bring[$booking->item_id])) { ?>
												<div class="name-value-container itinerary-bring">
													<span class="name"><i class="far fa-suitcase"></i>Bring</span>
													<span class="value">
														<?php echo wp_kses($bring[$booking->item_id], ALLOWED_HTML); ?>
													</span>
												</div>
											<?php } ?>

											<?php if (isset($inclusions[$booking->item_id ?? '']) && !empty($inclusions[$booking->item_id])) { ?>
												<div class="name-value-container itinerary-inclusions">
													<span class="name"><i class="far fa-check-circle"></i>Inclusions</span>
													<span class="value">
														<?php echo wp_kses($inclusions[$booking->item_id], ALLOWED_HTML); ?>
													</span>
												</div>
											<?php } ?>

											<?php if (isset($exclusions[$booking->item_id ?? '']) && !empty($exclusions[$booking->item_id])) { ?>
												<div class="name-value-container itinerary-exclusions">
													<span class="name"><i class="far fa-ban"></i>Exclusions</span>
													<span class="value">
														<?php echo wp_kses($exclusions[$booking->item_id], ALLOWED_HTML); ?>
													</span>
												</div>
											<?php } ?>

											<?php if (isset($checkin[$booking->item_id ?? '']) && !empty($checkin[$booking->item_id])) { ?>
												<div class="name-value-container itinerary-checkin">
													<span class="name"><i class="far fa-ticket"></i>Checkin</span>
													<span class="value">
														<?php echo wp_kses($checkin[$booking->item_id], ALLOWED_HTML); ?>
													</span>
												</div>
											<?php } ?>

											<?php if (isset($description[$booking->item_id ?? '']) && !empty($description[$booking->item_id])) { ?>
												<div class="name-value-container itinerary-custom">
													<span class="name"><i class="far fa-info-square"></i><?php echo $description_name[$com ?? ''] ?? 'Additional Info'; ?></span>
													<span class="value">
														<?php echo wp_kses($description[$booking->item_id], ALLOWED_HTML); ?>
													</span>
												</div>
											<?php } ?>

											<?php if(isset($lat[$booking->item_id ?? '']) && $lat[$booking->item_id ?? ''] != '' && isset($lon[$booking->item_id]) && $lon[$booking->item_id] != '' && GOOGLE_API_KEY && !REZGO_CUSTOM_DOMAIN) { ?>
										
												<?php 
													
													if (!$site->exists($zoom[$booking->item_id])) { 
														$map_zoom = 6; 
													} else { 
														$map_zoom = $zoom[$booking->item_id] ?? 6; 
													}
													
													if ($map_type[$booking->item_id] == 'ROADMAP') {
														$embed_type = 'roadmap';
													} else {
														$embed_type = 'satellite';
													} 
													
												?>

												<div class="name-value-container itinerary-map">
													<span class="name"><i class="far fa-map-marker-alt"></i>Map</span>
													<span class="value">
														<div class="rezgo-map" id="rezgo-receipt-map">
															<iframe width="100%" height="500" frameborder="0" style="border:0;margin-bottom:0;margin-top:-125px;" src="https://www.google.com/maps/embed/v1/place?key=<?php echo GOOGLE_API_KEY; ?>&maptype=<?php echo esc_attr($embed_type); ?>&q=<?php echo esc_attr($lat[$booking->item_id]); ?>,<?php echo esc_attr($lon[$booking->item_id]); ?>&center=<?php echo esc_attr($lat[$booking->item_id]); ?>,<?php echo esc_attr($lon[$booking->item_id]); ?>&zoom=<?php echo esc_attr($map_zoom); ?>"></iframe>

															<div class="rezgo-map-location rezgo-map-shadow">
																<?php if($location_name[$booking->item_id]) { ?>
																	<div class="rezgo-map-icon float-start"><i class="far fa-map-marker"></i></div> <?php echo esc_html($location_name[$booking->item_id]); ?>
																	<div class="rezgo-map-hr"></div>
																<?php } ?>

																<?php if($location_address[$booking->item_id]) { ?>
																	<div class="rezgo-map-icon float-start"><i class="far fa-location-arrow"></i></div> <?php echo esc_html($location_address[$booking->item_id]); ?>
																	<div class="rezgo-map-hr"></div>
																<?php } else { ?>
																	<div class="rezgo-map-icon float-start"><i class="far fa-location-arrow"></i></div> <?php echo esc_html($city[$booking->item_id].' '.$state[$booking->item_id].' '.$site->countryName($country[$booking->item_id])); ?>
																	<div class="rezgo-map-hr"></div>
																<?php } ?>

																<div class="rezgo-map-icon float-start"><i class="far fa-globe"></i></div> <?php echo esc_html(round((float) $lat[$booking->item_id], 3)); ?>, <?php echo esc_html(round((float) $lon[$booking->item_id], 3)); ?>
															</div>
														</div>
													</span>
												</div>
											<?php } ?>

										</div>
									</div>
								</div>
						<?php $booking_index++; } ?> 
					</div>
				</div>
			</div>
		</div>
	</div>

<?php } // if ($itinerary_array->bookings) ?>