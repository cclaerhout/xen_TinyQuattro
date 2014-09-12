<?php
class Sedo_TinyQuattro_Helper_BbCodes
{
	/*
	 * Check and get custom tagNames for Quattro BbCodes
	 */
	public static function getQuattroBbCodeTagName($expectedTagName)
	{
		$xenOptions = XenForo_Application::get('options');
		
		switch($expectedTagName)
		{
			case "bcolor":
				$selectedTagName = $xenOptions->quattro_extra_bbcodes_bcolor_tag;
				break;
			case "table":
				$selectedTagName = $xenOptions->quattro_extra_bbcodes_xtable_tag;
				break;
			case "sub":
				$selectedTagName = $xenOptions->quattro_extra_bbcodes_sub_tag;
				break;
			case "sup":
				$selectedTagName = $xenOptions->quattro_extra_bbcodes_sup_tag;
				break;
			case "hr":
				$selectedTagName = $xenOptions->quattro_extra_bbcodes_hr_tag;
				break;
			case "anchor":
				$selectedTagName = $xenOptions->quattro_extra_bbcodes_anchor_tag;
				break;
			default:
				$selectedTagName = '';
		}
		
		if( 	strlen($selectedTagName) == 0
			||
			($expectedTagName == $selectedTagName)
			||
			preg_match('/[^a-z0-9_-]/i', $selectedTagName)
		)
		{
			return $expectedTagName;
		}
		
		return 	$selectedTagName;
	}

	/*
	 * Get XenForo options used for Mce Table
	 */
	public static function getMceTableXenOptions()
	{
		$xenOptions = XenForo_Application::get('options');
		return array(
			'tagName' => self::getQuattroBbCodeTagName('xtable'),
			'size' => array(
				'px' => array(
					'maxWidth' => $xenOptions->quattro_extra_bbcodes_xtable_maxwidth_px,
					'minWidth' => $xenOptions->quattro_extra_bbcodes_xtable_minwidth_px,
					'maxHeight' => $xenOptions->quattro_extra_bbcodes_xtable_maxheight_px,
					'minHeight' => $xenOptions->quattro_extra_bbcodes_xtable_minheight_px		
				),
				'percent' => array(
					'maxWidth' => $xenOptions->quattro_extra_bbcodes_xtable_maxwidth_percent,
					'minWidth' => $xenOptions->quattro_extra_bbcodes_xtable_minwidth_percent,
					'maxHeight' => $xenOptions->quattro_extra_bbcodes_xtable_maxheight_percent,
					'minHeight' => $xenOptions->quattro_extra_bbcodes_xtable_minheight_percent
				)
			),
			'cell' => array(
				'maxCellpadding'  => $xenOptions->quattro_extra_bbcodes_xtable_cellpadding_max,
				'maxCellspacing'  => $xenOptions->quattro_extra_bbcodes_xtable_cellspacing_max
			),
			'border' => array(
				'max'  => $xenOptions->quattro_extra_bbcodes_xtable_border_max
			)
		);
	}

	/*
	 * Map of allowed attributes & CSS by tag = NOT USED
	 */
	public static function getTableOptionsMap($parentTagName = 'xtable', $mergeCss = false)
	{
		$map = array(
			$parentTagName => array(
				'attributes' => array('align', 'bgcolor', 'border', 'cellpadding', 'cellspacing', 'width', 'height'),
				'css' => array('width', 'height', 'float', 'bgcolor', 'marginleft', 'marginright')
			),
			'thead' => array(
				'attributes' => array('align', 'valign'),
				'css' => array()
			),
			'tbody' => array(
				'attributes' => array('align', 'valign'),
				'css' => array()
			),
			'tfoot' => array(
				'attributes' => array('align', 'valign'),
				'css' => array()
			),
			'colgroup' => array(
				'attributes' => array('width', 'height', 'align', 'valign'),
				'css' => array('width', 'height', 'bgcolor')
			),
			'col' => array(
				'attributes' => array('width', 'height', 'align', 'valign', 'span'),
				'css' => array('width', 'height', 'bgcolor')
			),
			'caption' => array(
				'attributes' => array('align'),
				'css' => array('width', 'height', 'bgcolor', 'textalign')
			),
			'tr' => array(
				'attributes' => array('align', 'valign', 'bcolor'),
				'css' => array('width', 'height', 'bgcolor', 'textalign')
			),
			'th' => array(
				'attributes' => array('width', 'height', 'align', 'valign', 'bgcolor', 'colspan', 'nowrap', 'rowspan', 'scope'),
				'css' => array('width', 'height', 'bgcolor', 'textalign')
			),
			'td' => array(
				'attributes' => array('width', 'height', 'align', 'valign', 'bgcolor', 'colspan', 'nowrap', 'rowspan', 'scope'),
				'css' => array('width', 'height', 'bgcolor', 'textalign')
			)
		);
		
		if($mergeCss == false)
		{
			return $map;
		}
		
		foreach($map as &$tag)
		{
			$tag = array_merge($tag['attributes'], $tag['css']);
		}
		
		return $map;
	}
}
