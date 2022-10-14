<?php namespace Waka\Utils\WakaRules\Contents;

use Waka\Utils\Classes\Rules\RuleContentBase;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use ApplicationException;
use Waka\Utils\Interfaces\RuleContent as RuleContentInterface;
use System\Classes\ImageResizer;
use System\Classes\MediaLibrary;

class ListeImages extends RuleContentBase implements RuleContentInterface
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
            'name'        => 'Une liste d\'images',
            'description' => 'UNe liste d\'images à partir d\'un répertoire',
            'icon'        => 'icon-pictures',
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
        $text = $hostObj->config_data['listeimages'] ?? null;
        if($text) {
            return "En construction";
        }
        return parent::getText();

    }

    /**
     * IS true
     */

    public function resolve($datas = []) {
        //trace_log('resolve');
        //Création de la fonction dynamique en fonction de staticImage. Compliqué mais permet d'étendre les fonctions...
        $data = $this->getConfigs();
        $listeImages = MediaLibrary::instance()->listFolderContents($data['media'], 'title', null, false);
        $finalImages = [];
        foreach($listeImages as $image) {
            $path_parts = pathinfo($image->publicUrl);
            $basename = $path_parts['filename'];
            array_push($finalImages, ['path'=> $image->publicUrl, 'name'=>  $basename]);
        }
        $data = array_merge($data, ['images' => $finalImages]);
        //trace_log($data);
        return $data;
    }
    
}
