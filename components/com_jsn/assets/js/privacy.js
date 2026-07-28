jQuery(document).ready(function($){
	
	$('input.privacy').each(function(){
		$(this).parent().parent().addClass('privacy');
		$(this).parent().addClass('dropdown');
		var id=$(this).attr('id');
		if($(this).val()=='0') $('#btn_'+id+' > i').addClass('green jsn-icon jsn-icon-users');
		if($(this).val()=='1') $('#btn_'+id+' > i').addClass('orange jsn-icon jsn-icon-user');
		if($(this).val()=='99') $('#btn_'+id+' > i').addClass('red jsn-icon jsn-icon-user-secret');
		
		/*$('#btn_'+id).click(function(event){
			event.preventDefault();
			$('#opt_'+id).toggle();//toggleClass('privacy_menu_show');
		})*/
		
		$('#opt_'+id+' a').click(function(event){
			event.preventDefault();
			$('#'+id).val($(this).attr('rel'));
			$('#btn_'+id+' > i').attr('class','');
			if($('#'+id).val()=='0') $('#btn_'+id+' > i').addClass('green jsn-icon jsn-icon-users');
			if($('#'+id).val()=='1') $('#btn_'+id+' > i').addClass('orange jsn-icon jsn-icon-user');
			if($('#'+id).val()=='99') $('#btn_'+id+' > i').addClass('red jsn-icon jsn-icon-user-secret');
			$('#opt_'+id).hide();//removeClass('privacy_menu_show');
			$('#btn_'+id+' + ul').attr('style','');// JS conflict
			$('#btn_'+id).focus(); // Workaround for IE8
		});
	});
	
});