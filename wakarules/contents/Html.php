<?php namespace Waka\Utils\WakaRules\Contents;

use Waka\Utils\Classes\Rules\RuleContentBase;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use ApplicationException;
use Waka\Utils\Interfaces\RuleContent as RuleContentInterface;

class Html extends RuleContentBase implements RuleContentInterface
{
    
    use \Waka\Utils\Classes\Traits\RuleHelpers;
    //RuleHepers contient les fonctions  dynamiseUrl, dynamiseMedia, dynamiseLinked, listsNested
    protected $tableDefinitions = [];
    public $extendMediaTypes = [];

    /**
     * Returns information about this event, including name and description.
     */
    public function subFormDetails()
    {
        return [
            'name'        => 'Champs HTML + image',
            'description' => 'Un titre, un champs HTML et une image',
            'icon'        => 'icon-html5',
            'premission'  => 'wcli.utils.cond.edit.admin',
        ];
    }

    public $importExportConfig = [
        'photo' => 'file',
        'media' => 'media',
    ];

    public function getText()
    {
        //trace_log('getText HTMLASK---');
        $hostObj = $this->host;
        //trace_log($hostObj->config_data);
        $text = $hostObj->config_data['html'] ?? null;
        if($text) {
            return strip_tags($text, '<p><br><b><strong><i><em>');
        }
        return parent::getText();

    }

    /**
     * IS true
     */

    public function resolve($datas = []) {
        //trace_log('resolve');
        $imageMode = $this->getConfig('imageMode');
        $objImage = null;
        $functionResolverName = 'dynamise'.studly_case($imageMode);
        $options = [
            'width' =>  $this->getConfig('opt_i_width'),
            'height' => $this->getConfig('opt_i_height'),
            'crop' => $this->getConfig('opt_i_crop'),
        ];
        //trace_log($imageMode);
        //trace_log($functionResolverName);
        

        if($this->methodExists($functionResolverName)) {
            $objImage = $this->$functionResolverName($datas, $options);
        } else {
            //\Log::error($functionResolverName." n'existe pas");
        }
        //Création de la fonction dynamique en fonction de staticImage. Compliqué mais permet d'étendre les fonctions...
        $data = $this->getConfigs();
        //trace_log('resolve');
        $data['html'] = \Twig::parse($data['html'], $datas);
        //on ajoute toutes les données du formulaire
        $data = array_merge($data, ['image' => $objImage]);
        
        //trace_log($data);
        return $data;
    }
    
}
