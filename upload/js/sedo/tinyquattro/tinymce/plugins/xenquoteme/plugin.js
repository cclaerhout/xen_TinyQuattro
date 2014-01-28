tinymce.PluginManager.add('xenquoteme', function(ed) {
	var tools = xenMCE.Lib.getTools(),
		quoteme = 'quoteme',
		$toggleMeMenu = $('#toggleMeMenu'),
		self = this;
		
	//Prevent bbm message alert
	ed.buttons['quoteme'] = false;
	
	if(!$toggleMeMenu.length || $toggleMeMenu.is(':hidden')){
		return false;
	}

	self.firstInit = true;
		
	function onClick(e){
		$toggleMeMenu.trigger('click');
		this.active($toggleMeMenu.hasClass('on'));
		
		if(self.firstInit){
			var alertQM = tools.getPhrase('quoteme_alert');
			if(alert.length){
				ed.windowManager.alert(alertQM);
			}
			self.firstInit = false;
		}
	}

	function extBtnState(e){
		 var ctrl = this;

		 $toggleMeMenu.click(function(e){
		 	ctrl.active($toggleMeMenu.hasClass('on'));
		 	
		 	var args = {
				skip_focus: true
			};
			ed.execCommand('resetFright', false, false, args);
		 });
	}

	var quotemeConfig = {
		name: quoteme,
		icon: quoteme,
		onclick: onClick,
		onPostRender: extBtnState,
		xenfright: true
	};

	ed.addButton(quoteme, quotemeConfig);

	ed.addMenuItem(quoteme, $.extend({},
		quotemeConfig, {
			text: "Fast quotes",
			xenfright: false,
			onPostRender: function(e){
				$.extend(e.control.settings, ed.buttons[quoteme]);
			}
		})
	);
});