$('body').delegate('form[data-handle=login]','submit',function(){
	var f = $(this);
	var data = AFAJ(f);
	return false;
});

$('body').delegate('form[data-handle=sign-up]','submit',function(){
	var f = $(this);
	var data = AFAJ(f);
	return false;
});