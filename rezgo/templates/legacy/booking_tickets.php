<?php 

	$split = explode(",", sanitize_text_field($site->decode($_REQUEST['trans_num']))); 

	$ticket_output = [];
	
	foreach((array) $split as $v) {
		$trans_num = $v;
		
		if(!$trans_num) $site->sendTo("/");
		$booking = $site->getBookings($trans_num, 0);
		
		$checkin = (string) $booking->checkin;
		$availability_type = (string) $booking->availability_type;
		$checkin_state = $booking->checkin_state;
		$type = (string) $booking->ticket_type ?: 'voucher'; 
		
		if($checkin && $availability_type != 'product') {
			
			$ticket_content = $site->getTicketContent($trans_num, 0);
			 
			foreach($ticket_content->tickets as $ticket_list) { 
				
				foreach ($ticket_list as $ticket) { 

					if($ticket == 'Already checked in') {
						$ticket_output[] = ['e' => 'Ticket for booking '.$trans_num.' is already checked in.'];
					} else {
						$ticket_output[] = $ticket;
					}
					
				}
				
			}
			
		} elseif(!$checkin && $availability_type != 'product') {
			if($booking->status == 3) {
				$ticket_output[] = ['e' => 'Booking '.$trans_num.' has been cancelled, ticket is not available.'];
			} else {
				$ticket_output[] = ['e' => 'Tickets for booking '.$trans_num.' are not available until the booking has been set to received.'];
			}
		} else {
			$ticket_output[] = ['e' => 'Tickets are not available for product purchase '.$trans_num.'.'];
		}
		
	}

	rezgo_plugin_scripts_and_styles();
	wp_print_scripts();
	wp_print_styles();
	
	$count = 0;
	$total = count($ticket_output);

		foreach($ticket_output as $ticket) {
		
		$count++;

		if(is_array($ticket)) {
			
			echo '<div class="rezgo-print-hide">
				<div class="rezgo-ticket text-center">
					'.$ticket['e'].'
					<div class="clearfix"></div>
				</div>
				
				<br>
				
			</div>';
				
		} else {
			
			echo wp_kses($ticket, ALLOWED_HTML);
	
			echo '<br>';
			
			if($total != $count) echo '<div class="rezgo-ticket-break"></div>';
			
		}
		
		echo '<div class="clearfix"></div>';
		
	}

?>