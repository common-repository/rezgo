<?php
$company = $site->getCompanyDetails();
$booking_currency = $company->currency_base;

if (REZGO_WORDPRESS) $site->setTimeZone();

$analytics_ga4 = (string)$company->analytics_ga4;
$analytics_gtm = (string)$company->analytics_gtm;
$meta_pixel = (string)$company->meta_pixel;
?>

<?php if (!REZGO_WORDPRESS) { ?> <script src="<?php echo $site->path; ?>/js/jquery.form.js"></script> <?php } ?>

<div id="rezgo-order-wrp" class="container-fluid rezgo-container">
	<div class="jumbotron rezgo-booking">
		<div id="rezgo-order-crumb" class="row">
			<ol class="breadcrumb rezgo-breadcrumb">
				<li id="rezgo-order-your-order" class="rezgo-breadcrumb-order active"><span class="default"> Order</span><span class="custom"></span></li>
				<li id="rezgo-order-info" class="rezgo-breadcrumb-info"><span class="default">Guest Information</span><span class="custom"></span></li>
				<li id="rezgo-order-billing" class="rezgo-breadcrumb-billing"><span class="default">Payment</span><span class="custom"></span></li>
				<li id="rezgo-order-confirmation" class="rezgo-breadcrumb-confirmation"><span class="default">Confirmation</span><span class="custom"></span></li>
			</ol>
		</div>

		<?php $cart = $site->getCart(); ?>
		<?php if(isset($_COOKIE['cart_status'])) $cart_status = new SimpleXMLElement($_COOKIE['cart_status']); ?>

		<?php if (isset($cart_status)){ 
			// clear promo if there is an invalid promo
			if (($cart_status->error_code == 9) || ($cart_status->error_code == 11)) $site->resetPromoCode(); ?>
			<div id="rezgo-order-error-message">
				<!-- Top level error message -->
				<span class="message">
					<span id="error-message"><?php echo esc_html($cart_status->message); ?></span>
						<?php // list items removed
							if (is_array($cart_status->removed->item)) {
								foreach ($cart_status->removed->item as $removed_item){
									$tour = $site->getTours('t=uid&q='.$removed_item->id); 
									$removed_date = $tour[0]->availability_type != 'open' ? ' ('. date((string) $company->date_format, (string) $removed_item->date) .')' : ''; ?>
									<br>
									<?php echo esc_html($tour[0]->item); ?> - <?php echo esc_html($tour[0]->option . $removed_date); ?>
								has been removed from your cart
							<?php } ?>
						<?php } ?>
				</span>
				<a href="#" id="rezgo-error-dismiss" class="btn"><span><i class="fas fa-times"></i></span></i></a>
			</div>

			<script>
				jQuery(function($) {
					// dismiss error when user navigates away or manually closes it
					function dismissError(){
						$.ajax({
							type: 'POST',
							url: '<?php echo admin_url('admin-ajax.php'); ?>' + '?action=rezgo&method=book_ajax',
							data: { rezgoAction: 'reset_cart_status'},
							success: function(data){
									// console.log('reset cart status session');
									},
							error: function(error){
									console.log(error);
									}
						});
					}

					$('#rezgo-error-dismiss').click(function(e){
						dismissError();
						e.preventDefault();
						$('#rezgo-order-error-message').fadeOut();
					});

					setTimeout(() => {
						dismissError();
					}, 3000);

					window.onbeforeunload = dismissError();
				});
			</script>
		<?php } ?>

		<?php if (empty($cart)) { ?>
			<div class="rezgo-order-empty-cart-wrp">
				<div class="rezgo-form-group cart_empty">
					<p class="lead">
						<span class="d-none d-sm-inline-block">There are</span><span>&nbsp;<span class="d-none d-sm-inline-block">n</span><span class="d-sm-none">N</span>o items</span><span class="d-none d-sm-inline-block">&nbsp;in your order.</span>
					</p>
				</div>

				<div class="row" id="rezgo-booking-btn">
					<div class="col-md-4 col-12 rezgo-btn-wrp">
						<span class="btn-check"></span>
						<a id="rezgo-order-book-more-btn" href="<?php echo esc_attr($site->base); ?>" class="btn rezgo-btn-default btn-lg btn-block">
							<span>Book More</span>
						</a>
					</div>
				</div>
			</div>
		<?php } else {
			$cart_total = 0;
			$complete_booking_total = 0;
			$item_num = 0; 
			$index = 0;
			$package_index = 0;
			$item_count = 1;

			$contents = array(); 
			$cart_coms = array(); 
			$cross_ids = array();  

			$item_total = array();
			$item_booking_total = array();
			$non_package_items = array();
			$cart_package_uids = array();
			$package_sub_total = array();
			$total_deposit_set = array();
			$package_overall_total = array();
			$package_deposit_value = array();
	
			foreach ($cart as $item) {
				if (isset($item->package) && $site->exists($item->package)) {
					$cart_package_uids[] .= $item->cart_package_uid; 
				} else {
					$non_package_items[] = $item; 
				}
			} unset($item);

			$unique_package_uids = array_unique($cart_package_uids);
			$cart_count = (int)count($unique_package_uids) + (int)count($non_package_items);
		?>
		
		<script>
			// google analytics vars
			<?php if ($analytics_ga4) { ?>
				let ga4_package_details = {
					<?php $i = 1; 
						foreach ($cart_package_uids as $uid) { ?>
						'item_<?php echo $i++; ?>_<?php echo esc_html($uid); ?>' : {
									id: '', 
									name: '', 
									price: '', 
									coupon: "<?php echo isset($_COOKIE['rezgo_promo']) ? esc_html($_COOKIE['rezgo_promo']) : esc_html($site->cart_trigger_code); ?>",
									currency: "<?php echo esc_html($booking_currency); ?>",
									index: '', 
									quantity: 1,
								},
					<?php } unset($i); ?>
					}
			<?php } ?>

			<?php if ($analytics_gtm) { ?>
				let gtm_package_details = {
					<?php $i = 1; 
						foreach ($cart_package_uids as $uid) { ?>
						'item_<?php echo $i++; ?>_<?php echo esc_html($uid); ?>' : {
									id: '', 
									name: '', 
									price: '', 
									coupon: "<?php echo isset($_COOKIE['rezgo_promo']) ? esc_html($_COOKIE['rezgo_promo']) : esc_html($site->cart_trigger_code); ?>",
									currency: "<?php echo esc_html($booking_currency); ?>",
									index: '', 
									quantity: 1,
								},
					<?php } unset($i); ?>
					}
			<?php } ?>

			<?php if ($meta_pixel) { ?>
				let pixel_package_details = {
					<?php $i = 1; 
						foreach ($cart_package_uids as $uid) { ?>
						'item_<?php echo $i++; ?>_<?php echo esc_html($uid); ?>' : {
									id: '', 
									name: '', 
									price: '', 
									coupon: "<?php echo isset($_COOKIE['rezgo_promo']) ? esc_html($_COOKIE['rezgo_promo']) : esc_html($site->cart_trigger_code); ?>",
									currency: "<?php echo esc_html($booking_currency); ?>",
									index: '', 
									quantity: 1,
								},
					<?php } unset($i); ?>
					}
			<?php } ?>
		</script>

		<div class="flex-container order-page-container">
			<div class="order-summary">

				<?php foreach ($cart as $item) { 
					$site->readItem($item); ?>

					<?php	
					if (isset($item->package_item_total) && $site->exists($item->package_item_total)){
						$first = (int)$item->package_item_index === 1 ? 1 : '';
						$last = (int)$item->package_item_index === (int)$item->package_item_total ? 1 : ''; 
						$package_id = (int)$item->package; 
						$cart_package_uid = (int)$item->cart_package_uid; 
						$package = $site->getTours('t=com&q='.$item->package); 
						$package_sub_total[$cart_package_uid] = 0;
						$package_overall_total[$cart_package_uid] = 0;
						$package_deposit_value[$cart_package_uid] = 0;
						$item_total[$cart_package_uid][$index] = 0;
						?>
												
						<script>
						<?php if ($analytics_ga4) { ?>
							ga4_package_details.item_<?php echo esc_html($item->num); ?>_<?php echo esc_html($cart_package_uid); ?>.index = '<?php echo esc_html($item->num); ?>';
							ga4_package_details.item_<?php echo esc_html($item->num); ?>_<?php echo esc_html($cart_package_uid); ?>.name = '<?php echo $item->item; ?>' + ' - ' + '<?php echo esc_html($item->option); ?>';
							ga4_package_details.item_<?php echo esc_html($item->num); ?>_<?php echo esc_html($cart_package_uid); ?>.id = '<?php echo esc_html($item->uid); ?>';
							ga4_package_details.item_<?php echo esc_html($item->num); ?>_<?php echo esc_html($cart_package_uid); ?>.price = <?php echo $item->overall_total; ?>;
						<?php } ?>

						<?php if ($analytics_gtm) { ?>
							gtm_package_details.item_<?php echo esc_html($item->num); ?>_<?php echo esc_html($cart_package_uid); ?>.index = '<?php echo esc_html($item->num); ?>';
							gtm_package_details.item_<?php echo esc_html($item->num); ?>_<?php echo esc_html($cart_package_uid); ?>.name = '<?php echo $item->item; ?>' + ' - ' + '<?php echo esc_html($item->option); ?>';
							gtm_package_details.item_<?php echo esc_html($item->num); ?>_<?php echo esc_html($cart_package_uid); ?>.id = '<?php echo esc_html($item->uid); ?>';
							gtm_package_details.item_<?php echo esc_html($item->num); ?>_<?php echo esc_html($cart_package_uid); ?>.price = <?php echo $item->overall_total; ?>;
						<?php } ?>

						<?php if ($meta_pixel) { ?>
							pixel_package_details.item_<?php echo esc_html($item->num); ?>_<?php echo esc_html($cart_package_uid); ?>.index = '<?php echo esc_html($item->num); ?>';
							pixel_package_details.item_<?php echo esc_html($item->num); ?>_<?php echo esc_html($cart_package_uid); ?>.name = '<?php echo $item->item; ?>' + ' - ' + '<?php echo esc_html($item->option); ?>';
							pixel_package_details.item_<?php echo esc_html($item->num); ?>_<?php echo esc_html($cart_package_uid); ?>.id = '<?php echo esc_html($item->uid); ?>';
							pixel_package_details.item_<?php echo esc_html($item->num); ?>_<?php echo esc_html($cart_package_uid); ?>.price = <?php echo $item->overall_total; ?>;
						<?php } ?>
						</script>

						<?php if ($first){ ?>
							<div class="rezgo-sub-title">
								<span> &nbsp; Booking <?php echo esc_html($item_count); ?> of <?php echo esc_html($cart_count); ?></span>
							</div>

							<h3 class="rezgo-package-title">
								<?php $package_url = $site->base.'/details/'.$item->package.'/'.$site->seoEncode($item->package_name); ?>
								<a href="<?php echo esc_url($package_url); ?>">
									<i class="fad fa-layer-group fa-lg"></i> 
								<span><?php echo esc_html($item->package_name); ?></span>
								</a>
							</h3>
						<?php } ?> 

						<div class="package-icon-container">
							<i class="fad fa-circle"></i>
						</div>

						<div id="rezgo-order-item-<?php echo esc_attr($item->uid); ?>" class="single-order-item single-package-order-item">

							<div class="row rezgo-cart-title-wrp">
								<div class="col-9 rezgo-cart-title">
									<h3 class="rezgo-item-title">
										<?php $item_url = $site->base.'/details/'.$item->com.'/'.$site->seoEncode($item->item); ?>
										<a href="<?php echo esc_url($item_url); ?>">
											<span><?php echo esc_html($item->item); ?></span>
											<span> &mdash; <?php echo esc_html($item->option); ?></span>
										</a>
									</h3>

									<?php $data_book_date = date("Y-m-d", (int)$this->cart_data[$index]->date); ?>

									<?php if (in_array((string) $item->date_selection, DATE_TYPES)){ ?>
										<label>
											<span>Date: </span>
											<span class="lead"><?php echo esc_html(date((string) $company->date_format, (int) $item->booking_date)); ?></span>
										</label>
										<?php if (isset($item->time) && $site->exists($item->time)){ ?>
											<label>at <?php echo (string) esc_html($item->time); ?></label>
										<?php } ?> 
									<?php } else { ?>
										<label><span class="lead"> Open Availability </span></label>
									<?php } ?>

									<?php if ($item->discount_rules->rule) {
										echo '<br><label class="rezgo-booking-discount">
										<span class="rezgo-discount-span">Discount:</span> ';
										$discount_string = '';
										foreach($item->discount_rules->rule as $discount) {	
											$discount_string .= ($discount_string) ? ', '.$discount : $discount;
										}
										echo '<span class="rezgo-promo-code-desc">'.esc_html($discount_string).'</span>
										</label>';
									} ?>

									<div class="rezgo-order-memo rezgo-order-date-<?php echo esc_attr($data_book_date); ?> rezgo-order-item-<?php echo esc_attr($item->uid); ?>"></div>
								</div>

								<?php if ($first){ ?>
									<div class="col-3 column-btns package-column-btns">
										<div class="rezgo-btn-cart-wrp">
											<span class="btn-check"></span>
											<button type="button" data-bs-toggle="collapse" class="btn btn-block rezgo-pax-edit-btn" data-index="<?php echo esc_attr($index); ?>" data-order-item="<?php echo esc_attr($item->uid); ?>" data-order-com="<?php echo esc_attr($item->com); ?>" data-cart-id="<?php echo esc_attr($item_num); ?>" data-book-date="<?php echo esc_attr($data_book_date); ?>" data-book-time="<?php echo esc_attr($item->time_format == 'dynamic' ? $item->time : ''); ?>" data-package-id="<?php echo esc_attr($package_id); ?>" data-cart-package-uid="<?php echo esc_attr($cart_package_uid); ?>" href="#pax-edit-<?php echo esc_attr($item_num); ?>">
												<span>Edit Guests</span>
											</button>
										</div>

										<div class="rezgo-btn-cart-wrp">
											<span class="btn-check"></span>
											<button type="button" class="btn rezgo-btn-remove btn-block" data-index="<?php echo esc_attr($index); ?>" data-date=<?php echo esc_attr($data_book_date); ?> data-order-item="<?php echo esc_attr($item->uid); ?>" data-com="<?php echo esc_attr($package[0]->com); ?>" data-name="<?php echo esc_attr($item->item . ' - ' . $item->option); ?>" data-total="<?php echo esc_attr($item->overall_total); ?>" data-url="<?php echo esc_attr($site->base); ?>/order?edit[<?php echo esc_attr($item->cartID); ?>][adult_num]=0" data-cart-package-uid="<?php echo esc_attr($cart_package_uid); ?>">
												<span>Remove<span class='d-none d-sm-inline-block'><u> &nbsp;from Order </u></span></span>
											</button>
										</div>
									</div>
								<?php } ?> 
							</div>

							<?php 
								// add up item totals
								$package_sub_total[$cart_package_uid] += $item->sub_total; 
								$package_overall_total[$cart_package_uid] += $item->overall_total; 
								$package_deposit_value[$cart_package_uid] += $item->deposit_value;

								//add up line items
								$package_line_items[$cart_package_uid.$item->uid.$index][] = $site->getTourLineItems();
							?>

								<div class="row rezgo-form-group rezgo-cart-table-wrp">

									<?php if ($first) { ?>
									<div class="collapse rezgo-pax-edit-box" id="pax-edit-<?php echo esc_attr($item_num); ?>"></div>
									<div id="pax-edit-scroll-<?php echo esc_attr($item_num); ?>" class="rezgo-cart-edit-wrp"></div>
									<?php } ?>
									
									<div class="col-12">
										<table class="table rezgo-billing-cart table-responsive">
											<tr class="rezgo-tr-head">
												<td class="text-start rezgo-billing-type"><label>Type</label></td>
												<td class="text-start rezgo-billing-qty"><label class="hidden-xs">Qty.</label></td>
												<td class="text-start rezgo-billing-cost"><label>Cost</label></td>
												<td class="text-end rezgo-billing-total"><label>Total</label></td>
											</tr>
			
											<?php 
												// gather package price points
												foreach ($package[0]->prices->price as $package_price_point) { 
													$package_price_id = (int) $package_price_point->id; ?>

													<span id="package-label-<?php echo esc_attr($package_id.'-'.$cart_package_uid.'-main-'.$package_price_id); ?>" class="d-none"><?php echo (string) esc_html($package_price_point->label); ?></span>

													<script>
														// replace subsequent labels in package items with package price labels
														setTimeout(() => {
															jQuery('.package-label-<?php echo esc_html($package_id.'-'.$cart_package_uid.'-sub-'.$package_price_id); ?>').text(jQuery('#package-label-<?php echo esc_html($package_id.'-'.$cart_package_uid.'-main-'.$package_price_id); ?>').text());
														}, 150);
													</script>

											<?php } ?>

											<?php 
												$price_label_count = 0;
												foreach($site->getTourPrices($item) as $price) { ?>

												<?php if($item->{$price->name.'_num'}) { ?>

													<?php $price_name = $cart_package_uid.'_'.$price->name; ?>

													<tr class="rezgo-tr-pax">
														<td class="text-start package-label-<?php echo esc_attr($package_id.'-'.$cart_package_uid.'-sub-'.$price->id); ?>"><?php echo esc_html($price->label); ?></td>
														<td class="text-start"><?php echo esc_html($item->{$price->name.'_num'}); ?></td>
														<td class="text-start package-pax-price">
															<?php
																$initial_price = isset($price->price) ? (float) $price->price : 0;
																$strike_price = isset($price->strike) ? (float) $price->strike : 0;
																$discount_price = isset($price->base) ? (float) $price->base : 0;
															?>
															<?php if ( ($site->exists($price->strike)) && (isset($price->base) && $site->exists($price->base)) )  { ?>
																<?php $show_this = max($strike_price, $discount_price); ?>

																<span class="discount">
																	<?php echo esc_html($site->formatCurrency($show_this)); ?>
																</span>

															<?php } else if($site->exists($price->strike)) { ?>

																<span class="discount">
																	<!-- show only if strike price is higher -->
																	<?php if ($strike_price >= $initial_price) { ?>
																		<span class="rezgo-strike-price">
																			<?php echo esc_html($site->formatCurrency($strike_price)); ?>
																		</span>
																	<?php } ?>
																</span>

															<?php } else if(isset($price->base) && $site->exists($price->base)) { ?>

																<span class="discount">
																	<?php echo esc_html($site->formatCurrency($price->base)); ?>
																</span>

															<?php } ?>
																<?php echo esc_html($site->formatCurrency($price->price)); ?>
														</td>		
														<td class="text-end package-pax-total">
															<span>
																<?php echo esc_html($site->formatCurrency($price->total)); ?>
															</span>
														</td>
														
													</tr>

													<?php if(!$site->exists($item->deposit)) { 
														$item_total[$cart_package_uid][$index] += $price->total; 
													} else {
														$item_total[$cart_package_uid][$index] = (float)$item->deposit_value; 
													} ?>

												<?php $price_label_count++; } ?>
											<?php } // end foreach($site->getTourPrices($item) as $price) ?>

											<?php if ((int) $item->availability < (int) $item->pax_count) { ?>
												<tr class="rezgo-tr-order-unavailable">
													<td colspan="4" class="rezgo-order-unavailable">
														<span data-bs-toggle="tooltip" data-placement="top" title="This item has become unavailable after it was added to your order">
															<i class="fa fa-exclamation-triangle"></i>
															<span> No Longer Available</span>
														</span>
													</td>
												</tr>
											<?php } else { $cart_total += (float) $package_overall_total; } ?>

												<?php 
													if (isset($package_line_items[$cart_package_uid.$item->uid.$index])) {
														foreach ($package_line_items[$cart_package_uid.$item->uid.$index] as $k => $v) {

															// remove booking fee and package discount
															foreach ($v as $line_item) {
																$omit = array('');
																if (!in_array( (string)$line_item->label, $omit)){
																	$line_items[$cart_package_uid]['item_'.$item->uid.$index][] = $line_item;
																} else {
																	$consolidated_line_items[$cart_package_uid][] = $line_item;
																}
															}
														}
													}
												?>

												<?php 	
													$pax_totals = array(
														'adult_num' => 'price_adult', 
														'child_num' => 'price_child', 
														'senior_num' => 'price_senior', 
														'price4_num' => 'price4', 
														'price5_num' => 'price5', 
														'price6_num' => 'price6', 
														'price7_num' => 'price7', 
														'price8_num' => 'price8', 
														'price9_num' => 'price9'
													);
												?>
											<?php if (isset($line_items[$cart_package_uid]['item_'.$item->uid.$index])) { ?>
												
												<?php foreach($line_items[$cart_package_uid]['item_'.$item->uid.$index] as $line) { ?>
												<?php $label_add = ''; ?>

													<?php

													if($site->exists($line->percent) || $site->exists($line->multi)) {
														$label_add = ' (';

														if($site->exists($line->percent)) {
															$label_add .= $line->percent.'%';
														}

														if($site->exists($line->multi)) {

															if(!$site->exists($line->percent)) {
																$label_add .= $site->formatCurrency($line->multi);
															}

																if($site->exists($line->meta)) {

																	$line_pax = 0;
																	foreach ($pax_totals as $p_num => $p_rate) {

																		if ( (int) $item->{$p_num} > 0 && ((float) $item->date->{$p_rate} > (float) $line->meta)) {
																			$line_pax += (int) $item->{$p_num};
																		}

																	}
																	$label_add .= ' x '.$line_pax;

																} else {
																	$label_add .= ' x '.$item->pax;

																}

														}

														$label_add .= ')';
													}
												
													?>
											
													<tr class="rezgo-tr-subtotal">
														<td colspan="3" class="text-end">
														<?php if ($line->source == 'bundle') { ?>
														<strong class="rezgo-line-bundle push-right"></i>&nbsp;<?php echo esc_html($line->label); ?><?php echo esc_html($label_add); ?> (Bundle)</strong></span>
														<?php } else { ?>
														<span class="push-right"><strong><?php echo esc_html($line->label); ?><?php echo esc_html($label_add); ?></strong></span>
														<?php } ?>
														</td>
														<td class="text-end text-nowrap"><?php echo esc_html($site->formatCurrency($line->amount)); ?></td>
													</tr>   

													<?php if(!$site->exists($item->deposit)) { 
														$item_total[$cart_package_uid][$index] += $line->amount;
													} else {
														$item_total[$cart_package_uid][$index] = (float)$item->deposit_value;
													} ?>
														
												<?php } unset($line); ?>

											<?php } ?>

												<tr class="rezgo-tr-subtotal package-item-total">

												<?php 
													if (!isset($item_booking_total[$cart_package_uid])) {
														$item_booking_total = [$cart_package_uid => (float) $item->overall_total]; 
													} else {
														$item_booking_total[$cart_package_uid] += (float) $item->overall_total;
													}
												?>
										
													<td colspan="3" class="text-end"><span class="push-right"><strong>Item Total</strong></span></td>
													<td class="text-end text-nowrap"><?php echo esc_html($site->formatCurrency($item->overall_total)); ?></td>
												</tr>
			
												<?php if($site->exists($item->deposit)) { ?>

													<?php 
														if (!isset($item_deposit_total[$cart_package_uid])) {
															$item_deposit_total = [$cart_package_uid => (float) $item->deposit_value]; 
														} else {
															$item_deposit_total[$cart_package_uid] += (float) $item->deposit_value;
														}
														$item_deposit_total[$cart_package_uid] += $item->deposit_value;
														$total_deposit_set = array($cart_package_uid => 1); 
													?>

													<tr class="rezgo-tr-subtotal package-item-total">
														<td colspan="3" class="text-end"><span class="push-right"><strong>Item Deposit Total</strong></span></td>
														<td class="text-end text-nowrap"><?php echo esc_html($site->formatCurrency($item->deposit_value)); ?></td>
													</tr>
												<?php } ?>

												<?php if ($last){ ?>

														<td colspan="3" class="rezgo-td-grouped-line-item text-end"></td>
														<td class="rezgo-td-grouped-line text-end"><i class="fad fa-horizontal-rule"></i></td>

														<tr class="rezgo-tr-package-subtotal package-total">
															<td colspan="3" class="text-end"><span class="push-right"><strong>Package Total</strong></span></td>
															<td class="text-end text-nowrap"><strong><?php echo esc_html($site->formatCurrency($item_booking_total[$cart_package_uid])); ?></strong></td>
														</tr>

													<?php //if((int)$total_deposit_set[$cart_package_uid] === (int)$item->package_item_total) { ?>

													<?php if (isset($total_deposit_set[$cart_package_uid])) { ?>
														<?php if ( ((int)$total_deposit_set[$cart_package_uid] === (int)$item->package_item_total) || 
																((int)$total_deposit_set[$cart_package_uid] > 0 && ((int)$total_deposit_set[$cart_package_uid] < (int)$item->package_item_total))   
															) { 

																$mixed_deposit = ((int)$total_deposit_set[$cart_package_uid] > 0 && ((int)$total_deposit_set[$cart_package_uid] < (int)$item->package_item_total)) ? 1 : 0;
																$deposit_wording = $mixed_deposit ? 'Required to Pay Now' : 'Deposit to Pay Now';
																$deposit_package_total = $mixed_deposit ? array_sum((array)$item_total[$cart_package_uid]) : $item_deposit_total[$cart_package_uid];
															?>

															<tr class="rezgo-tr-deposit">
																<td colspan="3" class="text-end">
																	<span class="push-right"><strong><?php echo esc_html($deposit_wording); ?></strong></span>
																</td>
																<td class="text-end text-nowrap">
																	<span class="rezgo-item-deposit">
																		<strong><?php echo esc_html($site->formatCurrency($deposit_package_total)); ?></strong>
																	</span>
																</td>
															</tr> 

														<?php } ?>
													<?php } ?>

												<?php } // if ($last) ?>
										</table>
										<script>jQuery(function($){$('.rezgo-order-unavailable span').tooltip()});</script>
									</div>
								</div>
						</div> <!-- class="single-order-item for packages" -->

						<?php if ($last) {

							$cart_coms[(int) $package[0]->com]['uid'] = (int) $package[0]->uid; 
							$cart_coms[(int) $package[0]->com]['com'] = (int) $package[0]->com; 

							if($package[0]->cross->items) {
								$cross_sell = $site->getCrossSell($package[0]);
								$cross_text = $site->getCrossSellText($package[0]);
				
								if($cross_sell) { 
									$cart_coms[(int) $package[0]->com]['cross'] = TRUE; 
								
									foreach($cross_sell as $cross) { 
										$cross_ids[(int) $package[0]->com][] = (int) $cross->com;
									} 
								
								} else {
									$cart_coms[(int) $package[0]->com]['cross'] = FALSE; 
								}

								if ($cross_text->title != '') {
									$cross_btn = (string) $cross_text->title;
								} else {
									$cross_btn = 'View Similar Items';
								}

								if ((string) $item->availability_type == 'date') {
									$cross_sell_date = date("Y-m-d", (int) $item->booking_date); 
								} else {
									$cross_sell_date  = 'open'; 
								}

							?>
									
							<div class="rezgo-cross-order-package">
								<div class="rezgo-btn-cross-wrp">
									<button type="button" class="rezgo-btn-cross" id="rezgo-btn-cross-<?php echo esc_attr($package[0]->com); ?>" onclick="openCrossSell('<?php echo esc_js($package[0]->com); ?>', '<?php echo esc_js($package[0]->uid); ?>', '<?php echo esc_js($cross_sell_date); ?>')">
									<span><?php echo $cross_btn; ?></span>
									</button>
								</div>
							</div>
							
						<?php } ?>

						<hr>
						<?php $item_num++; $item_count++; $package_index++; } $index++; ?>

				<?php } else { ?>

					<?php	

						if (isset($item->uid) && $item->uid) {
							$cart_coms[(int) $item->com]['uid'] = (int) $item->uid; 
						}

						if (isset($item->com) && $item->com) {
							$cart_coms[(int) $item->com]['com'] = (int) $item->com; 
						}

						if (isset($item->availability_type) && $item->availability_type) {
							if ((string) $item->availability_type == 'date') {
								$cart_coms[(int) $item->com]['date'] = date("Y-m-d", (int) $item->booking_date); 
							} else {
								$cart_coms[(int) $item->com]['date'] = 'open'; 
							}
						}
						
						if(!empty($site->getCrossSell())) { 

							$cart_coms[(int) $item->com]['cross'] = TRUE; 
						
							foreach($site->getCrossSell() as $cross) { 
								$cross_ids[(int) $item->com][] = (int) $cross->com;
							} 
						
						} else {
						
							if (isset($item->com) && $item->com) {
								$cart_coms[(int) $item->com]['cross'] = FALSE; 
							}
							
						}
					?>


					<div class="rezgo-sub-title">
						<span> &nbsp; Booking <?php echo esc_html($item_count); ?> of <?php echo esc_html($cart_count); ?></span>
					</div>

					<div id="rezgo-order-item-<?php echo esc_attr($item->uid ?? ''); ?>" class="single-order-item">

						<div class="row rezgo-form-group rezgo-cart-title-wrp">
							<div class="col-9 rezgo-cart-title">
								<h3 class="rezgo-item-title">
									<?php $item_url = $site->base.'/details/'.$item->com.'/'.$site->seoEncode($item->item); ?>
									<a href="<?php echo esc_url($item_url); ?>">
										<span><?php echo esc_html($item->item); ?></span>
										<span> &mdash; <?php echo esc_html($item->option); ?></span>
									</a>
								</h3>

								<?php $data_book_date = date("Y-m-d", (int)$this->cart_data[$index]->date); ?>

								<?php if (in_array((string) $item->date_selection, DATE_TYPES)){ ?>
									<label>
										<span>Date: </span>
										<span class="lead"><?php echo esc_html(date((string) $company->date_format, (int) $item->booking_date)); ?></span>
									</label>
									<?php if (isset($item->time) && $site->exists($item->time)){ ?>
										<label>at <?php echo (string) esc_html($item->time); ?></label>
									<?php } ?> 
								<?php } else { ?>
									<label><span class="lead"> Open Availability </span></label>
								<?php } ?>

								<?php if ($item->discount_rules->rule) {
									echo '<br><label class="rezgo-booking-discount">
									<span class="rezgo-discount-span">Discount:</span> ';
									$discount_string = '';
									foreach($item->discount_rules->rule as $discount) {	
										$discount_string .= ($discount_string) ? ', '.$discount : $discount;
									}
									echo '<span class="rezgo-promo-code-desc">'.esc_html($discount_string).'</span>
									</label>';
								} ?>

								<div class="rezgo-order-memo rezgo-order-date-<?php echo esc_attr(date('Y-m-d', (int) $item->booking_date)); ?> rezgo-order-item-<?php echo esc_attr($item->uid); ?>"></div>
							</div>

							<div class="col-3 column-btns">
								<div class="rezgo-btn-cart-wrp">
									<span class="btn-check"></span>
									<button type="button" data-bs-toggle="collapse" class="btn btn-block rezgo-pax-edit-btn" data-index="<?php echo esc_attr($index); ?>" data-order-item="<?php echo esc_attr($item->uid); ?>" data-order-com="<?php echo esc_attr($item->com); ?>" data-cart-id="<?php echo esc_attr($item_num); ?>" data-book-date="<?php echo esc_attr($data_book_date); ?>" data-book-time="<?php echo esc_attr($item->time_format == 'dynamic' ? $item->time : ''); ?>" href="#pax-edit-<?php echo esc_attr($item_num); ?>">
										<span>Edit Guests</span>
									</button>
								</div>

								<div class="rezgo-btn-cart-wrp">
									<span class="btn-check"></span>
									<button type="button" class="btn rezgo-btn-remove btn-block" data-index="<?php echo esc_attr($index); ?>" data-date=<?php echo esc_attr($data_book_date); ?> data-order-item="<?php echo esc_attr($item->uid); ?>" data-com="<?php echo esc_attr($item->com); ?>" data-name="<?php echo esc_attr($item->item . ' - ' . $item->option); ?>" data-total="<?php echo esc_attr($item->overall_total); ?>" data-url="<?php echo esc_attr($site->base); ?>/order?edit[<?php echo $item->cartID; ?>][adult_num]=0">
										<span>Remove<span class='d-none d-sm-inline-block'><u> &nbsp;from Order </u></span></span>
									</button>
								</div>
							</div>
						</div>

						<div class="row rezgo-form-group rezgo-cart-table-wrp">

							<div class="collapse rezgo-pax-edit-box" id="pax-edit-<?php echo esc_attr($item_num); ?>"></div>
							<div id="pax-edit-scroll-<?php echo esc_attr($item_num); ?>" class="rezgo-cart-edit-wrp"></div>

							<div class="col-12">
								<table class="table rezgo-billing-cart table-responsive">
									<tr class="rezgo-tr-head">
										<td class="text-start rezgo-billing-type"><label>Type</label></td>
										<td class="text-start rezgo-billing-qty"><label class="d-none d-sm-block">Qty.</label></td>
										<td class="text-start rezgo-billing-cost"><label>Cost</label></td>
										<td class="text-end rezgo-billing-total"><label>Total</label></td>
									</tr>

									<?php foreach($site->getTourPrices($item) as $price) { ?>

										<?php if($item->{$price->name.'_num'}) { ?>
											<tr class="rezgo-tr-pax">
												<td class="text-start"><?php echo esc_html($price->label); ?></td>
												<td class="text-start" ><?php echo esc_html($item->{$price->name.'_num'}); ?></td>
												<td class="text-start">
													<?php
														$initial_price = isset($price->price) ? (float) $price->price : 0;
														$strike_price = isset($price->strike) ? (float) $price->strike : 0;
														$discount_price = isset($price->base) ? (float) $price->base : 0;
													?>
													<?php if ( ($site->exists($price->strike)) && (isset($price->base) && $site->exists($price->base)) )  { ?>
														<?php $show_this = max($strike_price, $discount_price); ?>

														<span class="discount">
															<?php echo esc_html( $site->formatCurrency($show_this)); ?>
														</span>

													<?php } else if($site->exists($price->strike)) { ?>

														<span class="discount">
															<!-- show only if strike price is higher -->
															<?php if ($strike_price >= $initial_price) { ?>
																<span class="rezgo-strike-price">
																	<?php echo esc_html( $site->formatCurrency($strike_price)); ?>
																</span>
															<?php } ?>
														</span>

													<?php } else if(isset($price->base) && $site->exists($price->base)) { ?>

														<span class="discount">
															<?php echo esc_html($site->formatCurrency($price->base)); ?>
														</span>

													<?php } ?>
														<?php echo esc_html($site->formatCurrency($price->price)); ?>
												</td>		
												<td class="text-end">
													<span>
														<?php echo esc_html($site->formatCurrency($price->total)); ?>
													</span>
												</td>
												
											</tr>
										<?php } ?>
									<?php } ?>

								<?php if ((int) $item->availability < (int) $item->pax_count) { ?>
									<tr class="rezgo-tr-order-unavailable">
										<td colspan="4" class="rezgo-order-unavailable">
											<span data-bs-toggle="tooltip" data-placement="top" title="This item has become unavailable after it was added to your order">
												<i class="fa fa-exclamation-triangle"></i>
												<span> No Longer Available</span>
											</span>
										</td>
									</tr>
								<?php } else { $cart_total += (float) $item->overall_total; } ?>

								<tr class="rezgo-tr-subtotal">
									<td colspan="3" class="text-end"><span class="push-right"><strong>Subtotal</strong></span></td>
									<td class="text-end text-nowrap"><?php echo esc_html($site->formatCurrency($item->sub_total)); ?></td>
								</tr>
					
								<?php $line_items = $site->getTourLineItems(); ?>

								<?php 	
									$pax_totals = array(
										'adult_num' => 'price_adult', 
										'child_num' => 'price_child', 
										'senior_num' => 'price_senior', 
										'price4_num' => 'price4', 
										'price5_num' => 'price5', 
										'price6_num' => 'price6', 
										'price7_num' => 'price7', 
										'price8_num' => 'price8', 
										'price9_num' => 'price9'
									);
								?>
					
								<?php foreach($line_items as $line) { ?>
								<?php $label_add = ''; ?>

								<?php if($site->exists($line->percent) || $site->exists($line->multi)) {
									$label_add = ' (';

									if($site->exists($line->percent)) {
										$label_add .= $line->percent.'%';
									}

									if($site->exists($line->multi)) {

										if(!$site->exists($line->percent)) {
											$label_add .= $site->formatCurrency($line->multi);
										}
												
											if($site->exists($line->meta)) {

												$line_pax = 0;
												foreach ($pax_totals as $p_num => $p_rate) {

													if ( (int) $item->{$p_num} > 0 && ((float) $item->date->{$p_rate} > (float) $line->meta)) {
														$line_pax += (int) $item->{$p_num};
													}

												}
												$label_add .= ' x '.$line_pax;

											} else {
												$label_add .= ' x '.$item->pax;

											}

									}

									$label_add .= ')';
								}
						
								?>
					
								<tr class="rezgo-tr-subtotal">
									<td colspan="3" class="text-end">
									<?php if ($line->source == 'bundle') { ?>
									<strong class="rezgo-line-bundle push-right"></i>&nbsp;<?php echo esc_html($line->label); ?><?php echo esc_html($label_add); ?> (Bundle)</strong></span>
									<?php } else { ?>
									<span class="push-right"><strong><?php echo esc_html($line->label); ?><?php echo esc_html($label_add); ?></strong></span>
									<?php } ?>
									</td>
									<td class="text-end text-nowrap"><?php echo esc_html($site->formatCurrency($line->amount)); ?></td>
								</tr>                  
									
								<?php } ?>

								<tr class="rezgo-tr-subtotal package-summary-total">
									<td colspan="3" class="text-end"><span class="push-right"><strong>Total</strong></span></td>
									<td class="text-end text-nowrap"><strong><?php echo esc_html($site->formatCurrency($item->overall_total)); ?></strong></td>
								</tr>

									<?php if($site->exists($item->deposit)) { ?>
										<tr class="rezgo-tr-deposit">
											<td colspan="3" class="text-end">
												<span class="push-right"><strong>Deposit to Pay Now</strong></span>
											</td>
											<td class="text-end text-nowrap">
												<span class="rezgo-item-deposit" id="deposit_value_<?php echo esc_attr($c); ?>" rel="<?php echo esc_attr($item->deposit_value); ?>">
													<strong><?php echo esc_html($site->formatCurrency($item->deposit_value)); ?></strong>
												</span>
											</td>
										</tr>

										<?php $complete_booking_total += (float) $item->deposit_value; ?>
							
									<?php } else { ?>
							
										<?php $complete_booking_total += (float) $item->overall_total; ?>
							
									<?php } ?>
							</table>

								<?php if($site->getTourRelated()) { ?>

									<?php $related_items_arr = $site->getTourRelated(); ?>
									
									<div class="rezgo-related">
										<div class="rezgo-related-label"><span>Related products</span></div>
										<?php foreach($related_items_arr as $related) { ?>
											<?php $related_link = $site->base.'/details/'.$related->com.'/'.$site->seoEncode($related->name); ?>
											<a href="<?php echo esc_url($related_link); ?>" class="rezgo-related-link" onclick="select_item_<?php echo $related->com; ?>();"><?php echo esc_html($related->name); ?></a>
											
											<br />

											<script>
												function select_item_<?php echo $related->com; ?>(){
													<?php if ($analytics_ga4) { ?>
														// gtag select_item
														gtag("event", "select_item", {
															item_list_name: "Checkout Page",
															items: [
																{
																	item_id: "<?php echo $related->com; ?>",
																	item_name: "<?php echo $related->name; ?>",
																	currency: "<?php echo esc_html($booking_currency); ?>",
																	price: <?php echo $site->exists($related->starting) ? $related->starting : 0; ?>,
																	quantity: 1
																}
															]
														});
													<?php } ?>

													<?php if ($analytics_gtm) { ?>
														// tag manager select_item
														dataLayer.push({ ecommerce: null });  // Clear the previous ecommerce object.
														dataLayer.push({
														event: "select_item",
														item_list_name: "Checkout Page",
														ecommerce: {
															items: [
																{
																	item_id: "<?php echo $related->com; ?>",
																	item_name: "<?php echo $related->name; ?>",
																	currency: "<?php echo esc_html($booking_currency); ?>",
																	price: <?php echo $site->exists($related->starting) ? $related->starting : 0; ?>,
																	quantity: 1
																}
															]
														}
														});
													<?php } ?>

													<?php if ($meta_pixel) { ?>
															// meta_pixel custom event SelectItem
															fbq('track', 'SelectItem', { 
																	item_list_name: "Checkout Page",
																	contents: [
																		{
																			'id': "<?php echo $related->com; ?>",
																			'name': "<?php echo $related->name; ?>",
																			'price': <?php echo $site->exists($related->starting) ? $related->starting : 0; ?>,
																			'quantity': 1,
																		}
																	]
																}
															)
													<?php } ?>

													}
											</script>
										<?php } ?>
									</div>
					
								<?php } ?>

						<?php  
							if ($site->getCrossSell()) {

								$cross_sell = $site->getCrossSell();
								$cross_text = $site->getCrossSellText();
								$availability_type = $item->availability_type ?? '';

								if ($cross_text->title != '') {
									$cross_btn = (string) $cross_text->title;
								} else {
									$cross_btn = 'View Similar Items';
								}

								if ((string) $availability_type == 'date') {
									$cross_sell_date = date("Y-m-d", (int) $item->booking_date); 
								} else {
									$cross_sell_date  = 'open'; 
								}

						?>

							<div class="<?php echo (!$site->getTourRelated() ? '' : ''); ?> rezgo-cross-order">
								<div class="rezgo-btn-cross-wrp">
									<button type="button" class="rezgo-btn-cross" id="rezgo-btn-cross-<?php echo esc_attr($item->com); ?>" onclick="openCrossSell('<?php echo esc_js($item->com); ?>', '<?php echo esc_js($item->uid); ?>', '<?php echo esc_js($cross_sell_date); ?>')">
									<span><?php echo esc_html($cross_btn); ?></span>
									</button>
								</div>
							</div>
				
						<?php } ?>

							</div>
						</div>

					</div> <!-- // single-order-item -->

					<hr>
					<?php $item_num++; $item_count++; $index++; } ?>

				<?php  
				$pax_count = $item->pax_count ?? 0;
			    $contents[]['id'] = (int) $item->uid;
				$contents[]['quantity'] = (int) $pax_count;  } //foreach ($cart as $item) ?>

			<?php } // end if(!$cart) ?>  
				<?php
					// cart loop is done ... check for cross-sell items
					if (!empty($cross_ids)) {
						foreach ($cross_ids as $c_com => $c_array) {
							foreach ($c_array as $c_id) {
								if (array_key_exists($c_com, $cart_coms) && array_key_exists($c_id, $cart_coms)) {
									$cart_coms[$c_com]['cross'] = FALSE; 
								}
							}
						}
					}
					
					if (!empty($cart_coms)) {
						foreach ($cart_coms as $cart_id) {
							$cart_id_cookie = $_COOKIE['cross_'.$cart_id['com']] ?? '';
							$cart_cross_id = $cart_id['cross'] ?? '';
							$cart_id_date = $cart_id['date'] ?? '';
							if ($cart_cross_id == TRUE && $cart_id_cookie != 'shown') {
								echo '<script> 
									jQuery(document).ready(function($) { 
										if(getCookie("cross_'.esc_html($cart_id['com']).'") != "shown"){
											openCrossSell("'.esc_html($cart_id['com']).'", "'.esc_html($cart_id['uid']).'", "'.esc_html($cart_id_date).'"); 
										}
									}); 
								</script>';
								break; // only execute one cross-sell at a time
							}
						}
					}
				
				?>

			</div> <!-- // order-summary -->
			
			<?php if($cart) { ?>
				<!-- FIXED CART -->
				<?php require('fixed_cart.php'); ?>
			<?php } ?> 

		</div><!-- // flex-container -->

		<?php if(!empty($cart)) { ?>

			<?php $trigger_code = $site->cart_trigger_code ?>
			<?php if ( (!isset($trigger_code)) || ($trigger_code == '') ) { ?>

				<div id="rezgo-order-promo-code-wrp__mobile" class="row rezgo-order-promo-code-wrp__mobile rezgo-form-group-short">
					
					<form class="rezgo-promo-form__mobile" id="rezgo-promo-form__mobile" role="form">

							<span id="rezgo-promo-form-memo"></span>

						<div class="input-group <?php echo esc_attr($hidden); ?>">
							<input type="text" class="form-control" id="rezgo-promo-code__mobile" name="promo" aria-label="Enter Promo Code" placeholder="Enter Promo Code" value="<?php echo (isset($trigger_code) ? esc_html($trigger_code) : '')?>" required>

							<div class="input-group-btn">
								<span class="btn-check"></span>
								<button class="btn rezgo-btn-default apply-promo-btn" target="_parent" type="submit">
									<span>Apply</span>
								</button>
							</div>
						</div>

					</form>
								
				<?php } else { ?>

				<div id="rezgo-order-promo-code-wrp__mobile" class="applied row rezgo-order-promo-code-wrp__mobile rezgo-form-group-short">

					<span class="rezgo-booking-discount <?php echo esc_attr($disabled); ?> rezgo-promo-label">
						<span class="rezgo-discount-span">Promo applied:</span> 
							<span id="rezgo-promo-value__mobile">
								<?php echo esc_html($trigger_code); ?>
							</span>
							
							<?php if (REZGO_WORDPRESS) { ?>
							<a id="rezgo-promo-clear__mobile" style="color:#333;" class="btn <?php echo esc_attr($hidden); ?>" href="<?php echo esc_url($_SERVER['HTTP_REFERER']); ?>/?promo=" target="_top"><i class="fa fa-times"></i></a>
							<?php } ?>

							<?php if (REZGO_LITE_CONTAINER) { ?>
								<button id="rezgo-promo-clear__mobile" class="btn <?php echo $hidden; ?>" onclick="<?php echo LOCATION_WINDOW; ?>.location.href='<?php echo $site->base; ?>/order/?promo='"><i class="fa fa-times"></i></button>
							<?php } elseif(!REZGO_WORDPRESS) { ?>
								<button id="rezgo-promo-clear__mobile" class="btn <?php echo $hidden; ?>" onclick="<?php echo LOCATION_HREF; ?>='/order/?promo='" target="_parent"><i class="fa fa-times"></i></button>
							<?php } ?> 
						<hr>
					</span>
									
				<?php } ?>
				</div> <!-- // rezgo-order-promo-code-wrp__mobile -->

			<div id="rezgo-bottom-cta">
				<span id="rezgo-booking-btn">
					<a href="<?php echo esc_attr($site->base); ?>" id="rezgo-order-book-more-btn" class="btn btn-lg btn-block">
						<span>Book More</span>
					</a>
				</span>

				<span class="btn-check"></span>
				<a href="<?php echo esc_attr($site->base); ?>/book<?php echo (REZGO_CUSTOM_DOMAIN) ? '/'.esc_attr($site->cart_token).'?custom_domain=1' : ''; ?>" class="btn rezgo-btn-book btn-lg btn-block rezgo-order-step-btn-bottom" >
					<span>Check Out</span>
				</a>

				<script>
					jQuery(function($) {
						$('#rezgo-promo-form__mobile').submit( function(e){
							e.preventDefault();

							$('#rezgo-promo-form').ajaxSubmit({
								type: 'POST',
								url: '<?php echo admin_url('admin-ajax.php'); ?>' + '?action=rezgo&method=book_ajax',
								data: { rezgoAction: 'update_promo' },
								success: function(data){

									<?php if (REZGO_WORDPRESS) { ?>
										<?php echo LOCATION_HREF; ?> = <?php echo LOCATION_WINDOW; ?>.location.pathname + '?promo=' + $('#rezgo-promo-code__mobile').val();
									<?php } ?>
									
									<?php if (REZGO_LITE_CONTAINER) { ?>
										<?php echo LOCATION_WINDOW; ?>.location.href='<?php echo $site->base; ?><?php echo $promo_url; ?>/order?promo=' + $('#rezgo-promo-code__mobile').val();
									<?php } elseif (!REZGO_WORDPRESS) { ?>
										<?php echo LOCATION_HREF; ?>='/order?promo=' + $('#rezgo-promo-code__mobile').val();
									<?php } ?>
								}
							})
						});

						$('#rezgo-promo-clear__mobile').click(function(){
							$.ajax({
								type: 'POST',
								url: '<?php echo admin_url('admin-ajax.php'); ?>' + '?action=rezgo&method=book_ajax',
								data: { rezgoAction: 'update_promo' },
							})
						});
					});
				</script>

			</div>
		<?php } ?>
	
	</div> <!-- // Jumbotron -->
