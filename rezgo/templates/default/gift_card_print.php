<?php 
// send the user home if they shoulden't be here
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

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="robots" content="noindex, nofollow">
	<title>Gift Card</title>

	<?php
	rezgo_plugin_scripts_and_styles();
	wp_print_scripts();
	wp_print_styles();
	?>

	<?php if ($site->exists($site->getStyles())) { ?>
		<style><?php echo $site->getStyles();?></style>
	<?php } ?>
</head>

<body>
	<div id="rezgo-gift-card-print" class="rezgo-gift-card-container screen-center">
		<div class="row">
			<div class="col-xs-12">
				<p>&nbsp;</p>
			</div>

            <?php echo $card->content; ?>
			
		</div>
		
	</div>
	
	<script>
		window.print();
	</script>
</body>
</html>