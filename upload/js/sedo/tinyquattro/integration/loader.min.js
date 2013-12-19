/** @param {jQuery} $ jQuery Object */
!function($, window, document, undefined)
{
	if(typeof xenMCE === undefined) xenMCE = {};

	xenMCE.BbCodeWysiwygEditor = function($editor) { 
		var self = this;
		//setTimeout is needed to wait the template to be totally loaded and get access to xenMCE
		setTimeout(function(){self.__construct($editor)}, 0);
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
		__construct: function($editor)
		{
			//Let's mark the editor as failed by default, let's take back this after it has been loaded
			$editor.show().after($('<input type="hidden" name="_xfRteFailed" value="1" />'));
			
			var editorId = $editor.attr('id'),
				params = xenMCE.Params,
				config = xenMCE.defaultConfig,
				hasDraft = ($editor.data('auto-save-url')),
				wordcount = params.xenwordcount,
				draftOpt = params.xendraft,
				baseUrl = XenForo._baseUrl,
				loader;			
		
			$editor.each(function(){
				var $form = $(this).closest('form');
				if($form.attr('id') == 'QuickReply'){
					var amih = 'autoresize_min_height', amah = 'autoresize_max_height', qr = '_qr';
					config[amih] = config[amih+qr];
					config[amah] = config[amah+qr];
				}			
			});
		
			if(wordcount != 'no'){
				config.plugins.push('wordcount');
	
				if (wordcount == 'char'){
					config.wordcount_countregex = /\S/g;
					config.wordcount_cleanregex = '';
				}
				else if (wordcount == 'charwp'){			
					config.wordcount_countregex = /(\S|\b[\u0020]\b)/g;
					config.wordcount_cleanregex = /\n/g;
				}
			}

			if(!draftOpt || !hasDraft){
				config.plugins.push('autosave');
			}

			if (xenMCE.lazyLoad) {
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