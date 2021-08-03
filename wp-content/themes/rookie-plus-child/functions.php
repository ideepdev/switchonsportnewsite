<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

// BEGIN ENQUEUE PARENT ACTION
// AUTO GENERATED - Do not modify or remove comment markers above or below:

if ( !function_exists( 'chld_thm_cfg_locale_css' ) ):
    function chld_thm_cfg_locale_css( $uri ){
        if ( empty( $uri ) && is_rtl() && file_exists( get_template_directory() . '/rtl.css' ) )
            $uri = get_template_directory_uri() . '/rtl.css';
        return $uri;
    }
endif;
add_filter( 'locale_stylesheet_uri', 'chld_thm_cfg_locale_css' );

if ( !function_exists( 'chld_thm_cfg_parent_css' ) ):
    function chld_thm_cfg_parent_css() {
        wp_enqueue_style( 'chld_thm_cfg_parent', trailingslashit( get_template_directory_uri() ) . 'style.css', array( 'mega-slider-style','news-widget-style','social-sidebar-icons','social-sidebar','social-sidebar-classic','rookie-framework-style' ) );
    }
endif;
add_action( 'wp_enqueue_scripts', 'chld_thm_cfg_parent_css', 10 );

// END ENQUEUE PARENT ACTION
// 
// 

add_filter( 'gform_pre_render_1', 'populate_tournaments' );
add_filter( 'gform_pre_validation_1', 'populate_tournaments' );
add_filter( 'gform_pre_submission_filter_1', 'populate_tournaments' );
add_filter( 'gform_admin_pre_render_1', 'populate_tournaments' );
function populate_tournaments( $form ) {
 
    foreach ( $form['fields'] as $field ) {
 
        if ( $field->type != 'select' || strpos( $field->cssClass, 'populate-tournaments' ) === false ) {
            continue;
        }

        $tournaments = get_posts( 'post_type=sp_tournament' );
 		
        $choices = array();
 
        foreach ( $tournaments as $tournament ) {
				if ($tournament->post_content !== "Completed") {
					$choices[] = array( 'text' => $tournament->post_title, 'value' => $tournament->post_title );
				}
        }
 
        $field->placeholder = 'Select a Tournament';
        $field->choices = $choices;
 
    }
 
    return $form;
}


//
//
//
// Fetching season

add_filter( 'gform_pre_render_1', 'populate_season' );
add_filter( 'gform_pre_validation_1', 'populate_season' );
add_filter( 'gform_pre_submission_filter_1', 'populate_season' );
add_filter( 'gform_admin_pre_render_1', 'populate_season' );
function populate_season( $form ) {
 
    foreach ( $form['fields'] as $field ) {
 
        if ( $field->type != 'select' || strpos( $field->cssClass, 'populate-season' ) === false ) {
            continue;
        }

        $seasons = get_terms( 'sp_season', [
           'hide_empty' => false
        ] );

        $choices = array();
 
        foreach ( $seasons as $season ) {
            $choices[] = array( 'text' => $season->name, 'value' => $season->term_id );
        }

        $field->placeholder = 'Select a Season';
        $field->choices = $choices;
 
    }
 
    return $form;
}

function seasonRegistrationNotification($season_id) {
	$current_user = wp_get_current_user();
	
	$to = $current_user->user_email;
	$subject = 'Thank you for signing up';
	$body = 'Thanks for signing up to Switch On Sports FIFA tournament.';
	$headers = array('Content-Type: text/html; charset=UTF-8');

	wp_mail( $to, $subject, $body, $headers );
	
	global $wpdb;
	$season_result = $wpdb->get_row("SELECT * FROM $wpdb->terms WHERE term_id='$season_id'");
	$name_of_season = $season_result->name;
	
	$adminTo = "daniel.cecconi@computingaustralia.group";
	$adminSubject = "A plyer has registered for a season";
	$adminBody = "A player with the username: " . $current_user->user_login . " has registered for a season: " . $name_of_season;
	
	wp_mail( $adminTo, $adminSubject, $adminBody, $headers );
}

