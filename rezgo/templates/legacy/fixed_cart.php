            <div id="rezgo-fixed-cart" class="fixed-cart <?php if ( ($_REQUEST['mode'] == 'page_book') && ($_REQUEST['step'] == 2) ){ ?> 
				fixed-last-step	<?php } ?>">

				<?php foreach ($cart as $item){
					if ($site->exists($item->deposit) ) {
						$step_one_total += (float) $item->deposit_value; 
					} else {
						$step_one_total += (float) $item->overall_total;
					}

					if ($site->exists($item->package)) {
						$summary_cart_package_uids[] .= $item->cart_package_uid; 
					} else {
						$summary_non_package_items[] = $item; 
					}
				} unset($item); 

				$summary_unique_package_uids = is_array($summary_cart_package_uids) ? array_unique($summary_cart_package_uids) : '';
				$summary_cart_count = (int)count((is_countable($summary_unique_package_uids))?$summary_unique_package_uids:[]) + (int)count((is_countable($summary_non_package_items))?$summary_non_package_items:[]) ; ?>

					<div id ="rezgo-show-content-toggle" class="cart-summary-dropdown">
						<div>
							<h4 class="title"><i class="far fa-shopping-cart"></i>
							<span id="hide-show-text">Show </span> Order <span class="hidden-xs">Summary</span> <i class="far fa-angle-down"></i> </h4>
						</div>
						<div>

						<?php if ($_REQUEST['step'] == 2) { ?>
							<span id="summary_total_value" rel="<?php echo esc_attr($complete_booking_total); ?>"><?php echo esc_html($site->formatCurrency($complete_booking_total)); ?></span>
						<?php } else { ?>
							<span id="summary_total_value" data-total="<?php echo esc_attr($step_one_total); ?>" rel="<?php echo esc_attr($step_one_total); ?>"><?php echo esc_html($site->formatCurrency($step_one_total)); ?></span>
						<?php } ?>

						</div>
					</div> <!-- rezgo-show-content-toggle -->

				<div class="cart-summary">
					<div class="toggle-content">
						<div class="line-items">

							<?php $summary_count = 1; ?>
							<?php $cart_count = 1; ?>
							<?php $total_savings; ?>

							<?php foreach ($cart as $item){ ?>
								
								<?php $line_items = $site->getTourLineItems($item);?>

									<?php // bundle savings
									foreach($line_items as $line) {	
										if ($line->source == 'bundle') { 
											// echo $line->label . ' bundle : ';
											// echo $line->amount;
											// echo '<br>';
											$bundle_savings += $line->amount;
										}
									} ?>

									<?php foreach($site->getTourPrices($item) as $price) { ?>
										
										<?php if($item->{$price->name.'_num'}) {

											$count = (int) $price->count;
											$strike_price = (float) $price->strike * $count;
											$base_price = (float) $price->base * $count;
											$current_price = (float) $price->price * $count;
											
											if ( ($site->exists($price->strike)) && ($site->exists($price->base)) )  {

												$show_this = max($strike_price, $base_price);
												$discount_savings = $show_this - $current_price; 
												$total_discount += $discount_savings;

											} else if(!$site->isVendor() && $site->exists($price->strike)) {

												// check if strike price higher than set price
												if ($strike_price > $current_price){
													$discount_savings = $strike_price - $current_price;
													$total_discount += $discount_savings;
												}

											} else if($site->exists($price->base)) { 

												$discount_savings = $base_price - $current_price;
												$total_discount += $discount_savings;
											
											} ?>

										<?php } ?>
									<?php } ?>

							<?php 
								if ($site->exists($item->package_item_total)){
								$first = (int)$item->package_item_index === 1 ? 1 : '';
								$last = (int)$item->package_item_index === (int)$item->package_item_total ? 1 : ''; 
								$cart_package_uid = (int)$item->cart_package_uid; ?>

								<?php 
									// add up item totals
									// $summary_package_sub_total[$cart_package_uid] += $item->sub_total; 
									// $summary_package_overall_total[$cart_package_uid] += $item->overall_total; 
									// $summary_package_deposit_value[$cart_package_uid] += $item->deposit_value;
								?>

								<?php if ($first){ ?>
									<div class="package-icon-wrapper">
										<span class="summary-count"> <?php echo esc_html($cart_count); ?> of <?php echo esc_html($summary_cart_count); ?></span>
										<div class="count-icon-container">
											<i class="fad fa-layer-group package-icon"></i>
											<span class="summary-package-item-count"><?php echo (int) esc_html($item->package_item_total); ?> items </span>
										</div>
									</div>
								<?php } ?> 

								<div class="item packaged-item">
									<h4 class="single-item">
										<span class="rezgo-summary-item-name"><?php echo esc_html($item->item); ?></span>
										<br> 
										<span class="rezgo-summary-option-name"><?php echo esc_html($item->option); ?></span>
									</h4>

									<?php //if ($last){ ?>
										<?php if($site->exists($item->deposit)) { ?>
											
											<script>
												// override with set deposit 
												setTimeout(function () {
													jQuery('#summary_price_<?php echo esc_html($summary_count); ?>').html('<?php echo esc_html($site->formatCurrency($item->deposit_value)); ?>');
												}, 1500);
											</script>
											
											<div class="price-container">
												<h4 class="price"> 
													<span class="summary_deposit_value">
														<span rel="<?php echo esc_attr($item->deposit_value); ?>" id="summary_price_<?php echo esc_attr($summary_count); ?>">
														</span>
													</span>
													<span class="deposit">(Deposit)</span>
												</h4>
											</div>
											
										<?php } else { ?>

											<div class="price-container">
												<h4 class="price">
													<span class="summary_total_value">
													<?php if ($_REQUEST['step'] == 1 || $_REQUEST['mode'] == 'page_order'){ ?>
														<span rel="<?php echo esc_attr($item->overall_total); ?>" id="summary_price_<?php echo esc_attr($summary_count); ?>">
															<?php echo esc_html($site->formatCurrency($item->overall_total)); ?>
														</span>
													<?php } elseif ($_REQUEST['step'] == 2) { ?>
														<span rel="<?php echo esc_attr($total_value[$summary_count]); ?>" id="summary_price_<?php echo esc_attr($summary_count); ?>">
															<?php echo esc_html($site->formatCurrency($total_value[$summary_count])); ?>
														</span>
													</span>
													<?php } ?>
												</h4>
											</div>
										<?php } ?>
									<?php //} ?>

								</div>

								<?php 
									if ($last) {
										echo '<hr>'; 
										$cart_count++; 
									} $summary_count++;
								?>

							<?php } else { ?>

								<span class="summary-count"> <?php echo esc_html($cart_count); ?> of <?php echo esc_html($summary_cart_count); ?></span>
									<div class="item">
										<h4 class="single-item">
											<span class="rezgo-summary-item-name"><?php echo esc_html($item->item); ?></span>
											<br> 
											<span class="rezgo-summary-option-name"><?php echo esc_html($item->option); ?></span>
										</h4>

										<?php if($site->exists($item->deposit)) { ?>
											
											<script>
												// override with set deposit 
												setTimeout(function () {
													jQuery('#summary_price_<?php echo esc_html($summary_count); ?>').html('<?php echo esc_html($site->formatCurrency($item->deposit_value)); ?>');
												}, 1500);
											</script>
											
											<div class="price-container">
												<h4 class="price"> 
													<span class="summary_deposit_value">
														<span rel="<?php echo esc_attr($item->deposit_value); ?>" id="summary_price_<?php echo esc_attr($summary_count); ?>">
														</span>
													</span>
													<span class="deposit">(Deposit)</span>
												</h4>
											</div>
											
										<?php } else { ?>

											<div class="price-container">
												<h4 class="price">
													<span class="summary_total_value">
													<?php if ($_REQUEST['step'] == 1 || $_REQUEST['mode'] == 'page_order'){ ?>
														<span rel="<?php echo esc_attr($item->overall_total); ?>" id="summary_price_<?php echo esc_attr($summary_count); ?>">
															<?php echo esc_html($site->formatCurrency($item->overall_total)); ?>
														</span>
													<?php } elseif ($_REQUEST['step'] == 2) { ?>
														<span rel="<?php echo esc_attr($total_value[$summary_count]); ?>" id="summary_price_<?php echo esc_attr($summary_count); ?>">
															<?php echo esc_html($site->formatCurrency($total_value[$summary_count])); ?>
														</span>
													</span>
													<?php } ?>
												</h4>
											</div>
										<?php } ?>

									</div>
								<hr>

							<?php $summary_count++; $cart_count++; } // if ($site->exists($item->package_item_total))

							} // end foreach ($cart as $item)

							// make sure to return a positive value
							$bundle_savings = abs($bundle_savings);
							$total_discount = abs($total_discount);
							
							$total_savings += $bundle_savings; 
							$total_savings += $total_discount; 
							?>
						</div> <!-- // line-items -->

						<?php if(!$site->isVendor()) {
								$hidden = '';
								$disabled = '';
								if ($_REQUEST['mode'] != 'page_order'){$hidden = 'hidden'; $disabled = 'disabled'; }
							?>

							<div id="rezgo-order-promo-code-wrp" class="row rezgo-form-group-short">

								<?php $trigger_code = $site->cart_trigger_code ?>
								<?php if ( (!$trigger_code) || ($trigger_code == '') ) { ?>

								<?php if ($_REQUEST['mode'] == 'page_order') {?>

                                    <span id="rezgo-promo-form-memo"></span>
									
                                    <form class="form-inline rezgo-promo-form" id="rezgo-promo-form" role="form">

										<div class="input-group <?php echo esc_attr($hidden); ?>">
											<input type="text" class="form-control" id="rezgo-promo-code" name="promo" placeholder="Enter Promo Code" value="<?php echo ($trigger_code ? esc_attr($trigger_code) : '')?>" required>
											
											<span id="promo-error-msg"></span>

											<div class="input-group-btn">
												<button class="btn rezgo-btn-default" target="_parent" type="submit">
													<span>Apply</span>
												</button>
											</div>
										</div>

									</form>

									<?php } ?>

								<?php } else { ?>
							
									<span class="rezgo-booking-discount <?php echo esc_attr($disabled); ?> rezgo-promo-label">
										<span class="rezgo-discount-span">Promo applied:</span> 
											<span id="rezgo-promo-value">
												<?php echo esc_html($trigger_code); ?>
											</span>
											<a id="rezgo-promo-clear" style="color:#333;" class="btn <?php echo esc_attr($hidden); ?>" href="<?php echo esc_url($_SERVER['HTTP_REFERER']); ?>/?promo=" target="_top"><i class="fa fa-times"></i></a>
										<hr>
									</span>
									
							<?php } ?>
							</div> <!-- // rezgo-order-promo-code-wrp -->

							<script>
								jQuery('#rezgo-promo-form').submit( function(e){
									e.preventDefault();
									<?php if ($site->exists($site->getAnalyticsGa4())) { ?>
										// gtag select_promotion
										gtag("event", "select_promotion", {
											promo_code: document.querySelector('#rezgo-promo-code').value,
										});
									<?php } ?>

									<?php if ($site->exists($site->getAnalyticsGtm())) { ?>
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

									jQuery('#rezgo-promo-form').ajaxSubmit({
										type: 'POST',
										url: '<?php echo admin_url('admin-ajax.php'); ?>' + '?action=rezgo&method=book_ajax',
										data: { rezgoAction: 'update_promo' },
										success: function(){
											window.top.location.replace('<?php echo esc_url($_SERVER['HTTP_REFERER']); ?>?promo=' + jQuery('#rezgo-promo-code').val());
										}
									})
								});

								jQuery('#rezgo-promo-clear').click(function(){
									jQuery.ajax({
										type: 'POST',
										url: '<?php echo admin_url('admin-ajax.php'); ?>' + '?action=rezgo&method=book_ajax',
										data: { rezgoAction: 'update_promo' },
										success: function(){
										}
									})
								});
							</script>
						<?php } ?>  
		
						<div class="row">
							<?php if ($total_savings > 0) { ?>
								<div class="col-sm-12 rezgo-savings">
									<h5><span id="rezgo-savings-description">You saved</span></h5> &nbsp; &nbsp;
									<span id="total_savings"><?php echo esc_html($site->formatCurrency($total_savings)); ?></span>
								</div>
							<?php } ?>

							<div class="col-sm-12 rezgo-summary-order-total">

								<?php if ($_REQUEST['step'] == 2) { ?>

									<div class="rezgo-summary-tips-container">
										<span id="rezgo_summary_tips"></span>
									</div>

									<div class="rezgo-total-container">
										<h5>Total Due</h5> &nbsp; &nbsp;
										<span id="total_value" rel="<?php echo esc_attr($complete_booking_total); ?>"><?php echo $site->formatCurrency($complete_booking_total); ?></span>
									</div>

								<?php } else { ?>

								<div class="rezgo-total-container">
									<h5>Subtotal</h5> &nbsp; &nbsp;
									<span id="total_value" data-total="<?php echo esc_attr($step_one_total); ?>" rel="<?php echo esc_attr($step_one_total); ?>"><?php echo esc_html($site->formatCurrency($step_one_total)); ?></span>
								</div>

								<?php } ?>
							</div>
						</div>
					</div><!-- // toggle-content -->

						<div class="rezgo-btn-wrp fixed-cart-btn-wrap">
							<?php if ( $_REQUEST['mode'] == 'page_order') { ?>

								<?php if(count($cart)) { ?>
									<span id="rezgo-booking-btn">
										<a href="<?php echo esc_url($site->base); ?>" id="rezgo-order-book-more-btn" class="btn rezgo-btn-default btn-lg btn-block">
											<span>Book More</span>
										</a>
									</span>
									<a id ="rezgo-btn-book" href="<?php echo esc_url($site->base); ?>/book" class="btn rezgo-btn-book btn-lg btn-block rezgo-order-step-btn-side"><span>Check Out</span></a>
								<?php } ?>

							<?php } else if ( $_REQUEST['mode'] == 'page_book'){ ?>

									<?php if($_REQUEST['step'] == 1) { ?>
										<a id="rezgo-book-step-one-btn-previous" class="btn rezgo-btn-default btn-lg btn-block rezgo-book-step-btn-previous" href="<?php echo esc_url($site->base); ?>/order"><span>Back to Order</span></a>
										<button id ="rezgo-book-step-one-btn-continue" class="btn rezgo-btn-book btn-lg btn-block rezgo-book-step-btn-continue" type="submit" form="rezgo-guest-form">
											<span>Continue to Payment</span>
										</button>
									<?php } else { ?>
										<style>
											#rezgo-fixed-cart .rezgo-btn-wrp{
											margin-top: 0 !important;}
										</style>
											<a class="btn rezgo-btn-default btn-lg btn-block" href="<?php echo esc_url($site->base); ?>/book"><span>Previous Step</span></a>
										</button>
									<?php } ?>
							<?php } ?>
						</div>  <!-- // fixed-cart-btn-wrap -->

				</div> <!-- // cart summary -->

				<?php if ( ($_REQUEST['mode'] == 'page_book') && ($_REQUEST['step'] == 1) ){ ?> 
					<!-- show error msgs only on guest info page -->
					<div id="rezgo-book-errors" class="alert alert-danger rezgo-book-errors-side">
						<span>Some required fields are missing. Please complete the highlighted fields.</span>
					</div>
				<?php } ?>

			 </div><!-- // fixed cart -->
			 
