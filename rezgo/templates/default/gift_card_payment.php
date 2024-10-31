<?php
	session_start();
	// send to step 1 if user does not meet requirements
    if ( $_SESSION['gift-card']['rezgoAction'] != 'firstStepGiftCard' || !isset($_COOKIE['rezgo_gift_card_'.REZGO_CID]) ){
        $site->sendTo($site->base.'/gift-card');
    } else {
        // save form in session
		$gc_details_array = [];
        foreach ($_SESSION['gift-card'] as $k => $v){
			$gc_details_array[$k] = $v;
			$this->setCookie('rezgo_gc_details_'.REZGO_CID, json_encode($gc_details_array));
        }
    }

    $company = $site->getCompanyDetails();
	$gateway_id = (string)$company->gateway_id;
	$using_tmt = $gateway_id == 'tmt';
    $companyCountry = $site->getCompanyCountry();
    $site->readItem($company);
    $card_fa_logos = array(
        'visa' => 'fa-cc-visa',
        'mastercard' => 'fa-cc-mastercard',
        'american express' => 'fa-cc-amex',
        'discover' => 'fa-cc-discover'
    );

    $r = REZGO_WORDPRESS ? $_SESSION['gift-card'] : $_POST;
    $order_total = ($r['billing_amount'] == 'custom' ) ? $r['custom_billing_amount'] : $r['billing_amount'];

	if (REZGO_WORDPRESS) {
		if ($r['billing_amount'] == 'custom') {
			$_SESSION['gift-card']['billing_amount'] = 'custom';
		} else {
			unset($_SESSION['gift-card']['custom_billing_amount']);
		}
	}
	$show_gc = $site->showGiftCardPurchase();
?>

<script>
	let debug = <?php echo DEBUG; ?>;
	let float_total = parseFloat(decodeURIComponent( '<?php echo rawurlencode( (string) $order_total ); ?>' ));
	order_total = float_total.toFixed(2);

	setTimeout(() => {
		jQuery('input[name=billing_amount]').val(order_total);
        parent.scrollTo(0,0);
	}, 500);
</script>

