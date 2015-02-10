<?php

class Sedo_TinyQuattro_Listener_AllInOne
{
	/***
	 * Diktat mode - Inline Edit
	 **/
	public static function controllerPreDispatch(XenForo_Controller $controller, $action)
	{
		$options = XenForo_Application::get('options');
		
		if(	$options->quattro_disable_mobile_inline_edit
			&& (Sedo_TinyQuattro_Helper_Quattro::isEnabled(false, array(null, null, null)) || $options->currentVersionId < 1020031)
			&& XenForo_Visitor::isBrowsingWith('mobile') 
			&& XenForo_Visitor::getInstance()->enable_rte
		)
		{
			//No matter here mobiles or only tablets, stwich off the overlay edit
			$options->messageInlineEdit = 0;
		}

		if($controller->getResponseType() != 'json')
		{
			$requestPaths = XenForo_Application::get('requestPaths');
			$data = array(
				'noJsonRequestPaths' => $requestPaths
			);

			XenForo_Application::getSession()->set('sedoQuattro', $data);
		}
	}

	/***
	 * Extend ControllerPublic editor
	 **/
	public static function ControllerPublicEditor($class, array &$extend)
	{
		if ($class == 'XenForo_ControllerPublic_Editor')
        	{
			$extend[] = 'Sedo_TinyQuattro_ControllerPublic_Editor';
		}
	}

	/***
	 * Bb Code Formatter
	 **/
	public static function BbCodeBaseFormatter($class, array &$extend)
	{
		if ($class == 'XenForo_BbCode_Formatter_Base')
        	{
			$extend[] = 'Sedo_TinyQuattro_BbCode_Formatter_Base';
		}
	}

	/***
	 * Wysiwyg Formatter
	 **/	
	public static function BbCodeWysiwygFormatter($class, array &$extend)	
	{	
		if ($class == 'XenForo_BbCode_Formatter_Wysiwyg')
        	{
			$extend[] = 'Sedo_TinyQuattro_BbCode_Formatter_Wysiwyg';
		}
	}

	/***
	 * Extend the html renderer BbCode (html=>bbcode) ; ie: background-color
	 **/
	public static function LoadClassRendererBbCode($class, array &$extend)
	{
		if ($class == 'XenForo_Html_Renderer_BbCode')
        	{
			$extend[] = 'Sedo_TinyQuattro_Html_Renderer_BbCode';
		}
	}

	/***
	 * Extend DW
	 **/
	public static function ExtendDatawriter($class, array &$extend)
	{
		switch ($class)
		{
		   	case 'XenForo_DataWriter_User':
				if (XenForo_Application::get('options')->get('quattro_parser_mobile_user_option'))
				{
					$extend[] = 'Sedo_TinyQuattro_Datawriter_User_MobileOption';
				}
		   	break;
	   		
	   		case 'XenForo_DataWriter_DiscussionMessage_Post':
	   			if (XenForo_Application::get('options')->get('quattro_parser_fourws_to_tab'))
				{
		   			//2015/02/10: Should not be needed anymore with Sedo_TinyQuattro_Html_Renderer_BbCode::preFilter
		   			$extend[] = 'Sedo_TinyQuattro_Datawriter_DiscussionMessage';
		   		}
		   	break;

	   		case 'XenForo_DataWriter_ConversationMessage':
	   			if (XenForo_Application::get('options')->get('quattro_parser_fourws_to_tab'))
				{
		   			//2015/02/10: Should not be needed anymore with Sedo_TinyQuattro_Html_Renderer_BbCode::preFilter
		   			$extend[] = 'Sedo_TinyQuattro_Datawriter_ConversationMessage';
		   		}
		   	break;	
		}
	}

	/***
	 * Extend Smilie Model
	 **/
	public static function ExtendSmilieModel($class, array &$extend)
	{
	   	if($class == 'XenForo_Model_Smilie')
	   	{
			$extend[] = 'Sedo_TinyQuattro_Model_Smilie';
		}
	}

	/***
	 * Editor setup: for mce modal/ovl attach.
	 **/
	public static function editorSetup(XenForo_View $view, $formCtrlName, &$message, array &$editorOptions, &$showWysiwyg)
	{
		$viewParams = $view->getParams();
		$hash = $type = $id = '';

		if(!empty($viewParams['forum']['node_id']))
		{
			$type = 'newThread';
			$id = 	$viewParams['forum']['node_id'];
		}

		if(!empty($viewParams['thread']['thread_id']))
		{
			$type = 'newPost';
			$id = 	$viewParams['thread']['thread_id'];
		}

		if(!empty($viewParams['post']['post_id']))
		{		
			$type = 'edit';			
			$id = $viewParams['post']['post_id'];
		}

		if(!empty($viewParams['resource']))
		{
			if(!empty($viewParams['resource']['resource_id']))
			{
				$id = $viewParams['resource']['resource_id'];
				$type = 'resource';
			}
			elseif(isset($viewParams['resource']['resource_category_id']))
			{
				$id = $viewParams['resource']['resource_category_id'];
				$type = 'newResource';
			}
		}
		
		if(!empty($viewParams['attachmentParams']['hash']))
		{
			$hash = $viewParams['attachmentParams']['hash'];
		}

		$extraParams['sedo_quattro']['attach'] = array(
			'type' => $type,
			'id' => $id,
			'hash' => $hash
		);
		
		if(is_array($editorOptions))
		{
			$editorOptions += $extraParams;
		}
		else
		{
			$editorOptions = $extraParams;		
		}
	}

