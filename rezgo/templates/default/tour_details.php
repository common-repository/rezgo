<?php 
$ta_key = '2E2B919141464E31B384DE1026A2DE7B';

$analytics_ga4 = $site->exists($site->getAnalyticsGa4());
$analytics_gtm = $site->exists($site->getAnalyticsGtm());
$meta_pixel = $site->exists($site->getMetaPixel());

$option = isset($_REQUEST['option']) ? sanitize_text_field($_REQUEST['option']) : '';
$com = isset($_REQUEST['com']) ? sanitize_text_field($_REQUEST['com']) : '';
$sanitized_date = isset($_REQUEST['date']) ? sanitize_text_field($_REQUEST['date']) : '';
$parent_url =  $site->base;

$company = $site->getCompanyDetails();

$tz_offset = $company->time_format;
$now = strtotime($tz_offset.' hours', time());

?>
<?php if (!REZGO_WORDPRESS) { ?>
	<!-- fonts -->
	<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Lato:300,400,700">
	<!-- calendar.css -->
	<link href="<?php echo $this->path; ?>/css/responsive-calendar.css" rel="stylesheet">
	<link href="<?php echo $this->path; ?>/css/responsive-calendar.rezgo.css?v=<?php echo REZGO_VERSION; ?>" rel="stylesheet">

	<script type="text/javascript" src="<?php echo $this->path; ?>/js/responsive-calendar.min.js"></script>
	<script type="text/javascript" src="<?php echo $this->path; ?>/js/jquery.form.js"></script>
	<script type="text/javascript" src="<?php echo $this->path; ?>/js/jquery.readmore.min.js"></script>
<?php } ?>

<script>
	function addLeadingZero(num) {
		if (num < 10) {
			return "0" + num;
		} else {
			return "" + num;
		}
	}

	function sum( obj ) {
		var sum = 0;
		for( var el in obj ) {
			if( obj.hasOwnProperty( el ) ) {
			sum += parseFloat( obj[el] );
			}
		}
		return sum;
		}

	let tomorrow = new Date(new Date().setDate(new Date().getDate() + 1));
	tomorrow = tomorrow.toISOString().split('T')[0];

	// current JS timestamp
	let js_timestamp = Math.round(new Date().getTime()/1000);
	let js_timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
</script>

<div class="tour-details-wrp container-fluid rezgo-container">
<?php 
	if ($option) {
		$option = '&f[uid]=' . $option;
	} else {
		$option = '';
	}
	if ($sanitized_date) {
		$date = '&d=' . $sanitized_date;
	} else {
		$date = '';
	}
$items = $site->getTours('t=com&q='.$com.$option.$date); 

