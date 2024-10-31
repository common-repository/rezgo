<?php
	if (REZGO_WORDPRESS) {
		$re = explode('?',sanitize_text_field($_REQUEST['method']));

		parse_str($re[1], $data);

		$trs	= 't=uid';
		$trs .= '&q=' . $data['id'];
		$trs .= '&d=' . $data['date'];
		$trs .= '&file=edit_pax';

		$package_id = $data['package_id'] ?? '';
		$book_time = $data['book_time'] ?? '';
		$id = $data['id'] ?? '';
		$cart_package_uid = $data['cart_package_uid'] ?? '';
		$index = $data['index'] ?? '';
		$book_date = $data['date'] ?? '';

		$option = $site->getTours($trs);

		if (isset($data['parent_url'])) {
			$site->base = sanitize_text_field($data['parent_url']);
		}
	} else {
		$option = $site->getTours('t=uid&q='.$_REQUEST['id'].'&d='.$_REQUEST['date'].'&file=edit_pax');

		// non-open date date_selection elements
		$date_types = array('always', 'range', 'week', 'days', 'single');
		$package_id = $_REQUEST['package_id'];
		$book_time = $_REQUEST['book_time'];
		$id = $_REQUEST['id'];
		$cart_package_uid = $_REQUEST['cart_package_uid'];
		$index = $_REQUEST['index'];
		$book_date = $_REQUEST['date'];
	}
	$site->readItem($option[0]);
	$order_id = sanitize_text_field($data['order_id']);
	$cart = $site->getCart();

	// get min and max pax values from package response
	$package_resp = $package_id ? $site->getTours('t=uid&q='.$package_id.'&d='.$book_date.'&file=edit_pax') : '';

    $minimum_required_pax = $option[0]->date->time_data->time ? $option[0]->default_min_guests : $option[0]->per;

	if ($option[0]->date->time_data->time) {
        foreach ($option[0]->date->time_data->time as $timing) {
            if((string) $timing->id == $book_time){
                $availability = (int) $timing->av;
                $max_pax = (int) $timing->prices->price->av;
            }
        }
    } else {
        $availability = $option[0]->date->availability;
    }

?>

