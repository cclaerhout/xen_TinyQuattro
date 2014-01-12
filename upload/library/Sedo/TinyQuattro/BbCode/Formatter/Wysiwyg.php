<?php
class Sedo_TinyQuattro_BbCode_Formatter_Wysiwyg extends XFCP_Sedo_TinyQuattro_BbCode_Formatter_Wysiwyg
{
	/**
	 * Custom MCE tags name
	 */
	protected $_mceBackgroundColorTagName = 'bcolor';
	protected $_mceTableTagName = 'xtable';

	/**
	 * Table default skin
	 */
	protected $_mceTableDefaultSkin = 'skin1';
	
	/**
	 * Xen Options for MCE Table
	 */
	protected $_xenOptionsMceTable;

	/**
	 * Extend tags
	 */
	public function getTags()
	{
		$parentTags = parent::getTags();
		$xenOptions = XenForo_Application::get('options');
		$quattroEnable = Sedo_TinyQuattro_Helper_Quattro::isEnabled();
		
		if(is_array($parentTags))
		{
			if(Sedo_TinyQuattro_Helper_Quattro::canUseQuattroBbCode('bcolor'))
			{
				$bcTag = Sedo_TinyQuattro_Helper_BbCodes::getQuattroBbCodeTagName('bcolor');
				$this->_mceBackgroundColorTagName = $bcTag;
				
				$parentTags += array(
					$bcTag => array(
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
						'trimLeadingLinesAfter' => 1
					)
				);			
			}
			
			if(	Sedo_TinyQuattro_Helper_Quattro::canUseQuattroBbCode('xtable')
				&&
				(	$xenOptions->quattro_table_all_editors_activation
					||
					$quattroEnable
				)
			)
			{
				$this->_preloadMceTemplates[] = 'quattro_bbcode_xtable';
				
				$tableTag = Sedo_TinyQuattro_Helper_BbCodes::getQuattroBbCodeTagName('xtable');
				$this->_mceTableTagName =  $tableTag; 

				$this->_mceTableDefaultSkin = XenForo_Template_Helper_Core::styleProperty('quattro_table_skin_default');

				$parentTags += array(
					$tableTag => array(
						'callback' => array($this, 'renderTagSedoXtable'),
						'stopLineBreakConversion' => true,
						'trimLeadingLinesAfter' => 2
					)
				);
				
				$this->_xenOptionsMceTable = Sedo_TinyQuattro_Helper_BbCodes::getMceTableXenOptions();
			}

			if($quattroEnable && $xenOptions->quattro_wysiwyg_quote)
			{
				/***
					$keyQuote = array_search('quote', $this->_undisplayableTags);
				
					if($keyQuote !== false)
					{
						unset($this->_undisplayableTags[$keyQuote]);
					}
				**/

				$parentTags['quote'] = array(
					'callback' => array($this, 'renderTagMceQuote')
				);			
			}			
		}
		
		return $parentTags;
	}

	/**
	 * Extend the filter final output to add some custom fixes
	 */	
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

	/**
	 * Extend XenForo Tag align to add jystify option
	 */
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

	/**
	 * MCE Quote
	 */	
	public function renderTagMceQuote(array $tag, array $rendererStates)
	{
		//Code from bbcode base
		$content = $this->renderSubTree($tag['children'], $rendererStates);

		if ($content === '')
		{
			return '';
		}

		$source = false;
		$attributes = array();

		if ($tag['option'])
		{
			$parts = explode(',', $tag['option']);
			$name = $this->filterString(array_shift($parts),
				array_merge($rendererStates, array('stopSmilies' => true))
			);

			foreach ($parts AS $part)
			{
				$partAttributes = explode(':', $part, 2);
				if (isset($partAttributes[1]))
				{
					$attrName = trim($partAttributes[0]);
					$attrValue = trim($partAttributes[1]);
					if ($attrName !== '' && $attrValue !== '')
					{
						$attributes[$attrName] = $attrValue;
					}
				}
			}

			list($firstName, $firstValue) = each($attributes);
			if ($firstName && $firstName != 'member')
			{
				$source = array('type' => $firstName, 'id' => intval($firstValue));
			}
		}
		else
		{
			$name = false;
		}

		//Custom code starts here
		if(!preg_match('#^<p>#', $content))
		{
			$content = "<p>{$content}</p>";
		}

		$data = array();
		
		if($name)
		{
			$data[] = 'data-username="' . htmlspecialchars($name) . '"';
		}
		
		if(!empty($attributes))
		{
			$dataNames = array();
			foreach($attributes as $name => $value)
			{
				$name = htmlspecialchars($name);
				$value = htmlspecialchars($value);
				
				$data[] = 'data-' . $name . '="'. $value . '"';
				$dataNames[] = $name;
			}
		
			if(!empty($dataNames))
			{
				$dataAttributes = htmlspecialchars(implode(',', $dataNames));
				$data[] = 'data-attributes="' . $dataAttributes . '"';
			}
		}
		
		$data = (!empty($data)) ? implode($data, ' ') : '';

		return '<blockquote class="mce_quote" data-mcequote="true" ' . $data . '>' . $content . '</blockquote>';
	}

