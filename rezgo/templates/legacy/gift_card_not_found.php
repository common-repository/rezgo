<div class="container-fluid rezgo-container">
	<div class="row">
		<div class="col-xs-12">
			<div id="rezgo-gift-card-search" class="clearfix">
				<div class="rezgo-gift-card-group search-section clearfix">
					<h3 class="gc-page-header"><span class="">Gift Card Not Found</span></h3>
						<h5>To check your balance, enter a gift card number.</h5>

					<form id="search" role="form" method="post" target="rezgo_content_frame">
						<div class="input-group rezgo-gift-input-group">
							<input type="text" class="form-control" id="search-card-number" placeholder="Gift Card Number" />
							<button class="btn btn-primary rezgo-check-balance rezgo-btn-default" type="submit" form="search"><span>Check Balance</span></button>
						</div>
					</form>

					<div class='rezgo-gift-search-response' style='display:none'>
						<span class='msg'></span>
					</div>
					<br>
				</div>
				
				<div id="gift-icon-container">
					<img id="gift-card-img" src="<?php echo $site->path; ?>/img/gift.svg" alt="Search Gift Card">
				</div>

			</div>
		</div>
	</div>
</div>

<script>
	jQuery(document).ready(function($) {
	/* FORM (#search) */
	let $search = $('.search-section');
	let $searchForm = $('#search');
	let $searchText = $('#search-card-number');
	$searchForm.submit(function(e){
		e.preventDefault();
		let search = $searchText.val();
		if (search) {
			top.location.href = '<?php echo esc_html($site->base); ?>/gift-card/'+search;
		} else {
			$searchText.css({'borderColor':'#a94442'});
			err = "Please enter a Gift Card Number.";
			$('.rezgo-gift-search-response .msg').html(err);
			$('.rezgo-gift-search-response').addClass('error');
			$('.rezgo-gift-search-response').slideDown();
		}
});
	});
</script>
