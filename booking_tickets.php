<?php 
	// This is the booking receipt page
	require('rezgo/include/page_header.php');

	// start a new instance of RezgoSite
	$site = new RezgoSite('secure');

	$site->setMetaTags('<meta name="robots" content="noindex, nofollow">');
?>

<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta name="robots" content="noindex, nofollow">
		<title>Booking Tickets</title>
		
		<?php if($site->exists($site->getStyles())) { echo '<style>'.$site->getStyles().'</style>'; } ?>
		
		<style>
			@font-face {
				font-family: 'Open Sans';
				font-style: normal;
				font-weight: 400;
				src: local('Open Sans'), local('OpenSans'), url('<?php echo home_url('/', 'https').esc_attr($site->path); ?>/fonts/open-sans.woff') format('woff');
			}
			body {
				font-family: 'Open Sans', 'Noto';
			}
		</style>
		
	</head>

	<body>
    <?php echo $site->getTemplate('booking_tickets'); ?>
    
 	<?php if(isset($_REQUEST['print'])) { ?>
		<script>
			var imgs = document.images,
				len = imgs.length,
				counter = 0;

			[].forEach.call(imgs, function(img) {
				if(img.complete) {
					incrementCounter();
				} else {
					img.addEventListener('load', incrementCounter, false);
				}
			});

			function incrementCounter() {
				counter++;
				if (counter === len) {	
					window.focus();
					window.print();
				}
			}
		</script>
    <?php } ?>
    
	</body>
</html>