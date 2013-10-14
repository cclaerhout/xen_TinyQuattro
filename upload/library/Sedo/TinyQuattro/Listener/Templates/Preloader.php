<?php

class Sedo_TinyQuattro_Listener_Templates_Preloader
{
	public static function preloader($templateName, array &$params, XenForo_Template_Abstract $template)
	{
		switch ($templateName)
		{
		   	case 'PAGE_CONTAINER':
   				$visitor = XenForo_Visitor::getInstance();

				if(!$visitor->enable_rte)
				{
					break;
				}
				
				$params += array(
					'quattroIntegration' => (self::_checkQuattroPermissions() && self::_quattroIntegration()) //quattro integration js (for 1.2)
				);
		   	break;
		   	case 'editor':
   				$visitor = XenForo_Visitor::getInstance();

				//NoAttachment editors detection
				if(	isset($params['editorOptions']) 
					&& isset($params['editorOptions']['extraClass'])
					&& strpos('NoAttachment', $params['editorOptions']['extraClass']) !== false
					&& XenForo_Application::get('options')->get('quattro_noattach_img')
				)
				{
					$params['editorOptions']['extraClass'] .= ' ImgFallback';
				}

				if(!$visitor->enable_rte)
				{
					break;
				}
				
				$bbmShowWysiwyg = self::_showWysiwyg();
				$xenShowWysiwyg = $params['showWysiwyg'];
				
				$params += array(
					'loadQuattro' => self::_checkQuattroPermissions(), 	//quattro param
					'quattroIntegration' => self::_quattroIntegration(),	//quattro integration js (for 1.2)
					'quattroExtraPlugins' => self::_quattroExtraPlugins(),	//quattro param
					'quattroGrid' => array(), 				//default bbm param
					'customQuattroButtonsCss' => array(), 			//default bbm param
					'customQuattroButtonsJs' => array(), 				//default bbm param
					'bbmSmilies' => Sedo_TinyQuattro_Helper_Editor::getEditorSmilies()
				);
				
				if($xenShowWysiwyg == false && $bbmShowWysiwyg == true)
				{
					$params['formCtrlNameHtml'] =  $params['formCtrlNameHtml'] . '_html';
				}
				
				$params['showWysiwyg'] = $bbmShowWysiwyg;
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
			
			if(!$visitor->enable_rte)
			{
				$enable = false;			
			}
				
			self::$QuattroPerms = $enable;
			return $enable;
		}
		
		return self::$QuattroPerms;
	}

	protected static function _quattroExtraPlugins()
	{
		$options = XenForo_Application::get('options');
		$plugins = array();
		
		if(!empty($options->quattro_extra_bbcodes['xtable']))
		{
			$plugins[] = 'table';
		}

		if(!empty($options->quattro_usertagging))
		{
			$plugins[] = 'xen_tagging';
		}

		if(!empty($options->quattro_contextmenu))
		{
			$plugins[] = 'contextmenu';
		}

		if(!empty($options->quattro_xendropping))
		{
			$plugins[] = 'xen_dropping';
		}

		if(!empty($options->quattro_xenpaste_dataimg_upload))
		{
			$plugins[] = 'xen_paste_img'; // buggy: http://www.tinymce.com/develop/bugtracker_view.php?id=6367
		}

		$plugins = implode(' ', $plugins);
		return $plugins;
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