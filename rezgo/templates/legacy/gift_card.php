<?php 
session_start();
$company = $site->getCompanyDetails();
$companyCountry = $site->getCompanyCountry();
$companyCurrency = $company->currency_symbol;
$site->readItem($company);
$site->setCookie('rezgo_gift_card_'.REZGO_CID, REZGO_CID);

unset($_SESSION['gift-card']['buy_as_gift']);

$buy_as_gift = $_REQUEST['option'] && $_REQUEST['date'] ? 1 : 0;

if ($buy_as_gift) {

	if ($_SESSION['gift-card']) {
		foreach ($_SESSION['gift-card'] as $k => $v) {
			if (in_array($k, PAX_ARRAY)) {
				if ((int)$v > 0) {  
					if (strpos($k, '_num')) {
						$k = str_replace('_num','', $k);
					} 
					$pax[$k] = $v;
				}
			}
		}
	}


	// reset pax selections if option ID is different or starting from details page
	if ($_SESSION['gift-card']['option'] != $_REQUEST['option'] ){
		unset($pax);
	}
}
	$show_gc = $site->showGiftCardPurchase();
?>

<script>
	var debug = <?php echo DEBUG; ?>;
	const currency = "<?php echo esc_html($companyCurrency); ?>";
</script> 

<div class="rezgo-container rezgo-gift-card-outer-container">
	<div class="row">
		<div class="rezgo-gift-card-inner-col">

			<?php if (!$buy_as_gift) { ?>
				<div id="rezgo-gift-card-search" class="clearfix">
					<div class="search-section rezgo-gift-card-group clearfix">
						<h3 class="gc-page-header"><span class="">Check Gift Card Balance</span></h3>
						<form id="search" role="form" method="post" target="rezgo_content_frame">
							<div class="input-group rezgo-gift-input-group">
								<input type="text" class="form-control" id="search-card-number" placeholder="Gift Card Number" />
								<button class="btn btn-primary rezgo-check-balance rezgo-btn-default" type="submit" form="search"><span>Check Balance</span></button>
							</div>
						</form>

						<div class='rezgo-gift-search-response' style='display:none'>
							<span class='msg'></span>
						</div>
						<div id="rezgo-gift-card-memo-check"><span></span></div>
					</div>
					<div id="gift-icon-container">
						<img id="gift-card-img" src="<?php echo $site->path; ?>/img/gift.svg" alt="Search Gift Card">
					</div>
				</div>
			<?php } ?>

			<?php if ($show_gc) { ?>
				<div class="rezgo-gift-card-container clearfix">
					<form id="purchase" class="gift-card-purchase rezgo-order-form" role="form" method="post" target="" action="">
					<?php if (!$buy_as_gift) { ?>
						<div id="gift_amount_selector" class="rezgo-gift-card-group clearfix">
							<div class="rezgo-gift-card-head">
								<h3 class="rezgo-gift-card-heading gc-page-header"><span>Select a Gift Card Value</span></h3>
								<p class="rezgo-gift-card-desc"><span>*All Values are in <?php echo $companyCurrency; ?></span></p>
								<i class="far fa-gift gift-icon"></i>
							</div>

							<div class="row">
								<div class="col-xs-12">
									<div class="form-group">

										<div id="rezgo-gc-choose-container">
											<div class="rezgo-gc-choose-radio">
												<input id="gc_preset_50" checked type="radio" name="billing_amount" class="rezgo-gc-preset-amount" value="50" onclick="toggleAmount();">
												<label for="gc_preset_50" class="payment-label"><?php echo esc_html($site->formatCurrency(50)); ?></label>
											</div>

											<div class="rezgo-gc-choose-radio">
												<input id="gc_preset_100" type="radio" name="billing_amount" class="rezgo-gc-preset-amount" value="100" onclick="toggleAmount();"> 
												<label for="gc_preset_100" class="payment-label"><?php echo esc_html($site->formatCurrency(100)); ?></label>
											</div>

											<div class="rezgo-gc-choose-radio">
												<input id="gc_preset_150" type="radio" name="billing_amount" class="rezgo-gc-preset-amount" value="150" onclick="toggleAmount();">
												<label for="gc_preset_150" class="payment-label"><?php echo esc_html($site->formatCurrency(150)); ?></label>
											</div>

											<div class="rezgo-gc-choose-radio">
												<input id="gc_preset_250" type="radio" name="billing_amount" class="rezgo-gc-preset-amount" value="250" onclick="toggleAmount();">
												<label for="gc_preset_250" class="payment-label"><?php echo esc_html($site->formatCurrency(250)); ?></label>
											</div>

											<div class="rezgo-gc-choose-radio">
												<input id="gc_preset_300" type="radio" name="billing_amount" class="rezgo-gc-preset-amount" value="300" onclick="toggleAmount();">
												<label for="gc_preset_300" class="payment-label"><?php echo esc_html($site->formatCurrency(300)); ?></label>
											</div>

											<div class="rezgo-gc-choose-radio">
												<input id="gc_preset_custom" type="radio" name="billing_amount" class="rezgo-gc-preset-amount" value="custom" onclick="toggleAmount();">
												<label for="gc_preset_custom" class="payment-label">Custom Amount</label>
											</div>
										</div>

										<div class="rezgo-custom-billing-amount-container" style="display:none;">

											<div id="rezgo-custom-billing-amount-wrp">
												<div class="input-wrapper">
													<span id="custom-billing-currency-placeholder"><?php echo esc_html($companyCurrency); ?></span> 
													<input type="number" min="1" name="custom_billing_amount" id="rezgo-custom-billing-amount" class="form-control" placeholder="" oninput="two_decimal(this);">
												</div>
											
											<a id="rezgo-custom-amount-cancel" class="underline-link"><span>Cancel</span></a>
											
											</div>
										</div>
									</div>
								</div>
							</div>

                            <div id="rezgo-gift-card-memo-buy"><span></span></div>
						</div>

					<?php } else { ?> 

						<?php 
							$option = $site->getTours('t=uid&q='.$_REQUEST['option'].'&d='.$_REQUEST['date'].'&file=edit_pax'); 
							if ($option[0])	$prices = $site->getTourPrices($option[0]); 
						?>

						<script>
							let fields = new Array();
							let required_num = 0;
							let total_pax_price;
							let running_pax_price = {
								<?php foreach ($prices as $price) { 
									$original_price = $price->base ? $price->base : $price->price;
									
									if (array_key_exists($price->name, $_REQUEST)){
										echo "'" .esc_html($price->name)."'" .':'.esc_html($_REQUEST[$price->name]*$original_price). ',';
									} else {
										echo "'" .esc_html($price->name)."'" .':'.'0'. ',';
									}
								} ?> 
							};
							// get initial pax numbers
							let pax_obj = {
								'adult_num': <?php echo $pax['adult'] ? esc_html((int)$pax['adult']) : ($_REQUEST['adult'] ? esc_html($_REQUEST['adult']) : 0); ?>,
								'child_num': <?php echo $pax['child'] ? esc_html((int)$pax['child']) : ($_REQUEST['child'] ? esc_html($_REQUEST['child']) : 0); ?>,
								'senior_num': <?php echo $pax['senior'] ? esc_html((int)$pax['senior']) : ($_REQUEST['senior'] ? esc_html($_REQUEST['senior']) : 0); ?>,
								'price4_num': <?php echo $pax['price4'] ? esc_html((int)$pax['price4']) : ($_REQUEST['price4'] ? esc_html($_REQUEST['price4']) : 0); ?>,
								'price5_num': <?php echo $pax['price5'] ? esc_html((int)$pax['price5']) : ($_REQUEST['price5'] ? esc_html($_REQUEST['price5']) : 0); ?>,
								'price6_num': <?php echo $pax['price6'] ? esc_html((int)$pax['price6']) : ($_REQUEST['price6'] ? esc_html($_REQUEST['price6']) : 0); ?>,
								'price7_num': <?php echo $pax['price7'] ? esc_html((int)$pax['price7']) : ($_REQUEST['price7'] ? esc_html($_REQUEST['price7']) : 0); ?>,
								'price8_num': <?php echo $pax['price8'] ? esc_html((int)$pax['price8']) : ($_REQUEST['price8'] ? esc_html($_REQUEST['price8']) : 0); ?>,
								'price9_num': <?php echo $pax['price9'] ? esc_html((int)$pax['price9']) : ($_REQUEST['price9'] ? esc_html($_REQUEST['price9']) : 0); ?>,
							};
							function isInt(n) {
								return n % 1 === 0;
							}
							function getOverallTotal(total){
								jQuery.ajax({
									url: '<?php echo admin_url('admin-ajax.php'); ?>',
									type: 'POST',
									data: { 
											action: 'rezgo',
											method: 'gift_card_ajax',
											rezgoAction: 'getOverallTotal',
											pax_obj: pax_obj,
											total: total,
											option: '<?php echo $_REQUEST['option']; ?>',
											date: '<?php echo $_REQUEST['date']; ?>',
									},
									type: 'POST',
									success: function (result) {
										result = parseFloat(result);
										updateRunningTotal(result);
									}
								});
							}
							function updateRunningTotal(total){
								jQuery('#gc_total_due_step1').html(currency + total.formatMoney());
								jQuery('input[name=billing_amount]').val(total);
							}
						</script>

						<div id="gift_pax_selector" class="rezgo-gift-card-group">

						<?php if ($prices) { ?>
							<div class="rezgo-gift-card-head">
								<h3 class="rezgo-gift-card-heading gc-page-header"><span>Select your Guests</span></h3>
								<i class="far fa-user-circle gift-icon"></i>
							</div>

							<div class="row">
								<div class="col-xs-12">
									<div class="gift-pax-selector-container">

								<?php foreach($prices as $price) { ?>

									<?php 
										$original_price = $price->base ? $price->base : $price->price;

										if (array_key_exists($price->name, $_REQUEST)){
											$total_pax_price += ($_REQUEST[$price->name] ? $_REQUEST[$price->name] : $pax[$price->name]) * (float) $original_price;
										}
									?>

									<script>fields['<?php echo esc_html($price->name); ?>'] = <?php echo (($price->required) ? 1 : 0); ?>;</script>
										<div class="gift-edit-pax-wrp">
											<div class="edit-pax-label-container">
												<label for="<?php echo esc_html($price->name); ?>" class="control-label rezgo-pax-label rezgo-label-padding-left">
													<span><?php echo esc_html($price->label); ?></span>
												</label>
											</div>

											<div class="pax-price-container">
												<div class="form-group row pax-input-row">
													<div class="edit-pax-container">
														<div class="minus-pax-container">
															<a id="decrease_<?php echo esc_html($price->name); ?>" class="<?php echo ((int)$pax[$price->name] == 0 && (int)$_REQUEST[$price->name] == 0) ? 'not-allowed' : '';?>" onclick="decreasePax_<?php echo esc_html($price->name); ?>()">
																<i class="fa fa-minus"></i>
															</a>
														</div>
														<div class="input-container">
															<input type="number" name="<?php echo esc_html($price->name); ?>_num" value="<?php echo $pax[$price->name] ? $pax[$price->name] : $_REQUEST[$price->name]; ?>" id="<?php echo esc_html($price->name); ?>" size="3" class="pax-input" min="0" placeholder="0" autocomplete="off">
														</div>
														<div class="add-pax-container">
															<a onclick="increasePax_<?php echo esc_html($price->name); ?>()">
																<i class="fa fa-plus"></i>
															</a>
														</div>	
													</div>
												</div>

												<div>
													<div class="edit-pax-label-container">
														<label for="<?php echo esc_html($price->name); ?>" class="control-label rezgo-label-padding-left">
															<span class="rezgo-pax-price">
																<?php 
																	echo $site->formatCurrency($original_price); 
																?>
															</span>
														</label>
													</div>
												</div>

											</div><!-- // pax-price-container -->
										</div><!-- // order-edit-pax-wrp -->

										<script>

											jQuery('#<?php echo esc_html($price->name); ?>').change(function(){
												<?php echo esc_html($price->name); ?>_num = jQuery(this).val();
												if (jQuery(this).val() <= 0) {
													jQuery('#decrease_<?php echo esc_html($price->name); ?>').addClass('not-allowed');
												} else {
													jQuery('#decrease_<?php echo esc_html($price->name); ?>').removeClass('not-allowed');
												}

												// disable unwanted inputs
												if (jQuery(this).val() < 0){
													jQuery(this).val(0);
													running_pax_price.<?php echo esc_html($price->name); ?> = 0;

												} else if(!isInt(jQuery(this).val()) || jQuery(this).val() === 0) {
													jQuery(this).val(0);
													running_pax_price.<?php echo esc_html($price->name); ?> = 0;
												} else {
													running_pax_price.<?php echo esc_html($price->name); ?> = (jQuery(this).val() * <?php echo esc_html($original_price); ?>);
												}

												let pax = '<?php echo strtolower(esc_html($price->name)); ?>'+'_num';

												// populate pax object
												pax_obj[pax] = <?php echo esc_html($price->name); ?>_num;

												total_pax_price = 0;
												for (const [pax, amount] of Object.entries(running_pax_price)) {
													if (amount > 0) {
														console.log(amount);
														total_pax_price += amount;
													}
												}
												getOverallTotal(total_pax_price);
											});


											if (jQuery('#<?php echo esc_html($price->name); ?>_<?php echo esc_html($order_id); ?>').val() > 0){
												jQuery('#decrease_<?php echo esc_html($price->name); ?>_<?php echo esc_html($order_id); ?>').removeClass('not-allowed');
											}

											function increasePax_<?php echo esc_html($price->name); ?>(){
												let value = parseInt(document.getElementById('<?php echo esc_html($price->name); ?>').value);
												value = isNaN(value) ? 0 : value;
												value++;
												if (value > 0) { 
													jQuery('#decrease_<?php echo esc_html($price->name); ?>').removeClass('not-allowed');
												}
												document.getElementById('<?php echo esc_html($price->name); ?>').value = value;

												let pax = '<?php echo strtolower(esc_html($price->name)); ?>'+'_num';

												// populate pax object
												pax_obj[pax] = value;	

												running_pax_price.<?php echo esc_html($price->name); ?> = value*<?php echo esc_html($original_price); ?>;
												running_amount = 0;
												for (const [pax, amount] of Object.entries(running_pax_price)) {
													if (amount > 0) {
														running_amount += amount;
													}
												}
												getOverallTotal(running_amount);
											}
											function decreasePax_<?php echo esc_html($price->name); ?>(){
												let value = parseInt(document.getElementById('<?php echo esc_html($price->name); ?>').value);
												value = isNaN(value) ? 0 : value;
												if (value <= 0) {
													return false;
												}
												value--;
												if (value <= 0) {
													jQuery('#decrease_<?php echo esc_html($price->name); ?>').addClass('not-allowed');
												} 
												document.getElementById('<?php echo esc_html($price->name); ?>').value = value;

												let pax = '<?php echo strtolower(esc_html($price->name)); ?>'+'_num';

												// populate pax object
												pax_obj[pax] = value;	

												running_pax_price.<?php echo esc_html($price->name); ?> = value*<?php echo esc_html($original_price); ?>;
												running_amount = 0;
												for (const [pax, amount] of Object.entries(running_pax_price)) {
													if (amount > 0) {
														running_amount += amount;
													}
												}
												getOverallTotal(running_amount);
											}
										</script>
										
									<?php } ?>

									<script>
										total_pax_price = <?php echo $total_pax_price ? esc_html($total_pax_price) : 0; ?>;
										getOverallTotal(total_pax_price);
									</script>

									</div>
								</div>

								<p id="rezgo-gift-pax-errors" style="display:none;">
									Your total must be above <?php echo $companyCurrency; ?>0. Please review your choices.
								</p>
							</div>

						</div>

						<!-- pass variables to payment page -->
						<input type="hidden" name="billing_amount" value="<?php echo esc_html($total_pax_price); ?>">
						<input type="hidden" name="option" value="<?php echo esc_html($_REQUEST['option']); ?>">
						<input type="hidden" name="date" value="<?php echo esc_html($_REQUEST['date']); ?>">

						<input type="hidden" name="buy_as_gift" value="1">

						<div id="gift_pax_estimated_total">
							<span id="total_heading"> Gift Card Total </span>
							<span id="gc_total_due_step1"></span>
							<span id="estimated_wording">*total includes estimated fees at time of booking</span>
						</div>

						<img id="gifted-as-img" src="<?php echo $site->path; ?>/img/gifted_as.svg" alt="Gifted As">

						<?php } else { ?>
							<p id="rezgo-gift-invalid-link"><i class="far fa-exclamation-circle"></i> Invalid Link. Please ensure that you entered the correct URL.</p>
						<?php } ?>

					<?php } ?>

					</div>

					<span <?php if ($buy_as_gift && !$prices) { ?> style="display:none;" <?php } ?> class="rezgo-gift-card-container step-one-btn-span">
						<hr>
						<div id="rezgo-gift-errors" style="display:none;">
							<div>Some required fields are missing or incorrect. Please review the highlighted fields.</div>
						</div>

						<div class="cta">
							<button type="submit" class="btn rezgo-btn-book btn-lg btn-block rezgo-gc-first-step-btn" id="purchase-submit">
								Proceed to Checkout
							</button>
						</div>
						<input type="hidden" name="rezgoAction" value="firstStepGiftCard">
					</span>

					</form>
			<?php } ?>
		</div>
	</div>