add_action( 'gform_after_submission_1', 'after_submission', 10, 2 );
function after_submission($entry, $form ) {
	
    foreach ( $form['fields'] as $field ) {
 
        if ( $field->type != 'select' || strpos( $field->cssClass, 'populate-season' ) === false ) {
            continue;
        }

        if( empty( $entry[ $field->id  ]) ){
            continue;
        }

        $currentUserID = get_current_user_id();

        if( empty($currentUserID) ){
            continue;
        }

        $args = array(
            'author'         =>  $currentUserID,
            'post_status'    => 'any',
            'orderby'        =>  'post_date',
            'order'          =>  'ASC',
            'post_type'      => 'sp_player',
            'posts_per_page' => -1
        );

        // Player
        $currentUserPost = get_posts( $args );
        foreach( $currentUserPost as $post ){
            wp_set_object_terms( $post->ID, (int) $entry[ $field->id  ] , 'sp_season' );
        }
    }
 	seasonRegistrationNotification(rgar( $entry, '3' ));
    return $form;
	
}

//Hiding dashboard notifications and dashboard items...
add_action('admin_head', 'hide_notice');

function hide_notice() {
  echo '<style>
    .premium-notice {
        display: none;
    } 
	.update-nag {
		display: none;
	}
	.toplevel_page_sportspress-tutorials {
		display: none;
	}
	#e-dashboard-overview {
		display: none;
	}
	#wpseo-dashboard-overview {
		display: none;
	}
	#wp-admin-bar-wpseo-menu {
		display: none;
	}
	#wp-admin-bar-new-content {
		display: none;
	}
	#wp-admin-bar-comments {
		display: none;
	}
  </style>';
}

//Adding custom widget to dashboard
add_action('wp_dashboard_setup', 'my_custom_dashboard_widgets');
  
function my_custom_dashboard_widgets() {
    wp_add_dashboard_widget('custom_widget_tournaments', 'Select a tournament', 'custom_dashboard_tournaments');
	wp_add_dashboard_widget('custom_widget_matches', 'View upcoming matches', 'custom_dashboard_matches');
	
}
function custom_dashboard_tournaments() {
    echo do_shortcode( '[gravityform id="1" title="true" description="true" ajax="true"]' );
}
function custom_dashboard_matches() {
    echo do_shortcode( '[event_list 5116]' );
}


#
# Adding Player once the user is registered via gravity form
add_action( 'gform_user_registered', 'add_custom_user_meta', 10, 4 );
function add_custom_user_meta( $user_id, $feed, $entry, $user_pass ) {
    $formID = $entry['form_id'];
    $user = get_userdata($user_id);
    $name = trim("{$user->first_name} {$user->last_name}");
    if( empty($name) ){
      $name = $user->user_login;
    }
    if( $feed['meta']['role'] === 'sp_team_manager' ){
      $post = [
        'post_type'   => 'sp_player',
        'post_title'  => trim( $name ),
        'post_author' => $user_id,
        'post_status' => 'publish'
      ];
      wp_insert_post( $post );
    }
}
//
//
//Adding a default avatar option 
add_filter( 'avatar_defaults', 'wpb_new_gravatar' );
function wpb_new_gravatar ($avatar_defaults) {
	$myavatar = 'http://switchonsportnewsite.kinsta.cloud/wp-content/uploads/2021/03/default-profile-1.jpg';
	$avatar_defaults[$myavatar] = "Default Gravatar";
	return $avatar_defaults;
}
//Registration custom link
add_filter( 'register_url', 'change_my_register_url' );
function change_my_register_url( $url ) {
    if( is_admin() ) {
        return $url;
    }
    return "/user-registration-form";
}


