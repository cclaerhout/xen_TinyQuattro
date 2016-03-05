<?php
class Sedo_TinyQuattro_Datawriter_DiscussionMessage extends XFCP_Sedo_TinyQuattro_Datawriter_DiscussionMessage
{
	protected function _messagePreSave()
	{
		parent::_messagePreSave();
		
		if(isset($this->_newData['xf_post']['message']))
		{
			if(Sedo_TinyQuattro_Helper_Quattro::isOldXen())
			{
				$message = &$this->_newData['xf_post']['message'];
				$message = preg_replace('# {4}#', "\t", $message);
				$message = Sedo_TinyQuattro_Helper_Editor::tagsFixer($message);
			}
		}
	}
}
//Zend_Debug::dump($class);