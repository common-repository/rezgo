<?php $company = $site->getCompanyDetails(); ?>

<?php
$contact_enabled = $site->getPageName('contact');

if ($contact_enabled == '404') {
	$base_link = REZGO_WORDPRESS ? $site->base : '/'.$site->base;
    $site->sendTo($base_link);
}
?>

<?php 
	if (isset($_POST['rezgoAction']) && $_POST['rezgoAction'] == 'contact') {
		$bot_request = '';

		if (REZGO_WORDPRESS) {
			// NONCE CHECK
			check_admin_referer('rezgo-nonce');
		}
	
		if ($_POST['hp_rezgo'] != '') {
			$bot_request = TRUE;
		} else {

			$site->cleanRequest();

			if ($site->exists(REZGO_CAPTCHA_PRIV_KEY)){
				
				// recaptcha v3 
				$recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';
				$recaptcha_secret = REZGO_WORDPRESS ? REZGO_CAPTCHA_PRIV_KEY : '6LfIFM0ZAAAAAPd56M0j31FQSzSiiXLL4WRFc6sy';
				$recaptcha_response = sanitize_text_field($_POST['recaptcha_response']);
				$recaptcha_fail = '';

				// Make and decode POST request:
				$recaptcha = file_get_contents($recaptcha_url . '?secret=' . $recaptcha_secret . '&response=' . $recaptcha_response);
				$recaptcha = json_decode($recaptcha);

				if ($recaptcha->score >= 0.75) {
					$recaptcha_fail = FALSE;
					$result = $site->sendContact();
				} else {
					$recaptcha_fail = TRUE;
				}

			} else {
				$result = $site->sendContact();
			}

		}
	} 
?>

<?php if (!REZGO_WORDPRESS) { ?>
<script type="text/javascript" src="<?php echo esc_html($site->path); ?>/js/jquery.selectboxes.js"></script><!-- .min not working -->
<script type="text/javascript" src="<?php echo esc_html($site->path); ?>/js/jquery.validate.min.js"></script>  
<?php if($site->exists(REZGO_CAPTCHA_PUB_KEY)) { ?>
	<script src="https://www.google.com/recaptcha/api.js?render=<?php echo esc_html(REZGO_CAPTCHA_PUB_KEY); ?>"></script>
<?php } ?>
<?php } ?>

