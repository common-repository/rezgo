<?php

	/*
		This is the Rezgo parser class, it handles processing for the Rezgo API.

		VERSION:
				3.1.0

		- Documentation and latest version
				https://www.rezgo.com/rezgo-open-source-booking-engine/

		- Finding your Rezgo CID and API KEY
				https://www.rezgo.com/support-article/create-api-keys

		AUTHOR:
				Kevin Campbell
				John McDonald
				Joshua Loo

		Copyright (c) 2012-2024, Rezgo (A Division of Sentias Software Corp.)
		All rights reserved.

		Redistribution and use in source form, with or without modification,
		is permitted provided that the following conditions are met:

		* Redistributions of source code must retain the above copyright
		notice, this list of conditions and the following disclaimer.
		* Neither the name of Rezgo, Sentias Software Corp, nor the names of
		its contributors may be used to endorse or promote products derived
		from this software without specific prior written permission.
		* Source code is provided for the exclusive use of Rezgo members who
		wish to connect to their Rezgo API. Modifications to source code
		may not be used to connect to competing software without specific
		prior written permission.

		THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
		"AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
		LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
		A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
		HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
		SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
		LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
		DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
		THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
		(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
		OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
	*/

	class RezgoSite {

		var $version = '3.1.0';

		var $requestID;
		var $instanceID;

		var $xml_path;
		var $contents;
		var $get;
		var $xml;
		var $secure = 'http://';
		var $obj;

		var $country_list;

		// indexes are used to split up response caches by different criteria
		var $tours_index = 0; // split up by search string
		var $company_index = 0; // split up by CID (for vendors only, suppliers use 0)
		var $currency_values;
		var $tour_limit;
		var $refid;
		var $promo_code;
		var $booking_source;
		var $pageTitle;
		var $metaTags;

		// calendar specific values
		var $calendar_name;
		var $calendar_com;
		var $calendar_active;

		var $calendar_prev;
		var $calendar_next;

		var $calendar_months = array();
		var $calendar_years = array();

		// api result caches improve performance by not hitting the gateway multiple times
		// searches that have differing args sort them into arrays with the index variables above
		var $company_response;
		var $page_response = array();
		var $tags_response;
		var $search_response;
		var $tour_availability_response;
		var $search_bookings_response;
		var $search_total;
		var $cart_total;
		var $cart_items;
		var $commit_response;
		var $booking_edit_response;
		var $booking_edit_status;
		var $contact_response;
		var $ticket_response;
		var $waiver_response;
		var $signing_response;
		var $review_response;
		var $pickup_response;
		var $payment_response;
		var $public_response;
		var $itinerary_response;

		var $lead_passenger_email;
		var $lead_passenger_first_name;
		var $lead_passenger_last_name;

		var $cart_api_response;
		var $cart_api_request;
		var $cart_data;
		var $cart_status;
		var $empty_cart;

		// primary_forms
		var $form_display = array();
		// group_forms
		var $gf_form_display = array();

		var $tour_forms;
		var $all_required;
		var $cart = array();
		var $cart_token;
		var $cart_trigger_code;

		var $cart_ids;
		var $gift_card;

		// debug and error stacks
		var $error_stack;
		var $debug_stack;

		// ------------------------------------------------------------------------------
		// if the class was called with an argument then we use that as the object name
		// this allows us to load the object globalls for included templates.
		// ------------------------------------------------------------------------------

		function __construct($secure=null, $newID=null) {
			if (!$this->config('REZGO_SKIP_BUFFER')) ob_start();

			// check the config file to make sure it's loaded
			if (!$this->config('REZGO_CID')) $this->error('REZGO_CID definition missing, check config file', 1);

			if($newID) { $this->requestID = $this->setRequestID(); }
			else { $this->requestID = isset($_COOKIE['rezgo_request_id']) ? $_COOKIE['rezgo_request_id'] : $this->setRequestID(); }

			// get request ID if it exists, otherwise generate a fresh one
			$this->requestID = isset($_COOKIE['rezgo_request_id']) ? $_COOKIE['rezgo_request_id'] : $this->setRequestID();

			$this->origin = $this->config('REZGO_ORIGIN');

			// assemble API address
			$this->xml_path = REZGO_XML.'/xml?transcode='.REZGO_CID.'&key='.REZGO_API_KEY.'&req='.$this->requestID.'&g='.$this->origin;
			$this->api_post_string = 'xml='.urlencode('<request><transcode>'.REZGO_CID.'</transcode><key>'.REZGO_API_KEY.'</key>');
			$this->advanced_xml_path = REZGO_XML.'/xml?req='.$this->requestID.'&g='.$this->origin.'&'.$this->api_post_string;

			if (isset($this->path)){
				if(!defined(REZGO_PATH)) define("REZGO_PATH", $this->path);
			}

			// it's possible to define the document root manually if there is an issue with the _SERVER variable
			if (!defined("REZGO_DOCUMENT_ROOT")) define("REZGO_DOCUMENT_ROOT", $_SERVER["DOCUMENT_ROOT"]);

			if (REZGO_WORDPRESS) {
				// assemble template and url path
				if (REZGO_CUSTOM_TEMPLATE_USE) {
					$this->path = str_replace(REZGO_DOCUMENT_ROOT, '', sanitize_text_field(WP_CONTENT_DIR)) .'/rezgo/templates/'.sanitize_text_field(REZGO_TEMPLATE).'/';
				} elseif (REZGO_LEGACY_TEMPLATE_USE) {
					$this->path = sanitize_text_field(REZGO_DIR).'/templates/legacy';
				} else {
					$this->path = sanitize_text_field(REZGO_DIR).'/templates/'.sanitize_text_field(REZGO_TEMPLATE);
				}

				$this->ajax_url = sanitize_text_field(REZGO_URL_BASE);
				$this->base = sanitize_text_field(REZGO_URL_BASE);
			} else {
				// assemble template and url path
				$this->path = REZGO_DIR.'/templates/'.REZGO_TEMPLATE;
				$this->base = REZGO_URL_BASE;
			}

			// set the secure mode for this particular page
			$this->setSecure($secure);

			// perform some variable filtering
			if (isset($_REQUEST['start_date'])) {
				if (strtotime($_REQUEST['start_date']) == 0) unset($_REQUEST['start_date']);
			}

			if (isset($_REQUEST['end_date'])) {
				if (strtotime($_REQUEST['end_date']) == 0) unset($_REQUEST['end_date']);
			}

			// handle the refID if one is set
			if (isset($_REQUEST['refid']) || isset($_REQUEST['ttl']) || isset($_COOKIE['rezgo_refid_val']) || isset($_SESSION['rezgo_refid_val'])) {

				if (isset($_REQUEST['refid']) || isset($_REQUEST['ttl'])) {
					$this->searchCart();
					if ($this->cart) $this->updateRefId($_REQUEST['refid']);

					$new_header = esc_url_raw($_SERVER['REQUEST_URI']);
					
					// remove the refid information wherever it is
					$new_header = preg_replace("/&?refid=[^&\/]*/", "", $new_header);
					$new_header = str_replace("?&", "?", $new_header);
					$new_header = preg_replace("/&?ttl=[^&\/]*/", "", $new_header);
					$new_header = str_replace("?&", "?", $new_header);
					$new_header = str_replace("/?refid", "/", $new_header);

					if(substr($new_header, -1) == '?') { $new_header = substr($new_header, 0, -1); }

					$refid = $this->requestStr('refid');

					$ttl = ($this->requestStr('ttl')) ? $this->requestStr('ttl') : 7200;

				} elseif(isset($_COOKIE['rezgo_refid_val'])) {

					$refid = sanitize_text_field($_COOKIE['rezgo_refid_val']);
					$ttl = sanitize_text_field($_COOKIE['rezgo_refid_ttl']);

				}

				$this->setCookie("rezgo_refid_val", $refid);
				$this->setCookie("rezgo_refid_ttl", $ttl);

				if (REZGO_WORDPRESS) {
					// we need to set the session here before we header the user off or the old session will override the new refid each time
					if ($ttl > 0 && !is_multisite()) {
						$_SESSION['rezgo_refid_val'] = $refid;
						$_SESSION['rezgo_refid_ttl'] = $ttl;
					} else {
						unset($_SESSION['rezgo_refid_val']);
						unset($_SESSION['rezgo_refid_ttl']);
					}
				}

				if (!REZGO_LITE_CONTAINER) {
					if(isset($new_header)) $this->sendTo((($this->checkSecure()) ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].$new_header);
				}
			}

			// handle the promo code if one is set
			if(isset($_REQUEST['promo'])) {
				
				$ttl = 1209600; // two weeks is the default time-to-live for the promo cookie
				
				if(isset($_REQUEST['promo']) && !$_REQUEST['promo']) {
					$_REQUEST['promo'] = ' '; // force a request below
					$ttl = -1; // set the ttl to -1, removing the promo code
				}
				
				if($_REQUEST['promo']) {

					if($_REQUEST['promo'] == ' ') unset($_REQUEST['promo']);

					$this->searchCart();
					if ($this->cart) $this->updatePromo($_REQUEST['promo'] ?? '');
				
					$new_header = esc_url_raw($_SERVER['REQUEST_URI']);

					// remove the promo information wherever it is
					$new_header = preg_replace("/&?promo=[^&\/]*/", "", $new_header);
					$new_header = str_replace("?&", "?", $new_header);
					$new_header = str_replace("/?promo", "/", $new_header);

					if(substr($new_header, -1) == '?') { $new_header = substr($new_header, 0, -1); }

					$promo = $this->requestStr('promo');
					$this->setCookie("rezgo_promo", $promo);

				} 
				
				if (REZGO_WORDPRESS) {
					// we need to set the session here before we header the user off or the old session will override the new promo code each time
					if(!is_multisite()) {
						$_SESSION['promo'] = $promo;
					} else {
						unset($_SESSION['promo']);
					}
				}
			
				if (!REZGO_LITE_CONTAINER) {
					if(isset($new_header)) $this->sendTo((($this->checkSecure()) ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].$new_header);	
				}
			}

			// handle booking source if one is set
			if(isset($_REQUEST['lead'])) {

				$ttl = 1209600; // two weeks is the default time-to-live for the promo cookie

				if(isset($_REQUEST['lead']) && !$_REQUEST['lead']) {
					$_REQUEST['lead'] = ' '; // force a request below
					$ttl = -1; // set the ttl to -1, removing the promo code
				}

				if($_REQUEST['lead']) {

					if($_REQUEST['lead'] == ' ') unset($_REQUEST['lead']);

					$this->searchCart();
					if ($this->cart) $this->updateBookingSource($_REQUEST['lead']);
				
					$new_header = esc_url_raw($_SERVER['REQUEST_URI']);

					// remove the promo information wherever it is
					$new_header = preg_replace("/&?lead=[^&\/]*/", "", $new_header);
					$new_header = str_replace("?&", "?", $new_header);
					$new_header = str_replace("/?lead", "/", $new_header);

					if(substr($new_header, -1) == '?') { $new_header = substr($new_header, 0, -1); }

					$lead = $this->requestStr('lead');

					$this->setCookie("rezgo_lead", $lead);
				} 
		
				if (!REZGO_LITE_CONTAINER) {
					if(isset($new_header)) $this->sendTo((($this->checkSecure()) ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].$new_header);	
				}
			}

			// registering global events, these can be manually changed later with the same methods
			$this->setRefId();
			// set promo code to what is returned by the cart API
			$this->setPromoCode();
			// set booking source to what is returned by the cart API
			$this->setBookingSource();
		}

		function config($arg) {
			if(!defined($arg)) {
				return 0;
			} else {
				if(constant($arg) == '') { return 0; }
				else { return constant($arg); }
			}
		}

		function error($message, $exit=null) {
			throw new ErrorException($message);
		}

		function debug($message, $i=null) {
			$stack = debug_backtrace();
			$stack = $stack[count($stack)-1]; // get the origin point

			$message = urldecode($message);

			if($this->config('REZGO_CONSOLE_API')) {
				if(($i == 'commit' || $i == 'commitOrder' || $i == 'add_transaction' || $i == 'tg_postbooking') && $this->config('REZGO_SWITCH_COMMIT')) {
					if($this->config('REZGO_STOP_COMMIT')) {
						echo $_SESSION['error_catch'] = 'STOP::'.esc_html($message).'<br><br>';
					}
				} else {
                    //$_SESSION['debug'][] = addslashes($message).' FILE '.$stack['file'].' LINE '.$stack['line'].($stack['function'] ? ' CALLER '.$stack['function'] : ''); 
				}
			}

			if($this->config('REZGO_DISPLAY_XML'))	{
				if(($i == 'commit' || $i == 'commitOrder' || $i == 'add_transaction') && $this->config('REZGO_SWITCH_COMMIT')) {
					die('STOP::'.$message);
				} else {
					echo '<textarea rows="2" cols="25">'.esc_html($message).'</textarea>';
				}
			}
		}

		function log($data) {
			$this->XMLRequest('log', $data);
			return true;
		}

		function setRequestID() {
			$this->requestID = $this->config('REZGO_CID').'-'.time().'-'.$this->randstring(4);
			$this->setCookie('rezgo_request_id', $this->requestID);
			return $this->requestID;
		}

		function getRequestID() {
			return $this->requestID;
		}

		// generate a random string
		function randstring($len = 10) {
			$len = $len / 2;

			$timestring = microtime();
			$secondsSinceEpoch=(integer) substr($timestring, strrpos($timestring, " "), 100);
			$microseconds=(double) $timestring;
			$seed = mt_rand(0,1000000000) + 10000000 * $microseconds + $secondsSinceEpoch;
			mt_srand($seed);
			$randstring = "";
			for($i=0; $i < $len; $i++) {
				$randstring .= mt_rand(0, 9);
				$randstring .= chr(ord('A') + mt_rand(0, 24));
			}
			return($randstring);
		}

		function secureURL() {
			if($this->config('REZGO_FORWARD_SECURE')) {
				// forward is set, so we want to direct them to their .rezgo.com domain
				$secure_url = $this->getDomain().'.rezgo.com';
			} else {
				// forward them to this page or our external URL
				if($this->config('REZGO_SECURE_URL')) {
					$secure_url = $this->config('REZGO_SECURE_URL');
				} else {
					$secure_url = sanitize_text_field($_SERVER["HTTP_HOST"]);
				}
			}
			return $secure_url;
		}

		function isVendor() {
			$res = (strpos(REZGO_CID, 'p') !== false) ? 1 : 0;
			return $res;
		}

		// clean slashes from the _REQUEST superglobal if escape strings is set in php
		function cleanRequest() {
			$strip = function (&$val) {
				$val = stripslashes($val);
			};
			array_walk_recursive($_REQUEST, $strip);
		}
		// output a fixed number from a request variable
		function requestNum($request) {
			$r = sanitize_text_field($_REQUEST[$request] ?? '');
			$r = preg_replace("/[^0-9.]*/", "", $r);
			return $r;
		}

		function requestStr($request) {
			$r = sanitize_text_field($_REQUEST[$request] ?? '');
			$r = strip_tags($r);
			$r = preg_replace("/[;<>]*/", "", $r);

			return $r;
		}

		// remove all attributes from a user-entered field
		function cleanAttr($request) {
			$r = preg_replace("/<([a-z][a-z0-9]*)[^>]*?(\/?)>/i",'<$1$2>', $request ?? '');
			$r = strip_tags($r, '<br><strong><p><ul><ol><li><h2><h3><h4>');
			return $r;
		}

		// ------------------------------------------------------------------------------
		// read a tour item object into the cache so we can format it later
		// ------------------------------------------------------------------------------
		function readItem(&$obj) {
			$this->obj = $obj;
		}

		function getItem() {
			$obj = $this->obj;
			// if(!$obj) $this->error('no object found, expecting read object or object argument');

			// return empty object if there is nothing
			if(!$obj) $obj = (object)[];
			return $obj;
		}

		// ------------------------------------------------------------------------------
		// format a currency response to the standards of this company
		// ------------------------------------------------------------------------------
		function formatCurrency($num, &$obj=null) {
			$pre = '';
			if(!$obj) $obj = $this->getItem();
			// displays negative symbol in front of prices
			if (strpos((string)$num, "-") !== false) {
				$num = str_replace("-", "", $num);
				$pre = '- ';
			}
			return str_replace(" ", "&nbsp;", $pre.$obj->currency_symbol.number_format((float)$num, (int)$obj->currency_decimals, '.', (string)$obj->currency_separator));
		}

		// ------------------------------------------------------------------------------
		// Check if an object has any content in it, for template if statements
		// ------------------------------------------------------------------------------
		function exists($string) {
			$str = (string) $string;
			return (strlen(trim($str)) == 0) ? 0 : 1;
		}

		// ------------------------------------------------------------------------------
		// Direct a user to a different page
		// ------------------------------------------------------------------------------
		function sendTo($path) {
			$path = strip_tags(htmlspecialchars($path, ENT_QUOTES));
			$path = str_replace('&amp;', '&', $path);

			$this->debug('PAGE FORWARDING ( '.$path.' )');
			echo '<script>'.REZGO_FRAME_TARGET.'.location.href = "'.esc_url_raw($path).'";</script>';
			exit;
		}

		// ------------------------------------------------------------------------------
		// Format a string for passing in a URL
		// ------------------------------------------------------------------------------
		function seoEncode($string) {
			$str = trim($string);
			$str = str_replace(" ", "-", $str);
			$str = preg_replace('/[^A-Za-z0-9\-]/','', $str);
			$str = preg_replace('/[\-]+/','-', $str);
			if(!$str) $str = urlencode($string);
			return strtolower($str);
		}

		// ------------------------------------------------------------------------------
		// Save tour search info
		// ------------------------------------------------------------------------------
		function saveSearch() {
			$search_array = array('pg', 'start_date', 'end_date', 'tags', 'search_in', 'search_for');

			foreach($search_array as $v) { if(isset($_REQUEST[$v])) $search[] = $v.'='.rawurlencode($this->requestStr($v)); }

			if($search) $search = '?'.implode("&", $search);
			if (REZGO_WORDPRESS) { 
				if (is_multisite() && !SUBDOMAIN_INSTALL && ( DOMAIN_CURRENT_SITE != REZGO_WP_DIR )) {
					setcookie("rezgo_search", $search, strtotime('now +1 week'), str_replace( DOMAIN_CURRENT_SITE, '', REZGO_WP_DIR ), $_SERVER['HTTP_HOST']);
				} else {
					setcookie("rezgo_search", $search, strtotime('now +1 week'), '/', $_SERVER['HTTP_HOST']);
				}
			} else {
            	$this->setCookie("rezgo_search", $search);
			}
		}

		// ------------------------------------------------------------------------------
		// Toggles secure (https) or insecure (http) mode for API queries. Secure mode
		// is required when making all commit or modification requests.
		// ------------------------------------------------------------------------------
		function checkSecure() {
			if((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443 || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') ) {
				return true;
			} else {
				return false;
			}
		}

		function setSecureXML($set) {
			if($set) { $this->secure = 'https://'; }
			else { $this->secure = 'http://'; }
		}

		function setSecure($set) {
			$this->setSecureXML($set);

			if($set) {

				if (REZGO_WORDPRESS) {
					$request = '';
					$rezgoAction = $_POST['rezgoAction'] ?? '';
					$custom_domain = $_REQUEST['custom_domain'] ?? '';

					if($this->config('REZGO_FORWARD_SECURE')) {
						// since we are directing to a white label address, clean the request up
						if ($_REQUEST['mode'] == 'page_book') {

							$set_refid = isset($_COOKIE['rezgo_refid_val']) ? '&refid='.sanitize_text_field($_COOKIE['rezgo_refid_val']) : '';
							$cart_token = sanitize_text_field($_COOKIE['rezgo_cart_token_'.REZGO_CID]);

							// we are using '?custom_domain' here because it works with the current build for WL
							$request .= '/book/'.$cart_token.'/?custom_domain=1'.$set_refid;

						} elseif ($_REQUEST['mode'] == 'gift_card') {

							// check if link has 'buy as a gift' attributes
							if (isset($_REQUEST['option']) && isset($_REQUEST['date'])) {
								$option = sanitize_text_field($_REQUEST['option']);
								$date = sanitize_text_field($_REQUEST['date']);

								$pax_array = [
									'adult', 'child', 'senior', 'price4', 'price5', 'price6', 'price7', 'price8', 'price9', 
								];

								// build pax request string
								$pax = '';
								$pax_count = 0;
								foreach ($_REQUEST as $k => $v) {
									if (isset($k) && in_array($k, $pax_array)) {
										if ((int)$v > 0) {
											$pax .= ($pax_count > 0 ? '&' : '?') .$k.'='.$v;
											$pax_count++;
										}
									}
								}

								$request = '/gift-card/'.$option.'/'.$date.$pax;
								
							} else {
								$request = '/gift-card';
							}

						}

						if (!$rezgoAction == 'add_item'){
							$custom_val = $custom_domain == '1' ? '&custom_domain=1' : '';
							$this->sendTo($this->secure.$this->secureURL().$request.$custom_val);
						}
					}

				} else {
					// check if refid is set before https redirect
					$set_refid = $_COOKIE['rezgo_refid_val'] ? '&refid='.$_COOKIE['rezgo_refid_val'] : '';
					if(!$this->checkSecure()) {
						if($this->config('REZGO_FORWARD_SECURE')) {
							// since we are directing to a white label address, clean the request up
							$request = '/book?'.$_SERVER['QUERY_STRING'];
						} else {
							$request = $_SERVER['REQUEST_URI'].$set_refid;
						}
						
						// handle the custom redirect request if one is set
						if($_REQUEST['custom_domain'] == '1') {

							$cart_token = $_COOKIE['rezgo_cart_token_'.REZGO_CID];

							$request = preg_replace("/&?custom=[^&\/]*/", "", $request);
							$request = str_replace("?&", "/?/", $request);
							$request = preg_replace("/?custom\/[^\/]*/", "", $request);
							$request = str_replace("//", "/", $request);
							if(substr($request, -1) == '?') { $request = substr($request, 0, -1); }

							$request .= '/book/'.$cart_token.$set_refid;
						}
					
						// allow custom domain to add items
						if (!$_POST['rezgoAction'] == 'add_item'){
							$custom_val = $_REQUEST['custom_domain'] == '1' ? '&custom_domain=1' : '';
							$this->sendTo($this->secure.$this->secureURL().$request.$custom_val);
						}
					} 
				}

			} else {
				// switch to non-https on the current domain
				if($this->checkSecure() && REZGO_ALL_SECURE !== 1) {
					if (strpos( $_SERVER['REQUEST_URI'], 'admin-ajax.php')===false) {
						$this->sendTo($this->secure.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
					}
				}
			}
		}

		// ------------------------------------------------------------------------------
		// fetch a requested template from the templates directory and load it into a
		// variable for display.	If fullpath is set, fetch it from that location instead.
		// ------------------------------------------------------------------------------
		function getTemplate($req, $fullpath=false) {
			//reset($GLOBALS);
			foreach($GLOBALS as $key => $val) {
				if(($key != strstr($key,"HTTP_")) && ($key != strstr($key, "_")) && ($key != 'GLOBALS')) {
					global ${$key};
				}
			}

			if (REZGO_WORDPRESS) {
				// wordpress document root includes the install path so we change the path for wordpress installs
				if (REZGO_CUSTOM_TEMPLATE_USE) {
					$path = WP_CONTENT_DIR.'/rezgo/templates/'.sanitize_text_field(REZGO_TEMPLATE).'/';
				} elseif (REZGO_LEGACY_TEMPLATE_USE) {
					$abspath =  (strpos(ABSPATH, 'wordpress/core')) ?  sanitize_text_field(REZGO_DIR) : sanitize_text_field(REZGO_DOCUMENT_ROOT).sanitize_text_field(REZGO_DIR);
					$path = ($this->config('REZGO_USE_ABSOLUTE_PATH')) ? sanitize_text_field(REZGO_DOCUMENT_ROOT) : $abspath;
					$path .= '/templates/legacy/';
				} else {
					$abspath =  (strpos(ABSPATH, 'wordpress/core')) ?  sanitize_text_field(REZGO_DIR) : sanitize_text_field(REZGO_DOCUMENT_ROOT).sanitize_text_field(REZGO_DIR);
					$path = ($this->config('REZGO_USE_ABSOLUTE_PATH')) ? sanitize_text_field(REZGO_DOCUMENT_ROOT) : $abspath;
					$path .= '/templates/'.sanitize_text_field(REZGO_TEMPLATE).'/';
				}
			} else {
				$path = ($this->config('REZGO_USE_ABSOLUTE_PATH')) ? REZGO_DOCUMENT_ROOT : REZGO_DOCUMENT_ROOT.REZGO_DIR;
				$path .= '/templates/'.REZGO_TEMPLATE.'/';
			}

			$ext = explode(".", $req);
			$ext = (!isset($ext[1])) ? '.php' : '';

			$filename = ($fullpath) ? $req : $path.$req.$ext;

			if (is_file($filename)) {
				ob_start();
				include $filename;
				$contents = ob_get_contents();
				ob_end_clean();
			} else {
				$this->error('"'.$req.'" file not found'.(($fullpath) ? '' : ' in "'.$path.'"'));
			}

			return $contents;
		}

		// ------------------------------------------------------------------------------
		// general request functions for country lists
		// ------------------------------------------------------------------------------
		function countryName($iso) {
			$abspath =  (strpos(ABSPATH, 'wordpress/core')) ?  sanitize_text_field(REZGO_DIR) : sanitize_text_field(REZGO_DOCUMENT_ROOT).sanitize_text_field(REZGO_DIR);
			$path = ($this->config('REZGO_USE_ABSOLUTE_PATH')) ? sanitize_text_field(REZGO_DOCUMENT_ROOT) : $abspath;

			if(!$this->country_list) {
				if($this->config('REZGO_COUNTRY_PATH')) {
					include(sanitize_text_field(REZGO_COUNTRY_PATH));
				} else {
					include($path.'/include/countries_list.php');
				}
				$this->country_list = $countries_list;
			}
			$iso = sanitize_text_field((string)$iso);
			return ($this->country_list[$iso]) ? ucwords($this->country_list[$iso]) : $iso;
		}

		function getRegionList($node=null) {
			$abspath =  (strpos(ABSPATH, 'wordpress/core')) ?  sanitize_text_field(REZGO_DIR) : sanitize_text_field(REZGO_DOCUMENT_ROOT).sanitize_text_field(REZGO_DIR);
			$path = ($this->config('REZGO_USE_ABSOLUTE_PATH')) ? sanitize_text_field(REZGO_DOCUMENT_ROOT) : $abspath;

			if($this->config('REZGO_COUNTRY_PATH')) {
				include(sanitize_text_field(REZGO_COUNTRY_PATH));
			} else {
				include($path.'/include/countries_list.php');
			}

			if($node) {
				$n = $node.'_state_list';
				if($$n) {
					return $$n;
				} else {
					$this->error('"'.$node.'" region node not found');
				}
			} else {
				return $countries_list;
			}
		}

		// ------------------------------------------------------------------------------
		// encode scripts for trans numbers
		// ------------------------------------------------------------------------------
		function encode($enc_text, $iv_len = 16) {
			$var = base64_encode($enc_text);
			return str_replace("=", "", base64_encode($var.'|'.$var));
		}
		function decode($enc_text, $iv_len = 16) {
			$var = base64_decode($enc_text.'=');
			$var = explode("|", $var);
			return base64_decode($var[0]);
		}

		// ------------------------------------------------------------------------------
		// encode trans numbers for waivers
		// ------------------------------------------------------------------------------
		function waiver_encode( $string, $secret='rz|secret' ) {
			$key = hash('sha256', 'rz|key');
			$iv = substr( hash( 'sha256', $secret ), 0, 16 );
			return base64_encode( openssl_encrypt( $string, 'AES-256-CBC', $key, 0, $iv));
		}
		function waiver_decode( $string, $secret='rz|secret' ) {
			$key = hash( 'sha256', 'rz|key' );
			$iv = substr( hash( 'sha256', $secret ), 0, 16 );
			return openssl_decrypt( base64_decode( $string ), 'AES-256-CBC', $key, 0, $iv );
		}

		// ------------------------------------------------------------------------------
		// Make an API request to Rezgo. $i supports all arguments that the API supports
		// for pre-generated queries, or a full query can be passed directly
		// ------------------------------------------------------------------------------
		function getFile($url, $post='') {
			include('fetch.rezgo.php');
			return $result;
		}

		function fetchXML($i, $post='') {

			$file = $this->getFile($i, $post);

			// ensure that $file response is a string
			if (gettype($file) == 'string') {
				// attempt to filter out any junk data
				$this->get = strstr((string)$file, '<response');
			} 
			// added extra params for parsing large blocks of xml
			libxml_use_internal_errors(true);
			$res = $this->xml = simplexml_load_string($this->get, 'SimpleXMLElement', LIBXML_COMPACT | LIBXML_PARSEHUGE);

			$errors = ($res === false) ? libxml_get_errors() : '';

			if((!$file || $errors) && strpos($i, 'i=company') === false) {

				$error_set = 'Errors: '.var_export($errors, true);

				// there has been a fatal error with the API, report the error to the gateway
				$this->getFile($i.'&action=report', $post);

				$this->error('FATAL ERROR WITH PARSER :: '.$error_set.' :: _GET ('.$i.') _POST ('.html_entity_decode($post).') DATA ('.$file.')');

				// send the user to the fatal error page
				if(REZGO_FATAL_ERROR_PAGE) {
					$this->sendTo(REZGO_FATAL_ERROR_PAGE);
				}
			}

			return $res;
		}

		function fetchJSON($i, $post='') {
			$file = $this->getFile($i, $post);
			return $file;
		}

		function XMLRequest($i, $arguments=null, $advanced=null) {

			if($i == 'company') {
				if(!isset($this->company_response[$this->company_index])) {
					$arg = ($this->company_index) ? '&q='.$this->company_index : '';
					$query = $this->secure.$this->xml_path.'&i=company'.$arg;

					if (isset($this->company_response[$this->company_index])) return;

					if($arguments == 'voucher') {
						$query .= '&a=voucher';
					} elseif($arguments == 'ticket') {
						$query .= '&a=ticket';
					} elseif($this->config('REZGO_MOBILE_XML')) {
						$query .= '&a=mobile';
					}

					$xml = $this->fetchXML($query);

					if($xml) {
						$this->company_response[$this->company_index] = $xml;
					}
				}
			}
			// !i=page
			if($i == 'page') {
				if(!isset($this->page_response[$arguments])) {
					$query = $this->secure.$this->xml_path.'&i=page&q='.$arguments;

					$xml = $this->fetchXML($query);

					if($xml) {
						$this->page_response[$arguments] = $xml;
					}
				}
			}
			// !i=tags
			if($i == 'tags') {
				if(!$this->tags_response) {
					$query = $this->secure.$this->xml_path.'&i=tags';

					$xml = $this->fetchXML($query);

					if($xml) {
						if($xml->total > 1) {
							foreach($xml->tag as $v) {
								$this->tags_response[] = $v;
							}
						} else {
							$this->tags_response[] = $xml->tag;
						}
					}

				}
			}
			// !i=search
			if($i == 'search') {
				if(!isset($this->search_response[$this->tours_index])) {
					$query = $this->secure.$this->xml_path.'&i=search&'.$this->tours_index;

					if(is_array($this->cart)) $query .= '&cart='.$this->getCartIDs();

					$this->setCartToken();
					$query .= ($this->cart_token) ? '&cart_token='.$this->cart_token : '';

					$xml = $this->fetchXML($query);

					$this->search_total = $xml->total ?? 0;

					$c = 0;
					if($xml && $xml->total != 0) {
						if($xml->total > 1) {
							foreach($xml->item as $v) {
								$this->search_response[$this->tours_index][$c] = $v;
								$this->search_response[$this->tours_index][$c++]->index = $this->tours_index;
							}
						} else {
							$this->search_response[$this->tours_index][$c] = $xml->item;
							$this->search_response[$this->tours_index][$c++]->index = $this->tours_index;
						}	
					}

				}
			}
			// !i=cart
			if($i == 'cart') {
				if(!$this->cart_response) {

					$query = 'https://'.$this->advanced_xml_path.urlencode('<instruction>cart</instruction><cart>'.$this->getCartIDs().'</cart>'.$arguments.'</request>');

					$xml = $this->fetchXML($query);

					$this->cart_total = $xml->total;

					$c = 0;
					if($xml && $xml->total != 0) {
						if($xml->total > 1) {
							foreach($xml->item as $v) {
								$this->cart_response[$c++] = $v;
							}
						} else {
							$this->cart_response[$c++] = $xml->item;
						}
					}

				}
			}
			// !i=create_cart
			if($i == 'create_cart') {
				$query = 'https://'.$this->xml_path;
				$post = $this->api_post_string.urlencode($arguments);
				$xml = $this->fetchXML($query, $post);
				$this->cart_token = $xml->token;
			}
			// !i=search_cart
			if($i == 'search_cart') {

				$cart_gf_total = 0;
				$cart_pf_total = 0;

				// use the cached result if it is available
				if($this->cart_api_response) return;

				$query = 'https://'.$this->xml_path;
				$post = $this->api_post_string.urlencode($arguments);
				$xml = $this->fetchXML($query, $post);

				// clear empty/unusable cart token in widget URI
				if (REZGO_LITE_CONTAINER && $this->cartInUri()) {
					if (isset($xml->cart->item) && $xml->cart->item == '') {
						$uri = $_SERVER['HTTP_HOST'];
						$this->clean_uri = str_replace('-lite-'.$this->cart_token, '-lite', $uri);
						$this->empty_cart = 1;
					}
				}

				if (!$xml->token) {
					$this->setCookie('rezgo_cart_token_'.REZGO_CID, '');
				}

				// searching can't happen if cart has no items
				if ((int)$xml->items === 0) return;

				$this->cart_total = $xml->total;
				$this->cart_items = $xml->items;
				$this->cart_trigger_code = $xml->trigger_code;
				$this->refid = $xml->refid;
				$this->referrer = $xml->referrer;

				if ($xml->email) $this->lead_passenger_email = $xml->email;
				if ($xml->cart->payment->tour_first_name) $this->lead_passenger_first_name = $xml->cart->payment->tour_first_name;
				if ($xml->cart->payment->tour_last_name) $this->lead_passenger_last_name = $xml->cart->payment->tour_last_name;

				$b = 0;
				$c = 0;
				$d = 0;

				if($xml && (int)$xml->items !== 0) {
					if ($xml->cart){
						// get current <cart> block
						foreach($xml->cart->item as $v) {
							$this->cart_data[$b++] = $v;
						}
						// get current <cart> block
						foreach($xml->package as $v) {
							$this->package_data[$d++] = $v;
						}
					}
					if ($xml->item){
						foreach($xml->item as $v) {
							$this->cart_api_response[$c++] = $v;
						}
					}

				}

				if ($xml->cart_status) {
					$this->cart_status = $xml->cart_status;					
					$this->setCookie('cart_status', $xml->cart_status->asXML()); }

				// -----------primary forms ----------- //

				// get primary forms in <cart> block
				if ($xml->cart){
					$d = 0;
					foreach ($xml->cart->item as $item){
						$cart_pfs[$d] = (object) [];
						$cart_pfs[$d]->primary_forms = $item->primary_forms;
						$d++;
					}
				}

				$currency_symbol = $this->getCurrencySymbol();
				$e = 0;
				foreach ($xml->item as $item) {
					
					$item_pfs[$e] = (object) [];
					$item_pfs[$e]->primary_forms = $item->primary_forms;

					for ($f=0; $f < count((is_countable($item->primary_forms->form) ? $item->primary_forms->form : [])); $f++) {
						if ( (($item_pfs[$e]->primary_forms->form[$f]->type == 'checkbox') && ($item_pfs[$e]->primary_forms->form[$f]->price !=0)) ||
							(($item_pfs[$e]->primary_forms->form[$f]->type == 'checkbox_price') && ($item_pfs[$e]->primary_forms->form[$f]->price !=0)) ||
							($item_pfs[$e]->primary_forms->form[$f]->type == 'select_price')
						)
						{

							if ($cart_pfs[$e]->primary_forms->count()) {
								$cart_pfs[$e]->primary_forms->form[$f]->title = $item_pfs[$e]->primary_forms->form[$f]->title;

								// extract prices from select_price forms if applicable
								if ($item_pfs[$e]->primary_forms->form[$f]->type == 'select_price') {

									// save all values and options into a single array to separate and display later
									$s_options[$e] = explode(',' , $item_pfs[$e]->primary_forms->form[$f]->options);
									$s_prices[$e] = explode(',' , $item_pfs[$e]->primary_forms->form[$f]->options_price);

									$price_key = array_search($cart_pfs[$e]->primary_forms->form[$f]->value, $s_options[$e]);

									$value = $cart_pfs[$e]->primary_forms->form[$f]->value ?? 1;
									$cart_pfs[$e]->primary_forms->form[$f]->status = $value != '' ? 'on' : 'off';
									$cart_pfs[$e]->primary_forms->form[$f]->price = trim($s_prices[$e][$price_key]);
									$cart_pfs[$e]->primary_forms->form[$f]->select_price_option = $cart_pfs[$e]->primary_forms->form[$f]->value;

								} else {
									$cart_pfs[$e]->primary_forms->form[$f]->price = $item_pfs[$e]->primary_forms->form[$f]->price;
								}

								// -- total up cart primary form values
								// checkbox_prices
								if ($cart_pfs[$e]->primary_forms->form[$f]->value == 'on') $cart_pf_total += $cart_pfs[$e]->primary_forms->form[$f]->price;
								
								// select_prices
								if ($item_pfs[$e]->primary_forms->form[$f]->type == 'select_price') {
									$cart_pf_total += $cart_pfs[$e]->primary_forms->form[$f]->price;
								}
							}
						}

					}
					$e++;
				}

				if(isset($cart_pf_total)) $this->cart_total += $cart_pf_total;
				if(isset($cart_pfs)) $this->form_display = $cart_pfs;

				// -----------group forms ----------- //

				$types = array('adult', 'child', 'senior', 'price4', 'price5', 'price6', 'price7', 'price8', 'price9');

				if ($xml->cart){
					$f = 0;
					foreach ($xml->cart->item as $item){
						$cart_gfs[$f] = (object) [];
						$cart_gfs[$f]->group_forms =  $item->tour_group;
						$f++;
					}
				}

				$g = 0;
				foreach ($xml->item as $item){
					$item_gfs[$g] = (object) [];
					$item_gfs[$g]->group_forms = $item->group_forms;

					foreach ($types as $type) {

						if ($cart_gfs[$g]->group_forms) {
							foreach ($cart_gfs[$g]->group_forms->{$type} as $pax) {
								for ($j=0; $j < count((is_countable($pax->forms->form) ? $pax->forms->form : [])); $j++) {

									// extract prices from select_price forms if applicable
									if ($item_gfs[$g]->group_forms->form[$j]->type == 'select_price') {
										
										// save all values and options into a single array to separate and display later
										$s_options[$j] = explode(',' , $item_gfs[$g]->group_forms->form[$j]->options);
										$s_prices[$j] = explode(',' , $item_gfs[$g]->group_forms->form[$j]->options_price);
					
										$price_key = array_search($pax->forms->form[$j]->value, $s_options[$j]);

										$value = $pax->forms->form[$j]->value != '' ?? 1;
										$item_gfs[$g]->group_forms->form[$j]->status = $value ? 'on' : 'off';
										$item_gfs[$g]->group_forms->form[$j]->price = (string)$s_prices[$j][$price_key];

										$pax->forms->form[$j]->select_price_option = $pax->forms->form[$j]->value;
									}

									$pax->forms->form[$j]->title = $item_gfs[$g]->group_forms->form[$j]->title;
									$pax->forms->form[$j]->price = $item_gfs[$g]->group_forms->form[$j]->price;
									$pax->forms->form[$j]->status = $item_gfs[$g]->group_forms->form[$j]->status;

									$pax_array[$j] = $pax->forms->form[$j];
									$new_cart_gfs[$g][] = $pax_array[$j];

									// -- total up cart group form values
									// checkbox_prices
									if ($pax_array[$j]->value == 'on') $cart_gf_total += $pax->forms->form[$j]->price;

									// select_prices
									if ($item_gfs[$g]->group_forms->form[$j]->type == 'select_price') {
										$cart_gf_total += $pax->forms->form[$j]->price;
									}
								}
							}
						}
					}
					$g++;
				}
				if(isset($cart_gf_total)) $this->cart_total += $cart_gf_total;
				if(isset($new_cart_gfs)) $this->gf_form_display = $new_cart_gfs;
			}
			// !i=add_cart
			if($i == 'add_cart') {
				$query = 'https://'.$this->xml_path;
				$post = $this->api_post_string.urlencode($arguments);
				$xml = $this->fetchXML($query, $post);

				$this->cart_total = $xml->total;
				$this->cart_trigger_code = $xml->trigger_code;

				$c = 0;
				if($xml && $xml->total != 0) {
					if($xml->total > 1) {
						foreach($xml->item as $v) {
							$this->cart_api_response[$c++] = $v;
						}
					} else {
						$this->cart_api_response[$c++] = $xml->item;
					}
				}

				// don't return error on invalid promo code on add, this causes FE to stop redirecting
				if ($xml->cart_status->error_code != 9) $this->cart_status = $xml->cart_status;

				if (!$this->cart_token) {
					$this->cart_token = $xml->token;
					$this->setCookie('rezgo_cart_token_'.REZGO_CID, $this->cart_token);
				}

			}
			// !i=update_cart
			if($i == 'update_cart') {
				$query = 'https://'.$this->xml_path;
				$post = $this->api_post_string.urlencode($arguments);
				$xml = $this->fetchXML($query, $post);

				$this->cart_total = $xml->total;
				$this->cart_trigger_code = $xml->trigger_code;

				if ($xml->email) $this->lead_passenger_email = $xml->email;
				if ($xml->cart->payment->tour_first_name) $this->lead_passenger_first_name = $xml->cart->payment->tour_first_name;
				if ($xml->cart->payment->tour_last_name) $this->lead_passenger_last_name = $xml->cart->payment->tour_last_name;

				if ($xml->cart_status) {
					$this->cart_status = $xml->cart_status;
					$this->setCookie('cart_status', $xml->cart_status->asXML()); }
			}
			// !i=remove_cart
			if($i == 'remove_cart') {
				$query = 'https://'.$this->xml_path;
				$post = $this->api_post_string.urlencode($arguments);
				$xml = $this->fetchXML($query, $post);
			}
			// !i=initiate_checkout
			if($i == 'initiate_checkout') {		
				$query = 'https://'.$this->xml_path;
				$post = $this->api_post_string.urlencode($arguments);
				$xml = $this->fetchXML($query, $post);
			}
			// !i=destroy_cart
			if($i == 'destroy_cart') {
				$query = 'https://'.$this->xml_path;
				$post = $this->api_post_string.urlencode($arguments);
                $xml = $this->fetchXML($query, $post);
			}
			// !i=search_bookings
			if($i == 'search_bookings') {
				if(!isset($this->search_bookings_response[$this->bookings_index])) {
					$query = $this->secure.$this->xml_path.'&i=search_bookings&'.$this->bookings_index;
					$xml = $this->fetchXML($query);

					$c = 0;
					if($xml && $xml->total != 0) {
						if($xml->total > 1) {
							foreach($xml->booking as $v) {
								$this->search_bookings_response[$this->bookings_index][$c] = $v;
								$this->search_bookings_response[$this->bookings_index][$c++]->index = $this->bookings_index;
							}
						} else {
							$this->search_bookings_response[$this->bookings_index][$c] = $xml->booking;
							$this->search_bookings_response[$this->bookings_index][$c++]->index = $this->bookings_index;
						}
					}

				}
			}
			// !i=itinerary
			if($i == 'itinerary') {
				$query = 'https://'.REZGO_XML.'/v2/orders/'.$this->trans_num.'/?transcode='.REZGO_CID.'&key='.REZGO_API_KEY.'&a=itinerary';
				$this->itinerary_response = $this->fetchJSON($query);
			}
			// !i=log
			if($i == 'log') {
				$url = '';
				if(is_array($arguments)) {
					foreach($arguments as $k => $v) {
						if($v) $url .= '&log['.$k.']='.urlencode($v);
					}
					$query = $this->secure.$this->xml_path.'&i=log'.$url;
					$xml = $this->fetchXML($query);
				}
			}
			// !i=public
			if($i == 'public') {
				$query = $this->secure.$this->xml_path.'&i=public&amount='.$arguments;
				$xml = $this->fetchXML($query);
				$this->public_response = (array) $xml->data;
			}
			// !i=commit
			if($i == 'commit') {
				$query = 'https://'.$this->xml_path.'&i=commit&cart='.$this->getCartIDs().'&'.$arguments;

				$xml = $this->fetchXML($query);

				if($xml) {
					$this->commit_response = new stdClass();
					foreach($xml as $k => $v) {
						$this->commit_response->$k = trim((string)$v);
					}
				}
			}
			// !new commit mode
			if($i == 'commitOrder') {
				$query = 'https://'.$this->xml_path;

				$post = $this->api_post_string.urlencode('<instruction>commit</instruction><cart>'.$this->getCartIDs().'</cart>'.$arguments.'</request>');

				if (!REZGO_WORDPRESS) { 
					// event logging to api_log
					$logged = addslashes("SENDING COMMIT POST: ".$post);
					$GLOBALS['db']->query("insert into `xml_commit_log` (`date`, `time`, `ts`, `cid`, `query`, `return`, `response`) values ('".date("Y-m-d")."', '".date("H:i:s")."', '".strtotime('now')."', '".addslashes($c)."', '$logged', '', '99.002')");
				}

				$xml = $this->fetchXML($query, $post);

				if($xml) {
					$this->commit_response = new stdClass();
					foreach($xml as $k => $v) {
						$this->commit_response->$k = trim((string)$v);
					}
				}
			}
			
			if($i == 'tg_postbooking') {

				if (REZGO_WORDPRESS) $this->xml_path = REZGO_XML.'/xml?transcode='.REZGO_CID.'&key='.REZGO_API_KEY.'&req='.$this->requestID.'&g='.$this->origin;

				$query = 'https://'.$this->xml_path;
				
				$post = $this->api_post_string.urlencode('<instruction>insurance</instruction>'.$arguments.'</request>');

                $xml = $this->fetchXML($query, $post);

				if($xml) {
					$this->commit_response = new stdClass();
					foreach($xml as $k => $v) {
						$this->commit_response->$k = trim((string)$v);
					}
				}
			}
						
			if($i == 'edit_booking') {
				$query = 'https://'.$this->xml_path;

				$post = $this->api_post_string.urlencode('<instruction>edit_booking</instruction>'.$arguments.'</request>');

				$xml = $this->fetchXML($query, $post);

				if($xml) {
					$this->booking_edit_response = new stdClass();
					foreach($xml as $k => $v) {
						$this->booking_edit_response->$k = trim((string)$v);
					}

					// return status change
					if ($xml->status) {
						$this->booking_edit_status = $xml;
						$this->setCookie('booking_edit_status', json_encode($this->booking_edit_status));
					}
				}

			}
			
			if($i == 'cancel_booking') {
				$query = 'https://'.$this->xml_path;

				$post = $this->api_post_string.urlencode('<instruction>cancel_booking</instruction>'.$arguments.'</request>');
               
				$xml = $this->fetchXML($query, $post);
				
				if($xml) {
					$this->booking_edit_response = new stdClass();
					foreach($xml as $k => $v) {
						$this->booking_edit_response->$k = trim((string)$v);
					}
				}
			}

			// !i=booking_edit_contact
			if($i == 'booking_edit_contact') {
				$query = 'https://'.$this->xml_path.'&i=contact&'.$arguments;
			
				$xml = $this->fetchXML($query);

				if($xml) {
					$this->booking_edit_response = new stdClass();
					foreach($xml as $k => $v) {
						$this->booking_edit_response->$k = trim((string)$v);	
					}
				}
			}

			// !i=add gift card
			if($i == 'addGiftCard') {
				if (REZGO_WORDPRESS) $this->xml_path = REZGO_XML.'/xml?transcode='.REZGO_CID.'&key='.REZGO_API_KEY.'&req='.$this->requestID.'&g='.$this->origin;

				$query = 'https://'.$this->xml_path;

				$post = $this->api_post_string.urlencode('<instruction>add_card</instruction>'.$arguments.'</request>');

				$xml = $this->fetchXML($query, $post);

				if($xml) {
					$this->commit_response = new stdClass();

					foreach ($xml as $k => $v) {
						$this->commit_response->$k = trim((string)$v);
					}
				}
			}
			// !i=search gift card
			if($i == 'searchGiftCard') {
				$query = $this->secure.$this->xml_path.'&i=cards&q='.$arguments;
				$res = $this->fetchXML($query);
				$this->gift_card = $res;
			}
			// !i=contact
			if($i == 'contact') {
				$query = 'https://'.$this->xml_path.'&i=contact&'.$arguments;

				$xml = $this->fetchXML($query);

				if($xml) {
					$this->contact_response = new stdClass();
					foreach($xml as $k => $v) {
						$this->contact_response->$k = trim((string)$v);
					}
				}
			}
			// !i=tickets
			if($i == 'tickets') {
				if(!isset($this->ticket_response[$arguments])) {
					$query = $this->secure.$this->xml_path.'&i=tickets&q='.$arguments;

					$xml = $this->fetchXML($query);

					if($xml) {
						$this->ticket_response[$arguments] = $xml;
					}
				}
			}
			// !i=waiver
			if($i == 'waiver') {
				$target = '';
				
				if(!isset($this->waiver_response[$arguments])) {

					if ($advanced == 'com') $target = '&t=com';

					$query = $this->secure.$this->xml_path.'&i=waiver&q='.$arguments.$target;

					$xml = $this->fetchXML($query);

					if($xml) {
						$this->waiver_response[$arguments] = $xml;
					}
				}
			}
			// !i=sign
			if($i == 'sign') {
				if (REZGO_WORDPRESS) $this->xml_path = REZGO_XML.'/xml?transcode='.REZGO_CID.'&key='.REZGO_API_KEY.'&req='.$this->requestID.'&g='.$this->origin;

				$query = 'https://'.$this->xml_path;

				$post = $this->api_post_string.urlencode('<instruction>sign</instruction>'.$arguments.'</request>');

				$xml = $this->fetchXML($query, $post);

				if($xml) {
					$this->signing_response = new stdClass();

					foreach ($xml as $k => $v) {
						$this->signing_response->$k = trim((string)$v);
					}
				}
			}
			// !i=review
			if($i == 'review') {
				$query = 'https://'.$this->xml_path.'&i=review&'.$arguments;

				$xml = $this->fetchXML($query);

				if($xml) {
					$this->review_response = new stdClass();
					$this->review_response = $xml;
				}
			}
			// !i=add_review
			if($i == 'add_review') {
				//$query = 'https://'.$this->xml_path.'&i=add_review&'.$arguments;
				$query = 'https://'.$this->xml_path;

				$post = $this->api_post_string.urlencode('<instruction>add_review</instruction>'.$arguments.'</request>');

				$xml = $this->fetchXML($query, $post);

				if($xml) {
					$this->review_response = new stdClass();
					foreach($xml as $k => $v) {
						$this->review_response->$k = trim((string)$v);
					}
				}
			}
			// !i=pickup
			if($i == 'pickup') {
				$query = 'https://'.$this->xml_path.'&i=pickup&'.$arguments;

				$xml = $this->fetchXML($query);

				if($xml) {
					$this->pickup_response = new stdClass();
					$this->pickup_response = $xml;
				}
			}

			// !i=add_transaction
			if($i == 'add_transaction') {

				$query = 'https://'.$this->xml_path;

				$post = $this->api_post_string.urlencode('<instruction>add_transaction</instruction>'.$arguments.'</request>');

				$xml = $this->fetchXML($query, $post);

				if($xml) {
					$this->commit_response = new stdClass();

					foreach ($xml as $k => $v) {
						$this->commit_response->$k = trim((string)$v);
					}
				}
			}
			// !i=payment
			if($i == 'payment') {
				$query = 'https://'.$this->xml_path.'&i=payment&'.$arguments;

				$xml = $this->fetchXML($query);

				if($xml) {
					$this->payment_response = new stdClass();
					$this->payment_response = $xml;
				}
			}

			if(REZGO_TRACE_XML) {
				if(!$query && REZGO_INCLUDE_CACHE_XML) $query = 'called cached response';
				if($query) {
					$message = $i.' ( '.$query.(($post) ? '&'.$post : '').' )';
					$this->debug('API REQUEST: '.$message, $i); // pass the $i as well so we can freeze on commit
				}
			}
		}

		// ------------------------------------------------------------------------------
		// Set specific data
		// ------------------------------------------------------------------------------
		function setTourLimit($limit, $start=null) {
			$str = ($start) ? $start.','.$limit : $limit;
			$this->tour_limit = $str;
		}

		function setRefId($set=null) {
			$this->searchCart();
			if ($set) {
				$this->refid = $set;
			} else {
				$refid_val = isset($_COOKIE['rezgo_refid_val']) ? $_COOKIE['rezgo_refid_val'] : '';
				$this->refid = ($this->refid) ? urlencode($this->refid) : urlencode($refid_val);
			}
		}
		
		function setPromoCode($set=null) {
			$this->searchCart();
			if ($set) {
				$this->promo_code = $set;
			} else {
				$promo_code = isset($_COOKIE['rezgo_promo']) ? $_COOKIE['rezgo_promo'] : '';
				$this->promo_code = ($this->cart_trigger_code) ? urlencode($this->cart_trigger_code) : urlencode($promo_code);
			}
		}

		function setBookingSource($set=null) {
			$this->searchCart();
			if ($set) {
				$this->booking_source = $set;
			} else {
				$booking_source = isset($_COOKIE['rezgo_lead']) ? $_COOKIE['rezgo_lead'] : '';
				$this->booking_source = ($this->booking_source) ? urlencode($this->booking_source) : urlencode($booking_source);
			}
		}

		function setShoppingCart($array) {
			$this->cart = unserialize(stripslashes($array));
		}

		function setPageTitle($str) {
			$this->pageTitle = str_replace('_', ' ', $str);
		}

		function setMetaTags($str) {
			$this->metaTags = $str;
		}

		function setCookie($name, $data='', $secure=null) {

			$secure = $secure ?? $this->checkSecure();

			$old_chrome = $this->config('OLD_CHROME');

			$data = (is_array($data ?? '')) ? serialize($data) : $data;

			if (REZGO_WORDPRESS) {
				if (is_multisite() && !SUBDOMAIN_INSTALL && ( DOMAIN_CURRENT_SITE != REZGO_WP_DIR )) {
					$path = str_replace( DOMAIN_CURRENT_SITE, '', REZGO_WP_DIR );
				} else {
					$path = '/';
				}
			}

			$options = [
                'expires' => time() + (($data) ? REZGO_CART_TTL : -3600),
				'path' => $path ? $path : '/',
				'domain' => $_SERVER['SERVER_NAME'],
				'secure' => $secure,
				'samesite' => ($secure && !$old_chrome) ? 'None' : ''
			];

			setcookie($name, $data, $options);
		}

		// ------------------------------------------------------------------------------
		// Fetch specific data
		// ------------------------------------------------------------------------------
		function getSiteStatus() {
			$this->XMLRequest('company');
			return $this->company_response[0]->site_status;
		}

		function getHeader() {
			$this->XMLRequest('company');
			$header = $this->company_response[0]->header;
			// handle the tags in the template
			return $this->tag_parse($header);
		}

		function getFooter() {
			$this->XMLRequest('company');
			$footer = $this->company_response[0]->footer;
			// handle the tags in the template
			return $this->tag_parse($footer);
		}

		function getVoucherHeader() {
			$this->XMLRequest('company', 'voucher');
			$header = $this->company_response[0]->header;
			// handle the tags in the template
			return $this->tag_parse($header);
		}

		function getVoucherFooter() {
			$this->XMLRequest('company', 'voucher');
			return $this->company_response[0]->footer;
		}

		function getTicketHeader() {
			$this->XMLRequest('company', 'ticket');
			$header = $this->company_response[0]->header;
			return $this->tag_parse($header);
		}

		function getTicketFooter() {
			$this->XMLRequest('company', 'ticket');
			return $this->company_response[0]->footer;
		}

		function getTicketContent($trans_num) {
			$this->XMLRequest('tickets', $trans_num);
			return $this->ticket_response[$trans_num];
		}

		function getWaiverContent($args=null, $target=null) {
			$this->XMLRequest('waiver', $args, $target);
			return $this->waiver_response[$args]->waiver;
		}

		function getWaiverForms($args=null) {
			$this->XMLRequest('waiver', $args);
			return $this->waiver_response[$args]->forms->form;
		}

		function getStyles() {
			$this->XMLRequest('company');
			return $this->company_response[0]->styles;
		}

		function getPageName($page) {
			$this->XMLRequest('page', $page);
			if ($this->page_response[$page]->error) {
				return '404';
			} else {
				return $this->page_response[$page]->name;
			}
		}

		function getPageContent($page) {
			$this->XMLRequest('page', $page);
			return $this->page_response[$page]->content;
		}

		function getAnalytics() {
			$this->XMLRequest('company');
			return $this->company_response[0]->analytics_general;
		}
			
		function getAnalyticsGa4() {
			$this->XMLRequest('company');
			return (string) $this->company_response[0]->analytics_ga4;
		}
		
		function getAnalyticsGtm() {
			$this->XMLRequest('company');
			return (string) $this->company_response[0]->analytics_gtm;
		}

		function getMetaPixel() {
			$this->XMLRequest('company');
			return (string) $this->company_response[0]->meta_pixel;
		}

		function getAnalyticsConversion() {
			$this->XMLRequest('company');
			return $this->company_response[0]->analytics_convert;
		}

		function getTriggerState() {
			$this->XMLRequest('company');
			return $this->exists($this->company_response[0]->trigger);
		}

		function getBookNow() {
			$this->XMLRequest('company');
			return $this->company_response[0]->book_now;
		}

		function getCartState() {
			$this->XMLRequest('company');
			return ((int) $this->company_response[0]->cart == 1) ? 1 : 0;
		}

		function getTwitterName() {
			$this->XMLRequest('company');
			return $this->company_response[0]->social->twitter_name;
		}

		function getPaymentMethods($val=null, $a=null) {
			$this->company_index = ($a) ? (string) $a : 0; // handle multiple company requests for vendor
			$this->XMLRequest('company');

			if (isset($this->company_response[$this->company_index]->payment->method)) {
				if($this->company_response[$this->company_index]->payment->method[0]) {
					foreach($this->company_response[$this->company_index]->payment->method as $v) {
						$ret[] = array('name' => (string)$v, 'add' => (string)$v->attributes()->add);
						if($val && $val == $v) { return 1; }
					}
				} else {
					$ret[] = array(
						'name' => (string)$this->company_response[$this->company_index]->payment->method,
						'add' => (string)$this->company_response[$this->company_index]->payment->method->attributes()->add
					);
					if($val && $val == (string)$this->company_response[$this->company_index]->payment->method) { return 1; }
				}
			}

			// if we made it this far with a $val set, return false
			if($val) { return false; }
			else { return $ret ?? ''; }
		}

		function getPaymentCards($a=null) {
			$this->company_index = ($a) ? (string) $a : 0;
			$this->XMLRequest('company');
			$split = explode(",", $this->company_response[$this->company_index]->cards);
			foreach((array) $split as $v) {
				if(trim($v)) $ret[] = strtolower(trim($v));
			}
			return $ret;
		}

		function getPublicPayment($amount, $add=[], $gateway_override = false) {
			$string = $amount;
				$string .= '&cart=' . $this->cart_token;
				
			if($add) {
				foreach((array) $add as $k => $v) {
					$string .= '&add['.$k.']='.$v;
				}
			}
			if ($gateway_override) {
				$string .= '&gateway_override=' . $gateway_override;
			}
			$this->XMLRequest('public', $string);
			return $this->public_response;
		}

		function getCVV($a=null) {
			$this->company_index = ($a) ? (string) $a : 0;
			$this->XMLRequest('company');
			return (int) $this->company_response[$this->company_index]->get_cvv;
		}

		function getGateway($a=null) {
			$this->company_index = ($a) ? (string) $a : 0;
			$this->XMLRequest('company');
			return (int) $this->company_response[$this->company_index]->using_gateway ? 1 : 0;
		}

		function usingPaypalCheckout($a=null) {
			$paypal_checkout = 0;
			if ($this->getPaymentMethods()) {
				foreach ($this->getPaymentMethods() as $method) {
					if ((string) $method['name'] == 'PayPal Checkout') {
						$paypal_checkout = 1;
					}
				}
				return (int) $paypal_checkout;
			}
		}

		function usingTmt($a=null) {
			$this->company_index = ($a) ? (string) $a : 0;
			$this->XMLRequest('company');
			return (string) $this->company_response[$this->company_index]->gateway_id == 'tmt' ? 1 : 0;
		}

		function showGiftCardPurchase() {
			return ($this->usingPaypalCheckout() && $this->getGcStatus() || $this->getGateway() && !$this->usingTmt() && $this->getGcStatus()) ? 1 : 0;
		}

		function getDomain($a=null) {
			$this->company_index = ($a) ? (string) $a : 0;
			$this->XMLRequest('company');
			return $this->company_response[$this->company_index]->domain;
		}

		function getCompanyName($a=null) {
			$this->company_index = ($a) ? (string) $a : 0;
			$this->XMLRequest('company');
			return $this->company_response[$this->company_index]->company_name;
		}

		function getCompanyCountry($a=null) {
			$this->company_index = ($a) ? (string) $a : 0;
			$this->XMLRequest('company');
			return $this->company_response[$this->company_index]->country;
		}

		function getCompanyPaypal($a=null) {
			$this->company_index = ($a) ? (string) $a : 0;
			$this->XMLRequest('company');
			return $this->company_response[$this->company_index]->paypal_email;
		}

		function getCompanyDetails($a=null) {
			$this->company_index = ($a) ? (string) $a : 0;
			$this->XMLRequest('company');
			return $this->company_response[$this->company_index];
		}

		function getGcStatus($a=null) {
			$this->company_index = ($a) ? (string) $a : 0;
			$this->XMLRequest('company');
			return (int)$this->company_response[$this->company_index]->gift_cards->enabled == 1 ? 1 : 0;
		}
		
		function getCurrencySymbol($a=null) {
			$this->company_index = ($a) ? (string) $a : 0;
			$this->XMLRequest('company');
			return substr($this->company_response[$this->company_index]->currency_symbol, 0, 2);
		}

		// get a list of calendar data
		function getCalendar($item_id, $date=null, $grouped=1) {
			$available = '';
			if(!$date) { // no date? set a default date (today)
				$date = $default_date = strtotime(date("Y-m-15"));
				$available = 'available'; // get first available date from month API
			} else {
				$date = date("Y-m-15", strtotime($date));
				$date = strtotime($date);
			}

			// $promo = ($this->promo_code) ? '&trigger_code='.$this->promo_code : '';
			$promo = ($this->promo_code != ' ' ) ? '&trigger_code='.$this->promo_code : '';

				$group_set = $grouped ? 'group,' : '';
				$g_mod = $grouped ? '&g=1' : '&g=0';
				
				$query = $this->secure.$this->xml_path.'&i=month&q='.$item_id.'&d='.date("Y-m-d", $date).'&a='.$group_set.$available.$promo.$g_mod;	

			$xml = $this->fetchXML($query);

			if(REZGO_TRACE_XML) {
				if($query) {
					$message = 'month'.' ( '.$query.' )';
					$this->debug('API REQUEST: '.$message, 'month');
				}
			}

			// update the date with the one provided from the API response
			// this is done in case we hopped ahead with the API search (a=available)
			$date = $xml->year.'-'.$xml->month.'-15';

			$year = date("Y", strtotime($date));
			$month = date("m", strtotime($date));

			$date = $base_date = date("Y-m-15", strtotime($date));
			$date = strtotime($date);

			$next_partial = date("Y-m", strtotime($base_date.' +1 month'));
			$prev_partial = date("Y-m", strtotime($base_date.' -1 month'));

			$this->calendar_next = $next_date = date("Y-m-d", strtotime($base_date.' +1 month'));
			$this->calendar_prev = $prev_date = date("Y-m-d", strtotime($base_date.' -1 month'));


			$this->calendar_name = (string) $xml->name;
			$this->calendar_com = (string) $xml->com;
			$this->calendar_active = (int) $xml->active;

			$days = array('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat');

			$months = array("January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");

			$start_day = 1;
			$end_day = date("t", $date);

			$start_dow = date("D", strtotime(date("Y-m-1", $date)));

			$n = 0;
			foreach($months as $k => $v) {
				$this->calendar_months[$n] = new stdClass();
				$this->calendar_months[$n]->selected = ($v == date("F", $date)) ? 'selected' : '';
				$this->calendar_months[$n]->value = $year.'-'.$v.'-15';
				$this->calendar_months[$n]->label = $k;
				$n++;
			}

			for($y=date("Y", strtotime(date("Y").' -1 year')); $y<=date("Y", strtotime(date("Y").' +4 years')); $y++) {
				$this->calendar_years[$n] = new stdClass();
				$this->calendar_years[$n]->selected = ($y == date("Y", $date)) ? 'selected' : '';
				$this->calendar_years[$n]->value = $y.'-'.$month.'-15';
				$this->calendar_years[$n]->label = $y;
				$n++;
			}

			$c = 0;
			foreach($days as $v) {
				$c++;
				if($start_dow == $v) $start_offset = $c;
			}

			if($start_offset) {
				// this will display the lead-up days from last month
				$last_display = date("t", strtotime($prev_date)) - ($start_offset-2);

				for($d=1; $d<$start_offset; $d++) {
					$obj = isset($obj) ? $obj : new stdClass();
					$obj->day = $last_display;
					$obj->lead = 1; // mark as lead up day, so it's not counted in getCalendarDays($day) calls
					$this->calendar_days[] = $obj;
					$last_display++;
					unset($obj);
				}
			}

			$w = $start_offset;
			for($d=1; $d<=$end_day; $d++) {
				$obj = isset($obj) ? $obj : new stdClass();

				$xd = $d - 1;
				$obj->type = 1;

				if($xml->day[$xd]) {
					$obj->cond = $cond = (string) $xml->day[$xd]->attributes()->condition;
					if($xml->day[$xd]->item[0]) {
						// we want to convert the attribute to something easier to use in the template
						$n=0;
						foreach($xml->day[$xd]->item as $i) {
							if($i) {
								$obj->items[$n] = new stdClass();
								$obj->items[$n]->uid = $i->uid;
								$obj->items[$n]->name = $i->name;
								$obj->items[$n]->availability = $i->attributes()->value;
								$n++;
							}
						}
					} else {
						if($xml->day[$xd]->item) {
							$obj->items[0] = new stdClass();
							$obj->items[0]->uid = $xml->day[$xd]->item->uid;
							$obj->items[0]->name = $xml->day[$xd]->item->name;
							$obj->items[0]->availability = $xml->day[$xd]->item->attributes()->value;
						}
					}
				}

				$obj->date = strtotime($year.'-'.$month.'-'.$d);

				$obj->day = $d;
				$this->calendar_days[] = $obj;
				unset($obj);

				if($w == 7) { $w = 1; } else { $w++; }
			}

			if($w != 8 && $w != 1) {
				$d = 0;
				// this will display the lead-out days for next month
				while($w != 8) {
					$d++;
					$w++;
					$obj = isset($obj) ? $obj : new stdClass();
					$obj->day = $d;
					$this->calendar_days[] = $obj;
					unset($obj);
				}
			}
		}

		function getCalendarActive() {
			return $this->calendar_active;
		}

		function getCalendarPrev() {
			return $this->calendar_prev;
		}

		function getCalendarNext() {
			return $this->calendar_next;
		}

		function getCalendarMonths() {
			return $this->calendar_months;
		}

		function getCalendarYears() {
			return $this->calendar_years;
		}

		function getCalendarDays($day=null) {
			$day_response = '';
			if($day) {
				foreach($this->calendar_days as $v) {
					if((int)$v->day == $day && !(int)$v->lead) {
						$day_response = $v; break;
					}
				}

				return (object) $day_response;
			} else {
				return $this->calendar_days ?? '';
			}
		}

		function getCalendarId() {
			return $this->calendar_com;
		}

		function getCalendarName() {
			return $this->calendar_name;
		}

		function getCalendarDiff($date1, $date2) {
			// $date1 and $date2 must have format: Y-M-D

			$date1 = new DateTime($date1);
			$date2 = new DateTime($date2);
			$interval = $date1->diff($date2);

			$res = '';

			if($interval->y > 0) $res .= $interval->y . ' year' . (($interval->y > 1) ? 's' : '') . (($interval->y > 0)?', ':'');
			if($interval->m > 0) $res .= $interval->m . ' month' . (($interval->m > 1) ? 's' : '') . (($interval->m > 0)?', ':'');
			if($interval->d > 0) $res .= $interval->d . ' day' . (($interval->d > 1) ? 's' : '');

			return $res;
		}

		// get a list of tour data
		function getTours($a=null, $node=null) {
			$cart_token = '';
			$str = '';
			// generate the search string
			// if no search is specified, find searched items (grouped)
			if(!$a || $a == $_REQUEST) {
				if($this->requestStr('search_for')) $str .= ($this->requestStr('search_in')) ? '&t='.urlencode($this->requestStr('search_in')) : '&t=smart';
				if($this->requestStr('search_for')) $str .= '&q='.urlencode(stripslashes($this->requestStr('search_for')));
				if($this->requestStr('tags')) $str .= '&f[tags]='.urlencode($this->requestStr('tags'));

				if($this->requestNum('cid')) $str .= '&f[cid]='.urlencode($this->requestNum('cid')); // vendor only

				// details pages
				if($this->requestNum('com')) $str .= '&t=com&q='.urlencode($this->requestNum('com'));
				if($this->requestNum('uid')) $str .= '&t=uid&q='.urlencode($this->requestNum('uid'));
				if($this->requestStr('option')) $str .= '&t=uid&q='.urlencode($this->requestStr('option'));
				if($this->requestStr('date')) $str .= '&d='.urlencode($this->requestStr('date'));

				$a = ($a) ? $a : 'a=group'.$str;
			}

			// manually set promo_code and refid here because referrer urls are being stripped from widget in browsers with cross site protection on
			if(REZGO_LITE_CONTAINER) $this->setPromoCode($this->cart_trigger_code);
			if(REZGO_LITE_CONTAINER) $this->setRefId($this->refid);
			if(REZGO_LITE_CONTAINER) $this->setBookingSource($this->booking_source);

			$promo = ($this->promo_code != ' ' ) ? '&trigger_code='.$this->promo_code : '';

			$limit = '&limit='.$this->tour_limit;

			$this->setCartToken();
			$cart_token .= ($this->cart_token) ? '&cart_token='.$this->cart_token : '';

			// attach the search as an index including the limit value and promo code
			$this->tours_index = $a.$promo.$limit.$cart_token;

			$this->XMLRequest('search');

			if (isset($this->search_response[$this->tours_index])) {
				$return = ($node === null) ? (array) $this->search_response[$this->tours_index] : $this->search_response[$this->tours_index][$node];
				return $return;
			} 
		}

		function getTourAvailability(&$obj=null, $start=null, $end=null) {
			if(!$obj) $obj = $this->getItem();

			// check the object, create a list of com ids
			// search the API with those ids and the date search
			// create a list of dates and relevant options to return

			$loop = (string) $obj->index;

			$d[] = ($start) ? date("Y-M-d", strtotime($start)) : date("Y-M-d", strtotime($this->requestStr('start_date')));
			$d[] = ($end) ? date("Y-M-d", strtotime($end)) : date("Y-M-d", strtotime($this->requestStr('end_date')));
			if($d) { $d = implode(',', $d); } else { return false; }

			if(!$this->tour_availability_response[$loop])	{
				if($this->search_response[$loop]) {
					foreach((array)$this->search_response[$loop] as $v) {
						$uids[] = (string)$v->com;
					}

					$this->tours_index = 't=com&q='.implode(',', array_unique($uids)).'&d='.$d;

					$this->XMLRequest('search');

					$c=0;
					foreach((array)$this->search_response[$this->tours_index] as $i) {
						if($i->date) {
							if($i->date[0]) {
								foreach($i->date as $d) {
									$res[(string)$i->com][strtotime((string)$d->attributes()->value)] = new stdClass();

									$res[(string)$i->com][strtotime((string)$d->attributes()->value)]->id = $c;

									$res[(string)$i->com][strtotime((string)$d->attributes()->value)]->items[$c] = new stdClass();

									$res[(string)$i->com][strtotime((string)$d->attributes()->value)]->items[$c]->name = (string)$i->time;
									$res[(string)$i->com][strtotime((string)$d->attributes()->value)]->items[$c]->availability = (string)$d->availability;
									$res[(string)$i->com][strtotime((string)$d->attributes()->value)]->items[$c]->hide_availability = (string)$d->hide_availability;
									$res[(string)$i->com][strtotime((string)$d->attributes()->value)]->items[$c++]->uid = (string)$i->uid;
									$res[(string)$i->com][strtotime((string)$d->attributes()->value)]->date = strtotime((string)$d->attributes()->value);
								}
							} else {
								$res[(string)$i->com][strtotime((string)$i->date->attributes()->value)] = new stdClass();

								$res[(string)$i->com][strtotime((string)$i->date->attributes()->value)]->id = $c;

								$res[(string)$i->com][strtotime((string)$i->date->attributes()->value)]->items[$c] = new stdClass();

								$res[(string)$i->com][strtotime((string)$i->date->attributes()->value)]->items[$c]->name = (string)$i->time;
								$res[(string)$i->com][strtotime((string)$i->date->attributes()->value)]->items[$c]->availability = (string)$i->date->availability;
								$res[(string)$i->com][strtotime((string)$i->date->attributes()->value)]->items[$c]->hide_availability = (string)$i->date->hide_availability;
								$res[(string)$i->com][strtotime((string)$i->date->attributes()->value)]->items[$c++]->uid = (string)$i->uid;
								$res[(string)$i->com][strtotime((string)$i->date->attributes()->value)]->date = strtotime((string)$i->date->attributes()->value);
							}

							// sort by date so the earlier dates always appear first, the api will return them in that order
							// but if the first item found has a later date than a subsequent item, the dates will be out of order
							ksort($res[(string)$i->com]);
						}
					}
					$this->tour_availability_response[$loop] = $res;
				}
			}

			return (array) $this->tour_availability_response[$loop][(string)$obj->com];
		}

		function getTourPrices(&$obj=null) {
			if(!$obj) $obj = $this->getItem();
			$com = (string) $obj->com;

			$c=0;
			$all_required = 0;
			$valid_count = 0;
			$strike_prices = array();
			$age_min = array();
			$age_max = array();
			$max = array();

			$resource_av = $obj->date->time_data->time->prices->price ?? '';
			$price_id = array();

			$price_index = 0;
			foreach ($obj->prices->price as $price) {
				if($price->strike) {
					$strike_prices[(int) $price->id] = (string) $price->strike;
				}
				if ($price->age_min) {
					$age_min[(int) $price->id] = (string) $price->age_min;
				}
				if ($price->age_max) {
					$age_max[(int) $price->id] = (string) $price->age_max;
				}
				$max[(int) $price->id] = isset($resource_av[$price_index]) ? (int) $resource_av[$price_index]->av : $price->max;
				$price_id[(int) $price->id] = (int) $price->id;
				$price_index++;
			}

			if($this->exists($obj->date->price_adult)) {
				$ret[$c] = new stdClass();
				$ret[$c]->name = 'adult';
				$ret[$c]->id = $price_id[1];
				$ret[$c]->label = (string) $obj->adult_label;
				$ret[$c]->required = (string) $obj->adult_required;
				if($ret[$c]->required) $all_required++;
				($obj->date->base_prices->price_adult) ? $ret[$c]->base = (string) $obj->date->base_prices->price_adult : 0;
				$ret[$c]->price = (string) $obj->date->price_adult;
				$ret[$c]->strike = $strike_prices[1] ?? '';
				$ret[$c]->age_min = $age_min[1] ?? '';
				$ret[$c]->age_max = $age_max[1] ?? '';
				$ret[$c]->max = $max[1] ?? '';
				$ret[$c]->count = (string) $obj->prices->price[0]->count;
				$ret[$c++]->total = (string) $obj->total_adult;
				$valid_count++;
			}
			if($this->exists($obj->date->price_child)) {
				$ret[$c] = new stdClass();
				$ret[$c]->name = 'child';
				$ret[$c]->id = $price_id[2];
				$ret[$c]->label = (string) $obj->child_label;
				$ret[$c]->required = (string) $obj->child_required;
				if($ret[$c]->required) $all_required++;
				($obj->date->base_prices->price_child) ? $ret[$c]->base = (string) $obj->date->base_prices->price_child : 0;
				$ret[$c]->price = (string) $obj->date->price_child;
				$ret[$c]->strike = $strike_prices[2] ?? '';
				$ret[$c]->age_min = $age_min[2] ?? '';
				$ret[$c]->age_max = $age_max[2] ?? '';
				$ret[$c]->max = $max[2] ?? '';
				$ret[$c]->count = (string) $obj->prices->price[1]->count;
				$ret[$c++]->total = (string) $obj->total_child;
				$valid_count++;
			}
			if($this->exists($obj->date->price_senior)) {
				$ret[$c] = new stdClass();
				$ret[$c]->name = 'senior';
				$ret[$c]->id = $price_id[3];
				$ret[$c]->label = (string) $obj->senior_label;
				$ret[$c]->required = (string) $obj->senior_required;
				if($ret[$c]->required) $all_required++;
				($obj->date->base_prices->price_senior) ? $ret[$c]->base = (string) $obj->date->base_prices->price_senior : 0;
				$ret[$c]->price = (string) $obj->date->price_senior;
				$ret[$c]->strike = $strike_prices[3] ?? '';
				$ret[$c]->age_min = $age_min[3] ?? '';
				$ret[$c]->age_max = $age_max[3] ?? '';
				$ret[$c]->max = $max[3] ?? '';
				$ret[$c]->count = (string) $obj->prices->price[2]->count;
				$ret[$c++]->total = (string) $obj->total_senior;
				$valid_count++;
			}

			$j=3;
			for($i=4; $i<=9; $i++) {
				$val = 'price'.$i;
				if($this->exists($obj->date->$val)) {
					$ret[$c] = new stdClass();
					$ret[$c]->name = 'price'.$i;
					$ret[$c]->id = $price_id[$i];
					$val = 'price'.$i.'_label';
					$ret[$c]->label = (string) $obj->$val;
					$val = 'price'.$i.'_required';
					$ret[$c]->required = (string) $obj->$val;
					if($ret[$c]->required) $all_required++;
					$val = 'price'.$i;
					($obj->date->base_prices->$val) ? $ret[$c]->base = (string) $obj->date->base_prices->$val : 0;
					$ret[$c]->price = (string) $obj->date->$val;
					$ret[$c]->strike = $strike_prices[$i] ?? '';
					$ret[$c]->age_min = $age_min[$i] ?? '';
					$ret[$c]->age_max = $age_max[$i] ?? '';
					$ret[$c]->max = $max[$i] ?? '';
					$val = 'total_price'.$i;
					$ret[$c]->count = $obj->prices->price[$j] ? (string) $obj->prices->price[$j]->count : 0;
					$ret[$c++]->total = (string) $obj->$val;
					$valid_count++;
				}
				$j++;
			}

			// if the total required count is the same as the total price points, or if no prices are required
			// we want to set the all_required flag so that the parser won't display individual required marks
			if($all_required == $valid_count || $all_required == 0) {
				$this->all_required = 1;
			} else {
				$this->all_required = 0;
			}

			return (array) $ret;
		}

		function getTourBundles(&$obj=null) {

			$ret = [];
			if(!$obj) $obj = $this->getItem();
			$com = (string) $obj->com;

			$c = 0;

			$bundle_prices = array();

			if(isset($obj->bundles)) {

				foreach ($obj->bundles->bundle as $bundle) {

					$ret[$c] = new stdClass();

					if ($this->exists($bundle->name) && $this->exists($bundle->price)) { // bundles must have a name and a price

						$ret[$c]->id = $c + 1;
						$ret[$c]->name = $bundle->name;

						$ret[$c]->label = $this->seoEncode($bundle->name);

						$ret[$c]->price = $bundle->price;
						$ret[$c]->visible = $bundle->visible;
						$ret[$c]->pax = $bundle->pax;
						$ret[$c]->max = $bundle->max;

						unset($bundle_prices);
						$bundle_makeup = '';
						$bundle_total = 0;

						foreach ($bundle->pax->price as $count) {

							$bundle_total += (int) $count;

							if ($count['id'] == 1) {

								$bundle_makeup .= $count . ' ' . $obj->adult_label . ', ';
								$bundle_prices['adult'] = (string) $count;

							} elseif ($count['id'] == 2) {

								$bundle_makeup .= $count . ' ' . $obj->child_label . ', ';
								$bundle_prices['child'] = (string) $count;

							} elseif ($count['id'] == 3) {

								$bundle_makeup .= $count . ' ' . $obj->senior_label . ', ';
								$bundle_prices['senior'] = (string) $count;

							} else {

								$price_label = 'price'.$count['id'].'_label';
								$bundle_makeup .= $count . ' ' . $obj->$price_label . ', ';
								$bundle_prices['price'.$count['id']] = (string) $count;

							}

						}

						$bundle_makeup = rtrim($bundle_makeup, ', ');
						$ret[$c]->makeup = $bundle_makeup;

						$ret[$c]->prices = $bundle_prices;

						$ret[$c]->total = $bundle_total;

						$c++;

					}

				}

			}

			return (array) $ret;
		}

		function getTourRequired() {
			return ($this->all_required) ? 0 : 1;
		}

		function getTourPriceNum(&$obj=null, $order=null) {
			if(!$obj) $obj = $this->getItem();
			$ret = [];
			// get the value from either the order object or the _REQUEST var
			$val = (is_object($order)) ? $order->{$obj->name.'_num'} : sanitize_text_field($_REQUEST[$obj->name.'_num']);
			for($n=1; $n<=$val; $n++) {
				$ret[] = $n;
			}
			return (array) $ret;
		}

		function getTourTags(&$obj=null) {
			$ret = [];
			if(!$obj) $obj = $this->getItem();
			if($this->exists($obj->tags)) {
				$split = explode(',', $obj->tags);
				foreach((array) $split as $v) {
					$ret[] = trim($v);
				}
			}
			return (array) $ret;
		}

		function getTourLocations(&$obj=null) {
			$ret = [];
			if(!$obj) $obj = $this->getItem();
			$c = 0;
			if($obj->additional_locations->location) {
				if(!$obj->additional_locations->location[0]) {
					$ret[$c]->country = $obj->additional_locations->location->loc_country;
					$ret[$c]->state = $obj->additional_locations->location->loc_state;
					$ret[$c++]->city = $obj->additional_locations->location->loc_city;
				} else {
					foreach($obj->additional_locations->location as $v) {
						$ret[$c]->country = $v->loc_country;
						$ret[$c]->state = $v->loc_state;
						$ret[$c++]->city = $v->loc_city;
					}
				}
			}
			return (array) $ret;
		}

		function getTourMedia(&$obj=null) {
			if(!$obj) $obj = $this->getItem();
			$ret = [];
			$c = 0;
			if($obj->media->image) {
				foreach($obj->media->image as $v) {
					$ret[$c] = new stdClass();
					$ret[$c]->image = $v->path;
					$ret[$c]->path = $v->path;
					$ret[$c]->caption = $v->caption;
					$ret[$c++]->type = 'image';
				}
				return (array) $ret;
			} else { return false; }
		}

		function getTourRelated(&$obj=null) {
			if(!$obj) $obj = $this->getItem();
			$ret = [];
			$c = 0;
			if($obj->related->items) {
				foreach($obj->related->items->item as $v) {
					$ret[$c] = new stdClass();

					$ret[$c]->com = $v->com;
					$ret[$c]->image = $v->image;
					$ret[$c]->starting = $v->starting;
					$ret[$c]->overview = $v->overview;
					$ret[$c++]->name = $v->name;
				}
				return (array) $ret;
			} else { return false; }
		}

		function getCrossSell(&$obj=null) {
			if(!$obj) $obj = $this->getItem();
			$ret = [];
			$c = 0;

			if($obj->cross->items) {
				foreach($obj->cross->items->item as $v) {
					$ret[$c] = new stdClass();

					$ret[$c]->com = $v->com;
					$ret[$c]->image = $v->image;
					$ret[$c]->starting = $v->starting;
					$ret[$c]->overview = $v->overview;
					$ret[$c++]->name = $v->name;
				}
				return (array) $ret;
			} else { return false; }
		}

		function getCrossSellText(&$obj=null) {
			if(!$obj) $obj = $this->getItem();
			$ret = [];
			if($obj->cross) {

				$ret = new stdClass();

				$ret->title = $obj->cross->title;
				$ret->desc = $obj->cross->desc;

				return $ret;
			} else { return false; }
		}

		function getTourLineItems(&$obj=null) {
			if(!$obj) $obj = $this->getItem();
			$ret = [];
			if($obj->line_items) {
				if($obj->line_items->line_item[0]) {
					foreach($obj->line_items->line_item as $v) {
						$ret[] = $v;
					}
				} else {
					$ret[] = $obj->line_items->line_item;
				}
			}

			return (array) $ret;
		}

		function getTourForms($type='primary', &$obj=null, $source='') {
			if(!$obj) $obj = $this->getItem();
			$com = (string) $obj->com;
			$uid = (string) $obj->uid;
			$res = [];

			$type = strtolower($type);
			if($type != 'primary' && $type != 'group') $this->error('unknown type, expecting "primary" or "group"');

			if ($source == 'booking_edit') {
				$booking_edit_forms = $type == 'primary' ? $obj->primary_forms->form : $obj->passengers->passenger[0]->forms->form;

				if($booking_edit_forms) {
					foreach($booking_edit_forms as $f) {

						if ((string)$f->type != '') {
							$res[$type][(string)$f->id] = new stdClass();
							
							$res[$type][(string)$f->id]->id = (string)$f->id;
							$res[$type][(string)$f->id]->type = (string)$f->type;
							$res[$type][(string)$f->id]->title = (string)$f->title;
							$res[$type][(string)$f->id]->require = (string)$f->require;
							$res[$type][(string)$f->id]->value = (string)$f->value;
							$res[$type][(string)$f->id]->instructions = (string)$f->instructions;
							
							if((string)$f->price) {
								if(strpos((string)$f->price, '-') !== false) { 
									$res[$type][(string)$f->id]->price = str_replace("-", "", (string)$f->price);
									$res[$type][(string)$f->id]->price_mod = '-';
								} else {
									$res[$type][(string)$f->id]->price = str_replace("+", "", (string)$f->price);
									$res[$type][(string)$f->id]->price_mod = '+';
								}
							}
							
							if((string)$f->options) {
								$opt = explode(",", (string)$f->options);
								foreach((array)$opt as $v) {
									$res[$type][(string)$f->id]->options[] = $v;
								}
							}
							
							if((string)$f->options_price) {
								$opt = explode(",", (string)$f->options_price);
								foreach((array)$opt as $v) {
									$res[$type][(string)$f->id]->options_price[] = $v;
								}
							}
							
							if((string)$f->options_instructions) {
								$opt_inst = explode(",", (string)$f->options_instructions);
								foreach((array)$opt_inst as $v) {
									$res[$type][(string)$f->id]->options_instructions[] = $v;
								}
							}
						}
					}
				}

			} else {

				if($obj->{$type.'_forms'}) {

					foreach($obj->{$type.'_forms'}->form as $f) {

						if ((string)$f->type != '') {
							$res[$type][(string)$f->id] = new stdClass();

							$res[$type][(string)$f->id]->id = (string)$f->id;
							$res[$type][(string)$f->id]->type = (string)$f->type;
							$res[$type][(string)$f->id]->title = (string)$f->title;
							$res[$type][(string)$f->id]->require = (string)$f->require;
							$res[$type][(string)$f->id]->value = (string)$f->value;
							$res[$type][(string)$f->id]->instructions = (string)$f->instructions;

							if((string)$f->price) {
								if(strpos((string)$f->price, '-') !== false) {
									$res[$type][(string)$f->id]->price = str_replace("-", "", (string)$f->price);
									$res[$type][(string)$f->id]->price_mod = '-';
								} else {
									$res[$type][(string)$f->id]->price = str_replace("+", "", (string)$f->price);
									$res[$type][(string)$f->id]->price_mod = '+';
								}
							}

							if((string)$f->options) {
								$opt = explode(",", (string)$f->options);
								foreach((array)$opt as $v) {
									$res[$type][(string)$f->id]->options[] = $v;
								}
							}

							if((string)$f->options_price) {
								$opt = explode(",", (string)$f->options_price);
								foreach((array)$opt as $v) {
									$res[$type][(string)$f->id]->options_price[] = $v;
								}
							}

							if((string)$f->options_instructions) {
								$opt_inst = explode(",", (string)$f->options_instructions);
								foreach((array)$opt_inst as $v) {
									$res[$type][(string)$f->id]->options_instructions[] = $v;
								}
							}
						}
					}
				}
			}
			$this->tour_forms[$com.'-'.$uid] = $res;
			return isset($this->tour_forms[$com.'-'.$uid][$type]) ? (array) $this->tour_forms[$com.'-'.$uid][$type] : [];
		}

		function getTagSizes() {
			$this->XMLRequest('tags');

			foreach($this->tags_response as $v) {
				$valid_tags[((string)$v->name)] = (string) $v->count;
			}
			// returns high [0] and low [1] for a list()
			rsort($valid_tags);
			$ret[] = $valid_tags[0];
			sort($valid_tags);
			$ret[] = $valid_tags[0];

			return (array) $ret;
		}

		function getTags() {
			$this->XMLRequest('tags');
			return (array) $this->tags_response;
		}

		// get a list of booking data
		function getBookings($a=null, $node=null) {
			if(!$a) $this->error('No search argument provided, expected trans_num or formatted search string');

			if(strpos($a, '=') === false) $a = 'q='.$a; // in case we just passed the trans_num by itself

			$this->bookings_index = $a;

			$this->XMLRequest('search_bookings');

			if (isset($this->search_bookings_response[$this->bookings_index])) {
				$return = ($node === null) ? (array) $this->search_bookings_response[$this->bookings_index] : $this->search_bookings_response[$this->bookings_index][$node];

				return $return;
			}
		}

		// get itinerary
		function getItinerary($a=null, $node=null) {
			if(!$a) $this->error('No search argument provided, expected trans_num or formatted search string');

			$this->trans_num = $a;
			$this->XMLRequest('itinerary');

			return $this->itinerary_response;
		}

		function getBookingPrices(&$obj=null) {
			if(!$obj) $obj = $this->getItem();
			$ret = [];
			$c=0;
			if($obj->adult_num >= 1) {
				$ret[$c] = new stdClass();

				$ret[$c]->name = 'adult';
				$ret[$c]->label = (string) $obj->adult_label;
				($obj->prices->base_prices->price_adult) ? $ret[$c]->base = (string) $obj->prices->base_prices->price_adult : 0;
				$ret[$c]->price = ((float) $obj->prices->price_adult) / ((float) $obj->adult_num);
				$ret[$c]->number = (string) $obj->adult_num;
				$ret[$c++]->total = (string) $obj->prices->price_adult;
			}
			if($obj->child_num >= 1) {
				$ret[$c] = new stdClass();

				$ret[$c]->name = 'child';
				$ret[$c]->label = (string) $obj->child_label;
				($obj->prices->base_prices->price_child) ? $ret[$c]->base = (string) $obj->prices->base_prices->price_child : 0;
				$ret[$c]->price = ((float) $obj->prices->price_child) / ((float) $obj->child_num);
				$ret[$c]->number = (string) $obj->child_num;
				$ret[$c++]->total = (string) $obj->prices->price_child;
			}
			if($obj->senior_num >= 1) {
				$ret[$c] = new stdClass();
				
				$ret[$c]->name = 'senior';
				$ret[$c]->label = (string) $obj->senior_label;
				($obj->prices->base_prices->price_senior) ? $ret[$c]->base = (string) $obj->prices->base_prices->price_senior : 0;
				$ret[$c]->price = ((float) $obj->prices->price_senior) / ((float) $obj->senior_num);
				$ret[$c]->number = (string) $obj->senior_num;
				$ret[$c++]->total = (string) $obj->prices->price_senior;
			}

			for($i=4; $i<=9; $i++) {
				$val = 'price'.$i.'_num';
				if($obj->$val >= 1) {
					$ret[$c] = new stdClass();

					$ret[$c]->name = 'price'.$i;
					$val = 'price'.$i.'_label';
					$ret[$c]->label = (string) $obj->$val;
					$val = 'price'.$i;
					$val2 = 'price'.$i.'_num';
					($obj->prices->base_prices->$val) ? $ret[$c]->base = (string) $obj->prices->base_prices->$val : 0;
					$ret[$c]->price = ((string) $obj->prices->$val) / ((string) $obj->$val2);
					$val = 'price'.$i.'_num';
					$ret[$c]->number = (string) $obj->$val;
					$val = 'price'.$i;
					$ret[$c++]->total = (string) $obj->prices->$val;
				}
			}

			return (array) $ret;
		}

		function getBookingLineItems(&$obj=null) {
			if(!$obj) $obj = $this->getItem();
			$ret = [];

			if($obj->line_items) {
				if($obj->line_items->line_item[0]) {
					foreach($obj->line_items->line_item as $v) {
						$ret[] = $v;
					}
				} else {
					$ret[] = $obj->line_items->line_item;
				}
			}

			return (array) $ret;
		}

		function getBookingFees(&$obj=null) {
			if(!$obj) $obj = $this->getItem();
			$ret = [];
	
			if(isset($obj->triggered_fees) && $obj->triggered_fees->triggered_fee[0]) {
				foreach($obj->triggered_fees->triggered_fee as $v) {
					$ret[] = $v;
				}
			} else {
				$ret[] = $obj->triggered_fees->triggered_fee;
			}

			return (array) $ret;
		}

		function getBookingPassengers(&$obj=null) {
			if(!$obj) $obj = $this->getItem();
			$ret = [];

			$c=0;
			if($obj->passengers->passenger[0]) {
				foreach($obj->passengers->passenger as $v) {
					// do the forms on the passenger only have one question? if so, fix the formatting so it matches multiple questions
					if($v->total_forms > 0) {
						if(!$v->forms->form[0]) {
							$t = $v->forms->form;
							unset($v->forms->form); // remove the string value
							$v->forms->form[] = $t; // replace it with array value
						}
					} else {
						// no forms, fill the value with a blank array so the templates can use it
						// we add a supress @ modifier to prevent the complex-types error
						// apparently works without assigning a blank array?
						// $v->forms->form = [];
					}

					$ret[$c] = $v;
					@$ret[$c]->num = $v->type->attributes()->num;
					$val = $v->type.'_label';
					$ret[$c++]->label = $obj->$val;
				}
			} else {
				// do the forms on the passenger only have one question? if so, fix the formatting so it matches multiple questions
				if($obj->passengers->passenger->total_forms > 0) {
					if(!$obj->passengers->passenger->forms->form[0]) {
						$t = $obj->passengers->passenger->forms->form;
						unset($obj->passengers->passenger->forms->form); // remove the string value
						$obj->passengers->passenger->forms->form[] = $t; // replace it with array value
					}
				} else {
					// no forms, fill the value with a blank array so the templates can use it
					// apparently works without assigning a blank array?
					// @$obj->passengers->passenger->forms->form = array();
				}

				$ret[$c] = $obj->passengers->passenger;
				@$ret[$c]->num = $obj->passengers->passenger->type->attributes()->num;
				$val = $obj->passengers->passenger->type.'_label';
				$ret[$c++]->label = $obj->$val;
			}

			// unset it if the value is empty because we have no group info
			$count = count($ret);
			if($count == 1 && !(string)$ret[0]->num) unset($ret);

			// check to make sure the entire array isn't empty of data
			if($count > 1) {
				foreach((array)$ret as $k => $v) {

					if((string)$v->first_name) { $present = 1; break; }
					if((string)$v->last_name) { $present = 1; break; }

					if((string)$v->phone_number) { $present = 1; break; }
					if((string)$v->email_address) { $present = 1; break; }
					if((string)$v->total_forms > 0) { $present = 1; break; }
				}

				// if(!$present) unset($ret);
			}

			return (array) $ret;
		}

		function getBookingForms(&$obj=null) {
			if(!$obj) $obj = $this->getItem();
			$ret = [];

			if($obj->primary_forms->total_forms > 0) {
				if($obj->primary_forms->form[0]) {
					foreach($obj->primary_forms->form as $v) {
						$ret[] = $v;
					}
				} else {
					$ret[] = $obj->primary_forms->form;
				}
			}

			return (array) $ret;
		}

		function getBookingCounts(&$obj=null) {
			if(!$obj) $obj = $this->getItem();

			if (!empty($obj)) return false;

			$list = array('adult', 'child', 'senior', 'price4', 'price5', 'price6', 'price7', 'price8', 'price9');
			$c=0;
			foreach($list as $v) {
				$num = $v.'_num';
				$label = $v.'_label';
				if($obj->$num > 0) {
					$ret[$c] = new stdClass();
					$ret[$c]->label = $obj->$label;
					$ret[$c++]->num = $obj->$num;
				}
			}
			return (array) $ret;
		}

		function getBookingCurrency(&$obj=null) {
			if(!$obj) $obj = $this->getItem();

			return (string) $obj->currency_base;
		}

		function getPaxString() {
			
			$pax_list = '';

			if($this->requestNum('adult_num')) $pax_list .= '&adult_num='.$this->requestNum('adult_num');
			if($this->requestNum('child_num')) $pax_list .= '&child_num='.$this->requestNum('child_num');
			if($this->requestNum('senior_num')) $pax_list .= '&senior_num='.$this->requestNum('senior_num');
			if($this->requestNum('price4_num')) $pax_list .= '&price4_num='.$this->requestNum('price4_num');
			if($this->requestNum('price5_num')) $pax_list .= '&price5_num='.$this->requestNum('price5_num');
			if($this->requestNum('price6_num')) $pax_list .= '&price6_num='.$this->requestNum('price6_num');
			if($this->requestNum('price7_num')) $pax_list .= '&price7_num='.$this->requestNum('price7_num');
			if($this->requestNum('price8_num')) $pax_list .= '&price8_num='.$this->requestNum('price8_num');
			if($this->requestNum('price9_num')) $pax_list .= '&price9_num='.$this->requestNum('price9_num');

			return $pax_list;
		}

		// ------------------------------------------------------------------------------
		// Handle parsing the rezgo pseudo tags
		// ------------------------------------------------------------------------------
		function tag_parse($str) {
			$val = ($GLOBALS['pageHeader']) ? $GLOBALS['pageHeader'] : $this->pageTitle;
			$tags = array('[navigation]', '[navigator]');
			$str = str_replace($tags, $val, $str);

			$val = ($GLOBALS['pageMeta']) ? $GLOBALS['pageHeader'] : $this->metaTags;
			$tags = array('[meta]', '[meta_tags]', '[seo]');
			$str = str_replace($tags, $val, $str);

			return (string) $str;
		}

		// ------------------------------------------------------------------------------
		// Create an outgoing commit request based on the _REQUEST data
		// ------------------------------------------------------------------------------
		function sendBooking($var=null, $arg=null) {
			$r = ($var) ? $var : $_REQUEST;

			if($arg) $res[] = $arg; // extra API options

			($r['date']) ? $res[] = 'date='.urlencode(sanitize_text_field($r['date'])) : 0;
			($r['book']) ? $res[] = 'book='.urlencode(sanitize_text_field($r['book'])) : 0;

			($r['trigger_code']) ? $res[] = 'trigger_code='.urlencode(sanitize_text_field($r['trigger_code'])) : 0;

			($r['adult_num']) ? $res[] = 'adult_num='.urlencode(sanitize_text_field($r['adult_num'])) : 0;
			($r['child_num']) ? $res[] = 'child_num='.urlencode(sanitize_text_field($r['child_num'])) : 0;
			($r['senior_num']) ? $res[] = 'senior_num='.urlencode(sanitize_text_field($r['senior_num'])) : 0;
			($r['price4_num']) ? $res[] = 'price4_num='.urlencode(sanitize_text_field($r['price4_num'])) : 0;
			($r['price5_num']) ? $res[] = 'price5_num='.urlencode(sanitize_text_field($r['price5_num'])) : 0;
			($r['price6_num']) ? $res[] = 'price6_num='.urlencode(sanitize_text_field($r['price6_num'])) : 0;
			($r['price7_num']) ? $res[] = 'price7_num='.urlencode(sanitize_text_field($r['price7_num'])) : 0;
			($r['price8_num']) ? $res[] = 'price8_num='.urlencode(sanitize_text_field($r['price8_num'])) : 0;
			($r['price9_num']) ? $res[] = 'price9_num='.urlencode(sanitize_text_field($r['price9_num'])) : 0;

			($r['tour_first_name']) ? $res[] = 'tour_first_name='.urlencode(sanitize_text_field($r['tour_first_name'])) : 0;
			($r['tour_last_name']) ? $res[] = 'tour_last_name='.urlencode(sanitize_text_field($r['tour_last_name'])) : 0;
			($r['tour_address_1']) ? $res[] = 'tour_address_1='.urlencode(sanitize_text_field($r['tour_address_1'])) : 0;
			($r['tour_address_2']) ? $res[] = 'tour_address_2='.urlencode(sanitize_text_field($r['tour_address_2'])) : 0;
			($r['tour_city']) ? $res[] = 'tour_city='.urlencode(sanitize_text_field($r['tour_city'])) : 0;
			($r['tour_stateprov']) ? $res[] = 'tour_stateprov='.urlencode(sanitize_text_field($r['tour_stateprov'])) : 0;
			($r['tour_country']) ? $res[] = 'tour_country='.urlencode(sanitize_text_field($r['tour_country'])) : 0;
			($r['tour_postal_code']) ? $res[] = 'tour_postal_code='.urlencode(sanitize_text_field($r['tour_postal_code'])) : 0;
			($r['tour_phone_number']) ? $res[] = 'tour_phone_number='.urlencode(sanitize_text_field($r['tour_phone_number'])) : 0;
			($r['tour_email_address']) ? $res[] = 'tour_email_address='.urlencode(sanitize_text_field($r['tour_email_address'])) : 0;

			if($r['tour_group']) {
				foreach((array) $r['tour_group'] as $k => $v) {
					foreach((array) $v as $sk => $sv) {
						$res[] = 'tour_group['.sanitize_text_field($k).']['.sanitize_text_field($sk).'][first_name]='.urlencode(sanitize_text_field($sv['first_name']));
						$res[] = 'tour_group['.sanitize_text_field($k).']['.sanitize_text_field($sk).'][last_name]='.urlencode(sanitize_text_field($sv['last_name']));
						$res[] = 'tour_group['.sanitize_text_field($k).']['.sanitize_text_field($sk).'][phone]='.urlencode(sanitize_text_field($sv['phone']));
						$res[] = 'tour_group['.sanitize_text_field($k).']['.sanitize_text_field($sk).'][email]='.urlencode(sanitize_email($sv['email']));

						foreach((array) $sv['forms'] as $fk => $fv) {
							if(is_array($fv)) $fv = implode(", ", $fv); // for multiselects
							$res[] = 'tour_group['.sanitize_text_field($k).']['.sanitize_text_field($sk).'][forms]['.$sanitize_text_field(fk).']='.urlencode(stripslashes(sanitize_text_field($fv)));
						}
					}
				}
			}

			if($r['tour_forms']) {
				foreach((array) $r['tour_forms'] as $k => $v) {
					if(is_array($v)) $v = implode(", ", $v); // for multiselects
					$res[] = 'tour_forms['.$k.']='.urlencode(stripslashes(sanitize_text_field($v)));
				}
			}

			($r['payment_method']) ? $res[] = 'payment_method='.urlencode(sanitize_text_field($r['payment_method'])) : 0;

			($r['payment_method_add']) ? $res[] = 'payment_method_add='.urlencode(sanitize_text_field($r['payment_method_add'])) : 0;

			($r['payment_method'] == 'Credit Cards' && $r['tour_card_token']) ? $res[] = 'tour_card_token='.urlencode(sanitize_text_field($r['tour_card_token'])) : 0;
			($r['payment_method'] == 'PayPal' && $r['paypal_token']) ? $res[] = 'paypal_token='.urlencode(sanitize_text_field($r['paypal_token'])) : 0;
			($r['payment_method'] == 'PayPal' && $r['paypal_payer_id']) ? $res[] = 'paypal_payer_id='.urlencode(sanitize_text_field($r['paypal_payer_id'])) : 0;

			($r['agree_terms']) ? $res[] = 'agree_terms='.urlencode(sanitize_text_field($r['agree_terms'])) : 0;

			($r['review_sent']) ? $res[] = 'review_sent='.urlencode(sanitize_text_field($r['review_sent'])) : 0;

			($r['marketing_consent']) ? $res[] = 'marketing_consent='.urlencode(sanitize_text_field($r['marketing_consent'])) : 0;

			// add in external elements
			($this->refid) ? $res['refid'] = '&refid='.$this->refid : 0;
			($this->promo_code) ? $res['promo'] = '&trigger_code='.$this->promo_code : 0;
			($this->booking_source) ? $res['lead'] = '&lead='.$this->booking_source : 0;

			// add in requesting IP
			$res['ip'] = sanitize_text_field($_SERVER["REMOTE_ADDR"]);

			$request = '&'.implode('&', $res);

			$this->XMLRequest('commit', $request);

			return $this->commit_response;
		}

		// ------------------------------------------------------------------------------
		// Create an outgoing commit request based on the CART data
		// ------------------------------------------------------------------------------
		function sendBookingOrder($var=null, $arg=null) {
			$r = ($var) ? $var : $_REQUEST;

			if($arg) $res[] = $arg; // extra API options

			if(!$r['cart_token']) $this->error('sendBookingOrder failed. Cart token was not found', 1);

			$res[] = '<token>'.sanitize_text_field($r['cart_token']).'</token>';

			$res[] = '<payment>';

			isset($r['trigger_code']) ? $res[] = '<trigger_code>'.sanitize_text_field($r['trigger_code']).'</trigger_code>' : 0;

			isset($r['tour_first_name']) ? $res[] = '<tour_first_name>'.sanitize_text_field($r['tour_first_name']).'</tour_first_name>' : 0;
			isset($r['tour_last_name']) ? $res[] = '<tour_last_name>'.sanitize_text_field($r['tour_last_name']).'</tour_last_name>' : 0;
			isset($r['tour_address_1']) ? $res[] = '<tour_address_1>'.sanitize_text_field($r['tour_address_1']).'</tour_address_1>' : 0;
			isset($r['tour_address_2']) ? $res[] = '<tour_address_2>'.sanitize_text_field($r['tour_address_2']).'</tour_address_2>' : 0;
			isset($r['tour_city']) ? $res[] = '<tour_city>'.sanitize_text_field($r['tour_city']).'</tour_city>' : 0;
			isset($r['tour_stateprov']) ? $res[] = '<tour_stateprov>'.sanitize_text_field($r['tour_stateprov']).'</tour_stateprov>' : 0;
			isset($r['tour_country']) ? $res[] = '<tour_country>'.sanitize_text_field($r['tour_country']).'</tour_country>' : 0;
			isset($r['tour_postal_code']) ? $res[] = '<tour_postal_code>'.sanitize_text_field($r['tour_postal_code']).'</tour_postal_code>' : 0;
			isset($r['tour_phone_number']) ? $res[] = '<tour_phone_number>'.sanitize_text_field($r['tour_phone_number']).'</tour_phone_number>' : 0;
			isset($r['tour_email_address']) ? $res[] = '<tour_email_address>'.sanitize_text_field($r['tour_email_address']).'</tour_email_address>' : 0;
			isset($r['sms']) ? $res[] = '<sms>'.sanitize_text_field($r['sms']).'</sms>' : 0;
			isset($r['tip']) ? $res[] = '<tip>'.sanitize_text_field($r['tip']).'</tip>' : 0;

			isset($r['payment_method']) ? $res[] = '<payment_method>'.urlencode(stripslashes(sanitize_text_field($r['payment_method']))).'</payment_method>' : 0;

			isset($r['payment_method_add']) ? $res[] = '<payment_method_add>'.urlencode(stripslashes(sanitize_text_field($r['payment_method_add']))).'</payment_method_add>' : 0;

			($r['payment_method'] == 'Credit Cards' && $r['tour_card_token']) ? $res[] = '<tour_card_token>'.sanitize_text_field($r['tour_card_token']).'</tour_card_token>' : 0;
			($r['payment_method'] == 'PayPal' && $r['paypal_token']) ? $res[] = '<paypal_token>'.sanitize_text_field($r['paypal_token']).'</paypal_token>' : 0;
			($r['payment_method'] == 'PayPal' && $r['paypal_payer_id']) ? $res[] = '<paypal_payer_id>'.sanitize_text_field($r['paypal_payer_id']).'</paypal_payer_id>' : 0;

			isset($r['payment_id']) ? $res[] = '<payment_id>'.sanitize_text_field($r['payment_id']).'</payment_id>' : 0;

			isset($r['agree_terms']) ? $res[] = '<agree_terms>'.sanitize_text_field($r['agree_terms']).'</agree_terms>' : 0;

			isset($r['review_sent']) ? $res[] = '<review_sent>'.sanitize_text_field($r['review_sent']).'</review_sent>' : 0;

			isset($r['marketing_consent']) ? $res[] = '<marketing_consent>'.sanitize_text_field($r['marketing_consent']).'</marketing_consent>' : 0;

			// add in external elements
			($this->refid) ? $res[] = '<refid>'.$this->refid.'</refid>' : 0;
			($this->promo_code) ? $res[] = '<trigger_code>'.$this->promo_code.'</trigger_code>' : 0;
			($this->booking_source) ? $res[] = '<booking_source>'.$this->booking_source.'</booking_source>' : 0;

			// add in requesting IP
			$res[] = '<ip>'.sanitize_text_field($_SERVER["REMOTE_ADDR"]).'</ip>';

			// add in 3DS validation fields if available
			if($r['validate']) {
				$res[] = '<validate>';
				foreach((array) $r['validate'] as $validate_key => $validate_value) {
					$res[] = '<'.sanitize_text_field($validate_key).'>'.sanitize_text_field($validate_value).'</'.sanitize_text_field($validate_key).'>';
				}
				$res[] = '</validate>';
			}

			// GIFT-CARD
			isset($r['expected']) ? $res[] = '<expected>'.sanitize_text_field($r['expected']).'</expected>' : 0;
			isset($r['gift_card']) ? $res[] = '<gift_card>'.sanitize_text_field($r['gift_card']).'</gift_card>' : 0;

			$res[] = '</payment>';

			isset($r['tour_tg_insurance_coverage']) ? $res[] = '<tg>'.$r['tour_tg_insurance_coverage'].'</tg>' : 0;

			isset($r['waiver']) ? $res[] = '<waiver>'.sanitize_text_field($r['waiver']).'</waiver>' : 0;

			// include waiver initials
			$res[] = '<initials>';
				$initial_index = 0;
				foreach ($r as $key => $value) {
					if (strpos( $key, 'waiver_url_'.REZGO_CID ) !== false) {
						$index = substr($key, -1);
						$res[] = '<initial_'.$index.'>'.sanitize_text_field($r[$key]). '</initial_'.$index.'>';
						$initial_index++;
					}
				}
			$res[] = '</initials>';		

			$request = implode('', $res);

			$this->XMLRequest('commitOrder', $request, 1);

			return $this->commit_response;
		}
		
		// ------------------------------------------------------------------------------
		// Create a request to change TG status after purchase post booking
		// ------------------------------------------------------------------------------
		function sendTgPostBooking($var=null, $arg=null) {

			$r = ($var) ? $var : $_REQUEST;
			
			if($arg) $res[] = $arg; // extra API options


			// quote token
			$res[] = '<quote>'.$r['quoteToken'].'</quote>';


			// payment
			$res[] = '<token>'.$r['tour_card_token'].'</token>';

			// order code
			$res[] = '<order_code>'.$r['booking']['order_code'].'</order_code>';

			// tg_flag 
			$res[] = '<tg>1</tg>';
			
			$request = implode('', $res);

			$this->XMLRequest('tg_postbooking', $request, 1);
			return $this->commit_response;
		}

		// ------------------------------------------------------------------------------
		// GIFT CARD
		// ------------------------------------------------------------------------------
		function sendGiftOrder($request=null) {
			$r = $request;
			$res = array();

			if($r['rezgoAction'] != 'addGiftCard') {
				$this->error('sendGiftOrder failed. Card array was not found', 1);
			}

			$res[] = '<card>';
				$res[] = '<number></number>';
				$res[] = '<amount>'.sanitize_text_field($r['billing_amount']).'</amount>';
				$res[] = '<cash_value></cash_value>';
				$res[] = '<expires></expires>';
				$res[] = '<max_uses></max_uses>';
				$res[] = $r['option'] ? '<gifted_as>'.sanitize_text_field($r['option']).'</gifted_as>' : '';
				$res[] = $r['adult_num'] ? '<adult_num>' . $r['adult_num'] . '</adult_num>' : '';
				$res[] = $r['child_num'] ? '<child_num>' . $r['child_num'] . '</child_num>' : '';
				$res[] = $r['senior_num'] ? '<senior_num>' . $r['senior_num'] . '</senior_num>' : '';
				$res[] = $r['price4_num'] ? '<price4_num>' . $r['price4_num'] . '</price4_num>' : '';
				$res[] = $r['price5_num'] ? '<price5_num>' . $r['price5_num'] . '</price5_num>' : '';
				$res[] = $r['price6_num'] ? '<price6_num>' . $r['price6_num'] . '</price6_num>' : '';
				$res[] = $r['price7_num'] ? '<price7_num>' . $r['price7_num'] . '</price7_num>' : '';
				$res[] = $r['price8_num'] ? '<price8_num>' . $r['price8_num'] . '</price8_num>' : '';
				$res[] = $r['price9_num'] ? '<price9_num>' . $r['price9_num'] . '</price9_num>' : '';

				// NAME
				$recipient_name = explode(" ", sanitize_text_field($r['recipient_name']), 2);
				$res[] = '<first_name>'.$recipient_name[0].'</first_name>';
				$res[] = '<last_name>'.$recipient_name[1].'</last_name>';
				$res[] = '<email>'.sanitize_email($r['recipient_email']).'</email>';
				// MSG
				$res[] = '<message>'.urlencode(htmlspecialchars_decode(stripslashes(sanitize_textarea_field($r['recipient_message'])), ENT_QUOTES)).'</message>';

				$res[] = '<send>1</send>';
				$res[] = '<payment>';

					$res[] = '<method>'.urlencode(stripslashes($r['payment_method'])).'</method>';
					$res[] = '<token>'.sanitize_text_field($r['gift_card_token']).'</token>';
					if($r['payment_id']) $res[] = '<payment_id>'.sanitize_text_field($r['payment_id']).'</payment_id>';

					$res[] = '<first_name>'.sanitize_text_field($r['billing_first_name']).'</first_name>';
					$res[] = '<last_name>'.sanitize_text_field($r['billing_last_name']).'</last_name>';
					$res[] = '<address_1>'.sanitize_text_field($r['billing_address_1']).'</address_1>';
					$res[] = '<address_2>'.sanitize_text_field($r['billing_address_2']).'</address_2>';
					$res[] = '<city>'.sanitize_text_field($r['billing_city']).'</city>';
					$res[] = '<state>'.sanitize_text_field($r['billing_stateprov']).'</state>';
					$res[] = '<country>'.sanitize_text_field($r['billing_country']).'</country>';
					$res[] = '<postal>'.sanitize_text_field($r['billing_postal_code']).'</postal>';
					$res[] = '<phone>'.sanitize_text_field($r['billing_phone']).'</phone>';
					$res[] = '<email>'.sanitize_email($r['billing_email']).'</email>';
					if($r['terms_agree'] == 'on') {
						$res[] = '<agree_terms>1</agree_terms>';
					}
					else {
						$res[] = '<agree_terms>0</agree_terms>';
					}
					$res[] = '<ip>'.sanitize_text_field($_SERVER['REMOTE_ADDR']).'</ip>';

					// add in 3DS validation fields if available
					if($r['validate']) {
						$res[] = '<validate>';
						foreach((array) $r['validate'] as $validate_key => $validate_value) {
							$res[] = '<'.sanitize_text_field($validate_key).'>'.sanitize_text_field($validate_value).'</'.sanitize_text_field($validate_key).'>';
						}
						$res[] = '</validate>';
					}

				$res[] = '</payment>';
			$res[] = '</card>';

			$request = implode('', $res);
			
			$this->XMLRequest('addGiftCard', $request, 1);

			return $this->commit_response;
		}		

		// ------------------------------------------------------------------------------
		// Save Waiver Signature to S3
		// ------------------------------------------------------------------------------
		function saveWaiverSignature($request=null) {
			$r = $request ?: $_REQUEST;
			$res = [];

			$res[] = '<sign>';
				$res[] = '<signature_only>1</signature_only>';
				$res[] = '<item_id>'.$r['pax_item'].'</item_id>';
				$res[] = '<signature>'.$r['signature'].'</signature>';
			$res[] = '</sign>';

			$request = implode('', $res);
			
			$this->XMLRequest('sign', $request, 1);

			return $this->signing_response;
		}
				
		// ------------------------------------------------------------------------------
		// Save Initial Signature to S3
		// ------------------------------------------------------------------------------

		function saveWaiverInitial($request=null) {
			$r = $request ?: $_REQUEST;
			$res = [];

			$res[] = '<sign>';
				$res[] = '<signature_only>1</signature_only>';
				$res[] = '<item_id>'.$r['pax_item'].'</item_id>';
				$res[] = '<pax_id>'.$r['pax_id'].'</pax_id>';
				$res[] = '<initial_index>'.json_encode($r['initial_index']).'</initial_index>';
				$res[] = '<signature>'.$r['initial'].'</signature>';
			$res[] = '</sign>';

			$request = implode('', $res);
			
			$this->XMLRequest('sign', $request, 1);

			return $this->signing_response;
		}

		// ------------------------------------------------------------------------------
		// Sign Waiver
		// ------------------------------------------------------------------------------
		function signWaiver($request=null) {
			$r = $request ?: $_REQUEST;
			$res = [];

			if($r['pax_type'] != 'general') {

				$pax_forms = $r['pax_group'][$r['pax_type']][$r['pax_type_num']]['forms'];

				/*$group_forms = '
					<type>'.$r['pax_type'].'</type>
					<num>'.$r['pax_type_num'].'</num>
					<forms>
				';*/

				foreach((array) $pax_forms as $form_id => $form_answer) {
					if(is_array($form_answer)) { // for multiselects
						$form_answer = implode(', ', $form_answer);
						$r['pax_group'][$r['pax_type']][$r['pax_type_num']]['forms'][$form_id] = $form_answer;
					}
					//$group_forms .= '<form num="'.$form_id.'">'.$form_answer.'</form>';
				}

				/*$group_forms .= '
					</forms>
				';*/

			}

			$group_forms = serialize($r['pax_group']);

			$res[] = '<sign>';
			$res[] = '<type>pax</type>'; // change to dynamic later
			$res[] = '<child>'.$r['child'].'</child>';
			$res[] = '<pax_type>'.$r['pax_type'].'</pax_type>';
			$res[] = '<pax_type_num>'.$r['pax_type_num'].'</pax_type_num>';
			$res[] = '<order_code>'.$r['order_code'].'</order_code>';
			$res[] = '<trans_num>'.$r['trans_num'].'</trans_num>';
			$res[] = '<item_id>'.$r['pax_item'].'</item_id>';
			$res[] = '<pax_id>'.$r['pax_id'].'</pax_id>';
			$res[] = '<first_name>'.$r['pax_first_name'].'</first_name>';
			$res[] = '<last_name>'.$r['pax_last_name'].'</last_name>';
			$res[] = '<phone_number>'.$r['pax_phone'].'</phone_number>';
			$res[] = '<email_address>'.$r['pax_email'].'</email_address>';

			$res[] = '<birthdate>';
				$res[] = '<year>'.$r['pax_birthdate']['year'].'</year>';
				$res[] = '<month>'.$r['pax_birthdate']['month'].'</month>';
				$res[] = '<day>'.$r['pax_birthdate']['day'].'</day>';
			$res[] = '</birthdate>';

			$res[] = '<group_forms>'.$group_forms.'</group_forms>';
			$res[] = '<signature_url>'.$r['signature_url'].'</signature_url>';

				// include waiver initials
				$res[] = '<initials>';
					$initial_index = 0;
					foreach ($r as $key => $value) {
						if (strpos( $key, 'waiver_url_'.REZGO_CID ) !== false) {
							$index = substr($key, -1);
							$res[] = '<initial_'.$index.'>'.sanitize_text_field($r[$key]). '</initial_'.$index.'>';
							$initial_index++;
						}
					}
				$res[] = '</initials>';

			$res[] = '</sign>';

			$request = implode('', $res);

			$this->XMLRequest('sign', $request, 1);

			return $this->signing_response;
		}

		function getGiftCard($request=null) {
			if(!$request) {
				$this->error('No search argument provided, expected card number');
			}

			$this->XMLRequest('searchGiftCard', $request);

			return $this->gift_card;
		}

		// this function is for sending a partial commit request, it does not add any values itself
		function sendPartialCommit($var=null) {
			$request = '&'.$var;

			$this->XMLRequest('commit', $request);

			return $this->commit_response;
		}

		// send results of contact form
		function sendContact($var=null) {
			$r = ($var) ? $var : $_REQUEST;

			if (REZGO_WORDPRESS) $from_site = 'Sent from: ' . get_bloginfo('name') .' ('.home_url() .')'. "\n\n";

			// we use full_name instead of name because of a wordpress quirk
			($r['full_name']) ? $res[] = 'name='.urlencode(htmlspecialchars_decode($r['full_name'], ENT_QUOTES)) : $this->error('contact element "full_name" is empty', 1);
			($r['email']) ? $res[] = 'email='.urlencode(sanitize_email($r['email'])) : $this->error('contact element "email" is empty', 1);
			($r['body']) ? $res[] = 'body='.urlencode(htmlspecialchars_decode($r['body'], ENT_QUOTES)) : $this->error('contact element "body" is empty', 1);

			($r['phone']) ? $res[] = 'phone='.urlencode(sanitize_text_field($r['phone'])) : 0;
			($r['address']) ? $res[] = 'address='.urlencode(htmlspecialchars_decode($r['address'], ENT_QUOTES)) : 0;
			($r['address2']) ? $res[] = 'address2='.urlencode(htmlspecialchars_decode($r['address2'], ENT_QUOTES)) : 0;		
			($r['city']) ? $res[] = 'city='.urlencode(sanitize_text_field($r['city'])) : 0;
			($r['state_prov']) ? $res[] = 'state_prov='.urlencode(sanitize_text_field($r['state_prov'])) : 0;
			($r['country']) ? $res[] = 'country='.urlencode(sanitize_text_field($r['country'])) : 0;

			$request = '&'.implode('&', $res);

			$this->XMLRequest('contact', $request);

			return $this->contact_response;
		}

		// send review data
		function sendReview($request=null) {
			$r = ($request) ? $request : $_REQUEST;
			$res = array();

			$res[] = '<review>';
				$res[] = '<booking>'.sanitize_text_field($r['trans_num']).'</booking>';
				$res[] = '<rating>'.sanitize_text_field($r['rating']).'</rating>';
				$res[] = '<title><![CDATA['.urlencode(htmlspecialchars_decode(sanitize_text_field($r['review_title']), ENT_QUOTES)).']]></title>';
				$res[] = '<body><![CDATA['.urlencode(htmlspecialchars_decode(sanitize_text_field($r['review_body']), ENT_QUOTES)).']]></body>';
			$res[] = '</review>';

			$request = implode('', $res);

			$this->XMLRequest('add_review', $request, 1);

			return $this->review_response;
		}

		// get review data
		function getReview($q=null, $type=null, $limit=null, $sort=null, $order=null) {

			// 'type' default is booking // 'limit' default is 5

			($q) ? $res[] = 'q='.urlencode($q) : 0;
			($type) ? $res[] = 'type='.urlencode($type) : 0;
			($limit) ? $res[] = 'limit='.urlencode($limit) : 0;
			($sort) ? $res[] = 'sort='.urlencode($sort) : 0;
			($order) ? $res[] = 'order='.urlencode($order) : 0;

			$request = implode('&', $res);

			$this->XMLRequest('review', $request);

			return $this->review_response;
		}

		// get pickup list
		function getPickupList($option_id=null) {

			($option_id) ? $res[] = 'q='.urlencode($option_id) : 0;

			$request = implode('&', $res);

			$this->XMLRequest('pickup', $request);

			return $this->pickup_response;
		}

		// get pickup location
		function getPickupItem($option_id=null, $pickup_id=null, $book_time=null) {

			($option_id) ? $res[] = 'q='.urlencode($option_id) : 0;
			($pickup_id) ? $res[] = 'pickup='.urlencode($pickup_id) : 0;
			($book_time) ? $res[] = 'book_time='.urlencode($book_time) : 0;

			$request = implode('&', $res);

			$this->XMLRequest('pickup', $request);

			return $this->pickup_response->pickup;
		}

		// get payment request
		function getPayment($request_id=null) {

			($request_id) ? $res[] = 'q='.urlencode($request_id) : 0;

			$request = implode('&', $res);

			$this->XMLRequest('payment', $request);

			if ($this->payment_response->error) {
				return $this->payment_response; // ->error
			} else {
				return $this->payment_response->payment;
			}

		}

		// sending payment
		function sendPayment($var=null) {
			$r = ($var) ? $var : $_REQUEST;
			$res = array();

			if($r['rezgoAction'] != 'add_payment') {
				$this->error('sendPayment failed, payment array was not found', 1);
			}
			
			$res[] = '<transaction>';
				$res[] = '<request>'.$r['request_id'].'</request>';
				$res[] = '<amount>'.$r['payment_amount'].'</amount>';
				
				if (strlen($r['tour_order_code']) == 16) {
					$res[] = '<order>'.$r['tour_order_code'].'</order>';
				} else {
					$res[] = '<booking>'.$r['tour_order_code'].'</booking>';
				}
				
				$res[] = '<method>'.urlencode(stripslashes($r['payment_method'])).'</method>';
				$res[] = '<token>'.$r['tour_card_token'].'</token>';
				
				if($r['payment_id']) $res[] = '<payment_id>'.$r['payment_id'].'</payment_id>';
				
				$res[] = '<first_name>'.$r['tour_first_name'].'</first_name>';
				$res[] = '<last_name>'.$r['tour_last_name'].'</last_name>';
				$res[] = '<email_address>'.$r['tour_email_address'].'</email_address>';
				$res[] = '<address_1>'.$r['tour_address_1'].'</address_1>';
				$res[] = '<address_2>'.$r['tour_address_2'].'</address_2>';
				$res[] = '<city>'.$r['tour_city'].'</city>';
				$res[] = '<stateprov>'.$r['tour_stateprov'].'</stateprov>';
				$res[] = '<country>'.$r['tour_country'].'</country>';
				$res[] = '<postal_code>'.$r['tour_postal_code'].'</postal_code>';
				$res[] = '<phone_number>'.$r['tour_phone_number'].'</phone_number>';
				($r['tip']) ? $res[] = '<tip>'.$r['tip'].'</tip>' : 0;
				
				$res[] = '<agree_terms>'.$r['agree_terms'].'</agree_terms>';
				$res[] = '<ip>'.$_SERVER['REMOTE_ADDR'].'</ip>';
            
                // add in 3DS validation fields if available
                if($r['validate']) {
                    $res[] = '<validate>';
                    foreach((array) $r['validate'] as $validate_key => $validate_value) {
                        $res[] = '<'.$validate_key.'>'.$validate_value.'</'.$validate_key.'>';
                    }
                    $res[] = '</validate>';
                }
				
			$res[] = '</transaction>';
			
			$request = implode('', $res);
			
			$this->XMLRequest('add_transaction', $request, 1);

			return $this->commit_response;
		}		
		
		// add an item to the shopping cart
		function addToCart($item, $clear=0) {

			if(!$clear) {
				// don't load the existing cart if we are clearing it
				if($_COOKIE['rezgo_cart_'.REZGO_CID]) { $cart = unserialize(stripslashes($_COOKIE['rezgo_cart_'.REZGO_CID])); }

				// load the existing cart -- CART API
				if($_COOKIE['rezgo_cart_token_'.REZGO_CID]){
					$cart_token = $_COOKIE['rezgo_cart_token_'.REZGO_CID];
				}
			}

			if ($cart_token) {

				$res = array();
				$res[] = '<cart>'.$cart_token.'</cart>';
				$res[] = '<action>add</action>';

				$request = implode('', $res);

				$this->XMLRequest('cart', $request, 1);

				$this->cart_response;

				$tour = $this->cart_response;

				$contents = $tour->contents;
				$decode = json_decode($contents, true);

				// add the new item to the cart
				foreach((array) $decode as $v) {
					
					// at least 1 price point must be set to add this item
					// this works to remove items as well, if they match the date (above) but have no prices set
					if(
						$v['adult_num'] || $v['child_num'] || $v['senior_num'] || 
						$v['price4_num'] || $v['price5_num'] || $v['price6_num'] || 
						$v['price7_num'] || $v['price8_num'] || $v['price9_num']
					) {
						$cart[] = $v;
					}
				}
				
			}
				
			return $cart;
		}
		
		// add an item to the shopping cart
		function editCart($item) {
			
			// load the existing cart
			if($_COOKIE['rezgo_cart_'.REZGO_CID]) { $cart = unserialize(stripslashes($_COOKIE['rezgo_cart_'.REZGO_CID])); }

			// load the existing cart -- CART API
			if($_COOKIE['rezgo_cart_token_'.REZGO_CID]){
				$cart_token = $_COOKIE['rezgo_cart_token_'.REZGO_CID];
			}

			// add the new item to the cart
			foreach((array) $item as $k => $v) {
				
				if($cart[$k]) {
				
					// at least 1 price point must be set to edit this item
					// this works to remove items as well, if they match the date (above) but have no prices set
					if(
						$v['adult_num'] || $v['child_num'] || $v['senior_num'] || 
						$v['price4_num'] || $v['price5_num'] || $v['price6_num'] || 
						$v['price7_num'] || $v['price8_num'] || $v['price9_num']
					) {
						$cart[$k] = $v;
					} else {
						unset($cart[$k]);
					}
				
				}
			}
			
			return $cart;
		}
		
		// add pickup to the shopping cart
		function pickupCart($index, $pickup) {
			
			// load the existing cart
			if($_COOKIE['rezgo_cart_'.REZGO_CID]) { $cart = unserialize(stripslashes($_COOKIE['rezgo_cart_'.REZGO_CID])); }
				
			if($cart[$index]) {
				
				$cart[$index]['pickup'] = $pickup;

				// update cart
				$this->setCookie("rezgo_cart_".REZGO_CID, $cart);

				$this->setShoppingCart(serialize($cart));
			
			}
			
			return $cart;
		}

		function clearCart() {
			$this->setCookie("rezgo_cart_".REZGO_CID, '');
			$this->setCookie("cart_status", '');
			$this->setCookie("rezgo_cart_token_".REZGO_CID, '');

			foreach ($_COOKIE as $c_name => $c_content) {
				if (strpos($c_name, 'cross_') === 0 && $c_content == 'shown') {
					$this->setCookie($c_name, '');
				}
			}

			if (REZGO_WORDPRESS) {
				if (is_multisite() && !SUBDOMAIN_INSTALL && ( DOMAIN_CURRENT_SITE != REZGO_WP_DIR )) {
					setcookie("rezgo_cart_".REZGO_CID, '', 1, str_replace( DOMAIN_CURRENT_SITE, '', REZGO_WP_DIR ), $_SERVER['SERVER_NAME']);
				} else {
					setcookie("rezgo_cart_".REZGO_CID, '', 1, '/', $_SERVER['HTTP_HOST']);
				}
			}
		}

		// get a list of all the item IDs that are currently in the cart (used for search queries)
		function getCartIDs() {
			$n = 1;
			$ids = [];
			if(is_array($this->cart)) {
				foreach($this->cart as $k => $v) {
					$ids[] = $v['uid'];
					$n++;
				}
				$ids = ($ids) ? implode(",", $ids) : '';
			}
			return $ids;
		}

		// fetch the full shopping cart including detailed tour info
		function getCart($hide=null) {

			// check if there is a cart token in URL, and if it matches current cart
			$request_token = sanitize_text_field($_REQUEST['cart'] ?? '');
			$current_token = $_COOKIE['rezgo_cart_token_'.REZGO_CID] ?? '';

			if (!REZGO_LITE_CONTAINER) {

				if ( ($request_token && !isset($current_token)) || isset($_REQUEST['custom_domain']) ){
					$this->setCookie('rezgo_cart_token_'.REZGO_CID, $request_token);
					$this->searchToken($request_token);

					if (REZGO_WORDPRESS) {
						$this->sendTo((($this->checkSecure()) ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].'/'.$_REQUEST['wp_slug'] . '/order');
					} else {
						if ($_SERVER['SCRIPT_NAME'] == '/page_book.php') {
							// if redirected from other sources, initiate checkout here
							$this->initiateCheckout();
							$this->sendTo('/book');
						} else if  ($_SERVER['SCRIPT_NAME'] == '/page_order.php') {
							$this->sendTo('/order');
						}
					}

				}
				else if ($request_token) {
					
					if ($request_token != $current_token){

						// clear all previous cart contents
						$this->clearCart();

						$this->setCookie('rezgo_cart_token_'.REZGO_CID, $request_token);
						$this->searchToken($request_token);

						if (REZGO_WORDPRESS) {
							$this->sendTo((($this->checkSecure()) ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].'/'.$_REQUEST['wp_slug'] . '/order');
						} else {

							if ($_SERVER['SCRIPT_NAME'] == '/page_book.php') {
								$this->sendTo('/book');
							} else if  ($_SERVER['SCRIPT_NAME'] == '/page_order.php') {
								$this->sendTo('/order');
							}
						}
					}
					else {
						$this->cart_token = $current_token;
					}
				}
				else {
					$this->cart_token = $current_token;
				}
			}

			$cart = isset($this->cart) ? $this->cart : '';
			$cart_data = isset($this->cart_data) ? $this->cart_data : '';
			$package_data = isset($this->package_data) ? $this->package_data : '';
			$cart_package_uids = array();

			if ($cart) {
				$c = 0;
				foreach ($cart as $item) {
					$cart[$c]->booking_date = strtotime((string)$item->date[0]->attributes()->value);
					
					if ($cart_data[$c]->package){
						$cart[$c]->package = $cart_data[$c]->package;

						$cart[$c]->cart_package_uid = $cart_data[$c]->cart_package_uid;
						$cart[$c]->package_name = (string)$cart_data[$c]->package_name;

						// count of items in package
						if ($cart[$c]->package) {
							$cart_package_uids[] = (int) $cart[$c]->cart_package_uid;
						}
					}
					$c++;
				}
				$counted = array_count_values($cart_package_uids);

				$p_index = 1;
				$d = 0;
				foreach ($cart as $item){
					if (isset($counted[(int) $cart[$d]->cart_package_uid])){
						$cart[$d]->package_item_index = $p_index;
						$cart[$d]->package_item_total = $counted[(int) $cart[$d]->cart_package_uid];
						$p_index++;

						// reset index if it reaches total count
						if ($p_index === ($counted[(int) $cart[$d]->cart_package_uid])+1) {
							$p_index = 1;
						}
					} 
					$d++;
				}
			}

			return $cart ?? (array) $cart;
		}

		function getFormDisplay(){
			return $this->form_display;
		}

		function getGroupFormDisplay(){
			return $this->gf_form_display;
		}

		function getCartTotal(){
			return (string) $this->cart_total;
		}

		function getCartItems(){
			return (int) $this->cart_items;
		}

		function getCartStatus(){
			return $this->cart_status;
		}

		//*********************//
		//********** CART API
		//*********************//

		function cartRequest($action, $args='') {

			$this->setCartToken();

			if(!$this->cart_token && $action == 'search') return false;

			$res = array();
			$res[] = '<instruction>cart</instruction>';
			$res[] = '<cart>' . $this->cart_token . '</cart>';
			$res[] = '<action>' . $action . '</action>';
			$res[] = $args;
			$res[] = '</request>';

			$this->cart_api_request = implode('', $res);
		}

		function searchToken($token) {

			// searching can't happen until we have a token, added from the first item added to cart
			if($token) {

				$res = array();
				$res[] = '<instruction>cart</instruction>';
				$res[] = '<cart>' . $token . '</cart>';
				$res[] = '<action>search</action>';
				$res[] = '</request>';

				$this->cart_api_request = implode('', $res);
				$this->XMLRequest('search_cart', $this->cart_api_request);
				$this->cart = $this->cart_api_response;

			}
		}

		function setCartToken(){
			// set cart token based on cookie or constant
			if (!REZGO_LITE_CONTAINER){
				$this->cart_token = isset($_COOKIE['rezgo_cart_token_'.REZGO_CID]) ? $_COOKIE['rezgo_cart_token_'.REZGO_CID] : '';
			} else {
				if ($this->cartInUri()) $this->cart_token = REZGO_LITE_CART;
			}
		}	

		function parseFromUrl($arg=''){
			$url = $_SERVER['HTTP_REFERER'];
			$parts = parse_url($url);
			parse_str($parts['query'], $query);
			$parsed = $query[$arg];

			return $parsed;
		}

		function createCart() {
			$this->cartRequest('create');
			$this->XMLRequest('create_cart', $this->cart_api_request);
			$this->setCookie('rezgo_cart_token_'.REZGO_CID, $this->cart_token);

			return $this->cart_token;
		}

		function searchCart() {

			$res = $this->cartRequest('search');

			if($res !== false && !$this->cart_api_response) {
				$this->XMLRequest('search_cart', $this->cart_api_request);
				$this->cart = $this->cart_api_response;
			}

		}

		function initiateCheckout($additional_args='') {
            $this->cartRequest('checkout',$additional_args);
			$this->XMLRequest('initiate_checkout', $this->cart_api_request);
		}

		function getFormData() {
			return $this->cart_data;
		}

		// -------------------------------------------------
		// Create an update request for edit_pax to the CART
		// -------------------------------------------------
		function editPax($var=null, $arg=null) {

			$r = ($var) ? $var : $_REQUEST;
			
			if($arg) $res[] = $arg; // extra API options 

			$res[] = '<item>';

			$res[] = '<index>'.urlencode(sanitize_text_field($r['edit']['index'])).'</index>';
			($r['edit']['uid']) ? $res[] = '<id>'.urlencode(sanitize_text_field($r['edit']['uid'])).'</id>' : $this->error('editPax failed. edit element "uid" is empty', 1);
			($r['edit']['date']) ? $res[] = '<date>'.urlencode(sanitize_text_field($r['edit']['date'])).'</date>' : $this->error('editPax failed. edit element "date" is empty', 1);
			isset($r['edit']['book_time']) ?  $res[] = '<book_time>' . urlencode($r['edit']['book_time']) . '</book_time>' : '';
			isset($r['edit']['package']) ? $res[] = '<package>' . urlencode($r['edit']['package']) . '</package>' : '';
			isset($r['edit']['cart_package_uid']) ? $res[] = '<cart_package_uid>' . urlencode($r['edit']['cart_package_uid']) . '</cart_package_uid>' : '';

			if (isset($r['edit']['adult_num'])) {
				if ($r['edit']['adult_num'] != 0) {
					$res[] = '<adult_num>'.urlencode(sanitize_text_field($r['edit']['adult_num'])).'</adult_num>';
				} else {
					$res[] = '<adult_num>0</adult_num>';
				}
			}
			if (isset($r['edit']['child_num'])) {
				if ($r['edit']['child_num'] != 0) {
					$res[] = '<child_num>'.urlencode(sanitize_text_field($r['edit']['child_num'])).'</child_num>';
				} else {
					$res[] = '<child_num>0</child_num>';
				}
			}
			if (isset($r['edit']['senior_num'])) {
				if ($r['edit']['senior_num'] != 0) {
					$res[] = '<senior_num>'.urlencode(sanitize_text_field($r['edit']['senior_num'])).'</senior_num>';
				} else {
					$res[] = '<senior_num>0</senior_num>';
				}
			}
			if (isset($r['edit']['price4_num'])) {
				if ($r['edit']['price4_num'] != 0) {
					$res[] = '<price4_num>'.urlencode(sanitize_text_field($r['edit']['price4_num'])).'</price4_num>';
				} else {
					$res[] = '<price4_num>0</price4_num>';
				}
			}
			if (isset($r['edit']['price5_num'])) {
				if ($r['edit']['price5_num'] != 0) {
					$res[] = '<price5_num>'.urlencode(sanitize_text_field($r['edit']['price5_num'])).'</price5_num>';
				} else {
					$res[] = '<price5_num>0</price5_num>';
				}
			}
			if (isset($r['edit']['price6_num'])) {
				if ($r['edit']['price6_num'] != 0) {
					$res[] = '<price6_num>'.urlencode(sanitize_text_field($r['edit']['price6_num'])).'</price6_num>';
				} else {
					$res[] = '<price6_num>0</price6_num>';
				}
			}
			if (isset($r['edit']['price7_num'])) {
				if ($r['edit']['price7_num'] != 0) {
					$res[] = '<price7_num>'.urlencode(sanitize_text_field($r['edit']['price7_num'])).'</price7_num>';
				} else {
					$res[] = '<price7_num>0</price7_num>';
				}
			}
			if (isset($r['edit']['price8_num'])) {
				if ($r['edit']['price8_num'] != 0) {
					$res[] = '<price8_num>'.urlencode(sanitize_text_field($r['edit']['price8_num'])).'</price8_num>';
				} else {
					$res[] = '<price8_num>0</price8_num>';
				}
			}
			if (isset($r['edit']['price9_num'])) {
				if ($r['edit']['price9_num'] != 0) {
					$res[] = '<price9_num>'.urlencode(sanitize_text_field($r['edit']['price9_num'])).'</price9_num>';
				} else {
					$res[] = '<price9_num>0</price9_num>';
				}
			}

			$res[] = '</item>';

			$request = implode('', $res);

			$this->cartRequest('update', $request);
			$this->XMLRequest('update_cart', $this->cart_api_request);

			if ($this->cart_status){ return $this->cart_status;}
		}

		// -----------------------------------------------
		// Create an add request from step_one to the CART
		// -----------------------------------------------
		function addCart($var=null, $arg=null) {
			$r = ($var) ? $var : $_REQUEST;

			if($arg) $res[] = $arg; // extra API options

			foreach((array) $r['add'] as $k => $v) {

				$res[] = '<item>';

					($v['uid']) ? $res[] = '<id>' . urlencode(sanitize_text_field($v['uid'])) . '</id>' : $this->error('addCart failed. book element "uid" is empty', 1);
					($v['date']) ? $res[] = '<date>' . urlencode(sanitize_text_field($v['date'])) . '</date>' : $this->error('addCart failed. book element "date" is empty', 1);
					isset($v['book_time']) ?  $res[] = '<book_time>' . urlencode(sanitize_text_field($v['book_time'])) . '</book_time>' : '';
					isset($v['package']) ? $res[] = '<package>' . urlencode(sanitize_text_field($v['package'])) . '</package>' : '';
					isset($v['cart_package_uid']) ? $res[] = '<cart_package_uid>' . urlencode(sanitize_text_field($v['cart_package_uid'])) . '</cart_package_uid>' : '';

					isset($v['adult_num']) ? $res[] = '<adult_num>' . urlencode(sanitize_text_field($v['adult_num'])) . '</adult_num>' : '';
					isset($v['child_num']) ? $res[] = '<child_num>' . urlencode(sanitize_text_field($v['child_num'])) . '</child_num>' : '';
					isset($v['senior_num']) ? $res[] = '<senior_num>' . urlencode(sanitize_text_field($v['senior_num'])) . '</senior_num>' : '';

					isset($v['price4_num']) ? $res[] = '<price4_num>' . urlencode(sanitize_text_field($v['price4_num'])) . '</price4_num>' : '';
					isset($v['price5_num']) ? $res[] = '<price5_num>' . urlencode(sanitize_text_field($v['price5_num'])) . '</price5_num>' : '';
					isset($v['price6_num']) ? $res[] = '<price6_num>' . urlencode(sanitize_text_field($v['price6_num'])) . '</price6_num>' : '';
					isset($v['price7_num']) ? $res[] = '<price7_num>' . urlencode(sanitize_text_field($v['price7_num'])) . '</price7_num>' : '';
					isset($v['price8_num']) ? $res[] = '<price8_num>' . urlencode(sanitize_text_field($v['price8_num'])) . '</price8_num>' : '';
					isset($v['price9_num']) ? $res[] = '<price9_num>' . urlencode(sanitize_text_field($v['price9_num'])) . '</price9_num>' : '';

				$res[] = '</item>';

			}

			if (REZGO_LITE_CONTAINER) {

				// include promo and refid if one is set to the add request so it shows on order page
				if ($r['trigger_code']) $res[] = '<trigger_code>'.$r['trigger_code'].'</trigger_code>';
				if ($r['refid']) $res[] = '<refid>'.$r['refid'].'</refid>';
				if ($r['booking_source']) $res[] = '<booking_source>'.$r['booking_source'].'</booking_source>';
				
			} else {

				// include promo and refid if one is set to the add request so it shows on order page
				isset($_COOKIE['rezgo_promo']) ? $res[] = '<trigger_code>'.$_COOKIE['rezgo_promo'].'</trigger_code>' : '';
				isset($_COOKIE['rezgo_refid_val']) ? $res[] = '<refid>'.$_COOKIE['rezgo_refid_val'].'</refid>' : '';
				isset($_COOKIE['rezgo_lead']) ? $res[] = '<booking_source>'.$_COOKIE['rezgo_lead'].'</booking_source>' : '';
			}

			$request = implode('', $res);

			// var_dump($request);
			$this->cartRequest('add', $request);
			$this->XMLRequest('add_cart', $this->cart_api_request);

			if ($this->cart_status) return $this->cart_status;

			if ( REZGO_LITE_CONTAINER && !$this->cartInUri() ) {
				$uri = $_SERVER['HTTP_HOST'];
				$lite_uri = str_replace('-lite', '-lite-'.$this->cart_token, $uri);
				return $lite_uri;
			}
			
		}
		
		// -------------------------------------------------
		// Create an update request for referrer to the CART
		// --> needed for TMT gateway on the widget
		// -------------------------------------------------
		function updateReferrer( $referrer='' ) {

			$res[] = '<referrer>'.$referrer.'</referrer>';

			$request = implode('' , $res);

			$this->referrer = $referrer;
			$this->cartRequest('update', $request);
			$this->XMLRequest('update_cart', $this->cart_api_request);
		}

		// -----------------------------------------------------
		// Create an update request for trigger_code to the CART
		// -----------------------------------------------------
		function updatePromo( $promo_code='' ) {

			// add trigger code
			$res[] = '<trigger_code>'.$promo_code.'</trigger_code>';

			$request = implode('' , $res);

			// var_dump($request);
			$this->promo_code = $promo_code;
			$this->cartRequest('update', $request);
			$this->XMLRequest('update_cart', $this->cart_api_request);
		}

		// -------------------------------------------------
		// Create an update request for refid to the CART
		// -------------------------------------------------
		function updateRefId( $refid='' ) {

			$res[] = '<refid>'.$refid.'</refid>';

			$request = implode('' , $res);

			$this->refid = $refid;
			$this->cartRequest('update', $request);
			$this->XMLRequest('update_cart', $this->cart_api_request);
		}

		// -------------------------------------------------
		// Create an update request for booking_source to the CART
		// -------------------------------------------------
		function updateBookingSource( $booking_source='' ) {

			$res[] = '<booking_source>'.$booking_source.'</booking_source>';

			$request = implode('' , $res);
			
			$this->booking_source = $booking_source;
			$this->cartRequest('update', $request);
			$this->XMLRequest('update_cart', $this->cart_api_request);
		}

		// ------------------------------------------------------------------------------
		// Create an update request from guest information to the CART
		// ------------------------------------------------------------------------------
		function updateCart($var=null, $arg=null) {
			$r = ($var) ? $var : $_REQUEST;

			if($arg) $res[] = $arg; // extra API options

			// update lead passenger details
			$res[] = '<email>'.sanitize_email($r['lead_passenger_email']).'</email>';
			$res[] = '<payment>';
				$res[] = '<tour_first_name>'.sanitize_text_field($r['lead_passenger_first_name']).'</tour_first_name>';
				$res[] = '<tour_last_name>'.sanitize_text_field($r['lead_passenger_last_name']).'</tour_last_name>';
				$res[] = '<tour_email_address>'.sanitize_text_field($r['lead_passenger_email']).'</tour_email_address>';
                isset($r['lead_passenger_phone']) ? $res[] = '<tour_phone_number>'.sanitize_text_field($r['lead_passenger_phone']).'</tour_phone_number>' : '';
			$res[] = '</payment>';

			$c = 1;
			foreach($r['booking'] as $b) {

				$res[] = '<item>';

				$res[] = '<index>'.urlencode(sanitize_text_field($b['index'])).'</index>';
				($b['uid']) ? $res[] = '<id>'.urlencode(sanitize_text_field($b['uid'])).'</id>' : $this->error('updateCart failed. book element "uid" is empty', 1);
				($b['date']) ? $res[] = '<date>'.urlencode(sanitize_text_field($b['date'])).'</date>' : $this->error('updateCart failed. book element "date" is empty', 1);
				isset($b['package']) ? $res[] = '<package>' . urlencode(sanitize_text_field($b['package'])) . '</package>' : '';
				isset($b['cart_package_uid']) ? $res[] = '<cart_package_uid>' . urlencode(sanitize_text_field($b['cart_package_uid'])) . '</cart_package_uid>' : '';

				if($b['tour_group']) {

					$res[] = '<tour_group>';

					foreach((array) $b['tour_group'] as $k => $v) {
						foreach((array) $v as $sk => $sv) {
							$res[] = '<'.sanitize_text_field($k).'>';
								$res[] = '<num>'.sanitize_text_field($sk).'</num>';

								$res[] = '<first_name>'.urlencode(sanitize_text_field($sv['first_name'])).'</first_name>';
								$res[] = '<last_name>'.urlencode(sanitize_text_field($sv['last_name'])).'</last_name>';
								$res[] = '<phone>'.urlencode(sanitize_text_field($sv['phone'])).'</phone>';
								$res[] = '<email>'.urlencode(sanitize_email($sv['email'])).'</email>';

							if(is_array($sv['forms'])) {
								$res[] = '<forms>';

								foreach((array) $sv['forms'] as $fk => $fv) {
									$res[] = '<form>';
										if (REZGO_WORDPRESS) {
											$res[] = '<num>'.sanitize_text_field($fk).'</num>';
											if(is_array($fv)) { // for multiselects
												foreach($fv as $key => $val){
													// htmlentities() needed for WP to send to DB
													$fv[$key] = urlencode(htmlentities(stripslashes($val), ENT_QUOTES));
												}
												$fv = implode(", ", $fv);
												$res[] = '<value>'.$fv.'</value>';
											} else {
												// htmlentities() needed for WP to send to DB
												$res[] = '<value>'.urlencode(htmlentities(stripslashes(sanitize_text_field($fv)), ENT_QUOTES)).'</value>';
											}
										} else {
											if(is_array($fv)) $fv = implode(", ", $fv); // for multiselects
											$res[] = '<num>'.$fk.'</num>';
											$res[] = '<value>'.urlencode(stripslashes($fv)).'</value>';
										}
									$res[] = '</form>';
								}

								$res[] = '</forms>';
							}

							$res[] = '</'.$k.'>';

						}
					}

					$res[] = '</tour_group>';

				}

				// ---- NEW cart request format for tour_forms
				if($b['tour_forms']) {
					$res[] = '<primary_forms>';

					foreach((array) $b['tour_forms'] as $k => $v) {
						$res[] ='<form>';
							$res[] = '<num>'.$k.'</num>';
							if (REZGO_WORDPRESS) {
								if(is_array($v)) { // for multiselects
									foreach($v as $key => $val){
										// htmlentities() needed for WP to send to DB
										$v[$key] = urlencode(htmlentities(stripslashes(sanitize_text_field($val)), ENT_QUOTES));
									}
									$v = implode(", ", $v);
									$res[] = '<value>'.$v.'</value>';
								} else {
									// htmlentities() needed for WP to send to DB
									$res[] = '<value>'.urlencode(htmlentities(stripslashes(sanitize_text_field($v)), ENT_QUOTES)).'</value>';
								}
							} else {
								if(is_array($v)) $v = implode(", ", $v); // for multiselects
								$res[] = '<num>'.$k.'</num>';
								$res[] = '<value>'.urlencode(stripslashes($v)).'</value>';
							}
						$res[] = '</form>';
					}

					$res[] = '</primary_forms>';
				}

				if($b['pickup']) {

					$pickup_split = explode("-", stripslashes($b['pickup']));

					$res[] = '<pickup>'.sanitize_text_field($pickup_split[0]).'</pickup>';
					if(isset($pickup_split[1])) {
						$res[] = '<pickup_source>'.sanitize_text_field($pickup_split[1]).'</pickup_source>';
					} else {
						$res[] = '<pickup_source></pickup_source>';
					}

				} else { $res[] = '<pickup>0</pickup><pickup_source></pickup_source>'; }

				($b['waiver']) ? $res[] = '<waiver>'.str_replace('data:image/png;base64,', '', $r['waiver']).'</waiver>' : 0;

				$res[] = '</item>';

			} // cart loop

			$request = implode('', $res);

			$this->cartRequest('update', $request);
			$this->XMLRequest('update_cart', $this->cart_api_request);

		}

		// ------------------------------------------------------------------------------
		// DEBUG UPDATE REQUEST
		// ------------------------------------------------------------------------------
		function updateDebug($var=null, $arg=null) {
			$r = ($var) ? $var : $_REQUEST;

			if($arg) $res[] = $arg; // extra API options

			$res[] = '<email>'.sanitize_email($r['lead_passenger_email']).'</email>';
			$res[] = '<payment>';
			$res[] = '<tour_first_name>'.sanitize_text_field($r['lead_passenger_first_name']).'</tour_first_name>';
			$res[] = '<tour_last_name>'.sanitize_text_field($r['lead_passenger_last_name']).'</tour_last_name>';
			$res[] = '<tour_email_address>'.sanitize_text_field($r['lead_passenger_email']).'</tour_email_address>';
			$res[] = '</payment>';

			$c = 1;
			foreach($r['booking'] as $b) {

				$res[] = '<item>';

				$res[] = '<index>'.urlencode(sanitize_text_field($b['index'])).'</index>';
				($b['uid']) ? $res[] = '<id>'.urlencode(sanitize_text_field($b['uid'])).'</id>' : $this->error('updateCart failed. book element "uid" is empty', 1);
				($b['date']) ? $res[] = '<date>'.urlencode(sanitize_text_field($b['date'])).'</date>' : $this->error('updateCart failed. book element "date" is empty', 1);
				($b['package']) ? $res[] = '<package>' . urlencode(sanitize_text_field($b['package'])) . '</package>' : '';
				($b['cart_package_uid']) ? $res[] = '<cart_package_uid>' . urlencode(sanitize_text_field($b['cart_package_uid'])) . '</cart_package_uid>' : '';

				if($b['tour_group']) {

					$res[] = '<tour_group>';

					foreach((array) $b['tour_group'] as $k => $v) {
						foreach((array) $v as $sk => $sv) {
							$res[] = '<'.sanitize_text_field($k).'>';
								$res[] = '<num>'.sanitize_text_field($sk).'</num>';

								$res[] = '<first_name>'.urlencode(sanitize_text_field($sv['first_name'])).'</first_name>';
								$res[] = '<last_name>'.urlencode(sanitize_text_field($sv['last_name'])).'</last_name>';
								$res[] = '<phone>'.urlencode(sanitize_text_field($sv['phone'])).'</phone>';
								$res[] = '<email>'.urlencode(sanitize_email($sv['email'])).'</email>';

							if(is_array($sv['forms'])) {
								$res[] = '<forms>';

								foreach((array) $sv['forms'] as $fk => $fv) {
									$res[] = '<form>';
										if (REZGO_WORDPRESS) {
											$res[] = '<num>'.sanitize_text_field($fk).'</num>';
											if(is_array($fv)) { // for multiselects
												foreach($fv as $key => $val){
													// htmlentities() needed for WP to send to DB
													$fv[$key] = urlencode(htmlentities(stripslashes($val), ENT_QUOTES));
												}
												$fv = implode(", ", $fv);
												$res[] = '<value>'.$fv.'</value>';
											} else {
												// htmlentities() needed for WP to send to DB
												$res[] = '<value>'.urlencode(htmlentities(stripslashes(sanitize_text_field($fv)), ENT_QUOTES)).'</value>';
											}
										} else {
											if(is_array($fv)) $fv = implode(", ", $fv); // for multiselects
											$res[] = '<num>'.$fk.'</num>';
											$res[] = '<value>'.urlencode(stripslashes(sanitize_text_field($fv))).'</value>';
										}
									$res[] = '</form>';
								}

								$res[] = '</forms>';
							}
							$res[] = '</'.$k.'>';
						}
					}
					$res[] = '</tour_group>';
				}
				if($b['tour_forms']) {
					$res[] = '<primary_forms>';

					foreach((array) $b['tour_forms'] as $k => $v) {
						$res[] ='<form>';
						$res[] = '<num>'.$k.'</num>';
							if (REZGO_WORDPRESS) {
								if(is_array($v)) { // for multiselects
									foreach($v as $key => $val){
										// htmlentities() needed for WP to send to DB
										$v[$key] = urlencode(htmlentities(stripslashes(sanitize_text_field($val)), ENT_QUOTES));
									}
									$v = implode(", ", $v);
									$res[] = '<value>'.sanitize_text_field($v).'</value>';
								} else {
									// htmlentities() needed for WP to send to DB
									$res[] = '<value>'.urlencode(htmlentities(stripslashes(sanitize_text_field($v)), ENT_QUOTES)).'</value>';
								}
							} else {
								if(is_array($v)) $v = implode(", ", $v); // for multiselects
								$res[] = '<num>'.$k.'</num>';
								$res[] = '<value>'.urlencode(stripslashes(sanitize_text_value($v))).'</value>';
							}
						$res[] = '</form>';
					}

					$res[] = '</primary_forms>';
				}

				if($b['pickup']) {

					$pickup_split = explode("-", stripslashes($b['pickup']));

					$res[] = '<pickup>'.sanitize_text_field($pickup_split[0]).'</pickup>';
					if(isset($pickup_split[1])) {
						$res[] = '<pickup_source>'.sanitize_text_field($pickup_split[1]).'</pickup_source>';
					} else {
						$res[] = '<pickup_source></pickup_source>';
					}

				} else { $res[] = '<pickup>0</pickup><pickup_source></pickup_source>'; }

				($b['waiver']) ? $res[] = '<waiver>'.str_replace('data:image/png;base64,', '', $r['waiver']).'</waiver>' : 0;

				$res[] = '</item>';

			} // cart loop

			$request = implode('', $res);

			print_r($request);
		}

		// ------------------------------------------------------------------------------
		// DEBUG COMMIT REQUEST
		// ------------------------------------------------------------------------------
		function commitDebug($var=null, $arg=null) {
			$r = ($var) ? $var : $_REQUEST;

			if($arg) $res[] = $arg; // extra API options

			if(!$r['cart_token']) $this->error('sendBookingOrder failed. Cart token was not found', 1);

			$res[] = '<token>'.sanitize_text_field($this->cart_token).'</token>';

			$res[] = '<payment>';

			($r['trigger_code']) ? $res[] = '<trigger_code>'.sanitize_text_field($r['trigger_code']).'</trigger_code>' : 0;

			($r['tour_first_name']) ? $res[] = '<tour_first_name>'.sanitize_text_field($r['tour_first_name']).'</tour_first_name>' : 0;
			($r['tour_last_name']) ? $res[] = '<tour_last_name>'.sanitize_text_field($r['tour_last_name']).'</tour_last_name>' : 0;
			($r['tour_address_1']) ? $res[] = '<tour_address_1>'.sanitize_text_field($r['tour_address_1']).'</tour_address_1>' : 0;
			($r['tour_address_2']) ? $res[] = '<tour_address_2>'.sanitize_text_field($r['tour_address_2']).'</tour_address_2>' : 0;
			($r['tour_city']) ? $res[] = '<tour_city>'.sanitize_text_field($r['tour_city']).'</tour_city>' : 0;
			($r['tour_stateprov']) ? $res[] = '<tour_stateprov>'.sanitize_text_field($r['tour_stateprov']).'</tour_stateprov>' : 0;
			($r['tour_country']) ? $res[] = '<tour_country>'.sanitize_text_field($r['tour_country']).'</tour_country>' : 0;
			($r['tour_postal_code']) ? $res[] = '<tour_postal_code>'.sanitize_text_field($r['tour_postal_code']).'</tour_postal_code>' : 0;
			($r['tour_phone_number']) ? $res[] = '<tour_phone_number>'.sanitize_text_field($r['tour_phone_number']).'</tour_phone_number>' : 0;
			($r['tour_email_address']) ? $res[] = '<tour_email_address>'.sanitize_email($r['tour_email_address']).'</tour_email_address>' : 0;
			($r['sms']) ? $res[] = '<sms>'.sanitize_text_field($r['sms']).'</sms>' : 0;
			($r['tip']) ? $res[] = '<tip>'.sanitize_text_field($r['tip']).'</tip>' : 0;

			($r['payment_method']) ? $res[] = '<payment_method>'.urlencode(stripslashes(sanitize_text_field($r['payment_method']))).'</payment_method>' : 0;

			($r['payment_method_add']) ? $res[] = '<payment_method_add>'.urlencode(stripslashes(sanitize_text_field($r['payment_method_add']))).'</payment_method_add>' : 0;

			($r['payment_method'] == 'Credit Cards' && $r['tour_card_token']) ? $res[] = '<tour_card_token>'.sanitize_text_field($r['tour_card_token']).'</tour_card_token>' : 0;
			($r['payment_method'] == 'PayPal' && $r['paypal_token']) ? $res[] = '<paypal_token>'.sanitize_text_field($r['paypal_token']).'</paypal_token>' : 0;
			($r['payment_method'] == 'PayPal' && $r['paypal_payer_id']) ? $res[] = '<paypal_payer_id>'.($r['paypal_payer_id']).'</paypal_payer_id>' : 0;

			($r['payment_method'] == 'Credit Cards' && $r['payment_id']) ? $res[] = '<payment_id>'.sanitize_text_field($r['payment_id']).'</payment_id>' : 0;

			($r['agree_terms']) ? $res[] = '<agree_terms>'.sanitize_text_field($r['agree_terms']).'</agree_terms>' : 0;

			($r['review_sent']) ? $res[] = '<review_sent>'.sanitize_text_field($r['review_sent']).'</review_sent>' : 0;

			($r['marketing_consent']) ? $res[] = '<marketing_consent>'.sanitize_text_field($r['marketing_consent']).'</marketing_consent>' : 0;

			// add in external elements
			($this->refid) ? $res[] = '<refid>'.$this->refid.'</refid>' : 0;
			($this->promo_code) ? $res[] = '<trigger_code>'.$this->promo_code.'</trigger_code>' : 0;
			($this->booking_source) ? $res[] = '<booking_source>'.$this->booking_source.'</booking_source>' : 0;

			// add in requesting IP
			$res[] = '<ip>'.sanitize_text_field($_SERVER['REMOTE_ADDR']).'</ip>';

			// GIFT-CARD
			$res[] = '<expected>'.sanitize_text_field($r['expected']).'</expected>';
			($r['gift_card']) ? $res[] = '<gift_card>'.sanitize_text_field($r['gift_card']).'</gift_card>' : 0;

			$res[] = '</payment>';

			// ticketguardian
			($r['tour_tg_insurance_coverage']) ? $res[] = '<tg>'.$r['tour_tg_insurance_coverage'].'</tg>' : 0;

			($r['waiver']) ? $res[] = '<waiver>'.sanitize_text_field($r['waiver']).'</waiver>' : 0;
			
			// include waiver initials
			$res[] = '<initials>';
				$initial_index = 0;
				foreach ($r as $key => $value) {
					if (strpos( $key, 'waiver_url_'.REZGO_CID ) !== false) {
						$index = substr($key, -1);
						$res[] = '<initial_'.$index.'>'.sanitize_text_field($r[$key]). '</initial_'.$index.'>';
						$initial_index++;
					}
				}
			$res[] = '</initials>';

			$request = implode('', $res);

			print_r($request);
		}

		// complete booking process
		function commitCart($var=null, $arg=null) {

			$r = ($var) ? $var : $_REQUEST;

			if($arg) $res[] = $arg; // extra API options

			$res[] = '<token>'.sanitize_text_field($this->cart_token).'</token>';

			$res[] = '<payment>';

				($r['trigger_code']) ? $res[] = '<trigger_code>'.$r['trigger_code'].'</trigger_code>' : 0;

				($r['tour_first_name']) ? $res[] = '<tour_first_name>'.sanitize_text_field($r['tour_first_name']).'</tour_first_name>' : 0;
				($r['tour_last_name']) ? $res[] = '<tour_last_name>'.sanitize_text_field($r['tour_last_name']).'</tour_last_name>' : 0;
				($r['tour_address_1']) ? $res[] = '<tour_address_1>'.sanitize_text_field($r['tour_address_1']).'</tour_address_1>' : 0;
				($r['tour_address_2']) ? $res[] = '<tour_address_2>'.sanitize_text_field($r['tour_address_2']).'</tour_address_2>' : 0;
				($r['tour_city']) ? $res[] = '<tour_city>'.sanitize_text_field($r['tour_city']).'</tour_city>' : 0;
				($r['tour_stateprov']) ? $res[] = '<tour_stateprov>'.sanitize_text_field($r['tour_stateprov']).'</tour_stateprov>' : 0;
				($r['tour_country']) ? $res[] = '<tour_country>'.sanitize_text_field($r['tour_country']).'</tour_country>' : 0;
				($r['tour_postal_code']) ? $res[] = '<tour_postal_code>'.sanitize_text_field($r['tour_postal_code']).'</tour_postal_code>' : 0;
				($r['tour_phone_number']) ? $res[] = '<tour_phone_number>'.sanitize_text_field($r['tour_phone_number']).'</tour_phone_number>' : 0;
				($r['tour_email_address']) ? $res[] = '<tour_email_address>'.sanitize_email($r['tour_email_address']).'</tour_email_address>' : 0;
				($r['sms']) ? $res[] = '<sms>'.sanitize_text_field($r['sms']).'</sms>' : 0;
				($r['tip']) ? $res[] = '<tip>'.sanitize_text_field($r['tip']).'</tip>' : 0;

				($r['payment_method']) ? $res[] = '<payment_method>'.urlencode(stripslashes(sanitize_text_field($r['payment_method']))).'</payment_method>' : 0;

				($r['payment_method_add']) ? $res[] = '<payment_method_add>'.urlencode(stripslashes(sanitize_text_field($r['payment_method_add']))).'</payment_method_add>' : 0;

				($r['payment_method'] == 'Credit Cards' && $r['tour_card_token']) ? $res[] = '<tour_card_token>'.sanitize_text_field($r['tour_card_token']).'</tour_card_token>' : 0;
				($r['payment_method'] == 'PayPal' && $r['paypal_token']) ? $res[] = '<paypal_token>'.sanitize_text_field($r['paypal_token']).'</paypal_token>' : 0;
				($r['payment_method'] == 'PayPal' && $r['paypal_payer_id']) ? $res[] = '<paypal_payer_id>'.($r['paypal_payer_id']).'</paypal_payer_id>' : 0;

				($r['payment_method'] == 'Credit Cards' && $r['payment_id']) ? $res[] = '<payment_id>'.sanitize_text_field($r['payment_id']).'</payment_id>' : 0;

				($r['agree_terms']) ? $res[] = '<agree_terms>'.sanitize_text_field($r['agree_terms']).'</agree_terms>' : 0;

				($r['review_sent']) ? $res[] = '<review_sent>'.sanitize_text_field($r['review_sent']).'</review_sent>' : 0;

				($r['marketing_consent']) ? $res[] = '<marketing_consent>'.sanitize_text_field($r['marketing_consent']).'</marketing_consent>' : 0;

				// add in external elements
				($this->refid) ? $res[] = '<refid>'.$this->refid.'</refid>' : 0;
				($this->promo_code) ? $res[] = '<trigger_code>'.$this->promo_code.'</trigger_code>' : 0;
				($this->booking_source) ? $res[] = '<booking_source>'.$this->booking_source.'</booking_source>' : 0;

				// add in requesting IP
				$res[] = '<ip>'.sanitize_text_field($_SERVER['REMOTE_ADDR']).'</ip>';

				// GIFT-CARD
				$res[] = '<expected>'.sanitize_text_field($r['expected']).'</expected>';
				($r['gift_card']) ? $res[] = '<gift_card>'.sanitize_text_field($r['gift_card']).'</gift_card>' : 0;

			$res[] = '</payment>';

			($r['waiver']) ? $res[] = '<waiver>'.sanitize_text_field($r['waiver']).'</waiver>' : 0;

			// include waiver initials
			$res[] = '<initials>';
				$initial_index = 0;
				foreach ($r as $key => $value) {
					if (strpos( $key, 'waiver_url_'.REZGO_CID ) !== false) {
						$index = substr($key, -1);
						$res[] = '<initial_'.$index.'>'.sanitize_text_field($r[$key]). '</initial_'.$index.'>';
						$initial_index++;
					}
				}
			$res[] = '</initials>';

			$request = implode('', $res);

			$this->cartRequest('update', $request);

			$this->XMLRequest('update_cart', $this->cart_api_request);
			$this->XMLRequest('commitOrder', $request, 1);

			return $this->commit_response;

		}

		function removeCart($index='', $id='', $date='', $cart_package_uid=''){
			$item = '<item>';
				$item .= '<index>'.$index.'</index>';
				$item .= '<id>'.$id.'</id>';
				$item .= '<date>'.$date.'</date>';	
				$item .= $cart_package_uid ? '<cart_package_uid>'.$cart_package_uid.'</cart_package_uid>' : '';	
			$item .= '</item>';

			$this->cartRequest('remove', $item);
			$this->XMLRequest('remove_cart', $this->cart_api_request);
		}

		function destroyCart(){
			$this->cartRequest('destroy');
			$this->XMLRequest('destroy_cart', $this->cart_api_request);
			$this->setCookie('rezgo_cart_token_'.REZGO_CID, '');
		}

		function saveLeadPassenger($var=null) {
			$r = ($var) ? $var : $_REQUEST;

			$res[] = ($r['lead_passenger_email']) ? '<email>'.sanitize_email($r['lead_passenger_email']).'</email>' : '';

			if ($r['lead_passenger_first_name'] || $r['lead_passenger_last_name']) {
				$res[] = '<payment>';
					$res[] = '<tour_first_name>'.sanitize_text_field($r['lead_passenger_first_name']).'</tour_first_name>';
					$res[] = '<tour_last_name>'.sanitize_text_field($r['lead_passenger_last_name']).'</tour_last_name>';
				$res[] = '</payment>';
			}

			$request = implode('', $res);
			$this->cartRequest('update', $request);
			$this->XMLRequest('update_cart', $this->cart_api_request);
		}

		function getLeadPassenger(){
			$lead_passenger = array();
			$lead_passenger['email'] = (string) $this->lead_passenger_email;
			$lead_passenger['first_name'] = (string) $this->lead_passenger_first_name;
			$lead_passenger['last_name'] = (string) $this->lead_passenger_last_name;

			return $lead_passenger;
		}

		function cartInUri(){
			$uri = $_SERVER['HTTP_HOST'];
			if((strpos ($uri, 'lite-') !== false) && defined('REZGO_LITE_CART')){
				return true;
			} else {
				return false;
			}
		}

		// FORMAT CARD
		function cardFormat($num) {
			$cc = str_replace(array('-', ' '), '', $num);
			$cc_length = strlen($cc);
			$new_card = substr($cc, -4);

			for ($i = $cc_length - 5; $i >= 0; $i--) {
				if((($i + 1) - $cc_length) % 4 == 0) {
					$new_card = '-' . $new_card;
				}

				$new_card = $cc[$i] . $new_card;
			}

			return $new_card;
		}

				// ------------------------------------------------------------------------------
		// BOOKING EDIT -- get booking edit status
		// ------------------------------------------------------------------------------

		function getEditStatus($trans_num){

			$res[] = '<edit>';
				$res[] = '<action>get_edit_status</action>';
				$res[] = '<trans_num>'.$trans_num.'</trans_num>';
			$res[] = '</edit>';

			$request = implode('', $res);

			$this->XMLRequest('edit_booking', $request);

			return $this->booking_edit_response;

			// return $this->booking_edit_response ? 1 : 0;
		}

		// ------------------------------------------------------------------------------
		// BOOKING EDIT -- update pax count
		// ------------------------------------------------------------------------------

		function updateBookingPax($var=null, $action=null) {
			$r = ($var) ? $var : $_REQUEST;

			$res[] = '<edit>';

				$res[] = '<action>'.$action.'</action>';
				$res[] = '<trans_num>'.$r['trans_num'].'</trans_num>';

				$res[] = '<booking_date>'.$r['booking_date'].'</booking_date>';
				($r['booking_time']) ? $res[] = '<booking_time>'.$r['booking_time'].'</booking_time>' : 0;
				$res[] = '<item_id>'.$r['item_id'].'</item_id>';

					$res[] = $r['select_guest_type'] ? '<'.$r['select_guest_type'].'>' . 1 . '</'.$r['select_guest_type'].'>' : '';

					$res[] = $r['pax_id'] ? '<pax_id>'.sanitize_text_field($r['pax_id']).'</pax_id>' : '';
					$res[] = $r['pax_type'] ? '<pax_type>'.sanitize_text_field($r['pax_type']).'</pax_type>' : '';
					$res[] = $r['booking']['first_name'] ? '<first_name>'.sanitize_text_field($r['booking']['first_name']).'</first_name>' : '';
					$res[] = $r['booking']['last_name'] ? '<last_name>'.sanitize_text_field($r['booking']['last_name']).'</last_name>' : '';
					$res[] = $r['booking']['phone'] ? '<phone>'.sanitize_text_field($r['booking']['phone']).'</phone>' : '';
					$res[] = $r['booking']['email'] ? '<email>'.sanitize_email($r['booking']['email']).'</email>' : '';

					if($r['booking']['forms']) {
						$res[] = '<forms>';
														
						foreach((array) $r['booking']['forms'] as $fk => $fv) {
							$res[] = '<form>';
								if(is_array($fv)) $fv = implode(", ", $fv); // for multiselects
								$res[] = '<num>'.$fk.'</num>';
								$res[] = '<value>'.urlencode(stripslashes($fv)).'</value>';
							$res[] = '</form>';
						}
						$res[] = '</forms>';
						
					}

			$res[] = '</edit>';

			$request = implode('', $res);

			$this->XMLRequest('edit_booking', $request);

			return $this->booking_edit_response;
		}

		// ------------------------------------------------------------------------------
		// BOOKING EDIT -- update primary forms
		// ------------------------------------------------------------------------------

		function updateBookingPrimaryForm($var=null, $arg=null) {
			$r = ($var) ? $var : $_REQUEST;

			if($arg) $res[] = $arg; // extra API options

			$res[] = '<edit>';

				$res[] = '<action>update_primary_form</action>';
				$res[] = '<trans_num>'.$r['trans_num'].'</trans_num>';

				$res[] = '<booking_date>'.$r['booking_date'].'</booking_date>';
				($r['booking_time']) ? $res[] = '<booking_time>'.$r['booking_time'].'</booking_time>' : 0;
				$res[] = '<item_id>'.$r['item_id'].'</item_id>';

				foreach($r['booking'] as $b) {
					if($b['tour_forms']) {
						$res[] = '<primary_forms>';
					
						foreach((array) $b['tour_forms'] as $k => $v) {
							$res[] ='<form>';
								if(is_array($v)) $v = implode(", ", $v); // for multiselects
								$res[] = '<num>'.$k.'</num>';
								$res[] = '<value>'.urlencode(stripslashes($v)).'</value>';
							$res[] = '</form>';
						}
					
						$res[] = '</primary_forms>';
					}

					if($b['pickup']) {
						
						$pickup_split = explode("-", stripslashes($b['pickup']));

						$res[] = '<pickup>'.sanitize_text_field($pickup_split[0]).'</pickup>';
						if(isset($pickup_split[1])) {
							$res[] = '<pickup_source>'.sanitize_text_field($pickup_split[1]).'</pickup_source>';
						} else {
							$res[] = '<pickup_source></pickup_source>';
						}
						
					} else { $res[] = '<pickup>0</pickup><pickup_source></pickup_source>'; }

				} // cart loop

			$res[] = '</edit>';

			$request = implode('', $res);

			$this->XMLRequest('edit_booking', $request);

			return $this->booking_edit_response;
		}

		// ------------------------------------------------------------------------------
		// BOOKING EDIT -- update group forms
		// ------------------------------------------------------------------------------

		function updateBookingGroupForm($var=null, $arg=null) {
			$r = ($var) ? $var : $_REQUEST;

			if($arg) $res[] = $arg; // extra API options

			$res[] = '<edit>';

				$res[] = '<action>update_group_form</action>';
				$res[] = '<trans_num>'.$r['trans_num'].'</trans_num>';

				$res[] = '<booking_date>'.$r['booking_date'].'</booking_date>';
				($r['booking_time']) ? $res[] = '<booking_time>'.$r['booking_time'].'</booking_time>' : 0;
				$res[] = '<item_id>'.$r['item_id'].'</item_id>';
				$res[] = '<pax_id>'.$r['pax_id'].'</pax_id>';
				$res[] = '<pax_type>'.$r['pax_type'].'</pax_type>';

				foreach($r['booking'] as $b) {
					if($b['tour_group']) {
						
						$res[] = '<tour_group>';
						
						foreach((array) $b['tour_group'] as $k => $v) {
							foreach((array) $v as $sk => $sv) {
								$res[] = '<'.sanitize_text_field($k).'>';
									$res[] = '<num>'.sanitize_text_field($sk).'</num>';
								
									$res[] = $sv['id'] ? '<id>'.sanitize_text_field($sv['id']).'</id>' : '';
									$res[] = '<first_name>'.urlencode(sanitize_text_field($sv['first_name'])).'</first_name>';
									$res[] = '<last_name>'.urlencode(sanitize_text_field($sv['last_name'])).'</last_name>';
									$res[] = '<phone>'.urlencode(sanitize_text_field($sv['phone'])).'</phone>';
									$res[] = '<email>'.urlencode(sanitize_email($sv['email'])).'</email>';
								
								if(is_array($sv['forms'])) {
									$res[] = '<forms>';
																
									foreach((array) $sv['forms'] as $fk => $fv) {
										$res[] = '<form>';
											if(is_array($fv)) $fv = implode(", ", $fv); // for multiselects
											$res[] = '<num>'.$fk.'</num>';
											$res[] = '<value>'.urlencode(stripslashes($fv)).'</value>';
										$res[] = '</form>';
									}
									$res[] = '</forms>';
								}
								$res[] = '</'.$k.'>';
							}		
						}
						$res[] = '</tour_group>';

					}
				}

			$res[] = '</edit>';

			$request = implode('', $res);

			$this->XMLRequest('edit_booking', $request);

			return $this->booking_edit_response;
		}
		

		// ------------------------------------------------------------------------------
		// BOOKING EDIT -- change date / time
		// ------------------------------------------------------------------------------

		function editDateTime($var=null, $arg=null) {
			$r = ($var) ? $var : $_REQUEST;

			if($arg) $res[] = $arg; // extra API options

			// extract requested date change
			foreach ($r['add'] as $k => $v) {
				$requested_item_id = $v['uid'];
				$requested_date = $v['date'];
				$requested_time = $v['book_time'];
			}

			$res[] = '<edit>';

				$res[] = '<action>edit_date_time</action>';
				$res[] = '<trans_num>'.$r['trans_num'].'</trans_num>';

				$res[] = '<booking_date>'.$r['booking_date'].'</booking_date>';
				($r['booking_time']) ? $res[] = '<booking_time>'.$r['booking_time'].'</booking_time>' : 0;
				$res[] = '<item_id>'.$r['item_id'].'</item_id>';

				$res[] = '<adult_price>'.($r['adult'] ?? 0).'</adult_price>';
				$res[] = '<child_price>'.($r['child'] ?? 0).'</child_price>';
				$res[] = '<senior_price>'.($r['senior'] ?? 0).'</senior_price>';
				$res[] = '<price4>'.($r['price4'] ?? 0).'</price4>';
				$res[] = '<price5>'.($r['price5'] ?? 0).'</price5>';
				$res[] = '<price6>'.($r['price6'] ?? 0).'</price6>';
				$res[] = '<price7>'.($r['price7'] ?? 0).'</price7>';
				$res[] = '<price8>'.($r['price8'] ?? 0).'</price8>';
				$res[] = '<price9>'.($r['price9'] ?? 0).'</price9>';

				($requested_date != $r['booking_date']) ? $res[] = '<requested_date>'.($r['booking_date'] == 'open' ? 'open' : $requested_date).'</requested_date>' : 0;
				($requested_time) ? $res[] = '<requested_time>'.$requested_time.'</requested_time>' : 0;

				$res[] = '<requested_item_id>'.$requested_item_id.'</requested_item_id>';

			$res[] = '</edit>';

			$request = implode('', $res);

			$this->XMLRequest('edit_booking', $request);

			return $this->booking_edit_response;
		}

		// ------------------------------------------------------------------------------
		// BOOKING EDIT -- change options
		// ------------------------------------------------------------------------------

		function changeBookingOption($var=null, $arg=null) {
			$r = ($var) ? $var : $_REQUEST;

			if($arg) $res[] = $arg; // extra API options

			// extract requested date change
			foreach ($r['add'] as $k => $v) {
				$requested_item_id = $v['uid'];
				$requested_date = $v['date'];
			}

			$res[] = '<edit>';

				$res[] = '<action>edit_date</action>';
				$res[] = '<trans_num>'.$r['trans_num'].'</trans_num>';

				$res[] = '<booking_date>'.$r['booking_date'].'</booking_date>';
				($r['booking_time']) ? $res[] = '<booking_time>'.$r['booking_time'].'</booking_time>' : 0;
				$res[] = '<item_id>'.$r['item_id'].'</item_id>';

				$res[] = '<requested_date>'.$requested_date.'</requested_date>';
				$res[] = '<requested_item_id>'.$requested_item_id.'</requested_item_id>';

			$res[] = '</edit>';

			$request = implode('', $res);

			$this->XMLRequest('edit_booking', $request);

			return $this->booking_edit_response;
		}
		
		// ------------------------------------------------------------------------------
		// BOOKING EDIT -- cancel booking
		// ------------------------------------------------------------------------------

		function cancelBooking($var=null, $arg=null) {
			$r = ($var) ? $var : $_REQUEST;
			
			if($arg) $res[] = $arg; // extra API options

			$res[] = '<edit>';

				$res[] = '<action>cancel_booking</action>';
				$res[] = '<trans_num>'.$r['trans_num'].'</trans_num>';

				$res[] = '<booking_date>'.$r['booking_date'].'</booking_date>';
				($r['booking_time']) ? $res[] = '<booking_time>'.$r['booking_time'].'</booking_time>' : 0;
				$res[] = '<item_id>'.$r['item_id'].'</item_id>';

			$res[] = '</edit>';

			$request = implode('', $res);

			$this->XMLRequest('edit_booking', $request);

			return $this->booking_edit_response;
		}

		// ------------------------------------------------------------------------------
		// BOOKING EDIT -- contact form
		// ------------------------------------------------------------------------------

		function bookingEditContact($var=null, $arg=null) {

			$r = ($var) ? $var : $_REQUEST;

			($r['full_name']) ? $res[] = 'name='.urlencode(htmlspecialchars_decode($r['full_name'], ENT_QUOTES)) : $this->error('contact element "full_name" is empty', 1);
			($r['email']) ? $res[] = 'email='.urlencode($r['email']) : $this->error('contact element "email" is empty', 1);
			($r['phone']) ? $res[] = 'phone='.urlencode($r['phone']) : 0;
			($r['body']) ? $res[] = 'body='.urlencode(htmlspecialchars_decode($r['body'], ENT_QUOTES)) : $this->error('contact element "body" is empty', 1);

			// add a flag for booking edits
			$res[] = 'action=booking_edit_contact';

			// add booking details
			$res[] = 'booking_date='.urlencode($r['booking_date']);
			$res[] = 'booking_time='.urlencode($r['booking_time']);			
			$res[] = 'availability_type='.urlencode($r['availability_type']);
			$res[] = 'expiry_date='.urlencode($r['expiry_date']);
			$res[] = 'item_id='.urlencode($r['item_id']);
			$res[] = 'booking_id='.urlencode($r['booking_id']);
			$res[] = 'item_name='.urlencode($r['item_name']);

			$request = implode('&', $res);

			$this->XMLRequest('booking_edit_contact', $request);

			return $this->booking_edit_response;

		}

		function setTimeZone() {
			date_default_timezone_set('America/Los_Angeles');
		}

		// ------------------------------------------------------------------------------
		// CLEAR PROMO CODE
		// ------------------------------------------------------------------------------
		function resetPromoCode() {
			unset($_REQUEST['promo']);
			$this->setCookie("rezgo_promo",'');
		}
		// ------------------------------------------------------------------------------
		// CLEAR BOOKING SOURCE
		// ------------------------------------------------------------------------------
		function resetBookingSource() {
			unset($_REQUEST['lead']);
			$this->setCookie("rezgo_lead",'');
		}
	}