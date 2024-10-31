<?php
	$calendar_days = $site->getCalendarDays();
	$booking_edit = (isset($_REQUEST['src']) && $_REQUEST['src'] == 'booking_edit') ? 1 : 0;
	$item_id = $_REQUEST['uid'] ?? '';
	$total_pax_number = $_REQUEST['total_pax_number'] ?? '';
	$booking_date = $_REQUEST['booking_date'] ?? '';

	if ($_REQUEST['chosenDate'] && $_REQUEST['date'] || $_REQUEST['date']) {
		$site->getCalendar(
			sanitize_text_field($_REQUEST['uid']), 
			sanitize_text_field($_REQUEST['date'])
		);
	} else if ($_REQUEST['chosenDate']) {
		$site->getCalendar(
			sanitize_text_field($_REQUEST['uid']), 
			sanitize_text_field($_REQUEST['chosenDate'])
		);
	}

	$packageCalendar = sanitize_text_field($_REQUEST['packageCalendar'] ?? '');
	$bookable = sanitize_text_field($_REQUEST['bookable'] ?? '');
	$within = sanitize_text_field($_REQUEST['within'] ?? '');
	$chosenDate = isset($_REQUEST['chosenDate']) ? sanitize_text_field($_REQUEST['chosenDate']) : sanitize_text_field($_REQUEST['date']);
	$start_year;
	$start_month;

	if($bookable) {

		// Function to get all the dates in given range
		function getDatesFromRange($start, $end, $format = 'Y-m-d') {
			// Declare an empty array
			$array = array();
			
			// Variable that store the date interval
			// of period 1 day
			$interval = new DateInterval('P1D');
		
			$realEnd = new DateTime($end);
			$realEnd->add($interval);
		
			$period = new DatePeriod(new DateTime($start), $interval, $realEnd);
		
			// Use loop to store date into array
			foreach($period as $date) {                 
				$array[] = $date->format($format); 
			}
			// Return the array elements
			return $array;
		}

		if ($within) {
			// echo 'make available dates from: '.$chosenDate. ' to ' . date('Y-m-d', strtotime($chosenDate. '+ '.($within - 1).'days'));
			$available_dates = getDatesFromRange($chosenDate, date('Y-m-d', strtotime($chosenDate. '+ '.($within - 1).'days')));
		} else {
			// echo 'make available dates from: '.$chosenDate.' onwards';
			$available_dates = getDatesFromRange($chosenDate, date('Y-m-d', strtotime($chosenDate. '+6 months')));
		}

		// $chosenDate = date('Y-m-d', strtotime("+1 day", strtotime($chosenDate)));

	}

	if ($packageCalendar !== '') {
		$package_options = $site->getTours('t=com&q='.sanitize_text_field($_REQUEST['package']));
		$package_items = [];
		$included_uids = [];

		// gather available options from package items
		foreach ($package_options[0]->packages->package as $package) {
			$package_items[] = $package;
		}
		foreach ($package_items[$packageCalendar] as $choice) {
			$included_uids[] = (int)$choice->id;
		}
	}

	foreach ( $site->getCalendarDays() as $day ) {

		if ($booking_edit) {

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
			if ($day_av[$day->day] < $total_pax_number) {
				// check if availability is hidden 
				$class = $day_av[$day->day] != 'h' ? 'unavailable' : '';
			}

		} else {

			if ($day->cond == 'a') { $class = ''; } // available
			elseif ($day->cond == 'p') { $class = 'passed'; }
			elseif ($day->cond == 'f') { $class = 'full'; }
			elseif ($day->cond == 'i' || $day->cond == 'u') { $class = 'unavailable'; }
			elseif ($day->cond == 'c') { $class = 'cutoff'; }

		}

		$formatted_date = esc_html(date('Y-m-d', $day->date));

		if ($packageCalendar !== '') {

			foreach ($day->items as $item) {
				// find first available date to send to FE
				if ($day->cond == 'a' && in_array((int)$item->uid, (array)$included_uids)) {

					if ((string)$item->availability === 'h' || (int)$item->availability > 0) {
						$start_days[] = date('d', $day->date);
						$start_month = (int)date('m', $day->date); 
						$start_year = date('Y', $day->date);
						$package_avail_dates[] = $formatted_date;
					}
					else {
						$package_unavail_dates[] = $formatted_date;
					}
				}
			}
		}

		if ($bookable){

			if ($day->date && in_array($formatted_date, (array)$available_dates) && in_array($formatted_date, $package_avail_dates) ) {
				$calendar_events .= '"'.$formatted_date.'":{"class": "'.esc_html($class).'"},'; // ."\n"

				// only bookable within 1 day 
				if ((int)$within === 1){
					$within_one_date = $formatted_date;
					$within_one_day = date('d', $day->date);
					$within_one_month = $start_month;
					$within_one_year = $start_year;
				}
				
			} elseif(in_array($formatted_date, (array)$package_unavail_dates) && array_intersect($package_unavail_dates, $available_dates)) {
				$calendar_events .= '"'.$formatted_date.'":{"class": "full"},'; // ."\n"
			}
			else {
				// $calendar_events .= '"'.$formatted_date.'":{"class": "blocked"},'; // ."\n"
			}
			
		} else {

			if ($packageCalendar !== '') {
				if (in_array($formatted_date, (array)$package_avail_dates)) { // && (int)$day->lead != 1
					$calendar_events .= '"'.$formatted_date.'":{"class": "'.esc_html($class).'"},'; // ."\n"
				} elseif(in_array($formatted_date, (array)$package_unavail_dates)) {
					$calendar_events .= '"'.$formatted_date.'":{"class": "full"},'; // ."\n"
				} else {
					$calendar_events .= '"'.$formatted_date.'":{"class": "unavailable"},'; // ."\n"
				}
				if ($day->cond == 'a'){
					$start_dates[] = date('Y-m-d', $day->date);
				}
			} else {
				if ($day->date) $calendar_events .= '"'.$formatted_date.'":{"class": "'.esc_html($class).'"},'; // ."\n"
			}
		}
	}

	// grab the first available day
	$start_date = $start_dates[0];
	$start_day = $start_days[0];

	$calendar_events = trim($calendar_events, ','); // ."\n"

	if ($_REQUEST['cross_sell']) {
		
		echo '{' . $calendar_events . '}';
		
	} else {

		if ($packageCalendar !== '') {

			if (!empty($start_year) && !empty($start_month)){
				echo "
				<script>
					jQuery('.responsive-calendar-".esc_html($packageCalendar)."').responsiveCalendar('".esc_html($start_year)."-".esc_html($start_month)."');
				</script> 
				";
			}

			echo "
			<script>
				jQuery('.responsive-calendar-".esc_html($packageCalendar)."').responsiveCalendar('edit', {
					".$calendar_events."
				});
			</script>
			<span id='start_date_".esc_attr($packageCalendar)."'>".sanitize_text_field($start_date)."</span>
			<span id='start_day_".esc_attr($packageCalendar)."'>".sanitize_text_field($start_day)."</span>
			<span id='start_month_".esc_attr($packageCalendar)."'>".sanitize_text_field($start_month)."</span>
			<span id='start_year_".esc_attr($packageCalendar)."'>".sanitize_text_field($start_year)."</span>
			";

			if ((int)$within === 1){
				echo "
				<span id='within_one_date_".esc_attr($packageCalendar)."'>".sanitize_text_field($within_one_date)."</span>
				<span id='within_one_day_".esc_attr($packageCalendar)."'>".sanitize_text_field($within_one_day)."</span>
				<span id='within_one_month_".esc_attr($packageCalendar)."'>".sanitize_text_field($within_one_month)."</span>
				<span id='within_one_year_".esc_attr($packageCalendar)."'>".sanitize_text_field($within_one_year)."</span>
				";
			}

		} else {

			echo "
			<script>
				jQuery('.responsive-calendar').responsiveCalendar('edit', {
					".$calendar_events."
				});
			</script>	
			";
		}
		
	}
	
?>