<?php
class Sedo_TinyQuattro_ControllerPublic_Editor extends XFCP_Sedo_TinyQuattro_ControllerPublic_Editor
{
	public function actionQuattroDialog()
	{
		/*Retrieve JS overlay params*/
		$dialog = $this->_input->filterSingle('dialog', XenForo_Input::STRING);
		$selectedHtml = $this->_input->filterSingle('selectedHtml', XenForo_Input::STRING);
		$selectedText = $this->_input->filterSingle('selectedText', XenForo_Input::STRING);
		$isUrl = $this->_input->filterSingle('isUrl', XenForo_Input::STRING);
		$isLink = $this->_input->filterSingle('isLink', XenForo_Input::STRING);
		$isEmail = $this->_input->filterSingle('isEmail', XenForo_Input::STRING);
		$urlDatas = $this->_input->filterSingle('urlDatas', XenForo_Input::JSON_ARRAY);

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
			'urlDatas' => $urlDatas
		);

		/* Extend ViewParams */
		$viewParams = $this->_quattroViewParams($dialog, $viewParams);
		
		return $this->responseView('Sedo_Quattro_ViewPublic_Editor_Dialog', 'quattro_dialog_' . $dialog, $viewParams);	
	}
	
	protected function _quattroViewParams($dialog, $viewParams)
	{
		if ($dialog == 'media')
		{
			$viewParams['sites'] = $this->_getBbCodeModel()->getAllBbCodeMediaSites();
		}
		
		if ($dialog == 'smilies_slider')
		{
			$smilies = XenForo_ViewPublic_Helper_Editor::getEditorSmilies();

			foreach ($smilies as &$smiley){
				$smiley['type'] = (is_int($smiley[1])) ? 'sprite' : 'link';
			}

			$xSmiles = XenForo_Application::get('options')->get('quattro_xsmilies_slide');
			$smilies = array_chunk($smilies, $xSmiles, true);

			$viewParams['smiliesBySlides'] = $smilies;
		}
	
		return $viewParams;
	}
}
//Zend_Debug::dump($abc);