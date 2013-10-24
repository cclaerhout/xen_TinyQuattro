<?php
class Sedo_TinyQuattro_Listener_EditorSetup
{
	public static function ExtendOptions(XenForo_View $view, $formCtrlName, &$message, array &$editorOptions, &$showWysiwyg)
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
}
//Zend_Debug::dump($abc);