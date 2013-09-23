<?php
class Sedo_TinyQuattro_BbCode_Formatter_Base extends XFCP_Sedo_TinyQuattro_BbCode_Formatter_Base
{
	/**
	 * Custom MCE tags name
	 */
	protected $_mceBackgroundColorTagName = 'bcolor';
	protected $_mceTableTagName = 'xtable';

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
				$this->_mceTableTagName =  $tableTag; 
				
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
	 * Extend XenForo functions to check if a text has been parsed with the mini parser
	 * If yes, use the slave tags rules from the renderStates
	 */
	protected $_mceSlaveTags = false;
	protected $_miniParserNoHtmlspecialchars = false;

	public function renderSubTree(array $tree, array $rendererStates)
	{
		$this->_mceSlaveTags = false;
		$this->_miniParserNoHtmlspecialchars = false;
		
		if(!empty($rendererStates['miniParser']) && !empty($rendererStates['miniParserTagRules']))
		{
			$this->_mceSlaveTags = $rendererStates['miniParserTagRules'];
		}
		
		if(!empty($rendererStates['miniParserNoHtmlspecialchars']))
		{
			$this->_miniParserNoHtmlspecialchars = true;
		}
		
		return parent::renderSubTree($tree, $rendererStates);
	}

	protected function _getTagRule($tagName)
	{
		if(empty($this->_mceSlaveTags))
		{
			return parent::_getTagRule($tagName);
		}
		
		$tagName = strtolower($tagName);

		if (!empty($this->_mceSlaveTags[$tagName]) && is_array($this->_mceSlaveTags[$tagName]))
		{
			return $this->_mceSlaveTags[$tagName];
		}
		else
		{
			return false;
		}
	}

	public function replaceSmiliesInText($text, $escapeCallback = '')
	{
		if($this->_miniParserNoHtmlspecialchars)
		{
			/***
				Ugly workaround that will disable the Htmlspecialchars function
				Will only work if the smilies are not disable
				
				Disable with the XenForo wysiwyg formatter (use the mini parser formatter)
			**/
			return parent::replaceSmiliesInText($text, false);
		}

		return parent::replaceSmiliesInText($text, $escapeCallback);
	}

	/**
	 * Mce Table Master Bb Code Renderer
	 */
	public function renderTagSedoXtable(array $tag, array $rendererStates)
	{
		$tagName = $tag['tag'];
		$tagOptions = $tag['option'];
		
		$tableOptionsChecker = new Sedo_TinyQuattro_Helper_TableOptions($tagName, $tagOptions, $this->_xenOptionsMceTable);
		$options = $tableOptionsChecker->getValidOptions();
				
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
		$fallback = "<table class=\"quattro_table\" $options>$content</table>";

		if ($this->_view)
		{
			//Create and render template
			$template = 
				$this->_view->createTemplateObject(
					'quattro_bbcode_xtable', 
					array('tagContent' => $content, 'tagOptions'  => $options)
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
		$options = $tableOptionsChecker->getValidOptions();
		
		$openingHtmlTag = ($options) ? "<$tagName $options>":"<$tagName>";
		$closingHtmlTag = "</$tagName>";

		if(empty($rendererStates['miniParserFormatter']))
		{
			/***
				We're using the XenForo formatter, so the $parentClass is $this
				Disable with the XenForo wysiwyg formatter (use the mini parser formatter)
			**/
			$content = $this->renderSubTree($tag['children'], $rendererStates);
			return $this->_wrapInHtml($openingHtmlTag, $closingHtmlTag, $content);
		}
		else
		{
			/***
				We're using the formatter of the Miniparser - the "wrapInHtml" function is here public
			**/
			$content = $parentClass->renderSubTree($tag['children'], $rendererStates);
			return $parentClass->wrapInHtml($openingHtmlTag, $closingHtmlTag, $content);
		}
	}
}
//Zend_Debug::dump($parent);