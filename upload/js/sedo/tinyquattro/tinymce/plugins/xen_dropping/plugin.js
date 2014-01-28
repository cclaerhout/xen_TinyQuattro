/*The below code is based on XenForo Code and can only be used with it and with a valid license*/
tinymce.create('tinymce.plugins.xen_dropping',
{
	init: function(editor)
	{
		this.editor = editor;
		var self = this;
		
		editor.on('init', function(e){
			self.$textarea = $(editor.getElement());
			self.$container = $(editor.getContainer());
			self.initDragDrop();
		});
	},
	initDragDrop: function()
	{
		if (this.isMobile() || this.$textarea.hasClass('NoAttachment'))
			return;

		$droparea = $('<div class="quattro_editor_drop" />');

		var self = this,
			dragOverTimeout = function() { $droparea.removeClass('hover'); },
			$uploader = this.$container.closest('form').find('.AttachmentUploader'),
			canUpload = ($uploader.length > 0),
			timer,
			i18n = tinymce.util.I18n;

		$droparea.append(
			$('<span />').text((canUpload ? i18n.translate('drop_files_here_to_upload') : i18n.translate('uploads_are_not_available')))
		).appendTo(self.$container);
		
		if (!canUpload)
			$droparea.addClass('dragDisabled');

		var checkDraggable = function(e)
		{
			var dt = e.originalEvent.dataTransfer;
			
			if (!dt || typeof dt.files == 'undefined')
				return false;

			if (dt.types && ($.inArray('Files', dt.types) == -1 || dt.types[0] == 'text/x-moz-url'))
				return false;

			return true;
		};

		$([document, this.editor.dom.doc]).on('dragover', function(e) {
			if (!checkDraggable(e))
				return;

			$droparea.addClass('hover');

			clearTimeout(timer);
			timer = setTimeout(dragOverTimeout, 200);
		});
		
		$droparea.on('dragover', function(e) {
			if (!checkDraggable(e))
				return;

			e.preventDefault();
			
		}).on('drop', function(e){
			e.preventDefault();
			clearTimeout(timer);
			dragOverTimeout();

			if (!canUpload)
				return;

			var dt = e.originalEvent.dataTransfer;

			if (dt && dt.files && dt.files.length)
			{
				for (var i = 0; i < dt.files.length; i++)
				{
					try {
						var form = new FormData();
						form.append('upload', dt.files[i]);
						form.append('_xfToken', XenForo._csrfToken);
						form.append('_xfNoRedirect', '1');
						$uploader.find('.HiddenInput').each(function() {
							var $input = $(this);
							form.append($input.data('name'), $input.data('value'));
						});
					} catch (e) {
						return;
					}

					// need to use the direct jQuery interface here
					$.ajax({
						url: $uploader.data('action'),
						method: 'POST',
						dataType: 'json',
						data: form,
						processData: false,
						contentType: false
					}).done(function(ajaxData) {
						if (!XenForo.hasResponseError(ajaxData))
						{
							$uploader.trigger({
								type: 'AttachmentUploaded',
								ajaxData: ajaxData
							});
						}
					}).fail(function(xhr) {
						try
						{
							var ajaxData = $.parseJSON(xhr.responseText);
							if (ajaxData && XenForo.hasResponseError(ajaxData))
							{
							}
						}
						catch (e) {}
					});
				}
			}
		});
	},
	isMobile: function()
	{
		/*Really basic detection*/
		if (/(iPhone|iPod|iPad|BlackBerry|Android)/.test(navigator.userAgent)){
			return true;
		}
		
		return false;
	}
});

tinymce.PluginManager.add('xen_dropping', tinymce.plugins.xen_dropping);