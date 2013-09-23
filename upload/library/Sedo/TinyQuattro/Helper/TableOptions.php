<?php
class Sedo_TinyQuattro_Helper_TableOptions
{
	protected $_mceTableTagName;
	protected $_xenOptionsMceTable;
	
	protected $_tagName;
	protected $_tagOptions;

	protected $_mceOptionsStack;
	protected $_mceAttributes;
	protected $_mceCss;

	public function __construct($tagName, $tagOptions, array $xenOptionsMceTable)
	{
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
	}

	protected function _resetMceOptions()
	{
		$this->_mceAttributes = array();
		$this->_mceCss = array();
		$this->_mceOptionsStack = array();
	}

	public function getValidOptions()
	{
		$tagName = $this->_tagName;
		$options = $this->_tagOptions;

		if(empty($options))
		{
			return false;
		}

		$options = strtolower($options); //Should be ok everywhere
		$options = explode('|', $options);

		$xTag = $this->_mceTableTagName;
		
		switch($tagName)
		{
			case $xTag: 
				list($attributes, $css) =  $this->_checkMceTableOptions($options);
				break;
			case 'thead':
			case 'tbody':
			case 'tfoot':
				list($attributes, $css) =  $this->_checkMceTheadTbodyTfootOptions($options);
				break;
			case 'colgroup':
				list($attributes, $css) =  $this->_checkMceColgroupOptions($options);
				break;
			case 'col':
				list($attributes, $css) =  $this->_checkMceColOptions($options);
				break;
			case 'caption':
				list($attributes, $css) =  $this->_checkMceCaptionOptions($options);
				break;
			case 'tr':
				list($attributes, $css) =  $this->_checkMceTrOptions($options);
				break;
			case 'th':
			case 'td':
				list($attributes, $css) =  $this->_checkMceThTdOptions($options);
				break;
			default:
				$attributes = array();
				$css = array();		
		}

		$validOptions = '';
			
		if(!empty($attributes) && is_array($attributes))
		{
			$validOptions = implode(' ', $attributes);
		}

		if(!empty($css) && is_array($css))
		{
			$css = implode('; ', $css);
			$validOptions .= ' style="'.$css.'"';
		}
		
		$this->_resetMceOptions();
		
		return $validOptions;
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
		}
		
		return array($this->_mceAttributes, $this->_mceCss);
	}

	protected function _checkMceTheadTbodyTfootOptions(array $options)
	{
		$attributes = array();
		$css = array();
		
		foreach($options as $option)
		{
		
		}
		
		return array($attributes, $css);	
	}

	protected function _checkMceColgroupOptions(array $options)
	{
		$attributes = array();
		$css = array();
		
		foreach($options as $option)
		{
		
		}
		
		return array($attributes, $css);	
	}

	protected function _checkMceColOptions(array $options)
	{
		$attributes = array();
		$css = array();
		
		foreach($options as $option)
		{
		
		}
		
		return array($attributes, $css);	
	}

	protected function _checkMceCaptionOptions(array $options)
	{
		$attributes = array();
		$css = array();
		
		foreach($options as $option)
		{
		
		}
		
		return array($attributes, $css);	
	}

	protected function _checkMceTrOptions(array $options)
	{
		$attributes = array();
		$css = array();
		
		foreach($options as $option)
		{
		
		}
		
		return array($attributes, $css);	
	}

	protected function _checkMceThTdOptions(array $options)
	{
		$attributes = array();
		$css = array();
		
		foreach($options as $option)
		{
		
		}
		
		return array($attributes, $css);	
	}

	protected function _mceOptionChecker_Size($option, $tagType = null)
	{
		if(!empty($this->_mceOptionsStack['size']))
		{
			return false;
		}

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


		$regex = '/^(cellpadding|cellspacing):[ ]*?(\d{1,2})$/i';
		if(preg_match($regex, $option, $match))
		{		
			$xenOptions = XenForo_Application::get('options');
			$type = $match[1];
			$value = $match[2];
			
			$params = $this->getMceTableXenOptions('cell');
			
			if($type == 'cellpadding')
			{
				$value = ($value > $params['maxCellpadding']) ? $params['maxCellpadding'] : $value;
				
				$this->_pushMceOption("padding: $value");
				$this->_pushMceOption("cellpadding=\"$value\"", 'isAttribute');
				$this->_mceOptionsStack['cellspacing'] = true;
			}
			else
			{
				$value = ($value > $params['maxCellspacing']) ? $params['maxCellspacing'] : $value;
				
				$this->_pushMceOption("border-spacing: $value");
				$this->_pushMceOption("cellspacing=\"$value\"", 'isAttribute');
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
				$this->_pushMceOption("bgcolor=\"$bgColor\"", 'isAttribute');
			}
			
			$this->_mceOptionsStack['bgcolor'] = true;
			return true;
		}
		
		return false;
	}
}