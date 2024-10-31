<?php
$company = $site->getCompanyDetails();

// non-open date date_selection elements
$date_types = array('always', 'range', 'week', 'days', 'single'); // centralize this?
$site->setTimeZone();

$analytics_ga4 = $site->exists($site->getAnalyticsGa4());
$analytics_gtm = $site->exists($site->getAnalyticsGtm());
?>

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
		<?php if($_SESSION['cart_status']) $cart_status =  new SimpleXMLElement($_SESSION['cart_status']); ?>

		<?php if ($cart_status){ 
			// clear promo if there is an invalid promo
			if (($cart_status->error_code == 9) || ($cart_status->error_code == 11)) $site->resetPromoCode(); ?>

			<div id="rezgo-order-error-message">

				<!-- Top level error message -->
				<span class="message">
					<?php echo esc_html($cart_status->message); ?>

					<?php // list items removed
						foreach ($cart_status->removed->item as $removed_item){
							$tour = $site->getTours('t=uid&q='.$removed_item->id); ?>
							<br>
							<?php echo esc_html($tour[0]->item); ?> - <?php echo esc_html($tour[0]->option); ?> (<?php echo esc_html(date((string) $company->date_format, (string) $removed_item->date)); ?>)

						has been removed from your cart
					<?php } ?>

				</span>
				<a href="#" id="rezgo-error-dismiss" class="btn"><span>close</span></i></a>
			</div>

			<script>

				// dismiss error when user navigates away or manually closes it
				function dismissError(){
					jQuery.ajax({
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

				jQuery('#rezgo-error-dismiss').click(function(){
					dismissError();
				});

				setTimeout(() => {
					dismissError();
				}, 3000);

				window.onbeforeunload = dismissError();

			</script>
		<?php } ?>

		<?php if (!$cart) { ?>
			<div class="rezgo-order-empty-cart-wrp">
				<div class="rezgo-form-group cart_empty">
					<p class="lead">
						<span>There are no items in your order.</span>
					</p>
				</div>

				<div class="row" id="rezgo-booking-btn">
					<div class="col-md-4 col-xs-12 rezgo-btn-wrp">
						<a id="rezgo-order-book-more-btn" href="<?php echo esc_url($site->base); ?>" class="btn rezgo-btn-default btn-lg btn-block">
							<span>Book More</span>
						</a>
					</div>
				</div>
			</div>
		<?php } else {
			$item_num = 0; 
			$index = 0;
			$item_count = 1;

			$contents = array(); 
			$cart_coms = array(); 
			$cross_ids = array();  

			$non_package_items = array();
			$cart_package_uids = array();

			foreach ($cart as $item) {
				if ($site->exists($item->package)) {
					$cart_package_uids[] .= $item->cart_package_uid; 
				} else {
					$non_package_items[] = $item; 
				}
			} unset($item);

			$unique_package_uids = array_unique($cart_package_uids);
			$cart_count = (int)count($unique_package_uids) + (int)count($non_package_items);
		?>

		<div class="flex-container order-page-container">
			<div class="order-summary">

				<?php foreach ($cart as $item) { 
					$site->readItem($item); ?>

					<?php	
					if ($site->exists($item->package_item_total)){
						$first = (int)$item->package_item_index === 1 ? 1 : '';
						$last = (int)$item->package_item_index === (int)$item->package_item_total ? 1 : ''; 
						$package_id = (int)$item->package; 
						$cart_package_uid = (int)$item->cart_package_uid; 
						$package = $site->getTours('t=com&q='.$item->package); ?>

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
								<div class="col-xs-9 rezgo-cart-title">
									<h3 class="rezgo-item-title">
										<?php $item_url = $site->base.'/details/'.$item->com.'/'.$site->seoEncode($item->item); ?>
										<a href="<?php echo esc_url($item_url); ?>">
											<span><?php echo esc_html($item->item); ?></span>
											<span> &mdash; <?php echo esc_html($item->option); ?></span>
										</a>
									</h3>

									<?php $data_book_date = date("Y-m-d", (int)$this->cart_data[$index]->date); ?>

									<?php if (in_array((string) $item->date_selection, $date_types)){ ?>
										<label>
											<span>Date: </span>
											<span class="lead"><?php echo esc_html(date((string) $company->date_format, (int) $item->booking_date)); ?></span>
										</label>
										<?php if ($site->exists($item->time)){ ?>
											<label>at <?php echo (string) esc_html($item->time); ?></label>
										<?php } ?> 
									<?php } else { ?>
										<label><span class="lead"> Open Availability </span></label>
									<?php } ?>

									<?php if ($item->discount_rules->rule) {
										echo '<br><label class="rezgo-booking-discount">
										<span class="rezgo-discount-span">Discount:</span> ';
										unset($discount_string);
										foreach($item->discount_rules->rule as $discount) {	
											$discount_string .= ($discount_string) ? ', '.$discount : $discount;
										}
										echo '<span class="rezgo-promo-code-desc">'.esc_html($discount_string).'</span>
										</label>';
									} ?>

									<div class="rezgo-order-memo rezgo-order-date-<?php echo esc_attr($data_book_date); ?> rezgo-order-item-<?php echo esc_attr($item->uid); ?>"></div>
								</div>

								<?php if ($first){ ?>
									<div class="col-xs-3 column-btns package-column-btns">
										<div class="col-sm-12 rezgo-btn-cart-wrp">
											<button type="button" data-toggle="collapse" class="btn btn-block rezgo-pax-edit-btn" data-index="<?php echo esc_attr($index); ?>" data-order-item="<?php echo esc_attr($item->uid); ?>" data-order-com="<?php echo esc_attr($item->com); ?>" data-cart-id="<?php echo esc_attr($item_num); ?>" data-book-date="<?php echo esc_attr($data_book_date); ?>" data-book-time="<?php echo $item->time_format == 'dynamic' ? esc_attr($item->time) : ''; ?>" data-package-id="<?php echo esc_attr($package_id); ?>" data-cart-package-uid="<?php echo esc_attr($cart_package_uid); ?>" data-target="#pax-edit-<?php echo esc_attr($item_num); ?>">
												<span>Edit Guests</span>
											</button>
										</div>

										<div class="col-sm-12 rezgo-btn-cart-wrp">
											<button type="button" class="btn rezgo-btn-remove btn-block" data-index="<?php echo esc_attr($index); ?>" data-date=<?php echo esc_attr($data_book_date); ?> data-order-item="<?php echo esc_attr($item->uid); ?>" data-com="<?php echo esc_attr($package[0]->com); ?>" data-name="<?php echo esc_attr($item->item . ' - ' . $item->option); ?>" data-total="<?php echo esc_attr($item->overall_total); ?>" data-url="<?php echo esc_attr($site->base); ?>/order?edit[<?php echo esc_html($item->cartID); ?>][adult_num]=0" data-cart-package-uid="<?php echo esc_attr($cart_package_uid); ?>">
												<span>Remove<span class='hidden-xs'> from Order</span></span>
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
									
									<div class="col-xs-12">
										<table class="table rezgo-billing-cart table-responsive">
											<tr class="rezgo-tr-head">
												<td class="text-left rezgo-billing-type"><label>Type</label></td>
												<td class="text-left rezgo-billing-qty"><label class="hidden-xs">Qty.</label></td>
												<td class="text-left rezgo-billing-cost"><label>Cost</label></td>
												<td class="text-right rezgo-billing-total"><label>Total</label></td>
											</tr>
			
											<?php 
												// gather package price points
												foreach ($package[0]->prices->price as $package_price_point) { 
													$package_price_id = (int) $package_price_point->id; ?>

													<span id="package-label-<?php echo esc_attr($package_id.'-'.$cart_package_uid.'-main-'.$package_price_id); ?>" class="hidden"><?php echo (string) esc_html($package_price_point->label); ?></span>

													<script>
														// replace subsequent labels in package items with package price labels
														setTimeout(() => {
															jQuery('.package-label-<?php echo esc_html($package_id.'-'.$cart_package_uid.'-sub-'.$package_price_id); ?>').text(jQuery('#package-label-<?php echo esc_html($package_id.'-'.$cart_package_uid.'-main-'.$package_price_id); ?>').text());
														}, 150);
													</script>

												<?php }
													
												foreach($site->getTourPrices($item) as $price) { ?>

												<?php if($item->{$price->name.'_num'}) { ?>

													<?php $price_name = $cart_package_uid.'_'.$price->name; ?>

													<tr class="rezgo-tr-pax">
														<td class="text-left package-label-<?php echo esc_attr($package_id.'-'.$cart_package_uid.'-sub-'.$price->id); ?>"><?php echo esc_html($price->label); ?></td>
														<td class="text-left"><?php echo esc_html($item->{$price->name.'_num'}); ?></td>
														<td class="text-left package-pax-price">
															<?php
																$initial_price = (float) $price->price;
																$strike_price = (float) $price->strike;
																$discount_price = (float) $price->base;
															?>
															<?php if ( ($site->exists($price->strike)) && ($site->exists($price->base)) )  { ?>
																<?php $show_this = max($strike_price, $discount_price); ?>

																<span class="discount">
																	<?php echo esc_html($site->formatCurrency($show_this)); ?>
																</span>

															<?php } else if(!$site->isVendor() && $site->exists($price->strike)) { ?>

																<span class="discount">
																	<!-- show only if strike price is higher -->
																	<?php if ($strike_price >= $initial_price) { ?>
																		<span class="rezgo-strike-price">
																			<?php echo esc_html($site->formatCurrency($strike_price)); ?>
																		</span>
																	<?php } ?>
																</span>

															<?php } else if($site->exists($price->base)) { ?>

																<span class="discount">
																	<?php echo esc_html($site->formatCurrency($price->base)); ?>
																</span>

															<?php } ?>
																<?php echo esc_html($site->formatCurrency($price->price)); ?>
														</td>		
														<td class="text-right package-pax-total">
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
														<span data-toggle="tooltip" data-placement="top" title="This item has become unavailable after it was added to your order">
															<i class="fa fa-exclamation-triangle"></i>
															<span> No Longer Available</span>
														</span>
													</td>
												</tr>
											<?php } else { $cart_total += (float) $package_overall_total; } ?>

												<?php 
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
											<?php if ($line_items[$cart_package_uid]['item_'.$item->uid.$index]) { ?>
												
												<?php foreach($line_items[$cart_package_uid]['item_'.$item->uid.$index] as $line) { ?>

												<?php //foreach($line_items as $line) { ?>

													<?php unset($label_add); ?>

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
														<td colspan="3" class="text-right">
														<?php if ($line->source == 'bundle') { ?>
														<strong class="rezgo-line-bundle push-right"></i>&nbsp;<?php echo esc_html($line->label); ?><?php echo esc_html($label_add); ?> (Bundle)</strong></span>
														<?php } else { ?>
														<span class="push-right"><strong><?php echo esc_html($line->label); ?><?php echo esc_html($label_add); ?></strong></span>
														<?php } ?>
														</td>
														<td class="text-right"><?php echo esc_html($site->formatCurrency($line->amount)); ?></td>
													</tr>   

													<?php if(!$site->exists($item->deposit)) { 
														$item_total[$cart_package_uid][$index] += $line->amount;
													} else {
														$item_total[$cart_package_uid][$index] = (float)$item->deposit_value;
													} ?>
														
												<?php } unset($line); ?>

											<?php } ?>

												<tr class="rezgo-tr-subtotal package-item-total">
													<?php $item_booking_total[$cart_package_uid] += (float) $item->overall_total ?>
													<td colspan="3" class="text-right"><span class="push-right"><strong>Item Total</strong></span></td>
													<td class="text-right"><?php echo esc_html($site->formatCurrency($item->overall_total)); ?></td>
												</tr>
			
												<?php if($site->exists($item->deposit)) { ?>

													<?php 
														$item_deposit_total[$cart_package_uid] += $item->deposit_value;
														$total_deposit_set[$cart_package_uid]++; 
													?>

													<tr class="rezgo-tr-subtotal package-item-total">
														<td colspan="3" class="text-right"><span class="push-right"><strong>Item Deposit Total</strong></span></td>
														<td class="text-right"><?php echo esc_html($site->formatCurrency($item->deposit_value)); ?></td>
													</tr>
												<?php } ?>

												<?php if ($last){ ?>

														<td colspan="3" class="rezgo-td-grouped-line-item text-right"></td>
														<td class="rezgo-td-grouped-line text-right"><i class="fad fa-horizontal-rule"></i></td>

														<tr class="rezgo-tr-package-subtotal package-total">
															<td colspan="3" class="text-right"><span class="push-right"><strong>Package Total</strong></span></td>
															<td class="text-right"><strong><?php echo esc_html($site->formatCurrency($item_booking_total[$cart_package_uid])); ?></strong></td>
														</tr>

													<?php //if((int)$total_deposit_set[$cart_package_uid] === (int)$item->package_item_total) { ?>
													<?php if ( ((int)$total_deposit_set[$cart_package_uid] === (int)$item->package_item_total) || 
															((int)$total_deposit_set[$cart_package_uid] > 0 && ((int)$total_deposit_set[$cart_package_uid] < (int)$item->package_item_total))   
														) { 

															$mixed_deposit = ((int)$total_deposit_set[$cart_package_uid] > 0 && ((int)$total_deposit_set[$cart_package_uid] < (int)$item->package_item_total)) ? 1 : 0;
															$deposit_wording = $mixed_deposit ? 'Required to Pay Now' : 'Deposit to Pay Now';
															$deposit_package_total = $mixed_deposit ? array_sum($item_total[$cart_package_uid]) : $item_deposit_total[$cart_package_uid];
														?>

														<tr class="rezgo-tr-deposit">
															<td colspan="3" class="text-right">
																<span class="push-right"><strong><?php echo esc_html($deposit_wording); ?></strong></span>
															</td>
															<td class="text-right">
																<span class="rezgo-item-deposit">
																	<strong><?php echo esc_html($site->formatCurrency($deposit_package_total)); ?></strong>
																</span>
															</td>
														</tr> 

													<?php } ?>

												<?php } // if ($last) ?>
										</table>
										<script>jQuery(document).ready(function($){$('.rezgo-order-unavailable span').tooltip();});</script>
									</div>
								</div>
						</div> <!-- class="single-order-item for packages" -->

						<?php if ($last) {

							if($package[0]->cross->items) {
								$cross_sell = $site->getCrossSell($package[0]);
								$cross_text = $site->getCrossSellText($package[0]);

								$cart_coms[(int) $package[0]->com]['uid'] = (int) $package[0]->uid; 
								$cart_coms[(int) $package[0]->com]['com'] = (int) $package[0]->com; 
				
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

							?>
									
							<div class="rezgo-cross-order-package">
								<div class="rezgo-btn-cross-wrp">
									<button type="button" class="btn rezgo-btn-cross" id="rezgo-btn-cross-<?php echo esc_attr($package[0]->com); ?>" onclick="openCrossSell('<?php echo esc_js($package[0]->com); ?>', '<?php echo esc_js($package[0]->uid); ?>', '<?php echo esc_js($cross_sell_date); ?>')">
									<span><?php echo $cross_btn; ?></span>
									</button>
								</div>
							</div>
							
						<?php } ?>

						<hr>
						<?php $item_num++; $item_count++; $package_index++; } $index++; ?>

				<?php } else { ?>

					<?php	
						$cart_coms[(int) $item->com]['uid'] = (int) $item->uid; 
						$cart_coms[(int) $item->com]['com'] = (int) $item->com; 
							
						if ((string) $item->availability_type == 'date') {
							$cart_coms[(int) $item->com]['date'] = date("Y-m-d", (string) $item->booking_date); 
						} else {
							$cart_coms[(int) $item->com]['date'] = 'open'; 
						}
		
						if($site->getCrossSell()) { 
						
							$cart_coms[(int) $item->com]['cross'] = TRUE; 
						
							foreach($site->getCrossSell() as $cross) { 
								$cross_ids[(int) $item->com][] = (int) $cross->com;
							} 
						
						} else {
						
							$cart_coms[(int) $item->com]['cross'] = FALSE; 
							
						}
					?>

					<div class="rezgo-sub-title">
						<span> &nbsp; Booking <?php echo esc_html($item_count); ?> of <?php echo esc_html($cart_count); ?></span>
					</div>

					<div id="rezgo-order-item-<?php echo esc_attr($item->uid); ?>" class="single-order-item">

						<div class="row rezgo-form-group rezgo-cart-title-wrp">
							<div class="col-xs-9 rezgo-cart-title">
								<h3 class="rezgo-item-title">
									<?php $item_url = $site->base.'/details/'.$item->com.'/'.$site->seoEncode($item->item); ?>
									<a href="<?php echo esc_url($item_url); ?>">
										<span><?php echo esc_html($item->item); ?></span>
										<span> &mdash; <?php echo esc_html($item->option); ?></span>
									</a>
								</h3>

								<?php $data_book_date = date("Y-m-d", (int)$this->cart_data[$index]->date); ?>

								<?php if (in_array((string) $item->date_selection, $date_types)){ ?>
									<label>
										<span>Date: </span>
										<span class="lead"><?php echo esc_html(date((string) $company->date_format, (string) $item->booking_date)); ?></span>
									</label>
									<?php if ($site->exists($item->time)){ ?>
										<label>at <?php echo (string) esc_html($item->time); ?></label>
									<?php } ?> 
								<?php } else { ?>
									<label><span class="lead"> Open Availability </span></label>
								<?php } ?>

								<?php if ($item->discount_rules->rule) {
									echo '<br><label class="rezgo-booking-discount">
									<span class="rezgo-discount-span">Discount:</span> ';
									unset($discount_string);
									foreach($item->discount_rules->rule as $discount) {	
										$discount_string .= ($discount_string) ? ', '.$discount : $discount;
									}
									echo '<span class="rezgo-promo-code-desc">'.esc_html($discount_string).'</span>
									</label>';
								} ?>

                                <?php
                                if (!in_array((string) $item->date_selection, $date_types)) {
                                    $order_item_class = 'open';
                                } else {
                                    $order_item_class = date('Y-m-d', (string) $item->booking_date);
                                }
                                ?>

								<div class="rezgo-order-memo rezgo-order-date-<?php echo esc_attr($order_item_class); ?> rezgo-order-item-<?php echo esc_attr($item->uid); ?>"></div>
							</div>

							<div class="col-xs-3 column-btns">
								<div class="col-sm-12 rezgo-btn-cart-wrp">
									<button type="button" data-toggle="collapse" class="btn btn-block rezgo-pax-edit-btn" data-index="<?php echo esc_attr($index); ?>" data-order-item="<?php echo esc_attr($item->uid); ?>" data-order-com="<?php echo esc_attr($item->com); ?>" data-cart-id="<?php echo esc_attr($item_num); ?>" data-book-date="<?php echo esc_attr($data_book_date); ?>" data-book-time="<?php echo $item->time_format == 'dynamic' ? esc_attr($item->time) : ''; ?>" data-target="#pax-edit-<?php echo esc_attr($item_num); ?>">
										<span>Edit Guests</span>
									</button>
								</div>

								<div class="col-sm-12 rezgo-btn-cart-wrp">
									<button type="button" class="btn rezgo-btn-remove btn-block" data-index="<?php echo esc_attr($index); ?>" data-date=<?php echo esc_attr($data_book_date); ?> data-order-item="<?php echo esc_attr($item->uid); ?>" data-com="<?php echo esc_attr($item->com); ?>" data-name="<?php echo esc_attr($item->item . ' - ' . $item->option); ?>" data-total="<?php echo esc_attr($item->overall_total); ?>" data-url="<?php echo esc_attr($site->base); ?>/order?edit[<?php echo esc_attr($item->cartID); ?>][adult_num]=0">
										<span>Remove<span class='hidden-xs'> from Order</span></span>
									</button>
								</div>
							</div>
						</div>

						<div class="row rezgo-form-group rezgo-cart-table-wrp">

							<div class="collapse rezgo-pax-edit-box" id="pax-edit-<?php echo esc_attr($item_num); ?>"></div>
							<div id="pax-edit-scroll-<?php echo esc_attr($item_num); ?>" class="rezgo-cart-edit-wrp"></div>

							<div class="col-xs-12">
								<table class="table rezgo-billing-cart table-responsive">
									<tr class="rezgo-tr-head">
										<td class="text-left rezgo-billing-type"><label>Type</label></td>
										<td class="text-left rezgo-billing-qty"><label class="hidden-xs">Qty.</label></td>
										<td class="text-left rezgo-billing-cost"><label>Cost</label></td>
										<td class="text-right rezgo-billing-total"><label>Total</label></td>
									</tr>

									<?php foreach($site->getTourPrices($item) as $price) { ?>

										<?php if($item->{$price->name.'_num'}) { ?>
											<tr class="rezgo-tr-pax">
												<td class="text-left"><?php echo esc_html($price->label); ?></td>
											<td class="text-left" ><?php echo esc_html($item->{$price->name.'_num'}); ?></td>
												<td class="text-left">
													<?php
														$initial_price = (float) $price->price;
														$strike_price = (float) $price->strike;
														$discount_price = (float) $price->base;
													?>
													<?php if ( ($site->exists($price->strike)) && ($site->exists($price->base)) )  { ?>
														<?php $show_this = max($strike_price, $discount_price); ?>

														<span class="discount">
															<?php echo esc_html( $site->formatCurrency($show_this)); ?>
														</span>

													<?php } else if(!$site->isVendor() && $site->exists($price->strike)) { ?>

														<span class="discount">
															<!-- show only if strike price is higher -->
															<?php if ($strike_price >= $initial_price) { ?>
																<span class="rezgo-strike-price">
																	<?php echo esc_html( $site->formatCurrency($strike_price)); ?>
																</span>
															<?php } ?>
														</span>

													<?php } else if($site->exists($price->base)) { ?>

														<span class="discount">
															<?php echo esc_html($site->formatCurrency($price->base)); ?>
														</span>

													<?php } ?>
														<?php echo esc_html($site->formatCurrency($price->price)); ?>
												</td>		
												<td class="text-right">
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
											<span title="This item has become unavailable after it was added to your order">
												<i class="fa fa-exclamation-triangle"></i>
												<span> No Longer Available</span>
											</span>
										</td>
									</tr>
								<?php } else { $cart_total += (float) $item->overall_total; } ?>

								<tr class="rezgo-tr-subtotal">
									<td colspan="3" class="text-right"><span class="push-right"><strong>Subtotal</strong></span></td>
									<td class="text-right"><?php echo esc_html($site->formatCurrency($item->sub_total)); ?></td>
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
								<?php unset($label_add); ?>

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
									<td colspan="3" class="text-right">
									<?php if ($line->source == 'bundle') { ?>
									<strong class="rezgo-line-bundle push-right"></i>&nbsp;<?php echo esc_html($line->label); ?><?php echo esc_html($label_add); ?> (Bundle)</strong></span>
									<?php } else { ?>
									<span class="push-right"><strong><?php echo esc_html($line->label); ?><?php echo esc_html($label_add); ?></strong></span>
									<?php } ?>
									</td>
									<td class="text-right"><?php echo esc_html($site->formatCurrency($line->amount)); ?></td>
								</tr>                  
									
								<?php } ?>

								<tr class="rezgo-tr-subtotal package-summary-total">
									<td colspan="3" class="text-right"><span class="push-right"><strong>Total</strong></span></td>
									<td class="text-right"><strong><?php echo esc_html($site->formatCurrency($item->overall_total)); ?></strong></td>
								</tr>

									<?php if($site->exists($item->deposit)) { ?>
										<tr class="rezgo-tr-deposit">
											<td colspan="3" class="text-right">
												<span class="push-right"><strong>Deposit to Pay Now</strong></span>
											</td>
											<td class="text-right">
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
									
									<div class="col-lg-9 col-sm-8 col-xs-12 rezgo-related">
									<div class="rezgo-related-label"><span>Related products</span></div>
									<?php foreach($site->getTourRelated() as $related) { ?>
										<?php $related_link = $site->base.'/details/'.$related->com.'/'.$site->seoEncode($related->name); ?>
										<a href="<?php echo esc_url($related_link); ?>" class="rezgo-related-link"><?php echo esc_html($related->name); ?></a>
										<br />
									<?php } ?>
									</div>
					
								<?php } ?>

						<?php  
							if ($site->getCrossSell()) {

								$cross_sell = $site->getCrossSell();
								$cross_text = $site->getCrossSellText();

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
									
							<div class="<?php echo (!$site->getTourRelated() ? '' : '')?> rezgo-cross-order">
								<div class="rezgo-btn-cross-wrp">
									<button type="button" class="btn rezgo-btn-cross" id="rezgo-btn-cross-<?php echo esc_attr($item->com); ?>" onclick="openCrossSell('<?php echo esc_js($item->com); ?>', '<?php echo esc_js($item->uid); ?>', '<?php echo esc_js($cross_sell_date); ?>')">
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
			    $contents[]['id'] = (int) $item->uid;
				$contents[]['quantity'] = (int) $item->pax_count;  } //foreach ($cart as $item) ?>

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
							if ($cart_id['cross'] == TRUE && $_COOKIE['cross_'.$cart_id['com']] != 'shown') { // 
								echo '<script> 
								jQuery(document).ready(function() { 
									if(getCookie("cross_'.esc_html($cart_id['com']).'") != "shown"){
										openCrossSell("'.esc_html($cart_id['com']).'", "'.esc_html($cart_id['uid']).'", "'.esc_html($cart_id['date']).'"); 
										
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
				<?php require('fixed_cart.php');?>
			<?php } ?> 

		</div><!-- // flex-container -->

		<?php if(count($cart)) { ?>

			<?php if(!$site->isVendor()) { ?>

				<?php if ( (!$trigger_code) || ($trigger_code == '') ) { ?>

				<div id="rezgo-order-promo-code-wrp__mobile" class="row rezgo-order-promo-code-wrp__mobile rezgo-form-group-short">
					
					<form class="rezgo-promo-form__mobile" id="rezgo-promo-form__mobile" role="form">

                    	<span id="rezgo-promo-form-memo"></span>

						<div class="input-group <?php echo esc_attr($hidden); ?>">
							<input type="text" class="form-control" id="rezgo-promo-code__mobile" name="promo" placeholder="Enter Promo Code" value="<?php echo ($trigger_code ? esc_html($trigger_code) : '')?>" required>

							<div class="input-group-btn">
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
							<a id="rezgo-promo-clear__mobile" style="color:#333;" class="btn <?php echo esc_attr($hidden); ?>" href="<?php echo esc_url($_SERVER['HTTP_REFERER']); ?>/?promo=" target="_top"><i class="fa fa-times"></i></a>
						<hr>
					</span>
									
				<?php } ?>
				</div> <!-- // rezgo-order-promo-code-wrp__mobile -->

			<?php } ?>

			<div id="rezgo-bottom-cta">
				<span id="rezgo-booking-btn">
					<a href="<?php echo esc_url($site->base); ?>" id="rezgo-order-book-more-btn" class="btn rezgo-btn-default btn-lg btn-block">
						<span>Book More</span>
					</a>
				</span>

				<a id="rezgo-btn-book" href="<?php echo esc_url($site->base); ?>/book" class="btn rezgo-btn-book btn-lg btn-block rezgo-order-step-btn-bottom">
					<span>Check Out</span>
				</a>

				<script>

					jQuery('#rezgo-promo-form__mobile').submit( function(e){
						e.preventDefault();

						jQuery('#rezgo-promo-form').ajaxSubmit({
							type: 'POST',
							url: '<?php echo admin_url('admin-ajax.php'); ?>' + '?action=rezgo&method=book_ajax',
							data: { rezgoAction: 'update_promo' },
							success: function(data){
								window.top.location.replace('<?php echo esc_url($_SERVER['HTTP_REFERER']); ?>?promo=' + jQuery('#rezgo-promo-code__mobile').val());
							}
						})
					});

					jQuery('#rezgo-promo-clear__mobile').click(function(){
						jQuery.ajax({
							type: 'POST',
							url: '<?php echo admin_url('admin-ajax.php'); ?>' + '?action=rezgo&method=book_ajax',
							data: { rezgoAction: 'update_promo' },
						})
					});

				</script>

			</div>
		<?php } ?>
	
	</div> <!-- // Jumbotron -->
</div><!-- // rezgo-container -->

		<?php
			// build 'share this order' link
			$pax_nums = array ('adult_num', 'child_num', 'senior_num', 'price4_num', 'price5_num', 'price6_num', 'price7_num', 'price8_num', 'price9_num');

			$order_share_link = (($this->checkSecure()) ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].$site->base.'/order/?order=clear';

			foreach($cart as $key => $item) {	
				if(in_array((string) $item->date_selection, $date_types)) {	
					$order_share_date = date("Y-m-d", (int)$item->booking_date);
				} else {
					$order_share_date = date('Y-m-d', strtotime('+1 day')); // for open availability
				}

				$order_share_link .= '&add['.$item->num.'][uid]='.$item->uid.'&add['.$item->num.'][date]='.$order_share_date.'&add['.$item->num.'][book_time]='.$item->time;

				$order_share_link .= $item->package ? '&add['.$item->num.'][package]='.$item->package.'&add['.$item->num.'][cart_package_uid]='.$item->cart_package_uid : '';

				foreach($pax_nums as $pax) {	
					if($item->{$pax} != '') {
						$order_share_link .= '&add['.$item->num.']['.$pax.']='.$item->{$pax};
					}
				}
			}

			// finally, include promo/refid if set
			if($site->cart_trigger_code) {
				$order_share_link .= '&promo='.$site->cart_trigger_code;
			}
			if($site->refid) {
				$order_share_link .= '&refid='.$site->refid;
			}
		?>

				<?php if($cart) { ?>
					<div class="order-footer">
				
					<?php if(count($cart)) { ?>
						<div id="rezgo-order-share-btn-wrp" class="clearfix">
							<a href="javascript:void(0);" id="rezgo-share-order">
								<span><i class="fa fa-external-link"></i>Share this order </span>
							</a>
							<input type="text" id="rezgo-order-url" style="opacity:1;" class="form-control" onclick="this.select();" value="<?php echo esc_attr($order_share_link); ?>" readonly>
						</div>
						
						<!-- copy to clipboard -->
						<script>
							const shareBtn = document.querySelector('#rezgo-share-order');
							const copyText = document.querySelector("#rezgo-order-url");
							const showText = document.querySelector(".link-copy-success");

							const copyMeOnClipboard = () => {
								copyText.select();
								copyText.setSelectionRange(0, 99999); //for mobile phone
								document.execCommand("copy");
								shareBtn.innerHTML = '<i class="fa fa-check"></i>Link copied';
								
								setTimeout(() => {
									shareBtn.innerHTML = '<span><i class="fa fa-external-link"></i>Share this order</span>'    
								}, 3000)
							}

							shareBtn.addEventListener('click', function(){
								copyMeOnClipboard();
							});

						</script>

					<?php } ?>
				<?php } ?>

			 </div> <!--// order footer -->
<script>

    function openCrossSell(com, id, date) {
		
		var
		rezgoModalTitle = 'Return Trip',
		wp_slug = '<?php echo esc_html($_REQUEST['wp_slug']); ?>',
		query = '<?php echo home_url() . $site->base; ?>?rezgo=1&mode=return_trip&com=' + com + '&id=' + id + '&date=' + date + '&wp_slug='+ wp_slug+ '&headless=1&hide_footer=1&cross_sell=1';

		window.top.jQuery('#rezgo-modal-iframe').attr('src', query).attr('height', '90%');// 
		jQuery("#rezgo-modal-iframe").css({"width": "100%"});
		window.top.jQuery('#rezgo-modal').modal();
	}

	function setCookie(cname, cvalue, exdays) {
		var d = new Date();
		d.setTime(d.getTime() + (exdays*24*60*60*1000));
		var expires = "expires="+ d.toUTCString();
		document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
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
		document.cookie = cname + "=;Thu, 01 Jan 1970 00:00:00 UTC;path=/";
	}

	jQuery(document).ready(function($){

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
			<?php if ($analytics_ga4) { ?>
				// gtag begin_checkout
				gtag("event", "begin_checkout", {
					currency: "<?php echo esc_html($company->currency_base); ?>",
					value: document.querySelector('#total_value').dataset.total,
					coupon: "<?php echo $_COOKIE['rezgo_promo'] ? esc_html($_COOKIE['rezgo_promo']) : esc_html($site->cart_trigger_code); ?>",
					items: [
					<?php $tag_index = 1;
						foreach ($cart as $item){ ?>
					{
						item_id: "<?php echo esc_html($item->uid); ?>",
						item_name: "<?php echo esc_html($item->item . ' - ' . $item->option); ?>",
						currency: "<?php echo esc_html($company->currency_base); ?>",
						coupon: "<?php echo $_COOKIE['rezgo_promo'] ? esc_html($_COOKIE['rezgo_promo']) : esc_html($site->cart_trigger_code); ?>",
						price: <?php echo (float) esc_html($item->overall_total); ?>,
						quantity: 1,
						index: <?php echo esc_html($tag_index++); ?>,
					},
					<?php } unset($tag_index); ?>
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
					<?php $tag_index = 1;
						foreach ($cart as $item){ ?>
					{
						item_id: "<?php echo esc_html($item->uid); ?>",
						item_name: "<?php echo esc_html($item->item . ' - ' . $item->option); ?>",
						currency: "<?php echo esc_html($company->currency_base); ?>",
						coupon: "<?php echo $_COOKIE['rezgo_promo'] ? esc_html($_COOKIE['rezgo_promo']) : esc_html($site->cart_trigger_code); ?>",
						price: <?php echo (float) esc_html($item->overall_total); ?>,
						quantity: 1,
						index: <?php echo esc_html($tag_index++); ?>,
					},
					<?php } unset($tag_index); ?>
					]
				}
				});
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

		$('.rezgo-pax-edit-btn').click(function(){

			$(this).find('span').html() == "Edit Guests" ? $(this).find('span').html('Cancel') : $(this).find('span').html('Edit Guests');

			let cart_id = $(this).attr('data-cart-id'); 
			let pax_edit_position = $('#pax-edit-scroll-' + cart_id).position();
			let pax_edit_box = $('.rezgo-pax-edit-box');
		});

		$('.rezgo-btn-remove').click(function() {

			localStorage.clear();

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
								gtag("event", "remove_from_cart", {
									currency: "<?php echo esc_html($company->currency_base); ?>",
									value: item_total,
									items: [
										{
										item_id: item_id,
										item_name: item_name,
										currency: "<?php echo esc_html($company->currency_base); ?>",
										coupon: "<?php echo $_COOKIE['rezgo_promo'] ? esc_html($_COOKIE['rezgo_promo']) : esc_html($site->cart_trigger_code); ?>",
										index: 1,
										price: item_total,
										quantity: 1
										}
									]
								});
							<?php } ?>

							<?php if ($analytics_gtm) { ?>	
								// tag manager remove_from_cart
								dataLayer.push({ ecommerce: null });  // Clear the previous ecommerce object.
								dataLayer.push({
								event: "remove_from_cart",
								ecommerce: {
									items: [
									{
									item_id: item_id,
									item_name: item_name,
									coupon: "<?php echo $_COOKIE['rezgo_promo'] ? esc_html($_COOKIE['rezgo_promo']) : esc_html($site->cart_trigger_code); ?>",
									currency: "<?php echo esc_html($company->currency_base); ?>",
									index: 1,
									price: item_total,
									quantity: 1
									}
									]
								}
								});
							<?php } ?>
							window.top.location.reload();
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

		$('#rezgo-error-dismiss').click(function(e) {
			e.preventDefault();
			$('#rezgo-order-error-message').fadeOut();
		});

	});
</script>

<style>#debug_response {width:100%; height:200px;}</style>