//Populate season for player registration
add_filter( 'gform_pre_render_2', 'populate_season_2' );
add_filter( 'gform_pre_validation_2', 'populate_season_2' );
add_filter( 'gform_pre_submission_filter_2', 'populate_season_2' );
add_filter( 'gform_admin_pre_render_2', 'populate_season_2' );
function populate_season_2( $form ) {
 
    foreach ( $form['fields'] as $field ) {
 
        if ( $field->type != 'select' || strpos( $field->cssClass, 'populate-season' ) === false ) {
            continue;
        }

        $seasons = get_terms( 'sp_season', [
           'hide_empty' => false
        ] );

        $choices = array();
 
        foreach ( $seasons as $season ) {
            $choices[] = array( 'text' => $season->name, 'value' => $season->term_id );
        }

        $field->placeholder = 'Select a Season';
        $field->choices = $choices;
 
    }
 
    return $form;
}

//Register player after player registration
add_action( 'gform_after_submission_2', 'after_submission_2', 10, 2 );
function after_submission_2($entry, $form ) {
	
    foreach ( $form['fields'] as $field ) {
 
        if ( $field->type != 'select' || strpos( $field->cssClass, 'populate-season' ) === false ) {
            continue;
        }

        if( empty( $entry[ $field->id  ]) ){
            continue;
        }

        $currentUserID = get_current_user_id();

        if( empty($currentUserID) ){
            continue;
        }

        $args = array(
            'author'         =>  $currentUserID,
            'post_status'    => 'any',
            'orderby'        =>  'post_date',
            'order'          =>  'ASC',
            'post_type'      => 'sp_player',
            'posts_per_page' => -1
        );

        // Player
        $currentUserPost = get_posts( $args );
        foreach( $currentUserPost as $post ){
            wp_set_object_terms( $post->ID, (int) $entry[ $field->id  ] , 'sp_season' );
        }
 
    }
 
    return $form;
}

//Form gamertag validation 
add_filter( 'gform_validation_2', 'custom_validation' );
function custom_validation( $validation_result ) {
    $form = $validation_result['form'];

    if ( rgpost( 'input_14' ) == "" && rgpost( 'input_15' ) == "" && rgpost( 'input_16' ) == "" && rgpost( 'input_17' ) == "" && rgpost( 'input_18' ) == "" ) {

        // set the form validation to false
        $validation_result['is_valid'] = false;

        //finding Field with ID of 1 and marking it as failed validation
        foreach( $form['fields'] as &$field ) {

            if ( $field->id == '18' || $field->id == '17' || $field->id == '16' || $field->id == '15' || $field->id == '14') {
                $field->failed_validation = true;
                $field->validation_message = 'Please enter a gamertag.';
            }
			
        }

    }

    //Assign modified $form object back to the validation result
    $validation_result['form'] = $form;
    return $validation_result;

}

//Create shortcode for league count
function leagueCountFunction( $atts ) {
	global $wpdb;
	$id = $atts['id'];
	$countResult = $wpdb->get_row("SELECT * FROM $wpdb->term_taxonomy WHERE taxonomy='sp_league' AND term_id=$id");
	return $countResult->count;
}

add_shortcode('leagueCount', 'leagueCountFunction');

//Hide menu item
add_action( 'init', 'your_function' );
function your_function() {
if ( !is_user_logged_in() ) {
     echo '<style>
    .menu-item-5744 {
        display: none !important;
    } 
  </style>';
}
}

if( !(isset($_POST['action']) && $_POST['action'] == "update_profile_data") && !(isset($_POST['action']) && $_POST['action'] == "remove_league_season") ){
    //Restrict access to admin area...
    function restrict_admin() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_redirect( home_url() );
            exit;
        }
    }

    add_action( 'admin_init', 'restrict_admin', 1 );

    //Hide admin bar 
    add_action( 'init', 'hide_admin_bar' );
    function hide_admin_bar() {
        if ( ! current_user_can( 'manage_options' ) ) {
            echo '<style>
                #wpadminbar {
                display: none !important;
                } 
                </style>';
        }
    }
}

