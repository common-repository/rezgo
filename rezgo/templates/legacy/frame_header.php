<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>

<?php if ($site->exists($site->getAnalyticsGa4())) { ?>
	<!-- Google tag (gtag.js) -->
	<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_html($site->getAnalyticsGa4()); ?>"></script>
	<script>
	window.dataLayer = window.dataLayer || [];
	function gtag(){dataLayer.push(arguments);}
	gtag('js', new Date());

	gtag('config', '<?php echo esc_html($site->getAnalyticsGa4()); ?>');
</script>
<?php } ?>

<?php if ($site->exists($site->getAnalyticsGtm())) { ?>
	<!-- Google Tag Manager -->
	<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
	new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
	j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
	'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
	})(window,document,'script','dataLayer', '<?php echo esc_html($site->getAnalyticsGtm()); ?>');</script>
	<!-- End Google Tag Manager -->
<?php } ?>

  <meta http-equiv="Cache-control" content="no-cache" />
	<meta http-equiv="Pragma" content="no-cache" />
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta name="format-detection" content="telephone=no" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<?php 
		$http = ($_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://';
		$host = $_SERVER['HTTP_HOST'];
		$path = ($_REQUEST['mode'] != 'index') ? str_replace('page_', '', sanitize_text_field($_REQUEST['mode'])).'/' : '';
		$tags = ($_REQUEST['tags']) ? sanitize_text_field($_REQUEST['tags']).'/' : '';
		$slug = ($_REQUEST['wp_slug']) ? sanitize_text_field($_REQUEST['wp_slug']).'/' : '';
		
		// build canonical url
		$canonical = $http.$host.'/'.$slug;
		if ($path == 'details/') {
			$canonical .= $path.sanitize_text_field($_REQUEST['com']).'/'.sanitize_text_field($_REQUEST['seo_name']).'/'.$tags;
		} else {
			$canonical .= $path.$tags;
		}
  ?>
	<link rel="canonical" href="<?php echo esc_url($canonical); ?>" />
	<title><?php echo esc_html($_REQUEST['page_title']); ?></title>
	<style>body { overflow:hidden; }</style>
	

	<?php
	rezgo_plugin_scripts_and_styles();
	wp_print_scripts();
	wp_print_styles();
	?>

	<?php if ($site->exists($site->getStyles())) { ?>
		<style><?php echo $site->getStyles(); ?></style>
	<?php } ?>

	<base target="_<?php echo REZGO_FRAME_TARGET; ?>">
</head>

<body>

<?php if ($site->exists($site->getAnalyticsGtm())) { ?>
	<!-- Google Tag Manager (noscript) -->
	<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo esc_html($site->getAnalyticsGtm()); ?>"
	height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
	<!-- End Google Tag Manager (noscript) -->
<?php } ?>