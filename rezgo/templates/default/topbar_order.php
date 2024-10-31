<?php 
	$cart = $site->getCart(); 
	$cart_status = '';
	$cart_total = 0;
	$analytics_ga4 = $site->exists($site->getAnalyticsGa4());
	$analytics_gtm = $site->exists($site->getAnalyticsGtm());
	$meta_pixel = $site->exists($site->getMetaPixel());

	if ($cart) {
		foreach ($cart as $item) {
			$currency_base = (string)$item->currency_base;
			$cart_total += (float)$item->overall_total;
		}
	}
?>

<div class="col-12 top-bar-order">
		<div class="left-flex-wrap">
		<?php if ($_REQUEST['mode'] == 'index') { ?>
			<?php
				$ref_parts = explode('/?', $_SERVER['HTTP_REFERER'] ?? '');
				$promo_url = $ref_parts[0]; 
				$trigger_code = $site->cart_trigger_code;
				$promo_exists = isset($_COOKIE['rezgo_promo']) || $trigger_code;
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
								<input type="text" class="form-control" id="rezgo-promo-code" name="promo" placeholder="Enter Promo Code" value="<?php echo (isset($_COOKIE['rezgo_promo']) ? $_COOKIE['rezgo_promo'] : $trigger_code); ?>" required>
								<div class="input-group-btn">
									<span class="btn-check"></span>
									<button class="btn rezgo-btn-default" type="submit">
										<span>Apply</span>
									</button>
								</div>

							<?php 
							if(isset($_COOKIE['cart_status'])) {
								$cart_status = new SimpleXMLElement($_COOKIE['cart_status']);

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
							<?php } ?>

							</form>
						<?php } else { ?>
							<div>
								<i class="fad fa-tags"></i><span>&nbsp;</span>&nbsp;
								<label for="rezgo-promo-code">
									<span class="rezgo-promo-label">
										<span>Promo applied:</span>
									</span>
								</label>
								<span>&nbsp;</span>
									<span id="rezgo-promo-value"><?php echo (isset($_COOKIE['rezgo_promo'])) ? $_COOKIE['rezgo_promo'] : $trigger_code ?></span>
								<span>&nbsp;</span> 

								<?php if (REZGO_LITE_CONTAINER) { ?>
									<button id="rezgo-promo-clear" class="btn"
									onclick="<?php echo LOCATION_WINDOW; ?>.location.href='<?php echo $site->base; ?><?php echo $promo_url; ?>/?promo='; return false;"><i class="fa fa-times"></i></button>
										<?php } else { ?>
									<button id="rezgo-promo-clear" class="btn" onclick="<?php echo LOCATION_HREF; ?>='<?php echo $promo_url; ?>?promo='" target="_parent"><i class="fa fa-times"></i></button>
								<?php } ?>  

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
					
					<?php if ($meta_pixel) { ?>
						// meta_pixel custom event SelectPromotion
						fbq('trackCustom', 'SelectPromotion', { 
								promo_code: document.querySelector('#rezgo-promo-code').value,
							}
						)
					<?php } ?>	
					
					<?php if (REZGO_LITE_CONTAINER) { ?>
						<?php echo LOCATION_WINDOW; ?>.location.href='<?php echo $site->base; ?><?php echo $promo_url; ?>?promo=' + jQuery('#rezgo-promo-code').val();
					<?php } else { ?>
						<?php echo LOCATION_HREF; ?>='<?php echo $promo_url; ?>?promo=' + jQuery('#rezgo-promo-code').val();
					<?php } ?>
				});
				
				<?php if (REZGO_LITE_CONTAINER) { ?>
					$('#rezgo-promo-clear').click(function(){
						$.ajax({
							type: 'POST',
							url: '<?php echo $site->base; ?>/book_ajax.php',
							data: { rezgoAction: 'update_promo' },
							success: function(data){
							}
						})	
					});
				<?php } ?>
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
					<a id="rezgo-cart-button" href="<?php echo $site->base; ?>/order" target="_parent">
						<i class="fad fa-shopping-cart has-item"></i>
						<?php if (count($cart) > 0) { ?>
							<span id="rezgo-cart-badge"><?php echo esc_html(count($cart)); ?></span>
							<?php } ?>
							<span> Cart </span>
						</a>
				</span>
			</div>

			<script>
			jQuery(function($){
				$('#rezgo-cart-button').click(function(e){
					e.preventDefault();
					<?php if ($analytics_ga4) { ?>
						// gtag view_cart
						gtag("event", "view_cart", {
							currency: "<?php echo esc_html($currency_base); ?>",
							value: "<?php echo $cart_total; ?>",
							coupon: "<?php echo isset($_COOKIE['rezgo_promo']) ? esc_html($_COOKIE['rezgo_promo']) : esc_html($site->cart_trigger_code); ?>",
							items: [
							<?php $tag_index = 1;
								foreach ($cart as $item){ ?>
							{
								item_id: "<?php echo esc_html($item->uid); ?>",
								item_name: "<?php echo esc_html($item->item . ' - ' . $item->option); ?>",
								currency: "<?php echo esc_html($currency_base); ?>",
								coupon: "<?php echo isset($_COOKIE['rezgo_promo']) ? esc_html($_COOKIE['rezgo_promo']) : esc_html($site->cart_trigger_code); ?>",
								price: <?php echo esc_html((float)$item->overall_total); ?>,
								quantity: 1,
								index: <?php echo esc_html($tag_index++); ?>,
							},
							<?php } unset($tag_index); ?>
							]
						});
					<?php } ?>

					<?php if ($analytics_gtm) { ?>				
						// tag manager view_cart
						dataLayer.push({ ecommerce: null });  // Clear the previous ecommerce object.
						dataLayer.push({
						event: "view_cart",
						ecommerce: {
							items: [
							<?php $tag_index = 1;
								foreach ($cart as $item){ ?>
							{
								item_id: "<?php echo esc_html($item->uid); ?>",
								item_name: "<?php echo esc_html($item->item . ' - ' . $item->option); ?>",
								currency: "<?php echo esc_html($currency_base); ?>",
								coupon: "<?php echo isset($_COOKIE['rezgo_promo']) ? esc_html($_COOKIE['rezgo_promo']) : esc_html($site->cart_trigger_code); ?>",
								price: <?php echo esc_html((float)$item->overall_total); ?>,
								quantity: 1,
								index: <?php echo esc_html($tag_index++); ?>,
							},
							<?php } unset($tag_index); ?>
							]
						}
						});
					<?php } ?>

					<?php if ($meta_pixel) { ?>
						// meta_pixel custom event ViewCart
						fbq('trackCustom', 'ViewCart', { 
							currency: "<?php echo esc_html($currency_base); ?>",
							value: "<?php echo $cart_total; ?>",
							coupon: "<?php echo isset($_COOKIE['rezgo_promo']) ? esc_html($_COOKIE['rezgo_promo']) : esc_html($site->cart_trigger_code); ?>",
							items: [
							<?php $tag_index = 1;
								foreach ($cart as $item){ ?>
								{
									item_id: "<?php echo esc_html($item->uid); ?>",
									item_name: "<?php echo esc_html($item->item . ' - ' . $item->option); ?>",
									currency: "<?php echo esc_html($currency_base); ?>",
									coupon: "<?php echo isset($_COOKIE['rezgo_promo']) ? esc_html($_COOKIE['rezgo_promo']) : esc_html($site->cart_trigger_code); ?>",
									price: <?php echo esc_html((float)$item->overall_total); ?>,
									quantity: 1,
									index: <?php echo esc_html($tag_index++); ?>,
								},
								<?php } unset($tag_index); ?>
								]
							}
						);
					<?php } ?>	

					<?php echo LOCATION_WINDOW; ?>.location.href='<?php echo esc_url($site->base); ?>/order<?php echo (REZGO_LITE_CONTAINER) ? '/?cart_token='.esc_html($site->cart_token) : ''; ?>';
				});
			});
			</script>
		<?php } ?>
	</div> <!-- right-flex-wrap -->
</div> <!-- top-bar-order -->