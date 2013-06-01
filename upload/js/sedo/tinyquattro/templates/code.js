xenMCE.Templates.Code = {
	submit: function(e, $ovl, ed, src)
	{
		var data = e.data, type = data.type, code = src.escapeHtml(data.code), tag, output;
		
		switch (type){
			case 'html': tag = 'HTML'; break;
			case 'php':  tag = 'PHP'; break;
			default:     tag = 'CODE';
		}
		
		output = '[' + tag + ']' + code + '[/' + tag + ']';

		if (output.match(/\n/))
			output = '<p>' + output + '</p>';
			
		ed.execCommand('mceInsertContent', false, output);

	}
}