	/***
	 * Template helper - PlayWithColors
	 **/
	public static function init_helpers(XenForo_Dependencies_Abstract $dependencies, array $data)
	{
		if(!isset(XenForo_Template_Helper_Core::$helperCallbacks['playwithcolors']))
		{
			XenForo_Template_Helper_Core::$helperCallbacks += array(
				'playwithcolors' => array('Sedo_TinyQuattro_Helper_PlayWithColors', 'init')
			);
		}

		XenForo_Template_Helper_Core::$helperCallbacks += array(
			'get_mce_js_version' => array('Sedo_TinyQuattro_Helper_Quattro', 'getMceJsVersion')
		);		
	}

	/***
	 * Extend View Public - to Bb Code (BbCode <=> Html converter fix)
	 **/
	public static function ViewPublicEditorToBbCode($class, array &$extend)
	{
		if ($class == 'XenForo_ViewPublic_Editor_ToBbCode')
        	{
			$extend[] = 'Sedo_TinyQuattro_ViewPublic_Editor_ToBbCode';
		}
	}

	/***
	 * Extend View Public - to Html
	 **/
	public static function ViewPublicEditorToHtml($class, array &$extend)
	{
		if ($class == 'XenForo_ViewPublic_Editor_ToHtml')
        	{
			$extend[] = 'Sedo_TinyQuattro_ViewPublic_Editor_ToHtml';			
		}
	}
	

	/***
	 * Get Mce Config and set it as an application + CCV management
	 **/
	public static function controllerPreView(XenForo_FrontController $fc,
			 XenForo_ControllerResponse_Abstract &$controllerResponse,
			 XenForo_ViewRenderer_Abstract &$viewRenderer,
			 array &$containerParams
	)
	{
		self::_controllerPreView($fc, $controllerResponse, $viewRenderer, $containerParams);
	}
	
	protected static function _controllerPreView(XenForo_FrontController $fc,
			 XenForo_ControllerResponse_Abstract &$controllerResponse,
			 XenForo_ViewRenderer_Abstract &$viewRenderer,
			 array &$containerParams
	)
	{	
		//to do: try to see if the XenForo_Application::getSession can't be used instead in an earlier listener
		if(!XenForo_Visitor::getUserId())
		{
			return;
		}

		$isControllerAdmin = (strstr($controllerResponse->controllerName, 'ControllerAdmin')) ? true : false;
      		$controllerName = (isset($controllerResponse->controllerName)) ? $controllerResponse->controllerName : NULL;
      		$controllerAction = (isset($controllerResponse->controllerAction)) ? $controllerResponse->controllerAction : NULL;
      		$viewName = (isset($controllerResponse->viewName)) ? $controllerResponse->viewName : NULL;
      		$isJson = ($viewRenderer instanceof XenForo_ViewRenderer_Json) ? true : false;

		if(!$isJson)
		{
			list($enable, $bbmParams) = Sedo_TinyQuattro_Helper_Quattro::isEnabled(true, array($controllerName, $controllerAction, $viewName));
			XenForo_Application::set('mceConfig', array($enable, $bbmParams));
			XenForo_Helper_Cookie::setCookie('mce_ccv', "$controllerName,$controllerAction,$viewName");
		}
		else
		{
			$bbmCCVConfigs = XenForo_Application::get('options')->get('Bbm_Bm_Cust_Config');
			$useCurrentCCV = false;
			
			if(is_array($bbmCCVConfigs))
			{
				foreach($bbmCCVConfigs as $bbmCCVConfig)
				{
					if(!empty($bbmCCVConfig['viewname']) && $bbmCCVConfig['viewname'] == $viewName)
					{
						$useCurrentCCV = true;
						break;
					}
				}
			}

			if(!$useCurrentCCV)
			{
				//n-1 ccv
				$ccv = explode(',' , XenForo_Helper_Cookie::getCookie('mce_ccv'));
				$controllerName = isset($ccv[0]) ? $ccv[0] : null;
				$controllerAction = isset($ccv[1]) ? $ccv[1] : null;
				$viewName = isset($ccv[2]) ? $ccv[2] : null;
			}
			
			list($enable, $bbmParams) = Sedo_TinyQuattro_Helper_Quattro::isEnabled(true, array($controllerName, $controllerAction, $viewName));
			XenForo_Application::set('mceConfig', array($enable, $bbmParams));			
		}
      	}
}
//Zend_Debug::dump($class);