<?php
session_start();
// reset cookie
$site->setCookie('rezgo_gift_card_'.REZGO_CID, '');
if (in_array($_REQUEST['card'], FAKE_GIFT_CARDS)) {

		// give a dummy response
		$card = (object) array(
			'first_name' => 'Info',
			'email' => 'asd@gmail.com',
			'created' => date("U"),
			'sent' => (object) array(
					'to' => 'qwe@gmail.com',
					'message' => 'Lorem ipsum dolor!',
					),
			'amount' => 50,
			'billing' => (object) array(
					'first_name' => 'qwerty',
					'last_name' => 'ptyuio',
					'address_1' => '23 42nd Avenue',
					'address_2' => '',
					'city' => 'Brookfield',
					'state' => 'Columbia',
					'country' => 'ca',
					'postal' => '43212',
					'email' => 'asd@gmail.com',
					'phone' => '604-884-4526',
				),
			);

	$billing = $card->billing;

} else {
	// continue with a real success
	$res = $site->getGiftCard($site->decode(sanitize_text_field($_REQUEST['card']))); 
	$card = $res->card;
	$billing = $card->billing;
}

// continue with response
$company = $site->getCompanyDetails();
$site->readItem($company);
$debug = 0;
unset($_SESSION['gift-card']);
?>

<div class="container-fluid rezgo-container">
	<div class="row">
		<div class="col-xs-12">
			<div class="rezgo-gift-card-container gift-card-receipt">
				<div class="master-heading">
					<h3 style="margin-bottom:0px;"><span>PURCHASE COMPLETE</span></h3>
				</div>

				<div class="rezgo-gift-card-group balance-section clearfix">
					<div class="rezgo-gift-card-head">
						<h3><span class="text-info">Gift Card Receipt</span></h3>
						<p>Thank you for your gift card purchase. The gift card has been sent to <span><?php echo esc_html($card->email); ?>.</span></p>
					</div>
					
					<div class="clearfix">
					<table class="table">
						<tr>
							<td>Date</td>
							<td><?php echo esc_html(date((string) $company->date_format, (int) $card->created)); ?></td>
						</tr>

						<tr>
							<td>Sent To</td>
							<td><?php echo esc_html($card->first_name ?? ''); ?> <?php echo esc_html($card->last_name ?? ''); ?> <?php echo esc_html($card->sent->to ?? ''); ?></td>
						</tr>

						<tr>
							<td>Value</td>
							<td><?php echo esc_html($site->formatCurrency((float) esc_html($card->amount))); ?></td>
						</tr>
						<?php if(isset($card->sent->message)) { ?>
						<tr>
							<td>Message</td>
							<td><?php echo nl2br((string) esc_textarea($card->sent->message)); ?></td>
						</tr>
						<?php } ?>
					</table>
					</div>
				</div>

				<div class="rezgo-gift-card-group balance-section clearfix">
					<div class="rezgo-gift-card-head">
						<h3 class="text-info"><span>Billing Information</span></h3>
					</div>

					<div class="clearfix">
					<table class="table">
						<?php if (isset($billing->first_name)) { ?>
							<tr>
								<td>First Name</td>
								<td><?php echo esc_html($billing->first_name); ?></td>
							</tr>
						<?php } ?>

						<?php if (isset($billing->last_name)) { ?>
							<tr>
								<td>Last Name</td>
								<td><?php echo esc_html($billing->last_name); ?></td>
							</tr>
						<?php } ?>

						<?php if (isset($billing->address_1)) { ?>
							<tr>
								<td>Address</td>
								<td><?php echo esc_html($billing->address_1); ?></td>
							</tr>
						<?php } ?>

						<?php if (isset($billing->address_2)) { ?>
							<tr>
								<td>Address 2</td>
								<td><?php echo esc_html($billing->address_2); ?></td>
							</tr>
						<?php } ?>

						<?php if (isset($billing->city)) { ?>
							<tr>
								<td>City</td>
								<td><?php echo esc_html($billing->city); ?></td>
							</tr>
						<?php } ?>

						<?php if (isset($billing->state)) { ?>
							<tr>
								<td>Prov/State</td>
								<td><?php echo esc_html($billing->state); ?></td>
							</tr>
						<?php } ?>

						<?php if (isset($billing->country)) { ?>
							<tr>
								<td>Country</td>
								<td>
									<?php foreach ($site->getRegionList() as $iso => $name) { ?>
										<?php if ($iso == $billing->country) { ?>
											<?php echo ucwords(esc_html($name)); ?>
										<?php } ?>
									<?php } ?>
								</td>
							</tr>
						<?php } ?>

						<?php if (isset($billing->postal)) { ?>
							<tr>
								<td>Postal Code/ZIP</td>
								<td><?php echo esc_html($billing->postal); ?></td>
							</tr>
						<?php } ?>

						<?php if (isset($billing->email)) { ?>
							<tr>
								<td>Email</td>
								<td><?php echo esc_html($billing->email); ?></td>
							</tr>
						<?php } ?>

						<?php if (isset($billing->phone)) { ?>
							<tr>
								<td>Phone</td>
								<td><?php echo esc_html($billing->phone); ?></td>
							</tr>
						<?php } ?>
					</table>
					</div>

					<div class="rezgo-company-info">
						<p>
							<span>Only one gift card may be used per order.</span>

							<br/>

							<a href="javascript:void(0);" onclick="javascript:window.open('<?php echo esc_js($site->base); ?>/terms',null,'width=800,height=600,status=no,toolbar=no,menubar=no,location=no,scrollbars=1');">Click here to view the terms and conditions.</a>
						</p>

						<br/>

						<h3 id="rezgo-receipt-head-provided-by">
							<span>Valid At</span>
						</h3>

						<address>
							<?php $company = $site->getCompanyDetails($booking->cid ?? ''); ?>
							<strong><?php echo esc_html($company->company_name); ?></strong><br />
							<?php echo esc_html($company->address_1); ?><?php if($site->exists($company->address_2)) { ?>, <?php echo esc_html($company->address_2); ?><?php } ?>
							<br />
							<?php echo esc_html($company->city); ?>,
							<?php if ($site->exists($company->state_prov)) { ?><?php echo esc_html($company->state_prov); ?>, <?php } ?>
							<?php echo esc_html($site->countryName($company->country)); ?><br />
							<?php echo esc_html($company->postal_code); ?><br />
							<?php echo esc_html($company->phone); ?><br />
							<?php echo esc_html($company->email); ?>
							<?php if ($site->exists($company->tax_id)) { ?><br />Tax ID: <?php echo esc_html($company->tax_id); ?><?php } ?>
						</address>
					</div>
				</div>
			</div>
		</div>

		<?php if ($debug) { ?>
			<div class="col-xs-12">
				<pre><?php var_dump($card); ?></pre>
			</div>
		<?php } ?>

	</div>	
</div>