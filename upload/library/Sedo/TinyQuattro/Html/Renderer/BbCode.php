<?php
class Sedo_TinyQuattro_Html_Renderer_BbCode extends XFCP_Sedo_TinyQuattro_Html_Renderer_BbCode
{
	public function __construct(array $options = array())
	{
		if(is_array($this->_cssHandlers) && Sedo_TinyQuattro_Helper_Quattro::canUseQuattroBbCode('bcolor'))
		{
			$this->_cssHandlers += array('background-color' => array('$this', 'handleCssBckgndColor'));
		}

		return parent::__construct($options); 
		
		//WIP
		
		if(	is_array($this->_handlers) 
			&& Sedo_TinyQuattro_Helper_Quattro::isEnabled()) //only if TinyQuattro in enabled - reason: if other table bbcodes exist don't mess with them
			//&& Sedo_TinyQuattro_Helper_Quattro::canUseQuattroBbCode('table')
		{
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
		
		return parent::__construct($options);
	}

	public function handleCssBckgndColor($text, $color)
	{
		return "[BCOLOR=$color]{$text}[/BCOLOR]";		
	}

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
			$text = '[xtable'.$tagOptions.']'.$text.'[/xtable]';
		}
		elseif($outputType == 'specialBB')
		{
			$text = $posStart."{".$tagName.$tagOptions."}".$text."{/".$tagName."}".$posEnd;
		}

		return $text;	
	}

	protected $_mceTableAttributes; //CSS+ATTRIBUTES
	
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
		
		if(!empty($attributes['width']))
		{
			$attributes['width'] = str_replace('px', '', $attributes['width']);
		}
		
		if(!empty($attributes['height']))
		{
			$attributes['height'] = str_replace('px', '', $attributes['height']);
		}
		
		if(!empty($attributes['width']) && !empty($attributes['height']))
		{
			$attributes['size'] = $attributes['width'].'x'.$attributes['height'];
			unset($attributes['width'], $attributes['height']);
		}
		
		//Proceed to options
		$options = implode('|', $attributes);
		return "=$options";
	}

	protected function _manageMceTableAttributes($tag)
	{
		$tagName = $tag->tagName();
		$css = $tag->attribute('style');
		$this->_mceTableAttributes = array(); //RAZ

		switch ($tagName)
		{
			case 'table': 
				//Direct attributes first
				$this->_checkMceTableAttribute('align', $tag->attribute('align'), true);
				$this->_checkMceTableAttribute('bgcolor', $tag->attribute('bgcolor'));
				$this->_checkMceTableAttribute('border', $tag->attribute('border'));
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
						elseif($cssRule == 'text-align')
						{
							$this->_checkMceTableAttribute('css_text-align', $cssValue);
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
				$this->_checkMceTableAttribute('width', $tag->attribute('width'));
				$this->_checkMceTableAttribute('height', $tag->attribute('height'));
				$this->_checkMceTableAttribute('nowrap', $tag->attribute('nowrap'));
				$this->_checkMceTableAttribute('rowspan', $tag->attribute('rowspan'));
				$this->_checkMceTableAttribute('scope', $tag->attribute('scope'));

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
						elseif($cssRule == 'text-align')
						{
							$this->_checkMceTableAttribute('css_text-align', $cssValue);
						}						
					}
				}
				
				break;
		}
	}

	protected function _addMceTableAddAttribute($attribute, $value)
	{
		$this->_mceTableAttributes[$attribute] = $value;
	}
	
	protected function _checkMceTableAttribute($attribute, $value, $block = false)
	{
		if(empty($value))
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
				$value = (int) $value;
				if( $value > 0 && $value < 10 )
				{
					$this->_addMceTableAddAttribute($attribute, "border:$value");
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
				if($value == 'nowrap')
				{
					$this->_addMceTableAddAttribute('nowrap', "nowrap");
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
					$this->_addMceTableAddAttribute($attribute, $value);
				}
				break;
			case 'span':
				if(preg_match($twoDigitsMaxPattern, $value))
				{
					$this->_addMceTableAddAttribute($attribute, "span:$value");
				}
				break;				
			case 'valign':
				if(in_array($value, array('bottom','middle','top','baseline')))
				{
					$this->_addMceTableAddAttribute($attribute, $value);
				}
				break;
			case 'width':
			case 'css_width':
				if(preg_match($sizePxPercenPattern, $value))
				{
					$this->_addMceTableAddAttribute('width', $value);
				}
				break;
		}
	}

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