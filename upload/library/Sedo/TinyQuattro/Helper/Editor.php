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
		
		if($xenOptions->tinyquattro_fixer_tags_limit)
		{
			//For reference : 1500-1800 is a high number of tags...
			$miniParser->setFixerTagsLimit($xenOptions->tinyquattro_fixer_tags_limit);
		}
		
		$content = $miniParser->fixer();

		/*XenForo specific adaptation*/
			//Fix Blockquote/indent order - working with redactor
			$content = preg_replace('#(\[SIZE=\d{1,2}\])(\[INDENT\])#i', '$2$1', $content);
			$content = preg_replace('#(\[/INDENT\])(\[/SIZE\])#i', '$2$1', $content);
		
		return $content;
	}
}
//Zend_Debug::dump($bbmSmilies);