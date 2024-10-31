<?php
	if (REZGO_WORDPRESS) {
		if (isset($_REQUEST['trans_num'])) {
			$trans_num = $site->decode(sanitize_text_field($_REQUEST['trans_num']));
		}
		if (isset($_REQUEST['parent_url'])) {
			$site->base = '/' . $site->requestStr('parent_url');
		}
	}

	// send the user home if they shouldn't be here
	if(!$trans_num) $site->sendTo($site->base."/order-not-found:empty");

	// unset promo session and cookie
	$site->resetPromoCode();

	// unset lead session and cookie
	$site->resetBookingSource();

	// start a session so we can grab the analytics code
	session_start();

	$order_bookings = $site->getBookings('t=order_code&q='.$trans_num);

	if(!$order_bookings) $site->sendTo("/order-not-found:".sanitize_text_field($_REQUEST['trans_num']));

	$site->setTimeZone();
	$company = $site->getCompanyDetails();
	$rzg_payment_method = 'None';
	$gateway_id = (string)$company->gateway_id;
	$tz_offset = $company->time_format;
	$cart_total = 0;
	$cart_owing = 0;
	$tg_enabled = 0;

	$booking_total = 0;
	$has_insurance = 0;
	$booking_completed_time;
	$booking_dates = array();
	$booking_items = array();

	foreach($order_bookings as $booking) {
		$booking_total += $booking->overall_total;

		// save purchased timestamp
		$booking_completed_time = (int)$booking[0]->date_purchased_local;

		if ($gateway_id == 'tmt'){

			$tmt_date = $booking->date;
			array_push($booking_dates, (string)$tmt_date);
			array_push($booking_items, (string)$booking->tour_name .' - '. $booking->option_name);
		}
		if ((int)$booking->ticket_guardian === 1) $has_insurance++;
	}

	$now = time();
	$expires = $booking_completed_time+3600;

	// get remaining time
	$time_remaining = $expires - $now;
	$minutes = floor(($time_remaining / 60) % 60);
	$seconds = $time_remaining % 60;

	$expired = $expires < $now;

	// TICKET GUARDIAN -->
	$tg_supported_currencies = array('USD', 'CAD', 'GBP', 'AUD', 'MXN', 'JPY', 'BRL', 'EUR');
	$tg_info = [];
	$tg_items = [];
	$tg_info['order_code'] = $tg_info['first_name'] = $tg_info['last_name'] = $tg_info['address_1'] = $tg_info['address_2'] = $tg_info['city'] = $tg_info['stateprov'] = $tg_info['country'] = $tg_info['postal_code'] = $tg_info['phone'] = $tg_info['email'] = '';

	$tg_display_currency = 0;

	// save information to submit to TG
	$tg_info['order_code'] .= (string)$order_bookings[0]->order_code;
	$tg_info['first_name'] .= (string)$order_bookings[0]->first_name;
	$tg_info['last_name'] .= (string)$order_bookings[0]->last_name;
	$tg_info['address_1'] .= (string)$order_bookings[0]->address_1;
	$tg_info['address_2'] .= (string)$order_bookings[0]->address_2;
	$tg_info['city'] .= (string)$order_bookings[0]->city;
	$tg_info['stateprov'] .= (string)$order_bookings[0]->state_prov;
	$tg_info['country'] .= (string)$order_bookings[0]->country;
	$tg_info['postal_code'] .= (string)$order_bookings[0]->postal_code;
	$tg_info['phone'] .= (string)$order_bookings[0]->phone_number;
	$tg_info['email'] .= (string)$order_bookings[0]->email_address;

	$b = 0;
	foreach ($order_bookings as $booking) {

		$tg_items[$b]['name'] = (string)$booking->tour_name . ' - ' . $booking->option_name;
		$tg_items[$b]['reference_number'] = (string)$booking->trans_num;
		$tg_items[$b]['cost'] = (float)$booking->sub_total;

		$tg_items[$b]['customer']['first_name'] = (string)$booking->first_name;
		$tg_items[$b]['customer']['last_name'] = (string)$booking->last_name; 
		$tg_items[$b]['customer']['email'] = (string)$booking->email_address; 
		$tg_items[$b]['customer']['phone'] = (string)$booking->phone_number; 

		$b++;
	}

	$tg_required_info = $tg_info['first_name'] && $tg_info['last_name'] && $tg_info['email'] ? 1 : 0;

	$currency_base = strtoupper($company->currency_base);

	if(in_array($currency_base, $tg_supported_currencies) && $company->ticketguardian) {
		$tg_display_currency = $currency_base;
	}

	$tg_limit = ($booking_total >= '25' && $booking_total <= '2500') ? 1 : 0; 

	$tg_enabled = ( $tg_display_currency !== 0
					&& $tg_required_info
					&& in_array($currency_base, $tg_supported_currencies)
					&& $tg_limit ) ? 1 : 0;
?>

<!-- clear all previously stored form data in local storage -->
<script> window.localStorage.clear(); </script>
<?php if (!REZGO_WORDPRESS) { ?>
<script type="text/javascript" src="<?php echo $site->path; ?>/js/jquery.validate.min.js"></script>
<script type="text/javascript" src="<?php echo $site->path; ?>/js/jquery.form.js"></script>
<?php } ?>

