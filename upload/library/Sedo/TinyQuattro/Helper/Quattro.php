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

		$requestPath = XenForo_Application::get('requestPaths');
		if(!empty($requestPath['requestUri']) && preg_match('#/index\.php\?editor/to-html&rte=mce$#ui', $requestPath['requestUri']) && !$getConfig)
		{
			return true;
		} 

		if(!self::isOldXen())
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

		//TINYMCE IS DISABLED IN THE ADMIN CONTROLLER
		if(strpos($controllerName, 'ControllerAdmin') !== false || strpos($viewName, 'ViewAdmin') !== false)
		{
			return array(false, $fallback);	
		}

		//Check if the Bbm has been switched off
		if(!self::bbmIsEnabled())
		{
			return array(null, $fallback);			
		}
		
		$bbmParams = BBM_Helper_Buttons::getConfig($controllerName, $controllerAction, $viewName);

		if(!is_array($bbmParams) || empty($bbmParams['loadQuattro']))
		{
			return array(false, $fallback);		
		}

		return array(true, $bbmParams);
	}

	/*For private use*/
	public static function isOldXen()
	{
		return (XenForo_Application::get('options')->get('currentVersionId') < 1020031);
	}
	
	public static function canUseQuattroBbCode($tagName)
	{
		if(self::isOldXen())
		{
			return false; //Only for XenForo 1.2
		}
		
		$quattroBbCodes = XenForo_Application::get('options')->get('quattro_extra_bbcodes');
		
		//To do if needed: permissions by usergroups
		
		return (!empty($quattroBbCodes[$tagName]) ? true : false);
	}

	public static function getMceJsVersion($formatted = true)
	{
		if(self::isOldXen())
		{
			return '';
		}

		$addons = XenForo_Application::get('addOns');
		$xenJsVersion = XenForo_Application::$jsVersion;
		$mceVersion = (isset($addons['sedo_tinymce_quattro']))? $addons['sedo_tinymce_quattro'] : 0;
		$xenMceJsVersion = substr(md5($xenJsVersion.$mceVersion), 0, 8);


		if($formatted === 'raw')
		{
			return $xenMceJsVersion;
		}
	
		return "?_v=$xenMceJsVersion";
	}

	public static function bbmIsEnabled()
	{
		if(!XenForo_Application::isRegistered('addOns'))
		{
			//XenForo 1.1: check only the class & method
			return self::callbackChecker('BBM_Helper_Buttons', 'getConfig');
		}

		$activeAddons = XenForo_Application::get('addOns');
		
		return isset($activeAddons['BBM']);	
	}

	public static function checkIfAddonActive($addonId, $realReturn = false)
	{
		if(!XenForo_Application::isRegistered('addOns'))
		{
			return ($realReturn) ? false : true; //XenForo 1.1
		}

		$activeAddons = XenForo_Application::get('addOns');
		
		return isset($activeAddons[$addonId]);
	}

	public static function callbackChecker($class, $method)
	{
		if(!empty($method))
		{
			return (class_exists($class) && method_exists($class, $method));
		}
		
		return class_exists($class);
	}
}
//Zend_Debug::dump($abc);