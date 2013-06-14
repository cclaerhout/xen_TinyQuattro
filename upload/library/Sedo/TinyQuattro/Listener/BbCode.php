<?php

class Sedo_TinyQuattro_Listener_BbCode
{
	public static function listen($class, array &$extend)
	{
		if ($class == 'XenForo_BbCode_Formatter_Wysiwyg')
        	{
			$extend[] = 'Sedo_TinyQuattro_BbCode_Formatter_Wysiwyg';
		}
	}
}
//Zend_Debug::dump($class);