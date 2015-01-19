<?php
class Sedo_TinyQuattro_Helper_MceConfig
{
	protected $templateParams = array();
	protected $templateObject;
	protected $bbmParams = null;
	protected $availableButtons = array();

	protected $mceSettings = array();
	protected $mceParams = array();
	protected $mceBtnCss = array();	
	protected $mcePlugins = array();	

	public function __construct(array $templateParams, array $bbmParams, $templateObject)
	{
		$this->templateParams = $templateParams;

		$this->bbmParams = $bbmParams;

		if(isset($templateObject))
		{
			$this->templateObject = $templateObject;
		}
	}

	public function getMceConfig()
	{
		list($this->mceSettings, $this->mceParams, $this->mceBtnCss) = $this->_getMceDefaultOptions();
		$this->mcePlugins = $this->_getMcePluginsList();

		/* Word count configuration */
		$this->_manageWordCount();
		
		/* Manage Mce Menu */
		$this->_manageMceMenu();

		/* Trigger listener */
		XenForo_CodeEvent::fire('tinyquattro_setup', array($this));

		/* Add html button/menu */
		$this->_manageHtmlCodeButton();

		/* Check the grid is correctly formatted */
		$this->_mceGridChecker();

		/***
		 * The main plugin has now two parts 
		 *  1) The first is the main one, it will be loaded as the first plugin so other plugins 
		 *     can have access to some of his functions if needed
		 *  2) The second one must the last plugin. It will trigger an event telling the main plugins all 
		 *     plugins have been loaded
		 **/
		 
		array_unshift($this->mcePlugins, 'xenforo');
		$this->mcePlugins[] = '-xenReady';	

		if(isset($this->mceSettings))
		{
			$this->mceSettings['plugins'] = $this->mcePlugins;
		}

		/* Create Mce Menu */
		$this->_createMceMenu();	

		/* Format Buttons Grid */
		$buttonsGrid = $this->mceSettings['mceGrid'];

		unset($this->mceSettings['mceGrid']);

		foreach($buttonsGrid as $key => $grid)
		{
			$this->mceSettings["toolbar{$key}"] = implode(' ', $grid);
		}

		/* Encode options with Json */
		$mceOptions = array(
			'settings' => $this->mceSettings,
			'params' => $this->mceParams
		);

		/* Format extra css */
		$mceBtnCss = $this->_parseMceBtnCss();

		return array($mceOptions, $mceBtnCss);
	}

	protected function _mceGridChecker()
	{
		$mceGrid = $this->getMceGrid();
		
		foreach($mceGrid as $key => $grid)
		{
			$grid = array_diff($grid, array('|'));
			
			if(empty($grid))
			{
				//The grid line is empty, delete it
				unset($mceGrid[$key]);
			}
		}
		
		$this->overrideMceGrid($mceGrid);
	}

