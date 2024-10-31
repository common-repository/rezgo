<?php if (!REZGO_WORDPRESS) { ?>
	<script type="text/javascript" src="<?php echo $site->path; ?>/js/jquery.form.js"></script>
	<?php if ($_REQUEST['type'] != 'order') { ?>
	<script type="text/javascript" src="<?php echo $site->path; ?>/js/jquery.validate.min.js"></script>
	<script type="text/javascript" src="<?php echo $site->path; ?>/js/bootstrap-birthday.js"></script>
	<?php } ?>
<?php } ?>

<?php 
	if ($_REQUEST['type'] == 'order') {
		$id = $_REQUEST['ids'];
	} else {
		$booking_data = $site->getBookings('q='.$_REQUEST['trans_num']);
		$site->readItem($booking_data); // use $booking_data[0]
		$pax_data = array();
		
		foreach ($booking_data[0]->passengers->passenger as $pax) {
			if ((int) $pax->id == $_REQUEST['paxid']) {
				$pax_data = $pax;
			}
		}
		$id = $booking_data[0]->item_id;
	}
?>

<script>
	function removeLoader() {
		var loader = window.parent.document.getElementById('rezgo-modal-loader');
		loader.style.display = 'none';
	}
	// window.onload = function(){
	// 	removeLoader();
	// }

	// get waiver on page load
	jQuery.ajax({
		url: '<?php echo admin_url('admin-ajax.php'); ?>',
		type: 'POST',
		context: document.body,
		data : {
			action: 'rezgo',
			method: 'waiver_ajax',
			waiver_action: 'get_waiver',
			type: '<?php echo sanitize_text_field($_REQUEST['type']); ?>',
			option_id: '<?php echo $id; ?>',
			cart: "<?php echo $site->cart_token; ?>"
		},
		success: function(data) {
			removeLoader();
			jQuery('#waiver_modal_content').html(data); 
		}
	});
</script>

<?php if (!REZGO_WORDPRESS) { ?>
<link rel="stylesheet" href="<?php echo $site->path; ?>/css/signature-pad.css" />
<?php } ?>

<style media="print">
	#rezgo-waiver-wrp.rezgo-modal-wrp .tab-text .body {
		height: auto;
		overflow: visible;
	}
	.resign-initial-btn, 
	.initial-container .fa,
	#rezgo-waiver-wrp.rezgo-modal-wrp .tab-text .footer {
		display: none;
	}
	.initial-signature-container .initial-area {
		border: 0;
		margin: 0;
		padding: 0;
	}
	.initial-img-col {
		width: 100px;
	}
	.initial-img-col img {
		width: 100px;
		object-fit: contain;
	}
</style>

<style>
	.pax_year {
		padding-left:0 !important;
		padding-right:6px !important;
	}
	.pax_month { 
		padding-left:6px !important;
		padding-right:6px !important;
	}
	.pax_day { 
		padding-left:6px !important;
		padding-right:0 !important;
	}
	#rezgo-waiver-errors {
		width: 100%;
		margin-top: 20px;
	}
	.rezgo-waiver-label-error {
		color: #a94442;
	}	
</style>

