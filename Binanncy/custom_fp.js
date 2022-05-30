function toggle(elem){
jQuery(document).ready(function($) {
	$('#'+elem).slideToggle();
});
}
function cpyPaste(fld) {
  /* Get the text field */
  var copyText = document.getElementById(fld);

  /* Select the text field */
  copyText.select();
  copyText.setSelectionRange(0, 99999); /* For mobile devices */

   /* Copy the text inside the text field */
  navigator.clipboard.writeText(copyText.value);

  /* Alert the copied text */
  alert("Copied the text: " + copyText.value);
} 
function jsStatDialog(did, aid){
jQuery(document).ready(function($) {
	$("#diag_stat_ajax").html('Loading....');
    $("#dialog").dialog("open");
	//do ajax call for did ID
		var data = {
			'action': 'wpb_getstatdiag',
			'statid': did,
			'apiid' : aid
		};

				jQuery.post(wpmm.ajaxurl, data, function(response) {
					$("#diag_stat_ajax").html(response);
				});
	
	//display to #diag_stat_ajax
	
    return false;
});	
	
}
function getAPIStats(url){
jQuery(document).ready(function($) {
	document.location.href=url+'&keyid='+$("#choose_api").val();
});
}
function dltApi(api){
jQuery(document).ready(function($) {
		$('#apikey_'+api).block({
    message: 'Please wait...',
})

		//ajax the dleete request
				var data = {
			'action': 'wpmm_delete_api',
			'apikey': api
		};

				jQuery.post(wpmm.ajaxurl, data, function(response) {
					document.location.href='';
				});
		
		//refresh the page
	
});	
}
function confirmDelete(dlt){
jQuery(document).ready(function($) {
	$("#dlt_"+dlt).slideToggle();
	$("#suredelete_"+dlt).slideToggle();
	
	setTimeout(function(){	
	$("#suredelete_"+dlt).slideToggle();
	$("#dlt_"+dlt).slideToggle();
		
	}, 5000);
});
}
function jsGoto(url){
document.location.href=url;	
}
function toggleStats(api){
//add_new_key
jQuery(document).ready(function($) {
	$("#api_stats_"+api).toggle();
});
}
	
function toggleApi(api, state){
//add_new_key
jQuery(document).ready(function($) {
	$('#apikey_'+api).block({
    message: 'Please wait...',
});
	
	//switch the style based on response
			//ajax the result
		var data = {
			'action': 'wpmm_toggle_api',
			'apikey': api
		};

				jQuery.post(wpmm.ajaxurl, data, function(response) {
					
		if (response>0) {
		//enable
		$('#apikey_'+api).removeClass('disableddiv');
		
	} else {
		//disable
		$('#apikey_'+api).addClass('apidiv disableddiv');
	}		

		$('#apikey_'+api).unblock();
				});
	
	
	//do the AJAX call
	
	
	
});
}
function viewApiSecret(api){
//add_new_key
jQuery(document).ready(function($) {
	
$('#apikey_'+api).block({
    message: 'Please wait...',
});
	//get the scret from AJAX
		var data = {
			'action': 'wpmm_view_secret',
			'apikey': api
		};
				jQuery.post(wpmm.ajaxurl, data, function(response) {
		$('#api_sec_box_'+api).val(response);
		$('#api_sec_'+api).show();
		$('#apikey_'+api).unblock();
		
			setTimeout(function() {
				$('#api_sec_'+api).hide();
			}, 10000);
				});
	
	// display to screen
	
	//timer tohide/unblock again,
	
});
}

function addKey(){
//add_new_key
jQuery(document).ready(function($) {
	$("#add_new_key").slideToggle();
});
}
function jsVidStage(stage){
jQuery(document).ready(function($) {
		if(stage == 1){
	$('#vid_1').block({
    message: '<h4>Video Stage Completed.</h4>',
});	
	$('#vid_2').unblock();	
	
		}
		if(stage == 2){
	$('#vid_1').block({
    message: '<h4>Video Stage Completed.</h4>',
});	
	$('#vid_2').block({
    message: '<h4>Video Stage Completed.</h4>',
});	
	$('#vid_3').unblock();	
	
		}
		if(stage == 3){
	$('#vid_1').block({
    message: '<h4>Video Stage Completed.</h4>',
});	
	$('#vid_2').block({
    message: '<h4>Video Stage Completed.</h4>',
});	
	$('#vid_3').block({
    message: '<h4>Video Stage Completed.</h4>',
});	
	$('#vid_4').unblock();	
	
		}
		if(stage == 4){
	$('#vid_1').block({
    message: '<h4>Video Stage Completed.</h4>',
});	
	$('#vid_2').block({
    message: '<h4>Video Stage Completed.</h4>',
});	
	$('#vid_3').block({
    message: '<h4>Video Stage Completed.</h4>',
});	
	$('#vid_4').block({
    message: '<h4>Video Stage Completed.</h4>',
});	
	
		}
		//ajax the result
		var data = {
			'action': 'wpmm_update_videostage',
			'stage': stage
		};

				jQuery.post(wpmm.ajaxurl, data, function(response) {

				});
		
});
}

jQuery(document).ready(function($) {
	
	//dialogs
  $("#dialog").dialog({
   autoOpen: false,
   modal: true,
   width: "70%"
});
	
	$("#opendialog").click(function() {
    $("#dialog").dialog("open");
    return false;
  });
  
	//dialogs
	
	var get_stat = document.getElementById('getstat');
	
	if(get_stat){
	var getstat = $("#getstat").val();

		/*
		$('#stats_'+getstat).block({
    message: 'Loading Stats...',
});
	*/

	}
	
	var err_msg = document.getElementById('err_msg');
	
	if(err_msg){
			$("#add_new_key").slideToggle();

	}
	
	$("#my_inactive_profile").click(function() {
		alert('under construction');
	});
	
	var vid_stage = $("#vid_stage").val();
	
	if(vid_stage == 0){
		
	$('#vid_2').block({
    message: '<h4>Complete other sections first.</h4>',
});
	$('#vid_3').block({
    message: '<h4>Complete other sections first.</h4>',
});
	$('#vid_4').block({
    message: '<h4>Complete other sections first.</h4>',
});	

	}
	if(vid_stage == 1){
		
	$('#vid_1').block({
    message: '<h4>Complete other sections first.</h4>',
});
	$('#vid_3').block({
    message: '<h4>Complete other sections first.</h4>',
});
	$('#vid_4').block({
    message: '<h4>Complete other sections first.</h4>',
});	

	}
	if(vid_stage == 2){
		
	$('#vid_1').block({
    message: '<h4>Complete other sections first.</h4>',
});
	$('#vid_2').block({
    message: '<h4>Complete other sections first.</h4>',
});
	$('#vid_4').block({
    message: '<h4>Complete other sections first.</h4>',
});	

	}
		if(vid_stage == 3){
		
	$('#vid_1').block({
    message: '<h4>Complete other sections first.</h4>',
});
	$('#vid_2').block({
    message: '<h4>Complete other sections first.</h4>',
});
	$('#vid_3').block({
    message: '<h4>Complete other sections first.</h4>',
});	

	}
			if(vid_stage == 4){
		
	$('#vid_1').block({
    message: '<h4>All Sections Complete</h4>',
});
	$('#vid_2').block({
    message: '<h4>All Sections Complete</h4>',
});
	$('#vid_3').block({
    message: '<h4>All Sections Complete</h4>',
});
	$('#vid_4').block({
    message: '<h4>All Sections Complete</h4>',
});	

	}
	

});
