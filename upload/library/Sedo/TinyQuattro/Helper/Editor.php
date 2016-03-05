<?php
class Sedo_TinyQuattro_Helper_Editor
{
	public static function tagsFixer($content)
	{
		$xenOptions = XenForo_Application::get('options');

		if(!$xenOptions->quattro_converter_html_to_bbcode)
		{
			return $content;
		}

		$parserOptions = array(
			'parserOpeningCharacter' => '[',
			'parserClosingCharacter' => ']',
			'htmlspecialcharsForContent' => false,
			'htmlspecialcharsForOptions' => false,
			'checkClosingTag' => true,
			'mergeAdjacentTextNodes' => true,
			'nl2br' => false,
			'trimTextNodes' => false
		);

		$tagsToCheck = array_fill_keys(explode(',', $xenOptions->tinyquattro_guilty_tags), array());
		$tagsToCheck['plain'] = array('plainText' => true);
		$miniParser= new Sedo_TinyQuattro_Helper_MiniParser($content, $tagsToCheck, array(), $parserOptions);
		$content = $miniParser->fixer();
		
		return $content;
	}
}
//Zend_Debug::dump($bbmSmilies);