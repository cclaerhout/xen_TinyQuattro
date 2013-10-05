/*The below code is based on XenForo Code and can only be used with it and with a valid license*/
tinymce.create('tinymce.plugins.xen_paste_img',
{
	init: function(editor)
	{
		this.editor = editor;
		var self = this;
		
		editor.on('PastePreProcess', function(e){
			self.$textarea = $(editor.getElement());
			self.$container = $(editor.getContainer());
			self.$editor = $(editor.getBody());
			
			var 	tag = $(e.content).prop('tagName');
				if(!tag || tag.toLowerCase() != 'img')
					return;

			var 	src = $(e.content).attr('src'),
				regex = /^data:image\/([a-z0-9_-]+);([a-z0-9_-]+),([\W\w]+)$/i,
				matches = src.match(regex);

				if(!src || !matches)
					return;
			
			var 	pasteId = self.$editor.find('img[data-paste-id]').length,
				el = editor.dom.create('img', {
					'data-paste-id': pasteId,
					'src': src,
					'style':'-x-ignore: 1'
				});

			editor.selection.setNode(el);

			self.uploadPastedImage(pasteId, matches[1], matches[3], matches[2]);
		});
	},
      	uploadPastedImage: function(pasteId, type, data, encoding)
      	{
      		$uploader = this.$container.closest('form').find('.AttachmentUploader');
      		$textarea = this.$textarea;
      		$editor = this.$editor;

      		if (!$uploader.length)
      			return false;

      		try {
      			var form = new FormData();
      			if (typeof(data) == 'string') {
      				// data URI
      				var byteString;
      				if (encoding == 'base64') {
      					byteString = atob(data);
      				} else {
      					byteString = unescape(data);
      				}

      				var array = [];
      				for(var i = 0; i < byteString.length; i++) {
      					array.push(byteString.charCodeAt(i));
      				}
      				data = new Blob([new Uint8Array(array)], {type: 'image/' + type});
      			}

      			var date = new Date(),
      				filename = 'upload_' + date.getFullYear() + '-' + (date.getMonth() + 1) + '-' + date.getDate()
      					+ '_' + date.getHours() + '-' + date.getMinutes() + '-' + date.getSeconds()
      					+ '.' + type;

      			form.append('upload', data, filename);
      			form.append('filename', filename);
      			form.append('_xfToken', XenForo._csrfToken);
      			form.append('_xfNoRedirect', '1');
      			$uploader.find('.HiddenInput').each(function() {
      				var $input = $(this);
      				form.append($input.data('name'), $input.data('value'));
      			});
      		} catch (e) {
      			return false;
      		}

      		var self = this;

      		// need to use the direct jQuery interface here
      		$.ajax({
      			url: XenForo.canonicalizeUrl($uploader.data('action'), XenForo.ajaxBaseHref),
      			method: 'POST',
      			dataType: 'json',
      			data: form,
      			processData: false,
      			contentType: false
      		}).done(function(ajaxData) {
      			if (!self.$textarea.data('mce4'))
      				return;

      			$img = $editor.find('img[data-paste-id=' + pasteId + ']');
      			
      			if (XenForo.hasResponseError(ajaxData)){
      				$img.remove();
      			}else{
      				$img.data('paste-id', '').attr('src', ajaxData.viewUrl).attr('alt', 'attachFull' + ajaxData.attachment_id).addClass('attachFull');
      				$uploader.trigger({
      					type: 'AttachmentUploaded',
      					ajaxData: ajaxData
      				});
      			}
      		}).fail(function(xhr) {
      			$editor.find('img[data-paste-id=' + pasteId + ']').remove();

      			try
      			{
      				var ajaxData = $.parseJSON(xhr.responseText);
      				if (ajaxData && XenForo.hasResponseError(ajaxData))
      				{
      				}
      			}
      			catch (e) {}
      		});

      		return true;
      	}
});

tinymce.PluginManager.add('xen_paste_img', tinymce.plugins.xen_paste_img);