<div id="rezgo-waiver-wrp" class="container-fluid rezgo-container rezgo-modal-wrp">
	<div class="clearfix">
  
		<div class="tab-text">
			<div class="body">
				<div class="row">
					<div class="col-md-12 rezgo-waiver-modal-text">
						<div id="waiver_modal_content"></div>

						<?php if ($_REQUEST['type'] != 'order') { ?>
						
						<!-- boilerplate was here -->
							
						<div id="rezgo-waiver-please-complete" class="rezgo-waiver-instructions"><span>Please complete the following required fields.</span></div>
						
						<form name="pax_waiver_form" id="pax_waiver_form" method="post" target="rezgo_content_frame">

							<input type="hidden" name="pax_id" id="pax_id" value="<?php echo $pax_data->id; ?>" />
							<input type="hidden" name="pax_type" id="pax_type" value="<?php echo $pax_data->type; ?>" />
							<input type="hidden" name="pax_type_num" id="pax_type_num" value="<?php echo $pax_data->type[num]; ?>" />
							<input type="hidden" name="pax_item" id="pax_item" value="<?php echo $booking_data[0]->item_id; ?>" />
							<input type="hidden" name="pax_signature" id="pax_signature" value="" />
							<input type="hidden" name="trans_num" id="trans_num" value="<?php echo $_REQUEST['trans_num']; ?>" />
							
							<textarea name="pax_waiver_text" id="pax_waiver_text" style="display:none; visibility:hidden; height:0;"><?php echo $site->getWaiverContent((int) $booking_data[0]->item_id); ?></textarea>
							
							<div class="row rezgo-form-group rezgo-additional-info">
								<div class="rezgo-form-row rezgo-form-one form-group rezgo-pax-first-last row">
									<label id="rezgo-waiver-first-name" for="pax_first_name" class="col-5 col-sm-2 control-label rezgo-label-right"><span>First</span></label>
									<div class="col-7 col-sm-4 rezgo-form-input">
										<input type="text" class="form-control required" id="pax_first_name" name="pax_first_name" value="<?php echo $pax_data->first_name; ?>" /> 
									</div>
									<label id="rezgo-waiver-last-name" for="pax_last_name" class="col-5 col-sm-2 control-label rezgo-label-right"><span>Last</span></label>
									<div class="col-7 col-sm-4 rezgo-form-input">
										<input type="text" class="form-control required" id="pax_last_name" name="pax_last_name" value="<?php echo $pax_data->last_name; ?>" />
									</div>
								</div>
				
								<div class="rezgo-form-row rezgo-form-one form-group rezgo-pax-phone-email row">
									<label id="rezgo-waiver-phone" for="pax_phone" class="col-5 col-sm-2 control-label rezgo-label-right"><span>Phone</span></label>
									<div class="col-7 col-sm-4 rezgo-form-input">
										<input type="text" class="form-control required" id="pax_phone" name="pax_phone" value="<?php echo $pax_data->phone_number; ?>" />
									</div>
									<label id="rezgo-waiver-email" for="pax_email" class="col-5 col-sm-2 control-label rezgo-label-right"><span>Email</span></label>
									<div class="col-7 col-sm-4 rezgo-form-input">
										<input type="email" class="form-control required" id="pax_email" name="pax_email" value="<?php echo $pax_data->email_address; ?>" />
									</div>
								</div>
				
								<div class="rezgo-form-row rezgo-form-one form-group row" id="pax-birth-wrp">
									<label id="rezgo-waiver-birthdate" for="pax_birthdate" class="col-5 col-sm-2 control-label rezgo-label-right"><span>Birth <span class="hidden-xs">Date</span></span></label>
									<div class="col-7 col-sm-10 rezgo-form-input">
										<input type="text" class="form-control required" id="pax_birthdate" name="pax_birthdate" value="" />
									</div>
								</div>

							</div>

						</form>

						<?php } // end if ($_REQUEST[type]) ?>
						
						<div id='signature-area' style='display:none;'>
							<hr>

							<div class="row">
								<div class="col-12">
									<small>Signature:</small>
								</div>
							</div>

							<div class="row">
								<div class="col-12">
									<img id='signature-img' alt='signature' />
								</div>
							</div>
						</div>
					</div>
				</div>
				
				<div id="rezgo-waiver-errors" class="waiver-error-message row ms-auto">
					<span class="rezgo-waiver-error">Some required fields are missing. Please complete the highlighted fields.</span>
				</div>
			</div>

			<div class="footer row">
				<div id="rezgo-sign-nav" class="row align-items-center">
					<div class="col col-6">
					<span class="btn-check"></span>
					<button id="sign" class="btn rezgo-btn-default btn-block">
						<i class="fa fa-pencil"></i>
						<span id="rezgo-sign-nav-txt"> Sign Waiver</span>
					</button>
					</div>

					<div class="col col-6">
					<span class="btn-check"></span>
					<button id="print" class="btn rezgo-btn-print btn-block">
						<i class="fa fa-print"></i>
						<span> Print Waiver</span>
					</button>
					</div>
				</div>
			</div>
		</div>

		<div class="tab-sign" style="display:none;">
			<div id="signature-pad">
				<div class="undo-btn-wrapper">
					<button id="undo" data-action="undo" type="button">
						<i class="far fa-undo"></i>
						<span>Undo</span>
					</button>
				</div>
				<div class="body">
					<p>Please sign in the space below</p>
					<canvas></canvas>
				</div>
				<div class="footer">
					<div class="row align-items-center">
						<div class="col col-6">
							<span class="btn-check"></span>
							<button id="clear" class="btn rezgo-btn-default btn-block" data-action="clear" type="button">
								<i class="far fa-times"></i> &nbsp;
								<span> Clear</span>
							</button>
						</div>
						<div class="col col-6">
							<span class="btn-check"></span>
							<button id="save" class="btn rezgo-btn-book btn-block" data-action="save" type="button">
								<i class="far fa-check"></i>&nbsp;
								<span> Save</span>
							</button>
						</div>
					</div>
				</div>
			</div>
		</div>
    
	</div>