<div class="container-fluid rezgo-container rezgo-booking-order-container">
	<div class="jumbotron rezgo-booking"> 

		<?php if(isset($_SESSION['REZGO_CONVERSION_ANALYTICS'])) { ?>
			<div id="rezgo-booking-added-container" class="div-box-shadow">
				<i class="far fa-check-circle fa-lg"></i>&nbsp; <span id="rezgo-booking-added">Your booking has been added</span>
			</div>
		<?php } ?>

		<div class="row rezgo-confirmation-head">
			<h3 class="rezgo-confirm-complete">Your order <?php echo esc_html($trans_num); ?> contains <?php echo esc_html(count((array)$order_bookings)); ?> booking<?php echo ((count((array)$order_bookings) != 1) ? 's' : ''); ?></h3>
			<br>
			<div class="center-block">
				<?php 
					if (REZGO_LITE_CONTAINER) { 
						$print_order_link = 'https://'.$domain.'.'.$role.'rezgo.com/complete/'.$site->encode($trans_num).'/print';
						$view_itinerary_link = 'https://'.$domain.'.'.$role.'rezgo.com/itinerary/'.$site->encode($trans_num);
					} elseif (REZGO_WORDPRESS) {
						$print_order_link = $site->base.'/complete/'.$site->encode($trans_num).'/print';
						$view_itinerary_link = $site->base.'/itinerary/'.$site->encode($trans_num);
					} else {
						$print_order_link = $site->base.'/complete/'.$site->encode($trans_num).'/print';
						$view_itinerary_link = $site->base.'/itinerary/'.$site->encode($trans_num);
					}
				?>
				<span class="btn-check"></span>
				<button class="btn btn-lg rezgo-btn-print" onclick="window.open('<?php echo $print_order_link; ?>', '_blank'); return false;">
					<span><i class="far fa-print fa-lg"></i>&nbsp;&nbsp;Print Order</span>
				</button>

				<span class="btn-check"></span>
				<button class="btn btn-lg rezgo-btn-print" onclick="window.open('<?php echo $view_itinerary_link; ?>', '_blank'); return false;"><i class="far fa-list fa-lg"></i>&nbsp;&nbsp;View Itinerary</button>
			</div>
		</div>

		<?php if ($tg_enabled) { ?>

			<script>
				var split_total = new Array();
				let now = new Date(<?php echo $expires; ?> * 1000);

				// create stripe initial error state because we use this to validate the form
                let stripe_error = 0;
                let square_error = 0;
				let stripe_trace = Date.now();
				let tmt_data;
				let clientSecret = '';
				let paymentId = '';

				<?php $c = 0;

				foreach($order_bookings as $booking) { ?>
					split_total[<?php echo $c; ?>] = '<?php echo $booking->overall_total; ?>';
				<?php $c++; } ?>

				//tg item prices for quote
				tg_booking_prices = split_total.slice(0);
				
				// filter out empty elements
				let tg_items = [];
				for (let i = 0; i < tg_booking_prices.length; i++) {
					if (tg_booking_prices[i]) {
						tg_items.push(tg_booking_prices[i]);
					}
				}

				<?php if (!$expired) { ?>
				tg('configure', {
					apiKey: '<?php echo REZGO_TICKGUARDIAN_PK; ?>',
					currency: '<?php echo $tg_display_currency; ?>',
					costsOfItems: tg_items,
					<?php if (REZGO_TICKGUARDIAN_TEST) { ?>
					sandbox: true,
					<?php } ?>
					loadedCb: function() {
						console.log('update callback');
					},
					optInCb: function() {
						var quoteToken = tg.get("token");
						var coverageQuote = tg.get("quote");
						// console.log('opted in callback');
						jQuery('#tour_tg_insurance_coverage').attr('disabled' , false);
						jQuery("#tour_tg_insurance_coverage").val(1);
						jQuery('#rezgo-tg-quote-complete').text('<?php echo (string)$company->currency_symbol; ?>' + coverageQuote);

						jQuery('#tg_toggle_list').show();
						jQuery('#tg_protect_list').addClass('toggled');
						jQuery('#ticket_guardian_collapse').slideDown(250);
					},
					optOutCb: function() {
						var quoteToken = tg.get("token");
						// console.log('opted out callback');
						jQuery("#tour_tg_insurance_coverage").val('');
						jQuery('#tour_tg_insurance_coverage').attr('disabled' , true);

						jQuery('#tg_toggle_list').show();
						jQuery('#tg_protect_list').removeClass('toggled');
						jQuery('#ticket_guardian_collapse').slideUp(250);
					},
					onErrorCb: function(object){
						console.log(object);
					}
				});
				<?php } ?>

			</script>

			<?php if (!$has_insurance) { ?>

				<?php if (!$expired) { ?>
					<div id="rezgo-tg-postbooking" class="div-box-shadow">
						<div id="tg-postbooking-form">
							<!-- <span id="tg_time_limit"></span> -->

							<div id="tg-placeholder"></div>
							<input type="hidden" name="tour_tg_insurance_coverage" id="tour_tg_insurance_coverage">

							<div id="ticket_guardian_collapse" style="display:none;">
								<div class="tg-payment-container">
									<form id="rezgo-tg-postbooking-form" role="form" method="post" target="rezgo_content_frame">

										<div id="payment_cards" class="payment_method_container">
											<h4 class="payment-method-header">Credit Card Details</h4>
											<input type="hidden" name="tour_card_token" id="tour_card_token" value="">

												<?php if (REZGO_WORDPRESS) { ?>
                                                    <iframe scrolling="no" frameborder="0" name="tour_payment" id="tour_payment" src="<?php echo home_url(); ?>?rezgo=1&mode=booking_payment&action=tg_postbooking"></iframe>

												<?php } else { ?>
													
													<iframe scrolling="no" frameborder="0" name="tour_payment" id="tour_payment" src="<?php echo $site->base; ?>/booking_payment.php?mode=tg_postbooking"></iframe>
												<?php } ?>

												<script type="text/javascript">
													iFrameResize({
														scrolling: false
													}, '#tour_payment');
												</script>
										</div> <!-- div payment_cards -->

										<div id="rezgo-book-message" class="row" style="display:none;">
											<div id="rezgo-book-message-body" class="col-8 offset-sm-2"></div>
												<div id="rezgo-book-message-wait" class="col-2"><i class="far fa-sync fa-spin fa-3x fa-fw"></i></div>
										</div>

										<div id="rezgo-book-errors-wrp">
											<div id="rezgo-book-errors" class="alert" style="display:none;">
												<span>Some required fields are missing. Please complete the highlighted fields.</span>
											</div>
										</div> <!-- // book errors -->

										<div class="rezgo-btn-wrp rezgo-complete-btn-wrp">
											<span class="btn-check"></span>
											<button type="submit" class="btn rezgo-btn-book btn-lg btn-block" id="rezgo-complete-payment">
												Protect Booking &mdash; <span id="rezgo-tg-quote-complete"></span>
											</button>
										</div>
									</form>
								</div>
							</div>

							<div class="modal fade bd-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="scaModal" aria-hidden="true" id="sca_modal" style="bottom:0 !important; top:auto !important;">
								<div class="modal-dialog modal-md" style="top: 0;">
									<div class="modal-content">
										<div class="modal-header">
											<h4 class="modal-title" style="position:relative; top:3px; float:left;">Card Validation</h4>
											<button type="button" class="close" data-bs-dismiss="modal" aria-label="Close" style="width:50px; text-decoration:none; background: 0; border: 0; right: 20px; position:absolute; padding: 0;">
												<span aria-hidden="true" style="font-size:32px;">&times;</span>
											</button>
											<div class="clearfix"></div>
										</div>
										<div class="modal-body" id="sca_modal_content" style="height:640px;">
											<iframe style="border:0; width:100%; height:100%;" name="sca_modal_frame" id="sca_modal_frame"></iframe>
										</div>
									</div>
								</div>
							</div>
						</div> <!-- postbooking-form -->

						<script>
						jQuery(function($){
							let scaModal = new bootstrap.Modal(document.getElementById('sca_modal'));
							let timer = document.getElementById("tg_time_limit");
							let start = document.getElementById("start");
							let id;
							let pause = 1;
							/*
								function startTimer(m, s) {
									// add leading zero to seconds
									s = s < 10 ? '0' + s : s;
									
									timer.textContent = `${m}:${s}`;
									if (s == 0) {
										if (m == 0) {
											return;
										} else if (m != 0) {
											m = m - 1;
											s = 60;
										}
									} s--;

									id = setTimeout(function () {
										startTimer(m, s)
										if (m === 0 && s === 0) {
											// hide tg section when timer is up
											$('#rezgo-tg-postbooking').slideUp(450);
										}
									}, 1000);
								}

								function toggleTimer() {
									if (pause) {
										pause = 0;
										value = timer.textContent;
										clearTimeout(id);
										timer.classList.toggle('paused');
									} else {
										pause = 1;
										let t = value.split(":");
										startTimer(parseInt(t[0], 10), parseInt(t[1], 10));
										timer.classList.toggle('paused');
									}
								}
							*/
							function creditConfirm(token) {
								// the credit card transaction was completed, give us the token
								$('#tour_card_token').val(token);
							}
							/** 
							start timer in (minute, seconds) 
							--> only executes if purchasing window is still open and existing policy has not been purchased
							**/
							// startTimer(<?php echo $minutes; ?>, <?php echo $seconds; ?>);

							$('#tg_postbooking_cta').click(function() {
								$(this).hide();
								$('#tg_toggle_list').show();
								$('#tg_protect_list').toggleClass('toggled');
								$('#ticket_guardian_collapse').slideToggle(450);
								toggleTimer();
							});

							// Catch form submissions
							$('#rezgo-tg-postbooking-form').submit(function(e) {
								e.preventDefault();
								submit_payment();
							});

							function payment_wait(wait) {
								if (wait) {
									$('#rezgo-book-message-wait').show();
								} else {
									$('#rezgo-book-message-body').html('');
									$('#rezgo-book-message-wait').hide();
								}
							}

							// SCA passthrough data
							let passthrough = '';

							// show the sca challenge window if the gateway requires it
							function sca_window(mode, url, data, pass) {
							
								if(pass) passthrough = pass;

								if(mode == 'direct') {
									
									$('.sca-direct-area').remove();
									$('body').append('<div class="sca-direct-area">' + data + '</div>');
									
								}
								
								if(mode == 'iframe') {

									scaModal.show();

									let content = data ? JSON.parse(data) : null;
									
									if(content) {
										
										// post content to 3DS frame
										let form = '<form action="' + url + '" method="post" target="sca_modal_frame" id="sca_post">';

										$.each(content, function(index, value) {
											form += '<input type="hidden" name="' + index + '" value="' + value + '">';
										});
										
										form += '</form>';

										$('body').append(form);
									
										$('#sca_post').submit().remove();
										
									} else {
										
										// no post content, load directly into frame
										// this is needed to avoid frame-ancestors restrictions on some gateways like stripe
										$('#sca_modal_frame').attr('src', url);
										
									}
									
								}

							}
							
							// called by the sca challenge window callback URL
							function sca_callback(code) {
							
								if(!code) return false;
								
								//console.log(code);

								$('#sca_modal').modal('hide');

								if(passthrough) {
									let data = JSON.parse(code); // parse data sent back from 3DS
									data.pass = passthrough; // add the passthrough data to the array
									code = JSON.stringify(data);
								}
								
								$('#tour_card_token').val(code);
								$('#payment_id').val(1); // needed to trigger the validate step on commit

								$('#rezgo-book-message-body').html('Please wait one moment ...');
								$('#rezgo-complete-payment').attr('disabled','disabled');
								$('#rezgo-book-message').fadeIn();

								payment_wait(true);
								
								$('#rezgo-book-form').ajaxSubmit({
									url: '<?php echo admin_url('admin-ajax.php'); ?>' + '?action=rezgo&method=book_ajax',
									data: {rezgoAction: 'book'},
									success: delay_response,
									error: function () {
										var body = 'Sorry, the system has suffered an error that it can not recover from.<br />Please try again later.<br />';
										$('#rezgo-book-message-body').html(body);
										$('#rezgo-book-message-body').addClass('alert alert-warning');
									}
								});
								
							}

							let postbookingSuccess = `<p id="tg_success_msg"><span>Thank you for your purchase</span></p>
							<div id="tg-postbooking-success">
							<h3>Booking Protected by</h3>
								<img class="tg_logo" src='<?php get_home_url(); ?>/wp-content/plugins/rezgo/rezgo/templates/default/img/ticketguardian/TOURS-TGmirr-logo.png'>
							</div>`;

							// change the modal dialog box or pass the user to the receipt depending on the response
							function show_response() {

								response = response.trim();

								let title = '';
								let body = '';

								if(response.indexOf('STOP::') != -1) {  // debug handling

									let split = response.split('<br><br>');

									try {
										response = JSON.parse(split[1]);
									} catch (error) {
										response.status = 999;
									}

									if(response.status != '1') {
										$('#rezgo-complete-payment').val('Complete Booking');
										$('#rezgo-complete-payment').removeAttr('disabled');
									}

									if(response.status == 1) {
										split[1] = '<div class="clearfix">&nbsp;</div>PURCHASE COMPLETED WITHOUT ERRORS<div class="clearfix">&nbsp;</div><div class="clearfix">&nbsp;</div>';
									} else if(response.status == '8') {
										// an SCA challenge is required for this transaction
										sca_window('iframe', response.url, response.post, response.pass);
									} else {
										split[1] = '<br /><br />Error Code: ' + response.status + '<br />Error Message: ' + response.message + '<br />';
									}

									setTimeout(() => {
										parent.scrollTo(0,0);
									}, 250);

									// add debug
									let debug = '<br><br><div class="text-center debug-div">DEBUG-STOP ENCOUNTERED<br /><br />' + '<textarea style="width:400px;height:250px;" id="debug_response">' + split[0] + '</textarea>' + split[1];

									// show purchased banner
									document.getElementById('tg-postbooking-form').remove();
									document.getElementById('rezgo-tg-postbooking').innerHTML += postbookingSuccess;
									document.getElementById('rezgo-tg-postbooking').innerHTML += debug;

									return false;

								} else {

									try {
										response = JSON.parse(response);
									} catch (error) {
										response.status = 999;
									}

									if(response.status != '1') {
										$('#rezgo-complete-payment').val('Complete Booking');
										$('#rezgo-complete-payment').removeAttr('disabled');
									}

									if(response.status == '2') {
										title = 'No Availability Left';
										body = 'Sorry, there is not enough availability left for this item on this date.<br />';
									}
									else if(response.status == '3') {
										title = 'Payment Error';
										body = 'Sorry, your payment could not be completed. Please verify your card details and try again.<br /';
									}
									else if(response.status == '4') {
										title = 'Payment Error';
										body = 'Sorry, there has been an error with your payment and it can not be completed at this time.<br />';
									}
									else if(response.status == '5') {
										// this error should only come up in preview mode without a valid payment method set
										title = 'Payment Error';
										body = 'Sorry, you must have a credit card attached to your Rezgo Account in order to complete a booking.<br><br>Please go to "Settings &gt; Rezgo Account" to attach a credit card.<br />';
									}
									else if(response.status == '6') {
										// this error is returned when expected total does not match actual total
										title = 'Payment Error';
										body = 'Sorry, a price on an item you are booking has changed. Please return to the shopping cart and try again.<br />';
									}
									else if(response.status == '8') {
										// an SCA challenge is required for this transaction
										sca_window('iframe', response.url, response.post, response.pass);
									}
									else {

										console.log(response);
										

										if(response.status == '1') {

											setTimeout(() => {
												parent.scrollTo(0,0);
											}, 250);

											// replace with success message after successful transaction 
											document.getElementById('tg-postbooking-form').remove();
											document.getElementById('rezgo-tg-postbooking').innerHTML += postbookingSuccess;

										} else {

											title = 'Purchase Error';
											body = 'Sorry, an unknown error has occurred. Our staff have already been notified. Please try again later.<br />';
											console.log('Error: ' + response);

										}
									}
								}

								payment_wait(false);

								if(body) {
									$('#rezgo-book-message-body').html(body);
									$('#rezgo-book-message-body').addClass('alert alert-warning');
								}
							}

							// this function delays the output so we see the loading graphic
							function delay_response(responseText) {
								response = responseText;
								setTimeout(function () {

									console.log("RESPONSE: ");
									console.log(response);
									show_response();
								}, 800);
							}

							function error_payment() {
								$('#rezgo-book-errors').fadeIn();

								setTimeout(function () {
									$('#rezgo-book-errors').fadeOut();
								}, 5000);
								return false;
							}

							function submit_payment () {

								// let validate_check = validate_form();

								console.log('TG ENABLED? <? print_r($tg_enabled); ?>')
								console.log('FORM DATA: ');
								console.log(<?php echo json_encode($_POST) ; ?>);
								console.log($('#rezgo-tg-postbooking-form'));

								let force_error = 0;

									if(!$('#tour_payment').contents().find('#payment').valid()) {
										force_error = 1;
									}
					
								if(force_error || stripe_error || square_error) {
									console.log('force error: ' + force_error);
									return error_payment();

								} else {

									payment_wait(true);
					
									$('#rezgo-book-message-body').html('Please wait one moment ...');
					
									$('#rezgo-book-message').fadeIn();
					
									// clear the existing credit card token, just in case one has been set from a previous attempt
									$('#tour_card_token').val('');
					
									// submit the card token request and wait for a response
									$('#tour_payment').contents().find('#payment').submit();

									// get name from payment input field
									let tg_billing_name = $('#tour_payment').contents().find('#payment #name').val();
					
									// wait until the card token is set before continuing (with throttling)
									function check_card_token() {
										let card_token = $('#tour_card_token').val();

										// console.log('checking for token: ');
										// console.log(card_token);
										// console.log(<?php echo json_encode($tg_info) ?>)
										if (card_token == '') {
											// card token has not been set yet, wait and try again
											setTimeout(function () {
												check_card_token();
											}, 200);
										} else {
											// the field is present? submit normally
											$('#rezgo-tg-postbooking-form').ajaxSubmit({
												url: '<?php echo admin_url('admin-ajax.php'); ?>' + '?action=rezgo&method=book_ajax',
												data: {
													rezgoAction: 'tg_postbooking',
													quoteToken: tg.get("token"),
													billing_name: tg_billing_name,
													billing_email: '<?php echo $tg_info['email']; ?>',
													billing_phone: '<?php echo $tg_info['phone']; ?>',
													booking: <?php echo json_encode($tg_info); ?>,
													items: <?php echo json_encode($tg_items); ?>,
												},
												success: delay_response,
												error: function () {
													var body = 'Sorry, the system has suffered an error that it can not recover from.<br />Please try again later.<br />';
													$('#rezgo-book-message-body').html(body);
													$('#rezgo-book-message-body').addClass('alert alert-warning');
												}
											});
										}
									}
					
									check_card_token();
								}
							}

							// Validation Setup
							$.validator.setDefaults({
								highlight: function(element) {
									$(element).closest('.form-group').addClass('has-error');
								},
								unhighlight: function(element) {
									$(element).closest('.form-group').removeClass('has-error');
								},
								focusInvalid: false,
								errorElement: 'span',
								errorClass: 'help-block',
								errorPlacement: function(error, element) {
									if ($(element).attr("name") == "name" || $(element).attr("name") == "pan" || $(element).attr("name") == "cvv") {
										error.hide();
									}
								}
							});


						});

						</script>
					</div> <!-- rezgo-tg-postbooking -->

				<?php } // if (!$expired) ?>
			<?php } // if (!has_insurance) ?>

		<?php } // if ($tg_enabled) ?>

		<?php $n = 1; ?>

		<?php foreach($order_bookings as $booking ) { ?>
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
				$checkin_state = (int)$booking->checkin_state != 0 ? 1 : 0;
				
				$now = strtotime($tz_offset.' hours', time());

				$share_url = urlencode('https://'.$_SERVER['HTTP_HOST'].$site->base.'/details/'.$item->com.'/'.$site->seoEncode($item->item));

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

			<?php $site->readItem($booking); ?>

				<div class="row rezgo-confirmation div-box-shadow div-order-booking">
					<div class="rezgo-booking-status col-md-4 col-sm-12">
						<?php if($booking->status == 1 OR $booking->status == 4) { ?>
							<p class="booking-status rezgo-status-complete"><i class="far fa-calendar-check fa-lg"></i></i>&nbsp;&nbsp;Booking Complete</p>
						<?php } ?>

						<?php if($booking->status == 2) { ?>
							<p class="booking-status rezgo-status-pending"><i class="far fa-calendar-check fa-lg"></i></i>&nbsp;&nbsp;Booking Pending</p>
						<?php } ?>

						<?php if($booking->status == 3) { ?>
							<p class="booking-status rezgo-status-cancel"><i class="far fa-times fa-lg"></i>&nbsp;&nbsp;Booking Cancelled</p>
						<?php } ?>

					</div><!-- // .rezgo-booking-status -->

					<div class="clearfix"></div>

					<h3 class="order-booking-title"><?php echo esc_html($booking->tour_name); ?>&nbsp;(<?php echo esc_html($booking->option_name); ?>)</h3>

					<div class="order-booking-cols rezgo-form-group">

					<div class="col-12 col-lg-5 __details-col">
						<div class="flex-table">
							<div id="rezgo-receipt-transnum" class="flex-table-group">
								<div class="flex-table-header rezgo-order-transnum"><span>Booking #</span></div>
								<div class="flex-table-info"><?php echo esc_html($booking->trans_num); ?></div>
							</div>

							<?php if((string) $booking->date != 'open') { ?>
								<div id="rezgo-receipt-booked-for" class="flex-table-group">
									<div class="flex-table-header"><span>Date</span></div>
									<div class="flex-table-info">
                                        <span class="rezgo-order-booked-for-date-<?php echo esc_attr($booking->item_id); ?>">
										    <?php echo esc_html(date((string) $company->date_format, (int) $booking->date)); ?>
                                        </span>
                                        <span class="rezgo-order-booked-for-time-<?php echo esc_attr($booking->item_id); ?>">
										    <?php if ($site->exists($booking->time)) { ?> at <?php echo esc_html($booking->time); ?><?php } ?>
                                        </span>
									</div>
								</div>
							<?php } else { ?>
								<?php if ($site->exists($booking->time)) { ?>
									<div id="rezgo-receipt-booked-for" class="flex-table-group">
										<div class="flex-table-header"><span>Time</span></div>
										<div class="flex-table-info">
											<?php echo esc_html($booking->time); ?>
										</div>
									</div>
								<?php } ?>
							<?php } ?>

							<?php if(isset($booking->expiry)) { ?>
								<div id="rezgo-receipt-expires" class="flex-table-group">
									<div class="flex-table-header"><span>Expires</span></div>
									<?php if((int) $booking->expiry !== 0) { ?>
										<div class="flex-table-info"><span><?php echo esc_html(date((string) $company->date_format, (int) $booking->expiry)); ?></span></div>
									<?php } else { ?>
										<div class="flex-table-info"><span>Never</span></div>
									<?php } ?>
								</div>
							<?php } ?>

							<?php if($site->exists($booking->trigger_code)) { ?>
								<div id="rezgo-order-promo" class="flex-table-group">
									<div class="flex-table-header"><span>Promo Code</span></div>
									<div class="flex-table-info"><?php echo esc_html($booking->trigger_code); ?></div>
								</div>
							<?php } ?>

							<?php if($site->exists($booking->refid)) { ?>
								<div id="rezgo-order-refid" class="flex-table-group">
									<div class="flex-table-header"><span>Referral ID</span></div>
									<div class="flex-table-info"><?php echo (string) esc_html($booking->refid); ?></div>
								</div>
							<?php } ?>
						</div>

						<?php $booking_details_link = $site->base.'/complete/'.$site->encode($booking->trans_num); ?>
						<span class="btn-check"></span>
						<a href="<?php echo esc_url($booking_details_link); ?>" class="btn btn-lg rezgo-btn-default rezgo-btn-outline btn-block">
							<?php echo $booking_edit ? 'Modify or' : ''; ?> View Booking
						</a> 

						<?php if( $booking->waiver == '2' ) {  ?>
							<?php 
								echo '<div class="rezgo-waiver-order">';
									$pax_signed = $pax_count = 0;
									if ($site->getBookingPassengers()) { 
										foreach ($site->getBookingPassengers() as $passenger ) { 
											if ($passenger->signed) $pax_signed++;
											$pax_count++;
										}
									}
								
									if ($pax_signed != $pax_count) { // hide if all waivers signed
										echo '<span class="btn-check"></span>';
										echo '<a href="'.$site->base.'/waiver/'.$site->waiver_encode($booking->trans_num).'" class="btn btn-lg rezgo-waiver-btn btn-block"><span>Sign waivers</span></a>';
										echo '<div style="white-space:nowrap;display:flex;align-items:baseline;">';
											echo '<i class="far fa-exclamation-circle fa-lg"></i>&nbsp; <span class="pax-signed">' . $pax_signed . ' of ' . $pax_count . ' guests have signed waivers.</span>';
										echo '</div>';
									} else {
										echo '<i class="far fa-check-circle fa-lg"></i>&nbsp; <span class="pax-signed">All guests have signed waivers.</span></span>';
									}
								echo '</div>';
							?>
						<?php } ?> 

						<?php $domain = $site->getDomain(); ?>
			
						<?php if( !$company->manual_tickets &&
								  ($booking->status == 1 || $booking->status == 4) &&
								  (($booking->availability_type == 'date' && (int) $booking->date > strtotime('yesterday')) || 
								  ($booking->availability_type == 'open' && $booking->checkin_state == 0)) ){ ?>
								  	<?php 
										if (REZGO_WORDPRESS) {
											$voucher_link = $site->base.'/tickets/'.$site->encode($booking->trans_num); 
										} else {
											$voucher_link = 'https://'.$domain.$role.'rezgo.com/tickets/'.$site->encode($booking->trans_num);
										}
									?>
									<span class="btn-check"></span>
									<a href="<?php echo $voucher_link; ?>" class="btn btn-lg rezgo-btn-print-voucher btn-block" target="_blank">Print <?php echo ((string) $booking->ticket_type == 'ticket') ? 'Tickets' : 'Ticket' ?></a>
						<?php } ?>
						
						<?php if($site->exists($booking->paypal_owed)) { ?>

							<?php $company_paypal = $site->getCompanyPaypal(); ?>
							<div id="booking-order-paypal-container">

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
								<input type="hidden" name="encoded_transaction_id" id="encoded_transaction_id" value="<?php echo $site->encode($trans_num); ?>" />
								<input type="hidden" name="item_number" id="item_number" value="<?php echo $trans_num; ?>" />
								<input type="hidden" name="amount" id="amount" value="<?php echo esc_attr($booking->paypal_owed); ?>" />
								<input type="hidden" name="quantity" id="quantity" value="1" />	
								<input type="hidden" name="business" value="<?php echo esc_attr($company->paypal_email); ?>" />
								<input type="hidden" name="currency_code" value="<?php echo esc_html($company->currency_base); ?>" />
								<input type="hidden" name="domain" value="<?php echo esc_attr($domain) ?>.rezgo.com" />
								<input type="hidden" name="cid" value="<?php echo esc_attr(REZGO_CID); ?>" />
								<input type="hidden" name="paypal_signature" value="" />
								<input type="hidden" name="base_url" value="rezgo.com" />
								<input type="hidden" name="cancel_return" value="https://<?php echo esc_attr($_SERVER['SERVER_NAME'] . $site->base . '/complete/'. $_REQUEST['trans_num']);?>" />
									<div class="paypal_button-container">
										<input type="image"	class="paypal_button" name="submit_image" src="<?php echo esc_attr($site->path); ?>/img/logos/paypal_pay.png" />
									</div>
									<span id="paypal_owing"></span>
								</form>
							</div>
						<?php } ?>

					</div>

					<div class="col-12 col-lg-7 __table-col">
						<table class="table-responsive">
							<table class="table rezgo-billing-cart">
								<tr class="rezgo-tr-head">
									<td class="text-start rezgo-billing-type"><label>Type</label></td>
									<td class="text-start rezgo-billing-qty"><label class="d-none d-sm-block">Qty.</label></td>
									<td class="text-start rezgo-billing-cost"><label>Cost</label></td>
									<td class="text-end rezgo-billing-total"><label>Total</label></td>
								</tr>

								<?php foreach($site->getBookingPrices() as $price) { ?>
									<tr>
										<td class="text-start"><?php echo esc_html($price->label); ?></td>
										<td class="text-start"><?php echo esc_html($price->number); ?></td>
										<td class="text-start">
										<?php if(isset($price->base) && $site->exists($price->base)) { ?>
											<span class="discount"><?php echo esc_html($site->formatCurrency($price->base)); ?></span>
										<?php } ?>
										&nbsp;<?php echo esc_html($site->formatCurrency($price->price)); ?></td>
										<td class="text-end text-nowrap"><?php echo esc_html($site->formatCurrency($price->total)); ?></td>
									</tr>
								<?php } ?>

								<tr class="rezgo-tr-subtotal">
									<td colspan="3" class="text-end"><span class="push-right"><strong>Subtotal</strong></span></td>
									<td class="text-end text-nowrap"><?php echo esc_html($site->formatCurrency($booking->sub_total)); ?></td>
								</tr>

								<?php if ($site->getBookingLineItems()) { ?>
									<?php foreach($site->getBookingLineItems() as $line) { ?>
										<?php
											$label_add = '';
											if($site->exists($line->percent) || $site->exists($line->multi)) {
												$label_add = ' (';
													if($site->exists($line->percent)) $label_add .= $line->percent.'%';
													if($site->exists($line->multi)) {
														if(!$site->exists($line->percent)) $label_add .= $site->formatCurrency($line->multi);
				
														if($site->exists($line->meta)) {
															$pax_totals = array( 'adult_num' => 'price_adult', 'child_num' => 'price_child', 'senior_num' => 'price_senior', 'price4_num' => 'price4', 'price5_num' => 'price5', 'price6_num' => 'price6', 'price7_num' => 'price7', 'price8_num' => 'price8', 'price9_num' => 'price9');
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
											}
										?>

										<?php if( $site->exists($line->amount) ) { ?>
										<tr>
											<td colspan="3" class="text-end"><span class="push-right"><strong><?php echo esc_html($line->label); ?><?php echo esc_html($label_add); ?></strong></span></td>
											<td class="text-end text-nowrap"><?php echo esc_html($site->formatCurrency($line->amount)); ?></td>
										</tr>
										<?php } ?>
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
									<td class="text-end text-nowrap"><strong><?php echo esc_html($site->formatCurrency($booking->overall_total)); ?></strong></td>
								</tr>

								<?php if($site->exists($booking->deposit)) { ?>
									<tr>
										<td colspan="3" class="text-end"><span class="push-right"><strong>Deposit</strong></span></td>
										<td class="text-end text-nowrap"><strong><?php echo esc_html($site->formatCurrency($booking->deposit)); ?></strong></td>
									</tr>
								<?php } ?>

								<?php if($site->exists($booking->overall_paid)) { ?>
									<tr>
										<td colspan="3" class="text-end"><span class="push-right"><strong>Total Paid</strong></span></td>
										<td class="text-end text-nowrap"><strong><?php echo esc_html($site->formatCurrency($booking->overall_paid)); ?></strong></td>
									</tr>
									<tr>
										<td colspan="3" class="text-end"><span class="push-right"><strong>Total&nbsp;Owing</strong></span></td>
										<td class="text-end text-nowrap"><strong><?php echo esc_html($site->formatCurrency(((float)$booking->overall_total - (float)$booking->overall_paid))); ?></strong></td>
									</tr>
								<?php } ?>
							</table>
						</table>
					</div>
				</div><!-- //  tour confirm --> 
			</div>

			<?php 
			$cart_total += ((float)$booking->overall_total); 
			$cart_owing += ((float)$booking->overall_total - (float)$booking->overall_paid); 
			?>

			<?php if($booking->payment_method != 'None') {
				$rzg_payment_method = $booking->payment_method;
			} ?>
			
		<?php } ?>

		<div class="row rezgo-form-group rezgo-confirmation div-box-shadow">
			<div class="col-md-6 col-12 rezgo-billing-confirmation p-helper">
				<h3 id="rezgo-receipt-head-billing-info"><span>Billing Information</span></h3>

				<div class="flex-row">
					<?php if ($site->exists($booking->first_name)){ ?>
						<div class="flex-50 billing-payment-info-box" id="rezgo-receipt-name">
							<p class="rezgo-receipt-pax-label"><span>Name</span></p>
							<p class="rezgo-receipt-pax-info"><?php echo esc_html($booking->first_name); ?> <?php echo esc_html($booking->last_name); ?></p>
						</div>
					<?php } ?>

					<?php if ($site->exists($booking->phone_number)){ ?>
						<div class="flex-50 billing-payment-info-box" id="rezgo-receipt-phone">
							<p class="rezgo-receipt-pax-label"><span>Phone Number</span></p>
							<p class="rezgo-receipt-pax-info"><?php echo esc_html($booking->phone_number); ?></p>
						</div>
					<?php } ?>

					<?php if ($site->exists($booking->address_1)){ ?>
					<div class="flex-50 billing-payment-info-box" id="rezgo-receipt-address">
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

					<?php if ($site->exists($booking->email_address)){ ?>
						<div class="flex-50 billing-payment-info-box" id="rezgo-receipt-email">
							<p class="rezgo-receipt-pax-label"><span>Email Address</span></p>
							<p class="rezgo-receipt-pax-info"><?php echo esc_html($booking->email_address); ?></p>
						</div>
					<?php } ?>
				</div>
			</div>

			<div class="col-md-6 col-12 rezgo-payment-confirmation p-helper">
				<h3 id="rezgo-receipt-head-payment-info"><span>Payment Information</span></h3>
				<div class="flex-row">
					<div class="flex-50 billing-payment-info-box" id="rezgo-receipt-email">
						<p class="rezgo-receipt-pax-label"><span>Total&nbsp;Order</span></p>
						<p class="rezgo-receipt-pax-info"><?php echo esc_html($site->formatCurrency($cart_total)); ?></p>
					</div>

					<div class="flex-50 billing-payment-info-box" id="rezgo-receipt-email">
						<p class="rezgo-receipt-pax-label"><span>Total&nbsp;Owing</span></p>
						<p class="rezgo-receipt-pax-info"><?php echo esc_html($site->formatCurrency($cart_owing)); ?></p>
					</div>

					<?php if($cart_total > 0) { ?>
						<div class="flex-50 billing-payment-info-box" id="rezgo-receipt-email">
							<p class="rezgo-receipt-pax-label"><span>Payment&nbsp;Method</span></p>
							<p class="rezgo-receipt-pax-info"><?php echo esc_html($rzg_payment_method); ?></p>
						</div>
					<?php } ?>
				</div>
			</div>
		</div><!-- //  rezgo-confirmation --> 
	</div><!-- //  .jumbotron --> 
</div><!-- //  .rezgo-container -->

<?php if (DEBUG) { ?><pre><?php print_r($booking); ?></pre><?php } ?>

<?php if(isset($_SESSION['REZGO_CONVERSION_ANALYTICS'])) { 
	echo wp_kses($_SESSION['REZGO_CONVERSION_ANALYTICS'], ALLOWED_HTML);
	unset($_SESSION['REZGO_CONVERSION_ANALYTICS']);
} ?>
