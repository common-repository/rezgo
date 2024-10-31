<?php 
	$date = sanitize_text_field($_REQUEST['date']);
	$formatted_date = date((string) $_REQUEST['date_format'], strtotime($date)); 

	// data from booking_edit
	// -- set to always disallow for now
	$current_booking_pax = sanitize_text_field($_REQUEST['current_booking_pax']);
	$booking_uid = sanitize_text_field($_REQUEST['uid']);
	$booking_type = sanitize_text_field($_REQUEST['type']);
	$booking_date = $booking_type == 'open' ? 'open' : sanitize_text_field($_REQUEST['booking_date']);
	$booking_time = sanitize_text_field($_REQUEST['booking_time']);
	$src = sanitize_text_field($_REQUEST['src']);
	$trans_num = sanitize_text_field($_REQUEST['trans_num']);
	$trigger_code = sanitize_text_field($_REQUEST['trigger_code']) ?? '';

	$current_booking_pax = stripslashes(html_entity_decode($current_booking_pax));
	$current_booking_pax = json_decode($current_booking_pax, true);
	$total_pax_number = sanitize_text_field($_REQUEST['total_pax_number']);
	$parent_url = sanitize_text_field($_REQUEST['parent_url']);

	if ($_REQUEST['option_num']) {
		$option_num = sanitize_text_field($_REQUEST['option_num']);
	} else {
		$option_num = 1;	
	}

	if ($_REQUEST['date'] != 'open') {
		$date_request = '&d='.$date;
	} else {
		$date_request = '';
	}
	$option = $site->getTours('t=uid&q='.sanitize_text_field($booking_uid).$date_request.'&a=subtract_cart_availability&file=calendar_day_edit&trigger_code='.$trigger_code);
	$option = $option[0];
