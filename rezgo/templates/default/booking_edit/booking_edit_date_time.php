<?php 
	require ('header.php'); 
	$single_date_static_time = $item->date_selection == 'single' && $item->time_format != 'dynamic' ? 1 : 0;
	if (!$booking_edit || in_array(2, $booking_edit_options) || $single_date_static_time) $site->sendTo('/edit/'.$raw_trans_num); 
?>

<?php 
	// get the available dates
	$site->getCalendar($booking_uid, $booking_date); 
	$disallow_option_change = in_array(3, $booking_edit_options) ? 1 : 0;

	$cal_day_set = FALSE;
	$calendar_events = '';

	// gather list of pax
	$pax_array = array('adult_num', 'child_num', 'senior_num', 'price4_num', 'price5_num', 'price6_num', 'price7_num', 'price8_num', 'price9_num');
	$price_name_array = array('adult', 'child', 'senior', 'price4', 'price5', 'price6', 'price7', 'price8', 'price9');
	$price_point_array = array('price_adult', 'price_child', 'price_senior', 'price4', 'price5', 'price6', 'price7', 'price8', 'price9');
	$current_booking_pax = (array)[];
	$total_pax_number = (int)$booking->pax;
	$item_id = (int)$booking->item_id;
	$parent_url =  $site->base;
	
	function addLeadingZero($date) {
		if ($date < 10) {
			return '0' . $date;
		} else {
			return '' . $date;
		}
	}
	foreach ($pax_array as $k => $pax) {
		$current_booking_pax[$price_name_array[$k]]['amount'] = (int)$booking->{$pax};
		$current_booking_pax[$price_name_array[$k]]['pax_total'] = (float)$booking->prices->{$price_point_array[$k]};
	}

	foreach ($site->getCalendarDays() as $day) {

		foreach ($day->items as $v) {
			$day_items[$day->day][] = (int)$v->uid;
			if ((int)$v->uid == $item_id) {
				$day_av[$day->day] = $v->availability != 'h' ? (int)$v->availability : 'h';
			}
		}
		if ($day->cond == 'a' && in_array($item_id, $day_items[$day->day]) ) { $class = ''; } // available
			elseif ($day->cond == 'p') { $class = 'passed'; }
			elseif ($day->cond == 'f') { $class = 'full'; }
			elseif ($day->cond == 'i' || $day->cond == 'u') { $class = 'unavailable'; }
			elseif ($day->cond == 'c') { $class = 'cutoff'; }

			// remove days that do not have availablity, disregard currently booked date, disregard hidden availability
			if (addLeadingZero((int)$day->day) != date('d', strtotime($booking_date)) && 
				$day_av[$day->day] < $total_pax_number) {
				// check if availability is hidden 
				$class = $day_av[$day->day] != 'h' ? 'unavailable' : '';
			}
			
			if ($day->date) { // && (int)$day->lead != 1
				$calendar_events .= '"'.esc_html(date('Y-m-d', $day->date)).'":{"class": "'.esc_html($class).'"},'."\n"; 
			}
		
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

	$calendar_events = trim($calendar_events, ','."\n");
?>

<script>

	function fetchAvail(availability_type, date) {

		jQuery('.rezgo-date-loader').show();

		jQuery.ajax({
			url: '<?php echo admin_url('admin-ajax.php'); ?>',
			data: {
				action: 'rezgo',
				method: 'calendar_day_edit',
				parent_url: '<?php echo esc_html($parent_url); ?>',
				com: '<?php echo esc_html($booking->com); ?>',
				uid: '<?php echo esc_html($booking->item_id); ?>',
				trans_num: '<?php echo $trans_num; ?>',
				trigger_code: '<?php echo $booking->trigger_code; ?>',
				date: date,
				type: availability_type,
				js_timestamp: js_timestamp,
				js_timezone: js_timezone,
				date_format: '<?php echo esc_html($date_format); ?>',
				time_format: '<?php echo esc_html($time_format); ?>',
				src: '<?php echo $_SERVER['SCRIPT_NAME']; ?>',
				booking_date: '<?php echo $booking_date; ?>',
				booking_time: '<?php echo $booking_time; ?>',
				disallow_option_change: '<?php echo $disallow_option_change; ?>',
				current_booking_pax: '<?php echo json_encode($current_booking_pax); ?>',
				total_pax_number: '<?php echo $total_pax_number; ?>',
				security: '<?php echo wp_create_nonce('rezgo-nonce'); ?>',
			},
			context: document.body,
			success: function(data) {

				setTimeout(() => {
					jQuery('.rezgo-date-loader').fadeOut();
				}, 300);

				jQuery('.rezgo-date-selector').html(data).css('opacity', '1');
				jQuery('.rezgo-date-options').show();
			}
		});
	}

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

<div id="edit_date" class="panel-collapse">
	
	<div class="panel-body edit-booking-panel">
		<div class="edit-booking-change-date edit-change-container">

			<?php if ($availability_type != 'open') { ?> 
				<div class="row">
					<div id="rezgo-item-right-<?php echo $item->com; ?>" class="rezgo-calendar col-md-6">
						<div class="responsive-calendar rezgo-calendar-<?php echo esc_attr($booking->com); ?>" id="rezgo-calendar">
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
						<div class="rezgo-calendar-legend rezgo-legend-<?php echo esc_attr($booking->com); ?>">
							<span class="current">&nbsp;</span><span class="text-current"><span>&nbsp;Current&nbsp;&nbsp;</span></span>
							<span class="available">&nbsp;</span><span class="text-available"><span>&nbsp;Available&nbsp;&nbsp;</span></span>
							<span class="full">&nbsp;</span><span class="text-full"><span>&nbsp;Full&nbsp;&nbsp;</span></span>
							<span class="unavailable">&nbsp;</span><span class="text-unavailable"><span>&nbsp;Unavailable</span></span>

							<div id="rezgo-calendar-memo"></div>
						</div>
					</div>

					<div class="rezgo-date-selector col-md-5">
						<!-- available options will populate here -->
						<div class="rezgo-date-options"></div>
					</div>
					<div id="rezgo-date-script" style="display:none;">
						<!-- ajax script will be inserted here -->
					</div>

				</div>
			<?php } ?>
		</div>		
		
		<div class="edit-form-controls">
			<a href="<?php echo $site->base; ?>/edit/<?php echo $raw_trans_num; ?>" class="underline-link reset_edit_view"><i class="far fa-angle-left"></i><span>Back</span></a>
		</div>
	</div>

	<script>
		let booking_date = '<?php echo $booking_date; ?>';
		let date_split = booking_date.split('-');

		jQuery(function($){
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
					
					function addLeadingZero(num) {
						if (num < 10) {
							return "0" + num;
						} else {
							return "" + num;
						}
					}

					$('#rezgo-calendar').responsiveCalendar({
						time: '<?php echo esc_html($booking_date); ?>', 
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
								
								if ($('.rezgo-date-selector').css('display') == 'none') {
									$('.rezgo-date-selector').slideDown('fast');
								}

								fetchAvail('calendar', this_date);
							}
						},
											
						onActiveDayClick: function(events) { 
						
							$('.days .day').each(function () {
								$(this).removeClass('select');
							});
							
							$(this).parent().addClass('select');
							
						},

						onMonthChange: function(events) {

							// first hide any options below ...
							// $('.rezgo-date-selector').slideUp(slideSpeed);

							let newMonth = $(this)[0].currentYear + '-' + addLeadingZero($(this)[0].currentMonth + 1);

							$.ajax({
								url: '<?php echo admin_url('admin-ajax.php'); ?>',
								data: {
									action: 'rezgo',
									method: 'calendar_month',
									uid: '<?php echo esc_html($booking_uid); ?>',
									com: '<?php echo esc_html($booking->com); ?>',
									date: newMonth,
									booking_date: '<?php echo $booking_date; ?>',
									total_pax_number: '<?php echo $total_pax_number; ?>',
									src: 'booking_edit',
									
									security: '<?php echo wp_create_nonce('rezgo-nonce'); ?>',
								},
								context: document.body,
								success: function(data) {
									$('#rezgo-date-script').html(data); 

									$('.responsive-calendar a[data-day="'+parseInt(date_split[2])+'"][data-month="'+parseInt(date_split[1])+'"][data-year="'+date_split[0]+'"]').parent().addClass('select');

								}
							});
						},
						events: {
							<?php echo $calendar_events; ?>				
						}
					}); 

					<?php if ($availability_type != 'open') { ?> 
						// add selected class to current booking date
						setTimeout(() => {
							$('.responsive-calendar a[data-day="'+parseInt(date_split[2])+'"][data-month="'+parseInt(date_split[1])+'"][data-year="'+date_split[0]+'"]').parent().addClass('select');

							fetchAvail('calendar', booking_date);
						}, 250);
					<?php } ?>

				}); 
			</script>
		</div>
		
	</div>
</div>
