jQuery(document).ready(function(){

  // Remove validation error
  jQuery('body').on('change', '#input_season', function(){
    jQuery(this).parent().removeClass('validation-error');
  });

  /*On Clicking filter button*/
  jQuery('body').on('click', '.show-details-popup', function(){
    // change box title
    var leagueName = jQuery(this).attr('data-league-title');
    jQuery(".popup h2 label").html(leagueName);
    jQuery("#sosn-popup.overlay").addClass("target");
    var leagueId = jQuery(this).attr('data-league-id');
    jQuery(' .season-form a').attr('data-league-id', leagueId);
  });

  jQuery('body').on('click', '.overlay .close', function(){
    jQuery("#sosn-popup.overlay").removeClass("target");
  });
  
  /*On Clicking filter button*/
  jQuery('body').on('click', '.season-form a', function(){

    var season_id = jQuery('#input_season').val();
    var message   = jQuery(this).attr('data-response-id');
    var leagueId  = jQuery(this).attr('data-league-id');

    jQuery(this).parent().removeClass('validation-error');
    if( season_id == "" ){      // Validating season ID
      jQuery(this).parent().addClass('validation-error');
      return;
    }

    jQuery('.loading-image').show();    // Show loader
    jQuery('.season-form').hide();
    
    var data = {
      'action'    : 'update_profile_data',
      'season_id' : season_id,
      'league_id' : leagueId
    };

    jQuery.post(pp.ajax_url, data, function (response) {
      // console.log('Search complete');
      var tempResponse = JSON.parse(response);
      if( tempResponse["status"].code == 1 ){
        // jQuery('.season-form').html(message);
      }

      /* Hide loading image */
      jQuery('.loading-image').hide();
      jQuery('.season-form').show();
      jQuery("#sosn-popup.overlay").removeClass("target");
      jQuery("#league-box-"+leagueId).addClass("activated");
      jQuery("#league-box-"+leagueId+" a.active-button").attr("data-season-id",season_id);
    }).fail(function () {
      //alert('oops something went wrong while saving data');
      console.log('oops something went wrong while saving data');
      /* Hide loading image */
      jQuery('.loading-image').hide();
      jQuery('.season-form').show();
    });
  });

  /*Remove league and season*/
  jQuery('body').on('click', '.c-box a.active-button', function(){

    var self = jQuery(this);
    var leagueId  = jQuery(this).attr('data-league-id');
    var seasonId  = jQuery(this).attr('data-season-id');

    // Show loader image
    self.addClass("processing");
    
    var data = {
      'action'    : 'remove_league_season',
      'season_id' : seasonId,
      'league_id' : leagueId
    };

    jQuery.post(pp.ajax_url, data, function (response) {
      // console.log('Search complete');
      var tempResponse = JSON.parse(response);
      if( tempResponse["status"].code == 1 ){
        // jQuery('.season-form').html(message);
      }

      /* Hide loading image */
      self.removeClass("processing");
      jQuery("#league-box-"+leagueId).removeClass("activated");
    }).fail(function () {
      //alert('oops something went wrong while saving data');
      console.log('oops something went wrong while saving data');
      /* Hide loading image */
      self.removeClass("processing");
    });
  });
});
