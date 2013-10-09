<?php
require_once('manual-init.php');
use SyHolloway\MrColor\Color;

class Sedo_TinyQuattro_Helper_PlayWithColors
{
	public static function init($color, $cmd = null, $option = null, $extra = false, $extra2 = false)
	{
		$color =str_replace(' ', '', $color);
		$option = str_replace(' ', '', $option);
		$cmd = strtolower(str_replace(' ', '', $cmd));
		$extra = trim($extra);

		$color = self::checkIfColorName($color);

		//Start Mr Color
		$color = Color::load($color);

		switch($cmd)
		{
			case '_hex': return $color->hex;
			case '_red': return $color->red;
		        case '_green': return $color->green;
		        case '_blue': return $color->blue;
		        case '_hue': return $color->hue;
		        case '_saturation': return $color->saturation;
		        case '_lightness': return $color->lightness;
		        case '_alpha': return $color->alpha;
			case 'isLight': return $color->isLight();
			case 'isDark':	return $color->isDark();
			case 'hex': return $color->getHexString();
			case 'rgb': return $color->getRgbString();
			case 'rgba': return $color->getRgbaString();
			case 'hsl': return $color->getHslString();
			case 'hsla': return $color->getHslaString();
			case 'argb': return $color->getArgbHexString();
			case 'gradient': return self::createCssGradient($color, $option, $extra);
			case 'calc': return self::calc($color, $option, $extra); //not that great. Better use below functions
			case 'multiply': return self::multiply($color, $option, $extra);
			case 'screen': return self::screen($color, $option, $extra);
			case 'overlay': return self::overlay($color, $option, $extra);
			case 'softlight': return self::softlight($color, $option, $extra);
			case 'hardlight': return self::hardlight($color, $option, $extra);
			case 'difference': return self::difference($color, $option, $extra);
			case 'exclusion': return self::exclusion($color, $option, $extra);
			case 'average': return self::average($color, $option, $extra);
			case 'tint': return self::tint($color, $option, $extra);
			case 'shade': return self::shade($color, $option, $extra);
			case 'saturate': return self::saturate($color, $option, $extra);
			case 'desaturate': return self::desaturate($color, $option, $extra);
			case 'lighten': return self::lighten($color, $option, $extra);
			case 'darken': return self::darken($color, $option, $extra);
			case 'fadein': return self::fadein($color, $option, $extra);
			case 'fadeout': return self::fadeout($color, $option, $extra);
			case 'fade': return self::fade($color, $option, $extra);
			case 'spin': return self::spin($color, $option, $extra);
			case 'mix': return self::mix($color, $option, $extra, $extra2);
			case 'modify': $output = self::modify($color, $option);break;
			default: $output = self::fullOutput($color);
		}

		//$extra is used here as a debug
		if($extra == true && is_array($output))
		{
			$string = '';
			foreach($output as $k => $v)
			{
				$string.="[$k:$v] ";
			}
			
			$output = $string;
		}
		
		return $output;
	}
	
	public static $validChannels = array('red', 'green', 'blue', 'hue', 'saturation', 'lightness', 'alpha');

	public static $validOutput = array(
		'_hex' 	=> 'hex',
		'hex' 	=> 'getHexString',
		'rgb'	=> 'getRgbString',
		'rgba'	=> 'getRgbaString',
		'hsl'	=> 'getHslString',
		'hsla'	=> 'getHslaString',
		'argb'	=> 'getArgbString'
	);
	
	public static $channelMax = array(
		'red' => 255,
		'green' => 255,
		'blue'=> 255,
		'alpha' => 1,
		'hue' => 360, //not 239..
		'saturation' => 1,//do not put 240 here... I don't know why
		'lightness' => 1
	);

	public static function clamp($int, $channel, $min = 0, $max = 255)
	{
		if(array_search($channel, self::$validChannels) !== false)
		{
			$min = 0;
			$max = self::$channelMax[$channel];
		}
		
		if(!is_numeric($int))
		{
			$int = (int) $int;
		}

       	        if($int < $min)
       	        {
               	        $int = $min;
               	}
                	
                if($int > $max)
                {
       	                $int = $max;
       	        }
        	                
		return $int;
        }

