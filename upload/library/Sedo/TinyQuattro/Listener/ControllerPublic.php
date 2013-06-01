<?php
class Sedo_TinyQuattro_Listener_ControllerPublic
{
	public static function listen($class, array &$extend)
	{
		if ($class == 'XenForo_ControllerPublic_Editor')
        	{
			$extend[] = 'Sedo_TinyQuattro_ControllerPublic_Editor';
		}
	}
}