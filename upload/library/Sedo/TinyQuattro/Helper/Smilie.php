<?php
class Sedo_TinyQuattro_Helper_Smilie
{
	protected static $_smilieModel = null;

	public static function getSmilies()
	{
		if (XenForo_Application::isRegistered('smilies'))
		{
			return XenForo_Application::get('smilies');
		}

		return XenForo_Model::create('XenForo_Model_Smilie')->getAllSmiliesForCache();
	}

	public static function getMceSmilies($directSmilies = false)
	{
		if(!is_array($directSmilies))
		{
			$mceSmilie = XenForo_Application::getSimpleCacheData('mce_smilie');

			if(!is_array($mceSmilie))
			{
				$mceSmilie = self::prepareSmilies();
			}
		}
		else
		{
			$mceSmilie = self::prepareSmilies($directSmilies);
		}

		return $mceSmilie;
	}

	public static function getSmiliesAllVersions($categories = null)
	{
		$xenOptions = XenForo_Application::get('options');

		$xenCurrentVersionId = $xenOptions->currentVersionId;
		$showUncategorized = $xenOptions->SmileyManager_showUncategorized; //only for the SM addon
		$showUncategorizedAtBottom = $xenOptions->quattro_smilies_uncat_bottom;		
		
		/*XenForo > 1.3*/
		if($xenCurrentVersionId > 1030031) 
		{
			$mceSmilies = self::getMceSmilies(null);
	
			if($showUncategorizedAtBottom)
			{
				$mceSmilies = self::_bottomizedUncategorizedSmilie($mceSmilies);
			}

			return array(true, $mceSmilies);
		}

		/*XenForo < 1.3*/
		if(!$categories)
		{
			$categories = XenForo_Application::getSimpleCacheData('smilieCategories');
		}

		if(empty($categories))
		{
			return array(false, self::getMceSmilies(null));
		}
		
		$smiliesByCategory = XenForo_Application::getSimpleCacheData('groupedSmilies');
		$mceSmilies = array();
		
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

			$mceSmilies[$categoryId] = array(
				'id' => $category['smilie_category_id'],
				'title' => $category['category_title'],
				'smilies' => self::getMceSmilies($smilies)
			);
		}
		
		if(empty($mceSmilies))
		{
			return array(false,self::getMceSmilies(null));
		}

		if($showUncategorized && $showUncategorizedAtBottom)
		{
			$mceSmilies = self::_bottomizedUncategorizedSmilie($mceSmilies);
		}

		return array(true, $mceSmilies);
	}

	protected static function _bottomizedUncategorizedSmilie(array $smilies)
	{
		/*Uncategorized category should be the first key, let's put it at the bottom*/
		$unCategorized = $smilies[0]; //don't use array_shift, the key will be modified
		$smilies[] = $unCategorized;
		unset($smilies[0]);
		
		return $smilies;
	}

	public static function prepareSmilies($smilies = null)
	{	
		if (!is_array($smilies))
		{
			$smilies = self::getSmilies();
		}

		$output = array();

		foreach ($smilies AS $smilie)
		{
			$spriteCheck = (!empty($smilie['sprite_mode'])) ? true : !empty($smilie['sprite_params']);
			$smilieData = ($spriteCheck ? $smilie['smilie_id'] : $smilie['image_url']);
			$smilieText = array();

			if(isset($smilie['smilieText']))
			{
				$smilieText = $smilie['smilieText'];
			}
			elseif(isset($smilie['smilie_text']))
			{
				//Xen 1.3
				$smilieText = preg_split('/\r?\n/', $smilie['smilie_text'], -1, PREG_SPLIT_NO_EMPTY);
			}
		
			if(is_string($smilieText))
			{
				$smilieText = array($smilieText);
			}

			$type = (is_int($smilieData)) ? 'sprite' : 'link';
			$output[reset($smilieText)] = array($smilie['title'], $smilieData, 'type' => $type);
		}

		return $output;
	}

	public static function cacheMceSmiliesByCategory()
	{
		$xenOptions = XenForo_Application::get('options');
		$xenCurrentVersionId = $xenOptions->currentVersionId;
		
		if($xenCurrentVersionId < 1030031) 
		{
			return;
		}

		$smilieModel = self::_getSmilieModel();
		$smilieCategories = $smilieModel->getAllSmilieCategoriesWithSmilies();
		$mceSmilies = array();

		foreach($smilieCategories as $categoryId => $smiliesInfo)
		{

			if(empty($smiliesInfo['smilies']))
			{
				continue;
			}
			
			$mceSmilies[$categoryId] = array(
				'id' => $categoryId,
				'title' => $smilieModel->getSmilieCategoryMasterTitlePhraseValue($categoryId),
				'smilies' => self::prepareSmilies($smiliesInfo['smilies'])
			);
		}

		XenForo_Application::setSimpleCacheData('mce_smilie', $mceSmilies);
	}

	protected static function _getSmilieModel()
	{
		if(self::$_smilieModel == null)
		{
			return XenForo_Model::create('XenForo_Model_Smilie');
		}
		
		return self::$_smilieModel;
	}
}

//Zend_Debug::dump($mceSmilies);