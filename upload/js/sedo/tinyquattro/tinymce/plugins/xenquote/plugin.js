(function($, window, document, undefined) {
	tinymce.PluginManager.add('xenquote', function(ed) {
		var tools = xenMCE.Lib.getTools(),
			phrases = xenMCE.Phrases,
			quoteRegexNoParser = /\[quote(?:=(.*?))?\]([\s\S]*?)\[\/quote\]/g,
			quoteRegexWithParser = /^<blockquote.*?data-mcequote[\s\S]*?<\/blockquote>$/;

		/* The below function will be only used if the insert content doesn't use the XenForo formatter */
		var bbcodeToHtml = function(matchQuote, fullInsertion){
			var $quote = $('<blockquote class="mce_quote" data-mcequote="true" />'),
				originalQuote = matchQuote[0],
				options = matchQuote[1],
				content = matchQuote[2],
				modifiedQuote = '',
				pRegex = /^<p>/g;

			if(!content || content == undefined)
				return false;

			if(!pRegex.test(content)){
				content = '<p>'+content+'</p>';
			}

			$quote.html(content);

			if(options != undefined){
				//Unescape options
				options = tools.unescapeHtml(options);
				//Delete comas
				options = options.substring(1, options.length - 1);
				//Transform into array
				options = options.split(',');
				
				//Get attributes
				if($.isArray(options)){
					var username = options.shift(), attributes = [];	
								
					if(username){
						$quote.attr('data-username', username);				
					}

					$.each(options, function(i,v){
						var partAttributes = v.split(':', 2);

						if(partAttributes[1] == undefined)
							return;							
						
						var attrName = $.trim(partAttributes[0]),
							attrValue = $.trim(partAttributes[1]);
					
						if(attrName !== '' && attrValue !== ''){
							attributes.push(attrName);
							$quote.attr('data-'+attrName, attrValue);
						}
					});
					
					if(attributes.length != 0){
						$quote.attr('data-attributes', attributes.toString());
					}
				}
			}

			modifiedQuote = $quote.wrap('<div />').parent().append('<p />').html();
			return fullInsertion.replace(originalQuote, modifiedQuote);
		};

		/* Listen all insertion and modify them if needed */
		ed.on('BeforeExecCommand', function(e, b, c) {
			if($.inArray(e.command, ['insertHtml', 'mceInsertContent']) !== -1){
				var matchNoParser = quoteRegexNoParser.exec(e.value);
				if(matchNoParser){
					var modifiedQuote = bbcodeToHtml(matchNoParser, e.value);
					
					if(modifiedQuote){
						ed.execCommand(e.command, false, modifiedQuote);
					}
					return false;
				};
				
				if(quoteRegexWithParser.test(e.value)){
					//Add an extra blank paragraph (allow to do several quotes)
					var blankParagraph = (tinymce.isIE) ? '<p>&nbsp;</p>' : '<p><br /></p>';
					
					ed.execCommand(e.command, false, e.value+blankParagraph);
					return false;
				}
			}
		});

		/* Create the button */
		var n = 'xen_quote';

		var getBlockQuotes = function(){
			var el = ed.selection.getNode(),
				dom = ed.dom;

			if(el.tagName != 'BLOCKQUOTE'){
				el = dom.getParent(el, 'blockquote');
			} 
			
			if(el == undefined || !el)
				return false

			return el;	
		};

		var modal = function(blockquote, username, attribs, dom){
			var each = tinymce.each,
				inArray = tinymce.inArray,
				fields = [{type: 'textbox', name: 'username', label: 'Username', value: username || ''}],
				skipFields = ['data-username', 'class', 'data-mcequote', 'data-attributes'],
				extraFields = [],
				mergeFields = true;
				
			if(mergeFields){
				each(attribs, function(attrib){
					var name = attrib.name, value = attrib.value;
					
					if(inArray(skipFields, name) !== -1 || name == undefined){
						return;
					}

					name = name.replace(/data-/i, '');
					
					fields.push({
						type: 'textbox',
						name: name,
						label: phrases.field+' "'+name+'"',
						value: value || ''
					});
					
				});
			}
	
			ed.windowManager.open({
				title: 'Quote properties',
				body: fields,
				onsubmit: function(e) {
					var data = e.data;
					
					each(data, function(value, name){
						dom.setAttrib(blockquote, 'data-'+name, value);
					});
				}
			});
		}

		ed.addButton(n, {
			name: n,
			icon: n,
			iconset: 'xenforo',
			type: 'splitbutton',
			menu: [	{ value: 'setProperties', text: 'Quote properties' } ],
			onshow: function(e) {
				$('.mce-tooltip:visible').hide();
				var blockquote = getBlockQuotes();
				this.menu._items.disabled(!(blockquote));
			},
			onselect: function(e) {
				var ctrl = e.control,
					cmd = e.control._value,
					blockquote = getBlockQuotes(),
					dom = ed.dom,
					otherAttribs = dom.getAttribs(blockquote),
					username = dom.getAttrib(blockquote, 'data-username');
					
				if(cmd == 'setProperties'){
					modal(blockquote, username, otherAttribs, dom);
				}
			},
			onclick: function(e){
				ed.execCommand('mceBlockQuote');

				var blockquote = getBlockQuotes(), dom = ed.dom;
				
				if(blockquote == undefined)
					return false;
					
				var hasMceTag = dom.getAttribs(blockquote, 'data-mcequote');

				if(!hasMceTag.length){
					dom.setAttribs(blockquote, {'class': 'mce_quote', 'data-mcequote': 'true'});
				}
			},
			onPostRender: function(e) {
				var ctrl = this;

				if (ed.formatter) {
					ed.formatter.formatChanged('blockquote', function(state) {
						ctrl.active(state);
					});
				} else {
					ed.on('init', function() {
						ed.formatter.formatChanged('blockquote', function(state) {
							ctrl.active(state);
						});
					});
				}
			}
		});
	});
})(jQuery, this, document);