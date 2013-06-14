<?php
class Sedo_TinyQuattro_Datawriter_ConversationMessage extends XFCP_Sedo_TinyQuattro_Datawriter_ConversationMessage
{
	protected function _preSave()
	{
		parent::_preSave();
		
		if(isset($this->_newData['xf_conversation_message']['message']))
		{
			$this->_newData['xf_conversation_message']['message'] = preg_replace('# {4}#', "\t", $this->_newData['xf_conversation_message']['message']);
		}
	}
}
//Zend_Debug::dump($class);