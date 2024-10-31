<div class="rezgo-ajax-container" id="rezgo-ajax-container-<?php echo esc_attr($site->requestNum('pg')); ?>" style="display:none;">
	<?php if ($site->requestNum('pg') == 1) { ?>

		<?php 
		if (
			$site->requestStr('search_for') || 
			$site->requestStr('start_date') || 
			$site->requestStr('end_date') || 
			$site->requestStr('tags') || 
			$site->requestNum('cid')
		) { 
		?>
			<p class="rezgo-list-breadcrumb lead wp-hide">
				<span>Results</span>

				<?php if ($site->requestStr('search_for')) { ?>
					<span> for keyword </span>
					<?php $search_for_url = $site->base.'/?start_date='.$site->requestStr('start_date').'&end_date='.$site->requestStr('end_date').'&tags='.$site->requestStr('tags'); ?>
					<a class="rezgo-breadcrumb-link" data-toggle="tooltip" data-placement="top" title="Click to clear keywords" href="<?php echo esc_url($search_for_url); ?>" target="_top">
						<?php echo stripslashes(esc_html($site->requestStr('search_for'))); ?>
					</a>
				<?php } ?>

				<?php if ($site->requestStr('tags')) { ?>
					<span> tagged with </span>
					<?php $tags_url = $site->base.'/?start_date='.$site->requestStr('start_date').'&end_date='.$site->requestStr('end_date').'&search_in='.$site->requestStr('search_in').'&search_for='.$site->requestStr('search_for'); ?>
					<a class="rezgo-breadcrumb-link" data-toggle="tooltip" data-placement="top" title="Click to clear tags" href="<?php echo esc_url($tags_url); ?>" target="_top">
						<?php echo esc_html($site->requestStr('tags')); ?>
					</a>
				<?php } ?>

				<?php if ($site->requestNum('cid')) { ?>
					<span> supplied by </span>
					<?php $supplied_by_url = $site->base.'/?start_date='.$site->requestStr('start_date').'&end_date='.$site->requestStr('end_date'); ?>
					<a class="rezgo-breadcrumb-link" data-toggle="tooltip" data-placement="top" title="Click to clear supplier" href="<?php echo esc_url($supplied_by_url); ?>" target="_top">
						<?php echo esc_html($site->getCompanyName($site->requestNum('cid'))); ?>
					</a>
				<?php } ?>

				<?php if ($site->requestStr('start_date') AND $site->requestStr('end_date')) { ?>
					<span> between </span>
					<?php $clear_url = $site->base.'/?search_in='.$site->requestStr('search_in').'&search_for='.$site->requestStr('search_for').'&tags='.$site->requestStr('tags') ?>
					<a class="rezgo-breadcrumb-link" data-toggle="tooltip" data-placement="top" title="Click to clear date search" href="<?php echo esc_url($clear_url); ?>" target="_top">
						<?php echo esc_html($site->requestStr('start_date')); ?> and <?php echo esc_html($site->requestStr('end_date')); ?>
					</a>
				<?php } elseif ($site->requestStr('start_date')) { ?>
					<span> for </span>
					<?php $clear_url = $site->base.'/?search_in='.$site->requestStr('search_in').'&search_for='.$site->requestStr('search_for').'&tags='.$site->requestStr('tags'); ?>
					<a  class="rezgo-breadcrumb-link" data-toggle="tooltip" data-placement="top" title="Click to clear date search" href="<?php echo esc_url($clear_url); ?>" target="_top">
						<?php echo esc_html($site->requestStr('start_date')); ?>
					</a>
				<?php } elseif ($site->requestStr('end_date')) { ?>
					<span> for </span>
					<?php $clear_url = $site->base.'/?search_in='.$site->requestStr('search_in').'&search_for='.$site->requestStr('search_for').'&tags='.$site->requestStr('tags') ?>
					<a class="rezgo-breadcrumb-link" data-toggle="tooltip" data-placement="top" title="Click to clear date search" href="<?php echo esc_url($clear_url); ?>" target="_top">
						<?php echo esc_html($site->requestStr('end_date')); ?>
					</a>
				<?php } ?>

				<a href="<?php echo esc_url($site->base); ?>" class="rezgo-list-clear pull-right hidden-xs" target="_top">clear</a>
				<a href="<?php echo esc_url($site->base); ?>" class="rezgo-list-clear-xs pull-right hidden-sm hidden-md hidden-lg" target="_top">clear</a>
			</p>
		<?php } else { ?>
			<br />
		<?php } ?>
	<?php } ?>

	<?php 
		$tourList = $site->getTours();
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
		$site->readItem($item) ;
		$item_unavailable = ($site->requestStr('start_date') && count($site->getTourAvailability($item)) == 0) ? 1 : 0;
		if (!$item_unavailable) {
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

		<div itemscope itemtype="http://schema.org/Product" class="rezgo-list-item<?php echo (($item_unavailable) ? ' rezgo-inventory-unavailable' : ''); ?>" id="rezgo-item-<?php echo esc_attr($item->com); ?>">
			<div class="row rezgo-tour">
				<div class="col-xs-12">
					<div class="row">

						<div class="rezgo-tour-list col-xs-12 pull-left">

						<?php if ($item->media->image[0]) { ?>
							<div class="col-xs-12 col-sm-5 col-md-5 rezgo-list-image pull-left">
								<a href="<?php echo esc_url($tour_details_link); ?>" itemprop="url" onclick="select_item_<?php echo esc_js($item->com); ?>();" target="_top">
									<img src="<?php echo esc_url($item->media->image[0]->path); ?>" border="0" alt="<?php echo esc_attr($item->item); ?>"/>
								</a>
								<div class="visible-xs visible-sm rezgo-image-spacer"></div>
							</div>
							<?php } else { ?>

								<div class="col-xs-12 col-sm-5 col-md-5 rezgo-list-image pull-left">
									<a href="<?php echo esc_url($tour_details_link); ?>" itemprop="url" onclick="select_item_<?php echo esc_js($item->com); ?>();" target="_top">
										<img itemprop="image" id="no-image" src="<?php echo esc_html($site->path)?>/img/no_image.svg" alt="No Image">
									</a>
								</div>

							<?php } ?>

						<h2 itemprop="name">
							<a href="<?php echo esc_url($tour_details_link); ?>" itemprop="url" onclick="select_item_<?php echo esc_js($item->com); ?>();" target="_top"><?php echo esc_attr($item->item); ?></a>&nbsp;
							<span class="rezgo-list-star-rating"><?php echo wp_kses($star_rating_display, array('i' => array('class' => array()))); ?></span>
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
									&hellip;  <a class="underline-link" href="<?php echo esc_url($tour_details_link); ?>" itemprop="url" onclick="select_item_<?php echo esc_js($item->com); ?>();" target="_top">read more</a>
							<?php }	?>
						</p>
					<?php } ?>

						<div class="col-sm-12 col-md-4 rezgo-info-left pull-left">

							<?php if (count(is_countable($location) ? $location : []) > 0) { ?>
								<p class="rezgo-list-location">
									<strong class="rezgo-location-label">Location</strong>
									<?php
									if ($location['address'] != '') {
										echo '
										'.($location['name'] != '' ? '<span class="rezgo-location-name">'.esc_html($location['name']).' - </span>' : '').'
										<span class="rezgo-location-address">'.esc_html($location['address']).'</span>';
									} 
									else {
										echo '
										'.($location['city'] != '' ? '<span class="rezgo-location-city">'.esc_html($location['city']).', </span>' : '').'
										'.($location['state'] != '' ? '<span class="rezgo-location-state">'.esc_html($location['state']).', </span>' : '').'
										'.($location['country'] != '' ? '<span class="rezgo-location-country">'.esc_html($location['country']).'</span>' : '');
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
							</div>
						</div>

						<!-- <div class="col-xs-12 col-sm-12 col-md-3 pull-right rezgo-more-spacer"></div> -->

						<div class="col-xs-12 col-sm-12 col-md-3 pull-right rezgo-detail">
							<a href="<?php echo esc_url($tour_details_link); ?>" itemprop="url" class="btn rezgo-btn-detail btn-lg btn-block" onclick="select_item_<?php echo esc_js($item->com); ?>();" target="_top">
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
			gtag("event", "view_item_list", {
				item_list_name: "Tour List",
				items: [
					{
						item_id: "<?php echo $item->com; ?>",
						item_name: "<?php echo $item->item; ?>",
						index: <?php echo $available_items; ?>,
						currency: "<?php echo $booking_currency; ?>",
						price: <?php echo $site->exists($item->starting) ? $item->starting : 0; ?>,
						quantity: 1
					}
				]
			});
		<?php } ?>

		<?php if ($site->exists($site->getAnalyticsGtm())) { ?>
			// tag manager view_item_list
			dataLayer.push({ ecommerce: null });  // Clear the previous ecommerce object.
			dataLayer.push({
			event: "view_item_list",
			ecommerce: {
				items: [
					{
						item_id: "<?php echo $item->com; ?>",
						item_name: "<?php echo $item->item; ?>",
						index: <?php echo $available_items; ?>,
						currency: "<?php echo $booking_currency; ?>",
						price: <?php echo $site->exists($item->starting) ? $item->starting : 0; ?>,
						quantity: 1
					}
				]
			}
			});
		<?php } ?>

		}
		</script>

	<?php } // end foreach ( $tourList as $item ) ?>
</div>
|||<?php echo esc_html($moreButton); ?>