	protected function _parseMceBtnCss()
	{
		$visitor = XenForo_Visitor::getInstance();

		$parsedCSS = array(
			'allBrowsers' => '',
			'ie7' => ''
		);
		
		foreach($this->mceBtnCss as $css)
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

	protected function _getMceDefaultOptions()
	{
		$mceOptions = array();
		
		$xenOptions = XenForo_Application::get('options');
		$visitor = XenForo_Visitor::getInstance();
		
		$language = $visitor->getLanguage();
		$templateParams = $this->templateParams;

		/*Smilies*/
		list($bbmSmiliesHaveCategories, $bbmSmilies) = Sedo_TinyQuattro_Helper_Smilie::getSmiliesAllVersions();	

		/*Bbm buttons*/
		if($this->bbmParams == null)
		{
			$ccv = array(
				$this->getTemplateParam('controllerName'),
				$this->getTemplateParam('controllerAction'),
				$this->getTemplateParam('viewName')
			);

			list($bbmEnable, $bbmParams) = Sedo_TinyQuattro_Helper_Quattro::checkAndGetBbmConfig($ccv);
			$this->bbmParams = $bbmParams;
		}
		else
		{
			$bbmParams = $this->bbmParams;
		}

		/*Mce Grid*/
		if(!empty($bbmParams['quattroGrid']))
		{
			$mceGrid = array();
			
			foreach($bbmParams['quattroGrid'] as $key => $buttons)
			{
				if(is_array($buttons))
				{
					$mceGrid[$key] = $buttons;
				}
				else
				{
					$mceGrid[$key] = explode(' ', $buttons);
				}
			}
		}
		else
		{
			$mceGrid = array(
				'1' => array('removeformat', 'undo', 'redo', 'pastetext', 'restoredraft', '|',
						'xen_fontfamily', 'xen_fontsize', 'forecolor', 'backcolor', '|',
						'bold', 'italic', 'underline', 'strikethrough', '|',
						'alignleft', 'aligncenter', 'alignright', 'alignjustify', '|',
						'fullscreen', 'xen_switch'
				),
				'2' => array('bullist', 'numlist', 'outdent', 'indent', '|', 'subscript', 'superscript', '|',
						'xen_image', 'xen_media', 'table', 'xen_link', 'xen_unlink', '|',
						'xen_smilies', 'xen_smilies_picker', 'charmap', 'xen_nonbreaking',
						'xen_code', 'xen_quote', 'xen_spoiler'
				)
			);
		}

		//Add available buttons to extraValues
		$this->availableButtons = array_diff(array_unique(call_user_func_array('array_merge', $mceGrid)), array('|'));

		/*MCE Extra css*/
		$mceBtnCss = $bbmParams['customQuattroButtonsCss'];

		/*Text direction*/
		$textDirection = (!empty($language['text_direction'])) ? strtolower($language['text_direction']) : 'ltr';
		
		/*Mce skin*/
		$skin = false;
		if(!XenForo_Template_Helper_Core::styleProperty('tinyquattro_css_integration'))
		{
			$skin = trim(XenForo_Template_Helper_Core::styleProperty('tinyquattro_css_skin'));
		}
		elseif($textDirection == 'rtl')
		{
			$skin = 'lightgray';
		}
		
		/*Visitor style*/
		$visitorStyle = $this->getTemplateParam('visitorStyle');
		$style_id = (isset($visitorStyle['style_id'])) ? $visitorStyle['style_id'] : 0;
		$style_last_modified_date = (isset($visitorStyle['last_modified_date'])) ? $visitorStyle['last_modified_date'] : 0;
		$cssIframe = 'css.php?style=' . urlencode($style_id) . '&css=tiny_quattro_iframe&d=' . urlencode($style_last_modified_date);

		/*Request path*/
		$requestPath = $this->getTemplateParam('requestPaths');
		$fullBasePath = (isset($requestPath['fullBasePath'])) ? $requestPath['fullBasePath'] : null;
		
		/*EditorOptions*/
		$editorOptions = $this->getTemplateParam('editorOptions');
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

		//Menu bar
		$menubar = ($xenOptions->quattro_menubar) ? true : false;
		if($this->isCustomFieldsEditor() && $xenOptions->quattro_menubar_force_cstfield)
		{
			$menubar = true;
		}

		//Style formats
		$style_formats = array();
		$headingItems = array();
		$customFormatsItems = array();
		
		$H1 = $this->getTextStyleProperty('quattro_sf_h1_text');
		$H2 = $this->getTextStyleProperty('quattro_sf_h2_text');
		$H3 = $this->getTextStyleProperty('quattro_sf_h3_text');
		$H4 = $this->getTextStyleProperty('quattro_sf_h4_text');
		$H5 = $this->getTextStyleProperty('quattro_sf_h5_text');
		$H6 = $this->getTextStyleProperty('quattro_sf_h6_text');
		
		if($H1){ array_push($headingItems, array('title' => $H1, 'block' => 'h1', 'classes' => 'quattro_sf h1')); }
		if($H2){ array_push($headingItems, array('title' => $H2, 'block' => 'h2', 'classes' => 'quattro_sf h2')); }
		if($H3){ array_push($headingItems, array('title' => $H3, 'block' => 'h3', 'classes' => 'quattro_sf h3')); }
		if($H4){ array_push($headingItems, array('title' => $H4, 'block' => 'h4', 'classes' => 'quattro_sf h4')); }
		if($H5){ array_push($headingItems, array('title' => $H5, 'block' => 'h5', 'classes' => 'quattro_sf h5')); }
		if($H6){ array_push($headingItems, array('title' => $H6, 'block' => 'h6', 'classes' => 'quattro_sf h6')); }
		$heading = array('title' => 'Headings', 'items' => $headingItems);

		$CUSTOM1 = $this->getTextStyleProperty('quattro_sf_custom1_text');
		$CUSTOM2 = $this->getTextStyleProperty('quattro_sf_custom2_text');
		$CUSTOM3 = $this->getTextStyleProperty('quattro_sf_custom3_text');

		if($CUSTOM1){ array_push($customFormatsItems, array('title' => $CUSTOM1, 'inline' => 'span', 'classes' => 'quattro_sf cust1')); }
		if($CUSTOM2){ array_push($customFormatsItems, array('title' => $CUSTOM2, 'inline' => 'span', 'classes' => 'quattro_sf cust2')); }
		if($CUSTOM3){ array_push($customFormatsItems, array('title' => $CUSTOM3, 'inline' => 'span', 'classes' => 'quattro_sf cust3')); }
		$customFormats = array('title' => 'Custom formats', 'items' => $customFormatsItems);

		if(!empty($headingItems) && !empty($customFormatsItems))
		{
			array_push($style_formats, $heading);
			array_push($style_formats, $customFormats);
		}
		elseif(!empty($headingItems) && empty($customFormatsItems))
		{
			$style_formats = $headingItems;
		}
		elseif(empty($headingItems) && !empty($customFormatsItems))
		{
			$style_formats = $customFormats;
		}

		/*MCE Settings*/
		$mceSettings = array(
			'theme'			=> 'modern',
			'language'		=> 'xen',
			'skin'			=> $skin,
			'content_css'		=> $cssIframe,
			'menubar'		=> $menubar,
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
			'nonbreaking_force_tab' => true,
			'extended_valid_elements' => 'anchor[id],xformat[name]',
			'style_formats' => $style_formats,
			'cache_suffix' => Sedo_TinyQuattro_Helper_Quattro::getMceJsVersion('raw')
		);

		//Editor Size
		$editorCustomSize = (!empty($editorOptions['height'])) ? intval($editorOptions['height']) : false;
		$editorCustomSizeEnable = (isset($editorOptions['extraClass'])) ? strpos($editorOptions['extraClass'], 'SmallEditor') !== false : false;

		if($editorCustomSizeEnable)
		{
			if($editorCustomSize)
			{
				$mceSettings['autoresize_min_height'] = $editorCustomSize;
				$mceSettings['autoresize_max_height'] = $editorCustomSize;
				$mceSettings['autoresize_min_height_qr'] = $editorCustomSize;
				$mceSettings['autoresize_max_height_qr'] = $editorCustomSize;
			}
			else
			{
				$mceSettings['autoresize_min_height'] = $mceSettings['autoresize_min_height_qr'];
				$mceSettings['autoresize_max_height'] = $mceSettings['autoresize_max_height_qr'];			
			}
		}
		elseif($editorCustomSize && $xenOptions->quattro_autoresize_xen)
		{
				$mceSettings['autoresize_min_height'] = $editorCustomSize;
				$mceSettings['autoresize_max_height'] = $editorCustomSize;
				$mceSettings['autoresize_min_height_qr'] = $editorCustomSize;
				$mceSettings['autoresize_max_height_qr'] = $editorCustomSize;		
		}
		
		if(!empty($editorOptions['minHeight']) || !empty($editorOptions['maxHeight']))
		{
			$minHeight = (!empty($editorOptions['minHeight'])) ? $editorOptions['minHeight'] : $maxHeight;
			$maxHeight = (!empty($editorOptions['maxHeight'])) ? $editorOptions['maxHeight'] : $minHeight;
			
			$minHeight = intval($minHeight);
			$maxHeight = intval($maxHeight);

			$mceSettings['autoresize_min_height'] = $minHeight;
			$mceSettings['autoresize_max_height'] = $maxHeight;
			$mceSettings['autoresize_min_height_qr'] = $minHeight;
			$mceSettings['autoresize_max_height_qr'] = $maxHeight;
		}

		//Fake settings that will be deleted after being formatted
		$mceSettings['mceGrid'] = $mceGrid;

		//Menu options
		$tglMenuMode = $xenOptions->quattro_tglMenuMode;
		$tglMenuCollasped = $xenOptions->quattro_tglMenuCollasped;

		//Menu options - Custom fields editor 
		if($this->isCustomFieldsEditor() && $xenOptions->quattro_menubar_force_collapsed_cstfield)
		{
			$tglMenuCollasped = true;
		}

		$mceParams = array(
			'frightMode'		=> ($xenOptions->quattro_frightmode) ? true : false,
			'fastUnlink' 		=> ($xenOptions->quattro_fastunlink) ? true : false,
			'extraColors'		=> ($xenOptions->quattro_extracolors) ? true : false,
			'extraLists' 		=> ($xenOptions->quattro_extralists) ? true : false,
			'hidePath'		=> ($xenOptions->quattro_hidepath) ? true : false,
			'extendInsert'		=> ($xenOptions->quattro_extended_insert) ? true : false,
			'lazyLoader'		=> ($xenOptions->quattro_lazyloader) ? true : false,
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
			'mceRegexEl'		=> array('wordcount_countregex', 'wordcount_cleanregex'),
			'showSmilieItemWin'	=> ($xenOptions->quattro_smiliemenuitem_enable_menu_window) ? true : false,
			'smallFontBtn'		=> ($xenOptions->quattro_fonts_smallbuttons) ? true : false,
			'smiliesWindow'		=> $xenOptions->quattro_smilies_window_type,
			'tglMenuMode'		=> $tglMenuMode,
			'tglMenuCollasped'	=> $tglMenuCollasped,
			'xSmilies'		=> $xenOptions->quattro_xsmilies,
			'xCatSmilies'		=> $xenOptions->quattro_smilies_sm_addon_menubtn_cat,
			'smiliesCat'		=> $bbmSmiliesHaveCategories,
			'smiliesDesc'		=> $xenOptions->quattro_smilies_desc,
			'xenforo_smilies'	=> $bbmSmilies,
			'xendraft'		=> ($xenOptions->quattro_xendraft) ? true : false,
			'xenwordcount'		=> $xenOptions->quattro_wordcount,
			'disableResponsive'	=> !(XenForo_Template_Helper_Core::styleProperty('tinyquattro_modal_responsive')),
			'bbmButtons'		=> $this->formatBbmJsButtons($bbmParams['customQuattroButtonsJs'])
		);	

		return array($mceSettings, $mceParams, $mceBtnCss);
	}

	public function getTextStyleProperty($property)
	{
		return XenForo_Template_Helper_Core::jsEscape(XenForo_Template_Helper_Core::styleProperty($property));
	}

	public function getXenCustomBbCodes()
	{
		$editorOptions = $this->getTemplateParam('editorOptions');

		if(!isset($editorOptions['json'], $editorOptions['json']['bbCodes']))
		{
			return array();
		}

		return $editorOptions['json']['bbCodes'];
	}

	public function getXenCustomBbCode($bbCodeTag)
	{
		$xenCustomBbCodes = $this->getXenCustomBbCodes();

		if(!isset($xenCustomBbCodes[$bbCodeTag]))
		{
			return false;
		}
		
		return $xenCustomBbCodes[$bbCodeTag];
	}

	protected function formatBbmJsButtons(array $bbmButtons)
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

			/*Check if this button is a XenForo Custom Bb Code one*/
			$xenBbCode = $this->getXenCustomBbCode($tag);
			
			if($xenBbCode)
			{
				if(isset($xenBbCode['title']))
				{
					//Get back the proper description (generated in the view)
					$buttons[$tag]['desc'] = XenForo_Template_Helper_Core::jsEscape($xenBbCode['title']);
				}
			}
		}

