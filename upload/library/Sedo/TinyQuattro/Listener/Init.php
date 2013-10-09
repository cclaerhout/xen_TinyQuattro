<?php
class Sedo_TinyQuattro_Listener_Init
{
	public static function init_helpers(XenForo_Dependencies_Abstract $dependencies, array $data)
	{
		if(!isset(XenForo_Template_Helper_Core::$helperCallbacks['playwithcolors']))
		{
			XenForo_Template_Helper_Core::$helperCallbacks += array(
				'playwithcolors' => array('Sedo_TinyQuattro_Helper_PlayWithColors', 'init')
			);
		}
	}
}