//Shortcode for seasons
function seasonsFunction( ) {
	global $wpdb;
	
	$current_user = wp_get_current_user();
	$player_username = $current_user->user_login;
	$player_result = $wpdb->get_row("SELECT * FROM wp_posts WHERE post_author='$current_user->ID' AND post_type='sp_player'");
	$player_id = $player_result->ID;
	
	$seasonsResults = $wpdb->get_results("SELECT * FROM $wpdb->term_taxonomy WHERE taxonomy='sp_season'");
	foreach($seasonsResults as $result) {
		$count = $result->count;
		$termid = $result->term_id;
		$description = $result->description;
		$seasonName = $wpdb->get_row("SELECT * FROM $wpdb->terms WHERE term_id=$termid");
		if(is_user_logged_in()) {
			echo "<div class='competiton-card'><h2>" . $seasonName->name . "</h2><p>" . $description ."</p>" . "<p>" . "Number of registered players:" . " " . $count . "</p><form method='post' action=''> " . wp_nonce_field() ." <input type='hidden' id='id' name='seasonID' value='" . $seasonName->term_id . "'> <input type='hidden' id='id' name='playerID' value='" . $player_id . "'>
<input type='submit' name='registerSeasonButton' value='JOIN NOW'></div></form>";
		} else {
			echo "<div class='competiton-card'><h2>" . $seasonName->name . "</h2><p>" . $description ."</p>" . "<p>" . "Number of registered players:" . " " . $count . "</p></div>";
		}
	}
}

add_shortcode('seasons', 'seasonsFunction');

if(isset($_POST['registerSeasonButton'])){
	global $wpdb;
	$nonce = $_REQUEST['_wpnonce'];
	$playerID = $_POST['playerID'];
	$seasonID = $_POST['seasonID'];
	$season_results = $wpdb->get_results("SELECT * FROM wp_term_relationships WHERE object_id='$playerID' AND term_taxonomy_id='$seasonID'");
	if(wp_verify_nonce($nonce) and empty($season_results)) {
			$wpdb->insert('wp_term_relationships', array('object_id' => $playerID, 'term_taxonomy_id' => $seasonID, 'term_order' => 0)); 
			$count_result = $wpdb->get_row("SELECT * FROM wp_term_taxonomy WHERE term_id='$seasonID'");
			$count = $count_result->count + 1;
			$wpdb->update('wp_term_taxonomy', array('count' => $count), array('term_id' => $seasonID)); 
	}
}

//Getting tournament/league count
function tournamentCountFunction( ) {
	global $post;
    $postSlug = $post->post_name;
	$slugArray = explode("/", $postSlug);
	$tournamentSlug = end($slugArray);
	
	
	global $wpdb;
	$id_result = $wpdb->get_row("SELECT * FROM $wpdb->terms WHERE slug='$tournamentSlug'");
	$tournament_id = $id_result->term_id;
	$count_result = $wpdb->get_row("SELECT * FROM $wpdb->term_taxonomy WHERE term_id='$tournament_id'");
	echo "<p>" . $count_result->count . "</p>";
}

add_shortcode('tournamentCount', 'tournamentCountFunction');

//Submitting season registration
function season_registration() {
	 ?>
       <script>
		 window.onload = function(){
			 if(document.location.search.length) {
			  document.forms['gform_5'].submit();
		     }
		}
        </script>
    <?php
}
//add_action( 'wp_footer', 'season_registration', 10, 2 );

//Register player in season
add_action( 'gform_after_submission_5', 'after_submission_5', 10, 2 );

