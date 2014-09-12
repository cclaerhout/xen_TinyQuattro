xenMCE.Templates.Link = {
	onafterload: function($overlay, data, editor, parentClass)
	{
		this._manageAnchors($overlay, parentClass);
	},
	onfastreload: function($overlay, data, editor, parentClass)
	{
		this._manageAnchors($overlay, parentClass);
		
		var target = (data.isEmail) ? 1 : 0,
			$tabs = $overlay.find('.mceTabs').children();

		$tabs.eq(target).trigger('click');
	},
	_manageAnchors: function($overlay, parentClass)
	{
		var $anchorsPane = $overlay.find('#xenpane_anchors'),
			$anchorsList = $anchorsPane.children('ul'),
			$anchorsListClone  = $anchorsList.clone().empty(),
			hasAnchors = false,
			hiddenClass = 'mceAnchorHidden',
			$allAnchorsEl = $overlay.find('.anchor_el'),
			$href = $overlay.find('#ctrl_url_link'),
			anchors = parentClass.getAnchors($href.val()),
			$hrefText = $overlay.find('#ctrl_url_text'),
			$tabs = $overlay.find('.mceTabs').children();

		$.each(anchors, function(i, anchor){
			var $anchor = $('<li />').addClass('quattro-anchor-item').attr('data-anchor', anchor.value).text(anchor.text);
			if(anchor.selected){
				$anchor.addClass('selected');
			}

			$anchor.appendTo($anchorsListClone);
			hasAnchors = true;
		});

		if(hasAnchors){
			var $li = $anchorsListClone.children();
			$li.unbind('click').bind('click', function(e){
				var $this = $(this);
				
				$li.removeClass('selected');
				$this.addClass('selected');
				$href.val($this.data('anchor'));
				$tabs.eq(0).trigger('click');
				$hrefText.focus();
			});
			
			$anchorsList.empty().replaceWith($anchorsListClone);
			$allAnchorsEl.removeClass(hiddenClass);
		}else{
			$allAnchorsEl.addClass(hiddenClass);
		}
	},
	submit: function(e, $ovl, ed, src)
	{
		//Type 0: none, Type 1: link, Type 2: mail
		var data = e.data, 
			dom = ed.dom, type = 0, href, text,
			orginalSel = xenMCE.Lib.overlay.getSelection(),
			initialText = orginalSel.url.text,
			anchorElm = orginalSel.url.anchorElm,
			targetMode = '_blank';

		if(orginalSel.isEmail)
			initialText = initialText.replace(/mailto:/i, '');

		if(data.ctrlUrlLink)
			type = 1;
				
		if(data.ctrlMailLink)
			type = 2;
				
		if(data.ctrlUrlLink && data.ctrlMailLink)
			type = $ovl.find('.mce-active').index() + 1;
				
		if(type == 1){
			href = $.trim(data.ctrlUrlLink);
			text = data.ctrlUrlText;

			if(href.indexOf('#') == 0){
				targetMode = '_self';
			}else{
				if (/^\s*www\./i.test(href)) {
					href = 'http://' + href;
				}
			
				if (!/^\s*(https?|ftp):/i.test(href)) {
					href = 'http://' + href;			
				}
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
						target: targetMode,
						rel: null
					});
					ed.selection.select(anchorElm);
				} else {
					ed.insertContent(dom.createHTML('a', {
						href: href,
						target: targetMode,
						rel: null
					}, text));
				}
			} else {
			ed.execCommand('mceInsertLink', false, {
					href: href,
					target: targetMode,
					rel: null
				});
			}
		}

		insertLink();
	}
}