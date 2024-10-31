<?php if ($_REQUEST['cross_sell']) { ?>	
	<script type="text/javascript" src="<?php echo esc_html($site->path); ?>/js/jquery.form.js"></script>
<?php } ?>

<?php
	$company = $site->getCompanyDetails();
	$availability_title = '';	
	$buy_as_gift = $site->showGiftCardPurchase() ? 1 : 0;
	$analytics_ga4 = $site->exists($site->getAnalyticsGa4()) ? 1 : 0;
	$analytics_gtm = $site->exists($site->getAnalyticsGtm()) ? 1 : 0;
	$date = sanitize_text_field($_REQUEST['date']);

	if ($_REQUEST['option_num']) {
		$option_num = sanitize_text_field($_REQUEST['option_num']);
	} else {
		$option_num = 1;	
		
		if ($_REQUEST['type'] != 'open') {
			
			if ($_REQUEST['js_timestamp']) {
				$now = sanitize_text_field($_REQUEST['js_timestamp']);
	  			date_default_timezone_set($_REQUEST['js_timezone']);

			} else {
				$now = time();
			}

			$php_now = time();
			
			$today = date('Y-m-d', $now);
			$selected_date = date('Y-m-d', strtotime($date));
			$available_day = date('D', strtotime($date));
			$available_date = date((string) $company->date_format, strtotime($date)); 

			$availability_title = '<div class="rezgo-date-options" style="display:none;"><span class="rezgo-calendar-avail"><span>Availability&nbsp;for: </span></span> <strong><span class="rezgo-avail-day">'.$available_day.',&nbsp;</span><span class="rezgo-avail-date">'.$available_date.'</span></strong>';
      
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

	$options = $site->getTours('t=com&q='.sanitize_text_field($_REQUEST['com']).$date_request.'&file=calendar_day');
	
?>

<?php if ($options) { ?>
	<?php echo wp_kses($availability_title, ALLOWED_HTML); ?>

	<?php if ($_REQUEST['cross_sell']) { ?>
	<script>

		jQuery(document).ready(function($){

			$('.panel-collapse').on('shown.bs.collapse', function () {

				let panel_offset = $(this).closest('.panel');
				
				$('div.rezgo-container').animate({
						scrollTop: $(panel_offset).offset().top
				}, 500);		
				
			})
		});
		
  </script>
  <?php } ?>

	<span class="rezgo-date-memo rezgo-calendar-date-<?php echo esc_attr($_REQUEST['date']); ?>"></span>

	<div class="panel-group" id="rezgo-select-option-<?php echo esc_attr($option_num); ?>">
		<?php if (count($options) != 1) { // && $option_num != 1 ?>
			<span class="rezgo-choose-options">Choose one of the options below <i class="fal fa-angle-double-down"></i></span>
		<?php }

		if ($_REQUEST['type'] == 'open') {
			$sub_option = 'o1';
		} else {
			$sub_option = 'a';
		}

		// get cart digest for validation below
		$cart_data = array();
		$cart_today = array();
			
		$cart = $site->getCart();

		if ($cart) {
			foreach ($cart as $cart_array) {
				$cart_data[] = array(
					'id' => (string) $cart_array->uid,
					'date' => (string) $cart_array->date['value'],
					'pax' => (string) $cart_array->pax
					// spaces vs pax?
				);
			}
			
			foreach ($cart_data as $data) {
				if ($data['date'] == $_REQUEST['date'] || $data['date'] == 'open') {
					$cart_today[$data['id']] += $data['pax'];
				}
			}
		}

		foreach($options as $option) { ?>

			<?php $dynamic_start_time = ((string)$option->time_format == 'dynamic') ? 1 : 0; ?>

			<?php $site->readItem($option);
		
				// if option is in cart and pax num exceeds availability
				if (array_key_exists((int) $option->uid, $cart_today) && ((int) $option->date->availability <= (int) $cart_today[(int) $option->uid])) {
					// set availability to 0 for this day
					$option->date->availability = 0;
				} elseif (array_key_exists((int) $option->uid, $cart_today) && ((int) $option->date->availability > (int) $cart_today[(int) $option->uid])) {
					// adjust availability for this day
					$option->date->availability = $option->date->availability - (int) $cart_today[(int) $option->uid];
				}
				
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
	
							function isInt(n) {
								 return n % 1 === 0;
							}

						// buy as a gift redirect
						function buy_as_gift_<?php echo esc_html($option_num.'_'.$sub_option); ?>(){
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
							<?php echo LOCATION_WINDOW; ?>.location.href='<?php echo $site->base ?>/gift-card/' + '<?php echo esc_html($option->uid); ?>/' + '<?php echo esc_html($option->date->value); ?>/' + gift_pax;

							return false;
						}
	
							// validate form data
							function check_<?php echo esc_html($option_num.'_'.$sub_option); ?>() {
								var err;
								var count_<?php echo esc_html($option_num.'_'.$sub_option); ?> = 0;
								var required_<?php echo esc_html($option_num.'_'.$sub_option); ?> = 0;
	
								for(v in fields_<?php echo esc_html($option_num.'_'.$sub_option); ?>) {
									
									if (jQuery('#' + v).attr('rel') == 'bundle' && jQuery('#' + v).val() >= 1) {
										
										jQuery('.' + v).each(function() {
											let multiple = jQuery(this).data('multiple');
											let val = jQuery('#' + v).val();
											let newval = multiple * val;
											let rel = jQuery(this).attr('rel');
											
											count_<?php echo esc_html($option_num.'_'.$sub_option); ?> += newval; // increment total
											
											if(fields_<?php echo esc_html($option_num.'_'.$sub_option); ?>[rel]) { required_<?php echo esc_html($option_num.'_'.$sub_option); ?> = 1; }
											
											if((count_<?php echo esc_html($option_num.'_'.$sub_option); ?> <= <?php echo esc_html($option->date->availability); ?>) && (count_<?php echo esc_html($option_num.'_'.$sub_option); ?> <= 150)) {
												jQuery(this).attr('disabled', false).val(newval);
											}
											
										});									
									
									} else {
										
										count_<?php echo esc_html($option_num.'_'.$sub_option); ?> += jQuery('#' + v).val() * 1; // increment total
										
									}
									
									// has a required price point been used
									if(fields_<?php echo esc_html($option_num.'_'.$sub_option); ?>[v] && jQuery('#' + v).val() >= 1) { required_<?php echo esc_html($option_num.'_'.$sub_option); ?> = 1; 
									}

									// negative (-) symbol not allowed on PAX field
									if (jQuery('#' + v).val() < 0) 
									{
									    err = 'Please enter valid number for booking.';
								    }
								}
		
								if (jQuery('#book_time_<?php echo esc_html($option_num.'_'.$sub_option); ?>').length && !jQuery('#book_time_<?php echo esc_html($option_num.'_'.$sub_option); ?>').val()) {
									err = 'Please select a starting time';
									jQuery('#select_time_<?php echo esc_html($option->uid); ?>').addClass('error');
									setTimeout(() => {
										jQuery('#select_time_<?php echo esc_html($option->uid); ?>').removeClass('error');
									}, 2500);
								} else if(count_<?php echo esc_html($option_num.'_'.$sub_option); ?> == 0 || !count_<?php echo esc_html($option_num.'_'.$sub_option); ?>) {
									err = 'Please enter the number you would like to book.';
								} else if(required_num_<?php echo esc_html($option_num.'_'.$sub_option); ?> > 0 && required_<?php echo esc_html($option_num.'_'.$sub_option); ?> == 0) {
									err = 'At least one marked ( * ) price point is required to book.';
								} else if(!isInt(count_<?php echo esc_html($option_num.'_'.$sub_option); ?>)) {
									err = 'Please enter a whole number. No decimal places allowed.';
								} else if(count_<?php echo esc_html($option_num.'_'.$sub_option); ?> < <?php echo esc_html($option->per); ?>) {
									err = '<?php echo esc_html($option->per); ?> minimum required to book.';
								} else if(count_<?php echo esc_html($option_num.'_'.$sub_option); ?> > <?php echo esc_html($option->date->availability); ?>) {
									err = 'There is not enough availability to book ' + count_<?php echo esc_html($option_num.'_'.$sub_option); ?>;
								} else if(count_<?php echo esc_html($option_num.'_'.$sub_option); ?> > 250) {
									err = 'You can not book more than 250 spaces in a single booking.';
								}
								<?php if ($option->block_size) { ?>
									// prevent adding to cart if the user is trying to book for more than the block size availability
									else if(Math.ceil(count_<?php echo esc_html($option_num.'_'.$sub_option); ?> / <?php echo esc_html($option->block_size); ?>) * <?php echo esc_html($option->block_size); ?> > <?php echo esc_html($option->date->availability); ?>) {
										err = 'There is not enough availability to book ' + count_<?php echo esc_html($option_num.'_'.$sub_option); ?>;
									}
								<?php } ?>
								<?php if ($option->max_guests > 0) { ?>
								else if(count_<?php echo esc_html($option_num.'_'.$sub_option); ?> > <?php echo esc_html($option->max_guests); ?>) {
									err = 'There is a maximum of <?php echo esc_html($option->max_guests); ?> per booking.';
								}
								<?php } ?>
	
								if(err) {
									
									<?php if(!$site->config('REZGO_MOBILE_XML')) { ?>
										jQuery('#error_text_<?php echo esc_html($option_num.'_'.$sub_option); ?>').html(err);
										jQuery('#error_text_<?php echo esc_html($option_num.'_'.$sub_option); ?>').slideDown().delay(2000).slideUp('slow');
									<?php } else { ?>
										jQuery('#error_mobile_text_<?php echo esc_html($option_num.'_'.$sub_option); ?>').html(err);
										jQuery('#error_mobile_text_<?php echo esc_html($option_num.'_'.$sub_option); ?>').slideDown().delay(2000).slideUp('slow');
									<?php } ?>
									return false;
									
								} else {
									
									// prepare inputs before submitting (*bundles)							
									let inputs = new Object(); // create new object
									
									jQuery("#checkout_<?php echo esc_html($option_num.'_'.$sub_option); ?> input").each(function() {
										
										if (this.name != '') {
											let index = this.name; // set variable prop as input name
											let val;
											
											if (this.value == '') { val = 0; } else { val = parseInt(this.value); }
											
											if ( inputs.hasOwnProperty(index) == true ) { // check if prop exists 
												jQuery(this).val(val + parseInt(inputs[index])); // update value of current input, adding current prop val 
												inputs[index] += val; // update this prop
											} else {
												inputs[index] = val; // set first val of this prop
											}			

										}

									});

									// addCart() request
									jQuery('#checkout_<?php echo esc_html($option_num.'_'.$sub_option); ?>').submit( function(e) {
										e.preventDefault();

										jQuery('#checkout_<?php echo esc_html($option_num.'_'.$sub_option); ?>').ajaxSubmit({
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
															value: total_pax_<?php echo esc_html($option_num.'_'.$sub_option); ?>,
															items: [
																{
																	item_id: "<?php echo esc_html($option->uid); ?>",
																	item_name: "<?php echo esc_html($option->item . ' - ' . $option->option); ?>",
																	currency: "<?php echo esc_html($option->currency_base); ?>",
																	coupon: "<?php echo $_COOKIE['rezgo_promo'] ? esc_html($_COOKIE['rezgo_promo']) : esc_html($site->cart_trigger_code); ?>",
																	price: total_pax_<?php echo esc_html($option_num.'_'.$sub_option); ?>,
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
																		price: total_pax_<?php echo esc_html($option_num.'_'.$sub_option); ?>,
																		quantity: 1,
																		coupon: "<?php echo $_COOKIE['rezgo_promo'] ? esc_html($_COOKIE['rezgo_promo']) : esc_html($site->cart_trigger_code); ?>",
																	},
																]
															}
														});
													}
												<?php } ?>

												// no error from adding item to cart 
												if (response == null) {
													localStorage.clear();

													<?php if ($analytics_ga4) { ?>
														ga4_add_to_cart();
													<?php } ?>

													<?php if ($analytics_gtm) { ?>
														gtm_add_to_cart();
													<?php } ?>

													<?php if (!DEBUG){ 
														// add cart token to the redirect
														$cart_token = sanitize_text_field($_COOKIE['rezgo_cart_token_'.REZGO_CID]); ?>

														<?php if ($_REQUEST['cross_sell']) { ?>	
															
															let parentContainer = window.parent.parent;
															parentContainer.document.getElementById('rezgo-cross-dismiss').click();
															parentContainer.location.reload();

														<?php } else { ?>

                                                            top.location.href = '<?php echo esc_html($site->base).'/order';?>';

														<?php } ?>
														
													<?php } else { ?>
														alert('<?php echo esc_js($option->uid); ?>' + ' - ' + '<?php echo esc_js($_REQUEST['date']); ?>' + ' added');
													<?php } ?>
												} else {
													let err = response.message;

													<?php if(!$site->config('REZGO_MOBILE_XML')) { ?>
														jQuery('#error_text_<?php echo esc_html($option_num.'_'.$sub_option); ?>').html(err);
														jQuery('#error_text_<?php echo esc_html($option_num.'_'.$sub_option); ?>').slideDown().delay(2000).slideUp('slow');;
													<?php } else { ?>
														jQuery('#error_mobile_text_<?php echo esc_html($option_num.'_'.$sub_option); ?>').html(err);
														jQuery('#error_mobile_text_<?php echo esc_html($option_num.'_'.$sub_option); ?>').slideDown().delay(2000).slideUp('slow');;
													<?php } ?>
												}

											},
											error: function(error){
												console.log(error);
											}
										});

									});
									
								}
							}

							<?php if ( (REZGO_WORDPRESS) && ($_REQUEST['cross_sell']) ) { ?>
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
	
							<a data-toggle="collapse" data-parent="#rezgo-select-option-<?php echo esc_attr($option_num.'_'.$sub_option); ?>" data-target="#option_<?php echo esc_attr($option_num.'_'.$sub_option); ?>" class="panel-heading panel-title rezgo-panel-option-link" id="panel_<?php echo esc_attr($option_num.'_'.$sub_option); ?>">

								<script>
									jQuery('#panel_<?php echo esc_html($option_num.'_'.$sub_option); ?>').click( function(){
										jQuery(this).find('i.fa-angle-right').toggleClass('active');
									});
									var pax_<?php echo esc_html($option_num.'_'.$sub_option); ?> = {
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

								<div class="rezgo-panel-option"><i class="fal fa-angle-right <?php echo (((count($options) == 1 && $option_num == 1) || $_REQUEST['id'] == (int) $option->uid) ? ' active' : '')?>" aria-hidden="true"></i> &nbsp; <?php echo esc_html($option->option); ?> 
								
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
							<div id="option_<?php echo esc_attr($option_num.'_'.$sub_option); ?>" class="option-panel-<?php echo esc_attr($option->uid); ?> panel-collapse collapse<?php echo(((count($options) == 1 && $option_num == 1) || $_REQUEST['id'] == (int) $option->uid) ? ' in' : '')?>">
							<div class="panel-body">
								<?php if ($option->date->availability != 0 && $block_available) { 

									  if ($_REQUEST['cross_sell']) {
											$form_target = 'target="_parent"';
											$site->base = home_url('/', 'https').sanitize_text_field($_REQUEST['wp_slug']);
										} else {
											$form_target = 'target="rezgo_content_frame"';
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
								
										<?php if ($option->time != '') { ?>
											<span class="rezgo-memo rezgo-time"><strong>Time:</strong> <?php echo (string) esc_html($option->time); ?><br /></span>	
										<?php } ?>
	
										<?php if ($dynamic_start_time) { ?>

											<div class="time-select-container">
												<a id="select_time_<?php echo esc_html($option->uid); ?>" class="time-option-select"> 
													<i class="far fa-clock"></i>
													<span class="time-option-select-copy">
														<span class="top-placeholder">Start Time</span>

														<span id="time_selected_<?php echo esc_html($option->uid); ?>">
															<span class="default">Select a Time</span>
															<span class="custom"></span>
														</span>
													</span>
													<span id="selected"></span>

													<i class="fas fa-chevron-down"></i> 
												</a>
												<p class="rezgo-time-option-error" style="display:none;"></p>

												<div id="time-option-container-<?php echo esc_html($option->uid); ?>" class="time-option-container">
												<?php foreach ($option->time_data->time as $time) { ?>
													<?php if ((int)$time->av > 0) { ?>
														<p id="time_select_<?php echo esc_html($option->uid); ?>_<?php echo esc_html($i); ?>" class="time_select_<?php echo esc_html($option->uid); ?>" data-book-time="<?php echo esc_html($time->id); ?>">
															<?php echo (string)$time->id; ?>
															<?php if (!$option->date->hide_availability) { ?>
																<span class="availability"><i class="fas fa-circle"></i> &nbsp;<?php echo (int)$time->av; ?> left</span>
															<?php } ?>
														</p>
													<?php } ?>
												<?php $i++; } // foreach ($option->time_data->time as $time) ?> 
												</div>
												
											</div>

											<script>
												// Dynamic start time selector
												jQuery('#select_time_<?php echo esc_html($option->uid); ?>').click(function(){
													if(jQuery('#time-option-container-<?php echo esc_html($option->uid); ?>').children().length > 0){
														jQuery('#time-option-container-<?php echo esc_html($option->uid); ?>').toggleClass('open');
													}
												});

												jQuery('.time_select_<?php echo esc_html($option->uid); ?>').click(function(){
													let book_time = jQuery(this).data('book-time');
													jQuery('input[name="add[0][book_time]"]').val(book_time);
													jQuery('#time-option-container-<?php echo esc_html($option->uid); ?>').removeClass('open');
													jQuery('#time_selected_<?php echo esc_html($option->uid); ?>').html(book_time);
												});
											</script>

										<?php } // if ($dynamic_start_time) ?>

										<input type="hidden" name="add[0][uid]" value="<?php echo esc_html($option->uid); ?>">
										<input type="hidden" name="add[0][date]" value="<?php echo esc_html($_REQUEST['date']); ?>">
										<?php if ($dynamic_start_time) { ?>
											<input id="book_time_<?php echo esc_html($option_num.'_'.$sub_option); ?>" type="hidden" name="add[0][book_time]" value="">
										<?php } ?>

									<div class="row"> 
										<div class="col-xs-12 rezgo-order-fields">
											
											<?php $prices = $site->getTourPrices($option); ?>
	
												<?php if($site->getTourRequired() == 1) { ?>
													<span class="rezgo-memo">At least one marked ( <em><i class="fa fa-asterisk"></i></em> ) price point is required.</span>
												<?php } ?>
	
												<?php if($option->per > 1) { ?>
													<span class="rezgo-memo">At least <?php echo esc_html($option->per); ?> are required to book.</span>
												<?php } ?>

												<?php if ($option->block_size) { ?>
													<span class="rezgo-memo">Books in blocks of <?php echo esc_html($option->block_size); ?></span>
												<?php } ?>
	
												<!-- <div class="clearfix">&nbsp;</div> -->
	
											<?php $total_required = 0; ?>
											<?php $animation_order = 1; ?>
											<?php $prices_total = 0; ?>

											<script>
												total_pax_<?php echo esc_html($option_num.'_'.$sub_option); ?> = 0;
												running_pax_<?php echo esc_html($option_num.'_'.$sub_option); ?> = {
													<?php foreach ($prices as $price) { 
														echo "'" .$price->name. "'" .':'.'0'. ',';
													} ?> 
												};
											</script>

											<?php foreach( $prices as $price ) { ?>

												<?php // check if total for all price points equals to zero
													$original_price = $price->base ? $price->base : $price->price;
													$prices_total += $original_price;
												?>

												<script>fields_<?php echo esc_html($option_num.'_'.$sub_option); ?>['<?php echo esc_html($price->name); ?>_<?php echo esc_html($option_num.'_'.$sub_option); ?>'] = <?php echo (($price->required) ? 1 : 0)?>;</script>

												<div class="edit-pax-wrp" style="--animation-order: <?php echo esc_attr($animation_order); ?>;">
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
												</div>

												<div class="pax-price-container">
													<div class="form-group row pax-input-row left-col">

														<div class="edit-pax-container">
															<div class="minus-pax-container">
																<a id="decrease_<?php echo esc_attr($price->name); ?>_<?php echo esc_attr($option_num.'_'.$sub_option); ?>" class="not-allowed" onclick="decreasePax_<?php echo esc_js($price->name); ?>_<?php echo esc_js($option_num.'_'.$sub_option); ?>()">
																	<i class="fa fa-minus"></i>
																</a>
															</div>
															<div class="input-container">
																<input type="number" name="add[0][<?php echo esc_attr($price->name); ?>_num]" value="<?php echo esc_attr($_REQUEST[$price->name.'_num']); ?>" id="<?php echo esc_attr($price->name); ?>_<?php echo esc_attr($option_num.'_'.$sub_option); ?>" class="pax-input" value="0" min="0" placeholder="0">
															</div>
															<div class="add-pax-container">
																<a onclick="increasePax_<?php echo esc_js($price->name); ?>_<?php echo esc_js($option_num.'_'.$sub_option); ?>()">
																	<i class="fa fa-plus"></i>
																</a>
															</div>	
														</div>
													</div>

													<div class="right-col">
														<div class="edit-pax-label-container">
															<label for="<?php echo esc_attr($price->name); ?>_<?php echo esc_attr($option_num.'_'.$sub_option); ?>" class="control-label rezgo-label-margin rezgo-label-padding-left">

																<!-- if both strike prices and discount exists, show the higher price -->
																<?php
																	$initial_price = (float) $price->price;
																	$strike_price = (float) $price->strike;
																	$discount_price = (float) $price->base;
																?>
																<span class="rezgo-pax-price">
																<?php if ( ($site->exists($price->strike)) && ($site->exists($price->base)) )  { ?>
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

																<?php } else if($site->exists($price->base)) { ?>

																		<span class="discount">
																			<?php echo esc_html($site->formatCurrency($price->base)); ?>
																		</span><br>

																<?php } ?>

																	<?php echo esc_html($site->formatCurrency($price->price)); ?>
																</span>
															</label>
														</div>
													</div>
		
													<?php if ($price->required) $total_required++; ?>

													<script>
													// prepare values insert in addCart() request 
													jQuery('#<?php echo esc_html($price->name); ?>_<?php echo esc_html($option_num.'_'.$sub_option); ?>').change(function(){
														<?php echo esc_html($price->name); ?>_num = $(this).val();
														if ($(this).val() <= 0) {
															jQuery('#decrease_<?php echo esc_html($price->name); ?>_<?php echo esc_html($option_num.'_'.$sub_option); ?>').addClass('not-allowed');
														} else {
															jQuery('#decrease_<?php echo esc_html($price->name); ?>_<?php echo esc_html($option_num.'_'.$sub_option); ?>').removeClass('not-allowed');
														}
														// disable unwanted inputs
														if ($(this).val() < 0){
															$(this).val(0);
															running_pax_<?php echo esc_html($option_num.'_'.$sub_option); ?>.<?php echo esc_html($price->name); ?> = 0;
														} else if(!isInt($(this).val()) || $(this).val() === 0) {
															$(this).val(0);
															running_pax_<?php echo esc_html($option_num.'_'.$sub_option); ?>.<?php echo esc_html($price->name); ?> = 0;
														} else {
															running_pax_<?php echo esc_html($option_num.'_'.$sub_option); ?>.<?php echo esc_html($price->name); ?> = parseInt($(this).val());
														}

														total_pax = 0;
														for (const [pax, amount] of Object.entries(running_pax_<?php echo esc_html($option_num.'_'.$sub_option); ?>)) {
															if (amount > 0) {
																total_pax += parseInt(amount);
															}
														}
													});

													function increasePax_<?php echo esc_html($price->name); ?>_<?php echo esc_html($option_num.'_'.$sub_option); ?>(){
															let value = parseInt(document.getElementById('<?php echo esc_html($price->name); ?>_<?php echo esc_html($option_num.'_'.$sub_option); ?>').value);
															value = isNaN(value) ? 0 : value;
															value++;
															if (value > 0) { 
																jQuery('#decrease_<?php echo esc_html($price->name); ?>_<?php echo esc_html($option_num.'_'.$sub_option); ?>').removeClass('not-allowed');
															}
															document.getElementById('<?php echo esc_html($price->name); ?>_<?php echo esc_html($option_num.'_'.$sub_option); ?>').value = value;

															// populate pax object
															pax_<?php echo esc_html($option_num.'_'.$sub_option); ?>['<?php echo esc_html($price->name); ?>'] = value;

															running_pax_<?php echo esc_html($option_num.'_'.$sub_option); ?>.<?php echo esc_html($price->name); ?> = value;
															total_pax = 0;
															for (const [pax, amount] of Object.entries(running_pax_<?php echo esc_html($option_num.'_'.$sub_option); ?>)) {
																if (amount > 0) {
																	total_pax += amount;
																}
															}
														}

													function decreasePax_<?php echo esc_html($price->name); ?>_<?php echo esc_html($option_num.'_'.$sub_option); ?>(){
															let value = parseInt(document.getElementById('<?php echo esc_html($price->name); ?>_<?php echo esc_html($option_num.'_'.$sub_option); ?>').value);
															value = isNaN(value) ? 0 : value;
															if (value <= 0) {
																return false;
															}
															value--;
															if (value <= 0) {
																jQuery('#decrease_<?php echo esc_html($price->name); ?>_<?php echo esc_html($option_num.'_'.$sub_option); ?>').addClass('not-allowed');
															} 
															document.getElementById('<?php echo esc_html($price->name); ?>_<?php echo esc_html($option_num.'_'.$sub_option); ?>').value = value;

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

												</script>

											</div>
											</div>
								
											<?php $animation_order++; }
											// end foreach( $site->getTourPrices() ?>
	
												<script>required_num_<?php echo esc_html($option_num.'_'.$sub_option); ?> = <?php echo esc_html($total_required); ?>;</script>
												
												<?php
												
													$bundles = $site->getTourBundles($option);	
													
													if (count($bundles) > 0) { ?>
														
														<?php
														$b = 0;
														
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
																</div>

																<div class="pax-price-container">
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
																				<a onclick="increaseBundle_<?php echo esc_js($option_num.'_'.$sub_option.'_'.$b); ?>()">
																					<i class="fa fa-plus"></i>
																				</a>
																			</div>	
																		</div>	
																	</div>

																	<div class="right-col">
																		<div class="edit-pax-label-container rezgo-bundle-hidden">
																			<label for="<?php echo esc_attr($bundle->label); ?>_<?php echo esc_attr($option_num.'_'.$sub_option.'_'.$b); ?>" class="control-label rezgo-label-margin rezgo-label-padding-left">
																				<span class="rezgo-pax-price"><?php echo esc_html($site->formatCurrency($bundle->price)); ?></span><br />
																			</label>
																		</div>
																	</div>
																	
																		<?php
																			foreach ($bundle->prices as $p => $c) {
																				echo '<input type="hidden" name="add[0]['.esc_attr($p).'_num]" rel="'.esc_attr($p).'_'.esc_attr($option_num).'_'.esc_attr($sub_option).'" value="" data-multiple="'.esc_attr($c).'" class="'.esc_attr($bundle->label).'_'.esc_attr($option_num).'_'.esc_attr($sub_option).'" disabled />'; ?>
																				
																				<script>
																					// copy over amt of price points in bundle
																					jQuery('#<?php echo esc_html($bundle->label); ?>_<?php echo esc_html($option_num.'_'.$sub_option); ?>').attr('data-<?php echo esc_html($p); ?>_num' , <?php echo esc_html($c); ?> );
																				</script>
																		<?php	}
																		?>
																</div>
															</div>

															<script>
																jQuery('#<?php echo esc_html($bundle->label); ?>_<?php echo esc_html($option_num.'_'.$sub_option); ?>').change(function(){
																	if (jQuery(this).val() <= 0) {
																		jQuery('#decrease_<?php echo esc_html($bundle->label); ?>_<?php echo esc_html($option_num.'_'.$sub_option.'_'.$b); ?>').addClass('not-allowed');
																	} else {
																		jQuery('#decrease_<?php echo esc_html($bundle->label); ?>_<?php echo esc_html($option_num.'_'.$sub_option.'_'.$b); ?>').removeClass('not-allowed');
																	}

																	// disable unwanted inputs
																	if ($(this).val() < 0){
																		$(this).val(0);
																	} else if(!isInt($(this).val()) || $(this).val() === 0) {
																		$(this).val(0);
																	}
																});

																function increaseBundle_<?php echo esc_html($option_num.'_'.$sub_option.'_'.$b); ?>(){
																	let value = parseInt(document.getElementById('<?php echo esc_html($bundle->label); ?>_<?php echo esc_html($option_num.'_'.$sub_option); ?>').value);
																	value = isNaN(value) ? 0 : value;
																	value++;
																	if (value > 0) {
																		jQuery('#decrease_<?php echo esc_html($bundle->label); ?>_<?php echo esc_html($option_num.'_'.$sub_option.'_'.$b); ?>').removeClass('not-allowed');
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

																function decreaseBundle_<?php echo esc_html($option_num.'_'.$sub_option.'_'.$b); ?>(){
																	let value = parseInt(document.getElementById('<?php echo esc_html($bundle->label); ?>_<?php echo esc_html($option_num.'_'.$sub_option); ?>').value);
																	value = isNaN(value) ? 0 : value;
																	if (value <= 0) {
																		return false;
																	}
																	value--;
																	if (value <= 0) {
																		jQuery('#decrease_<?php echo esc_html($bundle->label); ?>_<?php echo esc_html($option_num.'_'.$sub_option.'_'.$b); ?>').addClass('not-allowed');
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

															</script>
															
															<?php
															
															$b++;
													
															} // if ($bundle->visible)
															
														} // foreach ($bundles)
														
													} // if (count($bundles))
													
													
													if ($b >= 1) {
														echo "<script> jQuery('#option_".esc_html($option_num)."_".esc_html($sub_option)." .rezgo-bundle-hidden').show();</script>";
														echo "<script> jQuery('#option_".esc_html($option_num)."_".esc_html($sub_option)." .pax-input-row').css('display','flex');</script>";
													}
																						
												?>

												<div class="text-danger rezgo-option-error" id="error_text_<?php echo esc_attr($option_num.'_'.$sub_option); ?>" style="display:none;"></div>
												<div class="text-danger rezgo-option-error" id="error_mobile_text_<?php echo esc_attr($option_num.'_'.$sub_option); ?>" style="display:none;"></div>
											</div><!-- end col-sm-8-->
	
											<div class="col-xs-12 pull-right">
												<button type="submit" class="btn btn-block rezgo-btn-book rezgo-btn-add" value="addToCart" onclick="return check_<?php echo esc_js($option_num.'_'.$sub_option); ?>();">Add to Order</button>
											</div>
											
											<?php if ($buy_as_gift && $prices_total > 0) { ?>
												<div class="col-xs-12 pull-right rezgo-buy-as-gift">
													<button id="rezgo_buy_as_gift_<?php echo esc_html($option->uid); ?>" class="btn btn-block rezgo-buy-as-gift-btn underline-link" value="buyAsGift" onclick="return buy_as_gift_<?php echo esc_html($option_num.'_'.$sub_option); ?>();"><span>Buy as a Gift</span></button>
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
	if ($_SESSION['debug']) {
		echo '<script>
		// output debug to console'."\n\n";
		foreach ($_SESSION['debug'] as $debug) {
			echo "window.console.log('".$debug."'); \n";
		}
		unset($_SESSION['debug']);
		echo '</script>';
	}
?>