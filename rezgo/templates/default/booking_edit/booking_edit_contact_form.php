<script type="text/javascript" src="<?php echo $site->path; ?>/js/jquery.validate.min.js"></script>

<div class="row rezgo-form-group" id="rezgo-booking-edit-form">

	<div id="contact_success" style="display:none;">Your message has been sent.</div>

	<form class="form-horizontal" id="contact_form" role="form">
		<input type="hidden" name="booking_date" value="<?php echo (string)$booking->date_formatted; ?>">
		<input type="hidden" name="booking_time" value="<?php echo $booking_time; ?>">
		<input type="hidden" name="expiry_date" value="<?php echo date((string)$company->date_format, (int)$booking->expiry); ?>">
		<input type="hidden" name="availability_type" value="<?php echo $availability_type; ?>">
		<input type="hidden" name="item_id" value="<?php echo $booking->item_id; ?>">
		<input type="hidden" name="booking_id" value="<?php echo $booking->trans_num; ?>">
		<input type="hidden" name="item_name" value="<?php echo $booking->tour_name . ' - ' . $booking->option_name; ?>">

		<div class="form-group row align-items-start">
			<div class="col-12 col-sm-6 rezgo-form-input">
				<label for="contact_fullname">Name</label>
				<input type="text" class="form-control" id="contact_fullname" placeholder="Full Name" required name="full_name" value="<?php echo esc_attr($_REQUEST['full_name']); ?>" />
			</div>

			<div class="col-12 col-sm-6 rezgo-form-input">
				<span class="required-group">
					<label for="contact_email">Email</label>
					<input type="email" class="form-control" id="contact_email" placeholder="Email" required name="email" value="<?php echo esc_attr($_REQUEST['email']); ?>" />
				</span>
			</div>
		</div>

		<div class="form-group row align-items-start">
			<div class="col-12 col-sm-6 rezgo-form-input">
				<label for="contact_phone">Phone</label>
				<input type="text" class="form-control" id="contact_phone" placeholder="Phone" name="phone"
						value="<?php echo $_REQUEST['phone']; ?>" />
			</div>
		</div>

		<div class="form-group row align-items-start">
			<div class="col-12 rezgo-form-input">
				<label for="contact_comment" class="form-label text-sm-end">Message</label>
				<textarea class="form-control" name="body" id="contact_comment" rows="8" wrap="on" required style="height:120px;"></textarea>
			</div>
		</div>

		<input type="hidden" name="recaptcha_response" id="recaptchaResponse">

		<div id="contact-submit-container" class="col-md-6 offset-md-3 text-center">
			<input type="submit" id="submit-contact-btn" class="btn btn-lg btn-block" value="Send Request" />

			<br>
			
			<?php $summary_link = $site->base.'/complete/'.$site->encode($booking->trans_num); ?>
			<a id="rezgo-back-to-summary" class="underline-link reset_edit_view" href="<?php echo esc_url($summary_link); ?>"><i class="far fa-angle-left"></i>
				<span>Back to Booking Details</span>
			</a>
		</div>
	</form>
</div>

<script>

	jQuery(document).ready(function($){
		$.validator.setDefaults({
			highlight: function(element) {
					if ($(element).attr("name") == "email" ) {
						$(element).closest('.required-group').addClass('has-error'); // only highlight email
					} else {
						$(element).closest('.form-group').addClass('has-error');
					}
			},
			unhighlight: function(element) {
				if ($(element).attr("name") == "email" ) {
					$(element).closest('.required-group').removeClass('has-error'); // unhighlight email
				} else {
					$(element).closest('.form-group').removeClass('has-error');
				}
			},
			errorElement: 'span',
			errorClass: 'help-block',
			errorPlacement: function(error, element) {
				if(element.parent('.input-group').length) {
					error.insertAfter(element.parent());
				} else if (element.attr("name") == "agree_privacy") {
					error.insertAfter(element.parents('#agree_privacy_checkbox_wrap')); 
				} else {
					error.insertAfter(element);
				}
			}
		});

		$('#contact_form').validate({
			rules: {
				full_name: {
					required: true
				},
				email: {
					required: true,
					email: true
				},
				phone: {
					required: true,
				},
			},
			messages: {
				full_name: {
					required: "Please enter your full name"
				},
				email: {
					required: "Please enter a valid email address"
				},
				phone: {
					required: "Please enter a valid phone number"
				},
				body: {
					required: "Please enter a comment"
				},
				agree_privacy: {
					required: "Please agree to the privacy policy"
				},
			}
		});

		$('#contact_form').submit(function(e) {
			e.preventDefault();

			let valid = $('#contact_form').valid();

			if (valid) {
				$('#contact_form').ajaxSubmit({
					url: '<?php echo admin_url('admin-ajax.php'); ?>' + '?action=rezgo&method=booking_edit_ajax', 
					data: { 
						rezgoAction: 'booking_edit_contact'
					},
					success: function(data){
						let response = JSON.parse(data);
						if (response.status == 1) {
							$('#contact_form').hide();
							$('#contact_success').show();
						}

					}
				});
			}
		});
		
	});

</script>