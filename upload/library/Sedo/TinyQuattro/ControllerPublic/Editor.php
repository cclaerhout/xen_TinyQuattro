<?php
class Sedo_TinyQuattro_ControllerPublic_Editor extends XFCP_Sedo_TinyQuattro_ControllerPublic_Editor
{
	/**
	 * Quattro Dialog Builder (TinyMCE-XenForo bridge)
	 */

	public function actionQuattroDialog()
	{
		/*Retrieve JS overlay params*/
		$dialog = $this->_input->filterSingle('dialog', XenForo_Input::STRING);

		if(!$dialog)
		{
			throw new XenForo_Exception(new XenForo_Phrase('sedo_quattro_you_should_not_be_there'), true);
		}		
		
		$selectedHtml = $this->_input->filterSingle('selectedHtml', XenForo_Input::STRING);
		$selectedText = $this->_input->filterSingle('selectedText', XenForo_Input::STRING);
		$isUrl = $this->_input->filterSingle('isUrl', XenForo_Input::STRING);
		$isLink = $this->_input->filterSingle('isLink', XenForo_Input::STRING);
		$isEmail = $this->_input->filterSingle('isEmail', XenForo_Input::STRING);
		$urlDatas = $this->_input->filterSingle('urlDatas', XenForo_Input::JSON_ARRAY);
		$attachmentData = $this->_input->filterSingle('attach', XenForo_Input::JSON_ARRAY);

		/*Convert needed strings to booleans*/
		$isUrl = filter_var($isUrl, FILTER_VALIDATE_BOOLEAN);
		$isLink = filter_var($isLink, FILTER_VALIDATE_BOOLEAN);
		$isEmail = filter_var($isEmail, FILTER_VALIDATE_BOOLEAN);

		/*Check length*/
		$htmlCharacterLimit = 4 * XenForo_Application::get('options')->messageMaxLength;

		if ($htmlCharacterLimit && utf8_strlen($selectedHtml) > $htmlCharacterLimit)
		{
			throw new XenForo_Exception(new XenForo_Phrase('submitted_message_is_too_long_to_be_processed'), true);
		}

		/* Basic URL checker: only for direct text inputs */
		if(!$isUrl)
		{
			/* LINK */
			$regex_url = '#^(?:\s+)?(?:(?:https?|ftp|file)://|www\.|ftp\.)[-\p{L}0-9+&@\#/%=~_|$?!:,.]*[-\p{L}0-9+&@\#/%=~_|$](?:\s+)?$#ui';
			$isLink= (!empty($selectedText) && preg_match($regex_url, $selectedText, $matches)) ? true : false;
			$urlDatas['text'] = $urlDatas['href'] = (isset($matches[0])) ? $matches[0] : null;
	
			/* EMAIL */
			if(!$isLink)
			{
				$regex_mail = '#^(?:\s+)?[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}(?:\s+)?$#ui';
				$isEmail = (!empty($selectedText) && preg_match($regex_mail, $selectedText, $matches)) ? true : false;
				$urlDatas['text'] = $urlDatas['href'] = (isset($matches[0])) ? $matches[0] : null;
			}
			
			/* URL */
			$isUrl = ($isLink || $isEmail) ? true : false;
		}

		/* Mail input cleaner */	
		if($isEmail)
		{
			$urlDatas['text'] = str_replace('mailto:', '', $urlDatas['text']);
			$urlDatas['href'] = str_replace('mailto:', '', $urlDatas['href']);
		}

		/*Get Post attachments*/
		$attachments = array();
		$attachments = $this->_quattroGetAttachments($attachmentData['type'], $attachmentData['id'], $attachmentData['hash']);

		$imgAttachments = array();

		foreach($attachments as $attachment)
		{
			if(!empty($attachment['thumbnailUrl']))
			{
				$imgAttachments[] = $attachment;
			}
		}
		
		/* Create ViewParams */
		$viewParams = array(
			'selection' => array(
				'text' => strip_tags($selectedText),
				'html' => $selectedHtml,
				'bbCode' => $this->getHelper('Editor')->convertEditorHtmlToBbCode($selectedHtml, $this->_input)
			),
			'isUrl' => $isUrl,
			'isLink' => $isLink,
			'isEmail' => $isEmail,
			'urlDatas' => $urlDatas,
			'attachments' =>  $attachments,
			'imgAttachments' => $imgAttachments
		);

		/* Extend ViewParams */
		$viewParams = $this->_quattroViewParams($dialog, $viewParams);
		
		return $this->responseView('Sedo_Quattro_ViewPublic_Editor_Dialog', 'quattro_dialog_' . $dialog, $viewParams);	
	}

