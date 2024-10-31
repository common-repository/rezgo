<?php 
	if (REZGO_WORDPRESS) {
		$book_page = $_REQUEST['mode'] == 'page_book' ? 1 : 0;
		$edit_booking_page = $_REQUEST['mode'] == 'booking_edit' ? 1 : 0;
	} else {
		$book_page = $_SERVER['SCRIPT_NAME'] == '/page_book.php' ? 1 : 0;
		$edit_booking_page = $_SERVER['SCRIPT_NAME'] == '/booking_edit.php' ? 1 : 0;
	}
	$source = $edit_booking_page ? 'booking_edit' : ''; // switch data source based on where this file is being accessed 
	$object = $edit_booking_page ? $booking : $item; // switch object based on where this file is being accessed 
	$primary_forms = $site->getTourForms('primary', $object, $source);

if ($primary_forms) { 

	// match values based on where this file is included 
	if ($book_page) {

		$cart_pf[$c-1] = $cart_data[$c-1]->primary_forms->form;
		if ($cart_pf[$c-1]){
			foreach ($cart_pf[$c-1] as $k => $v) {
				$cart_pf_val[$c-1][(int)$v->num]['value'] = $v->value ?? '';
			}
		}

	} elseif ($edit_booking_page) {

		$cart_pf[$c-1] = $booking->primary_forms->form;
		if ($cart_pf[$c-1]){
			foreach ($cart_pf[$c-1] as $k => $v) {

				// convert checkboxes to 'on';
				$checkboxes = array('checkbox', 'checkbox_price');
				if ( in_array((string)$v->type, $checkboxes) && (string)$v->answer == '1') {
					$v->answer = 'on';
				}
				$cart_pf_val[$c-1][(int)$v->id]['value'] = (string)$v->answer ?? '';
			}
		}
	}
?>

	
<div class="row rezgo-form-group rezgo-additional-info primary-forms-container">	
	<div class="col-12 rezgo-sub-title form-sectional-header rezgo-book-add-info">
		<span>Additional Information</span>
	</div>

	<div class="clearfix rezgo-short-clearfix">&nbsp;</div>

		<?php foreach($primary_forms as $form) { ?>

			<?php if(isset($form->require)) $required_fields++; ?>
			<?php if($form->type == 'text') { ?>
				<div class="form-group rezgo-custom-form rezgo-form-input">
					<label class="control-label"><?php echo esc_html($form->title); ?><?php if(isset($form->require)) { ?> <em class="fa fa-asterisk"></em><?php } ?></label>
					<input 
						id="text-<?php echo esc_attr($c); ?>_<?php echo esc_attr($form->id); ?>" 
						type="text" class="form-control<?php echo ($form->require) ? ' required' : ''; ?>" 
						name="booking[<?php echo esc_attr($c); ?>][tour_forms][<?php echo esc_attr($form->id); ?>]" 
						value="<?php echo esc_attr($cart_pf_val[$c-1][(int)$form->id]['value']); ?>">
					<?php if ($site->exists($form->instructions)){ ?>
						<p class="rezgo-form-comment"><span><?php echo wp_kses($form->instructions, ALLOWED_HTML); ?></span></p>
					<?php } ?>
				</div>
			<?php } ?>

			<?php if($form->type == 'select') { ?>
				<div class="form-group rezgo-custom-form rezgo-form-input">
					<label class="control-label"><?php echo esc_html($form->title); ?><?php if(isset($form->require)) { ?> <em class="fa fa-asterisk"></em><?php } ?></label>
					<select id="select-<?php echo esc_attr($c); ?>_<?php echo esc_attr($form->id); ?>" class="chosen-select form-control<?php echo ($form->require) ? ' required' : ''; ?> rezgo-custom-select" name="booking[<?php echo esc_attr($c); ?>][tour_forms][<?php echo esc_attr($form->id); ?>]">
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
						let select_primary_<?php echo esc_html($c); ?>_<?php echo esc_html($form->id); ?> = "<?php echo trim(addslashes(html_entity_decode($cart_pf_val[$c-1][(int)$form->id]['value'] ?? '', ENT_QUOTES))); ?>";

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
					
					<select id="rezgo-custom-select-<?php echo esc_attr($c); ?>_<?php echo esc_attr($form->id); ?>" class="chosen-select form-control<?php echo ($form->require) ? ' required' : ''; ?> rezgo-custom-select" multiple="multiple" name="booking[<?php echo esc_attr($c); ?>][tour_forms][<?php echo esc_attr($form->id); ?>][]">
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

						let multiselect_primary_<?php echo esc_html($c); ?>_<?php echo esc_html($form->id); ?> = '<?php echo trim(addslashes(html_entity_decode($cart_pf_val[$c-1][(int)$form->id]['value'] ?? '', ENT_QUOTES))); ?>';

						if (multiselect_primary_<?php echo esc_html($c); ?>_<?php echo esc_html($form->id); ?>.length){
							$("input[data-addon='multiselect_primary-<?php echo esc_html($c); ?>_<?php echo esc_html($form->id); ?>_hidden']").attr("disabled", true);
							multiselect_primary_<?php echo esc_html($c); ?>_<?php echo esc_html($form->id); ?> = trimArray(multiselect_primary_<?php echo esc_html($c); ?>_<?php echo esc_html($form->id); ?>.split(', '));

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
					<textarea id="textarea-<?php echo esc_attr($c); ?>_<?php echo esc_attr($form->id); ?>" class="form-control<?php echo ($form->require) ? ' required' : ''; ?>" name="booking[<?php echo esc_attr($c); ?>][tour_forms][<?php echo esc_attr($form->id); ?>]" cols="40" rows="4"><?php echo esc_textarea($cart_pf_val[$c-1][(int)$form->id]['value'] ?? ''); ?></textarea>
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
								id="<?php echo esc_attr($form->id); ?>|<?php echo esc_attr(base64_encode($form->title)); ?>|<?php echo esc_attr(isset($form->price) ?? ''); ?>|<?php echo esc_attr($c); ?>|<?php echo esc_attr(isset($price->name) ?? ''); ?>" 
								name="booking[<?php echo esc_attr($c); ?>][tour_forms][<?php echo esc_attr($form->id); ?>]" 
								data-addon="checkbox-<?php echo esc_attr($c); ?>_<?php echo esc_attr($form->id); ?>">

							<div class="state p-warning">
								<label for="<?php echo esc_attr($form->id); ?>|<?php echo esc_attr(base64_encode($form->title)); ?>|<?php echo esc_attr(isset($form->price) ?? ''); ?>|<?php echo esc_attr($c); ?>|<?php echo esc_attr(isset($price->name) ?? ''); ?>"><span><?php echo esc_html($form->title); ?></span>
								<?php if ($form->require) { ?> <em class="fa fa-asterisk"></em><?php } ?>
								<?php if (isset($form->price)) { ?> <em class="price"><?php echo esc_attr($form->price_mod); ?> <?php echo esc_html($site->formatCurrency($form->price)); ?></em><?php } ?></label>
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
								id="<?php echo esc_attr($form->id); ?>|<?php echo esc_attr(base64_encode($form->title)); ?>|<?php echo esc_attr(isset($form->price) ?? ''); ?>|<?php echo esc_attr($c); ?>|<?php echo esc_attr(isset($price->name) ?? ''); ?>" 
								name="booking[<?php echo esc_attr($c); ?>][tour_forms][<?php echo esc_attr($form->id); ?>]" data-addon="checkbox_price-<?php echo esc_attr($c); ?>_<?php echo esc_attr($form->id); ?>">
							<div class="state p-warning">
								<label for="<?php echo esc_attr($form->id); ?>|<?php echo esc_attr(base64_encode($form->title)); ?>|<?php echo esc_attr(isset($form->price) ?? ''); ?>|<?php echo esc_attr($c); ?>|<?php echo esc_attr(isset($price->name) ?? ''); ?>"><span><?php echo esc_html($form->title); ?></span>
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
					<select id="select-price-<?php echo esc_attr($c); ?>_<?php echo esc_attr($form->id); ?>" class="chosen-select form-control<?php echo ($form->require) ? ' required' : ''; ?> rezgo-custom-select" name="booking[<?php echo esc_attr($c); ?>][tour_forms][<?php echo esc_attr($form->id); ?>]">
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
<?php } else { ?>

	<?php if ($_SERVER['SCRIPT_NAME'] == '/booking_edit.php') { ?>
		<div class='col-12 text-center rezgo-no-primary-forms'>
			<p><span>No details required for this booking</span></p>
		</div>

		<script>
			// hide submit btn
			$(document).ready(function(){ $('#save_primary_form').hide(); });
		</script>
	<?php } ?>

<?php } ?>

<?php if($item->group == 'hide' && count ($site->getTourForms('primary')) == 0) { ?>
	<div class='rezgo-guest-info-not-required'>
		<span>Guest information is not required for booking #<?php echo esc_html($c); ?></span>
	</div>
<?php } // end if getTourForms('primary') ?>