xenMCE.Templates.Image = {
	submit: function(e, $ovl, ed, src)
	{
		var url = e.data.url, dom = ed.dom, config,
		orginalSel = xenMCE.Overlay.getSelection(), imgElm = orginalSel.sel.getNode();

		function waitLoad(imgElm) {
			imgElm.onload = imgElm.onerror = function() {
				imgElm.onload = imgElm.onerror = null;
				ed.selection.select(imgElm);
				ed.nodeChanged();
			};
		}

		config = {
			src: url,
			alt: '',
			width: null,
			height: null,
			'class' : 'bbCodeImage'
		};

		if (imgElm.nodeName != 'IMG') {
			config.id = '__mcenew';
			ed.insertContent(dom.createHTML('img', config));
			imgElm = dom.get('__mcenew');
			dom.setAttrib(imgElm, 'id', null);
		} else {
			dom.setAttribs(imgElm, config);
		}

		waitLoad(imgElm);
	}
}
