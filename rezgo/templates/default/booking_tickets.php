<?php 

	$split = explode(",", sanitize_text_field($site->decode($_REQUEST['trans_num']))); 

	$ticket_output = [];
	
	foreach((array) $split as $v) {

		$trans_num = $v;
		
		if(!$trans_num) $site->sendTo("/");
		$booking = $site->getBookings($trans_num, 0);
		
		$checkin = isset($booking->checkin) ? (string) $booking->checkin  : '';
		$availability_type = isset($booking->availability_type) ? (string) $booking->availability_type : '';
		$checkin_state = $booking->checkin_state ?? '';

		if($availability_type == 'product') {
			$type = 'merchandise_voucher';
		} else {
			$type = isset($booking->ticket_type) ? (string) $booking->ticket_type : 'voucher'; 
		}
		
		if($checkin) {
			
			$ticket_content = $site->getTicketContent($trans_num, 0);
			 
			foreach($ticket_content->tickets as $ticket_list) { 
				
				foreach ($ticket_list as $ticket) { 

					if($ticket == 'Already checked in') {
						$ticket_output[] = ['e' => 'Ticket #'.$ticket->attributes()['id'].' for booking '.$trans_num.' is already checked in.'];
					} elseif($ticket == 'Already redeemed') {
						$ticket_output[] = ['e' => 'Ticket #'.$ticket->attributes()['id'].' for purchase '.$trans_num.' has already been redeemed.'];
					} else {
						$ticket_output[] = $ticket;
					}
					
				}
				
			}
			
		} elseif(!$checkin) {
			
			if (isset($booking->status)) {
				if($booking->status == 3) {
					$ticket_output[] = ['e' => 'Booking '.$trans_num.' has been cancelled, ticket is not available.'];
				} elseif($booking->status == 2) {
					$ticket_output[] = ['e' => 'Tickets for booking '.$trans_num.' are not available until the booking has been set to received.'];
				}
			}

		} elseif($availability_type == 'product') {

            // do we need this?

		} else {

            // is this for product purchases that are not redeemable?
			$ticket_output[] = ['e' => 'Tickets are not available for product purchase '.$trans_num.'.'];
			
		}
		
	}

	if (REZGO_WORDPRESS){
		rezgo_plugin_scripts_and_styles();
		wp_print_scripts();
		wp_print_styles();
	}

	$count = 0;
	$total = count($ticket_output);

	$show_rezgo_logo = !REZGO_WORDPRESS ? '<div class="rezgo-ticket-rezgo">
					<img src="'.$site->base.'/rezgo/templates/rezgo/img/rezgo-logo.svg" alt="Rezgo">
				</div>' : '<br>';

	foreach($ticket_output as $ticket) {
		
		$count++;

		if(is_array($ticket)) {
			
			echo '<div class="rezgo-print-hide">
				<div class="rezgo-ticket text-center">
					'.$ticket['e'].'
					<div class="clearfix"></div>
				</div>
				
				'.$show_rezgo_logo.'
				
			</div>';
				
		} else {
			
			echo wp_kses($ticket, ALLOWED_HTML);
	
			echo $show_rezgo_logo;
			
			if($total != $count) echo '<div class="rezgo-ticket-break"></div>';
			
		}
		
		echo '<div class="clearfix"></div>';
		
	}

?>