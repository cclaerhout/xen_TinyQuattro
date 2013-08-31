<?php
class Sedo_TinyQuattro_BbCode_Formatter_Base extends XFCP_Sedo_TinyQuattro_BbCode_Formatter_Base
{
	public function getTags()
	{
		$parentTags = parent::getTags();
		
		if(is_array($parentTags))
		{
			if(Sedo_TinyQuattro_Helper_Quattro::canUseQuattroBbCode('bcolor'))
			{
				$parentTags += array(
					'bcolor' => array(
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
		}
		
		return $parentTags;
	}

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
}
//Zend_Debug::dump($parent);