<div class="rezgo-contact-container">
	<div class="rezgo-content-row">
		<h1 id="rezgo-contact-head">Contact Us</h1>
		<i class="far fa-comment-alt-lines gift-icon"></i>

		<div id="rezgo-about-content" class="rezgo-contact-page-content"><?php echo $site->getPageContent('contact'); ?></div>

		<?php if (isset($result) && $result->status == 1 && $bot_request !== TRUE) { ?>
			<script type="text/javascript">
				jQuery(document).ready(function($){
					parent.scrollTo(0,0);
				});
			</script>

			<div class="rezgo-form-group">
				<div id="contact_success">Thank you for your message.</div>
			</div>
		<?php } else { ?>
			<div class="row rezgo-form-group" id="rezgo-contact-form">
				<?php $action = !REZGO_WORDPRESS ? 'action="page_contact?"' : ''; ?>
				<form class="form-horizontal" id="contact_form" role="form" method="post" <?php echo $action; ?> target="_self">
					<input type="hidden" name="rezgoAction" value="contact" />

					<div class="form-group row align-items-start">
						<div class="col-12 col-sm-6 rezgo-form-input">
							<label for="contact_fullname">Name</label>
							<input type="text" class="form-control" id="contact_fullname" placeholder="Full Name" required name="full_name" value="<?php echo esc_attr($_REQUEST['full_name'] ?? ''); ?>" />
						</div>

						<div class="col-12 col-sm-6 rezgo-form-input">
							<span class="required-group">
								<label for="contact_email">Email</label>
								<input type="email" class="form-control" id="contact_email" placeholder="Email" required name="email" value="<?php echo esc_attr($_REQUEST['email'] ?? ''); ?>" />
							</span>
						</div>
					</div>

					<div class="form-group row align-items-start">
						<div class="col-12 col-sm-6 rezgo-form-input">
							<label for="contact_phone">Phone</label>
							<input type="text" class="form-control" id="contact_phone" placeholder="Phone" name="phone"
									value="<?php echo esc_attr($_REQUEST['phone'] ?? ''); ?>" />
						</div>

						<div class="col-12 col-sm-6 rezgo-form-input">		
							<label for="contact_address" class="control-label text-sm-end">Address</label>
							<input type="text" class="form-control" id="contact_address" placeholder="Address" name="address" value="<?php echo esc_attr($_REQUEST['address'] ?? ''); ?>" />
						</div>
					</div>

					<div class="form-group row align-items-start">
						<div class="col-12 col-sm-6 rezgo-form-input">
							<label for="contact_city" class="control-label text-sm-end">City</label>
							<input type="text" class="form-control" id="contact_city" placeholder="City" name="city" value="<?php echo esc_attr($_REQUEST['city'] ?? ''); ?>" />
						</div>	

						<div class="col-12 col-sm-6 rezgo-form-input">
							<label for="contact_state" class="control-label text-sm-end">State/Prov</label>
							<input type="text" class="form-control" id="contact_state" placeholder="State" name="state_prov" value="<?php echo esc_attr($_REQUEST['state_prov'] ?? ''); ?>" />
						</div>		
					</div>

					<div class="form-group row align-items-start">
						<div class="col-12 col-sm-6 rezgo-form-input">
							<label for="contact_country" class="control-label text-sm-end">Country</label>
							<select class="form-select" id="contact_country" name="country">
								<?php
								foreach( $site->getRegionList() as $iso => $country_name ) { 
									echo '<option value="'.esc_attr($iso).'"';
									if ($iso == $_REQUEST['country']) {
										echo ' selected';
									} elseif ($iso == $site->getCompanyCountry() && !isset($_REQUEST['country'])) {
										echo ' selected';
										}
										echo '>'.ucwords(esc_html($country_name)).'</option>';
									}
								?>
							</select>
						</div>
					</div>

					<div class="form-group row align-items-start">
						<div class="col-12 rezgo-form-input">
							<label for="contact_comment" class="form-label text-sm-end">Comment</label>
							<textarea class="form-control" name="body" id="contact_comment" rows="8" wrap="on" required style="height:120px;"><?php echo esc_textarea($_REQUEST['body'] ?? ''); ?></textarea>
							<input type="text" name="hp_rezgo" class="hp_rez" value="" />
						</div>
					</div>

					<div class="form-group">
						<div class="row rezgo-form-input">
							<div id="agree_privacy_checkbox_wrap" class="checkbox clearfix">
								<table>
									<tr>
										<td>
											<input type="checkbox" class="checkbox" id="agree_privacy" name="agree_privacy" value="1" <?php echo (isset($_REQUEST['agree_privacy']) ? 'checked' : ''); ?> required />
										</td>
										<td>
											<label for="agree_privacy" style="margin-bottom:0;"> I have read and agree to the <?php echo esc_html($company->company_name); ?> <a href="javascript: void();" onclick="window.open('<?php echo REZGO_WORDPRESS ? esc_js('/'.$_REQUEST['wp_slug'] ?? '') : ''; ?>/privacy',null, 'height=576,width=1024,resizable=no,scrollbars=yes,status=no,toolbar=no,menubar=no,location=no')" title="Privacy Policy" id="privacy_link">Privacy Policy.</a></label>
										</td>
									</tr>
								</table>
							</div>
						</div>
					</div>        


					<?php if($site->exists(REZGO_CAPTCHA_PUB_KEY)) { ?>	
						<input type="hidden" name="recaptcha_response" id="recaptchaResponse">
					<?php } ?> 

					<?php if (REZGO_WORDPRESS) wp_nonce_field('rezgo-nonce'); ?>
					<div id="contact-submit-container" class="col-md-6 offset-md-3">
						<input type="submit" id="submit-contact-btn" class="btn btn-lg btn-block" value="Send Request" />
					</div>
				</form>
			</div>
		<?php } ?>

		<div class="rezgo-content-row row" id="rezgo-contact-address">
			<div class="col-md-12 col-lg-4">
				<strong class="company-name">
					<?php echo esc_html($company->company_name); ?>
				</strong>
				<address>
					<?php echo esc_html($company->address_1); ?>
					<?php echo ($site->exists($company->address_2)) ? '<br>'.esc_html($company->address_2) : ''; ?>
					<?php echo ($site->exists($company->city)) ? '<br>'.esc_html($company->city) : ''; ?>
					<?php echo ($site->exists($company->state_prov)) ? esc_html($company->state_prov) : ''; ?>
					<?php echo ($site->exists($company->postal_code)) ? '<br>'.esc_html($company->postal_code) : ''; ?>
					<?php echo esc_html($site->countryName($company->country)); ?>
				</address>

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
				<?php if($site->exists($company->tax_id)) { ?><br>
					<div class="company-wrap-info">
						<span class="tax-label">Tax ID: </span>
						<?php echo esc_html($company->tax_id); ?>
					</div>
				<?php } ?>

			</div>
			<?php
				if (!$site->exists($company->map->zoom)) { 
					$map_zoom = 6; 
				} else { 
					$map_zoom = $company->map->zoom; 
				}
			?>
			<div class="col-md-12 col-lg-8 offset-md-0">
				<?php if (GOOGLE_API_KEY != '' && $company->map->lat != '' && $company->map->lon != '' && !REZGO_CUSTOM_DOMAIN) { ?>
					<div class="rezgo-map" id="rezgo-company-map">
					<iframe width="100%" height="500" frameborder="0" style="border:0;margin-bottom:0;margin-top:-110px;" src="https://www.google.com/maps/embed/v1/place?key=<?php echo esc_html(GOOGLE_API_KEY); ?>&maptype=roadmap&q=<?php echo esc_attr($company->map->lat); ?>,<?php echo esc_attr($company->map->lon); ?>&center=<?php echo esc_attr($company->map->lat); ?>,<?php echo esc_attr($company->map->lon); ?>&zoom=<?php echo esc_attr($map_zoom); ?>"></iframe>
					</div>
				<?php } ?> 
			</div>
		</div>

	</div><!-- // .rezgo-content-row -->
