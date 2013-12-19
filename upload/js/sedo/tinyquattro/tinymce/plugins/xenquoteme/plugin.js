tinymce.PluginManager.add('xenquoteme', function(ed) {

	if (XenForo.isTouchBrowser()){
		return false;
	}

	var quoteme = 'quoteme',
		$toggleMeMenu = $('#toggleMeMenu'),
		self = this;
		
	if(!$toggleMeMenu.length){
		return false;
	}

	self.firstInit = true;
		
	function onClick(e){
		$toggleMeMenu.trigger('click');
		this.active($toggleMeMenu.hasClass('on'));
		
		if(self.firstInit){
			//ed.windowManager.alert('test');
			self.firstInit = false;
		}
	}

	ed.addButton('quoteme', {
		name: quoteme,
		icon: quoteme,
		onclick: onClick,
		xenfright: true
	});
});