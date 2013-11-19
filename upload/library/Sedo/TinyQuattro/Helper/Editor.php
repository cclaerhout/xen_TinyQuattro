<?php
class Sedo_TinyQuattro_Helper_Editor
{
	public static function getEditorSmilies(array $smilies = null, $readableType = false)
	{
		if (!is_array($smilies))
		{
			if (XenForo_Application::isRegistered('smilies'))
			{
				$smilies = XenForo_Application::get('smilies');
			}
			else
			{
				$smilies = XenForo_Model::create('XenForo_Model_Smilie')->getAllSmiliesForCache();
				XenForo_Application::set('smilies', $smilies);
			}
		}

		$output = array();
		foreach ($smilies AS $smilie)
		{
			$smilieData = (empty($smilie['sprite_params']) ? $smilie['image_url'] : $smilie['smilie_id']);

			if($readableType)
			{
				//For Templates
				$type = (is_int($smilieData)) ? 'sprite' : 'link';
				$output[reset($smilie['smilieText'])] = array($smilie['title'], $smilieData, 'type' => $type);
			}
			else
			{
				//For JS
				$output[reset($smilie['smilieText'])] = array($smilie['title'], $smilieData);
			}
		}

		return $output;
	}
	
	public static function getSmiliesByCategory($categories = null, $readableType = false)
	{
		if(!$categories)
		{
			$categories = XenForo_Application::getSimpleCacheData('smilieCategories');
		}

		if(empty($categories))
		{
			return array(false, self::getEditorSmilies(null, $readableType));
		}
		
		$smiliesByCategory = XenForo_Application::getSimpleCacheData('groupedSmilies');
		$bbmSmilies = array();
		$xenOptions =  XenForo_Application::get('options');
		$showUncategorized = $xenOptions->SmileyManager_showUncategorized;
		$showUncategorizedAtBottom = $xenOptions->quattro_smilies_sm_addon_uncat_bottom;
		
		foreach($smiliesByCategory as $categoryId => $smilies)
		{
			if(!isset($categories[$categoryId]))
			{
				continue;
			}
			
			$category = $categories[$categoryId];

			if(	!isset($category['category_title'])
				||
				empty($category['active'])
				||
				(empty($category['smilie_category_id']) && !$showUncategorized)
			)
			{
				continue;
			}

			$bbmSmilies[$categoryId] = array(
				'id' => $category['smilie_category_id'],
				'title' => $category['category_title'],
				'smilies' => self::getEditorSmilies($smilies, $readableType)
			);
		}
		
		if(empty($bbmSmilies))
		{
			return array(false, self::getEditorSmilies(null, $readableType));
		}

		if($showUncategorized && $showUncategorizedAtBottom)
		{
			/*Uncategorized category should be the first key, let's put it at the bottom*/
			$unCategorized = array_shift($bbmSmilies);
			$bbmSmilies[] = $unCategorized;
		}

		return array(true, $bbmSmilies);
	}
}
