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
		var ctrl = this;
		$toggleMeMenu.trigger('click');
		ctrl.active($toggleMeMenu.hasClass('on'));

		if(self.firstInit && ctrl.active()){
			var alertQM = tools.getPhrase('quoteme_alert');
			if(alert.length){
				ed.windowManager.alert(alertQM);
			}
			self.firstInit = false;
		}
	}

	function extBtnState(e){
		var ctrl = this;

		var activeMe = function(){
			ctrl.active($toggleMeMenu.hasClass('on'));
		 	var args = {
				skip_focus: true
			};
			ed.execCommand('resetFright', false, false, args);			
		}

		 $toggleMeMenu.click(function(e){
		 	activeMe();
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
				var ctrl = this;
				$.extend(e.control.settings, ed.buttons[quoteme]);
				$.extend(ctrl, {'extBtnState': extBtnState });

				ctrl.active($toggleMeMenu.hasClass('on'));//First init, don't put it in the extra fct - creates a bug with the button
				ctrl.parent().on('show', function() {
					ctrl.extBtnState(e);				
				});
			}
		})
	);
});