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
					//TinyQuattro disable on mobiles
					$enable = false;
				}
				
				if(empty($visitor->permissions['sedo_quattro']['display']))
				{
					//No permission to load TinyQuattro
					$enable = false;
				}
		
				$params += array(
					'loadQuattro' => $enable, //auattro param
					'quattroGrid' => array(), //default bbm param
					'customButtonsCss' => array(), //default bbm param
					'customButtonsJs' => array(), //default bbm param
					'showWysiwyg' => self::_showWysiwyg() // XenForo param
				);
	   		break;
		}
	}
	
	protected static function _showWysiwyg()
	{
		$options = XenForo_Application::get('options');
		$visitor = XenForo_Visitor::getInstance();
		$isMobile = XenForo_Visitor::isBrowsingWith('mobile');
 
		if($options->quattro_parser_bypass_mobile_limit)
		{
			$showWysiwyg = true;
			
			if($isMobile && $options->quattro_parser_mobile_user_option && !$visitor->quattro_rte_mobile)
			{
				$showWysiwyg = false;			
			}
		}
		else
		{
			$showWysiwyg = !$isMobile;
		}
		
		return $showWysiwyg;
	}
}
//Zend_Debug::dump($abc);