</div><!-- // rezgo-container -->

<script>

function openCrossSell(com, id, date) {
	
<?php if (REZGO_WORDPRESS) { ?>
	var
	rezgoModalTitle = 'Return Trip',
	wp_slug = '<?php echo esc_html($_REQUEST['wp_slug']); ?>',
	parent_url = '<?php echo esc_html($site->base); ?>',
	query = '<?php echo home_url() . $site->base; ?>?rezgo=1&mode=return_trip&com=' + com + '&id=' + id + '&date=' + date + '&wp_slug='+ wp_slug+ '&headless=1&hide_footer=1&cross_sell=1&parent_url='+parent_url;
<?php } else { ?>
	var
	query = '/return?com=' + com + '&id=' + id + '&date=' + date + '&headless=1&hide_footer=1&cross_sell=1';
<?php } ?>

	<?php echo LOCATION_WINDOW; ?>.jQuery('#rezgo-modal-loader').css({'display':'block'});
	<?php echo LOCATION_WINDOW; ?>.jQuery('#rezgo-modal-iframe').attr('src', query).attr('height', '90%');
	<?php echo LOCATION_WINDOW; ?>.jQuery('#rezgo-modal-iframe').attr('title', 'Cross Sell Modal');
	<?php echo LOCATION_WINDOW; ?>.rezgoModal.show();
	
	//scroll to modal
	<?php if(REZGO_LITE_CONTAINER){ ?>
		window.scrollTo(0,0);
	<?php } ?>
	}

	// set secure flag on cookie based on connection
	<?php $secured = $site->checkSecure() ?? 0; ?>
	<?php $secure = $secured ? 'secure;" + "SameSite=None' : ''; ?> 

