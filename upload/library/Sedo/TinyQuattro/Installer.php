<?php
class Sedo_TinyQuattro_Installer
{
	public static function install($addon)
	{
		$db = XenForo_Application::get('db');
		
		if(empty($addon) || $addon['version_id'] < 3)
		{
			//Force uninstall on fresh install
			self::uninstall();

			$db->query("CREATE TABLE IF NOT EXISTS bbm_tinyquattro (             
			        		button_id INT NOT NULL AUTO_INCREMENT,
      						button_cat TINYTEXT NOT NULL,
      						button_name TEXT NOT NULL,
      						button_ltr_pos INT(11) NOT NULL DEFAULT '999',
      						button_rtl_pos INT(11) NOT NULL DEFAULT '999',
      						button_line INT(11) NOT NULL DEFAULT '3',
      						button_separator INT(1) NOT NULL DEFAULT '0',
      						button_font TINYTEXT NOT NULL,
						PRIMARY KEY (button_id)
					)
		                	ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;"
			);

			$defaultButtons = array(
				'removeformat' 		=> array(10, 10, 1, 0, 'tinymce'),
				'undo' 			=> array(20, 30, 1, 0, 'tinymce'),
				'redo' 			=> array(30, 20, 1, 0, 'tinymce'),
				'restoredraft' 		=> array(40, 40, 1, 1, 'tinymce'),
				'xen_fontfamily' 	=> array(50, 50, 1, 0, 'text'),
				'xen_fontsize'		=> array(60, 60, 1, 0, 'text'),
				'forecolor' 		=> array(70, 70, 1, 0, 'tinymce'),
				'backcolor'		=> array(80, 80, 1, 1, 'tinymce'),
				'bold'			=> array(90, 90, 1, 0, 'tinymce'),
				'italic'		=> array(100, 100, 1, 0, 'tinymce'),
				'underline'		=> array(110, 110, 1, 0, 'tinymce'),
				'strikethrough'		=> array(120, 120, 1, 1, 'tinymce'),
				'alignleft'		=> array(130, 160, 1, 0, 'tinymce'),
				'aligncenter'		=> array(140, 150, 1, 0, 'tinymce'),
				'alignright'		=> array(150, 140, 1, 0, 'tinymce'),
				'alignjustify'		=> array(160, 130, 1, 1, 'tinymce'),
				'fullscreen'		=> array(170, 170, 1, 0, 'tinymce'),
				'xen_switch'		=> array(180, 180, 1, 0, 'xenforo'),
				'bullist'		=> array(190, 190, 2, 0, 'tinymce'),
				'numlist'		=> array(200, 200, 2, 0, 'tinymce'),
				'outdent'		=> array(210, 220, 2, 0, 'tinymce'),
				'indent'		=> array(220, 210, 2, 1, 'tinymce'),
				'xen_image'		=> array(230, 230, 2, 0, 'tinymce'),
				'xen_media' 		=> array(240, 240, 2, 0, 'tinymce'),
				'xen_link'		=> array(250, 250, 2, 0, 'tinymce'),
				'xen_unlink'		=> array(260, 260, 2, 1, 'tinymce'),
				'xen_smilies'		=> array(270, 210, 2, 0, 'tinymce'),
				'charmap'		=> array(280, 280, 2, 0, 'tinymce'),
				'xen_nonbreaking'	=> array(290, 290, 2, 0, 'tinymce'),
				'xen_code'		=> array(300, 300, 2, 0, 'xenforo'),
				'xen_quote'		=> array(310, 310, 2, 0, 'xenforo')
			);

			self::insertButtons($defaultButtons, 'default');
		}

		if(empty($addon) || $addon['version_id'] < 4)
		{
			$buttonsToReset = array(
				'xen_smilies' => array(270, 270, 2, 0, 'xenforo') //error RTL
			);
			self::resetButtons($buttonsToReset, 'default');


			$newButtons = array(
				'xen_smilies_picker' => array(271, 271, 2, 0, 'xenforo')
			);
			self::insertButtons($newButtons, 'default');
		}

		if(empty($addon) || $addon['version_id'] < 5)
		{
			self::addColumnIfNotExist($db, 'xf_user_option', 'quattro_rte_mobile', 'TINYINT UNSIGNED NOT NULL DEFAULT 1');
		}
		
		if(empty($addon) || $addon['version_id'] < 7)
		{
			$newButtons = array(
				'pastetext' => array(35, 35, 1, 0, 'tinymce')
			);
			self::insertButtons($newButtons, 'default');
		}

