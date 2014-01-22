<?php

class Sedo_TinyQuattro_Listener_Templates_Preloader
{
	public static function preloader($templateName, array &$params, XenForo_Template_Abstract $template)
	{
		switch ($templateName)
		{
		   	case 'PAGE_CONTAINER':
   				$visitor = XenForo_Visitor::getInstance();

				if(!$visitor->enable_rte)
				{
					break;
				}
				
				$params += array(
					'quattroIntegration' => (self::_checkQuattroPermissions() && self::_quattroIntegration()) //quattro integration js (for 1.2)
				);
		   	break;
		   	case 'editor':
   				$visitor = XenForo_Visitor::getInstance();
   				$xenOptions = XenForo_Application::get('options');

				//NoAttachment editors detection
				if(	isset($params['editorOptions']) 
					&& isset($params['editorOptions']['extraClass'])
					&& strpos('NoAttachment', $params['editorOptions']['extraClass']) !== false
					&& $xenOptions->quattro_noattach_img
				)
				{
					$params['editorOptions']['extraClass'] .= ' ImgFallback';
				}

				if(!$visitor->enable_rte)
				{
					break;
				}
				
				$bbmShowWysiwyg = self::_showWysiwyg($params['showWysiwyg']);
				$xenShowWysiwyg = $params['showWysiwyg'];

				$bbmSmiliesHaveCategories = false;

				if($xenOptions->quattro_smilies_sm_addon_enable)
				{
					list($bbmSmiliesHaveCategories, $bbmSmilies) = Sedo_TinyQuattro_Helper_Editor::getSmiliesByCategory();	
				}
				else
				{
					$bbmSmilies = Sedo_TinyQuattro_Helper_Editor::getEditorSmilies();
				}

				$extraValues = array (
					'templateParams' => $params,
					'availableButtons' => array()
				);

				list($mceConfig, $mceBtnCss) = self::getMceConfig($extraValues);

				$params += array(
					'loadQuattro' => self::_checkQuattroPermissions(), 	//quattro param
					'quattroIntegration' => self::_quattroIntegration(),	//quattro integration js (for 1.2)
					'mceConfig' => $mceConfig,
					'mceBtnCss' => $mceBtnCss,
					'bbmSmiliesHaveCategories' => $bbmSmiliesHaveCategories
				);

				if($xenShowWysiwyg == false && $bbmShowWysiwyg == true)
				{
					$params['formCtrlNameHtml'] =  $params['formCtrlNameHtml'] . '_html';
				}
				
				$params['showWysiwyg'] = $bbmShowWysiwyg;
	   		break;
		}
	}

	protected static function _quattroIntegration()
	{
		return (XenForo_Application::get('options')->get('currentVersionId') >= 1020031);
	}

	protected static $QuattroPerms = null;

	protected static function _checkQuattroPermissions()
	{
		if(self::$QuattroPerms == null)
		{
			$options = XenForo_Application::get('options');
			$visitor = XenForo_Visitor::getInstance();
			$enable = true;
				
			if(XenForo_Visitor::isBrowsingWith('mobile') && $options->quattro_disable_on_mobiles)
			{
				//TinyQuattro disable on mobiles
				$enable = false;
			}
			
			if(empty($visitor->permissions['sedo_quattro']['display']))
			{
				//No permission to load TinyQuattro
				$enable = false;
			}
			
			if(!$visitor->enable_rte)
			{
				$enable = false;			
			}
				
			self::$QuattroPerms = $enable;
			return $enable;
		}
		
		return self::$QuattroPerms;
	}

	public static function getMceConfig($extraValues)
	{
		$mceOptions = self::getMceDefaultOptions($extraValues);
		$mceBtnCss = array_pop($mceOptions);
		$mcePlugins = self::getMceDefaultPlugins($extraValues);

		XenForo_CodeEvent::fire('tinyquattro_setup', array(&$mcePlugins, &$mceOptions, &$mceBtnCss, $extraValues));

		/***
		 * The main plugin has now two parts 
		 *  1) The first is the main one, it will be loaded as the first plugin so other plugins 
		 *     can have access to some of his functions if needed
		 *  2) The second one must the last plugin. It will trigger an event telling the main plugins all 
		 *     plugins have been loaded
		 **/
		 
		array_unshift($mcePlugins, 'xenforo');
		$mcePlugins[] = '-xenReady';	

		if(isset($mceOptions['settings']))
		{
			$mceOptions['settings']['plugins'] = $mcePlugins;
		}

		/* Encode options with Json */
		foreach($mceOptions as $key => &$option)
		{
			$option = XenForo_ViewRenderer_Json::jsonEncodeForOutput($option, false);
		}

		/* Format extra css */
		$mceBtnCss = self::_parseMceBtnCss($mceBtnCss);

		return array($mceOptions, $mceBtnCss);
	}