function after_submission_5($entry, $form ) {
	
    foreach ( $form['fields'] as $field ) {
	
        //if ( strpos( $field->cssClass, 'populate-season' ) === false ) {
       //     continue;
        //}

        if( empty( $entry[ $field->id  ]) ){
            continue;
        }

        $currentUserID = get_current_user_id();

        if( empty($currentUserID) ){
            continue;
        }

        $args = array(
            'author'         =>  $currentUserID,
            'post_status'    => 'any',
            'orderby'        =>  'post_date',
            'order'          =>  'ASC',
            'post_type'      => 'sp_player',
            'posts_per_page' => -1
        );

        // Player
        $currentUserPost = get_posts( $args );
	
        foreach( $currentUserPost as $post ){
			echo var_dump($post->ID);
			echo var_dump((int) $entry[ $field->id  ]);
            wp_set_object_terms( $post->ID, (int) $entry[ $field->id  ] , 'sp_season' );
        }
    }
    return $form;
}

add_action( 'template_redirect', 'testFunc' );
function testFunc(){
	global $wpdb;
    if(isset($_POST['testButton'])) {
		$season = $_POST['seasonValue'];
		$currentUserID = get_current_user_id();
		 $args = array(
            'author'         =>  $currentUserID,
            'post_status'    => 'any',
            'orderby'        =>  'post_date',
            'order'          =>  'ASC',
            'post_type'      => 'sp_player',
            'posts_per_page' => -1
        );
		$my_post_id = wp_insert_post($args, true);
	}
}

function playerInfoFinder() {
	global $wpdb;
	$current_user = wp_get_current_user();
	$player_username = $current_user->user_login;
	echo get_avatar( $current_user->ID, 128 );
	$player_result = $wpdb->get_row("SELECT * FROM wp_posts WHERE post_author='$current_user->ID' AND post_type='sp_player'");
	echo do_shortcode("[player_details id=\"$player_result->ID\"]");
}

add_shortcode('player_info', 'playerInfoFinder');

function tournamentDisplay($atts) {
	$tournament_id = (int)$atts['id'];
	global $wpdb;
	$tournament_row = $wpdb->get_row("SELECT * FROM wp_posts WHERE ID='$tournament_id'");
	$tournament_name = $tournament_row->post_title;
	echo "<h3>" . $tournament_name . "</h3>";
	$eventResults = $wpdb->get_results("SELECT * FROM wp_postmeta WHERE post_id='$tournament_id' AND meta_key='sp_event'");
	foreach($eventResults as $event) {
		if($event->meta_value != "") {
			$event_row = $wpdb->get_row("SELECT * FROM wp_posts WHERE ID='$event->meta_value'");
			echo "<h4>" . $event_row->post_title . "</h4>";
			echo do_shortcode("[countdown id=\"$event_row->ID\"]");
		
		}
	}
}
add_shortcode('tournament', 'tournamentDisplay');

function tournament_countdown_timer($atts) {
	global $wpdb;
	$now = time();
	$tournament_id = (int)$atts['id'];
	$result = $wpdb->get_row("SELECT * FROM wp_posts WHERE ID='$tournament_id' AND post_type='sp_tournament'");
	$date_string = substr($result->post_date, 0, 10);
	$date_array = explode("-", $date_string);
	$event = mktime(0,0,0,$date_array[1],$date_array[2],$date_array[0]);
	$countdown = $event - $now;
	
	if ($countdown > 0) {
		$time = $countdown / 60;
		$hours = floor($time / 60);
    	$minutes = ($time % 60);
    	echo "<p>Starts in:</p><h5>" . $hours . " hours " . $minutes . " minutes </h5>";
	} else {
		echo "<h5>Tournament has closed</h5>";
	}
}
add_shortcode('tournament_countdown', 'tournament_countdown_timer');
	
#
## Populate season for player registration
add_filter( 'gform_pre_render_2', 'populate_league' );
add_filter( 'gform_pre_validation_2', 'populate_league' );
add_filter( 'gform_pre_submission_filter_2', 'populate_league' );
add_filter( 'gform_admin_pre_render_2', 'populate_league' );
function populate_league( $form ) {
 
    foreach ( $form['fields'] as $field ) {
 
        if ( $field->type != 'select' || strpos( $field->cssClass, 'populate-league' ) === false ) {
            continue;
        }

        $leagues = get_terms( 'sp_league', [
           'hide_empty' => false
        ] );

        $choices = array();
 
        foreach ( $leagues as $league ) {
            $choices[] = array( 'text' => $league->name, 'value' => $league->term_id );
        }

        $field->placeholder = 'Select a league';
        $field->choices = $choices;
 
    }
 
    return $form;
}

