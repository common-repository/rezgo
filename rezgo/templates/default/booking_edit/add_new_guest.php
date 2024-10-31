<div class="row rezgo-form-group rezgo-additional-info guest-forms-container">
		<div class="select_guest_type_container">
			<select name="select_guest_type" id="add_new_guest_type" data-placeholder="Select a Guest Type" class="chosen-select chosen-guest-select form-control rezgo-custom-select required">
				<option value=""></option>
				<?php 
					$p = 0;
					foreach($booking->price_data->price as $price) { $booking_prices[] = $price->retail; } 
					foreach($synced_prices as $price) { ?>
					<?php if ($price->max != 0) { ?>
						<option data-pax="<?php echo $price->name; ?>" value="<?php echo $price->name; ?>_num"><?php echo $price->label; ?> (+<?php echo $site->formatCurrency($booking_prices[$p++]); ?>) </option>
					<?php } ?>
				<?php } ?>
			</select>

			<p id="add_new_guest_availability">
				<span id="add_guest_availability"></span>
			</p>

			<?php $dynamic_start_time = ((string)$item->time_format == 'dynamic') ? 1 : 0; ?>
			<script>
				let price_tier_obj = {};
				<?php 
					if ($dynamic_start_time) {
						$booking_time = (string)$booking->time;
						// grab av from date block
						foreach ($item->date->time_data->time as $time) {
							if ((string)$time->id == $booking_time) {
								foreach ($time->prices->price as $price) {
									$price_id = (int)$price->id;
									if ($price_id == 1) { $price_name = 'adult'; }
									elseif ($price_id == 2) { $price_name = 'child'; }
									elseif ($price_id == 3) { $price_name = 'senior'; }
									elseif ($price_id == 4) { $price_name = 'price4'; }
									elseif ($price_id == 5) { $price_name = 'price5'; }
									elseif ($price_id == 6) { $price_name = 'price6'; }
									elseif ($price_id == 7) { $price_name = 'price7'; }
									elseif ($price_id == 8) { $price_name = 'price8'; }
									elseif ($price_id == 9) { $price_name = 'price9'; }
								?>
									price_tier_obj.<?php echo $price_name; ?> = '<?php echo (int)$price->av; ?>';
								<?php }
							}
						}
					} else { 

						foreach($prices as $price) { 
							$total_avail = $booking_date != 'open' ? $price->max : $item->date->max_availability - $booking->pax; ?>
							// subtract availability from already booked pax 
							price_tier_obj.<?php echo $price->name; ?> = '<?php echo $total_avail; ?>';
						<?php } ?>	

				<?php } ?>

				jQuery(function($){
					$('#add_new_guest_type').change(function(){
						$(this).valid();
						if (!$(this).valid()) {
							$('#add_guest_availability').hide();
						}
						let selected_pax = $(this).find(':selected').data('pax');
						Object.keys(price_tier_obj).forEach(pax => {
							if (selected_pax == pax) {
								if (price_tier_obj[pax] < 10) {
									$('#add_guest_availability').show();
									$('#add_guest_availability').text('Only ' + price_tier_obj[pax] + ' left');
								} else {
									$('#add_guest_availability').hide();
								}
							}
						})
					})
				});
			</script>
		</div>

		<?php if($item->group == 'hide') { ?>
			<div class='edit-booking-guest-info-not-required'>
				<span>Guest information is not required</span>
			</div>
		<?php } else { ?>

			<div class="row rezgo-form-one form-group rezgo-pax-first-last rezgo-first-last-<?php echo esc_attr($item->uid); ?>">
				<div class="col-sm-6 rezgo-form-input">
					<label for="frm_<?php echo esc_attr($guest_uid); ?>_first_name" class="col-sm-2 control-label rezgo-label-right">
						<span>First&nbsp;Name<?php if($item->group == 'require' || $item->group == 'require_name') { ?>&nbsp;<em class="fa fa-asterisk"></em><?php } ?></span>
					</label>
					<input type="text" 
						class="form-control<?php echo ($item->group == 'require' || $item->group == 'require_name') ? ' required' : ''; ?> first_name_<?php echo esc_attr($c); ?>_<?php echo esc_attr($num); ?>" 
						id="frm_<?php echo esc_attr($guest_uid); ?>_first_name" name="booking[first_name]" 
						value="">
				</div>

				<div class="col-sm-6 rezgo-form-input">
					<label for="frm_<?php echo esc_attr($guest_uid); ?>_last_name" class="col-sm-2 control-label rezgo-label-right">
						<span>Last&nbsp;Name<?php if($item->group == 'require' || $item->group == 'require_name') { ?>&nbsp;<em class="fa fa-asterisk"></em><?php } ?></span>
					</label>
					<input type="text" 
						class="form-control<?php echo ($item->group == 'require' || $item->group == 'require_name') ? ' required' : ''; ?> last_name_<?php echo esc_attr($c); ?>" 
						id="frm_<?php echo esc_attr($guest_uid); ?>_last_name" name="booking[last_name]" 
						value="">
				</div>
			</div>

			<?php if($item->group != 'request_name') { ?>
				<div class="row rezgo-form-one form-group rezgo-pax-phone-email rezgo-phone-email-<?php echo esc_attr($item->uid); ?>">
					
					<div class="col-sm-6 rezgo-form-input">
						<label for="frm_<?php echo esc_attr($guest_uid); ?>_phone" class="col-sm-2 control-label rezgo-label-right">Phone<?php if($item->group == 'require') { ?>&nbsp;<em class="fa fa-asterisk"></em><?php } ?></label>
						<input type="text" 
							class="form-control<?php echo ($item->group == 'require') ? ' required' : ''; ?>" 
							id="frm_<?php echo esc_attr($guest_uid); ?>_phone" 
							name="booking[phone]"
							value="">
					</div>

					<div class="col-sm-6 rezgo-form-input">
						<label for="frm_<?php echo esc_attr($guest_uid); ?>_email" class="col-sm-2 control-label rezgo-label-right">Email<?php if($item->group == 'require') { ?>&nbsp;<em class="fa fa-asterisk"></em><?php } ?></label>
						<input type="email" 
							class="form-control<?php echo ($item->group == 'require') ? ' required' : ''; ?>" 
							id="frm_<?php echo esc_attr($guest_uid); ?>_email" 
							name="booking[email]" 
							value="">
					</div>
				</div>

			<?php } ?>
		<?php } // if ($item->group == 'hide') ?>

		<?php foreach( $site->getTourForms('group', $booking, 'booking_edit') as $form ) { ?>

			<?php if($form->require) $required_fields++; ?>

			<?php if($form->type == 'text') { ?>
				<div class="form-group rezgo-custom-form rezgo-form-input">
					<label class="control-label"><?php echo esc_html($form->title); ?><?php if($form->require) { ?> <em class="fa fa-asterisk"></em><?php } ?></label>

					<input 
						id="text-<?php echo esc_attr($guest_uid); ?>" 
						type="text" 
						class="form-control<?php echo ($form->require) ? ' required' : ''; ?> " 
						name="booking[forms][<?php echo esc_attr($form->id); ?>]" 
						value="">

					<?php if ($form->instructions){ ?>
						<p class="rezgo-form-comment"><span><?php echo wp_kses($form->instructions, ALLOWED_HTML); ?></span></p>
					<?php } ?>
				</div>
			<?php } ?>

			<?php if($form->type == 'select') { ?>
				<div class="form-group rezgo-custom-form rezgo-form-input">
					<label class="control-label"><span><?php echo esc_attr($form->title); ?><?php if($form->require) { ?> <em class="fa fa-asterisk"></em><?php } ?></span></label>

					<select id="select-<?php echo esc_attr($guest_uid); ?>_<?php echo esc_attr($form->id); ?>" class="chosen-select form-select<?php echo ($form->require) ? ' required' : ''; ?> rezgo-custom-select" name="booking[forms][<?php echo esc_attr($form->id); ?>]">
						<option value=""></option>
						<?php foreach($form->options as $option) { ?>
							<option><?php echo esc_html($option); ?></option>
						<?php } ?>
					</select>

					<?php if ($form->instructions){ ?>
						<p class="rezgo-form-comment"><span><?php echo wp_kses($form->instructions, ALLOWED_HTML); ?></span></p>
					<?php } ?>
					<?php if ($form->options_instructions) {
						$optex_count = 1;
						foreach($form->options_instructions as $opt_extra) {
							echo '<span class="opt_extra" id="optex_'.esc_attr($optex_count).'" style="display:none">'.esc_html($opt_extra).'</span>';
							$optex_count++;
						}
					}
					?>
					<input type="hidden" value='' name="booking[forms][<?php echo esc_attr($form->id); ?>]" data-addon="select_tour_group-<?php echo esc_attr($guest_uid); ?>_<?php echo esc_attr($form->id); ?>_hidden">
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
				});
				</script>
			<?php } ?>

			<?php if($form->type == 'multiselect') { ?>

				<div class="form-group rezgo-custom-form rezgo-form-input">
					<label class="control-label"><span><?php echo esc_attr($form->title); ?><?php if($form->require) { ?> <em class="fa fa-asterisk"></em><?php } ?></span></label>
					<select id="rezgo-custom-select-<?php echo esc_attr($guest_uid); ?>_<?php echo esc_attr($form->id); ?>" class="chosen-select form-select<?php echo ($form->require) ? ' required' : ''; ?> rezgo-custom-select" multiple="multiple" name="booking[forms][<?php echo esc_attr($form->id); ?>][]">
						<option value=""></option>
						<?php foreach($form->options as $option) { ?>
							<option><?php echo esc_html($option); ?></option>
						<?php } ?>
					</select>

					<?php if ($form->instructions){ ?>
						<p class="rezgo-form-comment"><span><?php echo wp_kses($form->instructions, ALLOWED_HTML); ?></span></p>
					<?php } ?>

					<?php if ($form->options_instructions) {
						$optex_count = 1;
						foreach($form->options_instructions as $opt_extra) {
							echo '<span class="opt_extra" id="optex_'.esc_attr($optex_count).'" style="display:none">'.esc_html($opt_extra).'</span>';
							$optex_count++;
						}
					}
					?>
					<input type="hidden" value='' name="booking[forms][<?php echo esc_attr($form->id); ?>][]" data-addon="multiselect_tour_group-<?php echo esc_attr($guest_uid); ?>_<?php echo esc_attr($form->id); ?>_hidden">
				</div>

				<script>
				jQuery(function($) {

					$('#rezgo-custom-select-<?php echo esc_html($guest_uid); ?>_<?php echo esc_html($form->id); ?>').change(function(){
						$(this).valid();

						if ($(this).val() === null){
							$("input[data-addon='multiselect_tour_group-<?php echo esc_html($guest_uid); ?>_<?php echo esc_html($form->id); ?>_hidden']").attr("disabled", false);
						} else {
							$("input[data-addon='multiselect_tour_group-<?php echo esc_html($guest_uid); ?>_<?php echo esc_html($form->id); ?>_hidden']").attr("disabled", true);
						}
					});
					
				});
				</script>
			<?php } ?>

			<?php if($form->type == 'datepicker') { ?>
				<div class="form-group rezgo-custom-form rezgo-form-input rezgo-datepicker-input">
					<label class="control-label"><?php echo esc_html($form->title); ?><?php if($form->require) { ?> <em class="fa fa-asterisk"></em><?php } ?></label>

					<input 
						autocomplete="off"
						id="datepicker-<?php echo esc_attr($guest_uid); ?>" 
						type="text" class="datepicker-<?php echo esc_attr($guest_uid); ?> form-control<?php echo ($form->require) ? ' required' : ''; ?>" 
						name="booking[<?php echo esc_attr($c); ?>][tour_group][<?php echo esc_attr($price->name); ?>][<?php echo esc_attr($num); ?>][forms][<?php echo esc_attr($form->id.$guest_uid); ?>]" 
						value="">

						<i class="fal fa-calendar-alt date-icon"></i>
					
						<?php if ($form->instructions){ ?>
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

			<?php if($form->type == 'textarea') { ?>
				
				<div class="form-group rezgo-custom-form rezgo-form-input">
					<label class="control-label"><span><?php echo esc_html($form->title); ?><?php if($form->require) { ?> <em class="fa fa-asterisk"></em><?php } ?></span></label>

					<textarea class="form-control<?php echo ($form->require) ? ' required' : ''; ?>" name="booking[forms][<?php echo esc_attr($form->id); ?>]" cols="40" rows="4"></textarea>

					<?php if ($form->instructions){ ?>
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
								id="<?php echo esc_attr($form->id); ?>|<?php echo esc_attr(base64_encode($form->title)); ?>|<?php echo esc_attr($form->price); ?>|<?php echo esc_attr($c); ?>|<?php echo esc_attr($price->name); ?>|<?php echo esc_attr($num); ?>" 
								name="booking[forms][<?php echo esc_attr($form->id); ?>]"
								data-addon="checkbox_tour_group-<?php echo esc_attr($checkbox_uid); ?>">

							<div class="state p-warning">
								<label for="<?php echo esc_attr($form->id."|".base64_encode($form->title)."|".$form->price."|".$c."|".$price->name."|".$num); ?>"><span><?php echo esc_html($form->title); ?></span>
								<?php if ($form->require) { ?> <em class="fa fa-asterisk"></em><?php } ?>
								<?php if ($form->price) { ?> <em class="price"><?php echo esc_attr($form->price_mod); ?> <?php echo esc_html($site->formatCurrency($form->price)); ?></em><?php } ?></label>
							</div>

							<input type='hidden' value='off' name="booking[forms][<?php echo esc_attr($form->id); ?>]"  data-addon="checkbox_tour_group-<?php echo esc_attr($checkbox_uid); ?>_hidden">

						</div>
					</div>

				<?php if ($form->instructions){ ?>
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
						
					});
					</script>

				</div>
			<?php } ?>

			<?php if($form->type == 'checkbox_price') { ?>

				<?php // build unique identifier for checkbox 
				$checkbox_uid = $c.'_'.$form->id.'_'.$num.'_'.$price->name; ?>

				<div class="rezgo-pretty-checkbox-container">
					<div class="rezgo-form-group rezgo-custom-form rezgo-form-input rezgo-pretty-checkbox">
						<div class="pretty p-default p-curve p-smooth">

							<input type="checkbox"<?php echo ($form->require) ? ' class="required"' : ''; ?> 
							id="<?php echo esc_attr($form->id); ?>|<?php echo esc_attr(base64_encode($form->title)); ?>|<?php echo esc_attr($form->price); ?>|<?php echo esc_attr($c); ?>|<?php echo esc_attr($price->name); ?>|<?php echo esc_attr($num); ?>" 
							name="booking[forms][<?php echo esc_attr($form->id); ?>]" 
							data-addon="checkbox_price_tour_group-<?php echo esc_attr($checkbox_uid); ?>"> 

							<div class="state p-warning">
								<label for="<?php echo esc_attr($form->id); ?>|<?php echo esc_attr(base64_encode($form->title)); ?>|<?php echo esc_attr($form->price); ?>|<?php echo esc_attr($c); ?>|<?php echo esc_attr($price->name); ?>|<?php echo esc_attr($num); ?>"><span><?php echo esc_html($form->title); ?></span>
								<?php if ($form->require) { ?> <em class="fa fa-asterisk"></em><?php } ?>
								<?php if ($form->price) { ?> <em class="price"><?php echo esc_attr($form->price_mod); ?> <?php echo esc_html($site->formatCurrency($form->price)); ?></em><?php } ?></label>
							</div>
						</div>

						<input type='hidden' value='off' name="booking[forms][<?php echo esc_attr($form->id); ?>]"  data-addon="checkbox_price_tour_group-<?php echo esc_attr($checkbox_uid); ?>_hidden">
					</div>

				<?php if ($form->instructions){ ?>
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

						<?php if ($cart_gf_val[$c-1][(int)$form->id.$guest_uid]['value'] == 'on'){ ?>
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
					
					<select id="select-price-<?php echo esc_attr($guest_uid); ?>_<?php echo esc_attr($form->id); ?>" class="chosen-select chosen-price-select form-control<?php echo ($form->require) ? ' required' : ''; ?> rezgo-custom-select" name="booking[forms][<?php echo esc_attr($form->id); ?>]">
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

					<?php if ($form->instructions){ ?>
						<p class="rezgo-form-comment"><span><?php echo wp_kses($form->instructions, ALLOWED_HTML); ?></span></p>
					<?php } ?>
					<?php if ($form->options_instructions) {
						$optex_count = 1;
						foreach($form->options_instructions as $opt_extra) {
							echo '<span class="opt_extra" id="optex_'.esc_attr($optex_count).'" style="display:none">'.esc_html($opt_extra).'</span>';
							$optex_count++;
						}
					}
					?>
					<input type="hidden" value='' name="booking[forms][<?php echo esc_attr($form->id); ?>]" data-addon="select_price_tour_group-<?php echo esc_attr($guest_uid); ?>_<?php echo esc_attr($form->id); ?>_hidden">
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

				});
				</script>
			<?php } ?>

		<?php } ?>

		<div id="error_text_add" class="rezgo-booking-edit-errors" style="display:none;">
			<span>Some required fields are missing. Please complete the highlighted fields.</span>
		</div>

		<div id="add_new_guest_cta">
			<a id="cancel_new_guest_btn" href="#add_guests" class="underline-link">Cancel</a>
			<button id="add_new_guest_btn" class="btn btn-lg rezgo-btn-add-guest">Add Guest</button>
		</div>
</div>