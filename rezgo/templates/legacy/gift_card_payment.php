<?php
	session_start();
	// send to step 1 if user does not meet requirements
    if ( $_SESSION['gift-card']['rezgoAction'] != 'firstStepGiftCard' || !isset($_COOKIE['rezgo_gift_card_'.REZGO_CID]) ){
        $site->sendTo($site->base.'/gift-card');
    } else {
		// unsetting this because we want to send the user back if they did not come from step 1
		unset($_SESSION['gift-card']['rezgoAction']);
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

    $r = $_SESSION['gift-card'];
    $order_total = ($r['billing_amount'] == 'custom' ) ? $r['custom_billing_amount'] : $r['billing_amount'];

    if ($r['billing_amount'] == 'custom') {
        $_SESSION['gift-card']['billing_amount'] = 'custom';
    } else {
        unset($_SESSION['gift-card']['custom_billing_amount']);
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

				<?php if ($r['option']) $option = $site->getTours('t=uid&q='.$r['option']); ?>

					<form id="purchase" class="gift-card-purchase" role="form" method="post" target="rezgo_content_frame">

						<input type="hidden" name="billing_amount">

							<?php if ($r['option']) { ?>
								<input type="hidden" name="option" value ="<?php echo $r['option']; ?>">
							<?php } ?>

							<?php foreach ($r as $k => $v) {
									if (in_array($k, PAX_ARRAY)) {
										if ((int)$v > 0) {  ?>
										<input type="hidden" name="<?php echo $k; ?>" value ="<?php echo $v; ?>">
									<?php } ?>
								<?php } ?>
							<?php } ?>

						<div class="rezgo-gift-card-container step-two row">

							<i class="far fa-gift-card gift-icon"></i>

							<div class="col-lg-8 col-md-12 rezgo-gift-card-left-wrp">


							<!-- <div class="col-xs-12"> -->
								<div class="rezgo-gift-card-head">
									<h3 class="gc-page-header space-below"><span>Gift Card Recipient</span></h3>
								</div>

								<div class="row">
									<div class="col-xs-12 col-sm-6">
										<div class="form-group">
											<label for="recipient_name" class="control-label">
												<span>Name</span>
											</label>

											<input class="form-control required" name="recipient_name" type="text" placeholder="Full Name" value="">
										</div>
									</div>

									<div class="col-xs-12 col-sm-6">
										<div class="form-group">
											<label for="recipient_email" class="control-label">
												<span>Email Address</span>
											</label>

											<input class="form-control required" name="recipient_email" type="email" placeholder="Email Address" value="">
										</div>
									</div>

								</div>

								<div class="row">
									<div class="col-xs-12">
										<div class="form-group">
											<label for="recipient_message" class="control-label">Your Message (optional)</label>

											<textarea class="form-control gc-recipient-message" name="recipient_message" rows="5" style="resize:none" placeholder="Your Message"></textarea>
										</div>
									</div>
								</div>

								<div id="rezgo-gift-card-memo-sendto"><span></span></div>
							<!-- </div> -->

							<div class="clearfix">

								<input type="hidden" name="gift_card_token" id="gift_card_token" value="" />
								<input type="hidden" name="payment_id" id="payment_id" value=""/>

								<script>
									jQuery('#gift_card_token').val("");
										// create stripe initial error state because we use this to validate the form
									var stripe_error = 0;
								</script>

									<div class="rezgo-payment-frame gift-card-payment row" id="payment_info">

										<div class="rezgo-gift-card-head">
											<h3 class="gc-page-header space-below">Payment Method</h3>
										</div>

										<!-- <div class="col-xs-12"> -->
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
																<input type="radio" name="payment_method" id="payment_method_credit" class="rezgo-payment-method required" value="Credit Cards" onclick="toggleCard();">
			
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
																<input type="radio" name="payment_method" id="payment_method_<?php echo esc_attr($pmc); ?>" class="rezgo-payment-method required" value="<?php echo $pay['name']; ?>" onclick="toggleCard();">
																<label class="non-cc-method" for="payment_method_<?php echo esc_attr($pmc); ?>"><img src="<?php echo $site->path; ?>/img/logos/paypal.png" style="height:25px; width:auto;"></label>
															</div>
															
															<?php
														}
														
														$pmc++;
													} // end foreach($site->getPaymentMethods()
												?>

											</div>
										<!-- </div>// #payment_methods -->

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

																	#rezgo-credit-card-container{
																		padding: 5px 0px 10px 10px;
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
																		color: #a94442;
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
																		#rezgo-credit-card-container{
																			padding-left: 0;
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

																		let stripe_trace = Date.now();
																									
																		var clientSecret = '';
																		var paymentId = '';

																	function createPaymentIntent(){
																		jQuery.ajax({
																			// create Payment Intent
																			url: "<?php echo admin_url('admin-ajax.php'); ?>" + '?action=rezgo',
																			context: document.body,
																			dataType:"json",
																			data: { 
																					rezgoAction: 'stripe_create',
																					method: 'gateways_stripe',
																					amount: order_total,
																					currency: decodeURIComponent( '<?php echo rawurlencode( (string) $currency_base ); ?>' ),
																			}, 
																			success: function(data){
																				// if payment intent was created, place client secret
																				clientSecret = data.client_secret;
																				paymentId = data.payment_id;

																				if(clientSecret) {
																					jQuery('#client_secret').val(clientSecret);
																					jQuery('#payment_id').val(paymentId);
																				}
																			}
																		})
																	}

																	// create payment intent based on initial value selected
																	createPaymentIntent();

																	// Create a Stripe client.
																	let stripe = Stripe(decodeURIComponent( '<?php echo rawurlencode( (string) REZGO_STRIPE_PUBLIC_KEY ); ?>' ), {stripeAccount: decodeURIComponent( '<?php echo rawurlencode( (string) $company->public_gateway_token ); ?>' )});

																	// Create an instance of Elements.
																	var elements = stripe.elements();
																	
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
																	
																	// Create an instance of the card Element.
																	var card = elements.create('card', {
																					style: style,
																					hidePostalCode: true,
																				});

																	var displayError = document.getElementById('card-errors');

																	// Add an instance of the card Element into the #card-element div.
																	card.mount('#card-element');
																	
																	window.addEventListener('resize', resizeStripe);
																	function resizeStripe(){
																		if (window.innerWidth <= 500) {
																			card.update({
																				style: {base: {fontSize: '13px'}},
																				hideIcon: true,
																			});
																		} else {
																			card.update({
																				style: {base: {fontSize: '14px'}},
																				hideIcon: false,
																			});
																		}
																	}

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

															<?php } elseif ($gateway_id == 'tmt') { ?>

																<script src="https://payment.tmtprotects.com/tmt-payment-modal.3.6.1.js"></script>
																<script>
																	let tmt_data;
																	// let overall_total;
																	
																	function set_tmt_modal(amount) {

																		jQuery.ajax('<?php echo admin_url('admin-ajax.php'); ?>' + '?action=rezgo&method=gateways_tmt&amount='+decodeURIComponent( '<?php echo rawurlencode( (string) $order_total ); ?>' )).
																			done(function(result) {
																			tmt_data = JSON.parse(result);
																			console.log('TMT DATA');
																			console.log(tmt_data);
																		});
																	}

																	set_tmt_modal();

																</script>
																
															<?php } else { ?>

																<iframe scrolling="no" frameborder="0" name="gift_payment" id="rezgo-gift-payment" src="<?php echo home_url(); ?>?rezgo=1&mode=booking_payment"></iframe>

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

									<!-- <div class="col-xs-12"> -->
										<div class="form-group">
											<label for="billing_first_name" class="control-label">
												<span>Name</span>
											</label>
											<div class="row" style="margin-right:15px;">
												<div class="col-xs-12 col-sm-6" style="padding-right:0;">
													<input class="form-control required" name="billing_first_name" id="billing_first_name" type="text" placeholder="First Name" />
												</div>

												<div class="col-xs-12 col-sm-6" style="padding-right:0;">
													<input class="form-control required" name="billing_last_name" id="billing_last_name" type="text" placeholder="Last Name" />
												</div>
											</div>
										</div>

										<div class="form-group">
											<label for="billing_address_1" class="control-label">
												<span>Address</span>
											</label>
											<div class="rezgo-form-input col-xs-12">
												<input class="form-control required" name="billing_address_1" id="billing_address_1" type="text" placeholder="Address 1" />
											</div>
											<div class="rezgo-form-input col-xs-12">
												<input class="form-control" name="billing_address_2" id="billing_address_2" type="text" placeholder="Address 2 (optional)" />
											</div>
										</div>

										<label for="rezgo_confirm_id" id="rezgo_confirm_label">
											<span>Confirm ID</span>
										</label>

										<input name="rezgo_confirm_id" id="rezgo_confirm_id" type="text" value="" tabindex="-1" autocomplete="off">

										<div class="form-group">
											<div class="rezgo-form-row">
												<div class="col-xs-12 rezgo-form-input">
													<label for="billing_city" class="control-label">
														<span>City</span>
													</label>

													<input class="form-control required" id="billing_city" name="billing_city" type="text" placeholder="City" />
												</div>
											</div>
										</div>
										
										<div class="form-group">
											<div class="rezgo-form-row">
												<div class="col-xs-12 rezgo-form-input">
													<label for="billing_postal_code" class="control-label">
														<span>Zip/Postal</span>
													</label>

													<input class="form-control required" name="billing_postal_code" type="text" placeholder="Zip/Postal Code" id="billing_postal_code" />
												</div>
											</div>
										</div>

										<div class="form-group">
											<div class="rezgo-form-row">
												<div class="col-xs-12 rezgo-form-input">
													<label for="billing_country" class="control-label">
														<span>Country</span>
													</label>

													<select id="billing_country" name="billing_country" class="form-control">
														<?php foreach ($site->getRegionList() as $iso => $name) { ?>
															<option value="<?php echo $iso; ?>" <?php echo (($iso == $companyCountry) ? 'selected' : ''); ?> ><?php echo ucwords($name); ?></option>
														<?php } ?>
													</select>
												</div>
											</div>
										</div>

										<div class="form-group">
											<div class="rezgo-form-row">
												<div class="col-xs-12 rezgo-form-input">
														<div class="rezgo-form-row">
														<label for="billing_stateprov" class="control-label">
															<span>State/Prov</span>
														</label>

														<select id="billing_stateprov" class="form-control" style="<?php echo (($companyCountry != 'ca' && $companyCountry != 'us' && $companyCountry != 'au') ? 'display:none' : ''); ?>" ></select>

														<input id="billing_stateprov_txt" class="form-control" name="billing_stateprov" type="text" value="" placeholder="State/Province" style="<?php echo (($companyCountry != 'ca' && $companyCountry != 'us' && $companyCountry != 'au') ? '' : 'display:none'); ?>" />
													</div>
												</div>
											</div>
										</div>

										<div class="form-group">
											<div class="rezgo-form-row">
												<div class="col-xs-12 rezgo-form-input">
													<label for="billing_email" class="control-label">
														<span>Email</span>
													</label>

													<input class="form-control required" name="billing_email" id="billing_email" type="email" placeholder="Email Address" />
												</div>
											</div>
										</div>

										<div class="form-group">
											<div class="rezgo-form-row">
												<div class="col-xs-12 rezgo-form-input">
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
											jQuery('#validate_language').val(navigator.language);
											jQuery('#validate_height').val(window.innerHeight);
											jQuery('#validate_width').val(window.innerWidth);
											jQuery('#validate_tz').val(new Date().getTimezoneOffset());
											jQuery('#validate_agent').val(navigator.userAgent);
											jQuery('#validate_callback').val(window.location.protocol + '//' + window.location.hostname + '/3DS');
										</script>

										<div id='rezgo-credit-card-success' style='display:none;'></div>
										
										<div id="rezgo-payment-wrp">
											<div class="row">
												<div class="col-xs-12">
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

								<!-- </div> -->
							</div>

							<div id="rezgo-gift-message" class="row" style="display:none;">
								<div id="rezgo-gift-message-body" class="col-sm-8 col-sm-offset-2"></div>
								<div id="rezgo-gift-message-wait" class="col-sm-2"><i class="far fa-sync fa-spin fa-3x fa-fw"></i></div>
							</div>

							<hr>

							<div id="rezgo-gift-errors" style="display:none;">
								<div>Some required fields are missing or incorrect. Please review the highlighted fields.</div>
							</div>

							<div class="rezgo-gift-card-cta">
								<button type="submit" class="btn rezgo-btn-book btn-lg btn-block" id="purchase-submit">
									<span>
										Complete Purchase <span id="gc_total_due"></span>
									</span>
								</button>

								<?php // create backlink to preserve pax changes on back
									$pax = '';
									$pax_count = 0;
									if ($r['buy_as_gift']){ 
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
									}
								?>

								<a class="underline-link" onclick="top.location.href= '<?php echo esc_js($site->base); ?>/gift-card<?php echo $giftlink.$pax; ?>';">Previous Step</a>
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

<div class="modal fade bd-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="scaModal" aria-hidden="true" id="sca_modal" style="bottom:20px !important; top:auto !important;">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" style="position:relative; top:3px; float:left;">Card Validation</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="width:50px; text-decoration:none;">
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

<?php if ($site->exists(REZGO_CAPTCHA_PUB_KEY)) { ?>
	<script src="https://www.google.com/recaptcha/api.js?render=<?php echo esc_html(REZGO_CAPTCHA_PUB_KEY); ?>"></script>
<?php } ?>

<?php if (!$site->isVendor() && $site->getGateway()) { ?>
	<script>

	jQuery(document).ready(function($){	
	
		/* FORM (#purchase) */

		// STATES VAR
		let ca_states = JSON.parse( decodeURIComponent( '<?php echo rawurlencode( wp_json_encode($site->getRegionList('ca')) ); ?>' ) );
		let us_states = JSON.parse( decodeURIComponent( '<?php echo rawurlencode( wp_json_encode($site->getRegionList('us')) ); ?>' ) );
		let au_states = JSON.parse( decodeURIComponent( '<?php echo rawurlencode( wp_json_encode($site->getRegionList('au')) ); ?>' ) );

		// FORM ELEM
		var $purchaseForm = $('#purchase');
		var $purchaseBtn = $('#purchase-submit');
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

		// FORM VALIDATE
		$purchaseForm.validate({
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

			if(pass) {
				passthrough = pass;
				//console.log("SETTING PASSTHROUGH DATA: " + passthrough);
			}

			if(mode == 'iframe') {

				$('#sca_modal').modal();

				let content = data ? JSON.parse(data) : null;

				let form = '<form action="' + url + '" method="post" target="sca_modal_frame" id="sca_post">';

				if(content) {
					$.each(content, function(index, value) {
						form += '<input type="hidden" name="' + index + '" value="' + value + '">';
					});
				}

				form += '</form>';

				$('body').append(form);

				$('#sca_post').submit().remove();

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
			$("#rezgo-gift-message-body").addClass('col-sm-8 col-sm-offset-2');
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
							top.location.replace('<?php echo esc_html($site->base); ?>/gift-receipt/'+card);
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
								// an SCA challenge is required for this transaction
								sca_window('iframe', json.url, json.post, json.pass);
							} else {
								body = 'Sorry, an unknown error has occurred. If this keeps happening, please contact <?php echo esc_html(addslashes($company->company_name)); ?>.';
							}

							$('#rezgo-credit-card-success').hide().empty();
							$formMsgBody.addClass('alert alert-danger').html(body);
							$formMessage.show();
							$purchaseBtn.removeAttr('disabled');

							payment_wait(false);
							$('#rezgo-gift-message-body').html(body);
							$('#rezgo-gift-message-body').addClass('alert alert-warning');
						}
					}, 
					error: function() {
						var body = 'Sorry, the system has suffered an error that it can not recover from.<br />Please try again later.<br />';
						$formMsgBody.addClass('alert alert-danger').html(body);
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
				$purchaseBtn.attr('disabled','disabled');
				$formMessage.hide();

				// catch empty fields on stripe
				if (cardInput.classList.contains('StripeElement--empty')){
					cardInput.style.borderColor = '#a94442';		
					displayError.textContent = 'Please enter your Credit Card details';
					stripe_error = 1;
				}
				if( cardHolder.val() == '' ){
					$('#stripe_cardholder_name').css({'borderColor':'#a94442'});
					$('#stripe_cardholder_name_error').show();
					stripe_error = 1;
				}
			
			<?php } elseif ($gateway_id == 'tmt') { ?>
					
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
				if (!validationCheck || stripe_error || force_error) {
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
						$("#rezgo-gift-message-body").addClass('col-sm-8 col-sm-offset-2');
						$('#rezgo-gift-message-body').html('Please wait one moment ...');
						payment_wait(true);
						$('#rezgo-gift-message').fadeIn();
					
						if(stripe_error != 1) {

							// pass postal code from booking form to stripe 
							var postal_code = $('#billing_postal_code').val();
							card.update({ 
								value: { postalCode: postal_code } 
							});

							let cardholder_name = $('#stripe_cardholder_name').val();

							stripe.confirmCardPayment(clientSecret,
								{
									payment_method: {
										card: card,
										billing_details: {
											name: cardholder_name,
										}
									},
									return_url: <?php echo LOCATION_WINDOW; ?>.origin + '/3ds_return_url.php'
								},
								// Disable the default next action handling because we want to use an iframe
								{handleActions: false}
							).then(function (result) {
								// console.log(result.paymentIntent);
								if (result.error) {
									// Show error to your customer and disable form
									var displayError = document.getElementById('card-errors');
									displayError.textContent = result.error.message;

									$purchaseBtn.removeAttr('disabled');
									payment_wait(false);
									$("#rezgo-gift-message-body").html(result.error.message);
									$("#rezgo-gift-message-body").addClass('alert alert-warning');

								} else {

									// check if there is a next_action
									var activate_3DS = result.paymentIntent.next_action !== null;

									// trigger 3Dsecure flow if exists
									if (activate_3DS) {
										// console.log(result.paymentIntent.next_action.redirect_to_url.url);

										iframe = document.createElement('iframe');
										iframe.setAttribute("id", "secureFrame");
										iframe.src = result.paymentIntent.next_action.redirect_to_url.url;
										iframe.width = 500;
										iframe.height = 600;
                                    	iframe.setAttribute("style", "position:absolute; z-index: 99; bottom:0; margin:auto; left:0; right:0; border:0;");

										bg = document.createElement('div');
										bg.setAttribute("style", "position:fixed; width: 100vw; height:100%; z-index: 98; left:0; bottom:0; background:rgba(0,0,0,0.70);");

										document.getElementById('rezgo-payment-wrp').appendChild(bg);

										setTimeout(() => {
											document.getElementById('rezgo-payment-wrp').appendChild(iframe);
										}, 250);

									
										var stripe_processing = 0;
										
										function checkStripeIntent() {
											
											if(stripe_processing == 1) return;
											
											stripe.retrievePaymentIntent(clientSecret).then(function(result) {
												
												//console.log('Intent status: ' + result.paymentIntent.status);
												
												if(result.paymentIntent.status === 'requires_source_action' || result.paymentIntent.status === 'processing') {
													// still waiting on 3DS status
													// set a timeout to check again
													// a result of 'processing' means a recent change was made, so we still want to wait a moment
													
													//console.log('3DS validation running...');
													
													setTimeout(function () {
														checkStripeIntent();
													}, 600);
													
												} else {
													
													stripe_processing = 1;
													
													fetch('<?php echo esc_html($site->base); ?>/log?type=stripe&action=' + encodeURIComponent('3DS validation completed. Status response: ' + result.paymentIntent.status) + '&source=' + stripe_trace);
													
													//console.log('3DS validation completed...');
													
													// Hide the 3DS UI
													iframe.remove();
		
													setTimeout(() => {
														bg.remove();
													}, 250);
													
													if(result.paymentIntent.status === 'requires_capture') {
														
														// Show your customer that the payment has succeeded, requires capture in BE

														$('#gift_card_token').val(result.paymentIntent.id);

														var displayError = document.getElementById('card-errors');
														displayError.textContent = '';

														check_card_token();

													} else {
														
														// Show error to your customer and disable form
														var displayError = document.getElementById('card-errors');
														displayError.textContent = 'Authentication failed, Please try again';

														$purchaseBtn.removeAttr('disabled');
														payment_wait(false);

														$("#rezgo-gift-message-body").html('Authentication failed, Please try again');
													}
												
												}
												
											}).catch(function(error) {
												
												fetch('<?php echo esc_html($site->base); ?>/log?type=stripe&action=' + encodeURIComponent('3DS promise exception') + '&long=' + encodeURIComponent(JSON.stringify(error)) + '&source=' + stripe_trace);
												
												// try the intent again with the normal loop
												setTimeout(function () {
													checkStripeIntent();
												}, 600);
												
											});
											
										}
										
										setTimeout(function () {
											checkStripeIntent();
										}, 300);
										

									} else if (!activate_3DS && result.paymentIntent.status === 'requires_capture') {

										// Show your customer that the payment has succeeded
										var displayError = document.getElementById('card-errors');
										displayError.textContent = '';

										$('#gift_card_token').val(result.paymentIntent.id);

										check_card_token();

									}
								}
							}); //-- confirmCardPayment
						}
					
					<?php } elseif ($gateway_id == 'tmt') { ?>

						$('#rezgo-complete-payment').removeAttr('disabled');

						const tmtPaymentModal = new parent.tmtPaymentModalSdk({
							path: tmt_data.path,
							environment: tmt_data.account_mode,
							transactionType: 'authorize',
							data: {
								// Booking Data
								booking_id: "0",
								channels: tmt_data.channel,
								date: "<?php echo date("Y-m-d", strtotime('+30 days'))?>",
								currencies:  decodeURIComponent( '<?php echo rawurlencode( (string) $currency_base ); ?>' ),
								total: (order_total * 100),
								description: "Rezgo Purchase",
								// Authentication
								booking_auth: tmt_data.auth_string,
								// Lead Traveller
								firstname: $('#billing_first_name').val() ? $('#billing_first_name').val() : 'First',
								surname: $('#billing_last_name').val() ? $('#billing_last_name').val() : 'Last',
								email: $('#billing_email').val() ? $('#billing_email').val() : 'email@address.com',
								country: $('#billing_country').val() ? $('#billing_country').val().toUpperCase() : 'CA',
								// Payment details
								payee_name: $('#billing_first_name').val() + ' ' + $('#billing_last_name').val(),
								payee_email: $('#billing_email').val() ? $('#billing_email').val() : 'email@address.com',
								payee_address: $('#billing_address_1').val() ? $('#billing_address_1').val() : 'Address',
								payee_city: $('#billing_city').val() ? $('#billing_city').val() : 'City',
								payee_country: $('#billing_country').val().toUpperCase(),
								payee_postcode: $('#billing_postal_code').val() ? $('#billing_postal_code').val() :'0000'
							}
						});
		
						console.log('REGISTERED TMT CALLBACKS');
		
						let lock = 0;
		
						// successful transaction
						tmtPaymentModal.on("transaction_logged", function (data) {
							console.log("TRANSACTION LOGGED - ", data);
		
							if(lock == 1) return;
							lock = 1;

							$purchaseBtn.attr('disabled','disabled');

							$("#rezgo-gift-message-body").removeClass();
							$("#rezgo-gift-message-body").addClass('col-sm-8 col-sm-offset-2');
							$('#rezgo-gift-message-body').html('Please wait one moment ...');
							payment_wait(true);
							$('#rezgo-gift-message').fadeIn();
		
							tmtPaymentModal.closeModal();
		
							$('#gift_card_token').val(data.id);
							$('#payment_id').val(1); // tmt doesn't need this value, but it is needed to trigger the validate API
							
							check_card_token();
		
						});
		
						tmtPaymentModal.on("transaction_failed", function (data) {
		
							if(lock == 1) return;
							lock = 1;
		
							console.log("TRANSACTION FAILED - ", data);
		
							tmtPaymentModal.closeModal();

							payment_wait(true);
							
							let body = 'Sorry, your payment could not be completed. Please verify your card details and try again.';
							
							$('#rezgo-credit-card-success').hide().empty();
							$formMsgBody.addClass('alert alert-danger').html(body);
							$formMessage.show();
							$purchaseBtn.removeAttr('disabled');

							payment_wait(false);
							$('#rezgo-gift-message-body').html(body);
							$('#rezgo-gift-message-body').addClass('alert alert-warning');
							
						});

					<?php } else { ?>

						$("#rezgo-gift-message-body").removeClass();
						$("#rezgo-gift-message-body").addClass('col-sm-8 col-sm-offset-2');
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
					$("#rezgo-gift-message-body").addClass('col-sm-8 col-sm-offset-2');
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
								top.location.replace('<?php echo esc_html($site->base); ?>/gift-receipt/'+card);
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
									// an SCA challenge is required for this transaction
									sca_window('iframe', json.url, json.post, json.pass);
								} else {
									body = 'Sorry, an unknown error has occurred. If this keeps happening, please contact <?php echo addslashes($company->company_name); ?>.';
								}

								$('#rezgo-credit-card-success').hide().empty();
								$formMsgBody.addClass('alert alert-danger').html(body);
								$formMessage.show();
								$purchaseBtn.removeAttr('disabled');

								payment_wait(false);
								$('#rezgo-gift-message-body').html(body);
								$('#rezgo-gift-message-body').addClass('alert alert-warning');
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

		// Gift card wait time
		var seconds = 0;

		function payment_wait (wait) { 
		
			if (wait) {
				
				$('#rezgo-gift-message-wait').show();
		
				timex = setTimeout(function(){
				seconds++;
			
				if (seconds == 10) {
				
					$("#rezgo-gift-message-body").fadeOut(function() {
						$(this).html('We are still working on your request. <br class="hidden-md hidden-lg" />Thank you for your patience.').fadeIn();
					});																
				
				} else if (seconds == 25) {
				
					$("#rezgo-gift-message-body").fadeOut(function() {
						$(this).html('Your request is taking longer than expected. <br class="hidden-md hidden-lg" />Please hold on ...').fadeIn();
					});	
				
				} else if (seconds == 40) {
				
					$("#rezgo-gift-message-body").fadeOut(function() {
						$(this).html('Working on payment processing. <br class="hidden-md hidden-lg" />Your order should be completed soon.').fadeIn();
					});	
				
				} else if (seconds == 55) {
				
					$("#rezgo-gift-message-body").fadeOut(function() {
						$(this).html('So &hellip; do you have any plans for the weekend?').fadeIn();
					});	
					
				} else if (seconds == 70) {
				
					$("#rezgo-gift-message-body").fadeOut(function() {
						$(this).html('We really had hoped to be done by now. <br class="hidden-md hidden-lg" />It shouldn\'t take much longer.').fadeIn();
					});	
				
				}
			
				// console.log(seconds);
				payment_wait(true);
				
			}, 1000);
			
			} else {
			
			clearTimeout(timex);
			$('#rezgo-gift-message-body').html('');
			$('#rezgo-gift-message-wait').hide();
			
			}
			
		}

		
		function showSuccessIcon(parent) {
			parent.append('<div class="icon icon--order-success svg"><svg xmlns="http://www.w3.org/2000/svg" width="72px" height="72px"><g fill="none" stroke="#8EC343" stroke-width="2"><circle cx="36" cy="36" r="35" style="stroke-dasharray:240px, 240px; stroke-dashoffset: 480px;"></circle><path d="M17.417,37.778l9.93,9.909l25.444-25.393" style="stroke-dasharray:50px, 50px; stroke-dashoffset: 0px;"></path></g></svg></div>').show();
		}
		$purchaseForm.submit(function(e) {
			e.preventDefault();
			submit_payment();
		});

	});

	</script>
<?php } ?>

<script>	

jQuery(document).ready(function($){	

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

	toggleCard = function() {

		let animateSpeed = 250;
			
		// reset stripe_error to allow form to be submitted
		stripe_error = 0;
			
		$('#payment_cards').hide();
			
		$('.payment_method_box').hide();
		$('.payment_method_field').attr('disabled', 'disabled');

		$(this).addClass('selected');
			
		if($('input[name=payment_method]:checked').val() == 'Credit Cards') {

			<?php if ($gateway_id == 'stripe_connect') { ?>
				// re enable stripe_error
				stripe_error = 1;
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