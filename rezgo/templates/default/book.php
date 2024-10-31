<?php
	// handle old-style booking requests
	if(isset($_REQUEST['uid']) && isset($_REQUEST['date'])) {
		$for_array = array('adult', 'child', 'senior', 'price4', 'price5', 'price6', 'price7', 'price8', 'price9');
		$new_header = '/book_new?order=clear&add[0][uid]='.sanitize_text_field($_REQUEST['uid']).'&add[0][date]='.sanitize_text_field($_REQUEST['date']);
		foreach($for_array as $v) {
			if($_REQUEST[$v.'_num']) $new_header .= '&add[0]['.sanitize_text_field($v).'_num]='.sanitize_text_field($_REQUEST[$v.'_num']);
		}
		$site->sendTo($new_header);
	}

	$new_date = new Datetime();
	$company = $site->getCompanyDetails();

	$site->initiateCheckout();

	if (REZGO_WORDPRESS) $site->setTimeZone();

	$date_format = (string)$company->date_format;
	$first_date = (string)$company->start_week == 'mon' ? 1 : 0;

	function convertPHPToMomentFormat($format) {
		$replacements = [
			'd' => 'DD',
			'D' => 'ddd',
			'j' => 'D',
			'l' => 'dddd',
			'N' => 'E',
			'S' => 'o',
			'w' => 'e',
			'z' => 'DDD',
			'W' => 'W',
			'F' => 'MMMM',
			'm' => 'MM',
			'M' => 'MMM',
			'n' => 'M',
			't' => '', // no equivalent
			'L' => '', // no equivalent
			'o' => 'YYYY',
			'Y' => 'YYYY',
			'y' => 'YY',
			'a' => 'a',
			'A' => 'A',
			'B' => '', // no equivalent
			'g' => 'h',
			'G' => 'H',
			'h' => 'hh',
			'H' => 'HH',
			'i' => 'mm',
			's' => 'ss',
			'u' => 'SSS',
			'e' => 'zz', // deprecated since version 1.6.0 of moment.js
			'I' => '', // no equivalent
			'O' => '', // no equivalent
			'P' => '', // no equivalent
			'T' => '', // no equivalent
			'Z' => '', // no equivalent
			'c' => '', // no equivalent
			'r' => '', // no equivalent
			'U' => 'X',
		];
    $momentFormat = strtr($format, $replacements);
    return $momentFormat;
}
$moment_date_format = convertPHPToMomentFormat($date_format);

?>
<?php if (!REZGO_WORDPRESS) { ?>
<script src="<?php echo $site->path; ?>/js/jquery.form.js"></script>
<script src="<?php echo $site->path; ?>/js/jquery.validate.min.js"></script>
<script src="/js/ie8.polyfils.min.js"></script>
<script src="<?php echo $site->path; ?>/js/chosen.jquery.min.js"></script>
<script src="<?php echo $site->path; ?>/js/date-time/bootstrap-datepicker.min.js"></script>
<script src="<?php echo $site->path; ?>/js/date-time/bootstrap-timepicker.min.js"></script>
<script src="<?php echo $site->path; ?>/js/date-time/moment.min.js"></script>
<script src="<?php echo $site->path; ?>/js/date-time/daterangepicker.min.js"></script>
<link rel="stylesheet" href="<?php echo $site->path; ?>/css/pretty-checkbox.min.css">
<link rel="stylesheet" href="<?php echo $site->path; ?>/css/chosen.min.css">
<link rel="stylesheet" href="<?php echo $site->path; ?>/css/datepicker.css">
<link rel="stylesheet" href="<?php echo $site->path; ?>/css/daterangepicker.css">
<?php } ?>
<script>

function trimArray(array) {
  return array.map(element => element.trim());
}

var elements = new Array();
var split_total = new Array();
var overall_total = '0';
var modified_total = '0';

