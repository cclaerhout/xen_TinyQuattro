<?php
class Sedo_TinyQuattro_Listener_LoadClass
{
	public static function listen($class, array &$extend)
	{
		/***
			Extend the html renderer BbCode (html=>bbcode) ; ie: background-color
		**/
		if ($class == 'XenForo_Html_Renderer_BbCode')
        	{
			$extend[] = 'Sedo_TinyQuattro_Html_Renderer_BbCode';
		}
	}
}
//Zend_Debug::dump($abc);