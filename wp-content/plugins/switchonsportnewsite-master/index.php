<?php
/*
* Plugin name:switchonsportnewsite customization
* Description:switchonsportnewsite customization
*/

class switchonsportnewsite{

  const ERROR_CODE   = 0;
  const SUCCESS_CODE = 1;

  public function __construct(){
    add_shortcode( 'sosn-leagues', [ $this, 'renderLeagues' ] );
    add_shortcode( 'sosn-seasons', [ $this, 'renderSeason' ] );
    add_action( 'wp_enqueue_scripts', [ $this, 'sosnCSS' ] );
    add_action( 'wp_ajax_update_profile_data', [ $this, 'updateProfileData' ] );
    // add_action( 'wp_ajax_nopriv_update_profile_data', [ $this, 'updateProfileData' ] );
    add_action( 'wp_ajax_remove_league_season', [ $this, 'removeLeagueSeason' ] );
    // add_action( 'wp_ajax_nopriv_remove_league_season', [ $this, 'removeLeagueSeason' ] );
    add_action( 'wp_footer', [ $this, 'renderPopup' ],1 );
  }

  ## Add custom stylee and script
  public function sosnCSS(){
    wp_enqueue_style( 'sosn-css', plugin_dir_url( __FILE__ ). 'css/style.css?'.time() );
    wp_enqueue_script( 'sosn-js', plugin_dir_url( __FILE__ ). 'js/script.js?'.time(), array('jquery'), '', true );
  }

  ## Render all leagues for players
  public function renderLeagues( $args ){

    $label    = isset( $args['button_label'] ) ? $args['button_label'] : 'Join now';
    $redirect = isset( $args['redirect_to'] ) ? $args['redirect_to'] : home_url();

    $leagues = get_terms( 'sp_league', [
      'hide_empty' => false
    ] );

    $userID = get_current_user_id();

    $output = '<div style="text-align:center">';
    foreach( $leagues as $league ){

      $leagueID  = $league->term_id;
      $count     = $league->count;

      $class = "";
      $seasonID = get_user_meta( $userID, "_sp_player_league_".$leagueID , true );
      if( !empty($seasonID) ){
        $class = " activated";
      }

      if( $count >= 10 ) {
        $output .='<div class="c-box'.$class.'"><h5>'.$league->name.'</h5><p class="count">' . $count . ' player(s)</p>
                    <p class="fullMessage">
                      <a class="full-slots">Sorry, tournament is full</a>
                      <a class="active-button" href="javascript:void(0)" data-league-id="'.$leagueID.'" data-season-id="'.$seasonID.'"><span>Leave</span><img src="'.plugin_dir_url( __FILE__ ).'img/loader1.gif"></a>  
                    </p></div>';
      } else {
        // $output .='<div class="c-box"><h5>'.$league->name.'</h5><p class="count">' . $count . ' player(s)</p><a href="'.$redirect.'?sp_league='.$leagueID.'">'.$label.'</a></div>';
        $output .='<div class="c-box'.$class.'" id="league-box-'.$leagueID.'"><h5>'.$league->name.'</h5><p class="count">' . $count . ' player(s)</p>
                    <a class="show-details-popup" href="javascript:void(0)" data-league-id="'.$leagueID.'" data-league-title="'.$league->name.'">'.$label.'</a>
                    <a class="active-button" href="javascript:void(0)" data-league-id="'.$leagueID.'" data-season-id="'.$seasonID.'"><span>Leave</span><img src="'.plugin_dir_url( __FILE__ ).'img/loader1.gif"></a>  
                  </div>';
      }
    }
    $output .= "</div>";
    return  $output;
  }

  ## Render all sessions for players
  public function renderSeason( $args ){

    // if( empty($_GET['sp_league']) ){
    //   return "League ID is missing";
    // }

    $label    = isset( $args['button_label'] ) ? $args['button_label'] : 'Join now';
    $message  = isset( $args['message'] ) ? $args['message'] : "Thank you for submitting your request";

    $leagueID = $_GET['sp_league'];
    $seasons = get_terms( 'sp_season', [
      'hide_empty' => false
    ] );

    $output = "<div class='season-form'><select name='input_season' id='input_season' class='medium gfield_select'><option value=''>Select a Season</option>";
    foreach( $seasons as $season ){
      $seasonID = $season->term_id;
      $output .= "<option value='".$seasonID."'>".$season->name."</option>";
    }
    $output .= "</select>";
    $output .= "<div class='spacer'></div>";
    $output .= '<a href="javascript:void(0)" data-league-id="'.$leagueID.'" data-response-id="'.$message.'" >'.$label.'</a>';
    $output .= '</div>';
    $output .= '<div class="loading-image"><img src="'.plugin_dir_url( __FILE__ ).'img/loader1.gif"></div>';
    return  $output;
  }