function setCookie(cname, cvalue, exdays) {
	var d = new Date();
	d.setTime(d.getTime() + (exdays*24*60*60*1000));
	var expires = "expires="+ d.toUTCString();
	document.cookie = cname + "=" + cvalue + ";" + expires + ";domain=.<?php echo $_SERVER['SERVER_NAME']; ?>;path=/;" + "<?php echo $secure; ?>";
}

function getCookie(cname) {
	var name = cname + "=";
	var decodedCookie = decodeURIComponent(document.cookie);
	var ca = decodedCookie.split(';');
	for(var i = 0; i <ca.length; i++) {
		var c = ca[i];
		while (c.charAt(0) == ' ') {
			c = c.substring(1);
		}
		if (c.indexOf(name) == 0) {
			return c.substring(name.length, c.length);
		}
	}
	return "";
}

function deleteCookie(cname) {
	document.cookie = cname + "=;Thu, 01 Jan 1970 00:00:00 UTC;domain=.<?php echo $_SERVER['SERVER_NAME']; ?>;path=/;" + "<?php echo $secure; ?>";
}

jQuery(function($){

	// switch up the btn/promo form ids 
	window.onload = (event) => {
		let width = this.innerWidth;
		if (width < 992){
			$(".rezgo-order-step-btn-bottom").prop("id", "rezgo-btn-book");
			$(".rezgo-order-step-btn-side").prop("id", "");

			$(".rezgo-promo-form__mobile").prop("id", "rezgo-promo-form");
			$(".rezgo-promo-form").prop("id", "");
		} else {
			$(".rezgo-order-step-btn-side").prop("id", "rezgo-btn-book");
			$(".rezgo-order-step-btn-bottom").prop("id", "");

			$(".rezgo-promo-form").prop("id", "rezgo-promo-form");
			$(".rezgo-promo-form__mobile").prop("id", "");
		}
	};
	$(window).resize(function() {
		let width = this.innerWidth;
		if (width < 992){
			$(".rezgo-order-step-btn-bottom").prop("id", "rezgo-btn-book");
			$(".rezgo-order-step-btn-side").prop("id", "");

			$(".rezgo-promo-form__mobile").prop("id", "rezgo-promo-form");
			$(".rezgo-promo-form").prop("id", "");

		} else {
			$(".rezgo-order-step-btn-side").prop("id", "rezgo-btn-book");
			$(".rezgo-order-step-btn-bottom").prop("id", "");

			$(".rezgo-promo-form").prop("id", "rezgo-promo-form");
			$(".rezgo-promo-form__mobile").prop("id", "");
		}

	});

	let checkout_btn = document.querySelector('#rezgo-btn-book');
	let checkout_btn_bottom = document.querySelector('.rezgo-order-step-btn-bottom');

	function begin_checkout(){

		$.ajax({
			url: '<?php echo admin_url('admin-ajax.php'); ?>' + '?action=rezgo&method=book_ajax',
			data: { rezgoAction: 'initiate_checkout'},
			success: function(data){
			},
			error: function(error){
			}
		});

		<?php if ($analytics_ga4) { ?>
			// gtag begin_checkout
			gtag("event", "begin_checkout", {
				currency: "<?php echo esc_html($company->currency_base); ?>",
				value: document.querySelector('#total_value').dataset.total,
				coupon: "<?php echo isset($_COOKIE['rezgo_promo']) ? esc_html($_COOKIE['rezgo_promo']) : esc_html($site->cart_trigger_code); ?>",
				items: [
				<?php 
				if ($cart) {
					$tag_index = 1;
					foreach ($cart as $item){ ?>
						{
							item_id: "<?php echo esc_html($item->uid); ?>",
							item_name: "<?php echo esc_html($item->item . ' - ' . $item->option); ?>",
							currency: "<?php echo esc_html($company->currency_base); ?>",
							coupon: "<?php echo isset($_COOKIE['rezgo_promo']) ? esc_html($_COOKIE['rezgo_promo']) : esc_html($site->cart_trigger_code); ?>",
							price: <?php echo esc_html((float)$item->overall_total); ?>,
							quantity: 1,
							index: <?php echo esc_html($tag_index++); ?>,
						},
					<?php } unset($tag_index); ?>
				<?php } ?>
				]
			});
		<?php } ?>
			
		<?php if ($analytics_gtm) { ?>				
			// tag manager begin_checkout
			dataLayer.push({ ecommerce: null });  // Clear the previous ecommerce object.
			dataLayer.push({
			event: "begin_checkout",
			ecommerce: {
				items: [
				<?php 
				if ($cart) {
					$tag_index = 1;
					foreach ($cart as $item){ ?>
						{
							item_id: "<?php echo esc_html($item->uid); ?>",
							item_name: "<?php echo esc_html($item->item . ' - ' . $item->option); ?>",
							currency: "<?php echo esc_html($booking_currency); ?>",
							coupon: "<?php echo isset($_COOKIE['rezgo_promo']) ? esc_html($_COOKIE['rezgo_promo']) : esc_html($site->cart_trigger_code); ?>",
							price: <?php echo esc_html((float)$item->overall_total); ?>,
							quantity: 1,
							index: <?php echo esc_html($tag_index++); ?>,
						},
					<?php } unset($tag_index); ?>
				<?php } ?>
				]
			}
			});
		<?php } ?>

		<?php if ($meta_pixel) { ?>
			fbq('track', 'InitiateCheckout', { 
				currency: "<?php echo esc_html($company->currency_base); ?>",
				value: document.querySelector('#total_value').dataset.total,
				contents :
				[
					<?php
					if ($cart) {
					foreach ($cart as $item){ ?>
					{
						'id': "<?php echo esc_html($item->uid); ?>",
						'name': "<?php echo esc_html($item->item . ' - ' . $item->option); ?>",
						'quantity': 1,
						'price': <?php echo esc_html((float)$item->overall_total); ?>,
					},
					<?php } unset($tag_index); ?>
				<?php } ?>
				],
				}
			);
		<?php } ?>

		<?php echo LOCATION_WINDOW; ?>.location.href='<?php echo $site->base; ?>/book';
	}

	if (checkout_btn) {
		checkout_btn.addEventListener('click', function(){
			begin_checkout();
		});

		checkout_btn_bottom.addEventListener('click', function(){
			begin_checkout();
		});
	}

	$('.rezgo-cart-count').text('<?php echo $cart_count ?? ''; ?>');
	$('#rezgo-btn-book').click(function() {

		<?php if (REZGO_LITE_CONTAINER){ ?>
			window.parent.scrollTo(0,0);
		<?php } ?>
	});   
		

});

