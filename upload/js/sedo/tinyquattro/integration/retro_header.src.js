!function($, window, document, undefined)
{
	/**************************************************************************
   	   Extend XenForo.scriptLoader.loadScript for  XenForo overlays & TinyMCE
	**************************************************************************/
	var xenLoadScript = XenForo.scriptLoader.loadScript;
	XenForo.scriptLoader.loadScript = function(url, success, failure){
		var mceUrl = 'js/sedo/tinyquattro/tinymce',
			mceRegex = new RegExp(mceUrl+'/tinymce(\.min)?\.js');
		if (mceRegex.test(url)){
			window.tinyMCEPreInit = {
				baseURL: XenForo.baseUrl()+mceUrl,
				suffix: /\.min\.js/.test(url) ? '.min' : ''
			}
		}
		xenLoadScript(url, success, failure);
	}

	/**************************************************************************
			Extend THE JQUERY FUNCTIONS TO MATCH WITH TINYMCE
	**************************************************************************/
	$.fn.extend(
	{
		_quattro_jqSerialize : $.fn.serialize,
		serialize: function()
		{
			if (window.tinyMCE)
			{
				try { 
					var mce = window.tinyMCE, ev = 'xenMceBeforeSubmit', args = {};

					mce.each(mce.editors, function(editor){
						editor.fire(ev, args);
					});

					mce.triggerSave();
				} catch (e) {}
			}

			return this._quattro_jqSerialize();
		},

		_quattro_jqSerializeArray : $.fn.serializeArray,
		serializeArray: function()
		{
			if (window.tinyMCE)
			{
				try { 
					var mce = window.tinyMCE, ev = 'xenMceBeforeSubmit', args = {};

					mce.each(mce.editors, function(editor){
						editor.fire(ev, args);
					});

					mce.triggerSave();
				} catch (e) {}
			}

			return this._quattro_jqSerializeArray();
		}
	});

	/**************************************************************************
			OVERRIDE THE XENFORO FUNCTION "getEditorInForm"
	**************************************************************************/
	var xenGetEditorInForm = XenForo.getEditorInForm;
	XenForo.getEditorInForm = function(form, extraConstraints, $lastFocusedElement, enableExtraConstraints)
	{
		/**
		 *  If the original function returns a Redactor editor, don't go further
		 **/
		var originalReturn = xenGetEditorInForm(form, extraConstraints);

		if(originalReturn && originalReturn.$editor){
			return originalReturn;
		}

		/**
		 *  To be able to use  $lastFocusedElement & keep compatibility with XenForo it should 
		 *  be in thrid position. Since extraContraints might mess the below detection method,
		 *  they will be disable by default and can only be enable with a fourth argurment
		 **/
		if(!enableExtraConstraints){
			extraConstraints = '';
		}

		/**
		 *  MessageEditor is used for original loaded ediors (exit Toggled editors)
		 *  .bbCodeEditorContainer textarea is to catch Toggled BbCode editors
		 **/
		var $form = $(form),
			$messageEditors = $form.find('textarea.MessageEditor' + (extraConstraints || '')),
			$bbCodeEditors = $form.find('.bbCodeEditorContainer textarea' + (extraConstraints || '')),
			$allEditors = $messageEditors.add($bbCodeEditors);
		
		/*There must be at least one message editor in the form*/
		if($messageEditors.length == 0){
			return false;
		}

		/*The first message editor will be the fallback*/
		var $messageEditor = $messageEditors.eq(0),
			$bbCodesBbCodeEditor = $bbCodeEditors.eq(0);

		/**
		 * Let's find if one of the message editors or bbCode editors 
		 * belongs to the $lastFocusedElement
		 **/
		if($lastFocusedElement && typeof $lastFocusedElement !== undefined){
			var safeCheck = $lastFocusedElement.get(0), validFocus = false;
			if(typeof safeCheck.id !== undefined)
			{
				$allEditors.each(function(){
					if($(this).attr('id') == safeCheck.id){
						validFocus = $(this);
						return;
					}
				});
	
				if(validFocus) {
					/**
					 *	The lastFocusedElement is valid
					 *	Let's focus it again if users need to insert several attachments
					 *	Then let's return it
					 **/
					validFocus.focus();
					//console.log('Editor from focus');
					return validFocus;
				}
			}
		}

		/**
		 * MCE Section
		 **/		
		if(window.tinyMCE){
			var activeEditor = tinyMCE.activeEditor;
			
			if(activeEditor){
				var $mceTextarea = $(activeEditor.getElement()),
					mceTextareaId = $mceTextarea.attr('id'),
					mceIsValid = false;
				
				$messageEditors.each(function(){
					if($(this).attr('id') == mceTextareaId){
						mceIsValid = true;
						return;
					}
				});
			}

			if(activeEditor && mceIsValid && !$mceTextarea.attr('disabled')){
				//console.log('MCE active');
				return tinyMCE.activeEditor;
			}else{
				/*With old browser it could be possible that the activeEditor is lost (not sure)*/
				var messageEditorId = $messageEditor.attr('id');
				if(	messageEditorId 
					&& typeof tinyMCE.editors[messageEditorId] !== undefined
					&& !$messageEditor.attr('disabled')
				)
				{
					//console.log('MCE Fallback');
					return tinyMCE.editors[messageEditorId];
				}
			}
		}
		
		if($messageEditor.attr('disabled')){
			/*.attr is used to maintain a compatibily with XenForo 1.1.x */
			if(!$bbCodesBbCodeEditor.length){
				return false;
			}
			
			//console.log('Bbcode Fallback');
			return $bbCodesBbCodeEditor;
		}

		//console.log('Editor Fallback');		
		return $messageEditor;
	};

	/**************************************************************************
			OVERRIDE THE XENFORO FUNCTION "AttachmentInserter"
	**************************************************************************/
	var xenAttachInserter = XenForo.AttachmentInserter,
		insertSpeed = XenForo.speed.normal,
		removeSpeed = XenForo.speed.fast;
	
	XenForo.AttachmentInserter = function($trigger)
	{
		var $lastFocusedElement = false;
		
		$trigger.hover(function(e){
			$lastFocusedElement = $(document.activeElement);
		});

		var editor = XenForo.getEditorInForm($trigger.closest('form'), '', $lastFocusedElement);
		if(editor.$editor){
			xenAttachInserter($trigger);
			return false;
		}

		$trigger.click(function(e)
		{
			//Don't specify extraConstraints, it will mess the detection method, check the .NoAttachment after
			var editor = XenForo.getEditorInForm($trigger.closest('form'), '', $lastFocusedElement);

			var $attachment = $trigger.closest('.AttachedFile').find('.Thumbnail a'),
				attachmentId = $attachment.data('attachmentid'),
				bbcode,
				imgBbcode,
				html,
				baseUrl = XenForo._baseUrl,
				thumb = $attachment.find('img').attr('src'),
				img = $attachment.attr('href');

			e.preventDefault();

			if ($trigger.attr('name') == 'thumb')
			{
				bbcode = '[ATTACH]' + attachmentId + '[/ATTACH] ';
				imgBbcode = '[img]'+baseUrl+thumb+'[/img]';
				html = '<img src="' + thumb + '" class="attachThumb bbCodeImage" alt="attachThumb' + attachmentId + '" /> ';
			}
			else
			{
				bbcode = '[ATTACH=full]' + attachmentId + '[/ATTACH] ';
				imgBbcode = '[img]'+baseUrl+img+'[/img]';
				html = '<img src="' + img + '" class="attachFull bbCodeImage" alt="attachFull' + attachmentId + '" /> ';
			}

			function activateImgFallback($textarea, output){
				if($textarea.hasClass('NoAttachment') && $textarea.hasClass('ImgFallback')){
					return imgBbcode;
				}
				return output;
			}

			if (editor.execCommand && window.tinyMCE) {
				$textarea = $(editor.getElement());
				html = activateImgFallback($textarea, html)
				editor.execCommand('mceInsertContent', false, html);
			}else{
				bbcode = activateImgFallback(editor, bbcode)
				editor.val(editor.val() + bbcode);
			}
		});
	};

	/**************************************************************************
			OVERRIDE THE XENFORO FUNCTION "AttachmentDeleter"
	**************************************************************************/
	var xenAttachmentDeleter = XenForo.AttachmentDeleter;
	XenForo.AttachmentDeleter = function($trigger)
	{
		var editor = XenForo.getEditorInForm($trigger.closest('form'), ':not(.NoAttachment)', false, true);

		if(editor.$editor){
			return xenAttachmentDeleter($trigger);
		}

		$trigger.css('display', 'block').click(function(e)
		{
			var $trigger = $(e.target),
				href = $trigger.attr('href') || $trigger.data('href'),
				$attachment = $trigger.closest('.AttachedFile'),
				$thumb = $trigger.closest('.AttachedFile').find('.Thumbnail a'),
				attachmentId = $thumb.data('attachmentid');

			if (href)
			{
				$attachment.xfFadeUp(XenForo.speed.normal, null, removeSpeed, 'swing');

				XenForo.ajax(href, '', function(ajaxData, textStatus)
				{
					if (XenForo.hasResponseError(ajaxData))
					{
						$attachment.xfFadeDown(XenForo.speed.normal);
						return false;
					}

					var $editor = $attachment.closest('.AttachmentEditor');

					$attachment.xfRemove(null, function() {
						$editor.trigger('AttachmentsChanged');
					}, removeSpeed, 'swing');
				});

				if (attachmentId)
				{
					if (typeof editor.getBody() !== undefined)
					{
						$(editor.getBody()).find('img[alt=attachFull' + attachmentId + '], img[alt=attachThumb' + attachmentId + ']').remove(); //TODO
					}
				}

				return false;
			}

			console.warn('Unable to locate href for attachment deletion from %o', $trigger);
		});
	};
}
(jQuery, this, document, 'undefined');