	/**
	 * Get Quattro View Params
	 */
	protected function _quattroViewParams($dialog, $viewParams)
	{
		if ($dialog == 'media')
		{
			$viewParams['sites'] = $this->_getBbCodeModel()->getAllBbCodeMediaSites();
		}
		
		if ($dialog == 'smilies_slider')
		{
			$xenOptions = XenForo_Application::get('options');
			$xSmiles = $xenOptions->quattro_xsmilies_slide;
			$smiliesHaveCategories = false;

			//Check if the Smiley Manager is installed
			if($xenOptions->quattro_smilies_sm_addon_enable)
			{
				list($smiliesHaveCategories, $smilies) = Sedo_TinyQuattro_Helper_Editor::getSmiliesByCategory(null, true);	
			}
			else
			{
				$smilies = Sedo_TinyQuattro_Helper_Editor::getEditorSmilies(null, true);
			}

			//Proceed differently if the Smiley manager is installed or not
			if(!$smiliesHaveCategories)
			{
				$smilies = array_chunk($smilies, $xSmiles, true);
			}
			else
			{
				$output = array();
				foreach($smilies as $smiliesCategory)
				{
					if(!isset($smiliesCategory['smilies']))
					{
						continue;
					}

					$title = $smiliesCategory['title'];
					$innerSmiliesGroups = array_chunk($smiliesCategory['smilies'], $xSmiles, true);
					$totalGroups = count($innerSmiliesGroups);
					$i = 1;
					
					foreach($innerSmiliesGroups as $innerSmilies)
					{
						$titleSuffixFirst = ($totalGroups > 1) ? $i : false;
						$titleSuffixLast = ($totalGroups > 1) ? $totalGroups : false;

						$output[] = array(
							'title' => $title,
							'titleSuffixFirst' => $titleSuffixFirst,
							'titleSuffixLast' => $titleSuffixLast,
							'smilies' => $innerSmilies
						);
						
						$i++;
					}
				}
				
				$smilies = $output;
			}

			$viewParams += array(
				'smiliesHaveCategories' => $smiliesHaveCategories,
				'smiliesBySlides' => $smilies
			);
		}

		return $viewParams;
	}

