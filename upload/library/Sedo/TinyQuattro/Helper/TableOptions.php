<?php
class Sedo_TinyQuattro_Helper_TableOptions
{
	protected $_formatterType;
	
	protected $_mceTableTagName;
	protected $_xenOptionsMceTable;
	
	protected $_tagName;
	protected $_tagOptions;

	protected $_mceOptionsStack;
	protected $_mceAttributes;
	protected $_mceCss;
	protected $_mceExtraClass;

	public function __construct($tagName, $tagOptions, array $xenOptionsMceTable, $formatterType = null)
	{
		//Could be used if needed
		$this->_formatterType = $formatterType;

		$this->_xenOptionsMceTable = $xenOptionsMceTable;
		$this->_mceTableTagName = $xenOptionsMceTable['tagName'];

		$this->_tagName = $tagName;
		$this->_tagOptions = $tagOptions;
	}

	public function getMceTableXenOptions($key = false)
	{
		if($key && !empty($this->_xenOptionsMceTable[$key]))
		{
			return $this->_xenOptionsMceTable[$key];
		}
		return $this->_xenOptionsMceTable;
	}

	protected function _pushMceOption($value, $type = 'isCss')
	{
		if($type == 'isCss')
		{
			$this->_mceCss[] = $value;
		}
		elseif($type == 'isAttribute')
		{
			$this->_mceAttributes[] = $value;		
		}
		elseif($type == 'isClass')
		{
			$this->_mceExtraClass[] = $value;		
		}
	}

	protected function _resetMceOptions()
	{
		$this->_mceOptionsStack = array();
		
		$this->_mceAttributes = array();
		$this->_mceCss = array();
		$this->_mceExtraClass = array();
	}

	public function getValidOptions()
	{
		$tagName = $this->_tagName;
		$options = $this->_tagOptions;

		if(empty($options))
		{
			//Needed to avoid problems with the parent class (use LIST)
			return array(null, null, null);
		}

		$options = strtolower($options); //Should be ok everywhere
		$options = explode('|', $options);

		$xTag = $this->_mceTableTagName;
		
		switch($tagName)
		{
			case $xTag: 
				list($attributes, $css, $extraClass) =  $this->_checkMceTableOptions($options);
				break;
			case 'thead':
			case 'tbody':
			case 'tfoot':
				list($attributes, $css, $extraClass) =  $this->_checkMceTheadTbodyTfootOptions($options);
				break;
			case 'colgroup':
				list($attributes, $css, $extraClass) =  $this->_checkMceColgroupOptions($options);
				break;
			case 'col':
				list($attributes, $css, $extraClass) =  $this->_checkMceColOptions($options);
				break;
			case 'caption':
				list($attributes, $css, $extraClass) =  $this->_checkMceCaptionOptions($options);
				break;
			case 'tr':
				list($attributes, $css, $extraClass) =  $this->_checkMceTrOptions($options);
				break;
			case 'th':
			case 'td':
				list($attributes, $css, $extraClass) =  $this->_checkMceThTdOptions($options);
				break;
			default:
				$extraClass = array();
				$attributes = array();
				$css = array();		
		}

		$attributes = implode(' ', $attributes);
		$attributes = (empty($attributes)) ? null : $attributes;

		$css = implode('; ', $css);
		$css = (empty($css)) ? null : $css;
		
		$extraClass = implode(' ', $extraClass);
		$extraClass = (empty($extraClass)) ? null : $extraClass;
		
		$this->_resetMceOptions();

		return array($attributes, $css, $extraClass);			
	}

	protected function _checkMceTableOptions(array $options)
	{
		$this->_resetMceOptions();

		foreach($options as $option)
		{
			if($this->_mceOptionChecker_Size($option, 'isMaster'))
			{
				continue;
			}
			elseif($this->_mceOptionChecker_Block($option))
			{
				continue;
			}
			elseif($this->_mceOptionChecker_BgColor($option))
			{
				continue;
			}
			elseif($this->_mceOptionChecker_CellpaddingSpacing($option))
			{
				continue;
			}
			elseif($this->_mceOptionChecker_Border($option))
			{
				continue;
			}
			elseif($this->_mceOptionChecker_ExtraClass($option, 'table'))
			{
				continue;
			}
		}
		
		return array($this->_mceAttributes, $this->_mceCss, $this->_mceExtraClass);
	}

	protected function _checkMceTheadTbodyTfootOptions(array $options)
	{
		$this->_resetMceOptions();
		
		foreach($options as $option)
		{
			if($this->_mceOptionChecker_TextAlign($option))
			{
				continue;
			}
			elseif($this->_mceOptionChecker_VerticalAlign($option))
			{
				continue;			
			}
		}
		
		return array($this->_mceAttributes, $this->_mceCss, $this->_mceExtraClass);	
	}

