xenMCE.Templates.Link = {
	submit: function(e, $ovl, ed, src)
	{
		//Type 0: none, Type 1: link, Type 2: mail
		var data = e.data, 
			dom = ed.dom, type = 0, href, text,
			orginalSel = xenMCE.Lib.overlay.getSelection(),
			initialText = orginalSel.url.text,
			anchorElm = orginalSel.url.anchorElm;

		if(orginalSel.isEmail)
			initialText = initialText.replace(/mailto:/i, '');

		if(data.ctrlUrlLink)
			type = 1;
				
		if(data.ctrlMailLink)
			type = 2;
				
		if(data.ctrlUrlLink && data.ctrlMailLink)
			type = $ovl.find('.mce-active').index() + 1;
				
		if(type == 1){
			href = data.ctrlUrlLink;
			text = data.ctrlUrlText;
	
			if (/^\s*www\./i.test(href)) {
				href = 'http://' + href;
			}
			
			if (!/^\s*(https?|ftp):/i.test(href)) {
				href = 'http://' + href;			
			}
		}

		if(type == 2){
			href = 'mailto:'+data.ctrlMailLink;
			text = data.ctrlMailText;
		}			

		if(type && !text)
			text = href;

		if (!href) {
			ed.execCommand('unlink');
			return;
		}
			
		function insertLink() {
			if (text != initialText) {
				if (anchorElm) {
					ed.focus();
					anchorElm.innerHTML = text;
						dom.setAttribs(anchorElm, {
						href: href,
						target: '_blank',
						rel: null
					});
					ed.selection.select(anchorElm);
				} else {
					ed.insertContent(dom.createHTML('a', {
						href: href,
						target: '_blank',
						rel: null
					}, text));
				}
			} else {
			ed.execCommand('mceInsertLink', false, {
					href: href,
					target: '_blank',
					rel: null
				});
			}
		}

		insertLink();
	},
	onfastreload: function($overlay, data, editor, parentClass)
	{
		var target = (data.isEmail) ? 1 : 0,
			$tabs = $overlay.find('.mceTabs').children();

		$tabs.eq(target).trigger('click');
	}
}