	/**
	 * Get Attachments and make them available for the mce overlay
	 */		
	protected function _quattroGetAttachments($type, $id, $hash)
	{
		$id = filter_var($id, FILTER_VALIDATE_INT);

		if(!in_array($type, array('newThread', 'newPost', 'edit', 'resource', 'newResource')) || !$id)
		{
			return array();
		}
		
		$ftpHelper = $this->getHelper('ForumThreadPost');
		
		if($type == 'edit')
		{
			$postId = $id;
			$attachmentModel = $this->_getAttachmentModel();
			$editAttachmentHash = $hash;
			
			list($post, $thread, $forum) = $ftpHelper->assertPostValidAndViewable($postId);

			if (!$this->_getPostModel()->canEditPost($post, $thread, $forum, $errorPhraseKey))
			{
				//To prevent users to edit the js code to get access to unauthorised attachments
				return array();
			}
			
			$attachmentParams = $this->_getForumModel()->getAttachmentParams(
					$forum,	array(
						'post_id' => $post['post_id']
					),
					null,
					null,
					$editAttachmentHash
			);

			$attachments = !empty($attachmentParams['attachments']) ? $attachmentParams['attachments'] : array();
			$attachments += $attachmentModel->getAttachmentsByContentId('post', $postId);
			$attachments = $attachmentModel->prepareAttachments($attachments);
		}
		elseif($type == 'newPost')
		{
			$threadId = $id;
			$quickReplyAttachmentHash = $hash;
			
			list($thread, $forum) = $ftpHelper->assertThreadValidAndViewable($threadId);

			if (!$this->_getThreadModel()->canReplyToThread($thread, $forum, $errorPhraseKey))
			{
				//To prevent users to edit the js code to get access to unauthorised attachments
				return array();
			}
			
			$attachmentParams = $this->_getForumModel()->getAttachmentParams($forum, array(
				'thread_id' => $thread['thread_id']
			), null, null, $quickReplyAttachmentHash);
			
			$attachments = !empty($attachmentParams['attachments']) ? $attachmentParams['attachments'] : array();			
		}
		elseif($type == 'newThread')
		{
			$forumId = $id;
			$attachmentHash = $hash;

			$forum = $ftpHelper->assertForumValidAndViewable($forumId);

			if (!$this->_getForumModel()->canPostThreadInForum($forum, $errorPhraseKey))
			{
				//Just keep the same logic as above
				return array();
			}

			$forumId = $forum['node_id'];
		
			$attachmentParams = $this->_getForumModel()->getAttachmentParams($forum, array(
				'node_id' => $forum['node_id']
			), null, null, $attachmentHash);
			
			$attachments = !empty($attachmentParams['attachments']) ? $attachmentParams['attachments'] : array();
		}
		elseif($type == 'resource')
		{
			$resourceId = $id;
			$attachmentHash = $hash;
			
			$fetchOptions = array('join' => XenResource_Model_Resource::FETCH_DESCRIPTION);

			list($resource, $category) = $this->_getResourceHelper()->assertResourceValidAndViewable($resourceId, $fetchOptions);

			if (!$this->_getResourceModel()->canEditResource($resource, $category, $errorPhraseKey))
			{
				return array();
			}

			$attachmentModel = $this->_getAttachmentModel();
			$attachments = $attachmentModel->getAttachmentsByContentId('resource_update', $resource['description_update_id']);
			$attachments = $attachmentModel->prepareAttachments($attachments);
			
			$tempAttachments = $attachmentModel->prepareAttachments(
				$attachmentModel->getAttachmentsByTempHash($attachmentHash)
			);
			$attachments = array_merge($attachments, $tempAttachments);
		}
		elseif($type == 'newResource')
		{
			$categoryId = $id;
			$attachmentHash = $hash;
		
			$category = $this->_getResourceHelper()->assertCategoryValidAndViewable($categoryId);
			if (!$category['allowResource'])
			{
				return array();
			}

			$attachmentModel = $this->_getAttachmentModel();
			$attachments = $attachmentModel->prepareAttachments(
				$attachmentModel->getAttachmentsByTempHash($attachmentHash)
			);
		}

		return $attachments;
	}

	/**
	 * @Extend XenForo function to add variable to template
	 */
	public function actionSmilies()
	{
		$parent = parent::actionSmilies();
		$mce = $this->_input->filterSingle('mce', XenForo_Input::STRING);
		$parent->params['mce'] = ($mce == 'mce4');
		
		return $parent;
	}

	/**
	 * Get Models
	 */
	 
	protected function _getPostModel()
	{
		return $this->getModelFromCache('XenForo_Model_Post');
	}

	protected function _getForumModel()
	{
		return $this->getModelFromCache('XenForo_Model_Forum');
	}

	protected function _getThreadModel()
	{
		return $this->getModelFromCache('XenForo_Model_Thread');
	}

	protected function _getAttachmentModel()
	{
		return $this->getModelFromCache('XenForo_Model_Attachment');
	}

	protected function _getResourceModel()
	{
		return $this->getModelFromCache('XenResource_Model_Resource');
	}

	protected function _getResourceHelper()
	{
		return $this->getHelper('XenResource_ControllerHelper_Resource');
	}
}
//Zend_Debug::dump($abc);