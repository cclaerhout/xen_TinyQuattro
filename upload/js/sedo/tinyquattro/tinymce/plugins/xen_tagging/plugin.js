!function($, window, document, undefined)
{
	tinymce.create('tinymce.plugins.xen_tagging',
	{
	      	init: function(editor)
	      	{
			//Only compatible with XenForo 1.2.x
			var tools = xenMCE.Lib.getTools();
			
			if(tools.getParam('oldXen'))
				return false;
			
			this.editor = editor;
			var src = this;
			
			editor.on('PastePreProcess', function(e){
				e.content = e.content.replace(/(.|^)<a\s[^>]*data-user="(\d+, [^"]+)"[^>]*>([\w\W]+?)<\/a>/gmi,
						function(match, prefix, user, username) {
							var userInfo = user.split(', ');
							if (!parseInt(userInfo[0], 10)){
								return match;
							}
							return prefix + (prefix == '@' ? '' : '@') + userInfo[1].replace(/^@/, '');
                    				}
                		);
            		});
            		
			editor.on('init', function(e) {
				var 	$textarea = $(editor.getElement()),
					$ed = $(editor.getBody()),
					autoCompleteUrl = $textarea.data('ac-url') || XenForo.AutoComplete.getDefaultUrl();
				
				src.$textarea = $textarea;
				src.$ed = $ed;
				src.autoCompleteUrl = autoCompleteUrl;
				
		      		var 	doc = $ed.get(0).ownerDocument,
		      			hideCallback = function() {
		      				setTimeout(function() {
		      					src.acResults.hideResults();
		      				}, 200);
		      			};
		
		      		src.acVisible = false;
		      		src.acResults = new XenForo.AutoCompleteResults({
		      			onInsert: $.proxy(src, 'insert')
		      		});
		
		      		$(doc.defaultView || doc.parentWindow).on('scroll', hideCallback);
		      		$ed.on('click blur', hideCallback);
	
		      		editor.on('keydown', function(e) {
		      			var acResults = src.acResults,
			      			prevent = true;
		      			
		      			if (!acResults.isVisible())
		      				return;
	
		      			switch (e.keyCode){
		      				case 40: acResults.selectResult(1); break; // down
		      				case 38: acResults.selectResult(-1); break; // up
		      				case 27: acResults.hideResults(); break; // esc
		      				case 13: acResults.insertSelectedResult(); break; // enter
		      				default: prevent = false;
		      			}
		
		      			if (prevent) {
		      				e.stopPropagation();
		      				e.stopImmediatePropagation();
		      				e.preventDefault();
		      			}
		      		});
		
		      		editor.on('keyup', function(e) {
		      			var acResults = src.acResults,
			      			prevent = false;
		      			
		      			if (acResults.isVisible()) {
                            switch (e.keyCode){
                                case 40: // down
                                case 38: // up
                                case 27: // esc
                                case 13: // enter
                                    prevent = true;
                                    break;
                            }
                        }
                        if (!prevent) {
                            var autoCompleteText  = src.getAfterAt();

                            if (autoCompleteText) {
                                src.trigger(autoCompleteText);
                            } else {
                                acResults.hideResults();
                            }
                        }
		
		      			if (prevent) {
		      				e.stopPropagation();
		      				e.stopImmediatePropagation();
		      				e.preventDefault();
		      			}
		      		});
			});
	      	},
	      	getAfterAt: function()
	      	{
			return this.parseCurrentLine();
	      	},
	      	insert: function(name)
	      	{
			if(!this.range){
				console.alert('Range is missing');
				return;
			}
	
			/*Redefine the selection range*/
			var ed = this.editor;
			var bookmark = ed.selection.getEnd();
			
			var 	rng = this.range,
				end = rng.endContainer,
				text, lastAt; //lastAT should start at 0 but let's do it again
	
			if(end.nodeType == 1 && tinymce.isIE && tinymce.Env.ie <= 9){
				//Let's make this function only for IE below version 10 at the moment
				text = $(end).contents().filter(function() {
					return this.nodeType == 3;
				}).text();
			}else if(end.nodeType == 3){
				text = end.nodeValue;
			}else{
				return false;
			}
	
			if(typeof text === undefined){
				return false;
			}
	
			lastAt = text.lastIndexOf('@');
			rng.setStart(end, lastAt);
	
			ed.selection.setRng(this.range);
			
			/*Replace the current selection*/
			var newContent = '@' + XenForo.htmlspecialchars(name) + '&nbsp;';
			ed.execCommand('mceInsertContent', false, newContent);
			ed.nodeChanged();
	
			this.lastAcLookup = name + ' ';
	
			return false;
	      	},
	      	trigger: function(name)
	      	{
	      		name = name.replace(new RegExp(String.fromCharCode(160), 'g'), ' ');
	      		if (this.lastAcLookup && this.lastAcLookup == name)
	      		{
	      			return;
	      		}
	
	      		//this.hide();
	      		this.lastAcLookup = name;
	      		if (name.length > 2 && name.substr(0, 1) != '[')
	      		{
	      			this.acLoadTimer = setTimeout($.proxy(this, 'lookup'), 200);
	      		}
	      	},
	      	lookup: function()
	      	{
	      		if (this.acXhr)
	      		{
	      			this.acXhr.abort();
	      		}
	
	      		this.acXhr = XenForo.ajax(
	      			this.autoCompleteUrl,
	      			{ q: this.lastAcLookup },
	      			$.proxy(this, 'showResults'),
	      			{ global: false, error: false }
	      		);
	      	},
	      	showResults: function(ajaxData)
	      	{
	      		this.acXhr = false;
			this.bookmark = false;
	
	      		var ed = this.editor;
	
			/*Get iframe and iframe body*/
			$iframe = $(ed.getContainer()).find('iframe');
			$iframeBody = $(ed.getBody());
				
			/*Create a bookmark and find it (I'm using jQuery because I'm lazy)*/
			var bm = ed.selection.getBookmark();
			$bm = $iframeBody.find('#'+bm.id+'_start');
			
			var removeBookmark = function(){
				$bm.remove();
			}
				
			/*Move the bookmark before the aerobase*/
			var parentNode = $bm.parent().get(0),
				bmNode = $bm.get(0),
				previousNode = $bm.get(0).previousSibling;
			
			if(typeof previousNode === undefined || previousNode === null){
				removeBookmark();
				return false;
			}
				
			var nodeValue = previousNode.nodeValue;
	
			if(typeof nodeValue === undefined || nodeValue === null){
				removeBookmark();
				return false;
			}
			
			var atOffset = nodeValue.lastIndexOf('@');
				
			if(atOffset == -1){
				removeBookmark();
				return false;
			}
	
			var replacementNode = previousNode.splitText(atOffset);
			//parentNode.insertBefore(bmNode, replacementNode);

			/*Get the iframe offset (inside the page) & the iframe body offset (inside the iframe), then create the futur offset for results*/
			var iframeOffset = $iframe.offset(), 
				bmOffset = $bm.offset(),
				css = {
					top: iframeOffset.top+bmOffset.top+$bm.parent().height(),
					left: iframeOffset.left+bmOffset.left
				};
				
			/*Remove the bookmark*/
			removeBookmark();

	      		this.acResults.showResults(
	      			this.lastAcLookup,
	      			ajaxData.results,
	      			$iframe,
	      			css
	      		);

	      	},
	      	hide: function()
	      	{
	      		this.acResults.hideResults();
	
	      		if (this.acLoadTimer)
	      		{
	      			clearTimeout(this.acLoadTimer);
	      			this.acLoadTimer = false;
	      		}
	      	},
		parseCurrentLine: function()
		{
			this.range = false;
			
			var 	editor = this.editor, 
				rng = editor.selection.getRng(true).cloneRange(),
				end = rng.endContainer,
				prev = rng.endContainer.previousSibling,
				text, lastAt, afterAt;
	
			if(end.nodeType != 3)
				return false;
						
			text = end.nodeValue;
			
			if(typeof text === undefined)
				return false;
			
			lastAt = text.lastIndexOf('@');
	
	      		if (lastAt != -1 && (lastAt == 0 || text.substr(lastAt - 1, 1).match(/(\s|[\](,]|--)/)))
	      		{
	      			afterAt = text.substr(lastAt + 1);
	
	      			if (afterAt.match(/\s/) || afterAt.length > 10 || afterAt.length < 2)
	      				return false;
	
				this.range = rng;
				return afterAt;
	      		}
	
			return false;
		}
	});
	
	tinymce.PluginManager.add('xen_tagging', tinymce.plugins.xen_tagging);
}
(jQuery, this, document, 'undefined');