<div class="container-fluid rezgo-container rezgo-gift-card-outer-container">
    <div class="row">
		<div class="rezgo-gift-card-inner-col">

			<?php if ($show_gc) { ?>

				<?php $option = isset($r['option']) ? $site->getTours('t=uid&q='.$r['option']) : ''; ?>

					<form id="purchase" class="gift-card-purchase" role="form" method="post" target="rezgo_content_frame">

						<input type="hidden" name="billing_amount">

							<?php if ($option) { ?>
								<input type="hidden" name="option" value ="<?php echo $r['option']; ?>">
							<?php } ?>

							<?php if (REZGO_WORDPRESS) { ?> 
								<?php foreach ($r as $k => $v) {
										if (in_array($k, PAX_ARRAY)) {
											if ((int)$v > 0) {  ?>
											<input type="hidden" name="<?php echo $k; ?>" value ="<?php echo $v; ?>">
										<?php } ?>
									<?php } ?>
								<?php } ?>

							<?php } else { ?>
								<?php if ($r['pax']) { ?>

									<?php foreach ($r['pax'] as $pax => $num){
										if ((int)$num > 0) { ?>
											<input type="hidden" name="<?php echo $pax . '_num'; ?>" value ="<?php echo $num; ?>">
										<?php } ?>
									<?php } ?>

								<?php } ?>
							<?php } ?>

						<div class="rezgo-gift-card-container step-two row">

							<div class="col-lg-8 col-md-12 rezgo-gift-card-left-wrp">

							<div class="col-12">
								<div class="rezgo-gift-card-head">
									<h3 class="gc-page-header space-below"><span>Gift Card Recipient</span></h3>
									<i class="far fa-gift-card gift-icon"></i>
								</div>

								<div class="row">
									<div class="col-12 col-sm-6">
										<div class="form-group">
											<label for="recipient_name" class="control-label">
												<span>Name</span>
											</label>

											<input class="form-control required" name="recipient_name" type="text" placeholder="Full Name" value="">
										</div>
									</div>

									<div class="col-12 col-sm-6">
										<div class="form-group">
											<label for="recipient_email" class="control-label">
												<span>Email Address</span>
											</label>

											<input class="form-control required" name="recipient_email" type="email" placeholder="Email Address" value="">
										</div>
									</div>

								</div>

								<div class="row">
									<div class="col-12">
										<div class="form-group">
											<label for="recipient_message" class="control-label">Your Message (optional)</label>

											<textarea class="form-control gc-recipient-message" name="recipient_message" rows="5" style="resize:none;height:130px;" placeholder="Your Message"></textarea>
										</div>
									</div>
								</div>

								<div id="rezgo-gift-card-memo-sendto"><span></span></div>
							</div>

							<div class="clearfix">

								<input type="hidden" name="gift_card_token" id="gift_card_token" value="" />
								<input type="hidden" name="payment_id" id="payment_id" value=""/>

								<script>
									jQuery('#gift_card_token').val("");
										// create stripe initial error state because we use this to validate the form
									var stripe_error = 0;
								</script>

									<div class="rezgo-payment-frame gift-card-payment row" id="payment_info">

										<div class="col-12 rezgo-gift-card-head">
											<h3 class="gc-page-header space-below">Payment Method</h3>
										</div>

										<div class="col-12">
											<div class="form-group" id="payment_methods">
												<?php
													$card_fa_logos = array(
														'visa' => 'fa-cc-visa',
														'mastercard' => 'fa-cc-mastercard',
														'american express' => 'fa-cc-amex',
														'discover' => 'fa-cc-discover'
													);
													$pmc = 1; // payment method counter 1
												
													foreach($site->getPaymentMethods() as $pay) {
														if(!$using_tmt && $pay['name'] == 'Credit Cards') {
															$set_name = $pay['name'];
															?>

															<div class="rezgo-input-radio">
																<input type="radio" name="payment_method" id="payment_method_credit" class="rezgo-payment-method required" value="Credit Cards">
			
																<label for="payment_method_credit" class="payment-label">
			
																	<div id="icon-container" class="icon-container">
																		<?php foreach($site->getPaymentCards() as $card ) { ?>
																			<i class="fab <?php echo esc_attr($card_fa_logos[$card]); ?>"></i>
																		<?php } ?>
																	</div>
			
																</label>
															</div>
															
															<?php
														} else if ($pay['name'] == 'PayPal Checkout') { ?>
															
															<div class="rezgo-input-radio" id="paypal-url">
																<input type="radio" name="payment_method" id="payment_method_<?php echo esc_attr($pmc); ?>" class="rezgo-payment-method required" value="<?php echo $pay['name']; ?>" />
																<label class="non-cc-method" for="payment_method_<?php echo esc_attr($pmc); ?>"><img src="<?php echo $site->path; ?>/img/logos/paypal.png" style="height:25px; width:auto;"></label>
															</div>
															
															<?php
														}
														
														$pmc++;
													} // end foreach($site->getPaymentMethods()
												?>

											</div>
										</div><!-- // #payment_methods -->

										<div id="payment_data" class="payment-data-payment-request">

											<?php $pmdc = 1; // payment method counter 1 ?>

											<?php foreach($site->getPaymentMethods() as $pay) { ?>

												<?php if(!$using_tmt && $pay['name'] == 'Credit Cards') { ?>

													<div id="payment_cards" class="payment_method_container" style="display:none;">
														<h4 class="payment-method-header">Credit Card Details</h4>

															<?php if ($gateway_id == 'stripe_connect') { ?>

																<!-- Stripe Elements -->
																<script src="https://js.stripe.com/v3/"></script>
																<style>
																	
																	.rezgo-booking-payment-body{
																		/* border-top:1px solid #CCCCCC; */
																		padding: 15px;
																	}
																	.payment-method-header{
																		margin: 20px 0 0 15px;
																	}

																	.StripeElement {
																		box-sizing: border-box;
																		height: 34px;
																		padding: 8px 12px;
																		border: 1px solid #CCC;
																		border-radius: 4px;
																		background-color: white;
																		box-shadow: none;
																		-webkit-transition: box-shadow 150ms ease;
																		transition: box-shadow 150ms ease;
																	}
																	
																	.StripeElement--focus {
																		border-color: #66afe9;
																		outline: 0;
																		-webkit-box-shadow: inset 0 1px 1px rgba(0,0,0,.075),0 0 8px rgba(102,175,233,.6);
																		box-shadow: inset 0 1px 1px rgba(0,0,0,.075),0 0 8px rgba(102,175,233,.6);
																	}
																	
																	.StripeElement--invalid {
																		border-color: #fa755a;
																	}
																	
																	.StripeElement--webkit-autofill {
																		background-color: #fefde5 !important;
																	}

																	#stripe_cardholder_name{
																		height: 34px;
																	}

																	#stripe_cardholder_name::placeholder {
																		opacity: 1;
																		color: #aab7c4;
																		-webkit-font-smoothing: antialiased;
																	}

																	#stripe_cardholder_name_error{
																		padding: 0 0 5px 0;
																	}

																	#stripe_cardholder_name,
																	#card-element{
																			font-family: 'Helvetica Neue', sans-serif;
																		max-width: 400px;
																	}

																	.stripe-payment-title{
																		font-size: 16px;
																	}

																	#card-element{
																		margin-top: 5px;
																	}

																	#card-errors{
																		padding: 5px 0;
																		color: var(--error-color);
																		font-size:14px;
																	}

																	@media screen and (max-width: 650px){
																		#secureFrame{
																			width: 100%;
																			height: 400px;
																		}
																	}
																	@media screen and (max-width:500px){
																		#stripe_cardholder_name,
																		#card-element{
																			font-size: 13px;
																			max-width: 270px;
																		}
																	}

																</style>
																	
																<div class="form-row rezgo-booking-payment-body">
																
																	<input id="stripe_cardholder_name" class ="StripeElement form-control" name="stripe_cardholder_name" placeholder="Name on Card">
																	<span id="stripe_cardholder_name_error" class="payment_method_error">Please enter the cardholder's name</span>

																	<div id="card-element">
																		<!-- Stripe Element will be inserted here. -->
																	</div>
																
																	<!-- Used to display form errors. -->
																	<div id="card-errors" role="alert"></div>
																		<input type="hidden" name="client_secret" id="client_secret" value="" />
																</div>
																
																<?php $currency_base = $site->getBookingCurrency(); ?>

																<script>

																	var clientSecret = '';
																	var paymentId = '';

																	// Create a Stripe client.
																	let stripe = Stripe('<?php echo $company->stripe_public_key; ?>', { stripeAccount: '<?php echo $company->public_gateway_token; ?>' });

																	// Create an instance of Elements.
																	var style = {
																		base: {
																		color: '#32325d',
																			fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
																			fontSmoothing: 'antialiased',
																			fontSize: '14px',
																			'::placeholder': {
																				color: '#aab7c4'
																			}
																		},
																		invalid: {
																			color: '#a94442',
																			iconColor: '#a94442'
																		}
																	};
																	
																	var cardHolder = jQuery('#stripe_cardholder_name');
																	var cardInput = document.getElementById('card-element');
																	
																	var elements = stripe.elements();

																	var card = elements.create('card', {
																					style: style,
																					hidePostalCode: true,
																				});

																	// Add an instance of the card Element into the #card-element div.
																	card.mount('#card-element');
																	
																	var displayError = document.getElementById('card-errors');

																	// create stripe error state to handle form error submission
																	var stripe_error = 1;
																	// console.log('loaded stripe_error');
																	
																	// Handle real-time validation errors from the card Element.
																	card.addEventListener('change', function(event) {
																		if (event.error) {
																			displayError.textContent = event.error.message;
																			stripe_error = 1;
																			cardInput.style.borderColor = '#a94442';
																			// console.log('listener error');
																		} else if (event.empty) {
																			displayError.textContent = 'Please enter your Credit Card details';
																			stripe_error = 1;
																		} else {
																			displayError.textContent = '';
																			stripe_error = 0;
																			cardInput.style.borderColor = '#ccc';
																			// console.log('listener error gone');
																		}
																	});

																	cardHolder.change(function() {
																		if (jQuery(this).val() == ''){
																			jQuery('#stripe_cardholder_name_error').show();
																			jQuery(this).css({'borderColor':'#a94442'});
																			stripe_error = 1;
																		} else {
																			jQuery('#stripe_cardholder_name_error').hide();
																			jQuery(this).css({'borderColor':'#ccc'});
																			stripe_error = 0;
																		}
																	});

																</script>

															<?php } elseif ($gateway_id == 'square') { ?>
															
																<script type="text/javascript" src="https://<?php echo DEBUG_STATE_SQUARE == 1 ? 'sandbox.' : '' ?>web.squarecdn.com/v1/square.js"></script>
															
																<div class="form-row rezgo-booking-payment-body">
															
																	<input id="square_cardholder_name" class="form-control SquareElement" name="square_cardholder_name"
																		placeholder="Name on Card">
																	<span id="square_cardholder_name_error" class="payment_method_error">Please enter the cardholder's name</span>
																	
																	<div id="card-element">
																		<!-- Stripe Element will be inserted here. -->
																	</div>
															
																	<!-- Apple Pay  -->
																	<div id="payment-request-button">
																		<!-- A Stripe Element will be inserted here. -->
																	</div>
															
																	<!-- Used to display form errors. -->
																	<div id="card-errors" role="alert"></div>
															
																	<input type="hidden" name="client_secret" id="client_secret" value="" />
															
																</div>
															
																<style>
																	/* From - https://stripe.com/docs/stripe-js */
															
																	.rezgo-booking-payment-body {
																		padding: 0 0 8px 0;
																	}
															
																	#square_cardholder_name {
																		height: 48px;
																		border-radius: 6px;
																	}
															
																	#square_cardholder_name:focus {
																		border: 2px solid #00f;
																		outline: none !important;
																		box-shadow: none;
																		-moz-box-shadow: none;
																		-webkit-box-shadow: none;
																	}
															
															
																	#square_cardholder_name::placeholder {
																		opacity: 1;
																		color: #aab7c4;
																		font-weight: 400;
																	}
															
																	#square_cardholder_name_error {
																		padding: 0 0 5px 0;
																	}
															
																	#square_cardholder_name,
																	#card-element {
																		font-family: 'Helvetica Neue', sans-serif;
																		max-width: 400px;
																	}
															
																	.square-payment-title {
																		font-size: 16px;
																	}
															
																	#card-element {
																		margin-top: 5px;
																	}
															
																	#card-errors {
																		padding: 5px 0;
																		color: var(--error-color);
																		font-size: 14px;
																	}
															
																	.sq-card-message {
																		display: none;
																	}
															
																	@media screen and (max-width: 650px) {
																		#secureFrame {
																			width: 100%;
																			height: 400px;
																		}
																	}
															
																	@media screen and (max-width: 500px) {
															
																		#square_cardholder_name,
																		#card-element {
																			font-size: 14px;
																			width: 248px;
																			max-width: 270px;
																		}
																	}
																</style>
															
																<script>
																	var cardHolder = jQuery('#square_cardholder_name');
																	var cardInput = document.getElementById('card-element');
																	var card;
																	var square_error = 1;
																	var square_invalid_error = 0;
																	var app_id;
																	var ajax_success = false;

																	function createSquareCard() {

																		jQuery.ajax({
																			url: '<?php echo admin_url('admin-ajax.php'); ?>' + '?action=rezgo&method=gateways_square&amount=' + order_total + '&currency=<?php echo $currency_base; ?>',
																			context: document.body,
																			dataType: "json",
																			data: {
																				rezgoAction: 'square_card_init'
																			},
																			success: function (data) {
																				app_id = data.app_id;

																				// Set the flag to true indicating AJAX call success
																				ajax_success = true;
																				addSquareContents();
																			}
																		});

																	}

																	/************ Styles for Square Element **************/
																	const style = {
																		'.input-container.is-focus': {
																			borderColor: '#006AFF',
																		},
																		'.input-container.is-error': {
																			borderColor: 'rgb(169, 68, 66)',
																		},
																		input: {
																			fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
																			fontSize: '14px',
																		},
																		'input::placeholder': {
																			color: '#808d9d',
																		},
																		'input.is-error': {
																			color: 'rgb(169, 68, 66)',
																		},

																		'@media screen and (max-width: 500px)': {
																			'input': {
																				'fontSize': '14px',
																			}
																		}
																	};

																	/************ create square error state to handle form error submission *************/

																	function displayErrors(error_message) {
																		const error_div = document.getElementById('card-errors');
																		error_div.textContent = error_message;
																		error_div.style.visibility = 'visible';
																		square_error = 1;
																	}

																	function clearPreviousError() {
																		const error_div = document.getElementById('card-errors');
																		error_div.textContent = '';
																		error_div.style.visibility = 'hidden';
																		square_invalid_error = 1;
																		jQuery("#card-element").css('border', 'none');
																	}

																	/*************** Initialize square payment card ***************/
																	function initializeCard(payments) {
																		return payments.card({
																			style: style
																		}).then((card) => {

																			return card.attach('#card-element').then(() => card);
																		});
																	}

																	createSquareCard();

																	/*************** Square payment card Init process ***************/
																	document.addEventListener('DOMContentLoaded', function () {
																		if (ajax_success) {
																			addSquareContents();
																		}
																	});

																	function addSquareContents() {
																		let payments;

																		if (!window.Square) {
																			throw new Error('Square.js failed to load properly');
																		}

																				payments = window.Square.payments(app_id);

																		initializeCard(payments).then((initializedCard) => {
																			card = initializedCard;

																			/************* Add event listener for error class added **************/
																			card.addEventListener('errorClassAdded', (event) => {
																				clearPreviousError();
																				const errorMessage = jQuery('.sq-card-message.sq-visible').text();
																				displayErrors(errorMessage);
																				square_invalid_error = 1;
																				square_error = 1;

																			});

																			card.addEventListener('focus', (event) => {
																			jQuery("#card-element").css('border', 'none');  
																			});

																			/************* Add event listener for focus class removed **************/
																			card.addEventListener('focusClassRemoved', (event) => {
																				/*********  Clear any previous error messages *********/
																				clearPreviousError();
																				/********  Check for empty fields ********/
																				if (event.detail.previousState.isEmpty) {
																					displayErrors('Please fill out all required fields.');
																					square_error = 1;
																				} else {
																					/************ Clear any previous error messages only if no square errors ***********/
																					if (!event.detail.previousState.hasErrorClass) {
																						clearPreviousError();
																						square_error = 0;
																					}
																				}

																			});

																		}).catch((error) => {
																			console.error('Initializing Card failed', error);
																		});
                                                                            }

                                                                    cardHolder.change(function () {
																		if (jQuery(this).val() == '') {
																			jQuery('#square_cardholder_name_error').show();
																			jQuery(this).css("border-color", "#a94442");
																			square_error = 1;
																		} else {
																			jQuery('#square_cardholder_name_error').hide();
																			jQuery(this).css("border-color", "#ccc");
                                                                                    square_error = 0;
                                                                                }
                                                                            });
                                                                </script>
                                                                    
                                                            <?php } else { ?>

																<?php if (REZGO_WORDPRESS) { ?>
																	<iframe scrolling="no" frameborder="0" name="gift_payment" id="rezgo-gift-payment" src="<?php echo home_url(); ?>?rezgo=1&mode=booking_payment"></iframe>
																<?php } else { ?>
																	<iframe scrolling="no" frameborder="0" name="gift_payment" id="rezgo-gift-payment" src="<?php echo $site->base; ?>/booking_payment.php"></iframe>
																<?php } ?>

																<script type="text/javascript">
																	iFrameResize({
																		enablePublicMethods: true,
																		scrolling: false
																	}, '#gift_payment');
																</script>

															<?php } ?>

														</div> <!-- payment cards -->

													<?php } elseif($pay['name'] == 'PayPal Checkout') { ?>

														<div id="payment_method_<?php echo $pmdc; ?>_box" class="payment_method_box" style="display:none;">
																	
															<div id="payment_method_<?php echo $pmdc; ?>_container" class="payment_method_container">
																<h4 class="payment-method-header" id="paypal-button-header">Click to pay with PayPal</h4>
																<div id="paypal-button-container" style="max-width: 400px; margin-top:20px;"></div>

																<span id="paypal_error" class="payment_method_error">
																	<br>Please proceed with PayPal before completing your purchase
																</span>
															</div>

															<!-- used for validating that the user paid via paypal before submitting -->
															<input type="hidden" id="paypal_checkout_id" value="">
															
														</div>
																
                                                        <script>
															var paypal_checkout = '';
                                                            jQuery.ajax({
																url: '<?php echo admin_url('admin-ajax.php'); ?>' + '?action=rezgo',
                                                                context: document.body,
                                                                dataType: "json",
                                                                data: { 
																	method: 'gateways_paypal',
																	rezgoAction: 'paypal_get_url',
																	amount: '<?php echo $order_total; ?>',
																},
                                                                complete: function (data) {

																	data = JSON.parse(data.responseText);
																	console.log("Paypal public data response:", data);

                                                                    let script = document.createElement('script');
                                                                    script.onload = function () {
																		paypal_checkout = data.checkout;
                                                                        paypalCheckoutRender();
                                                                    };
                                                                    script.src = data.url;
																	script.setAttribute('data-partner-attribution-id', 'Rezgo_STP_PPCP');
                                                                    document.head.appendChild(script);
                                                                }
                                                            });
                                                        </script>

														<?php
															
													} 
																
													$pmdc++; 
												} // end foreach($site->getPaymentMethods() ?>

										</div> <!-- payment data -->
									</div>
								</div>

								<hr>

								<div class="clearfix">
									<div class="rezgo-gift-card-head">
										<h3 class="gc-page-header space-below"><span>Billing Information</span></h3>
									</div>

									<div class="col-12">
										<div class="form-group">
											<div class="col-12">
												<label for="billing_first_name" class="control-label">
													<span>Name</span>
												</label>
											</div>
											<div class="row">
												<div class="col-12 col-sm-6">
													<input class="form-control required" name="billing_first_name" id="billing_first_name" type="text" placeholder="First Name" />
												</div>

												<div class="col-12 col-sm-6">
													<input class="form-control required" name="billing_last_name" id="billing_last_name" type="text" placeholder="Last Name" />
												</div>
											</div>
										</div>

										<div class="form-group">
											<label for="billing_address_1" class="control-label">
												<span>Address</span>
											</label>
											<div class="rezgo-form-input col-12">
												<input class="form-control required" name="billing_address_1" id="billing_address_1" type="text" placeholder="Address 1" />
											</div>
											<div class="rezgo-form-input col-12">
												<input class="form-control" name="billing_address_2" id="billing_address_2" type="text" placeholder="Address 2 (optional)" />
											</div>
										</div>

										<label for="rezgo_confirm_id" id="rezgo_confirm_label">
											<span>Confirm ID</span>
										</label>

										<input name="rezgo_confirm_id" id="rezgo_confirm_id" type="text" value="" tabindex="-1" autocomplete="off">

										<div class="form-group">
											<div class="rezgo-form-row">
												<div class="col-12 rezgo-form-input">
													<label for="billing_city" class="control-label">
														<span>City</span>
													</label>

													<input class="form-control required" id="billing_city" name="billing_city" type="text" placeholder="City" />
												</div>
											</div>
										</div>


										<div class="form-group">
											<div class="rezgo-form-row">
												<div class="col-12 rezgo-form-input">
													<label for="billing_postal_code" class="control-label">
														<span>Zip/Postal</span>
													</label>

													<input class="form-control required" name="billing_postal_code" type="text" placeholder="Zip/Postal Code" id="billing_postal_code" />
												</div>
											</div>
										</div>

										<div class="form-group">
											<div class="rezgo-form-row">
												<div class="col-12 rezgo-form-input">
													<label for="billing_country" class="control-label">
														<span>Country</span>
													</label>

													<select id="billing_country" name="billing_country" class="form-select">
														<?php foreach ($site->getRegionList() as $iso => $name) { ?>
															<option value="<?php echo $iso; ?>" <?php echo (($iso == $companyCountry) ? 'selected' : ''); ?> ><?php echo ucwords($name); ?></option>
														<?php } ?>
													</select>
												</div>
											</div>
										</div>

										<div class="form-group">
											<div class="rezgo-form-row">
												<div class="col-12 rezgo-form-input">
														<div class="rezgo-form-row">
														<label for="billing_stateprov" class="control-label">
															<span>State/Prov</span>
														</label>

														<select id="billing_stateprov" class="form-select" style="<?php echo (($companyCountry != 'ca' && $companyCountry != 'us' && $companyCountry != 'au') ? 'display:none' : ''); ?>" ></select>

														<input id="billing_stateprov_txt" class="form-control" name="billing_stateprov" type="text" value="" placeholder="State/Province" style="<?php echo (($companyCountry != 'ca' && $companyCountry != 'us' && $companyCountry != 'au') ? '' : 'display:none'); ?>" />
													</div>
												</div>
											</div>
										</div>

										<div class="form-group">
											<div class="rezgo-form-row">
												<div class="col-12 rezgo-form-input">
													<label for="billing_email" class="control-label">
														<span>Email</span>
													</label>

													<input class="form-control required" name="billing_email" id="billing_email" type="email" placeholder="Email Address" />
												</div>
											</div>
										</div>

										<div class="form-group">
											<div class="rezgo-form-row">
												<div class="col-12 rezgo-form-input">
													<label for="billing_phone" class="control-label">
														<span>Phone</span>
													</label>

													<input class="form-control required" name="billing_phone" id="billing_phone" type="text" placeholder="Phone Number" />
												</div>
											</div>
										</div>


										<input type="hidden" name="validate[language]" id="validate_language" value="">
										<input type="hidden" name="validate[height]" id="validate_height" value="">
										<input type="hidden" name="validate[width]" id="validate_width" value="">
										<input type="hidden" name="validate[tz]" id="validate_tz" value="">
										<input type="hidden" name="validate[agent]" id="validate_agent" value="">
										<input type="hidden" name="validate[callback]" id="validate_callback" value="">

										<script>
										jQuery(function($) {
											$('#validate_language').val(navigator.language);
											$('#validate_height').val(window.innerHeight);
											$('#validate_width').val(window.innerWidth);
											$('#validate_tz').val(new Date().getTimezoneOffset());
											$('#validate_agent').val(navigator.userAgent);
											$('#validate_callback').val(window.location.protocol + '//' + window.location.hostname + '<?php echo esc_html($site->base); ?>' + '/3DS');
										});
										</script>

										<div id='rezgo-credit-card-success' style='display:none;'></div>
										
										<div id="rezgo-payment-wrp">
											<div class="row">
												<div class="col-12">
													<div class="rezgo-form-row rezgo-terms-container">
														<div class="col-sm-12 rezgo-payment-terms">
															<div class="rezgo-form-input">

																<div class="checkbox">
																	<label id="rezgo-terms-check">

																		<input type="checkbox" id="agree_terms" name="agree_terms" value="1" required style="position:relative; top:1px;"/>

																		I agree to the

																		<a data-toggle="collapse" class="rezgo-terms-link" onclick="jQuery('#rezgo-privacy-panel').hide(); jQuery('#rezgo-terms-panel').toggle();">
																			<span>Terms and Conditions</span>
																		</a>
																		and
																		<a data-toggle="collapse" class="rezgo-terms-link" onclick="jQuery('#rezgo-terms-panel').hide(); jQuery('#rezgo-privacy-panel').toggle();">
																			<span>Privacy Policy</span>
																		</a>

																	</label>

																	<span for="agree_terms" class="help-block"></span>

																	<div id="rezgo-terms-panel" class="collapse rezgo-terms-panel">
																		<?php echo wp_kses($site->getPageContent('terms'), ALLOWED_HTML); ?>
																	</div>

																	<div id="rezgo-privacy-panel" class="collapse rezgo-terms-panel">
																		<?php echo wp_kses($site->getPageContent('privacy'), ALLOWED_HTML); ?>
																	</div>

																</div>

															</div>

														</div>
													</div>
												</div>
											</div>
										</div>

										<div class="col-sm-12 rezgo-payment-terms">
											<div id="rezgo-book-terms">
												<div class="help-block" id="terms_credit_card">
												<span> Please note that your credit card will be charged.</span>
													<br>
													<span>If you are satisfied with your entries, please confirm by clicking the
														<span id="rezgo-book-terms-wording">
															<span>&quot;Complete Purchase&quot;</span>
														</span>
														button.
													</span>
												</div>
											</div>
										</div>

								</div>
							</div>

							<div id="rezgo-gift-message" class="row" style="display:none;">
								<div id="rezgo-gift-message-body" class="col-sm-8 offset-sm-2"></div>
								<div id="rezgo-gift-message-wait" class="col-sm-2"><i class="far fa-sync fa-spin fa-3x fa-fw"></i></div>
							</div>

							<hr>

							<div id="rezgo-gift-errors" style="display:none;">
								<div>Some required fields are missing or incorrect. Please review the highlighted fields.</div>
							</div>

							<div class="rezgo-gift-card-cta">
								<span class="btn-check"></span>
								<button type="submit" class="btn rezgo-btn-book btn-lg btn-block" id="rezgo-complete-payment">
									<span>
										Complete Purchase <span id="gc_total_due"></span>
									</span>
								</button>

								<?php if (REZGO_LITE_CONTAINER) { ?>
									<?php // build link with $_POST
									$pax = '';
									foreach ($_POST['pax'] as $k => $v) {
										if ((int)$v > 0) {
											$pax .= '&'.$k.'='.$v;
										}
									}
									$giftlink = $_POST['option'] ? '/'.$_POST['option'].'/'.$_POST['date'] : '';
									$backlink = '?billing_amount='.$_POST['billing_amount'].'&custom_billing_amount='.$_POST['custom_billing_amount'].'&recipient_name='.$_POST['recipient_name'].'&recipient_email='.$_POST['recipient_email'].'&recipient_message='.$_POST['recipient_message']; ?>
									
									<a class="underline-link" id="gc_backlink" onclick="<?php echo LOCATION_WINDOW; ?>.location.href='<?php echo $site->base; ?>/gift-card<?php echo $giftlink.$backlink.$pax; ?>'; return false;"><span>Previous Step</span></a>

								<?php } else { ?>
								<?php // create backlink to preserve pax changes on back
									$pax = '';
									$pax_count = 0;
									$giftlink = '';
									if (isset($r['buy_as_gift'])){ 
										if (REZGO_WORDPRESS) {
											foreach ($r as $k => $v) {
												if (in_array($k, PAX_ARRAY)) {
													if (strpos($k, '_num')) {
														$k = str_replace('_num','', $k);
													} 
													if ((int)$v > 0) {
														$pax .= ($pax_count > 0 ? '&' : '?') .$k.'='.$v;
														$pax_count++;
													}
												}
											}
											$giftlink = $r['option'] ? '/'.$r['option'].'/'.$r['date'] : '';
										} else {
											foreach ($_POST['pax'] as $k => $v) {
												if ((int)$v > 0) {
													$pax .= ($pax_count > 0 ? '&' : '?') .$k.'='.$v;
													$pax_count++;
												}
											}
											$giftlink = $_POST['option'] ? '/'.$_POST['option'].'/'.$_POST['date'] : '';
										}
									}
								?>

									<a class="underline-link" id="gc_backlink" onclick="<?php echo LOCATION_WINDOW; ?>.location.href='<?php echo $site->base; ?>/gift-card<?php echo $giftlink.$pax; ?>'; return false;"><span>Previous Step</span></a>
								<?php } ?>
							</div>

							<?php if($site->exists(REZGO_CAPTCHA_PUB_KEY)) { ?>	
								<input type="hidden" name="recaptcha_response" id="recaptchaResponse">
							<?php } ?>
						</form>
					</div>
					<?php require('fixed_cart_gift_card.php'); ?>
				</div>

			<?php } ?>
		</div>
	</div>