// MONEY FORMATTING
const form_symbol = '$';
const form_decimals = '2';
const form_separator = ',';
const currency = decodeURIComponent( '<?php echo rawurlencode( (string) $site->xml->currency_symbol ); ?>' );

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
</script>	

			<div id="rezgo-book-wrp" class="container-fluid rezgo-container">
				<div class="tab-content">
					<div id="rezgo-book-step-one" class="tab-pane active">
						<div class="jumbotron rezgo-booking">
							<div id="rezgo-order-crumb" class="row">
								<ol class="breadcrumb rezgo-breadcrumb">
									<?php 
										// check for cart token, add to order link to preserve cart data 
										$cart_token = sanitize_text_field($_COOKIE['rezgo_cart_token_'.REZGO_CID]); 
										$order_url = $site->base.'/order/'.$cart_token; 
									?>
									<li id="rezgo-book-step-one-your-order" class="rezgo-breadcrumb-order">
										<a class="link" href="<?php echo esc_url($order_url); ?>">
											<span class="default">Order</span>
											<span class="custom"></span>
										</a>
									</li>
									<li id="rezgo-book-step-one-info" class="rezgo-breadcrumb-info active"><span class="default">Guest Information</span><span class="custom"></span></li>
									<li id="rezgo-book-step-one-billing" class="rezgo-breadcrumb-billing"><span class="default">Payment</span><span class="custom"></span></li>
									<li id="rezgo-book-step-one-confirmation" class="rezgo-breadcrumb-confirmation"><span class="default">Confirmation</span><span class="custom"></span></li>
								</ol>
							</div>
						<?php
							$complete_booking_total = 0;
							$c = 0;
							$first_index = 1; // only for the first instances of pax inputs
							$cart = $site->getCart(1); // get the cart, remove any dead entries
							$lead_passenger = $site->getLeadPassenger(); // get lead passenger details
							$cart_data = $site->getFormData();
							$primary_required_fields = [];
							$group_required_fields = [];

							if(isset($_COOKIE['cart_status'])) $cart_status = new SimpleXMLElement($_COOKIE['cart_status']);
						?>

						<?php if (isset($cart_status)){ ?>
							<div id="rezgo-order-error-message">
								<!-- Top level error message -->
								<span class="message">
									<span id="error-message"><?php echo esc_html($cart_status->message); ?></span>
										<?php // list items removed
											if (is_array($cart_status->removed->item)) {
												foreach ($cart_status->removed->item as $removed_item){
													$tour = $site->getTours('t=uid&q='.$removed_item->id); 
													$removed_date = $tour[0]->availability_type != 'open' ? ' ('. date((string) $company->date_format, (string) $removed_item->date) .')' : ''; ?>
													<br>
													<?php echo esc_html($tour[0]->item); ?> - <?php echo esc_html($tour[0]->option . $removed_date); ?>
												has been removed from your cart
											<?php } ?>
										<?php } ?>
								</span>
								<a href="#" id="rezgo-error-dismiss" class="btn"><span><i class="fas fa-times"></i></span></i></a>
							</div>

							<script>
								jQuery(function($) {
									// dismiss error when user navigates away or manually closes it
									function dismissError(){
										$.ajax({
											type: 'POST',
											url: '<?php echo admin_url('admin-ajax.php'); ?>' + '?action=rezgo&method=book_ajax',
											data: { rezgoAction: 'reset_cart_status'},
											success: function(data){
													// console.log('reset cart status session');
													},
											error: function(error){
													console.log(error);
													}
										});
									}

									$('#rezgo-error-dismiss').click(function(e){
										dismissError();
										e.preventDefault();
										$('#rezgo-order-error-message').fadeOut();
									});

									setTimeout(() => {
										dismissError();
									}, 3000);

									window.onbeforeunload = dismissError();
								});
							</script>
						<?php } ?>

						<?php if (!$cart) { ?>

							<div class="rezgo-order-empty-cart-wrp">
								<div class="rezgo-form-group cart_empty">
									<p class="lead">
										<span class="d-none d-sm-inline-block">There are</span><span>&nbsp;<span class="d-none d-sm-inline-block">n</span><span class="d-sm-none">N</span>o items</span><span class="d-none d-sm-inline-block">&nbsp;in your order.</span>
									</p>
								</div>

								<div class="row" id="rezgo-booking-btn">
									<div class="col-md-4 col-12 rezgo-btn-wrp">
										<span class="btn-check"></span>
										<a id="rezgo-order-book-more-btn" href="<?php echo esc_attr($site->base); ?>" class="btn rezgo-btn-default btn-lg btn-block">
											<span>Book More</span>
										</a>
									</div>
								</div>
							</div>

						<?php } else { ?>

							<div class="flex-container book-page-container">

								<div class="pax-info-container">

									<form id="rezgo-guest-form" role="form" method="post" target="rezgo_content_frame">

										<div class="lead-passenger-form-group rezgo-form-group">
											<h3 class="lead-passenger-header rezgo-item-title">Booking Contact</h3>
											<br>
											<div class="rezgo-form-row row form-group">
												<div class="col-sm-6 rezgo-form-input">
													<label for="lead_passenger_first_name" class="col-sm-2 control-label rezgo-label-right float-start">
														<span>First Name <em class ="fa fa-asterisk"></em></span>
													</label>
													<input type="text" class="form-control required lead-passenger-input" id="lead_passenger_first_name" name="lead_passenger_first_name" value="<?php echo esc_attr($lead_passenger['first_name'] ?? ''); ?>">
												</div>

												<div class="col-sm-6 rezgo-form-input lead-passenger-lname-group">
													<label for="lead_passenger_last_name" class="col-sm-2 control-label rezgo-label-right float-start">
														<span>Last Name <em class="fa fa-asterisk"></em></span>
													</label>
													<input type="text" class="form-control required lead-passenger-input" id="lead_passenger_last_name" name="lead_passenger_last_name" value="<?php echo esc_attr($lead_passenger['last_name'] ?? ''); ?>">
												</div>
											</div>

											<div class="rezgo-form-row row form-group">
												<div class="col-sm-12 rezgo-form-input">
													<label for="lead_passenger_email" class="col-sm-2 control-label rezgo-label-right float-start">
														<span>Email <em class="fa fa-asterisk"></em></span>
													</label>
													<input type="email" class="form-control required lead-passenger-input" id="lead_passenger_email" name="lead_passenger_email" value="<?php echo esc_attr($lead_passenger['email'] ?? ''); ?>">
														<span class="email-note">Booking confirmation will be sent to this email</span> 
												</div>
											</div>
										</div>
										<hr>

										<?php // start cart loop for each tour in the order ?>
										<?php 
											$item_count = 1;
											$non_package_items = array();
											$cart_package_uids = array();
											$primary_required_fields = array();
											$group_required_fields = array();

											foreach ($cart as $item) {
												if ($site->exists($item->package)) {
													$cart_package_uids[] .= $item->cart_package_uid; 
												} else {
													$non_package_items[] = $item; 
												}
											} unset($item);

											$unique_package_uids = array_unique($cart_package_uids);
											$cart_count = (int)count($unique_package_uids) + (int)count($non_package_items);
										?>

										<?php foreach($cart as $item) { ?>
											<?php
												$required_fields = 0;
												$site->readItem($item);
											?>
									
									<?php if((int) $item->availability >= (int) $item->pax_count) { ?>
										<?php $c++; // only increment if it's still available ?>
												<?php	
												if ($site->exists($item->package_item_total)){
													$first = (int)$item->package_item_index === 1 ? 1 : '';
													$last = (int)$item->package_item_index === (int)$item->package_item_total ? 1 : ''; 
													$package_id = (int)$item->package; 
													$cart_package_uid = (int)$item->cart_package_uid; 
													$package = $site->getTours('t=com&q='.$item->package); 
												?>

													<?php if ($first){ ?>
														<h3 class="rezgo-booking-of rezgo-booking-title">
															<div class="rezgo-sub-title">
																<span>Booking <?php echo esc_html($item_count); ?> of <?php echo esc_html($cart_count); ?></span>
															</div>
														</h3>
														<h3 class="rezgo-package-title">
															<?php $package_url = $site->base.'/details/'.$item->package.'/'.$site->seoEncode($item->package_name); ?>
															<a class="no-click" href="<?php echo esc_url($package_url); ?>">
																<i class="fad fa-layer-group fa-lg"></i> 
															<span><?php echo esc_html($item->package_name); ?></span>
															</a>
														</h3>

													<?php } // if ($first) ?>

													<div class="package-icon-container">
														<i class="fad fa-circle"></i>
													</div>

													<div id="rezgo-book-step-one-item-<?php echo esc_attr($item->uid); ?>">

														<div class="row rezgo-form-group rezgo-booking-info">
															<h3 class="rezgo-item-title">
																<?php echo esc_html($item->item); ?> &mdash; <?php echo esc_html($item->option); ?>
															</h3>
															
															<?php $data_book_date = date("Y-m-d", (int)$this->cart_data[$c-1]->date); ?>

															<label>
																<?php if(in_array((string) $item->date_selection, DATE_TYPES)) { ?>
																	<span>Date: </span>
																	<span class="lead"><?php echo esc_html(date((string) $company->date_format, (int) $item->booking_date)); ?></span>
																	<?php if ($site->exists($item->time)){ ?>
																		at <?php echo (string) esc_html($item->time); ?>
																	<?php } ?> 
																<?php } else { ?>
																	<label><span class="lead"> Open Availability </span></label>
																<?php } ?>
															</label>

															<?php if($item->discount_rules->rule) {
																echo '<br><label class="rezgo-booking-discount">
																<span class="rezgo-discount-span">Discount:</span> ';
																$discount_string = '';
																foreach($item->discount_rules->rule as $discount) {	
																	$discount_string .= ($discount_string) ? ', '.$discount : $discount;
																}
																echo '<span class="rezgo-promo-code-desc">'.esc_html($discount_string).'</span>
																</label>';
															} ?>
															
															<input type="hidden" name="booking[<?php echo esc_attr($c); ?>][index]" value="<?php echo esc_attr($c-1); ?>">
															<input type="hidden" name="booking[<?php echo esc_attr($c); ?>][uid]" value="<?php echo esc_attr($item->uid); ?>">
															<input type="hidden" name="booking[<?php echo esc_attr($c); ?>][date]" value="<?php echo esc_attr($data_book_date); ?>">
															<?php if($item->package){ ?> <input type="hidden" name="booking[<?php echo esc_attr($c); ?>][package]" value="<?php echo esc_attr($item->package); ?>"> <?php } ?>
															<?php if($item->cart_package_uid){ ?> <input type="hidden" name="booking[<?php echo esc_attr($c); ?>][cart_package_uid]" value="<?php echo esc_attr($item->cart_package_uid); ?>"> <?php } ?>

															<?php if ($first) { ?>
															<div class="row rezgo-booking-instructions">
																<span> To complete this booking, please fill out the following form.</span>

																<?php 
																	if (is_array($site->getTourForms('primary'))) {
																		$required_fields = 0;
																		foreach($site->getTourForms('primary') as $form) { 
																			if($form->require) {
																				$required_fields++;
																				$primary_required_fields = array (
																					$c => $required_fields
																				);
																			}
																		}
																	}
																	if (is_array($site->getTourForms('group'))) {
																		$required_fields = 0;
																		foreach($site->getTourForms('group') as $form) { 
																			if($form->require) {
																				$required_fields++;
																				$group_required_fields = array (
																					$c => $required_fields
																				);
																			}
																		}
																	}
																?>

																<span <?php if($item->group == 'require' || $item->group == 'require_name' || isset($primary_required_fields[$c]) || isset($group_required_fields[$c]))  { echo ' style="display:inline-block;"'; } else { echo ' style="display:none;"'; } ?>>
																	<span id="required_note-<?php echo esc_attr($c); ?>" >Please note that fields marked with <em class="fa fa-asterisk"></em> are required.</span>
																</span>
															</div>
															<?php } ?>

															<?php if($site->getTourForms('primary')) { 
																// match form index key with form value (prevent mismatch if form is set to BE only)
																$cart_pf[$c-1] = $cart_data[$c-1]->primary_forms->form ?? '';
																if ($cart_pf[$c-1]){
																	foreach ($cart_pf[$c-1] as $k => $v) {
																		$cart_pf_val[$c-1][(int)$v->num]['value'] = $v->value;
																	}
																}
															?>

															<div class="row rezgo-form-group rezgo-additional-info primary-forms-container">
																<div class="col-12 rezgo-sub-title form-sectional-header rezgo-book-add-info">
																	<span>Additional Information</span>
																</div>

															<div class="clearfix rezgo-short-clearfix">&nbsp;</div>

																<?php foreach($site->getTourForms('primary') as $form) { ?>
																	<?php if($form->require) $required_fields++; ?>
																	<?php if($form->type == 'text') { ?>
																		<div class="form-group rezgo-custom-form rezgo-form-input">
																			<label class="control-label"><?php echo esc_html($form->title); ?><?php if($form->require) { ?> <em class="fa fa-asterisk"></em><?php } ?></label>
																			<input 
																				id="text-<?php echo esc_attr($c); ?>_<?php echo esc_attr($form->id); ?>" 
																				type="text" class="form-control<?php echo ($form->require) ? ' required' : ''; ?>" 
																				name="booking[<?php echo esc_attr($c); ?>][tour_forms][<?php echo esc_attr($form->id); ?>]" 
																				value="<?php echo esc_attr($cart_pf_val[$c-1][(int)$form->id]['value'] ?? ''); ?>">
																			<?php if ($site->exists($form->instructions)){ ?>
																				<p class="rezgo-form-comment"><span><?php echo wp_kses($form->instructions, ALLOWED_HTML); ?></span></p>
																			<?php } ?>
																		</div>
																	<?php } ?>

																	<?php if($form->type == 'select') { ?>
																		<div class="form-group rezgo-custom-form rezgo-form-input">
																			<label class="control-label"><?php echo esc_html($form->title); ?><?php if($form->require) { ?> <em class="fa fa-asterisk"></em><?php } ?></label>
																			<select 
																				id="select-<?php echo esc_attr($c); ?>_<?php echo esc_attr($form->id); ?>" 
																				class="chosen-select form-control<?php echo ($form->require) ? ' required' : ''; ?> rezgo-custom-select" 
																				name="booking[<?php echo esc_attr($c); ?>][tour_forms][<?php echo esc_attr($form->id); ?>]">
																				<option value=""></option>
																				<?php foreach($form->options as $option) { ?>
																					<option><?php echo esc_html($option); ?></option>
																				<?php } ?>
																			</select>
																			<?php if ($site->exists($form->instructions)){ ?>
																				<p class="rezgo-form-comment"><span><?php echo wp_kses($form->instructions, ALLOWED_HTML); ?></span></p>
																			<?php } ?>
																			<?php
																				if (isset($form->options_instructions)) {
																						$optex_count = 1;
																						foreach($form->options_instructions as $opt_extra) {
																						echo '<span class="opt_extra" id="optex_'.esc_attr($optex_count).'" style="display:none">'.esc_html($opt_extra).'</span>';
																						$optex_count++;
																					}
																				}
																			?>
																			<input type="hidden" value='' name="booking[<?php echo esc_attr($c); ?>][tour_forms][<?php echo esc_attr($form->id); ?>]" data-addon="select_primary-<?php echo esc_attr($c); ?>_<?php echo esc_attr($form->id); ?>_hidden">
																		</div>
																		<script>
																			jQuery(function($) {
																				$('#select-<?php echo esc_html($c); ?>_<?php echo esc_html($form->id); ?>').change(function(){
																					$(this).valid();

																					if ($(this).val() == ''){
																						$("input[data-addon='select_primary-<?php echo esc_html($c); ?>_<?php echo esc_html($form->id); ?>_hidden']").attr("disabled", false);
																					} else {
																						$("input[data-addon='select_primary-<?php echo esc_html($c); ?>_<?php echo esc_html($form->id); ?>_hidden']").attr("disabled", true);
																					}

																				});
																				let select_primary_<?php echo esc_html($c); ?>_<?php echo esc_html($form->id); ?> = "<?php echo addslashes(html_entity_decode($cart_pf_val[$c-1][(int)$form->id]['value'] ?? '', ENT_QUOTES)); ?>";

																				if (select_primary_<?php echo esc_html($c); ?>_<?php echo esc_html($form->id); ?> != ''){
																					$("input[data-addon='select_primary-<?php echo esc_html($c); ?>_<?php echo esc_html($form->id); ?>_hidden']").attr("disabled", true);
																					$('#select-<?php echo esc_html($c); ?>_<?php echo esc_html($form->id); ?>').val(select_primary_<?php echo esc_html($c); ?>_<?php echo esc_html($form->id); ?>).trigger('chosen:updated');

																					let select_pf_options_<?php echo esc_html($c); ?>_<?php echo esc_html($form->id); ?> = document.getElementById('select-<?php echo esc_html($c); ?>_<?php echo esc_html($form->id); ?>').options;
																					for (i=0, len = select_pf_options_<?php echo esc_html($c); ?>_<?php echo esc_html($form->id); ?>.length; i<len; i++) {
																						let opt = select_pf_options_<?php echo esc_html($c); ?>_<?php echo esc_html($form->id); ?>[i];
																						if (opt.selected) {
																							$('#select-<?php echo esc_html($c); ?>_<?php echo esc_html($form->id); ?>').parent().find( '#optex_' + i + '.opt_extra' ).show();
																						} else {
																							$('#select-<?php echo esc_html($c); ?>_<?php echo esc_html($form->id); ?>').parent().find( '#optex_' + i + '.opt_extra' ).hide();
																						}
																					}
																				}
																			});
																		</script>

																	<?php } ?>

																	<?php if($form->type == 'multiselect') { ?>
																		<div class="form-group rezgo-custom-form rezgo-form-input">
																			<label class="control-label"><?php echo esc_html($form->title); ?><?php if($form->require) { ?> <em class="fa fa-asterisk"></em><?php } ?></label>
																			
																			<select 
																				id="rezgo-custom-select-<?php echo esc_attr($c); ?>_<?php echo esc_attr($form->id); ?>" 
																				class="chosen-select form-control<?php echo ($form->require) ? ' required' : ''; ?> rezgo-custom-select" 
																				multiple="multiple" 
																				name="booking[<?php echo esc_attr($c); ?>][tour_forms][<?php echo esc_attr($form->id); ?>][]">
																				<option value=""></option>
																				<?php foreach($form->options as $option) { ?>
																					<option><?php echo esc_html($option); ?></option>
																				<?php } ?>
																			</select>
																			<?php if ($site->exists($form->instructions)){ ?>
																				<p class="rezgo-form-comment"><span><?php echo wp_kses($form->instructions, ALLOWED_HTML); ?></span></p>
																			<?php } ?>
																			<?php
																				if (isset($form->options_instructions)) {
																						$optex_count = 1;
																						foreach($form->options_instructions as $opt_extra) {
																						echo '<span class="opt_extra" id="optex_'.esc_attr($optex_count).'" style="display:none">'.esc_html($opt_extra).'</span>';
																						$optex_count++;
																					}
																				}
																			?>
																			<input type="hidden" value='' name="booking[<?php echo esc_attr($c); ?>][tour_forms][<?php echo esc_attr($form->id); ?>][]" data-addon="multiselect_primary-<?php echo esc_attr($c); ?>_<?php echo esc_attr($form->id); ?>_hidden">
																		</div>
																		<script>
																			jQuery(function($) {
																				$('#rezgo-custom-select-<?php echo esc_html($c); ?>_<?php echo esc_html($form->id); ?>').change(function(){
																					$(this).valid();

																					if ($(this).val().length === 0){
																						$("input[data-addon='multiselect_primary-<?php echo esc_html($c); ?>_<?php echo esc_html($form->id); ?>_hidden']").attr("disabled", false);
																					} else {
																						$("input[data-addon='multiselect_primary-<?php echo esc_html($c); ?>_<?php echo esc_html($form->id); ?>_hidden']").attr("disabled", true);
																					}
																				});

																				let multiselect_primary_<?php echo esc_html($c); ?>_<?php echo esc_html($form->id); ?> = '<?php echo (string)addslashes(html_entity_decode($cart_pf_val[$c-1][(int)$form->id]['value'] ?? '', ENT_QUOTES)); ?>';

																				if (multiselect_primary_<?php echo esc_html($c); ?>_<?php echo esc_html($form->id); ?>.length){
																					$("input[data-addon='multiselect_primary-<?php echo esc_html($c); ?>_<?php echo esc_html($form->id); ?>_hidden']").attr("disabled", true);
																					multiselect_primary_<?php echo esc_html($c); ?>_<?php echo esc_html($form->id); ?> =  multiselect_primary_<?php echo esc_html($c); ?>_<?php echo esc_html($form->id); ?>.split(', ');

																					$('#rezgo-custom-select-<?php echo esc_html($c); ?>_<?php echo esc_html($form->id); ?>').val(multiselect_primary_<?php echo esc_html($c); ?>_<?php echo esc_html($form->id); ?>).trigger('chosen:updated');

																					let multiselect_pf_options_<?php echo esc_html($c); ?>_<?php echo esc_html($form->id); ?> = document.getElementById('rezgo-custom-select-<?php echo esc_html($c); ?>_<?php echo esc_html($form->id); ?>').options;
																					for (i=0, len = multiselect_pf_options_<?php echo esc_html($c); ?>_<?php echo esc_html($form->id); ?>.length; i<len; i++) {
																						let opt = multiselect_pf_options_<?php echo esc_html($c); ?>_<?php echo esc_html($form->id); ?>[i];
																						if (opt.selected) {
																							$('#rezgo-custom-select-<?php echo esc_html($c); ?>_<?php echo esc_html($form->id); ?>').parent().find( '#optex_' + i + '.opt_extra' ).show();
																						} else {
																							$('#rezgo-custom-select-<?php echo esc_html($c); ?>_<?php echo esc_html($form->id); ?>').parent().find( '#optex_' + i + '.opt_extra' ).hide();
																						}
																					}
																				}
																			});
																		</script>
																	<?php } ?>

																	<?php if($form->type == 'textarea') { ?>
																		<div class="form-group rezgo-custom-form rezgo-form-input">
																			<label class="control-label"><?php echo esc_html($form->title); ?><?php if($form->require) { ?> <em class="fa fa-asterisk"></em><?php } ?></label>
																			<textarea 
																				id="textarea-<?php echo esc_attr($c); ?>_<?php echo esc_attr($form->id); ?>" 
																				class="form-control<?php echo ($form->require) ? ' required' : ''; ?>" 
																				name="booking[<?php echo esc_attr($c); ?>][tour_forms][<?php echo esc_attr($form->id); ?>]" 
																				cols="40" 
																				rows="4"><?php echo esc_textarea($cart_pf_val[$c-1][(int)$form->id]['value'] ?? ''); ?></textarea>
																			<?php if ($site->exists($form->instructions)){ ?>
																				<p class="rezgo-form-comment"><span><?php echo wp_kses($form->instructions, ALLOWED_HTML); ?></span></p>
																			<?php } ?>
																		</div>
																	<?php } ?>
																	
																	<?php if($form->type == 'datepicker') { ?>
																		<div class="form-group rezgo-custom-form rezgo-form-input rezgo-datepicker-input">
																			<label class="control-label"><?php echo esc_html($form->title); ?><?php if($form->require) { ?> <em class="fa fa-asterisk"></em><?php } ?></label>

																			<input 
																				autocomplete="off"
																				id="text-<?php echo esc_attr($c); ?>_<?php echo esc_attr($form->id); ?>" 
																				type="text" class="datepicker-<?php echo esc_attr($c); ?> form-control<?php echo ($form->require) ? ' required' : ''; ?>" 
																				name="booking[<?php echo esc_attr($c); ?>][tour_forms][<?php echo esc_attr($form->id); ?>]" 
																				value="<?php echo esc_attr($cart_pf_val[$c-1][(int)$form->id]['value'] ?? ''); ?>">

																				<i class="fal fa-calendar-alt date-icon"></i>
																			
																				<?php if ($site->exists($form->instructions)){ ?>
																				<p class="rezgo-form-comment"><span><?php echo wp_kses($form->instructions, ALLOWED_HTML); ?></span></p>
																			<?php } ?>
																		</div>

																		<script>
																			jQuery(function($) {
																				$('.datepicker-<?php echo esc_attr($c); ?>').daterangepicker(
																					{	
																						startDate: moment(),
																						"singleDatePicker": true,
																						locale: {
																							format: '<?php echo $moment_date_format; ?>',
																							firstDay: <?php echo $first_date; ?>
																						}
																					},
																				);
																			});
																		</script>
																	<?php } ?>

																	<?php if($form->type == 'checkbox') { ?>
																		<div class="rezgo-pretty-checkbox-container">

																			<div class="rezgo-form-group rezgo-custom-form rezgo-form-input rezgo-pretty-checkbox">
																				<div class="pretty p-default p-curve p-smooth">

																					<input type="checkbox"<?php echo ($form->require) ? ' class="required"' : ''; ?> 
																						id="<?php echo esc_attr($form->id); ?>|<?php echo esc_attr(base64_encode($form->title)); ?>|<?php echo esc_attr($form->price); ?>|<?php echo esc_attr($c); ?>|<?php echo esc_attr($price->name ?? ''); ?>|<?php echo esc_attr($num ?? ''); ?>" 
																						name="booking[<?php echo esc_attr($c); ?>][tour_forms][<?php echo esc_attr($form->id); ?>]" 
																						data-addon="checkbox-<?php echo esc_attr($c); ?>_<?php echo esc_attr($form->id); ?>">

																					<div class="state p-warning">
																						<label for="<?php echo esc_attr($form->id); ?>|<?php echo esc_attr(base64_encode($form->title)); ?>|<?php echo esc_attr($form->price); ?>|<?php echo esc_attr($c); ?>|<?php echo esc_attr($price->name ?? ''); ?>|<?php echo esc_attr($num ?? ''); ?>"><span><?php echo esc_html($form->title); ?></span>
																						<?php if ($form->require) { ?> <em class="fa fa-asterisk"></em><?php } ?>
																						<?php if ($form->price) { ?> <em class="price"><?php echo esc_attr($form->price_mod); ?> <?php echo esc_html($site->formatCurrency($form->price)); ?></em><?php } ?></label>
																					</div>
																				</div>

																				<input type='hidden' value='off' name="booking[<?php echo esc_attr($c); ?>][tour_forms][<?php echo esc_attr($form->id); ?>]" data-addon="checkbox-<?php echo esc_attr($c); ?>_<?php echo esc_attr($form->id); ?>_hidden">
																			</div>

																		<?php if ($site->exists($form->instructions)){ ?>
																			<p class="rezgo-form-comment"><span><?php echo wp_kses($form->instructions, ALLOWED_HTML); ?></span></p>
																		<?php } ?>

																		</div>
																		
																		<script>
																			jQuery(function($) {
																				$("input[data-addon='checkbox-<?php echo esc_html($c); ?>_<?php echo esc_html($form->id); ?>']").change(function(){
																					if ($(this).is(":checked")){
																						$("input[data-addon='checkbox-<?php echo esc_html($c); ?>_<?php echo esc_html($form->id); ?>_hidden']").attr("disabled", true);
																					} else {
																						$("input[data-addon='checkbox-<?php echo esc_html($c); ?>_<?php echo esc_html($form->id); ?>_hidden']").attr("disabled", false);
																					}
																				});

																				<?php if (isset($cart_pf_val[$c-1][(int)$form->id]['value']) && $cart_pf_val[$c-1][(int)$form->id]['value'] == 'on'){ ?>
																					$("input[data-addon='checkbox-<?php echo esc_html($c); ?>_<?php echo esc_html($form->id); ?>']").prop('checked', true);
																					$("input[data-addon='checkbox-<?php echo esc_html($c); ?>_<?php echo esc_html($form->id); ?>_hidden']").attr("disabled", true);
																			<?php } ?>
																			});
																		</script>
																	<?php } ?>
																	
																	<?php if($form->type == 'checkbox_price') { ?>
																	
																		<div class="rezgo-pretty-checkbox-container">
																			<div class="rezgo-form-group rezgo-custom-form rezgo-form-input rezgo-pretty-checkbox">
																				<div class="pretty p-default p-curve p-smooth">

																					<input type="checkbox" <?php echo ($form->require) ? ' class="required"' : ''; ?>
																						id="<?php echo esc_attr($form->id); ?>|<?php echo esc_attr(base64_encode($form->title)); ?>|<?php echo esc_attr($form->price); ?>|<?php echo esc_attr($c); ?>|<?php echo esc_attr($price->name ?? ''); ?>|<?php echo esc_attr($num ?? ''); ?>" 
																						name="booking[<?php echo esc_attr($c); ?>][tour_forms][<?php echo esc_attr($form->id); ?>]" 
																						data-addon="checkbox_price-<?php echo esc_attr($c); ?>_<?php echo esc_attr($form->id); ?>">
																					<div class="state p-warning">
																						<label for="<?php echo esc_attr($form->id); ?>|<?php echo esc_attr(base64_encode($form->title)); ?>|<?php echo esc_attr($form->price); ?>|<?php echo esc_attr($c); ?>|<?php echo esc_attr($price->name ?? ''); ?>|<?php echo esc_attr($num ?? ''); ?>"><span><?php echo esc_html($form->title); ?></span>
																							<?php if ($form->require) { ?> <em class="fa fa-asterisk"></em><?php } ?>
																							<?php if ($form->price) { ?> <em class="price"><?php echo esc_html($form->price_mod); ?> <?php echo esc_html($site->formatCurrency($form->price)); ?></em><?php } ?>
																						</label>
																					</div>
																				</div>

																				<input type='hidden' value='off' name="booking[<?php echo esc_attr($c); ?>][tour_forms][<?php echo esc_attr($form->id); ?>]" data-addon="checkbox_price-<?php echo esc_attr($c); ?>_<?php echo esc_attr($form->id); ?>_hidden">
																			</div>

																		<?php if ($site->exists($form->instructions)){ ?>
																			<p class="rezgo-form-comment"><span><?php echo wp_kses($form->instructions, ALLOWED_HTML); ?></span></p>
																		<?php } ?>
																		</div>

																		<script>
																			jQuery(function($) {
																				$("input[data-addon='checkbox_price-<?php echo esc_html($c); ?>_<?php echo esc_html($form->id); ?>']").change(function(){
																					if ($(this).is(":checked")){
																						$("input[data-addon='checkbox_price-<?php echo esc_html($c); ?>_<?php echo esc_html($form->id); ?>_hidden']").attr("disabled", true);
																					} else {
																						$("input[data-addon='checkbox_price-<?php echo esc_html($c); ?>_<?php echo esc_html($form->id); ?>_hidden']").attr("disabled", false);
																					}
																				});

																				<?php if (isset($cart_pf_val[$c-1][(int)$form->id]['value']) && $cart_pf_val[$c-1][(int)$form->id]['value'] == 'on'){ ?>
																					$("input[data-addon='checkbox_price-<?php echo esc_html($c); ?>_<?php echo esc_html($form->id); ?>']").prop('checked', true);
																					$("input[data-addon='checkbox_price-<?php echo esc_html($c); ?>_<?php echo esc_html($form->id); ?>_hidden']").attr("disabled", true);
																				<?php } ?>
																			});
																		</script>
																	<?php } ?>

																	<?php if($form->type == 'select_price') { ?>
																		<div class="form-group rezgo-custom-form rezgo-form-input">
																			<label class="control-label"><?php echo esc_html($form->title); ?><?php if($form->require) { ?> <em class="fa fa-asterisk"></em><?php } ?></label>
																			<select 
																				id="select-price-<?php echo esc_attr($c); ?>_<?php echo esc_attr($form->id); ?>" 
																				class="chosen-select form-control<?php echo ($form->require) ? ' required' : ''; ?> rezgo-custom-select" 
																				name="booking[<?php echo esc_attr($c); ?>][tour_forms][<?php echo esc_attr($form->id); ?>]">
																				<option value=""></option>
																				<?php $p = 0; foreach($form->options as $option) { ?>
																					<?php 
																						if(strpos($form->options_price[$p], '-') === false) {
																							$pre = '+';
																							$val = str_replace('+', '', $form->options_price[$p]);
																						} else {
																							$pre = '-';	
																							$val = str_replace('-', '', $form->options_price[$p]);
																						}
																						$val = $site->exists($val) ? $val : 0;
																						$select_price = '('.$pre.esc_html($site->formatCurrency($val)).')';
																					?>
																					<option value="<?php echo esc_html($option); ?>">
																						<?php echo esc_html($option); ?> <?php echo esc_html($select_price); ?>
																					</option>
																				<?php $p++; } unset($select_price); ?>
																			</select>
																			<?php if ($site->exists($form->instructions)){ ?>
																				<p class="rezgo-form-comment"><span><?php echo wp_kses($form->instructions, ALLOWED_HTML); ?></span></p>
																			<?php } ?>
																			<?php
																				if (isset($form->options_instructions)) {
																						$optex_count = 1;
																						foreach($form->options_instructions as $opt_extra) {
																						echo '<span class="opt_extra" id="optex_'.esc_attr($optex_count).'" style="display:none">'.esc_html($opt_extra).'</span>';
																						$optex_count++;
																					}
																				}
																			?>
																			<input type="hidden" value='' name="booking[<?php echo esc_attr($c); ?>][tour_forms][<?php echo esc_attr($form->id); ?>]" data-addon="select_price_primary-<?php echo esc_attr($c); ?>_<?php echo esc_attr($form->id); ?>_hidden">
																		</div>
																		<script>
																			jQuery(function($) {

																			$('#select-price-<?php echo esc_html($c); ?>_<?php echo esc_html($form->id); ?>').change(function(){
																				$(this).valid();

																				if ($(this).val() == ''){
																					$("input[data-addon='select_price_primary-<?php echo esc_attr($c); ?>_<?php echo esc_attr($form->id); ?>_hidden']").attr("disabled", false);
																				} else {
																					$("input[data-addon='select_price_primary-<?php echo esc_attr($c); ?>_<?php echo esc_attr($form->id); ?>_hidden']").attr("disabled", true);
																				}
																			});

																			let select_price_primary_<?php echo esc_html($c); ?>_<?php echo esc_html($form->id); ?> = "<?php echo addslashes(html_entity_decode($cart_pf_val[$c-1][(int)$form->id]['value'] ?? '', ENT_QUOTES)); ?>";

																			if (select_price_primary_<?php echo esc_html($c); ?>_<?php echo esc_html($form->id); ?> != ''){
																				$("input[data-addon='select_price_primary-<?php echo esc_html($c); ?>_<?php echo esc_html($form->id); ?>_hidden']").attr("disabled", true);
																				$('#select-price-<?php echo esc_html($c); ?>_<?php echo esc_html($form->id); ?>').val(select_price_primary_<?php echo esc_html($c); ?>_<?php echo esc_html($form->id); ?>).trigger('chosen:updated');

																				let select_pf_options_<?php echo esc_html($c); ?>_<?php echo esc_html($form->id); ?> = document.getElementById('select-price-<?php echo esc_html($c); ?>_<?php echo esc_html($form->id); ?>').options;
																				for (i=0, len = select_pf_options_<?php echo esc_html($c); ?>_<?php echo esc_html($form->id); ?>.length; i<len; i++) {
																					let opt = select_pf_options_<?php echo esc_html($c); ?>_<?php echo esc_html($form->id); ?>[i];
																					if (opt.selected) {
																						$('#select-price-<?php echo esc_html($c); ?>_<?php echo esc_html($form->id); ?>').parent().find( '#optex_' + i + '.opt_extra' ).show();
																					} else {
																						$('#select-price-<?php echo esc_html($c); ?>_<?php echo esc_html($form->id); ?>').parent().find( '#optex_' + i + '.opt_extra' ).hide();
																					}
																				}
																			}
																		});
																		</script>
																	<?php } ?>
																<?php } // end foreach($site->getTourForms('primary') ?>
															</div>
														<?php } ?>

														<?php if($item->group == 'hide' && empty($site->getTourForms('primary'))) { ?>
															<div class='rezgo-guest-info-not-required'>
																<span>Guest information is not required for booking #<?php echo esc_html($c); ?></span>
															</div>
														<?php } ?>

														<?php if($required_fields > 0) { ?>
															<script>jQuery(document).ready(function($){$('#required_note-<?php echo esc_html($c); ?>').fadeIn();});</script>
														<?php } ?>

														<?php if ($item->pick_up_locations) { ?>
														<div class="row rezgo-form-group rezgo-additional-info">
														<div class="col-sm-12 rezgo-sub-title form-sectional-header rezgo-book-transportation">
															<span>Transportation</span>
														</div>

														<div class="clearfix rezgo-short-clearfix">&nbsp;</div>
														
														<?php $pickup_locations = $site->getPickupList((int) $item->uid); ?>
														
														<div class="form-group rezgo-custom-form rezgo-form-input">
																	
															<label id="rezgo-choose-pickup"><span>Choose your pickup location</span><?php if(!empty($pickup_locations->required)) { ?>&nbsp;<em class="fa fa-asterisk"></em><?php } ?></label>
															<select 
																id="rezgo-pickup-select-<?php echo esc_attr($c); ?>" 
																class="chosen-select form-control rezgo-pickup-select <?php echo (!empty($pickup_locations->required)) ? ' required' : ''; ?>" 
																name="booking[<?php echo esc_attr($c); ?>][pickup]" 
																data-target="rezgo-pickup-detail-<?php echo esc_attr($c); ?>" 
																data-id="<?php echo esc_attr($c); ?>" 
																data-counter="<?php echo esc_attr($form_counter); ?>" 
																data-option="<?php echo esc_attr($item->uid); ?>" 
																data-pax="<?php echo esc_attr($item->pax); ?>" 
																data-time="<?php echo esc_attr($item->time); ?>">
															<option value="" data-cost="0" id="last-picked-<?php echo esc_attr($c); ?>"></option>
																<?php
																	
																	foreach($pickup_locations->pickup as $pickup) {
																		
																		$cost = ((int) $pickup->cost > 0) ? ' ('.$site->formatCurrency($pickup->cost).')' : ''; 
																
																		if($pickup->sources) { 
																		
																			echo '<optgroup label="Pickup At: '.esc_html($pickup->name).' - '.esc_html($pickup->location_address.$cost).'">'."\n";
																				
																			$s=0;
																			foreach($pickup->sources->source as $source) {
																				echo '<option value="'.esc_html($pickup->id).'-'.esc_html($s).'" data-cost="'.esc_html(($item->pax*$pickup->cost)).'">'.esc_html($source->name).'</option>'."\n";
																				$s++;
																			}
																			echo '</optgroup>'."\n";
																			
																		} else { 
																			echo '<option value="'.esc_html($pickup->id).'" data-cost="'.esc_html(($item->pax*$pickup->cost)).'">'.esc_html($pickup->name).' - '.esc_html($pickup->location_address.$cost).'</option>'."\n";
																		} 
																		
																	}
																
																?>
															</select>
															<script>
																jQuery(function($) {

																// needed for deselect option?
																$("#rezgo-pickup-select-<?php echo esc_html($c); ?>").chosen({allow_single_deselect:true});

																$("#rezgo-pickup-select-<?php echo esc_html($c); ?>").chosen().change(function() {
																	$(this).valid();
																	pickup_cost_<?php echo esc_html($c); ?> = $(this).find('option:selected').data('cost');
																	pickup_id_<?php echo esc_html($c); ?> = $(this).val();

																	if ($(this).find('option:selected').data('cost') != 0){
																		$('#last-picked-<?php echo esc_html($c); ?>').data('cost', pickup_cost_<?php echo esc_html($c); ?>*-1);
																	}
																	else{
																		pickup_cost_<?php echo esc_html($c); ?> = $('#last-picked-<?php echo esc_html($c); ?>').data('cost');
																	}
																});

																// if there is existing pickup
																<?php if ( ($site->exists($cart_data[$c-1]->pickup)) && ($cart_data[$c-1]->pickup !=0) ){ ?>

																	<?php if (!$site->exists($cart_data[$c-1]->pickup_source)) { ?> 

																		$('#rezgo-pickup-select-<?php echo esc_html($c); ?>').val(<?php echo esc_html($cart_data[$c-1]->pickup); ?>).trigger('chosen:updated');

																	<?php } else { ?>

																		// if there is pickup source, trigger the optgroup select
																		$("#rezgo-pickup-select-<?php echo esc_html($c); ?> optgroup option[value='<?php echo esc_html($cart_data[$c-1]->pickup); ?>-<?php echo esc_html($cart_data[$c-1]->pickup_source); ?>']").attr("selected","selected").trigger('chosen:updated');

																	<?php } ?>

																	let pax_num_<?php echo esc_html($c); ?> = $('#rezgo-pickup-select-<?php echo esc_html($c); ?>').data('pax');
																	let option_id_<?php echo esc_html($c); ?> = $('#rezgo-pickup-select-<?php echo esc_html($c); ?>').data('option');
																	let book_time_<?php echo esc_html($c); ?> = $('#rezgo-pickup-select-<?php echo esc_html($c); ?>').data('time');
																	let pickup_id_exists_<?php echo esc_html($c); ?> = decodeURIComponent( '<?php echo rawurlencode( (string) $cart_data[$c-1]->pickup ); ?>' ) + '<?php echo ($site->exists($cart_data[$c-1]->pickup_source)) ? "-".$cart_data[$c-1]->pickup_source :  ""?>';

																	$.ajax({
																		url: "<?php echo admin_url('admin-ajax.php'); ?>",
																		data: { 
																			action: 'rezgo',
																			rezgoAction: 'item',
																			method: 'pickup_ajax',
																			pickup_id: pickup_id_exists_<?php echo esc_html($c); ?>,
																			option_id: option_id_<?php echo esc_html($c); ?>,
																			book_time: book_time_<?php echo esc_html($c); ?>,
																			pax_num: pax_num_<?php echo esc_html($c); ?>, 
																		},
																		context: document.body,
																		success: function(data) {			
																			$('#rezgo-pickup-detail-<?php echo esc_html($c); ?>').fadeOut().html(data).fadeIn('fast'); 
																		}
																	});	
																<?php } ?>
																});	
															</script>

															<?php $form_counter++; ?>
															</div>

															<div class="outer-container" style="margin-bottom: -15px;">
																<div id="rezgo-pickup-detail-<?php echo esc_attr($c); ?>" class="rezgo-pickup-detail"></div>
															</div>

														</div>   
													<?php } ?>     
													<span class="rezgo-booking-memo rezgo-booking-memo-<?php echo esc_attr($item->uid); ?>"></span>

													<?php 
														// gather package price points
														foreach ($package[0]->prices->price as $package_price_point) { 
															$package_price_id = (int) $package_price_point->id; ?>

															<span id="package-label-<?php echo esc_attr($package_id.'-'.$cart_package_uid.'-main-'.$package_price_id); ?>" class="d-none"><?php echo (string) esc_html($package_price_point->label); ?></span>

															<script>
																jQuery(function($) {
																	// replace subsequent labels in package items with package price labels
																	setTimeout(() => {
																		$('.package-label-<?php echo esc_html($package_id.'-'.$cart_package_uid.'-sub-'.$package_price_id); ?>').text($('#package-label-<?php echo esc_html($package_id.'-'.$cart_package_uid.'-main-'.$package_price_id); ?>').text());
																	}, 150);
																});
															</script>

														<?php }
													?>
													
													<?php if($item->group != 'hide') { ?>

														<?php $price_label_count = 0;
															foreach($site->getTourPrices($item) as $price) { ?>

															<?php foreach($site->getTourPriceNum($price, $item) as $num) { ?>

																<div class="row rezgo-form-group rezgo-additional-info">

																	<div class="rezgo-sub-title form-sectional-header">
																		<span class="package-label-<?php echo esc_html($package_id.'-'.$cart_package_uid.'-sub-'.$price->id); ?>"><?php echo esc_html($price->label); ?></span> <span>(<?php echo esc_html($num); ?>)</span>
																	</div>

																<?php // create unique id for each entry
																$guest_uid = $c.'_'.$price->name.'_'.$num; ?>

																	<?php if ($first){ 
																		
																		// create unique package id to copy selection
																		$p_uid = $cart_package_uid.'_'.$num.'_'.$item->package_item_total.'_'.$price->name; 
																		$saved_first_name = $cart_data[$c-1]->tour_group->{$price->name}[$num-1]->first_name ?? '';
																		$saved_last_name = $cart_data[$c-1]->tour_group->{$price->name}[$num-1]->last_name ?? '';
																		$saved_phone = $cart_data[$c-1]->tour_group->{$price->name}[$num-1]->phone ?? '';
																		$saved_email = $cart_data[$c-1]->tour_group->{$price->name}[$num-1]->email ?? '';
																	?>

																		<div class="row rezgo-form-one form-group rezgo-pax-first-last rezgo-first-last-<?php echo esc_attr($item->uid); ?>">
																			<div class="col-sm-6 rezgo-form-input">
																				<label for="<?php echo esc_attr($p_uid).'_fname'; ?>" class="col-sm-2 control-label rezgo-label-right">
																					<span>First&nbsp;Name<?php if($item->group == 'require' || $item->group == 'require_name') { ?>&nbsp;<em class="fa fa-asterisk"></em><?php } ?></span>
																				</label>
																				<input type="text" 
																					class="form-control<?php echo ($item->group == 'require' || $item->group == 'require_name') ? ' required' : ''; ?> first_name_<?php echo esc_attr($c); ?>_<?php echo esc_attr($num); ?>" 
																					data-index="<?php echo ($c==1) ? 'fname_from_'.$num : 'fname_to_'.esc_attr($num); ?>" 
																					id="<?php echo esc_attr($p_uid).'_fname'; ?>" name="booking[<?php echo esc_attr($c); ?>][tour_group][<?php echo esc_attr($price->name); ?>][<?php echo esc_attr($num); ?>][first_name]" 
																					value="<?php echo esc_html($saved_first_name) ?>">

																				<?php 
																				// create multiple inputs for guest info for packages
																				for ($i=1; $i < (int)$item->package_item_total; $i++) { ?>
																					<input type="hidden" id="<?php echo esc_attr($cart_package_uid.'_'.$num.'_'.$i.'_'.$price->name).'_fname'?>" name="booking[<?php echo esc_attr($item->num + $i); ?>][tour_group][<?php echo esc_attr($price->name); ?>][<?php echo esc_attr($num); ?>][first_name]" value="<?php echo esc_html($saved_first_name) ?>">
																					<script>
																						jQuery(function($) {
																							$('#<?php echo esc_attr($p_uid).'_fname'?>').blur(function(){
																								$('#<?php echo esc_html($cart_package_uid.'_'.$num.'_'.$i.'_'.$price->name.'_fname'); ?>').val($(this).val());
																							});		
																						});																			
																					</script>
																				<?php } ?>

																			</div>

																			<div class="col-sm-6 rezgo-form-input">
																				<label for="<?php echo esc_attr($p_uid).'_lname'; ?>" class="col-sm-2 control-label rezgo-label-right">
																					<span>Last&nbsp;Name<?php if($item->group == 'require' || $item->group == 'require_name') { ?>&nbsp;<em class="fa fa-asterisk"></em><?php } ?></span>
																				</label>
																				<input type="text" 
																					class="form-control<?php echo ($item->group == 'require' || $item->group == 'require_name') ? ' required' : ''; ?> last_name_<?php echo esc_attr($c); ?>" 
																					data-index="<?php echo ($c==1) ? 'lname_from_'.$num : 'lname_to_'.esc_attr($num); ?>" 
																					id="<?php echo esc_attr($p_uid).'_lname'?>" name="booking[<?php echo esc_attr($c); ?>][tour_group][<?php echo esc_attr($price->name); ?>][<?php echo esc_attr($num); ?>][last_name]" 
																					value="<?php echo esc_html($saved_last_name); ?>">

																				<?php 
																				// create multiple inputs for guest info for packages
																				for ($i=1; $i < (int)$item->package_item_total; $i++) { ?>
																					<input type="hidden" id="<?php echo esc_attr($cart_package_uid.'_'.$num.'_'.$i.'_'.$price->name.'_lname'); ?>" name="booking[<?php echo esc_attr($item->num + $i); ?>][tour_group][<?php echo esc_attr($price->name); ?>][<?php echo esc_attr($num); ?>][last_name]" value="<?php echo esc_html($saved_last_name); ?>">
																					<script>
																						jQuery(function($) {
																							$('#<?php echo esc_attr($p_uid).'_lname'; ?>').blur(function(){
																								$('#<?php echo esc_html($cart_package_uid.'_'.$num.'_'.$i.'_'.$price->name.'_lname'); ?>').val($(this).val());
																							});		
																						});		
																					</script>
																				<?php } ?>

																			</div>
																		</div>

																		<?php if($item->group != 'request_name') { ?>
																		<div class="row rezgo-form-one form-group rezgo-pax-phone-email rezgo-phone-email-<?php echo esc_attr($item->uid); ?>">

																			<div class="col-sm-6 rezgo-form-input">
																				<label for="<?php echo esc_attr($p_uid).'_phone'?>" class="col-sm-2 control-label rezgo-label-right">Phone<?php if($item->group == 'require') { ?>&nbsp;<em class="fa fa-asterisk"></em><?php } ?></label>
																				<input type="text" 
																					class="form-control<?php echo ($item->group == 'require') ? ' required' : ''; ?>" 
																					data-index="<?php echo ($c==1) ? 'phone_from_'.$num : 'phone_to_'.$num; ?>" 
																					id="<?php echo esc_attr($p_uid).'_phone'?>" 
																					name="booking[<?php echo esc_attr($c); ?>][tour_group][<?php echo esc_attr($price->name); ?>][<?php echo esc_attr($num); ?>][phone]"
																					value="<?php echo esc_attr($saved_phone); ?>">

																				<?php 
																				// create multiple inputs for guest info for packages
																				for ($i=1; $i < (int)$item->package_item_total; $i++) { ?>
																					<input type="hidden" id="<?php echo esc_attr($cart_package_uid.'_'.$num.'_'.$i.'_'.$price->name.'_phone'); ?>" name="booking[<?php echo esc_attr($item->num + $i); ?>][tour_group][<?php echo esc_attr($price->name); ?>][<?php echo esc_attr($num); ?>][phone]" value="<?php echo esc_attr($saved_phone); ?>">
																					<script>
																						jQuery(function($) {
																							$('#<?php echo esc_attr($p_uid).'_phone'; ?>').blur(function(){
																								$('#<?php echo esc_html($cart_package_uid.'_'.$num.'_'.$i.'_'.$price->name.'_phone'); ?>').val($(this).val());
																							});			
																						});																			
																					</script>
																				<?php } ?>
																			</div>

																			<div class="col-sm-6 rezgo-form-input">
																				<label for="<?php echo esc_attr($p_uid).'_email'; ?>" class="col-sm-2 control-label rezgo-label-right">Email<?php if($item->group == 'require') { ?>&nbsp;<em class="fa fa-asterisk"></em><?php } ?></label>
																				<input type="email" 
																					class="form-control<?php echo ($item->group == 'require') ? ' required' : ''; ?>" 
																					data-index="<?php echo ($c==1) ? 'email_from_'.$num : 'email_to_'.$num; ?>" 
																					id="<?php echo esc_attr($p_uid).'_email'?>" 
																					name="booking[<?php echo esc_attr($c); ?>][tour_group][<?php echo esc_attr($price->name); ?>][<?php echo esc_attr($num); ?>][email]" 
																					value="<?php echo esc_attr($cart_data[$c-1]->tour_group->{$price->name}[$num-1]->email ?? ''); ?>">

																				<?php 
																				// create multiple inputs for guest info for packages
																				for ($i=1; $i < (int)$item->package_item_total; $i++) { ?>
																					<input type="hidden" id="<?php echo esc_attr($cart_package_uid.'_'.$num.'_'.$i.'_'.$price->name.'_email'); ?>" name="booking[<?php echo esc_attr($item->num + $i); ?>][tour_group][<?php echo esc_attr($price->name); ?>][<?php echo esc_attr($num); ?>][email]" value="<?php echo esc_attr($cart_data[$c-1]->tour_group->{$price->name}[$num-1]->email ?? ''); ?>">
																					<script>
																						jQuery(function($) {
																							$('#<?php echo esc_attr($p_uid).'_email'; ?>').blur(function(){
																								$('#<?php echo esc_html($cart_package_uid.'_'.$num.'_'.$i.'_'.$price->name.'_email'); ?>').val($(this).val());
																							});		
																						});																		
																					</script>
																				<?php } ?>
																			</div>
																		</div>
																		<?php } // end if($item->group != 'request_name') { ?>

																	<?php } // if ($first) ?>

																	<?php $form_counter = 1; // form counter to create unique IDs ?>

																	<?php 
																	
																	if (is_array($site->getTourForms('group'))) {
																		$cart_gf_val = [];
																		foreach( $site->getTourForms('group') as $form ) { 
																			// match form index key with form value (prevent mismatch if form is set to BE only)
																			$cart_gf[$c-1] = $cart_data[$c-1]->tour_group->{$price->name}[(int) $num-1]->forms->form ?? '';
																			if ($cart_gf[$c-1]){
																				foreach ($cart_gf[$c-1] as $k => $v) {
																					$cart_gf_val[$c-1][(int)$v->num.$guest_uid]['value'] = $v->value;
																				}
																			}
																		?>

																		<?php if($form->require) $required_fields++; ?>

																		<?php if($form->type == 'text') { ?>
																			
																			<div class="form-group rezgo-custom-form rezgo-form-input">
																				<label class="control-label"><?php echo esc_html($form->title); ?><?php if($form->require) { ?> <em class="fa fa-asterisk"></em><?php } ?></label>

																				<input 
																					id="text-<?php echo esc_attr($guest_uid); ?>" 
																					type="text" 
																					class="form-control<?php echo ($form->require) ? ' required' : ''; ?> " 
																					name="booking[<?php echo esc_attr($c); ?>][tour_group][<?php echo esc_attr($price->name); ?>][<?php echo esc_attr($num); ?>][forms][<?php echo esc_attr($form->id); ?>]" 
																					value="<?php echo esc_html($cart_gf_val[$c-1][(int)$form->id.$guest_uid]['value'] ?? ''); ?>">

																				<?php if ($site->exists($form->instructions)){ ?>
																					<p class="rezgo-form-comment"><span><?php echo wp_kses($form->instructions, ALLOWED_HTML); ?></span></p>
																				<?php } ?>
																			</div>
																		<?php } ?>

																		<?php if($form->type == 'select') { ?>
																			
																			<div class="form-group rezgo-custom-form rezgo-form-input">
																				<label class="control-label"><span><?php echo esc_attr($form->title); ?><?php if($form->require) { ?> <em class="fa fa-asterisk"></em><?php } ?></span></label>

																				<select 
																					id="select-<?php echo esc_attr($guest_uid); ?>_<?php echo esc_attr($form->id); ?>" 
																					class="chosen-select form-control<?php echo ($form->require) ? ' required' : ''; ?> rezgo-custom-select" 
																					name="booking[<?php echo esc_attr($c); ?>][tour_group][<?php echo esc_attr($price->name); ?>][<?php echo esc_attr($num ?? ''); ?>][forms][<?php echo esc_attr($form->id); ?>]">
																					<option value=""></option>
																					<?php foreach($form->options as $option) { ?>
																						<option><?php echo esc_html($option); ?></option>
																					<?php } ?>
																				</select>

																				<?php if ($site->exists($form->instructions)){ ?>
																					<p class="rezgo-form-comment"><span><?php echo wp_kses($form->instructions, ALLOWED_HTML); ?></span></p>
																				<?php } ?>
																				<?php if (isset($form->options_instructions)) {
																					$optex_count = 1;
																					foreach($form->options_instructions as $opt_extra) {
																						echo '<span class="opt_extra" id="optex_'.esc_attr($optex_count).'" style="display:none">'.esc_html($opt_extra).'</span>';
																						$optex_count++;
																					}
																				}
																				?>
																				<input type="hidden" value='' name="booking[<?php echo esc_attr($c); ?>][tour_group][<?php echo esc_attr($price->name); ?>][<?php echo esc_attr($num ?? ''); ?>][forms][<?php echo esc_attr($form->id); ?>]" data-addon="select_tour_group-<?php echo esc_attr($guest_uid); ?>_<?php echo esc_attr($form->id); ?>_hidden">
																			</div> 
																			<script>
																				jQuery(function($) {
																					$('#select-<?php echo esc_html($guest_uid); ?>_<?php echo esc_html($form->id); ?>').change(function(){
																						$(this).valid();

																						if ($(this).val() == ''){
																							$("input[data-addon='select_tour_group-<?php echo esc_html($guest_uid); ?>_<?php echo esc_html($form->id); ?>_hidden']").attr("disabled", false);
																						} else {
																							$("input[data-addon='select_tour_group-<?php echo esc_html($guest_uid); ?>_<?php echo esc_html($form->id); ?>_hidden']").attr("disabled", true);
																						}
																					});

																					let select_group_<?php echo esc_html($guest_uid); ?>_<?php echo esc_html($form->id); ?> = decodeURIComponent('<?php echo rawurlencode((string)addslashes(html_entity_decode($cart_gf_val[$c-1][(int)$form->id.$guest_uid]['value'] ?? '', ENT_QUOTES))); ?>');

																					if (select_group_<?php echo esc_html($guest_uid); ?>_<?php echo esc_html($form->id); ?> != ''){
																						$("input[data-addon='select_tour_group-<?php echo esc_html($guest_uid); ?>_<?php echo esc_html($form->id); ?>_hidden']").attr("disabled", true);
																						$('#select-<?php echo esc_html($guest_uid); ?>_<?php echo esc_html($form->id); ?>').val(select_group_<?php echo esc_html($guest_uid); ?>_<?php echo esc_html($form->id); ?>).trigger('chosen:updated');

																						let select_gf_options_<?php echo esc_html($guest_uid); ?>_<?php echo esc_html($form->id); ?> = document.getElementById('select-<?php echo esc_html($guest_uid); ?>_<?php echo esc_html($form->id); ?>').options;
																						for (i=0, len = select_gf_options_<?php echo esc_html($guest_uid); ?>_<?php echo esc_html($form->id); ?>.length; i<len; i++) {
																							let opt = select_gf_options_<?php echo esc_html($guest_uid); ?>_<?php echo esc_html($form->id); ?>[i];
																							if (opt.selected) {
																								$('#select-<?php echo esc_html($guest_uid); ?>_<?php echo esc_html($form->id); ?>').parent().find( '#optex_' + i + '.opt_extra' ).show();
																							} else {
																								$('#select-<?php echo esc_html($guest_uid); ?>_<?php echo esc_html($form->id); ?>').parent().find( '#optex_' + i + '.opt_extra' ).hide();
																							}
																						}
																					}
																				});
																				</script>
																		<?php } ?>

																		<?php if($form->type == 'multiselect') { ?>
																			
																			<div class="form-group rezgo-custom-form rezgo-form-input">
																				<label class="control-label"><span><?php echo esc_attr($form->title); ?><?php if($form->require) { ?> <em class="fa fa-asterisk"></em><?php } ?></span></label>
																				<select 
																					id="rezgo-custom-select-<?php echo esc_attr($guest_uid); ?>_<?php echo esc_attr($form->id); ?>" 
																					class="chosen-select form-control<?php echo ($form->require) ? ' required' : ''; ?> rezgo-custom-select" 
																					multiple="multiple" 
																					name="booking[<?php echo esc_attr($c); ?>][tour_group][<?php echo esc_attr($price->name); ?>][<?php echo esc_attr($num ?? ''); ?>][forms][<?php echo esc_attr($form->id); ?>][]">
																					<option value=""></option>
																					<?php foreach($form->options as $option) { ?>
																						<option><?php echo esc_html($option); ?></option>
																					<?php } ?>
																				</select>

																				<?php if ($site->exists($form->instructions)){ ?>
																					<p class="rezgo-form-comment"><span><?php echo wp_kses($form->instructions, ALLOWED_HTML); ?></span></p>
																				<?php } ?>
																				<?php if (isset($form->options_instructions)) {
																					$optex_count = 1;
																					foreach($form->options_instructions as $opt_extra) {
																						echo '<span class="opt_extra" id="optex_'.esc_attr($optex_count).'" style="display:none">'.esc_html($opt_extra).'</span>';
																						$optex_count++;
																					}
																				}
																				?>
																				<input type="hidden" value='' name="booking[<?php echo esc_attr($c); ?>][tour_group][<?php echo esc_attr($price->name); ?>][<?php echo esc_attr($num ?? ''); ?>][forms][<?php echo esc_attr($form->id); ?>][]" data-addon="multiselect_tour_group-<?php echo esc_attr($guest_uid); ?>_<?php echo esc_attr($form->id); ?>_hidden">
																			</div>
																			<script>
																				jQuery(function($) {
																					$('#rezgo-custom-select-<?php echo esc_html($guest_uid); ?>_<?php echo esc_html($form->id); ?>').change(function(){
																						$(this).valid();

																						if ($(this).val().length === 0){
																							$("input[data-addon='multiselect_tour_group-<?php echo esc_html($guest_uid); ?>_<?php echo esc_html($form->id); ?>_hidden']").attr("disabled", false);
																						} else {
																							$("input[data-addon='multiselect_tour_group-<?php echo esc_html($guest_uid); ?>_<?php echo esc_html($form->id); ?>_hidden']").attr("disabled", true);
																						}
																					});
																					let multiselect_group_<?php echo esc_html($guest_uid); ?>_<?php echo esc_html($form->id); ?> = '<?php echo addslashes(html_entity_decode($cart_gf_val[$c-1][(int)$form->id.$guest_uid]['value'] ?? '', ENT_QUOTES)); ?>';

																					if (multiselect_group_<?php echo esc_html($guest_uid); ?>_<?php echo esc_html($form->id); ?>.length){
																						$("input[data-addon='multiselect_tour_group-<?php echo esc_html($guest_uid); ?>_<?php echo esc_html($form->id); ?>_hidden']").attr("disabled", true);
																						multiselect_group_<?php echo esc_html($guest_uid); ?>_<?php echo esc_html($form->id); ?> = multiselect_group_<?php echo esc_html($guest_uid); ?>_<?php echo esc_html($form->id); ?>.split(', ');

																						$('#rezgo-custom-select-<?php echo esc_html($guest_uid); ?>_<?php echo esc_html($form->id); ?>').val(multiselect_group_<?php echo esc_html($guest_uid); ?>_<?php echo esc_html($form->id); ?>).trigger('chosen:updated');

																						let multiselect_gf_options_<?php echo esc_html($guest_uid); ?>_<?php echo esc_html($form->id); ?> = document.getElementById('rezgo-custom-select-<?php echo esc_html($guest_uid); ?>_<?php echo esc_html($form->id); ?>').options;
																						for (i=0, len = multiselect_gf_options_<?php echo esc_html($guest_uid); ?>_<?php echo esc_html($form->id); ?>.length; i<len; i++) {
																							let opt = multiselect_gf_options_<?php echo esc_html($guest_uid); ?>_<?php echo esc_html($form->id); ?>[i];
																							if (opt.selected) {
																								$('#rezgo-custom-select-<?php echo esc_html($guest_uid); ?>_<?php echo esc_html($form->id); ?>').parent().find( '#optex_' + i + '.opt_extra' ).show();
																							} else {
																								$('#rezgo-custom-select-<?php echo esc_html($guest_uid); ?>_<?php echo esc_html($form->id); ?>').parent().find( '#optex_' + i + '.opt_extra' ).hide();
																							}
																						}
																					}
																				});
																			</script>
																		<?php } ?>

																		<?php if($form->type == 'textarea') { ?>
																			<div class="form-group rezgo-custom-form rezgo-form-input">
																				<label class="control-label"><span><?php echo esc_attr($form->title); ?><?php if($form->require) { ?> <em class="fa fa-asterisk"></em><?php } ?></span></label>
																				<textarea 
																					class="form-control<?php echo ($form->require) ? ' required' : ''; ?>" 
																					name="booking[<?php echo esc_attr($c); ?>][tour_group][<?php echo esc_attr($price->name); ?>][<?php echo esc_attr($num ?? ''); ?>][forms][<?php echo esc_attr($form->id); ?>]" 
																					cols="40" 
																					rows="4"><?php echo esc_textarea($cart_gf_val[$c-1][(int)$form->id.$guest_uid]['value'] ?? ''); ?></textarea>
																				<?php if ($site->exists($form->instructions)){ ?>
																					<p class="rezgo-form-comment"><span><?php echo wp_kses($form->instructions, ALLOWED_HTML); ?></span></p>
																				<?php } ?>
																			</div>
																		<?php } ?> 

																		<?php if($form->type == 'checkbox') { ?>
																			
																			<?php // build unique identifier for checkbox 
																			$checkbox_uid = $c.'_'.$form->id.'_'.$num.'_'.$price->name; ?>

																			<div class="rezgo-pretty-checkbox-container">

																				<div class="rezgo-form-group rezgo-custom-form rezgo-form-input rezgo-pretty-checkbox">
																					<div class="pretty p-default p-curve p-smooth">

																						<input type="checkbox"<?php echo ($form->require) ? ' class="required"' : ''; ?> 
																							id="<?php echo esc_attr($form->id); ?>|<?php echo esc_attr(base64_encode($form->title)); ?>|<?php echo esc_attr($form->price); ?>|<?php echo esc_attr($c); ?>|<?php echo esc_attr($price->name ?? ''); ?>|<?php echo esc_attr($num ?? ''); ?>" 
																							name="booking[<?php echo esc_attr($c); ?>][tour_group][<?php echo esc_attr($price->name ?? ''); ?>][<?php echo esc_attr($num ?? ''); ?>][forms][<?php echo esc_attr($form->id); ?>]"
																							data-addon="checkbox_tour_group-<?php echo esc_attr($checkbox_uid); ?>">

																						<div class="state p-warning">
																							<label for="<?php echo esc_attr($form->id)."|".esc_attr(base64_encode($form->title))."|".esc_attr($form->price)."|".esc_attr($c)."|".esc_attr($price->name)."|".esc_attr($num ?? ''); ?>"><span><?php echo esc_attr($form->title); ?></span>
																							<?php if ($form->require) { ?> <em class="fa fa-asterisk"></em><?php } ?>
																							<?php if ($form->price) { ?> <em class="price"><?php echo esc_html($form->price_mod); ?> <?php echo esc_html($site->formatCurrency($form->price)); ?></em><?php } ?></label>
																						</div>

																						<input type='hidden' value='off' name="booking[<?php echo esc_attr($c); ?>][tour_group][<?php echo esc_attr($price->name); ?>][<?php echo esc_attr($num ?? ''); ?>][forms][<?php echo esc_attr($form->id); ?>]"  data-addon="checkbox_tour_group-<?php echo esc_attr($checkbox_uid); ?>_hidden">

																					</div>
																				</div>

																			<?php if ($site->exists($form->instructions)){ ?>
																				<p class="rezgo-form-comment"><span><?php echo wp_kses($form->instructions, ALLOWED_HTML); ?></span></p>
																			<?php } ?>

																				<script>
																				jQuery(function($) {
																					$("input[data-addon='checkbox_tour_group-<?php echo esc_html($checkbox_uid); ?>']").change(function(){
																						if ($(this).is(":checked")){
																							$("input[data-addon='checkbox_tour_group-<?php echo esc_html($checkbox_uid); ?>_hidden']").attr("disabled", true);
																						} else {
																							$("input[data-addon='checkbox_tour_group-<?php echo esc_html($checkbox_uid); ?>_hidden']").attr("disabled", false);
																						}
																					});

																					<?php if (isset($cart_gf_val[$c-1][(int)$form->id.$guest_uid]['value'] ) && $cart_gf_val[$c-1][(int)$form->id.$guest_uid]['value'] == 'on'){ ?>
																						$("input[data-addon='checkbox_tour_group-<?php echo esc_html($checkbox_uid); ?>']").prop('checked', true);
																						$("input[data-addon='checkbox_tour_group-<?php echo esc_html($checkbox_uid); ?>_hidden']").attr("disabled", true);
																					<?php } ?>
																					});
																				</script>

																			</div>
																		<?php } ?>
																		
																		<?php if($form->type == 'datepicker') { ?>
																			<div class="form-group rezgo-custom-form rezgo-form-input rezgo-datepicker-input">
																				<label class="control-label"><?php echo esc_html($form->title); ?><?php if($form->require) { ?> <em class="fa fa-asterisk"></em><?php } ?></label>

																				<input 
																					autocomplete="off"
																					id="datepicker-<?php echo esc_attr($guest_uid); ?>" 
																					type="text" class="datepicker-<?php echo esc_attr($guest_uid); ?> form-control<?php echo ($form->require) ? ' required' : ''; ?>" 
																					name="booking[<?php echo esc_attr($c); ?>][tour_group][<?php echo esc_attr($price->name); ?>][<?php echo esc_attr($num ?? ''); ?>][forms][<?php echo esc_attr($form->id); ?>]" 
																					value="<?php echo esc_attr($cart_gf_val[$c-1][(int)$form->id.$guest_uid]['value'] ?? ''); ?>">

																					<i class="fal fa-calendar-alt date-icon"></i>
																				
																					<?php if ($site->exists($form->instructions)){ ?>
																					<p class="rezgo-form-comment"><span><?php echo wp_kses($form->instructions, ALLOWED_HTML); ?></span></p>
																				<?php } ?>
																			</div>

																			<script>
																				jQuery(function($) {
																					$('.datepicker-<?php echo esc_attr($guest_uid); ?>').daterangepicker(
																						{	
																							startDate: moment(),
																							"singleDatePicker": true,
																							locale: {
																								format: '<?php echo $moment_date_format; ?>',
																								firstDay: <?php echo $first_date; ?>
																							}
																						},
																					);
																				});
																			</script>
																		<?php } ?>

																		<?php if($form->type == 'checkbox_price') { ?>
																			
																			<?php // build unique identifier for checkbox 
																			$checkbox_uid = $c.'_'.$form->id.'_'.$num.'_'.$price->name; ?>

																			<div class="rezgo-pretty-checkbox-container">
																				<div class="rezgo-form-group rezgo-custom-form rezgo-form-input rezgo-pretty-checkbox">
																					<div class="pretty p-default p-curve p-smooth">

																						<input type="checkbox"<?php echo ($form->require) ? ' class="required"' : ''; ?> 
																						id="<?php echo esc_attr($form->id); ?>|<?php echo esc_attr(base64_encode($form->title)); ?>|<?php echo esc_attr($form->price); ?>|<?php echo esc_attr($c); ?>|<?php echo esc_attr($price->name); ?>|<?php echo esc_attr($num); ?>" 
																						name="booking[<?php echo esc_attr($c); ?>][tour_group][<?php echo esc_attr($price->name); ?>][<?php echo esc_attr($num ?? ''); ?>][forms][<?php echo esc_attr($form->id); ?>]" 
																						data-addon="checkbox_price_tour_group-<?php echo esc_attr($checkbox_uid); ?>"> 

																						<div class="state p-warning">
																							<label for="<?php echo esc_attr($form->id); ?>|<?php echo esc_attr(base64_encode($form->title)); ?>|<?php echo esc_attr($form->price); ?>|<?php echo esc_attr($c); ?>|<?php echo esc_attr($price->name); ?>|<?php echo esc_attr($num ?? ''); ?>"><span><?php echo esc_attr($form->title); ?></span>
																							<?php if ($form->require) { ?> <em class="fa fa-asterisk"></em><?php } ?>
																							<?php if ($form->price) { ?> <em class="price"><?php echo esc_attr($form->price_mod); ?> <?php echo esc_html($site->formatCurrency($form->price)); ?></em><?php } ?></label>
																						</div>
																					</div>

																					<input type='hidden' value='off' name="booking[<?php echo esc_attr($c); ?>][tour_group][<?php echo esc_attr($price->name); ?>][<?php echo esc_attr($num); ?>][forms][<?php echo esc_attr($form->id); ?>]"  data-addon="checkbox_price_tour_group-<?php echo esc_attr($checkbox_uid); ?>_hidden">
																				</div>

																			<?php if ($site->exists($form->instructions)){ ?>
																				<p class="rezgo-form-comment"><span><?php echo wp_kses($form->instructions, ALLOWED_HTML); ?></span></p>
																			<?php } ?>

																				<script>
																				jQuery(function($) {
																					$("input[data-addon='checkbox_price_tour_group-<?php echo esc_html($checkbox_uid); ?>']").change(function(){
																						if ($(this).is(":checked")){
																							$("input[data-addon='checkbox_price_tour_group-<?php echo esc_html($checkbox_uid); ?>_hidden']").attr("disabled", true);
																						} else {
																							$("input[data-addon='checkbox_price_tour_group-<?php echo esc_html($checkbox_uid); ?>_hidden']").attr("disabled", false);
																						}
																					});

																					<?php if (isset($cart_gf_val[$c-1][(int)$form->id.$guest_uid]['value']) && $cart_gf_val[$c-1][(int)$form->id.$guest_uid]['value'] == 'on'){ ?>
																						$("input[data-addon='checkbox_price_tour_group-<?php echo esc_html($checkbox_uid); ?>']").prop('checked', true);
																						$("input[data-addon='checkbox_price_tour_group-<?php echo esc_html($checkbox_uid); ?>_hidden']").attr("disabled", true);
																					<?php } ?>
																					});
																				</script>
																			</div>
																		<?php } ?>

																		<?php if($form->type == 'select_price') { ?>

																			<div class="form-group rezgo-custom-form rezgo-form-input">
																				<label class="control-label"><span><?php echo esc_attr($form->title); ?><?php if($form->require) { ?> <em class="fa fa-asterisk"></em><?php } ?></span></label>

																				<select 
																					id="select-price-<?php echo esc_attr($guest_uid); ?>_<?php echo esc_attr($form->id); ?>" 
																					class="chosen-select form-control<?php echo ($form->require) ? ' required' : ''; ?> rezgo-custom-select" 
																					name="booking[<?php echo esc_attr($c); ?>][tour_group][<?php echo esc_attr($price->name); ?>][<?php echo esc_attr($num); ?>][forms][<?php echo esc_attr($form->id); ?>]">
																					<option value=""></option>
																					<?php $p = 0; foreach($form->options as $option) { ?>
																						<?php 
																							if(strpos($form->options_price[$p], '-') === false) {
																								$pre = '+';
																								$val = str_replace('+', '', $form->options_price[$p]);
																							} else {
																								$pre = '-';	
																								$val = str_replace('-', '', $form->options_price[$p]);
																							}
																							$val = $site->exists($val) ? $val : 0;
																							$select_price = '('.$pre.esc_html($site->formatCurrency($val)).')';
																						?>
																						<option value="<?php echo esc_html($option); ?>">
																							<?php echo esc_html($option); ?> <?php echo esc_html($select_price); ?>
																						</option>
																					<?php $p++; } unset($select_price); ?>
																				</select>

																				<?php if ($site->exists($form->instructions)){ ?>
																					<p class="rezgo-form-comment"><span><?php echo wp_kses($form->instructions, ALLOWED_HTML); ?></span></p>
																				<?php } ?>
																				<?php if (isset($form->options_instructions)) {
																					$optex_count = 1;
																					foreach($form->options_instructions as $opt_extra) {
																						echo '<span class="opt_extra" id="optex_'.esc_attr($optex_count).'" style="display:none">'.esc_html($opt_extra).'</span>';
																						$optex_count++;
																					}
																				}
																				?>
																				<input type="hidden" value='' name="booking[<?php echo esc_attr($c); ?>][tour_group][<?php echo esc_attr($price->name); ?>][<?php echo esc_attr($num); ?>][forms][<?php echo esc_attr($form->id); ?>]" data-addon="select_price_tour_group-<?php echo esc_attr($guest_uid); ?>_<?php echo esc_attr($form->id); ?>_hidden">
																			</div> 
																			<script>
																				jQuery(function($) {
																					$('#select-price-<?php echo esc_html($guest_uid); ?>_<?php echo esc_html($form->id); ?>').change(function(){
																						$(this).valid();

																						if ($(this).val() == ''){
																							$("input[data-addon='select_price_tour_group-<?php echo esc_html($guest_uid); ?>_<?php echo esc_html($form->id); ?>_hidden']").attr("disabled", false);
																						} else {
																							$("input[data-addon='select_price_tour_group-<?php echo esc_html($guest_uid); ?>_<?php echo esc_html($form->id); ?>_hidden']").attr("disabled", true);
																						}
																					});

																					let select_price_group_<?php echo esc_html($guest_uid); ?>_<?php echo esc_html($form->id); ?> = decodeURIComponent('<?php echo rawurlencode((string)addslashes(html_entity_decode($cart_gf_val[$c-1][(int)$form->id.$guest_uid]['value'] ?? '', ENT_QUOTES))); ?>');

																					if (select_price_group_<?php echo esc_html($guest_uid); ?>_<?php echo esc_html($form->id); ?> != ''){
																						$("input[data-addon='select_price_tour_group-<?php echo esc_html($guest_uid); ?>_<?php echo esc_html($form->id); ?>_hidden']").attr("disabled", true);
																						$('#select-price-<?php echo esc_html($guest_uid); ?>_<?php echo esc_html($form->id); ?>').val(select_price_group_<?php echo esc_html($guest_uid); ?>_<?php echo esc_html($form->id); ?>).trigger('chosen:updated');

																						let select_gf_options_<?php echo esc_html($guest_uid); ?>_<?php echo esc_html($form->id); ?> = document.getElementById('select-price-<?php echo esc_html($guest_uid); ?>_<?php echo esc_html($form->id); ?>').options;
																						for (i=0, len = select_gf_options_<?php echo esc_html($guest_uid); ?>_<?php echo esc_html($form->id); ?>.length; i<len; i++) {
																							let opt = select_gf_options_<?php echo esc_html($guest_uid); ?>_<?php echo esc_html($form->id); ?>[i];
																							if (opt.selected) {
																								$('#select-price-<?php echo esc_html($guest_uid); ?>_<?php echo esc_html($form->id); ?>').parent().find( '#optex_' + i + '.opt_extra' ).show();
																							} else {
																								$('#select-price-<?php echo esc_html($guest_uid); ?>_<?php echo esc_html($form->id); ?>').parent().find( '#optex_' + i + '.opt_extra' ).hide();
																							}
																						}
																					}
																				});
																			</script>
																		<?php } ?>
																		<?php $form_counter++; ?>
																	<?php } // end foreach( $site->getTourForms('group') as $form ) ?>
																<?php } // end is_array( $site->getTourForms('group') ) ?>
																</div> <!-- row rezgo-form-group rezgo-additional-info -->
															<?php $price_label_count++; } // end foreach($site->getTourPriceNum($price, $item) as $num) ?>
														<?php } // end foreach ($site->getTourPrices($item) as $price) ?>
													<?php } // end ($item->group != 'hide') ?>
															
														</div>

														<?php if ($last) {
															$item_count++;
															echo '<hr>';
														} ?>

													</div> <!-- // rezgo-book-step-one-item -->

												<?php } else { ?>
												
												<div id="rezgo-book-step-one-item-<?php echo esc_attr($item->uid); ?>">

													<div class="rezgo-booking-title-wrp">
														<h3 class="rezgo-booking-title rezgo-sub-title" id="booking_title_<?php echo esc_html($c); ?>">

														<span>Booking <?php echo esc_html($item_count); ?> of <?php echo esc_html($cart_count); ?></span>

														</h3>
														<h3 class="rezgo-item-title"><?php echo esc_html($item->item); ?> &mdash; <?php echo esc_html($item->option); ?></h3>

														<?php $data_book_date = date("Y-m-d", (int)$this->cart_data[$c-1]->date); ?>

														<label>
															<?php if(in_array((string) $item->date_selection, DATE_TYPES)) { ?>
																<span>Date: </span>
																<span class="lead"><?php echo esc_html(date((string) $company->date_format, (int) $item->booking_date)); ?></span>
																<?php if ($site->exists($item->time)){ ?>
																	at <?php echo (string) esc_html($item->time); ?>
																<?php } ?> 
															<?php } else { ?>
																<label><span class="lead"> Open Availability </span></label>
															<?php } ?>
														</label>

												<?php if($item->discount_rules->rule) {
													echo '<br><label class="rezgo-booking-discount">
													<span class="rezgo-discount-span">Discount:</span> '; 
 													$discount_string = '';
													foreach($item->discount_rules->rule as $discount) {	
														$discount_string .= ($discount_string) ? ', '.$discount : $discount;
													}
													echo '<span class="rezgo-promo-code-desc">'.esc_html($discount_string).'</span>
													</label>';
												} ?>
												
												<input type="hidden" name="booking[<?php echo esc_attr($c); ?>][index]" value="<?php echo esc_attr($c-1); ?>">
												<input type="hidden" name="booking[<?php echo esc_attr($c); ?>][uid]" value="<?php echo esc_attr($item->uid); ?>">
												<input type="hidden" name="booking[<?php echo esc_attr($c); ?>][date]" value="<?php echo esc_attr($data_book_date); ?>">
											</div>

												<div class="row rezgo-booking-instructions">
													<span> To complete this booking, please fill out the following form.</span>

													<?php 
														if (is_array($site->getTourForms('primary'))) {
															$required_fields = 0;
															foreach($site->getTourForms('primary') as $form) { 
																if(isset($form->require)) {
																	$required_fields++;
																	$primary_required_fields = array (
																		$c => $required_fields
																	);
																}
															}
														}
														if (is_array($site->getTourForms('group'))) {
															$required_fields = 0;
															foreach($site->getTourForms('group') as $form) { 
																if(isset($form->require)) {
																	$required_fields++;
																	$group_required_fields = array (
																		$c => $required_fields
																	);
																}
															}
														}
													?>

													<span <?php if($item->group == 'require' || $item->group == 'require_name' || isset($primary_required_fields[$c]) || isset($group_required_fields[$c]))  { echo ' style="display:inline-block;"'; } else { echo ' style="display:none;"'; } ?>>
														<span id="required_note-<?php echo esc_html($c); ?>" >Please note that fields marked with <em class="fa fa-asterisk"></em> are required.</span>
													</span>
												</div>

												<?php include('booking_forms_primary.php'); ?>

											<?php if($required_fields > 0) { ?>
												<script>jQuery(document).ready(function($){$('#required_note-<?php echo $c; ?>').fadeIn();});</script>
											<?php } ?>
						
											<?php if ($item->pick_up_locations) { ?>
												<div class="row rezgo-form-group rezgo-additional-info">
												<div class="col-sm-12 rezgo-sub-title form-sectional-header rezgo-book-transportation">
													<span>Transportation</span>
												</div>

												<div class="clearfix rezgo-short-clearfix">&nbsp;</div>
												
												<?php $pickup_locations = $site->getPickupList((int) $item->uid); ?>
												
												<div class="form-group rezgo-custom-form rezgo-form-input">
															
													<label id="rezgo-choose-pickup"><span>Choose your pickup location</span><?php if(!empty($pickup_locations->required)) { ?>&nbsp;<em class="fa fa-asterisk"></em><?php } ?></label>
													<select id="rezgo-pickup-select-<?php echo esc_attr($c); ?>" class="chosen-select form-control rezgo-pickup-select <?php echo (!empty($pickup_locations->required)) ? ' required' : ''; ?>" name="booking[<?php echo esc_attr($c); ?>][pickup]" data-target="rezgo-pickup-detail-<?php echo esc_attr($c); ?>" data-id="<?php echo esc_attr($c); ?>" data-counter="<?php echo esc_attr($form_counter); ?>" data-option="<?php echo esc_attr($item->uid); ?>" data-pax="<?php echo esc_attr($item->pax); ?>" data-time="<?php echo esc_attr($item->time); ?>">
													<option value="" data-cost="0" id="last-picked-<?php echo esc_attr($c); ?>"></option>
														<?php
															
															foreach($pickup_locations->pickup as $pickup) {
																
																$cost = ((int) $pickup->cost > 0) ? ' ('.$site->formatCurrency($pickup->cost).')' : ''; 
														
																if($pickup->sources) { 
																
																	echo '<optgroup label="Pickup At: '.esc_html($pickup->name).' - '.esc_html($pickup->location_address).esc_html($cost).'">'."\n";
																		
																	$s=0;
																	foreach($pickup->sources->source as $source) {
																		echo '<option value="'.esc_html($pickup->id).'-'.esc_html($s).'" data-cost="'.($item->pax*$pickup->cost).'">'.esc_html($source->name).'</option>'."\n";
																		$s++;
																	}
																	echo '</optgroup>'."\n";
																	
																} else { 
																	echo '<option value="'.esc_html($pickup->id).'" data-cost="'.esc_attr($item->pax*$pickup->cost).'">'.esc_html($pickup->name).' - '.esc_html($pickup->location_address.$cost).'</option>'."\n";
																} 
																
															}
														
														?>
													</select>
													<script>
														jQuery(function($) {
															// needed for deselect option?
															$("#rezgo-pickup-select-<?php echo esc_html($c); ?>").chosen({allow_single_deselect:true});

															$("#rezgo-pickup-select-<?php echo esc_html($c); ?>").chosen().change(function() {
																$(this).valid();
																pickup_cost_<?php echo esc_html($c); ?> = $(this).find('option:selected').data('cost');
																pickup_id_<?php echo esc_html($c); ?> = $(this).val();

																if ($(this).find('option:selected').data('cost') != 0){
																	$('#last-picked-<?php echo esc_html($c); ?>').data('cost', pickup_cost_<?php echo esc_html($c); ?>*-1);
																}
																else{
																	pickup_cost_<?php echo esc_html($c); ?> = $('#last-picked-<?php echo esc_html($c); ?>').data('cost');
																}
															});

															// if there is existing pickup
															<?php if ( ($site->exists($cart_data[$c-1]->pickup)) && ($cart_data[$c-1]->pickup !=0) ){ ?>

																<?php if (!$site->exists($cart_data[$c-1]->pickup_source)) { ?> 

																	$('#rezgo-pickup-select-<?php echo esc_html($c); ?>').val(<?php echo esc_html($cart_data[$c-1]->pickup); ?>).trigger('chosen:updated');

																<?php } else { ?>

																	// if there is pickup source, trigger the optgroup select
																	$("#rezgo-pickup-select-<?php echo esc_html($c); ?> optgroup option[value='<?php echo esc_html($cart_data[$c-1]->pickup); ?>-<?php echo esc_html($cart_data[$c-1]->pickup_source); ?>']").attr("selected","selected").trigger('chosen:updated');

																<?php } ?>

																let pax_num_<?php echo esc_html($c); ?> = $('#rezgo-pickup-select-<?php echo esc_html($c); ?>').data('pax');
																let option_id_<?php echo esc_html($c); ?> = $('#rezgo-pickup-select-<?php echo esc_html($c); ?>').data('option');
																let book_time_<?php echo esc_html($c); ?> = $('#rezgo-pickup-select-<?php echo esc_html($c); ?>').data('time');
																let pickup_id_exists_<?php echo esc_html($c); ?> = '<?php echo esc_html($cart_data[$c-1]->pickup); ?>' + '<?php echo ($site->exists($cart_data[$c-1]->pickup_source)) ? "-".esc_html($cart_data[$c-1]->pickup_source) :  ""?>';

																$.ajax({
																	url: "<?php echo admin_url('admin-ajax.php'); ?>" + '?action=rezgo&method=pickup_ajax&pickup_id=' + pickup_id_exists_<?php echo esc_html($c); ?> + '&option_id=' + option_id_<?php echo esc_html($c); ?> + '&book_time=' + book_time_<?php echo esc_html($c); ?> + '&pax_num=' + pax_num_<?php echo esc_html($c); ?> + '', 
																	data: { rezgoAction: 'item'},
																	context: document.body,
																	success: function(data) {			
																		$('#rezgo-pickup-detail-<?php echo esc_html($c); ?>').fadeOut().html(data).fadeIn('fast'); 
																	}
																});	
															<?php } ?>
														});
													</script>

													<?php $form_counter++; ?>
													</div>

													<div class="outer-container" style="margin-bottom: -15px;">
														<div id="rezgo-pickup-detail-<?php echo esc_attr($c); ?>" class="rezgo-pickup-detail"></div>
													</div>

												</div>   
											<?php } ?>                                  
											
											<span class="rezgo-booking-memo rezgo-booking-memo-<?php echo esc_attr($item->uid); ?>"></span>

											<?php if($item->group != 'hide') { ?>

												<?php include ('booking_forms_group.php'); ?>

											<?php } // end ($item->group != 'hide') ?>
									</div><!-- // rezgo-book-step-one-item -->
									<hr>

									<?php $item_count++; } // end if ($site->exists($item->package_item_total)) ?>

									<?php } else { $cart_count--; } ?>
								<?php } // end cart loop for each tour in the order ?>

								<div id="rezgo-bottom-cta">
									<button id="rezgo-book-step-one-previous-bottom" class="btn btn-lg btn-block rezgo-book-step-one-previous-bottom" type="button" onclick="<?php echo LOCATION_WINDOW; ?>.location.href='<?php echo $site->base; ?>/order';">
										<span>Back to Order</span>
										</button>
									<span class="btn-check"></span>
									<button class="btn rezgo-btn-book btn-lg btn-block rezgo-book-step-one-continue-bottom" type="submit" form="rezgo-guest-form">
										<span>Continue to Payment</span>
									</button>
								</div>

								<script>
									jQuery(function($) {
										// switch up error message placement for smaller screen sizes on guest info page -->
										let append_count = 0;
										let bottom_error_msg = 
												'<div id="rezgo-book-errors" class="alert alert-danger rezgo-book-errors-bottom">' +
													'<span>Some required fields are missing. Please complete the highlighted fields.</span>' +
												'</div>';

										window.onload = (event) => {
											let width = this.innerWidth;
											if (width < 992){
												$('.rezgo-book-errors-side').remove();
												if (append_count == 0){
													$('#rezgo-bottom-cta').after(bottom_error_msg);
												}
											}
										};
										
										$(window).resize(function() {
											let width = this.innerWidth;
											if (width < 992){
												$('.rezgo-book-errors-side').remove();
												if (append_count == 0){
													$('#rezgo-bottom-cta').after(bottom_error_msg);
												}
											}
										});
									});
								</script>	
								<?php } ?>
								
							</form>

							</div> <!-- pax-info-container -->

								<?php if($cart) { ?>
									<!-- FIXED CART -->
									<?php require('fixed_cart.php'); ?>
								<?php } ?> 
								
							</div> <!-- flex-container -->
							
							<?php if (DEBUG) { ?>
								<div id="debug_container" class="text-center" style='display:none;'>
									<p> DEBUG API REQUEST </p>
									<textarea id="api_request_debug" readonly="readonly" rows="10"></textarea>
									<hr>
									<button id="api_send_request" class="btn btn-default" >Send Request</button>
								</div>
								
								<script>
									jQuery(function($) {

										$('#api_send_request').click(function(e){
											e.preventDefault();

											$('#rezgo-guest-form').ajaxSubmit({
												type: 'POST',
												url: '<?php echo admin_url('admin-ajax.php'); ?>' + '?action=rezgo&method=book_ajax',
												data: { rezgoAction: 'book_step_one' },
												success: function(data){
													alert('Request Sent')
												},
												error: function(error) {
													console.log(error);
												}
											});
										});
									});
								</script>
							<?php } ?>

						</div><!-- // #book_step_one -->

					<script>
						jQuery(document).ready(function($){

							// remove empty 'rezgo-additional-info' divs with no forms
							$('.rezgo-additional-info').each(function(){
								if ($(this).children().length === 1){
									$(this).remove();
								}
							})

							// switch up the btn ids 
							$(window).resize(function() {
								let width = this.innerWidth;
								if (width < 992){
									$(".rezgo-book-step-one-previous-bottom").prop("id", "rezgo-book-step-one-btn-previous");
									$(".rezgo-book-step-one-continue-bottom").prop("id", "rezgo-book-step-one-btn-continue");

								}
							});

							$(".chosen-select").chosen( { width: "100%", allow_single_deselect: true, disable_search_threshold: 10} );

							$('.rezgo-custom-select').chosen().change( function() {

								var parent = $(this).parent();
								var chosen_options = this && this.options;
								var opt;

								for (var i=0, len=chosen_options.length; i<len; i++) {

									opt = chosen_options[i];

									if (opt.selected) {
										parent.find( '#optex_' + i + '.opt_extra' ).show();
									} else {
										parent.find( '#optex_' + i + '.opt_extra' ).hide();
									}
								}
							});

							// Chosen.js touch support on mobile
							if ($('.chosen-select').length > 0) {
								$('.chosen-select').on('touchstart', function(e){
									e.stopPropagation(); e.preventDefault();
									// Trigger the mousedown event.
									$(this).trigger('mousedown');
								});
							}

							$('.rezgo-custom-select, .rezgo-pickup-select').on("chosen:hiding_dropdown", function(e){ 
								$('.chosen-container').css('pointer-events', 'none');
								$('.chosen-choices').css('pointer-events', 'none');
								$('#rezgo-bottom-cta').css('pointer-events', 'none');

								setTimeout(() => {
									$('.chosen-container').css('pointer-events', 'auto');
									$('.chosen-choices').css('pointer-events', 'auto');
									$('#rezgo-bottom-cta').css('pointer-events', 'auto');
								}, 150);
							});

							$('.rezgo-pickup-select').change(function () {
								$(this).valid();
								var pickup_id = $(this).val();
								var pickup_target = $(this).data('target');
								var count = $(this).data('counter');
								var book_time = $(this).data('time');
								var option_id = $(this).data('option');
								var pax_num = $(this).data('pax');
								
								if (pickup_id) {
									// wait animation
									$('#' + pickup_target).html('<div class="rezgo-pickup-loading"></div>');

									$.ajax({
										url: "<?php echo admin_url('admin-ajax.php'); ?>" + '?action=rezgo&method=pickup_ajax&pickup_id=' + pickup_id + '&option_id=' + option_id + '&book_time=' + book_time + '&pax_num=' + pax_num + '', 
										data: { rezgoAction: 'item'},
										context: document.body,
										success: function(data) {			
											$('#' + pickup_target).fadeOut().html(data).fadeIn('fast'); 
										}
									});	
								
								} else {
									$('#' + pickup_target).html('');
								}
							
							});

							function error_booking() {
								$('#rezgo-book-errors').fadeIn();
								append_count = 1;

								setTimeout(function () {
									$('#rezgo-book-errors').fadeOut();
								}, 5000);
								return false;
							}

							function submit_guest_form() {
								var validate_check = validate_form();

								if(!validate_check) {
									return error_booking();
								} else {

									<?php if (DEBUG){ ?> 

										// show debug window with update request
										$('#rezgo-guest-form').ajaxSubmit({
											type: 'POST',
											url: '<?php echo admin_url('admin-ajax.php'); ?>' + '?action=rezgo&method=book_ajax',
											data: { rezgoAction: 'update_debug', },
											success: function(data){
												console.log(data);
												$('#debug_container').show();
												$('#api_request_debug').html(data);
											},
											error: function(error) {
												console.log(error);
											}
										});

									<?php } else { ?>

										$('#rezgo-guest-form').ajaxSubmit({
											type: 'POST',
											url: '<?php echo admin_url('admin-ajax.php'); ?>' + '?action=rezgo&method=book_ajax',
											data: { rezgoAction: 'book_step_one', },
											success: function(data){
												<?php echo LOCATION_WINDOW; ?>.location.href = decodeURIComponent( '<?php echo rawurlencode( (string) $site->base ); ?>' ) +'/confirm';
											},
											error: function(error) {
												console.log(error);
											}
										});

									<?php } ?>

								}
							}
							// Validation Setup
							$.validator.setDefaults({
								highlight: function(element) {
									if ($(element).attr("type") == "checkbox") {
										$(element).closest('.rezgo-pretty-checkbox-container').addClass('has-error');
									} else if ($(element).hasClass("chosen-select")) {
										// for chosen hidden select inputs
										$(element).parent().find('.chosen-single').addClass('has-error');
										$(element).parent().find('.chosen-choices').addClass('has-error');
									} else {	
										$(element).closest('.rezgo-form-input').addClass('has-error');
									}
									$(element).closest('.form-group').addClass('has-error');

								},
								unhighlight: function(element) {
									if ( $(element).attr("type") == "checkbox" ) {
										$(element).closest('.rezgo-form-checkbox').removeClass('has-error');
									} else if ($(element).is(":hidden")) {
										// for chosen hidden select inputs
										$(element).parent().find('.chosen-single').removeClass('has-error');
										$(element).parent().find('.chosen-choices').removeClass('has-error');
									} else {
										$(element).closest('.rezgo-form-input').removeClass('has-error');
									}
									$(element).closest('.form-group').removeClass('has-error');
								},
								focusInvalid: false,
								errorElement: 'span',
								errorClass: 'help-block',
								ignore: ":hidden:not(.chosen-select)",
								errorPlacement: function(error, element) {
									if ($(element).attr("type") == "checkbox") {
										error.insertAfter(element.parent().parent());
									} else if (element.is(":hidden")) {
										// for chosen hidden select inputs
										error.insertAfter(element.parent().find('.chosen-container'));
									} else {
										error.insertAfter(element);
									}
								}
							});

							$('#rezgo-guest-form').validate({
								messages: {
									lead_passenger_first_name: {
										required: "Enter your first name"
									},
									lead_passenger_last_name: {
										required: "Enter your last name"
									},
									lead_passenger_email: {
										required: "Enter your email"
									},
								}
							});

							function validate_form() {
								var valid = $('#rezgo-guest-form').valid();
								return valid;
							}
							
							// Catch form submissions
							$('#rezgo-guest-form').submit(function(e) {
								e.preventDefault();
								submit_guest_form();
							});
							
						});
					</script>

	</div> <!-- // rezgo-book-wrp --> 

<style>#debug_response {width:100%; height:200px;}</style>
<style>#debug_container {width:50%; margin:30px auto;} #debug_container p{margin-bottom: 15px;font-size: 1.5rem; font-weight: 200;}</style>
<style>#api_request_debug {width:100%; height:200px;}</style>