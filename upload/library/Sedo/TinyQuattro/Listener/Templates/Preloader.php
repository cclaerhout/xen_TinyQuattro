<?php

class Sedo_TinyQuattro_Listener_Templates_Preloader
{
	public static function PageContainerPreloader($templateName, array &$params, XenForo_Template_Abstract $template)
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
					'quattroIntegration' =>  Sedo_TinyQuattro_Helper_Quattro::isEnabled()
				);
		   	break;
		}
	}

	public static function EditorPreloader($templateName, array &$params, XenForo_Template_Abstract $template)
	{
		switch ($templateName)
		{
		   	case 'editor':
   				$visitor = XenForo_Visitor::getInstance();
   				$xenOptions = XenForo_Application::get('options');
		   		
				/*Enable check + get Bbm Params*/
				$ccv = array(
					self::getTemplateParam('controllerName', $params),
					self::getTemplateParam('controllerAction', $params),
					self::getTemplateParam('viewName', $params)
				);
				
				list($enable, $bbmParams) = Sedo_TinyQuattro_Helper_Quattro::isEnabled(true, $ccv);

				if(!$enable)
				{
					break;
				}
				
				/* NoAttachment editors detection */
				if(	isset($params['editorOptions']) 
					&& isset($params['editorOptions']['extraClass'])
					&& strpos('NoAttachment', $params['editorOptions']['extraClass']) !== false
					&& $xenOptions->quattro_noattach_img
				)
				{
					$params['editorOptions']['extraClass'] .= ' ImgFallback';
				}

				$bbmShowWysiwyg = self::_showWysiwyg($params['showWysiwyg']);
				$xenShowWysiwyg = $params['showWysiwyg'];

				
				/* Smilie categories - will need to be rewritten */
				$bbmSmiliesHaveCategories = false;

				if($xenOptions->quattro_smilies_sm_addon_enable)
				{
					list($bbmSmiliesHaveCategories, $bbmSmilies) = Sedo_TinyQuattro_Helper_Editor::getSmiliesByCategory();	
				}

				/* Extra values to get the Mce Config */
				$mceConfig = new Sedo_TinyQuattro_Helper_MceConfig($params, $bbmParams, $template);

				list($mceConfig, $mceBtnCss) = $mceConfig->getMceConfig();

				$params += array(
					'loadQuattro' => self::_checkQuattroPermissions(), 	//quattro param
					'quattroIntegration' => self::_quattroIntegration(),	//quattro integration js (for 1.2)
					'mceConfig' => $mceConfig,
					'mceBtnCss' => $mceBtnCss,
					'bbmSmiliesHaveCategories' => $bbmSmiliesHaveCategories
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

	protected static function _showWysiwyg($showWysiwyg)
	{
		$options = XenForo_Application::get('options');
		$visitor = XenForo_Visitor::getInstance();
 
		if($options->quattro_parser_bypass_mobile_limit)
		{
			$isMobile = XenForo_Visitor::isBrowsingWith('mobile');
			
			if($isMobile)
			{
				$showWysiwyg = true;
			
				if($options->quattro_parser_mobile_user_option && !$visitor->quattro_rte_mobile)
				{
					$showWysiwyg = false;			
				}
			}
		}

		if($options->quattro_parser_bypass_ie7limit)
		{
			if(isset($visitor->getBrowser['IEis']))
			{
				//Browser Detection (Mobile/MSIE) Addon
				if($visitor->getBrowser['isIE'] && $visitor->getBrowser['IEis'] == 7)
				{
					$showWysiwyg = true;
				}
			}
			else
			{
				//Manual Detection
				if(isset($_SERVER['HTTP_USER_AGENT']) && preg_match('#msie (\d+)#i', $_SERVER['HTTP_USER_AGENT'], $match))
				{
					if($match[1] == 7)
					{
						$showWysiwyg = true;
					}
				}
			}
		}

		if(!XenForo_Visitor::getInstance()->enable_rte)
		{
			$showWysiwyg = false;
		}

		return $showWysiwyg;
	}

	public static function getTemplateParam($key, $params)
	{
		if(isset($params[$key]))
		{
			return $params[$key];
		}
		
		return null;
	}	
}
//Zend_Debug::dump($abc);