</div>

<?php if (!REZGO_WORDPRESS) { ?>
	<script src="<?php echo $site->path; ?>/js/signature_pad.min.js"></script>
<?php } ?>

<script>
	var 
	<?php if ($_REQUEST['type'] == 'order') { ?>
	signature = parent.rezgo_content_frame.document.getElementById('rezgo-waiver-input').value,
	<?php } ?>
	receiver = window.parent.document.getElementById('rezgo_content_frame').contentWindow,
	waiverModal = document.getElementById('rezgo-waiver-wrp'),
	signButton = document.getElementById('sign'),
	signBtnTxt = document.getElementById('rezgo-sign-nav-txt'),
	printButton = document.getElementById('print'),
	saveButton = document.getElementById('save'),
	signaturePad = document.getElementById('signature-pad'),
	clearButton = signaturePad.querySelector('[data-action=clear]'),
	undoButton = signaturePad.querySelector('[data-action=undo]'),
	canvas = signaturePad.querySelector('canvas'),
	waiverTxt = waiverModal.getElementsByClassName('tab-text')[0],
	waiverTxtBody = waiverTxt.getElementsByClassName('body')[0],
	waiverSignArea = document.getElementById('signature-area'),
	waiverSignImg = document.getElementById('signature-img'),
	
	firstName = document.getElementById('pax_first_name'),
	lastName = document.getElementById('pax_last_name'),
	paxPhone = document.getElementById('pax_phone'),
	paxEmail = document.getElementById('pax_email'),
	
	paxSignature = document.getElementById('pax_signature'),
	signaturePad = new SignaturePad(canvas);

	function resizeCanvas() {
		var ratio =  Math.max(window.devicePixelRatio || 1, 1);
		canvas.width = canvas.offsetWidth * ratio;
		canvas.height = canvas.offsetHeight * ratio;
		canvas.getContext("2d").scale(ratio, ratio);
		signaturePad.clear();
	}
	
	function printWaiver(e) {
		setTimeout(function() { 
			window.focus(); 
			window.print(); 
		}, 200);
	}
	
	function showSignaturePad(e) {
		<?php if ($_REQUEST['type'] != 'order') { ?>
		if(!validate_form()) return false;
		<?php } ?>

		valid_initial = true;
		parent.rezgo_content_frame.jQuery('.hidden-initial-inputs').each(function(){
			if (jQuery(this).val() == ''){ 
				
				// grab id from input id
				let id = jQuery(this).attr('id').substr(11);
				// show error
				jQuery('#initial_' + id + '_validation').show();

				jQuery('.rezgo-waiver-error').html('Some required fields are missing. Please complete the highlighted fields');
				valid_initial = false;
			}
		})
		if (!valid_initial) {
			jQuery('#rezgo-waiver-errors').fadeIn();

			jQuery('.waiver-initial-prompt').each(function(){
				if (jQuery(this).css('display') != 'none'){
					let id = jQuery(this).attr('id');
					jQuery('#' + id).get(0).scrollIntoView({ behavior: 'smooth' });
					return false;
				}
			})
			return false;
		}

		jQuery(".tab-text").hide();
		jQuery(".tab-sign").show();
		resizeCanvas();
	}

	function validate_form() {
		var valid = jQuery('#pax_waiver_form').valid();
		return valid;
	}
	
	function clearSignature(e) {
		signaturePad.clear();
	}
	
	function undoSignature(e) {
		let data = signaturePad.toData();
		if (data) {
			data.pop(); // remove the last dot or line
			signaturePad.fromData(data);
		}
	}
	
	function checkOverflow(el) {
		var curOverflow = el.style.overflow;

		if(!curOverflow || curOverflow === "visible") el.style.overflow = "hidden";

		var isOverflowing = el.clientHeight < el.scrollHeight;

		el.style.overflow = curOverflow;

		return isOverflowing;
	}
	
	function saveSignatureOrder(e) {
		if (signaturePad.isEmpty()) {
			alert("Please provide a signature first.");
		} else {
			e.preventDefault();

			// disable to prevent multiple submissions
			saveButton.disabled = true;

			canvas.style.visibility = 'hidden';
			// addSignature(signaturePad.toDataURL());
			
			data_url = signaturePad.toDataURL();

			// get raw base 64 string
			const getDataPart = (dataUrl) => dataUrl.split(',', 2)[1];
			let raw_base_64 = getDataPart(data_url);

			function safeBase64Encode(string) {
				try {
					// If it's encoded, it will look the same in atob(btoa()) and btoa(atob())
					let isEncoded = btoa(atob(string)) == atob(btoa(string))
					if ( isEncoded ) {
						// return string;
					}
				} catch (err) {
					// If we get an error like it's not a valid encoding,
					// return btoa(string);
				}
			}
			safeBase64Encode(raw_base_64);

			// save waiver signatures 
			jQuery.ajax({
				url: '<?php echo admin_url('admin-ajax.php'); ?>',
				type: 'POST',
				data: { 
					action: 'rezgo',
					method: 'waiver_ajax',
					waiver_action: 'save_signature',
					item_id: '<?php echo $id; ?>',
					signature: data_url,
				},
				success: function(response) {
					response = JSON.parse(response);

					if (response.signature_url) {

						var msg = {
							type:'modal',
							mode:'order_waiver',
							sig: data_url,
							signature_url: response.signature_url
						};

						receiver.postMessage(msg, '*');

						<?php if (!REZGO_LITE_CONTAINER){ ?>
							// save signature to local storage
							localStorage.setItem('signature', msg.sig );

							// save signature url to local storage
							localStorage.setItem('signature_url', response.signature_url );
						<?php } ?>
					}
				},
				error: function(response) {
					console.log(response);
				}
			})
						
		}
	}
	
	function saveSignaturePax(e) {
		if (signaturePad.isEmpty()) {
			alert("Please provide a signature first.");
		} else {
			e.preventDefault();

			canvas.style.visibility = 'hidden';
			//signaturePad.removeBlanks();
			addSignature(signaturePad.toDataURL());
			
			$('#pax_signature').val(signaturePad.toDataURL());
			
			// the field is present? submit normally
			$('#pax_waiver_form').ajaxSubmit({
				url: '<?php echo $site->base; ?>/waiver_ajax.php', 
				data: { action: 'sign' },
				success: function(response) { 
						
					var msg = {
						type: 'modal',
						mode: 'pax_waiver',
						resp: response,
						name: firstName.value + ' ' + lastName.value,
						phone: paxPhone.value,
						email: paxEmail.value,
						paxid: '<?php echo $_REQUEST['paxid'] ?? ''; ?>',
						sig: signaturePad.toDataURL()
					};
				
					receiver.postMessage(msg, '*');
				} 
				
			});
						
		}
	}
	
	function addSignature(req) {
		waiverSignArea.style.display = 'block';
		waiverSignImg.src = req;
		signBtnTxt.innerHTML = 're-sign waiver';
	}
	
	function back() {
		backButton.style.display = "none";
		jQuery(".tab-text").show();
		jQuery(".tab-sign").hide();
	}

	<?php if ($_REQUEST['type'] == 'order') { ?>
	saveButton.addEventListener('click', saveSignatureOrder);
	<?php } else { ?>
	saveButton.addEventListener('click', saveSignaturePax);
	<?php } ?>
	
	signButton.addEventListener('click', showSignaturePad);
	printButton.addEventListener('click', printWaiver);
	undoButton.addEventListener('click', undoSignature);
	clearButton.addEventListener('click', clearSignature);

	window.onresize = resizeCanvas;

	
	<?php if ($_REQUEST['type'] == 'order') { ?>
	if(signature !== '') {
		addSignature(signature);
	}
	<?php } ?>
		