  ## send ajax response
  public function send_response( $code, $message, $data = [] ){
    echo json_encode( [
      'status' => [
        'code'    => $code,
        'message' => $message,
      ],
      'body'  => $data
    ] );
    wp_die();
  }

  ## Update profile
  public function updateProfileData(){

    $seasonID  = isset( $_POST['season_id'] ) ? $_POST['season_id'] : null;
    $leagueID = isset( $_POST['league_id'] ) ? $_POST['league_id'] : null;

    ## Validation
    if( 
      empty($seasonID) || 
      empty($leagueID) 
    ){
      $this->send_response( self::ERROR_CODE, "Validation error" );
    }

    $userID = get_current_user_id();

    ## Updating league
    $args = array(
        'author'         =>  $userID,
        'post_status'    => 'any',
        'orderby'        =>  'post_date',
        'order'          =>  'ASC',
        'post_type'      => 'sp_player',
        'posts_per_page' => -1
    );

    // Player
    $currentUserPost = get_posts( $args );
    $templeagues[] = (int)$leagueID;
    foreach( $currentUserPost as $post ){
        $templeagues[] = (int)$post->term_id;
        wp_set_object_terms( $post->ID, $templeagues, 'sp_league', true );
    }

    ## Updating season
    $args = array(
        'author'         =>  $userID,
        'post_status'    => 'any',
        'orderby'        =>  'post_date',
        'order'          =>  'ASC',
        'post_type'      => 'sp_player',
        'posts_per_page' => -1
    );

    // // Player
    $currentUserPost = get_posts( $args );
    $tempSeasons[] = (int)$seasonID;
    foreach( $currentUserPost as $post ){
        $tempSeasons[] = (int)$post->term_id;
        wp_set_object_terms( $post->ID, $tempSeasons, 'sp_season', true );
    }

    // ## update user meta
    update_user_meta( $userID, "_sp_player_league_".$leagueID, $seasonID );

    ## Get selected season detail
    $selectedSession = get_term( $seasonID );

    ## Email variable
    $to = get_user_by( 'id', $userID ); // 54 is a user ID

    // use wordwrap() if lines are longer than 70 characters
    $msg = "You are successfully registered for the ".$selectedSession->name;

    // send email
    mail( $to->user_email, "Confirmation", $msg );

    $this->send_response( self::SUCCESS_CODE, "Profile updated", $userID );
    wp_die();
  }

  ## remove league & season profile
  public function removeLeagueSeason(){

    $seasonID  = isset( $_POST['season_id'] ) ? $_POST['season_id'] : null;
    $leagueID = isset( $_POST['league_id'] ) ? $_POST['league_id'] : null;

    ## Validation
    if( 
      empty($seasonID) || 
      empty($leagueID) 
    ){
      $this->send_response( self::ERROR_CODE, "Validation error" );
    }

    $userID = get_current_user_id();

    ## Updating league
    $args = array(
        'author'         =>  $userID,
        'post_status'    => 'any',
        'orderby'        =>  'post_date',
        'order'          =>  'ASC',
        'post_type'      => 'sp_player',
        'posts_per_page' => -1
    );

    // Player
    $currentUserPost = get_posts( $args );
    $templeagues[] = (int)$leagueID;
    foreach( $currentUserPost as $post ){
        $templeagues[] = (int)$post->term_id;
        wp_remove_object_terms( $post->ID, $templeagues, 'sp_league', true );
    }


    ## Updating season
    $args = array(
        'author'         =>  $userID,
        'post_status'    => 'any',
        'orderby'        =>  'post_date',
        'order'          =>  'ASC',
        'post_type'      => 'sp_player',
        'posts_per_page' => -1
    );

    // Player
    $currentUserPost = get_posts( $args );
    $tempSeasons[] = (int)$seasonID;
    foreach( $currentUserPost as $post ){
        $tempSeasons[] = (int)$post->term_id;
        wp_remove_object_terms( $post->ID, $tempSeasons, 'sp_season', true );
    }

    ## update user meta
    delete_user_meta( $userID, "_sp_player_league_".$leagueID, $seasonID );

    $this->send_response( self::SUCCESS_CODE, "Profile updated", $userID );
    wp_die();
  }
  
  ## aDD YOUR html HERE
  public function renderPopup(){
    ?>
    <div id="sosn-popup" class="overlay">
      <div class="popup">
        <h2>
        <label style="float:left;font-size:16px"></label>  
        <a class="close" href="javascript:void(0)">&times;</a></h2>
        <div class="content">
          <?php echo do_shortcode('[sosn-seasons button_label="Submit" message="Thank you for registering for a Tournament"]'); ?>
        </div>
      </di>
    </div>
    <?php
  }
}

new switchonsportnewsite();