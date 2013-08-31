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
}
//Zend_Debug::dump($abc);