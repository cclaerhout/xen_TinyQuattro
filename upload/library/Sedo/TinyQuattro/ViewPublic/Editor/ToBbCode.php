<?php

class Sedo_TinyQuattro_ViewPublic_Editor_ToBbCode extends XFCP_Sedo_TinyQuattro_ViewPublic_Editor_ToBbCode
{
	protected $_cleanBbCodesRegex;
	
	//@extended
	public function renderJson()
	{
		$parent = parent::renderJson();

		$options = XenForo_Application::get('options');
		
		if(!isset($parent['bbCode']) || !$options->quattro_converter_html_to_bbcode)
		{
			return $parent;
		}

		$content = $parent['bbCode'];
		
		/*Fix Tabs*/
		$content = preg_replace('# {4}#', "\t", $content);

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