	public static function fullOutput($color)
	{
		return array(
			'_hex' => $color->hex,
			'_red' => $color->red,
			'_green' => $color->green,
			'_blue' => $color->blue,
			'_hue' => $color->hue,
			'_saturation' => $color->saturation,
			'_lightness' => $color->lightness,
			'_alpha' => $color->alpha,
			'isLight' => $color->isLight(),
			'isDark' => $color->isDark(),
			'hex' => $color->getHexString(),
			'rgb' => $color->getRgbString(),
			'rgba' => $color->getRgbaString(),
			'hsl' => $color->getHslString(),
			'hsla' => $color->getHslaString(),
			'argb' => $color->getArgbHexString()
		);	
	}

	public static function checkIfColorName($color)
	{
		$colorNames = XenForo_Helper_Color::$colors;
		if(isset($colorNames[$color]))
		{
			$color = $colorNames[$color];
		}
		
		return $color;
	}

	public static function loadOptionAsSecondColor($color, $option)
	{
		if($option instanceof Color)
		{
			$color1 =  $color;
			$color2 = $option;
		}
		else
		{
			$color1 = $color->copy();
			$color2 = self::checkIfColorName($option);
			$color2 = Color::load($color2);
		}
		
		return array($color1, $color2);
	}
	
	public static function modify($color, $option)
	{
		$option = strtolower(str_replace(' ', '', $option));
		$options = explode(';', $option);
		$output = null;

		foreach($options as $data)
		{
			/* Check if an output has been set */
			if(isset(self::$validOutput[$data]))
			{
				$output = $data;
				continue;
			}

			/* Search for ":" */		
			$pos = strpos($data, ':');

			if($pos === false)
			{
				continue;
			}
			
			/* Search for command */
			$channel = substr($data, 0, $pos);

			$key = array_search($channel, self::$validChannels);
			if( $key === false)
			{
				continue;
			}

			if(!isset($data[$pos+1]))
			{
				continue;
			}

			/* Manage operator & value */
			if(in_array($data[$pos+1], array('+', '-')))
			{
				$operator = $data[$pos+1];
				$value = (isset($data[$pos+2])) ? substr($data, $pos+2) : 1;
			}
			else
			{
				$operator = null;
				$value = substr($data, $pos+1);			
			}

			/* Proceed */
			$currentValue = $color->$channel;
			switch($operator)
			{
				case '+':
					$color->$channel = self::clamp($currentValue + $value, $channel);
					continue;
				case '-':
					$color->$channel = self::clamp($currentValue - $value, $channel);
					continue;
				default:
					$color->$channel = self::clamp($value, $channel);
			}
		}

		if($output == '_hex')
		{
			return $color->hex;
		}
		elseif(!empty($output))
		{
			$method = self::$validOutput[$output];
			return $color->$method();
		}
		else
		{
			return self::fullOutput($color);
		
		}
	}	
	