#
## Add league to player
add_action( 'gform_user_registered', 'add_custom_user_league', 10, 4 );
function add_custom_user_league( $user_id, $feed, $entry, $user_pass ) {

    if( $feed['meta']['role'] !== 'sp_team_manager' ){
        return;
    }

    $leagueFieldID = 0;
    $form = GFAPI::get_form( $entry['form_id'] );
    if( false === $form ){
        return;
    }

    foreach ( $form['fields'] as $field ) {
        if ( $field->type == 'select' && strpos( $field->cssClass, 'populate-league' ) !== false ) {
            $leagueFieldID = $field->id;
        }
    }

    if( $leagueFieldID == 0 ){
        return;
    }

    if( empty($user_id) ){
        return;
    }

    $args = array(
        'author'         =>  $user_id,
        'post_status'    => 'any',
        'orderby'        =>  'post_date',
        'order'          =>  'ASC',
        'post_type'      => 'sp_player',
        'posts_per_page' => -1
    );

    // Player
    $currentUserPost = get_posts( $args );
    foreach( $currentUserPost as $post ){
        wp_set_object_terms( $post->ID, (int) $entry[ $leagueFieldID ] , 'sp_league' );
    }

}


#
## Add season to player
add_action( 'gform_user_registered', 'add_custom_user_season', 10, 4 );
function add_custom_user_season( $user_id, $feed, $entry, $user_pass ) {

    if( $feed['meta']['role'] !== 'sp_team_manager' ){
        return;
    }

    $leagueFieldID = 0;
    $form = GFAPI::get_form( $entry['form_id'] );
    if( false === $form ){
        return;
    }

    foreach ( $form['fields'] as $field ) {
        if ( $field->type == 'select' && strpos( $field->cssClass, 'populate-season' ) !== false ) {
            $leagueFieldID = $field->id;
        }
    }

    if( $leagueFieldID == 0 ){
        return;
    }

    if( empty($user_id) ){
        return;
    }

    $args = array(
        'author'         =>  $user_id,
        'post_status'    => 'any',
        'orderby'        =>  'post_date',
        'order'          =>  'ASC',
        'post_type'      => 'sp_player',
        'posts_per_page' => -1
    );

    // Player
    $currentUserPost = get_posts( $args );
    foreach( $currentUserPost as $post ){
        wp_set_object_terms( $post->ID, (int) $entry[ $leagueFieldID ] , 'sp_season' );
    }

}

add_filter( 'gform_pre_render_6', 'populate_leagues' );
add_filter( 'gform_pre_validation_6', 'populate_leagues' );
add_filter( 'gform_pre_submission_filter_6', 'populate_leagues' );
add_filter( 'gform_admin_pre_render_6', 'populate_leagues' );
function populate_leagues( $form ) {
	
    foreach ( $form['fields'] as $field ) {
 
        if ( $field->type != 'select' || strpos( $field->cssClass, 'populate-league' ) === false ) {
            continue;
        }

        $leagues = get_terms( 'sp_league', [
           'hide_empty' => false
        ] );

        $choices = array();
 
        foreach ( $leagues as $league ) {
            $choices[] = array( 'text' => $league->name, 'value' => $league->term_id );
        }

        $field->placeholder = 'Select a League';
        $field->choices = $choices;
 
    }
 
    return $form;
}

