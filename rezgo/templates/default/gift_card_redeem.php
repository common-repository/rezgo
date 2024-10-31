<div id="rezgo-gift-card-redeem" class="clearfix">
	<h3 id="rezgo-gift-card-redeem-header">
		<span>Apply a Gift Card &nbsp;<i class="far fa-gift"></i></span>
	</h3>

  	<span id="rezgo-gift-card-memo"></span>

	<div class="input-group">
		 <input type="text" class="form-control" id="gift-card-number" name="gift_card_number" placeholder="Enter Gift Card Number" />
		 <span class="input-group-btn">
			<span class="btn-check"></span>
			<button id="gift-card-btn" class="btn btn-primary rezgo-btn-default" type="button"><span>Apply</span></button>
		 </span>
	</div>
	
		<div class="gift-card-empty-warning" style="display:none">
			<span>Please enter your gift card number</span>
		</div>

	<div class="response">
		<div class="alert alert-info" style="display:none">
			<div class="row">
				<div class="col-12 text-center">
					<span>You have <strong class="cur"><span class="gift-card-amount"></span></strong> available on this gift card. Do you want to use it to purchase this booking?</span>
				</div>
			</div>

			<div class="row alert-info-nav">
				<div class="col-xs-6">
					<button id="-redeem-cancel-btn" type="button" class="btn btn-lg btn-block rezgo-btn-default"><span>Cancel</span></button>
				</div>
				<div class="col-xs-6">
					<button id="-redeem-confirm-btn" type="button" class="btn btn-lg btn-block rezgo-btn-book"><span>Use</span></button>
				</div>
			</div>
		</div>
		<div class="alert alert-danger" style="display:none">
			<div class="row">
				<div class="col-12">
					<span class="msg"></span>
				</div>
			</div>
		</div>
		<div class="alert alert-success" style="display:none">
			<div class="row">
				<div class="col-12">
					<span class="msg"></span> <a class="rezgo-redeem-reset-btn">remove</a>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
