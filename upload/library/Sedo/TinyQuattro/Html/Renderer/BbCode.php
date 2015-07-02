<?php
class Sedo_TinyQuattro_Html_Renderer_BbCode extends XFCP_Sedo_TinyQuattro_Html_Renderer_BbCode
{
	protected static $_sedoBypass = array(
		'tab' => '{sedo:\x09}'
	);

	/**
	 * Custom MCE tags name
	 */
	protected $_quattroEnable = false;
	protected $_mceBackgroundColorTagName = 'bcolor';
	protected $_mceTableTagName = 'xtable';
	protected $_mceSubTagName = 'sub';
	protected $_mceSupTagName = 'sup';
	protected $_mceAnchorTagName = 'anchor';
	protected $_mceHrTagName = 'hr';
	protected $_mceFormatTagName = 'format';

	/**
	 * Extend the class constructor to detect the background color css property
	 * and to detect any table tag and its children tags
	 */

	public function __construct(array $options = array())
	{
		$xenOptions = XenForo_Application::get('options');
		$this->_quattroEnable = $quattroEnable = Sedo_TinyQuattro_Helper_Quattro::isEnabled();
		
		if(is_array($this->_cssHandlers) && Sedo_TinyQuattro_Helper_Quattro::canUseQuattroBbCode('bcolor'))
		{
			$this->_mceBackgroundColorTagName = Sedo_TinyQuattro_Helper_BbCodes::getQuattroBbCodeTagName('bcolor');
			$this->_cssHandlers += array('background-color' => array('$this', 'handleCssBckgndColor'));
		}
		
		if(	is_array($this->_handlers) 
			&&
			Sedo_TinyQuattro_Helper_Quattro::canUseQuattroBbCode('xtable')
			&&
			(	$xenOptions->quattro_table_all_editors_activation
				||
				$quattroEnable
			)
		)
		{

			$this->_mceTableTagName = Sedo_TinyQuattro_Helper_BbCodes::getQuattroBbCodeTagName('xtable');
			
			$this->_handlers['table'] = 	array('filterCallback' => array('$this', 'handleTagMceTable'), 'skipCss' => true);
			$this->_handlers['thead'] = 	array('filterCallback' => array('$this', 'handleTagMceTable'), 'skipCss' => true);
			$this->_handlers['tbody'] = 	array('filterCallback' => array('$this', 'handleTagMceTable'), 'skipCss' => true);
			$this->_handlers['tfoot'] = 	array('filterCallback' => array('$this', 'handleTagMceTable'), 'skipCss' => true);
			$this->_handlers['colgroup'] = 	array('filterCallback' => array('$this', 'handleTagMceTable'), 'skipCss' => true);
			$this->_handlers['col'] =	array('filterCallback' => array('$this', 'handleTagMceTable'), 'skipCss' => true);
			$this->_handlers['caption'] = 	array('filterCallback' => array('$this', 'handleTagMceTable'), 'skipCss' => true);
			$this->_handlers['th'] = 	array('filterCallback' => array('$this', 'handleTagMceTable'), 'skipCss' => true);
			$this->_handlers['tr'] = 	array('filterCallback' => array('$this', 'handleTagMceTable'), 'skipCss' => true);
			$this->_handlers['td'] = 	array('filterCallback' => array('$this', 'handleTagMceTable'), 'skipCss' => true);
		}

		if(	is_array($this->_handlers) 
			&&
			$quattroEnable
			&&
			$xenOptions->quattro_wysiwyg_quote
		)
		{
			$this->_handlers['blockquote'] = array('filterCallback' => array('$this', 'handleTagMceBlockquote'), 'skipCss' => true);
		}


		if(	is_array($this->_handlers) 
			&&
			Sedo_TinyQuattro_Helper_Quattro::canUseQuattroBbCode('sub')
			&&
			$quattroEnable
		)
		{
			$this->_mceSubTagName = Sedo_TinyQuattro_Helper_BbCodes::getQuattroBbCodeTagName('sub');
			$this->_handlers['sub'] = array('filterCallback' => array('$this', 'handleTagMceSub'), 'skipCss' => true);		
		}

		if(	is_array($this->_handlers) 
			&&
			Sedo_TinyQuattro_Helper_Quattro::canUseQuattroBbCode('sup')
			&&
			$quattroEnable
		)
		{
			$this->_mceSupTagName = Sedo_TinyQuattro_Helper_BbCodes::getQuattroBbCodeTagName('sup');
			$this->_handlers['sup'] = array('filterCallback' => array('$this', 'handleTagMceSup'), 'skipCss' => true);		
		}

		if(	is_array($this->_handlers) 
			&&
			Sedo_TinyQuattro_Helper_Quattro::canUseQuattroBbCode('hr')
			&&
			$quattroEnable
		)
		{
			$this->_mceHrTagName = Sedo_TinyQuattro_Helper_BbCodes::getQuattroBbCodeTagName('hr');
			$this->_handlers['hr'] = array('filterCallback' => array('$this', 'handleTagMceHr'), 'skipCss' => true);
		}

		if(	is_array($this->_handlers) 
			&&
			Sedo_TinyQuattro_Helper_Quattro::canUseQuattroBbCode('anchor')
			&&
			$quattroEnable
		)
		{
			$this->_mceAnchorTagName = Sedo_TinyQuattro_Helper_BbCodes::getQuattroBbCodeTagName('anchor');
			$this->_handlers['anchor'] = array('filterCallback' => array('$this', 'handleTagMceAnchor'), 'skipCss' => true);
		}

		if(	is_array($this->_handlers) 
			&&
			Sedo_TinyQuattro_Helper_Quattro::canUseQuattroBbCode('format')
			&&
			$quattroEnable
		)
		{
			$this->_mceFormatTagName = Sedo_TinyQuattro_Helper_BbCodes::getQuattroBbCodeTagName('format');
			$this->_handlers['xformat'] = array('filterCallback' => array('$this', 'handleTagMceFormat'), 'skipCss' => true);
		}
		
		return parent::__construct($options);
	}