add_action( 'gform_after_submission_6', 'after_submission_6', 10, 2 );
function after_submission_6($entry, $form ) {
		
    foreach ( $form['fields'] as $field ) {
 
        if ( $field->type != 'select' || strpos( $field->cssClass, 'populate-league' ) === false ) {
            continue;
        }
		
        if( empty( $entry[ $field->id ]) ){
            continue;
        }

        $currentUserID = get_current_user_id();

        if( empty($currentUserID) ){
            continue;
        }

        $args = array(
            'author'         =>  $currentUserID,
            'post_status'    => 'any',
            'orderby'        =>  'post_date',
            'order'          =>  'ASC',
            'post_type'      => 'sp_player',
            'posts_per_page' => -1
        );

        // Player
        $currentUserPost = get_posts( $args );
        foreach( $currentUserPost as $post ){
            wp_set_object_terms( $post->ID, (int) $entry[ $field->id  ] , 'sp_league' );
        }
    }
 	seasonRegistrationNotification(rgar( $entry, '3' ));
    return $form;
	
}

//Generate custom dynamic link for the 'My Profile' page
function myProfileAndHide() {
	$current_user = wp_get_current_user();
	$username = $current_user->user_login;
	//Pass username to JS script
	echo "<div id='passedVariable' style='display: none;'>" . $username . "</div>";
	?>
       <script>
		   window.onload = function(){
			//Displaying custom link for My Profile menu item.
			let userName = document.querySelector('#passedVariable');
			userName = userName.innerHTML;
			let link = document.querySelector(".menu-item-8515 .elementor-item");
			link.href = "http://staging-switchonsportnewsite.kinsta.cloud/archives/player/" + userName;
			//Hiding the league selector drop down. 
			let leagueSelectorDisplay = document.querySelector(".elementor-element-786e4f1");
			if (leagueSelectorDisplay) {
				leagueSelectorDisplay.style.display = "none";
			}
			//Hiding the player selector.
			let playerSelectorDisplay = document.querySelector(".sp-section-content");
			if (playerSelectorDisplay) {
				playerSelectorDisplay.style.display = "none";
			}
		}
        </script>
    <?php
}

add_action('wp_footer', 'myProfileAndHide');

//Display league names and count with 'Join Now' button leading to join page
function leagueTableDisplay() {
	global $wpdb;
	//Grab all leagues within the DB
	$leagueResults = $wpdb->get_results("SELECT * FROM wp_term_taxonomy WHERE taxonomy='sp_league'");
	foreach($leagueResults as $result) {
		//Grab league ID
		$league_id = $result->term_id;
		//Grab count of league participation
		$count = $result->count;
		//Grab the specific league
		$league_row = $wpdb->get_row("SELECT * FROM wp_terms WHERE term_id='$league_id'");
		//If count exceeds participation limit then display that the tournament is full
		if ($count >= 10) {
			echo "<div class='competiton-card'><p class='leagueName'>" . $league_row->name . "</p><p class='count'>" . $count . " player(s)</p><p class='fullMessage'>Sorry, tournament is full</p></div>";
		} else {
			echo "<div class='competiton-card'><p class='leagueName'>" . $league_row->name . "</p><p class='count'>" . $count . " player(s)</p><form action='http://staging-switchonsportnewsite.kinsta.cloud/play-now'><input type='submit' value='Join Now' /></form></div>";
		}
	}
}