jQuery(document).ready(function($) { 

	<?php if (REZGO_WORDPRESS) { ?>

		// need to be redefined here for it to work with WP
		let payment_count = $("input[name='payment_method']").length;

		function hideSelectPayment(){
			$('.select-payment').hide();
			$('#rezgo-gift-card-use-hr').css({
				'margin' : '0px 0 35px',
				'border-color' : 'transparent'
			});
		}
		function showSelectPayment(){
			$('.select-payment').show();
			$('#rezgo-gift-card-use-hr').css({
				'margin' : '50px 0px 35px',
				'border-color' : '#eee'
			});
		}

	<?php } ?>

	var today = parseInt('<?php echo strtotime("today");?>');
	var debug = 0; // turn this off
	var $gcApp = $("#rezgo-gift-card-redeem");
	var gcData;
	var gcCur = decodeURIComponent( '<?php echo rawurlencode( (string) $company->currency_symbol ); ?>' );

	/*if (debug) {
		$('pre.copy').click(function(){
			var html = $(this).html();
			$('#gift-card-number').val(html);
		});
	}*/

	gcReq = function (req) {
		if (!req) {
			$gcApp.find('.alert').hide();
			return;
		}

		// UPDATE EACH ITEM SECTION
		$('.rezgo-gc-box').hide();

		// UPDATE GC SECTION
		$gcApp.find('.alert').hide();

		$.ajax({
			url: "<?php echo admin_url('admin-ajax.php'); ?>" + '?action=rezgo&method=gift_card_ajax',
			type: 'POST',
			data: {
				rezgoAction: 'getGiftCard',
				gcNum: req
			},
			success: function (data) {
				var json, success, error, msg;

				json = data.split("|||");
				json = json.slice(-1)[0];
				gcData = JSON.parse(json);
				success = parseFloat(gcData.status);
				error = 0;

				/*if (debug) {
					console.log(gcData);
				}*/

				// GIFT CARD BALANCE IS 0
				if (success && !parseFloat(gcData.amount) > 0) {
					error = 1;
					msg = 'Gift Card  <strong class="gift-card-number">' + req + '</strong> has no funds available.';
				}

				// GIFT CARD NOT FOUND
				if (!success) {
					error = 1;
					msg = 'Gift Card <strong class="gift-card-number">' + req + '</strong> not found.';
				}

				// RESULT
				if (error) {
					$gcApp.find('#gift-card-number').val('');
					$gcApp.find('.alert-danger .msg').html(msg);
					$gcApp.find('.alert-danger').show();
				}
				else {
					gcRedeem(gcData);
				}
			},
			error: function () {
				var msg = 'Connection error. Please try again or contact <?php echo esc_html(addslashes($company->company_name)); ?> for customer support.';
				$gcApp.find('.alert-danger .msg').html(msg);
				$gcApp.find('.alert-danger').show();
			}
		});
	}
	function gcRedeem(req) {
		var exp = parseInt(req.expires);
		
		/*if (debug) {
			console.log('GC expiry: ' + exp);
			console.log('today: ' + today);
		}*/

		// If GC is not expired
		if (!exp || (exp && exp >= today)) {
			var max = parseInt (req.max_uses);
			var use = parseInt (req.uses);

			/*if (debug) {
				console.log('GC max_uses: ' + max);
				console.log('GC uses: ' + use);
			}*/

			// If GC max is set to never OR is set but not reached
			if (!max || (max && max > use)) {
				var total = $('#total_value').attr('rel');
				var t = parseFloat(total);
				var b = parseFloat(req.amount); // GC balance
				var c = 0; // GC charge
				var r = 0;
				var applied = 0; // count GC item application
				let package_gc_uid = new Array();
				let package_gc_amounts = new Array();
				
				let $tip = $('#rezgo-tip').length ? $('#rezgo-tip') : '';
				let tip = $('#rezgo-tip').length ? parseFloat($tip.attr('rel')) : 0; // tip value

				// RESTRICTED CARD INFO
				if (typeof req.items === 'string') {
					r = req.items.replace(/ /g,"").split(',');

					if (debug) {
						console.log('Restricted card: ');
						console.log(r);
						console.log(req.items);
					}
				}

				// UPDATE EACH ITEM SECTION
				$('.rezgo-billing-cart').each(function(){	
					var $t = $(this);
					var id = $t.attr('id');
					var $r = $t.find('.rezgo-gc-box');
					var $i = $t.find('.rezgo-item-total');
					var $d = $t.find('.rezgo-item-deposit');
					var i = parseFloat($i.attr('rel')); // item value

					let book_id = $t.data('book-id');
					const original_total =  parseFloat($i.attr('rel')); // item value as a constant
					let sum_row = $('#summary_price_' + book_id);
					let deposit_words = sum_row.closest('.price-container').find('.deposit');

					if ($d.length) {
						var	d = parseFloat($d.attr('rel')); // deposit value
					}

					if (debug) {
						console.log('-------------------------------');
						console.log('balance: '+b+' '+typeof b);
						console.log('charge: '+c+' '+typeof c);
						console.log('item TOTAL: '+i+' '+typeof i);
						if ($d.length) {
							console.log('item DEPOSIT: '+d+' '+typeof d);
						}
						console.log('over TOTAL: '+t+' '+typeof b);
					}

					if ( b > 0 ){
						applied++;
					}

					function recalculate(mode) {
						if (mode === 'd') {
							if (debug) {
								console.log('gc deposit mode..');
							}

							var real_overall_total = t + i - d;
						}

						if (b > 0 && t > 0 && i > 0) {
							if (debug) {
								console.log('GC uses::'+use);
							}

							// CHECK USE < MAX IF MAX
							if (!max || (max && max > use)) {
								if (b >= i) {
									if (mode == 'd') {
										t = real_overall_total;

										$d.parents('tr').hide();

										if (debug) {
											console.log('over TOTAL = ' + t)
											console.log('gc balance >= item value');
											console.log('ignore deposit..');
										}
									}

									i = parseFloat(i.toFixed(2));
									$t.find('.rezgo-gc-min').html(i.formatMoney());

									sum_row.empty();
									deposit_words.hide();
									sum_row.prepend('<span class="gc-deposit">' + currency + i.formatMoney() + '</span>');
									sum_row.append( '<span class="gc-append">' + ' - ' + currency + i.formatMoney() + ' <span class="gc-append-text"> (Gift Card)</span></span>' );

									b = b-i;
									t = t-i;
									c = c+i;
									i = 0;
								}

								else {
									b = parseFloat(b.toFixed(2));
									$t.find('.rezgo-gc-min').html(b.formatMoney());

									i = i-b;
									c = c+b;	

									// item value is 80
									// if gc is $50, the remaining be 30, and order total be 25 for deposit to pay. (and show deposit line)
									// if gc is $70, the remaining be 10, and order total be 10 to pay. (and hide deposit line)

									if (mode === 'd') {

										sum_row.empty();
										sum_row.prepend('<span class="gc-deposit">' + currency + d.formatMoney() + '</span>');

										if ((i >= d) == 0) {

											t = real_overall_total;
											t = t-b;
											$d.parents('tr').hide();

											sum_row.empty();
											deposit_words.hide();
											sum_row.prepend('<span class="gc-deposit">' + currency + original_total.formatMoney() + '</span>');
											sum_row.append( '<span class="gc-append">' + ' - ' + currency + b.formatMoney() + ' <span class="gc-append-text"> (Gift Card)</span></span>' );
											
										} 
									}

									else {
										t = t-b;

										sum_row.empty();
										sum_row.prepend('<span class="gc-deposit">' + currency + original_total.formatMoney() + '</span>');
										sum_row.append( '<span class="gc-append">' + ' - ' + currency + b.formatMoney() + ' <span class="gc-append-text"> (Gift Card)</span></span>' );
									}

									b = 0;
								}

								if (t < 0) {
									t = 0;
								}

								t = parseFloat (t.toFixed(2));
								i = parseFloat (i.toFixed(2));
								b = parseFloat (b.toFixed(2));
								c = parseFloat (c.toFixed(2));

								// $i.html(gcCur+i.formatMoney());
								$r.find('.cur').html(gcCur);
								$r.show();

								// INCREMENT USE COUNT
								// use++;
							}
						}
					}

					if (debug) {
						console.log('r: '+r);
						console.log('id: '+id);
					}

					// If no restriction OR item is a restricted item
					if (!r || (r && $.inArray(id,r) >= 0)) {
						if (debug) {
							console.log('-- RECALCULATE --');
						}

						// DEPOSIT
						if ($d.length) {
							recalculate('d');
						}

						// NO DEPOSIT
						else {
							recalculate('i');
						}
					}

					if (debug) {
						console.log('--RESULT--');
						console.log('balance: '+b+' '+typeof b);
						console.log('charge: '+c+' '+typeof c);
						console.log('item TOTAL: '+i+' '+typeof i);
						if ($d.length) {
							console.log('item DEPOSIT: '+d+' '+typeof d);
						}
						console.log('over TOTAL: '+t+' '+typeof b);
						console.log('-------------------------------');
					}
				});

			// look for how many GC is applied
			$('.rezgo-billing-cart').each(function(){

				$t = $(this);
				let book_id = $t.data('book-id');
				let sum_row = $('#summary_price_' + book_id);
				let deposit_words = $('#deposit_words_'+book_id);

				let d = $('#rezgo_package_deposit_'+$t.data('cart-package-uid'));
				let d_val = d.attr('rel');

				let t = $('#rezgo_package_total_'+$t.data('cart-package-uid'));
				let t_val = t.attr('rel');

				// use book_id if cart_package_uid(packages) are not applicable
				let cart_package_uid = ($(this).data('cart-package-uid') !== undefined) ? $t.data('cart-package-uid') : book_id;

				let amount = parseFloat($(this).find('.rezgo-gc-min').text().replace(/,/g,""));

				// if ( (cart_package_uid !== undefined) && (amount > 0) ) {
				if (amount > 0) {
					package_gc_uid.push(parseInt(cart_package_uid));
					package_gc_amounts.push(cart_package_uid + ':::' + amount.formatMoney());
				}
			});
			
			// empty out previous GC item display
			$('.package-gc-line').remove();

			for (let i = 0; i < package_gc_uid.length; i++) {

				let e = package_gc_amounts[i].split(':::');
				
				if (package_gc_uid[i] === parseInt(e[0])) {
					// console.log($('.package-gc-line-'+e[0]));

					let append = '<tr class="package-gc-line package-gc-line-'+e[0]+'"><td colspan="3" class="text-end"><span class="push-right"><strong>Gift Card</strong></span></td><td class="text-end"><strong><span>- </span><span class="rezgo-gc-min">'+currency + e[1]+'</strong></span></td></tr>';

					$('.append-package-gc-'+e[0]).append(append);
					$('.append-package-gc-'+e[0]).show();

				}
			}
			// empty arrays for later use if needed
			package_gc_uid = [];
			package_gc_amounts = [];

				var cartIds = new Array();
				$('.rezgo-billing-cart').each(function(){
					var $t = $(this);
					var id = $t.attr('id');
					cartIds.push(id);

					let package_tr_deposit = $('.rezgo-tr-package-deposit_'+$t.data('cart-package-uid'));

					// if any GC is applied to this package
					if ($('.append-package-gc-'+$t.data('cart-package-uid')).css('display') != 'none') {
						package_tr_deposit.hide();
					}

				});

				// check there is GC restriction 
				if (r){

					// check GC inventory restriction against cart
					var matchedCart = findMatch(r, cartIds);
					function findMatch(a1, a2) { 
						for(i = 0; i < a1.length; i++) { 
							for(j = 0; j < a2.length; j++) { 
								if(a1[i] === a2[j]) { 
									return true; 
								}
							}
						} 
						return false;
					} 
					// console.log('is the cart matched? ' + matchedCart);

					// there is a match from the restriction check
					if (matchedCart) {

						// count how many bookings that matched 
						var matchedIds = new Array();
						for(i = 0; i < r.length; i++) { 
							for(j = 0; j < cartIds.length; j++) { 
								if(r[i] === cartIds[j]) { 
									matchedIds.push(r[i]);
								}
							}
						}
						// console.log(matchedIds);

						// ------ TIP calculations
						// only charge if balance is more than tip amount
						if (b > tip) {
							b = b - tip;
							t = t - tip;
							c = c + tip;

							// show tip calc
							$('.rezgo-tip-gc').show();
							$('.gc-tip-amount').html('- ' + gcCur + tip.formatMoney());

							if (debug) {
								console.log('-- CALCULATING TIP --');
								console.log('tip: '+tip+' '+typeof tip);
								console.log('balance: '+b+' '+typeof b);
								console.log('charge: '+c+' '+typeof c);
								console.log('over TOTAL: '+t+' '+typeof t);
							}
						} else {
								// total - GC balance
								t = t - b;
								c = c + tip;

								// only show if GC balance is over 0
								if (b > 0) {
									// show tip calc
									$('.rezgo-tip-gc').show();
									// show whatever balance is left to the user
									$('.gc-tip-amount').html('- ' + gcCur + b.formatMoney());
								}

							if (debug) {
								console.log('-- INSUFFICIENT BALANCE FOR TIP --');
								console.log('tip: '+tip+' '+typeof tip);
								console.log('balance: '+b+' '+typeof b);
								console.log('charge: '+c+' '+typeof c);
								console.log('over TOTAL: '+t+' '+typeof t);
							}
						}
						// ------ TIP calculations

						if (cartIds.length > 1){
							var msg = '<span>Gift Card <strong class="gift-card-number">'+ gcData.number +'</strong> was applied to '+ matchedIds.length + ' of ' + cartIds.length + ' bookings.';
						} else {
							var msg = '<span>Gift Card <strong class="gift-card-number">'+ gcData.number +'</strong> applied.';
						}

						$('#rezgo-gift-card-redeem-header').hide();
						$gcApp.find('.input-group').hide();
						$gcApp.find('.alert').hide();
						$gcApp.find('.alert-success .msg').html(msg);
						$gcApp.find('.alert-success').show();	

						// UPDATE OVERALL TOTAL
						$('#total_value').html(gcCur + t.formatMoney());
						$('#total_value').attr('rel', t);

						// update summary total 
						$('#rezgo-fixed-cart #total_value').html(gcCur + t.formatMoney());
						$('#rezgo-fixed-cart #total_value').attr('rel', t);

						// update summary total for mobile 
						$('#rezgo-fixed-cart #summary_total_value').html(gcCur + t.formatMoney());
						$('#rezgo-fixed-cart #summary_total_value').attr('rel', t);

						// prevent 0 amount
						expected_t = (t - tip).toFixed(2) > 0 ? (t - tip).toFixed(2) : 0;
						$('input[name="expected"]').val(expected_t);
						overall_total = t;

						$('#complete_booking_total').html(t > 0 ? gcCur + t.formatMoney() : '');

						if (overall_total > 0) {
							<?php if ($using_paypal_checkout) { ?>
								updatePaypalCheckout(overall_total);
							<?php } ?>
						}

						// update hidden input
						$('input[name="gift_card"]').val(gcData.number);

						// SHOW/HIDE PAYMENT INFO
						gcUpdatePaymentSection(parseFloat(t));

						// SCROLL TOP
						top.window.scrollTo(0,0);
								
					}
					else if (!matchedCart) {
						var msg = '<span>Gift Card <strong class="gift-card-number">'+ gcData.number +'</strong> is not valid for the items in your order.';
					
						$gcApp.find('#gift-card-number').val('');
						$gcApp.find('.alert').hide();
						$gcApp.find('.alert-danger .msg').html(msg);
						$gcApp.find('.alert-danger').show();		
					}
				} 
				// GC is not restricted 
				else if (r == 0){

					// console.log('its a free card');

					// ------ TIP calculations
					// only charge if balance is more than tip amount
					if (b > tip) {
						b = b - tip;
						t = t - tip;
						c = c + tip;

						console.log(t);

						// show tip calc
						$('.rezgo-tip-gc').show();
						$('.gc-tip-amount').html('- ' + gcCur + tip.formatMoney());

						if (debug) {
							console.log('-- CALCULATING TIP --');
							console.log('tip: '+tip+' '+typeof tip);
							console.log('balance: '+b+' '+typeof b);
							console.log('charge: '+c+' '+typeof c);
							console.log('over TOTAL: '+t+' '+typeof t);
						}
					} else {
							// total - GC balance
							t = t - b;
							c = c + tip;

							// only show if GC balance is over 0
							if (b > 0) {
								// show tip calc
								$('.rezgo-tip-gc').show();
								// show whatever balance is left to the user
								$('.gc-tip-amount').html('- ' + gcCur + b.formatMoney());
							}

						if (debug) {
							console.log('-- INSUFFICIENT BALANCE FOR TIP --');
							console.log('tip: '+tip+' '+typeof tip);
							console.log('balance: '+b+' '+typeof b);
							console.log('charge: '+c+' '+typeof c);
							console.log('over TOTAL: '+t+' '+typeof t);
						}
					}
					// ------ TIP calculations

					if (cartIds.length > 1){
						var msg = '<span>Gift Card <strong class="gift-card-number">'+ gcData.number +'</strong> was applied to '+ applied + ' of ' + cartIds.length + ' bookings.';
					}
					else {
						var msg = '<span>Gift Card <strong class="gift-card-number">'+ gcData.number +'</strong> applied.';
					}
					$('#rezgo-gift-card-redeem-header').hide();
					$gcApp.find('.input-group').hide();
					$gcApp.find('.alert').hide();
					$gcApp.find('.alert-success .msg').html(msg);
					$gcApp.find('.alert-success').show();		

					// UPDATE OVERALL TOTAL
					$('#total_value').html(gcCur + t.formatMoney());
					$('#total_value').attr('rel', t);

					// update summary total
					$('#rezgo-fixed-cart #total_value').html(gcCur + t.formatMoney());
					$('#rezgo-fixed-cart #total_value').attr('rel', t);

					// update summary total for mobile 
					$('#rezgo-fixed-cart #summary_total_value').html(gcCur + t.formatMoney());
					$('#rezgo-fixed-cart #summary_total_value').attr('rel', t);

					// prevent 0 amount
					expected_t = (t - tip).toFixed(2) > 0 ? (t - tip).toFixed(2) : 0;
					$('input[name="expected"]').val(expected_t);
					overall_total = t;

					$('#complete_booking_total').html(t > 0 ? gcCur + t.formatMoney() : '');

					if (overall_total > 0) {
						<?php if ($using_paypal_checkout) { ?>
							updatePaypalCheckout(overall_total);
						<?php } ?>
					}

					t = parseFloat(t.toFixed(2));

					// update hidden input
					$('input[name="gift_card"]').val(gcData.number);

					// SHOW/HIDE PAYMENT INFO
					gcUpdatePaymentSection(parseFloat(t));	
					
					// SCROLL TOP
					top.window.scrollTo(0,0);
				}
				
			}
			else {
				// UPDATE GC SECTION
				// $gcApp.find('.input-group').hide();
				$gcApp.find('#gift-card-number').val('');
				$gcApp.find('.alert').hide();
				var msg = 'Gift Card <strong class="gift-card-number">'+ gcData.number +'</strong> can\'t be applied because it does not have enough uses remaining.';
				$gcApp.find('.alert-danger .msg').html(msg);
				$gcApp.find('.alert-danger').show();
			}
		}
		else {
			// UPDATE GC SECTION
			// $gcApp.find('.input-group').hide();
			$gcApp.find('#gift-card-number').val('');
			$gcApp.find('.alert').hide();
			var msg = 'Gift Card <strong class="gift-card-number">'+ gcData.number +'</strong> can\'t be applied because the card has expired.';
			$gcApp.find('.alert-danger .msg').html(msg);
			$gcApp.find('.alert-danger').show();
		}

		// INCREMENT USE COUNT
		use++;
	}
	function gcUpdatePaymentSection(t) {
		if (t > 0) {
			$('.terms_credit_card_over_zero').show();

			$('.rezgo-input-radio').not('no-payment').show();
			$('.rezgo-input-radio.no-payment').hide();
			
			// no_payment_method is counted towards 'payment_count'
			if ( (payment_count === 2) && (overall_total > 0) ){
				$("input[name='payment_method']").eq(0).prop("checked", true);
				hideSelectPayment();
				toggleCard();
			}

		}
		else {
			$('.terms_credit_card_over_zero').hide();
			
			$('.rezgo-input-radio').not('no-payment').hide();
			$('.rezgo-input-radio.no-payment').show();
			
			$("input#no_payment_required").prop("checked", true);
			$('#payment_method-error').hide();
			hideSelectPayment();
			toggleCard();

		}
		
		$('#rezgo-gift-card-redeem-header').hide();

	}
	gcReset = function() {
		// TOTAL RESET
		var $t = $('#total_value');

		var t = parseFloat($t.attr('rel'));
		$t.html(gcCur + t.formatMoney());

		// overall_total = t;
		// reset total so we can add the original amount 
		overall_total = 0;
		let total_w_tip = 0;

		let $tip = $('#rezgo-tip').length ? $('#rezgo-tip') : '';
		let tip = $('#rezgo-tip').length ? parseFloat($tip.attr('rel')) : 0; // tip value

		// ITEM RESET
		$('.rezgo-gc-box').hide();
		$('.rezgo-gc-box').find('.rezgo-gc-min').empty();
		$('.rezgo-billing-cart').each(function(){
			var $i = $(this).find('.rezgo-item-total');
			var $d = $(this).find('.rezgo-item-deposit');
			var	i = parseFloat($i.attr('rel')); // item value
			let book_id = $(this).data('book-id');
			let sum_row = $('#summary_price_' + book_id);
			let deposit_words = sum_row.closest('.price-container').find('.deposit');
			let package_tr_deposit = $('.rezgo-tr-package-deposit_'+$(this).data('cart-package-uid'));

			if ($d.length) {
				var	d = parseFloat($d.attr('rel')); // deposit value
				// console.log('what is d ' + d);
				total(d);
			}
			else {
				// console.log('what is i ' + i);
				total(i)
			}
				
			$i.html(gcCur + i.formatMoney());
			$('#summary_price_' + book_id).find('.gc-append').remove();

			if ($d.length) {
				$d.parents('tr').show();

				deposit_words.show();
				sum_row.find('.gc-deposit').remove();
				sum_row.html(currency + d.formatMoney())
			}

			if (package_tr_deposit.length) {
				package_tr_deposit.show();
			}

		});

		// add total and deposits if applicable
		function total(num){
			overall_total = overall_total + num;
		}

		// console.log('overall_total now is: ' + overall_total);
		
		overall_total = parseFloat(overall_total.toFixed(2));

		total_w_tip = overall_total + tip;
		total_w_tip = parseFloat(total_w_tip.toFixed(2));

		// console.log('total_w_tip now is: ' + total_w_tip);
		
		// HIDDEN INPUT
		$('input[name="expected"]').val(overall_total);
		$('input[name="gift_card_number"]').val('');
		
		// reset hidden input
		$('input[name="gift_card"]').val('');

		// UPDATE OVERALL TOTAL AGAIN
		$('#total_value').html(gcCur + total_w_tip.formatMoney());
		$('#total_value').attr('rel', total_w_tip);

		// update summary total
		$('#rezgo-fixed-cart #total_value').html(gcCur + total_w_tip.formatMoney());
		$('#rezgo-fixed-cart #total_value').attr('rel', total_w_tip);

		// update summary total for mobile 
		$('#rezgo-fixed-cart #summary_total_value').html(gcCur + total_w_tip.formatMoney());
		$('#rezgo-fixed-cart #summary_total_value').attr('rel', total_w_tip);

		$('#rezgo-complete-payment').html('Complete Booking of <span id="complete_booking_total">'+gcCur + total_w_tip.formatMoney()+'</span>');

	<?php if ($using_paypal_checkout) { ?>
		updatePaypalCheckout(overall_total);
	<?php } ?>

		// GIFTCARD SECTION
		$gcApp.find('.alert').hide();
		$gcApp.find('.input-group').show();

		if ( (payment_count === 2) && (overall_total > 0) ){
			$("input[name='payment_method']").eq(0).prop("checked", true);
			hideSelectPayment();
		} else {
			showSelectPayment();
		}

		// SHOW/HIDE PAYMENT INFO
		gcUpdatePaymentSection(parseFloat(overall_total));	

		<?php if ($tg_enabled) { ?>
			// re-enable TG if applicable
			toggle_tg('Credit Cards');
		<?php } ?>
		
		<?php if ($tips_enabled) { ?>
			$('.rezgo-tip-gc').hide();
			if (total_w_tip > 0) {
				noPaymentMethod(total_w_tip);
			}
			resetTips(overall_total);
		<?php } ?>

		// empty appended content 
		$('.append-package-gc').empty();
		$('.append-package-gc').hide();

		$('.summary-append-package-gc').empty();
	}

	$("#gift-card-btn").click(function(){
		var req = $('#gift-card-number').val()

		if( $('#gift-card-number').val() == '' ){
			$('.gift-card-empty-warning').show();
			$('#gift-card-number').addClass('has-error');
		}

		gcReq(req);
	});

	$('#gift-card-number').change( function(){
		if( $(this).val() != '' ){
			$('.gift-card-empty-warning').hide();
			$(this).removeClass('has-error');
		}
	});

	$(document).on('click','.rezgo-redeem-reset-btn',function(){
		gcReset();

		$('#rezgo-gift-card-redeem-header').show();
		top.window.scrollTo(0,0);
	});

});
</script>
