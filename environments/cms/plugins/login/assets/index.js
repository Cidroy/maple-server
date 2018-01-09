const maple_login = {
	loading: '<div data-role="preloader" data-type="square" data-style="dark"></div>',
	notify: function(data){
		if(data.notify) Materialize.toast('<i class="material-icons">'+data.notify.icon+'</i>'+data.notify.caption+'<br>'+data.notify.content, 4000,(data.type=='success'?'success':'error'))
		if(data.maple){
			if(data.maple.location) window.location.href=data.maple.location+"#";
			if(data.maple.addclass)
				data.maple.addclass.object.forEach(function(e,i){
					$(data.maple.addclass.selector.replace('{{object}}',e)).addClass(data.maple.addclass.class);
				});
		}
	},
	afaj: function(f){
		var that = this;
		f.find('.error').removeClass('error');
		$.ajax({
			url : f.attr('action'),
			type: f.attr('method'),
			data: f.serialize()+'&ajax=true',
			dataType : 'json',
			timeout  : 10000,
			success: function(data){
				if(data.type=="error"){
					data.notify = {
						icon: "warning",
						caption: "",
						content: data.message,
					};
				}
				if(data.type=="success"){
					data.maple = {
						location: data.redirect_to,
					}
				}
				that.notify(data);
			},
			error: function(data){
				Materialize.toast('<i class="material-icons">warning</i>An Error has occured<br>We were unable to connect. Please check your internet connection.', 4000,'alert');
			}
		});
	}
};


(function(){
	$('body').delegate('form[data-form=login]','submit',function(e){
		var f = $(this);
		maple_login.afaj(f);
		return false;
	});
	$('body').delegate('form[data-form=sign-up]','submit',function(e){
		var f = $(this);
		maple_login.afaj(f);
		return false;
	});
})();