jQuery(document).ready(function($) {
	$('.rezgo-pax-edit-btn').each(function() {
		let order_com = $(this).attr('data-order-com'); 
		let order_item = $(this).attr('data-order-item');
		let cart_id = $(this).attr('data-cart-id'); 
		let book_date = $(this).attr('data-book-date'); 
		let book_time = $(this).attr('data-book-time'); 
		let package_id = $(this).attr('data-package-id') ? $(this).attr('data-package-id') : ''; 
		let index = $(this).attr('data-index'); 
		let cart_package_uid = $(this).attr('data-cart-package-uid') ? $(this).attr('data-cart-package-uid') : ''; 
		let security = '<?php echo wp_create_nonce('rezgo-nonce'); ?>';
		let method	= 'edit_pax.php?';
				method += 'com='		+ order_com;
				method += '&id='		+ order_item;
				method += '&order_id='	+ cart_id;
				method += '&date='		+ book_date;
				method += '&book_time='		+ book_time;
				method += '&package_id='+ package_id;
				method += '&index='		+ index;
				method += '&cart_package_uid='+ cart_package_uid;
				method += '&parent_url=<?php echo esc_html($site->base); ?>';

		jQuery.ajax({
			url: '<?php echo admin_url('admin-ajax.php'); ?>',
			data: {
				action: 'rezgo',
				method: method,
				security: security
			},
			context: document.body,
			success: function(data) {
				$('#pax-edit-' + cart_id).html(data);
			}
		});
	});	

		$('.rezgo-pax-edit-btn').click(function() {
			$(this).find('span').html() == "Edit Guests" ? $(this).find('span').html('Cancel') : $(this).find('span').html('Edit Guests');

			let cart_id = $(this).attr('data-cart-id'); 
			let pax_edit_position = $('#pax-edit-scroll-' + cart_id).position();
			let pax_edit_box = $('.rezgo-pax-edit-box');
		});

		$('.rezgo-btn-remove').click(function() {

			<?php if (!REZGO_LITE_CONTAINER) { ?>
				localStorage.clear();
			<?php } ?>

			let com = $(this).data('com');
			let url = $(this).data('url');

			if ( getCookie('cross_' + com ) != '') {
				deleteCookie( 'cross_' + com );
			}

			let index = $(this).data('index');
			let item_id = $(this).data('order-item');
			let date = $(this).data('date');
			let cart_package_uid = $(this).data('cart-package-uid') ? $(this).data('cart-package-uid') : '';
			let item_name =  $(this).data('name');
			let item_total =  $(this).data('total');

			<?php if ($analytics_ga4) { ?>
			let new_ga4_package_arr = [];
			let new_ga4_package_total = 0;
			Object.entries(ga4_package_details).forEach(entry => {
				const [key, item] = entry;
				if (key.includes(cart_package_uid)){
					new_ga4_package_arr.push(
						{
							item_id: item.id,
							item_name: item.name,
							currency: item.currency,
							coupon: item.coupon,
							index: item.index,
							price: item.price,
							quantity: 1,
						}
					)
					new_ga4_package_total += item.price;
				}
			})
			<?php } ?>

			<?php if ($analytics_gtm) { ?>
			let new_gtm_package_arr = [];
			Object.entries(gtm_package_details).forEach(entry => {
				const [key, item] = entry;
				if (key.includes(cart_package_uid)){
					new_gtm_package_arr.push(
						{
							item_id: item.id,
							item_name: item.name,
							currency: item.currency,
							coupon: item.coupon,
							index: item.index,
							price: item.price,
							quantity: 1,
						}
					)
				}
			})
			<?php } ?>

			<?php if ($meta_pixel) { ?>
			let new_pixel_package_arr = [];
			Object.entries(gtm_package_details).forEach(entry => {
				const [key, item] = entry;
				if (key.includes(cart_package_uid)){
					new_pixel_package_arr.push(
						{
							'id': item.id,
							'name': item.name,
							'currency': item.currency,
							'coupon': item.coupon,
							'price': item.price,
							'quantity': 1,
						}
					)
				}
			})
			<?php } ?>
			
			$.ajax({
				type: 'POST',
				url: '<?php echo admin_url('admin-ajax.php'); ?>' + '?action=rezgo&method=book_ajax',
				data: { rezgoAction: 'remove_item',
						index : index,
						item_id : item_id,
						date : date,
						cart_package_uid : cart_package_uid ? cart_package_uid : '',	
					  },
				success: function(data){
					<?php if (!DEBUG){ ?>

						<?php if ($analytics_ga4) { ?>
							// gtag remove_from_cart
							if (cart_package_uid) {
								gtag("event", "remove_from_cart", {
									currency: "<?php echo esc_html($booking_currency); ?>",
									value: new_ga4_package_total,
									items: new_ga4_package_arr,
								});
							} else {
								gtag("event", "remove_from_cart", {
									currency: "<?php echo esc_html($booking_currency); ?>",
									value: item_total,
									items: [
									{
									item_id: item_id,
									item_name: item_name,
									currency: "<?php echo esc_html($booking_currency); ?>",
									coupon: "<?php echo isset($_COOKIE['rezgo_promo']) ? esc_html($_COOKIE['rezgo_promo']) : esc_html($site->cart_trigger_code); ?>",
									index: 1,
									price: item_total,
									quantity: 1
									}
								]
							});
							}
						<?php } ?>

						<?php if ($analytics_gtm) { ?>	
							// tag manager remove_from_cart
							if (cart_package_uid) {
								dataLayer.push({ ecommerce: null });  // Clear the previous ecommerce object.
								dataLayer.push({
								event: "remove_from_cart",
								ecommerce: {
									items: new_gtm_package_arr,
								}
								});
							} else {
							dataLayer.push({ ecommerce: null });  // Clear the previous ecommerce object.
							dataLayer.push({
							event: "remove_from_cart",
							ecommerce: {
								items: [
								{
								item_id: item_id,
								item_name: item_name,
								coupon: "<?php echo isset($_COOKIE['rezgo_promo']) ? esc_html($_COOKIE['rezgo_promo']) : esc_html($site->cart_trigger_code); ?>",
								currency: "<?php echo esc_html($booking_currency); ?>",
								index: 1,
								price: item_total,
								quantity: 1
								}
								]
							}
							});
							}
						<?php } ?>

						<?php if ($meta_pixel) { ?>	
							// meta_pixel custom event RemoveFromCart
							if (cart_package_uid) {
								fbq('track', 'RemoveFromCart', { 
										contents: new_pixel_package_arr
									}
								)
							} else {
								fbq('track', 'RemoveFromCart', { 
										contents: [
											{
											'id': item_id,
											'name': item_name,
											'coupon': "<?php echo isset($_COOKIE['rezgo_promo']) ? esc_html($_COOKIE['rezgo_promo']) : esc_html($site->cart_trigger_code); ?>",
											'currency': "<?php echo esc_html($booking_currency); ?>",
											'price': item_total,
											'quantity': 1
											}
										]
									}
								)
							}
						<?php } ?>

						
						<?php if (REZGO_WORDPRESS) { ?> 
							window.top.location.reload();
						<?php } else { ?>
							<?php echo LOCATION_WINDOW; ?>.location.href='<?php echo $site->base; ?>/order<?php echo ($site->cart_trigger_code) ? '?promo='.$site->cart_trigger_code :''; ?>';
						<?php } ?>			

						<?php } else { ?>
						alert(item_id + ' - ' + date + ' removed');
					<?php } ?>
				},
				error: function(error){
					console.log(error);
				}
			});
		});

		$('#rezgo-cross-dismiss', parent.document).click(function() {
			var com = $(this).attr('rel');
			setCookie('cross_' + com, 'shown', 2); 
		});

	});
</script>

<style>#debug_response {width:100%; height:200px;}</style>