	protected static function _parseMceBtnCss(array $mceBtnCss)
	{
		$visitor = XenForo_Visitor::getInstance();

		$parsedCSS = array(
			'allBrowsers' => '',
			'ie7' => ''
		);
		
		foreach($mceBtnCss as $css)
		{
			$btnCode = $css['buttonCode'];
			$icoCode = $css['iconCode'];
			
			$parsedCSS['allBrowsers'] .= '.mce-i-'.$btnCode.':before {content:"\\'.$icoCode.'";} ';
			$parsedCSS['ie7'] .= '.mce-i-'.$btnCode.':before {-ie7-icon:"\\'.$icoCode.'"} ';
		}

		$parsedCSS['ie7'] .= '.mce-i-pastetext:before { -ie7-icon:"\e035" }';

		if(isset($visitor->getBrowser['IEis']) && $visitor->getBrowser['IEis'] == 7)
		{
			return $parsedCSS['ie7'];
		}
		
		return $parsedCSS['allBrowsers'];
	}

	public static function getMceDefaultOptions(&$extraValues)
	{
		$mceOptions = array();
		
		$xenOptions = XenForo_Application::get('options');
		$visitor = XenForo_Visitor::getInstance();
		
		$language = $visitor->getLanguage();
		$templateParams = (isset($extraValues['templateParams'])) ? $extraValues['templateParams'] : array();

		/*Smilies*/
		$bbmSmiliesHaveCategories = false;
		if($xenOptions->quattro_smilies_sm_addon_enable)
		{
			list($bbmSmiliesHaveCategories, $bbmSmilies) = Sedo_TinyQuattro_Helper_Editor::getSmiliesByCategory();	
		}
		else
		{
			$bbmSmilies = Sedo_TinyQuattro_Helper_Editor::getEditorSmilies();
		}

		/*Bbm buttons*/
		$bbmParams = array(
			'quattroGrid' => array(),
			'customQuattroButtonsCss' => array(),
			'customQuattroButtonsJs' => array()
		);
		
		if(class_exists('BBM_Helper_Buttons') && !empty($templateParams))
		{
			
			$controllerName = self::getParam('controllerName', $templateParams);
			$controllerAction = self::getParam('controllerAction', $templateParams);
			$viewName = self::getParam('viewName', $templateParams);

			$bbmParams = BBM_Helper_Buttons::getConfig($controllerName, $controllerAction, $viewName);
		}
		
		/*Mce Grid*/
		if(!empty($bbmParams['quattroGrid']))
		{
			$mceGrid = array();
			
			foreach($bbmParams['quattroGrid'] as $key => $buttons)
			{
				$mceGrid["toolbar{$key}"] = $buttons;
			}
		}
		else
		{
			$mceGrid = array(
				'toolbar1' => "removeformat undo redo pastetext restoredraft | ".
						"xen_fontfamily xen_fontsize forecolor backcolor | ".
						"bold italic underline strikethrough | ".
						"alignleft aligncenter alignright alignjustify | ".
						"fullscreen xen_switch",
				'toolbar2' => "bullist numlist outdent indent | subscript superscript | ".
						"xen_image xen_media xen_link xen_unlink | ".
						"xen_smilies xen_smilies_picker charmap xen_nonbreaking xen_code xen_quote"
			);
		}

		//Add available buttons to extraValues
		$extraValues['availableButtons'] = array_diff(array_unique(explode(' ', implode(' ', $mceGrid))), array('|'));

		/*MCE Extra css*/
		$mceBtnCss = $bbmParams['customQuattroButtonsCss'];

		/*Mce skin*/
		$skin = false;
		if(!XenForo_Template_Helper_Core::styleProperty('tinyquattro_css_integration'))
		{
			$skin = trim(XenForo_Template_Helper_Core::styleProperty('tinyquattro_css_skin'));
		}
		
		/*Visitor style*/
		$visitorStyle = self::getParam('visitorStyle', $templateParams);
		$style_id = (isset($visitorStyle['style_id'])) ? $visitorStyle['style_id'] : 0;
		$style_last_modified_date = (isset($visitorStyle['last_modified_date'])) ? $visitorStyle['last_modified_date'] : 0;
		$cssIframe = 'css.php?style=' . urlencode($style_id) . '&css=tiny_quattro_iframe&d=' . urlencode($style_last_modified_date);

		/*Request path*/
		$requestPath = self::getParam('requestPaths', $templateParams);
		$fullBasePath = (isset($requestPath['fullBasePath'])) ? $requestPath['fullBasePath'] : null;
		
		/*Text direction*/
		$textDirection = (!empty($language['text_direction'])) ? strtolower($language['text_direction']) : 'ltr';
		
		/*EditorOptions*/
		$editorOptions = self::getParam('editorOptions', $templateParams);
		$quattroOptions = (isset($editorOptions['sedo_quattro'])) ? $editorOptions['sedo_quattro'] : array();
		
		/*Attach options*/	
		$attachType = (isset($quattroOptions['attach']['type'])) ? $quattroOptions['attach']['type'] : '';
		$attachId = (isset($quattroOptions['attach']['id'])) ? $quattroOptions['attach']['id'] : '';
		$attachHash = (isset($quattroOptions['attach']['hash'])) ? $quattroOptions['attach']['hash'] : '';

		/*Resize Editor*/
		$resize = 'both';
		if($xenOptions->quattro_resize_editor != 'both')
		{
			$resize = (!empty($xenOptions->quattro_resize_editor)) ? true : false;
		}

		/*MCE Settings*/
		$mceSettings = array(
			'theme'			=> 'modern',
			'language'		=> 'xen',
			'skin'			=> $skin,
			'content_css'		=> $cssIframe,
			'menubar'		=> false,
			'toolbar_items_size'	=> $xenOptions->quattro_iconsize,
			'resize'		=> $resize,
			//'object_resizing'	=> false,
			'browser_spellcheck'	=> ($xenOptions->quattro_enable_browser_spellcheck) ? true : false,
			'autoresize_min_height' => $xenOptions->quattro_autoresize_min_height,
			'autoresize_max_height' => $xenOptions->quattro_autoresize_max_height,
			'autoresize_min_height_qr' => $xenOptions->quattro_autoresize_min_height_qr,
			'autoresize_max_height_qr' => $xenOptions->quattro_autoresize_max_height_qr,
			'autosave_interval' 	=> $xenOptions->quattro_autosave_interval . 's',
			'autosave_retention' 	=> $xenOptions->quattro_autosave_retention . 'm',
			'autosave_restore_when_empty' => ($xenOptions->quattro_autosave_autorestore) ? true : false,
			'directionality' 	=> $textDirection,
			'document_base_url' 	=> $fullBasePath,	
			'autosave_ask_before_unload' => ($xenOptions->quattro_autosave_ask_before_unload) ? true : false,
			'paste_as_text' 	=> ($xenOptions->quattro_paste_as_text) ? true : false,
			'font_size_legacy_values' => '9px,10px,12px,15px,18px,22px,26px',
			'statusbar' 		=> ($xenOptions->quattro_statusbar) ? true : false,
			'paste_data_images' 	=> false,
			'paste_retain_style_properties' => $xenOptions->quattro_retain_style_properties,
			'contextmenu' => 'xen_link inserttable | cell row column xen_tableskin deletetable',
			'xen_attach' => "{$attachType},{$attachId},{$attachHash}",
			'nonbreaking_force_tab' => true
		);

		$mceSettings += $mceGrid;

		$mceParams = array(
			'frightMode'		=> ($xenOptions->quattro_frightmode) ? true : false,
			'fastUnlink' 		=> ($xenOptions->quattro_fastunlink) ? true : false,
			'extraColors'		=> ($xenOptions->quattro_extracolors) ? true : false,
			'extraLists' 		=> ($xenOptions->quattro_extralists) ? true : false,
			'hidePath'		=> ($xenOptions->quattro_hidepath) ? true : false,
			'extendInsert'		=> ($xenOptions->quattro_extended_insert) ? true : false,
			'geckoFullfix'		=> ($xenOptions->quattro_geckofullfix) ? true : false,
			'overlayDefaultSize'	=> array('w' => 320, 'h' => 240),
			'overlayColorPickerSize' => array('w' => 450, 'h' => 265),
			'overlayImageSize'	=> array('w' => 480, 'h' => 110),
			'overlayLinkSize'	=> array('w' => 480, 'h' => 160),
			'overlayMediaSize'	=> array('w' => 480, 'h' => 160),
			'oldXen'		=> ($xenOptions->currentVersionId < 1020031),
			'overlaySmiliesSize'	=> array(
							'w' => XenForo_Template_Helper_Core::styleProperty('tinyquattro_smiliespicker_width'), 
							'h' => XenForo_Template_Helper_Core::styleProperty('tinyquattro_smiliespicker_height')
						),
			'smallFontBtn'		=> ($xenOptions->quattro_fonts_smallbuttons) ? true : false,
			'smiliesWindow'		=> $xenOptions->quattro_smilies_window_type,
			'xSmilies'		=> $xenOptions->quattro_xsmilies,
			'xCatSmilies'		=> $xenOptions->quattro_smilies_sm_addon_menubtn_cat,
			'smiliesCat'		=> $bbmSmiliesHaveCategories,
			'smiliesDesc'		=> $xenOptions->quattro_smilies_desc,
			'xenforo_smilies'	=> $bbmSmilies,
			'xendraft'		=> ($xenOptions->quattro_xendraft) ? true : false,
			'xenwordcount'		=> $xenOptions->quattro_wordcount,
			'disableResponsive'	=> !(XenForo_Template_Helper_Core::styleProperty('tinyquattro_modal_responsive')),
			'bbmButtons'		=> self::formatBbmJsButtons($bbmParams['customQuattroButtonsJs'])
		);	

		return array('settings' => $mceSettings, 'params' => $mceParams, 'mceBtnCss' => $mceBtnCss);
	}

