<?php namespace Waka\Utils\Classes\Traits;

use Lang;
use \Waka\Informer\Models\Inform;
use Session;

trait WakAnonymize
{

    public $anonymizeText = "Anonymisé";

    //public $anoymizeFields = [];


    public function scopeNotAnonymized($query) {
        $query->where('is_anonymized','<>', true);
    }
    public function scopeAnonymized($query) {
        $query->where('is_anonymized', true);
    }

    
    



    public function wakAnonymize() {
        //
        foreach($this->anonymizeFields as $field) {
            if(is_object($this->$field)) {
                $fields = $this->$field;
                foreach($fields as $field) {
                    $field->delete();
                }
            } elseif (is_array($this->$field)) {
                $this->$field = [$this->anonymizeText];
            } else {
                $this->$field = $this->anonymizeText;
            }
        }
        $this->is_anonymized = true;
        $this->save();
    }
    
}
