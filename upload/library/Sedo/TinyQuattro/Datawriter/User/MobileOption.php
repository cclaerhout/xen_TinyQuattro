<?php
class Sedo_TinyQuattro_DataWriter_User_MobileOption extends XFCP_Sedo_TinyQuattro_DataWriter_User_MobileOption
{
	protected function _getFields() 
	{
		$parent = parent::_getFields();
		$parent['xf_user_option']['quattro_rte_mobile'] = array(
				'type' => self::TYPE_BOOLEAN, 
				'default' => 1
		);

		return $parent;
	}

	protected function _preSave()
	{
		$options = XenForo_Application::get('options');
		$_input = new XenForo_Input($_REQUEST);

		if(!$options->quattro_parser_mobile_user_option)
		{
			return parent::_preSave();
		}

		/***
		*	The above check field is to be sure the checkbox field is available 
		*	Why? => return false if option is really false or if the "quattro_rte_mobile" field is not there
		*	Sometimes the DW is called from different controllers and the "quattro_rte_mobile" field is not there
		**/
		$mobileOptionChecker = $_input->filterSingle('quattro_rte_mobile_chk', XenForo_Input::UINT);

		if(!empty($mobileOptionChecker))
		{
			$mobileOption = $_input->filterSingle('quattro_rte_mobile', XenForo_Input::UINT);
			$this->set('quattro_rte_mobile', $mobileOption);
		}

		return parent::_preSave();
	}
}

