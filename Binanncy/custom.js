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
function admExport(){
jQuery(document).ready(function($) {
		var data = {
			'action': 'wpb_export',
			'_nonce': wpmm.nonce
		};

				jQuery.post(ajaxurl, data, function(response) {
					setTimeout(function() {
						
						jsDeleteFile(response);
						
					}, 10000);
					document.location.href=response;
					
				});	
});
}
function jsDeleteFile(f){
jQuery(document).ready(function($) {
		var data = {
			'action': 'wpb_delete_file',
			'file': f
		};

				jQuery.post(ajaxurl, data, function(response) {
					
				});	
});	
}
function syncCommas(){
jQuery(document).ready(function($) {
		var data = {
			'action': 'wpb_sync_commas',
			'_nonce': wpmm.nonce
		};

				jQuery.post(ajaxurl, data, function(response) {
					alert(response);
				});	
});

}
function toggleKeyState(rl){
jQuery(document).ready(function($) {
		var data = {
			'action': 'wpb_toggle_keystate',
			'keyid': rl
		};

				jQuery.post(ajaxurl, data, function(response) {

				});
});
}
function jsSyncComma(keyid){
jQuery(document).ready(function($) {
	
$("#comma_"+keyid).html('Connecting to 3commas...');

		var data = {
			'action': 'binanncy_sync_comma',
			'_wpnonce': wpmm.nonce,
			'apikey': keyid
		};

				jQuery.post(ajaxurl, data, function(response) {
					//notification...
				$("#comma_"+keyid).html(response);

				});
	
});
}
function admDeleteKey(elem){
jQuery(document).ready(function($) {
	
	var key = elem.split('_');
	
		var data = {
			'action': 'wpmm_admin_deletekey',
			'_wpnonce': wpmm.nonce,
			'apikey': key[1]
		};

				jQuery.post(ajaxurl, data, function(response) {
					//notification...
				document.location.href='';

				});
});
}
function admViewSecret(val){
jQuery(document).ready(function($) {
	
	var elem = val.split('_');
	
	var fld = elem[0]+"_"+elem[1]+"_"+elem[2];
	var apikey = elem[2];
	var secret = elem[3];
	
	$("#"+fld).val('Loading...');
	
	setTimeout(function() {
		$("#"+fld).val(secret);
		setTimeout(function() {
			$("#"+fld).val(apikey);
		}, 10000);
	}, 2000);
	
});
}
function swapForm(){
jQuery(document).ready(function($) {
		var form = $("#rule_type").val();
alert(form);

		if (form == 'time'){
		$("#form_frame_flood").hide();
		$("#form_frame_time").show();
		}
		if (form == 'flood'){
		$("#form_frame_time").hide();
		$("#form_frame_flood").show();
		}
});
}

function automon(){
jQuery(document).ready(function($) {
		var data = {
			'action': 'wpmm_admin_automon',
			'_wpnonce': wpmm.nonce
		};

				jQuery.post(ajaxurl, data, function(response) {
					//notification...
				$("#jax_throttle_mon").html(response);

				setTimeout(function(){
				automon();
				}, 5000);
				});
});
}
jQuery(document).ready(function($) {
	
	$("#my_inacive_profile").click(function() {
		alert('under construction');
	});

$(".schange").change(function(){
$("#form_frame_flood").toggle();
$("#form_frame_time").toggle();
});

var autom = document.getElementById('jax_throttle_mon');

if (autom){
	automon();
}

$('.custom_date').datepicker({
dateFormat : 'yy-mm-dd'
});


$('.ttip').tooltip();


		//wpmm_admin_deletelog
		$("#wpmm_admin_clearthrottle").click(function() {

		var data = {
			'action': 'wpmm_admin_clearthrottle',
			'_wpnonce': wpmm.nonce
		};

				jQuery.post(ajaxurl, data, function(response) {
					//notification...
				$("#jax_msg").html(response);
				$("#set_saved").show();
				setTimeout(function(){
				$("#set_saved").hide();
				$("#jax_msg").html('Setting Changed!');
				document.location.href='';
				}, 5000);
				});
		
		});
		//wpmm_admin_clearlogs

		//wpmm_admin_deletelog
		$("#wpmm_admin_deletelog").click(function() {

		var data = {
			'action': 'wpmm_admin_deletelog',
			'_wpnonce': wpmm.nonce
		};

				jQuery.post(ajaxurl, data, function(response) {
					//notification...
				$("#jax_fsize").html('Delete Log File [0 KB]');
				$("#jax_msg").html(response);
				$("#set_saved").show();
				setTimeout(function(){
				$("#set_saved").hide();
				$("#jax_msg").html('Setting Changed!');
				}, 5000);
				});
		
		});
		//wpmm_admin_clearlogs

		$("#wpmm_admin_clearlogs").click(function() {

		var data = {
			'action': 'wpmm_admin_clearlogs',
			'_wpnonce': wpmm.nonce
		};

				jQuery.post(ajaxurl, data, function(response) {
					//notification...
				$("#jax_msg").html(response);
				$("#set_saved").show();
				setTimeout(function(){
				$("#set_saved").hide();
				$("#jax_msg").html('Setting Changed!');
				document.location.href='';
				}, 5000);
				});
		
		});

		$("#wpmm_nset").click(function() {

		var data = {
			'action': 'toggle_setting',
			'setting': 'wpmm_throttle_notifications'
		};

				jQuery.post(ajaxurl, data, function(response) {
					//notification...
				$("#set_saved").show();
				setTimeout(function(){
				$("#set_saved").hide();
				}, 5000);
				});
		
		});

		//wpmm_authset
		$("#wpmm_authset").click(function() {

		var data = {
			'action': 'toggle_setting',
			'setting': 'wpmm_setting_smtp_auth'
		};

				jQuery.post(ajaxurl, data, function(response) {
					//notification...
				$("#set_saved").show();
				setTimeout(function(){
				$("#set_saved").hide();
				}, 5000);
				});
		
		});


		$("#wpmm_eset").click(function() {

		var data = {
			'action': 'toggle_setting',
			'setting': 'wpmm_email_logging'
		};

				jQuery.post(ajaxurl, data, function(response) {
					//notification...
				$("#set_saved").show();
				setTimeout(function(){
				$("#set_saved").hide();
				}, 5000);
				});
		
		});

		//wpmm_throttle_protection
		$("#wpmmadm_tset").click(function() {

		var data = {
			'action': 'toggle_setting',
			'setting': 'wpmm_api_enabled'
		};

				jQuery.post(ajaxurl, data, function(response) {
					//notification...
					alert('Setting Saved.');
				});
		
		});
		$("#autocomms").click(function() {

		var data = {
			'action': 'toggle_setting',
			'setting': 'autocomms'
		};

				jQuery.post(ajaxurl, data, function(response) {
					//notification...
				$("#set_saved").show();
				setTimeout(function(){
				$("#set_saved").hide();
				}, 5000);
				});
		
		})
		$("#wpmm_tset").click(function() {

		var data = {
			'action': 'toggle_setting',
			'setting': 'wpmm_api_enabled'
		};

				jQuery.post(ajaxurl, data, function(response) {
					//notification...
				$("#set_saved").show();
				setTimeout(function(){
				$("#set_saved").hide();
				}, 5000);
				});
		
		});
		//testtheapi
		$("#testtheapi").click(function() {

		var data = {
			'action': 'testtheapi'
		};

				jQuery.post(ajaxurl, data, function(response) {
					//notification...
					alert(response);
				});
		
		});
});