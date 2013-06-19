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
	XenForo.getEditorInForm = function(form)
	{
		var $form = $(form),
			$textarea = $form.find('textarea.MessageEditor');

		if ($textarea.length)
		{
			if ($textarea.prop('disabled'))
			{
				return $form.find('.bbCodeEditorContainer textarea');
			}
			else if (window.tinyMCE && $textarea.attr('id') && tinyMCE.editors[$textarea.attr('id')])
			{
				return tinyMCE.editors[$textarea.attr('id')];
			}
			else
			{
				return $textarea;
			}
		}
		return false;
	};

	/**************************************************************************
			OVERRIDE THE XENFORO FUNCTION "AttachmentInserter"
	**************************************************************************/
	var insertSpeed = XenForo.speed.normal,	removeSpeed = XenForo.speed.fast;
	
	XenForo.AttachmentInserter = function($trigger)
	{
		$trigger.click(function(e)
		{
			var $attachment = $trigger.closest('.AttachedFile').find('.Thumbnail a'),
				attachmentId = $attachment.data('attachmentid'),
			 	editor,
				bbcode,
				html,
				thumb = $attachment.find('img').attr('src'),
				img = $attachment.attr('href');

			e.preventDefault();

			if ($trigger.attr('name') == 'thumb')
			{
				bbcode = '[ATTACH]' + attachmentId + '[/ATTACH] ';
				html = '<img src="' + thumb + '" class="attachThumb bbCodeImage" alt="attachThumb' + attachmentId + '" /> ';
			}
			else
			{
				bbcode = '[ATTACH=full]' + attachmentId + '[/ATTACH] ';
				html = '<img src="' + img + '" class="attachFull bbCodeImage" alt="attachFull' + attachmentId + '" /> ';
			}

			var editor = XenForo.getEditorInForm($trigger.closest('form'));
			if (editor)
			{
				if (editor.execCommand)
				{
					editor.execCommand('mceInsertContent', false, html);
				}
				else
				{
					editor.val(editor.val() + bbcode);
				}
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
					var editor = XenForo.getEditorInForm($trigger.closest('form'));
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