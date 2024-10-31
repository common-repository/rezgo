<?php 
	// This script handles the booking requests made via ajax by book.php
	
	require('rezgo/include/page_header.php');

	// start a new instance of RezgoSite
	$site = new RezgoSite('secure');
	
	$response = 'empty';

	$option_id = sanitize_text_field($_REQUEST['option_id']);	

	// save signed waiver
	if ($_REQUEST['waiver_action'] == 'save_signature') {

		$result = $site->saveWaiverSignature();

		if ($result->status == 'saved') {
			$response = [
				'status' => (string)$result->status,
				'signature_url' => (string)$result->signature_url,
			];
			$response = json_encode($response, JSON_PRETTY_PRINT);
		}
		
	}

	// save signed initial information
	if ($_REQUEST['waiver_action'] == 'save_initial') {

		$result = $site->saveWaiverInitial();

		if ($result->status == 'saved') {
			$response = [
				'status' => (string)$result->status,
				'signature_url' => (string)$result->signature_url,
			];
			$response = json_encode($response, JSON_PRETTY_PRINT);
		}
		
	} 

	// save signed waiver information
	if ($_REQUEST['waiver_action'] == 'sign') {
		
		$result = $site->signWaiver();
		
		if ($result->status == 'signed') {
			$response = $result->status;
		} else {
			$response = (string) $result->error;
		}
		
	} 
	
	// get waiver content
	if ($_REQUEST['waiver_action'] == 'get_waiver') {

		$cart = sanitize_text_field($_REQUEST['cart'] ?? '');
		$pax_id = sanitize_text_field($_REQUEST['pax_id'] ?? '');
		$waiver_uid = !$pax_id ? REZGO_CID : REZGO_CID . '_' . $pax_id;
		
		if ($_REQUEST['type'] == 'order' || $pax_id) {
			$response = $site->getWaiverContent( $option_id );
		} else {
			$response = $site->getWaiverContent( $option_id, 'com');
		}
		
		$initial = '[initial]';
		$initial_prompt = '
					<div class="initial-container">
						<i class="fa fa-pencil"></i> 
						<a data-bs-toggle="collapse" class="waiver-initial-prompt underline-link collapsed">
							Click here to Initial <i class="fa fa-sm fa-asterisk"></i>
						</a>
						<div class="initial-signature-container collapse"></div>
						<div class="initial-signature-validation" style="display:none;"><span>Please Add Your Initials Above</span></div>
					</div>
				';

		$initial_count = substr_count($response, $initial);

		// add initial scripts here 
		if ($initial_count > 0) {

		$initial_scripts .= '<script>';

		$initial_scripts .= 'var order = '.($_REQUEST['type'] == 'order' ? 1 : 0).';';

			$initial_scripts .= '
			// initial related functions
			var initial_index = 0;
			jQuery(".waiver-initial-prompt").each(function(){
				let initial_id = "initial_'.$waiver_uid.'_" + initial_index;
				jQuery(this).attr("id", initial_id + "_prompt");
				jQuery(this).attr("data-id", initial_id);
				jQuery(this).attr("data-bs-target", "#" + initial_id);
				jQuery(this).closest(".initial-container").find(".initial-signature-container").attr("id", initial_id);
				jQuery(this).closest(".initial-container").find(".initial-signature-validation").attr("id", initial_id + "_validation");
				
				initial_index++;
			}); 

			var initial_signed_index = 0;
			jQuery(".initial-signed-container").each(function(){
				let initial_id = "initial_'.$waiver_uid.'_" + initial_signed_index + "_signed_container";
				jQuery(this).attr("id", initial_id);
				
				initial_signed_index++;
			}); 

			jQuery(".initial-signature-container").each(function(){
				let id = jQuery(this).attr("id");
				jQuery(this).closest(".initial-container").attr("id", id + "_container");

				let content = `
						<div class="initial-area">
							<div id="${id}_canvas_container" class="row">
								<div class="col-md-5 col-12">
									<div class="canvas-body">
										<p class="initial-header"><span>Please initial below</span></p>
										<canvas></canvas>
									</div>
									<div class="initial-btn-row">
										<button class="clear-initial-btn reset-btn me-auto ms-auto" data-action="clear">Clear</button>
										<button class="reset-btn underline-link me-auto ms-auto" id="save_${id}" data-action="clear">Save</button>
									</div>
								</div>
							</div>

							<div id="${id}_img_container" class="row initial-img-container" style="display:none;">
								<div class="col-md-5 col-12 initial-img-col">
									<img id="${id}_img" class="initial-imgs" alt="initial" />
									<button class="resign-initial-btn reset-btn underline-link ms-auto me-auto" data-action="resign">Re-Sign Initial</button>
									<div class="initial-img-loading-container"></div>
								</div>
							</div>
						</div>
				`;
				jQuery(this).append(content);
			});

			jQuery(".waiver-initial-prompt").click(function(e){
				e.preventDefault();
				jQuery(this).hide();
			});

			// we need to empty this 
			if (order) { 
				parent.rezgo_content_frame.jQuery("#append_initial_inputs").empty();
			} else {
				jQuery("#append_initial_inputs").empty();
			}
		';

		for ($i=0; $i < $initial_count; $i++) { 
			$id = $waiver_uid . '_' .$i; 

			$initial_scripts .= '

				// insert initial inputs
				let order_content_'.$i.' = "<input class=hidden-initial-inputs type=hidden name=waiver_url_'.$id.' id=waiver_url_'.$id.'>";
				if (order) {
					parent.rezgo_content_frame.jQuery("#append_initial_inputs").append(order_content_'.$i.');
				}

				// check for initials in localStorage
				if (order) {
					if (localStorage.length > 1) {
						for (i = 0; i < localStorage.length; i++){
							const key = localStorage.key(i);

							// show previously saved initials
							if (key.includes("'.$cart.'_initial_url_'.$id.'")) {
								jQuery("#initial_'.$id.'_canvas_container").hide();
								jQuery("#initial_'.$id.'_prompt").hide();
								jQuery("#initial_'.$id.'").addClass("show");
								jQuery("#initial_'.$id.'_img_container").find(".initial-img-loading-container").hide();
								jQuery("#initial_'.$id.'_img_container").show();
								jQuery("#initial_'.$id.'_img").attr("src",`${localStorage.getItem(key)}`);

								jQuery("#waiver_url_'.$id.'").val(`${localStorage.getItem(key)}`);
								parent.rezgo_content_frame.jQuery("#waiver_url_'.$id.'").val(`${localStorage.getItem(key)}`);
							}
						}
					}
				}

				var 
				initialPad_'.$id.' = document.getElementById("initial_'.$id.'"),
				initialCanvas_'.$id.' = initialPad_'.$id.'.querySelector("canvas"),
				showCanvas_'.$id.' =  document.getElementById("initial_'.$id.'_prompt"),
				saveButton_'.$id.' = document.getElementById("save_initial_'.$id.'"),
				clearButton_'.$id.' = initialPad_'.$id.'.querySelector("[data-action=clear]"),
				reSignInitial_'.$id.' = initialPad_'.$id.'.querySelector("[data-action=resign]"),
				initialPad_'.$id.' = new SignaturePad(initialCanvas_'.$id.');

				function resizeInitialCanvas_'.$id.'() {
					var ratio =  Math.max(window.devicePixelRatio || 1, 1);
					var canvas_styles = getComputedStyle(initialCanvas_'.$id.');

					initialCanvas_'.$id.'.width = parseInt(canvas_styles.getPropertyValue("width")) * ratio;
					initialCanvas_'.$id.'.height = parseInt(canvas_styles.getPropertyValue("height")) * ratio;
					initialCanvas_'.$id.'.getContext("2d").scale(ratio, ratio);
					initialPad_'.$id.'.clear();
				}
				
				function clearInitial_'.$id.'(e) {
					resizeInitialCanvas_'.$id.'();
					initialPad_'.$id.'.clear();

					jQuery("#initial_'.$id.'_img").attr("src", "");
					
					jQuery("#initial_'.$id.'_canvas_container").show();
					jQuery("#initial_'.$id.'_img_container").hide();
				}

				function saveInitial_'.$id.'() {
					if (initialPad_'.$id.'.isEmpty()) {
						alert("Please provide an initial first.");
					} else {
						jQuery("#initial_'.$id.'_img_container").find(".initial-img-loading-container").show();

						data_url = initialPad_'.$id.'.toDataURL();
						
						// get raw base 64 string
						const getDataPart = (dataUrl) => dataUrl.split(",", 2)[1];
						let raw_base_64 = getDataPart(data_url);

						function safeBase64Encode(string) {
							try {
								let isEncoded = btoa(atob(string)) == atob(btoa(string))
								if ( isEncoded ) {
									// return string;
								}
							} catch (err) {
								fetch("/log?type=waiver&action=" + encodeURIComponent("Invalid Encoding in saveSignatureData") + "&long=" + encodeURIComponent(JSON.stringify(log_data)) + "&source=" + waiver_trace);
								// return btoa(string);
							}
						}
						safeBase64Encode(raw_base_64);

						setTimeout(() => {
							jQuery("#initial_'.$id.'_img_container").find(".initial-img-loading-container").fadeOut();
						}, 2500);

						// hide error message
						jQuery("#initial_'.$id.'_validation").fadeOut();

						if (order) {
							let all_initial = true;
							jQuery(".waiver-initial-prompt").each(function(){
								if (jQuery(this).css("display") != "none"){
									all_initial = false;
								}
							})
							if (all_initial) jQuery("#rezgo-waiver-errors").fadeOut();
						} else {
							// hide bottom error message if all initials are filled 
							jQuery(".initial-imgs").each(function(){
								if (jQuery(this).attr("src") != ""){ 
									jQuery("#rezgo-waiver-errors").fadeOut();
								}
							})
						}

						// save waiver signatures 
						jQuery.ajax({
							url: "'.admin_url("admin-ajax.php").'", 
							type: "POST",
							data: { 
								action: "rezgo",
								method: "waiver_ajax",
								waiver_action: "save_initial",
								item_id: "'.$option_id.'",
								pax_id: "'.$pax_id.'",
								initial_index: "initial_'.$i.'",
								initial: data_url,
							},
							success: function(response) {
								response = JSON.parse(response);

								// there is a saved initial response? 
								jQuery("#waiver_url_'.$id.'").val(response.signature_url);

								if (order) { 
									parent.rezgo_content_frame.jQuery("#waiver_url_'.$id.'").val(response.signature_url);
								} else {
									jQuery("#waiver_url_'.$id.'").val(response.signature_url);
								}

								if (response.signature_url) {

									setTimeout(() => {
										jQuery("#initial_'.$id.'_img").attr("src", data_url);
										jQuery("#initial_'.$id.'_canvas_container").hide();
										jQuery("#initial_'.$id.'_img_container").show();

										// save signature to local storage
										if (order) localStorage.setItem("'.$cart.'_initial_img_'.$id.'", data_url );

										// save signature url to local storage
										if (order) localStorage.setItem("'.$cart.'_initial_url_'.$id.'", response.signature_url );

									}, 200);

								}
							},
							error: function(response) {
								console.log(response);
							}

						})
					}
				}

				showCanvas_'.$id.'.addEventListener("click", resizeInitialCanvas_'.$id.'());
				saveButton_'.$id.'.addEventListener("click", saveInitial_'.$id.');
				reSignInitial_'.$id.'.addEventListener("click", clearInitial_'.$id.');
				clearButton_'.$id.'.addEventListener("click", clearInitial_'.$id.');

				window.onresize = resizeInitialCanvas_'.$id.';
			';

			}
			$initial_scripts .= '</script>';
		}

		// replace instances of [initial] with a prompt on the FE
		$response = $initial_count > 0 ? str_replace($initial, $initial_prompt, $response) : $response;

		$response .= $initial_scripts;
	}
	
	// get waiver forms for booking pax
	if ($_REQUEST['waiver_action'] == 'get_forms') {
		
		foreach ($site->getBookings('q='.$_REQUEST['trans_num'].'&a=waiver,forms') as $booking) { 
		
			$item = $site->getTours('t=uid&q='.$booking->item_id, 0); // &d=2018-06-06
			
			$site->readItem($booking);
			
			if ($booking->availability_type != 'product') {
				
				foreach ($site->getBookingPassengers() as $passenger ) { 
				
					if ($passenger->id == $_REQUEST['pax_id']) $pax_data = $passenger;
				
				}
		
			} // if ($booking->availability_type)
						
		} // foreach $site->getBookings() 
		
		if ($pax_data) {
			
			$waiver_forms = '
			<div class="rezgo-waiver-child">
				<input type="checkbox" id="child" name="child" />  &nbsp;
				<strong>I am signing this waiver on behalf of a child.</strong>
				<div id="rezgo-waiver-child-text" style="display:none;">
				<span>Please enter the child\'s name and birthdate and sign on their behalf.</span></div>
				<div class="clearfix">&nbsp;</div>
			</div>			
			';
			
			$waiver_forms .= '<div id="rezgo-waiver-please-complete" class="rezgo-waiver-instructions"><span>Please complete the following required fields.</span></div>';
			
			$waiver_forms .= '
			<input type="hidden" name="pax_id" id="pax_id" value="'.$pax_data->id.'" />
			<input type="hidden" name="pax_type" id="pax_type" value="'.$pax_data->type.'" />
			<input type="hidden" name="pax_type_num" id="pax_type_num" value="'.$pax_data->num.'" />

				<div class="rezgo-form-row rezgo-form-one form-group rezgo-pax-first-last row">
					<label id="rezgo-waiver-first-name" for="pax_first_name" class="col-xs-5 col-sm-2 control-label rezgo-label-right"><span>First <span class="hidden-xs">Name</span></span></label>
					<div class="col-xs-7 col-sm-4 rezgo-form-input">
						<input type="text" class="form-control required" id="pax_first_name" name="pax_first_name" value="'.$pax_data->first_name.'" autocomplete="off" /> 
					</div>
					<label id="rezgo-waiver-last-name" for="pax_last_name" class="col-xs-5 col-sm-2 control-label rezgo-label-right"><span>Last <span class="hidden-xs">Name</span></span></label>
					<div class="col-xs-7 col-sm-4 rezgo-form-input">
						<input type="text" class="form-control required" id="pax_last_name" name="pax_last_name" value="'.$pax_data->last_name.'" autocomplete="off" />
					</div>
				</div>

				<div class="rezgo-form-row rezgo-form-one form-group rezgo-pax-phone-email row">
					<label id="rezgo-waiver-phone" for="pax_phone" class="col-xs-5 col-sm-2 control-label rezgo-label-right"><span>Phone</span></label>
					<div class="col-xs-7 col-sm-4 rezgo-form-input">
						<input type="text" class="form-control required" id="pax_phone" name="pax_phone" value="'.$pax_data->phone_number.'" autocomplete="off" />
					</div>
					<label id="rezgo-waiver-email" for="pax_email" class="col-xs-5 col-sm-2 control-label rezgo-label-right"><span>Email</span></label>
					<div class="col-xs-7 col-sm-4 rezgo-form-input">
					<input type="email" class="form-control required" id="pax_email" name="pax_email" value="'.$pax_data->email_address.'" autocomplete="off" />
					</div>
				</div>

				<div class="rezgo-form-row rezgo-form-one form-group row" id="pax-birth-wrp">
					<label id="rezgo-waiver-birthdate" for="pax_birthdate" class="col-xs-2 control-label rezgo-label-right"><span>Birth <span class="hidden-xs">Date</span></span></label>
					<div class="col-xs-10 rezgo-form-input" id="pax-birth-input">
						<div class="col-xs-4 rezgo-form-input pax_year">
							<select name="pax_birthdate[year]" id="pax_birthdate_year" class="form-control required">
								<option value=""></option>
							</select>
						</div>
						<div class="col-xs-4 rezgo-form-input pax_month">
							<select name="pax_birthdate[month]" id="pax_birthdate_month" class="form-control required">
								<option value=""></option>
							</select>
						</div>
						<div class="col-xs-4 rezgo-form-input pax_day">
							<select name="pax_birthdate[day]" id="pax_birthdate_day" class="form-control required">
								<option value=""></option>
							</select>
						</div>
					</div>
				</div>
			';
			
			$custom_fields = ' <table border="0" cellspacing="0" cellpadding="2" class="rezgo-table-list">';
			foreach ( $pax_data->forms->form as $form ) {
                $custom_fields .= '<tr class="rezgo-waiver-group-form">
			    				   		<td class="rezgo-td-label">'.$form->title.'</td>
				                   		<td class="rezgo-td-data" id="rezgo-waiver-answer-'.$form->id.'"><span></span></td>
				                   </tr>';
				
				if($form->type == 'text') {
					
					$waiver_forms .= '
						<div class="form-group rezgo-custom-form rezgo-form-input">
							<label><span>'.$form->title.' '.((string) $form->require == '1' ? '<em class="fa fa-asterisk"></em>' : '').'</span></label>  
							<input type="text" class="custom-form-input form-control'.((string) $form->require == '1' ? ' required' : '').'" data-answer="rezgo-waiver-answer-'.$form->id.'" id="rezgo-waiver-form-'.$form->id.'" name="pax_group['.$pax_data->type.']['.$pax_data->num.'][forms]['.$form->id.']" value="'.$form->answer.'" />
							<p class="rezgo-form-comment"><span>'.$form->instructions.'</span></p>
						</div>
					';
					
				}
				
				if($form->type == 'select') {
					
					$waiver_forms .= '
						<div class="form-group rezgo-custom-form rezgo-form-input">
							<label><span>'.$form->title.' '.((string) $form->require == '1' ? '<em class="fa fa-asterisk"></em>' : '').'</span></label>
							<select class="custom-form-input form-control'.((string) $form->require == '1' ? ' required' : '').'" data-answer="rezgo-waiver-answer-'.$form->id.'" id="rezgo-waiver-form-'.$form->id.'" name="pax_group['.$pax_data->type.']['.$pax_data->num.'][forms]['.$form->id.']">
					';
					
					if((string) $form->options) {
						$opt = explode(',', (string)$form->options);
						foreach((array)$opt as $v) {																		
							$waiver_forms .= '<option'.(((string) $form->answer == $v) ? ' selected' : '').'>' . $v . '</option>';
						}
					}
					
					$waiver_forms .= '
							</select>
							<p class="rezgo-form-comment"><span>'.$form->instructions.'</span></p>
						</div>
					';
					
				}
				
				if($form->type == 'multiselect') {
					
					$waiver_forms .= '
						<div class="form-group rezgo-custom-form rezgo-form-input">
							<label><span>'.$form->title.' '.((string) $form->require == '1' ? '<em class="fa fa-asterisk"></em>' : '').'</span></label>
							<select class="custom-form-input form-control'.((string) $form->require == '1' ? ' required' : '').'" multiple="multiple" data-answer="rezgo-waiver-answer-'.$form->id.'" id="rezgo-waiver-form-'.$form->id.'" name="pax_group['.$pax_data->type.']['.$pax_data->num.'][forms]['.$form->id.'][]">
					';
					
					if((string) $form->options) {
						$opt = explode(',', (string)$form->options);
						foreach((array)$opt as $v) {		
							if (strpos((string) $form->answer, ',' === false)) {														
								$waiver_forms .= '<option'.(((string) $form->answer == $v) ? ' selected' : '').'>' . $v . '</option>';
							} else {
								$answers = explode(', ', (string)$form->answer);
								$waiver_forms .= '<option'.((in_array($v, $answers)) ? ' selected' : '').'>' . $v . '</option>';
							}
						}
					}
					
					$waiver_forms .= '
							</select>
							<p class="rezgo-form-comment"><span>'.$form->instructions.'</span></p>
						</div>
					';
					
				}
				
				if($form->type == 'textarea') {
					
					$waiver_forms .= '
						<div class="form-group rezgo-custom-form rezgo-form-input">
							<label><span>'.$form->title.' '.((string) $form->require == '1' ? '<em class="fa fa-asterisk"></em>' : '').'</span></label>
							<textarea class="custom-form-input form-control'.((string) $form->require == '1' ? ' required' : '').'" data-answer="rezgo-waiver-answer-'.$form->id.'" id="rezgo-waiver-form-'.$form->id.'" name="pax_group['.$pax_data->type.']['.$pax_data->num.'][forms]['.$form->id.']" cols="40" rows="4">'.$form->answer.'</textarea>
							<p class="rezgo-form-comment"><span>'.$form->instructions.'</span></p>
						</div>
					';
					
				}
				
				if($form->type == 'checkbox') {
					
					$waiver_forms .= '
						<div class="form-group rezgo-custom-form rezgo-form-input">
							<div class="checkbox rezgo-form-checkbox">
								<label>
									<input type="checkbox" class="custom-form-input'.((string) $form->require == '1' ? ' required' : '').'" data-answer="rezgo-waiver-answer-'.$form->id.'" id="rezgo-waiver-form-'.$form->id.'" name="pax_group['.$pax_data->type.']['.$pax_data->num.'][forms]['.$form->id.']" '.(((string) $form->answer == 'on' || (string) $form->answer == "1") ? ' value =1 checked' : '').' />
									<span>'.$form->title.'</span>
									 '.((string) $form->require == '1' ? '<em class="fa fa-asterisk"></em>' : '').'
									<p class="rezgo-form-comment"><span>'.$form->instructions.'</span></p>
								</label>
							</div>
						</div>
					';
					
				}
				
			} // foreach ( $pax_data->forms->form )

			$custom_fields .= '</table>';
			$waiver_forms .= '<div id="rezgo-waiver-notes"><span></span></div>';
			
			$response = $waiver_forms . '|||' . $custom_fields;
			
		} else {
			
			$response = '';
			
		}
		
	} // get_forms

	if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
		// ajax response if we requested this page correctly
		echo $response;
	} else {
		// if, for some reason, the ajax form submit failed, then we want to handle the user anyway
		die ('Something went wrong during saving the waiver.');
	}
	
?>