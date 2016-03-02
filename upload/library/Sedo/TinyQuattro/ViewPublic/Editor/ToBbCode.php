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

		$formatter = XenForo_BbCode_Formatter_Base::create('Sedo_TinyQuattro_BbCode_Formatter_BbCode');
		$parser = XenForo_BbCode_Parser::create($formatter);
		$bbcodeTree = $parser->parse($content);

		$guiltyTags = array_fill_keys(array_filter(explode(',', $options->tinyquattro_guilty_tags)),true);
		$this->_cleanBbCodes($guiltyTags, null, $bbcodeTree);

		/*Save and return modifications*/
		$parent['bbCode'] = $formatter->renderTree($bbcodeTree);

		return $parent;
	}

	protected function _cleanBbCodes(array $guiltyTags, $parentTag, &$bbcodeTree)
	{
		// merge any children
		foreach($bbcodeTree as $key => &$tag)
		{
			if (isset($tag['tag']))
			{
				if (!isset($tag['children']))
				{
					$tag['children'] = array();
				}

				$this->_cleanBbCodes($guiltyTags, $tag, $tag['children']);

				$count = count($tag['children']);
				if (count($tag['children']) == 1)
				{
					$childTag = reset($tag['children']);
					if (isset($childTag['tag']) && isset($guiltyTags[$childTag['tag']]) &&
						$childTag['tag'] == $tag['tag'] && $childTag['option'] == $tag['option'])
					{
						$tag['children'] = $childTag['children'];
					}
				}
			}
		}
		// merge top-level, keep repeating untill we run out of mergable tags
		do
		{
			$modified = false;
			$lookback1Key = null;
			$lookback1Tag = null;
			$lookback2Key = null;
			$lookback2Tag = null;
			foreach($bbcodeTree as $key => &$tag)
			{
				if ($lookback1Tag !== null && isset($tag['tag']) && isset($guiltyTags[$tag['tag']]))
				{
					if (isset($tag['tag']) && $tag['tag'] == $parentTag['tag'] && $tag['option'] == $parentTag['option'])
					{
						// rebuild children array
						$children = array();
						foreach($bbcodeTree as $childkey => & $child)
						{
							if ($childkey == $key)
							{
								foreach($tag['children'] as $child)
								{
									$children[] = $child;
								}
								continue;
							}
							$children[] = $child;
						}
						$bbcodeTree = $children;
						// remove redundant tag, restart the cleaning loop
						$modified = true;
						break;
					}
					// lookback1Tag is a string
					if (!isset($lookback1Tag['tag']) && isset($lookback2Tag['tag']) &&
						$tag['tag'] == $lookback2Tag['tag'] && $tag['option'] == $lookback2Tag['option'])
					{
						// move whitespace
						$lastChildIndex = count($lookback2Tag['children']) - 1;
						if ($lastChildIndex >= 0)
						{
							if (!isset($lookback2Tag['children'][$lastChildIndex]['tag']))
							{
								$lookback2Tag['children'][$lastChildIndex] .= $lookback1Tag;
							}
							else
							{
								$lookback2Tag['children'][] = $lookback1Tag;
								$lastChildIndex += 1;
							}
							// merge tag
							foreach($tag['children'] as $child)
							{
								if (!isset($lookback2Tag['children'][$lastChildIndex]['tag']) && !isset($child['tag']))
								{
									$lookback2Tag['children'][$lastChildIndex] .= $child;
								}
								else
								{
									$lookback2Tag['children'][] = $child;
									$lastChildIndex += 1;
								}
							}
						}
						$bbcodeTree[$lookback2Key] = $lookback2Tag;
						unset($bbcodeTree[$lookback1Key]);
						unset($bbcodeTree[$key]);
						$modified = true;
						// update tracking to remove lookback1tag
						$lookback1Key = $lookback2Key;
						$lookback1Tag = $lookback2Tag;
						$lookback2Key = null;
						$lookback2Tag = null;
						continue;
					}
				}
				$lookback2Tag = $lookback1Tag;
				$lookback2Key = $lookback1Key;
				$lookback1Tag = $tag;
				$lookback1Key = $key;
			}
		}
		while ($modified);
	}
}
//Zend_Debug::dump($class);