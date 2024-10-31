<?php 
// gets availability from package item id

require('rezgo/include/page_header.php');

// start a new instance of RezgoSite
$site = new RezgoSite();
$company = $site->getCompanyDetails();
$time_format = sanitize_text_field((string)$company->time_format . ' hours');

$rezgoAction = isset($_REQUEST['rezgoAction']) ? sanitize_text_field($_REQUEST['rezgoAction']) : '';
$type = isset($_REQUEST['type']) ? sanitize_text_field($_REQUEST['type']) : '';
$r_date = is_array($_REQUEST['date']) ? array_map('sanitize_text_field', $_REQUEST['date']) : sanitize_text_field($_REQUEST['date']);
$option = sanitize_text_field($_REQUEST['option']);
$com = sanitize_text_field($_REQUEST['com']);
$js_timestamp = sanitize_text_field($_REQUEST['js_timestamp']);
$js_timezone = sanitize_text_field($_REQUEST['js_timezone']);

$chosenDate = is_array($_REQUEST['chosenDate']) ? array_map('sanitize_text_field', $_REQUEST['chosenDate']) : sanitize_text_field($_REQUEST['chosenDate']);
$within = isset($_REQUEST['within']) ? sanitize_text_field($_REQUEST['within']) : '';
$bookable = isset($_REQUEST['bookable']) ? sanitize_text_field($_REQUEST['bookable']) : '';
$spacing = isset($_REQUEST['spacing']) ? (float) sanitize_text_field($_REQUEST['spacing']) * 60 * 60 : ''; // convert to seconds, to account for decimalpoints (.5 hr increments)
$end_time = isset($_REQUEST['end_time']) ? (int) sanitize_text_field($_REQUEST['end_time']) : '';
$selected_time = isset($_REQUEST['selected_time']) ? sanitize_text_field($_REQUEST['selected_time']) : '';
$selected_duration = isset($_REQUEST['selected_duration']) ? sanitize_text_field($_REQUEST['selected_duration']) : '';
$pax_array = isset($_REQUEST['pax']) ? array_map('sanitize_text_field', $_REQUEST['pax']) : '';
$discount = isset($_REQUEST['discount']) ? (sanitize_text_field((float)$_REQUEST['discount'])/100) : '';
$package_index = isset($_REQUEST['package_index']) ? (int) sanitize_text_field($_REQUEST['package_index']) : '';
$cooldown = '';

if (REZGO_WORDPRESS) $site->setTimeZone();

	// return total amount due in correct currency format
	if ($rezgoAction == 'formatCurrency'){
		$company = $site->getCompanyDetails();
		echo $site->formatCurrency(sanitize_text_field($_REQUEST['amount']), $company);
	}

