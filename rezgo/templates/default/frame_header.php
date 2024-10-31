<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" id="rezgo_html">
<head>
<?php 
	$company = $site->getCompanyDetails();
	$tg_enabled = $company->ticketguardian;
	$analytics_ga4 = (string)$company->analytics_ga4;
	$analytics_gtm = (string)$company->analytics_gtm;
	$get_styles = $company->styles;
?>

<?php if ($tg_enabled) { ?> 
	<script>
		(function(w,d,c,n,s,p){
		w[n] = w[n] || function () { (w[n].q = w[n].q || []).push(arguments) }, w[n].l = +new Date();
		s = d.createElement(c), p = d.getElementsByTagName(c)[0];
		s.async = s.src = 'https://icw.protecht.io/client-widget.min.js';
		p.parentNode.insertBefore(s, p);
		})(window,document,'script','tg');
	</script>
<?php } ?>

<?php if ($analytics_ga4) { ?>
	<!-- Google tag (gtag.js) -->
	<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo $analytics_ga4; ?>"></script>
	<script>
	window.dataLayer = window.dataLayer || [];
	function gtag(){dataLayer.push(arguments);}
	gtag('js', new Date());

	gtag('config', '<?php echo $analytics_ga4; ?>');
</script>
<?php } ?>

<?php if ($analytics_gtm) { ?>
	<!-- Google Tag Manager -->
	<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
	new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
	j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
	'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
	})(window,document,'script','dataLayer', '<?php echo esc_html($analytics_gtm); ?>');</script>
	<!-- End Google Tag Manager -->
<?php } ?>

 	<meta http-equiv="Cache-control" content="no-cache" />
	<meta http-equiv="Pragma" content="no-cache" />
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta name="format-detection" content="telephone=no" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<?php 
		$http = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://';
		$host = $_SERVER['HTTP_HOST'] ?? '';
		$path = ($_REQUEST['mode'] != 'index') ? str_replace('page_', '', sanitize_text_field($_REQUEST['mode'])).'/' : '';
		$tags = isset($_REQUEST['tags']) ? sanitize_text_field($_REQUEST['tags']).'/' : '';
		$slug = isset($_REQUEST['wp_slug']) ? sanitize_text_field($_REQUEST['wp_slug']).'/' : '';
		
		// build canonical url
		$canonical = $http.$host.'/'.$slug;
		if ($path == 'details/') {
			$canonical .= $path.sanitize_text_field($_REQUEST['com'] ?? '').'/'.sanitize_text_field($_REQUEST['seo_name'] ?? '').'/'.$tags;
		} else {
			$canonical .= $path.$tags;
		}
  ?>
	<link rel="canonical" href="<?php echo esc_url($canonical); ?>" />
	<title><?php echo esc_html(stripslashes($_REQUEST['page_title'] ?? '')); ?></title>
	<style>body { overflow:hidden; }</style>
	

	<?php
	rezgo_plugin_scripts_and_styles();
	wp_print_scripts();
	wp_print_styles();
	?>

	<?php if ($get_styles) { ?>
		<style><?php echo $get_styles ?></style>
	<?php } ?>

<?php if ($site->exists($site->getMetaPixel())) { ?>
	<!-- Facebook Pixel Code -->
	<script>
	!function(f,b,e,v,n,t,s)
	{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
	n.callMethod.apply(n,arguments):n.queue.push(arguments)};
	if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
	n.queue=[];t=b.createElement(e);t.async=!0;
	t.src=v;s=b.getElementsByTagName(e)[0];
	s.parentNode.insertBefore(t,s)}(window, document,'script',
	'https://connect.facebook.net/en_US/fbevents.js');
	fbq('init', '<?php echo $site->getMetaPixel(); ?>');
	fbq('track', 'PageView');
	</script>
	<noscript>
	<img height="1" width="1" style="display:none" 
		src="https://www.facebook.com/tr?id=<?php echo $site->getMetaPixel(); ?>&ev=PageView&noscript=1"/>
	</noscript>
	<!-- End Facebook Pixel Code -->
<?php } ?>

	<base target="_<?php echo REZGO_FRAME_TARGET; ?>">
</head>

<body>

<?php if ($analytics_gtm) { ?>
	<!-- Google Tag Manager (noscript) -->
	<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo $analytics_gtm; ?>"
	height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
	<!-- End Google Tag Manager (noscript) -->
<?php } ?>