
	<div
		<?php echo (isset($_REQUEST['hide_footer']) ? ' style="display:none"' : ''); ?> 
		<?php if($_REQUEST['mode'] == 'waiver') { echo ' style="display:none"'; }; ?>
	>

		<div id="rezgo-seal-refid-container">
			<?php if (!REZGO_WORDPRESS) { ?>
				<?php if ( $_SERVER['SCRIPT_NAME'] == '/page_book.php' || $_SERVER['SCRIPT_NAME'] == '/page_payment.php' || $_SERVER['SCRIPT_NAME'] == '/gift_card.php' ) { ?>
					<div id="rezgo-secure-seal">
						<div id="trustwave-seal"><script type="text/javascript" referrerpolicy="origin" src="https://seal.securetrust.com/seal.js?style=invert"></script></div>
					</div>
				<?php } ?> 
			<?php } ?> 
			<?php if ($site->exists($site->refid) || isset($_COOKIE['rezgo_refid_val'])) { ?>
				<div id="rezgo-refid">
					RefID: <?php echo ($site->exists($site->refid)) ? esc_html($site->refid) : esc_html($_COOKIE['rezgo_refid_val']); ?>
				</div>
			<?php } ?>
		</div>

		<?php if (!REZGO_WORDPRESS) { ?>
			<?php if ($_SERVER['SCRIPT_NAME'] != '/modal.php' && !$_REQUEST['headless']) { ?>
				<div style="float:right;height:auto;margin:10px;display:table;">
					<div style="display:table-cell;vertical-align:bottom;">
						<div style="font-size:24px;">
							<a href="http://www.rezgo.com/features/online-booking/" title="Powering Tour and Activity Businesses Worldwide" style="color:#333;text-decoration:none;" target="_blank">
								<span style="display:inline-block;width:65px;height:65px;text-indent:-9999px;margin-left:4px;background:url(<?php echo $site->path; ?>/img/rezgo-logo.svg) no-repeat; background-size:contain;">Rezgo</span>
							</a>
						</div>
					</div>
				</div>
			<?php } ?>
		<?php } ?>
		
	</div>
</body>

<script>
	<?php if (!REZGO_WORDPRESS) { ?>
		(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
		(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
		m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
		})(window,document,'script','//www.google-analytics.com/analytics.js','ga');
		
		ga('create', 'UA-1943654-2', 'auto');
		
		// Set value for custom dimension at index 1.
		//ga('set', 'dimension5', '<?php echo REZGO_CID; ?>');
		
		// Send the custom dimension value with a pageview hit.
		ga('send', 'pageview', {
			'dimension1': '<?php echo REZGO_CID; ?>'
		});
		
		<?php if ($_SERVER['SCRIPT_NAME'] == '/page_order.php' || $_SERVER['SCRIPT_NAME'] == '/page_book.php' || $_SERVER['SCRIPT_NAME'] == '/booking_complete.php') {
			echo "ga('require', 'ec');"."\n";
			if ($_SERVER['SCRIPT_NAME'] == '/page_order.php') {
				echo "
				ga('ec:setAction','checkout', {
						'step': 1
				});
				";
			}
			if ($_SERVER['SCRIPT_NAME'] == '/page_book.php') {
				echo "
				ga('ec:setAction','checkout', {
						'step': 2
				});
				";
			}
			if ($_SERVER['SCRIPT_NAME'] == '/booking_complete.php' && $_SESSION['REZGO_CONVERSION_ANALYTICS']) {
				echo "
				ga('ec:setAction','checkout', {
						'step': 3
				});
				";
				
				echo "
				ga('require', 'ecommerce');
				".$ga_add_transacton."
				ga('ecommerce:send');
				";
			}
		} ?>
		
		var transcode = '<?php echo REZGO_CID; ?>';
	<?php } ?>

    <?php
    if (isset($_SESSION['debug'])) {
        echo '// output debug to console'."\n\n";
        foreach ($_SESSION['debug'] as $debug) {
			$debug = str_replace('"', "", $debug);
            echo "window.console.log('".$debug."'); \n";
        }
        unset($_SESSION['debug']);
    }
    ?>
</script>

</html>