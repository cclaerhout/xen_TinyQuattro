!function(n,t,r,u){var f=XenForo.QuickReply;XenForo.QuickReply=function(u){var o=XenForo.getEditorInForm(u),e;if(o.$editor)return f(u);f(u),e=XenForo.MultiSubmitFix(u),this.scrollAndFocus=function(){return n(r).scrollTop(u.offset().top),t.tinyMCE?t.tinyMCE.editors.ctrl_message_html.focus():n("#QuickReply").find("textarea:first").get(0).focus(),this},u.data("QuickReply",this).unbind("AutoValidationComplete").bind({AutoValidationComplete:function(r){var f,o,s;if(r.ajaxData._redirectTarget&&(t.location=r.ajaxData._redirectTarget),n('input[name="last_date"]',u).val(r.ajaxData.lastDate),e&&e(),u.find("input:submit").blur(),u.hasClass("QuickReplyLive")){if(n('input[name="last_position"]',u).val(r.ajaxData.lastPosition),n("form.InlineModForm").data("timestamp",r.ajaxData.lastDate),r.ajaxData.posts&&r.ajaxData.posts.length)for(i=0;i<r.ajaxData.posts.length;i++)n(r.ajaxData.posts[i]).xfInsert("appendTo",n("ol#messageList"),"xfSlideDown")}else new XenForo.ExtLoader(r.ajaxData,function(){n("#messageList").find(".messagesSinceReplyingNotice").remove(),n(r.ajaxData.templateHtml).each(function(){this.tagName&&n(this).xfInsert("appendTo",n("#messageList"))})});return n("#QuickReply").find("textarea").val(""),t.tinyMCE&&(f=t.tinyMCE.editors.ctrl_message_html,o=n(f.getElement()),f.setContent(""),s=function(){f.remove()},new xenMCE.BbCodeWysiwygEditor(o,s)),t.sessionStorage&&(t.sessionStorage.quickReplyText=null),u.trigger("QuickReplyComplete"),u.hasClass("QuickReplyLive")&&(n(".AttachmentEditor").find(".AttachmentList.New li:not(#AttachedFileTemplate)").xfRemove(),u.data("isReplying",0)),!1}})},XenForo.QuickReplyTrigger=function(t){var i=XenForo.MultiQuote!==u&&XenForo.MultiQuote.prototype!==u&&XenForo.MultiQuote.prototype.quickReplyDataPrepare!==u;t.click(function(){var f=null,o=null,s={},h=null,e=null;return t.is(".MultiQuote")?(f=n(t.data("form")),i||(s={postIds:n(t.data("inputs")).map(function(){return this.value}).get()})):(f=n("#QuickReply"),f.data("QuickReply").scrollAndFocus()),i&&(e=new n.Event("QuickReplyDataPrepare"),e.$trigger=t,e.queryData=s,n(r).trigger(e)),o||(o=XenForo.ajax(t.data("posturl")||t.attr("href"),s,function(n){var r,u;if(XenForo.hasResponseError(n)||(delete o,r=XenForo.getEditorInForm(f),!r))return!1;r.execCommand&&!r.$editor?(u={},r.execCommand("mceInsertContent",!1,n.quoteHtml,u)):r.$editor?(r.insertHtml(n.quoteHtml),r.$editor.data("xenForoElastic")&&r.$editor.data("xenForoElastic")()):r.val(r.val()+n.quote),t.is(".MultiQuote")&&f.trigger("MultiQuoteComplete")})),!1})}}(jQuery,this,document);