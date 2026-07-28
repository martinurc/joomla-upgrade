jQuery(document).ready(function($){
	// Name Field
	$('#jform_firstname,#jform_secondname,#jform_lastname').change(function(){
		var name='';
		if($('#jform_firstname').length && $('#jform_firstname').val()!='') name=name+$('#jform_firstname').val();
		if($('#jform_secondname').length && $('#jform_secondname').val()!='') name=name+' '+$('#jform_secondname').val();
		if($('#jform_lastname').length && $('#jform_lastname').val()!='') name=name+' '+$('#jform_lastname').val();
		$('#jform_name').val(name);
	});
	// Url Fields
	$('input[type="url"]').blur(function(){
		var value=$(this).val();
		if(value!='' && value.indexOf('http://')<0 && value.indexOf('https://')<0) $(this).val('http://'+value);
	});
	// Add Empty Class for empty fields with validation
	$('.validate-email,.validate-pattern,.validate-phone,.validate-confirmpassword,.validate-confirmemail').bind('change blur',function(){
		if($(this).val()=='') $(this).addClass('empty-val');
		else $(this).removeClass('empty-val');
	});
	// Normalize Form
	if($("#member-profile .control-group,#member-profile .form-group,#member-registration .control-group,#member-registration .form-group").length==0){
		$("#member-registration .control-label").next().addClass("controls").each(function(){
			if($(this).parent(".privacy").length==0) $(this).parent().attr("class","control-group");
			else $(this).parent().attr("class","control-group privacy");
		});

	}
	// Hide Labels
	$('#member-profile .no-title, #member-registration .no-title').closest('.control-label').hide();
	// Patter Validator
	try {
		document.formvalidator.setHandler('pattern', function(value, element) {
				    value = punycode.toASCII(value);
		 	 	 	var regex = new RegExp('^'+element.attr('jsn-pattern')+'$', "g");
		 	 	 	return regex.test(value);
 	 	 	
 	 	});
	}
	catch(err) {}
	// Phone Validator
	try {
		document.formvalidator.setHandler('phone', function(value, element) {
				    value = punycode.toASCII(value);
				    value = value.replace(/[+. \-(\)]/g,'');
		 	 	 	var regex = new RegExp('^[0-9]{7,15}?$', "g");
		 	 	 	return regex.test(value);
 	 	 	
 	 	});
	}
	catch(err) {}
	// Confirm Password
	try {
		document.formvalidator.setHandler('confirmpassword', function(value, element) {
				    if($('#jform_password').length) $first = $('#jform_password').val();
				    else $first = $('#jform_password1').val();
				    if(value!=$first) return false;
				    else return true;
 	 	});
	}
	catch(err) {}
	// Confirm Email
	try {
		document.formvalidator.setHandler('confirmemail', function(value, element) {
				    $first = $('#jform_email1').val();
				    if(value!=$first) return false;
				    else return true;
 	 	});
	}
	catch(err) {}

	// File
	try {
		document.formvalidator.setHandler('fileext', function(value, element) {
				    var regex = new RegExp('(' + element.attr('jsn-extensions') + ')$');
		 	 	 	return regex.test(value);
		
 	 	});
	}
	catch(err) {}

});
jQuery(window).load(function(){
	if(jQuery('#user-form[name="adminForm"],#profile-form[name="adminForm"]').length){
		jQuery('#myTabTabs a:not([href*="jsn_"])').each(function(){
			jQuery('#myTabTabs').append(jQuery(this).parent());
		});
		jQuery('#myTabTabs a[href="#details"],#myTabTabs a[href="#account"]').prepend('<i style="margin-right: .25em;" class="icon icon-joomla"></i>');
		jQuery('#myTabTabs a[href="#groups"]').prepend('<i style="margin-right: .25em;" class="icon icon-users"></i>');
		jQuery('#myTabTabs a[href="#settings"],#myTabTabs a[href="#attrib-settings"]').prepend('<i style="margin-right: .25em;" class="icon icon-cog"></i>');
		jQuery('#myTabTabs a[href*="jsn_"]').prepend('<i style="margin-right: .25em;" class="icon icon-user"></i>');
		jQuery('#myTabTabs a').click(function(){
			jQuery(this).tab('show');
			setTimeout(function(){jQuery('.jsn_map:visible:not([data-loaded])').each(function(){
				jQuery(this).attr("data-loaded","true");
				var id=jQuery(this).attr("id").substr(4);
				var center = jQuery("#jform_"+id).geocomplete('map').getCenter();
				google.maps.event.trigger(jQuery("#jform_"+id).geocomplete('map'), "resize");
				jQuery("#jform_"+id).geocomplete('map').setCenter(center);
				jQuery("#jform_"+id).geocomplete('map').setZoom(jQuery("#jform_"+id).geocomplete('options').maxZoom);
			})
		},100);
			return false;
		});
		if(jQuery('#details.active,#account.active').length) jQuery('#myTabTabs li:first a').click();
	}
});