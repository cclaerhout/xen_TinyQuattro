!function($, window, document, _undefined)
{

	/**************************************************************************
		OVERRIDE THE XENFORO FUNCTION "QuickReply" - ONLY NEEDED PARTS
	**************************************************************************/
	_XenForoQuickReply = XenForo.QuickReply;

	XenForo.QuickReply = function($form)
	{
		_XenForoQuickReply($form); //Parent
		var submitEnableCallback = XenForo.MultiSubmitFix($form); //Needed variable
		
		/*Override scrollAndFocus function*/
		this.scrollAndFocus = function()
		{
			$(document).scrollTop($form.offset().top);

			if (window.tinyMCE)
			{
				window.tinyMCE.editors['ctrl_message_html'].focus();
			}
			else
			{
				$('#QuickReply').find('textarea:first').get(0).focus();
			}

			return this;
		};

		/*Override AutoValidationComplete event*/
		$form.data('QuickReply', this).unbind('AutoValidationComplete').bind(
		{
			AutoValidationComplete: function(e)
			{
				if (e.ajaxData._redirectTarget)
				{
					window.location = e.ajaxData._redirectTarget;
				}
	
				$('input[name="last_date"]', $form).val(e.ajaxData.lastDate);
	
				if (submitEnableCallback)
				{
					submitEnableCallback();
				}
	
				$form.find('input:submit').blur();

				if($form.hasClass('QuickReplyLive')){
					//For sonnb - Live Thread 1/2
					$('input[name="last_position"]', $form).val(e.ajaxData.lastPosition);
					$('form.InlineModForm').data("timestamp", e.ajaxData.lastDate);

					if (e.ajaxData.posts && e.ajaxData.posts.length){
						for (i = 0; i < e.ajaxData.posts.length; i++){
							$(e.ajaxData.posts[i]).xfInsert('appendTo', $('ol#messageList'), 'xfSlideDown');
						}
					}
				}else{
					new XenForo.ExtLoader(e.ajaxData, function()
					{
						$('#messageList').find('.messagesSinceReplyingNotice').remove();
		
						$(e.ajaxData.templateHtml).each(function()
						{
							if (this.tagName)
							{
								$(this).xfInsert('appendTo', $('#messageList'));
							}
						});
					});
				}
				
				$('#QuickReply').find('textarea').val('');
				if (window.tinyMCE)
				{
					window.tinyMCE.editors['ctrl_message_html'].setContent('');
				}
	
				if (window.sessionStorage)
				{
					window.sessionStorage.quickReplyText = null;
				}
	
				$form.trigger('QuickReplyComplete');

				if($form.hasClass('QuickReplyLive')){
					//For sonnb - Live Thread 2/2
					$('.AttachmentEditor').find('.AttachmentList.New li:not(#AttachedFileTemplate)').xfRemove();
					$form.data('isReplying', 0);
				}

				return false;				
			}
		});
	};

	/**************************************************************************
		OVERRIDE THE XENFORO FUNCTION "QuickReplyTrigger"
	**************************************************************************/
	XenForo.QuickReplyTrigger = function($trigger)
	{
		if ($trigger.is('.MultiQuote'))
		{
			// not yet implemented
			return false;
		}

		$trigger.click(function(e)
		{
			var $form = $('#QuickReply'),
				xhr = null;

			$form.data('QuickReply').scrollAndFocus();

			if (!xhr)
			{
				xhr = XenForo.ajax
				(
					$trigger.data('posturl') || $trigger.attr('href'),
					'',
					function(ajaxData, textStatus)
					{
						if (XenForo.hasResponseError(ajaxData))
						{
							return false;
						}

						delete(xhr);

						var ed = XenForo.getEditorInForm($form);
						if (!ed)
						{
							return false;
						}

						if (ed.execCommand)
						{
							if (tinyMCE.isIE)
							{
								ed.execCommand('mceInsertContent', false, ajaxData.quoteHtml);
							}
							else
							{
								ed.execCommand('insertHtml', false, ajaxData.quoteHtml);
							}

							if (window.sessionStorage)
							{
								window.sessionStorage.quickReplyText = ajaxData.quoteHtml;
							}
						}
						else
						{
							ed.val(ed.val() + ajaxData.quote);

							if (window.sessionStorage)
							{
								window.sessionStorage.quickReplyText = ajaxData.quote;
							}
						}
					}
				);
			}

			return false;
		});
	};
}
(jQuery, this, document);