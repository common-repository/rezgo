<?php 
	require('rezgo/include/page_header.php');

	// new instance of RezgoSite
	$site = new RezgoSite();
	
	$company = $site->getCompanyDetails();
	
	$response = '';
	
	$pax_num = sanitize_text_field($_REQUEST['pax_num']);
	
	// get reviews
	if($_REQUEST['rezgoAction'] == 'item') {
		
		$pickup_split = explode("-", sanitize_text_field($_REQUEST['pickup_id']));
		$pickup = $pickup_split[0];
		$source_id = $pickup_split[1];
		
		$pickup_detail = $site->getPickupItem(sanitize_text_field($_REQUEST['option_id']), $pickup, sanitize_text_field($_REQUEST['book_time']));

		//if($pickup_detail->media && $site->exists($pickup_detail->lat)) { 
			$cl = $cr = 6;
		/*} elseif($pickup_detail->media && !$site->exists($pickup_detail->lat)) { 
			$cl = 11;
			$cr = 1;
		} elseif(!$pickup_detail->media && $site->exists($pickup_detail->lat)) { 
			$cl = 1;
			$cr = 11;
		}*/
		
		$p = 0;
		if ($pickup_detail->sources) {
			$pickup_sources = array();
			foreach ($pickup_detail->sources->source as $source) {
				$pickup_sources[$p] = (string) $source->name;
				$p++;
			}
		}
		
		$pickup_name = $pickup_detail->name;
		
		if ($source_id != '') {
			$pickup_name .= ' <span> - ' . $pickup_sources[(int) $source_id] . '</span>';
		}
		
		$response .= '
			<div class="col-12 rezgo-pickup-name">
				<h3>Pick up is at: <span class="text-info">'.$pickup_name.'</span></h3>
			</div>
			
			<div class="col-12">';
			
				if((int) $pickup_detail->cost > 0) {
					$pickup_cost = $pickup_detail->cost; // (int) 
					$response .= '<label>Cost: </label> '.$site->formatCurrency($pickup_cost, $company);
					
					if ((int) $pax_num > 1) {
						$response .= ' x ' . $pax_num;
						$pickup_cost = $pickup_cost * $pax_num;
					}
					
					$response .= '<br />';
				}
		
		$response .= '</div><div class="row">
			<div class="col-12 col-sm-'.$cl.' rezgo-pickup-left">
		';
		
		$response .= '<div class="rezgo-pickup-time">';
		
		if((int) $pickup_detail->pickup_time) {
			$response .= '<label>Pickup Time: </label> '.$pickup_detail->pickup_time.'';
		}
		
		$response .= '&nbsp;</div>';
		
		if($pickup_detail->media) { 
		
			$media_count = count($pickup_detail->media->image);
			if ($media_count > 0) {
				$m = 0;
				foreach ($pickup_detail->media->image as $pickup_image) {

					if($media_count > 1) {
						$indicators .= '<li data-bs-target="#rezgo-pickup-carousel" data-bs-slide-to="'.$m.'"'.($m==0 ? ' class="active"' : '').'></li>'."\n";
					}

					$media_caption = $pickup_image->caption ? '<div class="carousel-caption">'.$pickup_image->caption.'</div>' : '';
					$media_items .= '
						<div class="carousel-item'.($m==0 ? ' active' : '').'">
							<img src="'.$pickup_image->path.'" class="w-100" alt="'.$pickup_image->caption.'">'.
							$media_caption.'
						</div>
					';				
					$m++;
				}
			}
			
			$response .= '
				<div id="rezgo-pickup-carousel" class="carousel slide" data-bs-ride="carousel">
					<ol class="carousel-indicators">'.$indicators.'</ol>
					<div class="carousel-inner">'.$media_items.'</div>';
					if ($media_count > 1){
						$response .='
							<button class="left carousel-control-prev" data-bs-target="#rezgo-pickup-carousel" data-bs-slide="prev"><i class="fal fa-angle-left fa-3x"></i></button>
							<button class="right carousel-control-next" data-bs-target="#rezgo-pickup-carousel" data-bs-slide="next"><i class="fal fa-angle-right fa-3x"></i></button>
						';
					}
			$response .= '
				</div>
				';
		
		} else {
			
			$response .= '<div class="rezgo-pickup-location">';
			
			if($pickup_detail->location_address != '') { 
				$response .= '
					<label style="font-weight:normal;"><a target="_blank" href="https://www.google.com/maps/place/'.urlencode($pickup_detail->lat.','.$pickup_detail->lon).'"><i class="fas fa-map-marker-alt"></i> '.$pickup_detail->location_address.'</a></label>
				';
			}
			
			$response .= '&nbsp;</div>';
			
			if($site->exists($pickup_detail->lat  && GOOGLE_API_KEY != '')) { // && !REZGO_CUSTOM_DOMAIN
			
	      		if(!$site->exists($pickup_detail->zoom)) { $map_zoom = 8; } else { $map_zoom = $pickup_detail->zoom; }
				
	      		if($pickup_detail->map_type != '') { 
					$embed_type = strtolower($pickup_detail->map_type); 
					if ( $embed_type == 'hybrid' ) { $embed_type = 'satellite'; }
				} else { 
					$embed_type = 'roadmap'; 
				} 
			
				$response .= '
				<div style="position:relative;">
					<div class="rezgo-pickup-map" id="rezgo-pickup-map">
					<iframe width="100%" height="372" frameborder="0" style="border:0;margin-bottom:0;margin-top:-105px;" src="https://www.google.com/maps/embed/v1/place?key='.GOOGLE_API_KEY.'&maptype='.$embed_type.'&q='.$pickup_detail->lat.','.$pickup_detail->lon.'&center='.$pickup_detail->lat.','.$pickup_detail->lon.'&zoom='.$map_zoom.'"></iframe>
					</div>
						';
				
				$response .= '
					</div>';
			
			}

		}
		
		$response .= '</div>';
    
    	if($pickup_detail->media) {
    
			$response .= '<div class="col-12 col-sm-'.$cr.' rezgo-pickup-right">';
				
				$response .= '<div class="rezgo-pickup-location">';
				
				if($pickup_detail->location_address != '') { 
					$response .= '
						<label style="font-weight:normal;"><a target="_blank" href="https://www.google.com/maps/place/'.urlencode($pickup_detail->lat.','.$pickup_detail->lon).'"><i class="fas fa-map-marker-alt"></i> '.$pickup_detail->location_address.'</a></label>
					';
				}
				
				$response .= '&nbsp;</div>';
			
			if($site->exists($pickup_detail->lat && GOOGLE_API_KEY != '')) { 
			
					if(!$site->exists($pickup_detail->zoom)) { $map_zoom = 8; } else { $map_zoom = $pickup_detail->zoom; }
							
					if($pickup_detail->map_type != '') { 
								$embed_type = strtolower($pickup_detail->map_type); 
								if ( $embed_type == 'hybrid' ) { $embed_type = 'satellite'; }
							} else { 
								$embed_type = 'roadmap'; 
							} 
						
						$response .= '
							<div style="position:relative;">
								<div class="rezgo-pickup-map" id="rezgo-pickup-map">
								<iframe width="100%" height="372" frameborder="0" style="border:0;margin-bottom:0;margin-top:-105px;" src="https://www.google.com/maps/embed/v1/place?key='.GOOGLE_API_KEY.'&maptype='.$embed_type.'&q='.$pickup_detail->lat.','.$pickup_detail->lon.'&center='.$pickup_detail->lat.','.$pickup_detail->lon.'&zoom='.$map_zoom.'"></iframe>
								</div>
							';
							
					$response .= '
						</div>';
				}
				
			$response .= '
				</div></div>';		
		}

		if($site->exists($pickup_detail->pick_up) || $site->exists($pickup_detail->drop_off)) {
			$response .= '
				<div class="col-12 rezgo-pickup-extra">';
				
					if($site->exists($pickup_detail->pick_up)) {
						$response .= '<label>Pick Up</label> '.$pickup_detail->pick_up.'';
					}
						
					if($site->exists($pickup_detail->drop_off)) {
						$response .= '<label>Drop Off</label> '.$pickup_detail->drop_off.'';
					}
					
				$response .= '
					</div>';	
		}
		
	}
	if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
		// ajax response if we requested this page correctly
		echo wp_kses($response, ALLOWED_HTML);
	} else {
		// if, for some reason, the ajax form submit failed, then we want to handle the user anyway
		die ('Something went wrong getting pickup locations.');
	}
		
?>