<script>

jQuery(document).ready(function($){
	// fixed summary at the side 
	function getScrollTop() {
		if (typeof window.parent.pageYOffset !== "undefined" ) {
			// Most browsers
			return window.parent.pageYOffset;
		}
		var d = document.documentElement;
		if (typeof d.clientHeight !== "undefined") {
			// IE in standards mode
			return d.scrollTop;
		}
		// IE in quirks mode
		return document.body.scrollTop;
	}

	var cart = document.getElementById("rezgo-fixed-cart");
	var container = parent.document.getElementById('rezgo_content_container');
	// account for whitelabel header
	var header = parent.document.getElementById('rezgo-default-header');

	function toggleScroll(){
		window.parent.addEventListener('scroll', function() {
			var scroll = getScrollTop();
			var offset = container.offsetTop - 10;

			if (header){
				headerHeight = 80;
			} else {
				headerHeight = 0;
			}

			cart.style.top = (scroll - offset + headerHeight) + "px";
		});
	}

	// toggle order summary on mobile
	var toggle_div = $('#rezgo-show-content-toggle');
	var toggle_content = $('.toggle-content');

	$(window).resize(function() {
		let width = this.innerWidth;
		if (width > 992){
			toggle_content.show();
			toggle_div.find('i.fa-angle-down').addClass('active');
			toggle_div.find('span#hide-show-text').text('Hide ');

			toggleScroll();
		}
	});

	toggle_div.click(function(){
		toggle_content.slideToggle(250);
		$(this).find('span#hide-show-text').text(function(i, text){
			return text === "Show " ? "Hide " : "Show ";
		});
		$(this).find('i.fa-angle-down').toggleClass('active');
	});
});
</script>