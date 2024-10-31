<?php

	$availability_title = '';	
	$buy_as_gift = $site->showGiftCardPurchase() ? 1 : 0;
	$analytics_ga4 = $site->exists($site->getAnalyticsGa4()) ? 1 : 0;
	$analytics_gtm = $site->exists($site->getAnalyticsGtm()) ? 1 : 0;
	$meta_pixel = $site->exists($site->getMetaPixel()) ? 1 : 0;
	$date = sanitize_text_field($_REQUEST['date'] ?? '');
	$id = sanitize_text_field($_REQUEST['id'] ?? '');
	$parent_url = sanitize_text_field($_REQUEST['parent_url']);
	$time_format = sanitize_text_field((string)$_REQUEST['time_format'] . ' hours');

	if (REZGO_WORDPRESS) $site->setTimeZone();

	if (isset($_REQUEST['option_num'])) {
		$option_num = sanitize_text_field($_REQUEST['option_num'] ?? '');
	} else {
		$option_num = 1;	
		
		if ($_REQUEST['type'] != 'open') {
			$now = time();
			$offset_time_now = strtotime($time_format, $now);

			$selected_date = date('Y-m-d', strtotime($date));
			$available_day = date('D', strtotime($date));
			$available_date = date((string) $_REQUEST['date_format'] ?? '', strtotime($date)); 

			$availability_title = '<div class="rezgo-date-options" style="display:none;"><span class="rezgo-calendar-avail"><span>Availability&nbsp;for: </span></span> <strong><span class="rezgo-avail-day">'.$available_day.',&nbsp;</span><span class="rezgo-avail-date">'.$available_date.'</span></strong>';

			if ($_REQUEST['js_timestamp']) {
				$js_timestamp = sanitize_text_field($_REQUEST['js_timestamp']);
				$today = date('Y-m-d', $js_timestamp);
			}
      
		if($today !== $selected_date) {
			$date_diff = $site->getCalendarDiff($today, $selected_date);
			$date_diff = ($date_diff=='1 day') ? 'Tomorrow' : $date_diff . ' from today';
			$availability_title .= '<strong class="rezgo-calendar-diff"><span>('.$date_diff.')</span></strong>';
		} else {
			$availability_title .= '<strong class="rezgo-calendar-diff"><span>(Today)</span></strong>';
		}

			$availability_title .= '</div>';
		}
	}

	if ($_REQUEST['date'] != 'open') {
		$date_request = '&d='.$date;
	} else {
		$date_request = '';
	}

	$options = $site->getTours('t=com&q='.sanitize_text_field($_REQUEST['com']).$date_request.'&a=subtract_cart_availability&file=calendar_day');

?>