<div class="rezgo-edit-pax-content">
	<a id="close-pax-edit-box" class="pax-edit-cancel" data-bs-toggle="collapse" href="#pax-edit-<?php echo esc_attr($order_id); ?>">
		<i class="fas fa-times"></i>
	</a>
	<script>
		let fields_<?php echo esc_html($order_id); ?> = new Array();
		let required_num_<?php echo esc_html($order_id); ?> = 0;

		function isInt(n) {
			 return n % 1 === 0;
		}

		jQuery(document).ready(function($){
			check_pax_<?php echo esc_html($order_id); ?> = function() {
				let err;
				let count = 0;
				let required = 0;

				for (v in fields_<?php echo esc_html($order_id); ?>) {
					// total number of spots
					if($('#' + v).val()) count += $('#' + v).val() * 1;

					// has a required price point been used
					if (
						fields_<?php echo esc_html($order_id); ?>[v] && 
						$('#' + v).val() >= 1
					) { 
						required = 1; 
					}

					// negative (-) symbol not allowed on PAX field
					if ($('#' + v).val() < 0) 
					{
					    err = 'Please enter valid number for booking.';
				    }
				}

				if (count == 0 || !count) {
					err = 'Please enter the number you would like to book.';
				} 
				else if (required_num_<?php echo esc_html($order_id); ?> > 0 && required == 0) {
					err = 'At least one marked ( * ) price point is required to book.';
				} 
				else if (!isInt(count)) {
					err = 'Please enter a whole number. No decimal places allowed.';
				} 
				<?php if (!$package_id && $site->exists($option[0]->per)) { ?>
				else if(count < <?php echo esc_html($minimum_required_pax); ?>) {
					err = '<?php echo esc_html($minimum_required_pax); ?> minimum required to book.';
				} 
				<?php } else if($package_id && $site->exists($package_resp[0]->per)) { ?>
				else if(count < <?php echo esc_html($package_resp[0]->per); ?>) {
					err = '<?php echo esc_html($package_resp[0]->per); ?> minimum required to book.';
				} 
				<?php } ?>
				else if(count > <?php echo esc_html($availability); ?>) {
					err = 'There is not enough availability to book ' + count;
				} else if(count > 250) {
					err = 'You can not book more than 250 spaces in a single booking.';
				}
				<?php if (!$package_id) { ?>
					<?php if ($option[0]->max_guests > 0) { ?>
					else if(count > <?php echo esc_html($option[0]->max_guests); ?>) {
						err = 'There is a maximum of <?php echo esc_html($option[0]->max_guests); ?> per booking.';
					}
					<?php } ?>
				<?php } else { ?>
					<?php if ($package_resp[0]->max_guests > 0) { ?>
					else if(count > <?php echo esc_html($package_resp[0]->max_guests); ?>) {
						err = 'There is a maximum of <?php echo esc_html($package_resp[0]->max_guests); ?> per booking.';
					}
					<?php } ?>
				<?php } ?>

				if (err) {
					$('#error_text_<?php echo esc_html($order_id); ?>').html(err);

					$('#error_text_<?php echo esc_html($order_id); ?>').slideDown().delay(2000).slideUp('slow');

					return false;
				}
				else{
					// Catch form submissions
					$('#rezgo-edit-pax-<?php echo esc_html($order_id); ?>').submit( function(e) {
						e.preventDefault();

						$('#rezgo-edit-pax-<?php echo esc_html($order_id); ?>').ajaxSubmit({
							type: 'POST',
							url: '<?php echo admin_url('admin-ajax.php'); ?>' + '?action=rezgo&method=book_ajax',
							data: { rezgoAction: 'edit_pax' },
							success: function(data){
								// console.log(JSON.parse(data));
								let response = JSON.parse(data);

								// no error from adding item to cart 
								if (response == null) {
									<?php if (!DEBUG){ ?>
										window.location.reload();
									<?php } else { ?>
										alert('Updated Pax Count');
									<?php } ?>
								} else {
									let err = response.message;
									$('#error_text_<?php echo esc_html($order_id); ?>').html(err);
									$('#error_text_<?php echo esc_html($order_id); ?>').slideDown().delay(2000).slideUp('slow');
									
									if (response.error_code == 12) {
										window.location.reload(3000);
									}
								}

							},
							error: function(error) {
								console.log(error);
							}
						});
						
					});
				}
			}

			$('.rezgo-btn-book[rel="<?php echo esc_html($order_id); ?>"]').on('click', function(e){
				check_pax_<?php echo esc_html($order_id); ?>(e);
			});
		});
	</script>

	<?php if ($option[0]->date->availability != 0) { ?>

		<?php
			// get current cart to plug into getTourPrices
			foreach ($cart as $item){
				$options[] = $item;
			}
		?>

		<form class="rezgo-order-form clearfix" name="rezgo-edit-pax-<?php echo esc_attr($order_id); ?>" id="rezgo-edit-pax-<?php echo esc_attr($order_id); ?>" target="rezgo_content_frame">

			<input type="hidden" name="edit[index]" value="<?php echo esc_attr($index); ?>">
			<input type="hidden" name="edit[uid]" value="<?php echo esc_attr($option[0]->uid); ?>">
			<input type="hidden" name="edit[date]" value="<?php echo esc_attr($book_date); ?>">
			<?php if ($book_time) { ?>
			<input type="hidden" name="edit[book_time]" value="<?php echo esc_attr($book_time); ?>">
			<?php } // if ($book_time) ?> 
		<?php if ($package_id) { ?>
			<input type="hidden" name="edit[package]" value="<?php echo esc_attr($package_id); ?>">
			<input type="hidden" name="edit[cart_package_uid]" value="<?php echo esc_attr($cart_package_uid); ?>">

			<?php 
				// gather package price points
				$package = $site->getTours('t=com&q='.$package_id);
				$package_label_count = 0;
				foreach ($package[0]->prices->price as $package_price_point) {
					$package_modal_price_points[$cart_package_uid]['type'][] = (string)$package_price_point->type;
					$package_modal_price_points[$cart_package_uid]['label'][] = (string)$package_price_point->label;
				}
			?>

		<?php } // if ($package_id) ?> 

			<?php $prices = $site->getTourPrices($option[0]); ?>

			<?php if($site->getTourRequired()) { ?>
				<span class="rezgo-memo">At least one marked ( <em><i class="fa fa-asterisk"></i></em> ) price point is required to book.</span>
			<?php } ?>

			<?php if (!$package_id) { ?>
				<?php if($option[0]->per > 1) { ?>
					<span class="rezgo-memo">At least <?php echo esc_html($minimum_required_pax); ?> are required to book.</span>
				<?php } ?>
			<?php } else { ?>
				<?php if($package_resp[0]->per > 1) { ?>
					<span class="rezgo-memo">At least <?php echo esc_html($package_resp[0]->per); ?> are required to book.</span>
				<?php } ?>
			<?php } ?>

			<?php 
			$total_required = 0;

			// modify with package price points
			if ($package_id) {
				$p = 0;

				$price_label_modal_count = 0;
				foreach($prices as $price) {
					/* 
					There is a mismatch between labels between cart and getTourPrices()
					Consider fixing in the class instead
					*/

					if (in_array( $price->name, array('adult', 'child', 'senior'))) {
						$price_name = 'price_'.$price->name;
					} else {
						$price_name = $price->name;
					}
				}

			?>
		<?php }

			$price_tier_index = 1;
			foreach($prices as $price) {
				
				/* 
				There is a mismatch between labels between cart and getTourPrices()
				Consider fixing in the class instead
				*/

				if (in_array( $price->name, array('adult', 'child', 'senior'))) {
					$price_name = 'price_'.$price->name;
				} else {
					$price_name = $price->name;
				}
			?>

			<?php 
			if ($package_id) {
				// only show price point if price name matches
				if(in_array($price_name, $package_modal_price_points[$cart_package_uid]['type'])) { ?>

					<script>fields_<?php echo esc_html($order_id); ?>['<?php echo esc_html($price->name); ?>_<?php echo esc_html($order_id); ?>'] = <?php echo (($price->required) ? 1 : 0); ?>;</script>
					<div class="order-edit-pax-wrp">
						<div class="edit-pax-label-container">
							<label for="<?php echo esc_attr($price->name); ?>" class="control-label rezgo-pax-label rezgo-label-padding-left">
								<span>
									<?php echo (!$package_id) ? esc_html($price->label) : esc_html($package_modal_price_points[$cart_package_uid]['label'][$price_label_modal_count]); ?>
								</span>
								<?php echo (($price->required && $site->getTourRequired()) ? ' <em><i class="fa fa-asterisk"></i></em>' : ''); ?> 
							</label>
						</div>

						<div class="pax-price-container">

							<div class="form-group row pax-input-row">
								<div class="edit-pax-container">
									<div class="minus-pax-container">
									<span>
										<a id="decrease_<?php echo esc_attr($price->name); ?>_<?php echo esc_attr($order_id); ?>" class="not-allowed" onclick="decreasePax_<?php echo esc_js($price->name); ?>_<?php echo esc_js($order_id); ?>()">
											<i class="fa fa-minus"></i>
										</a>
									</span>
									</div>
									<div class="input-container">
										<input type="number" min="0" name="edit[<?php echo esc_attr($price->name); ?>_num]" value="<?php echo (string) esc_attr($cart[$index]->{$price->name.'_num'}); ?>" id="<?php echo esc_attr($price->name); ?>_<?php echo esc_attr($order_id); ?>" size="3" class="pax-input" value="0" min="0" placeholder="0">
									</div>
									<div class="add-pax-container">
									<span>
										<a id="increase_<?php echo esc_attr($price->type); ?>" onclick="increasePax_<?php echo esc_js($price->name); ?>_<?php echo esc_js($order_id); ?>()" class="">
											<i class="fa fa-plus"></i>
										</a>
									</span>
									</div>	
								</div>
							</div>

						</div><!-- // pax-price-container -->
					</div><!-- // order-edit-pax-wrp -->	

					<?php $price_label_modal_count++; 
				}
				
			} else { ?> 
			<script>
			fields_<?php echo esc_html($order_id); ?>['<?php echo esc_html($price->name.'_'.$order_id); ?>'] = <?php echo (($price->required) ? 1 : 0); ?>;
			</script>

			<div class="order-edit-pax-wrp rezgo-option-<?php echo esc_attr($id); ?>-price-tiers rezgo-option-<?php echo esc_attr($id); ?>-price-tier-<?php echo esc_attr($price_tier_index); ?>">
				<div class="edit-pax-label-container">
					<label for="<?php echo esc_attr($price->name); ?>_<?php echo esc_attr($option_num.'_'.$sub_option); ?>" class="control-label rezgo-pax-label rezgo-label-padding-left">
						<?php echo esc_html($price->label); ?><?php echo (($price->required && $site->getTourRequired()) ? ' <em><i class="fa fa-asterisk"></i></em>' : '')?> 
					</label>
					<?php 
						if($price->age_min || $price->age_max) {
							echo '<div class="edit-pax-age">';
								if($price->age_min == $price->age_max) { echo '<span>Age '.esc_html($price->age_min) .'</span>'; }
								elseif($price->age_min && !$price->age_max) { echo '<span>Ages '.esc_html($price->age_min).' and up' .'</span>'; }
								elseif(!$price->age_min && $price->age_max) { echo '<span>Ages '.esc_html($price->age_max).' and under' .'</span>'; }
								elseif($price->age_min && $price->age_max) { echo '<span>Ages '.esc_html($price->age_min).' - '.esc_html($price->age_max) .'</span>'; }
							echo '</div>';
						}
					?>
				</div>

				<div class="pax-price-container">

				<?php if(!isset($price->max) || $price->max !== 0) { ?>

					<div class="form-group row pax-input-row">
						<div class="edit-pax-container">
							<div class="minus-pax-container">
								<span>
									<a id="decrease_<?php echo esc_attr($price->name); ?>_<?php echo esc_attr($order_id); ?>" class="not-allowed" onclick="decreasePax_<?php echo esc_js($price->name); ?>_<?php echo esc_js($order_id); ?>()">
										<i class="fa fa-minus"></i>
									</a>
								</span>
							</div>
							<div class="input-container">
								<input type="number" min="0" name="edit[<?php echo esc_attr($price->name); ?>_num]" value="<?php echo (string) esc_attr($cart[$order_id]->{$price->name.'_num'}); ?>" id="<?php echo esc_attr($price->name); ?>_<?php echo esc_attr($order_id); ?>" size="3" class="pax-input" value="0" min="0" placeholder="0">
							</div>
							<div class="add-pax-container">
								<span>
									<a id="increase_<?php echo esc_attr($price->name); ?>_<?php echo esc_attr($order_id); ?>" onclick="increasePax_<?php echo esc_js($price->name); ?>_<?php echo esc_js($order_id); ?>()">
										<i class="fa fa-plus"></i>
									</a>
								</span>
							</div>	
						</div>
					</div>

					<div>
						<div class="edit-pax-label-container">
							<label for="<?php echo esc_attr($price->name); ?>" class="control-label rezgo-label-padding-left">
								<?php
									$initial_price = isset($price->price) ? (float) $price->price : 0;
									$strike_price = isset($price->strike) ? (float) $price->strike : 0;
									$discount_price = isset($price->base) ? (float) $price->base : 0;
								?>
								<span class="rezgo-pax-price">
								<?php if ( ($site->exists($price->strike)) && (isset($price->base) && $site->exists($price->base)) )  { ?>
									<?php $show_this = max($strike_price, $discount_price); ?>

									<span class="rezgo-strike-price">
										<?php echo esc_html( $site->formatCurrency($show_this)); ?>
									</span><br class="break-mobile">

								<?php } else if($site->exists($price->strike)) { ?>

									<!-- show only if strike price is higher -->
									<?php if ($strike_price >= $initial_price) { ?>
										<span class="rezgo-strike-price">
											<?php echo esc_html($site->formatCurrency($strike_price)); ?>
										</span><br class="break-mobile">
									<?php } ?>

								<?php } else if(isset($price->base) && $site->exists($price->base)) { ?>

									<span class="discount">
										<?php echo esc_html($site->formatCurrency($price->base)); ?>
									</span><br class="break-mobile">

								<?php } ?>

									<?php echo esc_html($site->formatCurrency($price->price)); ?>
								</span>
							</label>
						</div>
					</div>

					<?php } else { ?>
						
						<div class="form-group row pax-input-row">
							<div class="edit-pax-container">
								Not Available
							</div>
						</div>
						
					<?php } ?>

				</div><!-- // pax-price-container -->
			</div><!-- // order-edit-pax-wrp -->

		<?php } // end if($package_id) ?>

		<script>
            var max_pax = parseInt(<?php echo isset($max_pax) ? $max_pax : $price->max; ?>);

			jQuery(function($) {

				if ($('#<?php echo esc_html($price->name); ?>_<?php echo esc_html($order_id); ?>').val() >= max_pax){
					$('#increase_<?php echo esc_html($price->name); ?>_<?php echo esc_html($order_id); ?>').addClass('not-allowed');
				}
				if ($('#<?php echo esc_html($price->name); ?>_<?php echo esc_html($order_id); ?>').val() > 0){
					$('#decrease_<?php echo esc_html($price->name); ?>_<?php echo esc_html($order_id); ?>').removeClass('not-allowed');
				}

				$('#<?php echo esc_html($price->name); ?>_<?php echo esc_html($order_id); ?>').change(function(){
					<?php echo esc_html($price->name); ?>_num = $(this).val();
					if ($(this).val() <= 0) {
						$('#decrease_<?php echo esc_html($price->name); ?>_<?php echo esc_html($order_id); ?>').addClass('not-allowed');
					} else {
						$('#decrease_<?php echo esc_html($price->name); ?>_<?php echo esc_html($order_id); ?>').removeClass('not-allowed');
					}

					$('#increase_<?php echo esc_html($price->name); ?>_<?php echo esc_html($order_id); ?>').removeClass('not-allowed');
					
					// disable unwanted inputs
					if ($(this).val() < 0){
						$(this).val(0);
					}  else if ($(this).val() >= <?php echo $price->max; ?>){
						$(this).val(<?php echo $price->max; ?>);
						$('#increase_<?php echo esc_html($price->name); ?>_<?php echo esc_html($order_id); ?>').addClass('not-allowed');
					} else if(!isInt($(this).val()) || $(this).val() === 0) {
						$(this).val(0);
					} else {

						// let totalPax = 0;
						// let max_guests = parseInt(<?php echo $option[0]->max_guests; ?>);

						document.getElementById('<?php echo esc_html($price->name); ?>_<?php echo esc_html($order_id); ?>').value = $(this).val();

						// for(v in fields_<?php echo esc_html($order_id); ?>) {
						// 	totalPax += parseInt($('#' + v).val()*1);
						// }

						// if (totalPax >= max_guests) {
						// 	for(v in fields_<?php echo esc_html($order_id); ?>) {
						// 		$('#increase_'+v).addClass('not-allowed');
						// 	}
						// } else {
						// 	for(v in fields_<?php echo esc_html($order_id); ?>) {
						// 		$('#increase_'+v).removeClass('not-allowed');
						// 	}
						// }
					}
				});

				increasePax_<?php echo esc_html($price->name); ?>_<?php echo esc_html($order_id); ?> = function(){
					// let total_pax = 0;
					// let max_guests = parseInt(<?php echo $option[0]->max_guests; ?>);
					let value = parseInt(document.getElementById('<?php echo esc_html($price->name); ?>_<?php echo esc_html($order_id); ?>').value);
					value = isNaN(value) ? 0 : value;
					value++;
					if (value > 0) { 
						$('#decrease_<?php echo esc_html($price->name); ?>_<?php echo esc_html($order_id); ?>').removeClass('not-allowed');
					}

					if (value >= max_pax) { 
						$('#increase_<?php echo esc_html($price->name); ?>_<?php echo esc_html($order_id); ?>').addClass('not-allowed');
					} else {
						$('#increase_<?php echo esc_html($price->name); ?>_<?php echo esc_html($order_id); ?>').removeClass('not-allowed');
					}

					document.getElementById('<?php echo esc_html($price->name); ?>_<?php echo esc_html($order_id); ?>').value = value;

					// for(v in fields_<?php echo esc_html($order_id); ?>) {
					// 	total_pax += parseInt($('#' + v).val()*1);
					// }
					// if (total_pax >= max_guests) {
					// 	for(v in fields_<?php echo esc_html($order_id); ?>) {
					// 		$('#increase_'+v).addClass('not-allowed');
					// 	}
					// }
				}
				decreasePax_<?php echo esc_html($price->name); ?>_<?php echo esc_html($order_id); ?> = function(){
					// let total_pax = 0;
					// let max_guests = parseInt(<?php echo $option[0]->max_guests; ?>);
					let value = parseInt(document.getElementById('<?php echo esc_html($price->name); ?>_<?php echo esc_html($order_id); ?>').value);
					value = isNaN(value) ? 0 : value;
					if (value <= 0) {
						return false;
					}
					value--;
					if (value <= 0) {
						$('#decrease_<?php echo esc_html($price->name); ?>_<?php echo esc_html($order_id); ?>').addClass('not-allowed');
					} 

					if (value <= max_pax) { 
						$('#increase_<?php echo esc_html($price->name); ?>_<?php echo esc_html($order_id); ?>').removeClass('not-allowed');
					}	

					document.getElementById('<?php echo esc_html($price->name); ?>_<?php echo esc_html($order_id); ?>').value = value;

					// for(v in fields_<?php echo esc_html($order_id); ?>) {
					// 	total_pax += parseInt($('#' + v).val()*1);
					// }
					// if (total_pax <= max_guests) {
					// 	for(v in fields_<?php echo esc_html($order_id); ?>) {
					// 		$('#increase_'+v).removeClass('not-allowed');
					// 	}
					// }
				}
			});
		</script>

			<?php
				if ($price->required) { $total_required++; }
				$price_tier_index++;
			} // end foreach( $site->getTourPrices()	
		?>

		<script>
			// let initial_total_pax_<?php echo esc_html($order_id); ?> = 0;
			required_num_<?php echo esc_html($order_id); ?> = <?php echo esc_html($total_required); ?>;

			// for(v in fields_<?php echo esc_html($order_id); ?>) {
			// 	initial_total_pax_<?php echo esc_html($order_id); ?> += parseInt($('#' + v).val()*1);
			// }

			<?php //if ($option[0]->max_guests) { ?> 
				// if (initial_total_pax_<?php echo esc_html($order_id); ?> >= <?php echo $option[0]->max_guests; ?>) {
				// 	for(v in fields_<?php echo esc_html($order_id); ?>) {
				// 		$('#increase_'+v).addClass('not-allowed');
				// 	}
				// }
			<?php //} ?>
		</script>

			<div class="text-danger rezgo-option-error" id="error_text_<?php echo esc_attr($order_id); ?>" style="display:none;"></div>

		<div class="form-group float-end">
			<a 
			data-bs-toggle="collapse" 
			class="pax-edit-cancel"
			href="#pax-edit-<?php echo $order_id; ?>"
				>Cancel
			</a>
			<span>&nbsp;</span>
			<span class="btn-check"></span>
			<button 
			id="update_booking_<?php echo $order_id; ?>"
			type="submit" 
			class="btn rezgo-btn-book rezgo-btn-update_<?php echo esc_attr($order_id); ?>" 
			data-date=<?php echo esc_attr($book_date); ?> 
			data-order-item="<?php echo esc_attr($order_id); ?>" 
			onclick="return check_pax_<?php echo $order_id; ?>();">
				<span>Update Booking</span>
		</button>
		</div>

		<br />
	</form>

	<?php } else { ?>
		<div class="rezgo-order-unavailable">
			<span>Sorry, there is no availability left for this option</span>
		</div>
	<?php } // end if ($option->date->availability != 0) ?>
</div>

<script>
	jQuery('.pax-edit-cancel').click(function(){
		jQuery('.rezgo-pax-edit-btn').find('span').html('Edit Guests');
	})
</script>