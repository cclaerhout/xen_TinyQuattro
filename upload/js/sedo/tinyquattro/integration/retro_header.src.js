!function($, window, document, _undefined)
{
	/**************************************************************************
			OVERRIDE THE JQUERY FUNCTIONS TO MATCH WITH TINYMCE
	**************************************************************************/
	$.fn.extend(
	{
		_quattro_jqSerialize : $.fn.serialize,
		serialize: function()
		{
			if (window.tinyMCE)
			{
				try { window.tinyMCE.triggerSave(); } catch (e) {}
			}

			return this._quattro_jqSerialize();
		},

		_quattro_jqSerializeArray : $.fn.serializeArray,
		serializeArray: function()
		{
			if (window.tinyMCE)
			{
				try { window.tinyMCE.triggerSave(); } catch (e) {}
			}

			return this._quattro_jqSerializeArray();
		}
	});

	/**************************************************************************
			OVERRIDE THE XENFORO FUNCTION "getEditorInForm"
	**************************************************************************/
	XenForo.getEditorInForm = function(form, extraConstraints, $lastFocusedElement, enableExtraConstraints)
	{
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
		$form = $(form);
		$messageEditors = $form.find('textarea.MessageEditor' + (extraConstraints || ''));
		$bbCodeEditors = $form.find('.bbCodeEditorContainer textarea' + (extraConstraints || ''));
		$allEditors = $messageEditors.add($bbCodeEditors);
		
		/*There must be at least one message editor in the form*/
		if($messageEditors.length == 0){
			return false;
		}

		/*The first message editor will be the fallback*/
		$messageEditor = $messageEditors.eq(0);
		$bbCodesBbCodeEditor = $bbCodeEditors.eq(0);

		/**
		 * Let's find if one of the message editors or bbCode editors 
		 * belongs to the $lastFocusedElement
		 **/
		var safeCheck = $lastFocusedElement.get(0);

		if($lastFocusedElement && typeof safeCheck.id !== 'undefined'){
			var validFocus = false;

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

		/**
		 * MCE Section
		 **/		
		if(window.tinyMCE){
			var 	activeEditor = tinyMCE.activeEditor,
				$mceTextarea = $(activeEditor.getElement()),
				mceTextareaId = $mceTextarea.attr('id'),
				mceIsValid = false;
				
				$messageEditors.each(function(){
					if($(this).attr('id') == mceTextareaId){
						mceIsValid = true;
						return;
					}
				});

			if(activeEditor && mceIsValid && !$mceTextarea.attr('disabled')){
				//console.log('MCE active');
				return tinyMCE.activeEditor;
			}else{
				/*With old browser it could be possible that the activeEditor is lost (not sure)*/
				var messageEditorId = $messageEditor.attr('id');
				if(	messageEditorId 
					&& typeof tinyMCE.editors[messageEditorId] !== 'undefined'
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
	var insertSpeed = XenForo.speed.normal,	removeSpeed = XenForo.speed.fast;
	
	XenForo.AttachmentInserter = function($trigger)
	{
		var $lastFocusedElement = false;
		
		$trigger.hover(function(e){
			$lastFocusedElement = $(document.activeElement);
		});

		$trigger.click(function(e)
		{
			var $attachment = $trigger.closest('.AttachedFile').find('.Thumbnail a'),
				attachmentId = $attachment.data('attachmentid'),
			 	editor,
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

			//Don't specify extraConstraints, it will mess the detection method, check the .NoAttachment after
			editor = XenForo.getEditorInForm($trigger.closest('form'), '', $lastFocusedElement);

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
	XenForo.AttachmentDeleter = function($trigger)
	{
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
					var editor = XenForo.getEditorInForm($trigger.closest('form'), ':not(.NoAttachment)', false, true);
					if (typeof editor.getBody() !== 'undefined')
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
(jQuery, this, document);