/** @param {jQuery} $ jQuery Object */
!function($, window, document, _undefined)
{
	if(typeof xenMCE === 'undefined') xenMCE = {};

	xenMCE.BbCodeWysiwygEditor = function($editor) { 
		var self = this;
		//setTimeout is needed to wait the template to be totally loaded and get access to xenMCE
		setTimeout(function(){self.__construct($editor)}, 0);
	};

	/*Let's detect if MCE is loaded as soon as possible*/
	if (typeof tinymce === 'undefined'){
		xenMCE.lazyLoad = true;
	}

	xenMCE.BbCodeWysiwygEditor.prototype =
	{
		__construct: function($editor)
		{
			var 	editorId = $editor.attr('id'),
				params = xenMCE.Params,
				config = xenMCE.defaultConfig,
				hasDraft = ($editor.data('auto-save-url')),
				wordcount = params.xenwordcount,
				draftOpt = params.xendraft,
				baseUrl = XenForo._baseUrl,
				loader;			
		
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
			
			if(draftOpt && hasDraft){
				config.plugins.push('autosave');
			}

			if (xenMCE.lazyLoad) {
				console.info('MCE Lazyloader');
				
				//The jQuery Mce Lazy Loader will not start if the script if found, let's trick him
				if(typeof tinymce !== 'undefined'){
					delete window['tinymce']
				}
				
				xenMCE.lazyLoad = true;
				loader = 'jquery';
				config.script_url = baseUrl+'js/sedo/tinyquattro/tinymce/tinymce.min.js';
			}else{
				loader = 'mce';
				config.selector = '#'+editorId;
			}

			$editor.data('editorActivated', true).addClass('mceQuattro').show();
	
			if(loader == 'jquery'){
				$editor.tinymce(config);		
			}else{
				var ed = new tinymce.Editor(editorId, config, tinymce.EditorManager);
				ed.render();
			}
			
			console.info('XenForo editor %s, %o', editorId, $editor);
		
			setTimeout(function(){
				if(!$editor.prev().hasClass('mce-tinymce')){
					console.info('MCE failed');
					$editor.show().after($('<input type="hidden" name="_xfRteFailed" value="1" />'));
				}
			}, 3000);	
		}
	};
	
	XenForo.register('textarea.BbCodeWysiwygEditor', 'xenMCE.BbCodeWysiwygEditor');
}
(jQuery, this, document);