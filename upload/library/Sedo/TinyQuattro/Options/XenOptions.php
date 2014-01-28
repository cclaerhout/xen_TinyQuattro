<?php
class Sedo_TinyQuattro_Options_XenOptions
{
      	public static function render_force_buttons(XenForo_View $view, $fieldPrefix, array $preparedOption, $canEdit)
      	{
      		$editLink = $view->createTemplateObject('option_list_option_editlink', array(
      			'preparedOption' => $preparedOption,
      			'canEditOptionDefinition' => $canEdit
      		));

		$buttons = XenForo_Model::create('Sedo_TinyQuattro_Model_TinyQuattro')->getQuattroFontsMap();
		$bbmButtons = XenForo_Model::create('Sedo_TinyQuattro_Model_TinyQuattro')->getBbmButtonsMap();

		self::prepareCheckbox($buttons, $preparedOption['option_value']);
		self::prepareCheckbox($bbmButtons, $preparedOption['option_value']);
		
		$allButtons = array_merge($buttons, $bbmButtons);
		$preparedOption['formatParams'] = array(
			'buttons' => $buttons,
			'bbmButtons' =>$bbmButtons
		);

      		return $view->createTemplateObject('option_quattro_force_button', array(
      			'fieldPrefix' => $fieldPrefix,
      			'listedFieldName' => $fieldPrefix . '_listed[]',
      			'preparedOption' => $preparedOption,
      			'formatParams' => $preparedOption['formatParams'],
      			'editLink' => $editLink
      		));
      	}
      	
      	public static function prepareCheckbox(&$buttons, $optionValue)
      	{

	        foreach($buttons as &$button)
	        {
			$buttonName = $button['button_name'];
			$buttonFont = $button['button_font'];
			$fontText = (!empty($button['extra']['text'])) ? $button['extra']['text'] : $buttonName;
			
			if($buttonFont == 'text')
			{
				$buttonTemplateCode = "<div class='button_item text'>{$fontText}</div>";
			}
			else
			{
				$classFont = ($buttonFont == 'tinymce') ? "mce-ico" : "mce-xenforo-icons";
				$buttonTemplateCode = "<div class='JsOnly button_item font $classFont mce-i-{$buttonName}' title='{$fontText}'>&nbsp;</div>";			
			}

			$button += array(
				'label' => $buttonName,
				'value' => $buttonName,
				'selected' => in_array($buttonName, $optionValue),
				'templateCode' => $buttonTemplateCode
			);
	        }
      	}
}
//Zend_Debug::dump($data);