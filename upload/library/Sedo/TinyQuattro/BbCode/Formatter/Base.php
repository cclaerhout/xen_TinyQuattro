<?php
class Sedo_TinyQuattro_BbCode_Formatter_Base extends XFCP_Sedo_TinyQuattro_BbCode_Formatter_Base
{
	/**
	 * Custom MCE tags name
	 */
	protected $_mceBackgroundColorTagName = 'bcolor';
	protected $_mceTableTagName = 'xtable';
	protected $_mceSubTagName = 'sub';
	protected $_mceSupTagName = 'sup';
	protected $_mceHrTagName = 'hr';
	protected $_mceAnchorTagName = 'anchor';
	protected $_mceFormatTagName = 'format';

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
						'trimLeadingLinesAfter' => 1
					)
				);			
			}

			if(Sedo_TinyQuattro_Helper_Quattro::canUseQuattroBbCode('sub'))
			{
				$subTag = Sedo_TinyQuattro_Helper_BbCodes::getQuattroBbCodeTagName('sub');
				$this->_mceSubTagName = $subTag;
				
				$parentTags += array(
					$subTag => array(
						'hasOption' => false,
						'replace' => array('<sub class="xenmce" style="vertical-align:sub">', '</sub>')
					)
				);			
			}

			if(Sedo_TinyQuattro_Helper_Quattro::canUseQuattroBbCode('sup'))
			{
				$supTag = Sedo_TinyQuattro_Helper_BbCodes::getQuattroBbCodeTagName('sup');
				$this->_mceSupTagName = $supTag;
				
				$parentTags += array(
					$supTag => array(
						'hasOption' => false,
						'replace' => array('<sup class="xenmce" style="vertical-align:super">', '</sup>')
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
						'trimLeadingLinesAfter' => 2
					)
				);
				
				$this->_xenOptionsMceTable = Sedo_TinyQuattro_Helper_BbCodes::getMceTableXenOptions();
			}

			if(Sedo_TinyQuattro_Helper_Quattro::canUseQuattroBbCode('hr'))
			{
				$hrTag = Sedo_TinyQuattro_Helper_BbCodes::getQuattroBbCodeTagName('hr');
				$this->_mceHrTagName = $hrTag;
				
				$parentTags += array(
					$hrTag => array(
						'callback' => array($this, 'renderTagSedoHr'),
						'trimLeadingLinesAfter' => 1
					)
				);			
			}

			if(Sedo_TinyQuattro_Helper_Quattro::canUseQuattroBbCode('anchor'))
			{
				$anchorTag = Sedo_TinyQuattro_Helper_BbCodes::getQuattroBbCodeTagName('anchor');
				$this->_mceAnchorTagName = $anchorTag;
				
				$parentTags += array(
					$anchorTag => array(
						'callback' => array($this, 'renderTagSedoAnchor')
					)
				);			
			}

			if(Sedo_TinyQuattro_Helper_Quattro::canUseQuattroBbCode('format'))
			{
				$formatTag = Sedo_TinyQuattro_Helper_BbCodes::getQuattroBbCodeTagName('format');
				$this->_mceFormatTagName = $formatTag;
				
				$parentTags += array(
					$formatTag => array(
						'callback' => array($this, 'renderTagSedoFormat')
					)
				);			
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
	 * Cache emulation options
	 */
	protected $emulateAllWhiteSpace;
	protected $emulateTabs;
    
	/**
	 * Extend XenForo filterString function to add an option to recreate tabs in html
	 */
	public function filterString($string, array $rendererStates)
	{
		$string = parent::filterString($string, $rendererStates);

		if(!empty($rendererStates['stopWhiteSpaceEmulation']))
		{
			return $string;
		}

		$insideBbCode = !empty($rendererStates['tagDataStack']);
		if (!isset($this->emulateAllWhiteSpace))
		{
			$xenOptions = XenForo_Application::get('options');
			$emulateAllWhiteSpace = $xenOptions->quattro_emulate_allwhitespace_html;
			$emulateTabs = $xenOptions->quattro_emulate_tabs_html;
			
			$this->emulateAllWhiteSpace = (empty($emulateAllWhiteSpace) || $emulateAllWhiteSpace == 'no' || ($insideBbCode && $emulateAllWhiteSpace == 'limited')) ? false : true;
			$this->emulateTabs = (empty($emulateTabs) || $emulateTabs == 'no' || ($insideBbCode && $emulateTabs == 'limited')) ? false : true;
		}

		if($this->emulateAllWhiteSpace)
		{
			$string = $this->emulateAllWhiteSpace($string);
		}

		if($this->emulateTabs)
		{
			$string = $this->emulateWhiteSpaceTabs($string);
		}

		return $string;		
	}

	public function emulateAllWhiteSpace($string)
	{
		return preg_replace_callback(
			//The below regew will match whitespaces (start from 2 and exclude the last one of a line) + exclude match if a ending html tag is detected
			'#[ ]{2,}+(?<! $)(?![^<]*?>)#', 
			array($this, '_emulateAllWhiteSpaceRegexCallback'), 
			$string
		);
	}
	
	protected function _emulateAllWhiteSpaceRegexCallback($matches)
	{
		$breaksX = substr_count($matches[0], " ");
		$breakPattern = '&nbsp;'; //other possible UTF8 solutions = http://www.cs.tut.fi/~jkorpela/chars/spaces.html
		$breakOutput = str_repeat($breakPattern, $breaksX);
					
		return "{$breakOutput}";
	}

	public function emulateWhiteSpaceTabs($string)
	{
		return preg_replace_callback(
			'#[\t]+#', 
			array($this, '_emulateWhiteSpaceTabsRegexCallback'), 
			$string
		);
	}
	
	protected function _emulateWhiteSpaceTabsRegexCallback($matches)
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
	 * Mce Horizontal rule tag
	 */
	public function renderTagSedoHr(array $tag, array $rendererStates)
	{
		return "<hr />";
	}

	/**
	 * Mce Anchor tag
	 */
	protected $_quattroRequestPaths;
	protected $_quattroNoAppRegistered  = null;
	protected $_quattroJsonResponse = null;
	
	public function renderTagSedoAnchor(array $tag, array $rendererStates)
	{
		$content = htmlspecialchars(trim($this->renderSubTree($tag['children'], $rendererStates)));
		$anchorPrefix = 'message-anchor';

		/*Anchor point*/
		if(empty($tag['option']))
		{
			$anchor = strtolower($content);
			return "<a id='{$anchorPrefix}-{$anchor}' class='quattro_anchor'></a>";
		}

		/*Anchor link*/
		$anchor = strtolower(htmlspecialchars(trim($tag['option'])));
		
		if($anchor[0] != '#')
		{
			$anchor = "#{$anchorPrefix}-{$anchor}";
		}
		else
		{
			$anchor = "#{$anchorPrefix}-".substr($anchor, 1); 
		}

		if($this->_quattroNoAppRegistered === null)
		{
			$this->_quattroNoAppRegistered = (
				!XenForo_Application::isRegistered('fc')
				&& !XenForo_Application::isRegistered('session')
				&& !XenForo_Application::isRegistered('requestPaths')
			);
		}

		if($this->_quattroNoAppRegistered)
		{
			$url = "{$anchor}";		
		}
		else
		{
			//Json Response check
			if($this->_quattroJsonResponse === null)
			{
				if(!XenForo_Application::isRegistered('fc'))
				{
					$this->_quattroJsonResponse = (XenForo_Application::isRegistered('session'));
				}
				else
				{
					$fc = XenForo_Application::get('fc');
					$route = $fc->route();
					$this->_quattroJsonResponse = ($route->getResponseType() == 'json');
				}
			}
	
			$jsonResponse = $this->_quattroJsonResponse;
			
			//Request Paths Management
			if(!$this->_quattroRequestPaths)
			{
				$this->_quattroRequestPaths = XenForo_Application::get('requestPaths');
			}
		
			$requestPaths = $this->_quattroRequestPaths;
	
			if($jsonResponse)
			{
				//If the response type is json, the request paths will be the one from the json view, so try to get the data from the previous html response
				$sessionCache = XenForo_Application::getSession()->get('sedoQuattro');
				
				if(!empty($sessionCache['noJsonRequestPaths']))
				{
					$requestPaths = $sessionCache['noJsonRequestPaths'];
				}
			}

			//Get url & text
			$url = $requestPaths['fullUri'] . "{$anchor}";
		}

		$text = $content;

		/*Copy of XenForo url tag handler without the proxy feature*/
		$url = $this->_getValidUrl($url);
		if (!$url)
		{
			return $text;
		}
		else
		{
			list($class, $target, $type) = XenForo_Helper_String::getLinkClassTarget($url);
			if ($type == 'internal')
			{
				$noFollow = '';
			}
			else
			{
				$noFollow = (empty($rendererStates['noFollowDefault']) ? '' : ' rel="nofollow"');
			}

			$href = XenForo_Helper_String::censorString($url);

			$class = $class ? " class=\"$class\"" : '';
			$target = $target ? " target=\"$target\"" : '';

			return $this->_wrapInHtml(
				'<a href="' . htmlspecialchars($href) . '"' . $target . $class . $noFollow . '>',
				'</a>',
				$text
			);
		}
	}

	/**
	 * Mce Format tag
	 */
	public function renderTagSedoFormat(array $tag, array $rendererStates)
	{
		$content = $this->renderSubTree($tag['children'], $rendererStates);
		$option = trim($tag['option']);
		$headings = array('h1', 'h2', 'h3', 'h4', 'h5', 'h6');
		$customSpan = array('cust1', 'cust2', 'cust3');
		$isHeading = in_array($option, $headings);
		
		if(empty($tag['option']))
		{
			return $content;
		}
		
		//HEADING FORMAT
		if($isHeading && XenForo_Template_Helper_Core::styleProperty("quattro_sf_{$option}_text"))
		{
			return "<{$option} class='quattro_sf {$option}'>{$content}</{$option}>";
		}
		
		//CUSTOM FORMAT
		$customKey = array_search($option, $customSpan);
		
		if($customKey === false)
		{
			return $content;
		}

		$customKeyIndex = $customKey+1;

		if(XenForo_Template_Helper_Core::styleProperty("quattro_sf_custom{$customKeyIndex}_text"))
		{
			return "<span class='quattro_sf {$option}'>{$content}</span>";
		}

		return $content;
	}

	//@extend renderTag function to modify trimLeadingLinesAfter value
	public function renderTag(array $element, array $rendererStates, &$trimLeadingLines)
	{
		if(!isset($element['tag']))
		{
			return parent::renderTag($element, $rendererStates, $trimLeadingLines);
		}
		
		$tagName = $element['tag'];

		if(!empty($rendererStates['stopLineBreakConversion']) && !empty($this->forceLineBreakConversionExceptForThoseTags) && !in_array($tagName, ($this->forceLineBreakConversionExceptForThoseTags)))
		{
			$rendererStates['stopLineBreakConversion'] = false;
		}
		
		if($tagName  == $this->_mceFormatTagName)
		{
			$headings = array('h1', 'h2', 'h3', 'h4', 'h5', 'h6');
			$option = trim($element['option']);
			
			if(in_array($option, $headings))
			{
				$this->_tags[$tagName]['trimLeadingLinesAfter'] = 1;			
			}
			else
			{
				$this->_tags[$tagName]['trimLeadingLinesAfter'] = 0;
			}
		}

		return $this->_metaRenderTag(parent::renderTag($element, $rendererStates, $trimLeadingLines),  $element, $rendererStates);
	}

	/**
	*	Management of rendered tags by XenForo formatter
	*	Detect them, replace them with a placeholder, parse the mini parser tags, get back the XenForo formatted tags
	*	Purpose: avoid the nl2br php function breaks the rendered html of xen tags
	**/
	protected $_rTag = 1;
	protected $_tagHolders = array();
	protected $_mceRtagSkipForTags = array('b' => 0, 'i'=> 0, 'u'=> 0, 's'=> 0);
	public $forceLineBreakConversionExceptForThoseTags = array();	

	protected function _metaRenderTag($output,  $element, $rendererStates)
	{
		if(!empty($rendererStates['parsedTagInfoWrapper']) && empty($rendererStates['bbmPreCacheInit']))
		{
			$xenTag = strtolower($element['tag']);
		
			if(!empty($this->_mceRtagSkipForTags[$xenTag]))
			{
				return $output;
			}

			$rTag = 'rTag_'.$this->_rTag;
			$this->_rTag++;

			return "[$rTag]".$output."[/$rTag]";
		}
		else
		{
			return $output;
		}	
	}

	public function xenTagsHolderisation($content)
	{
		return preg_replace_callback('#\[(rTag_(\d+))](.*?)\[/\1](?:\r\n)?#s', array($this, '_xenTagsHolderisationRegex'), $content);
	}
	
	protected function _xenTagsHolderisationRegex($matches)
	{
		$id = $matches[2];
		$content = $matches[3];
		
		$content = $this->xenTagsHolderisation($content);
		$this->_tagHolders[$id] = $content;
					
		return "[xentagHolders_$id/]";
	}
	
	public function unXenTagsHolderisation($content, $reset = true, &$rendererStates = array())
	{
		if(!empty($rendererStates['XenTagsHolderisation']))
		{
			$reset = false;
		}
		else
		{
			$rendererStates['XenTagsHolderisation'] = true;
		}

		foreach($this->_tagHolders as $id => $replacement)
		{
			if(strpos($content, "[xentagHolders_$id/]") !== false)
			{
				unset($this->_tagHolders[$id]);				
				$replacement = $this->unXenTagsHolderisation($replacement, false);
				$content = str_replace("[xentagHolders_$id/]", $replacement, $content);
			}
		}
	
		if($reset)
		{
			$this->resetRtags();
		}
	
		return $content;
	}
	
	public function resetRtags()
	{
		$this->_tagHolders = array();
		$this->_rTag = 1;
		$this->forceLineBreakConversionExceptForThoseTags = array();
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

		$this->forceLineBreakConversionExceptForThoseTags = array($tagName);
		$rendererStates['parsedTagInfoWrapper'] = true;

		$content = $this->renderSubTree($tag['children'], $rendererStates);
		$content = $this->xenTagsHolderisation($content);

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
			'renderStates' => array()
		);

		$miniParser =  new Sedo_TinyQuattro_Helper_MiniParser($content, $slaveTags, $tag, $miniParserOptions);
		$content = $miniParser->render();

		$content = $this->unXenTagsHolderisation($content, false, $rendererStates);

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

		if(empty($content) && $content !== 0 && $content !== '0')
		{
			//Should not be needed with recent browsers but not sure with old ie
			$content="&nbsp;";			
		}
			
		return $parentClass->wrapInHtml($openingHtmlTag, $closingHtmlTag, $content);
	}
}
//Zend_Debug::dump($parent);