	/**
	 * Mce Table Master Bb Code Renderer
	 */
	public function renderTagSedoXtable(array $tag, array $rendererStates)
	{
		$tagName = $tag['tag'];
		$tagOptions = $tag['option'];
		
		$tableOptionsChecker = new Sedo_TinyQuattro_Helper_TableOptions($tagName, $tagOptions, $this->_xenOptionsMceTable);
		list($attributes, $css, $extraClass) = $tableOptionsChecker->getValidOptions();

		$content = $this->renderSubTree($tag['children'], $rendererStates);

		$slaveTags = array(
			'thead' => array(
				'callback'  => array($this, 'renderTagSedoXtableSlaveTags'),
				'allowedParents' => array($tagName),
				'allowedChildren' => array('tr'),
				'disableTextNodes' => 'inAndAfter'
			),
			'tbody' => array(
				'callback'  => array($this, 'renderTagSedoXtableSlaveTags'),
				'allowedParents' => array($tagName),
				'allowedChildren' => array('tr'),
				'disableTextNodes' => 'inAndAfter'
			),
			'tfoot' => array(
				'callback'  => array($this, 'renderTagSedoXtableSlaveTags'),
				'allowedParents' => array($tagName),
				'allowedChildren' => array('tr'),
				'disableTextNodes' => 'inAndAfter'
			),
			'colgroup' => array(
				'callback'  => array($this, 'renderTagSedoXtableSlaveTags'),
				'allowedParents' => array($tagName),
				'allowedChildren' => array('col'),
				'disableTextNodes' => 'insideContent'
			),
			'caption' => array(
				'callback'  => array($this, 'renderTagSedoXtableSlaveTags'),
				'allowedParents' => array($tagName),
				'allowedChildren' => 'none'
			),
			'tr' => array(
				'callback'  => array($this, 'renderTagSedoXtableSlaveTags'),
				'allowedParents' => array($tagName, 'thead', 'tbody', 'tfoot'),
				'allowedChildren' => array('td', 'th'),
				'disableTextNodes' => 'insideContent'
			),
			'col' => array(
				'callback'  => array($this, 'renderTagSedoXtableSlaveTags'),
				'allowedParents' => array('colgroup'),
				'allowedChildren' => 'none'
			),
			'td' => array(
				'callback'  => array($this, 'renderTagSedoXtableSlaveTags'),
				'allowedParents' => array('tr'),
				'allowedChildren' => 'none',
				'disableTextNodes' => 'afterClosing'
			),
			'th' => array(
				'callback'  => array($this, 'renderTagSedoXtableSlaveTags'),
				'allowedParents' => array('tr'),
				'allowedChildren' => 'none',
				'disableTextNodes' => 'afterClosing'
			)
		);
		
		/***
			MiniParser options
		 	Don't use the XenForo formatter here neither...
		**/
		$miniParserOptions = array(
			'htmlspecialcharsForContent' => false,
			'breakToBr' => true,
			'renderStates' => array(),
			//'externalFormatter' => array($this, 'renderTree')
		);

		$miniParser =  new Sedo_TinyQuattro_Helper_MiniParser($content, $slaveTags, $tag, $miniParserOptions);
		$content = $miniParser->render();

		/*In the wysiwyg formatter, we don't use the class, but the data-style to get the skin (easier to manage in the javascript)*/
		if(preg_match('#skin\d{1,2}#', $extraClass, $match))
		{
			$skin = $match[0];
			$extraClass = str_replace($skin, '', $extraClass);
			
		}
		else
		{
			$skin = $this->_mceTableDefaultSkin;
		}

		$formattedCss = (empty($css)) ? '' : "style='{$css}'";
		$wysiwygOuput = "<table class='quattro_table {$extraClass}' {$attributes} {$formattedCss} data-skin='{$skin}'>{$content}</table>";
		
		return $wysiwygOuput;
	}

	/**
	 * Mce Table Slave Tags Renderer
	 */
	public function renderTagSedoXtableSlaveTags(array $tag, array $rendererStates, $parentClass)
	{
		$tagName = $tag['tag'];
		$tagOptions = $tag['option'];

		$tableOptionsChecker = new Sedo_TinyQuattro_Helper_TableOptions($tagName, $tagOptions, $this->_xenOptionsMceTable);
		list($attributes, $css, $extraClass) = $tableOptionsChecker->getValidOptions();
		
		$formattedClass = (empty($extraClass)) ? '' : "class='{$extraClass}'";
		$formattedCss = (empty($css)) ? '' : "style='{$css}'";

		$openingHtmlTag = "<{$tagName} {$formattedClass} {$attributes} {$formattedCss}>";
		$closingHtmlTag = "</$tagName>";

		/***
			We're using the formatter of the Miniparser - the "wrapInHtml" function is here public
		**/
		$content = $parentClass->renderSubTree($tag['children'], $rendererStates);

		if(empty($content))
		{
			//Will avoid tags to be "eaten" (MCE does it automatically, not Redactor)
			$content="&nbsp;";			
		}
			
		return $parentClass->wrapInHtml($openingHtmlTag, $closingHtmlTag, $content);
	}
}
//Zend_Debug::dump($parent);