		return $buttons;
	}

	protected function _getMcePluginsList()
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

		if(!empty($xenOptions->quattro_extracolors))
		{
			$mcePlugins[] = 'colorpicker';
		}

		if(!empty($xenOptions->quattro_textpattern))
		{
			$mcePlugins[] = 'textpattern';
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

		if(!empty($xenOptions->quattro_extra_bbcodes['hr']))
		{
			$mcePlugins[] = 'hr';
		}

		if(!empty($xenOptions->quattro_extra_bbcodes['anchor']))
		{
			$mcePlugins[] = 'anchor';
		}		

		return $mcePlugins;
	}

	protected function _manageWordCount()
	{
		$wordCountParam = $this->getMceParam('xenwordcount');
		
		if($wordCountParam != 'no')
		{
			$this->addMcePlugin('wordcount');
			
			if($wordCountParam == 'char')
			{
				$this->setMceSetting('wordcount_countregex', array('\S', 'g'));
				$this->setMceSetting('wordcount_cleanregex', array('', ''));
			}
			elseif($wordCountParam == 'charwp')
			{
				$this->setMceSetting('wordcount_countregex', array('(\S|\b[\u0020]\b)', 'g'));
				$this->setMceSetting('wordcount_cleanregex', array('\n', 'g'));
			}
		}
	}

	protected function _manageHtmlCodeButton()
	{
		if(!$this->hasMcePlugin('code'))
		{
			return;	
		}
	
		$this->addButton('code', 1, '#end', true, false);
		$this->addMenuItem('code', 'tools', '#end', true);
	}

	protected function _manageMceMenu()
	{
		if(!$this->mceSettings['menubar'])
		{
			return;
		}

		/* Default layout & variables */
		$this->mceSettings['menubar'] = array('format', 'edit', 'insert', 'table', 'tools', 'view');

		$availableButtons = $this->availableButtons;
		$availableButtons[] = '|';
		
		$root = array();
		$buttonsToDelete = array();

		array_push($availableButtons, 
			'@format_1', '@format_2', '@format_3', 
			'@edit_1', '@edit_2', '@edit_3',
			'@insert_1', '@insert_2', '@insert_3',
			'@tools_1', '@view_1', '@view_2'
		);

		/* Format Menu */
		$formatMenuItems = array('bold', 'italic', 'underline', 'strikethrough', '@format_1', '|', 'superscript', 'subscript', '@format_2', '|', 'removeformat', '@format_3');
		$formatMenuItems = array_intersect($formatMenuItems, $availableButtons);

		$root['format'] = array('title' => 'Format', 'items' => $formatMenuItems);
		$buttonsToDelete = array_merge($buttonsToDelete, $formatMenuItems);


		/* Edit Menu */
		$editMenuItems = array('undo', 'redo', '@edit_1', '|', 'cut', 'copy', 'paste', 'pastetext', '@edit_2', '|', 'searchreplace', '|', 'selectall', '@edit_3');
		array_push($availableButtons, 'cut', 'copy', 'paste', 'searchreplace', 'selectall'); //needed to get the proper menu
		$editMenuItems = array_intersect($editMenuItems, $availableButtons);
		
		$root['edit'] = array('title' => 'Edit', 'items' => $editMenuItems);
		$buttonsToDelete = array_merge($buttonsToDelete, $editMenuItems);

		/* Table Menu */
		if(in_array('table', $availableButtons))
		{
			$tableMenuItems = array('inserttable', 'tableprops', 'deletetable', '|', 'cell', 'row', 'column', '|', 'xen_tableskin');
			$root['table'] = array('title' => 'Table', 'items' => $tableMenuItems);
			$buttonsToDelete[] = 'table';
		}

		/* Insert Menu */
		$insertMenuItems = array('xen_image', 'xen_media', 'xen_link', 'xen_unlink', 'anchor', '@insert_1', '|', 
			'xen_smilies', 'xen_smilies_picker', 'xen_quote', 'xen_spoiler', '@insert_2', '|', 
			'xen_code', 'charmap', 'hr', 'xen_nonbreaking', '@insert_3'
		);
		
		if($this->buttonIsEnabled('xen_smilies_picker'))
		{
			//The xen_smilies has already the picker function
			$availableButtons = array_diff($availableButtons, array('xen_smilies_picker'));
			array_push($buttonsToDelete, 'xen_smilies_picker');
		}
		$insertMenuItems = array_intersect($insertMenuItems, $availableButtons);
		
		$root['insert'] = array('title' => 'Insert', 'items' => $insertMenuItems);
		$buttonsToDelete = array_merge($buttonsToDelete, $insertMenuItems);

		/* Tools Menu */
		$toolsMenuItems = array('restoredraft', '@tools_1');
		$toolsMenuItems = array_intersect($toolsMenuItems, $availableButtons);

		$root['tools'] = array('title' => 'Tools', 'items' => $toolsMenuItems);
		$buttonsToDelete = array_merge($buttonsToDelete, $toolsMenuItems);

		/* View Menu */
		$viewMenuItems = array('togglemenu', 'fullscreen', '@view_1', '|', 'xen_switch', '@view_2');
		array_push($availableButtons, 'togglemenu');
		$viewMenuItems = array_intersect($viewMenuItems, $availableButtons);

		$root['view'] = array('title' => 'View', 'items' => $viewMenuItems);
		$buttonsToDelete = array_merge($buttonsToDelete, $viewMenuItems);
		
		/* Proceed */
		$this->mceSettings['menu'] = $root;

		$this->deleteButtons($buttonsToDelete);
	}

	protected function _createMceMenu()
	{
		/* Menu bar */
		if(empty($this->mceSettings['menubar']))
		{
			return false;
		}

		if($this->mceSettings['directionality'] == 'rtl'){
			$this->mceSettings['menubar'] = array_reverse($this->mceSettings['menubar']);
		}
		
		$this->mceSettings['menubar'] = implode(' ', $this->mceSettings['menubar']);

		/* Menu */
		if(empty($this->mceSettings['menu']))
		{
			return false;
		}
		
		$hooksToDelete = array(
			'@format_1', '@format_2', '@format_3', 
			'@edit_1', '@edit_2', '@edit_3',
			'@insert_1', '@insert_2', '@insert_3',
			'@tools_1', '@view_1', '@view_2'
		);
		
		foreach($this->mceSettings['menu'] as $menuKey => &$menu)
		{
			if(empty($menu['items']))
			{
				unset($this->mceSettings['menu'][$menuKey]);
				continue;
			}

			$menu['items'] = array_diff($menu['items'], $hooksToDelete);

			foreach($menu['items'] as $keyItem => $item)
			{
				if(!empty($item) && $item[0] == '@')
				{
					unset($menu['items'][$keyItem]);
				}
			}

			if(count($menu['items']) == 0 || !array_diff($menu['items'], array('|')))
			{
				unset($this->mceSettings['menu'][$menuKey]);
			}

			$menu['items'] = implode(' ', $menu['items']);
			$menu['items'] = preg_replace('#((\| ){2,})#i', '', $menu['items']);
		}
		
		//Zend_Debug::dump($this->mceSettings['menu']);
	}

	/***
	 * Functions that might be usefull from the listener
	 **/

		/* Related to plugins */

		public function getMcePlugins()
		{
			return $this->mcePlugins;
		}
	
		public function hasMcePlugin($pluginName)
		{
			return in_array($pluginName, $this->mcePlugins);
		}
	
		public function addMcePlugin($pluginName)
		{
			return $this->mcePlugins[] = $pluginName;
		}

		/* Related to settings */

		public function getMceSettings()
		{
			return $this->mceSettings;
		}
	
		public function getMceSetting($key)
		{
			if(isset($this->mceSettings[$key]))
			{
				return $this->mceSettings[$key];
			}
			
			return null;
		}

		public function overrideMceSettings(array $settings)
		{
			$this->mceSettings = $settings;
		}
	
		public function setMceSetting($key, $value)
		{
			$this->mceSettings[$key] = $value;
		}

		public function unsetMceSetting($key)
		{
			unset($this->mceSettings[$key]);
		}
	
		public function addBulkMceSettings(array $settings)
		{
			$this->mceSettings += $settings;
		}

		public function getMceGrid()
		{
			return $this->getMceSetting('mceGrid');
		}

		public function overrideMceGrid(array $mceGrid)
		{
			$this->mceSettings['mceGrid'] = $mceGrid;
		}
	
		/* Related to params */

		public function getMceParams()
		{
			return $this->mceParams;
		}
	
		public function getMceParam($key)
		{
			if(isset($this->mceParams[$key]))
			{
				return $this->mceParams[$key];
			}
			
			return null;
		}
	
		public function overrideMceParams(array $params)
		{
			$this->mceParams = $params;
		}
	
		public function setMceParam($key, $value)
		{
			$this->mceParams[$key] = $value;
		}

		public function unsetMceParam($key)
		{
			unset($this->mceParams[$key]);
		}
	
		public function addBulkMceParams(array $params)
		{
			$this->mceParams += $params;
		}

		/* Related to buttons */
					 
		public function buttonIsEnabled($button)
		{
			return in_array($button, $this->availableButtons);
		}
	
		public function getAvailableButtons()
		{
			return $this->availableButtons;
		}
	
		public function addButton($buttonName, $line = 1, $pos = '#end', $openingSeparator = false, $endingSeparator = false, $insertBefore = false)
		{
			if(!isset($this->mceSettings['mceGrid'][$line]))
			{
				$array = $this->mceSettings['mceGrid'];
				end($array);
				$line = key($array);
			}

			if(!in_array($pos, array('#start', '#end')))
			{
				$extra = array(
					'openingSeparator' => $openingSeparator,
					'endingSeparator' => $endingSeparator,
					'insertBefore' =>  $insertBefore,
					'loopMode' => true
				);
				
				if( !$this->arrayInsertAfterValue(
						$this->mceSettings['mceGrid'],
						$pos,
						$buttonName,
						$extra
					)
				)
				{
					$pos = '#end';
				}
				else
				{
					return;
				}
			}

			if($pos == '#start')
			{
				if($endingSeparator)
				{
					array_unshift($this->mceSettings['mceGrid'][$line], $buttonName, '|');
				}
				else
				{
					array_unshift($this->mceSettings['mceGrid'][$line], $buttonName);			
				}
	
				$this->availableButtons[] = $buttonName;
			}
			elseif($pos == '#end')
			{
				if($openingSeparator)
				{
					$this->mceSettings['mceGrid'][$line][] = '|';			
				}
	
				$this->mceSettings['mceGrid'][$line][] = $buttonName;
	
				if($endingSeparator)
				{
					$this->mceSettings['mceGrid'][$line][] = '|';
				}
	
				$this->availableButtons[] = $buttonName;
			}
		}
	
		public function deleteButtons(array $buttonsToDelete, array $buttonsToExclude = array(), $bypassForcedButtons = false)
		{
			$xenOptions = XenForo_Application::get('options');

			$buttonsToExclude[] = '|';
			
			if(!$bypassForcedButtons)
			{
				$buttonsToExclude = array_merge($buttonsToExclude, $xenOptions->quattro_menubar_force_buttons);
			}
			
			$buttonsToDelete = array_diff($buttonsToDelete, $buttonsToExclude);
			
			foreach($this->mceSettings['mceGrid'] as &$grid)
			{
				$grid = array_diff($grid, $buttonsToDelete);
			}
			
			$this->availableButtons = array_diff($this->availableButtons, $buttonsToDelete);
		}

		public function getMceButtonsExtraCss()
		{
			return $this->mceBtnCss;
		}
	
		public function overrideMceButtonsExtraCss(array $buttonsExtraCss)
		{
			$this->mceBtnCss = $buttonsExtraCss;
		}
	
		public function addMceButtonExtraCss($buttonCode, $iconCode, $iconSet)
		{
			$this->mceBtnCss[] = array(
				'buttonCode' => $buttonCode,
				'iconCode' => $iconCode,
				'iconSet' => $iconSet
			);
		}

		/* Related to menus */
		public function getMenusOrder()
		{
			return $this->getMceSetting('menubar');
		}

		public function overrideMenusOrder($menusOrder)
		{
			if(is_array($menusOrder))
			{
				$this->setMceSetting('menubar', $menusOrder);
			}
			else
			{
				$this->setMceSetting('menubar', false);
			}
		}

		public function getMenusGrid()
		{
			return $this->getMceSetting('menu');
		}

		public function overrideMenusGrid($menusGrid)
		{
			if(is_array($menusGrid) && $this->getMceSetting('menu'))
			{
				$this->setMceSetting('menu', $menusGrid);
			}
			else
			{
				$this->unsetMceSetting('menu');
			}
		}

		public function hasMenu($menuName)
		{
			return isset($this->mceSettings['menu'][$menuName]);
		}

		public function addMenu($menuName, $pos = '#end', $menuTitle = '', array $menuItems = array(), $bypassAvailableButtons = false, $bypassForcedButtons = false)
		{
			if(!$this->getMceSetting('menubar') ||
				!$this->getMceSetting('menu') ||
				isset($this->mceSettings['menu'][$menuName])
			)
			{
				return;
			}

			$availableButtons = $this->availableButtons;
			$availableButtons[] = '|';
			
			if(!$bypassAvailableButtons)
			{
				$menuItems = array_intersect($menuItems, $availableButtons);
			}

			$this->_autoTagMenuItems($menuItems, $menuName);

			$newMenu[$menuName] = array(
				'title' => (!empty($menuTitle)) ? $menuTitle : ucfirst($menuName),
				'items' => $menuItems
			);

			$this->mceSettings['menu'] += $newMenu;
			$this->deleteButtons($menuItems, array(), $bypassForcedButtons);

			if(!in_array($pos, array('#start', '#end')))
			{
				if( !$this->arrayInsertAfterValue($this->mceSettings['menubar'], $pos, $menuName) )
				{
					$pos = '#end';
				}
				else
				{
					return;
				}
			}

			if($pos == '#start')
			{			
				array_unshift($this->mceSettings['menubar'], $menuName);
			}
			elseif($pos == '#end')
			{
				array_push($this->mceSettings['menubar'], $menuName);
			}
		}

		protected function _autoTagMenuItems(array &$menuItems, $menuName = false)
		{
			if(empty($menuItems) || !$menuName)
			{
				return $menuItems;
			}

			$menuItemsTemp = array();
			$i = 1;
			
			foreach($menuItems as $item)
			{
				if($item == '|')
				{
					$menuItemsTemp[] = "@{$menuName}_{$i}";
					$i++;
				}

				$menuItemsTemp[] = $item;
			}
			
			$menuItems = $menuItemsTemp;	
		}

		public function addMenuItem($menuItemName, $menuName, $pos = '#end', 
			$openingSeparator = false, $endingSeparator = false, $insertBefore = false,
			$bypassAvailableButtons = false, $bypassForcedButtons = false
		)
		{
			$menuName = strtolower($menuName);

			if(!$this->getMceSetting('menu') || empty($menuName) || (!$bypassAvailableButtons && !$this->buttonIsEnabled($menuItemName)))
			{
				return;
			}

			if(!$this->hasMenu($menuName))
			{
				$this->addMenu($menuName);
			}

			if(!in_array($pos, array('#start', '#end')))
			{
				$extra = array(
					'openingSeparator' => $openingSeparator,
					'endingSeparator' => $endingSeparator,
					'insertBefore' =>  $insertBefore
				);
				
				if( !$this->arrayInsertAfterValue(
						$this->mceSettings['menu'][$menuName]['items'],
						$pos,
						$menuItemName,
						$extra
					)
				)
				{
					$pos = '#end';
				}
			}

			if($pos == '#start')
			{
				if($endingSeparator)
				{
					array_unshift($this->mceSettings['menu'][$menuName]['items'], $menuItemName, '|');
				}
				else
				{
					array_unshift($this->mceSettings['menu'][$menuName]['items'], $menuItemName);			
				}
			}
			elseif($pos == '#end')
			{
				if($openingSeparator)
				{
					$this->mceSettings['menu'][$menuName]['items'][] = '|';			
				}
	
				$this->mceSettings['menu'][$menuName]['items'][] = $menuItemName;
	
				if($endingSeparator)
				{
					$this->mceSettings['menu'][$menuName]['items'][] = '|';
				}
			}

			$this->deleteButtons(array($menuItemName), array(), $bypassForcedButtons);
		}

		public function &findMenuItem($menuItemName, $menuName, array $commands = array())
		{
			if(!empty($menuName) && !empty($this->mceSettings['menu'][$menuName]))
			{
				$key = array_search($menuItemName, $this->mceSettings['menu'][$menuName]['items']);

				if($key !== false)
				{
					$array = &$this->mceSettings['menu'][$menuName]['items'];
					
					$deletePrevSeparator = !empty($commands['deletePrevSeparator']);
					$deleteNextSeparator = !empty($commands['deleteNextSeparator']);
					
					
					if(!empty($commands))
					{
						while(key($array) !== $key) next($array);
						$prevVal = prev($array);
						$prevKey = key($array);
	
						$currentVal = next($array);
						$currentKey = key($array);
	
						$nextVal = next($array);
						$nextKey = key($array);
						
						reset($array);
						
						if(!empty($array[$prevKey]) && $array[$prevKey] == '|' && $deletePrevSeparator)
						{
							$array[$prevKey] = null;
						}
						
						if(!empty($array[$nextKey]) && $array[$nextKey] == '|' && $deleteNextSeparator)
						{
							$array[$nextKey] = null;
						}					
						
						return $array[$currentKey];						
					}
					else
					{
						return $array[$key];
					}
				}
				else
				{
					return null;
				}
			}
		}

		public function deleteMenuItem($menuItemName, $menuName, $deleteOpeningSeparator = false, $deleteClosingSeparator = false)
		{
			$commands = array(
				'deletePrevSeparator' => $deleteOpeningSeparator,
				'deleteNextSeparator' =>$deleteClosingSeparator
			);

			$menuItemToDelete = &$this->findMenuItem($menuItemName, $menuName, $commands);
			
			if($menuItemToDelete != null && (is_string($menuItemToDelete) && $menuItemToDelete[0] != '@'))
			{
				$menuItemToDelete = null;
			}
		}

		/* Misc */

		public function getTemplateParams()
		{
			return $this->templateParams;
		}
	
		public function getTemplateParam($key)
		{
			if(isset($this->templateParams[$key]))
			{
				return $this->templateParams[$key];
			}
			
			return null;
		}
	
		public function getTemplateObject()
		{
			return $this->templateObject;
		}

		public function isMobile()
		{
			$visitor = XenForo_Visitor::getInstance();
			
			if( class_exists('Sedo_DetectBrowser_Listener_Visitor') && isset($visitor->getBrowser['isMobile']))
			{
				//External Addon
				return $visitor->getBrowser['isMobile'];
			}
			else
			{
				//XenForo
				return  XenForo_Visitor::isBrowsingWith('mobile');
			}	
		}

		public function arrayInsertAfterValue(&$arraySource, $arraySourceTargetedValue, $elementToAdd, array $extra = array())
		{
			if(!is_array($arraySource))
			{
				return false;
			}

			$insertBefore = (!empty($extra['insertBefore']));
			$reorderArrayKeys = (empty($extra['reorderArrayKeys']) || $extra['reorderArrayKeys'] == true);
			$openingSeparator = (!empty($extra['openingSeparator']));
			$endingSeparator = (!empty($extra['endingSeparator']));
			$loopMode = (!empty($extra['loopMode']));

			if($loopMode)
			{
				unset($extra['loopMode']);
				
				$return = false;
				
				foreach($arraySource as &$arraySourceItem)
				{
					$success = $this->arrayInsertAfterValue($arraySourceItem, $arraySourceTargetedValue, $elementToAdd, $extra);

					if($success)
					{
						$return = $success;
					}
				}
			
				return $return;
			}

			/* Search key of targeted value */
			$targetedKey = array_search($arraySourceTargetedValue, $arraySource);

			if($targetedKey === false)
			{
				return false;
			}
			
			$targetedPos = array_search($targetedKey, array_keys($arraySource));

			//Increment will make it insert after
			if($insertBefore != true)
			{
				$targetedPos++;
			}
			
			if(!$reorderArrayKeys)
			{
				$arraySource = 	
					//cut the array source from the start to the target
					array_slice($arraySource, 0, $targetedPos, true) + 	
					//add the new element
					$elementToAdd +
					//cut the array source from the target to the end
					array_slice($arraySource, $targetedPos, count($arraySource)-$targetedPos, true);
			}
			else
			{

				$newArrayStart = array_slice($arraySource, 0, $targetedPos, true);
				$this->arrayKeyFix = 1;

				if($openingSeparator)
				{
					array_push($newArrayStart, '|');
					$this->arrayKeyFix++;
				}
				
				array_push($newArrayStart, $elementToAdd);
	
				if($endingSeparator)
				{
					array_push($newArrayStart, '|');
					$this->arrayKeyFix++;
				}
				
				$this->tagSeparatorId = 0;
				
				$newArrayEnd = array_slice($arraySource, $targetedPos, count($arraySource)-$targetedPos, true);
				$newArrayEnd = array_map(array($this, '_tagSeparator') , $newArrayEnd);
				$newArrayEnd = array_flip(array_map(array($this, '_arrayInsertAfterValueArrayMap') , array_flip($newArrayEnd)));
				$newArrayEnd = array_map(array($this, '_detagSeparator') , $newArrayEnd);

				$arraySource = $newArrayStart + $newArrayEnd;
			}

			return $arraySource;
    		}
    		
    		protected $arrayKeyFix;
    		protected function _arrayInsertAfterValueArrayMap($el)
    		{
    			return $el + $this->arrayKeyFix;
    		}

    		protected $tagSeparatorId;
    		protected function _tagSeparator($el)
    		{
			if($el != '|')	{ return $el; }

			$this->tagSeparatorId++;
    			return "$el".$this->tagSeparatorId;
    		}

    		protected function _detagSeparator($el)
    		{
    			return (!empty($el[0]) && $el[0] == '|') ? '|' : $el;
    		}

		public function getEditorId()
		{
			return $this->getTemplateParam('editorId');
		}
		
		public function isCustomFieldsEditor($outputViewNameIfTrue = false)
		{
			$editorId = $this->getEditorId();
			$isCustomField = (strpos($editorId,'ctrl_custom_fields') !== false);

			if($outputViewNameIfTrue && $isCustomField)
			{
				return $this->getTemplateParam('viewName');
			}
			else
			{
				return $isCustomField;
			}
		}
}
//Zend_Debug::dump($abc);
