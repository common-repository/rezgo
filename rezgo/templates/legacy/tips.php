<?php 
	$type = (string)$company->tips->type; 
	$switch = (float)$company->tips->switch;

	// detect payment page
	$payment_page = $_REQUEST['mode'] == 'page_book' ? 1 : 0; 
?>

<div id="rezgo-tips" class="<?php echo $payment_page ? 'payment-page' : ''; ?>" 
							<?php echo $payment_page ? 'style='.'"'.'display:none;'.'"' : '' ; ?>
	>
	<div class="flex-row">
		<h3 id="rezgo-tips-header">
			<span>Add a Tip</span></h3>
			&nbsp;&nbsp; <i class="far fa-coins"></i>
	</div>

	<p id="rezgo-tips-desc">
		<span>Show your support by leaving a tip for <?php echo $company->company_name; ?></span>
	</p>
	
  	<span id="rezgo-tips-memo"></span>

	<div id="rezgo-tips-choices">

		<?php if ($type === 'percentfixed' && $complete_booking_total < $switch || $type === 'fixed') { ?>

			<?php foreach ($company->tips->tip_fixed->value as $fixed) { ?>
				<a class="rezgo-tips-btn set-amt" data-tip="<?php echo number_format((float)$fixed, 2); ?>">
					<span class="tip-value">	
						<?php echo '+ '	. $site->formatCurrency($fixed, $company); ?>
					</span>
				</a>
			<?php } ?>


		<?php } else if ($type === 'percent' && $complete_booking_total > 0 || $complete_booking_total >= $switch){ ?>
			
			<?php foreach ($company->tips->tip_percent->value as $percent) { 
				$tip_total = ($percent / 100) * $complete_booking_total; ?>
				<a class="rezgo-tips-btn set-amt" data-tip="<?php echo number_format($tip_total, 2); ?>">
					<span class="tip-value">
						<?php echo '+ '	. $site->formatCurrency($tip_total, $company); ?>
					</span>
					<span class="tip-percentage">
						<?php echo $percent . '%'; ?>
					</span>
				</a>
			<?php } ?>

		<?php } else { ?>
			
			<style>
				#rezgo-tips { display: none; }
			</style>

		<?php } ?>

		<?php if ($complete_booking_total == 0 && $type == 'percent' ) { ?>

			<!-- hide preset tip amount btns if total booking is 0 && percentage is chosen-->
			<style>
				#rezgo-tips .rezgo-tips-btn.set-amt { display: none; }
			</style>

		<?php } ?>

		<div class="rezgo-tips-btn custom-amt" data-tip="">
			<span class="custom-tip-toggle">
				<span>Custom Tip</span>
			</span>
			<div class="custom-tip-container" style="display:none;">
				<div class="input-container">
					<span id="custom-tip-currency-placeholder"><?php echo $company->currency_symbol; ?></span> 
					<input type="number" min="1" placeholder="0.00" step=".01" id="rezgo-custom-tip">
				</div>
			</div>
		</div>
		<a id="cancel-custom-amt" style="display:none;"><span>Cancel</span></a>

		<input id="tip" name="tip" type="hidden" value="" disabled="disabled">
	</div>

	<script>

	let slideSpeed = 250;
	let cart_total = <?php echo (float) $complete_booking_total; ?>;
	let custom_tip_valid = false;

	jQuery(document).ready(function($){	

		function calculateTips(tips, overall_total) {
			tips = Number(tips);
			let total_w_tips = cart_total + tips;
			total_w_tips = Number(parseFloat(total_w_tips).toFixed(2));

			let tips_html =  `<div class="flex-row align-center justify-end">
								<h5>Tip </h5> &nbsp;&nbsp;
								<span id="rezgo-tip" class="tip-value" rel="${tips}">
									+ ${currency + tips.formatMoney()}
								</span>
								</div>
								<div class="flex-row align-center justify-end">
								<span class="rezgo-tip-gc tip-value" style="display:none;">
									<span class="gc-tip-wording">Gift Card</span>
									<span class="gc-tip-amount"></span>
								</span>
								</div>`;
									
			$('#rezgo_table_tips').html(tips_html);
			$('#rezgo_table_tips').slideDown(slideSpeed);

			$('#rezgo_summary_tips').html(tips_html);
			$('.rezgo-summary-tips-container').slideDown(slideSpeed);

			// add to all associated booking total values
			$('#rezgo-fixed-cart #total_value, #rezgo-fixed-cart #summary_total_value, .rezgo-total-payable #total_value').attr('rel', total_w_tips);
			$('#rezgo-fixed-cart #total_value, #rezgo-fixed-cart #summary_total_value, .rezgo-total-payable #total_value, #complete_booking_total').html(`${currency + total_w_tips.formatMoney()}`);

			$('#tip').val(tips);
			$('#tip').attr('disabled', false);

			<?php if ($gateway_id === 'stripe_connect') { ?>
				updatePaymentIntent();
			<?php } elseif ($gateway_id === 'tmt') { ?>
				updateTmt(overall_total)
			<?php } ?>
		}

		resetTips = function(overall_total) {

			$('.rezgo-tips-btn').removeClass('selected');

			$('#rezgo_table_tips').slideUp(slideSpeed);
			$('#rezgo_table_tips').html('');

			$('.rezgo-summary-tips-container').slideUp(slideSpeed);
			$('#rezgo_summary_tips').html('');

			// revert back to booking total
			$('#rezgo-fixed-cart #total_value, #rezgo-fixed-cart #summary_total_value, .rezgo-total-payable #total_value').attr('rel', cart_total);
			$('#rezgo-fixed-cart #total_value, #rezgo-fixed-cart #summary_total_value, .rezgo-total-payable #total_value, #complete_booking_total').html(`${currency + cart_total.formatMoney()}`);

			$('#tip').val('');
			$('#tip').attr('disabled', true);

			<?php if ($gateway_id === 'stripe_connect') { ?>

				updatePaymentIntent();

			<?php } elseif ($gateway_id === 'tmt') { ?>

				updateTmt(overall_total);
				
			<?php } ?>
		}

		$('.rezgo-tips-btn').click(function(){
			$(this).toggleClass('selected');
			$('.rezgo-tips-btn').not(this).removeClass('selected');

			let selected = $(this).hasClass('selected');
			let custom_amt = $(this).hasClass('custom-amt');

			let tips = $(this).data('tip') ? parseFloat($(this).data('tip')) : 0;
			let total_w_tips = cart_total + tips;
			total_w_tips = Number(parseFloat(total_w_tips).toFixed(2));

			let req = $('#gift-card-number').val();

			if (custom_amt) {

				// only reset tip amount if there is no GC applied
				if (!req) {
					overall_total = cart_total;
					resetTips(overall_total);
				}
				
				$('.custom-tip-container').show();
				$('#rezgo-custom-tip').focus();

				// show cancel button
				$('#cancel-custom-amt').show();

			} else {

				$('.custom-tip-container').hide();
				$('#rezgo-custom-tip').val('');

				// disable custom tip validation
				custom_tip_valid = true;

				if (selected) {
					overall_total = total_w_tips;
					calculateTips(tips, overall_total);

					if (typeof noPaymentMethod === 'function') { 
						noPaymentMethod(overall_total);
					}

				} else {
					overall_total = cart_total;
					resetTips(overall_total);

					if (typeof noPaymentMethod === 'function') { 
						noPaymentMethod(overall_total);
					}
				}

				// hide cancel button
				$('#cancel-custom-amt').hide();

				// recalculate with GC
				if (req) {
					gcReq(req);
				}
			}
		});

		// save amount from custom tip input
		$('#rezgo-custom-tip').blur(function(){
			let tips = $(this).val();
			tips = parseFloat(tips);
			let total_w_tips = cart_total + tips;
			total_w_tips = Number(parseFloat(total_w_tips).toFixed(2));

			overall_total = total_w_tips;

			// enable custom tip validation
			if (isNaN(tips)) {
				custom_tip_valid = false;
				$('#rezgo-custom-tip').addClass('error');
			} else {
				custom_tip_valid = true;
				$('#rezgo-custom-tip').removeClass('error');
			}

			if (tips > 0 && overall_total > 0) {
				calculateTips(tips, overall_total);

				// recalculate with GC
				let req = $('#gift-card-number').val();
				if (req) {
					gcReq(req);
				}

				if (typeof noPaymentMethod === 'function') { 
					noPaymentMethod(overall_total);
				}
			}
			// keep custom amount btn selected
			$('.rezgo-tips-btn.custom-amt').addClass('selected');
		});

		// reset all values 
		$('#cancel-custom-amt').click(function(){

			$('.custom-tip-toggle').show();
			$('.rezgo-tips-btn.custom-amt').removeClass('selected');
			$('#rezgo-custom-tip').val('');

			$('.custom-tip-container').hide();

			overall_total = cart_total;
			resetTips(overall_total);

			if (overall_total == 0) {
				noPaymentMethod(overall_total);
			} 

			// hide cancel button
			$('#cancel-custom-amt').hide();

			// recalculate with GC
			let req = $('#gift-card-number').val();
			if (req) {
				gcReq(req);
			}
		});
		
		toggle_tips = function (payment_method) {

			$('#rezgo-tips').hide();

			// GC covers everything plus tip
			if (payment_method == 'no_payment_required') {
				return;
			}

			if (payment_method != 'Credit Cards') {
				$('.rezgo-tips-btn').removeClass('selected');
				$('#cancel-custom-amt').hide();

				if ($('#tip').val() != '' ) {
					overall_total = cart_total;
					resetTips(overall_total);

					$('.custom-tip-container').hide();
					$('#rezgo-custom-tip').val('');

					$('#tip').val('');	
					$('#tip').attr('disabled', true);

					// recalculate with GC
					let req = $('#gift-card-number').val();
					if (req) {
						gcReq(req);
						
					}
				}
				
			} else {
				$('#rezgo-tips').show();
			}
		}
	});
	</script>
</div>