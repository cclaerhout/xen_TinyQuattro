xenMCE.Templates.Bbm_demo = {
	onafterload: function($overlay, data, editor, parentClass)
	{
		console.log('OnLoad Callback - Overlay element is:');
		console.log($overlay);
		console.log(data);
	},
	submit: function(event, $overlay, editor, parentClass)
	{
		var tag = parentClass.bbm_tag, data = event.data, content = data.content, options = data.options;
		
		console.log('Submit Function');
		console.log('tag is:'+tag);
		console.log('Use ParentClass insertBbCode function');
		parentClass.insertBbCode(tag, options, content);
	}
}
