<div id="rezgo-fixed-cart-gc" class="col-4 d-lg-block d-none">

	<div class="cart-summary">
		<div class="toggle-content">
			<div class="line-items">

				<?php if ($option) { ?>
				<span class="summary-count"> Gift Card </span>
				<?php } ?>

				<div class="item">
					<h4 class="single-item">
						<span class="rezgo-summary-item-name"><?php echo $option ? esc_html($option[0]->item) : 'Gift Card '; ?></span>
						
						<?php if ($option) { ?>
						<br> 
						<span class="rezgo-summary-option-name"><?php echo esc_html($option[0]->option); ?></span>
						<?php } ?>

					</h4>
				</div>
				<hr>

				<div class="row">
					<div class="col-12 rezgo-summary-order-total">
						<div class="rezgo-total-container">
							<h5><span>Total Due</span></h5> &nbsp; &nbsp;
							<span id="total_value"><?php echo $site->formatCurrency($order_total); ?></span>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>


<script>

// fixed summary at the side 
function getScrollTop() {
    if (typeof window.parent.pageYOffset !== "undefined" ) {
        // Most browsers
        return window.parent.pageYOffset;
    }
    var d = document.documentElement;
    if (typeof d.clientHeight !== "undefined") {
        // IE in standards mode
        return d.scrollTop;
    }
    // IE in quirks mode
    return document.body.scrollTop;
}

let cart = document.getElementById("rezgo-fixed-cart-gc");
let container = parent.document.getElementById('rezgo_content_container');
// account for whitelabel header
let header = parent.document.getElementById('rezgo-default-header');

function toggleScroll(){
	window.parent.addEventListener('scroll', function() {
		let scroll = getScrollTop();
		let offset = container.offsetTop - 150;

		if (header){
			headerHeight = 80;
		} else {
			headerHeight = 0;
		}

		cart.style.top = (scroll - offset + headerHeight) + "px";

		<?php if (REZGO_WORDPRESS) { ?>
			if (parseInt(cart.style.top) < 0) {
				cart.style.top = 120 + "px";
			}
		<?php } ?>
	});
}

toggleScroll();

</script>