</div><!-- // .rezgo-container -->

<script>
		
	jQuery(document).ready(function($) {

		<?php if($site->exists(REZGO_CAPTCHA_PUB_KEY)) { ?>
			<?php if ($recaptcha_fail == TRUE) { ?> 
			$('#contact-submit-container').append("<span class='help-block' style='color:#a94442'>Recaptcha failed, your message was not sent.</span>");
			console.log('failed recaptcha');
			<?php } ?> 

			grecaptcha.ready(function() {
				grecaptcha.execute('<?php echo REZGO_CAPTCHA_PUB_KEY; ?>', {action: 'submit'}).then(function(token) {
				var recaptchaResponse = document.getElementById('recaptchaResponse');
				if (recaptchaResponse) recaptchaResponse.value = token;
				});
			});
		<?php } ?>

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

		$.validator.addMethod("validate_domain", function(value, element) {
			if (/^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/.test(value)) {
				return true;
			} else {
				return false;
			}
		}, "Enter a valid email address");

		$('#contact_form').validate({
			rules: {
				full_name: {
					required: true
				},
				email: {
					required: true,
					email: true,
					validate_domain: true,
				},
				body: {
					required: true,
				},
				agree_privacy: {
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
				body: {
					required: "Please enter a comment"
				},
				agree_privacy: {
					required: "Please agree to the privacy policy"
				},
			}
		});
	});
</script> 
