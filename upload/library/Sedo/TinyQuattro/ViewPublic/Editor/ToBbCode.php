<?php

class Sedo_TinyQuattro_ViewPublic_Editor_ToBbCode extends XFCP_Sedo_TinyQuattro_ViewPublic_Editor_ToBbCode
{
	protected $_cleanBbCodesRegex;
	
	//@extended
	public function renderJson()
	{
		$parent = parent::renderJson();
		$oldXen = Sedo_TinyQuattro_Helper_Quattro::isOldXen();
		
		if(!isset($parent['bbCode']))
		{
			return $parent;
		}

		$options = XenForo_Application::get('options');
		$content = $parent['bbCode'];

		/*Fix Tabs*/
		if($options->quattro_parser_fourws_to_tab)
		{
			$content = preg_replace('# {4}#', "\t", $content);
		}

		/* Detect if the user is no more connected */
		$visitor = XenForo_Visitor::getInstance();
		$parent['isConnected'] = ($visitor->user_id) ? 1 : 0;
		if(!$visitor->user_id)
		{
			$parent['notConnectedMessage'] = new XenForo_Phrase('quattro_no_more_connected');
		}

		if($oldXen)
		{
			$content = Sedo_TinyQuattro_Helper_Editor::tagsFixer($content);
		}

		$parent['bbCode'] = $content;
		
		return $parent;
	}
}
//Zend_Debug::dump($class);