<?php
	// any new page must start with the page_header, it will include the correct files
	// so that the rezgo parser classes and functions will be available to your templates
	if (!REZGO_WORDPRESS) require('rezgo/include/page_header.php');

	// start a new instance of RezgoSite
	$site = new RezgoSite(isset($_REQUEST['sec']) ? sanitize_text_field($_REQUEST['sec']) : '', 1);

    $company = $site->getCompanyDetails();
	$primary_domain = $company->primary_domain;

	// remove the 'mode=page_type' from the query string we want to pass on
	$_SERVER['QUERY_STRING'] = preg_replace("/([&|?])?mode=([a-zA-Z_]+)/", "", sanitize_text_field($_SERVER['QUERY_STRING']));

	$mode = isset($_REQUEST['mode']) ? strip_tags(sanitize_text_field($_REQUEST['mode'])) : '';
	$com = isset($_REQUEST['com']) ? sanitize_text_field($_REQUEST['com']) : ''; 
	$tags = isset($_REQUEST['tags']) ? sanitize_text_field($_REQUEST['tags']) : ''; 
	$date = isset($_REQUEST['date']) ? sanitize_text_field($_REQUEST['date']) : ''; 
	$option = isset($_REQUEST['option']) ? sanitize_text_field($_REQUEST['option']) : ''; 
	$search_for = isset($_REQUEST['search_for']) ? sanitize_text_field($_REQUEST['search_for']) : ''; 
	$start_date = isset($_REQUEST['start_date']) ? sanitize_text_field($_REQUEST['start_date']) : ''; 
	$end_date = isset($_REQUEST['end_date']) ? sanitize_text_field($_REQUEST['end_date']) : ''; 
	$rezgo_page = isset($_REQUEST['rezgo_page']) ? sanitize_text_field($_REQUEST['rezgo_page']) : ''; 
	$review_link = isset($_REQUEST['review_link']) ? sanitize_text_field($_REQUEST['review_link']) : ''; 
	$review_item = isset($_REQUEST['review_item']) ? sanitize_text_field($_REQUEST['review_item']) : ''; 
	$cid = isset($_REQUEST['cid']) ? sanitize_text_field($_REQUEST['cid']) : ''; 
	$trans_num = isset($_REQUEST['trans_num']) ? sanitize_text_field($_REQUEST['trans_num']) : ''; 
	$card = isset($_REQUEST['card']) ? sanitize_text_field($_REQUEST['card']) : ''; 
	$view = isset($_REQUEST['view']) ? sanitize_text_field($_REQUEST['view']) : ''; 
	$type = isset($_REQUEST['type']) ? sanitize_text_field($_REQUEST['type']) : ''; 
	$ids = isset($_REQUEST['ids']) ? sanitize_text_field($_REQUEST['ids']) : ''; 
	$step = isset($_REQUEST['step']) ? sanitize_text_field($_REQUEST['step']) : ''; 
	$cart = isset($_REQUEST['cart']) ? sanitize_text_field($_REQUEST['cart']) : ''; 

	$details_page = $mode == 'page_details' ? 1 : 0;
	$content_page = $mode == 'page_content' ? 1 : 0;
	$index_page = $mode == 'index' ? 1 : 0;
	$order_page = $mode == 'page_order' ? 1 : 0;

    // set a default page title
    $site->setPageTitle(isset($_REQUEST['title']) ? $_REQUEST['title'] : ucwords(str_replace("page_", "", $mode)));

