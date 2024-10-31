<?php 
	if (REZGO_WORDPRESS) {
		$book_page = $_REQUEST['mode'] == 'page_book' ? 1 : 0;
		$edit_booking_page = $_REQUEST['mode'] == 'booking_edit' ? 1 : 0;
	} else {
		$book_page = $_SERVER['SCRIPT_NAME'] == '/page_book.php' ? 1 : 0;
		$edit_booking_page = $_SERVER['SCRIPT_NAME'] == '/booking_edit.php' ? 1 : 0;
	}
	$price_num_arg = $book_page ? $item : $booking;
	$hide_edit_controls = $book_page ? 'd-none' : '';
	$pax_counter = 0;

foreach($site->getTourPrices($item) as $price) { ?>

	<?php foreach($site->getTourPriceNum($price, $price_num_arg) as $num) { ?>
		
		<?php if ($edit_booking_page) { ?> 
			<?php 
				$required_price_point = $price->required ?? 0; 
				$min_guest_threshold = (int)$booking->pax <= (int)$item->default_min_guests ? 1 : 0;
			?>
			<input type="hidden" 
			name="booking[<?php echo esc_attr($c); ?>][tour_group][<?php echo esc_attr($price->name); ?>][<?php echo esc_attr($num); ?>][id]" value="<?php echo $booking->passengers->passenger[$pax_counter]->id; ?>">
		<?php } ?>
		
		<div class="row rezgo-form-group rezgo-additional-info guest-forms-container">
			<div class="rezgo-sub-title form-sectional-header">
				<span><?php echo $price->label; ?> (<?php echo $num; ?>)</span>

				<?php if ($edit_booking_page && $required_price_point) { ?> 
					<span class="edit-booking-required"></span>
				<?php } ?>

				<?php 
					if($price->age_min || $price->age_max) {
						echo '<span class="rezgo-pax-age">';
							if($price->age_min == $price->age_max) { echo '<span>Age '.$price->age_min .'</span>'; }
							elseif($price->age_min && !$price->age_max) { echo '<span>Ages '.$price->age_min.' and up' .'</span>'; }
							elseif(!$price->age_min && $price->age_max) { echo '<span>Ages '.$price->age_max.' and under' .'</span>'; }
							elseif($price->age_min && $price->age_max) { echo '<span>Ages '.$price->age_min.' - '.$price->age_max .'</span>'; }
						echo '</span>';
					}
				?>
			</div>

		<span class="<?php echo $hide_edit_controls; ?>">

			<div class="edit-guests-container">
				<a class="edit-guest-control" style="<?php echo $item->group == 'hide' ? 'display:none;' : ''; ?>" data-edit=0><span>Edit Guest</span</a>
				<a class="remove-guest-control <?php echo !$edit_pax_amount ? 'd-none' : '';?>"><span>Remove</span></a>
			</div>

			<div class="remove-guest-confirmation-container" style="display:none;">
				<div class="remove-confirmation-links">
					<p class="cancellation-message">
						<?php if ($min_guest_threshold) { ?> 
							<span class="removing-guest">You cannot have less than <?php echo (int)$item->default_min_guests; ?> guest<?php echo (int)$item->default_min_guests > 1 ? '(s)' : ''; ?> in this booking</span>
						<?php } else { ?>
							<span class="removing-guest <?php echo $required_price_point ? 'required' : ''; ?>">Are you sure you want to remove this guest?</span>
						<?php } ?>
					</p>

					<div class="flex-row justify-center">
						<?php if ($min_guest_threshold) { ?> 
							<a class="btn dismiss-banner">Dismiss message</a>
						<?php } else { ?>
							<a class="btn dismiss-banner <?php echo $required_price_point ? 'required' : ''; ?>">No, take me back</a>
							<a class="btn text-danger remove-guest <?php echo $required_price_point ? 'required' : ''; ?>" data-id="<?php echo $booking->passengers->passenger[$pax_counter]->id; ?>" data-type="<?php echo $booking->passengers->passenger[$pax_counter]->type; ?>">Remove guest</a>
						<?php } ?>
					</div>
				</div>
			</div>

			<div class="saving-guest-container" style="display:none;">
				<p class="saving-guest-message"><i class="fal fa-circle-notch fa-spin"></i><span>Saving Changes...</span></p>
			</div>

			<div class="removing-guest-container" style="display:none;">
				<p class="removing-guest-message"><i class="fal fa-circle-notch fa-spin"></i><span>Removing Guest...</span></p>
			</div>

		</span>

		<?php if($item->group == 'hide') { ?>
			<div class='edit-booking-guest-info-not-required'>
				<span>Guest information is not required</span>
			</div>
		<?php } else { ?>

			<?php // create unique id for each entry
				$guest_uid = $c.'_'.$price->name.'_'.$num; 

				// assign values based on the page location
				$first_name[$num] = $book_page ? ($cart_data[$c-1]->tour_group->{$price->name}[$num-1]->first_name ?? '') : ($booking->passengers->passenger[$pax_counter]->first_name ?? '');
				$last_name[$num] = $book_page ? ($cart_data[$c-1]->tour_group->{$price->name}[$num-1]->last_name ?? '') : ($booking->passengers->passenger[$pax_counter]->last_name ?? '');
				$email[$num] = $book_page ? ($cart_data[$c-1]->tour_group->{$price->name}[$num-1]->email ?? '') : ($booking->passengers->passenger[$pax_counter]->email_address ?? '');
				$phone[$num] = $book_page ? ($cart_data[$c-1]->tour_group->{$price->name}[$num-1]->phone ?? '') : ($booking->passengers->passenger[$pax_counter]->phone_number ?? '');
			?>

			<div class="row rezgo-form-one form-group rezgo-pax-first-last rezgo-first-last-<?php echo esc_attr($item->uid); ?>">
				<div class="col-sm-6 rezgo-form-input">
					<label for="frm_<?php echo esc_attr($guest_uid); ?>_first_name" class="col-sm-2 control-label rezgo-label-right">
						<span>First&nbsp;Name<?php if($item->group == 'require' || $item->group == 'require_name') { ?>&nbsp;<em class="fa fa-asterisk"></em><?php } ?></span>
					</label>
					<input type="text" 
						class="form-control<?php echo ($item->group == 'require' || $item->group == 'require_name') ? ' required' : ''; ?> first_name_<?php echo esc_attr($c); ?>_<?php echo esc_attr($num); ?>" 
						data-index="<?php echo ($c==1) ? 'fname_from_'.esc_attr($num) : 'fname_to_'.esc_attr($num); ?>" 
						id="frm_<?php echo esc_attr($guest_uid); ?>_first_name" name="booking[<?php echo esc_attr($c); ?>][tour_group][<?php echo esc_attr($price->name); ?>][<?php echo esc_attr($num); ?>][first_name]" 
						value="<?php echo esc_attr($first_name[$num] ?? ''); ?>">
				</div>

				<div class="col-sm-6 rezgo-form-input">
					<label for="frm_<?php echo esc_attr($guest_uid); ?>_last_name" class="col-sm-2 control-label rezgo-label-right">
						<span>Last&nbsp;Name<?php if($item->group == 'require' || $item->group == 'require_name') { ?>&nbsp;<em class="fa fa-asterisk"></em><?php } ?></span>
					</label>
					<input type="text" 
						class="form-control<?php echo ($item->group == 'require' || $item->group == 'require_name') ? ' required' : ''; ?> last_name_<?php echo esc_attr($c); ?>" 
						data-index="<?php echo ($c==1) ? 'lname_from_'.esc_attr($num) : 'lname_to_'.esc_attr($num); ?>" 
						id="frm_<?php echo esc_attr($guest_uid); ?>_last_name" name="booking[<?php echo esc_attr($c); ?>][tour_group][<?php echo esc_attr($price->name); ?>][<?php echo esc_attr($num); ?>][last_name]" 
						value="<?php echo esc_attr($last_name[$num] ?? ''); ?>">
				</div>
			</div>

			<?php if($item->group != 'request_name') { ?>
				<div class="row rezgo-form-one form-group rezgo-pax-phone-email rezgo-phone-email-<?php echo esc_attr($item->uid); ?>">
					
					<div class="col-sm-6 rezgo-form-input">
						<label for="frm_<?php echo esc_attr($guest_uid); ?>_phone" class="col-sm-2 control-label rezgo-label-right">Phone<?php if($item->group == 'require') { ?>&nbsp;<em class="fa fa-asterisk"></em><?php } ?></label>
						<input type="text" 
							class="form-control<?php echo ($item->group == 'require') ? ' required' : ''; ?>" 
							data-index="<?php echo ($c==1) ? 'phone_from_'.esc_attr($num) : 'phone_to_'.esc_attr($num); ?>" 
							id="frm_<?php echo esc_attr($guest_uid); ?>_phone" 
							name="booking[<?php echo esc_attr($c); ?>][tour_group][<?php echo esc_attr($price->name); ?>][<?php echo esc_attr($num); ?>][phone]"
							value="<?php echo esc_attr($phone[$num] ?? ''); ?>">
					</div>

					<div class="col-sm-6 rezgo-form-input">
						<label for="frm_<?php echo esc_attr($guest_uid); ?>_email" class="col-sm-2 control-label rezgo-label-right">Email<?php if($item->group == 'require') { ?>&nbsp;<em class="fa fa-asterisk"></em><?php } ?></label>
						<input type="email" 
							class="form-control<?php echo ($item->group == 'require') ? ' required' : ''; ?>" 
							data-index="<?php echo ($c==1) ? 'email_from_'.esc_attr($num) : 'email_to_'.esc_attr($num); ?>" 
							id="frm_<?php echo esc_attr($guest_uid); ?>_email" 
							name="booking[<?php echo esc_attr($c); ?>][tour_group][<?php echo esc_attr($price->name); ?>][<?php echo esc_attr($num); ?>][email]" 
							value="<?php echo esc_attr($email[$num] ?? ''); ?>">
					</div>
				</div>

			<?php } ?>
		<?php } // if ($item->group == 'hide') ?>

			<?php 
				$form_counter = 1; // form counter to create unique IDs 
				$source = $edit_booking_page ? 'booking_edit' : ''; // switch data source based on where this file is being accessed 
				$object = $edit_booking_page ? $booking : $item; // switch object based on where this file is being accessed 
			?>

			<?php foreach($site->getTourForms('group', $object, $source) as $form ) { ?>

				<?php 
					// match values based on where this file is included 
					if ($book_page) {

						$cart_gf[$c-1] = $cart_data[$c-1]->tour_group->{$price->name}[(int) $num-1]->forms->form ?? '';

						if ($cart_gf[$c-1]){
							foreach ($cart_gf[$c-1] as $k => $v) {
								$cart_gf_val[$c-1][(int)$v->num.$guest_uid]['value'] = $v->value ?? '';
							}
						}
					} elseif ($edit_booking_page) {

						$cart_gf[$c-1] = $booking->passengers->passenger[$pax_counter]->forms->form ?? '';

						if ($cart_gf[$c-1]){
							foreach ($cart_gf[$c-1] as $k => $v) {

								// convert checkboxes to 'on';
								$checkboxes = array('checkbox', 'checkbox_price');
								if ( in_array((string)$v->type, $checkboxes) && (string)$v->answer == '1') {
									$v->answer = 'on';
								}
								$cart_gf_val[$c-1][(int)$v->id.$guest_uid]['value'] = (string)$v->answer ?? '';
							}
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
							value="<?php echo esc_attr($cart_gf_val[$c-1][(int)$form->id.$guest_uid]['value'] ?? ''); ?>">

						<?php if ($site->exists($form->instructions)){ ?>
							<p class="rezgo-form-comment"><span><?php echo wp_kses($form->instructions, ALLOWED_HTML); ?></span></p>
						<?php } ?>
					</div>
				<?php } ?>

				<?php if($form->type == 'select') { ?>
					<div class="form-group rezgo-custom-form rezgo-form-input">
						<label class="control-label"><span><?php echo esc_attr($form->title); ?><?php if($form->require) { ?> <em class="fa fa-asterisk"></em><?php } ?></span></label>

						<select id="select-<?php echo esc_attr($guest_uid); ?>_<?php echo esc_attr($form->id); ?>" class="chosen-select form-control<?php echo ($form->require) ? ' required' : ''; ?> rezgo-custom-select" name="booking[<?php echo esc_attr($c); ?>][tour_group][<?php echo esc_attr($price->name); ?>][<?php echo esc_attr($num); ?>][forms][<?php echo esc_attr($form->id); ?>]">
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
						<input type="hidden" value='' name="booking[<?php echo esc_attr($c); ?>][tour_group][<?php echo esc_attr($price->name); ?>][<?php echo esc_attr($num); ?>][forms][<?php echo esc_attr($form->id); ?>]" data-addon="select_tour_group-<?php echo esc_attr($guest_uid); ?>_<?php echo esc_attr($form->id); ?>_hidden">
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

						let select_group_<?php echo esc_html($guest_uid); ?>_<?php echo esc_html($form->id); ?> = "<?php echo trim(addslashes(html_entity_decode($cart_gf_val[$c-1][(int)$form->id.$guest_uid]['value'] ?? '', ENT_QUOTES))); ?>";
						
						if (select_group_<?php echo esc_html($guest_uid); ?>_<?php echo esc_html($form->id); ?> != ''){
							$("input[data-addon='select_tour_group-<?php echo esc_html($guest_uid); ?>_<?php echo esc_html($form->id); ?>_hidden']").attr("disabled", true);
							$('#select-<?php echo esc_html($guest_uid); ?>_<?php echo esc_html($form->id); ?>').val(select_group_<?php echo esc_attr($guest_uid); ?>_<?php echo esc_attr($form->id); ?>).trigger('chosen:updated');

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
						<select id="rezgo-custom-select-<?php echo esc_attr($guest_uid); ?>_<?php echo esc_attr($form->id); ?>" class="chosen-select form-control<?php echo ($form->require) ? ' required' : ''; ?> rezgo-custom-select" multiple="multiple" name="booking[<?php echo esc_attr($c); ?>][tour_group][<?php echo esc_attr($price->name); ?>][<?php echo esc_attr($num); ?>][forms][<?php echo esc_attr($form->id); ?>][]">
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
						<input type="hidden" value='' name="booking[<?php echo esc_attr($c); ?>][tour_group][<?php echo esc_attr($price->name); ?>][<?php echo esc_attr($num); ?>][forms][<?php echo esc_attr($form->id); ?>][]" data-addon="multiselect_tour_group-<?php echo esc_attr($guest_uid); ?>_<?php echo esc_attr($form->id); ?>_hidden">
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
						let multiselect_group_<?php echo esc_html($guest_uid); ?>_<?php echo esc_html($form->id); ?> = '<?php echo trim(addslashes(html_entity_decode($cart_gf_val[$c-1][(int)$form->id.$guest_uid]['value'], ENT_QUOTES))); ?>';

						if (multiselect_group_<?php echo esc_html($guest_uid); ?>_<?php echo esc_html($form->id); ?>.length){
							$("input[data-addon='multiselect_tour_group-<?php echo esc_html($guest_uid); ?>_<?php echo esc_html($form->id); ?>_hidden']").attr("disabled", true);
							multiselect_group_<?php echo esc_html($guest_uid); ?>_<?php echo esc_html($form->id); ?> = trimArray(multiselect_group_<?php echo esc_html($guest_uid); ?>_<?php echo esc_html($form->id); ?>.split(', '));

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
						} else {
							$("input[data-addon='multiselect_tour_group-<?php echo esc_html($guest_uid); ?>_<?php echo esc_html($form->id); ?>_hidden']").attr("disabled", false);
						}
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
							name="booking[<?php echo esc_attr($c); ?>][tour_group][<?php echo esc_attr($price->name); ?>][<?php echo esc_attr($num); ?>][forms][<?php echo esc_attr($form->id); ?>]" 
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

				<?php if($form->type == 'textarea') { ?>
					
					<div class="form-group rezgo-custom-form rezgo-form-input">
						<label class="control-label"><span><?php echo esc_html($form->title); ?><?php if($form->require) { ?> <em class="fa fa-asterisk"></em><?php } ?></span></label>

						<textarea class="form-control<?php echo ($form->require) ? ' required' : ''; ?>" name="booking[<?php echo esc_attr($c); ?>][tour_group][<?php echo esc_attr($price->name); ?>][<?php echo esc_attr($num); ?>][forms][<?php echo esc_attr($form->id); ?>]" cols="40" rows="4"><?php echo esc_textarea($cart_gf_val[$c-1][(int)$form->id.$guest_uid]['value']); ?></textarea>

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
									id="<?php echo esc_attr($form->id); ?>|<?php echo esc_attr(base64_encode($form->title)); ?>|<?php echo esc_attr($form->price); ?>|<?php echo esc_attr($c); ?>|<?php echo esc_attr($price->name); ?>|<?php echo esc_attr($num); ?>" 
									name="booking[<?php echo esc_attr($c); ?>][tour_group][<?php echo esc_attr($price->name); ?>][<?php echo esc_attr($num); ?>][forms][<?php echo esc_attr($form->id); ?>]"
									data-addon="checkbox_tour_group-<?php echo esc_attr($checkbox_uid); ?>">

								<div class="state p-warning">
									<label for="<?php echo esc_attr($form->id."|".base64_encode($form->title)."|".$form->price."|".$c."|".$price->name."|".$num); ?>"><span><?php echo esc_html($form->title); ?></span>
									<?php if ($form->require) { ?> <em class="fa fa-asterisk"></em><?php } ?>
									<?php if ($form->price) { ?> <em class="price"><?php echo esc_attr($form->price_mod); ?> <?php echo esc_html($site->formatCurrency($form->price)); ?></em><?php } ?></label>
								</div>

								<input type='hidden' value='off' name="booking[<?php echo esc_attr($c); ?>][tour_group][<?php echo esc_attr($price->name); ?>][<?php echo esc_attr($num); ?>][forms][<?php echo esc_attr($form->id); ?>]"  data-addon="checkbox_tour_group-<?php echo esc_attr($checkbox_uid); ?>_hidden">

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

							<?php if (isset($cart_gf_val[$c-1][(int)$form->id.$guest_uid]['value']) && $cart_gf_val[$c-1][(int)$form->id.$guest_uid]['value'] == 'on'){ ?>
								$("input[data-addon='checkbox_tour_group-<?php echo esc_html($checkbox_uid); ?>']").prop('checked', true);
								$("input[data-addon='checkbox_tour_group-<?php echo esc_html($checkbox_uid); ?>_hidden']").attr("disabled", true);
							<?php } else { ?>
								$("input[data-addon='checkbox_tour_group-<?php echo esc_html($checkbox_uid); ?>']").prop('checked', false);
								$("input[data-addon='checkbox_tour_group-<?php echo esc_html($checkbox_uid); ?>_hidden']").attr("disabled", false);
							<?php } ?>
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
								name="booking[<?php echo esc_attr($c); ?>][tour_group][<?php echo esc_attr($price->name); ?>][<?php echo esc_attr($num); ?>][forms][<?php echo esc_attr($form->id); ?>]" 
								data-addon="checkbox_price_tour_group-<?php echo esc_attr($checkbox_uid); ?>"> 

								<div class="state p-warning">
									<label for="<?php echo esc_attr($form->id); ?>|<?php echo esc_attr(base64_encode($form->title)); ?>|<?php echo esc_attr($form->price); ?>|<?php echo esc_attr($c); ?>|<?php echo esc_attr($price->name); ?>|<?php echo esc_attr($num); ?>"><span><?php echo esc_html($form->title); ?></span>
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
							<?php } else { ?>
								$("input[data-addon='checkbox_price_tour_group-<?php echo esc_html($checkbox_uid); ?>']").prop('checked', false);
								$("input[data-addon='checkbox_price_tour_group-<?php echo esc_html($checkbox_uid); ?>_hidden']").attr("disabled", false);
							<?php } ?>
						});
						</script>
					</div>
				<?php } ?>


				<?php if($form->type == 'select_price') { ?>

					<div class="form-group rezgo-custom-form rezgo-form-input">
						<label class="control-label"><span><?php echo esc_attr($form->title); ?><?php if($form->require) { ?> <em class="fa fa-asterisk"></em><?php } ?></span></label>
						
						<select id="select-price-<?php echo esc_attr($guest_uid); ?>_<?php echo esc_attr($form->id); ?>" class="chosen-select chosen-price-select form-control<?php echo ($form->require) ? ' required' : ''; ?> rezgo-custom-select" name="booking[<?php echo esc_attr($c); ?>][tour_group][<?php echo esc_attr($price->name); ?>][<?php echo esc_attr($num); ?>][forms][<?php echo esc_attr($form->id); ?>]">
							<option value=""></option>
							<?php 
								$p = 0; 
								foreach($form->options as $option) { 
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

						let select_price_group_<?php echo esc_html($guest_uid); ?>_<?php echo esc_html($form->id); ?> = "<?php echo addslashes(html_entity_decode($cart_gf_val[$c-1][(int)$form->id.$guest_uid]['value'] ?? '', ENT_QUOTES)); ?>";

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

			<span class="booking-edit-error-span <?php echo $hide_edit_controls; ?>">
				<div class="rezgo-booking-edit-errors" style="display:none;">
					<span>Some required fields are missing. Please complete the highlighted fields.</span>
				</div>
			</span>
			<span class="<?php echo $hide_edit_controls; ?>">
				<div class="save-guest-changes-cta" style="display:none;">
					<a class="cancel-save-guest-changes-btn underline-link"><span>Cancel</span></a>
					<span class="btn-check"></span>
					<button class="btn btn-lg rezgo-btn-save-changes" data-type="<?php echo $booking->passengers->passenger[$pax_counter]->type; ?>" data-id="<?php echo $booking->passengers->passenger[$pax_counter]->id; ?>"><span>Save Changes</span></button>
				</div>
			</span>
			
		</div> <!-- row rezgo-form-group rezgo-additional-info -->
		<?php $pax_counter++; ?>
	<?php } // end foreach($site->getTourPriceNum($price, $item) as $num) ?>
<?php } // end foreach ($site->getTourPrices($item) as $price) ?>