<div class="modal fade bd-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="scaModal" aria-hidden="true" id="sca_modal" style="bottom:0 !important; top:auto !important;">
    <div class="modal-dialog modal-md" style="top: calc(100% - 900px);">
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

<?php if (!REZGO_WORDPRESS) { ?>
<script type="text/javascript" src="<?php echo $site->path; ?>/js/jquery.form.js"></script>
<script type="text/javascript" src="<?php echo $site->path; ?>/js/jquery.validate.min.js"></script>
<script type="text/javascript" src="<?php echo $site->path; ?>/js/jquery.selectboxes.js"></script>
<script src="https://www.google.com/recaptcha/api.js?render=6LfIFM0ZAAAAAHZuD4ht2Nk2oBYVki4vTktpeOXA"></script>
<?php } ?>

<?php if (REZGO_WORDPRESS && $site->exists(REZGO_CAPTCHA_PUB_KEY)) { ?>
	<script src="https://www.google.com/recaptcha/api.js?render=<?php echo esc_html(REZGO_CAPTCHA_PUB_KEY); ?>"></script>
<?php } ?>

<?php if ($show_gc) { ?>
	<script>

	jQuery(function($){	

		let scaModal = new bootstrap.Modal(document.getElementById('sca_modal'));

		<?php if (REZGO_LITE_CONTAINER) { ?>
			// simulates user click on the back button with backlink params
			if (window.history && window.history.pushState) {
				$(window).on('popstate', function() {
					$('#gc_backlink').click();
					return false;
				});
				window.history.pushState('forward', null);
			}
		<?php } ?>
	
		/* FORM (#purchase) */

		// STATES VAR
		let ca_states = <?php echo  json_encode( $site->getRegionList('ca') ); ?>;
		let us_states = <?php echo  json_encode( $site->getRegionList('us') ); ?>;
		let au_states = <?php echo  json_encode( $site->getRegionList('au') ); ?>;

		// FORM ELEM
		var $purchaseForm = $('#purchase');
		var $purchaseBtn = $('#rezgo-complete-payment');
		var $formMessage = $('#rezgo-gift-message');
		var $formMsgBody = $('#rezgo-gift-message-body');
		var $amtSelect = $('#rezgo-billing-amount');
		var $amtCustom = $('#rezgo-custom-billing-amount');
		
		// Validation Setup
		$.validator.setDefaults({
			highlight: function(element) {
				if ($(element).attr("type") == "checkbox") {
					$(element).closest('.rezgo-form-checkbox').addClass('has-error');
				} else if ($(element).attr("name")=="waiver") {
					$(element).parent().find('.error').show();
				} else if ($(element).attr("type") == "radio") {
					$(element).closest('.rezgo-input-radio').addClass('has-error');
				} else {
					$(element).closest('.rezgo-form-input').addClass('has-error');
				}

				$(element).closest('.form-group').addClass('has-error');
			},
			unhighlight: function(element) {
				if ( $(element).attr("type") == "checkbox" ) {
					$(element).closest('.rezgo-form-checkbox').removeClass('has-error');
				} else if ($(element).attr("type") == "radio") {
					$(element).closest('.rezgo-input-radio').addClass('has-error');
				} else {
					$(element).closest('.rezgo-form-input').removeClass('has-error');
				}

				$(element).closest('.form-group').removeClass('has-error');
			},
			focusInvalid: false,
			errorElement: 'span',
			errorClass: 'help-block',
			errorPlacement: function(error, element) {
				if ($(element).attr("name") == "name" || $(element).attr("name") == "pan" || $(element).attr("name") == "cvv" || $(element).attr("name") == "waiver") {
					error.hide();
				} else if ($(element).attr("type") == "radio") {
					error.insertAfter(element.parent().parent());
				} else if ($(element).attr("name") == "agree_terms") {
					error.insertAfter(element.parent().parent());
				} else if ($(element).attr("type") == "checkbox") {
					error.insertAfter(element.siblings('.rezgo-form-comment'));
				} else {
					error.insertAfter(element);
				}
			}
		});

		$.validator.addMethod("validate_domain", function(value, element) {
			if (/^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/.test(value)) {
				return true;
			} else {
				return false;
			}
		}, "Enter a valid email address");

		// FORM VALIDATE
		$purchaseForm.validate({
			rules: {
				recipient_email: {
					validate_domain: true,
				},
				billing_email: {
					validate_domain: true,
				}
			},
			messages: {
				recipient_first_name: {
					required: "Enter recipient first name"
				},
				recipient_last_name: {
					required: "Enter recipient last name"
				},
				recipient_address_1: {
					required: "Enter recipient address"
				},
				recipient_city: {
					required: "Enter recipient city"
				},
				recipient_postal_code: {
					required: "Enter recipient postal code"
				},
				recipient_phone_number: {
					required: "Enter recipient phone number"
				},
				recipient_email: {
					required: "Enter a valid email address"
				},
				billing_first_name: {
					required: "Enter billing first name"
				},
				billing_last_name: {
					required: "Enter billing last name"
				},
				billing_address_1: {
					required: "Enter billing address"
				},
				billing_city: {
					required: "Enter billing city"
				},
				billing_state: {
					required: "Enter billing state"
				},
				billing_country: {
					required: "Enter billing country"
				},
				billing_postal_code: {
					required: "Enter billing postal code"
				},
				billing_email: {
					required: "Enter a valid email address"
				},
				billing_phone: {
					required: "Enter billing phone number"
				},
				payment_method: {
					required: "Please select a payment method"
				},
				terms_agree: {
					required: "You must agree to our terms &amp; conditions"
				}
			}
		});

		// FORM COUNTRY & STATES OPTIONS SWITCH
		$('#billing_country').change(function() {
			var country = $(this).val();

			$('#billing_stateprov').removeOption(/.*/);
			switch (country) {
				case 'ca':
					$('#billing_stateprov_txt').hide();
					$('#billing_stateprov').addOption(ca_states, false).show();
					$('#billing_stateprov_txt').val($('#billing_stateprov').val());
					break;
				case 'us':
					$('#billing_stateprov_txt').hide();
					$('#billing_stateprov').addOption(us_states, false).show();
					$('#billing_stateprov_txt').val($('#billing_stateprov').val());
					break;
				case 'au':
					$('#billing_stateprov_txt').hide();
					$('#billing_stateprov').addOption(au_states, false).show();
					$('#billing_stateprov_txt').val($('#billing_stateprov').val());
					break;		
				default:
					$('#billing_stateprov').hide();
					$('#billing_stateprov_txt').val('');
					$('#billing_stateprov_txt').show();
					break;
			}
		});
		$('#billing_stateprov').change(function() {
			var state = $(this).val();

			$('#billing_stateprov_txt').val(state);
		});
		<?php if (in_array($site->getCompanyCountry(), array('ca', 'us', 'au'))) { ?>
			$('#billing_stateprov').addOption(<?php echo esc_html($site->getCompanyCountry()); ?>_states, false);
			$('#billing_stateprov_txt').val($('#billing_stateprov').val());
		<?php } ?>

		<?php if ($site->exists(REZGO_CAPTCHA_PUB_KEY)) { ?>
			// RECAPTCHA V3
			function verifyRecaptcha() {
				grecaptcha.ready(function() {
					grecaptcha.execute('<?php echo REZGO_CAPTCHA_PUB_KEY; ?>', {action: 'submit'}).then(function(token) {
						var recaptchaResponse = document.getElementById('recaptchaResponse');
						recaptchaResponse.value = token;
					});
				});
			}
		<?php } ?>

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
    	sca_callback = function(code) {

			if(!code) return false;

			$('#sca_modal').modal('hide');

			if(passthrough) {
				let data = JSON.parse(code); // parse data sent back from 3DS
				data.pass = passthrough; // add the passthrough data to the array
				code = JSON.stringify(data);
			}

			$('#gift_card_token').val(code);
			$('#payment_id').val(1); // needed to trigger the validate step on commit

			$("#rezgo-gift-message-body").removeClass();
       		$("#rezgo-gift-message-body").addClass('col-sm-8 offset-sm-2');
			$('#rezgo-gift-message-body').html('Please wait one moment ...');
			payment_wait(true);
			$('#rezgo-gift-message').fadeIn();

			// Submit the card token request and wait for a response
			$('#rezgo-gift-payment').contents().find('#payment').submit();

			check_card_token();

		}

		// FORM SUBMIT
		function creditConfirm(token) {
			// the credit card transaction was completed, give us the token
			$('#gift_card_token').val(token);
		}
		function error_payment() {
			$('#rezgo-gift-errors').show();

			setTimeout(function(){
				$('#rezgo-gift-errors').hide();
			}, 8000);
		}
		function check_card_token() {
			var card_token = $('#gift_card_token').val();

			if (card_token == '') {
				// card token has not been set yet, wait and try again
				setTimeout(function() {
					check_card_token();
				}, 200);
			} else {
				// TOKEN SUCCESS ANIM
				//showSuccessIcon($('#rezgo-credit-card-success'));
				// the field is present? submit normally
				$purchaseForm.ajaxSubmit({
					url: '<?php echo admin_url('admin-ajax.php'); ?>', 
					data: {
						action: 'rezgo',
						method: 'gift_card_ajax',
						rezgoAction:'addGiftCard'
					},
					success: function(data){
						var strArray, json, card;

						strArray = data.split("|||");         
						strArray = strArray.slice(-1)[0];     
						json = JSON.parse(strArray);          
						response = json.response;
						card = json.card;
						
						let body;

						if (response == 1) {
							$purchaseForm[0].reset();
							<?php echo LOCATION_REPLACE; ?>('<?php echo esc_html($site->base); ?>/gift-receipt/'+card);
						} else {
							if (response == 2) {
								body = 'Sorry, your transaction could not be completed at this time.';
							} else if (response == 3) {
								body = 'Sorry, your payment could not be completed. Please verify your card details and try again.';
							} else if (response == 4) {
								body = 'Sorry, there has been an error with your transaction and it can not be completed at this time.';
							} else if (response == 5) {
								body = 'Sorry, you must have a credit card attached to your Rezgo Account in order to purchase a gift card.<br><br>Please go to "Settings &gt; Rezgo Account" to attach a credit card.';
							} else if (response == 8) {

								if(json.direct) {
									// frictionless flow   
									sca_window('direct', null, json.direct, json.pass);
									return false;
								} else {
									// challenge flow
									sca_window('iframe', json.url, json.post, json.pass);
								}

							} else {
								body = 'Sorry, an unknown error has occurred. If this keeps happening, please contact <?php echo esc_html(addslashes($company->company_name)); ?>.';
							}

							$('#rezgo-credit-card-success').hide().empty();
							reset_payment(body);
						}
					}, 
					error: function() {
						var body = 'Sorry, the system has suffered an error that it can not recover from.<br />Please try again later.<br />';
						reset_payment(body);
					}
				});
			}
		}
		function submit_payment() {

			<?php if ($site->exists(REZGO_CAPTCHA_PUB_KEY)) { ?>
				verifyRecaptcha();
			<?php } ?>

			// FORM VALIDATION
			let validationCheck = $purchaseForm.valid();
			let payment_method = $('input:radio[name=payment_method]:checked').val();
			let force_error = 0;

			// if we set a card token via a SCA challenge, clear it for a potential new one
			if(passthrough) {
				$('#gift_card_token').val('');
				$('#payment_id').val('');
			}

			if(payment_method == 'Credit Cards') {
			
			<?php if ($gateway_id == 'stripe_connect') { ?>

				// MESSAGE TO CLIENT
                $purchaseBtn.attr('disabled', 'disabled');
                $formMessage.hide();

                // catch empty fields on stripe
                if (cardInput.classList.contains('StripeElement--empty')) {
					cardInput.style.borderColor = '#a94442';		
					displayError.textContent = 'Please enter your Credit Card details';
					stripe_error = 1;
				}
                if (cardHolder.val() == '') {
                    $('#stripe_cardholder_name').css({ 'borderColor': '#a94442' });
					$('#stripe_cardholder_name_error').show();
					stripe_error = 1;
				}

			<?php } elseif ($gateway_id == 'square') { ?>
            var tokenized_square_card = false;
            if (cardHolder.val() == '') {
                $('#square_cardholder_name').css("border-color", "#a94442");
                $('#square_cardholder_name_error').show();
                square_error = 1;
            } else {
                try {
                    card.tokenize().then(token_result => {
                        $('#gift_card_token').val();
                        console.log(token_result);
                        if (token_result.status === 'OK') {
                            $('#gift_card_token').val(token_result.token);
                            tokenized_square_card = true;

                        } else {

                            let error_message = `Tokenization failed with status: ${token_result.status}`;
                            if (token_result.errors) {
                                error_message += ` and errors: ${JSON.stringify(
                                    token_result.errors
                                )}`;
                            }
                            square_error = 1;
							reset_payment(error_message);
                        }
                    }).catch(error => {
                            console.log("An error occurred during tokenization:", error);
                            square_error = 1;
                    });
                } catch (error) {
                    console.log("Something went wrong during Tokenization");
                    square_error = 1;
                }
            }
            if(!tokenized_square_card && square_error){
                $('#card-element').css("border-radius", "6px");
                $('#card-element').css("border", "#a94442 solid 1px");
                displayErrors('Please fill out all required fields.');
            }
					
			<?php } else { ?>

				let $cardForm = $('#rezgo-gift-payment').contents().find('#payment');
				
				// MESSAGE TO CLIENT
				$purchaseBtn.attr('disabled','disabled');
				$formMessage.hide();

				// FORM VALIDATION
				$cardForm.validate({
					messages: {
						name: {
							required: ""
						},
						pan: {
						required: ""
						},
						cvv: {
							required: ""
						}
					},
					highlight: function(element) {
						$(element).closest('.form-group').addClass('has-error');
					},
					unhighlight: function(element) {
						$(element).closest('.form-group').removeClass('has-error');
					},
					errorClass: 'help-block',
					focusInvalid: false,
					errorElement: 'span'
				});
				if (!$cardForm.valid()) {
					validationCheck = false; 
				}

			<?php } ?>

			} else if(payment_method == 'PayPal Checkout') {

				if(!$('#paypal_checkout_id').val()) {
					force_error = 1;
					$('#paypal_error').show();
				}
			}

				// UNVALID FORM
				// error_payment()
				if (!validationCheck || stripe_error ||square_error || force_error) {
					$purchaseBtn.removeAttr('disabled');
					// console.log(stripe_error);
					error_payment();
				}

				// VALID FORM
				// 1) check_card_token()
				// 2) creditConfirm()
				// 3) $purchaseForm.ajaxSubmit()
				else {

					if(payment_method == 'Credit Cards') {

					<?php if ($gateway_id == 'stripe_connect') { ?>

						$("#rezgo-gift-message-body").removeClass();
                   	 	$("#rezgo-gift-message-body").addClass('col-sm-8 offset-sm-2');
						$('#rezgo-gift-message-body').html('Please wait one moment ...');
						payment_wait(true);
						$('#rezgo-gift-message').fadeIn();
					
						if(stripe_error != 1) {

							card.update({
								value: { postalCode: $('#billing_postal_code').val() }
							});
		
							stripe.createPaymentMethod({
								type: 'card',
								card: card,
								billing_details: {
									name: $('#stripe_cardholder_name').val(),
								},
							}).then((result) => {

								if (result.error) {
									console.log(result);

									card.clear();
									reset_payment(result.error.message);

								} else {

									$('#gift_card_token').val(result.paymentMethod.id);

									check_card_token();

								}

							}).catch((error) => {

								console.error(error);

								card.clear();
								reset_payment(error);

							});
						}

					<?php } else { ?>

						$("#rezgo-gift-message-body").removeClass();
						$("#rezgo-gift-message-body").addClass('col-sm-8 offset-sm-2');
						$('#rezgo-gift-message-body').html('Please wait one moment ...');
						payment_wait(true);
						$('#rezgo-gift-message').fadeIn();

						if (payment_method == 'Credit Cards') {

							let $cardForm = $('#rezgo-gift-payment').contents().find('#payment');

							// Clear the existing credit card token, just in case one has been set from a previous attempt
							$('#gift_card_token').val('');

							// Submit the card token request and wait for a response
							$cardForm.submit();

							// Wait until the card token is set before continuing (with throttling)
							check_card_token();
						}

					<?php } ?>
				} else {

					$("#rezgo-gift-message-body").removeClass();
					$("#rezgo-gift-message-body").addClass('col-sm-8 offset-sm-2');
					$('#rezgo-gift-message-body').html('Please wait one moment ...');
					payment_wait(true);
					$('#rezgo-gift-message').fadeIn();

					// the field is present? submit normally
					$purchaseForm.ajaxSubmit({
						url: '<?php echo admin_url('admin-ajax.php'); ?>', 
						data: {
							action: 'rezgo',
							method: 'gift_card_ajax',
							rezgoAction:'addGiftCard'
						},
						success: function(data){
							var strArray, json, card;

							strArray = data.split("|||");         
							strArray = strArray.slice(-1)[0];     
							json = JSON.parse(strArray);          
							response = json.response;
							card = json.card;
							
							let body;

							if (response == 1) {
								$purchaseForm[0].reset();
								<?php echo LOCATION_REPLACE; ?>('<?php echo esc_html($site->base); ?>/gift-receipt/'+card);
							} else {
								if (response == 2) {
									body = 'Sorry, your transaction could not be completed at this time.';
								} else if (response == 3) {
									body = 'Sorry, your payment could not be completed. Please verify your card details and try again.';
								} else if (response == 4) {
									body = 'Sorry, there has been an error with your transaction and it can not be completed at this time.';
								} else if (response == 5) {
									body = 'Sorry, you must have a credit card attached to your Rezgo Account in order to purchase a gift card.<br><br>Please go to "Settings &gt; Rezgo Account" to attach a credit card.';
								} else if (response == 8) {

								if(json.direct) {
									// frictionless flow   
									sca_window('direct', null, json.direct, json.pass);
									return false;
								} else {
									// challenge flow
									sca_window('iframe', json.url, json.post, json.pass);
								}
								
							} else {
									body = 'Sorry, an unknown error has occurred. If this keeps happening, please contact <?php echo addslashes($company->company_name); ?>.';
								}

								$('#rezgo-credit-card-success').hide().empty();
								reset_payment(body);
							}
						}, 
						error: function() {
							var body = 'Sorry, the system has suffered an error that it can not recover from.<br />Please try again later.<br />';
							$formMsgBody.addClass('alert alert-danger').html(body);
						}
					});

				}
					
			}
		}

		function payment_wait(wait) {
			if (wait) {
				$('#rezgo-gift-message-wait').show();
			} else {
			$('#rezgo-gift-message-body').html('');
			$('#rezgo-gift-message-wait').hide();
			}
		}
		
		function reset_payment(error_message) {
			$('#rezgo-complete-payment').removeAttr('disabled');
			payment_wait(false);
			$('#rezgo-gift-message-body').html(error_message);
		}
		$purchaseForm.submit(function(e) {
			e.preventDefault();
			submit_payment();
		});
	});

	</script>
<?php } ?>