	/**
	 * Use the preFilter & _postRender functions instead to convert globally 4 whitespaces to a tab when viewing (even occurs before the parser)
	 * Note: the conversion is also done in the datawriter (not needed but keep it for retro compatibility) of posts & conversations
	 */
	public function preFilter($html)
	{
		$html = parent::preFilter($html);
		$xenOptions = XenForo_Application::get('options');
		$extraWysiwygTags = $xenOptions->quattro_extra_bbcodes;

		if(!empty($extraWysiwygTags['xtable']))
		{
			$html = preg_replace('#(<td>)(?:[\r\n]|\r\n)#', "$1", $html); //New: Prevent to create a carriage return after the td tag
		}

		if($xenOptions->quattro_parser_fourws_to_tab)
		{
			$html = preg_replace('#(&nbsp;| ){4}#', self::$_sedoBypass['tab'], $html);
		}

		return $html;
	}

	/**
	 * Background color css property handler
	 */
	public function handleCssBckgndColor($text, $color)
	{
		$tag = $this->_mceBackgroundColorTagName;
		$tag = strtoupper($tag);
		
		return "[$tag=$color]{$text}[/$tag]";		
	}

	/**
	 * Extend the XenForo textalign handler to add the justify property
	 */
	public function handleCssTextAlign($text, $alignment)
	{
		$parentOutput = parent::handleCssTextAlign($text, $alignment);

		if(strtolower($alignment) == 'justify' && Sedo_TinyQuattro_Helper_Quattro::canUseQuattroBbCode('justify'))
		{
			$alignmentUpper = strtoupper($alignment);
			$parentOutput = "[$alignmentUpper]{$parentOutput}[/$alignmentUpper]";	
		}
		
		return $parentOutput;
	}	

	protected function _postRender($text)
	{
		do
		{
			$newText = preg_replace('#\[/(justify)\]([\r\n]*)\[\\1\]#i', '\\2', $text);
			if ($newText === null || $newText == $text)
			{
				break;
			}
			$text = $newText;
		}
		while (true);

		if(XenForo_Application::get('options')->get('quattro_parser_fourws_to_tab'))
		{
			$text = str_replace(self::$_sedoBypass['tab'], "\t", $text);
		}

		return parent::_postRender($text);		
	}

	/**
	 * Mce Subscript tag handler
	 */
	public function handleTagMceSub($text, XenForo_Html_Tag $tag)
	{
		$tag = $this->_mceSubTagName;

		return "[$tag]{$text}[/$tag]";
	}

