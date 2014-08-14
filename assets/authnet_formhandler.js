(function($){
window.addEvent('domready', function(){

	// Set the hidden "x_exp_date" field's value when the month/year are selected
	var expmonth = document.id('ctrl_card_expirationMonth');
	var expyear = document.id('ctrl_card_expirationYear');

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
		
			item.setProperty('onclick', 'setExpDate(); this.getParent().setStyle(\'display\', \'none\');');
		
		});
	}
	
	// CIM logic
	if (document.id('ctrl_paymentProfile'))
	{
		var radios = document.id('ctrl_paymentProfile').getElements('input.radio');
		
		if (radios.length)
		{
			radios.each(function(item, index)
			{
				item.addEvent('click', function()
				{
					var type = this.get('value');
					var parents = document.id('ctrl_paymentProfile').getParents('.paymentmethod');
					
					if (parents.length)
					{
						var parent = parents[0];
						var fields = parent.getElements('.ccField, .bankField');
						var tableless = document.id('ctrl_paymentProfile').getParents('table').length == 0 || document.id('ctrl_paymentProfile').getParents('table')[0].getChildren('tr, tbody').length == 0;
	
						for (var j = 0; j < fields.length; j++)
						{
							if (tableless)
							{
								fields[j].setStyle('display', 'none');
							}
							else
							{
								fields[j].getParent().getParent().setStyle('display', 'none');
							}
						}
						
						fields = parents[0].getElements('.'+type+'Field');
						
						for (var j = 0; j < fields.length; j++)
						{
							if (tableless)
							{
								fields[j].setStyle('display', 'block');
							}
							else
							{
								fields[j].getParent().getParent().setStyle('display', 'table-row');
							}
						}
					}
				});
			});
		}
		
		// Fire default event
		if (!document.id('ctrl_paymentProfile').getElement('input:checked') && radios.length)
		{
			radios[0].click();
		}
		// Had issues with IE8
		if (document.id('ctrl_paymentProfile').getElement('input:checked'))
		{
			document.id('ctrl_paymentProfile').getElement('input:checked').fireEvent('click');
		}
	}
	
});


function setExpDate()
{
	try
	{
		var expmonth = document.id('ctrl_card_expirationMonth');
		var expyear = document.id('ctrl_card_expirationYear');
		var expval = expmonth.get('value') + expyear.get('value').substr(2,2);
		
		var exp = $$('input[type="hidden"][name="x_exp_date"]');
		if (exp.length > 0)
			exp[0].set('value', expval);
	}
	catch(err) {}
}
})(document.id);