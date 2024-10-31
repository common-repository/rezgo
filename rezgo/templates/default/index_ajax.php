<div class="rezgo-ajax-container" id="rezgo-ajax-container-<?php echo esc_attr($site->requestNum('pg')); ?>" style="display:none;">
	<?php if($site->requestNum('pg') == 1) { ?>
		<?php if (!REZGO_WORDPRESS) { ?> 
		<script>
			//fix for firefox
			$('.rezgo-ajax-container').css('display','block');
			$(function() {
				$('a.rezgo-breadcrumb-link').tooltip();
			});
		</script>
		<?php } ?>

		<?php if (REZGO_LITE_CONTAINER) {

			if ($_COOKIE['rezgo_promo']){
				$link_promo_code = $_COOKIE['rezgo_promo'] ?? '';
			} else {
				$link_promo_code = (string) $site->cart_trigger_code;
			}

		 } ?>

		<?php if($site->requestStr('search_for') OR $site->requestStr('start_date') OR $site->requestStr('end_date') OR $site->requestStr('tags') OR $site->requestNum('cid')) { ?>
			<p class="rezgo-list-breadcrumb wp-hide">
				Results
				<?php if($site->requestStr('search_for')) { ?> for keyword <a class="rezgo-breadcrumb-link" data-bs-toggle="tooltip" data-placement="top" title="Click to clear keywords" href="<?php echo $site->base; ?>/?start_date=<?php echo $site->requestStr('start_date'); ?>&end_date=<?php echo $site->requestStr('end_date'); ?>&tags=<?php echo $site->requestStr('tags'); ?>" target="_parent"><?php echo stripslashes($site->requestStr('search_for')); ?></a><?php } ?>
				<?php if($site->requestStr('tags')) { ?> tagged with <a class="rezgo-breadcrumb-link" data-bs-toggle="tooltip" data-placement="top" title="Click to clear tags" href="<?php echo $site->base; ?>/?start_date=<?php echo $site->requestStr('start_date'); ?>&end_date=<?php echo $site->requestStr('end_date'); ?>&search_in=<?php echo $site->requestStr('search_in'); ?>&search_for=<?php echo $site->requestStr('search_for'); ?>" target="_parent"><?php echo $site->requestStr('tags'); ?></a><?php } ?>
				<?php if($site->requestNum('cid')) { ?> supplied by <a class="rezgo-breadcrumb-link" data-bs-toggle="tooltip" data-placement="top" title="Click to clear supplier"	href="<?php echo $site->base; ?>/?start_date=<?php echo $site->requestStr('start_date'); ?>&end_date=<?php echo $site->requestStr('end_date'); ?>" target="_parent"><?php echo $site->getCompanyName($site->requestNum('cid')); ?></a><?php } ?>
				<?php if($site->requestStr('start_date') AND $site->requestStr('end_date')) { ?>
				 between <a class="rezgo-breadcrumb-link" data-bs-toggle="tooltip" data-placement="top" title="Click to clear date search" href="<?php echo $site->base; ?>/?search_in=<?php echo $site->requestStr('search_in'); ?>&search_for=<?php echo $site->requestStr('search_for'); ?>&tags=<?php echo $site->requestStr('tags'); ?>" target="_parent"><?php echo $site->requestStr('start_date'); ?> and <?php echo $site->requestStr('end_date'); ?></a>
				<?php } elseif($site->requestStr('start_date')) { ?>
				 for <a class="rezgo-breadcrumb-link" data-bs-toggle="tooltip" data-placement="top" title="Click to clear date search" href="<?php echo $site->base; ?>/?search_in=<?php echo $site->requestStr('search_in'); ?>&search_for=<?php echo $site->requestStr('search_for'); ?>&tags=<?php echo $site->requestStr('tags'); ?>" target="_parent"><?php echo $site->requestStr('start_date'); ?></a>
				<?php } elseif($site->requestStr('end_date')) { ?>
				 for <a class="rezgo-breadcrumb-link" data-bs-toggle="tooltip" data-placement="top" title="Click to clear date search" href="<?php echo $site->base; ?>/?search_in=<?php echo $site->requestStr('search_in'); ?>&search_for=<?php echo $site->requestStr('search_for'); ?>&tags=<?php echo $site->requestStr('tags'); ?>" target="_parent"><?php echo $site->requestStr('end_date'); ?></a>
				<?php } ?>
				<a href="<?php echo $site->base; ?>/" class="rezgo-list-clear float-end d-none d-sm-block" target="_parent">clear</a>
				<a href="<?php echo $site->base; ?>/" class="rezgo-list-clear-xs float-sm-end d-sm-none d-md-none d-lg-none" target="_parent">clear</a>
			</p>
		<?php } else { ?>
			<br />
		<?php } ?>
	<?php } ?>

	<?php 
		$tourList = $site->getTours();
		$linkTarget =  REZGO_WORDPRESS ? "_top" : "_parent";
		if (!$tourList) { ?>
		<p class="lead" id="rezgo-no-results"><span>Sorry, there were no results for your search.</span></p>
	<?php } ?>

	<?php
		if ($tourList[REZGO_RESULTS_PER_PAGE]) {
			$moreButton = 1;	
			unset($tourList[REZGO_RESULTS_PER_PAGE]);
		} 
		else {
			$moreButton = 0; 
		}
	?>

	<?php $available_items = 0; ?>

	<?php foreach($tourList as $item) {

		$site->readItem($item);
		$item_unavailable = ($site->requestStr('start_date') AND count($site->getTourAvailability($item)) == 0) ? 1 : 0;
		if(!$item_unavailable) {
			$available_items++;
		}
		$tour_details_link = $site->base.'/details/'.$item->com.'/'.$site->seoEncode($item->item);
		$booking_currency = $site->getBookingCurrency();
		
		// prepare average star rating
		$star_rating_display = '';

		if($item->rating_count >= 1) {
							
			$avg_rating = round(floatval($item->rating) * 2) / 2;	
			
			for($n=1; $n<=5; $n++) {
				if($avg_rating == ($n-0.5)) $star_rating_display .= '<i class="rezgo-star fas fa-star-half-alt rezgo-star-half"></i>';
				elseif($avg_rating >= $n) $star_rating_display .= '<i class="rezgo-star fa fa-star rezgo-star-full"></i>';
				else $star_rating_display .= '<i class="rezgo-star far fa-star rezgo-star-empty"></i>';
			}	
			
		}
		
		unset($location);
		if($site->exists($item->location_name)) $location['name'] = $item->location_name;
		if($site->exists($item->location_address)) $location['address'] = $item->location_address;
		if($site->exists($item->city)) $location['city'] = $item->city;
		if($site->exists($item->state)) $location['state'] = $item->state;
		if($site->exists($item->country)) $location['country'] = ucwords($site->countryName(strtolower($item->country)));
		?>

		<script type="application/ld+json">
			{
				"@context": "https://schema.org/",
				"@type": "Product",	
				"name": "<?php echo htmlentities(esc_html($item->item)); ?>",
				"image": "<?php echo esc_html($item->media->image[0]->path); ?>",
				"description": "<?php echo htmlentities(strip_tags(esc_html($item->details->overview)), ENT_COMPAT); ?>",
				<?php if($site->exists($item->starting)) { ?>
				"offers": {
					"@type": "Offer",
					"availability": "https://schema.org/InStock",
					"price": "<?php echo esc_html($item->starting); ?>",
					"priceCurrency": "<?php echo esc_html($booking_currency); ?>"
				},
				<?php } ?>
				<?php if($item->rating_count >= 1) { ?>
				"aggregateRating": {
					"@type": "AggregateRating",
					"ratingValue": "<?php echo esc_html($avg_rating); ?>",
					"ratingCount": "<?php echo esc_html($item->rating_count); ?>"
				},
				<?php } ?>
				"address": {
					"@type": "PostalAddress",
					"addressLocality": "<?php echo htmlentities(esc_html($location['city'])); ?>",
					"addressRegion": "<?php echo htmlentities(esc_html($location['state'])); ?>",
					"streetAddress": "<?php echo htmlentities(esc_html($location['address'])); ?>"
				},
				"url": "<?php echo 'https://'.esc_html($_SERVER['HTTP_HOST']).esc_html($tour_details_link); ?>"
			}
		</script>

		<div itemscope itemtype="http://schema.org/Product" class="rezgo-list-item<?php echo (($item_unavailable) ? ' rezgo-inventory-unavailable' : ''); ?>" id="rezgo-item-<?php echo esc_attr($item->com); ?>" role="document" tabindex="0">
			<div class="row rezgo-tour">
				<div class="col-12">
					<div class="row">

							<div class="rezgo-tour-list col-12 float-start">

								<?php if ($item->media->image[0]) { ?>
									<div class="col-12 col-sm-5 col-md-5 rezgo-list-image float-start">
										<a href="<?php echo esc_url($tour_details_link); ?><?php if (REZGO_LITE_CONTAINER) { ?>/?promo=<?php echo $link_promo_code; ?><?php } ?>" itemprop="url" onclick="select_item_<?php echo esc_js($item->com); ?>();" target="<?php echo $linkTarget; ?>">
											<img itemprop="image" src="<?php echo esc_url($item->media->image[0]->path); ?>" border="0" alt="<?php echo esc_attr($item->item); ?>"/>
										</a>
										<div class="visible-xs visible-sm rezgo-image-spacer"></div>
									</div>
									<?php } else { ?>

										<div class="col-12 col-sm-5 col-md-5 rezgo-list-image float-start">
											<a href="<?php echo esc_url($tour_details_link); ?><?php if (REZGO_LITE_CONTAINER) { ?>/?promo=<?php echo $link_promo_code; ?><?php } ?>" itemprop="url" onclick="select_item_<?php echo esc_js($item->com); ?>();" target="<?php echo $linkTarget; ?>">
												<img itemprop="image" id="no-image" src="<?php echo $site->path; ?>/img/no_image.svg" alt="No Image">
											</a>
										</div>

									<?php } ?>

								<h2 itemprop="name"><a href="<?php echo esc_url($tour_details_link); ?><?php if (REZGO_LITE_CONTAINER) { ?>/?promo=<?php echo $link_promo_code; ?><?php } ?>" itemprop="url" onclick="select_item_<?php echo esc_js($item->com); ?>();" target="<?php echo $linkTarget; ?>"><?php echo esc_attr($item->item); ?></a>&nbsp;
									<span class="rezgo-list-star-rating"><?php echo wp_kses($star_rating_display, ALLOWED_HTML); ?></span>
								</h2>

								<?php if($item->rating_count >= 1) { ?>
									<span itemprop="aggregateRating" itemtype="https://schema.org/AggregateRating">
										<span itemprop="ratingValue" content="<?php echo esc_html($avg_rating); ?>"></span>
										<span itemprop="ratingCount" content="<?php echo esc_html($item->rating_count); ?>"></span>
									</span>
								<?php } ?>

								<?php if($site->exists($item->details->overview)) { ?>
									<p itemprop="description">
										<?php
											$text = strip_tags($item->details->overview);
											$text = $text." ";
											$text = substr($text, 0, 200);
											$text = substr($text, 0, strrpos($text,' '));
											echo esc_html($text);

											if(strlen(strip_tags($item->details->overview)) > 200) { ?>
												&hellip;  <a class="underline-link" href="<?php echo esc_url($tour_details_link); ?><?php if (REZGO_LITE_CONTAINER) { ?>/?promo=<?php echo $link_promo_code; ?><?php } ?>" itemprop="url" onclick="select_item_<?php echo esc_js($item->com); ?>();" target="<?php echo $linkTarget; ?>">read more</a>
										<?php }	?>
									</p>
								<?php } ?>

							<article class="rezgo-info-left col-sm-12 col-md-12 col-lg-4 float-start">

								<?php if (isset($location) && count(is_countable($location) ? $location : []) > 0) { ?>
									<p class="rezgo-list-location">
										<strong class="rezgo-location-label"><span>Location</span></strong>

										<?php
										if (isset($location['address'])) {
											echo '
											'. (isset($location['name']) ? '<span class="rezgo-location-name">'.esc_html($location['name']).' - </span>' : '').'
											<span class="rezgo-location-address">'.esc_html($location['address']).'</span>';
										} else {
											echo '
											'. (isset($location['city']) ? '<span class="rezgo-location-city">'.esc_html($location['city']).', </span>' : '').'
											'. (isset($location['state']) ? '<span class="rezgo-location-state">'.esc_html($location['state']).', </span>' : '').'
											'. (isset($location['country']) ? '<span class="rezgo-location-country">'.esc_html($location['country']).'</span>' : '');
										}
										?>
									</p>
								<?php } ?>

								<?php if ($site->exists($item->starting)) { ?>
									<p class="rezgo-list-price" itemprop="offers" itemscope itemtype="http://schema.org/Offer">
										<strong class="rezgo-starting-label"><span>Starting from</span></strong>
										<span itemprop="price" content="<?php echo esc_html($item->starting); ?>"></span>
										<span itemprop="priceCurrency" content="<?php echo esc_html($booking_currency); ?>"></span>
										<span class="rezgo-starting-price"><span><?php echo esc_html($site->formatCurrency($item->starting)); ?></span></span>
									</p>
								<?php } ?>
								</article>
							</div>
							<div class="col-12 col-sm-12 col-md-12 col-lg-3 float-end rezgo-detail">
								<span class="btn-check"></span>
								<a role="button" href="<?php echo esc_url($tour_details_link); ?><?php if (REZGO_LITE_CONTAINER) { ?>/?promo=<?php echo $link_promo_code; ?><?php } ?>" itemprop="url" class="btn rezgo-btn-detail btn-lg btn-block" onclick="select_item_<?php echo esc_js($item->com); ?>();" target="<?php echo $linkTarget; ?>">
								<span>More details</span></a>
							</div>
							<div class="clearfix"></div>
						</div>
				</div><!-- // .row -->
			</div><!-- // .rezgo-tour -->
		</div><!-- // .rezgo-list-item -->

		<script>

		function select_item_<?php echo $item->com; ?>(){

		<?php if ($site->exists($site->getAnalyticsGa4())) { ?>
			// gtag select_item
			gtag("event", "select_item", {
				item_list_name: "Tour List",
				items: [
					{
						item_id: "<?php echo $item->com; ?>",
						item_name: "<?php echo $item->item; ?>",
						index: <?php echo $available_items; ?>,
						currency: "<?php echo esc_html($booking_currency); ?>",
						price: <?php echo $site->exists($item->starting) ? $item->starting : 0; ?>,
						quantity: 1
					}
				]
			});
		<?php } ?>

		<?php if ($site->exists($site->getAnalyticsGtm())) { ?>
			// tag manager select_item
			dataLayer.push({ ecommerce: null });  // Clear the previous ecommerce object.
			dataLayer.push({
			event: "select_item",
			item_list_name: "Tour List",
			ecommerce: {
				items: [
					{
						item_id: "<?php echo $item->com; ?>",
						item_name: "<?php echo $item->item; ?>",
						index: <?php echo $available_items; ?>,
						currency: "<?php echo esc_html($booking_currency); ?>",
						price: <?php echo $site->exists($item->starting) ? $item->starting : 0; ?>,
						quantity: 1
					}
				]
			}
			});
		<?php } ?>

		<?php if ($site->exists($site->getMetaPixel())) { ?>
			// meta_pixel custom event SelectItem
			fbq('track', 'SelectItem', { 
					item_list_name: "Tour List",
					contents: [
						{
							'id': "<?php echo $item->com; ?>",
							'name': "<?php echo $item->item; ?>",
							'price': <?php echo $site->exists($item->starting) ? $item->starting : 0; ?>,
							'quantity': 1,
						}
					]
				}
			)
		<?php } ?>

		}
		</script>

	<?php } // end foreach ( $tourList as $item ) ?>
</div>
|||<?php echo esc_html($moreButton); ?>