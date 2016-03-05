<?php
class Sedo_TinyQuattro_Datawriter_ConversationMessage extends XFCP_Sedo_TinyQuattro_Datawriter_ConversationMessage
{
	protected function _preSave()
	{
		parent::_preSave();
		
		if(isset($this->_newData['xf_conversation_message']['message']))
		{
			if(Sedo_TinyQuattro_Helper_Quattro::isOldXen())
			{
				$message = &$this->_newData['xf_conversation_message']['message'];
				$message = preg_replace('# {4}#', "\t", $message);
				$message = Sedo_TinyQuattro_Helper_Editor::tagsFixer($message);
			}
		}
	}
}
//Zend_Debug::dump($class);