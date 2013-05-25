
window.addEvent('domready', function(){

	// Set the hidden "x_exp_date" field's value when the month/year are selected
	var expmonth = $('ctrl_card_expirationMonth');
	var expyear = $('ctrl_card_expirationYear');

	if (expmonth && expyear)
	{
		expmonth.addEvent('blur', setExpDate);
		expyear.addEvent('blur', setExpDate);
	}
	
	// Set the action back to the normal action when the user clicks "Back"
	if ($$('input.submit.previous').length > 0)
	{
		$$('input.submit.previous').each(function(item){
		
			item.addEvent('click', function(){
				
				var prevUrl = $$('input[type="hidden"][name="iso_redirect_url"]').get('value');
				
				if (prevUrl.length > 0 && prevUrl.indexOf('ajax.php') == -1)
				{
					this.getParent('form').setProperty('action', prevUrl);
				}
				
			});
		
		});
	}
	
	if ($$('input.submit.confirm').length > 0)
	{
		$$('input.submit.confirm').each(function(item){
		
			item.setProperty('onclick', 'setExpDate();');
		
		});
	}
	
});


function setExpDate()
{
	try
	{
		var expmonth = $('ctrl_card_expirationMonth');
		var expyear = $('ctrl_card_expirationYear');
		var expval = expmonth.get('value') + expyear.get('value').substr(2,2);
		
		var exp = $$('input[type="hidden"][name="x_exp_date"]');
		if (exp.length > 0)
			exp[0].set('value', expval);
	}
	catch(err) {}
}