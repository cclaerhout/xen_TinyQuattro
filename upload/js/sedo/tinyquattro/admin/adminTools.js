!function($, window, document, undefined)
{    
	if(typeof xenMCE === 'undefined') xenMCE = {};
	
	xenMCE.Admin = {
		forceButtonsToogler: function($e)
		{
			var target = $e.data('target');
			$e.siblings(target).hide();
			
			$e.click(function(){
				var $this = $(this),
					target = $this.data('target'),
					$target = $this.siblings(target);

				$target.toggle();
			});	
		},
		forceButtonsInterface: function($li)
		{
			var $buttonItem = $li.find('.button_item'),
				$buttonName = $li.find('.button_name'),
				$input = $li.find('input');

			$buttonName.hide();
			
			if($input.is(':checked')){
				$buttonItem.addClass('active');
			}else{
				$buttonItem.removeClass('active');
			}

			$buttonItem.click(function(){
				var self = this;
				setTimeout(function(e){
					var $input = $(self).parents('li').find('input');
					if($input.is(':checked')){
						$(self).addClass('active');
					}else{
						$(self).removeClass('active');
					}
				}, 100);
			});
		}
	};

	XenForo.register('#quattro_force_button .Toggler', 'xenMCE.Admin.forceButtonsToogler');
	XenForo.register('#quattro_force_button .buttons_wrapper li','xenMCE.Admin.forceButtonsInterface');
}
(jQuery, this, document);