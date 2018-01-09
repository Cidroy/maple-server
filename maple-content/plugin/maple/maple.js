var ROOT="<?php echo URL::http("%ROOT%")?>";
var loading = '<div data-role="preloader" data-type="square" data-style="dark"></div>';

function Notify(data){
	if(data.notify){
		Materialize.toast('<i class="material-icons">'+data.notify.icon+'</i>'+data.notify.caption+'<br>'+data.notify.content, 4000,(data.type=='success'?'success':'alert'))
	}
	if(data.maple){
		if(data.maple.location) window.location.href=data.maple.location+"#";
		if(data.maple.addclass) 
			data.maple.addclass.object.forEach(function(e,i){
				$(data.maple.addclass.selector.replace('{{object}}',e)).addClass(data.maple.addclass.class); 
			}); 
	}
}

function AFAJ(f){
	f.find('.error').removeClass('error');
	$.ajax({
		url : f.attr('action'),
		type: f.attr('method'),
		data: f.serialize(),
		dataType : 'json',
		timeout  : 10000,
		success: function(data){
			Notify(data);
			return data;
		},
		error: function(data){
			Materialize.toast('<i class="material-icons">warning</i>An Error has occured<br>We were unable to connect. Please check your internet connection.', 4000,'alert');
			return data;
		}
	});
}

(function(){
	$('body').delegate('[data-maple-link]','click',function(){
		var t = $(this);
		window.open(t.attr('href'),t.attr('target'));
	});
})();