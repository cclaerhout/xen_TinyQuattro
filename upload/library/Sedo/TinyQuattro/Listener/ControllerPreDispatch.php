<?php
class Sedo_TinyQuattro_Listener_ControllerPreDispatch
{
	public static function Diktat(XenForo_Controller $controller, $action)
	{
		$options = XenForo_Application::get('options');
		
		if(	$options->quattro_disable_mobile_inline_edit
			&& (Sedo_TinyQuattro_Helper_Quattro::isEnabled() || $options->currentVersionId < 1020031)
			&& XenForo_Visitor::isBrowsingWith('mobile') 
			&& XenForo_Visitor::getInstance()->enable_rte
		)
		{
			//No matter here mobiles or only tablets, stwich off the overlay edit
			$options->messageInlineEdit = 0;
		}
	}
}