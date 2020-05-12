<?php namespace Waka\Utils\Classes\Traits;

trait NestedFunctions
{

    public function findChildIds($child, $ids)
    {
        if (isset($child->children)) {
            if (count($child->children) > 0) {
                foreach ($child->children as $ch) {
                    array_push($ids, $ch->id);
                    $ids = $this->findChildIds($ch, $ids);
                }
            }
        }

        return $ids;
    }
}
