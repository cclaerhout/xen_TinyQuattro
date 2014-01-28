<?php
class Sedo_TinyQuattro_Model_TinyQuattro extends XenForo_Model
{
	public function getQuattroDatas()
	{
		return $this->fetchAllKeyed('
			SELECT *
			FROM  bbm_tinyquattro
			ORDER BY button_name
		', 'button_id');
	}

	public function getQuattroByTextDirection($direction)
	{
		return $this->fetchAllKeyed("
			SELECT *
			FROM  bbm_tinyquattro
			ORDER BY button_{$direction}_pos
		", 'button_id');
	}

	public function getQuattroFontsMap()
	{
		$buttons = $this->fetchAllKeyed("
			SELECT button_name, button_font
			FROM  bbm_tinyquattro
			ORDER BY button_name
		", 'button_name'); //button name are unique - so use it as array key
		
		return $buttons;
	}
	
	/***
	 * BBM Extended
	 **/
	public function checkBbmTable()
	{
		$db = $this->_getDb();
		return ($db->query("SHOW TABLES LIKE 'bbm'")->rowCount() > 0) ? true : false;
	}

	public function getBbCodesWithButtons()
	{
		if(!$this->checkBbmTable())
		{
			return array();
		}

		$buttons =  $this->fetchAllKeyed("
			SELECT tag, quattro_button_type, quattro_button_type_opt, killCmd, custCmd
			FROM bbm
			WHERE hasButton = ?
				AND active = ?
		", 'tag', array(true, true));
		

		return $buttons;
	}
	
	public function getBbmButtonsMap()
	{
		$bbmButtons = $this->getBbCodesWithButtons();
		
		$bbmButtonsReadyToUse = array();

		foreach($bbmButtons as $button)
		{
			//Button name
			if(!empty($button['killCmd']) && !empty($button['custCmd']))
			{
				$button_name = $button['custCmd'];
			}
			else
			{
				$button_name = 'bbm_';
				$button_name .= str_replace('@', 'at_', $button['tag']);
			}

			//Font name
			switch($button['quattro_button_type'])
			{
				case 'icons_xen': $button_font = 'xenforo'; break;
				case 'icons_mce': $button_font = 'tinymce'; break;
				case 'text':
				case 'manual':  $button_font = 'text'; break;
				default: $button_font = 'text';
			}
					
			//Font extra
			$font_icon = null;
			$font_text = null;
			
			if($button_font == 'text' && !empty($button['quattro_button_type_opt']))
			{
				$font_text = $button['quattro_button_type_opt'];
			}
			elseif(in_array($button_font, array('xenforo', 'tinymce')) && !empty($button['quattro_button_type_opt']))
			{
				$font_icon =  $button['quattro_button_type_opt'];
			}
			else
			{
				$font_text = $button_name;
			}
			

			$bbmButtonsReadyToUse[] = array(
				'button_name' => $button_name,
				'button_font' => $button_font,
				'extra' => array(
					'icon' => $font_icon,
					'text' => $font_text
				)
			);
		}

		return $bbmButtonsReadyToUse;
	}
}
//Zend_Debug::dump($configs);