</div>

<?php if ($show_gc) { ?>
	<script>

	jQuery(document).ready(function($){	

		/* FORM (#purchase) */

		// STATES VAR
		let ca_states = JSON.parse( decodeURIComponent( '<?php echo rawurlencode( wp_json_encode($site->getRegionList('ca')) ); ?>' ) )
		let us_states = JSON.parse( decodeURIComponent( '<?php echo rawurlencode( wp_json_encode($site->getRegionList('us')) ); ?>' ) )
		let au_states = JSON.parse( decodeURIComponent( '<?php echo rawurlencode( wp_json_encode($site->getRegionList('au')) ); ?>' ) )

		// FORM ELEM
		let $purchaseForm = $('#purchase');
		let $purchaseBtn = $('#purchase-submit');
		let $formMessage = $('#rezgo-gift-message');
		let $formMsgBody = $('#rezgo-gift-message-body');
		let $amtSelect = $('#rezgo-billing-amount');
		let $amtCustom = $('#rezgo-custom-billing-amount');

		// restrict custom amount to 2 decimal places
		two_decimal = function(e) {
			let t = e.value;
			e.value = (t.indexOf(".") >= 0) ? (t.substr(0, t.indexOf(".")) + t.substr(t.indexOf("."), 3)) : t;
		}

		function error_booking() {
			$('#rezgo-gift-errors').show();

			setTimeout(function(){
				$('#rezgo-gift-errors').hide();
			}, 8000);
		}

		// FORM VALIDATE
		$purchaseForm.validate({
			messages: {
				recipient_name: {
					required: "Please enter a name"
				},
				recipient_email: {
					required: "Please enter a valid email address"
				},
				custom_billing_amount: {
					required: 'Please enter an amount'
				},
				billing_amount: {
					required: 'Please select an amount'
				},
			},
			errorPlacement: function(error, element) {
				error.insertAfter(element);
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

		$purchaseForm.submit(function(e) {

			<?php if ($buy_as_gift) { ?>
				// disable purchases with 0 
				if ($('input[name=billing_amount]').val() == 0) {
					
					$('#rezgo-gift-pax-errors').show();

					setTimeout(function(){
						$('#rezgo-gift-pax-errors').hide();
					}, 8000);

					return false;
				}
			<?php } ?>

			// FORM VALIDATION
			let validationCheck = $purchaseForm.valid();
			if (!validationCheck) {
				$purchaseBtn.removeAttr('disabled');
				error_booking();
			} else {
				e.preventDefault();
				$.ajax({
					url: '<?php echo admin_url('admin-ajax.php'); ?>',
					type: 'POST',
					data: {
						action: 'rezgo',
						method: 'gift_card_ajax',
						rezgoAction:'giftCardPayment',
						formData: $purchaseForm.serialize(),
					},
					success: function(data)
						{
							top.location.href= '<?php echo esc_html($site->base); ?>/gift-card-payment';
						}
				});
			}
		});

	});

	</script>
<?php } ?>

<script>	

jQuery(document).ready(function($){	

	// MONEY FORMATTING
	var form_symbol = '$';
	var form_decimals = '2';
	var form_separator = ',';

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

	let gc_total_due;
	let default_amt = 50;
	let custom_input = $('#rezgo-custom-billing-amount');

	if ($('#gc_preset_custom').is(':checked')){
		$('.rezgo-custom-billing-amount-container').show();
		$('#rezgo-gc-choose-container').slideToggle();
	}

	<?php if ($_SESSION['gift-card']['billing_amount'] == 'custom'){ ?>

		$('.rezgo-custom-billing-amount-container').show();
		$('#rezgo-gc-choose-container').hide();
		custom_input.addClass('required');
		custom_input.val('<?php echo esc_html($_SESSION['gift-card']['custom_billing_amount']); ?>');
		$('input[name=billing_amount]:checked').val('custom');

	<?php } else { ?>

		$('input[name=billing_amount]:checked').val(<?php echo esc_html($_SESSION['gift-card']['billing_amount']); ?>);
		custom_input.val('');
		$('#gc_preset_<?php echo (string) esc_html($_SESSION['gift-card']['billing_amount']); ?>').prop("checked", true);

	<?php } ?>

	toggleAmount = function() {

		if($('input[name=billing_amount]:checked').val() == 'custom') {

			// clear any custom amount if filled previously
			$('#gc_total_due_step1').html('');

			$(this).hide();

			$('.rezgo-custom-billing-amount-container').show();
			$('#rezgo-gc-choose-container').slideToggle();
			custom_input.addClass('required');

			let custom_billing_pos = $('#purchase').position();
			let search_div_height = $('#rezgo-gift-card-search').outerHeight() + 50;
			let custom_billing_scroll = Math.round(custom_billing_pos.top);

			setTimeout(() => {
				custom_input.focus();
				window.parent.scrollTo({
					top: custom_billing_scroll + search_div_height,
					left: 0,
					behavior: 'smooth'
				});
			}, 150);

			custom_input.change(function(){
				gc_total_due = Number(custom_input.val());
			});

		} else {
			gc_total_due = Number($('input[name=billing_amount]:checked').val());
		}
	}

	// cancel custom amount and reset values
	$('#rezgo-custom-amount-cancel').click(function(){
		$('.rezgo-custom-billing-amount-container').hide();
		$('#rezgo-gc-choose-container').slideToggle();
		custom_input.removeClass('required');
		
		custom_input.val('');
		$('#gc_total_due_step1').html('');

		// reset custom amount
		$('#rezgo-custom-billing-amount').val('');

		// select first default amount again 
		$("input[name='billing_amount']").eq(0).prop("checked", true);
		$('input[name=billing_amount]:checked').val(50);
	});

	/* FORM (#search) */
	var $search = $('.search-section');
	var $searchForm = $('#search');
	var $searchText = $('#search-card-number');
	var $searchError = $('#search-card-empty-error');
	var today = parseInt('<?php echo strtotime("today"); ?>');

	$searchForm.submit(function(e){
		e.preventDefault();

		var search = $searchText.val();

		$('.rezgo-gift-search-response').hide();
		$('.rezgo-gift-search-response').removeClass('error');
		$('.rezgo-gift-search-response').removeClass('success');

		if (search) {
			
			$.ajax({
				url: '<?php echo admin_url('admin-ajax.php'); ?>', 
				type: 'POST',
				data: {
					action: 'rezgo',
					method: 'gift_card_ajax',
					rezgoAction: 'getGiftCard',
					gcNum: search
				},
				success: function (data) {
					var json, success, err, msg, amt, exp, max, use;

					err = 0;
					json = data.split("|||");
					json = json.slice(-1)[0];
					gcData = JSON.parse(json);

					s = parseFloat(gcData.status);

					if (debug) console.log(gcData);

					if (s) {
						amt = parseFloat(gcData.amount);
						exp = parseInt(gcData.expires);
						max = parseInt(gcData.max_uses);
						use = parseInt(gcData.uses);
						msg = '<i class="far fa-gift"></i> &nbsp; Gift Card Balance: ' + currency + amt.formatMoney();

						if (max && use >= max) {
							err = "Gift card max use reached.";
						}

						if (exp && today >= exp) {
							err = "Gift card expired.";
						}
					} else {
						err = 'Gift card not found. Please, make sure you entered a correct card number.';
					}

					// RESULT
					if (err) {
						$('.rezgo-gift-search-response .msg').html(err);
						$('.rezgo-gift-search-response').addClass('error');
						$('.rezgo-gift-search-response').slideDown();

					} else {
						
						$('.rezgo-gift-search-response .msg').html(msg);
						$('.rezgo-gift-search-response').addClass('success');
						setTimeout(() => {
							$('.rezgo-gift-search-response').slideDown();
						}, 150);
					}
				},
				error: function () {
					let msg = 'Connection error. Please try again or contact Rezgo for customer support.';
					$('.rezgo-gift-search-response').addClass('error');
					$('.rezgo-gift-search-response .msg').html(msg);
				}
			});
		} else {
			err = "Please enter a Gift Card Number.";

			$('.rezgo-gift-search-response .msg').html(err);
			$('.rezgo-gift-search-response').addClass('error');
			$('.rezgo-gift-search-response').slideDown();
		}
	});
});
</script>