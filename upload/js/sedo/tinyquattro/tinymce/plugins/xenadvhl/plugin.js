tinymce.PluginManager.add('xenadvhl', function(ed) {
	/*Framework variables*/
	
	var self = this, 
		tools = xenMCE.Lib.getTools();
		
	self.adv_hl_active = false;

	/*Global variables*/
	var replace_id_n = 0, replace_id_s = 0,
	n = 'tags_highlighter',
      	pattern = {}; 	// Highlighting Regex patterns

	pattern.normalOpen = 	/(\[[^\/]+?)((?==)[^\[]*?)?\](?!<[\/]?span)/gi;
	pattern.normalClose = 	/(\[\/([^[]+?)\])(?!<\/span>)/gi;
	pattern.special = 	/(\{([^\/]+?)(?:=.+?)?\})(?!<\/span>)((?:\{\2(?:=.+?)?\}(?:\{\2(?:=.+?)?\}[\s\S]*?\{\/\2\}|[\s\S])+?\{\/\2\}|[\s\S])*?)(\{\/\2\})/gi;		      	

	/*Highlighting colors*/
	var adv_hl_norm = tools.getParam('adv_hl_norm'),
		adv_hl_norm_open = adv_hl_norm.open,
		adv_hl_norm_options = adv_hl_norm.options,
		adv_hl_norm_close = adv_hl_norm.close;
		
	var adv_hl_spe = tools.getParam('adv_hl_spe'),
		adv_hl_spe_open = adv_hl_spe.open,
		adv_hl_spe_options = adv_hl_spe.options,
		adv_hl_spe_content = adv_hl_spe.content,
		adv_hl_spe_close = adv_hl_spe.close;
	
	var adv_hl_tag_separator = tools.getParam('adv_hl_tag_separator');

	function toggleAdvHighlight() {
		var ctrl = this;
		
		self.adv_hl_active = !self.adv_hl_active;

		if(self.adv_hl_active === false){
			ctrl.active(false);
			_removeHighlight();
			return false;
		}

		ctrl.active(true);		
		_getHighlight();
		_getHighlight(); //ugly fix for regex test patterns... Don't get it
	}

	ed.on('init RestoreDraft', function(e) {
		_removeHighlight();
	});

	ed.on('SaveContent XenSwitchToBbCode SavingXenDraft', function(e) {
		_removeHighlight();
		e.content = ed.getContent();
	});

	ed.on('NodeChange keydown', function(e) {
		_getHighlight();
	});

	function _removeHighlight()
	{
		ed.dom.remove(ed.dom.select('.adv_hl'), 'adv_hl'); 
		replace_id_n = 0;
		replace_id_s = 0;
	}

	function _getHighlight()
	{
		if(self.adv_hl_active === false)
			return false;

		var content = ed.getContent(),
		sel = ed.selection,
		triger_replace = false,
		triger_normal = false,
		triger_special = false,
		noContent = false;

		if (pattern.normalOpen.test(content))
		{
			triger_replace = true;
			triger_normal = true;
			content = content.replace(pattern.normalOpen,
				function(fullMatch, tagopen, tagoptions) {
					replace_id_n++;
					var builder = '<span id="advhln_'+ (replace_id_n) +'" class="adv_hl adv_hln_'+ (replace_id_n) +'" style="background-color:'+ adv_hl_norm_open +'">'+ tagopen;

					if(typeof tagoptions !== 'undefined')
					{
						var separator;
						if(tagopen == '[picasa') { separator = /,/gi; } else { separator = /\|/gi; };
						tagoptions = tagoptions.replace(separator, '<span class="adv_hl" style="color:'+ adv_hl_tag_separator +';font-weight:bolder;">$&</span>');
							
						builder+= '<span class="adv_hl adv_hln_'+ (replace_id_n) +' tag_options_'+ (replace_id_n) +'" style="background-color:'+ adv_hl_norm_options +'">'+ tagoptions +'</span>';
					}
						
					builder+= ']</span>';
						
					return builder;
			});
		}

		if (pattern.normalClose.test(content))
		{
			triger_replace = true;
			triger_normal = true;
			content = content.replace(pattern.normalClose,
				function(fullMatch, tagclose, tag) {
					replace_id_n++;
					return '<span id="advhln_'+ (replace_id_n) +'" class="adv_hl adv_hln_'+ (replace_id_n) +'" style="background-color:'+ adv_hl_norm_close +'">'+ tagclose +'</span>';
			});
		}

		//Special tags
		if (pattern.special.test(content))
		{
			//Special tags
			triger_replace = true;
			triger_special = true;				
					
			content = content.replace(pattern.special,
				function(fullMatch, tagopen, tag, tagcontent, tagclose) {
					replace_id_s++;
					if(!tagcontent){ noContent = true; }
						return '<span id="advhls_open_'+ (replace_id_s) +'" class="adv_hl adv_hls_'+ (replace_id_s) +'" style="background-color:'+ adv_hl_spe_open +'">'+ tagopen +'</span><span id="advhls_content_'+ (replace_id_s) +'" class="adv_hl  adv_hls_'+ (replace_id_s) +'" style="background-color:'+ adv_hl_spe_content +'">'+ tagcontent +'</span><span id="advhls_close_'+ (replace_id_s) +'" class="adv_hl  adv_hls_'+ (replace_id_s) +'" style="background-color:'+ adv_hl_spe_close +'">'+ tagclose +'</span>';
				});
		}

		if (triger_replace)
  		{
			//Highlight!!!
			ed.setContent(content);
				
			if (triger_normal === true)
			{ 
				sel.select(ed.dom.select('span#advhln_' + replace_id_n)[0]); 		//Get the selection
				sel.setContent(sel.getContent());					//Replace the selection to move the caret outside
			}
			
			if (triger_special === true)
			{
  				if(noContent) //If tag has no content, target the open tag to move the caret on the right
  				{
  					var builder_temp;
  					//Select the opening tag
  					sel.select(ed.dom.select('span#advhls_open_' + replace_id_s)[0]);
  					//Rebuild the span for the content tag (its content was nulled so the span hadn't been created) and insert in it a "caretfix" span to select later
  					builder_temp = sel.getContent() + '<span id="advhls_content_'+ (replace_id_n) +'" class="adv_hl adv_hls_'+ (replace_id_n) +'" style="background-color:'+ adv_hl_spe_content +'"><span id="adv_caretfix"></span></span>';
  					sel.setContent(builder_temp);
  					//Select the caretfix tag (will be automatically killed during setContent)
  					sel.select(ed.dom.select('span#adv_caretfix')[0]);
  					sel.setContent(sel.getContent());
  				}
  				else
  				{
  					sel.select(ed.dom.select('span#advhls_close_' + replace_id_s)[0]);
  					sel.setContent(sel.getContent());
  				}
			}
		}
	}

	ed.addButton(n, {
		name: n,
		icon: n,
		onclick: toggleAdvHighlight,
	});

	ed.addMenuItem(n, {
		name: n,
		selectable: true,
		text: "Tags Helper",
		icon: false,
		onClick: toggleAdvHighlight
	});	
});