	/**
	 * Mce Superscript tag handler
	 */
	public function handleTagMceSup($text, XenForo_Html_Tag $tag)
	{
		$tag = $this->_mceSupTagName;
		
		return "[$tag]{$text}[/$tag]";
	}

	/**
	 * Mce horizontal rule tag handler
	 */
	public function handleTagMceHr($text, XenForo_Html_Tag $tag)
	{
		$tag = $this->_mceHrTagName;
		
		return "[$tag][/$tag]";
	}

	/**
	 * Mce Blockquote tag handler
	 */
	public function handleTagMceBlockquote($text, XenForo_Html_Tag $tag)
	{
		$tagName = $tag->tagName();

		if(!$tag->attribute('data-mcequote'))
		{
			return $text;
		}
		
		$tagOption = array();
		
		if($tag->attribute('data-username'))
		{
			$tagOption[] = $tag->attribute('data-username');
		}
		
		if($tag->attribute('data-attributes'))
		{
			$attributes = htmlspecialchars($tag->attribute('data-attributes'));
			$attributes = explode(',', $attributes);
			$safe_i = 0;

			foreach($attributes as $attribute)
			{
				if(preg_match('#[a-z0-9]#i', $attribute) && $tag->attribute("data-{$attribute}"))
				{
					$value = htmlspecialchars($tag->attribute("data-{$attribute}"));
					$tagOption[] = "{$attribute}: {$value}";
				}

				if($safe_i > 10)
				{
					break;
				}
				
				$safe_i++;
			}
		}

		$tagOption = (!empty($tagOption)) ? implode($tagOption, ', ') : '';
		$openTag = ($tagOption) ? '[QUOTE="'.$tagOption.'"]' : '[QUOTE]';
		$closingTag = '[/QUOTE]';
		
		return "{$openTag}{$text}{$closingTag}";
	}


	/**
	 * Anchor (fake) tag handler
	 */
	public function handleTagMceAnchor($text, XenForo_Html_Tag $tag)
	{
		$id = $tag->attribute('id');
		$tagName = $this->_mceAnchorTagName;
				
		return "[$tagName]{$id}[/$tagName]";
	}

	/**
	 * Format (fake) tag handler
	 */
	public function handleTagMceFormat($text, XenForo_Html_Tag $tag)
	{
		$formatName = $tag->attribute('name');
		$tagName = $this->_mceFormatTagName;
				
		return "[$tagName=$formatName]{$text}[/$tagName]";
	}

	//extend <a> tag handle to get url starting with '#' and make them use the anchor tag
	public function handleTagA($text, XenForo_Html_Tag $tag)
	{
		$href = trim($tag->attribute('href'));

		if (!$href || $href[0] != '#' || !Sedo_TinyQuattro_Helper_Quattro::canUseQuattroBbCode('anchor'))
		{
			return parent::handleTagA($text, $tag);
		}
		
		$tagName = $this->_mceAnchorTagName;
		
		return "[$tagName=$href]{$text}[/$tagName]";		
	}
		
	/**
	 * Table tag handler
	 */
	public function handleTagMceTable($text, XenForo_Html_Tag $tag)
	{
		$tagName = $tag->tagName();
		$parent = $tag->parent();
		
		//Get attributes & Css
		$tagOptions = $this->_getMceTableAttributes($tag);

		$outputType = false;
		$posStart = '';
		$posEnd = '';

		switch ($tagName)
		{
			case 'table': 
				$text = "\n".$text."\n";
				$outputType = 'normalBB';
				break;
			case 'thead':
			case 'tbody': 
			case 'tfoot': 
			case 'colgroup':
			case 'caption': 
				if (!$parent || $parent->tagName() != 'table')
				{
					break;
				}
				
				if($tagName != 'caption')
				{
					$text = "\n".$text."\n";
				}
				
				$outputType = 'specialBB';
				break;
			case 'col':
				if (!$parent || $parent->tagName() != 'colgroup')
				{
					break;
				}

				$outputType = 'specialBB';
				break;
			case 'tr':
				if ( !$parent || !in_array($parent->tagName(), array('table', 'thead', 'tbody', 'tfoot')) )
				{
					break;
				}

				$text = "\n".$text."\n";
				$outputType = 'specialBB';
				break;
			case 'td':
			case 'th':
				if (!$parent || $parent->tagName() != 'tr')
				{
					break;
				}

				$posStart = "\n";
				$outputType = 'specialBB';
				break;
		}

		if($outputType == 'normalBB')
		{
			$xtableTag = $this->_mceTableTagName;
			$text = "[{$xtableTag}{$tagOptions}]{$text}[/{$xtableTag}]";
		}
		elseif($outputType == 'specialBB')
		{
			$text = $posStart."{".$tagName.$tagOptions."}".$text."{/".$tagName."}".$posEnd;
		}

		return $text;	
	}

