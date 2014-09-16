/** @param {jQuery} $ jQuery Object */
!function($, window, document, undefined)
{
	if(typeof xenMCE === undefined) xenMCE = {};

	xenMCE.BbCodeWysiwygEditor = function($editor, setupCallback) { 
		var self = this;
		//setTimeout is needed to wait the template to be totally loaded and get access to xenMCE
		setTimeout(function(){self.__construct($editor, setupCallback)}, 0);
	};

	/**
	 *  Let's detect if MCE is loaded on dom ready
	 *  Edit: let's also check if there's no RTE editor
	 * 	 Reason: in some very unlikely occasions (two pages open using the lazyloader)
	 *	 tinymce will be considered in the window... 
	 **/
	$(function() {
		if (!window.tinymce || $('textarea.BbCodeWysiwygEditor').length == 0){
			xenMCE.lazyLoad = 1;
		}
	});

	xenMCE.BbCodeWysiwygEditor.prototype =
	{
		__construct: function($editor, setupCallback)
		{
			var quattroData = $editor.data('quattro'),
				redactorData = $editor.data('redactor'),
				editorId = $editor.attr('id');
			
			if(!quattroData){
				return false;
			}
			
			if(redactorData){
				var edVal = $editor.val();
				$.extend(redactorData, { $editor: $editor });
				redactorData.destroy();
				$editor.val(edVal);
			}
			
			//Let's mark the editor as failed by default, let's take back this after it has been loaded
			$editor.show().after($('<input type="hidden" name="_xfRteFailed" value="1" />'));

			var params = quattroData.params,
				config = quattroData.settings,
				regexEl = params.mceRegexEl,
				hasDraft = ($editor.data('auto-save-url')),
				draftOpt = params.xendraft,
				enableLazyLoad = params.lazyLoader,
				baseUrl = XenForo.baseUrl(),
				loader;
			
			config.xenParams = params; 

			//Transform mce regex to proper format
			if(regexEl != undefined){
				$.each(regexEl, function(i, v){
					if(config[v] !== undefined && $.isArray(config[v])){
						config[v] = new RegExp(config[v][0], config[v][1]);
					}
				});
			}

			//No proper way to check if the form is the quickreply from templates
			$editor.each(function(){
				var $this = $(this), $form = $this.closest('form');
				if($form.attr('id') == 'QuickReply'){
					var amih = 'autoresize_min_height', amah = 'autoresize_max_height', qr = '_qr';
					config[amih] = config[amih+qr];
					config[amah] = config[amah+qr];
				}			
			});
		
			if(!draftOpt || !hasDraft){
				config.plugins.push('autosave');
			}

			if (xenMCE.lazyLoad) {
				if(enableLazyLoad || typeof window.tinyMCEPreInit === 'undefined'){
					config.script_url = baseUrl+'js/sedo/tinyquattro/tinymce/tinymce.min.js';
					console.info('MCE Lazyloader - Url: ', config.script_url);
					
					/**
					 *  The jQuery Mce Lazy Loader will not start if the script if found, let's trick him
					 *  Edit: but let's trick him only once... otherwise if a second overlay is loaded, it
					 *  will fail
					 **/
					if(typeof tinymce !== undefined && xenMCE.lazyLoad == 1){
						delete window['tinymce']
					}
	
					xenMCE.lazyLoad ++;
					loader = 'jquery';
				}else{
					//Remove the second parameter after a while
					console.info('MCE Preinit Mode', window.tinyMCEPreInit);
				}
			}else{
				loader = 'mce';
				config.selector = '#'+editorId;
			}

			/**
			 *  Hook for setup config (The Javascript files will be executed after MCE is loaded)
			 *  This hook can be extented with xenMCE.onSetup. Just create it before to call this 
			 *  Javascript and push a function like below.
			 **/
			if(typeof xenMCE.onSetup === undefined) xenMCE.onSetup = [];
			
			var tagEditor = function tagEditor(ed){
				ed.on('init', function(e) {
					//Tag the textarea
					$textarea = $(ed.getElement())
							.data('editorActivated', true)
							.addClass('mceQuattro')
							.hide(); //Should not be needed
					
					//Remove the XenForo Failure detection
					$textarea.siblings('input[name="_xfRteFailed"]').remove();
					
					//Display the editor
					ed.show(); //Should not be needed
				});
			};
			
			xenMCE.onSetup.push(tagEditor);

			config.setup = function(ed) {
				$.each(xenMCE.onSetup, function(){
					//Load all functions
					this(ed);
				});
				
				if(typeof setupCallback === 'function'){
					setupCallback(ed);
				}
			};

			//Loader selection
			if(loader == 'jquery'){
				$editor.tinymce(config);		
			}else{
				var ed = new tinymce.Editor(editorId, config, tinymce.EditorManager);
				ed.render();
			}
			
			console.info('XenForo editor %s, %o', editorId, $editor);
		}
	};
	
	XenForo.register('textarea.BbCodeWysiwygEditor', 'xenMCE.BbCodeWysiwygEditor');
}
(jQuery, this, document, 'undefined');