		if(empty($addon) || $addon['version_id'] < 18)
		{
			$newButtons = array(
				'table' => array(248, 248, 2, 0, 'tinymce')
			);
			self::insertButtons($newButtons, 'default');
		}

		if(empty($addon) || $addon['version_id'] < 48)
		{
			$newButtons = array(
				'subscript' => array(225, 225, 2, 0, 'tinymce'),
				'superscript' => array(226, 226, 2, 1, 'tinymce')
			);
			self::insertButtons($newButtons, 'default');
		}

		if(empty($addon) || $addon['version_id'] < 50)
		{
			$newButtons = array(
				'xen_spoiler' => array(320, 320, 2, 0, 'xenforo')
			);
			self::insertButtons($newButtons, 'default');
		}

		if(empty($addon) || $addon['version_id'] < 64)
		{
			//Sedo_TinyQuattro_Helper_Smilie::cacheMceSmiliesByCategory();
		}

		if(empty($addon) || $addon['version_id'] < 70)
		{
			$newButtons = array(
				'anchor' => array(262, 262, 2, 1, 'tinymce'),
				'hr' => array(285, 285, 2, 0, 'tinymce')
			);
			self::insertButtons($newButtons, 'default');

			$buttonsToReset = array(
				'xen_smilies' => array(270, 270, 2, 0, 'tinymce'), //font error
				'xen_unlink' => array(260, 260, 2, 0, 'tinymce') //remove separator (cf anchor)
			);
			self::resetButtons($buttonsToReset, 'default');
		}

		if(empty($addon) || $addon['version_id'] < 72)
		{
			$newButtons = array(
				'styleselect' => array(85, 85, 1, 1, 'text')
			);
			self::insertButtons($newButtons, 'default');
		}
		
		Sedo_TinyQuattro_Helper_Smilie::cacheMceSmiliesByCategory();									
	}

	public static function insertButtons(array $buttons, $category, $reset = false)
	{
		$db = XenForo_Application::get('db');
	
		foreach($buttons as $buttonName => $buttonDatas)
		{
			$ltrPosition = 	(isset($buttonDatas[0])) ? $buttonDatas[0] : 999;
			$rtlPosition = 	(isset($buttonDatas[1])) ? $buttonDatas[1] : 999;
			$line = 	(isset($buttonDatas[2])) ? $buttonDatas[2] : 3;
			$separator = 	(isset($buttonDatas[3])) ? $buttonDatas[3] : 0;
			$font = 	(isset($buttonDatas[4])) ? $buttonDatas[4] : 'tinymce';
			
			$bakeButtons[] = "('$category', '$buttonName', '$ltrPosition', '$rtlPosition', '$line', '$separator', '$font')";
			
			if($reset == true)
			{
				$db->query("DELETE FROM bbm_tinyquattro WHERE button_name = '$buttonName'");
			}
		}
		
		$buttonsSQL = implode(',', $bakeButtons);
		
		$db->query("INSERT INTO bbm_tinyquattro (button_cat, button_name, button_ltr_pos, button_rtl_pos, button_line, button_separator, button_font) VALUES $buttonsSQL;");
	}

	public static function resetButtons(array $buttons, $category)
	{
		self::insertButtons($buttons, $category, true);
	}
	
	public static function uninstall()
	{
		$db = XenForo_Application::get('db');
		$db->query("DROP TABLE IF EXISTS bbm_tinyquattro");

		if ($db->fetchRow("SHOW COLUMNS FROM xf_user_option WHERE Field = ?", 'quattro_rte_mobile'))
		{
			$db->query("ALTER TABLE xf_user_option DROP quattro_rte_mobile");
		}
		
		XenForo_Application::setSimpleCacheData('mce_smilie', false);
	}
	
	public static function addColumnIfNotExist($db, $table, $field, $attr)
	{
		if ($db->fetchRow("SHOW COLUMNS FROM $table WHERE Field = ?", $field))
		{
			return;
		}
	 
		return $db->query("ALTER TABLE $table ADD $field $attr");
	}
	
	public static function changeColumnValueIfExist($db, $table, $field, $attr)
	{
		if (!$db->fetchRow("SHOW COLUMNS FROM $table WHERE Field = ?", $field))
		{
			return;
		}

		return $db->query("ALTER TABLE $table CHANGE $field $field $attr");
	}
}