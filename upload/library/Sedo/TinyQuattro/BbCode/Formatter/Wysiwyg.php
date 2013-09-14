<?php
class Sedo_TinyQuattro_BbCode_Formatter_Wysiwyg extends XFCP_Sedo_TinyQuattro_BbCode_Formatter_Wysiwyg
{
	public function getTags()
	{
		$parentTags = parent::getTags();
		
		if(is_array($parentTags))
		{
			if(Sedo_TinyQuattro_Helper_Quattro::canUseQuattroBbCode('bcolor'))
			{
				$parentTags += array(
					'bcolor' => array(
						'hasOption' => true,
						'optionRegex' => '/^(rgb\(\s*\d+%?\s*,\s*\d+%?\s*,\s*\d+%?\s*\)|#[a-f0-9]{6}|#[a-f0-9]{3}|[a-z]+)$/i',
						'replace' => array('<span style="background-color: %s">', '</span>')
					)
				);			
			}

			if(Sedo_TinyQuattro_Helper_Quattro::canUseQuattroBbCode('justify'))
			{
				$parentTags += array(
					'justify' => array(
						'hasOption' => false,
						'callback' => array($this, 'renderTagAlign'),
						'trimLeadingLinesAfter' => 1,
					)
				);			
			
			}

			//WIP
			/*
			if(Sedo_TinyQuattro_Helper_Quattro::canUseQuattroBbCode('justify'))
			{
				$parentTags += array(
					'xtable' => array(
						'callback' => array($this, 'renderTagSedoXtable'),
					)
				);			
			}
			*/
		}
		
		return $parentTags;
	}
	
	public function filterFinalOutput($output)
	{
		$parent = parent::filterFinalOutput($output);

		if(!XenForo_Application::get('options')->get('quattro_parser_bb_to_wysiwyg'))
		{
			return $parent;
		}

		$emptyParaText = (XenForo_Visitor::isBrowsingWith('ie') ? '&nbsp;' : '<br />');

		//Fix Pacman effect with ol/ul with RTE editing
		$parent = preg_replace('#(</(ul|ol)>)\s</p>#', '$1<p>' . $emptyParaText . '</p>', $parent);

		//Fix for tabs (From DB to RTE editor && from Bb Code editor to rte Editor)
		$parent = preg_replace('#\t#', '&nbsp;&nbsp;&nbsp;&nbsp;', $parent);

		return $parent;
	}

	public function renderTagAlign(array $tag, array $rendererStates)
	{
		$parentOuput = parent::renderTagAlign($tag, $rendererStates);
		
		if(strtolower($tag['tag']) == 'justify' && Sedo_TinyQuattro_Helper_Quattro::canUseQuattroBbCode('justify'))
		{
			$text = $this->renderSubTree($tag['children'], $rendererStates);
			return $this->_wrapInHtml('<p style="text-align: ' . $tag['tag'] . '">', '</p>', $text) . "<break-start />\n";
		}
		
		return $parentOuput;
	}
	
	//WIP
	public function renderTagSedoXtable(array $tag, array $rendererStates)
	{
		$content = $this->renderSubTree($tag['children'], $rendererStates);

		$slaveTags = array(
			'thead' => array(
				'a' => '',
				'b' => ''
			),
			'tbody' => array(
				'a' => '',
				'b' => ''
			),
			'tfoot' => array(
				'a' => '',
				'b' => ''
			),
			'colgroup' => array(
				'a' => '',
				'b' => ''
			),
			'caption' => array(
				'a' => '',
				'b' => ''
			),
			'tr' => array(
				'a' => '',
				'b' => ''
			),
			'col' => array(
				'a' => '',
				'b' => ''
			),
			'td' => array(
				'a' => '',
				'b' => ''
			),
			'th' => array(
				'a' => '',
				'b' => ''
			)
		);

		$test = new Sedo_TinyQuattro_Helper_MiniParser($content, 'xtable', $slaveTags);
		break;

		$L0 = '{(thead|tbody|tfoot)(=(\[([\w\d]+)(?:=.+?)?\].+?\[/\4\]|[^{}]+)+?)?}(.*?){/\1}(?!(?:\W+)?{/\1})';
		$L1 = '{(colgroup|caption|tr)(=(\[([\w\d]+)(?:=.+?)?\].+?\[/\4\]|[^{}]+)+?)?}(.*?){/\1}(?!(?:\W+)?{/\1})';
		$L2 = '{(col|td|th)(=(\[([\w\d]+)(?:=.+?)?\].+?\[/\4\]|[^{}]+)+?)?}(.*?){/\1}(?!(?:\W+)?{/\1})';


		$start  = strpos($content, '{');
		$end    = strpos($str, '}', $start + 1);
		$length = $end - $start;
		$result = substr($str, $start + 1, $length - 1);


		preg_match_all("#$L0#is", $content, $matches, PREG_SET_ORDER);
		$content = ''; //RAZ

		foreach($matches as $match)
		{
			preg_match_all("#$L1#is", $match[5], $matches_l1, PREG_SET_ORDER);
			Zend_Debug::dump($matches_l1);
		}
		Zend_Debug::dump('a');		
	}
}
//Zend_Debug::dump($parent);