if(!$items) { ?>
    <?php if ($_REQUEST['review_link']) { ?>
   	<div class="rezgo-item-not-found">
		<h3 id="item-not-found-header">Item not found</h3>
		<h3 id="item-not-found-subheader">We're sorry, <span style="font-weight: bold;"><?php echo esc_html($_REQUEST['name'] ?? ''); ?></span> is no longer available.</h3>

        <br>
		
        <form role="form" onsubmit="<?php echo LOCATION_HREF; ?>='<?php echo esc_js($site->base); ?>/keyword/'+jQuery('#rezgo-404-search').val(); return false;" target="rezgo_content_frame">
            <div class="row">
                <div class="input-group rezgo-404-input-group">
                    <input class="form-control" type="text" name="search_for" id="rezgo-404-search" value="<?php echo stripslashes(htmlentities($_REQUEST['name'] ?? '')); ?>" />
                    <span class="input-group-btn">
                       <button class="btn btn-primary rezgo-btn-default" type="submit" id="rezgo-search-button"><span>Search</span></button>
                     </span>
                </div>
				<div class="rezgo-search-empty-warning" style="display:none">
					<span>Please enter a search term</span>
				</div>
            </div>
        </form>

		<img id="item-not-found-img" src="<?php echo esc_html($site->path); ?>/img/item_not_found.svg" alt="Item Not Found">

        <br>

        <a class="underline-link" href="javascript:history.back()"><i class="fas fa-arrow-left" style="margin-right:5px;"></i> Return to reviews</a>
    </div>

		<script>
		jQuery(function($) {
			$("#rezgo-search-button").click(function(e){
				if( $('#rezgo-404-search').val() == '' ){
					e.preventDefault();
					$('.rezgo-search-empty-warning').show();
					$('#rezgo-404-search').addClass('has-error');
				}
			});
			$('#rezgo-404-search').change( function(){
				if( $(this).val() != '' ){
					$('.rezgo-search-empty-warning').hide();
					$(this).removeClass('has-error');
				}
			});
		});
	</script>

    <?php } else { ?>
	<div class="rezgo-item-not-found">
		<h3 id="item-not-found-header">Item not found</h3>
		<h3 id="item-not-found-subheader">Sorry, the item you are looking for is not available or has no available options.</h3>
		<img id="item-not-found-img" src="<?php echo esc_html($site->path); ?>/img/item_not_found.svg" alt="Item Not Found">
		<a class="return-home-link underline-link" href="<?php echo $site->base; ?>"><i class="fas fa-arrow-left" style="margin-right:5px;"></i> Return home</a>
	</div>
    <?php } ?>
<?php } else { ?>
	<?php
	function date_sort($a, $b) {
		if ($a['start_date'] == $b['start_date']) {
				return 0;
		}

		return ($a['start_date'] < $b['start_date']) ? -1 : 1;
	}

	function recursive_array_search($needle,$haystack) {
		foreach ($haystack as $key=>$value) {
				$current_key=$key;
				if ($needle===$value OR (is_array($value) && recursive_array_search($needle,$value) !== false)) {
					return $current_key;
				}
		}
		return false;
	}	

	$day_options = array();
	$single_dates = 0;
	$calendar_dates = 0;
	$open_dates = 0;
	$item_count = 1;
	$calendar_start = '';

	// see if $item availability contains packages
	$package = $items[0]->availability_type == 'package' ? 1 : 0;

	foreach ($items as $item) {
		$site->readItem($item);

		$day_start = (int) $item->start_date;
		
		if (recursive_array_search($day_start, $day_options) === FALSE) {
			$day_options[(int) $item->uid]['start_date'] = $day_start;
		}

		// calendar availability types
		$calendar_selects = array('always', 'range', 'week', 'days');
		
		// open availability types
		$open_selects = array('never', 'number', 'specific');
		
		$date_selection = (string) $item->date_selection;
		
		// get option availability types (single, open or calendar)
		if ($date_selection == 'single') { 
			$single_dates++; 
		} elseif (in_array($date_selection, $open_selects)) { 
			// get total_availability before adding to total
			$option = $site->getTours('t=uid&q='.$item->uid);
			if ((int)$option[0]->total_availability > 0) {
				$open_dates++; 
			}
		} elseif (in_array($date_selection, $calendar_selects)) { 
			$calendar_dates++; 
		}
		
		// prepare media gallery
		if ($item_count == 1) {
			$media_count = $item->media->attributes()->value;
			$item_cutoff = $item->cutoff;

			if ($media_count > 0) {
				$m = 0;
				$indicators = '';
				$media_items = '';

				foreach ($site->getTourMedia($item) as $media) { 
					if ($m == 0) {
						$pinterest_img_path = $media->path;
					}
				
					$indicators .= '
					<li data-bs-target="#rezgo-img-carousel" data-bs-slide-to="'.$m.'"'.($m==0 ? ' class="active"' : '').'></li>'."\n";
					
					$media_caption = $media->caption ? '<div class="carousel-caption">'.$media->caption.'</div>' : '';

					$media_items .= '
						<div class="carousel-item'.($m==0 ? ' active' : '').'">
							<img src="'.$media->path.'" alt="'.$media->caption.'">'.
							$media_caption.'
						</div>
					';				
				
					$m++;
				} 
			}
		}
		
		$item_count++;
	}

	// resort by date
	usort($day_options, 'date_sort'); 

	// reduce calls in calendar_day.php by setting vars here
	$start_week = $company->start_week;
	$ta_url = $company->tripadvisor_url;
	$time_format = $company->time_format;
	$date_format = $company->date_format;

	// set defaults for start of availability
	$start_day = date('j', strtotime('+'.($item_cutoff + $time_format).' hours'));
	$open_cal_day = date('Y-m-d', strtotime('+'.($item_cutoff + $time_format).' hours'));

	// get package details
	if ($package) {

		$package_id = $item->uid;
		$package_items = array();
		$package_day_options = array();
		$package_dates = array();
		$days = (object)[];
		$package_prices = $item->prices;
		foreach ($item->packages->package as $package_item){
			$package_items[] = $package_item;
		}
		foreach ($package_items[0] as $choice) {
			if ((int)$choice->id != 0) {
				$included_uids[] = (int)$choice->id;
			}
			$date_selections[(int)$choice->id] = $choice->date_selection; 	
		}

		// calendar availability types
		$calendar_selects = array('always', 'range', 'week', 'days');

		// open availability types
		$open_selects = array('never', 'number', 'specific');

		if (array_intersect($date_selections, $calendar_selects) && array_intersect($date_selections, $open_selects)){
			foreach ($date_selections as $id => $selection) {
				// ignore date returned from open items
				if (!in_array($selection, $open_selects)) {
					$site->getCalendar($id, $sanitized_date, 0);
					$days->day = $site->calendar_days;
				}
			}
			
		} elseif (array_intersect($date_selections, $calendar_selects)) {
			foreach ($included_uids as $id) {
				$site->getCalendar($id, $sanitized_date, 0);
				$days->day = $site->calendar_days;
			}
		}
		if ($site->getCalendarDays()) {
			$package_first_date = '';
			foreach($site->getCalendarDays() as $day) {
				if (property_exists($day, 'items')) {
					$formatted_date = date('Y-m-d', isset($day->date) ? (int)$day->date : 0);

						foreach ($day->items as $v) {
							if ($day->cond == 'a' && in_array((int)$v->uid, $included_uids)) {
								if ((string)$v->availability === 'h' || (int)$v->availability > 0) {	
									$package_dates[] = $day->date;
								}
							}
						}
					if ($day->cond == 'i' || $day->cond == 'u') {
							$package_first_date = $formatted_date;
					}
				}
			}
			$calendar_start = $package_dates ? date('Y-m-d', min($package_dates)) : $package_first_date;
		}
	} else {

		// get the available dates
		$site->getCalendar($item->uid, $sanitized_date); 

		$cal_day_set = FALSE;
		$calendar_events = '';

		foreach ($site->getCalendarDays() as $day) {
			if (property_exists($day, 'cond')) {
				if ($day->cond == 'a') { $class = ''; } // available
					elseif ($day->cond == 'p') { $class = 'passed'; }
					elseif ($day->cond == 'f') { $class = 'full'; }
					elseif ($day->cond == 'i' || $day->cond == 'u') { $class = 'unavailable'; }
					elseif ($day->cond == 'c') { $class = 'cutoff'; }
					
					if ($day->date) { // && (int)$day->lead != 1
						$calendar_events .= '"'.esc_html(date('Y-m-d', $day->date)).'":{"class": "'.esc_html($class).'"},'."\n"; 
					}
					
					if ($sanitized_date) {
					$request_date = strtotime($sanitized_date);
					$calendar_start = date('Y-m', $request_date);
					$start_day =	date('j', $request_date);
					$open_cal_day =	date('Y-m-d', $request_date);
					$cal_day_set = TRUE;
				} else {
					if ($day->date) {
						$calendar_start = date('Y-m', (int) $day->date);
					}
					// redefine start days
					if ($day->cond == 'a' && !$cal_day_set) { 
						$start_day =	date('j', $day->date);
						$open_cal_day =	date('Y-m-d', $day->date);
						$cal_day_set = TRUE;
					} 
				}
			}
		}
		$calendar_events = trim($calendar_events, ','."\n");
	}

	$show_reviews = $company->reviews;

	$tour_details_link = $site->base.'/details/'.$item->com.'/'.$site->seoEncode($item->item);

	$booking_currency = $site->getBookingCurrency();

	// centralize tourTags
	$tourTags = $site->getTourTags();

	// prepare average star rating
	$star_rating_display = '';
	
	if($show_reviews == 1 && $item->rating_count >= 1) {
						
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
			"name": "<?php echo esc_html($item->item); ?>",
			"image": "<?php echo esc_html($item->media->image[0]->path ?? ''); ?>",
			"description": "<?php echo htmlentities(strip_tags(esc_html($item->details->overview ?? ''), ENT_COMPAT)); ?>",
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
				"addressLocality": "<?php echo htmlentities(esc_html($location['city'] ?? '')); ?>",
				"addressRegion": "<?php echo htmlentities(esc_html($location['state'] ?? '')); ?>",
				"streetAddress": "<?php echo htmlentities(esc_html($location['address'] ?? '')); ?>"
			},
			"url": "<?php echo 'https://'.esc_html($_SERVER['HTTP_HOST']).esc_html($tour_details_link); ?>"
		}
	</script>

	<script>
		
		<?php if ($analytics_ga4) { ?>
			// gtag view_item
			gtag("event", "view_item", {
				currency: "<?php echo esc_html($booking_currency); ?>",
				value: <?php echo $site->exists($item->starting) ? $item->starting : 0; ?>,
				items: [
					{
						item_id: "<?php echo $item->com; ?>",
						item_name: "<?php echo $item->item; ?>",
						price: <?php echo $site->exists($item->starting) ? $item->starting : 0; ?>,
						quantity: 1
					}
				]
			});
		<?php } ?>

		<?php if ($analytics_gtm) { ?>
			// tag manager view_item
			dataLayer.push({ ecommerce: null });  // Clear the previous ecommerce object.
			dataLayer.push({
			event: "view_item",
			ecommerce: {
				currency: "<?php echo esc_html($booking_currency); ?>",
				value: <?php echo $site->exists($item->starting) ? $item->starting : 0; ?>,
				items: [
					{
						item_id: "<?php echo $item->com; ?>",
						item_name: "<?php echo $item->item; ?>",
						currency: "<?php echo esc_html($booking_currency); ?>",
						price: <?php echo $site->exists($item->starting) ? $item->starting : 0; ?>,
						quantity: 1
					}
				]
			}
			});
		<?php } ?>

		<?php if ($meta_pixel) { ?>
			fbq('track', 'ViewContent', {
					content_ids: "<?php echo $item->com; ?>",
					content_name: "<?php echo $item->item; ?>", 
					currency: "<?php echo esc_html($booking_currency); ?>",
					value: <?php echo $site->exists($item->starting) ? $item->starting : 0; ?>,
				}
			);
		<?php } ?>
	</script>

	<div class="row tour-details-title-wrp" itemscope itemtype="http://schema.org/Product">

		<div id="rezgo-cart-container" class="col-12">
			<div class="row">
				<div class="rezgo-cart-link-wrp">
					<span>&nbsp;</span>

					<?php 
						$cart = $site->getCart();	
						$cart_total = 0;
						if($cart) { 
							foreach ($cart as $cart_item) {
								$currency_base = (string)$cart_item->currency_base;
								$cart_total += (float)$cart_item->overall_total; } 
						?> 					
						<div id="rezgo-cart-list">
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
										foreach ($cart as $ga4_item){ ?>
									{
										item_id: "<?php echo esc_html($ga4_item->uid); ?>",
										item_name: "<?php echo esc_html($ga4_item->item . ' - ' . $ga4_item->option); ?>",
										currency: "<?php echo esc_html($currency_base); ?>",
										coupon: "<?php echo isset($_COOKIE['rezgo_promo']) ? esc_html($_COOKIE['rezgo_promo']) : esc_html($site->cart_trigger_code); ?>",
										price: <?php echo esc_html((float)$ga4_item->overall_total); ?>,
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
										foreach ($cart as $gtm_item){ ?>
									{
										item_id: "<?php echo esc_html($gtm_item->uid); ?>",
										item_name: "<?php echo esc_html($gtm_item->item . ' - ' . $gtm_item->option); ?>",
										currency: "<?php echo esc_html($currency_base); ?>",
										coupon: "<?php echo isset($_COOKIE['rezgo_promo']) ? esc_html($_COOKIE['rezgo_promo']) : esc_html($site->cart_trigger_code); ?>",
										price: <?php echo esc_html((float)$gtm_item->overall_total); ?>,
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
										foreach ($cart as $pixel_item){ ?>
										{
											item_id: "<?php echo esc_html($pixel_item->uid); ?>",
											item_name: "<?php echo esc_html($pixel_item->item . ' - ' . $pixel_item->option); ?>",
											currency: "<?php echo esc_html($currency_base); ?>",
											coupon: "<?php echo isset($_COOKIE['rezgo_promo']) ? esc_html($_COOKIE['rezgo_promo']) : esc_html($site->cart_trigger_code); ?>",
											price: <?php echo esc_html((float)$pixel_item->overall_total); ?>,
											quantity: 1,
											index: <?php echo esc_html($tag_index++); ?>,
										},
										<?php } unset($tag_index); ?>
										]
									}
								);
							<?php } ?>	
							<?php echo LOCATION_WINDOW; ?>.location.href='<?php echo $site->base; ?>/order/';
						});
					});
					</script>
					<?php } ?>
				</div>

			</div>
		</div>

		<div class="col-12">
			<h1 itemprop="name" id="rezgo-item-name">
				<span id="rezgo-item-name-text"><?php echo esc_html($item->item); ?></span>&nbsp;
				<?php if($item->rating_count >= 1) { ?>
						<?php $rating_url = $site->base.'/reviews/item/'.$item->com; ?>
						<span id="rezgo-item-star-rating" class="rezgo-show-reviews" data-bs-toggle="tooltip" data-bs-placement="right" title="Click to view reviews"><a href="<?php echo esc_url($rating_url); ?>"><?php echo wp_kses($star_rating_display, array('i' => array('class' => array()))); ?></a></span>
						<span itemprop="aggregateRating" itemtype="https://schema.org/AggregateRating">
							<span itemprop="ratingValue" content="<?php echo esc_html($avg_rating); ?>"></span>
							<span itemprop="ratingCount" content="<?php echo esc_html($item->rating_count); ?>"></span>
						</span>
        		<?php } ?>
			</h1>
		</div>

		<?php if ($site->exists($item->starting)) { ?>
			<span class="rezgo-list-price" itemprop="offers" itemscope itemtype="http://schema.org/Offer">
				<span itemprop="price" content="<?php echo esc_attr($item->starting); ?>"></span>
				<span itemprop="priceCurrency" content="<?php echo esc_attr($booking_currency); ?>"></span>
			</span>
		<?php } ?>

	</div>

	<div class="row">
		<div id="rezgo-item-left-<?php echo esc_attr($item->com); ?>" class="col-md-12 rezgo-left-wrp">
			<?php if($media_count > 0) { ?>
				<div id="rezgo-img-carousel" class="carousel slide" data-bs-ride="carousel">
					<?php if($media_count > 1) { ?>
						<ol class="carousel-indicators">
							<?php echo wp_kses($indicators, ALLOWED_HTML); ?>
						</ol>
					<?php } ?>

					<div class="carousel-inner">
						<?php echo wp_kses($media_items, ALLOWED_HTML); ?>
					</div>

					<?php if($media_count > 1) { ?>
						<button class="left carousel-control-prev" data-bs-target="#rezgo-img-carousel" data-bs-slide="prev">
								<i class="fal fa-angle-left fa-3x"></i>
						</button>
							<button class="right carousel-control-next" data-bs-target="#rezgo-img-carousel" data-bs-slide="next">
								<i class="fal fa-angle-right fa-3x"></i>
						</button>
					<?php } ?>
				</div>
			<?php } ?>
		</div>

		<div class="details-calendar-row row">

		<div class="col-lg-7 col-md-12 rezgo-left-wrp" id="rezgo-details">
			<hr>

			<?php if ($site->exists($item->details->highlights)) { ?> 
				<div class="rezgo-tour-highlights" role="document" tabindex="0"><?php echo wp_kses($item->details->highlights, ALLOWED_HTML); ?></div>
			<?php } ?>
			
			<?php if ($site->exists($item->details->overview)) { ?> 
				<div class="rezgo-tour-description" role="document" tabindex="0">
					<div class="lead" id="rezgo-tour-overview"><?php echo wp_kses($item->details->overview, ALLOWED_HTML); ?></div>
				</div>
			<?php } ?>	

			<?php
				unset($location);
				if ($site->exists($item->location_name)) $location['name'] = $item->location_name;
				if ($site->exists($item->location_address)) $location['address'] = $item->location_address;
				if ($site->exists($item->city)) $location['city'] = $item->city;
				if ($site->exists($item->state)) $location['state'] = $item->state;
				if ($site->exists($item->country)) $location['country'] = ucwords($site->countryName(strtolower($item->country)));
			?>
			<?php if (isset($location) && count($location ? $location : []) > 0) { ?>
				<div id="rezgo-tour-location">
					<label id="rezgo-tour-location-label" class="rezgo-location-label">
						<span>Location:&nbsp;</span>
					</label>

					<?php 
						if (isset($location['address']) && $location['address'] != '') {
							echo '
							'.(isset($location['name']) && $location['name'] != '' ? '<span class="rezgo-location-name">'.esc_html($location['name']).' - </span>' : '').'
							<span class="rezgo-location-address">'.esc_html($location['address']).'</span>';
							} else {
							echo '
							'.(isset($location['city']) && $location['city'] != '' ? '<span class="rezgo-location-city">'.esc_html($location['city']).', </span>' : '').'
							'.(isset($location['state']) && $location['state'] != '' ? '<span class="rezgo-location-state">'.esc_html($location['state']).', </span>' : '').'
							'.(isset($location['country']) && $location['country'] != '' ? '<span class="rezgo-location-country">'.esc_html($location['country']).'</span>' : '');
						} 
					?>
				</div>
			<?php } ?>
			
            <!-- Logic for toggle the sections -->
			<?php if(!$site->config('REZGO_MOBILE_XML')) {
					// add 'show' class to expand collapsible for non-mobile devices
					$mclass = ' show';

					// add 'toggled' class to hide drop shadow on non-mobile devices
					$mcollapsed = ' toggled';

					$aria_expanded = 'true';
				} else {
					// add 'collapsed' class to change to default collapsed chevron for mobile devices
					$mcollapsed = ' collapsed';

					$aria_expanded = 'false';
			} ?>

            <script>
                jQuery(document).ready(function($) {
                    $('.panel-collapse').each(function() {
                    
                        var toggle_indicator = $(this).prev('.panel-heading').find('.panel-toggle-indicator');
                        let heading_title = $(this).prev('.panel-heading').find('.rezgo-section-link');

                        // Check the display property of the toggle indicator span
                        if (toggle_indicator.css('display') === 'none') {
                            $(this).removeClass('show');
                            $(heading_title).addClass('collapsed');
                            $(heading_title).removeClass('toggled'); // Remove toggled class
                        }
                    });
                });
            </script>

			<div class="panel-group rezgo-desc-panel" id="rezgo-tour-panels">

				<?php if ($package) { ?> 
					<div class="panel panel-default rezgo-panel" id="rezgo-panel-package">
						<div class="panel-heading rezgo-section">
							<h4 class="panel-title">
                            	<span class="panel-toggle-indicator panel-toggle-indicator-package"></span>
								<a role="button" data-bs-toggle="collapse" class="rezgo-section-link <?php echo esc_attr($mcollapsed); ?>" data-bs-target="#package" aria-controls="package" aria-expanded="<?php echo esc_attr($aria_expanded); ?>">
									<div class="rezgo-section-text rezgo-package-text">
										<div class="rezgo-section-icon"><i class="fad fa-layer-group fa-lg"></i></i></div>
										<span>Items Included</span>
									</div>
									<div class="clearfix"></div>
								</a>
							</h4>
						</div>
						<div id="package" class="panel-collapse collapse<?php echo esc_attr($mclass); ?>" <?php echo esc_attr($aria_expanded); ?> role="document" tabindex="0">
							<div class="panel-body rezgo-panel-body">
									<?php foreach ($package_items as $option) {
											$option_items[] = $site->base.'/details/'.$option->com.'/'.$site->seoEncode($option->item).','.$option->item ;
										} 
										// account for same item being in the package
										$option_items = array_unique($option_items);
									?>

									<?php foreach ($option_items as $option_item) {
										$option_item = explode(',', $option_item); ?>
										
										<p><i class="far fa-external-link fa-sm" style="margin:0 7px 5px 15px;"></i><a class="underline-link" href="<?php echo esc_url($option_item[0]); ?>" target="_blank"><?php echo esc_html($option_item[1]); ?></a></p>
									<?php } ?>
							</div>
						</div>
					</div> 
				<?php } ?>

				<?php if($site->exists($item->details->itinerary)) { ?> 
					<div class="panel panel-default rezgo-panel" id="rezgo-panel-itinerary">
						<div class="panel-heading rezgo-section">
							<h4 class="panel-title">
                                <span class="panel-toggle-indicator panel-toggle-indicator-itinerary"></span>
								<a role="button" data-bs-toggle="collapse" class="rezgo-section-link<?php echo esc_attr($mcollapsed); ?>" data-bs-target="#itinerary" aria-controls="itinerary" aria-expanded="<?php echo esc_attr($aria_expanded); ?>">
									<div class="rezgo-section-icon"><i class="far fa-clipboard-list fa-lg"></i></div>
									<div class="rezgo-section-text rezgo-itinerary-text"><span>Itinerary</span></div>
									<div class="clearfix"></div>
								</a>
							</h4>
						</div>
						<div id="itinerary" class="panel-collapse collapse <?php echo esc_attr($mclass); ?>">
							<div class="panel-body rezgo-panel-body"><?php echo wp_kses($item->details->itinerary, ALLOWED_HTML); ?></div>
						</div>
					</div>
				<?php } ?>

				<?php if ($site->exists($item->details->pick_up)) { ?> 
					<div class="panel panel-default rezgo-panel" id="rezgo-panel-pickup">
						<div class="panel-heading rezgo-section">
							<h4 class="panel-title">
                                <span class="panel-toggle-indicator panel-toggle-indicator-pickup"></span>
								<a role="button" data-bs-toggle="collapse" class="rezgo-section-link<?php echo esc_attr($mcollapsed); ?>" data-bs-target="#pickup" aria-controls="pickup" aria-expanded="<?php echo esc_attr($aria_expanded); ?>">
									<div class="rezgo-section-icon"><i class="far fa-map-marker fa-lg"></i></div>
									<div class="rezgo-section-text rezgo-pickup-text"><span>Pickup</span></div>
									<div class="clearfix"></div>
								</a>
							</h4>
						</div>

						<div id="pickup" class="panel-collapse collapse <?php echo esc_attr($mclass); ?>" <?php echo esc_attr($aria_expanded); ?> aria-controls="pickup" role="document" tabindex="0">
							<div class="panel-body rezgo-panel-body"><?php echo wp_kses($item->details->pick_up, ALLOWED_HTML); ?></div>
						</div>
					</div> 
				<?php } ?>

				<?php if ($site->exists($item->details->drop_off)) { ?> 
					<div class="panel panel-default rezgo-panel" id="rezgo-panel-dropoff">
						<div class="panel-heading rezgo-section">
							<h4 class="panel-title">
                                <span class="panel-toggle-indicator panel-toggle-indicator-dropoff"></span>
								<a role="button" data-bs-toggle="collapse" class="rezgo-section-link<?php echo esc_attr($mcollapsed); ?>" data-bs-target="#dropoff" aria-controls="dropoff" aria-expanded="<?php echo esc_attr($aria_expanded); ?>">
									<div class="rezgo-section-icon"><i class="far fa-location-arrow fa-lg"></i></div>
									<div class="rezgo-section-text rezgo-dropoff-text"><span>Drop Off</span></div>
									<div class="clearfix"></div>
								</a>
							</h4>
						</div>
						
						<div id="dropoff" class="panel-collapse collapse <?php echo esc_attr($mclass); ?>" <?php echo esc_attr($aria_expanded); ?> aria-controls="dropoff" role="document" tabindex="0">
							<div class="panel-body rezgo-panel-body"><?php echo wp_kses($item->details->drop_off, ALLOWED_HTML); ?></div>
						</div>
					</div> 
				<?php } ?>

				<?php if ($site->exists($item->details->bring)) { ?> 
					<div class="panel panel-default rezgo-panel" id="rezgo-panel-thingstobring">
						<div class="panel-heading rezgo-section">
							<h4 class="panel-title">
                                <span class="panel-toggle-indicator panel-toggle-indicator-thingstobring"></span>
								<a role="button" data-bs-toggle="collapse" class="rezgo-section-link<?php echo esc_attr($mcollapsed); ?>" data-bs-target="#thingstobring" aria-controls="thingstobring" aria-expanded="<?php echo esc_attr($aria_expanded); ?>">
									<div class="rezgo-section-icon"><i class="far fa-suitcase fa-lg"></i></div>
									<div class="rezgo-section-text rezgo-thingstobring-text"><span>Things To Bring</span></div>
									<div class="clearfix"></div>
								</a>
							</h4>
						</div>

						<div id="thingstobring" class="panel-collapse collapse <?php echo esc_attr($mclass); ?>" aria-controls="thingstobring" role="document" tabindex="0">
							<div class="panel-body rezgo-panel-body"><?php echo wp_kses($item->details->bring, ALLOWED_HTML); ?></div>
						</div>
					</div> 
				<?php } ?>

				<?php if ($site->exists($item->details->inclusions)) { ?> 
					<div class="panel panel-default rezgo-panel" id="rezgo-panel-inclusion">
						<div class="panel-heading rezgo-section">
							<h4 class="panel-title">
                                <span class="panel-toggle-indicator panel-toggle-indicator-inclusion"></span>
								<a role="button" data-bs-toggle="collapse" class="rezgo-section-link<?php echo esc_attr($mcollapsed); ?>" data-bs-target="#inclusion" aria-controls="inclusion" aria-expanded="<?php echo esc_attr($aria_expanded); ?>">
									<div class="rezgo-section-icon"><i class="far fa-check-circle fa-lg"></i></div>
									<div class="rezgo-section-text rezgo-inclusions-text"><span>Inclusions</span></div>
									<div class="clearfix"></div>
								</a>
							</h4>
						</div>

						<div id="inclusion" class="panel-collapse collapse <?php echo esc_attr($mclass); ?>" <?php echo esc_attr($aria_expanded); ?> role="document" tabindex="0">
							<div class="panel-body rezgo-panel-body"><?php echo wp_kses($item->details->inclusions, ALLOWED_HTML); ?></div>
						</div>
					</div> 
				<?php } ?>

				<?php if ($site->exists($item->details->exclusions)) { ?> 
					<div class="panel panel-default rezgo-panel" id="rezgo-panel-exclusion">
						<div class="panel-heading rezgo-section">
							<h4 class="panel-title">
                                <span class="panel-toggle-indicator panel-toggle-indicator-exclusion"></span>
								<a role="button" data-bs-toggle="collapse" class="rezgo-section-link<?php echo esc_attr($mcollapsed); ?>" data-bs-target="#exclusion" aria-controls="exclusion" aria-expanded="<?php echo esc_attr($aria_expanded); ?>">
									<div class="rezgo-section-icon"><i class="far fa-ban fa-lg"></i></div>
									<div class="rezgo-section-text rezgo-exclusions-text"><span>Exclusions</span></div>
									<div class="clearfix"></div>
								</a>
							</h4>
						</div>

						<div id="exclusion" class="panel-collapse collapse <?php echo esc_attr($mclass); ?>" <?php echo esc_attr($aria_expanded); ?> role="document" tabindex="0">
							<div class="panel-body rezgo-panel-body"><?php echo wp_kses($item->details->exclusions, ALLOWED_HTML); ?></div>
						</div>
					</div> 
				<?php } ?>
        
				<?php if($site->exists($item->details->checkin)) { ?> 
					<div class="panel panel-default rezgo-panel" id="rezgo-panel-checkin">
						<div class="panel-heading rezgo-section">
							<h4 class="panel-title">
                                <span class="panel-toggle-indicator panel-toggle-indicator-checkin"></span>
								<a role="button" data-bs-toggle="collapse" class="rezgo-section-link<?php echo esc_attr($mcollapsed); ?>" data-bs-target="#checkin" aria-controls="checkin" aria-expanded="<?php echo esc_attr($aria_expanded); ?>">
									<div class="rezgo-section-icon"><i class="far fa-ticket fa-lg"></i></div>
									<div class="rezgo-section-text rezgo-checkin-text"><span>Check-In</span></div>
									<div class="clearfix"></div>
								</a>
							</h4>
						</div>
						<div id="checkin" class="panel-collapse collapse<?php echo esc_attr($mclass); ?>" <?php echo esc_attr($aria_expanded); ?> role="document" tabindex="0">
						<div class="panel-body rezgo-panel-body"><?php echo wp_kses($item->details->checkin, ALLOWED_HTML); ?></div>
						</div>
					</div> 
				<?php } ?>

				<?php if ($site->exists($item->details->description)) { ?> 
					<div class="panel panel-default rezgo-panel" id="rezgo-panel-addinfo">
						<div class="panel-heading rezgo-section">
							<h4 class="panel-title">
                                <span class="panel-toggle-indicator panel-toggle-indicator-addinfo"></span>
								<a role="button" data-bs-toggle="collapse" class="rezgo-section-link<?php echo esc_attr($mcollapsed); ?>" data-bs-target="#addinfo" aria-controls="addinfo" aria-expanded="<?php echo esc_attr($aria_expanded); ?>">
									<div class="rezgo-section-icon"><i class="far fa-info-square fa-lg"></i></div>
									<div class="rezgo-section-text"><span><?php echo wp_kses($item->details->description_name, ALLOWED_HTML); ?></span></div>
									<div class="clearfix"></div>
								</a>
							</h4>
						</div>
						
						<div id="addinfo" class="panel-collapse collapse <?php echo esc_attr($mclass); ?>" <?php echo esc_attr($aria_expanded); ?> role="document" tabindex="0">
							<div class="panel-body rezgo-panel-body"><?php echo wp_kses($item->details->description, ALLOWED_HTML); ?></div>
						</div>
					</div> 
				<?php } ?>

				<?php if ($site->exists($item->details->cancellation)) { ?> 
					<div class="panel panel-default rezgo-panel" id="rezgo-panel-cancellation">
						<div class="panel-heading rezgo-section">
							<h4 class="panel-title">
                                <span class="panel-toggle-indicator panel-toggle-indicator-cancellation"></span>
								<a role="button" data-bs-toggle="collapse" class="rezgo-section-link<?php echo esc_attr($mcollapsed); ?>" data-bs-target="#cancellation" aria-controls="cancellation" aria-expanded="<?php echo esc_attr($aria_expanded); ?>">
									<div class="rezgo-section-icon"><i class="far fa-exclamation-circle fa-lg"></i></div>
									<div class="rezgo-section-text rezgo-cancellationpolicy-text"><span>Cancellation Policy</span></div>
									<div class="clearfix"></div>
								</a>
							</h4>
						</div>

						<div id="cancellation" class="panel-collapse collapse <?php echo esc_attr($mclass); ?>" <?php echo esc_attr($aria_expanded); ?> role="document" tabindex="0">
							<div class="panel-body rezgo-panel-body"><?php echo wp_kses($item->details->cancellation, ALLOWED_HTML); ?></div>
						</div>
					</div> 
				<?php } ?>

					<?php if (count(is_countable($item->details->specifications->specification) ? $item->details->specifications->specification : []) >= 1) { ?>
						<?php $s=1; ?>

					<?php foreach ($item->details->specifications->specification as $spec) { ?>
						<?php $spec_id = $site->seoEncode($spec->name); ?>

						<div class="panel panel-default rezgo-panel rezgo-spec-panel" id="rezgo-spec-<?php echo esc_attr($spec_id); ?>">
							<div class="panel-heading rezgo-section">
								<h4 class="panel-title">
                                    <span class="panel-toggle-indicator panel-toggle-indicator-<?php echo esc_attr($spec_id); ?>"></span>
									<a role="button" data-bs-toggle="collapse" class="rezgo-section-link<?php echo esc_attr($mcollapsed); ?>" data-bs-target="#spec-<?php echo esc_attr($s); ?>" aria-controls="spec-<?php echo esc_attr($s); ?>" aria-expanded="<?php echo esc_attr($aria_expanded); ?>">
										<div class="rezgo-section-icon"><i class="far fa-circle fa-lg"></i></div>
										<div class="rezgo-section-text"><span>
											<?php echo $spec->name; ?></span></div>
										<div class="clearfix"></div>
									</a>
								</h4>
							</div>

							<div id="spec-<?php echo esc_attr($s); ?>" class="panel-collapse collapse <?php echo esc_attr($mclass); ?>" <?php echo esc_attr($aria_expanded); ?> role="document" tabindex="0">
								<div class="panel-body rezgo-panel-body"><?php echo wp_kses($spec->value, ALLOWED_HTML); ?></div>
							</div>
						</div>

						<?php $s++; ?>
					<?php } ?>
				<?php } ?>
			
				<?php if($show_reviews == 1 && $item->rating_count >= 1) { ?>
					<div class="panel panel-default rezgo-panel" id="rezgo-panel-reviews">
						<div class="panel-heading rezgo-section">
							<h4 class="panel-title">
                                <span class="panel-toggle-indicator panel-toggle-indicator-reviews"></span>
								<a role="button" data-bs-toggle="collapse" class="rezgo-section-link collapsed" data-bs-target="#reviews" id="reviews-load" aria-controls="reviews-load" aria-expanded="<?php echo esc_attr($aria_expanded); ?>">
									<div class="rezgo-section-icon"><i class="far fa-star fa-lg"></i></div>
									<div class="rezgo-section-text"><span><?php echo esc_html($item->rating_count); ?> <span class="hidden-xxs">Verified </span><span class="hidden-xs">Guest </span> Review<?php echo ($item->rating_count > 1 ? 's' : ''); ?> </span>&nbsp;
										<span id="rezgo-rating-average" class="rezgo-show-reviews" data-bs-toggle="tooltip" data-bs-placement="right" title="Click to view reviews"><?php echo wp_kses($star_rating_display, array('i' => array('class' => array()))); ?></span>
									</div>
									<div class="clearfix"></div>
								</a>
							</h4>
						</div>
						<div id="reviews" class="panel-collapse collapse" <?php echo esc_attr($aria_expanded); ?> role="document" tabindex="0">
							<div class="panel-body rezgo-panel-body" id="reviews-list">&nbsp;<div class="rezgo-wait-div"></div></div>
						</div>
					</div>
				<?php } ?>

				<?php if($ta_url != '') {
					$ta_id = (string) $ta_url;
				?>
					<div class="panel panel-default rezgo-panel" id="rezgo-panel-tripadvisor">
						<div class="panel-heading rezgo-section">
							<h4 class="panel-title">
                                <span class="panel-toggle-indicator panel-toggle-indicator-tripadvisor"></span>
								<a role="button" data-bs-toggle="collapse" class="rezgo-section-link collapsed" data-bs-target="#tripadvisor" aria-controls="tripadvisor" aria-expanded="<?php echo esc_attr($aria_expanded); ?>">
									<div class="rezgo-section-icon">
										<svg style="margin: -4px 0 0 -5px;" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" aria-hidden="true" role="img" width="30px" height="20px" preserveAspectRatio="xMidYMid meet" viewBox="0 0 576 512" ><path d="M528.91 178.82L576 127.58H471.66a326.11 326.11 0 0 0-367 0H0l47.09 51.24a143.911 143.911 0 0 0 194.77 211.91l46.14 50.2l46.11-50.17a143.94 143.94 0 0 0 241.77-105.58h-.03a143.56 143.56 0 0 0-46.94-106.36zM144.06 382.57a97.39 97.39 0 1 1 97.39-97.39a97.39 97.39 0 0 1-97.39 97.39zM288 282.37c0-64.09-46.62-119.08-108.09-142.59a281 281 0 0 1 216.17 0C334.61 163.3 288 218.29 288 282.37zm143.88 100.2h-.01a97.405 97.405 0 1 1 .01 0zM144.06 234.12h-.01a51.06 51.06 0 1 0 51.06 51.06v-.11a51 51 0 0 0-51.05-50.95zm287.82 0a51.06 51.06 0 1 0 51.06 51.06a51.06 51.06 0 0 0-51.06-51.06z" fill="currentColor"></path></svg>
									</div>
									<div class="rezgo-section-text"><span>TripAdvisor<span class="hidden-xxs"> Reviews</span></span></div>
									<div class="clearfix"></div>
								</a>
							</h4>
						</div>
						<div id="tripadvisor" class="panel-collapse collapse" <?php echo esc_attr($aria_expanded); ?> role="document" tabindex="0">
							<div class="panel-body rezgo-panel-body tripadvisor-panel-body">
								<div id="TA_selfserveprop753" class="TA_selfserveprop"></div>
								<script src="//www.jscache.com/wejs?wtype=selfserveprop&amp;uniq=753&amp;locationId=<?php echo esc_attr($ta_id); ?>&amp;lang=en_US&amp;rating=true&amp;nreviews=4&amp;writereviewlink=true&amp;popIdx=true&amp;iswide=true&amp;border=true&amp;display_version=2"></script>
							</div>
						</div>
					</div>

					<style>
						#CDSWIDSSP, #CDSWIDERR { width:100% !important; }
						.widSSPData { border:none !important; }
						.widErrCnrs { display:none; }
						.widErrData { margin:1px }
						#CDSWIDERR.widErrBx .widErrData .widErrBranding dt { width: 100%; }
					</style>
				<?php } ?>
        
        <div class="clearfix" id="scroll_reviews">&nbsp;</div>
        
			</div><!-- //	#rezgo-tour-panels -->

			<?php if($site->getTourRelated()) { ?>
				<?php $related_items_array = $site->getTourRelated(); ?>
				<div class="col-12 rezgo-related rezgo-related-details">

					<div class="rezgo-related-label">
						<span>Related products</span>
					</div>

					<div id="rezgo-related-carousel">

						<?php foreach($related_items_array as $related) { 
							$related_image = '<img src="'.$related->image.'" alt="'.$related->name.'">'; 
							$placeholder = '<div class="placeholder"></div>'; 
							$related_link = $site->base.'/details/'.$related->com.'/'.$site->seoEncode($related->name);
							?>

							<a role="button" class="item" href="<?php echo esc_attr($related_link); ?>" onclick="select_item_<?php echo esc_js($related->com); ?>();">
								<?php echo $related->image ? $related_image : $placeholder ?>
								<div class="overview-container">
									<div class="carousel-title"><?php echo $related->name; ?></div>
									<?php if ($site->exists($related->overview)){ ?>
										<div class="carousel-overview">
											<?php 
												$text = strip_tags($related->overview);
												$text = $text." ";
												$text = substr($text, 0, 50);
												$text = substr($text, 0, strrpos($text,' '));
												echo $text;
											?> 
									</div>
									<?php } ?>
								</div>
							</a>
						<?php } ?>

					</div>

					<script>
					<?php foreach($related_items_array as $related) { ?>	
						function select_item_<?php echo $related->com; ?>(){
							<?php if ($analytics_ga4) { ?>
								// gtag select_item
								gtag("event", "select_item", {
									item_list_name: "Item Details Page",
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
								item_list_name: "Item Details Page",
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
										item_list_name: "Item Details Page",
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
					<?php } ?>
					</script>
				</div>
			<?php } ?>
		</div><!-- // .rezgo-left-wrp -->

		<div id="rezgo-item-right-<?php echo esc_attr($item->com); ?>" class="col-lg-5 col-md-12 rezgo-right-wrp">
			<?php if ($open_dates > 0) { ?>
				<?php $opt = 1; ?>
				<div class="rezgo-calendar-wrp">
					<div class="rezgo-open-container rezgo-open-<?php echo esc_attr($item->com); ?>">
						<?php $open_date = date('Y-m-d', strtotime('+1 day')); ?>
					
						<div class="rezgo-open-options" id="rezgo-open-option-<?php echo esc_attr($opt); ?>" style="display:none;">
							<div class="rezgo-open-header">
								<span>Open Options</span>
							</div>
							<div class="rezgo-open-selector" id="rezgo-open-date-<?php echo esc_attr($opt); ?>"></div>

							<script type="text/javascript">
								jQuery(document).ready(function($){
									$.ajax({
										url: '<?php echo admin_url('admin-ajax.php'); ?>',
										data: {
											action: 'rezgo',
											method: 'calendar_day',
											parent_url: '<?php echo esc_html($parent_url); ?>',
											com: '<?php echo esc_html($item->com); ?>',
											date: '<?php echo esc_html($open_date); ?>',
											type: 'open',
											date_format: '<?php echo esc_html($date_format); ?>',
											time_format: '<?php echo esc_html($time_format); ?>',
											security: '<?php echo wp_create_nonce('rezgo-nonce'); ?>',
										},
										context: document.body,
										success: function(data) {
											if (data.indexOf('rezgo-order-none-available') == -1) {
												$('#rezgo-open-date-<?php echo esc_html($opt); ?>').html(data).slideDown('fast');
												$('#rezgo-open-option-<?php echo esc_html($opt); ?>').fadeIn('fast');
												$('.option-panel-<?php echo esc_html($_REQUEST['option'] ?? ''); ?>').addClass('in');	
											}
										}
									});
								});
							</script> 
						</div>
					
						<div id="rezgo-open-memo"></div>
					</div>
				</div>
			<?php } // end if $open_dates > 0 ?>

			<?php if ($package) { ?>

				<script>
					let pax_obj = {
						'adult':0,
						'child':0,
						'senior':0,
						'price4':0,
						'price5':0,
						'price6':0,
						'price7':0,
						'price8':0,
						'price9':0,
					};
					let date_obj = {};
					let package_total = {};
					let filtered_pax = new Array();

					// generate list to delete from 
					let zero_entries = new Array();

					// google analytics vars
					<?php if ($analytics_ga4) { ?>
						let ga4_package_amount = 0;
						let ga4_package_details = {
							<?php for ($i=0; $i < count($package_items); $i++) { ?>
								'item_<?php echo esc_html($i+1); ?>' : {
									item_id: '', 
									item_name: '', 
									price: '', 
									coupon: "<?php echo (isset($_COOKIE['rezgo_promo']) ? esc_html($_COOKIE['rezgo_promo']) : esc_html($site->cart_trigger_code)); ?>",
									currency: "<?php echo esc_html($booking_currency); ?>",
									index: '', 
									quantity: 1,
								},
							<?php } ?> 
						};
					<?php } ?>

					<?php if ($analytics_gtm) { ?>
						let gtm_package_details = {
							<?php for ($i=0; $i < count($package_items); $i++) { ?>
								'item_<?php echo esc_html($i+1); ?>' : {
									"name": '',
									"id": '',
									"price": '',
									"quantity": 1
								},
							<?php } ?> 
						}
					<?php } ?>

					<?php if ($meta_pixel) { ?>
						let pixel_package_amount = 0;
						let pixel_package_details = {
							<?php for ($i=0; $i < count($package_items); $i++) { ?>
								'item_<?php echo esc_html($i+1); ?>' : {
									"name": '',
									"id": '',
									"price": '',
									"quantity": 1
								},
							<?php } ?> 
						}
					<?php } ?>
				</script>

				<div class="package-select-container">

					<div id="select_guests" class="active">
						<span id="guest_header">
							<span>Guests</span>
						</span>
						<span id="guests_selected">
							<span class="guests-placeholder">
								<span>Select guests</span>
							</span>
							<p id="guests"></p>
						</span>
						<i id="guest_icon" class="far fa-user-circle"></i>

						<a id="mobile_edit_guests" class="underline-link">Edit</a>

						<?php if ($item->per > 1) { ?>
							<span class="rezgo-memo">At least <?php echo esc_html($item->per); ?> are required to book.</span>
						<?php } ?>
					</div>

						<div id="reset_pax_warning" style="display:none;">
							<p> <i class="far fa-exclamation-circle" style="margin-right:7px;"></i>
								Changed your mind? 
								<br>This will reset all selected choices.
							</p>
							
							<div class="reset-warning-link-container">
								<a onclick="" id="warning_reset_pax" class="underline-link">Change</a>
								<a onclick="jQuery('#reset_pax_warning').slideUp(150);" id="warning_cancel" class="sec-btn">Cancel</a>
							</div>
						</div>

					<div class="package-pax-select active">

						<div class="rezgo-order-form">

							<script>
								let fields_<?php echo esc_html($package_id); ?> = new Array();
								let required_num_<?php echo esc_html($package_id); ?> = 0;
								function isInt(n) {
									return n % 1 === 0;
								}
							</script>

							<?php $total_required = 0; ?>
							<?php $total_price_points = count($package_prices->price); ?>
							<?php $animation_order = 1; ?>

							<?php foreach ($package_prices->price as $price) { ?>

								<script>
									zero_entries.push("0 <?php echo ucfirst(htmlspecialchars($price->label, ENT_QUOTES)); ?><br>");
									fields_<?php echo esc_html($package_id); ?>['<?php echo esc_html($price->type); ?>'] = <?php echo (((int)$price->required === 1) ? 1 : 0); ?>;
								</script>

								<div class="edit-pax-wrp" style="--animation-order: <?php echo esc_attr($animation_order); ?>;">

									<div class="pax-price-container">
										<div class="form-group row pax-input-row left-col">

											<div class="edit-pax-container">
												<div class="minus-pax-container">
													<span>
														<a id="decrease_<?php echo esc_attr($price->type); ?>" class="not-allowed" onclick="decreasePax_<?php echo esc_js($price->type); ?>()">
															<i class="fa fa-minus"></i>
														</a>
													</span>
												</div>
												<div class="input-container">
													<input type="number" min="0" name="add[0][<?php echo esc_attr($price->type); ?>_num]" id="<?php echo esc_attr($price->type); ?>" class="pax-input" value="" min="0" placeholder="0" autocomplete="off">
												</div>
												<div class="add-pax-container">
													<span>
														<a id="increase_<?php echo esc_attr($price->type); ?>" onclick="increasePax_<?php echo esc_js($price->type); ?>()">
															<i class="fa fa-plus"></i>
														</a>
													</span>
												</div>	
											</div>
										</div>

										<div class="right-col">
											<div class="edit-pax-label-container">
												<label for="<?php echo esc_attr($price->type); ?>" class="control-label rezgo-pax-label rezgo-label-margin rezgo-label-padding-left">
													<?php echo esc_html($price->label); ?><?php echo (int)$price->required === 1 ? ' <em><i class="fa fa-asterisk"></i></em>' : ''?> 
												</label>
										
												<?php 
													if(isset($price->max) && $price->max < 10) {
														
														$max_text = 'Only '.$price->max.' Left';
														if($price->max == 0) $max_text = 'Not Available <div class="space-6"></div>';
														
														echo '<div class="edit-pax-max-package">
															<span>'.$max_text.'</span>
														</div>
														<br>';
													}
												?>
											</div>
										</div>
										<div id="max_pax_error_<?php echo esc_attr($price->type); ?>" class="text-danger rezgo-option-error rezgo-max-pax-error" style="display:none;"></div>

										<?php if ((int)$price->required === 1) $total_required++; ?>

										<script>
										jQuery(function($){
											// prepare values insert in addCart() request 
											$('#<?php echo esc_html($price->type); ?>').change(function(){
												<?php echo esc_html($price->type); ?>_num = $(this).val();
												if ($(this).val() <= 0) {
													$('#decrease_<?php echo esc_html($price->type); ?>').addClass('not-allowed');
												} else {
													$('#decrease_<?php echo esc_html($price->type); ?>').removeClass('not-allowed');
												}

												// disable unwanted inputs
												if ($(this).val() < 0){
													$(this).val(0);
												} else if ($(this).val() > <?php echo $price->max; ?>){

													// show an error message
													let max_pax = <?php echo (int)$price->max; ?>;
													let plural_start = max_pax > 1 ? 'are' : 'is';
													let plural_end = max_pax > 1 ? 's' : '';
													let err = 'There ' + plural_start + ' only ' + max_pax + ' space' + plural_end + ' available';
													$('#max_pax_error_<?php echo esc_html($price->type); ?>').html(err);
													$('#max_pax_error_<?php echo esc_html($price->type); ?>').slideDown();

													// reset input 
													$(this).val(0);
													$('#decrease_<?php echo esc_html($price->type); ?>').addClass('not-allowed');

													setTimeout(() => {
														$('#max_pax_error_<?php echo esc_html($price->type); ?>').slideUp();
													}, 3500);	
													
												} else if(!isInt($(this).val()) || $(this).val() === 0) {
													$(this).val(0);
												} else {

													let value = parseInt(document.getElementById('<?php echo esc_html($price->type); ?>').value);	
													let pax = '<?php echo ucfirst(esc_html($price->label)); ?>';

													document.getElementById('<?php echo esc_html($price->type); ?>').value = $(this).val();

													// strip 'price_' from the type
													let name = '<?php echo esc_html($price->type); ?>'.replace('price_','');

													// populate pax object
													pax_obj[name] = value + ' ' + pax + '<br>';	

													// grab object values as an array and filter out empty values
													new_pax_obj = Object.values(pax_obj).filter(n => n);
													filtered_pax = new_pax_obj.filter(item => !zero_entries.includes(item));

													let filtered_str = '';
													filtered_pax.forEach(element => {
														filtered_str += element.replace(',' , '')
													});

													// place them in the guest selector
													$('#guests_selected').find('.guests-placeholder').hide();
													$('#guests').html('<strong>'+filtered_str+'</strong>');

													// fill input
													$('.pax_input_'+name).val(value);

													if (filtered_pax.length === 0){
														$('#guests_selected').find('.guests-placeholder').show();
													}
												}
											});

											increasePax_<?php echo esc_html($price->type); ?> = function(){
													let value = parseInt(document.getElementById('<?php echo esc_html($price->type); ?>').value);
													value = isNaN(value) ? 0 : value;
													value++;
													if (value > 0) { 
														$('#decrease_<?php echo esc_html($price->type); ?>').removeClass('not-allowed');
													}

													if (value >= <?php echo $price->max; ?>) { 
														$('#increase_<?php echo esc_html($price->type); ?>').addClass('not-allowed');
													} else {
														$('#increase_<?php echo esc_html($price->type); ?>').removeClass('not-allowed');
													}
													document.getElementById('<?php echo esc_html($price->type); ?>').value = value;

													let pax = '<?php echo ucfirst(esc_html($price->label)); ?>';

													// strip 'price_' from the type
													let name = '<?php echo esc_html($price->type); ?>'.replace('price_','');

													// populate pax object
													pax_obj[name] = value + ' ' + pax + '<br>';	

													// grab object values as an array and filter out empty values
													new_pax_obj = Object.values(pax_obj).filter(n => n);
													filtered_pax = new_pax_obj.filter(item => !zero_entries.includes(item));

													let filtered_str = '';
													filtered_pax.forEach(element => {
														console.log(element);
														filtered_str += element.replace(',' , '')
													});

													// place them in the guest selector
													$('#guests_selected').find('.guests-placeholder').hide();
													$('#guests').html('<strong>'+filtered_str+'</strong>');

													if (filtered_pax.length === 0){
														$('#guests_selected').find('.guests-placeholder').show();
													}

													// fill input
													$('.pax_input_'+name).val(value);
												}

											decreasePax_<?php echo esc_html($price->type); ?> = function(){
													let value = parseInt(document.getElementById('<?php echo esc_html($price->type); ?>').value);
													value = isNaN(value) ? 0 : value;
													if (value <= 0) {
														return false;
													}
													value--;
													if (value <= 0) {
														$('#decrease_<?php echo esc_html($price->type); ?>').addClass('not-allowed');
													} 

													if (value <= <?php echo $price->max; ?>) { 
														$('#increase_<?php echo esc_html($price->type); ?>').removeClass('not-allowed');
													}
													document.getElementById('<?php echo esc_html($price->type); ?>').value = value;

													let pax = '<?php echo ucfirst(esc_html($price->label)); ?>';

													// strip 'price_' from the type
													let name = '<?php echo esc_html($price->type); ?>'.replace('price_','');

													// populate pax object
													pax_obj[name] = value + ' ' + pax + '<br>';	

													// grab object values as an array and filter out empty values
													new_pax_obj = Object.values(pax_obj).filter(n => n);
													filtered_pax = new_pax_obj.filter(item => !zero_entries.includes(item));

													let filtered_str = '';
													filtered_pax.forEach(element => {
														filtered_str += element.replace(',' , '')
													});

													// place them in the guest selector
													$('#guests_selected').find('.guests-placeholder').hide();
													$('#guests').html('<strong>'+filtered_str+'</strong>');

													if (filtered_pax.length === 0){
														$('#guests_selected').find('.guests-placeholder').show();
													}

													// fill input
													$('.pax_input_'+name).val(value);
												}
											});

										</script>
									</div>
								</div>
								
								<?php $animation_order++; }
								// end foreach() ?>
							</div>
							<script>
								required_num_<?php echo esc_html($package_id); ?> = <?php echo esc_html($total_required); ?>;

								<?php if ($total_required === $total_price_points) { ?>
									// hide text and markers if all price points are set to required
									jQuery('.edit-pax-wrp').each(function(){
										jQuery(this).find('.fa-asterisk').hide();
									})
								<?php } ?>

							</script>

						<div id="rezgo-package-pax-error" class="rezgo-package-error" style="display:none;"></div>

						<div class="link-container">
							<span class="btn-check"></span>
							<a id="close_pax_select" class="btn btn-block rezgo-btn-book rezgo-btn-next-step"><span>Next Step</span></a>
							<a id="reset_pax" class="underline-link">reset</a>
						</div>
					</div>

					<!-- Dates -->
					<div id="select_date" style="display:none;">

						<div id="avail-loaded">
							<span id="date_header">
								<span>Dates</span>
							</span>
							
							<span id="dates_selected">
								<span>Select your dates & options</span>
							</span>
							<span id="date_instruction" style="display:none;"> 
								<span>
									Select your dates & options
								</span>
							</span>

							<script>package_date_options = new Array();</script>

							<?php 
							$h = 0;
							$opt = 0;

							foreach ($package_items as $option) {
								$bookable[] = $option->bookable;
								$within[] = $option->within;
								$spacing[] = $option->spacing;

								$package_item_com[] = (string)$option->com;
								$choices[] = $option->choice; 

								// week , days , range

									$date_selection = (string) $choices[$h]->date_selection;

									if (in_array($date_selection, $calendar_selects)){
										$select_type[] = 'calendar_date';
									} elseif (in_array($date_selection, $open_selects)){
										$select_type[] = 'open_date';
									} elseif ( $date_selection == 'single') {
										$select_type[] = 'single_date';
									}

									foreach ($option->choice as $choice) {
										$select_type_combination[$h][$opt++] = (string)$choice->date_selection;
										if ((string)$choice->date_selection === 'single') {
											if (recursive_array_search((int)$choice->start_date, $package_day_options) === FALSE) {
												$package_day_options[(int) $choice->id]['start_date'] = date('Y-m-d', (int) $choice->start_date);
											}
										}
									}
									$h++;
								} 
								// resort by date
								usort($package_day_options, 'date_sort'); 
							?>

							<?php foreach ($package_day_options as $package_date_option){
								echo '<script>package_date_options.push("'.esc_html($package_date_option['start_date']).'")</script>';
							} ?>

							<?php $i = 0;
							foreach ($package_items as $option) { ?>

							<div id="package-container-<?php echo esc_attr($i); ?>" class="package-container locked">

								<i id="item_selected_<?php echo esc_attr($i); ?>" class="item-selected-icon far fa-check-circle" style="display:none;"></i>

								<p class="package-item" id="package-item-<?php echo esc_attr($package_id); ?>-<?php echo esc_attr($i+1); ?>">
									<span class="package-item-number">
										<span><?php echo esc_html(($i+1). '. '); ?></span>
									</span>
									<span class="package-item-name">
										<span><?php echo (string) esc_html($option->item); ?></span>
									</span>
								</p>

								<div class="package-date-container">
									<p class="package-date" id="package-date-<?php echo esc_attr($i); ?>" style="display:none;"></p>
									<a id="reset_date_<?php echo esc_attr($i); ?>" class="underline-link reset-date" style="display:none;">Change</a>
								</div>

								<div id="date-select-container-<?php echo esc_attr($i); ?>" class="date-select-container">

									<?php // load calendar if at least one of the options have > 2 days avail
									if (count(array_intersect($select_type_combination[$i], $calendar_selects)) > 0) { ?> 
									<div id="rezgo_calendar_wrp_<?php echo esc_attr($i); ?>" class="rezgo-calendar-wrp" style="display:none;">
										<div class="rezgo-calendar">
											<div class="responsive-calendar responsive-calendar-<?php echo esc_attr($i); ?> rezgo-calendar-<?php echo esc_attr($option->com); ?>" data-com="<?php echo esc_attr($option->com); ?>">
												<div class="controls">
													<a class="float-start" data-go="prev"><div class="fas fa-angle-left fa-lg"></div></a>
													<h4><span><span data-head-year></span> <span data-head-month></span></span></h4>
													<a class="float-end" data-go="next"><div class="fas fa-angle-right fa-lg"></div></a>
												</div>
												<?php if ($start_week == 'mon') { ?>
												<div class="day-headers">
													<div class="day header">Mon</div>
													<div class="day header">Tue</div>
													<div class="day header">Wed</div>
													<div class="day header">Thu</div>
													<div class="day header">Fri</div>
													<div class="day header">Sat</div>
													<div class="day header">Sun</div>
												</div>
												<?php } else { ?>
												<div class="day-headers">
													<div class="day header">Sun</div>
													<div class="day header">Mon</div>
													<div class="day header">Tue</div>
													<div class="day header">Wed</div>
													<div class="day header">Thu</div>
													<div class="day header">Fri</div>
													<div class="day header">Sat</div>
												</div>
												<?php } ?>
												<div class="days" data-group="days"></div>
											</div>
											<div class="rezgo-calendar-legend rezgo-legend-<?php echo esc_attr($item->com); ?>">
												<span class="available">&nbsp;</span><span class="text-available"><span>&nbsp;Available&nbsp;&nbsp;</span></span>
												<span class="full">&nbsp;</span><span class="text-full"><span>&nbsp;Full&nbsp;&nbsp;</span></span>
												<span class="unavailable">&nbsp;</span><span class="text-unavailable"><span>&nbsp;Unavailable</span></span>
												<div id="rezgo-calendar-memo"></div>
											</div>
											<div id="rezgo-scrollto-options"></div>
											<div id="rezgo-date-script-<?php echo esc_attr($i); ?>" style="display:none;"></div>

											<div class="responsive-calendar-loading-container responsive-calendar-loading-container-<?php echo esc_attr($i); ?>">
												<div class="controls"></div>
												<div class="calendar"></div>
											</div>

										</div>
									</div>
									
									<?php } else { ?> 

										<div id="rezgo-date-script" style="display:none;"></div>

									<?php } ?>

								</div>

								<div class="option-select-container">
									<a id="select_option_<?php echo esc_attr($i); ?>" class="package-option-select restrict"> 
										<i class="fad fa-circle"></i>
										<span class="package-option-select-copy">
											<span class="top-placeholder">
												<span>Package Option</span>
											</span>
											<span id="option_selected_<?php echo esc_attr($i); ?>">
												<span class="default">Select Option <?php echo esc_html($i+1); ?></span>
												<span class="custom"></span>
											</span>
										</span>
										<span id="selected_<?php echo esc_attr($i); ?>"></span>

										<i class="fas fa-chevron-down"></i> 

										<div class="package-option-loading-container package-option-loading-container-<?php echo esc_attr($i); ?>" style="display:none;">
											<i class="fad fa-circle"></i>
											<span class="package-option-loading-copy">Loading Options...</span>
										</div>
									</a>

									<p class="rezgo-package-error rezgo-package-option-error rezgo-package-option-error-<?php echo esc_attr($i); ?>" style="display:none;"></p>
									<p class="rezgo-package-error rezgo-package-date-error rezgo-package-date-error-<?php echo esc_attr($i); ?>" style="display:none;"></p>

									<div id="package-option-container-<?php echo esc_attr($i); ?>" class="package-option-container">
									</div>
									<span id="end_time_<?php echo esc_attr($i); ?>" class="d-none"></span>
								</div>

								<div id="package_time_select_<?php echo esc_attr($i); ?>" class="package-time-select-container" style="display:none;">

									<a id="select_time_<?php echo esc_attr($i); ?>" class="package-time-select"> 
										<i class="far fa-clock"></i>
										<span class="package-time-select-copy">
											<span class="top-placeholder">
												<span>Start Time</span>
											</span>
											<span id="time_selected_<?php echo esc_attr($i); ?>">
												<span class="default">Select Time</span>
												<span class="custom"></span>
											</span>
										</span>
										<span id="selected_time_<?php echo esc_attr($i); ?>"></span>

										<i class="fas fa-chevron-down"></i> 
									</a>

									<p class="rezgo-package-error rezgo-package-time-error rezgo-package-time-error-<?php echo esc_attr($i); ?>" style="display:none;"></p>

									<div id="package-time-container-<?php echo esc_attr($i); ?>" class="package-time-container">
									</div>

								</div>

							</div> <!-- package container-->
							
							<script>

							jQuery(function($) {

									const package_el_<?php echo esc_html($i); ?> = document.querySelector('.package-option-select-copy .custom');
									let package_custom_content_<?php echo esc_html($i); ?> = getComputedStyle(package_el_<?php echo esc_html($i); ?>, ':before').content.replace(/['"]+/g, '');
									let package_custom_text_<?php echo esc_html($i); ?> = package_custom_content_<?php echo esc_html($i); ?> != 'none' ? package_custom_content_<?php echo esc_html($i); ?> : 0;

								<?php for ($j=0; $j < count($option); $j++) { ?>

									choose_opt_<?php echo esc_html($i); ?>_<?php echo esc_html($j); ?> = function(index, uid){

										let name = $('#package-option-container-'+<?php echo esc_html($i); ?>).find('#opt_select_<?php echo esc_html($i); ?>_'+index).html();
										let opt_total = $('#package-option-container-'+<?php echo esc_html($i); ?>).find('#opt_select_<?php echo esc_html($i); ?>_'+index).data('amount');
										let date = $('#package-option-container-'+<?php echo esc_html($i); ?>).find('#opt_select_<?php echo esc_html($i); ?>_'+index).data('date');
										let start_time = $('#package-option-container-'+<?php echo esc_html($i); ?>).find('#opt_select_<?php echo esc_html($i); ?>_'+index).data('start-time');
										let end_time = $('#package-option-container-'+<?php echo esc_html($i); ?>).find('#opt_select_<?php echo esc_html($i); ?>_'+index).data('end-time');
										let duration = $('#package-option-container-'+<?php echo esc_html($i); ?>).find('#opt_select_<?php echo esc_html($i); ?>_'+index).data('duration');
										let single_date = $('#package-option-container-'+<?php echo esc_html($i); ?>).find('#opt_select_<?php echo esc_html($i); ?>_'+index).data('single-date');
										let open_date = $('#package-option-container-'+<?php echo esc_html($i); ?>).find('#opt_select_<?php echo esc_html($i); ?>_'+index).data('open-date');

										let dynamic_start_time = $('#package-option-container-'+<?php echo esc_html($i); ?>).find('#opt_select_<?php echo esc_html($i); ?>_'+index).data('time-format') ? 1 : 0;
										let book_time = $('#package-option-container-'+<?php echo esc_html($i); ?>).find('#opt_select_<?php echo esc_html($i); ?>_'+index).data('book-time') ?? '';
										let hide_av = $('#package-option-container-'+<?php echo esc_html($i); ?>).find('#opt_select_<?php echo esc_html($i); ?>_'+index).data('hide-av') ?? '';
										let total_options = $('#package-option-container-'+<?php echo esc_html($i); ?>).find('#opt_select_<?php echo esc_html($i); ?>_'+index).data('total-options') ?? '';

										$('#end_time_<?php echo esc_html($i); ?>').text(end_time);

										bookable = '<?php echo isset($bookable[$i+1]) ? esc_html($bookable[$i+1]) : ''?>';
										within = '<?php echo isset($within[$i+1]) ? esc_html($within[$i+1]) : ''?>';
										date_obj['date_<?php echo esc_html($i+1); ?>'] = date;

										prev_selected_uid = parseInt($('input[name="add[<?php echo esc_html($i); ?>][uid]"]').val())
										if (uid != prev_selected_uid) {
											// empty previously selected subsequent package selection if previous selection changed
											$('input[name="add[<?php echo esc_html($i+1); ?>][uid]"]').val('');
											$('input[name="add[<?php echo esc_html($i+1); ?>][date]"]').val('');

											$('#package-option-container-<?php echo esc_html($i+1); ?>').empty();
											$('#selected_<?php echo esc_html($i+1); ?>').hide();
											$('#option_selected_<?php echo esc_html($i+1); ?>').show();
										}

										// fill input
										$('input[name="add['+<?php echo esc_html($i); ?>+'][uid]"]').val(uid);

										// unselect calendar date if single_date or open_date option is selected
										if (open_date || single_date) {

											if (total_options > 1 && !single_date) {
												$('#rezgo_calendar_wrp_<?php echo esc_html($i); ?>').find('.active').removeClass('select');
												$('input[name="add[<?php echo esc_html($i); ?>][date]"]').val('');
											}

											// reset error
											$('.rezgo-package-date-error-<?php echo esc_html($i); ?>').slideUp();

										} else {
											// if non open_date || single_date is selected, we need to reselect the calendar date
											if ($('#rezgo_calendar_wrp_<?php echo esc_html($i); ?>').find('.select').length == 0) {
												$('input[name="add[<?php echo esc_html($i); ?>][date]"]').val('');

												// prompt user to select date again
												let err = '<i class="far fa-exclamation-circle"></i> &nbsp; This option requires a date selection.'
												$('.rezgo-package-date-error-<?php echo esc_html($i); ?>').html(err)
												$('.rezgo-package-date-error-<?php echo esc_html($i); ?>').slideDown();
											}
										}

										if (single_date) {
											$('input[name="add[<?php echo esc_html($i); ?>][date]"]').val(single_date);
										} else if (open_date) {
											$('input[name="add[<?php echo esc_html($i); ?>][date]"]').val(tomorrow);
										}

										// reset previously set book_time fields
										$('input[name="add['+<?php echo esc_html($i); ?>+'][book_time]"]').val('');
										$('#time_selected_<?php echo esc_html($i); ?>').html('Select Time');

										$('#selected_<?php echo esc_html($i); ?>').html(name);

										$('#option_selected_<?php echo esc_html($i); ?>').hide();
										$('#selected_<?php echo esc_html($i); ?>').show();
										$('#package-option-container-'+<?php echo esc_html($i); ?>).removeClass('open');

										package_total.item_<?php echo esc_html($i+1); ?> = opt_total;
										amount = sum(package_total);

										// google analytics vars
										<?php if ($analytics_ga4) { ?>
											ga4_package_amount = amount;

											ga4_package_details.item_<?php echo esc_html($i+1); ?>.index = '<?php echo esc_html($i + 1); ?>';
											ga4_package_details.item_<?php echo esc_html($i+1); ?>.item_name = $('#package-option-container-'+<?php echo esc_html($i); ?>).find('#opt_select_<?php echo esc_html($i); ?>_'+index).data('item') + ' - ' + $('#package-option-container-'+<?php echo esc_html($i); ?>).find('#opt_select_<?php echo esc_html($i); ?>_'+index).data('name');
											ga4_package_details.item_<?php echo esc_html($i+1); ?>.item_id = $('#package-option-container-'+<?php echo esc_html($i); ?>).find('#opt_select_<?php echo esc_html($i); ?>_'+index).data('id');
											ga4_package_details.item_<?php echo esc_html($i+1); ?>.price = $('#package-option-container-'+<?php echo esc_html($i); ?>).find('#opt_select_<?php echo esc_html($i); ?>_'+index).data('amount').toFixed(2);
										<?php } ?>

										<?php if ($analytics_gtm) { ?>
											gtm_package_details.item_<?php echo esc_html($i+1); ?>.name = $('#package-option-container-'+<?php echo esc_html($i); ?>).find('#opt_select_<?php echo esc_html($i); ?>_'+index).data('item') + ' - ' + $('#package-option-container-'+<?php echo esc_html($i); ?>).find('#opt_select_<?php echo esc_html($i); ?>_'+index).data('name');
											gtm_package_details.item_<?php echo esc_html($i+1); ?>.id = $('#package-option-container-'+<?php echo esc_html($i); ?>).find('#opt_select_<?php echo esc_html($i); ?>_'+index).data('id');
											gtm_package_details.item_<?php echo esc_html($i+1); ?>.price = $('#package-option-container-'+<?php echo esc_html($i); ?>).find('#opt_select_<?php echo esc_html($i); ?>_'+index).data('amount').toFixed(2);
										<?php } ?>

										<?php if ($meta_pixel) { ?>
											pixel_package_amount = amount;

											pixel_package_details.item_<?php echo esc_html($i+1); ?>.name = $('#package-option-container-'+<?php echo esc_html($i); ?>).find('#opt_select_<?php echo esc_html($i); ?>_'+index).data('item') + ' - ' + $('#package-option-container-'+<?php echo esc_html($i); ?>).find('#opt_select_<?php echo esc_html($i); ?>_'+index).data('name');
											pixel_package_details.item_<?php echo esc_html($i+1); ?>.id = $('#package-option-container-'+<?php echo esc_html($i); ?>).find('#opt_select_<?php echo esc_html($i); ?>_'+index).data('id');
											pixel_package_details.item_<?php echo esc_html($i+1); ?>.price = $('#package-option-container-'+<?php echo esc_html($i); ?>).find('#opt_select_<?php echo esc_html($i); ?>_'+index).data('amount').toFixed(2);
										<?php } ?>

										$.ajax({
											url: '<?php echo admin_url('admin-ajax.php'); ?>',
											data: {
												action: 'rezgo',
												method: 'package_ajax',
												rezgoAction: 'formatCurrency',
												amount: amount, 
												security: '<?php echo wp_create_nonce('rezgo-nonce'); ?>',
											},
											context: document.body,
											success: function(data) {
												$('#package_total').html(data); 
												$('#item_selected_<?php echo esc_html($i); ?>').show();
											}
										});
										
										let chosenDate = bookable ? date_obj['date_<?php echo (isset($bookable[$i+1]) && $bookable[$i+1] == 'first') ? 1 : $i+1; ?>'] : date;

										if (dynamic_start_time){

											$('#package-time-container-<?php echo esc_html($i); ?>').empty();
											$('#package_time_select_<?php echo esc_html($i); ?>').slideDown();
											let times = book_time.split(',');
											times.forEach(e => {
												time = e.split(':::');
												let avail_text = !hide_av ? '<span class="availability"><i class="fas fa-circle"></i> &nbsp;'+time[1]+' left</span>' : '';
												let append = '<p id="time_select" class="package-time-select-<?php echo esc_html($i); ?>" data-duration="'+duration+'" data-book-time="'+time[0]+'">'+time[0]+avail_text+'</p>';

												$('#package-time-container-<?php echo esc_html($i); ?>').append(append);
											});

											$('.package-time-select-<?php echo esc_html($i); ?>').click(function(){
												let book_time = $(this).data('book-time');
												$('input[name="add['+<?php echo esc_html($i); ?>+'][book_time]"]').attr('disabled', false);

												$('input[name="add['+<?php echo esc_html($i); ?>+'][book_time]"]').val(book_time);
												$('#time_selected_<?php echo esc_html($i); ?>').html(book_time);
												$('#time_selected_<?php echo esc_html($i); ?>').attr('data-duration', duration);

												$('#package-time-container-<?php echo esc_html($i); ?>').removeClass('open');

												// check if time has been selected 
												if ($('#time_selected_<?php echo esc_html($i); ?>').html() != ''){
													loadNextCal();

													// reset next calendar choices and force user to query availability again
													$('#option_selected_<?php echo esc_html($i+1); ?>').show();
													$('#selected_<?php echo esc_html($i+1); ?>').hide();
													$('#time_selected_<?php echo esc_html($i+1); ?>').html('Select Time');

													$('input[name="add[<?php echo esc_html($i+1); ?>][book_time]"]').val('');
												}
											});

										} else {
											$('input[name="add['+<?php echo esc_html($i); ?>+'][book_time]"]').attr('disabled', true);
											$('#package_time_select_<?php echo esc_html($i); ?>').slideUp();
											loadNextCal();
										}
										
										function loadNextCal(){
											// assign select type
											let type;
										<?php if (isset($select_type_combination[$i+1])) { ?>
											type = '<?php 
												if (count(array_intersect((array)$select_type_combination[$i+1], $calendar_selects)) > 0) {
													echo 'calendar';
												} elseif ($select_type[$i+1] === 'single_date'){
													echo 'single';
												} elseif ($select_type[$i+1] === 'open_date'){
													echo 'open';
												}
											?>';
										<?php } ?>

											if (type == 'single' || type == 'open') {
												// show loading container 
												$('.package-option-loading-container-<?php echo esc_html($i+1); ?>').fadeIn();
												$('#select_option_<?php echo esc_html($i+1); ?>').addClass('restrict');
											}

											// try loading the next calendar
											$('#package-container-<?php echo esc_html($i+1); ?>').removeClass('locked');

											$('#rezgo_calendar_wrp_<?php echo esc_html($i+1); ?>').slideDown();

											$.ajax({
												url: '<?php echo admin_url('admin-ajax.php'); ?>',
												data: {
													<?php echo isset($package_item_com[$i+1]) ? 'uid: '. $package_item_com[$i+1]. ',' : ''; ?>
													action: 'rezgo',
													method: 'calendar_month',
													package: '<?php echo esc_html($item->uid); ?>',
													packageCalendar: <?php echo esc_html($i+1); ?>,
													<?php echo isset($bookable[$i+1]) ? 'bookable:' .'"'.esc_html($bookable[$i+1]).'"'. ',' : ''; ?>
													<?php echo isset($within[$i+1]) ? 'within:' .'"'.esc_html($within[$i+1]).'"'. ',' : ''; ?>
													chosenDate: single_date ? single_date : chosenDate,
													security: '<?php echo wp_create_nonce('rezgo-nonce'); ?>',
												},
												context: document.body,
												success: function(data) {

													// hide loading container 
													$('.responsive-calendar-loading-container-<?php echo esc_attr($i+1); ?>').fadeOut();
													$('#select_option_<?php echo esc_html($i+1); ?>').removeClass('restrict');

													$('#rezgo-date-script-<?php echo esc_html($i+1); ?>').html(data);
													let start_date = $('#start_date_<?php echo esc_html($i+1); ?>').text();
													let start_day = parseInt($('#start_day_<?php echo esc_html($i+1); ?>').text());
													let start_month = parseInt($('#start_month_<?php echo esc_html($i+1); ?>').text());
													let start_year = parseInt($('#start_year_<?php echo esc_html($i+1); ?>').text());

													// get end_time from previously selected option if there is spacing set
													let end_time = $('#end_time_<?php echo esc_html($i); ?>').text();

													// check if there is a multiple start time option in the previous package option
													let selected_time_status, selected_time, selected_duration;

													selected_time_status = /((1[0-2]|0?[1-9]):([0-5][0-9]) ?([AaPp][Mm]))/.test($('#time_selected_<?php echo esc_html($i); ?>').text());
													if (selected_time_status) {
														selected_time = $('#time_selected_<?php echo esc_html($i); ?>').text();
														selected_duration = $('#time_selected_<?php echo esc_html($i); ?>').data('duration');
													} 

													// determine what date to send
													if (type === 'calendar'){
														date = this_date;
													} else if(type === 'single'){
														date = package_date_options;
													} else if(type === 'open'){
														date = tomorrow;
													}

													function getPackageOptions(date){
														$.ajax({
															url: '<?php echo admin_url('admin-ajax.php'); ?>',
															data: {
																action: 'rezgo',
																method: 'package_ajax',
																rezgoAction: 'price',
																<?php echo isset($package_item_com[$i+1]) ? 'com: '.esc_html($package_item_com[$i+1]). ',' : ''; ?>
																date: date,
																type: type,
																js_timestamp: js_timestamp,
																js_timezone: js_timezone,
																date_format: '<?php echo esc_html($date_format); ?>',
																time_format: '<?php echo esc_html($time_format); ?>',
																pax: pax_obj,
																<?php echo isset($bookable[$i+1]) ? 'bookable:' .'"'.esc_html($bookable[$i+1]).'"'. ',' : ''; ?>
																<?php echo isset($within[$i+1]) ? 'within:' .'"'.esc_html($within[$i+1]).'"'. ',' : ''; ?>
																<?php echo isset($spacing[$i+1]) ? 'spacing:' .'"'.esc_html($spacing[$i+1]).'"'. ',' : ''; ?>
																end_time: end_time,
																selected_time: selected_time ? selected_time : '',
																selected_duration: selected_duration ? selected_duration : '',
																chosenDate: date,
																discount: '<?php echo esc_html($item->packages->discount); ?>',
																package: '<?php echo esc_html($item->uid); ?>',
																package_index: <?php echo esc_html($i+1); ?>,
																security: '<?php echo wp_create_nonce('rezgo-nonce'); ?>',
															},
															context: document.body,
															success: function(result) {

																// hide loading container 
																setTimeout(() => {
																	$('.package-option-loading-container-<?php echo esc_html($i+1); ?>').fadeOut();
																	$('#select_option_<?php echo esc_html($i+1); ?>').removeClass('restrict');
																}, 650);

																// if there are no available package results 
																if (result.trim() !== 'null'){
																	result = JSON.parse(result);

																	// empty all entries if there are previous options present
																	$('#package-option-container-<?php echo esc_html($i+1); ?>').empty();
																	$('#package-time-container-<?php echo esc_html($i+1); ?>').empty();

																	if (result !== 0) {
																		let options = result.options;
																		let total_options = options.length;

																		// if there are options returned, populate list
																		options.forEach(obj => {
																			console.log(obj);
																			let single_date = obj.single_date ? '&nbsp; <span class="option-single-date">('+ obj.single_date +')</span>' : '';
																			let open_date = obj.open_date ? '&nbsp; <span class="option-open-avail">('+ obj.open_date +')</span>' : '';
																			let open_date_indicator = obj.open_date ? 1 : '';
																			obj.time_format = obj.time_format ?? 0; 
																			obj.book_time = obj.book_time ?? 0; 
																			obj.hide_av = obj.hide_av ?? 0; 

																			let append = 
																				`<p onclick="choose_opt_<?php echo esc_html($i+1); ?>_${obj.index}(${obj.index},${obj.uid});"
																					data-item="${obj.item}" 
																					data-name="${obj.name}" 
																					data-com="${obj.com}" 
																					data-date="${obj.chosen_date}" 
																					data-bookable="${obj.bookable}" 
																					data-within="${obj.within}" 
																					data-single-date="${obj.single_date}" 
																					data-open-date="${open_date_indicator}" 
																					data-time-format="${obj.time_format}" 
																					data-book-time="${obj.book_time}" 
																					data-start-time="${obj.start_time}" 
																					data-hide-av="${obj.hide_av}" 
																					data-end-time="${obj.end_time}" 
																					data-duration="${obj.duration}" 
																					data-id="${obj.uid}" 
																					data-amount="${obj.option_total}" 
																					data-total-options="${total_options}" 
																						id="opt_select_<?php echo esc_html($i+1); ?>_${obj.index}"><span class="option-name">${obj.name}${single_date}${open_date}</span><span class="option-total-formatted">+ ${obj.option_total_formatted}</span>
																				</p>`;

																			$('#package-option-container-<?php echo esc_html($i+1); ?>').append(append);
																		});
																		
																	} else {
																		$('#option_selected_<?php echo esc_html($i+1); ?>').text('No Available Options');
																	}
																	
																} else {
																	$('#option_selected_<?php echo esc_html($i+1); ?>').text('No Available Options');
																}
															}
														});
													}

													// autoselects date if there is just one available day, else let the user select the date normally
													if ($('#within_one_date_<?php echo esc_html($i+1); ?>').length){

														let within_one_day = parseInt($('#within_one_day_<?php echo esc_html($i+1); ?>').text());
														let within_one_month = parseInt($('#within_one_month_<?php echo esc_html($i+1); ?>').text());
														let within_one_year = parseInt($('#within_one_year_<?php echo esc_html($i+1); ?>').text());

														setTimeout(() => {
															$('.responsive-calendar-<?php echo esc_html($i+1); ?> a[data-day="'+within_one_day+'"][data-month="'+within_one_month+'"][data-year="'+within_one_year+'"]').parent().addClass('select');
														}, 250);
														
														within_one_date = within_one_year+'-'+ addLeadingZero(within_one_month) +'-'+ addLeadingZero(within_one_day);

														$('input[name="add[<?php echo esc_html($i+1); ?>][date]"]').val(within_one_date);
														getPackageOptions(within_one_date);
													}

													// only execute if single/open type item and there is a subsequent item after
													<?php if (isset($select_type[$i+1]) && isset($package_item_com[$i+1])) { ?>
														<?php if ($select_type[$i+1] !== 'calendar_date' && $package_item_com[$i+1]) { ?>
															// get prices and available options
															getPackageOptions(date);
														<?php } ?>
													<?php } ?>
												}
											});
										}

									}

								<?php } ?>

								$('.responsive-calendar-<?php echo esc_html($i); ?>').responsiveCalendar({
									time: '<?php echo esc_html($calendar_start); ?>', 
									startFromSunday: <?php echo (($company->start_week == 'mon') ? 'false' : 'true') ?>,
									allRows: false,
									monthChangeAnimation: false,
									events: {},
									onDayClick: function(events) {

										// reset error
										$('.rezgo-package-date-error-<?php echo esc_html($i); ?>').slideUp();

										// show loading container 
										setTimeout(() => {
											$('.package-option-loading-container-<?php echo esc_html($i); ?>').fadeIn();
											$('#select_option_<?php echo esc_html($i); ?>').addClass('restrict');
										}, 150);

										if ($('#package-option-container-<?php echo esc_html($i); ?>').hasClass('open')){
											$('#package-option-container-<?php echo esc_html($i); ?>').removeClass('open')
										}
										if ($('#package-time-container-<?php echo esc_html($i); ?>').hasClass('open')){
											$('#package-time-container-<?php echo esc_html($i); ?>').removeClass('open');
										}
										$('#package_time_select_<?php echo esc_html($i); ?>').slideUp();

										let parent_calendar = $(this).closest('.responsive-calendar-<?php echo esc_html($i); ?>');
										parent_calendar.find('.active').removeClass('select');
										$(this).parent().addClass('select');

										let this_date, this_class;
									
										this_date = $(this).data('year')+'-'+ addLeadingZero($(this).data('month')) +'-'+ addLeadingZero($(this).data('day'));

										this_class = events[this_date].class;

										if (this_class == 'passed') {
											//$('.rezgo-date-selector').html('<p class="lead">This day has passed.</p>').show();
										} else if (this_class == 'cutoff') {
											//$('.rezgo-date-selector').html('<p class="lead">Inside the cut-off.</p>').show();
										} else if (this_class == 'unavailable') {
											//$('.rezgo-date-selector').html('<p class="lead">No tours available on this day.</p>').show();
										} else if (this_class == 'full') {
											//$('.rezgo-date-selector').html('<p class="lead">This day is fully booked.</p>').show();

										} else {

											$('.rezgo-date-options').html('<div class="rezgo-date-loading"></div>');

											if($('.rezgo-date-selector').css('display') == 'none') {
												$('.rezgo-date-selector').slideDown('fast');
											}

											// fill input
											$('input[name="add[<?php echo esc_html($i); ?>][date]"]').val(this_date);
											// empty chosen option input if one was selected previously
											$('input[name="add[<?php echo esc_html($i); ?>][uid]"]').val('');

											<?php 
											// refresh next calendar events if there is a bookable element
											if (isset($bookable[$i+1])) { ?>
												$('.responsive-calendar-<?php echo esc_html($i+1); ?>').responsiveCalendar('clearAll');
												let parent_calendar = $('.responsive-calendar-<?php echo esc_html($i+1); ?>');
												parent_calendar.find('div.day').removeClass('select');
												parent_calendar.find('div.day').removeClass('full');

												$('input[name="add[<?php echo esc_html($i+1); ?>][date]"]').val('');
												$('input[name="add[<?php echo esc_html($i+1); ?>][uid]"]').val('');

												// empty all entries
												$('#option_selected_<?php echo esc_html($i+1); ?>').show();
												$('#selected_<?php echo esc_html($i+1); ?>').hide();

												$('#package-option-container-<?php echo esc_html($i+1); ?>').empty();

												$('#item_selected_<?php echo esc_html($i+1); ?>').hide();
											<?php } ?>

											// get end_time from previously selected option if there is spacing set
											let end_time = $('#end_time_<?php echo esc_html($i-1); ?>').text();

											// check if there is a multiple start time option in the previous package option
											let selected_time_status, selected_time, selected_duration;

											selected_time_status = /((1[0-2]|0?[1-9]):([0-5][0-9]) ?([AaPp][Mm]))/.test($('#time_selected_<?php echo esc_html($i-1); ?>').text());
											if (selected_time_status) {
												selected_time = $('#time_selected_<?php echo esc_html($i-1); ?>').text();
												selected_duration = $('#time_selected_<?php echo esc_html($i-1); ?>').data('duration');
											} 

											<?php if (isset($bookable[$i+1]) && $bookable[$i+1] == 'first') { ?>
												let chosenDate = date_obj['date_1'];
											<?php } else { ?>
												let chosenDate = this_date;
											<?php } ?>

											// empty all entries if there are previous options present
											$('#package-option-container-<?php echo esc_html($i); ?>').empty();
											$('#package-time-container-<?php echo esc_html($i); ?>').empty();
											$('#option_selected_<?php echo esc_html($i); ?>').show();
											$('#selected_<?php echo esc_html($i); ?>').hide();
												
											$('#package-uid-input_<?php echo esc_html($i); ?>').val('');

											// get prices and available options
											$.ajax({
												url: '<?php echo admin_url('admin-ajax.php'); ?>',
												data: {
													action: 'rezgo',
													method: 'package_ajax',
													rezgoAction: 'price',
													com: $('.responsive-calendar-<?php echo esc_html($i); ?>').data('com'),
													date: this_date,
													type: 'calendar',
													js_timestamp: js_timestamp,
													js_timezone: js_timezone,
													date_format: '<?php echo esc_html($date_format); ?>',
													time_format: '<?php echo esc_html($time_format); ?>',
													pax: pax_obj,
													<?php echo isset($bookable[$i+1]) ? 'bookable:' .'"'.esc_html($bookable[$i+1]).'"'. ',' : ''; ?>
													<?php echo isset($within[$i+1]) ? 'within:' .'"'.esc_html($within[$i+1]).'"'. ',' : ''; ?>
													<?php echo isset($spacing[$i]) ? 'spacing:' .'"'.esc_html($spacing[$i]).'"'. ',' : ''; ?>
													end_time: end_time,
													selected_time: selected_time ? selected_time : '',
													selected_duration: selected_duration ? selected_duration : '',
													chosenDate: this_date,
													discount: '<?php echo esc_html($item->packages->discount); ?>',
													package: '<?php echo esc_html($item->uid); ?>',
													package_index: <?php echo esc_html($i); ?>,
													security: '<?php echo wp_create_nonce('rezgo-nonce'); ?>',
												},
												context: document.body,
												success: function(result) {

													// hide loading container 
													setTimeout(() => {
														$('.package-option-loading-container-<?php echo esc_html($i); ?>').fadeOut();
														$('#select_option_<?php echo esc_html($i); ?>').removeClass('restrict');
													}, 650);

													// if there are no available package results 
													if (result.trim() !== 'null'){
														result = JSON.parse(result);
														
														// empty all entries if there are previous options present
														$('#package-option-container-<?php echo esc_html($i); ?>').empty();
														$('#package-time-container-<?php echo esc_html($i); ?>').empty();


														if (result !== 0) {
															let options = result.options;
															let total_options = options.length;

															// if there are options returned, populate list
															options.forEach(obj => {
																console.log(obj);
																let single_date = obj.single_date ? '&nbsp; <span class="option-single-date">('+ obj.single_date +')</span>' : '';
																let open_date = obj.open_date ? '&nbsp; <span class="option-open-avail">('+ obj.open_date +')</span>' : '';
																let open_date_indicator = obj.open_date ? 1 : '';
																obj.time_format = obj.time_format ?? 0; 
																obj.book_time = obj.book_time ?? 0; 
																obj.hide_av = obj.hide_av ?? 0; 
																	
																let append = 
																	`<p onclick="choose_opt_<?php echo esc_html($i); ?>_${obj.index}(${obj.index},${obj.uid});"
																		data-item="${obj.item}" 
																		data-name="${obj.name}" 
																		data-com="${obj.com}" 
																		data-date="${obj.chosen_date}" 
																		data-bookable="${obj.bookable}" 
																		data-within="${obj.within}" 
																		data-single-date="${obj.single_date}" 
																		data-open-date="${open_date_indicator}" 
																		data-time-format="${obj.time_format}" 
																		data-book-time="${obj.book_time}" 
																		data-start-time="${obj.start_time}" 
																		data-hide-av="${obj.hide_av}" 
																		data-end-time="${obj.end_time}" 
																		data-duration="${obj.duration}" 
																		data-id="${obj.uid}" 
																		data-amount="${obj.option_total}" 
																		data-total-options="${total_options}" 
																			id="opt_select_<?php echo esc_html($i); ?>_${obj.index}"><span class="option-name">${obj.name}${single_date}${open_date}</span><span class="option-total-formatted">+ ${obj.option_total_formatted}</span>
																	</p>`;

																// reset text 
																if (package_custom_text_<?php echo esc_html($i); ?>) {
																	$('#option_selected_<?php echo esc_html($i); ?>').text(package_custom_text_<?php echo $i; ?>);
																} else {
																	$('#option_selected_<?php echo esc_html($i); ?>').text('Select Option <?php echo esc_html($i+1); ?>');
																}
																$('#package-option-container-<?php echo esc_html($i); ?>').append(append);
															});

														} else {
															$('#option_selected_<?php echo esc_html($i); ?>').text('No Available Options');
														}
														
													} else {
														$('#option_selected_<?php echo esc_html($i); ?>').text('No Available Options');
													}
												}
											});

										}
									
									},

									onActiveDayClick: function(events) { 
									
										let parent_calendar = $(this).closest('.responsive-calendar-<?php echo esc_html($i); ?>');
										parent_calendar.find('.active').removeClass('select');
										$(this).parent().addClass('select');

									},

									onMonthChange: function(events) {

										// show loading container 
										$('.responsive-calendar-loading-container-<?php echo esc_html($i); ?>').show();

										if ($('#package-option-container-<?php echo esc_html($i); ?>').hasClass('open')){
											$('#package-option-container-<?php echo esc_html($i); ?>').removeClass('open')
										}

										if ($('#package-time-container-<?php echo esc_html($i); ?>').hasClass('open')){
											$('#package-time-container-<?php echo esc_html($i); ?>').removeClass('open')
										}

										// send next month to fetch availabilty
										let nextMonth = $(this)[0].currentYear + '-' + addLeadingZero($(this)[0].currentMonth + 1);

											$.ajax({
												url: '<?php echo admin_url('admin-ajax.php'); ?>',
												data: {
													action: 'rezgo',
													method: 'calendar_month',
													uid: '<?php echo esc_html($choices[$i]->id); ?>',
													date: nextMonth,
													package: '<?php echo esc_html($item->uid); ?>',
													packageCalendar: <?php echo esc_html($i); ?>,
												<?php if ($i >= 1) { ?>
													<?php echo $bookable[$i] ? 'bookable:' .'"'.$bookable[$i].'"'. ',' : ''; ?>
													<?php echo $within[$i] ? 'within:' .'"'.$within[$i].'"'. ',' : ''; ?>
													chosenDate: date_obj.date_<?php echo esc_html($i); ?>,
												<?php } ?>
												security: '<?php echo wp_create_nonce('rezgo-nonce'); ?>',
												},
												context: document.body,
												success: function(data) {

													// hide loading container 
													$('.responsive-calendar-loading-container-<?php echo esc_html($i); ?>').fadeOut();

													$('#rezgo-date-script-<?php echo esc_html($i); ?>').html(data); 
												}
											});
										
									},
								});
								
								$('#reset_date_<?php echo esc_html($i); ?>').click(function(){

									$('#package-date-<?php echo esc_html($i); ?>').hide().empty();
									$('#option_selected_<?php echo esc_html($i); ?>').show();
									$('#selected_<?php echo esc_html($i); ?>').hide();
									$(this).hide();

									$('.responsive-calendar-<?php echo esc_html($i); ?>').find('.days .day').each(function () {
										$(this).removeClass('select');
									});

									$('#rezgo_calendar_wrp_<?php echo esc_html($i); ?>').slideDown();
									$('#package-container-<?php echo esc_html($i); ?>').removeClass('locked');
									$('#item_selected_<?php echo esc_html($i); ?>').hide();

									// reset input
									$('input[name="add[<?php echo esc_html($i); ?>][date]"]').val('');

									// empty all entries if there are previous options present
									$('#package-option-container-<?php echo esc_html($i); ?>').empty();
									$('#package-time-container-<?php echo esc_html($i); ?>').empty();
								});

								$('#select_option_<?php echo esc_html($i); ?>').click(function(){

									// console.log('<?php echo esc_html($select_type[$i]); ?>');
									let err;
									let select_type = decodeURIComponent( '<?php echo rawurlencode( $select_type[$i] ); ?>' );
									
									$('#package-time-container-<?php echo esc_html($i); ?>').removeClass('open');

									if($('#package-option-container-<?php echo esc_html($i); ?>').children().length > 0){
										$('#package-option-container-<?php echo esc_html($i); ?>').toggleClass('open');
									// check if date is selected/ and if there are selectable dates
									} else if (select_type === 'calendar_date' && $('.responsive-calendar-<?php echo esc_html($i); ?>').find('.select').length === 0 && $('.responsive-calendar-<?php echo esc_html($i); ?>').find('.active').length >= 1){
										err = 'Please select a date first';
									// check if there are available options
									} else if($('#package-option-container-<?php echo esc_html($i); ?>').children().length === 0) {
										err = 'Sorry, there are no available options';
									}

									if (err){
										$('.rezgo-package-option-error-<?php echo esc_html($i); ?>').html(err);
										$('.rezgo-package-option-error-<?php echo esc_html($i); ?>').slideDown();
										setTimeout(() => {
											$('.rezgo-package-option-error-<?php echo esc_html($i); ?>').slideUp();
										}, 2500);
									}
								});

								$('#select_time_<?php echo esc_html($i); ?>').click(function(){

									let err;

									$('#package-option-container-<?php echo esc_html($i); ?>').removeClass('open');

									if($('#package-time-container-<?php echo esc_html($i); ?>').children().length > 0){
										$('#package-time-container-<?php echo esc_html($i); ?>').toggleClass('open');
									} else if($('#package-time-container-<?php echo esc_html($i); ?>').children().length === 0) {
										err = 'Sorry, there are no available times';
									}
									
								});

								$('#package-option-container-<?php echo esc_html($i); ?> > p').click(function(){
									$('#option_selected_<?php echo esc_html($i); ?>').html($(this).text());
									$('#package-option-container-<?php echo esc_html($i); ?>').removeClass('open');
								});
								$('#package-time-container-<?php echo esc_html($i); ?> > p').click(function(){
									$('#time_selected_<?php echo esc_html($i); ?>').html($(this).text());
									$('#package-time-container-<?php echo esc_html($i); ?>').removeClass('open');
								});

							});

							</script>

							<?php $i++; } ?>

							<i id="date_icon" class="far fa-calendar-day"></i>
						</div> 

						<div id="loading-avail-div" style="display:none;">
							<span id="loading-avail-header">Fetching Availability</span> &nbsp;
							<i class="fad fa-circle-notch fa-spin"></i>
						</div>
					</div>

					<?php
						if(REZGO_LITE_CONTAINER) {
							if ($_REQUEST['cross_sell']) {
								$form_target = 'target="_parent"';
							} else {
								$form_target = 'target="rezgo_content_frame"';
							}
						} else {
							$form_target = ''; 
						}
					?>

					<form class="rezgo-order-form" method="post" id="checkout_package" <?php echo esc_attr($form_target); ?>>

						<?php $cart_package_uid = rand(); ?>

						<!-- prepare fields to add package to cart  -->
						<?php for ($i=0; $i < count($package_items); $i++) { ?>
							
							<input id="package-uid-input_<?php echo esc_attr($i); ?>" class="package-uid-input hidden-inputs" type="hidden" name="add[<?php echo esc_attr($i); ?>][uid]" value="">
							<input id="date_input_<?php echo esc_attr($i); ?>" class="package-date-input hidden-inputs" type="hidden" name="add[<?php echo esc_attr($i); ?>][date]" value="">
							<input class="package-time-input hidden-inputs" type="hidden" name="add[<?php echo esc_attr($i); ?>][book_time]" value="">
							<input class="package-input" type="hidden" name="add[<?php echo esc_attr($i); ?>][package]" value="<?php echo esc_attr($package_id); ?>">
							<input class="cart-package-uid-input" type="hidden" name="add[<?php echo esc_attr($i); ?>][cart_package_uid]" value="<?php echo esc_attr($cart_package_uid); ?>">

							<?php foreach ($package_prices->price as $price) { ?>
								<?php $price = str_replace('price_', '', $price->type); ?>
								<input class="pax_input_<?php echo esc_attr($price); ?> hidden-inputs" type="hidden" name="add[<?php echo esc_attr($i); ?>][<?php echo esc_attr($price); ?>_num]" value=""> 
							<?php } ?>

						<?php } ?>

						<div id="rezgo-package-date-error" class="rezgo-package-error"  style="display:none;"></div>

						<div class="rezgo-btn-add-wrap" style="display:none;">
							<br>
							<span class="btn-check"></span>
							<button type="submit" class="btn btn-block rezgo-btn-book rezgo-btn-add"><span>Add To Order</span></button>

							<p id="package_total"></p>
						</div>
					</form>

					<script>

					jQuery(function($){
						$('#select_guests').click(function(){
							// show warning
							if( $(this).hasClass('locked') ){
								$('#reset_pax_warning').slideDown(150);
							}
						});

						// load first options here
						$('#close_pax_select').click(function(){

							// only apply loading state on the first option
							$('.package-option-loading-container-0').show();
							$('#select_option_0').addClass('restrict');

							console.log('type: <?php echo esc_html($select_type[0]); ?>');

							$('#package-container-0').removeClass('locked');

							// reopen first calendar
							$('#rezgo_calendar_wrp_0').slideDown();

							let err;
							let count = 0;
							let required = 0;
							
							for(v in fields_<?php echo esc_html($package_id); ?>) {
								// total number of spots
								count += $('#' + v).val() * 1;
								// has a required price point been used
								if(fields_<?php echo esc_html($package_id); ?>[v] && $('#' + v).val() >= 1) { required = 1; }
							}

							if (filtered_pax.length === 0){
								err = 'Please select at least one price point';
							} else if(required_num_<?php echo esc_html($package_id); ?> > 0 && required == 0) {
								err = 'At least one marked ( * ) price point is required to book';
							} else if(count < <?php echo esc_html($item->per); ?>) {
								err = '<?php echo esc_html($item->per); ?> minimum required to book.';
							}  else if(count > 250) {
								err = 'You cannot book more than 250 spaces in a single booking.';
							}
							<?php if ($item->max_guests > 0) { ?>
							else if(count > <?php echo esc_html($item->max_guests); ?>) {
								err = 'There is a maximum of <?php echo esc_html($item->max_guests); ?> per booking.';
							}
							<?php } ?>

							if (err){
								$('#rezgo-package-pax-error').html(err);
								$('#rezgo-package-pax-error').slideDown();

								setTimeout(() => {
									$('#rezgo-package-pax-error').slideUp(500);
								}, 5000);
							} else {

								$('#select_date').slideDown();

								// lock pax changes 
								$('.package-pax-select').slideUp(); 

								$('#select_guests').toggleClass('active');
								$('#select_guests').find('#guest_icon').removeClass().addClass('far fa-check-circle');
								$('#select_guests').find('#mobile_edit_guests').show();
								$('#select_guests').addClass('locked');

									// fetch first calendar avail
									$.ajax({
										url: '<?php echo admin_url('admin-ajax.php'); ?>',
										data: {
											action: 'rezgo',
											method: 'calendar_month',
											date: '<?php echo esc_html($calendar_start); ?>',
											uid: '<?php echo esc_html($choices[0]->id); ?>',
											packageCalendar: '0',
											package: '<?php echo esc_html($item->uid); ?>',
											package_index: 0,
											security: '<?php echo wp_create_nonce('rezgo-nonce'); ?>',
										},
										context: document.body,
										success: function(data) {

											// hide loading container 
											$('.responsive-calendar-loading-container-0').fadeOut();

											$('#rezgo-date-script-0').html(data); 
											let start_date = $('#start_date_0').text();
											let start_day = parseInt($('#start_day_0').text());
											let start_month = parseInt($('#start_month_0').text());
											let start_year = parseInt($('#start_year_0').text());

											<?php if (count(array_intersect($select_type_combination[0], $calendar_selects)) > 0) { ?>

												setTimeout(() => {
													// autoselect first available date
													$('.responsive-calendar-0 a[data-day="'+start_day+'"][data-month="'+start_month+'"][data-year="'+start_year+'"]').click();

													let selected = $('.responsive-calendar-0 .select a');

													this_date = selected.data('year')+'-'+ addLeadingZero(selected.data('month')) +'-'+ addLeadingZero(selected.data('day'));
												}, 100);

											<?php } ?>

											setTimeout(() => {

												// assign select type
												let type = '<?php 
													if (count(array_intersect($select_type_combination[0], $calendar_selects)) > 0) { 
														echo 'calendar';
													} elseif ($select_type[0] === 'single_date'){
														echo 'single';
													} elseif ($select_type[0] === 'open_date'){
														echo 'open';
													}
												?>';

												// determine what date to send
												if (type === 'calendar'){
													date = this_date;
												} else if(type === 'single'){
													date = package_date_options;
												} else if(type === 'open'){
													date = tomorrow;
												}

												this_date = start_year +'-'+ addLeadingZero(start_month);

												// fetch first avail options here
												$.ajax({
													url: '<?php echo admin_url('admin-ajax.php'); ?>',
													data: {
														action: 'rezgo',
														method: 'package_ajax',
														rezgoAction: 'price',
														<?php echo isset($package_item_com[0]) ? 'com: '.esc_html($package_item_com[0]). ',' : ''; ?>
														date: date,
														type: type,
														js_timestamp: js_timestamp,
														js_timezone: js_timezone,
														date_format: '<?php echo esc_html($date_format); ?>',
														time_format: '<?php echo esc_html($time_format); ?>',
														pax: pax_obj,
														<?php echo isset($bookable[1]) ? 'bookable:' .'"'.esc_html($bookable[1]).'"'. ',' : ''?>
														<?php echo isset($within[1]) ? 'within:' .'"'.esc_html($within[1]).'"'. ',' : ''?>
														bookable: '',
														within: '',
														spacing: '',
														end_time: '',
														selected_time: '',
														selected_duraction: '',
														chosenDate: date,
														discount: '<?php echo esc_html($item->packages->discount); ?>',
														package: '<?php echo esc_html($item->uid); ?>',
														package_index: 0,
														security: '<?php echo wp_create_nonce('rezgo-nonce'); ?>',
													},
													context: document.body,
														success: function(result) {

															// hide loading container 
															setTimeout(() => {
																$('.package-option-loading-container-0').fadeOut();
																$('#select_option_0').removeClass('restrict');
															}, 650);

															// if there are no available package results 
															if (result.trim() !== 'null'){
																result = JSON.parse(result);

																// empty all entries if there are previous options present
																$('#package-option-container-0').empty();

																if (result !== 0) {
																	let options = result.options;
																	let total_options = options.length;

																	// if there are options returned, populate list
																	options.forEach(obj => {
																		console.log(obj);
																		let single_date = obj.single_date ? '&nbsp; <span class="option-single-date">('+ obj.single_date +')</span>' : '';
																		let open_date = obj.open_date ? '&nbsp; <span class="option-open-avail">('+ obj.open_date +')</span>' : '';
																		let open_date_indicator = obj.open_date ? 1 : '';
																		obj.time_format = obj.time_format ?? 0; 
																		obj.book_time = obj.book_time ?? 0; 
																		obj.hide_av = obj.hide_av ?? 0; 
																		
																		let append = 
																		`<p onclick="choose_opt_0_${obj.index}(${obj.index},${obj.uid});"
																			data-item="${obj.item}" 
																			data-name="${obj.name}" 
																			data-com="${obj.com}" 
																			data-date="${obj.chosen_date}" 
																			data-bookable="${obj.bookable}" 
																			data-within="${obj.within}" 
																			data-single-date="${obj.single_date}" 
																			data-open-date="${open_date_indicator}" 
																			data-time-format="${obj.time_format}" 
																			data-book-time="${obj.book_time}" 
																			data-start-time="${obj.start_time}" 
																			data-hide-av="${obj.hide_av}" 
																			data-end-time="${obj.end_time}" 
																			data-duration="${obj.duration}" 
																			data-id="${obj.uid}" 
																			data-amount="${obj.option_total}" 
																			data-total-options="${total_options}" 
																				id="opt_select_0_${obj.index}"><span class="option-name">${obj.name}${single_date}${open_date}</span><span class="option-total-formatted">+ ${obj.option_total_formatted}</span>
																		</p>`;

																		$('#package-option-container-0').append(append);
																	});

																} else {
																	$('#option_selected_0').text('No Available Options');
																}

															} else {
																$('#option_selected_0').text('No Available Options');
															}
														}
													});
												}, 100);

										}
									});

								$('.rezgo-btn-add-wrap').show();
							}

						});

						$('#reset_pax').click(function(){
							// reset all pax numbers
							$('.rezgo-order-form input[type=number]').each(function(){
								$(this).val('');
								$('#guests').html('');
								$('#guests_selected').find('.guests-placeholder').show();
							});
							for(v in fields_<?php echo esc_html($package_id); ?>) {
								$('#increase_'+v).removeClass('not-allowed');
								$('#decrease_'+v).addClass('not-allowed');
							}

							// reset pax object
							pax_obj = {
								'adult':0,
								'child':0,
								'senior':0,
								'price4':0,
								'price5':0,
								'price6':0,
								'price7':0,
								'price8':0,
								'price9':0,
							};
							filtered_pax = [];
						});

						$('#warning_reset_pax').click(function(){

							// reset pax choices
							$('.package-pax-select').slideDown(); 
							$('#select_guests').toggleClass('active');
							$('#reset_pax_warning').hide();
							$('#select_guests').find('#guest_icon').removeClass().addClass('far fa-user-circle');
							$('#select_guests').find('#mobile_edit_guests').hide();
							$('#select_guests').removeClass('locked');

							// reset all pax
							$('.rezgo-order-form input[type=number]').each(function(){
								$(this).val('');
								$('#guests').html('');
								$('#guests_selected').find('.guests-placeholder').show();
							});
							for(v in fields_<?php echo esc_html($package_id); ?>) {
								$('#increase_'+v).removeClass('not-allowed');
								$('#decrease_'+v).addClass('not-allowed');
							}

							// reset pax object
							pax_obj = {
								'adult':0,
								'child':0,
								'senior':0,
								'price4':0,
								'price5':0,
								'price6':0,
								'price7':0,
								'price8':0,
								'price9':0,
							};
							filtered_pax = [];

							// resets all previously selected calendar dates
							$('.responsive-calendar').find('.active').removeClass('select');

							// resets all selections
							<?php for ($i=0; $i < count($package_items); $i++) { ?>
								$('#option_selected_<?php echo esc_html($i); ?>').show();
								$('#selected_<?php echo esc_html($i); ?>').hide();

								$('#time_selected_<?php echo esc_html($i); ?>').show();
								$('#selected_time_<?php echo esc_html($i); ?>').hide();
							<?php } ?>
							$('.hidden-inputs').val('');

							$('.package-date').hide().empty();
							$('.reset-date').hide();

							$('.package-time-select-container').hide();

							$('.package-container').addClass('locked');
							$('.package-option-container').empty();
							$('.item-selected-icon').hide();

							// hide dates again
							$('#select_date').hide();
							$('#select_date').toggleClass('active');
							$('#dates_selected').text('Select your dates & options');

							// hide add to order btn
							$('.rezgo-btn-add-wrap').hide();

							// reset total 
							for (const prop of Object.getOwnPropertyNames(package_total)) {
								delete package_total[prop];
							}
						});

						$('#close_options_select').click(function(){
							$('.date-select-container').toggle();
							$('.date-select-container').toggleClass('active');
							$('#select_date').toggleClass('active');
						});

						$('#reset_options').click(function(){
							$('#dates_selected').text('Select your dates & options');
							$('#select_date').find('.fa-check-circle').removeClass().addClass('far fa-angle-down');
						});

					});
					</script>
									
				</div>
					
			<?php } else { ?>
				
			<script>
				// track value of item added to cart
				let add_to_cart_total = 0;

				// price_tier array
				let price_tier_array = [
					'adult',
					'child',
					'senior',
					'price4',
					'price5',
					'price6',
					'price7',
					'price8',
					'price9',
				];
			</script>

			<?php if ( $calendar_dates > 0 || $single_dates > 10 ) { ?>
				<div class="hidden visible-xs">
					<span>&nbsp;</span>
				</div>

				<div class="rezgo-calendar-wrp">

					<div class="rezgo-calendar single-item">
						<div class="rezgo-calendar-header">
							<span id="date_header">
								<span>Select a Date</span>
							</span>
							<br>
						</div>
						<div class="responsive-calendar rezgo-calendar-<?php echo esc_attr($item->com); ?>" id="rezgo-calendar">
							<div class="controls">
								<a class="float-start" data-go="prev"><div class="fas fa-angle-left fa-lg"></div></a>
								<h4><span><span data-head-year></span> <span data-head-month></span></span></h4>
								<a class="float-end" data-go="next"><div class="fas fa-angle-right fa-lg"></div></a>
							</div>
							<?php if ($start_week == 'mon') { ?>
							<div class="day-headers">
								<div class="day header">Mon</div>
								<div class="day header">Tue</div>
								<div class="day header">Wed</div>
								<div class="day header">Thu</div>
								<div class="day header">Fri</div>
								<div class="day header">Sat</div>
								<div class="day header">Sun</div>
							</div>
							<?php } else { ?>
							<div class="day-headers">
								<div class="day header">Sun</div>
								<div class="day header">Mon</div>
								<div class="day header">Tue</div>
								<div class="day header">Wed</div>
								<div class="day header">Thu</div>
								<div class="day header">Fri</div>
								<div class="day header">Sat</div>
							</div>
							<?php } ?>
							<div class="days" data-group="days"></div>
						</div>
						<div class="rezgo-calendar-legend rezgo-legend-<?php echo esc_attr($item->com); ?>">
							<span class="available">&nbsp;</span><span class="text-available"><span>&nbsp;Available&nbsp;&nbsp;</span></span>
							<span class="full">&nbsp;</span><span class="text-full"><span>&nbsp;Full&nbsp;&nbsp;</span></span>
							<span class="unavailable">&nbsp;</span><span class="text-unavailable"><span>&nbsp;Unavailable</span></span>
							<div id="rezgo-calendar-memo"></div>
						</div>
						<div id="rezgo-scrollto-options"></div>
						<div class="rezgo-date-selector" style="display:none;">
							<!-- available options will populate here -->
							<div class="rezgo-date-options"></div>
						</div>
						<div id="rezgo-date-script" style="display:none;">
							<!-- ajax script will be inserted here -->
						</div>
					</div>
				</div>
			<?php } elseif ( ($calendar_dates == 0 || $single_dates <= 10) && $open_dates == 0 ) { // single day options ?>
				<div class="rezgo-calendar-wrp">
					<?php $opt = 1; // pass an option counter to calendar day ?>

					<?php foreach ($day_options as $option) { ?>
						<div class="rezgo-calendar-single rezgo-single-<?php echo esc_attr($item->com); ?>" id="rezgo-calendar-single-<?php echo esc_attr($opt); ?>" style="display:none;">
  						<div class="rezgo-calendar-single-head">
							<?php
								$available_day = date('D', $option['start_date']);
								$available_date = date((string) $date_format, $option['start_date']);
							?>
							<span class="rezgo-calendar-avail">
							<span>Availability&nbsp;for:&nbsp;</span>
							</span>
							<strong><span class="rezgo-avail-day"><?php echo esc_html($available_day); ?>,&nbsp;</span><span class="rezgo-avail-date"><?php echo esc_html($available_date); ?></span></strong>
						</div>

  						<div class="rezgo-date-selector" id="rezgo-single-date-<?php echo esc_attr($opt); ?>"></div>
						
						<script type="text/javascript">
							jQuery(document).ready(function($){
								$.ajax({
									url: '<?php echo admin_url('admin-ajax.php'); ?>',
									data: {
										action: 'rezgo',
										method: 'calendar_day',
										parent_url: '<?php echo esc_html($parent_url); ?>',
										com: '<?php echo esc_html($item->com); ?>',
										date: '<?php echo esc_html(date('Y-m-d', $option['start_date'])); ?>',
										option_num: '<?php echo esc_html($opt); ?>',
										type: 'single',
										date_format: '<?php echo esc_html($date_format); ?>',
										time_format: '<?php echo esc_html($time_format); ?>',
										security: '<?php echo wp_create_nonce('rezgo-nonce'); ?>'
									},
									context: document.body,
									success: function(data) {
										if (data.indexOf('rezgo-order-none-available') == -1) {
											$('#rezgo-single-date-<?php echo esc_html($opt); ?>').html(data).slideDown('fast');
											$('#rezgo-calendar-single-<?php echo esc_html($opt); ?>').fadeIn('fast');
											$('.option-panel-<?php echo esc_html($_REQUEST['option'] ?? ''); ?>').addClass('in');	
										}
									}
								});
							});
						</script> 
						</div>
						<?php $opt++; ?>
					<?php } // end foreach ($day_options) ?> 
					
					<div id="rezgo-single-memo"></div>
				</div><!-- // .rezgo-calendar-wrp -->
			<!-- // single day booking -->
			<?php } // end single dates > 0 ?>

			<?php } // if ($package) ?>
	
			<?php if ($site->showGiftCardPurchase()) { ?>
				<div id="rezgo-gift-link-use" class="rezgo-gift-link-wrp">
					<a class="rezgo-gift-link" href="<?php echo esc_url($site->base); ?>/gift-card">
						<span>
							<i class="far fa-gift fa-lg"></i>
							&nbsp;<span>Buy a gift card</span>
						</span>
					</a>
				</div>
			<?php } ?>

			<?php
				$ref_parts = explode('/?', $_SERVER['HTTP_REFERER'] ?? '');
				$promo_url = $ref_parts[0];
			?>

				<div id="rezgo-details-promo" class="rezgo-promo-<?php echo esc_attr($item->com); ?>"><!-- hidden by default -->
					<div class="rezgo-form-group-short">
						<?php $trigger_code = $site->cart_trigger_code; ?>
						<?php if (!isset($_COOKIE['rezgo_promo']) && !$trigger_code) { ?>
							<form class="form-inline" id="rezgo-promo-form" role="form">
								<label for="rezgo-promo-code">
									<span>
										<i class="fad fa-tags"></i>
										<span>&nbsp;</span>
										<span class="rezgo-promo-label">
											<span>Promo code</span>
										</span>
									</span>
								</label>
								<span>&nbsp;</span>
								<div class="rezgo-promo-input">
									<div class="input-group">
										<input type="text" class="form-control" id="rezgo-promo-code" name="promo" placeholder="Enter Promo Code" value="<?php echo (isset($_COOKIE['rezgo_promo']) ? esc_attr($_COOKIE['rezgo_promo']) : esc_attr($trigger_code)); ?>" required>
										<div class="input-group-btn">
												<span class="btn-check"></span>
												<button class="btn rezgo-btn-default" type="submit">
													<span>Apply</span>
												</button>
											</div>
										</div>
									</div>

								<?php if(isset($_COOKIE['cart_status'])) {
									$cart_status =  new SimpleXMLElement($_COOKIE['cart_status']);

									// cart only validates the promo code if there are items in the cart
									if (($cart_status->error_code == 9) || ($cart_status->error_code == 11)) { ?>
										<div id ="rezgo-promo-invalid" class="text-danger" style="padding-top:5px; font-size:13px;">
											<span><?php echo esc_html($cart_status->message); ?></span>
										</div>

										<script>
											// reset invalid promo error so it doesn't show on order page again
											setTimeout(() => {
												jQuery.ajax({
													type: 'POST',
													url: '<?php echo admin_url('admin-ajax.php'); ?>' + '?action=rezgo&method=book_ajax',
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
									<div class="input-group">
										<label for="rezgo-promo-code">
										<span class="rezgo-promo-label">
											<span>Promo applied:</span>
										</span>
										</label>
										<span>&nbsp;</span>
										<span id="rezgo-promo-value"><?php echo isset($_COOKIE['rezgo_promo']) ? $_COOKIE['rezgo_promo'] : $trigger_code ?></span>
										<span>&nbsp;</span> 

										<?php if (REZGO_LITE_CONTAINER) { ?>
											<button id="rezgo-promo-clear" class="btn <?php echo esc_attr($hidden); ?>"
											onclick="<?php echo LOCATION_WINDOW; ?>.location.href='<?php echo esc_html($site->base); ?><?php echo esc_html($promo_url); ?>/?promo='; return false;"><i class="fa fa-times"></i></button>
										<?php } else { ?>
											<button id="rezgo-promo-clear" class="btn <?php echo esc_attr($hidden); ?>" onclick="<?php echo LOCATION_HREF; ?>='<?php echo esc_html($promo_url); ?>?promo='" target="_parent"><i class="fa fa-times"></i></button>
										<?php } ?>  

								</div>

							<?php } ?>
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
								<?php echo LOCATION_WINDOW; ?>.location.href='<?php echo esc_html($site->base); ?><?php echo esc_html($promo_url); ?>/?promo=' + jQuery('#rezgo-promo-code').val();
							<?php } else { ?>
								<?php echo LOCATION_HREF; ?>='<?php echo esc_html($promo_url); ?>/?promo=' + jQuery('#rezgo-promo-code').val();
							<?php } ?>
						});

						<?php if (REZGO_LITE_CONTAINER) { ?>
							$('#rezgo-promo-clear').click(function(){
								$.ajax({
									type: 'POST',
									url: '<?php echo esc_html($site->base); ?>/book_ajax.php',
									data: { rezgoAction: 'update_promo' },
									success: function(data){
									}
								})	
							});
						<?php } ?>
				</script>

				<div id="rezgo-tour-map-lg">
					<div id="rezgo-tour-map-container" class="col-12">
					<?php if($site->exists($item->lat) && !REZGO_CUSTOM_DOMAIN && GOOGLE_API_KEY) { ?>

						<?php

							if (!$site->exists($item->zoom)) { 
								$map_zoom = 8; 
							} else { 
								$map_zoom = $item->zoom; 
							}
							
							if ($item->map_type == 'ROADMAP') {
								$embed_type = 'roadmap';
							} else {
								$embed_type = 'satellite';
							} 

						?>
							
						<div style="position:relative;">
							<div class="rezgo-map" id="rezgo-tour-map">
								<iframe width="100%" height="500" frameborder="0" style="border:0;margin-bottom:0;margin-top:-125px;" src="https://www.google.com/maps/embed/v1/place?key=<?php echo esc_attr(GOOGLE_API_KEY); ?>&maptype=<?php echo esc_attr($embed_type); ?>&q=<?php echo esc_attr($item->lat); ?>,<?php echo esc_attr($item->lon); ?>&center=<?php echo esc_attr($item->lat); ?>,<?php echo esc_attr($item->lon); ?>&zoom=<?php echo esc_attr($map_zoom); ?>"></iframe>
								<div class="rezgo-map-location rezgo-map-shadow">
									<?php if($item->location_name != '') { ?>
										<div class="rezgo-map-icon float-start">
											<i class="far fa-map-marker"></i>
										</div>
										<span> <?php echo esc_html($item->location_name); ?></span>
										<div class="rezgo-map-hr"></div>
									<?php } ?>
									
									<?php if($item->location_address != '') { ?>
										<div class="rezgo-map-icon float-start">
											<i class="far fa-location-arrow"></i>
										</div>
										<span> <?php echo esc_html($item->location_address); ?></span>
									<?php } else { ?>
										<div class="rezgo-map-icon float-start">
											<i class="far fa-location-arrow"></i>
										</div>
										<?php
											echo '
											'.($item->city != '' ? esc_html($item->city).', ' : '').'
											'.($item->state != '' ? esc_html($item->state).', ' : '').'
											'.($item->country != '' ? ucwords(esc_html($site->countryName(strtolower($item->country)))) : '');
										?>
									<?php } ?>
								</div>
							</div>
						</div>
					<?php } ?>

					<?php if(count($tourTags) > 0) { ?>
						<div id="rezgo-tour-tags">
							<?php
								$taglist = '';
								foreach($tourTags as $tag) { 
									if ($tag != '') {
										// we need to extract the slug if this happens to be on a nested page 
										$extract_slug = explode('/', $_REQUEST['wp_slug'] ?? '');
										$home_link = $extract_slug[0];
										$tag_link = (REZGO_WORDPRESS ? '/'.$home_link : $site->base).'/tag/'.urlencode($tag);
										$taglist .= '<a href="'.esc_url($tag_link).'" class="single-tag">'.$tag.'</a>';
									}
								}
								$taglist = trim($taglist, ', ');
								echo wp_kses($taglist, ALLOWED_HTML);
							?>
						</div>
					<?php } ?>
					</div>
				</div> <!-- rezgo-tour-map-lg --> 

			</div>
		</div> <!-- details-calendar-row -->

		<div id="rezgo-tour-map-sm"></div>
	</div>

	<script type="text/javascript">
		jQuery(document).ready(function($){

		<?php if($site->getTourRelated()) { ?>
			$('#rezgo-related-carousel').slick({ 
				slidesToShow: 3,
				slidesToScroll: 1,
				dots: false,
				adaptiveHeight: true,
				lazyLoad: 'ondemand',
				variableWidth: true,
				arrows: true,
				prevArrow: '',
				nextArrow: '<button class="slick-next"><i class="far fa-angle-right"></i></button>',
				speed: 300,
				responsive: [
					{
						breakpoint: 500,
						settings: {
							slidesToShow: 1,
							slidesToScroll: 1,
						}
					}
				]
			});
		<?php } ?>
		
			<?php if ($site->config('REZGO_MOBILE_XML')) { ?>
				$("#rezgo-tour-map-container").detach().appendTo('#rezgo-tour-map-sm');
			<?php } ?>

			function viewport() {
				var e = window, a = 'inner';
				if (!('innerWidth' in window )) {
					a = 'client';
					e = document.documentElement || document.body;
				}
				return { width : e[ a+'Width' ] , height : e[ a+'Height' ] };
			}

			if (viewport().width < 786) {
				$("#rezgo-tour-map-container").detach().appendTo('#rezgo-tour-map-sm');
			} else {
				$("#rezgo-tour-map-container").detach().appendTo('#rezgo-tour-map-lg');
			}

			// function returns Y-m-d date format
			(function() {
				Date.prototype.toYMD = Date_toYMD;
				function Date_toYMD() {
					let year, month, day;
					year = String(this.getFullYear());
					month = String(this.getMonth() + 1);
					if (month.length == 1) {
							month = "0" + month;
					}
					day = String(this.getDate());
					if (day.length == 1) {
							day = "0" + day;
					}
					return year + "-" + month + "-" + day;
				}
			})();
			
			// new Date() object for tracking months
			let rezDate = new Date(decodeURIComponent( '<?php echo rawurlencode( (string) $calendar_start ); ?>' )+'-15');
						
			function addLeadingZero(num) {
				if (num < 10) {
					return "0" + num;
				} else {
					return "" + num;
				}
			}

			let slideSpeed = 250;
			
			<?php if ($package) { ?>

			<?php } else { ?>

				$('#rezgo-calendar').responsiveCalendar({
					time: '<?php echo esc_html($calendar_start); ?>', 
					startFromSunday: <?php echo (($start_week == 'mon') ? 'false' : 'true') ?>,
					allRows: false,
					monthChangeAnimation: false,

					onDayClick: function(events) {
						$('.days .day').each(function () {
							$(this).removeClass('select');
						});
						$(this).parent().addClass('select');
						
						let this_date, this_class;
						
						this_date = $(this).data('year')+'-'+ addLeadingZero($(this).data('month')) +'-'+ addLeadingZero($(this).data('day'));

						this_class = events[this_date].class;
						
						if (this_class == 'passed') {
							//$('.rezgo-date-selector').html('<p class="lead">This day has passed.</p>').show();
						} else if (this_class == 'cutoff') {
							//$('.rezgo-date-selector').html('<p class="lead">Inside the cut-off.</p>').show();
						} else if (this_class == 'unavailable') {
							//$('.rezgo-date-selector').html('<p class="lead">No tours available on this day.</p>').show();
						} else if (this_class == 'full') {
							//$('.rezgo-date-selector').html('<p class="lead">This day is fully booked.</p>').show();
							
						} else {

							$('.rezgo-date-options').html('<div class="rezgo-date-loading"></div>');
							
							if ($('.rezgo-date-selector').css('display') == 'none') {
								$('.rezgo-date-selector').slideDown('fast');
							}
						
							// $('.rezgo-date-selector').css('opacity', '0.4');
							
							$.ajax({
								url: '<?php echo admin_url('admin-ajax.php'); ?>',
								data: {
									action: 'rezgo',
									method: 'calendar_day',
									parent_url: '<?php echo esc_html($parent_url); ?>',
									com: '<?php echo esc_html($item->com); ?>',
									date: this_date,
									type: 'calendar',
									js_timestamp: js_timestamp,
									js_timezone: js_timezone,
									date_format: '<?php echo esc_html($date_format); ?>',
									time_format: '<?php echo esc_html($time_format); ?>',
									security: '<?php echo wp_create_nonce('rezgo-nonce'); ?>',
								},
								context: document.body,
								success: function(data) {
									$('.rezgo-date-selector').html(data).css('opacity', '1');
									$('.rezgo-date-options').show();
								}
							});
						}
					},
										
					onActiveDayClick: function(events) { 
					
						$('.days .day').each(function () {
							$(this).removeClass('select');
						});
						
						$(this).parent().addClass('select');
						
					},

					onMonthChange: function(events) {

						// show loading container 
						$('.responsive-calendar-loading-container-<?php echo esc_html(isset($i) ? $i : ''); ?>').show();

						// first hide any options below ...
						// $('.rezgo-date-selector').slideUp(slideSpeed);
						
						rezDate.setMonth(rezDate.getMonth() + 1);
						let rezNewMonth = rezDate.toYMD();

					$.ajax({
						url: '<?php echo admin_url('admin-ajax.php'); ?>',
						data: {
							action: 'rezgo',
							method: 'calendar_month',
							uid: '<?php echo esc_html($item->uid); ?>',
							com: '<?php echo esc_html($item->com); ?>',
							date: rezNewMonth,
							security: '<?php echo wp_create_nonce('rezgo-nonce'); ?>',
						},
						context: document.body,
						success: function(data) {

							// hide loading container 
							$('.responsive-calendar-loading-container-<?php echo esc_html(isset($i) ? $i : ''); ?>').fadeOut();

							$('#rezgo-date-script').html(data); 
							}
						});
					},
					events: {
						<?php echo $calendar_events; ?>				
					}
			}); 

			<?php } ?>

			<?php if (($calendar_dates > 0 || $single_dates > 10) && $cal_day_set === TRUE) { ?>
				// open the first available day			
				$('.rezgo-date-options').html('<div class="rezgo-date-loading"></div>');
					$('.rezgo-date-selector').show();
				
				if($('.rezgo-date-selector').css('display') == 'none') {
					$('.rezgo-date-selector').show();
				}

				$.ajax({
					url: '<?php echo admin_url('admin-ajax.php'); ?>',
					data: {
						action: 'rezgo',
						method: 'calendar_day',
						parent_url: '<?php echo esc_html($parent_url); ?>',
						com: '<?php echo esc_html($item->com); ?>',
						date: '<?php echo esc_html($open_cal_day); ?>',
						type: 'calendar',
						js_timestamp: js_timestamp,
						js_timezone: js_timezone,
						date_format: '<?php echo esc_html($date_format); ?>',
						time_format: '<?php echo esc_html($time_format); ?>',
						security: '<?php echo wp_create_nonce('rezgo-nonce'); ?>',
					},
					context: document.body,
					success: function(data) {
						$('.rezgo-date-selector').html(data).css('opacity', '1');
						$('.rezgo-date-options').show();
						$('.active [data-day="<?php echo esc_html($start_day); ?>"]').parent().addClass('select');
						$('.option-panel-<?php echo esc_html($_REQUEST['option'] ?? ''); ?>').addClass('in');	
					}
				});
				// end open first day
			<?php } ?>

			<?php if ($package) { ?> 

				// handle package addCart() request
				$('#checkout_package').submit(function(e){
					e.preventDefault();
					$('.rezgo-btn-add').attr('disabled', true);

					let empty = 0;
					let time_empty = 0;

					$('.package-uid-input').each(function(){
						if ($(this).val() === ''){
							empty++;
						}
					});
					$('.package-date-input').each(function(){
						if ($(this).val() === ''){
							empty++;
						}
					});
					$('.package-time-input').each(function(){
						if ($(this).val() === '' && !$(this).prop("disabled")){
							time_empty++;
						}
					});

					if (empty){
						err = 'Please select all your dates & options';
						$('#rezgo-package-date-error').html(err).slideDown();

						setTimeout(() => {
							$('#rezgo-package-date-error').slideUp(500);
						}, 2500);

					} else if(time_empty){
						err = 'Please select all your starting times';
						$('#rezgo-package-date-error').html(err).slideDown();

						setTimeout(() => {
							$('#rezgo-package-date-error').slideUp(500);
						}, 2500);
					} else {

						$(this).ajaxSubmit({
							type: 'POST',
							url: '<?php echo admin_url('admin-ajax.php'); ?>',
							data: {
								action: 'rezgo',
								method: 'book_ajax',
								rezgoAction: 'add_item'
							},
							success: function(data){

								// console.log(data);
								let response = JSON.parse(data);

								<?php if ($analytics_ga4) { ?>
									// gtag add_to_cart
									function ga4_add_to_cart(){
										gtag("event", "add_to_cart", {
											currency: "<?php echo esc_html($booking_currency); ?>",
											value: ga4_package_amount,
											items: [
												<?php for ($i=0; $i < count($package_items); $i++) { ?>
													ga4_package_details.item_<?php echo esc_html($i+1); ?>,
												<?php } ?>
											]
										});
									}
								<?php } ?>

								<?php if ($analytics_gtm) { ?>
									// tag manager add_to_cart
									function gtm_add_to_cart() {
										dataLayer.push({ ecommerce: null });  // Clear the previous ecommerce object.
										dataLayer.push({
										"event": "add_to_cart",
										"ecommerce": {
											"currencyCode": "<?php echo esc_html($booking_currency); ?>",
											"add": {                               
											"products": [
												<?php for ($i=0; $i < count($package_items); $i++) { ?>
													gtm_package_details.item_<?php echo esc_html($i+1); ?>,
												<?php } ?>
											]
											}
										}
										});
									}
								<?php } ?>

								<?php if ($meta_pixel) { ?>
									// meta pixel add_to_cart
									function pixel_add_to_cart() {
										fbq('track', 'AddToCart', { 
											currency: "<?php echo esc_html($booking_currency); ?>",
											value: parseFloat(pixel_package_amount).toFixed(2),
											contents: [
													<?php for ($i=0; $i < count($package_items); $i++) { ?>
														pixel_package_details.item_<?php echo esc_html($i+1); ?>,
													<?php } ?>
												]
											}
										);
									}
								<?php } ?>

								<?php if (!REZGO_LITE_CONTAINER){ ?>

									//no errors
									if (response == null) {
										localStorage.clear();

										<?php if ($analytics_ga4) { ?>
											ga4_add_to_cart();
										<?php } ?>

										<?php if ($analytics_gtm) { ?>
											gtm_add_to_cart();
										<?php } ?>

										<?php if ($meta_pixel) { ?>
											pixel_add_to_cart();
										<?php } ?>
										
										<?php 
											$cart_token = isset($_COOKIE['rezgo_cart_token_'.REZGO_CID]) ? $_COOKIE['rezgo_cart_token_'.REZGO_CID] : ''; 
											$order_link = REZGO_WORDPRESS ? $parent_url : $site->base;
										?>
										<?php echo LOCATION_WINDOW; ?>.location.href='<?php echo $order_link; ?>/order/<?php echo $cart_token; ?>';
									} 
									else {
										let err = response.message;
										$('.rezgo-btn-add').attr('disabled', false);
										$('#rezgo-package-date-error').html(err).slideDown();

										setTimeout(() => {
											$('#rezgo-package-date-error').slideUp(500);
										}, 2500);
									}

								<?php } else { ?>

									// check if there is a token in the lite URI
									<?php if ($site->cartInUri()) { ?> 

										<?php if ($analytics_ga4) { ?>
											ga4_add_to_cart();
										<?php } ?>

										<?php if ($analytics_gtm) { ?>
											gtm_add_to_cart();
										<?php } ?>

										<?php if ($meta_pixel) { ?>
											pixel_add_to_cart();
										<?php } ?>

										//no errors
										if (response == null) {
											<?php echo LOCATION_WINDOW; ?>.location.href='<?php echo $site->base ?>/order/';
										}
										else {
											let err = response.message;
											$('.rezgo-btn-add').attr('disabled', false);
											$('#rezgo-package-date-error').html(err).slideDown();

											setTimeout(() => {
												$('#rezgo-package-date-error').slideUp(500);
											}, 2500);
										}

									<?php } else { ?>

										// redirect to lite URI with cart token
										<?php echo LOCATION_WINDOW; ?>.location.href = 'https://' + response + '/order/';

									<?php } // endif cartInUri() ?>
								<?php } // endif REZGO_LITE_CONTAINER ?>
							},
							error: function(error){
								console.log(error);
							}
						})
					}
				});

			<?php } ?>
	
			// handle short url popover
			$('*[data-ajaxload]').bind('click',function() {
				let e = $(this);
				e.unbind('click');
				$.get(e.data('ajaxload'),function(d){
						e.popover({
							html : true,
							title: false,
							placement: 'left',
							content: d,
							}).popover('show');
				});
			});
			
			$('body').on('click', function (e) {
				$('[data-toggle="popover"]').each(function () {
					if (!$(this).is(e.target) && e.target.id != 'rezgo-short-url' && $(this).has(e.target).length === 0) {
						$(this).popover('hide');
					}
				});
			});

			// get reviews from panel click
			$('#reviews-load').click(function(e){ 
			
				e.preventDefault();
				
				$.ajax({
					url: '<?php echo admin_url('admin-ajax.php'); ?>',
				    data: {
						action: 'rezgo',
						method: 'reviews_ajax',
						parent_url: '<?php echo esc_html($site->base); ?>',
						wp_slug: '<?php echo esc_html($_REQUEST['wp_slug']); ?>',
						view:'details',
						com: '<?php echo esc_html($item->com); ?>',
						type:'inventory',
						limit:5,
						total:'<?php echo esc_html($item->rating_count); ?>',
						security: '<?php echo wp_create_nonce('rezgo-nonce'); ?>',
				    },
					context: document.body,
					success: function(data) {
						
						$('#reviews-list').hide(); 
						setTimeout(function () {
								$('#reviews-list').html(data); 
						}, 500);								
						$('#reviews-list').show(); 
						
					}
				});
				
			});

		$('.rezgo-show-reviews').tooltip();
			

		$('.rezgo-section-link').click(function(){
			if( !$(this).hasClass('collapsed')){
				$(this).addClass('toggled');
			} else {
				$(this).removeClass('toggled');
			}
		});

	});

	</script>
<?php } ?>

</div>