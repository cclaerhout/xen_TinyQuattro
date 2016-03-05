<?php
class Sedo_TinyQuattro_ControllerHelper_Editor extends XFCP_Sedo_TinyQuattro_ControllerHelper_Editor
{
	public function convertEditorHtmlToBbCode($messageTextHtml, XenForo_Input $input, $htmlCharacterLimit = -1)
	{
		$content = parent::convertEditorHtmlToBbCode($messageTextHtml, $input, $htmlCharacterLimit = -1);
		$content = Sedo_TinyQuattro_Helper_Editor::tagsFixer($content);

		return $content;
	}
}
//Zend_Debug::dump($abc);