?>

	<div class="panel-group" id="rezgo-select-option-<?php echo esc_attr($option_num); ?>">

		<?php $dynamic_start_time = ((string)$option->time_format == 'dynamic') ? 1 : 0;

			// don't mix open options with calendar options
			// only return options that match the request type
			if ((($_REQUEST['type'] == 'calendar' || $_REQUEST['type'] == 'single') && (string) $option->date['value'] != 'open') 
				|| ($_REQUEST['type'] == 'open' && (string) $option->date['value'] == 'open' )
			) { ?>
				<div class="panel <?php echo esc_attr($panel_unclass.$block_unclass); ?>">
					<script>
						var fields_<?php echo esc_html($option_num); ?> = new Array();
						var required_num_<?php echo esc_html($option_num); ?> = 0;
						var price_av_obj_<?php echo esc_html($option_num); ?> = {};

						jQuery(function($){
							function isInt(n) {
								return n % 1 === 0;
							}

							// validate form data for booking edit changes to date / time
							check_edit_date_time_<?php echo esc_html($option_num); ?> = function () {
								let err;

							<?php if ($dynamic_start_time) { ?>
								if ($('#book_time_<?php echo esc_html($option_num); ?>').length && !$('#book_time_<?php echo esc_html($option_num); ?>').val()) {
									err = 'Please select a starting time';
									$('#select_time_<?php echo esc_html($option->uid); ?>').addClass('error');
									setTimeout(() => {
										$('#select_time_<?php echo esc_html($option->uid); ?>').removeClass('error');
									}, 2500);
								} 
							<?php if ($booking_date == $_REQUEST['date']) { ?> 
								else if ($('#book_time_<?php echo esc_html($option_num); ?>').val() == '<?php echo $booking_time; ?>'){
									err = 'This booking is already booked for this time';
								}
							<?php } ?>

							<?php } else { ?>

								if ($('#book_date_<?php echo esc_html($option_num); ?>').val() == '<?php echo $booking_date; ?>'){
									err = 'This booking is already booked for this date';
								}

							<?php } ?>

								if(err) {
									
									<?php if(!$site->config('REZGO_MOBILE_XML')) { ?>
										$('#error_text_<?php echo esc_html($option_num); ?>').html(err);
										$('#error_text_<?php echo esc_html($option_num); ?>').slideDown().delay(2000).slideUp('slow');
									<?php } else { ?>
										$('#error_mobile_text_<?php echo esc_html($option_num); ?>').html(err);
										$('#error_mobile_text_<?php echo esc_html($option_num); ?>').slideDown().delay(2000).slideUp('slow');
									<?php } ?>
									return false;
									
								} else {

									$('.rezgo-btn-add-booking-edit').attr('disabled', true);

									$('#checkout_<?php echo esc_html($option_num); ?>').ajaxSubmit({
										type: 'POST',
										url: '<?php echo admin_url('admin-ajax.php'); ?>' + '?action=rezgo&method=booking_edit_ajax', 
										data: { 
											rezgoAction: 'edit_date_time',
											item_id: '<?php echo $booking_uid; ?>',
											booking_date: '<?php echo $booking_date; ?>',
											booking_time: '<?php echo $booking_time; ?>',
											trans_num: '<?php echo $trans_num; ?>',
										},
										success: function(data){
											let response = JSON.parse(data);

											if (response.status == 1) {

												<?php $back_link = $parent_url.'/edit/'.$site->encode($trans_num); ?>
												<?php echo LOCATION_WINDOW; ?>.location.href= "<?php echo $back_link; ?>";

											} else {
												let err = response.message;

												$('.rezgo-btn-add-booking-edit').attr('disabled', false);

												// show a toast error if there is an API error
												if (response.status == 3) {
													<?php echo LOCATION_WINDOW; ?>.location.reload();
													return false;
												}

												<?php if(!$site->config('REZGO_MOBILE_XML')) { ?>
													$('#error_text_<?php echo esc_html($option_num); ?>').html(err);
													$('#error_text_<?php echo esc_html($option_num); ?>').slideDown().delay(2000);
												<?php } else { ?>
													$('#error_mobile_text_<?php echo esc_html($option_num); ?>').html(err);
													$('#error_mobile_text_<?php echo esc_html($option_num); ?>').slideDown().delay(2000);
												<?php } ?>

											}

										},
										error: function(error){
											console.log(error);
										}
									});

								}
								return false;
							}
						});
						</script>
						
						<div id="option_<?php echo $option_num; ?>" class="option-panel-<?php echo $option->uid; ?> panel-collapse collapse show">
							<div class="rezgo-date-loader"></div>

								<?php if ($option->date->availability != 0) { ?>
								
								<?php
								if(REZGO_LITE_CONTAINER) {
									if ($_REQUEST['cross_sell']) {
										$form_target = 'target="_parent"';
									} else {
										$form_target = 'target="rezgo_content_frame"';
									}
								} else {
									$form_target = ''; 
								}
								
								?>

								<span class="rezgo-option-memo rezgo-option-<?php echo esc_attr($option->uid); ?> rezgo-option-date-<?php echo esc_attr($_REQUEST['date']); ?>"></span>
								<form class="rezgo-order-form" method="post" id="checkout_<?php echo esc_attr($option_num); ?>" <?php echo esc_attr($form_target); ?>>

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
												<span id="time_selected_availability_<?php echo esc_html($option_num); ?>" style="display:none;"></span>

												<i class="fas fa-chevron-down"></i> 
											</a>
											<p class="rezgo-time-option-error" style="display:none;"></p>

											<div id="time-option-container-<?php echo esc_html($option->uid); ?>" class="time-option-container">

												<script>
													price_av_obj_<?php echo esc_html($option_num); ?> = {
														<?php foreach ($option->date->time_data->time as $time) { ?>
															'<?php echo $time->id; ?>' : '<?php echo json_encode($time->prices); ?>',
														<?php } ?>
													};
												</script>
											
												<?php foreach ($option->date->time_data->time as $time) { ?>
													<?php if ((int)$time->av > 0 && (int)$time->av >= $total_pax_number) { ?>
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
												$('input[name="add[0][book_time]"]').val(book_time);
												$('#time-option-container-<?php echo $option->uid; ?>').removeClass('open');
												$('#time_selected_<?php echo $option->uid; ?>').html(book_time);
												$('#time_selected_availability_<?php echo $option_num; ?>').html(book_time_avail);

												$('#booking_edit_time_changes').show();
												$('#booking_edit_time_selected').html(book_time);

												if( price_av_obj_<?php echo esc_html($option_num); ?>.hasOwnProperty(book_time) ) {
													price_tier_av = price_av_obj_<?php echo esc_html($option_num); ?>[book_time];
													price_tier_av = JSON.parse(price_tier_av);

													if (price_tier_av.price) {
														for(let i = 0; i < price_tier_av.price.length; i++) {
															let price_tier = price_tier_av.price[i];
															let availability = parseInt(price_tier.av);
															let existing_val = $('#' + price_tier_array[i] +'_<?php echo esc_html($option_num); ?>').val();
															// console.log(price_tier_array[i] + ' : ' + availability);

															$('#price_tier_div_' + i + '_<?php echo $option_num; ?>').show();
															$('#max_pax_error_'+ price_tier_array[i] +'_<?php echo esc_html($option_num); ?>').hide();

															// toggle change event listeners
															$('#'+ price_tier_array[i] +'_<?php echo esc_html($option_num); ?>').off('change');
															$('#'+ price_tier_array[i] +'_<?php echo esc_html($option_num); ?>').change(function(){

																// disable unwanted inputs
																if ($(this).val() < 0){
																	$(this).val('');
																} else if(!isInt($(this).val()) || $(this).val() === 0) {
																	$(this).val('');
																} else {
																	document.getElementById(price_tier_array[i] +'_<?php echo esc_html($option_num); ?>').value = parseInt($(this).val());
																}
										
																if ($(this).val() > availability){
																	// show an error message
																	let plural_start = availability > 1 ? 'are' : 'is';
																	let plural_end = availability > 1 ? 's' : '';
																	let err = 'There ' + plural_start + ' only ' + availability + ' space' + plural_end + ' available';
																	$('#max_pax_error_'+ price_tier_array[i] +'_<?php echo esc_html($option_num); ?>').html(err);
																	$('#max_pax_error_'+ price_tier_array[i] +'_<?php echo esc_html($option_num); ?>').slideDown();

																	// reset input 
																	$('#' + price_tier_array[i] +'_<?php echo esc_html($option_num); ?>').val('');
																	$('#increase'+ price_tier_array[i] +'_<?php echo esc_html($option_num); ?>').addClass('not-allowed');

																	setTimeout(() => {
																		$('#max_pax_error_'+ price_tier_array[i] +'_<?php echo esc_html($option_num); ?>').slideUp();
																	}, 3500);
																	
																} else if ($(this).val() < availability) {
																	
																	$('#decrease_'+ price_tier_array[i] +'_<?php echo esc_html($option_num); ?>').removeClass('not-allowed');
																	$('#increase'+ price_tier_array[i] +'_<?php echo esc_html($option_num); ?>').removeClass('not-allowed');

																} else if ($(this).val() == availability) {

																	$('#decrease_'+ price_tier_array[i] +'_<?php echo esc_html($option_num); ?>').removeClass('not-allowed');
																	$('#increase_'+ price_tier_array[i] +'_<?php echo esc_html($option_num); ?>').addClass('not-allowed');
																} 

															})

															$('#increase_'+ price_tier_array[i] +'_<?php echo esc_html($option_num); ?>').click(function(){
																let value = $('#'+ price_tier_array[i] +'_<?php echo esc_html($option_num); ?>').val();
																let plus_sign = $(this);

																if (value < availability){
																	plus_sign.removeClass('not-allowed');
																} else if (value == availability) {
																	plus_sign.addClass('not-allowed');
																}
															})

															$('#decrease_'+ price_tier_array[i] +'_<?php echo esc_html($option_num); ?>').click(function(){
																let value = $('#'+ price_tier_array[i] +'_<?php echo esc_html($option_num); ?>').val();
																let plus_sign = $('#increase_'+ price_tier_array[i] +'_<?php echo esc_html($option_num); ?>');
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
																$('#max_pax_error_'+ price_tier_array[i] +'_<?php echo esc_html($option_num); ?>').html(err);
																$('#max_pax_error_'+ price_tier_array[i] +'_<?php echo esc_html($option_num); ?>').slideDown();

																// reset input 
																$('#' + price_tier_array[i] +'_<?php echo esc_html($option_num); ?>').val('');
																$('#decrease_'+ price_tier_array[i] +'_<?php echo esc_html($option_num); ?>').addClass('not-allowed');
																$('#increase'+ price_tier_array[i] +'_<?php echo esc_html($option_num); ?>').removeClass('not-allowed');

																setTimeout(() => {
																	$('#max_pax_error_'+ price_tier_array[i] +'_<?php echo esc_html($option_num); ?>').slideUp();
																}, 3500);

															} else if (existing_val == availability) {
																$('#increase_'+ price_tier_array[i] +'_<?php echo esc_html($option_num); ?>').addClass('not-allowed');
															} else if (existing_val < availability) {
																$('#decrease_'+ price_tier_array[i] +'_<?php echo esc_html($option_num); ?>').removeClass('not-allowed');
																$('#increase_'+ price_tier_array[i] +'_<?php echo esc_html($option_num); ?>').removeClass('not-allowed');
															}
															
															if (availability < 10) {
																$('#price_tier_amount_left_' + i + '_<?php echo $option_num; ?>').removeClass('d-none');
																$('#price_tier_amount_left_' + i + '_<?php echo $option_num; ?>').html('<span class="edit-pax-max">Only ' + availability + ' Left</span>');

																if (availability == 0) {
																	$('#price_tier_div_' + i + '_<?php echo $option_num; ?>').hide();
																	$('#' + price_tier_array[i] +'_<?php echo esc_html($option_num); ?>').val('');
																	$('#price_tier_amount_left_' + i + '_<?php echo $option_num; ?>').html('<span class="edit-pax-max">Not Available</span>');
																}
															} else {
																$('#price_tier_amount_left_' + i + '_<?php echo $option_num; ?>').addClass('d-none');
															}
														}
													}
												}

											});
											
											// pre-fill booking time in booking edit if set
											<?php if ($booking_time && $booking_date == $date) { ?>
												$('#time_selected_<?php echo $option->uid; ?>').html('<?php echo $booking_time; ?>');
												$('input[name="add[0][book_time]"]').val('<?php echo $booking_time; ?>');
											<?php } ?>

										});
										</script>

									<?php } // if ($dynamic_start_time) ?>

									<input type="hidden" class="hidden-inputs" name="add[0][uid]" value="<?php echo $option->uid; ?>">
									<input id="book_date_<?php echo $option_num; ?>" class="hidden-inputs" type="hidden" name="add[0][date]" value="<?php echo $date; ?>">
									<?php if ($dynamic_start_time) { ?>
										<input id="book_time_<?php echo esc_attr($option_num); ?>" class="hidden-inputs" type="hidden" name="add[0][book_time]" value="">
									<?php } ?>

										<div class="row"> 
											<div class="booking-edit-date-changes">
												<?php if ($booking_date != 'open' && $booking_date != $date) { ?>
													<div class="flex-row align-items-baseline flex-nowrap">
														<i class="far fa-calendar"></i>
														<p id="booking_edit_change_desc">
															Your booking date will be changed to <?php echo $formatted_date; ?>
														</p>
													</div>
												<?php } else if ($booking_date != 'open'){ ?>
														<div class="flex-row align-items-baseline flex-nowrap">
															<i class="far fa-calendar"></i>
															<p id="booking_edit_change_desc">
																Currently booked for <br> <?php echo $formatted_date; ?>
															</p>
														</div>
												<?php } ?>

												<div id="booking_edit_time_changes" class="flex-row align-items-baseline flex-nowrap" style="display:none;">
													<i class="far fa-clock"></i>
													<p id="booking_edit_change_desc">
														You selected <span id="booking_edit_time_selected"></span>
													</p>
												</div>
												
												<?php 
													$prices = $site->getTourPrices($option); 
													$animation_order = 1; 
													$current_total = $edit_total = 0;
											
													foreach( $prices as $price ) { 
														$current_total += $current_booking_pax[$price->name]['pax_total'];
														$edit_total += ($current_booking_pax[$price->name]['amount'] * $price->price); ?>

														<input type="hidden" value="<?php echo (float)$price->price; ?>" name="<?php echo $price->name; ?>">
												<?php } ?>

											<?php
												$difference = $current_total - $edit_total;
												$increase_decrease = $edit_total > $current_total ? 'increase' : 'decrease';

												if ($difference != 0 && $booking_date != $date) { ?>
													<div class="flex-row align-items-baseline flex-nowrap">
														<i class="far fa-exclamation-triangle"></i>
														<p id="booking_edit_price_wording">
															Your Total will <span class="increase-decrease-wording"><?php echo $increase_decrease; ?></span> by 
															<span id="booking_edit_price" class="<?php echo $increase_decrease; ?>">
																<?php echo $site->formatCurrency(abs($difference), $option); ?>
															</span>
														</p>
													</div>
												<?php } ?>
											</div>

										<div class="text-danger rezgo-option-error" id="error_text_<?php echo esc_attr($option_num); ?>" style="display:none;"></div>
										<div class="text-danger rezgo-option-error" id="error_mobile_text_<?php echo esc_attr($option_num); ?>" style="display:none;"></div>

										<?php if (REZGO_LITE_CONTAINER){ ?> 
											<input type="hidden" name="trigger_code" value="<?php echo (REZGO_LITE_CONTAINER) ? $site->promo_code : $site->cart_trigger_code; ?>">
											<input type="hidden" name="refid" value="<?php echo (REZGO_LITE_CONTAINER) ? $site->refid : ''; ?>">
										<?php } ?>

										<?php if ($booking_date != 'open' && $booking_date != $date || $dynamic_start_time) { ?>
											<div class="col-12 float-end">
												<span class="btn-check"></span>
													<button type="submit" class="btn btn-block rezgo-btn-book rezgo-btn-add-booking-edit" onclick="return check_edit_date_time_<?php echo esc_js($option_num); ?>();">
														<span>Confirm Changes</span>
													</button>
											</div>
										<?php } ?>
									</div>
								</form>
																
							<?php } else { ?>

								<div class="row"> 
									<div class="booking-edit-date-changes">

									<?php if ($booking_date != 'open'){ ?>
										<div class="flex-row align-items-baseline flex-nowrap">
											<i class="far fa-calendar"></i>
											<p id="booking_edit_change_desc">
												Currently booked for <br> <?php echo $formatted_date; ?>
											</p>
										</div>
										<div class="flex-row align-items-baseline flex-nowrap">
											<i class="far fa-exclamation-triangle"></i>
											<p id="booking_edit_price_wording">
												No changes can be made on this date.
											</p>
										</div>
									<?php } ?>

									</div>
								</div>
							<?php } // end if ($option->date->availability != 0) ?>
						</div>
					</div>

				<?php $sub_option++; // increment sub option instead ?>
			<?php } // if ($_REQUEST['type']) ?>
      
    	<?php //} // end if($option) ?>
	</div>