	protected static function formatBbmJsButtons(array $bbmButtons)
	{
		$buttons = array();
		
		foreach($bbmButtons as $bbmButton)
		{
			$tag = $bbmButton['tag'];

			$buttons[$tag] = array(
				'code' => $bbmButton['code'],
				'iconSet' => $bbmButton['iconSet'],
				'desc' => $bbmButton['description'],
				'type' => $bbmButton['type'],	
				'typeOpt' => $bbmButton['typeOption'],
				'_return' => $bbmButton['return'],
				'separator' => $bbmButton['separator']
				);
			
			if($bbmButton['return'] == 'direct')
			{
				$buttons[$tag] += array(
					'tagOpt' => $bbmButton['tagOptions'],
					'tagCont' => $bbmButton['tagContent'],				
				);
			}
			else
			{
				$buttons[$tag] += array(
					'template' => $bbmButton['returnOption']
				);		
			}
		}

		return $buttons;
	}

	public static function getMceDefaultPlugins()
	{
		$xenOptions = XenForo_Application::get('options');
		$mcePlugins = array();	

		$mcePlugins = array('advlist', 'lists', 'charmap', 'visualchars',
			'fullscreen', 'directionality', 'searchreplace',
			'paste', 'textcolor', 'autoresize'
		);

		if(XenForo_Application::debugMode())
		{
			$mcePlugins[] = 'code';
		}

		if(!empty($xenOptions->quattro_extra_bbcodes['xtable']))
		{
			$mcePlugins[] = 'table';
		}

		if(!empty($xenOptions->quattro_usertagging))
		{
			$mcePlugins[] = 'xen_tagging';
		}

		if(!empty($xenOptions->quattro_contextmenu))
		{
			$mcePlugins[] = 'contextmenu';
		}

		if(!empty($xenOptions->quattro_xendropping))
		{
			$mcePlugins[] = 'xen_dropping';
		}

		if(!empty($xenOptions->quattro_xenpaste_dataimg_upload))
		{
			$mcePlugins[] = 'xen_paste_img'; // buggy: http://www.tinymce.com/develop/bugtracker_view.php?id=6367
		}

		if(!empty($xenOptions->quattro_wysiwyg_quote))
		{
			$mcePlugins[] = 'xenquote';
		}		
		
		return $mcePlugins;
	}

