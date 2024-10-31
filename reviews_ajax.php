<?php 
	require('rezgo/include/page_header.php');

	// new instance of RezgoSite
	$site = new RezgoSite();
	
	$company = $site->getCompanyDetails();
	
	$response = '';

	$limit = sanitize_text_field($_REQUEST['limit']);
	$com = sanitize_text_field($_REQUEST['com']);
	$type = sanitize_text_field($_REQUEST['type']);
	$wp_slug = sanitize_text_field($_REQUEST['wp_slug']);
	
	// get reviews
	if ($_REQUEST['action'] == 'rezgo') {
		
		$item_reviews = $site->getReview($com, $type, $limit);

		if ( $_REQUEST['sort'] || $_REQUEST['order']) {
			$item_reviews = $site->getReview($com, $type, $limit, sanitize_text_field($_REQUEST['sort']), sanitize_text_field($_REQUEST['order']));
		}
		
		if (strpos($_REQUEST['limit'], ',') !== false) {
			$l = explode(',', $limit);
			$lower_limit = $l[0];
			$upper_limit = $l[1];
		}	else {
			$lower_limit = 0;
			$upper_limit = $limit;
		}
			
		$counted = 0;
		
		$running_count = $lower_limit + 1;
		
		if($item_reviews->total >= 1) {
			
			foreach ($item_reviews->review as $review) {
				
				if ($review->item != '') {
					$base_link = REZGO_WORDPRESS ? home_url().'/'.$wp_slug : $site->base;
					$item_link = $base_link.'/details/'.$review->com.'/'.$site->seoEncode($review->item).'/?review_link=1&review_item='.urlencode($review->item);
				}
				
				$response .= '<div class="rezgo-review-container review-'.$review->rating.'-star" data-num="'.$running_count.'" data-trans="'.$review->booking.'">';

				$response .= '<div class="review-title-container">'. ($review->title ? '<p class="review-title">'. $review->title .'</p>' : '');

				if ($_REQUEST['com'] == 'all' && $review->option_id != '') {
					$response .= ' <span class="rezgo-review-item-name">reviewing <a href="'.$item_link.'" class="link">'.$review->item.'</a></span>';
				}

				$response .= '</div>';
																
				for($n=1; $n<=5; $n++) {
					$response .= '<i class="rezgo-star fa-star'.(($review->rating >= $n) ? ' fa rezgo-star-full' : ' far rezgo-star-empty').'"></i>';
				}
				
				$review_date = (int) $review->date;
				
				if (strpos($company->time_format, '-') === false) {
					$review_date += (int) $company->time_format * 3600;
				} else {
					$review_date -= (int) $company->time_format * 3600;
				}					
				
				$response .= '
				<span class="review-info">
				'. ($review->name != '' ? $review->name : '') . '
				'. ($review->country != '' ? ' from '. $site->countryName($review->country) : '') . '
				 on '. date((string) $company->date_format, $review_date) . '
				</span><br />
				<div class="rezgo-review-body" style="max-height:'.($_REQUEST['view'] == 'list' ? '320' : '110').'px; overflow:hidden;">'. nl2br($review->body); // 
				
				if ($review->response) {
					
					$response_date = (int) $review->response->date;
					
					if (strpos($company->time_format, '-') === false) {
						$response_date += (int) $company->time_format * 3600;
					} else {
						$response_date -= (int) $company->time_format * 3600;
					}					
				
					$response .= '
					<div class="clearfix">&nbsp;</div>
					<i class="fa fa-reply fa-flip-horizontal"></i> &nbsp;
					<span class="review-response">Response by '.$company->company_name.' on '. date((string) $company->date_format, $response_date) .'</span><br />
					<blockquote>'. nl2br($review->response->body) .'</blockquote>'; // 
				
				}
				
				$response .= '
				</div>
				</div>
				<div class="clearfix rezgo-review-break review-'.$review->rating.'-star">&nbsp;</div>
				';
				
				$counted++;
				$running_count++;
				
			}
		
		}
		
		if ( $counted > 0 ) {
			
			$response .= '
				<script>
				jQuery(document).ready(function ($) {
					$(\'.rezgo-review-body\').readmore({
						speed: 500,
						collapsedHeight: '.($_REQUEST['view'] == 'list' ? '180' : '110').',
						heightMargin: 26,
						moreLink: \'<a href="#" class="rezgo-review-readmore"><i class="fa fa-chevron-down"></i> Read More</a>\',
						lessLink: \'<a href="#" class="rezgo-review-readmore"><i class="fa fa-chevron-up"></i> Read Less</a>\'	
					});
				});          
				</script>		
			';
			
			// link to full review list
			if ($_REQUEST['view'] == 'details' && $_REQUEST['total'] > 5) {

				if (REZGO_WORDPRESS) {				
					if (is_multisite() && !SUBDOMAIN_INSTALL) {
						$wp_current_page = str_replace( DOMAIN_CURRENT_SITE.'/', '', REZGO_WP_DIR ) .'/'. $wp_slug;
					} else {
						$wp_current_page = $wp_slug;
					}
					$review_link = $wp_current_page.'/reviews/item/'.$com;
				} else {
					$review_link = $site->base.'/reviews/item/'.$com;
				}
				
				$response .= '
				<span id="rezgo-view-all-reviews">
					<a class="underline-link" href="'.esc_html($review_link).'" target="_top">View '.(esc_html($_REQUEST['total']) - esc_html($limit)).' more review'.(($_REQUEST['total'] - $limit > 1) ? 's' : '').' for this item</a>
				</span>
				';
			}		
			
			// show the next page 
			if ($_REQUEST['view'] == 'list' && ($counted == $upper_limit) && $running_count <= $_REQUEST['total']) {
				$response .= '
				<div id="rezgo-more-reviews-btn">
					<span class="rezgo-load-more-wrap">
						<span class="btn-check"></span>
						<button class="btn btn-default btn-lg btn-block rezgo-review-load-more" id="rezgo-load-more-reviews"><i class="fa fa-list"></i> &nbsp; Load more reviews</button>
					</span>
				</div>
				';
			}
			
		} else {
			
			if ($lower_limit == 0) {
				$response .= '<p class="lead">There are no reviews to show at this time. Please check back later.</p>';		
			} else {
				$response .= '<p class="rezgo-review-container">There are no more reviews available. Please check back later.</p>';	
			}
			
		} // end if (counted)
		
	}

	if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
		// ajax response if we requested this page correctly
		echo wp_kses($response, ALLOWED_HTML);		
	} else {
		// if, for some reason, the ajax form submit failed, then we want to handle the user anyway
		die ('Something went wrong getting reviews.');
	}
	
?>