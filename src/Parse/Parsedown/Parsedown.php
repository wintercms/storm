<?php namespace Winter\Storm\Parse\Parsedown;

use ParsedownExtra;

class Parsedown extends ParsedownExtra
{
    public function setUnmarkedBlockTypes($unmarkedBlockTypes)
    {
        $this->unmarkedBlockTypes = $unmarkedBlockTypes;

        return $this;
    }
}
