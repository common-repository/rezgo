<?php
	$raw_trans_num = sanitize_text_field($_REQUEST['trans_num'] ?? '');
	$trans_num = $site->decode($_REQUEST['trans_num']);
	$type = $_REQUEST['type'] ?? '';

	$order_bookings = $site->getBookings('q='.$trans_num.'&a=forms');

	if(!$order_bookings) $site->sendTo("/booking-not-found:".$trans_num);

	$site->setTimeZone();
	$new_date = new Datetime();
	$company = $site->getCompanyDetails();
	$first_date = (string)$company->start_week == 'mon' ? 1 : 0;

	$currency_symbol = (string)$company->currency_symbol;

	$pax_array = array('adult', 'child', 'senior', 'price4', 'price5', 'price6', 'price7', 'price8', 'price9');

	$date_format = (string)$company->date_format;
	$start_week = $company->start_week;
	$today = $new_date->format($date_format);
	$tz_offset = $company->time_format;

	// $c is not assigned in WORDPRESS
	$c = REZGO_CID;
	
	// $booking_status = $site->getEditStatus($trans_num);
	// $pending_cancellation = $booking_status->pending_change_type == 4 ? 1 : 0;

	function convertPHPToMomentFormat($format) {
		$replacements = [
			'd' => 'DD',
			'D' => 'ddd',
			'j' => 'D',
			'l' => 'dddd',
			'N' => 'E',
			'S' => 'o',
			'w' => 'e',
			'z' => 'DDD',
			'W' => 'W',
			'F' => 'MMMM',
			'm' => 'MM',
			'M' => 'MMM',
			'n' => 'M',
			't' => '', // no equivalent
			'L' => '', // no equivalent
			'o' => 'YYYY',
			'Y' => 'YYYY',
			'y' => 'YY',
			'a' => 'a',
			'A' => 'A',
			'B' => '', // no equivalent
			'g' => 'h',
			'G' => 'H',
			'h' => 'hh',
			'H' => 'HH',
			'i' => 'mm',
			's' => 'ss',
			'u' => 'SSS',
			'e' => 'zz', // deprecated since version 1.6.0 of moment.js
			'I' => '', // no equivalent
			'O' => '', // no equivalent
			'P' => '', // no equivalent
			'T' => '', // no equivalent
			'Z' => '', // no equivalent
			'c' => '', // no equivalent
			'r' => '', // no equivalent
			'U' => 'X',
		];
		$momentFormat = strtr($format, $replacements);
		return $momentFormat;
	}
	$moment_date_format = convertPHPToMomentFormat($date_format);

?>	

<?php if (!REZGO_WORDPRESS) { ?>
<!-- calendar.css -->
<link href="<?php echo $this->path; ?>/css/responsive-calendar.rezgo.css?v=<?php echo REZGO_VERSION; ?>" rel="stylesheet">
<link href="//cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css" rel="stylesheet">
<script type="text/javascript" src="//cdn.jsdelivr.net/npm/toastify-js"></script>
<script type="text/javascript" src="<?php echo $this->path; ?>/js/responsive-calendar.min.js"></script>

<script src="<?php echo $site->path; ?>/js/jquery.form.js"></script>
<script src="<?php echo $site->path; ?>/js/jquery.validate.min.js"></script>
<script src="<?php echo $site->path; ?>/js/chosen.jquery.min.js"></script>
<script src="<?php echo $site->path; ?>/js/date-time/bootstrap-datepicker.min.js"></script>
<script src="<?php echo $site->path; ?>/js/date-time/bootstrap-timepicker.min.js"></script>
<script src="<?php echo $site->path; ?>/js/date-time/moment.min.js"></script>
<script src="<?php echo $site->path; ?>/js/date-time/daterangepicker.min.js"></script>
<link rel="stylesheet" href="<?php echo $site->path; ?>/css/pretty-checkbox.min.css">
<link rel="stylesheet" href="<?php echo $site->path; ?>/css/chosen.min.css">
<link rel="stylesheet" href="<?php echo $site->path; ?>/css/datepicker.css">
<link rel="stylesheet" href="<?php echo $site->path; ?>/css/daterangepicker.css">
<?php } ?>

