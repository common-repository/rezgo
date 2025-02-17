<?php

/*
    Plugin Name: Rezgo Online Booking
    Plugin URI: https://wordpress.org/plugins/rezgo/
    Description: Connect WordPress to your Rezgo account and accept online bookings directly on your website.
    Version: 4.13.2
    Author: Rezgo
    Author URI: http://www.rezgo.com
    License: Modified BSD
*/

/*
    - Documentation and latest version
            https://wordpress.org/plugins/rezgo/

    - Finding your Rezgo CID and API KEY
            https://www.rezgo.com/support-article/create-api-keys

    AUTHOR:
            Kevin Campbell, John McDonald, Drishti Trivedi, Joshua Loo

    Copyright (c) 2011-2020, Rezgo (A Division of Sentias Software Corp.)
    All rights reserved.

    Redistribution and use in source form, with or without modification,
    is permitted provided that the following conditions are met:

    * Redistributions of source code must retain the above copyright
    notice, this list of conditions and the following disclaimer.
    * Neither the name of Rezgo, Sentias Software Corp, nor the names of
    its contributors may be used to endorse or promote products derived
    from this software without specific prior written permission.
    * Source code is provided for the exclusive use of Rezgo members who
    wish to connect to their Rezgo XMP API. Modifications to source code
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

ob_start();

header("Cache-Control: no-cache");

define('REZGO_PLUGIN_NAME', 'rezgo');
define('REZGO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('REZGO_PLUGIN_VERSION', '4.13.2');

require_once('rezgo/include/page_header.php');
require_once('rezgo_service_functions.php');

rezgo_include_file('/settings/rezgo_settings.php');

if ( REZGO_CID && REZGO_API_KEY ) {
    require_once('rezgo_plugin_logic.php');

    // create rewrite rules on activation
    register_activation_hook(__FILE__, 'flush_rewrite_rules');
    add_action('generate_rewrite_rules', 'rezgo_add_rewrite_rules');

    // flush_rules() if v4 rules are not yet included
    $rewrite_rules = get_option( 'rewrite_rules' );
    if ( !isset($rewrite_rules['[(.+?)/confirm/?$] => index.php?pagename=$matches[1]&mode=page_book&sec=1&step=2']) && (int) REZGO_PLUGIN_VERSION >= 4 ) {
        add_action('wp_loaded', 'flush_rewrite_rules');
        add_action('generate_rewrite_rules', 'rezgo_add_rewrite_rules');
    }

    add_shortcode('rezgo_shortcode', 'rezgo_iframe');
    add_action('rezgo_tpl_display', 'rezgo_iframe');
}