if ($rezgoAction == 'price') {

	$package_options = $site->getTours('t=com&q='.sanitize_text_field($_REQUEST['package']));
	$included_uids = [];

	// gather available options from package items
	foreach ($package_options[0]->packages->package[$package_index]->choice as $package_option) {
		$included_uids[] = (int)$package_option->id;
	}

	$availability_title = '';	

	if ($_REQUEST['option_num']) {
		$option_num = sanitize_text_field($_REQUEST['option_num']);
	} else {
		$option_num = 1;	
		
		if ($type != 'open') {
			$now = time();
			$offset_time_now = strtotime($time_format, $now);
			
      		// $today = date('Y-m-d', $now);
			// $selected_date = date('Y-m-d', strtotime($r_date . ' ' . sanitize_text_field($_REQUEST['time_format']) . ' hours'));
			// $selected_date = date('Y-m-d', strtotime($r_date));
			// $available_day = date('D', strtotime($r_date));
      		// $available_date = date((string) sanitize_text_field($_REQUEST['date_format']), strtotime($r_date)); 
      
		}
	}

	if ($type === 'single'){

		function has_duplicates($array) {
			return count($array) !== count(array_unique($array));
		}
		function get_duplicates($array) {
			return array_unique( array_diff_assoc( $array, array_unique( $array ) ) );
		}

		$options = array();
		$single_options = array();
		$same_dates = get_duplicates($r_date);

		// handle multiple items on the same date
		if (has_duplicates($r_date)){
			foreach ($same_dates as $date) {
				$single_options[] = $site->getTours('t=com&q='.$com.'&d='.$date);
			}
			foreach ($single_options as $tour) {
				if (is_array($tour)){
					foreach ($tour as $v) {
						$options[] = $v;
					}
				} else {
					$options[] = $tour;
				}
			} unset($tour); unset($single_options);
		}

		// add in other remaining dates if applicable
		$remaining_dates = array_diff($r_date, $same_dates);
		if ($remaining_dates) {
			foreach ($remaining_dates as $date) {
				$single_options[] = $site->getTours('t=com&q='.$_REQUEST['com'].'&d='.$date);
			}
			foreach ($single_options as $tour) {
				if (is_array($tour)){
					foreach ($tour as $v) {
						$options[] = $v;
					}
				} else {
					$options[] = $tour;
				}
			} 
		}

	} else {
		$options = $site->getTours('t=com&q='.$com.'&d='.$r_date);
	}
	
	if ($options) {

		$avail_options = (object) [];

		// assign a new end time based on different start times
		if ($selected_time) {
			// use selected date to compare times
			$selected_time = strtotime((string)$chosenDate.$selected_time);
			$end_time = strtotime('+'.$selected_duration, $selected_time);
		}

		$cooldown = strtotime('+'.($spacing - 1).' seconds', $end_time);

		// get current package restriction to show/hide selectable options 
		$current_restrictions = $package_options[0]->packages->package[$package_index]->bookable || $package_options[0]->packages->package[$package_index]->spacing ? 1 : 0;

		$c = 0;
		foreach($options as $option) {
			$site->readItem($option);
			// only include items added in the package
			if (in_array((int)$option->uid, $included_uids)){ 

				$item = $site->getItem();
				$time_format = (string)$option->time_format;

				if ($time_format == 'static') {
					$start_time = (int)$item->date->start_time;

					// ignore cooldown on next open/single date availability options
					if ((string)$option->date->value == 'open' || (string)$option->date_selection == 'single') {
						$cooldown = 0;
					}

					if ($current_restrictions) {
						$selectable_time = ($start_time > $cooldown) ? 1 : 0;
					} else {
						$selectable_time = 1;
					}

				} else {
					foreach ($option->date->time_data->time as $time) {
						$chosenDateTime = $chosenDate.$time->id;
						if (is_array($chosenDate)) {
							foreach ($chosenDate as $v) {
								$chosenDateTime = $v.$time->id;
								// check if at least one time option is available
								if (strtotime($chosenDateTime) > $cooldown){
									$dynamic_start_times[] = strtotime($chosenDateTime);
								}
							}
						} else {
							// check if at least one time option is available
							if (strtotime($chosenDateTime) > $cooldown){
								$dynamic_start_times[] = strtotime($chosenDateTime);
							}
						}
					}
					$selectable_time = $dynamic_start_times ? 1 : 0;
				}

				if ((int)$item->total_availability !== 0 && $selectable_time) {

					// explicitly instantiate objects
					$avail_options->options[$c] = (object)[];
					$prices = (object)[];

					$avail_options->options[$c]->uid = (int)$option->uid;
					$avail_options->options[$c]->com = (int)$option->com;
					$avail_options->options[$c]->item = (string)$option->item;
					$avail_options->options[$c]->name = (string)$option->option;
					$avail_options->options[$c]->index = (string)$c;

					if ($time_format == 'dynamic'){
						$avail_options->options[$c]->time_format = $time_format;

						foreach ($option->date->time_data->time as $time) {
							$cutoff_operator = '+';
							if (strpos($option->cutoff, '-') !== false) {
								$option_cutoff = str_replace('-', '', $option->cutoff);
								$cutoff_operator = '-';
							}
							$option_cutoff = round((float)$option->cutoff * 60);
							$cutoff_time = strtotime($cutoff_operator .$option_cutoff. ' minutes', strtotime($r_date.$time->id) );
							$passed = $offset_time_now > $cutoff_time ? 1 : 0;

							if ((int)$time->av > 0 && strtotime((string)$option->date->value.$time->id) > $cooldown && !$passed){
								$avail_options->options[$c]->book_time[] = (string)$time->id.':::'.(string)$time->av;
							}
						}
						$avail_options->options[$c]->hide_av = $option->date->hide_availability ? 1 : 0;
					}

					$prices->price_adult = ($item->date->price_adult) ? (string) $item->date->price_adult : 0;
					$prices->price_child = ($item->date->price_child) ? (string) $item->date->price_child : 0;
					$prices->price_senior = ($item->date->price_senior) ? (string) $item->date->price_senior : 0;
					$prices->price4 = ($item->date->price4) ? (string) $item->date->price4 : 0;
					$prices->price5 = ($item->date->price5) ? (string) $item->date->price5 : 0;
					$prices->price6 = ($item->date->price6) ? (string) $item->date->price6 : 0;
					$prices->price7 = ($item->date->price7) ? (string) $item->date->price7 : 0;
					$prices->price8 = ($item->date->price8) ? (string) $item->date->price8 : 0;
					$prices->price9 = ($item->date->price9) ? (string) $item->date->price9 : 0;

					// add in pax_obj info
					foreach ($pax_array as $pax => $num) {
						$avail_options->options[$c]->pax[] = intval($num);
					}

					foreach( $prices as $price ) {
						$discount_off = (float) ($price * $discount);
						$avail_options->options[$c]->price[] = ($price) ? ($price - $discount_off) : 0;
					}

					// total everything up to send to FE
					foreach ($avail_options as $options){

						for ($i = 0; $i < count($avail_options->options[$c]->pax) ; $i++) { 
							$avail_options->options[$c]->total[] = $options[$c]->price[$i] * (int)$options[$c]->pax[$i];
						}
						$avail_options->options[$c]->option_total = array_sum($avail_options->options[$c]->total);
					}

					$avail_options->options[$c]->option_total_formatted = $site->formatCurrency($avail_options->options[$c]->option_total); 

					// add all other details
					$avail_options->options[$c]->chosen_date = $chosenDate ? $chosenDate : '';
					$avail_options->options[$c]->bookable = $bookable ? $bookable : '';
					$avail_options->options[$c]->within = $within ? $within : '';

					// add date if available on a single date
					$avail_options->options[$c]->single_date = (string)$option->date_selection === 'single' ? date('Y-m-d', (int)$option->start_date) : '';

					// add 'open availability' if open avail
					$avail_options->options[$c]->open_date = (string)$option->date->value === 'open' ? 'Open Availability' : '';

					// add start and end times with duration
					$avail_options->options[$c]->start_time = $option->date->start_time ? (int)$option->date->start_time : '';
					$avail_options->options[$c]->end_time = $option->date->end_time ? (int)$option->date->end_time : '';
					$avail_options->options[$c]->duration = $option->duration ? (string)$option->duration : '';

					$c++;
				}
			}

    	} // end foreach($options as $option) 
	}

	echo $avail_options != new stdClass() ? json_encode($avail_options) : 0;
}

?>