add_shortcode('leagues', 'leagueTableDisplay');
function getTournamentDate( ) {
	global $post;

	$round_1 = get_post_meta($post->ID, "round_1", true);
	$round_2 = get_post_meta($post->ID, "round_2", true);
	$round_3 = get_post_meta($post->ID, "round_3", true);
	$round_4 = get_post_meta($post->ID, "round_4", true);
	if($round_4){
		$round_name = 'Round 4';
		$date = date('F j, Y g:i a', strtotime($round_4));
	    $convert_date = date('Y-m-d', strtotime($round_4));
		$timerdate = date("F j, Y", strtotime($convert_date.'+5 days'));

	}elseif($round_3){
		$round_name = 'Round 3';
		$date = date('F j, Y g:i a', strtotime($round_3));
	    $convert_date = date('Y-m-d', strtotime($round_3));
		$timerdate = date("F j, Y", strtotime($convert_date.'+5 days'));
	}elseif($round_2){
		$round_name = 'Round 2';
		$date = date('F j, Y g:i a', strtotime($round_2));
	    $convert_date = date('Y-m-d', strtotime($round_2));
		$timerdate = date("F j, Y", strtotime($convert_date.'+5 days'));
	}elseif($round_1){
		$round_name = 'Round 1';
		$date = date('F j, Y g:i a', strtotime($round_1));
	    $convert_date = date('Y-m-d', strtotime($round_1));
		$timerdate = date("F j, Y", strtotime($convert_date.'+5 days'));
	}else{
		$round_name = 'There is no date';
		$date = 'There is no date';
	    $convert_date = 'There is no date';
		$timerdate = 'There is no date';
	}

	$meta = get_post_meta($post->ID, "sp_events", true);
	//echo "<h1>TIMER HERE ".$meta[0]['date']."<span class ='timer'></span></h1>";
	echo "<h2>TIMER HERE : ".$date."<span class ='timer'></span></h2>";
?>
<style type="text/css">
	@font-face {font-family: "NTF Grand"; src: url("//db.onlinewebfonts.com/t/b30ffe63cc57ffdba315d296d0ca85b0.eot"); src: url("//db.onlinewebfonts.com/t/b30ffe63cc57ffdba315d296d0ca85b0.eot?#iefix") format("embedded-opentype"), url("//db.onlinewebfonts.com/t/b30ffe63cc57ffdba315d296d0ca85b0.woff2") format("woff2"), url("//db.onlinewebfonts.com/t/b30ffe63cc57ffdba315d296d0ca85b0.woff") format("woff"), url("//db.onlinewebfonts.com/t/b30ffe63cc57ffdba315d296d0ca85b0.ttf") format("truetype"), url("//db.onlinewebfonts.com/t/b30ffe63cc57ffdba315d296d0ca85b0.svg#NTF-Grand-Regular") format("svg"); }
	.timer{
		/*font-size:10vmin;*/
		padding: 60px;
		color:#fff;
		text-shadow:-1px -1px 0px #2cf,
					1px -1px 0px #2cf,
					-1px 1px 0px #2cf,
					1px 1px 0px #2cf,
					0px 0px 15px #fff,
					0px 0px 20px #5af,
					0px 0px 25px #5af,
					0px 0px 30px #5af,
					0px 0px 35px #5af,
					0px 0px 40px #5af;
	}
</style>
<script type="text/javascript">

    let deadline ='<?php echo $timerdate." 00:00:00";?>';
	let round_name ='<?php echo $round_name;?>';
    function getTimeRemaining(endtime){
        let t = Date.parse(endtime) - Date.now();
        var hundreth = Math.floor((t % 1000)/ 10);
        var seconds = Math.floor((t / 1000) % 60);
        var minutes = Math.floor((t / 1000 / 60) % 60);
        var hours = Math.floor((t / (1000 * 60 * 60)) % 24);
        var days = Math.floor(t / (1000 * 60 * 60 * 24));
            
        return{
        'total':t,
        'days': days,
        'hours': hours,
        'minutes': minutes,
        'seconds': seconds
        };
    }
        
    function initializeClock(id, endtime){
            var clock = document.getElementById(id);
            var timerDisplay = document.querySelector(".timer");
            function updateClock(){
                var t   = getTimeRemaining(endtime);
                timerDisplay.innerText = t.days + ":" + ('0' + t.hours).slice(-2) + ":" + ('0' + t.minutes).slice(-2) + ":" + ('0' + t.seconds).slice(-2) ;
                            
                if(t.total<=0){
                   // clearInterval(timeInterval);
					timerDisplay.innerText = "Hi "+round_name+" Time Is Over" ;
                }
            }
            updateClock();
            let timeInterval = setInterval(updateClock,1000);
    }
initializeClock("timer", deadline);

</script>
<?php
	//print_r($meta);
}

add_shortcode('tournamentdate', 'getTournamentDate');