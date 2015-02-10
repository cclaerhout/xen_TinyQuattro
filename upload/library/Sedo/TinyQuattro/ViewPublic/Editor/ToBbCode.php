<?php

class Sedo_TinyQuattro_ViewPublic_Editor_ToBbCode extends XFCP_Sedo_TinyQuattro_ViewPublic_Editor_ToBbCode
{
	protected $_cleanBbCodesRegex;
	
	//@extended
	public function renderJson()
	{
		$parent = parent::renderJson();
		
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

		/* Do not continue if the converter is not enabled*/
		if(!$options->quattro_converter_html_to_bbcode)
		{
			$parent['bbCode'] = $content;
			return $parent;
		}

		/*Clean BbCodes - step 1*/
      		$guiltyTags = implode('|', array_filter(explode(',', $options->tinyquattro_guilty_tags)));
		$this->_cleanBbCodesRegex = '#\[(?P<tag>' . $guiltyTags . ')(?P<options>=.+?)?\].+?\[/\1\](?:[\s]+\[\1(?:\2)?\].+?\[/\1\])+#iu';
		
		$content = $this->_cleanBbCodes($content);

		/*Clean BbCodes - step 2: Fix duplicated consecutive tags: [b][b] => [b]*/
		$content = preg_replace('#(\[(?:/)?[^\]]+\])(?:\1)+#ui', '$1', $content);

		/*Save and return modifications*/
		$parent['bbCode'] = $content;
		
		return $parent;
	}

	protected function _cleanBbCodes($string)
	{
		$string = preg_replace_callback($this->_cleanBbCodesRegex, array($this, '_cleanBbCodesRegexCallback'), $string);
		return $string;
	}
	
   	protected function _cleanBbCodesRegexCallback($matches)
	{
		$fullString = $matches[0];
		$tag = $matches['tag'];
		$options = (isset($matches['options'])) ? $matches['options'] : '';
		
		$openingTag = "[{$tag}{$options}]";
		$closingTag = "[/$tag]";
				
		//Siblings BbCodes
		$fullString = str_replace(array($openingTag, $closingTag), '', $fullString);
		$fullString = $openingTag . $fullString . $closingTag;

		//Nested BbCodes - Loop
		if(preg_match($this->_cleanBbCodesRegex, $fullString))
		{
			$fullString = $this->_cleanBbCodes($fullString);
		}

		return $fullString;
	}
}
//Zend_Debug::dump($class);