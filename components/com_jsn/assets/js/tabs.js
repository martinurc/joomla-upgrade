jQuery(document).ready(function($){
	if($('#member-registration #jform_privacyconsent_privacy').length) $('#member-registration #jform_privacyconsent_privacy').parent().closest('fieldset').insertAfter('#jform_privacyposition');
	if($('#member-profile #jform_privacyconsent_privacy').length) $('#member-profile #jform_privacyconsent_privacy').parent().closest('fieldset').addClass('notabs').css('display','block');
	$('#member-registration > fieldset:not(.notabs),#member-profile > fieldset:not(.notabs),.jsn-p-fields > fieldset').addClass('jsn-form-fieldset').wrapAll('<div id="jsn-form" />');
	$('#member-registration >  .control-group .controls,#member-profile > .control-group .controls').addClass('jsn_registration_controls').append('<div style="clear:both"></div>');
	$('#member-registration .form-actions a,#member-registration > .control-group a').hide();
	$('#jsn-form').wrapInner('<div />').prepend('<ul id="jsn-profile-tabs"></ul>');
	$('.jsn-form-fieldset').wrap('<div />');
	$('.jsn-form-fieldset > legend').hide();
	var index=0;
	$('.jsn-form-fieldset').each(function(){
		var tabTitle=String($(this).find('legend:first').html());
		$(this).find('legend:first span').remove();
		var tabSlug=String($(this).find('legend:first').text());
		if(tabTitle=='') tabTitle=String($(this).find('[id$="lbl"]:first').text()).replace('*','').trim();
		var tabSlug=tabSlug.toLowerCase().replace(/[^\w ]+/g,'').replace(/ +/g,'-');
		if(tabSlug.length < 5) tabSlug='profile-tab'+index;
		if($('#jsn-profile-tabs li[data-link="'+tabSlug+'"]').length) tabSlug+=index;
		$(this).attr('data-index',index);
		if($('#member-registration').length && stepbystep) $(this).attr('data-name','tab'+(index+1));
		else $(this).attr('data-name',tabSlug);
		$('#jsn-profile-tabs').append('<li data-index="'+index+'" data-link="'+tabSlug+'"><a>'+tabTitle+'</a></li>');
		index+=1;
	});

	$(function(){
		var position='top-compact';
		var bordered=false;
		var mobileNav=false;
		if(index>5) mobileNav=true;
		var defaultTab=$('.jsn-form-fieldset').not('.hide').first().attr('data-name');
		var deeplinking=true;
		if($('#member-registration').length && stepbystep) deeplinking=false;

		$("#jsn-form").zozoTabs({
			maxRows: 10,
	        position: position,
	        defaultTab: defaultTab,
	        style: "clean",
	        theme: "flat",
	        spaced: true,
	        bordered: bordered,
	        rounded: false,
	        deeplinking: deeplinking,
	        mobileNav: mobileNav,
	        select: function(){tabs($);},
	        animation: {
	            easing: "easeInOutExpo",
	            duration: 300,
	            effects: "fade"
	        },
	        size:"mini"
	    });
    });

    
	
	$('#member-registration [type="submit"],#member-profile [type="submit"]').click(function(){
		if($('#member-registration [type="submit"]:hidden,#member-profile [type="submit"]:hidden').length) return false;
		var found=false;
		$('#jsn-profile-tabs li a').removeClass('jsninvalidfieldgroup');
		$('.jsn-form-fieldset .invalid').each(function(){
			if(!found){
				if(jQuery(window).scrollTop()>100)
					jQuery('html, body').animate({
					         scrollTop: jQuery("#system-message-container").offset().top-100
					     }, 300);
				$("#jsn-form").data('zozoTabs').select($(this).parents('.jsn-form-fieldset').attr('data-index'));
			}
			$('#jsn-profile-tabs li[data-link="'+$(this).parents('.jsn-form-fieldset').attr('data-name')+'"] a').addClass('jsninvalidfieldgroup');
			found=true;
		});
		
	});
	
	//tabs($);
});
function tabs($){
	$('.jsn-form-fieldset').each(function(){
		if($(this).is('.hide')){
			$('li[data-index="'+$(this).attr('data-index')+'"] a').parent().hide().addClass('hide');
		}
		else{
			$('li[data-index="'+$(this).attr('data-index')+'"] a').parent().show().removeClass('hide');
		}
	});
	var first=$('.jsn-form-fieldset').not('.hide').first().attr('data-index');
	var last=$('.jsn-form-fieldset').not('.hide').last().attr('data-index');
	var active=$('.z-content.z-active .jsn-form-fieldset').not('.hide').first().attr('data-index');

	if(active==undefined){
		active=first;
	}

	$('.next-button').remove();
	$('.prev-button').remove();

	if($('#member-registration').length && active==last){
		$('#member-registration .form-actions button[type="submit"],#member-registration > .control-group button[type="submit"]').show().addClass('pull-right');
		if(active!=first) $('#member-registration .form-actions button[type="submit"],#member-registration > .control-group button[type="submit"]').before(' <a class="btn btn-default prev-button pull-left" href="#">'+jsn_prev_button+'</a> ');
	}
	else if($('#member-registration').length && active==first){
		$('#member-registration .form-actions button[type="submit"],#member-registration > .control-group button[type="submit"]').hide().addClass('pull-right');
		$('#member-registration .form-actions button[type="submit"],#member-registration > .control-group button[type="submit"]').before(' <a class="btn btn-default next-button pull-right" href="#">'+jsn_next_button+'</a> ');
	}
	else if($('#member-registration').length){
		$('#member-registration .form-actions button[type="submit"],#member-registration > .control-group button[type="submit"]').hide().addClass('pull-right')
			.before(' <a class="btn btn-default prev-button pull-left" href="#">'+jsn_prev_button+'</a> ')
			.before(' <a class="btn btn-default next-button pull-right" href="#">'+jsn_next_button+'</a> ');
	}

	if($('#member-registration').length && stepbystep) {
		$('li.z-tab a').click(function(){
			var tab=$(this).parent().attr('data-index');
			for(i=0;i<tab;i++) {
				if(!document.formvalidator.isValid('.jsn-form-fieldset[data-index="'+i+'"]')) {
					$("#jsn-form").data('zozoTabs').select(i);
					return false;
				}
			}
	    });
	}

	$('.next-button').click(function(){
		if(stepbystep) {
			if(!document.formvalidator.isValid('.jsn-form-fieldset[data-index="'+active+'"]')) {
				if(jQuery(window).scrollTop()>100)
					jQuery('html, body').animate({
					         scrollTop: jQuery("#system-message-container").offset().top-100
					     }, 300);
				return false;
			}
		}
		var next=true;
		for (i = active; i <= last; i++) { 
    		if(i!=active && next && !$('.jsn-form-fieldset[data-index="'+i+'"]').is('.hide')) {
    			$("#jsn-form").data('zozoTabs').select(i);
    			next=false;
    		}
		}
		if(jQuery(window).scrollTop()>100)
			jQuery('html, body').animate({
			         scrollTop: jQuery("#jsn-profile-tabs").offset().top-100
			     }, 300);
		return false;
	});
	$('.prev-button').click(function(){
		var prev=true;
		for (i = active; i >= first; i--) { 
    		if(i!=active && prev && !$('.jsn-form-fieldset[data-index="'+i+'"]').is('.hide')) {
    			$("#jsn-form").data('zozoTabs').select(i);
    			prev=false;
    		}
		}
		if(jQuery(window).scrollTop()>100)
			jQuery('html, body').animate({
			         scrollTop: jQuery("#jsn-profile-tabs").offset().top-100
			     }, 300);
		return false;
	});

	if($('#member-registration').length || $('#member-profile').length)
		setTimeout(function(){$('.jsn_map:visible:not([data-loaded])').each(function(){
				$(this).attr("data-loaded","true");
				var id=$(this).attr("id").substr(4);
				var center = $("#jform_"+id).geocomplete('map').getCenter();
				google.maps.event.trigger($("#jform_"+id).geocomplete('map'), "resize");
				$("#jform_"+id).geocomplete('map').setCenter(center);
				$("#jform_"+id).geocomplete('map').setZoom($("#jform_"+id).geocomplete('options').maxZoom);
			})
		},100);
	else
		setTimeout(function(){$('.jsn_map:visible:not([data-loaded])').each(function(){
				$(this).attr("data-loaded","true");
				var id=$(this).attr("id").substr(4);
				var center = $("."+id+"Value").geocomplete('map').getCenter();
				google.maps.event.trigger($("."+id+"Value").geocomplete('map'), "resize");
				$("."+id+"Value").geocomplete('map').setCenter(center);
				$("."+id+"Value").geocomplete('map').setZoom($("."+id+"Value").geocomplete('options').maxZoom);
			})
		},100);
	if($("#jsn-form").data('zozoTabs')) $("#jsn-form").data('zozoTabs').refresh();
	var tabheight = 0;
	$('ul#jsn-profile-tabs li a').each(function(){
		var h = $(this).height();
		if(h > tabheight) tabheight = h;
	});
	$('ul#jsn-profile-tabs li a').css('min-height',tabheight+'px');
	return;
}