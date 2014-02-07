!function($, window, document, _undefined)
{

	/**************************************************************************
		OVERRIDE THE XENFORO FUNCTION "QuickReply" - ONLY NEEDED PARTS
	**************************************************************************/
	var xenQuickReply = XenForo.QuickReply;

	XenForo.QuickReply = function($form)
	{
		var editor = XenForo.getEditorInForm($form);
		if(editor.$editor){
			return xenQuickReply($form);
		}

		xenQuickReply($form); //Parent
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
		$trigger.click(function(e)
		{
			var $form = null,
				xhr = null,
				queryData = null;

			if ($trigger.is('.MultiQuote'))
			{
				$form = $($trigger.data('form'));

				queryData =
				{
					postIds: $($trigger.data('inputs')).map(function()
					{
						return this.value;
					}).get()
				};
			}
			else
			{
				$form = $('#QuickReply');
				$form.data('QuickReply').scrollAndFocus();
			}

			if (!xhr)
			{
				xhr = XenForo.ajax
				(
					$trigger.data('posturl') || $trigger.attr('href'),
					queryData,
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

						if(ed.execCommand && !ed.$editor){
							ed.execCommand('insertHtml', false, ajaxData.quoteHtml);
						}else if(ed.$editor){
							ed.insertHtml(ajaxData.quoteHtml);
							if (ed.$editor.data('xenForoElastic'))
							{
								ed.$editor.data('xenForoElastic')();
							}						
						}else{
							ed.val(ed.val() + ajaxData.quote);
						}
						
						if ($trigger.is('.MultiQuote'))
						{
							// reset cookie and checkboxes
							$form.trigger('MultiQuoteComplete');
						}
					}
				);
			}

			return false;
		});
	};
}
(jQuery, this, document);