</script>
<?php if (!REZGO_WORDPRESS) { ?>

	<?php if ($_REQUEST['type'] != 'order') { ?>
	<script>
		$(document).ready(function() {

			$('#pax_birthdate').bootstrapBirthday({
				dateFormat: 'bigEndian',
				widget: {
				wrapper: {
					tag: 'div',
					class: 'pax_birth_row'
				},
				wrapperYear: {
					use: true,
					tag: 'div',
					class: 'col-4 pax_year'
				},
				wrapperMonth: {
					use: true,
					tag: 'div',
					class: 'col-4 pax_month'
				},
				wrapperDay: {
					use: true,
					tag: 'div',
					class: 'col-4 pax_day'
				},
				selectYear: {
					name: 'pax_birthdate[year]',
					class: 'form-control required'
				},
				selectMonth: {
					name: 'pax_birthdate[month]',
					class: 'form-control required'
				},
				selectDay: {
					name: 'pax_birthdate[day]',
					class: 'form-control required'
				}
				}
			});

			// Validation Setup
			$.validator.setDefaults({
				highlight: function(element) {
					if ( $(element).attr("name") == "pax_birthdate" ) {
						$('#pax-birth-wrp span.help-block').addClass('rezgo-waiver-error');
						$('#pax-birth-wrp label').addClass('rezgo-waiver-label-error');
					} else {
						$(element).closest('.rezgo-form-input').addClass('has-error');
					}
					$(element).closest('.form-group').addClass('has-error');
				},
				unhighlight: function(element) {
					if ( $(element).attr("name") == "pax_birthdate" ) {
						$('#pax-birth-wrp span.help-block').hide();
						$('#pax-birth-wrp span.help-block').removeClass('rezgo-waiver-error');
					} else {
						$(element).closest('.rezgo-form-input').removeClass('has-error');
					}
					$(element).closest('.form-group').removeClass('has-error');
				},
				ignore: ":hidden:not(.required)", 
				focusInvalid: false,
				errorElement: 'span',
				errorClass: 'help-block',
				errorPlacement: function(error, element) {
					if ($(element).attr("name") == "pax_birthdate") {
						error.insertAfter('.pax_birth_row');
					} else {
						error.insertAfter(element);
					}
				}

			});

			$('#pax_waiver_form').validate({
				rules: { 
					pax_birthdate:"required"
				},
				messages: {
					pax_first_name: {
						required: "Enter your first name"
					},
					pax_last_name: {
						required: "Enter your last name"
					},
					pax_phone: {
						required: "Enter your phone number"
					},
					pax_email: {
						required: "Enter a valid email address"
					},
					pax_birthdate: {
						required: "Please enter your date of birth"
					}
				}
			});

		});
	</script>
	<?php } ?>
<?php } ?>
