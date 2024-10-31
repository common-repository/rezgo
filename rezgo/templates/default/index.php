<div id="rezgo-index-wrp" class="container-fluid rezgo-container">
  <div class="row">
	  <?php if ($site->getPageContent('intro')) { ?>
	    <div class="rezgo-intro col-12">
			<?php echo wp_kses($site->getPageContent('intro'), ALLOWED_HTML); ?>
	    </div>
	  <?php } ?>

	  <?php echo $site->getTemplate('topbar_order'); ?>

	<div class="rezgo-list-view-container">

		<div class="col-12" id="rezgo-list-content"></div>

		<div class="col-12" id="rezgo-list-content-footer"></div>

		<div class="col-12" id="rezgo-list-content-more">
			<button type="button" class="btn btn-default btn-lg btn-block" id="rezgo-index-more-button" data-rezgo-page="<?php echo $site->requestNum('pg'); ?>">
				<span><i class="fa fa-list" style="margin-right: 15px;"></i>Load more items </span>
				<!-- <span> More Items &hellip;</span> -->
			</button>
		</div>

		<div class="col-12" id="rezgo-list-content-bottom">&nbsp;</div>
	</div><!-- // .rezgo-list-container -->
</div><!-- // .rezgo-container -->

<script>
	let start = 1;
	let search_start_date = '<?php echo $site->requestStr('start_date'); ?>';
	let search_end_date = '<?php echo $site->requestStr('end_date'); ?>';
	let search_tags = '<?php echo $site->requestStr('tags'); ?>';
	let search_in = '<?php echo $site->requestStr('search_in'); ?>';
	let search_for = '<?php echo $site->requestStr('search_for'); ?>';
	let cid = '<?php echo $site->requestNum('cid'); ?>';

	jQuery(document).ready(function($){

		<?php if ($this->empty_cart) { ?>
			<?php echo LOCATION_WINDOW; ?>.location.href = 'https://' + '<?php echo $site->clean_uri; ?>';
		<?php } ?>
          
		$content = $('#rezgo-list-content');
		$footer = $('#rezgo-list-content-footer');

		function getRezgoFeed() {
			$.ajax({
				url: '<?php echo admin_url('admin-ajax.php'); ?>',
				data: {
					action: 'rezgo',
					method: 'index_ajax',
					parent_url: '<?php echo esc_html($site->base); ?>',
					wp_slug: '<?php echo esc_html($_REQUEST['wp_slug'] ?? ''); ?>',
					pg: start,
					start_date: search_start_date,
					end_date: search_end_date,
					tags: search_tags,
					search_in: search_in,
					search_for: search_for,
					cid: cid,
					security: '<?php echo wp_create_nonce('rezgo-nonce'); ?>'
				},
				context: document.body,
				success: function(data) {

					$footer.html('');

					var split = data.split('|||');

					$content.append(split[0]);

					$('#rezgo-ajax-container-' + start).fadeIn('slow', function() {
						if (split[1] == 1) {
							$('#rezgo-list-content-more').show();
							start++;	
						}
					});

					if ('parentIFrame' in window) {
						setTimeout(function(){
							parentIFrame.size();
						}, 0);
					}
				}
			});
		}

		$footer.html('<div class="rezgo-wait-div"></div>');
		getRezgoFeed();

		$('#rezgo-index-more-button').click(function() {
		
			let page_num = $(this).attr('data-rezgo-page'); 
			
			$footer.html('<div class="rezgo-wait-div"></div>');
			$('#rezgo-list-content-more').fadeOut();
				getRezgoFeed();
		});		
			
	});
</script> 