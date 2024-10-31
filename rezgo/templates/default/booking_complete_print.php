<?php
	// grab and decode the trans_num if it was set
	$trans_num = $site->decode(sanitize_text_field($_REQUEST['trans_num']));

	// send the user home if they shouldn't be here
	if(!$trans_num) $site->sendTo($site->base."/booking-not-found");

	$company = $site->getCompanyDetails();

	$rzg_payment_method = 'None';
?>
<html id="rezgo_html">
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta name="robots" content="noindex, nofollow">
		<title>Booking - <?php echo esc_html($trans_num); ?></title>

	<?php
		if (REZGO_WORDPRESS) {
			rezgo_plugin_scripts_and_styles();
			wp_print_scripts();
			wp_print_styles();
		}
	?>

	<?php if (!REZGO_WORDPRESS) { ?>
	<!-- Bootstrap CSS -->
	<link href="//cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-iYQeCzEYFbKjA/T2uDLTpkwGzCiq6soy8tYaI1GyVh/UjpbCx/TYkiZhlZB6+fzT" crossorigin="anonymous">

	<!-- Font awesome 5 -->
	<link href="<?php echo $site->path; ?>/css/font-awesome/css/all.min.css" rel="stylesheet">

	<!-- Rezgo stylesheet -->
	<link href="<?php echo $site->path; ?>/css/rezgo.css?v=<?php echo REZGO_VERSION; ?>" rel="stylesheet">
	<?php } ?>

	<?php if($site->exists($site->getStyles())) { ?><style><?php echo $site->getStyles(); ?></style><?php } ?>