	protected function _checkMceColgroupOptions(array $options)
	{
		$this->_resetMceOptions();
		
		foreach($options as $option)
		{
			if($this->_mceOptionChecker_Size($option))
			{
				continue;
			}
			elseif($this->_mceOptionChecker_TextAlign($option))
			{
				continue;
			}
			elseif($this->_mceOptionChecker_BgColor($option))
			{
				continue;
			}
			elseif($this->_mceOptionChecker_VerticalAlign($option))
			{
				continue;			
			}
		}
		
		return array($this->_mceAttributes, $this->_mceCss, $this->_mceExtraClass);	
	}

	protected function _checkMceColOptions(array $options)
	{
		$this->_resetMceOptions();
		
		foreach($options as $option)
		{
			if($this->_mceOptionChecker_Size($option))
			{
				continue;
			}
			elseif($this->_mceOptionChecker_TextAlign($option))
			{
				continue;
			}
			elseif($this->_mceOptionChecker_VerticalAlign($option))
			{
				continue;			
			}
			elseif($this->_mceOptionChecker_Span($option))
			{
				continue;			
			}
		}
		
		return array($this->_mceAttributes, $this->_mceCss, $this->_mceExtraClass);		
	}

	protected function _checkMceCaptionOptions(array $options)
	{
		$this->_resetMceOptions();
		
		foreach($options as $option)
		{
			if($this->_mceOptionChecker_Size($option))
			{
				continue;
			}
			elseif($this->_mceOptionChecker_TextAlign($option))
			{
				continue;
			}
			elseif($this->_mceOptionChecker_BgColor($option))
			{
				continue;
			}
		}
		
		return array($this->_mceAttributes, $this->_mceCss, $this->_mceExtraClass);	
	}

	protected function _checkMceTrOptions(array $options)
	{
		$this->_resetMceOptions();
		
		foreach($options as $option)
		{
			if($this->_mceOptionChecker_Size($option))
			{
				continue;
			}
			elseif($this->_mceOptionChecker_TextAlign($option))
			{
				continue;
			}
			elseif($this->_mceOptionChecker_BgColor($option))
			{
				continue;
			}
			elseif($this->_mceOptionChecker_VerticalAlign($option))
			{
				continue;			
			}
		}
		
		return array($this->_mceAttributes, $this->_mceCss, $this->_mceExtraClass);	
	}

	protected function _checkMceThTdOptions(array $options)
	{
		$this->_resetMceOptions();
		
		foreach($options as $option)
		{
			if($this->_mceOptionChecker_Size($option))
			{
				continue;
			}
			elseif($this->_mceOptionChecker_Colspan($option))
			{
				continue;			
			}
			elseif($this->_mceOptionChecker_Rowspan($option))
			{
				continue;			
			}
			elseif($this->_mceOptionChecker_TextAlign($option))
			{
				continue;
			}
			elseif($this->_mceOptionChecker_BgColor($option))
			{
				continue;
			}
			elseif($this->_mceOptionChecker_VerticalAlign($option))
			{
				continue;			
			}
			elseif($this->_mceOptionChecker_Scope($option))
			{
				continue;			
			}
			elseif($this->_mceOptionChecker_Nowrap($option))
			{
				continue;			
			}
		}
		
		return array($this->_mceAttributes, $this->_mceCss, $this->_mceExtraClass);	
	}

	protected function _mceOptionChecker_Size($option, $tagType = null)
	{
		if(!empty($this->_mceOptionsStack['size']))
		{
			return false;
		}

		$option = trim($option);
		$regex = '#^(\d{1,3}|@)(%?)x(\d{1,3}|@)(%?)$#';

		if(preg_match($regex, $option, $match))
		{
			$width = $match[1];
			$widthUnit = (empty($match[2])) ? 'px' : '%';
			$height = $match[3];
			$heightUnit = (empty($match[4])) ? 'px' : '%';

			if($tagType == 'isMaster')
			{
				list($params_px, $params_percent) = array_values($this->getMceTableXenOptions('size'));

				if($width != '@')
				{
					if($widthUnit == 'px'){
						$maxWidth = $params_px['maxWidth'];
						$minWidth = $params_px['minWidth'];
					} else {
						$maxWidth = $params_percent['maxWidth'];
						$minWidth = $params_percent['minWidth'];
					}

					$width = ($width > $maxWidth) ? $maxWidth : ($width < $minWidth) ? $minWidth : $width;
				}

				if($height != '@')
				{
					if($heightUnit == 'px')	{
						$maxHeight = $params_px['maxHeight'];
						$minHeight = $params_px['minHeight'];
					} else {
						$maxHeight = $params_percent['maxHeight'];
						$minHeight = $params_percent['minHeight'];			
					}

					$height = ($height > $maxHeight) ? $maxHeight : ($height < $minHeight) ? $minHeight : $height;
				}
			}
			
			if($width != '@')
			{
				$this->_pushMceOption("width: {$width}{$widthUnit}");
			}

			if($height != '@')
			{			
				$this->_pushMceOption("height: {$height}{$heightUnit}");
			}
			 
			$this->_mceOptionsStack['size'] = true;
			return true;
		}

		return false;
	}

