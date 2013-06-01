xenMCE.Templates.Media = {
	submit: function(e, $ovl, ed, src)
	{
		var url = e.data.url;
		this.ed = ed;
		
		if(!url)
			return false;
		
		XenForo.ajax(
			'index.php?editor/media',
			{ url: url },
			$.proxy(this, 'insert')
		);	
	},
	insert: function(ajaxData)
	{
		if (XenForo.hasResponseError(ajaxData))
			return false;
		
		if (ajaxData.matchBbCode){
			this.ed.execCommand('mceInsertContent', false, ajaxData.matchBbCode);
		}
		else if (ajaxData.noMatch)
		{
			this.ed.windowManager.alert(ajaxData.noMatch);
		}
	
	}
}