<?php if ($options) { ?>
	<?php echo wp_kses($availability_title, ALLOWED_HTML); ?>

<?php if (isset($_REQUEST['cross_sell'])) { ?>
	<script>
		jQuery(document).ready(function($){
			$('.panel-collapse').on('shown.bs.collapse', function () {
				
				let panel_offset = $(this).closest('.panel');
				
				$('html, body').animate({
						scrollTop: $(panel_offset).offset().top
				}, 500);		
				
			})
		});
</script>
<?php } ?>

	<span class="rezgo-date-memo rezgo-calendar-date-<?php echo esc_attr($date); ?>"></span>

	<div class="panel-group" id="rezgo-select-option-<?php echo esc_attr($option_num); ?>">
		<?php if (count($options) != 1) { // && $option_num != 1 ?>
			<span class="rezgo-choose-options">Choose one of the options below <i class="fal fa-angle-double-down"></i></span>
		<?php }

		if ($_REQUEST['type'] == 'open') {
			$sub_option = 'o1';
		} else {
			$sub_option = 'a';
		}
		
		foreach($options as $option) { ?>

			<?php $dynamic_start_time = ((string)$option->time_format == 'dynamic') ? 1 : 0;
            $minimum_required_pax = $dynamic_start_time ? $option->default_min_guests : $option->per;
            ?>

			<?php $site->readItem($option);
		
				// hide if block size exceeds availability
				if ( (int) $option->date->availability >= (int) $option->block_size || !$option->block_size) {
					$block_unclass = '';
					$block_available = TRUE;
				} else {
					$block_unclass = ' block-unavailable';
					$block_available = FALSE;
					$option->date->availability = 0;
				}

				// hide unavailable options
				if ($option->date->availability == 0) {
					$panel_unclass = ' panel-unavailable';
				} else {
					$panel_unclass = '';
				}
			
				// don't mix open options with calendar options
				// only return options that match the request type
				if ((($_REQUEST['type'] == 'calendar' || $_REQUEST['type'] == 'single') && (string) $option->date['value'] != 'open') 
					|| ($_REQUEST['type'] == 'open' && (string) $option->date['value'] == 'open' )
				) { ?>
					<div class="panel panel-default<?php echo esc_attr($panel_unclass.$block_unclass); ?>">
						<script>
							var fields_<?php echo esc_html($option_num.'_'.$sub_option); ?> = new Array();
							var required_num_<?php echo esc_html($option_num.'_'.$sub_option); ?> = 0;
							var price_av_obj_<?php echo esc_html($option_num.'_'.$sub_option); ?> = {};

						jQuery(function($){
	
							isInt = function(n) {
								 return n % 1 === 0;
							}

						// buy as a gift redirect
						buy_as_gift_<?php echo esc_html($option_num.'_'.$sub_option); ?> = function(){
							let pax_array = [
								running_pax_<?php echo esc_html($option_num.'_'.$sub_option); ?>,
								<?php 
									$bundles = $site->getTourBundles($option);
									if (count($bundles) > 0) {
										$d = 0;	
										foreach ($bundles as $bundle) {
											if ((int) $bundle->visible !== 0 && $option->date->availability >= $bundle->total) { ?>
												running_pax_bundle_<?php echo esc_html($option_num.'_'.$sub_option.'_'.$d); ?> ? running_pax_bundle_<?php echo esc_html($option_num.'_'.$sub_option.'_'.$d); ?> : '',
										<?php 
										$d++; }
										}
									} 
								?>
							];

							// merge pax and bundles if applicable
							let deepMergeSum = (obj1, obj2) => {
								return Object.keys(obj1).reduce((acc, key) => {
									if (typeof obj2[key] === 'object') {
									acc[key] = deepMergeSum(obj1[key], obj2[key]);
									} else if (obj2.hasOwnProperty(key) && !isNaN(parseFloat(obj2[key]))) {
									acc[key] = obj1[key] + obj2[key]
									}
									return acc;
								}, {});
							};
							let result = pax_array.reduce((acc, obj) => acc = deepMergeSum(acc, obj));

							let gift_pax = '';
							let counter = 0;
							for (const [pax, amount] of Object.entries(result)) {
								if (amount > 0) { 
									gift_pax += (counter == 0 ? '?' : '&')+pax+'='+amount;
									counter++;
								}
							}
							<?php echo LOCATION_WINDOW; ?>.location.href='<?php echo esc_html($parent_url); ?>/gift-card/' + '<?php echo esc_html($option->uid); ?>/' + '<?php echo esc_html($option->date->value); ?>/' + gift_pax;

							return false;
						}
	
						// validate form data
						check_<?php echo esc_html($option_num.'_'.$sub_option); ?> = function() {

							var err;
							var count_<?php echo esc_html($option_num.'_'.$sub_option); ?> = 0;
							var required_<?php echo esc_html($option_num.'_'.$sub_option); ?> = 0;
							add_to_cart_total = 0;
	
							for(v in fields_<?php echo $option_num.'_'.$sub_option; ?>) {

								if ($('#' + v).attr('rel') != 'bundle' && $('#' + v).val() >= 1) {
									add_to_cart_total += parseInt($('#' + v).val()) * parseFloat($('#' + v + '_price').data('price'));
								}

								if ($('#' + v).attr('rel') == 'bundle' && $('#' + v).val() >= 1) {
									add_to_cart_total += parseInt($('#' + v).val()) * parseFloat($('#' + v + '_price').data('price'));
									$('.' + v).each(function() {
										let multiple = $(this).data('multiple');
										let val = $('#' + v).val();
										let newval = multiple * val;
										let rel = $(this).attr('rel');
										
										count_<?php echo esc_html($option_num.'_'.$sub_option); ?> += newval; // increment total
										
										if(fields_<?php echo esc_html($option_num.'_'.$sub_option); ?>[rel]) { required_<?php echo esc_html($option_num.'_'.$sub_option); ?> = 1; }
											
										if((count_<?php echo esc_html($option_num.'_'.$sub_option); ?> <= <?php echo esc_html($option->date->availability); ?>) && (count_<?php echo esc_html($option_num.'_'.$sub_option); ?> <= 150)) {
											$(this).attr('disabled', false).val(newval);
										}
										
									});										
								
								} else {
									if(jQuery('#' + v).val()) count_<?php echo esc_html($option_num.'_'.$sub_option); ?> += jQuery('#' + v).val() * 1; // increment total
								}
								
								// has a required price point been used
								if(fields_<?php echo esc_html($option_num.'_'.$sub_option); ?>[v] && jQuery('#' + v).val() >= 1) { 
									required_<?php echo esc_html($option_num.'_'.$sub_option); ?> = 1; 
								}

								// negative (-) symbol not allowed on PAX field
								if ($('#' + v).val() < 0) {
									err = 'Please enter valid number for booking.';
								}
							}
		
							if ($('#book_time_<?php echo esc_html($option_num.'_'.$sub_option); ?>').length && !$('#book_time_<?php echo esc_html($option_num.'_'.$sub_option); ?>').val()) {
								err = 'Please select a starting time';
								$('#select_time_<?php echo esc_html($option->uid); ?>').addClass('error');
								setTimeout(() => {
									$('#select_time_<?php echo esc_html($option->uid); ?>').removeClass('error');
								}, 2500);
							} else if(count_<?php echo esc_html($option_num.'_'.$sub_option); ?> == 0 || !count_<?php echo esc_html($option_num.'_'.$sub_option); ?>) {
								err = 'Please enter the number you would like to book.';
							} else if(required_num_<?php echo esc_html($option_num.'_'.$sub_option); ?> > 0 && required_<?php echo esc_html($option_num.'_'.$sub_option); ?> == 0) {
								err = 'At least one marked ( * ) price point is required to book.';
							} else if(!isInt(count_<?php echo esc_html($option_num.'_'.$sub_option); ?>)) {
								err = 'Please enter a whole number. No decimal places allowed.';
							} else if(count_<?php echo esc_html($option_num.'_'.$sub_option); ?> < <?php echo esc_html($minimum_required_pax); ?>) {
								err = '<?php echo esc_html($minimum_required_pax); ?> minimum required to book.';
							<?php if ($dynamic_start_time) { ?>
								} else if(count_<?php echo esc_html($option_num.'_'.$sub_option); ?> > parseInt($('#time_selected_availability_<?php echo esc_attr($option_num.'_'.$sub_option); ?>').text())) {
								err = 'There is not enough availability to book ' + count_<?php echo esc_html($option_num.'_'.$sub_option); ?>;
							<?php } else { ?>
								} else if(count_<?php echo esc_html($option_num.'_'.$sub_option); ?> > <?php echo esc_html($option->date->availability); ?>) {
									err = 'There is not enough availability to book ' + count_<?php echo esc_html($option_num.'_'.$sub_option); ?>;
							<?php } ?>

							} else if(count_<?php echo esc_html($option_num.'_'.$sub_option); ?> > 250) {
								err = 'You can not book more than 250 spaces in a single booking.';
							}
							<?php if ($option->block_size) { ?>
								// prevent adding to cart if the user is trying to book for more than the block size availability
								else if(Math.ceil(count_<?php echo $option_num.'_'.$sub_option; ?> / <?php echo $option->block_size; ?>) * <?php echo $option->block_size; ?> > <?php echo $option->date->availability; ?>) {
									// console.log('trying to book for ' + Math.ceil(count_<?php echo $option_num.'_'.$sub_option; ?> / <?php echo $option->block_size; ?>) * <?php echo $option->block_size; ?>);
									// console.log('but there is only ' +<?php echo $option->date->availability; ?>);
									err = 'There is not enough availability to book ' + count_<?php echo $option_num.'_'.$sub_option; ?>;
								}
							<?php } ?>
							<?php if ($option->max_guests > 0) { ?>
							else if(count_<?php echo esc_html($option_num.'_'.$sub_option); ?> > <?php echo esc_html($option->max_guests); ?>) {
								err = 'There is a maximum of <?php echo esc_html($option->max_guests); ?> per booking.';
							}
							<?php } ?>
	
							if(err) {
									
								<?php if(!$site->config('REZGO_MOBILE_XML')) { ?>
									$('#error_text_<?php echo esc_html($option_num.'_'.$sub_option); ?>').html(err);
									$('#error_text_<?php echo esc_html($option_num.'_'.$sub_option); ?>').slideDown().delay(2000).slideUp('slow');
								<?php } else { ?>
									$('#error_mobile_text_<?php echo esc_html($option_num.'_'.$sub_option); ?>').html(err);
									$('#error_mobile_text_<?php echo esc_html($option_num.'_'.$sub_option); ?>').slideDown().delay(2000).slideUp('slow');
								<?php } ?>
								return false;
								
							} else {
									
								// prepare inputs before submitting (*bundles)							
								let inputs = new Object(); // create new object
								
								$("#checkout_<?php echo esc_html($option_num.'_'.$sub_option); ?> input").each(function() {
									if (this.name != '') {

										let index = this.name; // set variable prop as input name
										let val;
										
										if (this.value == '') { val = 0; } else { val = parseInt(this.value); }
										
										if ( inputs.hasOwnProperty(index) == true ) { // check if prop exists 
											$(this).val(val + parseInt(inputs[index])); // update value of current input, adding current prop val 
											inputs[index] += val; // update this prop
										} else {
											inputs[index] = val; // set first val of this prop
										}			

									}

								});

								// addCart() request
								$('#checkout_<?php echo esc_html($option_num.'_'.$sub_option); ?>').submit( function(e) {
									e.preventDefault();

									$('.rezgo-btn-add').attr('disabled', true);

									$('#checkout_<?php echo esc_html($option_num.'_'.$sub_option); ?>').ajaxSubmit({
										type: 'POST',
										url: '<?php echo admin_url('admin-ajax.php'); ?>' + '?action=rezgo&method=book_ajax', 
										data: { rezgoAction: 'add_item'},
										success: function(data){

											// console.log(data);
											let response = JSON.parse(data);

											<?php if ($analytics_ga4) { ?>
												// gtag add_to_cart
												function ga4_add_to_cart(){
													gtag("event", "add_to_cart", {
														currency: "<?php echo esc_html($option->currency_base); ?>",
														value: add_to_cart_total,
														items: [
															{
																item_id: "<?php echo esc_html($option->uid); ?>",
																item_name: "<?php echo esc_html($option->item . ' - ' . $option->option); ?>",
																currency: "<?php echo esc_html($option->currency_base); ?>",
																coupon: "<?php echo (isset($_COOKIE['rezgo_promo']) ? esc_html($_COOKIE['rezgo_promo']) : esc_html($site->cart_trigger_code)); ?>",
																price: add_to_cart_total,
																quantity: 1,
																index: 1,
															}
														]
													});
												}
											<?php } ?>

											<?php if ($analytics_gtm) { ?>
												// tag manager add_to_cart
												function gtm_add_to_cart() {
													dataLayer.push({ ecommerce: null });  // Clear the previous ecommerce object.
													dataLayer.push({
													event: "add_to_cart",
														ecommerce: {
															items: [
																{
																	item_id: "<?php echo esc_html($option->uid); ?>",
																	item_name: "<?php echo esc_html($option->item . ' - ' . $option->option); ?>",
																	currency: "<?php echo esc_html($option->currency_base); ?>",
																	price: add_to_cart_total,
																	quantity: 1,
																	coupon: "<?php echo (isset($_COOKIE['rezgo_promo']) ? esc_html($_COOKIE['rezgo_promo']) : esc_html($site->cart_trigger_code)); ?>",
																},
															]
														}
													});
												}
											<?php } ?>

											<?php if ($meta_pixel) { ?>
												// meta_pixel AddToCart
												function pixel_add_to_cart() {
													fbq('track', 'AddToCart', { 
														currency: "<?php echo esc_html($option->currency_base); ?>",
														value: add_to_cart_total,
														contents: {
																	'id': "<?php echo esc_html($option->uid); ?>",
																	'name': "<?php echo esc_html($option->item . ' - ' . $option->option); ?>",
																	'quantity': 1,
																	'price': add_to_cart_total,
																},
														}
													);
												}
											<?php } ?>

											<?php if (!REZGO_LITE_CONTAINER){ ?>

												//no errors
												if (response == null) {
													localStorage.clear();

													<?php if ($analytics_ga4) { ?>
														ga4_add_to_cart();
													<?php } ?>

													<?php if ($analytics_gtm) { ?>
														gtm_add_to_cart();
													<?php } ?>

													<?php if ($meta_pixel) { ?>
														pixel_add_to_cart();
													<?php } ?>

													<?php $cart_token = sanitize_text_field($_COOKIE['rezgo_cart_token_'.REZGO_CID] ?? ''); ?>
													<?php echo LOCATION_WINDOW; ?>.location.href='<?php echo $parent_url; ?>/order/<?php echo esc_html($cart_token); ?>';

													<?php if (isset($_REQUEST['cross_sell'])) { ?>	
														let parentContainer = window.parent.parent;
														parentContainer.document.getElementById('rezgo-cross-dismiss').click();
														parentContainer.location.reload();
													<?php } ?>
												} 
												else {
													let err = response.message;
													$('.rezgo-btn-add').attr('disabled', false);

													<?php if(!$site->config('REZGO_MOBILE_XML')) { ?>
														$('#error_text_<?php echo esc_html($option_num.'_'.$sub_option); ?>').html(err);
														$('#error_text_<?php echo esc_html($option_num.'_'.$sub_option); ?>').slideDown().delay(2000);
													<?php } else { ?>
														$('#error_mobile_text_<?php echo esc_html($option_num.'_'.$sub_option); ?>').html(err);
														$('#error_mobile_text_<?php echo esc_html($option_num.'_'.$sub_option); ?>').slideDown().delay(2000);
													<?php } ?>
												}

												<?php } else { ?>

												// check if there is a token in the lite URI
												<?php if ($site->cartInUri()) { ?> 

													//no errors
													if (response == null) {
														<?php if ($analytics_ga4) { ?>
															ga4_add_to_cart();
														<?php } ?>

														<?php if ($analytics_gtm) { ?>
															gtm_add_to_cart();
														<?php } ?>

														<?php if ($meta_pixel) { ?>
															pixel_add_to_cart();
														<?php } ?>
														<?php echo LOCATION_WINDOW; ?>.location.href='<?php esc_html($site->base); ?>/order/';
													}
													else {
														let err = response.message;
														$('.rezgo-btn-add').attr('disabled', false);

														<?php if(!$site->config('REZGO_MOBILE_XML')) { ?>
															$('#error_text_<?php echo esc_html($option_num.'_'.$sub_option); ?>').html(err);
															$('#error_text_<?php echo esc_html($option_num.'_'.$sub_option); ?>').slideDown().delay(2000);
														<?php } else { ?>
															$('#error_mobile_text_<?php echo esc_html($option_num.'_'.$sub_option); ?>').html(err);
															$('#error_mobile_text_<?php echo esc_html($option_num.'_'.$sub_option); ?>').slideDown().delay(2000);
														<?php } ?>
														}

												<?php } else { ?>

													// redirect to lite URI with cart token
													<?php echo LOCATION_WINDOW; ?>.location.href = 'https://' + response + '/order/';

												<?php } // endif cartInUri() ?>
											<?php } // endif REZGO_LITE_CONTAINER ?>

										},
										error: function(error){
											console.log(error);
										}
										});

									});

									// let fb_quantity = count_<?php echo $option_num.'_'.$sub_option; ?>; 
									// let fb_uid = <?php echo $option->uid; ?> ;
									// let obj = [{'id': fb_uid},{'quantity': fb_quantity}];

									//fbq('track', 'AddToCart',
									// //begin parameter object data
									// {
									//    content_ids: fb_uid , 
									//    content_type: 'Product', 
									//    contents: obj,
									// });				           
								}
								
								// return false;

								}
							});

						<?php if ( (REZGO_WORDPRESS) && (isset($_REQUEST['cross_sell'])) ) { ?>
							document.querySelector('#panel_<?php echo esc_html($option_num.'_'.$sub_option); ?>').addEventListener('click',function(){
								setTimeout(() => {
									document.querySelector('#panel_<?php echo esc_html($option_num.'_'.$sub_option); ?>').scrollIntoView({
											behavior :'smooth',
											block: "start",
											inline: "start"
										});
								}, 500);
							});
						<?php } ?>
					</script>
	
						<a data-bs-toggle="collapse" data-parent="#rezgo-select-option-<?php echo esc_attr($option_num.'_'.$sub_option); ?>" data-bs-target="#option_<?php echo esc_attr($option_num.'_'.$sub_option); ?>" class="panel-heading panel-title rezgo-panel-option-link" id="panel_<?php echo $option_num.'_'.$sub_option; ?>">

							<script>
								jQuery('#panel_<?php echo esc_html($option_num.'_'.$sub_option); ?>').click( function(){
									jQuery(this).find('i.fa-angle-right').toggleClass('active');
								});

								var pax_<?php echo $option_num.'_'.$sub_option; ?> = {
									'adult':0,
									'child':0,
									'senior':0,
									'price4':0,
									'price5':0,
									'price6':0,
									'price7':0,
									'price8':0,
									'price9':0,
								};
							</script>

							<div class="rezgo-panel-option"><i class="fal fa-angle-right <?php echo (((count($options) == 1 && $option_num == 1) || $id == (int) $option->uid) ? ' active' : '')?>" aria-hidden="true"></i> &nbsp; <?php echo esc_html($option->option); ?> 
							
							<?php if (!$site->exists($option->date->hide_availability)) { ?>
							
								<span class="rezgo-show-count">
								
								<?php if ($option->date->availability == 0) { ?>
								
								<span class="fa rezgo-full-dash"><span>&nbsp;&ndash;&nbsp;</span></span>
								<span class="rezgo-option-full"><span>full</span></span>
								
								<?php } else { ?>
								
								<span class="fa rezgo-option-dash"><span>&nbsp;&ndash;&nbsp;</span></span>
								<span class="rezgo-option-count"><?php echo (string) esc_html($option->date->availability); ?></span>
								<span class="rezgo-option-pax"><span>&nbsp;<?php echo ((int) esc_html($option->date->availability) == 1 ? 'spot':'spots');?></span></span>
								
								<?php } ?>
								
								</span>	
								
							<?php } ?>
							
							</div>
						</a>
						<div id="option_<?php echo $option_num.'_'.$sub_option; ?>" class="option-panel-<?php echo esc_attr($option->uid); ?> panel-collapse collapse<?php echo (((count($options) == 1 && $option_num == 1) || $id == (int) $option->uid) ? ' show' : ''); ?>">
						<div class="panel-body">

							<?php if ($option->date->availability != 0 && $block_available) { ?>

							<?php
							if(REZGO_LITE_CONTAINER) {
									if ($_REQUEST['cross_sell']) {
										$form_target = 'target="_parent"';
										if (REZGO_WORDPRESS) $site->base = home_url('/', 'https').sanitize_text_field($_REQUEST['wp_slug']);
									} else {
										$form_target = 'target="rezgo_content_frame"';
									}  
							} else {
								$form_target = ''; 
							}
							
							?>
								<span class="rezgo-option-memo rezgo-option-<?php echo esc_attr($option->uid); ?> rezgo-option-date-<?php echo esc_attr($_REQUEST['date']); ?>"></span>
								<form class="rezgo-order-form" method="post" id="checkout_<?php echo esc_attr($option_num.'_'.$sub_option); ?>" <?php echo esc_attr($form_target); ?>>
	
									<?php if (!$site->exists($option->date->hide_availability) && !$dynamic_start_time) { ?>
									<span class="rezgo-memo rezgo-availability"><strong>Availability:</strong> <?php echo ($option->date->availability == 0 ? 'full' : (string) $option->date->availability)?><br /></span>	
									<?php } ?>
								
									<?php if ($option->duration != '') { ?>
										<span class="rezgo-memo rezgo-duration"><strong>Duration:</strong> <?php echo (string) esc_html($option->duration); ?><br /></span>	
									<?php } ?>
								
									<?php if ($option->time != '' && !$dynamic_start_time) { ?>
										<span class="rezgo-memo rezgo-time"><strong>Time:</strong> <?php echo (string) esc_html($option->time); ?><br /></span>	
									<?php } ?>
	
									<?php if ($dynamic_start_time) { ?>

										<div class="time-select-container">
											<a id="select_time_<?php echo esc_html($option->uid); ?>" class="time-option-select"> 
												<i class="far fa-clock"></i>
												<span class="time-option-select-copy">
												<span class="top-placeholder">
													<span>Start Time</span>
												</span>

													<span id="time_selected_<?php echo esc_html($option->uid); ?>">
														<span class="default">Select a Time</span>
														<span class="custom"></span>
													</span>
												</span>
												<span id="selected"></span>
												<span id="time_selected_availability_<?php echo esc_html($option_num.'_'.$sub_option); ?>" style="display:none;"></span>

												<i class="fas fa-chevron-down"></i> 
											</a>
											<p class="rezgo-time-option-error" style="display:none;"></p>

											<div id="time-option-container-<?php echo esc_html($option->uid); ?>" class="time-option-container">

												<script>
													price_av_obj_<?php echo esc_html($option_num.'_'.$sub_option); ?> = {
														<?php foreach ($option->date->time_data->time as $time) { ?>
															'<?php echo $time->id; ?>' : '<?php echo json_encode($time->prices); ?>',
														<?php } ?>
															
													};
												</script>

												<?php foreach ($option->date->time_data->time as $time) { 
													$cutoff_operator = '+';
													if (strpos($option->cutoff, '-') !== false) {
														$option_cutoff = str_replace('-', '', $option->cutoff);
														$cutoff_operator = '-';
													}
													$option_cutoff = round((float)$option->cutoff * 60);
													$cutoff_time = strtotime($cutoff_operator .$option_cutoff. ' minutes', strtotime($selected_date.$time->id) );
													
													$passed = $offset_time_now > $cutoff_time ? 1 : 0; ?>

													<?php if ((int)$time->av > 0 && !$passed) { ?>
														<p id="time_select_<?php echo esc_html($option->uid); ?>_<?php echo esc_html($i); ?>" class="time_select_<?php echo esc_html($option->uid); ?>" data-book-time="<?php echo esc_html($time->id); ?>" data-availability="<?php echo (int)$time->av; ?>">
														<?php echo (string)$time->id; ?>
															<?php if (!$option->date->hide_availability) { ?>
																<span class="availability"><i class="fas fa-circle"></i> &nbsp;<?php echo (int)$time->av; ?> left</span>
															<?php } ?>
														</p>
													<?php } ?>
												<?php $i++; } ?>
												</div>
												
											</div>

											<script>
												jQuery(function($){
													// Dynamic start time selector
													$('#select_time_<?php echo esc_html($option->uid); ?>').click(function(){
														if($('#time-option-container-<?php echo $option->uid; ?>').children().length > 0){
															$('#time-option-container-<?php echo $option->uid; ?>').toggleClass('open');
														}
													});

													$('.time_select_<?php echo $option->uid; ?>').click(function(){
														let book_time_avail = $(this).data('availability');
														let book_time = $(this).data('book-time');
														let hide_av = <?php echo $site->exists($option->date->hide_availability) ? 1 : 0; ?>;

														$('input[name="add[0][book_time]"]').val(book_time);
														$('#time-option-container-<?php echo $option->uid; ?>').removeClass('open');
														$('#time_selected_<?php echo $option->uid; ?>').html(book_time);
														$('#time_selected_availability_<?php echo $option_num.'_'.$sub_option; ?>').html(book_time_avail);

												if( price_av_obj_<?php echo esc_html($option_num.'_'.$sub_option); ?>.hasOwnProperty(book_time) ) {
													price_tier_av = price_av_obj_<?php echo esc_html($option_num.'_'.$sub_option); ?>[book_time];
													price_tier_av = JSON.parse(price_tier_av);

													if (price_tier_av.price) {
														for(let i = 0; i < price_tier_av.price.length; i++) {
															let price_tier = price_tier_av.price[i];
															let availability = parseInt(price_tier.av);
															let existing_val = $('#' + price_tier_array[i] +'_<?php echo esc_html($option_num.'_'.$sub_option); ?>').val();

															$('#price_tier_div_' + i + '_<?php echo $option_num.'_'.$sub_option; ?>').show();
															$('#max_pax_error_'+ price_tier_array[i] +'_<?php echo esc_html($option_num.'_'.$sub_option); ?>').hide();

															// toggle change event listeners
															$('#'+ price_tier_array[i] +'_<?php echo esc_html($option_num.'_'.$sub_option); ?>').off('change');
															$('#'+ price_tier_array[i] +'_<?php echo esc_html($option_num.'_'.$sub_option); ?>').change(function(){

																// disable unwanted inputs
																if ($(this).val() < 0){
																	$(this).val('');
																} else if(!isInt($(this).val()) || $(this).val() === 0) {
																	$(this).val('');
																} else {
																	document.getElementById(price_tier_array[i] +'_<?php echo esc_html($option_num.'_'.$sub_option); ?>').value = parseInt($(this).val());
																}
										
																if ($(this).val() > availability){
																	// show an error message
																	let plural_start = availability > 1 ? 'are' : 'is';
																	let plural_end = availability > 1 ? 's' : '';
																	let err = 'There ' + plural_start + ' only ' + availability + ' space' + plural_end + ' available';
																	$('#max_pax_error_'+ price_tier_array[i] +'_<?php echo esc_html($option_num.'_'.$sub_option); ?>').html(err);
																	$('#max_pax_error_'+ price_tier_array[i] +'_<?php echo esc_html($option_num.'_'.$sub_option); ?>').slideDown();

																	// reset input 
																	$('#' + price_tier_array[i] +'_<?php echo esc_html($option_num.'_'.$sub_option); ?>').val('');
																	$('#increase'+ price_tier_array[i] +'_<?php echo esc_html($option_num.'_'.$sub_option); ?>').addClass('not-allowed');

																	setTimeout(() => {
																		$('#max_pax_error_'+ price_tier_array[i] +'_<?php echo esc_html($option_num.'_'.$sub_option); ?>').slideUp();
																	}, 3500);
																	
																} else if ($(this).val() < availability) {
																	
																	$('#decrease_'+ price_tier_array[i] +'_<?php echo esc_html($option_num.'_'.$sub_option); ?>').removeClass('not-allowed');
																	$('#increase'+ price_tier_array[i] +'_<?php echo esc_html($option_num.'_'.$sub_option); ?>').removeClass('not-allowed');

																} else if ($(this).val() == availability) {

																	$('#decrease_'+ price_tier_array[i] +'_<?php echo esc_html($option_num.'_'.$sub_option); ?>').removeClass('not-allowed');
																	$('#increase_'+ price_tier_array[i] +'_<?php echo esc_html($option_num.'_'.$sub_option); ?>').addClass('not-allowed');
																} 

															})

															$('#increase_'+ price_tier_array[i] +'_<?php echo esc_html($option_num.'_'.$sub_option); ?>').click(function(){
																let value = $('#'+ price_tier_array[i] +'_<?php echo esc_html($option_num.'_'.$sub_option); ?>').val();
																let plus_sign = $(this);

																if (value < availability){
																	plus_sign.removeClass('not-allowed');
																} else if (value == availability) {
																	plus_sign.addClass('not-allowed');
																}
															})

															$('#decrease_'+ price_tier_array[i] +'_<?php echo esc_html($option_num.'_'.$sub_option); ?>').click(function(){
																let value = $('#'+ price_tier_array[i] +'_<?php echo esc_html($option_num.'_'.$sub_option); ?>').val();
																let plus_sign = $('#increase_'+ price_tier_array[i] +'_<?php echo esc_html($option_num.'_'.$sub_option); ?>');
																let minus_sign = $(this);

																if (value < availability){
																	plus_sign.removeClass('not-allowed');
																}
															})

															// revalidate input if there is a previous value
															if (existing_val > availability) {

																// show an error message
																let plural_start = availability > 1 ? 'are' : 'is';
																let plural_end = availability > 1 ? 's' : '';
																let err = 'There ' + plural_start + ' only ' + availability + ' space' + plural_end + ' available';
																$('#max_pax_error_'+ price_tier_array[i] +'_<?php echo esc_html($option_num.'_'.$sub_option); ?>').html(err);
																$('#max_pax_error_'+ price_tier_array[i] +'_<?php echo esc_html($option_num.'_'.$sub_option); ?>').slideDown();

																// reset input 
																$('#' + price_tier_array[i] +'_<?php echo esc_html($option_num.'_'.$sub_option); ?>').val('');
																$('#decrease_'+ price_tier_array[i] +'_<?php echo esc_html($option_num.'_'.$sub_option); ?>').addClass('not-allowed');
																$('#increase'+ price_tier_array[i] +'_<?php echo esc_html($option_num.'_'.$sub_option); ?>').removeClass('not-allowed');

																setTimeout(() => {
																	$('#max_pax_error_'+ price_tier_array[i] +'_<?php echo esc_html($option_num.'_'.$sub_option); ?>').slideUp();
																}, 3500);

															} else if (existing_val == availability) {
																$('#increase_'+ price_tier_array[i] +'_<?php echo esc_html($option_num.'_'.$sub_option); ?>').addClass('not-allowed');
															} else if (existing_val < availability) {
																$('#decrease_'+ price_tier_array[i] +'_<?php echo esc_html($option_num.'_'.$sub_option); ?>').removeClass('not-allowed');
																$('#increase_'+ price_tier_array[i] +'_<?php echo esc_html($option_num.'_'.$sub_option); ?>').removeClass('not-allowed');
															}
															
															if (availability < 10 && !hide_av) {
																$('#price_tier_amount_left_' + i + '_<?php echo $option_num.'_'.$sub_option; ?>').removeClass('d-none');
																$('#price_tier_amount_left_' + i + '_<?php echo $option_num.'_'.$sub_option; ?>').html('<span class="edit-pax-max">Only ' + availability + ' Left</span>');

																if (availability == 0) {
																	$('#price_tier_div_' + i + '_<?php echo $option_num.'_'.$sub_option; ?>').hide();
																	$('#' + price_tier_array[i] +'_<?php echo esc_html($option_num.'_'.$sub_option); ?>').val('');
																	$('#price_tier_amount_left_' + i + '_<?php echo $option_num.'_'.$sub_option; ?>').html('<span class="edit-pax-max">Not Available</span>');
																}
															} else {
																$('#price_tier_amount_left_' + i + '_<?php echo $option_num.'_'.$sub_option; ?>').addClass('d-none');
															}
														}
													}
												}

											});
										});
											
											// pre-fill booking time in booking edit if set
											<?php if (isset($edit_booking_page) && $booking_time && $booking_date == $_REQUEST['date']) { ?>
												$('#time_selected_<?php echo $option->uid; ?>').html('<?php echo $booking_time; ?>');
												$('input[name="add[0][book_time]"]').val('<?php echo $booking_time; ?>');
											<?php } ?>

										</script>

										<?php } // if ($dynamic_start_time) ?>

										<input type="hidden" name="add[0][uid]" value="<?php echo esc_html($option->uid); ?>">
										<input type="hidden" name="add[0][date]" value="<?php echo esc_html($_REQUEST['date']); ?>">
										<?php if ($dynamic_start_time) { ?>
											<input id="book_time_<?php echo esc_attr($option_num.'_'.$sub_option); ?>" type="hidden" name="add[0][book_time]" value="">
										<?php } ?>

									<div class="row"> 
										<div class="col-12 rezgo-order-fields">
											
											<?php $prices = $site->getTourPrices($option); ?>

											<?php if($site->getTourRequired() == 1) { ?>
												<span class="rezgo-memo">At least one marked ( <em><i class="fa fa-asterisk"></i></em> ) price point is required.</span>
											<?php } ?>

											<?php if($option->per > 1) { ?>
												<span class="rezgo-memo">At least <?php echo esc_html($minimum_required_pax); ?> are required to book.</span>
											<?php } ?>

											<?php if ($option->block_size) { ?>
												<span class="rezgo-memo">Books in blocks of <?php echo esc_html($option->block_size); ?></span>
											<?php } ?>

											<?php $total_required = 0; ?>
											<?php $animation_order = 1; ?>
											<?php $prices_total = 0; ?>

											<script>
												running_pax_<?php echo esc_html($option_num.'_'.$sub_option); ?> = {
													<?php foreach ($prices as $price) { 
														echo "'" .$price->name. "'" .':'.'0'. ',';
													} ?> 
												};
											</script>

											<?php foreach( $prices as $price ) { ?>

												<?php // check if total for all price points equals to zero
													$original_price = $price->base ?? $price->price;
													$prices_total += $original_price;
												?>

												<script>fields_<?php echo esc_html($option_num.'_'.$sub_option); ?>['<?php echo esc_html($price->name); ?>_<?php echo esc_html($option_num.'_'.$sub_option); ?>'] = <?php echo (($price->required) ? 1 : 0); ?>;</script>

												<div class="edit-pax-wrp rezgo-option-<?php echo esc_attr($option->uid); ?>-price-tiers rezgo-option-<?php echo esc_attr($option->uid); ?>-price-tier-<?php echo esc_attr($animation_order); ?>" style="--animation-order: <?php echo esc_attr($animation_order); ?>;">
												<div class="edit-pax-label-container">
													<label for="<?php echo esc_attr($price->name); ?>_<?php echo esc_attr($option_num.'_'.$sub_option); ?>" class="control-label rezgo-pax-label rezgo-label-margin rezgo-label-padding-left">
														<?php echo esc_html($price->label); ?><?php echo (($price->required && $site->getTourRequired()) ? ' <em><i class="fa fa-asterisk"></i></em>' : ''); ?> 
													</label>

													<?php 
														if($price->age_min || $price->age_max) {
															echo '<div class="edit-pax-age">';
																if($price->age_min == $price->age_max) { echo '<span>Age '.esc_html($price->age_min) .'</span>'; }
																elseif($price->age_min && !$price->age_max) { echo '<span>Ages '.esc_html($price->age_min).' and up' .'</span>'; }
																elseif(!$price->age_min && $price->age_max) { echo '<span>Ages '.esc_html($price->age_max).' and under' .'</span>'; }
																elseif($price->age_min && $price->age_max) { echo '<span>Ages '.esc_html($price->age_min).' - '.esc_html($price->age_max) .'</span>'; }
															echo '</div>';
														}
													?>
													<div id="price_tier_amount_left_<?php echo ($animation_order - 1); ?>_<?php echo $option_num.'_'.$sub_option; ?>" class="<?php echo ($site->exists($option->date->hide_availability) || $dynamic_start_time || (int)$option->date->availability == (int)$price->max) ? 'd-none' : ''; ?>">
														<?php 
															if(!$dynamic_start_time && isset($price->max) && $price->max < 10) {
																echo '<span class="edit-pax-max">';
																	$max_text = 'Only '.$price->max.' Left';
																	if($price->max == 0) $max_text = 'Not Available <div class="space-6"></div>';
																	echo '<span>'.$max_text.'</span>';
																echo '</span>';

															}
														?>
													</div>
												</div>

												<div id="price_tier_div_<?php echo ($animation_order - 1); ?>_<?php echo $option_num.'_'.$sub_option; ?>" class="pax-price-container <?php echo (!$dynamic_start_time && isset($price->max) && $price->max == 0) ? 'd-none' : ''; ?>">						

													<div class="form-group row pax-input-row left-col">

														<div class="edit-pax-container">
															<div class="minus-pax-container">
																<span>
																	<a id="decrease_<?php echo esc_attr($price->name); ?>_<?php echo esc_attr($option_num.'_'.$sub_option); ?>" class="not-allowed" onclick="decreasePax_<?php echo esc_js($price->name ?? ''); ?>_<?php echo esc_js($option_num.'_'.$sub_option); ?>()">
																		<i class="fa fa-minus"></i>
																	</a>
																</span>
															</div>
															<div class="input-container">
																<input type="number" min="0" name="add[0][<?php echo esc_attr($price->name); ?>_num]" value="<?php echo esc_attr($_REQUEST[$price->name.'_num'] ?? ''); ?>" id="<?php echo esc_attr($price->name ?? ''); ?>_<?php echo esc_attr($option_num.'_'.$sub_option); ?>" class="pax-input" value="0" min="0" placeholder="0">
															</div>
															<div class="add-pax-container">
																<span>
																	<a id="increase_<?php echo esc_attr($price->name); ?>_<?php echo esc_attr($option_num.'_'.$sub_option); ?>" onclick="increasePax_<?php echo esc_js($price->name); ?>_<?php echo esc_js($option_num.'_'.$sub_option); ?>()">
																		<i class="fa fa-plus"></i>
																	</a>
																</span>
															</div>	
														</div>
													</div>

													<div class="right-col">
														<div class="edit-pax-label-container">
															<label for="<?php echo esc_attr($price->name); ?>_<?php echo esc_attr($option_num.'_'.$sub_option); ?>" class="control-label rezgo-label-margin rezgo-label-padding-left">

																<!-- if both strike prices and discount exists, show the higher price -->
																<?php
																	$initial_price = isset($price->price) ? (float) $price->price : 0;
																	$strike_price = isset($price->strike) ? (float) $price->strike : 0;
																	$discount_price = isset($price->base) ? (float) $price->base : 0;
																?>
																<span class="rezgo-pax-price" id="<?php echo esc_attr($price->name); ?>_<?php echo esc_attr($option_num.'_'.$sub_option); ?>_price" data-price="<?php echo esc_attr($price->price); ?>">
																<?php if ( ($site->exists($price->strike)) && (isset($price->base) && $site->exists($price->base)) )  { ?>
																	<?php $show_this = max($strike_price, $discount_price); ?>

																	<span class="rezgo-strike-price">
																		<?php echo esc_html( $site->formatCurrency($show_this)); ?>
																	</span><br>
																		
																<?php } else if($site->exists($price->strike)) { ?>

																		<!-- show only if strike price is higher -->
																		<?php if ($strike_price >= $initial_price) { ?>
																			<span class="rezgo-strike-price">
																				<?php echo esc_html($site->formatCurrency($strike_price)); ?>
																			</span><br>
																		<?php } ?>
																		<span class="rezgo-strike-extra"><span>

																<?php } else if(isset($price->base) && $site->exists($price->base)) { ?>

																		<span class="discount">
																			<?php echo esc_html($site->formatCurrency($price->base)); ?>
																		</span><br>

																<?php } ?>

																	<?php echo esc_html($site->formatCurrency($price->price)); ?>
																</span>
															</label>
														</div>
													</div>

													<div id="max_pax_error_<?php echo esc_attr($price->name); ?>_<?php echo esc_attr($option_num.'_'.$sub_option); ?>" class="text-danger rezgo-option-error rezgo-max-pax-error" style="display:none;"></div>
		
													<?php if ($price->required) $total_required++; ?>

													<script>
													jQuery(function($){

													// prepare values insert in addCart() request 
													$('#<?php echo esc_html($price->name); ?>_<?php echo esc_html($option_num.'_'.$sub_option); ?>').change(function(){
														<?php echo esc_html($price->name); ?>_num = $(this).val();
														if ($(this).val() <= 0) {
															$('#decrease_<?php echo esc_html($price->name); ?>_<?php echo esc_html($option_num.'_'.$sub_option); ?>').addClass('not-allowed');
														} else {
															$('#decrease_<?php echo esc_html($price->name); ?>_<?php echo esc_html($option_num.'_'.$sub_option); ?>').removeClass('not-allowed');
														}

														$('#increase_<?php echo esc_html($price->name); ?>_<?php echo esc_html($option_num.'_'.$sub_option); ?>').removeClass('not-allowed');

														// disable unwanted inputs
														if ($(this).val() < 0){
															$(this).val('');
															running_pax_<?php echo esc_html($option_num.'_'.$sub_option); ?>.<?php echo esc_html($price->name); ?> = 0;
																<?php if (!$dynamic_start_time) { ?>
																	} else if ($(this).val() > <?php echo $price->max; ?>){
																		// show an error message
																		let max_pax = <?php echo (int)$price->max; ?>;
																		let plural_start = max_pax > 1 ? 'are' : 'is';
																		let plural_end = max_pax > 1 ? 's' : '';
																		let err = 'There ' + plural_start + ' only ' + max_pax + ' space' + plural_end + ' available';
																		$('#max_pax_error_<?php echo esc_html($price->name); ?>_<?php echo esc_html($option_num.'_'.$sub_option); ?>').html(err);
																		$('#max_pax_error_<?php echo esc_html($price->name); ?>_<?php echo esc_html($option_num.'_'.$sub_option); ?>').slideDown();

																		// reset input 
																		$(this).val('');
																		$('#decrease_<?php echo esc_html($price->name); ?>_<?php echo esc_html($option_num.'_'.$sub_option); ?>').addClass('not-allowed');

																		setTimeout(() => {
																			$('#max_pax_error_<?php echo esc_html($price->name); ?>_<?php echo esc_html($option_num.'_'.$sub_option); ?>').slideUp();
																		}, 3500);
																	<?php } ?>

																} else if(!isInt($(this).val()) || $(this).val() === 0) {
																	$(this).val('');
																	running_pax_<?php echo esc_html($option_num.'_'.$sub_option); ?>.<?php echo esc_html($price->name); ?> = 0;
																} else {

															// let totalPax = 0;
															// let max_guests = parseInt(<?php echo $option->max_guests; ?>);

															document.getElementById('<?php echo esc_html($price->name); ?>_<?php echo esc_html($option_num.'_'.$sub_option); ?>').value = parseInt($(this).val());

															// for(v in fields_<?php echo $option_num.'_'.$sub_option; ?>) {
															// 	totalPax += parseInt($('#' + v).val()*1);
															// }

															// if (totalPax >= max_guests) {
															// 	for(v in fields_<?php echo $option_num.'_'.$sub_option; ?>) {
															// 		$('#increase_'+v).addClass('not-allowed');
															// 	}
															// } else {
															// 	for(v in fields_<?php echo $option_num.'_'.$sub_option; ?>) {
															// 		$('#increase_'+v).removeClass('not-allowed');
															// 	}
															// }

															running_pax_<?php echo esc_html($option_num.'_'.$sub_option); ?>.<?php echo esc_html($price->name); ?> = parseInt($(this).val());
														}

														total_pax = 0;
														for (const [pax, amount] of Object.entries(running_pax_<?php echo esc_html($option_num.'_'.$sub_option); ?>)) {
															if (amount > 0) {
																total_pax += parseInt(amount);
															}
														}
														// console.log(running_pax_<?php echo $option_num.'_'.$sub_option; ?>);

													});

													increasePax_<?php echo esc_html($price->name); ?>_<?php echo esc_html($option_num.'_'.$sub_option); ?> = function(){
														// let total_pax = 0;
														// let max_guests = parseInt(<?php echo $option->max_guests; ?>);
															let value = parseInt(document.getElementById('<?php echo esc_html($price->name); ?>_<?php echo esc_html($option_num.'_'.$sub_option); ?>').value);
															value = isNaN(value) ? 0 : value;
															value++;
															if (value > 0) { 
																$('#decrease_<?php echo esc_html($price->name); ?>_<?php echo esc_html($option_num.'_'.$sub_option); ?>').removeClass('not-allowed');
															}

															<?php if (!$dynamic_start_time) { ?>
															if (value >= <?php echo $price->max; ?>) { 
																$('#increase_<?php echo esc_html($price->name); ?>_<?php echo esc_html($option_num.'_'.$sub_option); ?>').addClass('not-allowed');
															} else {
																$('#increase_<?php echo esc_html($price->name); ?>_<?php echo esc_html($option_num.'_'.$sub_option); ?>').removeClass('not-allowed');
															}
															<?php } ?>

															document.getElementById('<?php echo esc_html($price->name); ?>_<?php echo esc_html($option_num.'_'.$sub_option); ?>').value = value;
															
														// for(v in fields_<?php echo $option_num.'_'.$sub_option; ?>) {
														// 	total_pax += parseInt($('#' + v).val()*1);
														// }
														// if (total_pax >= max_guests) {
														// 	for(v in fields_<?php echo $option_num.'_'.$sub_option; ?>) {
														// 		$('#increase_'+v).addClass('not-allowed');
														// 	}
														// }

															// populate pax object
															pax_<?php echo esc_html($option_num.'_'.$sub_option); ?>['<?php echo esc_html($price->name); ?>'] = value;

															running_pax_<?php echo esc_html($option_num.'_'.$sub_option); ?>.<?php echo esc_html($price->name); ?> = value;
															total_pax = 0;
															for (const [pax, amount] of Object.entries(running_pax_<?php echo esc_html($option_num.'_'.$sub_option); ?>)) {
																if (amount > 0) {
																	total_pax += amount;
																}
															}
														// console.log(running_pax_<?php echo $option_num.'_'.$sub_option; ?>);
														}

													decreasePax_<?php echo esc_html($price->name); ?>_<?php echo esc_html($option_num.'_'.$sub_option); ?> = function(){
														// let total_pax = 0;
														// let max_guests = parseInt(<?php echo $option->max_guests; ?>);
															let value = parseInt(document.getElementById('<?php echo esc_html($price->name); ?>_<?php echo esc_html($option_num.'_'.$sub_option); ?>').value);
															value = isNaN(value) ? 0 : value;
															if (value <= 0) {
																return false;
															}
															value--;
															if (value <= 0) {
																$('#decrease_<?php echo esc_html($price->name); ?>_<?php echo esc_html($option_num.'_'.$sub_option); ?>').addClass('not-allowed');
															} 

															<?php if (!$dynamic_start_time) { ?>
															if (value <= <?php echo $price->max; ?>) { 
																$('#increase_<?php echo esc_html($price->name); ?>_<?php echo esc_html($option_num.'_'.$sub_option); ?>').removeClass('not-allowed');
															}
															<?php } ?>

															document.getElementById('<?php echo esc_html($price->name); ?>_<?php echo esc_html($option_num.'_'.$sub_option); ?>').value = value;

														// for(v in fields_<?php echo $option_num.'_'.$sub_option; ?>) {
														// 	total_pax += parseInt($('#' + v).val()*1);
														// }
															// if (total_pax <= max_guests) {
														// 	for(v in fields_<?php echo $option_num.'_'.$sub_option; ?>) {
														// 		$('#increase_'+v).removeClass('not-allowed');
														// 	}
														// }

															// populate pax object
															pax_<?php echo esc_html($option_num.'_'.$sub_option); ?>['<?php echo esc_html($price->name); ?>'] = value;	

															running_pax_<?php echo esc_html($option_num.'_'.$sub_option); ?>.<?php echo esc_html($price->name); ?> = value;
															total_pax = 0;
															for (const [pax, amount] of Object.entries(running_pax_<?php echo esc_html($option_num.'_'.$sub_option); ?>)) {
																if (amount > 0) {
																	total_pax += amount;
																}
															}
															// console.log(running_pax_<?php echo esc_html($option_num.'_'.$sub_option); ?>);
														}

													});
												</script>

											</div>

											</div>
								
											<?php $animation_order++; }
											// end foreach( $site->getTourPrices() ?>

											<script>required_num_<?php echo esc_html($option_num.'_'.$sub_option); ?> = <?php echo esc_html($total_required); ?>;</script>

											<?php
												$b = 0;
												$bundles = $site->getTourBundles($option);	
												
												//echo '<pre>'.print_r($bundles, 1).'</pre>';
												
												if (count($bundles) > 0) { ?>

													<?php
													
													foreach ($bundles as $bundle) {
														
														if ((int) $bundle->visible !== 0 && $option->date->availability >= $bundle->total) {
														
														?>
														
														<script>
															fields_<?php echo esc_html($option_num.'_'.$sub_option); ?>['<?php echo esc_html($bundle->label); ?>_<?php echo esc_html($option_num.'_'.$sub_option); ?>'] = 0;

															running_pax_bundle_<?php echo esc_html($option_num.'_'.$sub_option.'_'.$b); ?> = {
																<?php foreach ($prices as $price) { 
																	echo "'" .esc_html($price->name). "'" .':'.'0'. ',';
																} ?> 
															}
														</script>

														<div class="edit-pax-wrp" style="--animation-order: <?php echo esc_attr($animation_order); ?>;">
															<div class="edit-pax-label-container">
																<label for="<?php echo esc_attr($bundle->label); ?>_<?php echo esc_attr($option_num.'_'.$sub_option); ?>" class="control-label rezgo-pax-label rezgo-label-margin rezgo-label-padding-left">
																<?php echo esc_html($bundle->name); ?> <br> <span class="rezgo-bundle-makeup">- includes <?php echo esc_html($bundle->makeup); ?></span>
																</label>
																
																<div id="price_tier_amount_left_<?php echo ($animation_order - 1); ?>_<?php echo $option_num.'_'.$sub_option; ?>" class="<?php echo $dynamic_start_time ? 'd-none' : ''; ?>">
																	<?php 
																		if(isset($bundle->max) && $bundle->max < 10) {
																			echo '<span class="edit-pax-max">';
																				$max_text = 'Only '.$bundle->max.' Left';
																				if($bundle->max == 0) $max_text = 'Not Available <div class="space-6"></div>';
																				echo '<span>'.$max_text.'</span>';
																			echo '</span>';
																		}
																	?>
																</div>

															</div>

															<div class="pax-price-container <?php echo (isset($bundle->max) && $bundle->max == 0) ? 'd-none' : ''; ?>">

																<div class="form-group row rezgo-bundle-hidden pax-input-row left-col">
																	<div class="edit-pax-container">
																		<div class="minus-pax-container">
																			<a id="decrease_<?php echo esc_attr($bundle->label); ?>_<?php echo esc_attr($option_num.'_'.$sub_option.'_'.$b); ?>" class="not-allowed" onclick="decreaseBundle_<?php echo esc_js($option_num.'_'.$sub_option.'_'.$b); ?>()">
																					<i class="fa fa-minus"></i>
																			</a>
																		</div>
																		<div class="input-container">
																			<input type="number" min="0" name="" value="" id="<?php echo esc_attr($bundle->label); ?>_<?php echo esc_attr($option_num.'_'.$sub_option); ?>" rel="bundle" class="pax-input" value="0" min="0" placeholder="0">
																		</div>
																		<div class="add-pax-container">
																			<a id="increase_<?php echo esc_attr($bundle->label); ?>_<?php echo esc_attr($option_num.'_'.$sub_option.'_'.$b); ?>" onclick="increaseBundle_<?php echo esc_js($option_num.'_'.$sub_option.'_'.$b); ?>()" class="">
																				<i class="fa fa-plus"></i>
																			</a>
																		</div>	
																	</div>	
																</div>

																<div class="right-col">
																	<div class="edit-pax-label-container rezgo-bundle-hidden">
																		<label for="<?php echo esc_attr($bundle->label); ?>_<?php echo esc_attr($option_num.'_'.$sub_option.'_'.$b); ?>" class="control-label rezgo-label-margin rezgo-label-padding-left">
																			<span class="rezgo-pax-price" id="<?php echo esc_attr($bundle->label); ?>_<?php echo esc_attr($option_num.'_'.$sub_option); ?>_price" data-price="<?php echo esc_attr($bundle->price); ?>"><?php echo esc_html($site->formatCurrency($bundle->price)); ?></span><br />
																		</label>
																	</div>
																</div>
																<div id="max_pax_error_<?php echo esc_attr($bundle->label); ?>_<?php echo esc_attr($option_num.'_'.$sub_option); ?>" class="text-danger rezgo-option-error rezgo-max-pax-error" style="display:none;"></div>
																
																	<?php
																		foreach ($bundle->prices as $p => $c) {
																			echo '<input type="hidden" name="add[0]['.esc_attr($p).'_num]" rel="'.esc_attr($p).'_'.esc_attr($option_num).'_'.esc_attr($sub_option).'" value="" data-multiple="'.esc_attr($c).'" class="'.esc_attr($bundle->label).'_'.esc_attr($option_num).'_'.esc_attr($sub_option).'" disabled />'; ?>
																			
																			<script>
																				// copy over amt of price points in bundle
																				jQuery('#<?php echo esc_html($bundle->label); ?>_<?php echo esc_html($option_num.'_'.$sub_option); ?>').attr('data-<?php echo esc_html($p); ?>_num' , <?php echo esc_html($c); ?> );
																			</script>
																	<?php	}											
																	?>

														<script>
														jQuery(function($){
															$('#<?php echo esc_html($bundle->label); ?>_<?php echo esc_html($option_num.'_'.$sub_option); ?>').change(function(){
																if ($(this).val() <= 0) {
																	$('#decrease_<?php echo esc_html($bundle->label); ?>_<?php echo esc_html($option_num.'_'.$sub_option).'_'.esc_html($b); ?>').addClass('not-allowed');
																																		
																	// disable bundle inputs 
																	$('.<?php echo esc_html($bundle->label); ?>_<?php echo esc_html($option_num.'_'.$sub_option); ?>').attr('disabled', true).val('');
																} else {
																	$('#decrease_<?php echo esc_html($bundle->label); ?>_<?php echo esc_html($option_num.'_'.$sub_option).'_'.esc_html($b); ?>').removeClass('not-allowed');
																}
																// disable unwanted inputs
																if ($(this).val() < 0){
																	$(this).val('');
																	// reset associated bundle input values
																	$('.<?php echo esc_html($bundle->label); ?>_<?php echo esc_html($option_num.'_'.$sub_option); ?>').val('');
																} else if ($(this).val() > <?php echo $bundle->max; ?>){
																	// show an error message
																	let max_pax = <?php echo (int)$bundle->max; ?>;
																	let plural_start = max_pax > 1 ? 'are' : 'is';
																	let plural_end = max_pax > 1 ? 's' : '';
																	let err = 'There ' + plural_start + ' only ' + max_pax + ' space' + plural_end + ' available';
																	$('#max_pax_error_<?php echo esc_html($bundle->label); ?>_<?php echo esc_html($option_num.'_'.$sub_option); ?>').html(err);
																	$('#max_pax_error_<?php echo esc_html($bundle->label); ?>_<?php echo esc_html($option_num.'_'.$sub_option); ?>').slideDown();

																	// reset input 
																	$(this).val('');
																	$('#decrease_<?php echo esc_html($bundle->label); ?>_<?php echo esc_html($option_num.'_'.$sub_option); ?>').addClass('not-allowed');

																	setTimeout(() => {
																		$('#max_pax_error_<?php echo esc_html($bundle->label); ?>_<?php echo esc_html($option_num.'_'.$sub_option); ?>').slideUp();
																	}, 3500);

																} else if(!isInt($(this).val()) || $(this).val() === 0) {
																	$(this).val('');
																}
															});

															increaseBundle_<?php echo esc_html($option_num.'_'.$sub_option.'_'.$b); ?> = function(){
																let value = parseInt(document.getElementById('<?php echo esc_html($bundle->label); ?>_<?php echo esc_html($option_num.'_'.$sub_option); ?>').value);
																value = isNaN(value) ? 0 : value;
																value++;
																if (value > 0) {
																	$('#decrease_<?php echo esc_html($bundle->label); ?>_<?php echo esc_html($option_num.'_'.$sub_option).'_'.esc_html($b); ?>').removeClass('not-allowed');
																}

																if (value >= <?php echo $bundle->max; ?>) { 
																	$('#increase_<?php echo esc_html($bundle->label); ?>_<?php echo esc_html($option_num.'_'.$sub_option.'_'.$b); ?>').addClass('not-allowed');
																} else {
																	$('#increase_<?php echo esc_html($bundle->label); ?>_<?php echo esc_html($option_num.'_'.$sub_option.'_'.$b); ?>').removeClass('not-allowed');
																}

																document.getElementById('<?php echo esc_html($bundle->label); ?>_<?php echo esc_html($option_num.'_'.$sub_option); ?>').value = value;
																
																<?php foreach ($bundle->prices as $p => $c) { ?>
																	// populate pax object
																	running_pax_bundle_<?php echo esc_html($option_num.'_'.$sub_option.'_'.$b); ?>.<?php echo esc_html($p); ?> = value*<?php echo esc_html($c); ?>;
																<?php } ?>

																total_pax = 0;
																for (const [pax, amount] of Object.entries(running_pax_bundle_<?php echo esc_html($option_num.'_'.$sub_option.'_'.$b); ?>)) {
																	if (amount > 0) {
																		total_pax += amount;
																	}
																}
																// console.log(running_pax_bundle_<?php echo esc_html($option_num.'_'.$sub_option.'_'.$b); ?>);
															}

															decreaseBundle_<?php echo esc_html($option_num.'_'.$sub_option.'_'.$b); ?> = function(){
																let value = parseInt(document.getElementById('<?php echo esc_html($bundle->label); ?>_<?php echo esc_html($option_num.'_'.$sub_option); ?>').value);
																value = isNaN(value) ? 0 : value;
																if (value <= 0) {
																	return false;
																}
																value--;
																if (value <= 0) {
																	$('#decrease_<?php echo esc_html($bundle->label); ?>_<?php echo esc_html($option_num.'_'.$sub_option).'_'.esc_html($b); ?>').addClass('not-allowed');

																	// disable bundle inputs 
																	$('.<?php echo esc_html($bundle->label); ?>_<?php echo esc_html($option_num.'_'.$sub_option.'_'.$b); ?>').attr('disabled', true).val('');

																	// reset associated bundle input values
																	$('.<?php echo esc_html($bundle->label); ?>_<?php echo esc_html($option_num.'_'.$sub_option); ?>').val('');
																} 

																if (value <= <?php echo $bundle->max; ?>) { 
																	$('#increase_<?php echo esc_html($bundle->label); ?>_<?php echo esc_html($option_num.'_'.$sub_option.'_'.$b); ?>').removeClass('not-allowed');
																}
																document.getElementById('<?php echo esc_html($bundle->label); ?>_<?php echo esc_html($option_num.'_'.$sub_option); ?>').value = value;

																<?php foreach ($bundle->prices as $p => $c) { ?>
																	// populate pax object
																	running_pax_bundle_<?php echo esc_html($option_num.'_'.$sub_option.'_'.$b); ?>.<?php echo esc_html($p); ?> = value*<?php echo esc_html($c); ?>;
																<?php } ?>

																total_pax = 0;
																for (const [pax, amount] of Object.entries(running_pax_bundle_<?php echo esc_html($option_num.'_'.$sub_option.'_'.$b); ?>)) {
																	if (amount > 0) {
																		total_pax += amount;
																	}
																}
																// console.log(running_pax_bundle_<?php echo esc_html($option_num.'_'.$sub_option.'_'.$b); ?>);
															}

															});
														</script>

														</div>
													</div>
														
													<?php
														
													$b++;
												
													}  // if ($bundle->visible) ?>

												<?php } // foreach ($bundles)
													
												} // if (count($bundles))
													
													
												if ($b >= 1) {
													echo "<script> jQuery('#option_".esc_html($option_num)."_".esc_html($sub_option)." .rezgo-bundle-hidden').show();</script>";
													echo "<script> jQuery('#option_".esc_html($option_num)."_".esc_html($sub_option)." .pax-input-row').css('display','flex');</script>";
												}
																					
											?>

											<div class="text-danger rezgo-option-error" id="error_text_<?php echo esc_attr($option_num.'_'.$sub_option); ?>" style="display:none;"></div>
											<div class="text-danger rezgo-option-error" id="error_mobile_text_<?php echo esc_attr($option_num.'_'.$sub_option); ?>" style="display:none;"></div>
										</div><!-- end col-sm-8-->

										<?php if (REZGO_LITE_CONTAINER){ ?> 
											<input type="hidden" name="trigger_code" value="<?php echo (REZGO_LITE_CONTAINER) ? $site->promo_code : $site->cart_trigger_code; ?>">
											<input type="hidden" name="refid" value="<?php echo (REZGO_LITE_CONTAINER) ? $site->refid : ''; ?>">
										<?php } ?>

										<div class="col-12 float-end">
											<span class="btn-check"></span>
											<button type="submit" class="btn btn-block rezgo-btn-book rezgo-btn-add" value="addToCart" onclick="return check_<?php echo esc_js($option_num.'_'.$sub_option); ?>();"><span>Add To Order</span></button>
										</div>

										<?php if ($buy_as_gift && $prices_total > 0) { ?>
											<div class="col-12 float-end rezgo-buy-as-gift">
												<button id="rezgo_buy_as_gift_<?php echo esc_attr($option->uid); ?>" class="btn btn-block rezgo-buy-as-gift-btn underline-link" value="buyAsGift" onclick="return buy_as_gift_<?php echo esc_js($option_num.'_'.$sub_option); ?>();"><span>Buy as a Gift</span></button>
											</div>
										<?php } ?>
									</div>
								</form>
																
							<?php } else { ?>
								<div class="rezgo-order-unavailable"><span>Sorry, there is no availability for this option</span></div>
							<?php } // end if ($option->date->availability != 0) ?>
						</div>
					</div>
				</div>

				<?php $sub_option++; // increment sub option instead ?>
			<?php } // if ($_REQUEST['type']) ?>
      
    
    <?php } // end foreach($options as $option) ?>
</div>
  
<?php } else { // no availability, hide this option ?>
		<?php echo wp_kses($availability_title, ALLOWED_HTML); ?>
		<div class="panel panel-default panel-none-available">
			<div class="panel-body">
			<div class="rezgo-order-none-available"><span>Sorry, there are no available options on this day</span></div>
			</div>
		</div>
<?php } ?>

<?php
	if (isset($_SESSION['debug'])) {
		echo '<script>
		// output debug to console'."\n\n";
		foreach ($_SESSION['debug'] as $debug) {
			echo "window.console.log('".$debug."'); \n";
		}
		unset($_SESSION['debug']);
		echo '</script>';
	}
?>