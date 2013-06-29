<?php
class Sedo_TinyQuattro_Helper_Editor
{
	public static function getEditorSmilies(array $smilies = null)
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

			$output[reset($smilie['smilieText'])] = array($smilie['title'], $smilieData);
		}

		return $output;
	}
}