if (!REZGO_WORDPRESS) {
	/*
		this query searches for an item based on a com id (limit 1 since we only want one response)
		then adds a $f (filter) option by uid in case there is an option id, and adds a date in case there is a date set
    */
	if($details_page) {
		$item = $site->getTours('t=com&q=' . $_REQUEST['com'] . '&f[uid]=' . $_REQUEST['option'] . '&d=' . $_REQUEST['date'] . '&limit=1', 0);
	}

    if($primary_domain != '') {
        $site->setMetaTags('<link rel="canonical" href="http://' . (string) $primary_domain . '" />');
        $canonical_link = '<link rel="canonical" href="http://' . (string) $primary_domain . $site->base.'/details/'.$item->com.'/'.$site->seoEncode($item->item) . '" />';
    }

	if($details_page) { 
        
        // if the item does not exist, we want to generate an error message and change the page accordingly
        if(!$item) {
            $item = new stdClass();
            $item->unavailable = 1;
            $item->name = 'Item Not Available';
        }
        
        if($item->seo->seo_title != '') {
            $page_title = $item->seo->seo_title;
        } else {
            $page_title = $item->item;
        }
        
        if($item->seo->introduction != '') {
            $page_description = $item->seo->introduction;
        } else {
            $page_description = strip_tags($item->details->overview);
        }
        
        $site->setPageTitle($page_title);
        
        $site->setMetaTags('
			<meta name="description" content="' . $page_description . '" />
			<meta property="og:url" content="https://' . $_SERVER['HTTP_HOST'] . $site->base.'/details/'.$item->com.'/'.$site->seoEncode($item->item) . '" />
			<meta property="og:title" content="' . $page_title . '" />
			<meta property="og:description" content="' . $page_description . '" />
			<meta property="og:image" content="' . $item->media->image[0]->path . '" />
			<meta http-equiv="X-UA-Compatible" content="IE=edge">
			' . $canonical_link . '
		');
    } elseif($content_page) {
        $title = $site->getPageName($page);
        
        $site->setPageTitle($title);
        
    } elseif($mode == 'index') {
        
        // expand to include keywords and dates
        $site->setPageTitle((($_REQUEST['tags']) ? ucwords($_REQUEST['tags']) : 'Home'));
        
    }
    
    $_SERVER['QUERY_STRING'] .= '&title=' . $site->pageTitle;
    
    // output site header
    echo $site->getTemplate('header');
    
    if($site->config('REZGO_COUNTRY_PATH')) {
        include(REZGO_COUNTRY_PATH);
    } else {
        include($site->path . '/include/countries_list.php');
    }
?>
	<script>
		// load jQuery if not loaded
		window.onload = function () {
			if (typeof jQuery == 'undefined') {
				// load jQuery
				var script = document.createElement('script');
				script.src = 'https://code.jquery.com/jquery-3.5.1.min.js';
				script.type = 'text/javascript';
				script.onload = function () {
					var $ = window.jQuery;
				};
				document.getElementsByTagName('head')[0].appendChild(script);
			}
		}
	</script>

	<?php if(in_array((string) $country, $eu_countries)) { ?>
		<?php if(!REZGO_LITE_CONTAINER && $_REQUEST['mode'] != 'return_trip') { ?>
			<script>
				window.cookieconsent_options = {
					message: 'This website uses cookies to improve user experience. By using our website you consent to all cookies in accordance with our Cookie Policy. Click &lsquo;Accept&rsquo; to allow all cookies from this website.',
					theme: '<?php echo $site->path; ?>/css/cookieconsent.css',
					learnMore: 'Read more',
					link: '/cookie-policy',
					dismiss: 'Accept'
				};
			</script>

			<script src="//cdnjs.cloudflare.com/ajax/libs/cookieconsent2/1.0.9/cookieconsent.min.js"></script>
			<style>
				/* places cookie banner at the bottom  */
				.cc_container {
					top: unset;
					bottom: 0;
				}
				.cc_banner-wrapper {
					animation: none;
					height: 0;
				}
			</style>
		<?php } ?>
	<?php } ?>
	<script type="text/javascript" src="/js/ie8.polyfils.min.js"></script>
	<script type="text/javascript" src="/js/iframeResizer.contentWindow.min.js"></script>

<?php } else { ?>

	<?php
		if ($details_page) {
			/*
				this query searches for an item based on a com id (limit 1 since we only want one response)
				then adds a $f (filter) option by uid in case there is an option id, and adds a date in case there is a date set	
			*/

			$trs	= 't=com';
			$trs .= '&q=' .$com;
			$trs .= '&f[uid]=' .$option;
			$trs .= '&d=' .$date;
			$trs .= '&limit=1';

			$item = $site->getTours($trs, 0);

			// if the item does not exist, we want to generate an error message and change the page accordingly
			if (!$item) {
				$item = new stdClass();
				$item->unavailable = 1;
				$item->name = 'Item Not Available'; 
			}

			if (isset($item->seo->seo_title) && $item->seo->seo_title != '') {
				$site->setPageTitle($item->seo->seo_title);
			} 
			else {
				$site->setPageTitle($item->item ?? '');
			}

			$site->setMetaTags('
				<meta name="description" content="' . ($item->seo->introduction ?? '') . '" /> 
				<meta property="og:title" content="' . ($item->seo->seo_title ?? '') . '" /> 
				<meta property="og:description" content="' . ($item->seo->introduction ?? '') . '" /> 
				<meta property="og:image" content="' . ($item->media->image[0]->path ?? '') . '" /> 
				<meta http-equiv="X-UA-Compatible" content="IE=edge">
			');
		}

		elseif ($mode == 'index') {
			// expand to include keywords and dates
			if ($tags) {
				$site->setPageTitle(ucwords($tags));
			}

			else {
				$site->setPageTitle('Home');
			}
		}
	?>

<?php } ?>

<?php
    if($mode == 'return_trip') {
        // $iframe_height = '600px';
        $iframe_height = '90vh';
    } elseif($mode == 'booking_complete') {
        $iframe_height = '1600px';
    } else {
        $iframe_height = '900px';
    }

	if (REZGO_WORDPRESS) {
		$src	= home_url();
		$src .= '?rezgo=1';
		$src .= '&mode='.$mode;
		$src .= '&com='.$com;
		$src .= '&parent_url='.$wp_current_page;
		$src .= '&wp_slug='.$wp_slug;
		$src .= '&tags='.$tags;
		$src .= '&search_for='.$search_for;
		$src .= '&start_date='.$start_date;
		$src .= '&end_date='.$end_date;
		$src .= '&date='.$date;
		$src .= '&rezgo_page='.$rezgo_page;
		$src .= '&option='.$option;
		$src .= '&review_link='.$review_link;
		$src .= '&review_item='.$review_item;
		$src .= '&cid='.$cid;
		$src .= '&trans_num='.$trans_num;
		$src .= '&card='.$card;
		$src .= '&page_title='.sanitize_text_field($site->pageTitle);
		$src .= '&seo_name='.$site->seoEncode($item->item ?? '') ;
		$src .= '&view='.$view;
		$src .= '&type='.$type;
		$src .= '&ids='.$ids;
		$src .= '&step='.$step;
		$src .= '&cart='.$cart;

		// add pax if applicable
		if ($mode == 'gift_card') {
			$src .= isset($_REQUEST['adult']) ? '&adult='.sanitize_text_field($_REQUEST['adult']) : '';
			$src .= isset($_REQUEST['child']) ? '&child='.sanitize_text_field($_REQUEST['child']) : '';
			$src .= isset($_REQUEST['senior']) ? '&senior='.sanitize_text_field($_REQUEST['senior']) : '';
			$src .= isset($_REQUEST['price4']) ? '&price4='.sanitize_text_field($_REQUEST['price4']) : '';
			$src .= isset($_REQUEST['price5']) ? '&price5='.sanitize_text_field($_REQUEST['price5']) : '';
			$src .= isset($_REQUEST['price6']) ? '&price6='.sanitize_text_field($_REQUEST['price6']) : '';
			$src .= isset($_REQUEST['price7']) ? '&price7='.sanitize_text_field($_REQUEST['price7']) : '';
			$src .= isset($_REQUEST['price8']) ? '&price8='.sanitize_text_field($_REQUEST['price8']) : '';
			$src .= isset($_REQUEST['price9']) ? '&price9='.sanitize_text_field($_REQUEST['price9']) : '';
		}

		if ($mode == '3DS') {
			foreach ($_REQUEST as $key => $val) {
				$src .= '&'.$key.'||3DS'.'='.urlencode($val);
			}
		}

		if ($mode == 'log') {
			foreach ($_REQUEST as $key => $val) {
				$src .= '&'.$key.'||log'.'='.sanitize_text_field($val);
			}
		}

	} else {

		parse_str($_SERVER['QUERY_STRING'], $query_arr);

		$query_str = '';
		foreach ($query_arr as $k => $v) {
			$query_str .= '&'.$k.'='.$v;
		}
		$src = htmlspecialchars('/'.$mode.'?'.$query_str, ENT_QUOTES);

	}
?>

<div id="rezgo_content_container" style="width:100%; height:100%;">
	<iframe id="rezgo_content_frame" name="rezgo_content_frame" src="<?php echo $src; ?>" title="<?php echo ucwords(sanitize_text_field($site->pageTitle)); ?>"
		style="width:100%; height:<?php echo esc_attr($iframe_height); ?>; padding:0px; margin:0px;" frameBorder="0" scrolling="no"></iframe>
</div>

<?php if (!REZGO_WORDPRESS) { ?>
<script type="text/javascript" src="/js/iframeResizer.min.js"></script>
<?php } ?>
<script>
    iFrameResize({
        scrolling: true,
        checkOrigin: false,
        onMessage: function (msg) { // send message for scrolling
            var scroll_to = msg.message;
            jQuery('html, body').animate({
                scrollTop: scroll_to
            }, 600);
        }
    });
</script>

<?php if($mode == 'page_order' || $mode == 'page_book' || $mode == 'page_payment' || $mode == 'gift_card' || $mode == 'booking_complete') { ?>

    <?php
		if($mode == 'page_order') {
			$modal_size = 'modal-xl';
			$modal_scroll = 'no';
		} else {
			$modal_size = 'modal-lg';
			$modal_scroll = 'no';
		}
    ?>

	 <?php if(REZGO_LITE_CONTAINER) { ?>
        <style>
            @media (max-width: 1080px) and (min-width: 400px) {
                .modal-xl .modal-content,
                .modal-xl.modal-dialog {
                    max-height: 450px !important;
                }
            }
        </style>
    <?php } ?>

	 <?php if(REZGO_WORDPRESS) { ?>
		<style type="text/css">
			#rezgo-modal-iframe {
				width: 100% !important;
			}
			<?php if($mode == 'page_order') {  ?>
				#rezgo-modal{
					overflow-y: hidden;
				}
			<?php } ?> 
		</style>		
		
		<?php $bs_dismiss = !REZGO_LEGACY_TEMPLATE_USE ? 'data-bs-dismiss="modal"' : 'data-dismiss="modal"'; ?> 
    <?php } ?>

<!-- waiver modal -->
<div class="modal fade" id="rezgo-modal" tabindex="-1" aria-hidden="true" aria-labelledby="rezgo-modal">
		<div class="modal-dialog <?php echo esc_attr($modal_size); ?>">
			<div class="modal-content">
				<div class="modal-header">
					<?php if($mode == 'page_order') { ?>
                        <button type="button" class="btn btn-default" rel="" <?php echo $bs_dismiss; ?> id="rezgo-cross-dismiss"><span>No Thank You</span>
                        </button>
						<?php if (REZGO_WORDPRESS) { ?>
							<!-- add hidden span to dismiss modal on outer container click -->
							<span id="parent-dismiss" class="hidden" data-dismiss="modal"></span>
						<?php } ?>
					<?php } else { ?>
						<button type="button" class="close" <?php echo $bs_dismiss; ?> aria-label="Close">&times;</button>
					<?php } ?>
					<h4 id="rezgo-modal-title" class="modal-title"></h4>
				</div>
		
				<iframe id="rezgo-modal-iframe" frameborder="0" scrolling="<?php echo esc_attr($modal_scroll); ?>" style="width:100%; padding:0px; margin:0px;"></iframe>
                <div id="rezgo-modal-loader" style="display:none">
                    <div class="modal-loader"></div>
                </div>
		</div>
	</div>
</div>

<?php if (!REZGO_WORDPRESS) { ?>
  	<link href="<?php echo $site->path; ?>/css/bootstrap-modal.css" rel="stylesheet"/>
	<link href="<?php echo $site->path; ?>/css/rezgo-modal.css" rel="stylesheet"/>
	
    <script src="//code.jquery.com/jquery-3.5.1.min.js"></script>
	<script src="//cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-u1OknCvxWvY5kfmNBILK2hRnQC3Pr17a+RTT6rIHI7NnikvbZlHgTPOOmMi466C8" crossorigin="anonymous"></script>
<?php } ?>
	<?php if (!REZGO_LEGACY_TEMPLATE_USE) { ?>
		<script>
			var rezgoModal = new bootstrap.Modal(document.getElementById('rezgo-modal'));
		</script>
	<?php } ?>
  
	<?php if ((string) $company->gateway_id == 'tmt') { ?>
		<script src="https://payment.tmtprotects.com/tmt-payment-modal.3.6.1.js"></script>
	<?php } ?>

<?php } ?>

<?php if (!REZGO_WORDPRESS) echo $site->getTemplate('footer'); ?>
