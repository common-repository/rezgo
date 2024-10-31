<?php 
	require ('header.php'); 
	if (!$booking_edit) $site->sendTo('/edit/'.$raw_trans_num); 
?>

	<div id="edit_forms" class="panel-collapse">
		<div class="panel-body edit-booking-panel edit-booking-guest-info">
			<form id="update_primary_form" action="">

				<?php require (REZGO_DOCUMENT_ROOT.$site->path.'/booking_forms_primary.php'); ?>

				<div id="rezgo_primary_forms_errors" class="rezgo-booking-edit-errors" style="display:none;">
					<span>Some required fields are missing. Please complete the highlighted fields.</span>
				</div>

				<div class="edit-form-controls">
					<button id="save_primary_form" type="submit" onclick="updatePrimaryForm(); return false;">
						<span id="save_primary_form_wording">
							<i class="far fa-check"></i>&nbsp; Save Changes
						</span>
					</button>
					<a href="<?php echo $site->base; ?>/edit/<?php echo $raw_trans_num; ?>" class="underline-link reset_edit_view"><i class="far fa-angle-left"></i><span>Back</span></a>
				</div>

			</form>

			<script>

			jQuery(function($){

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

				updatePrimaryForm = function(){

					let validate_check = $('#update_primary_form').valid();
					if (!validate_check) {
						$('#rezgo_primary_forms_errors').fadeIn();
						setTimeout(function () {
							$('#rezgo_primary_forms_errors').fadeOut();
						}, 5000);

						return false;
					} else {

						$('#save_primary_form_wording').html('<i class="fal fa-pencil"></i>&nbsp; Edit');
						$('#save_primary_form_wording').html('<i class="fal fa-circle-notch fa-spin"></i>&nbsp; Saving...');

						setTimeout(() => {
							// submit form
							$('#update_primary_form').ajaxSubmit({
								type: 'POST',
								url: '<?php echo admin_url('admin-ajax.php'); ?>' + '?action=rezgo&method=booking_edit_ajax', 
								data: { 
									rezgoAction: 'update_primary_form',
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
</div>
