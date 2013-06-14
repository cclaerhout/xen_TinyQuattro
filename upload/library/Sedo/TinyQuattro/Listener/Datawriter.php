<?php
class Sedo_TinyQuattro_Listener_Datawriter
{
	public static function listen($class, array &$extend)
	{
		switch ($class)
		{
		   	case 'XenForo_DataWriter_User':
				if (XenForo_Application::get('options')->get('quattro_parser_mobile_user_option'))
				{
					$extend[] = 'Sedo_TinyQuattro_Datawriter_User_MobileOption';
				}
		   	break;
	   		
	   		case 'XenForo_DataWriter_DiscussionMessage_Post':
	   			if (XenForo_Application::get('options')->get('quattro_parser_wysiwyg_to_bb'))
				{
		   			$extend[] = 'Sedo_TinyQuattro_Datawriter_DiscussionMessage';
		   		}
		   	break;

	   		case 'XenForo_DataWriter_ConversationMessage':
	   			if (XenForo_Application::get('options')->get('quattro_parser_wysiwyg_to_bb'))
				{
		   			$extend[] = 'Sedo_TinyQuattro_Datawriter_ConversationMessage';
		   		}
		   	break;	   		
		}
	}
}
//Zend_Debug::dump($class);