	public static function createCssGradient($color, $option, $fallback)
	{
		$fallback = ($fallback) ? $fallback : $color->getRgbString();
		$mode = (preg_match('#^[^\#]\d{1,3}$#', $option)) ? 'singleColor' : 'dualColor';
		
		if($mode == 'singleColor')
		{
			$amount = (int)$option;
			
			if ($color->isLight())
		        {
        			$lightColor = $color->getRgbaString();
        			$lightColorIE = $color->getArgbHexString();
				
				$color->darken($amount);
				$darkColorIE = $color->getArgbHexString();
				$darkColor = $color->getRgbaString();
			}
			else
			{
				$darkColor = $color->getRgbaString();
				$darkColorIE = $color->getArgbHexString();
				
				$color->lighten($amount);
        			$lightColorIE = $color->getArgbHexString();
				$lightColor = $color->getRgbaString();
			}
	        }
	        else
	        {
	        	$firstColor = $color->copy();
			$secondColor = $option;

       			$lightColor = $firstColor->getRgbaString();
       			$lightColorIE = $firstColor->getArgbHexString();

			$secondColor = self::checkIfColorName($option);
			$secondColor = Color::load($secondColor);
			$darkColor = $secondColor->getRgbaString();
			$darkColorIE = $secondColor->getArgbHexString();
		}

		$css = "background-color:{$fallback};\n";
		$css.= "background-image:-moz-linear-gradient(top,{$lightColor},{$darkColor});\n";
		$css.= "background-image:-webkit-gradient(linear,0 0,0 100%,from({$lightColor}),to({$darkColor}));\n";
		$css.= "background-image:-webkit-linear-gradient(top,{$lightColor},{$darkColor});\n";
	        $css.= "background-image:-o-linear-gradient(top,{$lightColor},{$darkColor});\n";
	        $css.= "background-image:linear-gradient(to bottom,{$lightColor},{$darkColor});\n";	        
	        $css.= "filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='{$lightColorIE}',endColorstr='{$darkColorIE}',GradientType=0);\n";
	    	$css.= "background-repeat:repeat-x;\n";

		return $css;	        
	}
	
	public static function calc($color, $option, $output)
	{
		$output = (isset(self::$validOutput[$output])) ? $output : 'hex';

		$operator = '+';
		if(in_array($option[0], array('+', '-', '|')))
		{
			$operator = $option[0];
			$option = substr($option, 1);
		}

		list($color1, $color2) = self::loadOptionAsSecondColor($color, $option);

		switch($operator)
		{
			case '|': 	$red = (($color->red+$color2->red)/2);
					$green = (($color->green+$color2->green)/2);
					$blue = (($color->blue+$color2->blue)/2);
					$alpha = (($color->alpha+$color2->alpha)/2);
					
					$outputColor = Color::create(array(
						'red' => $red,
						'green' => $green,
						'blue' => $blue,
						'alpha'=> $alpha
					));

					break;
			case '-': 	$newColor = dechex(hexdec($color1->hex) - hexdec($color2->hex)); 
					break;
			default:	$newColor = dechex(hexdec($color1->hex) + hexdec($color2->hex));
		}

		if(empty($outputColor))
		{
			$outputColor = Color::load($newColor);
		}

		if($output == '_hex')
		{
			return $outputColor->hex;
		}
		elseif(!empty($output))
		{
			$method = self::$validOutput[$output];
			return $outputColor->$method();
		}
	}
	


	/* Functions taken from Less Script (Converted from JS to PHP)
	 * Url: http://lesscss.org
	 *
	 *  The above functions are copyrighted
	 *  Copyright (c) 2006-2009 Hampton Catlin, Nathan Weizenbaum, and Chris Eppstein
    	 *  Url: http://sass-lang.com
	 **/

	public static function mix($color, $option, $weight, $output)
	{
		list($color1, $color2) = self::loadOptionAsSecondColor($color, $option);
		$weight = (!$weight) ? 50 : $weight;


		if($output != 'Color')
		{
			$output = (isset(self::$validOutput[$output])) ? $output : 'hex';
		}
		
		$p = $weight / 100;
		$w = $p * 2 - 1;
		$a = $color1->alpha - $color2->alpha;

		$w1 = ((($w * $a == -1) ? w : ($w + $a) / (1 + $w * $a)) + 1) / 2.0;
		$w2 = 1 - $w1;
		
		$red = 	$color->red*$w1 + $color2->red*$w2;
		$green = $color->green*$w1 + $color2->green*$w2;
		$blue = $color->blue*$w1 + $color2->blue*$w2;
		$alpha = $color->alpha*$p + $color2->alpha*(1-$p);

		$outputColor = Color::create(array(
			'red' => $red,
			'green' => $green,
			'blue' => $blue,
			'alpha'=> $alpha
		));

		if($output == '_hex')
		{
			return $outputColor->hex;
		}
		elseif($output == 'Color')
		{
			return $outputColor;
		}
		elseif(!empty($output))
		{
			$method = self::$validOutput[$output];
			return $outputColor->$method();
		}
	}