	/**
	 * This variable will be used to stock all the direct and css attributes 
	 * for the table tags once they have been verified
	 */
	protected $_mceTableAttributes;

	/**
	 * Function to inject attributes in the above variable
	 */
	protected function _addMceTableAddAttribute($attribute, $value)
	{
		$this->_mceTableAttributes[$attribute] = $value;
	}

	/**
	 * This function will get all the attributes for the table tags and
	 * will return them with the tag option format "=attribute1|attribute2"
	 *
	 * It will also allow to make some last modifications on attributes
	 */	
	protected function _getMceTableAttributes($tag)
	{
		//Execute
		$this->_manageMceTableAttributes($tag);
		
		//Check & Patch
		if(empty($this->_mceTableAttributes))
		{
			return '';
		}

		$attributes = $this->_mceTableAttributes;

		if(!empty($attributes['float']) && !empty($attributes['balign']))
		{
			unset($attributes['balign']);
		}
		
		if(!empty($attributes['width']) || !empty($attributes['height']))
		{
			/*
			 * Uniformize the width & height under the same standard: width x height
			 * Ie: 200x100, @x100, 100x@, @ being use for "auto"
			 * This uniformization allows to have another option based on a number
			 */
			$width = '@';
			$height = '@';
			
			if(!empty($attributes['width']))
			{
				$width = str_replace('px', '', $attributes['width']);
			}
		
			if(!empty($attributes['height']))
			{
				$height = str_replace('px', '', $attributes['height']);
			}

			unset($attributes['width'], $attributes['height']);

			$attributes['size'] = "{$width}x{$height}";
		}

		//Proceed to options
		$options = implode('|', $attributes);
		return "=$options";
	}