<script>

	let tomorrow = new Date(new Date().setDate(new Date().getDate() + 1));
	tomorrow = tomorrow.toISOString().split('T')[0];

	// current JS timestamp
	let js_timestamp = Math.round(new Date().getTime()/1000);
	let js_timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;

	function trimArray(array) {
		return array.map(element => element.trim());
	}
	
	// MONEY FORMATTING
	const form_symbol = '$';
	const form_decimals = '2';
	const form_separator = ',';
	const currency = decodeURIComponent( '<?php echo rawurlencode( (string) $site->xml->currency_symbol ); ?>' );

	Number.prototype.formatMoney = function(decPlaces, thouSeparator, decSeparator) {
		var n = this,
		decPlaces = isNaN(decPlaces = Math.abs(decPlaces)) ? form_decimals : decPlaces,
		decSeparator = decSeparator == undefined ? "." : decSeparator,
		thouSeparator = thouSeparator == undefined ? form_separator : thouSeparator,
		sign = n < 0 ? "-" : "",
		i = parseInt(n = Math.abs(+n || 0).toFixed(decPlaces)) + "",
		j = (j = i.length) > 3 ? j % 3 : 0;

		var dec;
		var out = sign + (j ? i.substr(0, j) + thouSeparator : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + thouSeparator);
		if(decPlaces) dec = Math.abs(n - i).toFixed(decPlaces).slice(2);
		if(dec) out += decSeparator + dec;
		return out;
	};

	let booking_status, notification_offset;
	let success_icon = '<i class="far fa-check-circle" id="success_icon"></i>';
	let error_icon = '<i class="far fa-exclamation-circle" id="error_icon"></i>';

	let width = this.innerWidth;
	if (width > 992){
		notification_offset = { x: 30, y: 60 };
	} else {
		notification_offset = { x: 30, y: 10 };
	}

	// Toastify global notification settings
	function show_notification(message, dismissDuration, border) {

		// border = 'error-border';
		
		setTimeout(() => {
			parent.scrollTo({
				top: 0,
				left: 0,
				behavior: "smooth"
			});
		}, 200);

		setTimeout(() => {
			const toastMessage = Toastify({
				text: message,
				duration: dismissDuration ? dismissDuration : -1,
				close: true,
				gravity: "top", // `top` or `bottom`
				position: "right", // `left`, `center` or `right`
				offset: notification_offset,
				stopOnFocus: true, // Prevents dismissing of toast on hover
				className: 'rezgo-toast-notif ' + border,
				escapeMarkup: false,
				style: {
					background: '#f6f6f6',
				},
				onClick: function() {
					if (toastMessage) {
						toastMessage.hideToast();
					}
				},
			}).showToast();
		}, 350);
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

	function resetBookingStatus() {
		jQuery.ajax({
			url: '<?php echo admin_url('admin-ajax.php'); ?>' + '?action=rezgo&method=booking_edit_ajax', 
			data: {
				rezgoAction: 'reset_status',
			}
		})
	}

	function getBookingStatus() {
		jQuery.ajax({
			url: '<?php echo admin_url('admin-ajax.php'); ?>' + '?action=rezgo&method=booking_edit_ajax', 
			data: {
				rezgoAction: 'get_edit_status',
				trans_num: '<?php echo $trans_num; ?>'
			}
		})
	}

	if ( getCookie('booking_edit_status') ) {
		let response = JSON.parse(getCookie('booking_edit_status'));
		console.log(response);

		let pending_change_type = response.pending_change_type ? parseInt(response.pending_change_type) : 0;
		let status = parseInt(response.status);

		let icon = (response.status == 1) ? success_icon : error_icon;
		let border = (response.status == 1) ? 'success-border' : 'error-border';

		// highlight panels that have pending changes
		/** 
		 	1. Change Date and Time
			2. Edit Booking Details
			3. Manage Guests
		**/ 
		setTimeout(() => {
			let pending_class = 'pending-review';
			if (pending_change_type == 1) {
				$('#edit_date_time_panel').addClass(pending_class);
				$('#edit_date_time_panel .pending-icon').show();
			} else if (pending_change_type == 2) {
				$('#edit_primary_forms_panel').addClass(pending_class);
				$('#edit_primary_forms_panel .pending-icon').show();
			} else if (pending_change_type == 3) {
				$('#edit_group_forms_panel').addClass(pending_class);
				$('#edit_group_forms_panel .pending-icon').show();
			}
		}, 250);

		show_notification(
			`${icon}
			<div class="rezgo-toast-notif-wrapper">
				<span class="rezgo-toast-message">${response.message}</span><br>
				<span class="rezgo-toast-details">${response.details}</span>
			</div>`, 
			null, 
			border
		);
		resetBookingStatus();
	}
</script>
		
<div class="container-fluid rezgo-container rezgo-booking-edit-container">

	<div class="jumbotron rezgo-booking">

		<?php foreach ($order_bookings as $booking) { ?>
			<?php 
				$booking_uid = (int)$booking->item_id;
				$availability_type = (string)$booking->availability_type;
				$booking_date = $availability_type == 'open' ? 'open' : date('Y-m-d', (string)$booking->date);

				$item = $site->getTours('t=uid&q='.$booking->item_id.'&d='.$booking_date , 0); 
				$available = $item[0] ?? 0;
				$site->readItem($booking); 

				$booking_time = (string)$booking->time;
				$booking_expiry = (int)$booking->expiry;
				$booking_cancel = (float)$item->cancel;
				$booking_cutoff = (float)$item->cutoff;
				$booking_start = strtotime((string)$booking_date.$booking->time);
				$cancel_time = strtotime('-'.$booking_cancel.' hours', $booking_start);
				$cutoff_time = strtotime('-'.$booking_cutoff.' hours', $booking_start);
				$checkin_state = (int)$booking->checkin_state != 0 ? 1 : 0;

				$now = strtotime($tz_offset.' hours', time());

				// add a check to see if booking is part of a package as well ?
				// account for booking expiry if set
				if ($booking_expiry != 0) {	
					$booking_expired = $now > $booking_expiry ? 1 : 0;
				} else {
					$booking_expired = 0;
				}
				
				// account for cancellation window
				if ($booking->availability_type != 'open') {
					$passed = $now > $cancel_time ? 1 : 0;
				} else {
					$passed = 0;
				}

				if ($booking->reseller) {
					$reseller_locked = $booking->reseller == 2 ?? 0;
				}

				/* Booking Edit Type
					0  =  disabled 
					1  =  fully enabled
					2  =  enabled (no changing dates or times)
					3  =  enabled (no changing options)
					4  =  enabled (no changing guest numbers)
				*/

				$booking_edit_enabled = (int) $company->booking_edit != 0 ? 1 : 0;
				$booking_cancellation_enabled = (int) $company->booking_edit_cancellation != 0 ? 1 : 0;
				$booking_edit_options = (array) $company->booking_edit_options->options;
				
				$booking_edit = ( ($booking_edit_enabled || $booking_cancellation_enabled) && 
								  $available &&
								  !$checkin_state &&
								  $booking->status != 3 &&
								  !$booking_expired &&
								  !$passed &&
								  !$reseller_locked &&
								  !$booking->package) ? 1 : 0;

				if ($type == 'date') {
					$breadcrumb_title = 'Date and Time';
				} elseif ($type == 'primary') {
					$breadcrumb_title = 'Booking Details';
				} elseif ($type == 'group') {
					$breadcrumb_title = 'Manage Guests';
				} else {
					$breadcrumb_title = 'Modify Booking';
				}
			?>

				<div class="row rezgo-confirmation-head booking-edit-head">
					<?php $back_link = $site->base.($type ? '/edit/' : '/complete/').$site->encode($booking->trans_num); ?>
					<div id="rezgo-booking-edit-crumb" class="row">
						<ol class="breadcrumb">
							<li id="rezgo-edit-your-booking" class="rezgo-breadcrumb-order">
								<a id="booking-edit-crumb-home" class="rezgo-breadcrumb-info" href="<?php echo esc_url($back_link); ?>">
									<i class="far fa-angle-left"></i><span class="default"><span><?php echo $type ? 'Modify Booking' : 'Booking Details'; ?></span>
									<span class="custom"></span>
								</a>
							</li>
							<li id="booking-edit-crumb-date" class="rezgo-breadcrumb-info active">
								<span class="default"><?php echo $breadcrumb_title; ?></span>
								<span class="custom"></span>
							</li>
						</ol>
					</div>

					<h4 class="edit-booking-title"><span>Edit Booking for:</span></h4>
					<h3 class="booking-edit-option-name"><?php echo $booking->tour_name; ?> - <?php echo $booking->option_name; ?></h3>

					<span class="booking-edit-note"></span>
				</div>

				<div class="row rezgo-booking-edit div-box-shadow">

					<div class="current-booking-details">
						<?php if((string) $booking->date != 'open') { ?>
							<div class="flex-table-group flex-50">
								<div class="flex-table-header"><span class="booking-edit-booked-for">Booked For:</span></div>
								<div class="flex-table-info">
									<p>
										<?php echo esc_html(date((string) $company->date_format, (int) $booking->date)); ?>
										<?php if ($site->exists($booking->time)) { ?> at <?php echo esc_html($booking->time); ?>
											<?php } ?>
										&nbsp;
										&nbsp;
									</p>
								</div>
							</div>
						<?php } else { ?>
							<?php if ($site->exists($booking->time)) { ?>
								<div class="flex-table-group flex-50">
									<div class="flex-table-header"><span class="booking-edit-time">Time</span></div>
									<div class="flex-table-info">
										<p><span><?php echo esc_html($booking->time); ?></span></p>
									</div>
								</div>
							<?php } ?>
						<?php } ?>

						<?php 
							foreach ($pax_array as $pax) {
								if ($booking->{$pax.'_num'} > 0) {
									$pax_str[] .= $booking->{$pax.'_num'}.' '.$booking->{$pax.'_label'};
									$pax_count += (int)$booking->{$pax.'_num'};
									$pax_booked[$pax] = $booking->{$pax.'_num'};
								}
							} 
							$pax_str = implode(', ', $pax_str);
						?>

						<div class="flex-table-group flex-50">
							<div class="flex-table-header"><span class="booking-edit-guest-list">Current Guest List:</span></div>
							<div class="flex-table-info">
								<p>
									<?php echo $pax_str;  ?>
								</p>
							</div>
						</div>
					</div>

					<div class="current-booking-summary">
						<div class="flex-table-group flex-50">
							<div class="flex-table-header">
								<span class="booking-edit-total">Booking Total:</span>
								<span class="total-amount"><?php echo esc_html($site->formatCurrency($booking->overall_total)); ?></span>
							</div>
						</div>

						<?php if($site->exists($booking->overall_paid)) { ?>
							<div class="flex-table-group flex-50">
								<div class="flex-table-header">
									<span class="booking-edit-total-paid">Total Paid:</span>
									<span class="total-paid-amount"><?php echo esc_html($site->formatCurrency($booking->overall_paid)); ?></span>
								</div>
							</div>
							<div class="flex-table-group flex-50">
								<div class="flex-table-header">
									<span class="booking-edit-total-owing">Total Owing:</span>
									<span class="total-owing-amount"><?php echo esc_html($site->formatCurrency(((float)$booking->overall_total - (float)$booking->overall_paid))); ?></span>
								</div>
							</div>
						<?php } ?>
					</div>
		<?php } // foreach ($site->getBookings('q='.$trans_num) as $booking) ?>