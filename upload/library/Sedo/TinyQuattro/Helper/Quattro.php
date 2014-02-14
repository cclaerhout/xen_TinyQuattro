<?php
class Sedo_TinyQuattro_Helper_Quattro
{
	/***
	 * For Integration with other addons
	 * Optional paramater: array with $controllerName, $controllerAction, $viewName
	 * Purpose: check if a Bbm special editor config is not used
	 * 
	 * If no argument provided, return true/false
	 * If ccv provided, return true/false + the bbm params
	 **/
	public static function isEnabled($getConfig = false, array $manual_ccv = array())
	{
		$options = XenForo_Application::get('options');
		$visitor = XenForo_Visitor::getInstance();
		$enable = true;

		if($options->currentVersionId > 1020031)
		{
			//Only for XenForo 1.2: Check if addon is activated
			$activeAddons = array();
			
			if(XenForo_Application::isRegistered('addOns'))
			{
				$activeAddons = XenForo_Application::get('addOns');
			}
			
			$enable = (!empty($activeAddons['sedo_tinymce_quattro'])) ? true : false;
		}
				
		if(XenForo_Visitor::isBrowsingWith('mobile') && $options->quattro_disable_on_mobiles)
		{
			//TinyQuattro is disabled on mobiles
			$enable = false;
		}
			
		if(empty($visitor->permissions['sedo_quattro']['display']) || !$visitor->enable_rte)
		{
			//No permission to load TinyQuattro or RTE disabled
			$enable = false;
		}


		if(XenForo_Application::isRegistered('mceConfig'))
		{
			list($bbmEnable, $bbmConfig) = XenForo_Application::get('mceConfig');

			if($bbmEnable !== null)
			{
				$enable = $bbmEnable;
			}
				
			if($getConfig)
			{
				return array($enable, $bbmConfig);
			}
			else
			{
				return $enable;
			}
		}
		elseif(!empty($manual_ccv))
		{
			//Be sure the BBM don't use a custom editor config
			list($bbmEnable, $bbmConfig) = self::checkAndGetBbmConfig($manual_ccv);

			if($bbmEnable !== null)
			{
				$enable = $bbmEnable;
			}
			
			if($getConfig)
			{
				return array($enable, $bbmConfig);
			}
			else
			{
				return $enable;
			}
		}
		
		if($getConfig)
		{
			$fallback = array(
				'quattroGrid' => array(),
				'customQuattroButtonsCss' => array(),
				'customQuattroButtonsJs' => array()
			);

			return array($enable, $fallback);
		}
		else
		{
			return $enable;
		}
	}

	public static function checkAndGetBbmConfig(array $ccv)
	{
		$fallback = array(
			'quattroGrid' => array(),
			'customQuattroButtonsCss' => array(),
			'customQuattroButtonsJs' => array()
		);

		if(!class_exists('BBM_Helper_Buttons'))
		{
			return array(null, $fallback);
		}

		list($controllerName, $controllerAction, $viewName) = $ccv;
		
		$bbmParams = BBM_Helper_Buttons::getConfig($controllerName, $controllerAction, $viewName);

		if(!is_array($bbmParams) || empty($bbmParams['loadQuattro']))
		{
			return array(false, $fallback);		
		}

		return array(true, $bbmParams);
	}

	/*For private use*/
	public static function canUseQuattroBbCode($tagName)
	{
		if(XenForo_Application::get('options')->get('currentVersionId') < 1020031)
		{
			return false; //Only for XenForo 1.2
		}
		
		$quattroBbCodes = XenForo_Application::get('options')->get('quattro_extra_bbcodes');
		
		//To do if needed: permissions by usergroups
		
		return (!empty($quattroBbCodes[$tagName]) ? true : false);
	}
}
//Zend_Debug::dump($abc);