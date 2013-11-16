<?php
class Sedo_TinyQuattro_BbCode_Formatter_Base extends XFCP_Sedo_TinyQuattro_BbCode_Formatter_Base
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
						'trimLeadingLinesAfter' => 1,
					)
				);			
			}
			
			if(Sedo_TinyQuattro_Helper_Quattro::canUseQuattroBbCode('xtable'))
			{
				$this->_preloadMceTemplates[] = 'quattro_bbcode_xtable';
				
				$tableTag = Sedo_TinyQuattro_Helper_BbCodes::getQuattroBbCodeTagName('xtable');
				$this->_mceTableTagName = $tableTag; 

				$this->_mceTableDefaultSkin = XenForo_Template_Helper_Core::styleProperty('quattro_table_skin_default');

				$parentTags += array(
					$tableTag => array(
						'callback' => array($this, 'renderTagSedoXtable'),
						'stopLineBreakConversion' => true,
						'trimLeadingLinesAfter' => 2,
					)
				);
				
				$this->_xenOptionsMceTable = Sedo_TinyQuattro_Helper_BbCodes::getMceTableXenOptions();
			}
		}
		
		return $parentTags;
	}

	/**
	 * Extend preloaded templates
	 */
	protected $_preloadMceTemplates = array();

	public function preLoadTemplates(XenForo_View $view)
	{
		 //Preload Table Template (use for css)
		 if($this->_view && !empty($this->_preloadMceTemplates))
		{
			foreach($this->_preloadMceTemplates as $templateName)
			{
				$this->_view->preLoadTemplate($templateName);
			}
		}

		return parent::preLoadTemplates($view);
	}

	/**
	 * Extend XenForo filterString function to add an option to recreate tabs in html
	 */
	public function filterString($string, array $rendererStates)
	{
		$string = parent::filterString($string, $rendererStates);

		if(XenForo_Application::get('options')->get('quattro_emulate_tabs_html'))
		{
			$string = $this->emulateWhiteSpace($string);
		}

		return $string;		
	}
	
	public function emulateWhiteSpace($string)
	{
		return preg_replace_callback(
			'#[\t]+#', 
			array($this, '_emulateWhiteSpaceRegexCallback'), 
			$string
		);
	}
	
	protected function _emulateWhiteSpaceRegexCallback($matches)
	{
		$breaksX = substr_count($matches[0], "\t");
		$breakPattern = '    ';
		$breakOutput = str_repeat($breakPattern, $breaksX);
					
		return "<span style='white-space:pre'>{$breakOutput}</span>";	
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
			$invisibleSpace = $this->_endsInBlockTag($text) ? '' : '&#8203;';
			return $this->_wrapInHtml('<div style="text-align: ' . $tag['tag'] . '">', $invisibleSpace. '</div>', $text);
		}
		
		return $parentOuput;
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

		if(!preg_match('#skin\d{1,2}#', $extraClass, $match))
		{
			$extraClass .= " $this->_mceTableDefaultSkin";
		}

		$formattedCss = (empty($css)) ? '' : "style='{$css}'";
		$fallback = "<table class='quattro_table {$extraClass}' {$attributes} {$formattedCss}>{$content}</table>";

		if ($this->_view)
		{
			//Create and render template
			$template = 
				$this->_view->createTemplateObject(
					'quattro_bbcode_xtable', 
					array(
						'tagContent' => $content, 
						'css' => $css,
						'attributes' => $attributes,
						'extraClass' => $extraClass
					)
				);
			
			return $template->render();
		}

		return $fallback;
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
			//Should not be needed with recent browsers but not sure with old ie
			$content="&nbsp;";			
		}
			
		return $parentClass->wrapInHtml($openingHtmlTag, $closingHtmlTag, $content);
	}
}
//Zend_Debug::dump($parent);