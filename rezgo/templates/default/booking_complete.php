<?php
	$trans_num = $site->decode($_REQUEST['trans_num']);

	// send the user home if they shouldn't be here
	if(!$trans_num) $site->sendTo($site->base."/booking-not-found:empty");

	// start a session so we can grab the analytics code
	// unset promo session and cookie
	$site->resetPromoCode();
	session_start();

	// unset lead session and cookie
	$site->resetBookingSource();
	$site->setTimeZone();
	$company = $site->getCompanyDetails();
	$tz_offset = $company->time_format;
?>

<div class="container-fluid rezgo-container rezgo-booking-complete-container">
	<div class="jumbotron rezgo-booking">
		<?php if(!$site->getBookings('q='.$trans_num)) {
			$site->sendTo("/booking-not-found:".sanitize_text_field($_REQUEST['trans_num'])); 
		} ?>

		<?php foreach ($site->getBookings('q='.$trans_num.'&a=forms') as $booking) { ?>
			<?php $item = $site->getTours('t=uid&q='.$booking->item_id, 0); ?>
			
			<?php $site->readItem($booking); ?>
			<?php $has_insurance = (int)$booking->ticket_guardian === 1 ?? 0; ?>

			<?php
				$availability_type = (string)$booking->availability_type;
				$booking_date = $availability_type == 'open' ? 'open' : date('Y-m-d', (string)$booking->date);

				$item = $site->getTours('t=uid&q='.$booking->item_id.'&d='.$booking_date , 0); 
				$available = $item[0] ?? 0;

				$booking_time = (string)$booking->time;
				$booking_expiry = (int)$booking->expiry;
				$booking_cancel = (float)$item->cancel;
				$booking_cutoff = (float)$item->cutoff;
				$booking_start = strtotime((string)$booking_date.$booking->time);
				$cancel_time = strtotime('-'.$booking_cancel.' hours', $booking_start);
				$cutoff_time = strtotime('-'.$booking_cutoff.' hours', $booking_start);
				$checkin_state = (int)$booking->checkin_state;

				$now = strtotime($tz_offset.' hours', time());

				// account for booking expiry if set
				if ($booking_expiry != 0) {	
					$booking_expired = $now > $booking_expiry ? 1 : 0;
				} else {
					$booking_expired = 0;
				}

				// account for cancellation window
				if ($booking->availability_type != 'open') {
					$passed = $now > $cancel_time ? 1 : 0;
				} else {
					$passed = 0;
				}
				
				if ($booking->reseller) {
					$reseller_locked = $booking->reseller == 2 ?? 0;
				}

				$booking_edit_enabled = (int) $company->booking_edit != 0 ? 1 : 0;
				$booking_cancellation_enabled = (int) $company->booking_edit_cancellation != 0 ? 1 : 0;

				$booking_edit = ( ($booking_edit_enabled || $booking_cancellation_enabled) &&
								  $available &&
								  !$checkin_state &&
								  $booking->status != 3 &&
								  !$booking_expired  &&
								  !$passed &&
								  !$reseller_locked ) ? 1 : 0;
			?>

			<?php 
				if(
					!$company->manual_tickets &&
					($booking->status == 1 || $booking->status == 4) && 
					(
						($booking->availability_type == 'date' && (int) $booking->date > strtotime('yesterday')) || 
						($booking->availability_type == 'open' && $booking->checkin_state == 0)
					)
				) { 
					$show_voucher = true;
				} else {
					$show_voucher = false;
				}
			?>      
		
			<div class="row rezgo-confirmation-head">

				<?php if($site->exists($booking->order_code)) { ?>
					<div id="rezgo-booking-complete-crumb" class="row">
						<ol class="breadcrumb">
							<li id="rezgo-edit-your-booking" class="rezgo-breadcrumb-order">
								<?php $summary_link = $site->base.'/complete/'.$site->encode($booking->order_code); ?>
								<a id="rezgo-back-to-summary" class="underline-link text-white" href="<?php echo esc_url($summary_link); ?>">
									<i class="far fa-angle-left"></i><span class="default">Order Summary</span>
									<span class="custom"></span>
								</a>
							</li>
							<li id="booking-edit-crumb-date" class="rezgo-breadcrumb-info active">
								<span class="default">Booking Details</span>
								<span class="custom"></span>
							</li>
						</ol>
					</div>
				<?php } ?>

				<?php if($booking->status == 1 OR $booking->status == 4) { ?>
        			<?php $status_class = 'rezgo-complete'; ?>
					<h3 class="rezgo-confirm-complete"><span>BOOKING COMPLETE</span></h3>

					<?php if ($show_voucher) { ?>
						<p class="rezgo-confirm-complete"><span>Click on the button below for your printable <?php echo ((string) $booking->ticket_type == 'ticket') ? 'ticket' : 'voucher' ?>.</span></p>
					<?php } else { ?>
						<p class="rezgo-confirm-complete"><span>Click on the button below for your printable receipt.</span></p>
					<?php } ?>

				<?php } ?>

				<?php if($booking->status == 2) { ?>
        			<?php $status_class = 'rezgo-pending'; ?>
					<h3 class="rezgo-confirm-pending"><span>BOOKING NOT YET COMPLETE</span></h3>

					<p class="rezgo-confirm-pending">
						<span>
							Your booking will be complete once payment has been processed.
						</span>
					</p>
				<?php } ?>
          
				<?php if($booking->status == 3) { ?>
        			<?php $status_class = 'rezgo-cancelled'; ?>
					<h3 class="rezgo-confirm-cancelled"><span>This booking has been CANCELLED</span></h3>
				<?php } ?>

				<div class="center-block <?php echo esc_attr($status_class); ?>">
					<?php if (REZGO_LITE_CONTAINER) { ?>
					<span class="btn-check"></span>
					<button class="btn btn-lg rezgo-btn-print" onclick="window.open('<?php echo 'https://'.$domain.'.'.$role.'rezgo.com'; ?>/complete/<?php echo $site->encode($trans_num); ?>/print', '_blank'); return false;"><i class="far fa-print fa-lg"></i>&nbsp;&nbsp;Print Receipt</button>
				<?php } else { ?>
					<span class="btn-check"></span>
					<button class="btn btn-lg rezgo-btn-print" onclick="window.open('<?php echo $site->base; ?>/complete/<?php echo $site->encode($trans_num); ?>/print', '_blank'); return false;"><i class="far fa-print fa-lg"></i>&nbsp;&nbsp;Print Receipt</button>
				<?php } ?>	

				<?php if ($show_voucher) { ?>
					<?php 
						if (REZGO_WORDPRESS) {
							$voucher_link = $site->base.'/tickets/'.$site->encode($booking->trans_num); 
						} else {
							$voucher_link = 'https://'.$domain.$role.'rezgo.com/tickets/'.$site->encode($booking->trans_num);
						}
					?>
					<span class="btn-check"></span>
            		<button class="btn btn-lg rezgo-btn-print-voucher" onclick="window.open('<?php echo $voucher_link; ?>', '_blank'); return false;"><i class="far fa-ticket fa-lg"></i>&nbsp;&nbsp;Print <?php echo ((string) $booking->ticket_type == 'ticket') ? 'Tickets' : 'Voucher'; ?></button>
				<?php } ?>
				
				<?php if ($booking_edit) { 
					$edit_link = $site->base.'/edit/'.$site->encode($booking->trans_num); ?>
					<a class="rezgo-btn-modify-booking" href="<?php echo $edit_link; ?>"><i class="fal fa-cog"></i>Modify Booking</a>
				<?php } ?>

					<?php 
						$show_reviews = $company->reviews;
						if(
							($booking->status != 3) && 
							$show_reviews == 1 && 
							!$booking->reviewed && 
							$booking->com != '' && 
							(((string) $booking->date != 'open' && $booking->date < strtotime('yesterday')) || 
							(int) $booking->checkin_state > 0 )) { ?>
						<span class="btn-check"></span>
						<button class="btn btn-lg rezgo-btn-leave-review" onclick="<?php echo LOCATION_HREF; ?>='<?php echo $site->base; ?>/reviews/<?php echo $site->waiver_encode($trans_num); ?>'"><i class="far fa-star fa-lg"></i>&nbsp;&nbsp;Leave a Review</button>
					<?php } ?>
					
				</div>
			</div>
			<?php $domain = "https://".$site->getDomain(); ?>
			<?php if( $booking->waiver == '2' ) {  ?>
				<div class="row rezgo-waiver-count div-box-shadow">
					<?php 
						$pax_signed = $pax_count = 0;
						foreach ($site->getBookingPassengers() as $passenger ) { 
							if ($passenger->signed) $pax_signed++;
							$pax_count++;
						}

						if ($pax_signed != $pax_count) { // hide if all waivers signed
							echo '<div class="text-center">';
								echo '<div style="white-space:nowrap;"><i class="far fa-exclamation-circle fa-lg"></i>&nbsp; <span><span id="pax-signed">' . $pax_signed . '</span> of ' . $pax_count . ' guests have signed waivers.</span></div>';
							echo '</div>';
							echo '<span class="btn-check"></span>';
							$waiver_link = $domain.'.rezgo.com/waiver/'.$site->waiver_encode($booking->trans_num);
							echo '<a href="'.esc_url($waiver_link).'" class="btn btn-lg rezgo-waiver-btn"><span><i class="far fa-pen"></i>&nbsp;&nbsp;Sign waivers</span></a>';
						} else {
							echo '<div class="text-center">';
								echo '<div style="white-space:nowrap;"><i class="far fa-check-circle fa-lg"></i>&nbsp; <span><span id="pax-signed">All guests have signed waivers.</span></span></div>';
							echo '</div>';
						}
					?>
				</div>
			<?php } ?> 
			
			<?php if($site->exists($booking->paypal_owed)) { ?>
				<?php 
					$company_paypal = $site->getCompanyPaypal(); 
					$domain = $site->getDomain();
				?>
				<div id="booking-complete-paypal-container" class="div-box-shadow">

					<p class="paypal-amount-owed"><?php echo esc_html($site->formatCurrency($booking->paypal_owed)); ?></p>

					<p class="complete-paypal-text">
						<span class="small-text">Amount Payable Now</span>
					</p>

			<?php if (REZGO_LITE_CONTAINER) { ?>
				<form role="form" method="post" action="<?php echo REZGO_DIR; ?>/php_paypal/process.php" target="_top">	
			<?php } else { ?>
				<form role="form" class="form-inline" method="post" action="https://www.paypal.com/cgi-bin/webscr">
			<?php } ?>		
					<?php if (REZGO_WORDPRESS) { ?>

					<!-- PayPal Configuration -->
					<input type="hidden" name="cmd" value="_xclick">
					<input type="hidden" name="image_url" value="<?php echo 'https://'.esc_attr($domain).'.rezgo.com/'; ?>">
					<input type="hidden" name="return" value="<?php echo 'https://'.esc_attr($domain).'.rezgo.com/' . 'complete/'.esc_attr($site->encode($booking->trans_num)); ?>">
					<input type="hidden" name="notify_url" value="<?php echo 'https://'.esc_attr($domain).'.rezgo.com/' . 'rezgo/php_paypal/ipn/ipn.php'; ?>">
					<input type="hidden" name="rm" value="2">
					<input type="hidden" name="lc" value="US">
					<input type="hidden" name="bn" value="Rezgocom_SP_PPS">
					<input type="hidden" name="cbt" value="Click here to complete your booking">

					<!-- Payment Page Information -->
					<input type="hidden" name="no_shipping" value="1">
					<input type="hidden" name="no_note" value="1">
					<input type="hidden" name="cn" value="Comments">
					<input type="hidden" name="cs" value="">

					<!-- Shipping and Misc Information -->
					<input type="hidden" name="shipping" value="">
					<input type="hidden" name="shipping2" value="">
					<input type="hidden" name="handling" value="">
					<input type="hidden" name="tax" value="">
					<input type="hidden" name="custom" value="">
					<input type="hidden" name="invoice" value="">

					<?php } ?>

					<!-- Customer Information -->
					<input type="hidden" name="firstname" id="firstname" value="<?php echo esc_attr($booking->first_name); ?>" />
					<input type="hidden" name="lastname" id="lastname" value="<?php echo esc_attr($booking->last_name); ?>" />
					<input type="hidden" name="address1" id="address1" value="<?php echo esc_attr($booking->address_1); ?>" /> 
					<input type="hidden" name="address2" id="address2" value="<?php echo esc_attr($booking->address_2); ?>" />
					<input type="hidden" name="city" value="<?php echo esc_attr($booking->city); ?>" />
					<input type="hidden" name="state" value="<?php echo esc_attr($booking->stateprov); ?>" />
					<input type="hidden" name="country" value="<?php echo esc_attr($site->countryName($booking->country)); ?>" />
					<input type="hidden" name="zip" value="<?php echo esc_attr($booking->postal_code); ?>" />
					<input type="hidden" name="email" id="email" value="<?php echo esc_attr($booking->email_address); ?>" />
					<input type="hidden" name="phone" id="phone" value="<?php echo esc_attr($booking->phone_number); ?>" />
					<input type="hidden" name="item_name" id="item_name" value="<?php echo esc_attr($booking->tour_name); ?> - <?php echo esc_attr($booking->option_name); ?>" />
					<input type="hidden" name="item_number" id="item_number" value="<?php echo $trans_num; ?>" />
					<input type="hidden" name="amount" id="amount" value="<?php echo esc_attr($booking->paypal_owed); ?>" />
					<input type="hidden" name="quantity" id="quantity" value="1" />	
					<input type="hidden" name="business" value="<?php echo esc_attr($company->paypal_email); ?>" />
					<input type="hidden" name="currency_code" value="<?php echo esc_html($company->currency_base); ?>" />
					<input type="hidden" name="domain" value="<?php echo esc_attr($domain) ?>.rezgo.com" />
					<input type="hidden" name="cid" value="<?php echo esc_attr(REZGO_CID); ?>" />
					<input type="hidden" name="paypal_signature" value="" />
					<input type="hidden" name="base_url" value="rezgo.com" />
					<input type="hidden" name="cancel_return" value="https://<?php echo esc_attr($_SERVER['SERVER_NAME'] . $site->base. '/complete/'. $_REQUEST['trans_num']);?>" />
					<div class="paypal_button-container">
						<input type="image"	class="paypal_button" name="submit_image" src="<?php echo esc_html($site->path)?>/img/logos/paypal_pay.png" />
					</div>
				</form>
			</div>
			<?php } ?>

			<div class="row rezgo-form-group rezgo-booking-complete div-box-shadow">

				<?php
					if (isset($item->media->image->path)) { 
						$has_bg = true;
						$bg_src = $item->media->image->path;
					}
					$no_img_bg = "style='background-image: linear-gradient(170deg, #36669d 66%, #9c9c9c 108%); height:auto;'";
				?>

				<div class="rezgo-feature-bg <?php echo ($has_bg) ? 'feature-bg-gradient' : ''; ?>" <?php if ($has_bg) { echo "style='background-image: url(".$bg_src."); background-position: center; background-size: cover;'"; } else { echo wp_kses($no_img_bg, ALLOWED_HTML); } ?>>
					<div class="rezgo-feature-bg-text <?php echo (!$has_bg) ? 'no-bg' : ''; ?>">
						<h3 class="rezgo-book-name">
							<span><?php echo esc_html($booking->tour_name); ?> - <?php echo esc_html($booking->option_name); ?></span>
						</h3>
						<small class="rezgo-booked-on">booked on <?php echo esc_html(date((string) $company->date_format, (int) $booking->date_purchased_local)); ?> / local time</small>

						<?php if((string) $booking->date != 'open') { ?>
							<div class="rezgo-add-cal">
								<div class="rezgo-add-cal-cell">
									<i class="fal fa-calendar-plus"></i>
									<a href="https://feed.rezgo.com/b/<?php echo esc_attr($site->waiver_encode($booking->trans_num, 'rz|summaryval')); ?>">Add to calendar</a>
								</div>
							</div>
						<?php } ?>
					</div>
				</div>
				
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
							<td class="text-end text-nowrap">
								<?php echo esc_html($site->formatCurrency($price->total)); ?></td>
						</tr>
					<?php } // end foreach ($site->getBookingPrices() as $price ) ?>

					<tr class="rezgo-tr-subtotal">
						<td colspan="3" class="text-end"><span class="push-right"><strong>Subtotal</strong></span></td>
						<td class="text-end text-nowrap">
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
								<td class="text-end text-nowrap"><?php echo esc_html($site->formatCurrency($line->amount)); ?></td>
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
										<td class="text-end text-nowrap"><?php echo esc_html($site->formatCurrency($amount)); ?></td>
									</tr>
								<?php } ?>
							<?php } ?>
					<?php } ?>

					<tr class="rezgo-tr-subtotal summary-total">
						<td colspan="3" class="text-end"><span class="push-right"><strong>Total</strong></span></td>
						<td class="text-end text-nowrap">
							<strong><?php echo esc_html($site->formatCurrency($booking->overall_total)); ?></strong>
						</td>
					</tr>

					<?php if($site->exists($booking->deposit)) { ?>
						<tr class="rezgo-tr-deposit">
							<td colspan="3" class="text-end"><span class="push-right"><strong>Deposit</strong></span></td>
							<td class="text-end text-nowrap">
								<strong><?php echo esc_html($site->formatCurrency($booking->deposit)); ?></strong>
							</td>
						</tr>
					<?php } ?>

					<?php if($site->exists($booking->overall_paid)) { ?>
						<tr>
							<td colspan="3" class="text-end"><span class="push-right"><strong>Total Paid</strong></span></td>
							<td class="text-end text-nowrap">
								<strong><?php echo esc_html($site->formatCurrency($booking->overall_paid)); ?></strong>
							</td>
						</tr>
						<tr>
							<td colspan="3" class="text-end"><span class="push-right"><strong>Total&nbsp;Owing</strong></span></td>
							<td class="text-end text-nowrap">
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
				
				<div class="booking-receipt-details-container">
					<hr><br>

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
						
						<?php if (isset($item->details->pick_up) && (string) $item->details->pick_up != '') { ?>
							<hr class="details-separator">
							<div id="rezgo-receipt-pickup" class="flex-table-group">
								<div class="flex-table-header"><i class="far fa-map-marker"></i><span>Pickup/Departure Information</span></div>
								<div class="flex-table-info indent"><span><?php echo wp_kses($item->details->pick_up, ALLOWED_HTML); ?></span></div>
							</div>
						<?php } ?>

						<?php if (isset($item->details->drop_off) && (string) $item->details->drop_off != '') { ?>
							<hr class="details-separator">
							<div id="rezgo-receipt-dropoff" class="flex-table-group">
								<div class="flex-table-header"><i class="far fa-location-arrow"></i><span>Dropoff/Return Information</span></div>
								<div class="flex-table-info indent"><span><?php echo wp_kses($item->details->drop_off, ALLOWED_HTML); ?></span></div>
							</div>
						<?php } ?>
						
						<?php if (isset($item->details->checkin) && (string) $item->details->checkin != '') { ?>
							<hr class="details-separator">
							<div id="rezgo-receipt-checkin-instructions" class="flex-table-group">
								<div class="flex-table-header"><i class="far fa-ticket"></i><span>Check-In Instructions</span></div>
								<div class="flex-table-info indent"><span><?php echo wp_kses($item->details->checkin, ALLOWED_HTML); ?></span></div>
							</div>
						<?php } ?>

						<?php if (isset($item->details->bring) && (string) $item->details->bring != '') { ?>
							<hr class="details-separator">
							<div id="rezgo-receipt-thingstobring" class="flex-table-group">
								<div class="flex-table-header"><i class="far fa-suitcase"></i><span>Things to bring</span></div>
								<div class="flex-table-info indent"><span><?php echo wp_kses($item->details->bring, ALLOWED_HTML); ?></span></div>
							</div>
						<?php } ?>

						<?php if (isset($item->details->itinerary) && (string) $item->details->itinerary != '') { ?>
							<hr class="details-separator">
							<div id="rezgo-receipt-itinerary" class="flex-table-group rezgo-receipt-itinerary">
								<div class="flex-table-header"><i class="far fa-clipboard-list"></i><span>Itinerary</span></div>
								<div class="flex-table-info indent"><span><?php echo wp_kses($item->details->itinerary, ALLOWED_HTML); ?></span></div>
							</div>
						<?php } ?>


					<?php if (isset($booking->pickup->name) && (string) $booking->pickup->name != '') { ?>
						<hr class="details-separator">  
            			<?php $pickup_detail = $site->getPickupItem((string) $booking->item_id, (int) $booking->pickup->id); ?>
						<div id="rezgo-receipt-pickup" class="flex-table-group">
							<div class="flex-table-header"><i class="far fa-info-square"></i><span>Pick Up Information</span></div>
							<div class="flex-table-info indent"><span>
								<?php echo esc_html($booking->pickup->name); ?> <?php if ($booking->pickup->time) { ?> at <?php echo esc_html($booking->pickup->time); ?> <?php } ?> <br>

									<?php if($site->exists($pickup_detail->lat) && $site->exists($pickup_detail->location_address)) {  ?>
										<i class="far fa-map-marker"></i>&nbsp;
										<a class="underline-link" href="https://www.google.com/maps/place/<?php echo urlencode(esc_attr($pickup_detail->lat.','.$pickup_detail->lon)); ?>" target="_blank"><?php echo esc_html($pickup_detail->location_address); ?></a><br>
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
			<!-- // tour confirmation--> 

			<?php if(isset($item->lat) && $item->lat != '' && isset($item->lon) && $item->lon != '' && GOOGLE_API_KEY && !REZGO_CUSTOM_DOMAIN) { ?>
        
				<?php 
					
					if (!$site->exists($item->zoom)) { 
						$map_zoom = 6; 
					} else { 
						$map_zoom = $item->zoom; 
					}
					
					if ($item->map_type == 'ROADMAP') {
						$embed_type = 'roadmap';
					} else {
						$embed_type = 'satellite';
					} 
					
				?>

				<div class="row div-box-shadow" id="rezgo-receipt-map-container">
					<div class="col-12 p-helper">
						<h3 id="rezgo-receipt-head-map"><span>Map</span></h3>

					<div class="rezgo-map" id="rezgo-receipt-map">
              			<iframe width="100%" height="500" frameborder="0" style="border:0;margin-bottom:0;margin-top:-130px;" src="https://www.google.com/maps/embed/v1/place?key=<?php echo esc_attr(GOOGLE_API_KEY); ?>&maptype=<?php echo esc_attr($embed_type); ?>&q=<?php echo esc_attr($item->lat); ?>,<?php echo esc_attr($item->lon); ?>&center=<?php echo esc_attr($item->lat); ?>,<?php echo esc_attr($item->lon); ?>&zoom=<?php echo esc_attr($map_zoom); ?>"></iframe>

						<div class="rezgo-map-location rezgo-map-shadow">
							<?php if($item->location_name != '') { ?>
								<div class="rezgo-map-icon pull-left"><i class="far fa-map-marker"></i></div> <?php echo esc_html($item->location_name); ?>
								<div class="rezgo-map-hr"></div>
							<?php } ?>

							<?php if($item->location_address != '') { ?>
								<div class="rezgo-map-icon pull-left"><i class="far fa-location-arrow"></i></div> <?php echo esc_html($item->location_address); ?>
								<div class="rezgo-map-hr"></div>
							<?php } else { ?>
								<div class="rezgo-map-icon pull-left"><i class="far fa-location-arrow"></i></div> <?php echo esc_html($item->city).' '.esc_html($item->state).' '.esc_html($site->countryName($item->country)); ?>
								<div class="rezgo-map-hr"></div>
							<?php } ?>

							<div class="rezgo-map-icon pull-left"><i class="far fa-globe"></i></div> <?php echo round((float) esc_html($item->lat), 3); ?>, <?php echo round((float) esc_html($item->lon), 3); ?>
						</div>
					</div>

					</div>
				</div>
				<!-- end receipt map -->
			<?php } ?>

			<div class="row rezgo-form-group rezgo-confirmation-additional-info div-box-shadow">
				<div class="p-helper">
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

					<table border="0" cellspacing="0" cellpadding="2" class="rezgo-table-list d-none">
						<tr id="rezgo-receipt-name">
							<td class="rezgo-td-label">Name</td>
							<td class="rezgo-td-data"><?php echo esc_html($booking->first_name); ?> <?php echo esc_html($booking->last_name); ?></td>
						</tr>

						<tr id="rezgo-receipt-address">
							<td class="rezgo-td-label">Address</td>
							<td class="rezgo-td-data">
								<?php echo esc_html($booking->address_1); ?><?php if($site->exists($booking->address_2)) { ?>, <?php echo esc_html($booking->address_2); ?><?php } ?><?php if($site->exists($booking->city)) { ?>, <?php echo esc_html($booking->city); ?><?php } ?><?php if($site->exists($booking->stateprov)) { ?>, <?php echo esc_html($booking->stateprov); ?><?php } ?><?php if($site->exists($booking->postal_code)) { ?>, <?php echo esc_html($booking->postal_code); ?><?php } ?>, <?php echo esc_html($site->countryName($booking->country)); ?>
							</td>
						</tr>

						<tr id="rezgo-receipt-phone">
							<td class="rezgo-td-label">Phone Number</td>
							<td class="rezgo-td-data"><?php echo esc_html($booking->phone_number); ?></td>
						</tr>

						<tr id="rezgo-receipt-email">
							<td class="rezgo-td-label">Email Address</td>
							<td class="rezgo-td-data"><?php echo esc_html($booking->email_address); ?></td>
						</tr>

						<?php if($booking->overall_total > 0) { ?>
							<tr id="rezgo-receipt-payment-method">
								<td class="rezgo-td-label">Payment Method</td>
								<td class="rezgo-td-data"><?php echo esc_html($booking->payment_method); ?></td>
							</tr>
							<?php if($booking->payment_method == 'Credit Cards') { ?>
							<tr id="rezgo-receipt-cardnum">
								<td class="rezgo-td-label">Card Number</td>
								<td class="rezgo-td-data"><?php echo esc_html($booking->card_number); ?></td>
							</tr>
							<?php } ?>
							<?php if($site->exists($booking->payment_method_add->label)) { ?>
								<?php if ((string)$booking->payment_method != 'None'){ ?>
								<tr>
									<td class="rezgo-td-label"><?php echo esc_html($booking->payment_method_add->label); ?></td>
									<td class="rezgo-td-data"><?php echo esc_html($booking->payment_method_add->value); ?></td>
								</tr>
							<?php } ?>
							<?php } ?>
						<?php } ?>

						<tr id="rezgo-receipt-payment-status">
							<td class="rezgo-td-label">Payment Status</td>
							<td class="rezgo-td-data">
								<?php echo (($booking->status == 1) ? 'CONFIRMED' : ''); ?>
								<?php echo (($booking->status == 2) ? 'PENDING' : ''); ?>
								<?php echo (($booking->status == 3) ? 'CANCELLED' : ''); ?>
							</td>
						</tr>

						<?php if($site->exists($booking->trigger_code)) { ?>
							<tr id="rezgo-receipt-trigger">
								<td class="rezgo-td-label"><span>Promotional Code</span></td>
								<td class="rezgo-td-data"><?php echo esc_html($booking->trigger_code); ?></td>
							</tr>
						<?php } ?>
	
						<?php if($site->exists($booking->refid)) { ?>
							<tr id="rezgo-receipt-refid">
								<td class="rezgo-td-label">Referral ID</td>
								<td class="rezgo-td-data"><?php echo esc_html($booking->refid); ?></td>
							</tr>
						<?php } ?>
					</table>

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

											<?php unset($pax_name_display, $pax_phone_display, $pax_email_display); ?>

											<?php // check if forms and guest information is present
												if ( (int)$passenger->total_forms == 0 && 
													!$site->exists($passenger->first_name) && 
													!$site->exists($passenger->last_name) && 
													!$site->exists($passenger->phone_number) && 
													!$site->exists($passenger->email_address) ) { ?>

												<p id="no-guest-info" class="rezgo-receipt-pax-info"><span>No Guest Information entered</span></p>

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

											<div class="flex-table-info">
												<?php if( $booking->waiver == '2' ) { ?>
													<?php 
														if (REZGO_WORDPRESS) { 
															$waiver_link = esc_js($domain).'.rezgo.com/waiver/'.esc_html($site->waiver_encode($booking->trans_num.'-'.$passenger->id));
														} else {
															$waiver_link = $site->base.'/waiver/'.$site->waiver_encode($booking->trans_num.'-'.$passenger->id);
														}
													?>
													<span class="btn-check"></span>
													<button class="btn rezgo-btn-default btn-sm rezgo-waiver-sign" type="button" data-paxid="<?php echo esc_attr($passenger->id); ?>" id="rezgo-sign-<?php echo esc_attr($passenger->id); ?>" <?php echo (($passenger->signed) ? ' style="display:none;"' : ''); ?> onclick="<?php echo LOCATION_WINDOW; ?>.location.href='<?php echo $waiver_link; ?>'">
															<span>
															<span id="rezgo-sign-txt-<?php echo $passenger->id; ?>">
																<i class="far far fa-pen"></i>&nbsp; Sign Waiver
															</span>
														</span>
													</button>
													
													<span id="rezgo-signed-<?php echo esc_attr($passenger->id); ?>" <?php echo (($passenger->signed) ? '' : 'style="display:none;"'); ?>>
													<div class="flex-row rezgo-signed-row">
														<span class="btn btn-sm rezgo-signed"> 
															<i class="far fa-check-circle"></i>
															&nbsp; Waiver Signed
														</span>
													</div>
													<input type="hidden" id="rezgo-sign-count-<?php echo esc_attr($passenger->id); ?>" class="rezgo-sign-count" value="<?php echo (($passenger->signed) ? '1' : '0'); ?>" />
													</span>
												<?php } ?>  
											</div>

										</div>

									<?php } // foreach ($site->getBookingPassengers() as $passenger) ?>

								</div> <!-- flex-table -->

							</div> <!-- forms-container -->
								
							<?php foreach ($site->getBookingPassengers() as $passenger ) { ?>
								<?php 
									if (REZGO_WORDPRESS) { 
										$waiver_link = esc_js($site->base).'/waiver/'.esc_js($site->waiver_encode($booking->trans_num).'-'.$passenger->id);
									} else {
										$waiver_link = $site->base.'/waiver/'.$site->waiver_encode($booking->trans_num.'-'.$passenger->id);
									}
								?>
									<div class="col-sm-6 col-12 d-none">
								
										<table border="0" cellspacing="0" cellpadding="2" class="rezgo-table-list">
									
										<tr class="rezgo-receipt-pax">
											<td class="rezgo-td-label"><?php echo esc_html($passenger->label); ?>&nbsp;<?php echo esc_html($passenger->num); ?></td>
											<td class="rezgo-td-data">
											<?php if( $booking->waiver == '2' ) { ?>
												<span class="btn-check"></span>
												<button class="btn rezgo-btn-default btn-sm rezgo-waiver-sign" type="button" data-paxid="<?php echo esc_attr($passenger->id); ?>" id="rezgo-sign-<?php echo esc_attr($passenger->id); ?>" <?php echo (($passenger->signed) ? ' style="display:none;"' : ''); ?> onclick="<?php echo LOCATION_WINDOW; ?>.location.href='<?php echo $waiver_link; ?>'">
													<span><i class="far fa-edit"></i>&nbsp;<span id="rezgo-sign-txt-<?php echo esc_attr($passenger->id); ?>">sign waiver</span></span>
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

											<?php unset($pax_name_display, $pax_phone_display, $pax_email_display); ?>

											<?php if((string) $passenger->first_name == '' && (string) $passenger->last_name == '') $pax_name_display = 'style="display:none" '; ?>
											<tr class="rezgo-receipt-name">
												<td <?php echo esc_attr($pax_name_display); ?>class="rezgo-td-label" id="rezgo-label-name-<?php echo esc_attr($passenger->id); ?>">Name</td>
												<td <?php echo esc_attr($pax_name_display); ?>class="rezgo-td-data" id="rezgo-pax-name-<?php echo esc_attr($passenger->id); ?>"><?php echo esc_html($passenger->first_name); ?> <?php echo esc_html($passenger->last_name); ?></td>
												</tr>
								
												<?php if((string) $passenger->phone_number == '') $pax_phone_display = 'style="display:none" '; ?>
												<tr class="rezgo-receipt-pax-phone">
												<td <?php echo esc_attr($pax_phone_display); ?>class="rezgo-td-label" id="rezgo-label-phone-<?php echo esc_attr($passenger->id); ?>">Phone Number</td>
												<td <?php echo esc_attr($pax_phone_display); ?>class="rezgo-td-data" id="rezgo-pax-phone-<?php echo esc_attr($passenger->id); ?>"><?php echo esc_html($passenger->phone_number); ?></td>
												</tr>

												<?php if((string) $passenger->email_address == '') $pax_email_display = 'style="display:none" '; ?>
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
																	$answer_key = html_entity_decode(trim($pax_answer), ENT_QUOTES);
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
					<?php } // if(booking_passengers > 0) ?>
				</div>

			</div><!-- // .rezgo-confirmation-additional-info --> 

			<div class="rezgo-company-info div-box-shadow p-helper">
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
						<br>
					<?php } ?>
				<?php } ?>

				<a href="javascript:void(0);" onclick="javascript:window.open('<?php echo esc_js($site->base); ?>/terms',null,'width=800,height=600,status=no,toolbar=no,menubar=no,location=no,scrollbars=1');">View terms and conditions</a>
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

		<?php } // foreach ($site->getBookings('q='.$trans_num) as $booking) ?>
	</div><!-- // .rezgo-booking --> 
</div><!-- //	rezgo-container --> 

<?php if (DEBUG) { ?><pre><?php print_r($booking, 1); ?></pre><?php } ?>

<?php 
	if(isset($_SESSION['REZGO_CONVERSION_ANALYTICS'])) {
		echo esc_html($_SESSION['REZGO_CONVERSION_ANALYTICS']);
		unset($_SESSION['REZGO_CONVERSION_ANALYTICS']);
	} 
?>