	protected function _mceOptionChecker_Block($option)
	{
		if(!empty($this->_mceOptionsStack['block']))
		{
			return false;
		}

		$option = trim($option);

		switch($option)
		{
			case "fright":
				$this->_pushMceOption("float: right");
				break;
			case "fleft":
				$this->_pushMceOption("float: left");
				break;
			case "bright":
				$this->_pushMceOption("margin-left: auto");
				$this->_pushMceOption("margin-right: 0");
				break;
			case "bcenter":
				$this->_pushMceOption("margin-left: auto");
				$this->_pushMceOption("margin-right: auto");
				break;
			case "bleft":
				$this->_pushMceOption("margin-left: 0");
				$this->_pushMceOption("margin-right: auto");
				break;

			default: return false;
		}
		
		$this->_mceOptionsStack['block'] = true;
		return true;
	}

	protected function _mceOptionChecker_CellpaddingSpacing($option)
	{
		if(
			!empty($this->_mceOptionsStack['cellspacing']) 
			&&
			!empty($this->_mceOptionsStack['cellpadding'])
		)
		{
			return false;
		}

		$option = trim($option);
		$regex = '/^(cellpadding|cellspacing):[ ]*?(\d{1,2})$/i';
		
		if(preg_match($regex, $option, $match))
		{		
			$type = $match[1];
			$value = $match[2];
			
			$params = $this->getMceTableXenOptions('cell');
			
			if($type == 'cellpadding')
			{
				$value = ($value > $params['maxCellpadding']) ? $params['maxCellpadding'] : $value;
				$unit = ($value == 0) ? '' : 'px';
				
				$this->_pushMceOption("padding: {$value}{$unit}");
				$this->_pushMceOption("cellpadding={$value}", 'isAttribute');
				$this->_mceOptionsStack['cellspacing'] = true;
			}
			else
			{
				$value = ($value > $params['maxCellspacing']) ? $params['maxCellspacing'] : $value;
				$unit = ($value == 0) ? '' : 'px';
								
				$this->_pushMceOption("border-spacing: {$value}{$unit}");
				$this->_pushMceOption("cellspacing={$value}", 'isAttribute');
				$this->_mceOptionsStack['cellpadding'] = true;
			}

			return true;
		}
		
		return false;
	}

	protected function _mceOptionChecker_BgColor($option, $isCss = true)
	{
		if(!empty($this->_mceOptionsStack['bgcolor']))
		{
			return false;
		}
		
		$option = trim($option);
		$regex = '/^bcolor:[ ]*?(rgb\(\s*\d+%?\s*,\s*\d+%?\s*,\s*\d+%?\s*\)|#[a-f0-9]{6}|#[a-f0-9]{3}|[a-z]+)$/i';

		if(preg_match($regex, $option, $match))
		{		
			$bgColor = $match[1];
			
			if($isCss)
			{
				$this->_pushMceOption("background-color: $bgColor");
			}
			else
			{
				$this->_pushMceOption("bgcolor={$bgColor}", 'isAttribute');
			}
			
			$this->_mceOptionsStack['bgcolor'] = true;
			return true;
		}
		
		return false;
	}

	protected function _mceOptionChecker_Border($option)
	{
		if(!empty($this->_mceOptionsStack['border']))
		{
			return false;
		}

		$option = trim($option);
		$regex = '/^border:[ ]*?(\d{1,2})$/i';

		if(preg_match($regex, $option, $match))
		{		
			$value = $match[1];
			$params = $this->getMceTableXenOptions('border');

			$value = ($value > $params['max']) ? $params['max'] : $value;
			$value = (int) $value;
			$unit = ($value == 0) ? '' : 'px';
							
			$this->_pushMceOption("border-width: {$value}{$unit}");
			$this->_pushMceOption("border={$value}", 'isAttribute');
			$this->_mceOptionsStack['border'] = true;
			return true;
		}
		
		return false;
	}