<script>	

jQuery(function($){	
	// MONEY FORMATTING
	let form_symbol = '$';
	let form_decimals = '2';
	let form_separator = ',';

	const currency =  decodeURIComponent( '<?php echo rawurlencode( (string) $company->currency_symbol ); ?>' );
	Number.prototype.formatMoney = function(decPlaces, thouSeparator, decSeparator) {
		var n = this,
		decPlaces = isNaN(decPlaces = Math.abs(decPlaces)) ? form_decimals : decPlaces,
		decSeparator = decSeparator == undefined ? "." : decSeparator,
		thouSeparator = thouSeparator == undefined ? form_separator : thouSeparator,
		sign = n < 0 ? "-" : "",
		i = parseInt(n = Math.abs(+n || 0).toFixed(decPlaces)) + "",
		j = (j = i.length) > 3 ? j % 3 : 0;

		var dec;
		var out = sign + (j ? i.substr(0, j) + thouSeparator : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + thouSeparator);
		if(decPlaces) dec = Math.abs(n - i).toFixed(decPlaces).slice(2);
		if(dec) out += decSeparator + dec;
		return out;
	};

	function formatCurrency(amount){
		$.ajax({
			url: '<?php echo admin_url('admin-ajax.php'); ?>', 
			data: {
				action: 'rezgo',
				method: 'gift_card_ajax',
				rezgoAction:'formatCurrency',
				amount: amount,
			},
			type: 'POST',
			success: function (result) {
				// console.log(result);
				$('#gc_total_due').html('of ' + result);
			}
		});
	}

	// fill in default value of the first selected amount
	formatCurrency(<?php echo esc_html($order_total); ?>);

	paypalCheckoutRender = function() {

		paypal.Buttons({
			style: {
				label: 'checkout'
			},
			fundingSource: paypal.FUNDING.PAYPAL,
			createOrder: function() { return paypal_checkout; },
			onApprove: function(data, actions) {

				$('#payment_id').val(data.orderID);

				// used to flag this account as "added" for checkout
				// if we use payment_id, it might get caught from a stripe card input
				$('#paypal_checkout_id').val(1);
				
				return actions.order.get().then(function(details) {

					$('#paypal-button-header').hide();
					$('#paypal-button-container').hide()
					
					setTimeout(function() {
						$('#paypal-button-header').html('<span style="color:#003087;">Paying with <img src="<?php echo $site->path; ?>/img/logos/paypal.png" style="height:25px; width:auto; position:relative; top:-5px; left:5px;"></span>').fadeIn();
						$('#paypal-button-container')
							.html('<label>' + details.payer.name.given_name + ' ' + details.payer.name.surname + '<br>' + details.payer.email_address + '</label><br><br><p>Your PayPal account will be charged when you complete your purchase.</p>')
							.css('max-width', '100%').delay(500).fadeIn();	
					}, 500);
					
					let country = details.purchase_units[0].shipping.address.country_code;
					country = country.toLowerCase();
					
					if(details.payer.name.given_name) $('#billing_first_name').val(details.payer.name.given_name);
					if(details.payer.name.surname) $('#billing_last_name').val(details.payer.name.surname);
					
					if(details.purchase_units[0].shipping.address.address_line_1) $('#billing_address_1').val(details.purchase_units[0].shipping.address.address_line_1);
					if(details.purchase_units[0].shipping.address.address_line_2) $('#billing_address_2').val(details.purchase_units[0].shipping.address.address_line_2);
					
					if(details.purchase_units[0].shipping.address.admin_area_2) $('#billing_city').val(details.purchase_units[0].shipping.address.admin_area_2);
					if(details.purchase_units[0].shipping.address.postal_code) $('#billing_postal_code').val(details.purchase_units[0].shipping.address.postal_code);
					
					if(details.purchase_units[0].shipping.address.admin_area_1) $('#billing_stateprov').val(details.purchase_units[0].shipping.address.admin_area_1).trigger('change');
					if(country) $('#billing_country').val(country);
						
					$('#paypal_error').hide();

					validate_form();
						
				});
				
			}
		}).render('#paypal-button-container');
			
	}

	function validate_form() {
		return $('#purchase').valid();
	}

	$('.rezgo-payment-method').click(function() {
		toggleCard();
	});

	toggleCard = function() {

		let animateSpeed = 250;
			
		// reset stripe_error to allow form to be submitted
		stripe_error = 0; 
		square_error = 0;
			
		$('#payment_cards').hide();
			
		$('.payment_method_box').hide();
		$('.payment_method_field').attr('disabled', 'disabled');

		$(this).addClass('selected');
			
		if($('input[name=payment_method]:checked').val() == 'Credit Cards') {

			<?php if ($gateway_id == 'stripe_connect') { ?>
				// re enable stripe_error
				stripe_error = 1;
			<?php }else if ($gateway_id == 'square') { ?>
				square_error = 1;
			<?php } ?>

			$('#payment_cards').show();
			
		} else if($('input[name=payment_method]:checked').val() == 'PayPal Checkout') {

			let id = $('input[name=payment_method]:checked').attr('id');

			// console.log('#' + id + '_box');
			
			$('#' + id + '_box').fadeIn(animateSpeed);
			$('#' + id + '_field').attr('disabled', false);
				
		} else {

			let id = $('input[name=payment_method]:checked').attr('id');

			$('#' + id + '_box').fadeIn(animateSpeed);
			$('#' + id + '_field').attr('disabled', false);
		}
	}

	let payment_count = $("input[name='payment_method']").length;
	if ( payment_count === 1 ){
		$("input[name='payment_method']").eq(0).prop("checked", true);
		toggleCard();
	} 

});

</script>