	/**
	 * Before to get all the table tags attributes, it must be decided which
	 * attribute is allowed for which tag
	 */
	protected function _manageMceTableAttributes($tag)
	{
		$tagName = $tag->tagName();
		$css = $tag->attribute('style');
		$this->_mceTableAttributes = array(); //RAZ

		switch ($tagName)
		{
			case 'table': 
				//Skin
				$skinValue = ($tag->attribute('data-skin')) ? $tag->attribute('data-skin') : 'default';
				$this->_checkMceTableAttribute('skin', $skinValue);
				
				//Direct attributes first
				$this->_checkMceTableAttribute('align', $tag->attribute('align'), true); //for block
				$this->_checkMceTableAttribute('bgcolor', $tag->attribute('bgcolor'));
				$this->_checkMceTableAttribute('border', $tag->attribute('border')); //To do may be: border-width, but not needed for mce
				$this->_checkMceTableAttribute('cellpadding', $tag->attribute('cellpadding'));
				$this->_checkMceTableAttribute('cellspacing', $tag->attribute('cellspacing'));
				$this->_checkMceTableAttribute('width', $tag->attribute('width'));
				$this->_checkMceTableAttribute('height', $tag->attribute('height'));

				//Css after (will override some of the direct attributes)
				if ($css)
				{
					foreach ($css AS $cssRule => $cssValue)
					{
						$cssRule  = strtolower($cssRule);
						$cssValue = strtolower($cssValue);
						
						if($cssRule == 'width')
						{
							$this->_checkMceTableAttribute('css_width', $cssValue);
						}
						elseif($cssRule == 'height')
						{
							$this->_checkMceTableAttribute('css_height', $cssValue);
						}
						elseif($cssRule == 'float')
						{
							$this->_checkMceTableAttribute('css_float', $cssValue);						
						}
						elseif($cssRule == 'background-color')
						{
							$this->_checkMceTableAttribute('css_background-color', $cssValue);						
						}
						elseif($cssRule == 'border-color')
						{
							$this->_checkMceTableAttribute('css_border-color', $cssValue);						
						}						
					}
					
					$alignRules = array_merge(array('margin-left' => '', 'margin-right' => ''), $css);
					$this->_checkMceTableCssAlign($alignRules);
				}
			
				break;
			case 'thead':
			case 'tbody':
			case 'tfoot':
				$this->_checkMceTableAttribute('align', $tag->attribute('align'));
				$this->_checkMceTableAttribute('valign', $tag->attribute('valign'));

				//Css after (will override some of the direct attributes)
				if ($css)
				{
					foreach ($css AS $cssRule => $cssValue)
					{
						$cssRule  = strtolower($cssRule);
						$cssValue = strtolower($cssValue);
						
						if($cssRule == 'text-align')
						{
							$this->_checkMceTableAttribute('css_text-align', $cssValue);
						}
						elseif($cssRule == 'vertical-align')
						{
							$this->_checkMceTableAttribute('css_vertical-align', $cssValue);
						}
					}
				}
				break;
			case 'colgroup':
				$this->_checkMceTableAttribute('align', $tag->attribute('align'));
				$this->_checkMceTableAttribute('valign', $tag->attribute('valign'));
				$this->_checkMceTableAttribute('width', $tag->attribute('width'));
				$this->_checkMceTableAttribute('height', $tag->attribute('height'));

				//Css after (will override some of the direct attributes)
				if ($css)
				{
					foreach ($css AS $cssRule => $cssValue)
					{
						$cssRule  = strtolower($cssRule);
						$cssValue = strtolower($cssValue);
						
						if($cssRule == 'width')
						{
							$this->_checkMceTableAttribute('css_width', $cssValue);
						}
						elseif($cssRule == 'height')
						{
							$this->_checkMceTableAttribute('css_height', $cssValue);
						}
						elseif($cssRule == 'background-color')
						{
							$this->_checkMceTableAttribute('css_background-color', $cssValue);						
						}
						elseif($cssRule == 'border-color')
						{
							$this->_checkMceTableAttribute('css_border-color', $cssValue);						
						}						
						elseif($cssRule == 'text-align')
						{
							$this->_checkMceTableAttribute('css_text-align', $cssValue);
						}
						elseif($cssRule == 'vertical-align')
						{
							$this->_checkMceTableAttribute('css_vertical-align', $cssValue);
						}
					}
				}				
				break;
			case 'col':
				$this->_checkMceTableAttribute('align', $tag->attribute('align'));
				$this->_checkMceTableAttribute('valign', $tag->attribute('valign'));
				$this->_checkMceTableAttribute('width', $tag->attribute('width'));
				$this->_checkMceTableAttribute('height', $tag->attribute('height'));
				$this->_checkMceTableAttribute('span', $tag->attribute('span'));

				//Css after (will override some of the direct attributes)
				if ($css)
				{
					foreach ($css AS $cssRule => $cssValue)
					{
						$cssRule  = strtolower($cssRule);
						$cssValue = strtolower($cssValue);
						
						if($cssRule == 'width')
						{
							$this->_checkMceTableAttribute('css_width', $cssValue);
						}
						elseif($cssRule == 'height')
						{
							$this->_checkMceTableAttribute('css_height', $cssValue);
						}
						elseif($cssRule == 'background-color')
						{
							$this->_checkMceTableAttribute('css_background-color', $cssValue);						
						}
						elseif($cssRule == 'border-color')
						{
							$this->_checkMceTableAttribute('css_border-color', $cssValue);						
						}						
						elseif($cssRule == 'text-align')
						{
							$this->_checkMceTableAttribute('css_text-align', $cssValue);
						}
						elseif($cssRule == 'vertical-align')
						{
							$this->_checkMceTableAttribute('css_vertical-align', $cssValue);
						}
					}
				}
				break;
			case 'caption':
				$this->_checkMceTableAttribute('align', $tag->attribute('align'));

				//Css after (will override some of the direct attributes)
				if ($css)
				{
					foreach ($css AS $cssRule => $cssValue)
					{
						$cssRule  = strtolower($cssRule);
						$cssValue = strtolower($cssValue);
						
						if($cssRule == 'width')
						{
							$this->_checkMceTableAttribute('css_width', $cssValue);
						}
						elseif($cssRule == 'height')
						{
							$this->_checkMceTableAttribute('css_height', $cssValue);
						}
						elseif($cssRule == 'background-color')
						{
							$this->_checkMceTableAttribute('css_background-color', $cssValue);						
						}
						elseif($cssRule == 'border-color')
						{
							$this->_checkMceTableAttribute('css_border-color', $cssValue);						
						}						
						elseif($cssRule == 'text-align')
						{
							$this->_checkMceTableAttribute('css_text-align', $cssValue);
						}
					}
				}
				break;
			case 'tr':
				$this->_checkMceTableAttribute('align', $tag->attribute('align'));
				$this->_checkMceTableAttribute('valign', $tag->attribute('valign'));
				$this->_checkMceTableAttribute('bgcolor', $tag->attribute('bgcolor'));

				//Css after (will override some of the direct attributes)
				if ($css)
				{
					foreach ($css AS $cssRule => $cssValue)
					{
						$cssRule  = strtolower($cssRule);
						$cssValue = strtolower($cssValue);
						
						if($cssRule == 'width')
						{
							$this->_checkMceTableAttribute('css_width', $cssValue);
						}
						elseif($cssRule == 'height')
						{
							$this->_checkMceTableAttribute('css_height', $cssValue);
						}
						elseif($cssRule == 'background-color')
						{
							$this->_checkMceTableAttribute('css_background-color', $cssValue);						
						}
						elseif($cssRule == 'border-color')
						{
							$this->_checkMceTableAttribute('css_border-color', $cssValue);						
						}						
						elseif($cssRule == 'text-align')
						{
							$this->_checkMceTableAttribute('css_text-align', $cssValue);
						}
						elseif($cssRule == 'vertical-align')
						{
							$this->_checkMceTableAttribute('css_vertical-align', $cssValue);
						}
					}
				}
				
				break;
			case 'th':
			case 'td':
				$this->_checkMceTableAttribute('align', $tag->attribute('align'));
				$this->_checkMceTableAttribute('valign', $tag->attribute('valign'));
				$this->_checkMceTableAttribute('bgcolor', $tag->attribute('bgcolor'));
				$this->_checkMceTableAttribute('colspan', $tag->attribute('colspan'));
				$this->_checkMceTableAttribute('rowspan', $tag->attribute('rowspan'));
				$this->_checkMceTableAttribute('width', $tag->attribute('width'));
				$this->_checkMceTableAttribute('height', $tag->attribute('height'));
				$this->_checkMceTableAttribute('scope', $tag->attribute('scope'));
				$this->_checkMceTableAttribute('nowrap', $tag->attribute('nowrap'));

				//Css after (will override some of the direct attributes)
				if ($css)
				{
					foreach ($css AS $cssRule => $cssValue)
					{
						$cssRule  = strtolower($cssRule);
						$cssValue = strtolower($cssValue);
						
						if($cssRule == 'width')
						{
							$this->_checkMceTableAttribute('css_width', $cssValue);
						}
						elseif($cssRule == 'height')
						{
							$this->_checkMceTableAttribute('css_height', $cssValue);
						}
						elseif($cssRule == 'background-color')
						{
							$this->_checkMceTableAttribute('css_background-color', $cssValue);						
						}
						elseif($cssRule == 'border-color')
						{
							$this->_checkMceTableAttribute('css_border-color', $cssValue);						
						}						
						elseif($cssRule == 'text-align')
						{
							$this->_checkMceTableAttribute('css_text-align', $cssValue);
						}
						elseif($cssRule == 'vertical-align')
						{
							$this->_checkMceTableAttribute('css_vertical-align', $cssValue);
						}
						elseif($cssRule == 'white-space' && $cssValue == 'nowrap')
						{
							$this->_checkMceTableAttribute('css_nowrap', true);
						}
					}
				}
				
				break;
		}
	}


