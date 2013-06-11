xenMCE.Templates.SmiliesSlider = {
	init: function($overlay, data, editor, parentClass)
	{
		$overlay.find('.xenSubmit').remove();
		$smilies = $overlay.find('.smilies_slide > a');

		$smilies.click(function(e){
			e.preventDefault();
			editor.execCommand('mceInsertContent', false, $(this).html());
		});
	}
}
