<?php require ('header.php'); ?>

			<?php if ($booking_edit) { ?>

				<?php if ($pending_cancellation) { ?>

					<div class="booking-not-editable">
						<h3 class="booking-not-editable-copy"><i class="far fa-exclamation-triangle"></i>&nbsp; Booking is pending cancellation. Please contact <?php echo $company->company_name; ?> if you need more information. </h3>
						<?php require ('booking_edit_contact_form.php'); ?>
					</div>

				<?php } else { ?>

				<div class="edit-booking-menu">

					<?php if ($booking_edit_enabled) { ?>
						<?php $single_date_static_time = $item->date_selection == 'single' && $item->time_format != 'dynamic' ? 1 : 0; ?>
						<?php if (!in_array(2, $booking_edit_options)) { ?>
							<?php if ($booking_date != 'open' && !$single_date_static_time) { ?>
								<!-- Edit Date and Time -->
								<a class="edit-booking-link edit-link change-date-time-link" href="<?php echo $site->base; ?>/edit/<?php echo $raw_trans_num; ?>/date-and-time">
									<i class="fal fa-calendar-alt"></i>
									<span>Change Date and Time</span>
								</a>
							<?php } ?>
						<?php } ?>

						<?php if (count($site->getTourForms('primary', $booking, 'booking_edit')) != 0) { ?>
							<!-- Edit Booking Details -->
							<a class="edit-booking-link edit-link edit-booking-details-link" href="<?php echo $site->base; ?>/edit/<?php echo $raw_trans_num; ?>/booking-details">
								<i class="fal fa-file-edit"></i>
								<span>Edit Booking Details</span>
							</a>
						<?php } // end if getTourForms('primary') ?>

							<!-- Manage Guests -->
							<a class="edit-booking-link edit-link manage-guests-link" href="<?php echo $site->base; ?>/edit/<?php echo $raw_trans_num; ?>/guest-details">
								<i class="fal fa-user-cog"></i>
								<span>Manage Guests</span>
							</a>						
					<?php } ?>

					<?php if ($booking_cancellation_enabled) { ?>
						<!-- Cancel Booking -->
						<a data-bs-toggle="collapse" id="cancel_booking_panel" class="cancel-booking-link edit-booking-link edit-link" data-bs-target="#cancel_booking">
							<i class="fal fa-times-circle"></i>
							<span>Cancel Booking</span>
						</a>

						<div id="cancel_booking" class="panel-collapse collapse">
							<br>

							<div class="edit-change-container text-center">
								<h3 class="cancel-booking-warning">
									<span>
										Are you sure you want to cancel this booking? 
									</span>
								</h3>
								<span class="booking-cancellation-note">Warning: You cannot undo this action.</span>

								<div class="edit-form-controls">
									<button 
										onclick="cancelBooking(); return false;"
										style="background:#fff; box-shadow:none;"
									>
										<span id="cancel_booking_wording">Cancel Booking</span>
									</button>
									<a class="underline-link reset_edit_view"><i class="far fa-angle-left"></i><span>Back</span></a>
								</div>
							</div>
						</div>
					<?php } ?>

				</div>

				<script>
					let transitionSpeed = 200;

					jQuery(function($){
						// animation to dismiss all other options except this
						$('.edit-booking-link').click(function(){
							$('.edit-link').not($(this)).fadeOut(transitionSpeed);
							$(this).addClass('locked');
							$('.edit-booking-menu').addClass('active');
						})

						$('.reset_edit_view').click(function(){
							$('.edit-link').fadeIn(transitionSpeed).addClass('collapsed').removeClass('locked');
							$('.edit-booking-menu').removeClass('active');
							$('.panel-collapse').removeClass('show');
						})

						cancelBooking = function() {
							$('#cancel_booking_wording').html('<i class="fal fa-circle-notch fa-spin"></i>&nbsp; Cancelling...');

							$.ajax({
								url: '<?php echo admin_url('admin-ajax.php'); ?>' + '?action=rezgo&method=booking_edit_ajax', 
								data: { 
									rezgoAction: 'cancel_booking',
									trans_num: '<?php echo $trans_num; ?>',
								},
								success: function(response){
									<?php echo LOCATION_WINDOW; ?>.location.href= "<?php echo $site->base.'/complete/'.$site->encode($trans_num); ?>";
								},
								error: function(error){
									console.log(error);
								}
							});
						}

						// Chosen.js touch support on mobile
						if ($('.chosen-select').length > 0) {
							$('.chosen-select').on('touchstart', function(e){
								e.stopPropagation(); e.preventDefault();
								// Trigger the mousedown event.
								$(this).trigger('mousedown');
							});
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
					});
				</script>

			<?php } // if ($cancel_booking_pending) ?>

		<?php } else { ?>

			<div class="booking-not-editable">
				<div class="booking-not-editable-copy">

					<i class="far fa-exclamation-triangle"></i>&nbsp; 

					<span class="support-wording">
						<?php echo ($booking->status == 3) ? 'Booking has been cancelled.' : 'Booking can no longer be modified.'; ?> 
						Please contact <?php echo $company->company_name; ?> for additional support.
					</span>
				</div>

				<?php require ('booking_edit_contact_form.php'); ?>

				<div class="rezgo-content-row row" id="rezgo-contact-address">
					<div class="col-md-12 col-lg-4">
						<span>
							<?php if($site->exists($company->phone)) { ?>
								<div class="company-wrap-info">
									<i class="far fa-phone fa-md"></i>
									<a href="tel:<?php echo esc_html($company->phone); ?>">
									<?php echo esc_html($company->phone); ?>
									</a> 
								</div>
							<?php } ?><br>
							<?php if($site->exists($company->email)) { ?>
								<div class="company-wrap-info">
									<i class="far fa-envelope fa-md"></i>
									<a href="mailto:<?php echo esc_html($company->email); ?>">
										<?php echo esc_html($company->email); ?>
									</a> 
								</div>
							<?php } ?>
						</span>
					</div>
				</div>
			</div>

		<?php } ?>
	</div>
	<?php if ($site->exists($site->getPageContent('booking_edit_terms'))) { ?>
		<div class="rezgo-company-info div-box-shadow p-helper">
			<h3 id="rezgo-booking-edit-terms-header"><span>Booking Edit Terms</span></h3>
			<?php echo $site->getPageContent('booking_edit_terms'); ?>
		</div>
	<?php } ?>
</div>
