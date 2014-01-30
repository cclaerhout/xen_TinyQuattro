(function($, _window, document, undefined) {
/***
*	xenMCE - AllInOne Functions
***/
	if(xenMCE == undefined) xenMCE = {};

	/***
	*	Extend xenMCE - Overlay: Shortcuts to get Overlay params
	***/
	
	xenMCE.Lib = 
	{
		getTools: function()
		{
			return this.Tools;
		},
		overlay: 
		{
			create: function(dialog, windowManagerConfig, callbacks){
				xenMCE.Lib.Tools.loadOverlay(dialog, windowManagerConfig, callbacks); //Static call
			},
			get: function()
			{
				return xenMCE.Lib._get('$overlay');
			},
			getParams: function()
			{
				return xenMCE.Lib._get('params');	
			},
			getInputs: function()
			{
				return xenMCE.Lib._get('inputs');
			},
			getEditor: function()
			{
				return xenMCE.Lib._get('editor');
			},
			getSelection: function()
			{
				return xenMCE.Lib._get('selection');
			}
		},
		_get:function(key)
		{
			var bckOv = xenMCE.Tools.backupLib;
				
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
	*	> To create a real TinyMCE plugin and access these functions a static mirror is available in xenMCE.Lib.Tools
	***/

	tinymce.create('xenMCE.Tools', {
		Tools: function(ed) 
		{
			var src = this, s = this.static;
			
			if(ed === undefined){
				ed = tinymce.activeEditor;
			}

			/* Get Editor */
			this.getEditor = function (){ return ed; };

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

			/* Fullscreen State */			
			this.isFullscreen = function(e) { 
				if(ed.plugins.fullscreen === undefined)
					return false
				else
					return ed.plugins.fullscreen.isFullscreen();
			};
			
			/*Create a static mirror*/
			xenMCE.Lib.Tools = this;

			/*XenForo plugins AutoLoader*/
			ed.on('XenReady', function(){
				if(xenMCE.Plugins.Auto !== undefined){
					$.each(xenMCE.Plugins.Auto, function(k, v){
						new v(src, ed);
					});
				}
			});
		},
		overlayParams: {},
		overlayInputs: {},
		overlayCache: {},
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
					   # 'onclose'		(string with the name of the function to call - optionnal)
					   	> Use this callback to do something when the cancel button of the form is pressed
					   	> Arguments: event, $overlay, editor, parentClass

				Please note that the onsubmit from the official windowManagerConfig has been modified:
				=> Its arguments are now: event, $overlay, editor, parentClass (instead of only event)
				
				About the windowManagerConfig and its optional data object, if one of its key can be found in the template inside
				the "name" parameter of a tag, this tag will be automatically filled.
				
				When submitting the form, TinyMCE automatically passes the input tags in a data object inside the event. If you need
				to get the the content of another tag, just add this tag a "data-value" parametter, you can put what you want in the value
				You will have both the content of the data-value and the content of the tag
			**/

			if(this.xenOverlayIsloading == true) return false;
		
			this.xenOverlayIsloading = true;

			var self = this,
				editor = this.getEditor(),
				dom = editor.dom,
				isUrl = isLink = isEmail = false, url_text = url_href = '',
				sel = editor.selection, selHtml, selText,
				isEmpty = (selText) ? 0 : 1,
				staticBackup = xenMCE.Tools.backupLib,
				each = tinymce.each;

			this.dialog = dialog;
			
			this.findTargetWindow = function(dialogName){
				var windows = editor.windowManager.windows,
					targetWindow = false;

				each(windows, function(window){
					if(window.params != undefined & window.params.dialogName == dialogName){
						targetWindow = window;
						return false;;
					}
				});

				if(!targetWindow && windows[0] !== undefined){
					return windows[0];
				}

				return targetWindow;
			};
			
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
			var attachData = editor.settings.xen_attach.split(',');
			
			this.xenAttach = { type:attachData[0], id:attachData[1], hash:attachData[2] };
			
			/* Get selection after url checker */
			selHtml = sel.getContent();
			selText = sel.getContent({format: 'text'});
		
			/* Backup selection so it can be retrieve from xenMCE.Lib */
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

			var targetWindow = this.findTargetWindow(dialog);

			if(targetWindow){
				targetWindow.show();
				this.xenOverlayIsloading = false;
				return false;
			}
			
			if(typeof windowManagerConfig !== 'object')
				windowManagerConfig = {};

			this.overlayParams = {
				dialog: dialog,
				src: self.isDefined(callbacks, 'src'),
				onbeforeload: self.isDefined(callbacks, 'onbeforeload'),
				onafterload: self.isDefined(callbacks, 'onafterload'),
				onsubmit: self.isDefined(callbacks, 'onsubmit'),
				onclose: self.isDefined(callbacks, 'onclose'),
				wmConfig: windowManagerConfig
			}

			var data = { 
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
				attach: this.xenAttach
			};

			var $cacheNode = $(document).find('#mce4_cache_'+dialog);

			if(self.overlayCache[dialog] === undefined || !$cacheNode.length){
				XenForo.ajax('index.php?editor/quattro-dialog', data, $.proxy(this, '_overlayLoader'));
			}else{
				self._overlayFormatter($cacheNode.html(), false, true, data);
			}
		},
		_overlayLoader:function(ajaxData)
		{
			if (XenForo.hasResponseError(ajaxData) || typeof ajaxData.templateHtml  !== 'string'){
				this.xenOverlayIsloading = false;
				return;
			}

			var self = this,
				html = '<div><div class="mce-xen-body">'+ajaxData.templateHtml+'</div></div>',
				regex = /<script[^>]*>([\s\S]*?)<\/script>/ig,
				regexMatch,
				scripts = [];

			//Take template inline scripts and place them inside an array
			while (regexMatch = regex.exec(html)){
				scripts.push(regexMatch[1]);
			}
				
			html = html.replace(regex, '');

			self.overlayCache[self.dialog] = this.xenAttach.hash;
			
			$cache = $('<div id="mce4_cache_'+self.dialog+'">'+html+'</div>').hide();
			$(document).find('body').append($cache);

 			new XenForo.ExtLoader(ajaxData, function()
 			{
				//XenForo ExtLoader: JS & CSS successfully loaded
				self._overlayFormatter(html, scripts);
			});
		},
		_overlayFormatter:function(html, scripts, reloaded, reloadedData)
		{
      			var self = this,
      				editor = this.getEditor(),
      				params = this.overlayParams,
      				wmConfig = params.wmConfig, 
      				win = editor.windowManager,
      				data = {},
      				Dialog = self.ucfirst(self.dialog);
      				
      			var buttonOk = self.getPhrase('insert'),
      				buttonCancel = self.getPhrase('cancel'),
      				inputsTags = self.getInputTags(),
      				staticBackup = xenMCE.Tools.backupLib;

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
      				wmConfig.title = self.getPhrase('notitle');

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
      					var targetWindow = self.findTargetWindow(self.dialog),
      						$overlay = $(targetWindow.getEl()),
      						xenDatas = getDatas($overlay);
      	
      					$.extend(params.data, xenDatas);

      					originalSubmit(params, $overlay, editor, self);
      				};
      			}

      			/* Buttons Ok/Cancel + listeners */
      			if(wmConfig.buttons === undefined){
      				wmConfig.buttons = [
      					{text: buttonOk, subtype: 'primary xenSubmit', minWidth: 80, onclick: function(e) {
      						var targetWindow = self.findTargetWindow(self.dialog),
      							$overlay = $(targetWindow.getEl());

      						/* Private onsubmit callback */
      						if(params.onsubmit != false){
      							var xenDatas = getDatas($overlay);
      							$.extend(e.data, xenDatas);
      							
      							params.src[params.onsubmit](e, $overlay, editor, self);
      						}

      						if(targetWindow.find('form')[0] !== undefined)
      							targetWindow.find('form')[0].submit();
      						else
      							targetWindow.submit();

      						/* Private onclose callback */
      						if(params.onclose != false){
      							var xenDatas = getDatas($overlay);
      							$.extend(e.data, xenDatas);
      							params.src[params.onclose](e, $overlay, editor, self);
      						}

      						targetWindow.close();
      					}},
      					{text: buttonCancel, subtype: 'xenCancel', onclick: function(e) {
      						var $overlay = $(targetWindow.getEl());

      						/* Private onclose callback */
      						if(params.onclose != false){
      							var xenDatas = getDatas($overlay);
      							$.extend(e.data, xenDatas);
      							params.src[params.onclose](e, $overlay, editor, self);
      						}							
      						
      						targetWindow.close();
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
      			var targetWindow = self.findTargetWindow(self.dialog, true),
      				$overlay = $(targetWindow.getEl());
      				
      			xenMCE.Tools.backupLib.$overlay = $overlay;

      			/* Eval inline scripts */
      			if (scripts.length && scripts !== undefined){
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
      				targetWindow.off('keydown');
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

      			/* Onfastreload callback*/
			if(!$title.hasClass('FastReload')){
				delete self.overlayCache[self.dialog];
			}else if(reloaded){
				if(!reloadedData.isUrl){
					self._inlineLink(reloadedData);
				}

				self._onFastReload($overlay, reloadedData);
				
				if(self.isDefined(xenMCE.Templates[Dialog], 'onfastreload')){
					xenMCE.Templates[Dialog].onfastreload($overlay, reloadedData, editor, self);
				}
			}

      			/* Afterload Callback*/				
      			if(params.onafterload != false)
      				params.src[params.onafterload]($overlay, data, editor, self);
      				
      			self.xenOverlayIsloading = false;		
		},
		_onFastReload: function($overlay, data)
		{
			var self = this, inputsTags = self.getInputTags();

			inputsTags.push('textarea');
			
			$.each(inputsTags, function(i, v){
				$overlay.find(v+'.MceReset').val('');
			});

			if(data.isUrl){
				var urlDatas = data.urlDatas, urlTarget;
				if(data.isLink){
					urlTarget = 'input.MceLink';
				}else if(data.isEmail){
					urlTarget = 'input.MceEmail';
				}
				$overlay.find(urlTarget).val(urlDatas.href);
				$overlay.find(urlTarget+'Text').val(urlDatas.text);				
			}else{
				$.each(inputsTags, function(i, v){
					$overlay.find(v+'.MceSelec').val(data.selectedText);
					$overlay.find(v+'.MceSelecHtml').val(data.selectedHtml);				
				});
			}			
		},
		_inlineLink: function(data)
		{
			var self = this,
				urlRegex = self.getUrlRegex(),
				mailRegex = self.getMailRegex(),
				text = data.selectedText;

			if(!text.length)
				return;
			
			var urlDatasOk = function(){
				data.isUrl = true;
				data.urlDatas.text = data.urlDatas.href = text;			
			}
			
			if(urlRegex.test(text)){
				data.isLink = true;
				urlDatasOk();
			}

			if(mailRegex.test(text)){
				data.isEmail = true;
				urlDatasOk();
			}			
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
			var self = this,
				tag = $e.get(0).tagName.toLowerCase();

			//TagName Tool
			if(expectedTags === undefined && autoReturn === undefined)
				return tag;

			if(autoReturn !== undefined && typeof autoReturn !== 'boolean')
				 expectedTags = autoReturn;

			//AutoReturn Tool if inputs => return val else => return text
			if(autoReturn === true){
				var inputs = self.getInputTags();

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
		isActiveButton: function(buttonName, skipMenu){
			var editor = this.getEditor(),
				each = tinymce.each,
				buttonConfig = [],
				skipMenu = (skipMenu == true);
				
			for(var i=1; editor.settings['toolbar'+i] !== undefined;i++){
				buttonConfig = buttonConfig.concat(editor.settings['toolbar'+i].split(' '));
			}

			if(!skipMenu && editor.settings.menubar != false && editor.settings.menu != undefined){
				each(editor.settings.menu, function(v, k){
					if(v.items != undefined){
						buttonConfig = buttonConfig.concat(v.items.split(' '));
					}
					buttonConfig.push(k);
				});
			}

			if(tinymce.inArray(buttonConfig, buttonName) == -1){
				return false;
			}
					
			return true;
		},
		getButtonByName: function(name, getEl)
		{
			var editor = this.getEditor(),	
				buttons = editor.buttons,
				toolbarObj = editor.theme.panel.find('toolbar *');
	
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
			var editor = this.getEditor(),
				statusbar = editor.theme.panel && editor.theme.panel.find('#statusbar')[0];
			
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
			var editor = this.getEditor(),	
			toolbarObj = editor.theme.panel.find('toolbar *'),
			results = {};

			tinymce.each(toolbarObj, function(v, k) {
				var settings = v.settings;
				
				if ($.inArray(v.type, ['button', 'splitbutton']) === -1 || settings === undefined)
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
		getMenubar: function(){
			var editor = this.getEditor(),
				menubar = editor.theme.panel.find('menubar');
			
			if(menubar[0] !== undefined ){
				return $(menubar[0].getEl());
			}
			return $();
		},
		getToolbars: function(bypassParam){
			var editor = this.getEditor(),
				toolbars = editor.theme.panel.find('toolbar'),
				toolbarContainer = toolbars.parent(),
				tglMenuMode = parseInt(this.getParam('tglMenuMode'));

			if(!tglMenuMode && bypassParam === undefined){
				return	toolbarContainer;
			}else{
				return 	toolbars.slice(tglMenuMode);
			}
		},
		addIconClass: function($e, className){
			return $e.find('i.mce-ico').addClass('mce-'+className+'-icons');
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
		createListBoxChangeHandler: function(items, formatName, extraFct) {
			var editor = this.getEditor();
		
			return function(e) {
				var self = this;

				if(typeof extraFct === 'function') extraFct(self, e);
				
				editor.on('nodeChange', function(e) {
					if(self.nodeChangeOff) return;
					
					var formatter = editor.formatter;
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
					
					if(self.resetText){
						self.text(false);
					}
				});
			};
		},
		fixBtnFullscreen: function(btnCtrl, type, directEl)
		{
			/***
			 * For some unknow reasons, the fullscreen mode is buggy on XenForo & Firefox, let's fix it
			 * The above function must be called using the onshow callback
			 * Arguments:
			 * 1) btnCtrl : the button controler
			 * 2) type : the button type, ie: menu, panel
			 ***/
			var parent = this;

			if(!parent.isFullscreen())
				return false;

			var $button = $(btnCtrl.getEl()), 
				$el = (directEl === undefined) ? $(btnCtrl[type].getEl()) : $(directEl.getEl()),
				btnOffset = $button.offset(),
				btnPos = $button.position();

			if(!parent.isFullscreen())
				btnOffset.top = btnOffset.top + $button.height() + parseInt($el.css('marginTop'));
			else
				btnOffset.top = btnPos.top + $button.height() + parseInt($el.css('marginTop'));

			//Apply the fix
			$el.offset(btnOffset);			
		},
		handleDisabledState: function(ctrl, selector)
		{
			//Code from table plugin
			var editor = this.getEditor(), 
				dom = editor.dom,
				sel = editor.selection;
		
			function bindStateListener() {
				ctrl.disabled(!dom.getParent(sel.getStart(), selector));

				sel.selectorChanged(selector, function(state) {
					ctrl.disabled(!state);
				});
			}

			if (editor.initialized) {
				bindStateListener();
			} else {
				editor.on('init', bindStateListener);
			}
		},
		insertBbCode: function(tag, tagOptions, content){
			tag = tag.replace(/^at_/, '');
		
			var editor = this.getEditor(),
				dom = editor.dom, 
				caretId = 'MceCaretBb', 
				caret,
				oTag ='['+tag, 
				cTag = '[/'+tag+']';
		
			if(!content)
				content = editor.selection.getContent();

			content += '<span id="'+caretId+'"></span>';
			
			if(tagOptions)
				oTag += '='+tagOptions+']';
			else
				oTag += ']';
			
			editor.execCommand('mceInsertContent', false, oTag+content+cTag);
			editor.selection.select(dom.get(caretId));
			dom.remove(caretId);
		},
		getParam: function(name)
		{
			var editor = this.getEditor(),
				xenParams = editor.getParam('xenParams');

			if(xenParams[name] !== undefined){
				return xenParams[name];
			} else {
				return null;
			}
		},
		getPhrase: function(key1, key2)
		{
			/**
			 * TinyMCE has an I18n translation function, but it can still be useful
			 * to use this function to get long phrases - it avoid to have a long key
			 **/
			var xemMcePhrase = xenMCE.Phrases;
			 
			if(xemMcePhrase[key1] === undefined){
				return '';
			}
			 
			if(key2 !== undefined){
				 if(xemMcePhrase[key1][key2] !== undefined){
				 	return xemMcePhrase[key1][key2];
				 }
			}else{
				return xemMcePhrase[key1];
			}
			 
			return '';
		},
		getInputTags: function(){
			return ['input','textarea','select'];
		},
		ucfirst: function(string)
		{
			return string.charAt(0).toUpperCase() + string.slice(1);
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
		getUrlRegex: function()
		{
			return new RegExp('^(?:\s+)?(?:(?:https?|ftp|file)://|www\.|ftp\.)[-a-zA-Z0-9+&@#/%=~_|$?!:,.]*[-a-zA-Z0-9+&@\#/%=~_|$](?:\s+)?$', 'i');
		},
		getMailRegex: function()
		{
			return new RegExp('^(?:\s+)?[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}(?:\s+)?$', 'i');		
		},		
		getSelection: function()
		{
			return xenMCE.Lib._get('selection');			
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
			backupLib: {} //This static object will be used to easily retrieve the overlay datas outside the class (@see xenMCE.Lib)
		}
	});

	tinymce.PluginManager.add('xenforo', xenMCE.Tools);
	tinymce.PluginManager.add('xenReady', function(editor){
		editor.fire('XenReady');
	});	

/***
*	SUB-PLUGINS
***/
	var xenPlugin = 'xenMCE.Plugins.Auto';

	/***
	*	XenForo Custom Buttons
	*	Independent plugin
	***/
	tinymce.create(xenPlugin+'.BbmButtons', {
		BbmButtons: function(parent, ed) 
		{
			$.extend(this, parent);
			this.ed = ed;

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
							onafterload: 'onafterload',
							onclose: 'onclose'
						};
					
						src.loadOverlay('bbm_'+data.template, ovlConfig, ovlCallbacks);
					}
				}
				
				var createButtonAndMenu = function(config){
					src.ed.addButton(n, config);

					var menuConfig = $.extend({}, config);
					if(menuConfig.text == undefined){
						menuConfig.text = data.desc;
						menuConfig.tooltip = false;
						menuConfig.context = 'insert';
					}					
					src.ed.addMenuItem(n, menuConfig);
				}
				
				
				if(data._return == 'kill'){
					if(!parent.isActiveButton(data.code)){
						console.debug('Button "'+data.code+'" not found - Delete it from BBM');
						return;						
					}

					if(ed.buttons[data.code] === undefined){ //don't use isActiveButton here
						$.extend(ed.buttons[data.code], config);
						//createButtonAndMenu(config); /*To do: modify the return kill */
					}else{
						$.extend(ed.buttons[data.code], config);
					}
				}else{
					createButtonAndMenu(config);
				}
			});
			
			//Source Btn - Fright mode
			if(src.isActiveButton('code', true)){
				src.ed.buttons.code.xenfright = true;
			}
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
		onclose: function(e, $overlay, ed, src)
		{
			var dialog = src.overlayParams.dialog.replace('bbm_', 'Bbm_');

			if(	xenMCE.Templates[dialog] !== undefined
				&&
				xenMCE.Templates[dialog].onclose !== undefined
			){
				xenMCE.Templates[dialog].onclose(e, $overlay, ed, src);
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
		XenSwitch: function(parent, ed) 
		{
			$.extend(this, parent);
			
			var name = 'xen_switch',
				switchConfig;
				
			this.ed = ed;
			
			switchConfig = {
				name: name,
				icon: name,
				iconset: 'xenforo',
				xenfright: true,
				tooltip: this.getPhrase('switch_text', 0),
				onclick: $.proxy(this, 'wysiwygToBbCode')			
			}
			
			ed.addButton(name, switchConfig);
			ed.addMenuItem(name, $.extend({},
				switchConfig, {
					text: "Bb Code editor",
					tooltip: false
				})
			);			
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

			var $container = $(this.ed.getContainer()),
				$existingTextArea = $(this.ed.getElement()),
				$textContainer = $('<div class="bbCodeEditorContainer" />'),
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
				.text(this.getPhrase('switch_text', 1))
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
				
			var $container = $(this.ed.getContainer()),
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
		XenFonts: function(parent, ed) 
		{
			var Factory = tinymce.ui.Factory, 
				menuSize, menuFam,
				fontSize = 'font-size', sizeClass = 'xen-'+fontSize, fs = 'fontsize',
				fontFamily = 'font-family',  famClass = 'xen-'+fontFamily, ff = 'fontfamily',
				xenIcon = 'mce-xenforo-icons', fontSizeText = '', fontSizeValues, 
				smallFontBtn = parent.getParam('smallFontBtn');

			if(parent.isOldXen === true){
				fontSizeValues = 'xx-small|x-small|small|medium|large|x-large|xx-large'; //for Xen 1.1
			} else {
				fontSizeValues = '9px|10px|12px|15px|18px|22px|26px'; //for Xen 1.2
			}

			for (var i=1;i<8;i++)
			{
				fontSizeText += parent.getPhrase('size')+' '+i;

				if(i != 7)
					fontSizeText += '|';
			}

			menuSize = parent.buildMenuItems(
				fontSizeText, //Text
				fontSizeValues, //Value
				'font-size:{v}', //Css
				sizeClass //Item Class
			);
			
			//Use icons on small screens
			var extraFct = function(ctrl, e){
				var smallMode = function(){
					var $container = $(ed.getContainer()),
						fw = 'fixed-width',
						activated = false;

					var tasks = function(){
						var width = $container.width();

						if(width < 450 || smallFontBtn){
							ctrl.resetText = true;
							ctrl.text(false);
							
							if(!activated){
								ctrl.icon(ctrl._name+' '+xenIcon);
								ctrl.removeClass(fw);
								activated = true;
							}
						}else if(width >= 450 && activated){
							function getText(){
								var val = ctrl._value, text = ctrl.settings.text;
								if(val != null){
									$.each(ctrl._values, function(i,v){
										if(v.value == val){
											text = v.text;
											return false;
										} 
									});
								}
								return text;
							}
							
							ctrl.resetText = false;
							ctrl.text(getText());
							ctrl.icon(false);
							ctrl.addClass(fw);
							activated = false;
						}
					}
						
					tasks();
					ctrl.on('click', tasks)
					$(_window).resize(tasks);
				};
			
				ed.on('postrender', smallMode);
			}

			var onShowScrollToSelection = function(ctrl){
				var menu = ctrl.menu;

				if(menu){
					var $menu = $(menu.getEl()).children().first(),
						$pressed = $menu.find('[aria-pressed="true"]');
							
					if($pressed.length){
						$menu.scrollTop($menu.scrollTop() + $pressed.position().top);
					}else{
						$menu.scrollTop(0);
					}
				}
			}

			var fontSizeConfig = {
				name: 'xen_'+fs,
				//type: 'menubutton',
				//menu: menuSize,
				type: 'listbox',
				icon: false,
				fixedWidth: true,
				text: parent.getPhrase('font_size'),
				values: menuSize,
				onPostRender: parent.createListBoxChangeHandler(menuSize, fs, extraFct),
				onShow: function(e) {
					e.control.addClass(sizeClass+'-menu');
					e.control.initLayoutRect();
					parent.fixBtnFullscreen(this, 'menu');
					onShowScrollToSelection(this);
				},
				onclick: function(e) {
					if (e.control.settings.value) {
						ed.execCommand('FontSize', false, e.control.settings.value);
					}
				}			
			};

			ed.addButton('xen_'+fs, fontSizeConfig);

			menuFam = parent.buildMenuItems(
					'Andale Mono|Arial|Arial Black|Book Antiqua|Courier New|Georgia|Helvetica|'
					+'Impact|Tahoma|Times New Roman|Trebuchet MS|Verdana',
					
					'andale mono,times|arial,helvetica,sans-serif|arial black,avant garde|book antiqua,palatino|'
					+'courier new,courier|georgia,palatino|helvetica|impact,chicago|tahoma,arial,helvetica,sans-serif|'
					+'times new roman,times|trebuchet ms,geneva|verdana,geneva',
					
					fontFamily+':{v}',
					famClass
				);

			var fontFamilyConfig = {
				name: 'xen_'+ff,
				//type: 'menubutton',
				//menu: menuFam,
				type: 'listbox',
				icon: false,
				fixedWidth: true,
				text: parent.getPhrase('font_family'),
				values: menuFam,
				onPostRender: parent.createListBoxChangeHandler(menuFam, 'fontname', extraFct),
				onShow: function(e) {
					e.control.addClass(famClass+'-menu');
					e.control.initLayoutRect();
					parent.fixBtnFullscreen(this, 'menu');
					onShowScrollToSelection(this);
				},
				onclick: function(e) {
					if (e.control.settings.value) {
						ed.execCommand('FontName', false, e.control.settings.value);
					}
				}		
			};

			ed.addButton('xen_fontfamily', fontFamilyConfig);
			
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
		Fright: function(parent, ed) 
		{
			if(parent.getParam('frightMode') != true)
				return false;

			$.extend(this, parent);

			var src = this, 
				blockId = 'mce-top-right-body';

			ed.on('postrender', function(event) {
				var $toolbar = $(event.target.contentAreaContainer).prev(),
					$buttons = src.getButtonsByProperty('xenfright', null, $toolbar),
					$firstLine = $toolbar.find('.mce-toolbar').first();
				
				if(!$buttons.length)
					return false;

				function resetFrightButtons(){
					var first = 'mce-first', last = 'mce-last';
					
					$buttons.each(function(){
						$(this).removeClass(first).removeClass(last);
					})
					.first().addClass(first).end()
					.last().addClass(last).end();
				}

				ed.addCommand('resetFright', resetFrightButtons);

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
		Modal: function(parent, ed) 
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
			});
		}
	});

	/***
	*	jQueryTools Fix Plugin for XenForo overlays
	*	Independent plugin
	***/
	tinymce.create(xenPlugin+'.quirks', {
		quirks: function(parent, ed) 
		{
			var inlineEd = 'InlineMessageEditor';

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
					if(typeof originalBeforeLoadFct === 'function'){
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
		TableIntegration: function(parent, ed) 
		{
			this.ed = ed;
			
			var self = this;
		
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

			
			var postRenderMenu = function(e){
				parent.handleDisabledState(this, 'table');
			};

			var menu = [];
			for (var i=1; i<5;i++){
				menu.push({text: 'Skin '+i, onclick: function(e) { addSkin(e, 1); }, onPostRender: postRenderMenu});
			}
		
			/*Context menu*/
			ed.addMenuItem('xen_tableskin', {
				name: 'xen_tableskin',
				text: 'Table skins',
				context: 'table',
				onShow: selectedSkin,
				menu:  menu
			});
		}
	});

	/***
	*	TinyMCE fullscreen plugin modified for XenForo
	*	This modifies the official plugin (so keep it in the config)
	***/
	tinymce.create(xenPlugin+'.Fullscreen', {
		Fullscreen: function(parent, ed) 
		{
			if(!parent.isActiveButton('fullscreen'))
				return false;

			//Edit 2013-06-14: is working now with IE8 but not with IE7
			if(tinymce.isIE && tinymce.Env.ie <= 7 ){
				delete ed.buttons['fullscreen'];
				return false;
			}

			var flsc = ed.buttons.fullscreen;
			flsc.xenfright = true;

			ed.on('submit', function() {
				if(parent.isFullscreen()){
					ed.execCommand('mceFullScreen');
				}
			});

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
		XenLink: function(parent, ed) 
		{
			$.extend(this, parent);
			this.ed = ed;

			var src = this,
				linkConfig,
				linkConfigExtra,
				unlinkConfig;
			
			linkConfig = {
				name: 'xen_link',
				icon: 'link',
				shortcut: 'Ctrl+K',
				stateSelector: 'a[href]',
				onclick: $.proxy(this, 'init')
			};
			
			linkConfigExtra = {
				type: 'splitbutton',
				menu: src.buildMenuItems(parent.getPhrase('unlink')),
				onshow: function(e) {
					$('.mce-tooltip:visible').hide();
				},
				onselect: function(e) {
					ed.execCommand('unlink');
				}
			};
			
			if(parent.getParam('fastUnlink'))
				$.extend(linkConfig, linkConfigExtra);
			
			unlinkConfig = {
				name: 'xen_unlink',
				icon: 'unlink',
				cmd: 'unlink',
				stateSelector: 'a[href]'
			};

			ed.addButton('xen_link', linkConfig);
			ed.addMenuItem('xen_link', $.extend({},
				linkConfig, {
					text: 'Link',
					context: 'insert',
					prependToContext: true
				})
			);

			ed.addButton('xen_unlink', unlinkConfig);
			ed.addMenuItem('xen_unlink', $.extend({},
				unlinkConfig, {
					text: 'Unlink',
					tooltip: false
				})
			);
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
		XenColors: function(parent, ed) 
		{
			$.extend(this, parent);
			var src = this, extra;

			this.ed = ed;

			/*Extra color picker*/
			function modifyButton(button) {
				var html = button.panel.html, cmd = button.selectcmd, onclick = button.onclick;
				
				if(parent.getParam('extraColors') == true){
					button.panel.html = function(e){
						var advPicker = '<div href="#" class="mceAdvPicker" data-mode="'+cmd+'">';
						advPicker += parent.getPhrase('more_colors');
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
					src.fixBtnFullscreen(buttonCtrl, 'panel');
				}
			}

			if(parent.isActiveButton('forecolor', true)){
				modifyButton(ed.buttons.forecolor);
			}

			if(parent.isActiveButton('backcolor', true)){
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
		XenMedia: function(parent, ed) 
		{
			$.extend(this, parent);
			this.ed = ed;

			var src = this,	mediaConfig;

			mediaConfig = {
				name: 'xen_media',
				icon: 'media',
				onclick: $.proxy(this, 'init')
			};
			
			ed.addButton('xen_media', mediaConfig);

			ed.addMenuItem('xen_media', $.extend({},
				mediaConfig, {
					text: parent.getPhrase('xen_media_desc'),
					context: 'insert',
					tooltip: false
				})
			);			
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
		XenImage: function(parent, ed) 
		{
			$.extend(this, parent);

			this.ed = ed;
			
			var src = this, imageConfig;
		
			imageConfig = {
				name: 'xen_image',
				icon: 'image',
				stateSelector: 'img:not([data-mce-object],[data-smilie])',
				onclick: $.proxy(this, 'init')
			};
		
			ed.addButton('xen_image', imageConfig);
			
			ed.addMenuItem('xen_image', $.extend({},
				imageConfig, {
					text: 'Image',
					context: 'insert',
					tooltip: false
				})
			);			
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
		XenCode: function(parent, ed) 
		{
			$.extend(this, parent);
			
			var src = this, 
				name = 'xen_code',
				codeConfig;
		
			this.ed = ed;
			
			codeConfig = {
				name: name,
				icon: name,
				iconset: 'xenforo',
				onclick: $.proxy(this, 'init')			
			}
		
			ed.addButton(name, codeConfig);
			ed.addMenuItem(name, $.extend({},
				codeConfig, {
					text: parent.getPhrase('xen_code_desc'),
					context: 'insert',
					tooltip: false
				})
			);
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
		XenQuote: function(parent, ed) 
		{
			var xen_quote = 'xen_quote',
				quoteConfig;

			quoteConfig = {
				name: xen_quote,
				icon: xen_quote,
				iconset: 'xenforo',
				onclick: function(e){
					parent.insertBbCode('quote', false);
				}
			};

			if(!parent.isActiveButton(xen_quote)){
				ed.addButton(xen_quote, quoteConfig);
				ed.addMenuItem(name, $.extend({},
					quoteConfig, {
						text: parent.getPhrase('xen_quote_desc'),
						context: 'insert',
						tooltip: false
					})
				);
			}
		}
	});

	/***
	*	XenForo Smilies
	*	Independent plugin
	***/
	tinymce.create(xenPlugin+'.XenSmilies', {
		XenSmilies: function(parent, ed) 
		{
			$.extend(this, parent);
			
			var src = this, 
				n = 'xen_smilies', 
				n2 = 'xen_smilies_picker',
				windowType = parent.getParam('smiliesWindow');

			function _getHtml(fullSmilies) 
			{
				var dom = ed.dom || tinymce.DOM,
					i = 1, i_max, prefix = 'mceQuattroSmilie', suffix = '',
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
						var smilieInfo = { id: v[1], desc: v[0], bbcode: dom.encode(k)}, 
							smiliesDesc = parent.getParam('smiliesDesc');
						
						if(smiliesDesc == 'bbcode'){
							smilieInfo.desc = k;
						}else if(smiliesDesc == 'none'){
							smilieInfo.desc = '';					
						}
	
						if(i_max != 0 && i > i_max)
							return false;
	
						if(typeof smilieInfo.id === 'number'){
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

			var configN1Window = {
				autohide: true,
				html: getSmilies,
				onclick: function(e) {
					var linkElm = ed.dom.getParent(e.target, 'a');

					if (linkElm) {
						var imgHtml = $(linkElm).html();
						ed.execCommand('mceInsertContent', false, imgHtml);
						if(e.dontHide == undefined || !e.dontHide){
							this.hide();
						}
					}
				}
			};
			
			var configN1 = {
				name: n,
				icon: 'emoticons',
				type: 'panelbutton',
				stateSelector: 'img[data-smilie]',
				popoverAlign: 'bc-tl',
				panel: configN1Window
			};

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
					if(!src.isFullscreen()){
						src.initBox(this, e, ed);
					} else {
						//$.proxy(src.initOvl(e), src);
						pickerDialog();
					}
				}
			}else{
				configN2.onclick = pickerDialog;			
			}

			ed.addButton(n, configN1);
			ed.addButton(n2, configN2);

			ed.addMenuItem(n, $.extend({},
				configN1, {
					text: "Smilies",
					tooltip: false,
					type: 'menuitem',
					context: 'insert',
					autohide: false,
					panel: false,
					align: 'center',
					onclick: function(e){
						var self = this;
						$.extend(self, { mirrorClick: configN2.onclick });
						self.mirrorClick(e);
						self.hideMenu().parent().hide();
					},
					menu: [{
						type: 'container',
						html: getSmilies(),
						onclick: function(e){
							e.preventDefault();
							e.dontHide = true;
							configN1Window.onclick(e);
							e.stopPropagation();
						}
					}]
				})
			);

			ed.addMenuItem(n2, $.extend({},
				configN2, {
					text: "Smilies picker",
					context: 'insert',
					tooltip: false
				})
			);
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
							$smilies.xfActivate();
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
		XenNonBreaking: function(parent, editor) 
		{
			var name = 'xen_nonbreaking', 
				cmd = 'XenNonBreaking',
				noneBreakConfig;
			
			editor.addCommand(cmd, function() {
				//to do: disable in lists
				editor.insertContent(
					(editor.plugins.visualchars && editor.plugins.visualchars.state) ?
					'<span data-mce-bogus="1" class="mce-nbsp">&nbsp;</span>' : '&nbsp;'
				);
			});
		
			noneBreakConfig = {
				name: name,
				cmd: cmd
			};
		
			editor.addButton(name, noneBreakConfig);
			editor.addMenuItem(name, $.extend({},
				noneBreakConfig, {
					text: "Non-breaking space",
					context: 'insert',
					tooltip: false
				})
			);

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
		XenDraft: function(parent, ed) 
		{
			//Only compatible with XenForo 1.2.x
			if(parent.isOldXen)
				return false;

			$.extend(this, {
				ed: ed,
				draftText: parent.getPhrase('draft'),
				$textarea: parent.$textarea,
				autoSaveUrl: parent.$textarea.data('auto-save-url')		
			});

			var src = this, 
				linkButtonExtra = {},
				dfm = 'Draft mode: ',
				rd = 'restoredraft',
				rdConfig;

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

			rdConfig = {
				name: rd,
				title: src.draftText.save,
				type: "menubutton",
				menu: menuItems,
				onselect: menuAction			
			};

			ed.addButton(rd, rdConfig);

			ed.addMenuItem(rd, $.extend({},
				rdConfig, {
					separator: 'before',
					text: src.draftText.save,
					tooltip: false,
					type: false
				})
			);
			
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
			var self = this, 
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
	*	XenForo Spoiler
	*	Independent plugin
	***/
	tinymce.create(xenPlugin+'.XenSpoiler', {
		XenSpoiler: function(parent, ed) 
		{
			var xen_spoiler = 'xen_spoiler',
				insertSpoiler = "Insert spoiler",
				spoilerConfig,
				spoilerModal;

			spoilerModal = function(e){
				ed.windowManager.open({			
					title: insertSpoiler,
					body: [
						{name: 'title', type: 'textbox', size: 40, label: "Spoiler Title"}
					],
					onSubmit: function(e) {
						var tagOptions = e.data.title,
							content = ed.selection.getContent();
							
						parent.insertBbCode('spoiler', tagOptions, content);			
					}
				});
			};
			
			spoilerConfig = {
				name: xen_spoiler,
				icon: xen_spoiler,
				iconset: 'xenforo',
				tooltip: insertSpoiler,
				onclick: spoilerModal	
			}
			
			ed.addButton(xen_spoiler, spoilerConfig);
			ed.addMenuItem(xen_spoiler, $.extend({},
				spoilerConfig, {
					text: "Spoiler",
					tooltip: false,
					context: 'insert'
				})
			);		
		}
	});

	/***
	*	XenForo Full editor Toggler
	*	Independent plugin
	***/
	tinymce.create(xenPlugin+'.XenToggleMenu', {
		XenToggleMenu: function(parent, ed) 
		{
      			var self = this,
      				toggleMenu = 'togglemenu',
      				showText = "Show full editor",
      				toggleMenuConfig;

			ed.on('init', function(e) {
				if(parent.getParam('tglMenuCollasped') == true && ed.getParam('menubar')){
					var tb = parent.getToolbars();
					tb.hide();
      					cssFix();
				}
			});

      			var cssFix = function(){
      				var $menubar = parent.getMenubar(),
	      				tb = parent.getToolbars();

     				if(tb.visible()){
					 $menubar.css('borderWidth', '0 0 1px'); //To do: improve this
      				}else{
					 $menubar.css('borderWidth', 0);
      				}	      			
      			}
      			
      			var toggleAction = function(e){
      				var self = this, tb = parent.getToolbars();
      					
      				if(tb.visible()){
      					tb.hide();
      				}else{
      					tb.show();
      				}

				self.active(tb.visible());
				cssFix();
      			};

			var xenFulleditor = function(display){
				var tb = parent.getToolbars();
				
				if(display || display === undefined){
					tb.show();
				}else{
					tb.hide();
				}
				cssFix();
			}

      			toggleMenuConfig = {
      				name: toggleMenu,
      				selectable: true,
      				onClick: toggleAction,
      				text: showText,
      				context: 'view',
      				onPostRender: function(e){
      					var self = this;

      					function direct(){
      						var tb = parent.getToolbars();
      						self.disabled((tb.length == 0));
	      					self.active(tb.visible());
	      				}
	      				
	      				direct();
      					
					self.parent().on('show', function() {
						if(parent.isFullscreen()){
							self.disabled(true)
						}else{
							direct();
						}
					});
      				}
      			};

			ed.addMenuItem(toggleMenu, toggleMenuConfig);
			ed.addCommand('xenFullEditor', xenFulleditor);
			
			ed.on('FullscreenStateChanged', function(e){
				ed.execCommand('xenFullEditor', true);//e.state				
			});
		}
	});
	
	/***
	*	XenForo Icons
	*	Independent plugin - Must be at the last of this file (reason - bug:#6543)
	***/
	tinymce.create(xenPlugin+'.XenIcons', {
		XenIcons: function(parent, ed) 
		{
			var each = tinymce.each, xenforo = 'xenforo';

			//2013-12-05: still needs to use BeforeRenderUI here
			ed.on('BeforeRenderUI', function(e) {
				/*Delete items from Context menu if the button is not there*/
				var deleteContextMenuItem = function(item){
					if(ed.menuItems[item] !== undefined)
						delete ed.menuItems[item];
				}

				if(!parent.isActiveButton('xen_link')){
					deleteContextMenuItem('xen_link');
				}

				if(!parent.isActiveButton('table')){
					var menuToDelete = ['inserttable', 'xen_tableskin', 'cell', 'row', 'column', 'deletetable'];
					each(menuToDelete, function(v){
						deleteContextMenuItem(v);
					});
				}
			});

			/* Auto translate tooltips based on suffix _desc*/
      			each(ed.buttons, function(v, k){
      				var key_desc = k+'_desc';

      				if(!XenForo.isTouchBrowser()){
      					var autoTranslate = parent.getPhrase(key_desc);
      					if(autoTranslate.length){
      						ed.buttons[k].tooltip = autoTranslate;
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
				/**
				 * Add XenForo Class to Icons with XenForo xenset
				 **/
			
					//Add to icons from icons bar
					var $buttons = parent.getButtonsByProperty('iconset', xenforo, true);
					parent.addIconClass($buttons, xenforo);

					//Add to icons from menu bar
					var menus = ed.theme.panel.find('menubar menubutton');
					each(menus, function(v, k){
						v.on('show', function(e){
							var menuItems = e.control.find('menuitem');
							each(menuItems, function(menuItem){
								if(menuItem.settings != undefined && menuItem.settings.iconset == xenforo){
									parent.addIconClass($(menuItem.getEl()), xenforo);
								}
							});
							//Add position fix here
							var parentEl = e.control.parent();
							if(parentEl !== undefined && parentEl.type == 'menubutton'){
								parent.fixBtnFullscreen(v, false, e.control);
							}
						});
					});
					
				/**
				 * Hide status bar
				 **/
				var statBar = ed.theme.panel.find('#statusbar');

				if(statBar && statBar.length > 0)
					$statBar = $(statBar[0].getEl());

				if(parent.getParam('hidePath') && statBar.length > 0)
					$statBar.find('.mce-path').css('visibility', 'hidden');
			});
		}
	});	
})(jQuery, this, document);