</head>
	<body style="background-color: #FFF;">
		<div class="container-fluid rezgo-container rezgo-print-version p-helper">
			<?php if(!$site->getBookings('q='.$trans_num)) {
				$site->sendTo("/booking-not-found:".$_REQUEST['trans_num']); 
			} ?>

			<?php foreach( $site->getBookings('q='.$trans_num.'&a=forms') as $booking ): ?>
				<?php $item = $site->getTours('t=uid&q='.$booking->item_id, 0); ?>

				<?php $site->readItem($booking) ?>
				<?php $has_insurance = (int)$booking->ticket_guardian === 1 ?? 0; ?>

				<div class="rezgo-content-row rezgo-form-group">

					<h3 id="rezgo-receipt-head-your-booking" class="rezgo-confirm-complete print-header">Your booking (booked on <?php echo esc_html(date((string) $company->date_format, (int) $booking->date_purchased_local)); ?> / local time)</h3>

					<br>
					
						<div class="rezgo-form-group">
							<h3 id="rezgo-receipt-head-billing-info"><span><?php echo esc_html($booking->tour_name); ?> - <?php echo esc_html($booking->option_name); ?></span></h3>

							<table class="table rezgo-billing-cart">
								<tr class="rezgo-tr-head">
									<td class="text-start rezgo-billing-type"><label>Type</label></td>
									<td class="text-start rezgo-billing-qty"><label class="d-none d-sm-block">Qty.</label></td>
									<td class="text-start rezgo-billing-cost"><label>Cost</label></td>
									<td class="text-end rezgo-billing-total"><label>Total</label></td>
								</tr>

								<?php foreach ($site->getBookingPrices() as $price ) { ?>
									<tr>
										<td class="text-start">
											<?php echo esc_html($price->label); ?></td>
										<td class="text-start">
											<?php echo esc_html($price->number); ?></td>
										<td class="text-start">
										<?php if(isset($price->base) && $site->exists($price->base)) { ?>
											<span class="discount"><?php echo esc_html($site->formatCurrency($price->base)); ?></span>
											<?php } ?>
											&nbsp;<?php echo esc_html($site->formatCurrency($price->price)); ?></td>
										<td class="text-end">
											<?php echo esc_html($site->formatCurrency($price->total)); ?></td>
									</tr>
								<?php } // end foreach ($site->getBookingPrices() as $price ) ?>

								<tr class="rezgo-tr-subtotal">
									<td colspan="3" class="text-end"><span class="push-right"><strong>Subtotal</strong></span></td>
									<td class="text-end">
										<?php echo esc_html($site->formatCurrency($booking->sub_total)); ?></td>
								</tr>

								<?php foreach ($site->getBookingLineItems() as $line ) { ?>
									<?php $label_add = ''; ?>

									<?php if($site->exists($line->percent) || $site->exists($line->multi)) {
											$label_add = ' (';

											if($site->exists($line->percent)) $label_add .= $line->percent.'%';

											if($site->exists($line->multi)) {
												if(!$site->exists($line->percent)) $label_add .= $site->formatCurrency($line->multi);

												if($site->exists($line->meta)) {
												
											$pax_totals = array(
												'adult_num' => 'price_adult', 
												'child_num' => 'price_child', 
												'senior_num' => 'price_senior', 
												'price4_num' => 'price4', 
												'price5_num' => 'price5', 
												'price6_num' => 'price6', 
												'price7_num' => 'price7', 
												'price8_num' => 'price8', 
												'price9_num' => 'price9'
											);

											$line_pax = 0;
											foreach ($pax_totals as $p_num => $p_rate) {

												if ( (int) $booking->{$p_num} > 0 && ((float) $booking->price_range->date->{$p_rate} > (float) $line->meta)) {
													$line_pax += (int) $booking->{$p_num};
												}

											}

											$label_add .= ' x '.$line_pax;

												} else {
												
											$label_add .= ' x '.$booking->pax;
												
												}

											}

											$label_add .= ')';	
									} ?>

									<?php if( $site->exists($line->amount) ) { ?>
										<tr>
											<td colspan="3" class="text-end"><span class="push-right"><strong><?php echo esc_html($line->label); ?><?php echo esc_html($label_add); ?></strong></span></td>
											<td class="text-end"><?php echo esc_html($site->formatCurrency($line->amount)); ?></td>
										</tr>
									<?php } ?>
								<?php } ?>

								<?php 
									foreach ($site->getBookingFees() as $fee ) {
										if ($fee) {
											$title = (string)$fee->label;
											$count = (int)$fee->count == 0 ? 1 : $fee->count;
											$amount = (string)$fee->total_amount; ?>
											<?php if ($amount) { ?>
												<tr>
													<td colspan="3" class="text-end">
														<span class="push-right">
															<strong>
																<?php echo esc_html($count) .' <i class="far fa-times" style="position:relative; top:1px;"></i>'; ?>
																<?php echo esc_html($title); ?>
															</strong>
														</span>
													</td>
													<td class="text-end"><?php echo esc_html($site->formatCurrency($amount)); ?></td>
												</tr>
											<?php } ?>
										<?php } ?>
								<?php } ?>

								<tr class="rezgo-tr-subtotal summary-total">
									<td colspan="3" class="text-end"><span class="push-right"><strong>Total</strong></span></td>
									<td class="text-end">
										<strong><?php echo esc_html($site->formatCurrency($booking->overall_total)); ?></strong>
									</td>
								</tr>

								<?php if($site->exists($booking->deposit)) { ?>
									<tr class="rezgo-tr-deposit">
										<td colspan="3" class="text-end"><span class="push-right"><strong>Deposit</strong></span></td>
										<td class="text-end">
											<strong><?php echo esc_html($site->formatCurrency($booking->deposit)); ?></strong>
										</td>
									</tr>
								<?php } ?>

								<?php if($site->exists($booking->overall_paid)) { ?>
									<tr>
										<td colspan="3" class="text-end"><span class="push-right"><strong>Total Paid</strong></span></td>
										<td class="text-end">
											<strong><?php echo esc_html($site->formatCurrency($booking->overall_paid)); ?></strong>
										</td>
									</tr>
									<tr>
										<td colspan="3" class="text-end"><span class="push-right"><strong>Total&nbsp;Owing</strong></span></td>
										<td class="text-end">
											<strong><?php echo esc_html($site->formatCurrency(((float)$booking->overall_total - (float)$booking->overall_paid))); ?></strong>
										</td>
									</tr>
								<?php } ?>
								
								<?php if ($has_insurance) { ?>
									<tr style="vertical-align:center;">
										<td colspan="3" class="text-end"><span class="push-right"><strong>Enhanced Refund Protection</strong></span></td>
										<td class="text-end"> Opted In <i class="far fa-check-circle"></i></td>
									</tr>
								<?php } ?>
							</table>
						</div>

					<hr>
					
					<div class="booking-receipt-details-container">
						<div class="flex-table">
							<div id="rezgo-receipt-transnum" class="flex-table-group flex-50">
								<div class="flex-table-header rezgo-order-transnum"><span>Booking #</span></div>
								<div class="flex-table-info"><?php echo esc_html($booking->trans_num); ?></div>
							</div>

							<?php if((string) $booking->date != 'open') { ?>
								<div id="rezgo-receipt-booked-for" class="flex-table-group flex-50">
									<div class="flex-table-header"><span>Date</span></div>
										<div class="flex-table-info">
											<span class="rezgo-receipt-booked-for-date-<?php echo esc_attr($booking->item_id); ?>">
												<?php echo esc_html(date((string) $company->date_format, (int) $booking->date));  ?>
											</span>
											<span class="rezgo-receipt-booked-for-time-<?php echo esc_attr($booking->item_id); ?>">
												<?php if ($site->exists($booking->time)) { ?> at <?php echo esc_html($booking->time); ?><?php } ?>
											</span>
										</div>
								</div>
							<?php } else { ?>
								<?php if ($site->exists($booking->time)) { ?>
									<div id="rezgo-receipt-booked-for" class="flex-table-group flex-50">
										<div class="flex-table-header"><span>Time</span></div>
										<div class="flex-table-info"><span><?php echo esc_html($booking->time); ?></span></div>
									</div>
								<?php } ?>
							<?php } ?>

							<?php if(isset($booking->expiry)) { ?>
								<div id="rezgo-receipt-expires" class="flex-table-group flex-50">
									<div class="flex-table-header"><span>Expires</span></div>
									<?php if((int) $booking->expiry !== 0) { ?>
										<div class="flex-table-info"><span><?php echo esc_html(date((string) $company->date_format, (int) $booking->expiry)); ?></span></div>
									<?php } else { ?>
										<div class="flex-table-info"><span>Never</span></div>
									<?php } ?>
								</div>
							<?php } ?>

						<?php if (isset($item->duration) && (string) $item->duration != '') { ?>
							<div id="rezgo-receipt-duration" class="flex-table-group flex-50">
								<div class="flex-table-header"><span>Duration</span></div>
								<div class="flex-table-info"><span><?php echo esc_html($item->duration); ?></span></div>
							</div>
						<?php } ?>

						<?php if(isset($item->location_name) && $item->location_name != '') {
							$location = $item->location_name.', '.$item->location_address;
						} else {
							$loc = [];
							if($site->exists($item->city ?? '')) $loc[] = $item->city;
							if($site->exists($item->state ?? '')) $loc[] = $item->state;
							if($site->exists($item->country ?? '')) $loc[] = $site->countryName($item->country);
								if($loc) $location = implode(', ', $loc);	
							}
							if (isset($location) && $location != '') { ?>
								<div id="rezgo-receipt-location" class="flex-table-group flex-50">
									<div class="flex-table-header"><span>Location</span></div>
									<div class="flex-table-info"><span><?php echo esc_html($location); ?></span></div>
								</div>
							<?php } ?>

							<?php
								if (isset($item->checkin_offset) && (string) $item->checkin_offset != '') {
									$checkin_time = date("g:i A", strtotime($booking->time) - $item->checkin_offset);
								} elseif (isset($item->checkin_time) && (string) $item->checkin_time != '') {
									$checkin_time = (string) $item->checkin_time ?? '';
								}
							?>

							<?php if (isset($checkin_time)) { ?>
								<div id="rezgo-receipt-checkin-time" class="flex-table-group flex-50">
									<div class="flex-table-header"><span>Check-In Time</span></div>
									<div class="flex-table-info"><span><?php echo esc_html($checkin_time); ?></span></div>
								</div>
							<?php } ?>

							<div style="page-break-after:always;"></div>
							
							<?php if (isset($item->details->pick_up) && (string) $item->details->pick_up != '') { ?>
								<div id="rezgo-receipt-pickup" class="flex-table-group">
									<div class="flex-table-header"><i class="far fa-map-marker"></i><span>Pickup/Departure Information</span></div>
									<div class="flex-table-info indent"><span><?php echo wp_kses($item->details->pick_up, ALLOWED_HTML); ?></span></div>
								</div>
							<?php } ?>

							<?php if (isset($item->details->drop_off) && (string) $item->details->drop_off != '') { ?>
								<div id="rezgo-receipt-dropoff" class="flex-table-group">
									<div class="flex-table-header"><i class="far fa-location-arrow"></i><span>Dropoff/Return Information</span></div>
									<div class="flex-table-info indent"><span><?php echo wp_kses($item->details->drop_off, ALLOWED_HTML); ?></span></div>
								</div>
							<?php } ?>
							
							<?php if (isset($item->details->checkin) && (string) $item->details->checkin != '') { ?>
								<div id="rezgo-receipt-checkin-instructions" class="flex-table-group">
									<div class="flex-table-header"><i class="far fa-ticket"></i><span>Check-In Instructions</span></div>
									<div class="flex-table-info indent"><span><?php echo wp_kses($item->details->checkin, ALLOWED_HTML); ?></span></div>
								</div>
							<?php } ?>

							<?php if (isset($item->details->bring) && (string) $item->details->bring != '') { ?>
								<div id="rezgo-receipt-thingstobring" class="flex-table-group">
									<div class="flex-table-header"><i class="far fa-suitcase"></i><span>Things to bring</span></div>
									<div class="flex-table-info indent"><span><?php echo wp_kses($item->details->bring, ALLOWED_HTML); ?></span></div>
								</div>
							<?php } ?>

							<?php if (isset($item->details->itinerary) && (string) $item->details->itinerary != '') { ?>
								<div id="rezgo-receipt-itinerary" class="flex-table-group rezgo-receipt-itinerary">
									<div class="flex-table-header"><i class="far fa-clipboard-list"></i><span>Itinerary</span></div>
									<div class="flex-table-info indent"><span><?php echo wp_kses($item->details->itinerary, ALLOWED_HTML); ?></span></div>
								</div>
							<?php } ?>


							<?php if (isset($booking->pickup->name) && (string) $booking->pickup->name != '') { ?>

								<?php $pickup_detail = $site->getPickupItem((string) $booking->item_id, (int) $booking->pickup->id); ?>
								<div id="rezgo-receipt-pickup" class="flex-table-group">
									<div class="flex-table-header"><i class="far fa-info-square"></i><span>Pick Up Information</span></div>
									<div class="flex-table-info indent"><span>
										<?php echo esc_html($booking->pickup->name); ?> <?php if ($booking->pickup->time) { ?> at <?php echo esc_html($booking->pickup->time); ?> <?php } ?> <br>

											<?php if($site->exists($pickup_detail->lat) && $site->exists($pickup_detail->location_address)) {  ?>
												<i class="far fa-map-marker"></i>&nbsp;<a class="underline-link" href="https://www.google.com/maps/place/<?php echo urlencode(esc_attr($pickup_detail->lat.','.$pickup_detail->lon)); ?>" target="_blank"><?php echo esc_html($pickup_detail->location_address); ?></a><br>
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
															<iframe width="100%" height="372" frameborder="0" style="border:0;margin-bottom:0;margin-top:-130px;" src="https://www.google.com/maps/embed/v1/place?key='.esc_attr(GOOGLE_API_KEY).'&maptype='.esc_attr($embed_type).'&q='.esc_attr($pickup_detail->lat).','.esc_attr($pickup_detail->lon).'&center='.esc_attr($pickup_detail->lat).','.esc_attr($pickup_detail->lon).'&zoom='.esc_attr($map_zoom).'"></iframe>
														</div>
														</div>
													';
														
												}

												if($pickup_detail->media) { 

													echo '
														<div class="col-12 rezgo-pickup-receipt-data">
														<img src="'.esc_url($pickup_detail->media->image[0]->path).'" alt="'.esc_attr($pickup_detail->media->image[0]->caption).'" style="max-width:100%;"> 
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

						</div> <!-- flex-table -->
					</div>
				</div>

				<hr>

				<?php if(isset($item->lat) && $item->lat != '' && isset($item->lon) && $item->lon != '' && GOOGLE_API_KEY && !REZGO_CUSTOM_DOMAIN) { ?>

					<?php if ($item->map_type == 'ROADMAP') {
					$embed_type = 'roadmap';
					} else {
					$embed_type = 'satellite';
					} ?>

					<!-- start receipt map -->	
					<div style="page-break-after:always;"></div>

					<div class="row" id="rezgo-receipt-map-container">
						<div class="col-12">
							<h3 id="rezgo-receipt-head-map"><span>Map</span></h3>
							<?php if ($item->location_name) { ?>
								<div id="rezgo-receipt-map-location">
									<strong><?php echo esc_html($item->location_name); ?></strong>	
									<br>
									<?php echo esc_html($item->location_address); ?>
								</div>
							<?php } ?>
							<div id="rezgo-receipt-map">
								<iframe width="100%" height="390" frameborder="0" style="border:0;margin-bottom:0;pointer-events:none;" src="https://www.google.com/maps/embed/v1/view?key=<?php echo esc_attr(GOOGLE_API_KEY); ?>&maptype=<?php echo esc_attr($embed_type); ?>&center=<?php echo esc_attr($item->lat); ?>,<?php echo esc_attr($item->lon); ?>&zoom=<?php echo (($item->zoom != '' && $item->zoom > 0) ? esc_attr($item->zoom) : 6)?>"></iframe>
							</div>
						</div>
					</div>	
					<!-- end receipt map -->
				<?php } ?>

				<div style="page-break-after:always;"></div>

				<?php if($booking->payment_method != 'None') {
					$rzg_payment_method = $booking->payment_method;
				} ?>

				<div class="rezgo-form-group rezgo-confirmation-additional-info">
					<div class="billing-info-wrapper">
						<h3 id="rezgo-receipt-head-billing-info"><span>Billing Information</span></h3>

						<div id="billing-info-row" class="flex-row">

							<?php if ($site->exists($booking->first_name)){ ?>
							<div class="flex-33" id="rezgo-receipt-name">
								<p class="rezgo-receipt-pax-label"><span>Name</span></p>
								<p class="rezgo-receipt-pax-info"><?php echo esc_html($booking->first_name); ?> <?php echo esc_html($booking->last_name); ?></p>
							</div>
							<?php } ?>
							
							<?php if ($site->exists($booking->phone_number)){ ?>
							<div class="flex-33" id="rezgo-receipt-phone">
								<p class="rezgo-receipt-pax-label"><span>Phone Number</span></p>
								<p class="rezgo-receipt-pax-info"><?php echo esc_html($booking->phone_number); ?></p>
							</div>
							<?php } ?>

							<?php if ($site->exists($booking->email_address)){ ?>
							<div class="flex-33" id="rezgo-receipt-email">
								<p class="rezgo-receipt-pax-label"><span>Email Address</span></p>
								<p class="rezgo-receipt-pax-info"><?php echo esc_html($booking->email_address); ?></p>
							</div>
							<?php } ?>
							
							<?php if ($site->exists($booking->address_1)){ ?>
							<div class="flex-33" id="rezgo-receipt-address">
								<p class="rezgo-receipt-pax-label"><span>Address</span></p>
								<p class="rezgo-receipt-pax-info">
									<?php echo esc_html($booking->address_1); ?>
									<?php echo ($site->exists($booking->address_2)) ? '<br>'.esc_html($booking->address_2) : ''; ?>
									<?php echo ($site->exists($booking->city)) ? '<br>'.esc_html($booking->city) : ''; ?>
									<?php echo ($site->exists($booking->stateprov)) ? esc_html($booking->stateprov) : ''; ?>
									<?php echo ($site->exists($booking->postal_code)) ? '<br>'.esc_html($booking->postal_code) : ''; ?>
									<?php echo esc_html($site->countryName($booking->country)); ?>
								</p>
							</div>
							<?php } ?>

							<?php if($booking->overall_total > 0) { ?>
								<?php if ($site->exists($booking->payment_method)){ ?>
									<div class="flex-33" id="rezgo-receipt-payment-method">
										<p class="rezgo-receipt-pax-label"><span>Payment Method</span></p>
										<p class="rezgo-receipt-pax-info"><?php echo esc_html($booking->payment_method); ?></p>
									</div>
								<?php } ?>

								<?php if($booking->payment_method == 'Credit Cards') { ?>
									<div class="flex-33" id="rezgo-receipt-cardnum">
										<p class="rezgo-receipt-pax-label"><span>Card Number</span></p>
										<p class="rezgo-receipt-pax-info">****<?php echo esc_html($booking->card_number); ?></p>
									</div>
								<?php } ?>

								<?php if($site->exists($booking->payment_method_add->label)) { ?>
									<?php if ((string)$booking->payment_method != 'None') { ?>
										<div class="flex-33">
											<p class="rezgo-receipt-pax-label"><?php echo esc_html($booking->payment_method_add->label); ?></p>
											<p class="rezgo-receipt-pax-info"><?php echo esc_html($booking->payment_method_add->value); ?></p>
										</div>
									<?php } ?>
								<?php } ?>
							<?php } ?>

							<div class="flex-33" id="rezgo-receipt-payment-status">
								<p class="rezgo-receipt-pax-label"><span>Payment Status</span></p>
								<p class="rezgo-receipt-pax-info">
									<?php echo (($booking->status == 1) ? 'CONFIRMED' : ''); ?>
									<?php echo (($booking->status == 2) ? 'PENDING' : ''); ?>
									<?php echo (($booking->status == 3) ? 'CANCELLED' : ''); ?>
								</p>
							</div>

							<?php if($site->exists($booking->trigger_code)) { ?>
								<div class="flex-33" id="rezgo-receipt-trigger">
									<p class="rezgo-receipt-pax-label"><span>Promotional Code</span></p>
									<p class="rezgo-receipt-pax-info"><?php echo esc_html($booking->trigger_code); ?></p>
								</div>
							<?php } ?>
		
							<?php if($site->exists($booking->refid)) { ?>
								<div class="flex-33" id="rezgo-receipt-refid">
									<p class="rezgo-receipt-pax-label"><span>Referral ID</span></p>
									<p class="rezgo-receipt-pax-info"><?php echo esc_html($booking->refid); ?></p>
								</div>
							<?php } ?>

						</div>
						
						<?php 
						//We will only show this container when the booking is non-pos booking
						if(!empty($booking->agree_terms) && empty($booking->back_end)) { ?>
						<hr>
						<div class="agree-terms-container">
							<div class="rezgo-receipt-primary-forms">
								<div class="question-flex-container">
									<i class="far fa-dot-circle"></i> <p class="form-question">I Agree to the Terms and Conditions and Privacy Policy</p>
								</div>
								<hr class="form-separator">
								<p class="form-answer"><?php echo !empty($booking->agree_terms) ? 'Yes' : 'No' ?></p>
							</div>
						</div>
						<?php } ?>
						
						<div style="page-break-after:always;"></div>
						
						<?php 
							$booking_forms = 0;
							$booking_passengers = 0;
						
							if ($booking->availability_type != 'product') {
								if(is_array($site->getBookingForms())) { 
									$booking_forms = count($site->getBookingForms());
								}

								if(is_array($site->getBookingPassengers())) { 
									$booking_passengers = count($site->getBookingPassengers());
								}
							}
						?>

						<?php if($booking_forms > 0 || $booking_passengers > 0) { ?>
							<hr>
							<!-- primary forms -->
							<div class="booking-receipt-forms-container">
								
								<h3 id="rezgo-receipt-head-customer-info"><span>Customer Information</span></h3>

								<?php if ($booking_forms > 0) { ?>
									<div class="p-forms-table">
										<?php foreach ($site->getBookingForms() as $form ) { ?>
											<?php $price_checkbox = (in_array($form->type, array('checkbox','checkbox_price'))) ? 1 : 0; ?>

											<div id="" class="rezgo-receipt-primary-forms">
												<div class="question-flex-container">
													<i class="far fa-dot-circle"></i> <p class="form-question"><?php echo esc_html($form->question); ?></p>
												</div>
												<hr class="form-separator">
												<p class="form-answer">
													<?php 
														unset($form_answer);
														if ($form->type == 'select_price') {
															echo $form_answer = ( $site->exists($form->answer) ) ? $form->answer : 'Not Selected';
														} else if ($price_checkbox){
															echo $form_answer = ( $site->exists($form->answer) ) ? 'Yes' : 'No';
														} else {
															echo $form_answer = ( $site->exists($form->answer) ) ? $form->answer : 'N/A';
														}
														if (!in_array($form_answer, array('No', 'Not Selected', 'N/A')) && $form->price != 0) {
															$pre = (strpos($form->price, '-') === false) ? '+ ' : '';
															echo ' (' . $pre.$site->formatCurrency($form->price) .')'; 
														}
													?>
												</p>
											</div>

											<?php if ($form->options_instructions) { ?>
											
												<?php
													$options = explode(',', (string) $form->options);
													$options_instructions = explode(',', (string) $form->options_instructions);
													$option_extras = array_combine($options, $options_instructions);
												?>

												<?php if ( in_array($form->type, array('select','multiselect')) ) { ?>
												
													<?php if ( $form->type == 'multiselect' ) { ?>
													
														<?php
															$multi_answers = explode(',', (string) $form->answer);
															$multi_answer_list = '';
															foreach ($multi_answers as $answer) {
																$answer_key = html_entity_decode(trim($answer), ENT_QUOTES);
																if ( array_key_exists( $answer_key, $option_extras ) ) {
																	$multi_answer_list .= '<p class="form-answer rezgo-form-extras">'.$option_extras[$answer_key].'</p>';
																}
															}
														?>
														
														<?php if ( $multi_answer_list != '' ) { ?>
															<div id="" class="rezgo-receipt-primary-forms">
																<p class="form-question"></p>
																<div class="multiselect-answer-group"><?php echo wp_kses($multi_answer_list, array('p' => array('class' => array())) ); ?></div>
															</div>
														<?php } ?>
													
													<?php } else { ?>
														
														<?php $answer_key = html_entity_decode((string) $form->answer, ENT_QUOTES); ?>
														<?php if ( array_key_exists( $answer_key, $option_extras ) ) { ?>
															<div id="" class="rezgo-receipt-primary-forms">
																<p class="form-question"></p>
																<div class="multiselect-answer-group"><p class="form-answer rezgo-form-extras"><?php echo esc_html($option_extras[$answer_key]); ?></p></div>
															</div>
														<?php } ?>
													
													<?php } ?>
												
												<?php } ?>
											<?php } //if ($form->options_instructions) ?>
										<?php } ?>
									</div> <!-- p-forms-table -->
								<?php } //if ($booking_forms > 0) ?>

							</div> <!-- booking-receipt-forms-container -->

							<?php if ($booking_passengers > 0) { ?>
								<!-- guest forms -->
								<div class="booking-receipt-forms-container <?php echo esc_attr($item->group); ?>_pax_info">

									<div class="flex-table">
										<?php foreach ($site->getBookingPassengers() as $passenger) { ?>

											<div class="flex-table-group bordered flex-50 rezgo-receipt-pax">
												<div class="flex-table-header"><span><i class="far fa-user"></i> <?php echo esc_html($passenger->label); ?>&nbsp;(<?php echo esc_html($passenger->num); ?>)</span></div>

												<?php // check if forms and guest information is present
													if ( (int)$passenger->total_forms == 0 && 
														!$site->exists($passenger->first_name) && 
														!$site->exists($passenger->last_name) && 
														!$site->exists($passenger->phone_number) && 
														!$site->exists($passenger->email_address) ) { ?>

													<p id="no-guest-info" class="rezgo-receipt-pax-info"><span>No Guest Information entered</span></p><br>

												<?php } else { // if forms and guest information is present ?>

													<?php $pax_name_display = (string) $passenger->first_name == '' && (string) $passenger->last_name == '' ? 'd-none' : ''; ?>
													<?php $pax_phone_display = (string) $passenger->phone_number == '' ? 'd-none' : ''; ?>
													<?php $pax_email_display = (string) $passenger->email_address == '' ? 'd-none' : ''; ?>

													<div class="flex-row">
														<div class="flex-50 billing-payment-info-box">
															<p class="rezgo-receipt-pax-label <?php echo esc_attr($pax_name_display); ?>" id="rezgo-label-name-<?php echo esc_attr($passenger->id); ?>">Name</p>
															<p class="rezgo-receipt-pax-info <?php echo esc_attr($pax_name_display); ?>" id="rezgo-pax-name-<?php echo esc_attr($passenger->id); ?>"><?php echo esc_html($passenger->first_name); ?> <?php echo esc_html($passenger->last_name); ?></p>
														</div>
														<div class="flex-50 billing-payment-info-box">
															<p class="rezgo-receipt-pax-label <?php echo esc_attr($pax_phone_display); ?> " id="rezgo-label-phone-<?php echo esc_attr($passenger->id); ?>">Phone Number</p>
															<p class="rezgo-receipt-pax-info <?php echo esc_attr($pax_phone_display); ?>" id="rezgo-pax-phone-<?php echo esc_attr($passenger->id); ?>"><?php echo esc_html($passenger->phone_number); ?></p>
														</div>
													</div>

													<p class="rezgo-receipt-pax-label <?php echo esc_attr($pax_email_display); ?>" id="rezgo-label-email-<?php echo esc_attr($passenger->id); ?>">Email</p>
													<p class="rezgo-receipt-pax-info <?php echo esc_attr($pax_email_display); ?>" id="rezgo-pax-email-<?php echo esc_attr($passenger->id); ?>"><?php echo esc_html($passenger->email_address); ?></p>

													<?php if ($passenger->forms->form) { ?>

														<?php foreach ($passenger->forms->form as $form ) { ?>              
															<?php $price_checkbox = (in_array($form->type, array('checkbox','checkbox_price'))) ? 1 : 0; ?>

															<div id="" class="rezgo-receipt-guest-forms">
																<div class="question-flex-container">
																	<i class="far fa-dot-circle"></i> <span class="form-question"><?php echo esc_html($form->question); ?></span>
																</div>
																<hr class="form-separator">
																<span class="form-answer">
																	<?php 
																		unset($form_answer);
																		if ($form->type == 'select_price') {
																			echo $form_answer = ( $site->exists($form->answer) ) ? $form->answer : 'Not Selected';
																		} else if ($price_checkbox){
																			echo $form_answer = ( $site->exists($form->answer) ) ? 'Yes' : 'No';
																		} else {
																			echo $form_answer = ( $site->exists($form->answer) ) ? $form->answer : 'N/A';
																		}
																		if (!in_array($form_answer, array('No', 'Not Selected', 'N/A')) && $form->price != 0) {
																			$pre = (strpos($form->price, '-') === false) ? '+ ' : '';
																			echo ' (' . $pre.$site->formatCurrency($form->price) .')'; 
																		}
																	?>
																</span>
															</div>
														
															<?php if ($form->options_instructions) { ?>
														
																<?php
																$pax_options = explode(',', (string) $form->options);
																$pax_options_instructions = explode(',', (string) $form->options_instructions);
																$pax_option_extras = array_combine($pax_options, $pax_options_instructions);
																?>  
																
																<?php if ( in_array($form->type, array('select','multiselect')) ) { ?>
																
																	<?php if ( $form->type == 'multiselect' ) { ?>
																		
																		<?php
																			$pax_multi_answers = explode(',', (string) $form->answer);
																			$pax_multi_answer_list = '';
																			foreach ($pax_multi_answers as $pax_answer) {
																				$answer_key = html_entity_decode(trim($pax_answer), ENT_QUOTES);
																				if ( array_key_exists( $answer_key, $pax_option_extras ) ) {
																					$pax_multi_answer_list .= '<p class="form-answer rezgo-form-extras">'.$pax_option_extras[$answer_key].'</p>';
																				}
																			}
																		?>

																		<?php if ( $pax_multi_answer_list != '' ) { ?>
																			<div id="" class="rezgo-receipt-guest-forms">
																				<p class="form-question"></p>
																				<div class="multiselect-answer-group"><?php echo wp_kses($pax_multi_answer_list, array('p' => array('class' => array())) ); ?></div>
																			</div>
																		<?php } ?>
																	
																	<?php } else { ?>

																		<?php $answer_key = html_entity_decode((string) $form->answer, ENT_QUOTES); ?>
																		<?php if ( array_key_exists( $answer_key, $pax_option_extras ) ) { ?>
																			<div id="" class="rezgo-receipt-guest-forms">
																				<p class="form-question"></p>
																				<div class="multiselect-answer-group"><p class="form-answer rezgo-form-extras"><?php echo esc_html($pax_option_extras[$answer_key]); ?></p></div>
																			</div>
																		<?php } ?>

																	<?php } // if ($form->type) ?>
																
																<?php } // if (in_array($form->type)) ?>
															
															<?php } // if ($form->options_instructions) ?>
														
														<?php } // foreach ($passenger->forms) ?>

													<?php } // if ($passenger->forms->form) ?>

												<?php } // if forms and guest information is present ?>

											</div>

										<?php } // foreach ($site->getBookingPassengers() as $passenger) ?>

									</div> <!-- flex-table -->

								</div> <!-- forms-container -->
							
								<?php foreach ($site->getBookingPassengers() as $passenger ) { ?>
							
								<div class="col-sm-6 col-12 d-none">
								
									<table border="0" cellspacing="0" cellpadding="2" class="rezgo-table-list">
								
									<tr class="rezgo-receipt-pax">
										<td class="rezgo-td-label"><?php echo esc_html($passenger->label); ?>&nbsp;<?php echo esc_html($passenger->num); ?></td>
										<td class="rezgo-td-data">
										<?php if( $booking->waiver == '2' ) { ?>
												<?php 
												if (REZGO_WORDPRESS) { 
													$waiver_link = esc_js($site->base).'/waiver/'.esc_js($site->waiver_encode($booking->trans_num).'-'.$passenger->id);
												} else {
													$waiver_link = $site->base.'/waiver/'.$site->waiver_encode($booking->trans_num.'-'.$passenger->id);
												}
											?>
											<button class="btn rezgo-btn-default btn-sm rezgo-waiver-sign" type="button" data-paxid="<?php echo esc_attr($passenger->id); ?>" id="rezgo-sign-<?php echo esc_attr($passenger->id); ?>" <?php echo (($passenger->signed) ? ' style="display:none;"' : ''); ?> onclick="<?php echo LOCATION_WINDOW; ?>.location.href='<?php echo $waiver_link; ?>'">
											<span><i class="fa fa-pencil-square-o"></i>&nbsp;<span id="rezgo-sign-txt-<?php echo esc_attr($passenger->id); ?>">sign waiver</span></span>
											</button>
											
											<span id="rezgo-signed-<?php echo esc_attr($passenger->id); ?>" <?php echo (($passenger->signed) ? '' : 'style="display:none;"'); ?>>
											<span class="btn btn-sm rezgo-signed">signed</span>
											<span class="rezgo-signed-check"><i class="fa fa-check" aria-hidden="true"></i></span>
											<input type="hidden" id="rezgo-sign-count-<?php echo esc_attr($passenger->id); ?>" class="rezgo-sign-count" value="<?php echo (($passenger->signed) ? '1' : '0'); ?>" />
											</span>
											
										<?php } ?>          
										&nbsp;
										</td>
									</tr>
									
										<?php $pax_name_display = (string) $passenger->first_name == '' && (string) $passenger->last_name == '' ? 'd-none' : ''; ?>
										<?php $pax_phone_display = (string) $passenger->phone_number == '' ? 'd-none' : ''; ?>
										<?php $pax_email_display = (string) $passenger->email_address == '' ? 'd-none' : ''; ?>

										<tr class="rezgo-receipt-name">
											<td <?php echo esc_attr($pax_name_display); ?>class="rezgo-td-label" id="rezgo-label-name-<?php echo esc_attr($passenger->id); ?>">Name</td>
											<td <?php echo esc_attr($pax_name_display); ?>class="rezgo-td-data" id="rezgo-pax-name-<?php echo esc_attr($passenger->id); ?>"><?php echo esc_html($passenger->first_name); ?> <?php echo esc_html($passenger->last_name); ?></td>
											</tr>
							
											<tr class="rezgo-receipt-pax-phone">
											<td <?php echo esc_attr($pax_phone_display); ?>class="rezgo-td-label" id="rezgo-label-phone-<?php echo esc_attr($passenger->id); ?>">Phone Number</td>
											<td <?php echo esc_attr($pax_phone_display); ?>class="rezgo-td-data" id="rezgo-pax-phone-<?php echo esc_attr($passenger->id); ?>"><?php echo esc_html($passenger->phone_number); ?></td>
											</tr>
							
											<tr class="rezgo-receipt-pax-email">
											<td <?php echo esc_attr($pax_email_display); ?>class="rezgo-td-label" id="rezgo-label-email-<?php echo esc_attr($passenger->id); ?>">Email</td>
											<td <?php echo esc_attr($pax_email_display); ?>class="rezgo-td-data" id="rezgo-pax-email-<?php echo esc_attr($passenger->id); ?>"><?php echo esc_html($passenger->phone_number); ?></td>
										</tr>
						
										<?php foreach ($passenger->forms->form as $form ) { ?>              
										
											<?php if (in_array($form->type, array('checkbox','checkbox_price'))) { ?>
												<?php if($site->exists($form->answer)) { $form->answer = 'Yes'; } else { $form->answer = 'No'; } ?>
											<?php } ?>
							
											<tr class="rezgo-receipt-guest-forms">
												<td class="rezgo-td-label"><?php echo esc_html($form->question); ?></td>
												<td class="rezgo-td-data"><?php echo esc_html($form->answer); ?>&nbsp;</td>
											</tr>
											
											<?php if ($form->options_instructions) { ?>
										
												<?php
												$pax_options = explode(',', (string) $form->options);
												$pax_options_instructions = explode(',', (string) $form->options_instructions);
												$pax_option_extras = array_combine($pax_options, $pax_options_instructions);
												?>  
												
												<?php if ( in_array($form->type, array('select','multiselect')) ) { ?>
												
													<?php if ( $form->type == 'multiselect' ) { ?>
														
														<?php
															$pax_multi_answers = explode(',', (string) $form->answer);
															$pax_multi_answer_list = '';
															foreach ($pax_multi_answers as $pax_answer) {
																$answer_key = html_entity_decode(trim($answer), ENT_QUOTES);
																if ( array_key_exists( $answer_key, $pax_option_extras ) ) {
																	$pax_multi_answer_list .= '<li>'.$pax_option_extras[$answer_key].'</li>';
																}
															}
														?>
														
														<?php if ( $pax_multi_answer_list != '' ) { ?>
															<tr class="rezgo-receipt-guest-forms">
																<td class="rezgo-td-label">&nbsp;</td>
																<td class="rezgo-td-data rezgo-form-extras"><ul><?php echo esc_html($pax_multi_answer_list); ?></ul></td>
															</tr>
														<?php } ?>
													
													<?php } else { ?>
													
														<?php $answer_key = html_entity_decode((string) $form->answer, ENT_QUOTES); ?>
														<?php if ( array_key_exists( $answer_key, $pax_option_extras ) ) { ?>
															<tr class="rezgo-receipt-guest-forms">
																<td class="rezgo-td-label">&nbsp;</td>
																<td class="rezgo-td-data rezgo-form-extras"><?php echo esc_html($pax_option_extras[$answer_key]); ?>&nbsp;</td>
															</tr>
														<?php } ?>
													
													<?php } // if ($form->type) ?>
												
												<?php } // if (in_array($form->type)) ?>
											
											<?php } // if ($form->options_instructions) ?>
										
										<?php } // foreach ($passenger->forms) ?>
										
									</table>
								</div> 
							<?php } // foreach ($site->getBookingPassengers() as $passenger) ?>
						<?php } ?>
					</div>
				<?php } // if(booking_passengers > 0) ?>

				</div><!-- // .rezgo-confirmation-additional-info --> 

				<div style="page-break-after:always;"></div>

				<div class="rezgo-company-info div-order-booking" id="rezgo-receipt-customer-service-section">
					<h3 id="rezgo-receipt-head-cancel"><span>Cancellation Policy</span></h3>

					<p>
					<?php if($site->exists($booking->rezgo_gateway)) { ?>
						Canceling a booking with Rezgo can result in cancellation fees being
						applied by Rezgo, as outlined below. Additional fees may be levied by
						the individual supplier/operator (see your Rezgo 
						<?php echo ((string) $booking->ticket_type == 'ticket') ? 'Ticket' : 'Voucher' ?> for specific
						details). When canceling any booking you will be notified via email,
						facsimile or telephone of the total cancellation fees.<br>
						<br>
						1. Event, Attraction, Theater, Show or Coupon Ticket<br>
						These are non-refundable in all circumstances.<br>
						<br>
						2. Gift Certificate<br>
						These are non-refundable in all circumstances.<br>
						<br>
						3. Tour or Package Commencing During a Special Event Period<br>
						These are non-refundable in all circumstances. This includes,
						but is not limited to, Trade Fairs, Public or National Holidays,
						School Holidays, New Year's, Thanksgiving, Christmas, Easter, Ramadan.<br>
						<br>
						4. Other Tour Products & Services<br>
						If you cancel at least 7 calendar days in advance of the
						scheduled departure or commencement time, there is no cancellation
						fee.<br>
						If you cancel between 3 and 6 calendar days in advance of the
						scheduled departure or commencement time, you will be charged a 50%
						cancellation fee.<br>
						If you cancel within 2 calendar days of the scheduled departure
						or commencement time, you will be charged a 100% cancellation fee. <br>
						<br>
					<?php } else { ?>
						<?php if($site->exists($item->details->cancellation ?? '')) { ?>
							<?php echo wp_kses($item->details->cancellation, ALLOWED_HTML); ?>
						<?php } ?>
					<?php } ?>

					View terms and conditions at <strong>https://<?php echo esc_html($site->getDomain()); ?>.rezgo.com/terms</strong>
					</p>

					<hr>

					<div class="rezgo-receipt-footer-address-container">
						<?php if($site->exists($booking->rid ?? '')) { ?>

							<div class="rezgo-cancellation-policy-address">
								<h3 id="rezgo-receipt-head-customer-service"><span>Customer Service</span></h3>

								<?php if($site->exists($booking->rezgo_gateway)) { ?>
									<strong class="company-name">Rezgo.com</strong>
									<address>
									Rezgo.com<br>
									Attn: Partner Bookings<br>
									333 Brooksbank Avenue<br>
									Suite 718<br>
									North Vancouver, BC<br>
									Canada V7J 3V8<br>
									</address>
									<span>
										<i class="fal fa-phone fa-sm"></i>&nbsp;&nbsp;
										<a href="tel:(604) 983-0083">
											(604) 983-0083
										</a> <br>
										<i class="fal fa-envelope fa-sm"></i>&nbsp;&nbsp;
										<a href="mailto:bookings@rezgo.com">bookings@rezgo.com</a> 
									</span>

								<?php } else { ?>
									<?php $company = $site->getCompanyDetails('p'.$booking->rid); ?>
									<strong class="company-name"><?php echo esc_html($company->company_name); ?></strong><br>
									<address>
										<?php echo esc_html($company->address_1); ?>
										<?php echo ($site->exists($company->address_2)) ? '<br>'.esc_html($company->address_2) : ''; ?>
										<?php echo ($site->exists($company->city)) ? '<br>'.esc_html($company->city) : ''; ?>
										<?php echo ($site->exists($company->state_prov)) ? esc_html($company->state_prov) : ''; ?>
										<?php echo ($site->exists($company->postal_code)) ? '<br>'.esc_html($company->postal_code) : ''; ?>
										<?php echo esc_html($site->countryName($company->country)); ?>
									</address>

									<span>
										<?php if($site->exists($company->phone)) { ?>
											<i class="fal fa-phone fa-sm"></i>&nbsp;&nbsp;
											<a href="tel:<?php echo esc_attr($company->phone); ?>">
											<?php echo esc_html($company->phone); ?>
											</a> 
										<?php } ?><br>
										<?php if($site->exists($company->email)) { ?>
											<i class="fal fa-envelope fa-sm"></i>&nbsp;&nbsp;
											<a href="mailto:<?php echo esc_attr($company->email); ?>">
											<?php echo esc_html($company->email); ?>
											</a> 
										<?php } ?>
									</span>
									<?php if($site->exists($company->tax_id)) { ?><br>Tax ID: <?php echo esc_html($company->tax_id); ?><?php } ?>

								<?php } ?>
							</div>
						<?php } ?>

						<div class="rezgo-cancellation-policy-address">
							<h3 id="rezgo-receipt-head-provided-by"><span>Service Provided by</span></h3>

							<?php $company = $site->getCompanyDetails($booking->cid); ?>
							<strong class="company-name"><?php echo esc_html($company->company_name); ?></strong>

							<address>
								<?php echo esc_html($company->address_1); ?>
								<?php echo ($site->exists($company->address_2)) ? '<br>'.esc_html($company->address_2) : ''; ?>
								<?php echo ($site->exists($company->city)) ? '<br>'.esc_html($company->city) : ''; ?>
								<?php echo ($site->exists($company->state_prov)) ? esc_html($company->state_prov) : ''; ?>
								<?php echo ($site->exists($company->postal_code)) ? '<br>'.esc_html($company->postal_code) : ''; ?>
								<?php echo esc_html($site->countryName($company->country)); ?>
							</address>

							<span>
								<?php if($site->exists($company->phone)) { ?>
									<i class="fal fa-phone fa-sm"></i>&nbsp;&nbsp;
									<a href="tel:<?php echo esc_attr($company->phone); ?>">
										<?php echo esc_html($company->phone); ?>
									</a> 
								<?php } ?><br>
								<?php if($site->exists($company->email)) { ?>
									<i class="fal fa-envelope fa-sm"></i>&nbsp;&nbsp;
									<a href="mailto:<?php echo esc_attr($company->email); ?>">
										<?php echo esc_html($company->email); ?>
									</a> 
								<?php } ?>
							</span>
							<?php if($site->exists($company->tax_id)) { ?><br>Tax ID: <?php echo esc_html($company->tax_id); ?><?php } ?>
						</div>
					</div><!-- // .rezgo-receipt-footer-address-container --> 
				</div><!-- // .rezgo-company-info --> 
			<?php endforeach; ?>
		</div>
	</body>
</html>