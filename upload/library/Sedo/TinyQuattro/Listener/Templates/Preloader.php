<?php

class Sedo_TinyQuattro_Listener_Templates_Preloader
{
	public static function preloader($templateName, array &$params, XenForo_Template_Abstract $template)
	{
		switch ($templateName)
		{
		   	case 'PAGE_CONTAINER':
				$params += array(
					'quattroIntegration' => (self::_checkQuattroPermissions() && self::_quattroIntegration()) //quattro integration js (for 1.2)
				);
		   	break;
		   	case 'editor':
				$params += array(
					'loadQuattro' => self::_checkQuattroPermissions(), 	//quattro param
					'quattroIntegration' => self::_quattroIntegration(),	//quattro integration js (for 1.2)
					'quattroGrid' => array(), 				//default bbm param
					'customQuattroButtonsCss' => array(), 			//default bbm param
					'customQuattroButtonsJs' => array(), 				//default bbm param
					'bbmSmilies' => Sedo_TinyQuattro_Helper_Editor::getEditorSmilies()
				);
				
				$params['showWysiwyg'] = self::_showWysiwyg();
	   		break;
		}
	}

	protected static function _quattroIntegration()
	{
		return (XenForo_Application::get('options')->get('currentVersionId') >= 1020031);
	}

	protected static $QuattroPerms = null;

	protected static function _checkQuattroPermissions()
	{
		if(self::$QuattroPerms == null)
		{
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
				
			self::$QuattroPerms = $enable;
			return $enable;
		}
		
		return self::$QuattroPerms;
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