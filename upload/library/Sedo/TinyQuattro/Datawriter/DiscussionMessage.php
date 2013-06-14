<?php
class Sedo_TinyQuattro_Datawriter_DiscussionMessage extends XFCP_Sedo_TinyQuattro_Datawriter_DiscussionMessage
{
	protected function _messagePreSave()
	{
		parent::_messagePreSave();
		
		if(isset($this->_newData['xf_post']['message']))
		{
			$this->_newData['xf_post']['message'] = preg_replace('# {4}#', "\t", $this->_newData['xf_post']['message']);
		}
	}
}
//Zend_Debug::dump($class);