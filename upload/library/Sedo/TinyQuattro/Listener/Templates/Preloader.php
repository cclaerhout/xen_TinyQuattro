<?php

class Sedo_TinyQuattro_Listener_Templates_Preloader
{
	public static function preloader($templateName, array &$params, XenForo_Template_Abstract $template)
	{
		switch ($templateName)
		{
		   	case 'editor':
				$options = XenForo_Application::get('options');
			       	$visitor = XenForo_Visitor::getInstance();
				$enable = true;
				
				if(XenForo_Visitor::isBrowsingWith('mobile') && $options->quattro_disable_on_mobiles)
				{
					$enable = false;
				}
				
				if(empty($visitor->permissions['sedo_quattro']['display']))
				{
					$enable = false;
				}
		
				$params += array('loadQuattro' => $enable);
	   		break;
		}
	}
}
//Zend_Debug::dump($abc);