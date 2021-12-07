<?php namespace Waka\Utils\Classes\Traits;

trait WakaContent
{
    public function getContent($code) {
        $content = $this->rule_contents()->where('code', $code);
        if($content->count()) {
            trace_log($content->get()->toArray());
            return $content->first()->resolve();
        } else {
            return null;
        }
        
    }

    public function getResursiveContent($code) {
        $content = $this->rule_contents()->where('code', $code);
        if($content->count()) {
            return $content->first()->resolve();
        } else {
            $parents = $this->getParents()->sortByDesc('nest_depth');
            foreach ($parents as $parent) {
                $subContent = $parent->rule_contents()->where('code', $code);
                trace_log($parent->name);
                if($subContent->count()) {
                    return $subContent->first()->resolve();
                }
            }
            return null;
        }

    }
}
