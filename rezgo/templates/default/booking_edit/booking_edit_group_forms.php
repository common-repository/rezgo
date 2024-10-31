<?php 
	require ('header.php'); 
	if (!$booking_edit) $site->sendTo('/edit/'.$raw_trans_num); 
?>

<div id="edit_guests" class="panel-collapse">
	<script>
		let fields_<?php echo esc_html($booking->item_id); ?> = new Array();
		let required_num_<?php echo esc_html($booking->item_id); ?> = 0;

		function isInt(n) {
			return n % 1 === 0;
		}
	</script>

	<div class="panel-body edit-booking-panel">

		<div class="edit-booking-guest-info current-edit-container">
			<?php 
				$booking_av = $booking_date != 'open' ? $item->date->availability : $item->date->max_availability - $booking->pax;
				$max_pax = isset($item->max_guests) ? (int)$item->max_guests : (int)$item->date->max_availability;
				$booking_maxed = (int)$booking->pax >= $max_pax ? 1 : 0; 
				$edit_pax_amount = !in_array(4, $booking_edit_options) ? 1 : 0;
				$prices = $site->getTourPrices($item);
				$time_av_full = 0;
				$available_price_tier = 0;				
				$synced_prices = [];

				$p = 0;
				foreach($prices as $price) {
					if ($price->max != 0) {
						$available_price_tier = 1;
					}
					// sync price tiers from booking data 
					if ($price->id == (int)$booking->price_data->price[$p]->id && $booking->price_data->price[$p]->retail) {
						$synced_prices[] = $price;
					} 
					$p++;
				}
				
				// account for availability for dynamic start times
				if ((string)$item->time_format == 'dynamic') {
					foreach ($item->date->time_data->time as $time) {
						if ((string)$time->id == (string)$booking->time) {
							$time_av_full = (int)$time->av == 0 ? 1 : 0;
						}
					}
				}
			?>

			<?php if ($booking_av > 0 && $edit_pax_amount && !$booking_maxed && $available_price_tier && !$time_av_full) { ?>
				<div id="add_guests_control_container">
					<a id="add_guests_control" class="underline-link" data-bs-toggle="collapse" data-bs-target="#add_guests" aria-controls="add_guests">
						<span><i class="far fa-user-plus"></i> Add New Guest</span>
					</a>
				</div>

				<div id="add_guests" class="accordion-collapse collapse">
					<form id="add_guest_form" action="" >
						<?php require ('add_new_guest.php'); ?>
					</form>
					<i id="faded_plus_sign" class="fal fa-plus-circle inactive"></i>
				</div>

			<?php } ?>

			<div class="edit-booking-guest-container">
				<form id="update_group_form" action="">
					<?php require (REZGO_DOCUMENT_ROOT.$site->path.'/booking_forms_group.php'); ?>
				</form>
			</div>
					
			<div class="edit-form-controls">
				<a href="<?php echo $site->base; ?>/edit/<?php echo $raw_trans_num; ?>" class="underline-link reset_edit_view"><i class="far fa-angle-left"></i><span>Back</span></a>
			</div>

		</div>
	</div>

	<script>
		let slideSpeed = 150;

		jQuery(function($){

			// hide remove guest btn if there is only 1 pax left in booking
			if ($('a.remove-guest-control').length <= 1) { 
				$('a.remove-guest-control').hide();
			}

			$(".chosen-select").chosen( { width: "100%", allow_single_deselect: true, disable_search_threshold: 10 } );

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

			$('#add_guests_control').click(function(){
				$('.edit-booking-guest-container').toggleClass('inactive');
				$('#faded_plus_sign').toggleClass('inactive');
			});

			$('#cancel_new_guest_btn').click(function(e){
				e.preventDefault();
				let collapse_div = document.getElementById('add_guests');
				let bsCollapse = new bootstrap.Collapse(collapse_div, {
					toggle: true
				});
				$('.edit-booking-guest-container').removeClass('inactive');
				$('#faded_plus_sign').addClass('inactive');
			});

			$('.edit-booking-guest-container .rezgo-form-input input').attr('disabled', true);
			$('.edit-booking-guest-container .rezgo-form-input textarea').attr('disabled', true);
			$('.edit-booking-guest-container .chosen-select').prop('disabled', true).trigger("chosen:updated");

			$('.edit-guest-control').click(function(){
				let rezgo_form_group = $(this).parents('.rezgo-form-group');
				let inputs = rezgo_form_group.find('.rezgo-form-input');

				$(this).hide();
				rezgo_form_group.find('.save-guest-changes-cta').slideDown(slideSpeed);
				rezgo_form_group.addClass('active');

				// save changes
				if ($(this).data('edit') != 1) {
					inputs.each(function(){
						$(this).find(':input:not([type=hidden])').attr('disabled', false);
						$(this).find('.chosen-select').prop('disabled', false).trigger("chosen:updated");
					})
				}

			});

			$('.rezgo-btn-save-changes').click(function(e){
				e.preventDefault();

				let rezgo_form_group = $(this).parents('.rezgo-form-group');
				let inputs = rezgo_form_group.find('.rezgo-form-input');
				let all_inputs = $('#update_group_form').find('.rezgo-form-input');
				let error_msg = rezgo_form_group.find('.rezgo-booking-edit-errors');
				let pax_id = $(this).data('id');
				let pax_type = $(this).data('type');

				let validate_check = $('#update_group_form').valid();
				console.log(validate_check);

				if (!validate_check) {
					error_msg.fadeIn();
					setTimeout(function () {
						error_msg.fadeOut();
					}, 5000);

				} else {

					// enable all inputs to submit form
					all_inputs.each(function(){	
						$(this).find(':input:not([type=hidden])').removeAttr('disabled');
						$(this).find('.chosen-select').prop('disabled', false).trigger("chosen:updated");
					})

					rezgo_form_group.find('.saving-guest-container').show();

					$('#update_group_form').ajaxSubmit({
						type: 'POST',
						url: '<?php echo admin_url('admin-ajax.php'); ?>' + '?action=rezgo&method=booking_edit_ajax', 
						data: { 
							rezgoAction: 'update_group_form',
							item_id: '<?php echo $booking_uid; ?>',
							booking_date: '<?php echo $booking_date; ?>',
							booking_time: '<?php echo $booking_time; ?>',
							trans_num: '<?php echo $trans_num; ?>',
							pax_id: pax_id,
							pax_type: pax_type,
						},
						success: function(data){
							let response = JSON.parse(data);
							console.log(response);

							if (response !== null) {

								setTimeout(() => {
									rezgo_form_group.find('.saving-guest-container').fadeOut();
								}, 1000);

								show_notification(
									`${success_icon}
									<div class="rezgo-toast-notif-wrapper">
										<span class="rezgo-toast-message">Booking Updated</span><br>
										<span class="rezgo-toast-details">Guest Details Updated</span>
									</div>`,
									3500,
									'success-border'
								);
								resetBookingStatus();

								// disable all inputs again
								all_inputs.each(function(){	
									$(this).find(':input:not([type=hidden])').attr('disabled', true);
									$(this).find('.chosen-select').prop('disabled', true).trigger("chosen:updated");
								})

								rezgo_form_group.find('.save-guest-changes-cta').slideUp(slideSpeed);
								rezgo_form_group.find('.edit-guest-control').show();
								rezgo_form_group.removeClass('active');

							} else {
								show_notification(`${error_icon} Error Saving`, 5000, 'error-border');
							}
						},
						error: function(error){
							console.log(error);
							show_notification(error);
						}
					});

					$('#save_guest_changes_cta').slideUp(slideSpeed);
					$(this).show();
					inputs.each(function(){
						$(this).find(':input:not([type=hidden])').attr('disabled', true);
						$(this).find('.chosen-select').prop('disabled', true).trigger("chosen:updated");
					})
				}
			})

			$('.cancel-save-guest-changes-btn').click(function(e){
				e.preventDefault();
				window.location.reload();
			})

			let booking_required = document.getElementById('update_group_form').querySelectorAll('.edit-booking-required').length ?? 0;
			$('.remove-guest-control').click(function(){
				// check if only one required price point is present
				let booking_pax = <?php echo (int)$booking->pax; ?>;
				if (booking_required == 1) {
					$(this).parents('.rezgo-form-group').find('.remove-guest.required').hide();
					$(this).parents('.rezgo-form-group').find('.removing-guest.required').text('You cannot remove a required price point');
					$(this).parents('.rezgo-form-group').find('.dismiss-banner.required').text('Dismiss message');
				}
				$(this).parents('.rezgo-form-group').find('.remove-guest-confirmation-container').show();
			})

			$('.dismiss-banner').click(function(){
				$(this).parents('.rezgo-form-group').find('.remove-guest-confirmation-container').hide();
			})

			$('.remove-guest').click(function(){
				$(this).parents('.rezgo-form-group').find('.remove-guest-confirmation-container').hide();
				$(this).parents('.rezgo-form-group').find('.remove-guest-pending-container').show();

				let rezgo_form_group = $(this).parents('.rezgo-form-group');
				let inputs = rezgo_form_group.find('.rezgo-form-input');

				let pax_id = $(this).data('id');
				let pax_type = $(this).data('type');

				rezgo_form_group.find('.removing-guest-container').show();

				$.ajax({
					type: 'POST',
					url: '<?php echo admin_url('admin-ajax.php'); ?>' + '?action=rezgo&method=booking_edit_ajax', 
					data: {
						rezgoAction: 'remove_pax',
						item_id: '<?php echo $booking_uid; ?>',
						booking_date: '<?php echo $booking_date; ?>',
						booking_time: '<?php echo $booking_time; ?>',
						trans_num: '<?php echo $trans_num; ?>',
						pax_id: pax_id,
						pax_type: pax_type,

					},
					success: function(data){
						let response = JSON.parse(data);
						if (response !== null) {
							<?php echo LOCATION_WINDOW; ?>.location.reload();
						} else {
							show_notification(`${error_icon} Error Updating`, 5000);
						}
					},
					error: function(error) {
						console.log(error);
					}
				});

			})

			// make ajax request to add a new guest
			$('#add_new_guest_btn').click(function(e){
				e.preventDefault();

				let validate_check = $('#add_guest_form').valid();
				console.log(validate_check);

				if (!validate_check) {
					$('#error_text_add').fadeIn();
					setTimeout(function () {
						$('#error_text_add').fadeOut();
					}, 5000);

					return false;

				} else {

					$('#add_guest_form').ajaxSubmit({
						type: 'POST',
						url: '<?php echo admin_url('admin-ajax.php'); ?>' + '?action=rezgo&method=booking_edit_ajax', 
						data: { 
							rezgoAction: 'update_pax',
							item_id: '<?php echo $booking_uid; ?>',
							booking_date: '<?php echo $booking_date; ?>',
							booking_time: '<?php echo $booking_time; ?>',
							trans_num: '<?php echo $trans_num; ?>',
						},
						success: function(data){
							let response = JSON.parse(data);
							if (response !== null) {
								<?php echo LOCATION_WINDOW; ?>.location.reload();
							} else {
								show_notification(`${error_icon} Error Updating`, 5000);
							}
						},
						error: function(error) {
							console.log(error);
						}
					});
				}
			})

			function updateGroupForm(){
				let validate_check = $('#update_group_form').valid();

				console.log(validate_check);

				if (!validate_check) {
					$('#rezgo_group_form_errors').fadeIn();
					setTimeout(function () {
						$('#rezgo_group_form_errors').fadeOut();
					}, 5000);

					return false;
				} else {

					console.log('submitting');

					// submit form here
					$('#save_group_form_wording').html('<i class="fal fa-circle-notch fa-spin"></i>&nbsp; Saving...');

					setTimeout(() => {
						// submit form
						$('#update_group_form').ajaxSubmit({
							type: 'POST',
							url: '<?php echo admin_url('admin-ajax.php'); ?>' + '?action=rezgo&method=booking_edit_ajax', 
							data: { 
								rezgoAction: 'update_group_form',
								item_id: '<?php echo $booking_uid; ?>',
								booking_date: '<?php echo $booking_date; ?>',
								booking_time: '<?php echo $booking_time; ?>',
								trans_num: '<?php echo $trans_num; ?>',
							},
							success: function(data){
								let response = JSON.parse(data);
								console.log(response);

								if (response !== null) {
									<?php echo LOCATION_WINDOW; ?>.location.reload();
								} else {
									show_notification(`${error_icon} Error Updating`, 5000);
								}
							},
							error: function(error){
								console.log(error);
								show_notification(error);
							}
						});
					}, 550);
				}
			}
		});
		</script>
	</div>
</div>