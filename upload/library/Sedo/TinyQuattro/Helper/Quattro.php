<?php
class Sedo_TinyQuattro_Helper_Quattro
{
	/*For Integration with other addons*/
	public static function isEnabled()
	{
		$options = XenForo_Application::get('options');
		$visitor = XenForo_Visitor::getInstance();
		$enable = true;

		if($options->currentVersionId > 1020031)
		{
			//Only for XenForo 1.2: Check if addon is activated
			$activeAddons = XenForo_Model::create('XenForo_Model_DataRegistry')->get('addOns');
			$enable = (!empty($activeAddons['sedo_tinymce_quattro'])) ? true : false;
		}
				
		if(XenForo_Visitor::isBrowsingWith('mobile') && $options->quattro_disable_on_mobiles)
		{
			//TinyQuattro is disabled on mobiles
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
			
		return $enable;
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
