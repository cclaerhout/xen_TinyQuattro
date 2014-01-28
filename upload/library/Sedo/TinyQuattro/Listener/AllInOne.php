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
			&& (Sedo_TinyQuattro_Helper_Quattro::isEnabled() || $options->currentVersionId < 1020031)
			&& XenForo_Visitor::isBrowsingWith('mobile') 
			&& XenForo_Visitor::getInstance()->enable_rte
		)
		{
			//No matter here mobiles or only tablets, stwich off the overlay edit
			$options->messageInlineEdit = 0;
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
	   			if (XenForo_Application::get('options')->get('quattro_parser_wysiwyg_to_bb'))
				{
		   			$extend[] = 'Sedo_TinyQuattro_Datawriter_DiscussionMessage';
		   		}
		   	break;

	   		case 'XenForo_DataWriter_ConversationMessage':
	   			if (XenForo_Application::get('options')->get('quattro_parser_wysiwyg_to_bb'))
				{
		   			$extend[] = 'Sedo_TinyQuattro_Datawriter_ConversationMessage';
		   		}
		   	break;	   		
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
}
//Zend_Debug::dump($class);