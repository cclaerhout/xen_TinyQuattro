<?php

class Sedo_TinyQuattro_Model_Smilie extends XFCP_Sedo_TinyQuattro_Model_Smilie
{
	public function massUpdateDisplayOrder(array $order)
	{
		parent::massUpdateDisplayOrder($order);
		$this->rebuildSmilieCache();
	}

	public function rebuildSmilieCache()
	{
		$parent = parent::rebuildSmilieCache();
		$this->_rebuildMceSmilieCache();
		return $parent;
	}
	
	protected function _rebuildMceSmilieCache()
	{
		Sedo_TinyQuattro_Helper_Smilie::cacheMceSmiliesByCategory();
	}	
}