	/**
	 * Once the attribute has been authentified, its value must be checked
	 * Only after, it will be added to attributes variable under a specified layout
	 */
	protected function _checkMceTableAttribute($attribute, $value, $block = false)
	{
		if((empty($value) && !is_numeric($value)))
		{
			return;
		}

		$value = trim(strtolower($value));
		
		$hexaRgbColorPattern = '/^(rgb\(\s*\d+%?\s*,\s*\d+%?\s*,\s*\d+%?\s*\)|#[a-f0-9]{6}|#[a-f0-9]{3}|[a-z]+)$/i';
		$twoDigitsMaxPattern = '/^\d{1,2}$/';
		$sizePxPercenPattern = '/^\d{1,3}(px|%)?$/';

		switch ($attribute)
		{
			case 'align':
			case 'css_text-align':
				if(in_array($value, array('left','center','right','justify')))
				{
					if($block == true && $value != 'justify')
					{
						$this->_addMceTableAddAttribute('balign', "b$value");

					}
					else
					{
						$this->_addMceTableAddAttribute($attribute, $value);				
					}
				}
				break;
			case 'bgcolor':
			case 'css_background-color':
				if(preg_match($hexaRgbColorPattern, $value))
				{
					$this->_addMceTableAddAttribute('bcolor', "bcolor:$value");
				}
				break;
			case 'border':
				if(preg_match($twoDigitsMaxPattern, $value))
				{
					$this->_addMceTableAddAttribute($attribute, "border:$value");
				}
				break;
			case 'css_border-color':
				if(preg_match($hexaRgbColorPattern, $value))
				{
					$this->_addMceTableAddAttribute('bdcolor', "border-color:$value");
				}
				break;	
			case 'cellpadding':
				if(preg_match($twoDigitsMaxPattern, $value))
				{
					$this->_addMceTableAddAttribute($attribute, "cellpadding:$value");
				}
				break;
			case 'cellspacing':
				if(preg_match($twoDigitsMaxPattern, $value))
				{
					$this->_addMceTableAddAttribute($attribute, "cellspacing:$value");
				}
				break;	
			case 'colspan':
				if(preg_match($twoDigitsMaxPattern, $value))
				{
					$this->_addMceTableAddAttribute('colspan', "colspan:$value");
				}
				break;
			case 'css_float':
				if(in_array($value, array('left', 'right')))
				{
					$this->_addMceTableAddAttribute('float', "f$value");
				}
				break;
			case 'height':
			case 'css_height':
				if(preg_match($sizePxPercenPattern, $value))
				{
					$this->_addMceTableAddAttribute('height', $value);
				}
				break;
			case 'nowrap':
			case 'css_nowrap':
				if($value == true)
				{
					$this->_addMceTableAddAttribute('nowrap', 'nowrap');
				}
				break;
			case 'rowspan':
				if(preg_match($twoDigitsMaxPattern, $value))
				{
					$this->_addMceTableAddAttribute('rowspan', "rowspan:$value");
				}
				break;
			case 'scope':
				if(in_array($value, array('col', 'colgroup', 'row', 'rowgroup')))
				{
					$this->_addMceTableAddAttribute($attribute, "scope:$value");
				}
				break;
			case 'span':
				if(preg_match($twoDigitsMaxPattern, $value))
				{
					$this->_addMceTableAddAttribute($attribute, "span:$value");
				}
				break;				
			case 'valign':
			case 'css_vertical-align':
				if(in_array($value, array('bottom','middle','top','baseline')))
				{
					$this->_addMceTableAddAttribute('valign', $value);
				}
				break;
			case 'width':
			case 'css_width':
				if(preg_match($sizePxPercenPattern, $value))
				{
					$this->_addMceTableAddAttribute('width', $value);
				}
				break;
			case 'skin':
				if(preg_match('#^(skin\d{1,2}$)#', $value, $match))
				{
					$skin = $match[1];
					$this->_addMceTableAddAttribute('skin', $skin);
				}
				else
				{
					//The below function doesn't work here, returns null
					//$defaultSkin = XenForo_Template_Helper_Core::styleProperty('quattro_table_skin_default');
					//$this->_addMceTableAddAttribute('skin', $defaultSkin);				
				}
				break;
		}
	}

	/**
	 * Table with MCE doesn't seem to use all of this. 
	 * The table is on the left (default) or at the center or directly float on the left or on the right
	 * Which been the "bright" option, will not occur and the bleft one should never be used to (default)
	 */
	protected function _checkMceTableCssAlign(array $alignRules)
	{
		if ($alignRules['margin-left'] == 'auto' && $alignRules['margin-right'] == 'auto')
		{
			$this->_addMceTableAddAttribute('balign', "bcenter");
		}
		else if ($alignRules['margin-left'] == 'auto' && substr($alignRules['margin-right'], 0, 1) == '0')
		{
			$this->_addMceTableAddAttribute('balign', "bright");
		}
		else if (substr($alignRules['margin-left'], 0, 1) == '0' && $alignRules['margin-right'] == 'auto')
		{
			$this->_addMceTableAddAttribute('balign', "bleft");
		}	
	}
}
//Zend_Debug::dump($abc);