	public static function multiply($color, $option, $output)
	{
		return self::_blendingRgb($color, $option, $output, 'multiply');
	}

	public static function screen($color, $option, $output)
	{
		return self::_blendingRgb($color, $option, $output, 'screen');
	}

	public static function overlay($color, $option, $output)
	{
		return self::_blendingRgb($color, $option, $output, 'overlay');
	}

	public static function softlight($color, $option, $output)
	{
		return self::_blendingRgb($color, $option, $output, 'softlight');
	}

	public static function hardlight($color, $option, $output)
	{
		return self::_blendingRgb($color, $option, $output, 'hardlight');
	}

	public static function difference($color, $option, $output)
	{
		return self::_blendingRgb($color, $option, $output, 'difference');
	}

	public static function exclusion($color, $option, $output)
	{
		return self::_blendingRgb($color, $option, $output, 'exclusion');
	}

	public static function average($color, $option, $output)
	{
		return self::_blendingRgb($color, $option, $output, 'average');
	}

	public static function tint($color, $weight, $output)
	{
		$firstColor = Color::create(array(
			'red' => '255',
			'green' => '255',
			'blue' => '255'
		));
		
		return self::mix($firstColor, $color, $weight, $output);
	}

	public static function shade($color, $weight, $output)
	{
		$firstColor = Color::create(array(
			'red' => '0',
			'green' => '0',
			'blue' => '0'
		));
		
		return self::mix($firstColor, $color, $weight, $output);
	}	

	public static function saturate($color, $amount, $output)
	{
		return self::_blendingHsl($color, $amount, $output, 'saturate');
	}

	public static function desaturate($color, $amount, $output)
	{
		return self::_blendingHsl($color, $amount, $output, 'desaturate');
	}

	public static function lighten($color, $amount, $output)
	{
		return self::_blendingHsl($color, $amount, $output, 'lighten');
	}

	public static function darken($color, $amount, $output)
	{
		return self::_blendingHsl($color, $amount, $output, 'darken');
	}

	public static function fadein($color, $amount, $output)
	{
		return self::_blendingHsl($color, $amount, $output, 'fadein');
	}

	public static function fadeout($color, $amount, $output)
	{
		return self::_blendingHsl($color, $amount, $output, 'fadeout');
	}

	public static function fade($color, $amount, $output)
	{
		return self::_blendingHsl($color, $amount, $output, 'fade');
	}

	public static function spin($color, $amount, $output)
	{
		return self::_blendingHsl($color, $amount, $output, 'spin');
	}

