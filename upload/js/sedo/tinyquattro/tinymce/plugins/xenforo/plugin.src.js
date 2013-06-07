(function() {
/***
*	xenMCE - AllInOne Functions
***/

	/***
	*	Extend xenMCE - Overlay: Shortcuts to get Overlay params
	***/
	
	xenMCE.Overlay = {
		create: function(dialog, windowManagerConfig, callbacks){
			xenMCE.Overlay._Tools.loadOverlay(dialog, windowManagerConfig, callbacks); //Static call
		},
		getParams: function()
		{
			return xenMCE.Overlay._get('params');	
		},
		getInputs: function()
		{
			return xenMCE.Overlay._get('inputs');
		},
		getOverlay: function()
		{
			return xenMCE.Overlay._get('$overlay');	
		},
		getEditor: function()
		{
			return xenMCE.Overlay._get('editor');				
		},
		getSelection: function(opt)
		{
			return xenMCE.Overlay._get('selection');			
		},
		_get:function(key)
		{
			var bckOv = xenMCE.Tools.backupOverlay;
			
			if(typeof bckOv[key] !== 'undefined')
				return bckOv[key];
			else
				return false;
		}
	};

	/***
	*	Extend xenMCE - Templates: this object will be used if needed after the load of a dialog to execute callbacks (ie: submit)
	*	Advantage: it will limit the size of the framework
	*		> Inside the template: <xen:require js="js/.../file.js" />
	*		> Inside the file.js =>  xenMCE.Templates.TemplateName = {...}
	***/
	
	xenMCE.Templates = {};

	/***
	*	Extend xenMCE - Tools: TinyMCE plugin 
	*	> This class functions should be applied to any "sub-plugins" put inside the xenMCE.Plugins.Auto
	*	> This "sub-plugins" must be created with the tinymce.create() function but must not be added in
	*	  the plugin manager
	*	> To create a real TinyMCE plugin and access these functions a static mirror is available in xenMCE.Overlay._Tools
	***/

	tinymce.create('xenMCE.Tools', {
		Tools: function(ed) 
		{
			var src = this, s = this.static, un = 'undefined';
			
			if(typeof ed === 'undefined'){
				ed = tinymce.activeEditor;
			}
			
			/* Get Editor */
			this.getEditor = function (){ return ed; };
			
			/* Fullscreen State */			
			this.isFullscreen = function(e) { 
				if(typeof ed.plugins.fullscreen === un)
					return false
				else
					return ed.plugins.fullscreen.isFullscreen();
			 };
			
			/*Create a static mirror*/
			xenMCE.Overlay._Tools = this;

			/*XenForo plugins AutoLoader*/
			if(typeof xenMCE.Plugins.Auto !== un){
				$.each(xenMCE.Plugins.Auto, function(k, v){
					new v(src);
				});
			}
		},
		overlayParams: {},
		overlayInputs: {},		
		loadOverlay: function(dialog, windowManagerConfig, callbacks)
		{
			/***
				This is the main function to load a XenForo overlay
					> The dialog parameter is the dialog template name (don't forget a prefix is automatically added
					> The windowManagerConfig is the default TinyMCE configuration (see the documentation for this)
					> the callbacks are some extra listeners. The content must be an object with these keys:
					   # 'src'		(needed) this is the source of the listeners
					   # 'onbeforeload'	(string with the name of the function to call - optionnal)
					   	> Use this callback to modify the windowManagerConfig just before opening the TinyMCE modal
					   	> It must return the configuration
					   	> Argument: windowManagerConfig 
					   # 'onafterload'	(string with the name of the function to call - optionnal)
					   	> Use this callback to do something after the XenForo overlay has been loaded
					   	> Arguments: $overlay, data, editor, parentClass
					   # 'onsubmit'		(string with the name of the function to call - optionnal)
					   	> Use this callback to do something when the form is submitted
					   	> Arguments: event, $overlay, editor, parentClass

				Please note that the onsubmit from the official windowManagerConfig has been modified:
				=> Its arguments are now: event, $overlay, editor, parentClass (instead of only event)
				
				About the windowManagerConfig and its optional data object, if one of its key can be found in the template inside
				the "name" parameter of a tag, this tag will be automatically filled.
				
				When submitting the form, TinyMCE automatically passes the input tags in a data object inside the event. If you need
				to get the the content of another tag, just add this tag a "data-value" parametter, you can put what you want in the value
				You will have both the content of the data-value and the content of the tag
			**/

			var t = this,
			editor = this.getEditor(),
			dom = editor.dom,
			isUrl = isLink = isEmail = false, url_text = url_href = '',
			sel = editor.selection, selHtml, selText,
			isEmpty = (selText) ? 0 : 1,
			staticBackup = xenMCE.Tools.backupOverlay;

			/* Url (+email) checker: take the parent a element */
			selElm = sel.getNode();
			anchorElm = dom.getParent(selElm, 'a[href]');
			
			if (anchorElm) {
				isUrl = true;
				sel.select(anchorElm); //Refresh selection
				url_text = sel.getContent({format: 'text'});
				url_href = anchorElm ? dom.getAttrib(anchorElm, 'href') : '';
				isEmail = (url_href.indexOf('mailto') == 0 && url_href.indexOf('@') > 0 && url_href.indexOf('//') == -1) ? true : false;
				isLink = !isEmail;
			}

			/* Get selection after url checker */
			selHtml = sel.getContent();
			selText = sel.getContent({format: 'text'});
		
			/* Backup selection so it can be retrieve from xenMCE.Overlay */
			staticBackup.selection = {
				sel: sel,
				text: selText,
				html: selHtml,
				isUrl: isUrl,
				isLink: isLink,
				isEmail: isEmail,
				url: {
					text: url_text,
					href: url_href,
					anchorElm: anchorElm
				}
			}

			if(typeof windowManagerConfig !== 'object')
				windowManagerConfig = {};
						
			this.overlayParams = {
				dialog: dialog,
				src: t.isDefined(callbacks, 'src'),
				onbeforeload: t.isDefined(callbacks, 'onbeforeload'),
				onafterload: t.isDefined(callbacks, 'onafterload'),
				onsubmit: t.isDefined(callbacks, 'onsubmit'),
				wmConfig: windowManagerConfig
			}

			XenForo.ajax('index.php?editor/quattro-dialog',{ 
					dialog: dialog,
					selectedHtml: selHtml,
					selectedText: selText,
					isUrl: isUrl,
					isLink: isLink,
					isEmail: isEmail,
					urlDatas: {
						text: url_text,
						href: url_href
					}
				}, $.proxy(this, '_overlayLoader')
			);
		},
		_overlayLoader:function(ajaxData)
		{
			if (XenForo.hasResponseError(ajaxData) || typeof(ajaxData.templateHtml) === 'undefined')
				return;

			var 	t = this,
				editor = this.getEditor(),
				params = this.overlayParams,
				wmConfig = params.wmConfig, 
				html = '<div><div class="mce-xen-body">'+ajaxData.templateHtml+'</div></div>',
				data = {};

 			new XenForo.ExtLoader(ajaxData, function()
 			{
				/**
				*	XenForo ExtLoader: JS & CSS successfully loaded
				**/
				
				var 	un = 'undefined',
					win = editor.windowManager,
					phrase = xenMCE.Phrases,
					buttonOk = phrase.insert,
					buttonCancel = phrase.cancel,
					inputsTags = ['input','textarea','select'],
					staticBackup = xenMCE.Tools.backupOverlay;

				staticBackup.params = params;
				staticBackup.editor = editor;
				
				$html = $(html);
				$title = $html.find('.mceTitle').remove();
				$Submit = $html.find('.mceSubmit').remove();
				$Cancel = $html.find('.mceCancel').remove();

				if($Submit.length == 1)
					buttonOk = t.getTagName($Submit, true);

				if($Cancel.length == 1)
					buttonCancel = t.getTagName($Cancel, true);

				/* Get all datas from inputs or from data-input tag */
				function getDatas($el) {
					$inputs = $el.find(inputsTags.toString());

					if($inputs.length > 0){
						$inputs.each(function(i) {
							var n = $(this).attr('name');
							if(n){
								data[n] = $(this).val();
							}
						});
					}

					$el.find('*').filter(function() { 
						if($(this).data('value')){
							data[$(this).attr('name')] = {
								'html': $(this).html(),
								'text': $(this).text(),
								'data': $(this).data('value')
							}
						}
						
						if(parseInt($(this).data('hide')) == 1)
							$(this).hide();
					});
					
					t.overlayInputs = data;
					staticBackup.inputs = data;
					return data;
				}
				
				getDatas($html);
				
				/*Title*/
				if($title)
					wmConfig.title = $title.text();

				if(typeof wmConfig.title === un || !wmConfig.title)
					wmConfig.title = phrase.notitle;

				/*Overlay size*/
				var defaultSize = xenMCE.Params.overlayDefaultSize;
				
				if(typeof wmConfig.width === un)
					wmConfig.width = defaultSize.w;

				if(typeof wmConfig.height === un)
					wmConfig.height = defaultSize.h;
			
				/*Autofield & extend Data*/
				if(typeof wmConfig.data !== un){
					//Autofield existed tags with name attribute with their related data
					$.each(wmConfig.data, function(k, v){
						$e = $html.find('[name='+k+']');
						if($e.length == 1){
							if(t.getTagName($e, inputsTags)){
								/***
									# Data ugly trick (step 1) #
									Ok here's the thing, the value of the input tags
									can't be modified before the overlay is appeared
									& the data value is automaticaly modified after the
									overlay is displayed (no way to change that). 
									So let's use an ugly trick (has two steps).
								**/
								$('<span class="tmp-data-mce">'+v+'</span>').insertAfter($e).hide();
							}else{
								$e.html(v);
							}
						}
					});
					//Extend data
					$.extend(wmConfig.data, data);				
				}else{
					wmConfig.data = data;
				}

				/* MCE onsubmit callback */
				if(typeof wmConfig.onsubmit !== un){
					originalSubmit = wmConfig.onsubmit;
					
					wmConfig.onsubmit = function(params){
						var $overlay = $(editor.windowManager.windows[0].getEl());
						xenDatas = getDatas($overlay);
						$.extend(params.data, xenDatas);

						originalSubmit(params, $overlay, editor, t);
					};
				}

				/* Buttons Ok/Cancel + listeners */
				if(typeof wmConfig.buttons === un){
					wmConfig.buttons = [
						{text: buttonOk, subtype: 'primary xenSubmit', minWidth: 80, onclick: function(e) {
							var win = editor.windowManager.windows[0];
							$overlay = $(win.getEl());

							/* Private onsubmit callback */
							if(params.onsubmit != false){
								var xenDatas = getDatas($overlay);
								$.extend(e.data, xenDatas);
								params.src[params.onsubmit](e, $overlay, editor, t);
							}

							if(typeof win.find('form')[0] !== 'undefined')
								win.find('form')[0].submit();
							else
								win.submit();

							win.close();
						}},
						{text: buttonCancel, subtype: 'xenCancel', onclick: function() {
							var win = editor.windowManager.windows[0];
							win.close();
						}}
					];
				}

				/*Get template html code*/
				wmConfig.html = $html.html();

				/* Beforeload callback: use to modify the wmConfig if needed*/
				if(params.onbeforeload != false){
					var wmConfigModified = params.src[params.onbeforeload](wmConfig, t);
					if (typeof wmConfigModified !== un)
						wmConfig = wmConfigModified;
				}

				/* Launch the TinyMCE overlay */
				win.open(wmConfig);

				/* Get overlay */
				$overlay = $(win.windows[0].getEl());
				xenMCE.Tools.backupOverlay.$overlay = $overlay;

				/* AutoFocus */
				$overlay.find('.mceFocus').focus();

				/*Data ugly trick (step 2)*/
				$overlay.find('.tmp-data-mce').each(function(){
					$e = $(this);
					$e.prev().val($e.html());
					$e.remove();
				});

				/*Tabs & Panes - provided by JQT*/
				$tabs = $overlay.find('.mceTabs').addClass('mce-tabs');
				$tabs = t.cleanWhiteSpace($tabs);
					
				$panes = $tabs.next('.mcePanes').children().addClass('mce-pane');
				if($tabs.length > 0 && $panes.length > 0){
					$tabs.children().addClass('mce-tab');
					var i = $tabs.find('.mceActive').index();
					$tabs.tabs($panes, {
						current: 'mce-active',
						initialIndex: (i >= 0 ? i:0)
					});
				}

				/*MultiLine mode for Textarea*/
				$multi = $overlay.find('textarea, .mce-multiline');
				if($multi){
					win.windows[0].off('keydown');
					$multi.attr('spellcheck', 'false').attr('hidefocus', 'true');
				}

				/* Afterload Callback*/				
				if(params.onafterload != false)
					params.src[params.onafterload]($overlay, data, editor, t);
		
				return false;
			});
		},
		isDefined: function(v, k)
		{
			var un = 'undefined';
			if(typeof k === un){
				if(typeof v === un)
					return false;
				else
					return v;
			}else{
				if(typeof v === un || typeof v[k] === un)
					return false;
				else
					return v[k];		
			}
		},
		getTagName: function($e, autoReturn, expectedTags)
		{
			var tag = $e.get(0).tagName.toLowerCase();

			//TagName Tool
			if(typeof expectedTags === 'undefined' && typeof autoReturn === 'undefined')
				return tag;

			if(typeof autoReturn !== 'undefined' && typeof autoReturn !== 'boolean')
				 expectedTags = autoReturn;

			//AutoReturn Tool if inputs => return val else => return text
			if(autoReturn === true){
				inputs = ['input','textarea','select'];
				
				if($.inArray(tag, inputs))
					return $e.val();
				else
					return $e.text();
			}

			//Compare Tool
			if(typeof expectedTags === 'object'){
				var arrayChk = [];
				$.each(expectedTags, function(i, v){
					arrayChk.push(v.toLowerCase());
				});
			
				if($.inArray(tag, arrayChk) != -1)
					return true;				
				else
					return false;			
			}
			
			if(tag == expectedTags.toLowerCase())
				return true;				
			else
				return false;
		},
		cleanWhiteSpace: function($e)
		{
			$e.contents().filter(function() {
				return (this.nodeType == 3 && !/\S/.test(this.nodeValue));
			}).remove();
			
			return $e;
		},
		getButtonByName: function(name, getEl)
		{
			var ed = this.getEditor(),	
			buttons = ed.buttons,
			toolbarObj = ed.theme.panel.find('toolbar *'),
			un = 'undefined';
	
			if(typeof buttons[name] === un)
				return false;
			
			var settings = buttons[name], result = false, length = 0;
			
			tinymce.each(settings, function(v, k){
				length++;
			});
			
			tinymce.each(toolbarObj, function(v, k) {
				if (v.type != 'button' || typeof v.settings === un)
					return;
	
				var i = 0;
	
				tinymce.each(v.settings, function (v, k) {
					if(settings[k] == v)
						i++;
				});
	
				if(i != length)
					return;
				
				result = v;
	
				if(getEl != false)
					result = v.getEl();
				
				return false;
			});
			
			return result;
		},
		getButtonsByProperty: function(prop, val, jQueryEl)
		{
			var ed = this.getEditor(),	
			toolbarObj = ed.theme.panel.find('toolbar *'),
			un = 'undefined',
			results = {};
	
			tinymce.each(toolbarObj, function(v, k) {
				var settings = v.settings;
				
				if (v.type != 'button' || typeof settings === un)
					return;

				if( (typeof val !== un) && typeof settings[prop] !== un && settings[prop] == val)
					results[k] = v;
				else if( (typeof val === un || val === null) && typeof settings[prop] !== un)
					results[k] = v;
			});

			if(typeof jQueryEl !== un){
				var $buttons = $();
				$.each(results, function(k, v){
					$buttons = $buttons.add($(v.getEl()));
				});
				
				results = $buttons;
			}
			
			if(typeof jQueryEl === 'object' && jQueryEl instanceof jQuery){
				//Returns element in the context
				var $context = jQueryEl, ids = [], tmp = '_tmp_';
				
				$.each(results, function(k, v){
					 ids.push("#"+$(v).attr('id'));
				});

				ids = ids.toString();
				results = 	$context.find(ids)
						/* Fix to get the elements in the right order */
						.addClass(tmp).find('>:first-child').parents('.'+tmp).removeClass(tmp);
			}
			
			return results;
		},
		buildMenuItems: function(text, value, css, classes)
		{
			var items = [], bakeData = [], un = 'undefined', dataVal, textVal;
			
			if(typeof value === un)
				value = text;
			
			tinymce.each(value.split(/\|/), function(v) {
				bakeData.push(v);
			});
			
			tinymce.each(text.split(/\|/), function(text, i) {
				dataVal = (typeof bakeData[i] !== un) ? bakeData[i] : '';

				if(typeof css === un)
					return items.push({ text: text, data: dataVal } );

				var baker = { 
					title: text,
					text: text,
					data: dataVal,
					textStyle: css.replace(/{t}/g, text).replace(/{v}/g, dataVal)
				};
				
				if(typeof classes !== un)
					baker.classes = classes;

				items.push(baker);
			});

			return items;
		},
		insertBbCode: function(tag, tagOptions, content){
			var ed = this.getEditor(), dom = ed.dom, caretId = 'MceCaretBb', caret,
			oTag ='['+tag, cTag = '[/'+tag+']';
		
			if(!content)
				content = ed.selection.getContent();

			content += '<span id="'+caretId+'"></span>';
			
			if(tagOptions)
				oTag += '='+tagOptions+']';
			else
				oTag += ']';
			
			ed.execCommand('mceInsertContent', false, oTag+content+cTag);
			ed.selection.select(dom.get(caretId));
			dom.remove(caretId);
		},
		unescapeHtml : function(string, options) 
		{
			/* Use to get data from RTE and send them inside a textarea or input - available options: noBlank*/
			
			string = string
				.replace(/&amp;/g, "&")
				.replace(/&lt;/g, "<")
				.replace(/&gt;/g, ">")
				.replace(/&quot;/g, '"')
				.replace(/&#039;/g, "'");
				
			if(options == 'noBlank')
			{
				string = string
					.replace(/	/g, '\t')
					.replace(/&nbsp;/g, '  ')
					.replace(/<\/p>\n<p>/g, '\n');
			}
	      
			var regex_p = new RegExp("^<p>([\\s\\S]+)</p>$", "i");
			if(regex_p.test(string))
			{
				string = string.match(regex_p);
				string = string[1];
			}
				
			return string;
		},
		escapeHtml: function(string, options) 
		{
			/* Use to get data from textarea/inputs and send inside RTE - available options: noBlank & onlyBlank */
			if( options !== 'onlyBlank' ){
				string = string
					.replace(/&/g, "&amp;")
					.replace(/</g, "&lt;")
					.replace(/>/g, "&gt;")
					.replace(/"/g, "&quot;")
					.replace(/'/g, "&#039;");
			}

			if( options !== 'noBlank' ){
				string = string
					.replace(/\t/g, '	')
					.replace(/ /g, '&nbsp;')
					.replace(/\n/g, '</p>\n<p>');
      			}

			return string;
		},
		zen2han: function(str)
		{
			/*Source: proutCore - MIT license*/
			var nChar, cString= '', j, jLen;

			for (j=0, jLen = str.length; j<jLen; j++)
			{
				nChar = str.charCodeAt(j);
				nChar = ((nChar>=65281 && nChar<=65392)?nChar-65248:nChar);
				nChar = ( nChar===12540?45:nChar) ;
				cString = cString + String.fromCharCode(nChar);
			}
			return cString;
		},
		static:{
			backupOverlay: {} //This static object will be used to easily retrieve the overlay datas outside the class (@see xenMCE.Overlay)
		}
	});

	tinymce.PluginManager.add('xenforo', xenMCE.Tools);

/***
*	SUB-PLUGINS
***/
	var xenPlugin = 'xenMCE.Plugins.Auto';

	/***
	*	XenForo Switch Plugin: change editor RTE<=>BBCODE
	*	Independent plugin
	***/
	tinymce.create(xenPlugin+'.BbmButtons', {
		BbmButtons: function(parent) 
		{
			$.extend(this, parent);
			this.ed = this.getEditor();
			
			var src = this, buttons = xenMCE.Params.bbmButtons, un = 'undefined';

			$.each(buttons, function(tag, data){
				var n = data.code;
			
				config = { name: n, tooltip: data.desc };
			
				if(data.type == 'manual'){
					config.icon = n;
					if(data.typeOpt)
						config.classes = data.typeOpt;
				}
				else if(data.type == 'text'){
					config.icon = false;
					config.text = data.typeOpt;
				}
				else{
					config.icon = n;
					config.iconset = data.iconSet;
				}
			
				if(data._return == 'direct'){
					config.onclick = function(e){
						src.insertBbCode(tag, data.tagOpt, data.tagCont);
					}
				}else{
					config.onclick = function(e){
						var ovlConfig, ovlCallbacks;
						
						src.bbm_tag = tag;
						
						ovlConfig = {
							onsubmit: src.submit
						};
			
						ovlCallbacks = {
							src: src,
							onafterload: 'onafterload'
						};
					
						src.loadOverlay('bbm_'+data.template, ovlConfig, ovlCallbacks);
					}
				}
				
				src.ed.addButton(n, config);
			});
		},
		onafterload: function($ovl, data, ed, src)
		{
			var dialog = src.overlayParams.dialog.replace('bbm_', 'Bbm_');

			if(typeof xenMCE.Templates[dialog].onafterload !== 'undefined')
				xenMCE.Templates[dialog].onafterload($ovl, data, ed, src);
			
		},
		submit: function(e, $overlay, ed, src)
		{
			var dialog = src.overlayParams.dialog.replace('bbm_', 'Bbm_');
			
			if(typeof xenMCE.Templates[dialog].submit === 'undefined'){
				return console.error('Submit function not found');			
			}

			xenMCE.Templates[dialog].submit(e, $overlay, ed, src);
		}
	});

	/***
	*	XenForo Switch Plugin: change editor RTE<=>BBCODE
	*	Independent plugin
	***/
	tinymce.create(xenPlugin+'.XenSwitch', {
		XenSwitch: function(parent) 
		{
			$.extend(this, parent);
			this.ed = this.getEditor();
			
			var name = 'xen_switch';
			
			this.ed.addButton(name, {
				name: name,
				icon: name,
				iconset: 'xenforo',
				xenfright: true,
				tooltip: xenMCE.Phrases.switch_text[0],
				onclick: $.proxy(this, 'wysiwygToBbCode')
			});
		},
		wysiwygToBbCode: function(e)
		{
			if(this.isFullscreen() == true){
				var fullscreen = this.getButtonByName('fullscreen');
				if(fullscreen != false)
					$(fullscreen).trigger('click');
			}

			XenForo.ajax(
				'index.php?editor/to-bb-code',
				{ html: this.ed.getContent() },
				$.proxy(this, 'wysiwygToBbCodeSuccess')
			);
		},
		wysiwygToBbCodeSuccess: function(ajaxData)
		{
			if (XenForo.hasResponseError(ajaxData) || typeof(ajaxData.bbCode) === 'undefined') 
				return;

			$container = $(this.ed.getContainer());
			$existingTextArea = $(this.ed.getElement());
			$textContainer = $('<div class="bbCodeEditorContainer" />');
			$newTextArea = $('<textarea class="textCtrl Elastic" rows="5" />');
	
			if ($existingTextArea.attr('disabled'))
				return; // already using this
	
			var uniqId = $existingTextArea.attr('name').replace(/_html(]|$)/, '');
			$newTextArea
				.attr('id', uniqId)
				.attr('name', uniqId)
				.val(ajaxData.bbCode)
				.appendTo($textContainer);
	
			$('<a />')
				.attr('href', 'javascript:')
				.text(xenMCE.Phrases.switch_text[1])
				.click($.proxy(this, 'bbCodeToWysiwyg'))
				.appendTo(
					$('<div />').appendTo($textContainer)
				);
	
			$existingTextArea.attr('disabled', true);
			$container.after($textContainer).hide();
			$textContainer.xfActivate();
			$newTextArea.focus();

			this.$bbCodeTextContainer = $textContainer;
			this.$bbCodeTextArea = $newTextArea;
		},
		bbCodeToWysiwyg: function()
		{
			XenForo.ajax(
				'index.php?editor/to-html',
				{ bbCode: this.$bbCodeTextArea.val() },
				$.proxy(this, 'bbCodeToWysiwygSuccess')
			);
		},
		bbCodeToWysiwygSuccess: function(ajaxData)
		{
			if (XenForo.hasResponseError(ajaxData) || typeof(ajaxData.html) == 'undefined')
				return;
	
			$container = $(this.ed.getContainer());
			$existingTextArea = $(this.ed.getElement());
	
			if(!$existingTextArea.attr('disabled'))
				return; // already using
	
			$existingTextArea.attr('disabled', false);
			$container.show();
	
			this.ed.setContent(ajaxData.html);
			this.ed.focus();
	
			this.$bbCodeTextContainer.remove();
		}
	});

	/***
	*	XenForo Icons
	*	Independent plugin
	***/
	tinymce.create(xenPlugin+'.XenIcons', {
		XenIcons: function(parent) 
		{
			var ed = parent.getEditor(), p = xenMCE.Phrases, settings = xenMCE.Params, un = 'undefined';

			ed.on('BeforeRenderUI', function(e) {
				/* Auto translate tooltips based on suffix _desc*/
				tinymce.each(ed.buttons, function(v, k){
					var key_desc = k+'_desc';
					
					if(!XenForo.isTouchBrowser()){
						if(typeof p[key_desc] !== un)
							ed.buttons[k].tooltip = p[key_desc];
					}else{
						//Tooltip are annoying on Touch devices - let's delete them
						if(typeof ed.buttons[k].tooltip !== un)
							delete ed.buttons[k].tooltip;
					}
				});

				/***
					Need to modify the list buttons and delete extra options 
					(XenForo_Html_Renderer_BbCode can't be extended)
				**/
				function disableExtra(button){
					if(typeof ed.buttons[button].type !== un)
						delete ed.buttons[button].type;
				}

				if(xenMCE.Params.extraLists != true){
					disableExtra('bullist');
					disableExtra('numlist');
				}
			});

			ed.on('init', function(e) {
				$buttons = parent.getButtonsByProperty('iconset', 'xenforo', true);
				$buttons.find('i.mce-ico').addClass('mce-xenforo-icons');

				$statBar = $(ed.theme.panel.find('#statusbar')[0].getEl());
		
				if(settings.hidePath)
					 $statBar.find('.mce-path').css('visibility', 'hidden');
			});
		}
	});

	/***
	*	XenForo Fonts
	*	Independent plugin
	***/
	tinymce.create(xenPlugin+'.XenFonts', {
		XenFonts: function(parent) 
		{
			var ed = parent.getEditor(), Factory = tinymce.ui.Factory, menuSize, menuFam,
			sizeClass = 'xen-font-size', famClass = 'xen-font-family', p = xenMCE.Phrases;

			menuSize = parent.buildMenuItems(
					'1|2|3|4|5|6|7', //Text
					'xx-small|x-small|small|medium|large|x-large|xx-large', //Value
					'font-size:{v}', //Css
					sizeClass //Item Class
				);
				
			ed.addButton('xen_fontsize', {
				name: 'xen_fontsize',
				type: 'menubutton',
				icon: false,
				text: p.font_size,
				menu: menuSize,
				onShow: function(e) {
					e.control.addClass(sizeClass+'-menu');
					e.control.initLayoutRect();
				},
				onclick: function(e) {
					if (e.control.settings.data) {
						ed.execCommand('FontSize', false, e.control.settings.data);
					}
				}
			});

			menuFam = parent.buildMenuItems(
					'Andale Mono|Arial|Arial Black|Book Antiqua|Courier New|Georgia|Helvetica|'
					+'Impact|Tahoma|Times New Roman|Trebuchet MS|Verdana',
					
					'andale mono,times|arial,helvetica,sans-serif|arial black,avant garde|book antiqua,palatino|'
					+'courier new,courier|georgia,palatino|helvetica|impact,chicago|tahoma,arial,helvetica,sans-serif|'
					+'times new roman,times|trebuchet ms,geneva|verdana,geneva',
					
					'font-family:{v}',
					famClass
				);

			ed.addButton('xen_fontfamily', {
				name: 'xen_fontfamily',
				type: 'menubutton',
				icon: false,
				text: p.font_family,
				menu: menuFam,
				onShow: function(e) {
					e.control.addClass(famClass+'-menu');
					e.control.initLayoutRect();
				},
				onclick: function(e) {
					if (e.control.settings.data) {
						ed.execCommand('FontName', false, e.control.settings.data);
					}
				}
			});
		}
	});

	/***
	*	XenForo Fright Plugin: float right buttons
	*	Independent plugin
	***/
	tinymce.create(xenPlugin+'.Fright', {
		Fright: function(parent) 
		{
			if(xenMCE.Params.frightMode != true)
				return false;

			$.extend(this, parent);

			var src = this, ed = this.getEditor(), 
			blockId = 'mce-top-right-body', first = 'mce-first', last = 'mce-last';
			
			ed.on('postrender', function(e) {
				$toolbar = $(e.target.contentAreaContainer).prev();
				$firstLine = $toolbar.find('.mce-toolbar').first();
				$buttons = src.getButtonsByProperty('xenfright', null, $toolbar);

				if(!$buttons.length > 0)
					return false;
				
				$buttons.each(function(i){
					$e = $(this);
					i++;
				
					if($e.hasClass(first))
						$e.removeClass(first).next().addClass(first);
						
					if($e.hasClass(last))
						$e.removeClass(last).prev().addClass(last);
		
					if(i == 1)
						$e.addClass(first);
		
					if(i == $buttons.length)
						$e.addClass(last);
				});
				
				$fl = $('<div id="mce-top-right" class="mce-container mce-flow-layout-item mce-btn-group" role="toolbar" />');
				$fl_body = $('<div id="'+blockId+'" />').append($buttons);
				$fl_body.prependTo($fl);
		
				$fl.prependTo($firstLine);
			})
			.on('FullscreenStateChanged', function(e){
				$('#'+blockId+':first-child').addClass(first);
				$('#'+blockId+':last-child').addClass(last);
			});
		}
	});

	/***
	*	jQueryTools Fix Plugin for XenForo overlays
	*	Independent plugin
	***/
	tinymce.create(xenPlugin+'.jQueryToolsFix', {
		jQueryToolsFix: function(parent) 
		{
			var ed = parent.getEditor(), settings = xenMCE.Params;
			
			ed.on('postrender', function(e) {
				var doc = ed.getDoc();
		
				if (!tinymce.isIE) {
					try {
						if (!ed.settings.readonly)
							doc.designMode = 'On';
					} catch (ex) {
						// Will fail on Gecko if the editor is placed in an hidden container element
						// The design mode will be set ones the editor is focused
					}
				}
			});

			if(settings.geckoFullfix){
				XenForo._overlayConfig.onBeforeLoad = function(e){
					if(window.tinyMCE && tinymce.isGecko && $(this.getTrigger()).hasClass('edit')){
						$mceTextarea = $(tinyMCE.activeEditor.getElement());
					
						if(!$mceTextarea.attr('disabled')){
							var id = $mceTextarea.attr('id');
							tinyMCE.execCommand('mceRemoveEditor', false, id);
							tinyMCE.execCommand('mceAddEditor', true, id);
							tinyMCE.activeEditor.focus();
						}
					};
				};
			}
		}
	});

	/***
	*	TinyMCE fullscreen plugin modified for XenForo
	*	This modifies the official plugin (so keep it in the config)
	***/
	tinymce.create(xenPlugin+'.Fullscreen', {
		Fullscreen: function(parent) 
		{
			var ed = parent.getEditor();

			if(typeof ed.buttons.fullscreen === 'undefined')
				return false;
				
			var flsc = ed.buttons.fullscreen;
			
			//Radical fix for a bug on IE8; might come from jQuery (to check)
			if(tinymce.isIE && tinymce.Env.ie <= 8 ){
				delete ed.buttons['fullscreen'];
				return false;
			}

			flsc.xenfright = true;

			ed.on('FullscreenStateChanged', function(e) {
				$container = $(ed.getContainer()); //must be inside the bind function
				$(e.target.contentDocument).find('html').css('overflow', 'auto')

				if(e.state == false){
					$('html, body').animate({ scrollTop: $container.offset().top }, 300);
					ed.focus();
				}
			});		
		}
	});
	
	/***
	*	XenForo Link Plugin
	*	Independent plugin (no need to keep the official one in the config)
	***/
	tinymce.create(xenPlugin+'.XenLink', {
		XenLink: function(parent) 
		{
			$.extend(this, parent);
			this.ed = this.getEditor();

			var src = this, ed = this.ed, linkButton = {}, linkButtonExtra = {};
			
			linkButton = {
				name: 'xen_link',
				icon: 'link',
				shortcut: 'Ctrl+K',
				stateSelector: 'a[href]',
				onclick: $.proxy(this, 'init')
			};
			
			linkButtonExtra = {
				type: 'splitbutton',
				menu: src.buildMenuItems(xenMCE.Phrases.unlink),
				onshow: function(e) {
					$('.mce-tooltip:visible').hide();
				},
				onselect: function(e) {
					ed.execCommand('unlink');
				}
			};
			
			if(xenMCE.Params.fastUnlink)
				$.extend(linkButton, linkButtonExtra);
			
			ed.addButton('xen_link', linkButton );
			
			ed.addButton('xen_unlink', {
				name: 'xen_unlink',
				icon: 'unlink',
				cmd: 'unlink',
				stateSelector: 'a[href]'
			});
		},
		init: function(e)
		{
			var size = xenMCE.Params.overlayLinkSize;
			config = {
				width: size.w,
				height: size.h,
				onsubmit: this.submit
			}
			
			this.loadOverlay('link', config);
		},
		submit: function(e, $overlay, ed, src)
		{
			xenMCE.Templates.Link.submit(e, $overlay, ed, src);
		}
	});

	/***
	*	XenForo Colors Plugin
	*	This modifies the official plugin (so keep it in the config)
	***/
	tinymce.create(xenPlugin+'.XenColors', {
		XenColors: function(parent) 
		{
			if(xenMCE.Params.extraColors != true)
				return false;

			$.extend(this, parent);
			var src = this, ed = this.ed = this.getEditor(), extra;

			/*Extra color picker*/
			function modifyButton(button) {
				var html = button.panel.html, cmd = button.selectcmd;
				
				button.panel.html = function(e){
					var advPicker = '<div href="#" class="mceAdvPicker" data-mode="'+cmd+'">';
					advPicker += xenMCE.Phrases.more_colors;
					advPicker += '</div>';
					return html() + advPicker;
				}

				/***
					The ideal solution would have been to modify the panel.onclick function but
					with jQuery 1.5.3 the $.proxy doesn't accept arguments, so the function couldn't
					be extended without being rewritten. To avoid this let's use the parent onclick
					listener (which is not used here) and a jQuery trick (unbind/bind);
				**/
				button.onclick = function(e){
					var buttonCtrl = this;
					
					$('.mceAdvPicker').unbind('click').bind('click', function(e){
						e.preventDefault();
						buttonCtrl.hidePanel();
						src.init($(this).data('mode'));
					});
				}
			}

			if(typeof ed.buttons.forecolor !== 'undefined'){
				modifyButton(ed.buttons.forecolor);
			}

			if(typeof ed.buttons.backcolor !== 'undefined'){
				modifyButton(ed.buttons.backcolor);
			}
			
			/*Postion fix - TinyMCE bug #bug 5910*/
			function posFix(e){
				$button = $(this.getEl());
				$panel = $(this.panel.getEl());
				var btnOffset = $button.offset();
				var btnPos = $button.position();

				if(!src.isFullscreen())
					btnOffset.top = btnOffset.top + $button.height() + parseInt($panel.css('marginTop'));
				else
					btnOffset.top = btnPos.top + $button.height() + parseInt($panel.css('marginTop'));

				$panel.offset(btnOffset);
			}

			ed.buttons.forecolor.onShow = posFix;
			ed.buttons.backcolor.onShow = posFix;
			
		},
		init: function(mode)
		{
			var size = xenMCE.Params.overlayColorPickerSize;
			config = {
				width: size.w,
				height: size.h,
				data: {	colorMode: mode },
				onsubmit: this.submit
			}
			
			callbacks = {
				src: this,
				onafterload: 'onafterload'
			}

			this.loadOverlay('colors', config, callbacks);
		},
		onafterload: function($ovl, data, ed, src)
		{
			xenMCE.Templates.ColorPicker.init($ovl, data, ed, src);
		},
		submit: function(e, $overlay, ed, src)
		{
			xenMCE.Templates.ColorPicker.submit(e, $overlay, ed, src);
		}
	});

	/***
	*	XenForo Media Plugin
	*	Independent plugin (no need to keep the official one in the config)
	***/
	tinymce.create(xenPlugin+'.XenMedia', {
		XenMedia: function(parent) 
		{
			$.extend(this, parent);
			this.ed = this.getEditor();
			var src = this, ed = this.ed;
			
			ed.addButton('xen_media', {
				name: 'xen_media',
				icon: 'media',
				onclick: $.proxy(this, 'init')
			});
		},
		init: function(e)
		{
			var size = xenMCE.Params.overlayMediaSize;
			
			config = {
				width: size.w,
				height: size.h,
				onsubmit: this.submit
			}
			
			this.loadOverlay('media', config);
		},
		submit: function(e, $overlay, ed, src)
		{
			xenMCE.Templates.Media.submit(e, $overlay, ed, src);
		}
	});

	/***
	*	XenForo Image Plugin
	*	Independent plugin (no need to keep the official one in the config)
	***/
	tinymce.create(xenPlugin+'.XenImage', {
		XenImage: function(parent) 
		{
			$.extend(this, parent);
			this.ed = this.getEditor();
			var src = this, ed = this.ed;
		
			ed.addButton('xen_image', {
				name: 'xen_image',
				icon: 'image',
				stateSelector: 'img:not([data-mce-object],[data-smilie])',
				onclick: $.proxy(this, 'init')
			});
		},
		init: function(e)
		{
			var node = this.ed.selection.getNode(),
			size = xenMCE.Params.overlayImageSize;
			
			config = {
				width: size.w,
				height: size.h,
				onsubmit: this.submit
			}

			if(node.nodeName == 'IMG'){
				config.data = {
					url: this.ed.dom.getAttrib(node, 'src')
				}
			}
			
			this.loadOverlay('image', config);
		},
		submit: function(e, $overlay, ed, src)
		{
			xenMCE.Templates.Image.submit(e, $overlay, ed, src);
		}
	});

	/***
	*	XenForo Code Plugin
	*	Independent plugin
	***/
	tinymce.create(xenPlugin+'.XenCode', {
		XenCode: function(parent) 
		{
			$.extend(this, parent);
			this.ed = this.getEditor();
			var src = this, ed = this.ed, n = 'xen_code';
		
			ed.addButton(n, {
				name: n,
				icon: n,
				iconset: 'xenforo',
				onclick: $.proxy(this, 'init')
			});
		},
		init: function(e)
		{
			config = {
				width: 480,
				height: 300,
				onsubmit: this.submit
			}

			this.loadOverlay('code', config);
		},
		submit: function(e, $overlay, ed, src)
		{
			xenMCE.Templates.Code.submit(e, $overlay, ed, src);
		}
	});
	
	/***
	*	XenForo Quote
	*	Independent plugin
	***/
	tinymce.create(xenPlugin+'.XenQuote', {
		XenQuote: function(parent) 
		{
			var ed = parent.getEditor(), n = 'xen_quote';

			ed.addButton(n, {
				name: n,
				icon: n,
				iconset: 'xenforo',
				onclick: function(e){
					parent.insertBbCode('quote', false);
				}
			});
		}
	});

	/***
	*	XenForo Smilies
	*	Independent plugin
	***/
	tinymce.create(xenPlugin+'.XenSmilies', {
		XenSmilies: function(parent) 
		{
			$.extend(this, parent);
			var ed = this.getEditor(), n = 'xen_smilies',i = 1, i_max = xenMCE.Params.xSmilies,
			smilies = xenMCE.Params.xenforo_smilies, prefix = 'mceQuattroSmilie';

			function getHtml() 
			{
				var dom = ed.dom, smiliesHtml = '<div role="presentation" class="'+prefix+'Block">',
				dataTags = 'data-smilie="yes"';
				/*** Above: 	> The data-smilie is used by @XenForo_Html_Renderer_BbCode. There are many conditions but actually the data-smilie should be enough
					 	> It is will also used to trigger the smilie button and prevent the img button to be triggered
				**/
				
				tinymce.each(smilies, function(v, k) {
					if(i_max != 0 && i > i_max)
						return false;

					k = dom.encode(k);
					
					if(typeof v[1] === 'number'){
						smiliesHtml += '<a href="#"><img src="styles/default/xenforo/clear.png" alt="'+k+'" '+dataTags+' class="'+prefix+' '+prefix+'Sprite mceSmilie'+v[1]+'"  /></a>';
					}else{
						smiliesHtml += '<a href="#"><img src="'+dom.encode(v[1])+'" alt="'+k+'" '+dataTags+' class="'+prefix+'" /></a>';
					}

						i++;
				});
		
				smiliesHtml += '</div>';
				
				return smiliesHtml;
			}

			ed.addButton(n, {
				name: n,
				icon: 'emoticons',
				type: 'panelbutton',
				stateSelector: 'img[data-smilie]',
				popoverAlign: 'bc-tl',
				panel: {
					autohide: true,
					html: getHtml,
					onclick: function(e) {
						var linkElm = ed.dom.getParent(e.target, 'a');

						if (linkElm) {
							var imgHtml = $(linkElm).html();
							ed.execCommand('mceInsertContent', false, imgHtml);
							this.hide();
						}
					}
				}
			});
		}
	});

	/***
	*	XenForo Non Breakin 
	*	Independent plugin: copy of the official plugin. Reason: Want 4 and not 3 blank characters
	***/
	tinymce.create(xenPlugin+'.XenNonBreaking', {
		XenNonBreaking: function(parent) 
		{
			var editor = parent.getEditor(), name = 'xen_nonbreaking', cmd = 'XenNonBreaking';
			
			editor.addCommand(cmd, function() {
				editor.insertContent(
					(editor.plugins.visualchars && editor.plugins.visualchars.state) ?
					'<span data-mce-bogus="1" class="mce-nbsp">&nbsp;</span>' : '&nbsp;'
				);
			});
		
			editor.addButton(name, {
				name: name,
				cmd: cmd
			});

			if (editor.getParam('nonbreaking_force_tab')) {
				editor.on('keydown', function(e) {
					if (e.keyCode == 9) {
						e.preventDefault();
		
						var i = 0;
						while (i<4){
							editor.execCommand(cmd);
							i++;
						}
					}
				});
			}
		
		}
	});
})();