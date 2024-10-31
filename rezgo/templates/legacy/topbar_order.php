<?php 
	$cart = $site->getCartItems(); 
	$analytics_ga4 = $site->exists($site->getAnalyticsGa4());
	$analytics_gtm = $site->exists($site->getAnalyticsGtm());
?>

<div class="col-xs-12 top-bar-order">
		<div class="left-flex-wrap">

		<?php if ($_SERVER['SCRIPT_NAME'] == '/index.php') { ?>
			<?php
				$ref_parts = explode('/?', $_SERVER['HTTP_REFERER']);
				$promo_url = $ref_parts[0]; 
				$trigger_code = $site->cart_trigger_code;
				$promo_exists = $_COOKIE['rezgo_promo'] || $trigger_code;
			?>
			
			<div id="rezgo-tourlist-promo" class="rezgo-tourlist-promo-container <?php echo $promo_exists ? 'applied' : ''; ?>">

				<a id="rezgo-tourlist-promo-toggle" onclick="jQuery('.rezgo-tourlist-promo').slideToggle(200); jQuery(this).find('.fa-chevron-down').toggleClass('active'); return false;" style="<?php echo $promo_exists ? 'display:none;' : ''; ?>"> 
					<span>
						<i class="fad fa-tags"></i><span>&nbsp;</span>
						Promo Code<span>&nbsp;</span><i class="fas fa-chevron-down <?php echo $promo_exists ? 'active' : ''; ?>"></i>
					</span>
				</a>

				<div class="rezgo-tourlist-promo <?php echo $promo_exists ? 'applied' : ''; ?>" style="<?php echo !$promo_exists ? 'display:none;' : ''; ?>">
					<div>
						<?php if (!$promo_exists) { ?>
							<form class="form-inline" id="rezgo-promo-form" role="form">
								<div class="input-group">
									<input type="text" class="form-control" id="rezgo-promo-code" name="promo" placeholder="Enter Promo Code" value="<?php echo ($_COOKIE['rezgo_promo'] ? $_COOKIE['rezgo_promo'] : $trigger_code); ?>" required>
									<div class="input-group-btn">
										<button class="btn rezgo-btn-default" type="submit">
									<span>Apply</span>
								</button>
								</div>
							</div>

							<?php if($_SESSION['cart_status']) $cart_status =  new SimpleXMLElement($_SESSION['cart_status']);

								// cart only validates the promo code if there are items in the cart
								if (($cart_status->error_code == 9) || ($cart_status->error_code == 11)) { ?>
							<div id ="rezgo-promo-invalid" class="text-danger" style="padding-top:5px; font-size:13px;">
								<span><?php echo $cart_status->message; ?></span>
							</div>

							<script>
								// reset invalid promo error so it doesn't show on order page again
								setTimeout(() => {
									$.ajax({
										type: 'POST',
										url: '<?php echo $site->base; ?>/book_ajax.php',
										data: { rezgoAction: 'reset_cart_status'},
										success: function(data){
											// console.log('reset cart status session');
											jQuery('#rezgo-promo-code').val('');
											jQuery('#rezgo-promo-invalid').slideUp();
										},
											error: function(error){
											console.log(error);
										}
									});
								}, 3500);
							</script>
							<?php } ?>

							</form>
						<?php } else { ?>
							<div class="input-group">
								<i class="fad fa-tags"></i><span>&nbsp;</span>&nbsp;
								<label for="rezgo-promo-code">
									<span class="rezgo-promo-label">
										<span>Promo applied:</span>
									</span>
								</label>
								<span>&nbsp;</span>
									<span id="rezgo-promo-value"><?php echo ($_COOKIE['rezgo_promo']) ? $_COOKIE['rezgo_promo'] : $trigger_code ?></span>
								<span>&nbsp;</span> 
								<button id="rezgo-promo-clear" class="btn <?php echo $hidden; ?>" onclick="<?php echo LOCATION_HREF; ?>='<?php echo $promo_url; ?>?promo='" target="_parent"><i class="fa fa-times"></i></button>
							</div>

						<?php } ?>
					</div>
				</div>
			</div>

			<script>
				jQuery('#rezgo-promo-form').submit( function(e){
					e.preventDefault();

					<?php if ($analytics_ga4) { ?>
						// gtag select_promotion
						gtag("event", "select_promotion", {
							promo_code: document.querySelector('#rezgo-promo-code').value,
						});
					<?php } ?>

					<?php if ($analytics_gtm) { ?>
						// tag manager select_promotion
						dataLayer.push({ ecommerce: null });  // Clear the previous ecommerce object.
						dataLayer.push({
						event: "select_promotion",
						ecommerce: {
							items: [
							{
								coupon:String(document.querySelector('#rezgo-promo-code').value),
							}
							]
						}
						});
					<?php } ?>	
					
					<?php echo LOCATION_HREF; ?>='<?php echo $promo_url; ?>?promo=' + jQuery('#rezgo-promo-code').val();
				});

			</script>

		<?php } ?>
	</div> <!-- left-flex-wrap -->

	<div class="right-flex-wrap">
		<?php if ($site->showGiftCardPurchase()) { ?>
			<div id="rezgo-gift-link-use">
				<a class="rezgo-gift-link" href="<?php echo $site->base; ?>/gift-card" target="_parent">
					<i class="fad fa-gift fa-lg"></i><span>&nbsp;Gift Card</span>
				</a>
			</div>
		<?php } ?>

		<?php if ($cart) { ?>
			<div id="rezgo-cart-list" class="order-spacer">
				<span>
					<a href="<?php echo esc_url($site->base); ?>/order" target="_parent">
						<i class="fad fa-shopping-cart has-item"></i>
							<?php if ($cart > 0) { ?>
								<span id="rezgo-cart-badge"><?php echo esc_html($cart); ?></span>
							<?php } ?>
							<span> Cart </span>
						</a>
				</span>
			</div>
		<?php } ?>
	</div> <!-- right-flex-wrap -->
</div> <!-- top-bar-order -->