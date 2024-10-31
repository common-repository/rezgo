<?php 
// send the user home if they shouldn't be here
if (!$_REQUEST['card']) {
	$site->sendTo($site->base."/gift-card");
}
$company = $site->getCompanyDetails();
$site->readItem($company);
$res = $site->getGiftCard(sanitize_text_field($_REQUEST['card'])); 
$card = $res->card;
if (!$card) {
	$site->sendTo($site->base."/gift-not-found");
}
$billing = $card->billing;
?>

<div class="container-fluid rezgo-container">
	<div class="row">
		<div class="col-xs-12">
			<div id="rezgo-gift-card-details" class="rezgo-gift-card-container">
				<div class="master-heading">
					<h3><span>Gift Card Details</span></h3>
					<p><span>Click the button below to print your gift card.</span></p>
						<?php $print_url = $site->base.'/gift-print/'.$_REQUEST['card']; ?>
						<a class="btn btn-lg rezgo-btn-print" href="<?php echo esc_url($print_url); ?>" target="_blank">
							<i class="fa fa-print fa-lg"></i>
							<span>&nbsp;PRINT GIFT CARD</span>
						</a>
				</div>

				<div class="rezgo-gift-card-group clearfix">
					
                    <?php echo $card->content; ?>

				</div>

				<div class="rezgo-gift-card-group clearfix">

					<div class="rezgo-gift-card-head">
						<h3><span class="text-info">Transactions</span></h3>
					</div>

					<div class="table-responsive">
						<table class="table">
							<tbody>
								<?php 
									foreach ($card->transactions->transaction as $trans) {
										$action = str_replace('[', '<span class="text-primary">', str_replace(']', '</span>', $trans->action));
										$change = (float) $trans->change;
										if($change > 0) { 
											$change = '<span class="text-success">+&nbsp;'.$site->formatCurrency(preg_replace("/[^0-9.]/", "", $change)).'</span>'; 
										}
										elseif($change < 0) { 
											$change = '<span class="text-danger">-&nbsp;'.$site->formatCurrency(preg_replace("/[^0-9.]/", "", $change)).'</span>'; 
										} else { 
											$change = '0'; 
										}
								?>
								<tr>
									<td><?php echo wp_kses(date((string) $company->date_format, (int) $trans->date), ALLOWED_HTML); ?></td>
									<td><?php echo wp_kses($action, ALLOWED_HTML); ?></td>
									<td align="right"><?php echo wp_kses($change, ALLOWED_HTML); ?></td>
									<td align="right"><?php echo wp_kses($site->formatCurrency((float) $trans->balance), ALLOWED_HTML); ?></td>
								</tr>
								<?php } ?>
							</tbody>
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
							<?php $company = $site->getCompanyDetails($booking->cid); ?>
							<strong><?php echo esc_html($company->company_name); ?></strong><br />
							<?php echo esc_html($company->address_1); ?><?php if($site->exists($company->address_2)) { ?>, <?php echo esc_html($company->address_2); ?><?php } ?>
							<br />
							<?php echo esc_html($company->city); ?>,
							<?php if($site->exists($company->state_prov)) { ?><?php echo esc_html($company->state_prov); ?>, <?php } ?>
							<?php echo esc_html($site->countryName($company->country)); ?><br />
							<?php echo esc_html($company->postal_code); ?><br />
							<?php echo esc_html($company->phone); ?><br />
							<?php echo esc_html($company->email); ?>
							<?php if($site->exists($company->tax_id)) { ?><br />Tax ID: <?php echo esc_html($company->tax_id); ?><?php } ?>
						</address>
					</div>
				</div>

				<?php if (DEBUG) { ?>
					<pre><?php var_dump($card); ?></pre>
				<?php } ?>
			</div>
		</div>
	</div>
</div>