	protected static function _blendingRgb($color, $option, $output, $blendingMode)
	{
		list($color1, $color2) = self::loadOptionAsSecondColor($color, $option);
		$output = (isset(self::$validOutput[$output])) ? $output : 'hex';

		switch($blendingMode)
		{
			case 'multiply':
				$red = 	$color->red * $color2->red / 255;
				$green = $color->green * $color2->green / 255;
				$blue = $color->blue * $color2->blue / 255;
				break;
			case 'screen':
			        $red = 255 - (255 - $color1->red) * (255 - $color2->red) / 255;
			        $green = 255 - (255 - $color1->green) * (255 - $color2->green) / 255;
			        $blue = 255 - (255 - $color1->blue) * (255 - $color2->blue) / 255;
				break;
			case 'overlay':
			        $red = 	($color1->red < 128) ? 2 * $color1->red * $color2->red / 255 
			        	: 255 - 2 * (255 - $color1->red) * (255 - $color2->red) / 255;
			        $green = ($color1->green < 128) ? 2 * $color1->green * $color2->green / 255
			        	: 255 - 2 * (255 - $color1->green) * (255 - $color2->green) / 255;
			        $blue = ($color1->blue < 128) ? 2 * $color1->blue * $color2->blue / 255
			        	: 255 - 2 * (255 - $color1->blue) * (255 - $color2->blue) / 255;
				break;
			case 'softlight':
			        $t = $color2->red * $color1->red / 255;
			        $red = $t + $color1->red * (255 - (255 - $color1->red) * (255 - $color2->red) / 255 - $t) / 255;
			        $t = $color2->green * $color1->green / 255;
			        $green = $t + $color1->green * (255 - (255 - $color1->green) * (255 - $color2->green) / 255 - $t) / 255;
			        $t = $color2->blue * $color1->blue / 255;
			        $blue = $t + $color1->blue * (255 - (255 - $color1->blue) * (255 - $color2->blue) / 255 - $t) / 255;
				break;
			case 'hardlight':
			        $red = ($color2->red < 128) ? 2 * $color2->red * $color1->red / 255
			        	: 255 - 2 * (255 - $color2->red) * (255 - $color1->red) / 255;
			        $green = ($color2->green < 128) ? 2 * $color2->green * $color1->green / 255
			        	: 255 - 2 * (255 - $color2->green) * (255 - $color1->green) / 255;
			        $blue = ($color2->blue < 128) ? 2 * $color2->blue * $color1->blue / 255
			        	: 255 - 2 * (255 - $color2->blue) * (255 - $color1->blue) / 255;
				break;
			case 'difference':
			        $red = abs($color1->red - $color2->red);
			        $green = abs($color1->green - $color2->green);
			        $blue = abs($color1->blue - $color2->blue);
				break;
			case 'exclusion':
			        $red = $color1->red + $color2->red * (255 - $color1->red - $color1->red) / 255;
			        $green = $color1->green + $color2->green * (255 - $color1->green - $color1->green) / 255;
			        $blue = $color1->blue + $color2->blue * (255 - $color1->blue - $color1->blue) / 255;
				break;
			case 'average':
			        $red = ($color1->red + $color2->red) / 2;
			        $green = ($color1->green + $color2->green) / 2;
			        $blue = ($color1->blue + $color2->blue) / 2;
				break;
		}

		$outputColor = Color::create(array(
			'red' => $red,
			'green' => $green,
			'blue' => $blue
		));

		if($output == '_hex')
		{
			return $outputColor->hex;
		}
		elseif($output)
		{
			$method = self::$validOutput[$output];
			return $outputColor->$method();
		}
	}

	protected static function _blendingHsl($color, $amount, $output, $blendingMode)
	{
		$output = (isset(self::$validOutput[$output])) ? $output : 'hex';
		
		switch($blendingMode)
		{
			case 'saturate':
				$amount = intval($amount) / 100;
				$color->saturation = self::clamp($color->saturation + $amount, 'saturation');
			break;
			case 'desaturate':
				$amount = intval($amount) / 100;
				$color->saturation = self::clamp($color->saturation - $amount, 'saturation');
			break;
			case 'lighten':
				$color->lighten($amount);
			break;
			case 'darken':
				$color->darken($amount);
			break;
			case 'fadein':
				$amount = intval($amount) / 100;
				if(!in_array($output, array('rgba', 'hsla')))
				{
					$output = 'rgba';
				}
							
			        $color->alpha = self::clamp($color->alpha + $amount, 'alpha');;
			break;
			case 'fadeout':
				$amount = intval($amount) / 100;
				if(!in_array($output, array('rgba', 'hsla')))
				{
					$output = 'rgba';
				}

			        $color->alpha = self::clamp($color->alpha - $amount, 'alpha');;
			break;
			case 'fade':
				$amount = intval($amount) / 100;
				if(!in_array($output, array('rgba', 'hsla')))
				{
					$output = 'rgba';
				}
				
			        $color->alpha = self::clamp($amount, 'alpha');
			break;
			case 'spin':
			        $hue = ($color->hue + $amount) % 360;
				$new = $hue < 0 ? 360 + $hue : $hue;
				$color->hue = self::clamp($color->hue + $amount, 'hue');
			break;
		}

		if($output == '_hex')
		{
			return $color->hex;
		}
		elseif($output)
		{
			$method = self::$validOutput[$output];
			return $color->$method();
		}
	}
}