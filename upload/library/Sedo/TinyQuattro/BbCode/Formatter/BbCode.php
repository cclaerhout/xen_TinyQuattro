<?php

class Sedo_TinyQuattro_BbCode_Formatter_BbCode extends XenForo_BbCode_Formatter_Base
{
    protected function _getTagRule($tagName)
    {
        return false;
    }

    public function filterString($string, array $rendererStates)
    {
        return $string;
    }
}