	protected static function _showWysiwyg($showWysiwyg)
	{
		$options = XenForo_Application::get('options');
		$visitor = XenForo_Visitor::getInstance();
 
		if($options->quattro_parser_bypass_mobile_limit)
		{
			$isMobile = XenForo_Visitor::isBrowsingWith('mobile');
			
			if($isMobile)
			{
				$showWysiwyg = true;
			
				if($options->quattro_parser_mobile_user_option && !$visitor->quattro_rte_mobile)
				{
					$showWysiwyg = false;			
				}
			}
		}

		if($options->quattro_parser_bypass_ie7limit)
		{
			if(isset($visitor->getBrowser['IEis']))
			{
				//Browser Detection (Mobile/MSIE) Addon
				if($visitor->getBrowser['isIE'] && $visitor->getBrowser['IEis'] == 7)
				{
					$showWysiwyg = true;
				}
			}
			else
			{
				//Manual Detection
				if(isset($_SERVER['HTTP_USER_AGENT']) && preg_match('#msie (\d+)#i', $_SERVER['HTTP_USER_AGENT'], $match))
				{
					if($match[1] == 7)
					{
						$showWysiwyg = true;
					}
				}
			}
		}

		if(!XenForo_Visitor::getInstance()->enable_rte)
		{
			$showWysiwyg = false;
		}

		return $showWysiwyg;
	}

	public static function getParam($key, $params)
	{
		if(isset($params[$key]))
		{
			return $params[$key];
		}
		
		return null;
	}	
}
//Zend_Debug::dump($abc);