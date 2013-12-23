(function($, window, document, undefined) {
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
		getSelection: function()
		{
			return xenMCE.Overlay._get('selection');			
		},
		_get:function(key)
		{
			var bckOv = xenMCE.Tools.backupOverlay;
			
			if(bckOv[key] !== undefined)
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
			var src = this, s = this.static;
			
			if(ed === undefined){
				ed = tinymce.activeEditor;
			}

			ed.on('init', function(e) {
				$(ed.getElement()).data('mce4', true);
			});
			
			this.$textarea = $(ed.getElement());
			this.isOldXen = src.getParam('oldXen');

			/*Extend events*/
			ed.on('init', function(e) {
				var win = ed.windowManager, winOpen = win.open;
				
				win.open = function(args, params)
				{
					if(args === undefined)
						args = false;
						
					if(params === undefined)
						params = false;
				
					var settings = { args: args, params: params};
					ed.fire('XenModalInit', settings);
					
					var modal = winOpen(args, params),
						settings = { modal: modal, args: args, params: params};
					ed.fire('XenModalComplete', settings);
					
					return modal;
				}
			});

			/* Get Editor */
			this.getEditor = function (){ return ed; };
			
			/* Fullscreen State */			
			this.isFullscreen = function(e) { 
				if(ed.plugins.fullscreen === undefined)
					return false
				else
					return ed.plugins.fullscreen.isFullscreen();
			};
			
			/*Create a static mirror*/
			xenMCE.Overlay._Tools = this;

			/*XenForo plugins AutoLoader*/
			if(xenMCE.Plugins.Auto !== undefined){
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

			/*Get attachments key params*/
			var attachData = ed.settings.xen_attach.split(',');
			xenAttach = { type:attachData[0], id:attachData[1], hash:attachData[2] };

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

			if(windowManagerConfig !== 'object')
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
					},
					attach: xenAttach
				}, $.proxy(this, '_overlayLoader')
			);
		},
		_overlayLoader:function(ajaxData)
		{
			if (XenForo.hasResponseError(ajaxData) || typeof ajaxData.templateHtml !== 'string')
				return;

			var self = this,
				editor = this.getEditor(),
				params = this.overlayParams,
				wmConfig = params.wmConfig, 
				html = '<div><div class="mce-xen-body">'+ajaxData.templateHtml+'</div></div>',
				data = {},
				regex = /<script[^>]*>([\s\S]*?)<\/script>/ig,
				regexMatch,
				scripts = [];

			//Take template inline scripts and place them inside an array
			while (regexMatch = regex.exec(html)){
				scripts.push(regexMatch[1]);
			}
				
			html = html.replace(regex, '');

 			new XenForo.ExtLoader(ajaxData, function()
 			{
				/**
				*	XenForo ExtLoader: JS & CSS successfully loaded
				**/
				
				var 	win = editor.windowManager,
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
					buttonOk = self.getTagName($Submit, true);

				if($Cancel.length == 1)
					buttonCancel = self.getTagName($Cancel, true);

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
					
					self.overlayInputs = data;
					staticBackup.inputs = data;
					return data;
				}
				
				getDatas($html);
				
				/*Title*/
				if($title)
					wmConfig.title = $title.text();

				if(wmConfig.title === undefined || !wmConfig.title)
					wmConfig.title = phrase.notitle;

				/*Overlay size*/
				var defaultSize = self.getParam('overlayDefaultSize');
				
				if(wmConfig.width === undefined)
					wmConfig.width = defaultSize.w;

				if(wmConfig.height === undefined)
					wmConfig.height = defaultSize.h;
			
				if($title.data('width') !== undefined)
					wmConfig.width = parseInt($title.data('width'));

				if($title.data('height') !== undefined)
					wmConfig.height = parseInt($title.data('height'));

				/*Autofield & extend Data*/
				if(wmConfig.data !== undefined){
					//Autofield existed tags with name attribute with their related data
					$.each(wmConfig.data, function(k, v){
						$e = $html.find('[name='+k+']');
						if($e.length == 1){
							if(self.getTagName($e, inputsTags)){
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
				if(wmConfig.onsubmit !== undefined){
					originalSubmit = wmConfig.onsubmit;
					
					wmConfig.onsubmit = function(params){
						var $overlay = $(editor.windowManager.windows[0].getEl());
						xenDatas = getDatas($overlay);
						$.extend(params.data, xenDatas);

						originalSubmit(params, $overlay, editor, self);
					};
				}

				/* Buttons Ok/Cancel + listeners */
				if(wmConfig.buttons === undefined){
					wmConfig.buttons = [
						{text: buttonOk, subtype: 'primary xenSubmit', minWidth: 80, onclick: function(e) {
							var win = editor.windowManager.windows[0];
							$overlay = $(win.getEl());

							/* Private onsubmit callback */
							if(params.onsubmit != false){
								var xenDatas = getDatas($overlay);
								$.extend(e.data, xenDatas);
								params.src[params.onsubmit](e, $overlay, editor, self);
							}

							if(win.find('form')[0] !== undefined)
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
					var wmConfigModified = params.src[params.onbeforeload](wmConfig, self);
					if (wmConfigModified !== undefined)
						wmConfig = wmConfigModified;
				}

				/* Launch the TinyMCE overlay */
				var modal = win.open(wmConfig, { 
					dialogName: params.dialog,
					modalClassName: params.dialog,
					ovlParams: params
				});

				/* Get overlay */
				$overlay = $(win.windows[0].getEl());
				xenMCE.Tools.backupOverlay.$overlay = $overlay;
				
				/* Eval inline scripts */
				if (scripts.length){
					for (i = 0; i < scripts.length; i++) {
						$.globalEval(scripts[i]);
					}
				}
				
				/* Get body height */
					//$overlay.find('.mce-xen-body').height(); doesn't work well on IE 7-8-9
				var ovlBodyHeight = $overlay.children('.mce-container-body').height();

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
				$tabs = self.cleanWhiteSpace($tabs);
					
				$panes = $tabs.next('.mcePanes').children().addClass('mce-pane');
				if($tabs.length > 0 && $panes.length > 0){
					$tabs.children().addClass('mce-tab');
					var i = $tabs.find('.mceActive').index();
					$tabs.tabs($panes, {
						current: 'mce-active',
						initialIndex: (i >= 0 ? i:0)
					});
				}

				/*Slider - provided by JQT*/
				var slideTag = 'mceSlides';
				$slides = $overlay.find('.'+slideTag);

				
				if($slides.length > 0){
					var sl = ovlBodyHeight;
					$slides.height(sl).children().height(sl-$slides.data('diff'));
					
					$slider_tabs = $overlay.find('.'+slideTag+'Tabs');
					$('<a class="'+slideTag+'Navig '+slideTag+'Backward">&lsaquo;</a><a class="'+slideTag+'Navig '+slideTag+'Forward">&rsaquo;</a>').insertBefore($slides);
					$overlay.find('.'+slideTag+'Navig').css('top', (sl/2)-10+'px');

					$slider_tabs.tabs($slides.children(), {
						effect: 'fade',
						fadeOutSpeed: "fast",
						rotate: true
					}).slideshow({
						prev:'.'+slideTag+'Backward',
						next:'.'+slideTag+'Forward',
						clickable: false
					});
				}

				/*MultiLine mode for Textarea*/
				$multi = $overlay.find('textarea, .mce-multiline');
				if($multi){
					win.windows[0].off('keydown');
					$multi.attr('spellcheck', 'false').attr('hidefocus', 'true');
				}

				/*Checkbox shortcut*/
				$checkBox = $overlay.find('.xenCheckBox');
				
				if($checkBox.length > 1){
					$checkBox.each(function(i){
						var phrase = $(this).data('phrase'),
						inputName = $(this).data('inputname'),
						checked = ($(this).data('checked') == 'checked') ? 1 : 0,
						html = '<i class="mce-ico mce-i-checkbox"></i><span>'+phrase+'</span><input name="'+inputName+'" type="hidden" value="'+checked+'" />';
				
						if(checked)
							$(this).addClass('mce-checked');
				
						$(html).prependTo($(this));
					});
					
					self._initCheckBox($checkBox);
				}

				/* Activate overlay & its inline scripts*/
				$overlay.xfActivate();
				
				/* Afterload Callback*/				
				if(params.onafterload != false)
					params.src[params.onafterload]($overlay, data, editor, self);
			});
		},
		_initCheckBox: function($checkBox)
		{
			$checkBox.children('i').unbind('click').bind('click', function(e){
				$parent = $(this).parent();
				$input = $(this).siblings('input');

				var isChecked = parseInt($input.val()), chkClass = 'mce-checked';
				
				if(isChecked){
					$parent.removeClass(chkClass);
					$input.val(0);
				}else{
					$parent.addClass(chkClass);
					$input.val(1);				
				}
			});
		},
		responsiveModal: function(modal)
		{
			var $overlay = $(modal.getEl()), responsive = 'responsive',
			$mainBody = $overlay.children('.mce-container-body');
		
			function centerRepos(){
				var repos_left = ( $(window).width() - $overlay.width() )/2,
					$xenMceBody = $overlay.find('.mce-xen-body'),
					bodyOverscrollVal = $xenMceBody.css('overscroll'),
					$xenMceFooter = $overlay.find('mce-foot');
					
				if(repos_left < 0){
					repos_left = 1;
					
					/*Tag some Elements with the responsive class*/
					modal.addClass(responsive);
					$overlay.children().add($xenMceBody).addClass(responsive);

					var autoWidthVal = $(window).width(),
						safeWidthVal = autoWidthVal-5,
						$autoWidthEl = $overlay.find('*').filter(function() {
							var inlineWidthVal = $(this).prop('style')['width'],
								allWidthVal = $(this).css('width'),
								filterResult;

							function filterWidth(targetWidthVal)
							{
								if(targetWidthVal.indexOf('%') != -1){
									return false;
								}
								
								if(parseInt(targetWidthVal) >= autoWidthVal){
									if(!$(this).data('owidth')){
										$(this).data('owidth', targetWidthVal);
									};
									return true;
								}							
							}
							
							filterResult = filterWidth(inlineWidthVal);
							
							if(filterResult == true){
								return true;
							}
							
							filterResult = filterWidth(allWidthVal);
							
							if($(this).css('width') == $(this).parent().css('width')){
								return false;
							}
							
							if(filterResult == true){
								return true;
							}

							return false;
						}),
						$noWrapEl = $overlay.find('*').filter(function() {
							var onw = $(this).css('white-space');
							if(onw != 'normal' && !$(this).data('onw')){
								$(this).data('onw', onw);
								return true;
							}
							return false;
						});

					/*Width Management*/
					$autoWidthEl.add($overlay).css({'max-width':safeWidthVal+'px'});
					$overlay.find('.responsiveBlock').css({'max-width':safeWidthVal-15+'px'});
					
					/*Overflow Management*/
					if($overlay.find('.mainBodyOverflow').length){
						$mainBody.css('overflow', 'auto');
					}

					if($overlay.find('.disableXenBodyOverflow').length == 0){
						$xenMceBody.data('ovsc', bodyOverscrollVal).css('overflow', 'auto');
					}
					
					/*White-space Management*/
					$noWrapEl.css({'white-space':'normal'});
				}else{
					//Initially for the resize function, but doesn't work well - keep for reference
					/*
					var ovsc = $xenMceBody.data('ovsc');
					if(ovsc){
						$xenMceBody.css('overflow', ovsc);
					}
					
					$overlay.find('*').filter(function() {
						var owidth = $(this).data('owidth'),
							onw = $(this).data('onw');
						if(owidth){
							$(this).css({width:owidth, 'max-width':owidth});
						}
						if(onw){
							$(this).css({'white-space':onw});
						}
					});
					
					$overlay.children().removeClass('responsive');
					$xenMceBody.removeClass('responsive');
					*/
				}
				
				$overlay.css({ left: repos_left });
			}
				
			centerRepos();		
		},
		isDefined: function(v, k)
		{
			if(k === undefined){
				if(v === undefined)
					return false;
				else
					return v;
			}else{
				if(v === undefined || v[k] === undefined)
					return false;
				else
					return v[k];		
			}
		},
		getTagName: function($e, autoReturn, expectedTags)
		{
			var tag = $e.get(0).tagName.toLowerCase();

			//TagName Tool
			if(expectedTags === undefined && autoReturn === undefined)
				return tag;

			if(autoReturn !== undefined && autoReturn !== 'boolean')
				 expectedTags = autoReturn;

			//AutoReturn Tool if inputs => return val else => return text
			if(autoReturn === true){
				inputs = ['input','textarea','select'];

				if($.inArray(tag, inputs) !== -1){
					return $e.val();
				} else {
					return $e.text();
				}
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
		isActiveButton: function(buttonName){
			var buttonConfig = [], ed = this.getEditor();
			for (var i=1; ed.settings['toolbar'+i] !== undefined;i++){
				buttonConfig = buttonConfig.concat(ed.settings['toolbar'+i].split(' '));
			}

			if(tinymce.inArray(buttonConfig, buttonName) == -1){
				return false;
			}
					
			return true;
		},
		getButtonByName: function(name, getEl)
		{
			var ed = this.getEditor(),	
			buttons = ed.buttons,
			toolbarObj = ed.theme.panel.find('toolbar *');
	
			if(buttons[name] === undefined)
				return false;
			
			var settings = buttons[name], result = false, length = 0;
			
			tinymce.each(settings, function(v, k){
				length++;
			});
			
			tinymce.each(toolbarObj, function(v, k) {
				if (v.type != 'button' || v.settings === undefined)
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
		getPathEl: function(jquery)
		{
			var ed = this.getEditor(),
			statusbar = ed.theme.panel && ed.theme.panel.find('#statusbar')[0];
			
			if(statusbar){
				var path = statusbar.find('.path');
						
				if(path[0] !== undefined){
					var el = path[0].getEl();
					return (jquery == true) ? $(el) : el;
				}
			}
			
			return false;
		},
		getButtonsByProperty: function(prop, val, jQueryEl)
		{
			var ed = this.getEditor(),	
			toolbarObj = ed.theme.panel.find('toolbar *'),
			results = {};
	
			tinymce.each(toolbarObj, function(v, k) {
				var settings = v.settings;
				
				if (v.type != 'button' || settings === undefined)
					return;

				if( (val !== undefined) && settings[prop] !== undefined && settings[prop] == val)
					results[k] = v;
				else if( (val === undefined || val === null) && settings[prop] !== undefined)
					results[k] = v;
			});

			if(jQueryEl !== undefined){
				var $buttons = $();
				$.each(results, function(k, v){
					$buttons = $buttons.add($(v.getEl()));
				});
				
				results = $buttons;
			}
			
			if(jQueryEl === 'object' && jQueryEl instanceof jQuery){
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
			var items = [], bakeData = [], dataVal, textVal;
			
			if(value === undefined)
				value = text;
			
			tinymce.each(value.split(/\|/), function(v) {
				bakeData.push(v);
			});
			
			tinymce.each(text.split(/\|/), function(text, i) {
				dataVal = (bakeData[i] !== undefined) ? bakeData[i] : '';

				if(css === undefined || css == null)
					return items.push({ text: text, value: dataVal } );

				var baker = { 
					title: text,
					text: text,
					value: dataVal,
					textStyle: css.replace(/{t}/g, text).replace(/{v}/g, dataVal)
				};
				
				if(classes !== undefined)
					baker.classes = classes;

				items.push(baker);
			});

			return items;
		},
		createListBoxChangeHandler: function(items, formatName) {
			ed = this.getEditor();
		
			return function() {
				/*How to spend 3 hours to debug a function that was supposed to work? */
				var self = this; //Answer: forget the "var". Bloody hell
				
				ed.on('nodeChange', function(e) {
					var formatter = ed.formatter;
					var value = null;
			
					tinymce.each(e.parents, function(node) {
						tinymce.each(items, function(item) {
						
							if (formatName) {
								if (formatter.matchNode(node, formatName, {value: item.value})) {
									value = item.value;
								}
							} else {
								if (formatter.matchNode(node, item.value)) {
									value = item.value;
								}
							}
		
							if (value) {
								return false;
							}
						});
		
						if (value) {
							return false;
						}
					});

					self.value(value);
				});
			};
		},
		insertBbCode: function(tag, tagOptions, content){
			tag = tag.replace(/^at_/, '');
		
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
		getParam: function(name)
		{
			if(xenMCE.Params[name] !== undefined){
				return xenMCE.Params[name];
			} else {
				return null;
			}
		},
		unescapeHtml: function(string, options) 
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
		getSelection: function()
		{
			return xenMCE.Overlay._get('selection');			
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
	*	XenForo Custom Buttons
	*	Independent plugin
	***/
	tinymce.create(xenPlugin+'.BbmButtons', {
		BbmButtons: function(parent) 
		{
			$.extend(this, parent);
			this.ed = this.getEditor();
			var ed = this.ed;
			
			var src = this, buttons = src.getParam('bbmButtons');

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
				}else if (data._return != 'kill'){
					config.onclick = function(e){
						var ovlConfig, ovlCallbacks;
						
						src.bbm_tag = tag;
						src.bbm_separator = data.separator;
						
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
				
				if(data._return == 'kill'){
					if(ed.buttons[data.code] === undefined){
						console.debug('Button "'+data.code+'" not found - Dev: activate your plugin before xenforo plugin / Admin: Delete it from the BBM');
						return;						
					}
						
					$.extend(ed.buttons[data.code], config);
					return;
				}
				
				src.ed.addButton(n, config);
			});
		},
		onafterload: function($ovl, data, ed, src)
		{
			var dialog = src.overlayParams.dialog.replace('bbm_', 'Bbm_');

			if(	xenMCE.Templates[dialog] !== undefined
				&&
				xenMCE.Templates[dialog].onafterload !== undefined
			){
				xenMCE.Templates[dialog].onafterload($ovl, data, ed, src);
			}
			
		},
		submit: function(e, $overlay, ed, src)
		{
			var dialog = src.overlayParams.dialog.replace('bbm_', 'Bbm_');
			
			if(	xenMCE.Templates[dialog] !== undefined
				&&
				xenMCE.Templates[dialog].submit !== undefined
			){
				xenMCE.Templates[dialog].submit(e, $overlay, ed, src);
			} 
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

			var args = {content: this.ed.getContent()};
			this.ed.fire('XenSwitchToBbCode', args);
			var html = args.content;

			XenForo.ajax(
				'index.php?editor/to-bb-code',
				{ html: html },
				$.proxy(this, 'wysiwygToBbCodeSuccess')
			);
		},
		wysiwygToBbCodeSuccess: function(ajaxData)
		{
			if (XenForo.hasResponseError(ajaxData) || ajaxData.bbCode === undefined) 
				return;

			if(ajaxData.isConnected !== undefined && !ajaxData.isConnected){
				this.ed.windowManager.alert(ajaxData.notConnectedMessage);
			}

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
			if (XenForo.hasResponseError(ajaxData) || ajaxData.html == undefined)
				return;

			if(ajaxData.isConnected !== undefined && !ajaxData.isConnected){
				this.ed.windowManager.alert(ajaxData.notConnectedMessage);
			}
				
			$container = $(this.ed.getContainer());
			$existingTextArea = $(this.ed.getElement());
	
			if(!$existingTextArea.attr('disabled'))
				return; // already using
	
			$existingTextArea.attr('disabled', false);
			$container.show();


			var args = {content: ajaxData.html};
			this.ed.fire('XenSwitchToWysiwyg', args);
			var html = args.content;
	
			this.ed.setContent(html);
			this.ed.focus();
	
			this.$bbCodeTextContainer.remove();
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
			sizeClass = 'xen-font-size', fontFamily = 'font-family',  famClass = 'xen-'+fontFamily, p = xenMCE.Phrases, 
			fontSizeText = '', fontSizeValues;

			if(parent.isOldXen === true){
				fontSizeValues = 'xx-small|x-small|small|medium|large|x-large|xx-large'; //for Xen 1.1
			} else {
				fontSizeValues = '9px|10px|12px|15px|18px|22px|26px'; //for Xen 1.2
			}

			for (var i=1;i<8;i++)
			{
				fontSizeText += p.size+' '+i;

				if(i != 7)
					fontSizeText += '|';
			}

			menuSize = parent.buildMenuItems(
					fontSizeText, //Text
					fontSizeValues, //Value
					'font-size:{v}', //Css
					sizeClass //Item Class
				);
				
			ed.addButton('xen_fontsize', {
				name: 'xen_fontsize',
				//type: 'menubutton',
				//menu: menuSize,
				type: 'listbox',
				values: menuSize,
				icon: false,
				fixedWidth: true,
				text: p.font_size,
				onPostRender: parent.createListBoxChangeHandler(menuSize, 'fontsize'),
				onShow: function(e) {
					e.control.addClass(sizeClass+'-menu');
					e.control.initLayoutRect();
				},
				onclick: function(e) {
					if (e.control.settings.value) {
						ed.execCommand('FontSize', false, e.control.settings.value);
					}
				}
			});

			menuFam = parent.buildMenuItems(
					'Andale Mono|Arial|Arial Black|Book Antiqua|Courier New|Georgia|Helvetica|'
					+'Impact|Tahoma|Times New Roman|Trebuchet MS|Verdana',
					
					'andale mono,times|arial,helvetica,sans-serif|arial black,avant garde|book antiqua,palatino|'
					+'courier new,courier|georgia,palatino|helvetica|impact,chicago|tahoma,arial,helvetica,sans-serif|'
					+'times new roman,times|trebuchet ms,geneva|verdana,geneva',
					
					fontFamily+':{v}',
					famClass
				);

			ed.addButton('xen_fontfamily', {
				name: 'xen_fontfamily',
				//type: 'menubutton',
				//menu: menuFam,
				type: 'listbox',
				values: menuFam,
				icon: false,
				fixedWidth: true,
				text: p.font_family,
				onPostRender: parent.createListBoxChangeHandler(menuFam, 'fontname'),
				onShow: function(e) {
					e.control.addClass(famClass+'-menu');
					e.control.initLayoutRect();
				},
				onclick: function(e) {
					if (e.control.settings.value) {
						ed.execCommand('FontName', false, e.control.settings.value);
					}
				}
			});
			
			//Get back the font-family fallbacks so MCE can match them with the above listbox values
			var convTable = {};

			$.each(menuFam, function(k, v){
				convTable[v.title.toLowerCase()] = v.value;
			});
			
			ed.on('preInit', function() {
				ed.on('PreProcess SetContent', function(e){
					var dom = ed.dom;

					tinymce.each(dom.select('span', e.node), function(node) {
						var fontname = node.style.fontFamily;
						if(fontname){
							fontname = fontname.toLowerCase().replace(/'/g, '');
							if(fontname !== 'serif' && convTable[fontname] !== undefined){
								dom.setStyle(node, 'fontFamily', convTable[fontname]);
							}
						}
					});
				});
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
			if(parent.getParam('frightMode') != true)
				return false;

			$.extend(this, parent);

			var src = this, ed = this.getEditor(), blockId = 'mce-top-right-body';

			ed.on('postrender', function(event) {
				var $toolbar = $(event.target.contentAreaContainer).prev(),
					$buttons = src.getButtonsByProperty('xenfright', null, $toolbar),
					$firstLine = $toolbar.find('.mce-toolbar').first();

				function resetFrightButtons(){
					var first = 'mce-first', last = 'mce-last';
					
					$buttons.each(function(){
						$(this).removeClass(first).removeClass(last);
					})
					.first().addClass(first).end()
					.last().addClass(last).end();
				}

				if(!$buttons.length > 0)
					return false;

				$buttons.click(function(){
					setTimeout(function() {
						resetFrightButtons();
					}, 0);
				});

				resetFrightButtons();
				
				var $fl = $('<div id="mce-top-right" class="mce-container mce-flow-layout-item mce-btn-group" role="toolbar" />'),
					$fl_body = $('<div id="'+blockId+'" />').append($buttons);	
								
				$fl_body.prependTo($fl);
				$fl.prependTo($firstLine);

				ed.on('FullscreenStateChanged', function(){
					resetFrightButtons();
				});
			});
		}
	});

	/***
	*	XenForo Modal Extension
	*	Independent plugin
	***/
	tinymce.create(xenPlugin+'.Modal', {
		Modal: function(parent) 
		{
			ed.on('XenModalComplete', function(settings) {
				var modal = settings.modal, args = settings.args, params = settings.params,
					$modal = $(modal.getEl()), 
					className = false,
					disableResponsive = ($modal.find('.noResponsive').length) ? true : false;

				function addBodyOverflow(){
					$modal.children('.mce-container-body').addClass('mainBodyOverflow');
				}

				/*Manual detection for charmap*/
				var chmp = 'mce-charmap';
				if($modal.find('.'+chmp).length >= 1){
					className = 'modal-'+chmp;
					addBodyOverflow();
				}

				/*Make MCE+XEN Modal Responsive*/
				if(params && params.disableResponsive !== undefined){
					disableResponsive = params.disableResponsive;
				}
				
				if(args && args.disableResponsive !== undefined){
					disableResponsive = args.disableResponsive;
				}
	
				if(parent.isOldXen === true || parent.getParam('disableResponsive')){
					disableResponsive = true;
				}
	
				if(!disableResponsive){
					parent.responsiveModal(modal);
				}

				/*Add class to modal root if defined*/
				if(args && args.modalClassName !== undefined){
					className = args.modalClassName;
				}
				
				if(params && params.modalClassName !== undefined){
					className = params.modalClassName;
				}
				
				if(className){
					modal.addClass(className);
				}
				
				/*IE Btn Size fix - #bug:6538*/
				var $normalBtn = $modal.find('.mce-btn:not(.mce-primary)');
				$normalBtn.width($normalBtn.width()+2);
			});
		}
	});

	/***
	*	jQueryTools Fix Plugin for XenForo overlays
	*	Independent plugin
	***/
	tinymce.create(xenPlugin+'.quirks', {
		quirks: function(parent) 
		{
			var ed = parent.getEditor(), inlineEd = 'InlineMessageEditor';

			/* 2013/08/28: fix for the autoresize plugin needed with the overlay and with some browsers (IE, Chrome) */
			ed.on('postrender', function(e) { //other event possible: focus
				$container = $(ed.getContainer());
				if($container.parents('form').hasClass(inlineEd)){
					tinyMCE.activeEditor.execCommand('mceAutoResize', false, e);
				}
			});

			/* 2013/10/26: Extend the behaviour of mceInsertContent command to go the to bottom of the inserted object (optional) */
			ed.on('BeforeSetContent', function(e)
			{
				if(!parent.getParam('extendInsert'))
					return;
				
				function contentIsImg(content)
				{
					var regex = /^(?:[\s]+)?<img[^>]*?\/>(?:[\s]+)?$/;
					return regex.test(content);
				}
				
				function tagImgClass(content)
				{
					var regex = /(class="[^"]+?(?="))/;
					
					if(regex.test(content)){
						content = content.replace(regex, '$1 '+uniqid);				
					}else{
						content = content.replace(/(<img)/, '$1 class="'+uniqid+'" ');
					}
					
					return content;
				}

				var uniqid = 'tmp_'+(new Date().getTime()).toString(16);
				
				if(contentIsImg(e.content)){
					e.content = tagImgClass(e.content);
				}

				ed.on('ExecCommand', function(e) {
					var cmd = e.command;
					
					if(cmd != 'mceInsertContent')
						return;
						
					var bm = ed.selection.getBookmark(),
						$iframeBody = $(ed.getBody()),
						$bm = $iframeBody.find('#'+bm.id+'_start'),
						top = $bm.offset().top,
						$iframeHtml = $iframeBody.parent().add($iframeBody);//crossbrowsers
	
					ed.selection.moveToBookmark(bm);
	
					if(!$bm.length)
						return;
	
					var content = e.value, 
						args = {
							skip_focus: true
						};

					if(!contentIsImg(content)){
						$iframeHtml.scrollTop(top);
						ed.execCommand('mceAutoResize', false, e, args);
					}else{
						$iframeBody.find('img.'+uniqid).one('load', function(e) {
							ed.execCommand('mceAutoResize', false, e, args);

							var $img = $(this),
								offset = $img.offset(),
								top = offset.top + $img.height();
		
							$img.removeClass(uniqid);
							$iframeHtml.scrollTop(top);
						});
					}
				});
			});
			
			/**
			 * The below quirks are only for XenForo 1.1.x
			 */
			if(!parent.isOldXen){
				return false;
			}

			/* 2013/08/28: These two fixes don't seem to be needed anymore. I'm going to wait a little then I will exclude them (just need to comment) from the minify version*/
			ed.on('postrender', function(e) {
				var doc = ed.getDoc();
				$form = $(ed.getContainer()).parents('form');
				
				if (!tinymce.isIE && $form.hasClass(inlineEd)) {
					try {
						if (!ed.settings.readonly){
							doc.designMode = 'On';
						}
					} catch (ex) {
						// Will fail on Gecko if the editor is placed in an hidden container element
						// The design mode will be set ones the editor is focused
					}
				}
			});

			if(parent.getParam('geckoFullfix')){
				var originalBeforeLoadFct = XenForo._overlayConfig.onBeforeLoad;
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
					if(originalBeforeLoadFct === 'function'){
						originalBeforeLoadFct(e);
					}
				};
			}
		}
	});


	/***
	*	TinyMCE Table plugin Integration
	*
	***/
	tinymce.create(xenPlugin+'.TableIntegration', {
		TableIntegration: function(parent) 
		{
			this.ed = parent.getEditor();
			var self = this, ed = this.ed;
		
			function addSkin(e, id){
				var dom = ed.dom, tableElm;
				tableElm = ed.dom.getParent(ed.selection.getStart(), 'table');

				if(tableElm == undefined)
					return;
					
				$tableElm = $(tableElm);
				$tableElm.attr('data-skin', 'skin'+id);
			}
			
			function selectedSkin(e){
				var dom = ed.dom, tableElm, skinId;
				tableElm = ed.dom.getParent(ed.selection.getStart(), 'table');

				$skins = $(e.control.getEl()).find('.mce-text');
				$skins.css('font-weight', 'normal');
				
				if(tableElm == undefined)
					return;
					
				$tableElm = $(tableElm);
				skinId = $tableElm.attr('data-skin');
				
				if(skinId == undefined)
					return;
				
				skinId = parseInt(skinId.replace('skin', ''));
				
				if(isNaN(skinId) || skinId > 4)
					return false;

				$skins.eq(skinId-1).css('font-weight', 'bold');
			}
		
			/*Context menu*/
			ed.addMenuItem('xen_tableskin', {
				name: 'xen_tableskin',
				text: 'Table skins',
				context: 'table',
				onShow: selectedSkin,
				menu: [
					{text: 'Skin 1', onclick: function(e) { addSkin(e, 1); }},
					{text: 'Skin 2', onclick: function(e) { addSkin(e, 2); }},
					{text: 'Skin 3', onclick: function(e) { addSkin(e, 3); }},
					{text: 'Skin 4', onclick: function(e) { addSkin(e, 4); }}
				]
			});
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

			if(ed.buttons.fullscreen === undefined)
				return false;
				
			var flsc = ed.buttons.fullscreen;
			
			//Radical fix for a bug on IE8; might come from jQuery (to check)
			//Edit 2013-06-14: is working now with IE8 but not with IE7
			if(tinymce.isIE && tinymce.Env.ie <= 7 ){
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
			
			if(parent.getParam('fastUnlink'))
				$.extend(linkButton, linkButtonExtra);
			
			ed.addButton('xen_link', linkButton );
			
			ed.addButton('xen_unlink', {
				name: 'xen_unlink',
				icon: 'unlink',
				cmd: 'unlink',
				stateSelector: 'a[href]'
			});
			
			/*Context menu*/
			ed.addMenuItem('xen_link', {
				name: 'xen_link',
				icon: 'link',
				shortcut: 'Ctrl+K',
				stateSelector: 'a[href]',
				onclick: $.proxy(this, 'init'),
				text: xenMCE.Phrases.xen_link_desc,				
				context: 'insert',
				prependToContext: true
			});
		},
		init: function(e)
		{
			var size = this.getParam('overlayLinkSize');
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
	*	Last update: 2013-06-14
	***/
	tinymce.create(xenPlugin+'.XenColors', {
		XenColors: function(parent) 
		{
			$.extend(this, parent);
			var src = this, ed = this.ed = this.getEditor(), extra;

			/*Extra color picker*/
			function modifyButton(button) {
				var html = button.panel.html, cmd = button.selectcmd, onclick = button.onclick;
				
				if(parent.getParam('extraColors') == true){
					button.panel.html = function(e){
						var advPicker = '<div href="#" class="mceAdvPicker" data-mode="'+cmd+'">';
						advPicker += xenMCE.Phrases.more_colors;
						advPicker += '</div>';
						return html() + advPicker;
					}
				}

				/***
				*	The ideal solution would have been to modify the panel.onclick function but
				*	with jQuery 1.5.3 the $.proxy doesn't accept arguments, so the function couldn't
				*	be extended without being rewritten. To avoid this let's use the parent onclick
				*	listener (which is not used here) and a jQuery trick (unbind/bind);
				*	
				*	Edit 2013-06-14: the onclick is now used by mce, use onshow instead
				**/
				button.onshow = function(e){
					/*Color Picker Mangement*/
					var buttonCtrl = this;
					$('.mceAdvPicker').unbind('click').bind('click', function(e){
						e.preventDefault();
						buttonCtrl.hidePanel();

						var buttonObj = {
							mode: $(this).data('mode'),
							buttonCtrl: buttonCtrl
						}

						src.init(buttonObj); // arg = created element (1)
					});

					/*Postion fix - TinyMCE bug #bug 5910*/
					$button = $(this.getEl());
					$panel = $(this.panel.getEl());
					var btnOffset = $button.offset(), btnPos = $button.position();

					if(!src.isFullscreen())
						btnOffset.top = btnOffset.top + $button.height() + parseInt($panel.css('marginTop'));
					else
						btnOffset.top = btnPos.top + $button.height() + parseInt($panel.css('marginTop'));

					$panel.offset(btnOffset);					
				}
			}

			if(ed.buttons.forecolor !== undefined){
				modifyButton(ed.buttons.forecolor);
			}

			if(ed.buttons.backcolor !== undefined){
				modifyButton(ed.buttons.backcolor);
			}
		},
		init: function(buttonObj)
		{
			this._colorButton = buttonObj;// element added (2)
			
			var size = this.getParam('overlayColorPickerSize');
			config = {
				width: size.w,
				height: size.h,
				onsubmit: $.proxy(this, 'submit') // element sent (3)
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
			src = this; // src modifided with the new element (4)
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
			var size = this.getParam('overlayMediaSize');
			
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
			size = this.getParam('overlayImageSize');
			
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
			var src = this, ed = this.getEditor(),  n = 'xen_smilies', n2 = 'xen_smilies_picker',
			windowType = parent.getParam('smiliesWindow');
			
			function _getHtml(fullSmilies) 
			{
				var i = 1, i_max, prefix = 'mceQuattroSmilie', suffix = '',
					smilies = parent.getParam('xenforo_smilies'), 
					smiliesCat = parent.getParam('smiliesCat'),
					smiliesMenuBtnCat = parent.getParam('xCatSmilies');

				if(fullSmilies === true){
					i_max = 0;
					suffix = 'Full';
				}else{
					i_max = parent.getParam('xSmilies');
				}
				
				var smiliesHtml = '<div role="presentation" class="'+prefix+'Block'+suffix+' responsiveBlock">', dataTags = 'data-smilie="yes"';
				/*** Above: 	> The data-smilie is used by @XenForo_Html_Renderer_BbCode. There are many conditions but actually the data-smilie should be enough
					 	> It is will also used to trigger the smilie button and prevent the img button to be triggered
				**/

				function getGrid(smiliesHtml, smilies)
				{
					tinymce.each(smilies, function(v, k) {
						var dom = ed.dom, smilieInfo = { id: v[1], desc: v[0], bbcode: dom.encode(k)}, smiliesDesc = parent.getParam('smiliesDesc');
						
						if(smiliesDesc == 'bbcode'){
							smilieInfo.desc = k;
						}else if(smiliesDesc == 'none'){
							smilieInfo.desc = '';					
						}
	
						if(i_max != 0 && i > i_max)
							return false;
	
						if(smilieInfo.id === 'number'){
							smiliesHtml += '<a href="#"><img src="styles/default/xenforo/clear.png" alt="'+smilieInfo.bbcode+'" title="'+smilieInfo.desc+'" '+dataTags+' class="'+prefix+' '+prefix+'Sprite mceSmilie'+smilieInfo.id+'"  /></a>';
						}else{
							smiliesHtml += '<a href="#"><img src="'+dom.encode(smilieInfo.id)+'" alt="'+smilieInfo.bbcode+'" title="'+smilieInfo.desc+'" '+dataTags+' class="'+prefix+'" /></a>';
						}
	
						i++;
					});
					
					return smiliesHtml;
				}

				if(!smiliesCat){
					smiliesHtml = getGrid(smiliesHtml, smilies);
				} else {
					if(	fullSmilies !== true 
						&& smiliesMenuBtnCat != -1 
						&& smilies[smiliesMenuBtnCat] !== undefined
					){
						smiliesHtml += getGrid('', smilies[smiliesMenuBtnCat].smilies);
					} else {
						tinymce.each(smilies, function(v, k) {
							if(fullSmilies === true){
								smiliesHtml += '<p class="mce_smilie_cat_title">'+v.title+'</p>';
								smiliesHtml += '<div class="mce_smilie_cat">'+getGrid('', v.smilies)+'</div>';
							} else {
								smiliesHtml += getGrid('', v.smilies);
							}
						});
					}
				}
		
				smiliesHtml += '</div>';
				
				return smiliesHtml;			
			}

			function getSmilies() 
			{
				return _getHtml();
			}

			function pickerDialog()
			{
				var fullSmilies = _getHtml(true);
				
				var smiliesPanel = {
					type: 'container',
					html: fullSmilies,
					onclick: function(e) {
						e.preventDefault();
						var linkElm = ed.dom.getParent(e.target, 'a');
						if (linkElm) {
							var imgHtml = $(linkElm).html();
							ed.execCommand('mceInsertContent', false, imgHtml);
						}
					}
				}

				var win = ed.windowManager.open({
					title: "Smilies picker",
					spacing: 10,
					padding: 10,
					items: [smiliesPanel],
					buttons: [{
						text: "Close", onclick: function() {
							win.close();
						}
					}]
				},{ 
					modalClassName: 'modal-smilies'
				});

			}

			ed.addButton(n, {
				name: n,
				icon: 'emoticons',
				type: 'panelbutton',
				stateSelector: 'img[data-smilie]',
				popoverAlign: 'bc-tl',
				panel: {
					autohide: true,
					html: getSmilies,
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

			var configN2 = {
				name: n2,
				icon: n2,
				iconset: 'xenforo',
				tooltip: "Smilies picker",
				stateSelector: 'img[data-smilie]'
			};

			if( windowType == 'slider'){
				configN2.onclick = $.proxy(this, 'initOvl');
			}else if(windowType == 'belowbox' && !this.isOldXen){
				configN2.onclick = function(e){
					src.initBox(this, e, ed);
				}
			}else{
				configN2.onclick = pickerDialog;			
			}
			
			ed.addButton(n2, configN2);
		},
		initOvl: function(e)
		{
			var size = this.getParam('overlaySmiliesSize');
			
			config = {
				width: parseInt(size.w),
				height: parseInt(size.h)
			}
			
			callbacks = {
				src: this,
				onafterload: 'onafterload'
			}

			this.loadOverlay('smilies_slider', config, callbacks);
		},
		onafterload: function($ovl, data, ed, src)
		{
			xenMCE.Templates.SmiliesSlider.init($ovl, data, ed, src);
		},
		initBox: function(src, e, ed)
		{
			var self = this, 
				$container = $(ed.getContainer()).parent(),
				$smilies =  $container.find('.redactor_smilies');
			
			if ($smilies.length){
				$smilies.slideToggle('slow', function(){
					src.active($smilies.is(":visible"));
				});
				return;
			}

			if (self.smiliesPending){
				return;
			}
			
			self.smiliesPending = true;

			XenForo.ajax(
				'index.php?editor/smilies',
				{ mce: 'mce4' },
				function(ajaxData) {
					if (XenForo.hasResponseError(ajaxData)){
						return;
					}
					if (ajaxData.templateHtml){
						new XenForo.ExtLoader(ajaxData, function(){
							$smilies = $('<div class="redactor_smilies mce" />').html(ajaxData.templateHtml);
							$smilies.hide();
							$smilies.on('click', '.Smilie', function(e) {
								e.preventDefault();
								var $smilie = $(this), html = $.trim($smilie.html());
								ed.execCommand('mceInsertContent', false, html);
								ed.focus();
							});

							$container.append($smilies);
							$smilies.slideToggle();
						});
					}
				}
			).complete(function() {
				self.smiliesPending = false;
				src.active(true);
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
	
	/***
	*	XenForo Draft Integration
	*	Independent plugin
	***/
	tinymce.create(xenPlugin+'.XenDraft', {
		XenDraft: function(parent) 
		{
			//Only compatible with XenForo 1.2.x
			if(parent.isOldXen)
				return false;

			this.ed = parent.getEditor();
			this.draftText = xenMCE.Phrases.draft;
			this.$textarea = parent.$textarea;
			this.autoSaveUrl = this.$textarea.data('auto-save-url');

			var 	src = this, 
				ed = this.ed,
				linkButtonExtra = {},
				dfm = 'Draft mode: ',
				rd = 'restoredraft';

			if(!this.autoSaveUrl || !parent.getParam('xendraft')){
				console.info(dfm+'Mce');
				return;
			}

			/*The xendraft system has been found, disable the autosave plugin*/
			console.info(dfm+'Xen');
			
			var menuItems = parent.buildMenuItems(
						src.draftText.save+'|'+src.draftText._delete,
						'saveDraft|deleteDraft',
						null,
						'xen_draft'
					);
			
			var menuAction = function(e){
				src.saveDraft(true, (e.control.settings.value == 'deleteDraft'))
			}
			
			ed.on('BeforeRenderUI', function(e) {
				if(!parent.isActiveButton(rd)){
					return false;
				}
			});

			ed.addButton(rd, {
				name: rd,
				title: src.draftText.save,
				type: "menubutton",
				menu: menuItems,
				onselect: menuAction
			});
			
			ed.on('init', function(e) {
				src.pathEl = parent.getPathEl(true);
				src.initAutoSave();
			});
			
			ed.addCommand('xenDraftSave', function() {
				src.saveDraft(true);				
			});

			ed.addCommand('xenDraftDelete', function() {
				src.saveDraft(true, true);
			});
		},
		initAutoSave: function()
		{
			var 	self = this, 
				$form = $(this.ed.getContainer()).parents('form'),
				options = self.$textarea.data('options'),
				content = this.ed.getContent();

			if (!$form.length)
				return;

			this.lastAutoSaveContent = content;

			var interval = setInterval(function() {
				if (!self.$textarea.data('mce4')){
					clearInterval(interval);
					return;
				}

				self.saveDraft();
			}, (options.autoSaveFrequency || 60) * 1000);
		},
		saveDraft: function(forceUpdate, deleteDraft)
		{
			var 	self = this, 
				$form = $(this.ed.getContainer()).parents('form'),
				wmn = this.ed.windowManager,
				args = {content: this.ed.getContent()},
				content = '';

			this.ed.fire('SavingXenDraft', args);
			content = args.content;
				
			if (!deleteDraft && !forceUpdate && content == this.lastAutoSaveContent)
			{
				return false;
			}

			this.lastAutoSaveContent = content;

			var e = $.Event('BbCodeWysiwygEditorAutoSave');
				e.editor = this;
				e.content = content;
				e.deleteDraft = deleteDraft;
			
			$form.trigger(e);
			
			if (e.isDefaultPrevented())
				return false;

			if (this.autoSaveRunning)
				return false;
			
			this.autoSaveRunning = true;

			XenForo.ajax(
				this.autoSaveUrl,
				$form.serialize() + (deleteDraft ? '&delete_draft=1' : ''),
				function(ajaxData) {
					var e = $.Event('BbCodeWysiwygEditorAutoSaveComplete');
					e.ajaxData = ajaxData;
					$form.trigger(e);

					if (!e.isDefaultPrevented()) {
						var notice;

						if (ajaxData.draftSaved) {
							notice = self.draftText.saved;
						} else if (ajaxData.draftDeleted) {
							notice = self.draftText.deleted;
						}

						if(!notice)
							return;
							
						if (forceUpdate || deleteDraft) {
							wmn.alert(notice);
							return;
						}
						
						if(self.pathEl == false){
							console.log(self.draftText.saved);
							return;
						}
						
						$path = self.pathEl;
						
						$('<div class="draftNotice"><span>'+notice+'</span></div>')
							.insertAfter($path)
							.css({display:'inline-block', padding: '8px'})
							.finish().hide().fadeIn().delay(2000).fadeOut();
					}
				},
				{global: false}
			).complete(function() {
				self.autoSaveRunning = false;
			});

			return true;
		}
	});

	/***
	*	XenForo Icons
	*	Independent plugin - Must be at the last of this file (reason - bug:#6543)
	***/
	tinymce.create(xenPlugin+'.XenIcons', {
		XenIcons: function(parent) 
		{
			var ed = parent.getEditor(), p = xenMCE.Phrases;

			//2013-12-05: still needs to use BeforeRenderUI here
			ed.on('BeforeRenderUI', function(e) {
				/*Delete items from menu if the button is not there*/
				function deleteMenuItem(item){
					if(ed.menuItems[item] !== undefined)
						delete ed.menuItems[item];
				}

				if(!parent.isActiveButton('xen_link')){
					deleteMenuItem('xen_link');
				}

				if(!parent.isActiveButton('table')){
					var menuToDelete = ['inserttable', 'xen_tableskin', 'cell', 'row', 'column', 'deletetable'];
					tinymce.each(menuToDelete, function(v){
						deleteMenuItem(v);
					});
				}
			});

			/* Auto translate tooltips based on suffix _desc*/
      			tinymce.each(ed.buttons, function(v, k){
      				var key_desc = k+'_desc';

      				if(!XenForo.isTouchBrowser()){
      					if(p[key_desc] !== undefined){
      						ed.buttons[k].tooltip = p[key_desc];
      					}
      				}else{
      					//Tooltip are annoying on Touch devices - let's delete them
      					if(ed.buttons[k].tooltip !== undefined)
      						delete ed.buttons[k].tooltip;
      				}
      			});
			
			/***
			*	Modify the list buttons and delete extra options if selected
			**/
			function disableExtra(button){
				if(ed.buttons[button].type !== undefined){
					ed.buttons[button].type = 'button';
				}
			}

			if(parent.getParam('extraLists') != true){
				disableExtra('bullist');
				disableExtra('numlist');
			}

			ed.on('init', function(e) {
				$buttons = parent.getButtonsByProperty('iconset', 'xenforo', true);
				$buttons.find('i.mce-ico').addClass('mce-xenforo-icons');

				var statBar = ed.theme.panel.find('#statusbar');

				if(statBar && statBar.length > 0)
					$statBar = $(statBar[0].getEl());

				if(parent.getParam('hidePath') && statBar.length > 0)
					$statBar.find('.mce-path').css('visibility', 'hidden');
			});
		}
	});	
})(jQuery, this, document);