	protected function _mceOptionChecker_TextAlign($option)
	{
		if(!empty($this->_mceOptionsStack['textAlign']))
		{
			return false;
		}

		$option = trim($option);

		switch($option)
		{
			case "left":
				$this->_pushMceOption("text-align: left");
				break;
			case "center":
				$this->_pushMceOption("text-align: center");
				break;
			case "right":
				$this->_pushMceOption("text-align: right");
				break;

			default: return false;
		}
		
		$this->_mceOptionsStack['textAlign'] = true;
		return true;
	}

	protected function _mceOptionChecker_VerticalAlign($option)
	{
		if(!empty($this->_mceOptionsStack['verticalAlign']))
		{
			return false;
		}
		
		$option = trim($option);

		switch($option)
		{
			case "bottom":
				$this->_pushMceOption("vertical-align: bottom");
				break;
			case "middle":
				$this->_pushMceOption("vertical-align: middle");
				break;
			case "top":
				$this->_pushMceOption("vertical-align: top");
				break;
			case "baseline":
				$this->_pushMceOption("vertical-align: baseline");
				break;

			default: return false;
		}
		
		$this->_mceOptionsStack['verticalAlign'] = true;
		return true;
	}

	protected function _mceOptionChecker_Span($option)
	{
		//http://www.w3schools.com/tags/att_col_span.asp
		
		if(!empty($this->_mceOptionsStack['span']))
		{
			return false;
		}

		$option = trim($option);
		$regex = '/^span:[ ]*?(\d{1,2})$/i';

		if(preg_match($regex, $option, $match))
		{		
			$value = $match[1];
			/*Checker not really needed here, the regex is enough*/

			$this->_pushMceOption("span={$value}", 'isAttribute');
			$this->_mceOptionsStack['span'] = true;
			return true;
		}
		
		return false;
	}

	protected function _mceOptionChecker_Colspan($option)
	{
		if(!empty($this->_mceOptionsStack['colspan']))
		{
			return false;
		}

		$option = trim($option);
		$regex = '/^colspan:[ ]*?(\d{1,2})$/i';

		if(preg_match($regex, $option, $match))
		{		
			$value = $match[1];
			/*Checker not really needed here, the regex is enough*/

			$this->_pushMceOption("colspan={$value}", 'isAttribute');
			$this->_mceOptionsStack['colspan'] = true;
			return true;
		}
		
		return false;
	}
	
	protected function _mceOptionChecker_Rowspan($option)
	{
		if(!empty($this->_mceOptionsStack['rowspan']))
		{
			return false;
		}

		$option = trim($option);
		
		$regex = '/^rowspan:[ ]*?(\d{1,2})$/i';
		if(preg_match($regex, $option, $match))
		{		
			$value = $match[1];
			/*Checker not really needed here, the regex is enough*/

			$this->_pushMceOption("rowspan={$value}", 'isAttribute');
			$this->_mceOptionsStack['rowspan'] = true;
			return true;
		}
		
		return false;
	}

	protected function _mceOptionChecker_Scope($option)
	{
		if(!empty($this->_mceOptionsStack['scope']))
		{
			return false;
		}
		
		$option = trim($option);
		$regex = '/^scope:[ ]*?(\d{1,2})$/i';

		if(preg_match($regex, $option, $match))
		{		
			$value = $match[1];
			/*Checker not really needed here, the regex is enough*/

			$this->_pushMceOption("scope={$value}", 'isAttribute');
			$this->_mceOptionsStack['scope'] = true;
			return true;
		}
		
		return false;
	}

	protected function _mceOptionChecker_Nowrap($option)
	{
		if(!empty($this->_mceOptionsStack['nowrap']))
		{
			return false;
		}
		
		$option = trim($option);

		if($option == 'nowrap')
		{		
			$this->_pushMceOption("white-space: nowrap");
			$this->_pushMceOption('nowrap', 'isAttribute');
			$this->_mceOptionsStack['nowrap'] = true;
			return true;
		}
		
		return false;
	}
	
	protected function _mceOptionChecker_ExtraClass($option, $tagName)
	{
		if($tagName == 'table')
		{
			if(!empty($this->_mceOptionsStack['theme_class']))
			{
				return false;
			}		
			
			$option = trim($option);
			$regex = '/^skin\d{1,2}$/i';

			if(preg_match($regex, $option, $match))
			{	
				$class = $match[0];
				$this->_pushMceOption($class, 'isClass');
				$this->_mceOptionsStack['theme_class'] = true;
				return true;
			}
			
			return false;
		}

		return false;
	}							
}