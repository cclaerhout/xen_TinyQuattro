<?php
class Sedo_TinyQuattro_Listener_ViewPublic
{
	public static function listen($class, array &$extend)
	{
		/***
		*	The two above classes are extented to fix the toggle between the html/bbcode editors
		*	They are not triggered to save a message, but only to toggle the view
		**/
		
		if ($class == 'XenForo_ViewPublic_Editor_ToBbCode')
        	{
			$extend[] = 'Sedo_TinyQuattro_ViewPublic_Editor_ToBbCode';
		}

		if ($class == 'XenForo_ViewPublic_Editor_ToHtml')
        	{
			$extend[] = 'Sedo_TinyQuattro_ViewPublic_Editor_ToHtml';			
		}
	}
}
//Zend_Debug::dump($class);