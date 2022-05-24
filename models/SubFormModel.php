<?php namespace Waka\Utils\Models;

use Model;
use Exception;
use SystemException;

/**
 * SubForm Model
 */
class SubFormModel extends Model
{
    use \Winter\Storm\Database\Traits\Validation;
    use \Winter\Storm\Database\Traits\Sortable;
    use \Winter\Storm\Database\Traits\Purgeable;

    /**
     * @var array Guarded fields
     */
    protected $guarded = [];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [];

    /**
     * @var array The rules to be applied to the data.
     */
    public $rules = [];

    /**
     * @var array List of attribute names which are json encoded and decoded from the database.
     */
    protected $jsonable = ['config_data'];

    protected $purgeable = ['saved_from_builder'];
    
    

    /**
     * Extends this model with the ask class
     * @param  string $class Class name
     * @return boolean
     */
    public function applySubFormClass($class = null)
    {
        if (!$class) {
            $class = $this->class_name;
        }

        if (!$class) {
            return false;
        }

        if (!$this->isClassExtendedWith($class)) {
            $this->extendClassWith($class);
        }

        $this->class_name = $class;
        return true;
    }

    public function beforeSave()
    {
        //trace_log("beforeSavelog : ".$this->code);
        //trace_log($this->toArray());
        $this->setCustomData();
    }

    public function applyCustomData()
    {
        $this->setCustomData();
        $this->loadCustomData(false);
    }

    protected function loadCustomData()
    {
        $this->setRawAttributes((array) $this->getAttributes() + (array) $this->config_data, true);
    }

    public function decryptConfigJsonData($config) {
        if (!$subFormObj = $this->getSubFormObject()) {
            throw new SystemException(sprintf('Unable to find subform object [%s]', $this->getSubFormClass()));
        }
        foreach($subFormObj->jsonable as $jsonKey) {
            if($config[$jsonKey] ?? false) {
                $config[$jsonKey] = json_decode($config[$jsonKey], true);
            }
        }
        return $config;
    }

    function isJson() {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    public function encryptConfigJsonData($config) {
        if (!$subFormObj = $this->getSubFormObject()) {
            throw new SystemException(sprintf('Unable to find subForm object [%s]', $this->getSubFormClass()));
        }
        foreach($subFormObj->jsonable as $jsonKey) {
            if($config[$jsonKey] ?? false) {
                $config[$jsonKey] = json_encode($config[$jsonKey], true);
            }
        }
        return $config;

    }

    public function setCustomData()
    {
        //trace_log('setCustomData');
        if (!$subFormObj = $this->getSubFormObject()) {
            throw new SystemException(sprintf('Unable to find subForm object [%s]', $this->getSubFormClass()));
        }   
        
        /*
         * Spin over each field and add it to config_data
         */
        $config = $subFormObj->getFieldConfig();
        //
        /*
         * SubForm class has no fields
         */
        if (!isset($config->fields)) {
            return;
        }

        $staticAttributes = $this->staticAttributes;
        $realFields = $this->realFields;

        

        //Gestion des tabs si il y en a
        $fieldInConfigWithTabs = $this->getFieldsFromConfig($config);
        $fieldInConfig = array_diff(array_keys($fieldInConfigWithTabs), $realFields);
        // trace_log("------------------------------fieldInConfig-------------------------------");
        // trace_log($fieldInConfig);

        $fieldAttributes = array_merge($staticAttributes, $fieldInConfig);

        // trace_log("------------------------------fieldAttributes-------------------------------");
        // trace_log($fieldAttributes);

        $dynamicAttributes = array_only($this->getAttributes(), $fieldAttributes);

        // trace_log("------------------------------dynamicAttributes-------------------------------");
        // trace_log($dynamicAttributes);
        //
        $dynamicAttributes = $this->decryptConfigJsonData($dynamicAttributes);
        //
        $this->config_data = $dynamicAttributes;

        // trace_log("------------------------------fieldAttributes-------------------------------");
        // trace_log($fieldAttributes);

        // trace_log("------------------------------fieldAttributes-------------------------------");
        // trace_log($dynamicAttributes);

        // trace_log("------------------------------getAllRealFields-------------------------------");
        // trace_log($dynamicAttributes);

        $this->setRawAttributes(array_only($this->getAttributes(), $this->getAllRealFields()));

        // trace_log("------------------------------fieldAttributes-------------------------------");
        // trace_log(array_except($this->getAttributes(), $fieldAttributes));

        //trace_log($this->getAttributes());
        
    }

    public function getAllRealFields() {
        $modelRealFields = $this->realFields;
        //TODO je n'arrive pas as acceder à $this->morphName
        //$modeleAble = [$this->morphName.'_id', $this->morphName.'_type'];
        $modeleAble = [
            'actioneable_id',
            'actioneable_type',
            'askeable_id',
            'askeable_type',
            'fnceable_id',
            'fnceable_type',
            'ruleeable_id',
            'ruleeable_type',
            'contenteable_id',
            'contenteable_type',

        ];
        $base = ['id', 'data_source', 'sort_order'];
        $dates = ['created_at', 'updated_at'];
        $allRealFields = array_merge($this->realFields, $base, $modeleAble, $dates, ['config_data', 'class_name'] );
        //trace_log($allRealFields);
        return $allRealFields;
    }

    public function getFieldsFromConfig($config) {
        $fields = $config->fields;
        $tabs = $config->tabs['fields'] ?? [];
        //trace_log($tabs);
        //trace_log(array_merge($fields, $tabs));
        return array_merge($fields, $tabs);

    }

    public function afterFetch()
    {
        //trace_log('afterFetch');
        $this->applySubFormClass();
        //TRICKY ! Gestion du problème des json. puisque les champs json vont être transfromé en array, je forcer l'encrypt juste avant de montrer le champs
        $this->config_data = $this->encryptConfigJsonData($this->config_data);
        //trace_log($this->config_data);
        $this->loadCustomData();
    }

    public function getText()
    {
        //Je ne comprend pas d' ou vient subForm text. Il empèche de retrouver le texte correctement
        // if (strlen($this->subForm_text)) {
        //     return $this->subForm_text;
        // }

        if ($subFormObj = $this->getSubFormObject()) {
            return $subFormObj->getText();
        }
    }

    public function getSubFormObject()
    {
        $this->applySubFormClass();
        if(!$this->getSubFormClass()) {
            return;
        }

        return $this->asExtension($this->getSubFormClass());
    }

    public function getSubFormClass()
    {
        return $this->class_name;
    }

    public function filterFields($fields, $context = null) {
        if(!$this->getSubFormObject()) {
            return;
        }
        return $this->getSubFormObject()->filterFields($fields, $context);
    }

    /**
     * EXPORTER
     */
    public function prepareExport($path) {
        $exportConfigs =  $this->importExportConfig;
        $datas = $this->toArray();
        unset($datas['id']);
        if($datas['optioneable_id'] ?? false) {
            unset($datas['optioneable_id']);
            unset($datas['optioneable_type']);
        }
        if($datas['actioneable_id'] ?? false) {
            unset($datas['actioneable_id']);
            unset($datas['actioneable_type']);
        }
        if($datas['contenteable_id'] ?? false) {
            unset($datas['contenteable_id']);
            unset($datas['contenteable_type']);
        }
        $code = $this->getCode();
        if(!$exportConfigs) {
            return $datas;
        }
        //trace_log("prepareExport code : ".$code);
        //trace_log($datas);
        foreach($exportConfigs as $key=>$exportConfig) {
            $valueToExport = null;
            $value = $this->getConfig($key);
            //trace_log($code." EXPORT CONFIG : ".$exportConfig." Value (next ligne) ");
            //trace_log($value);
            if($exportConfig == 'media' && $value) {
                    $fileName = basename($value);
                    $finalPath = $path.'/media'.'/'.$code;
                    \Storage::makeDirectory($finalPath);
                    $fileContent = \System\Classes\MediaLibrary::instance()->get($value);
                    //trace_log($finalPath.'/'.$fileName);
                    //Creation du fichier de sauvegarde
                    \Storage::put($finalPath.'/'.$fileName, $fileContent);
                    $valueToExport =  [
                        'type' => 'media',
                        'name' => $fileName,
                        'savePath' =>  $finalPath,
                        'value' => $value
                    ];
                    //trace_log($valueToExport);
            }
            if($exportConfig == 'mediaFolder' && $value) {
                    $folderName = basename($value);
                    $finalPath = $path.'/media'.'/'.$code.'/'.$folderName;
                    \Storage::makeDirectory($finalPath);
                    $files = \System\Classes\MediaLibrary::instance()->listFolderContents($value);
                    foreach($files as $file) {
                        $fileName = basename($file->path);
                        $content = \File::get(storage_path('../'.$file->publicUrl));
                        \Storage::put($finalPath.'/'.$fileName, $content);
                    }
                    $valueToExport =  [
                        'type' => 'mediaFolder',
                        'savePath' =>  $finalPath,
                        'folderName' => $folderName,
                        'value' => $value
                    ];
            }
            else if($exportConfig == 'file' && $this->$key) {
                    //trace_log('IL FAUT  EXPORTER UN FILE');
                    $finalPath = $path.'/uploads'.'/'.$code;
                    \Storage::makeDirectory($finalPath);
                    $file = $this->$key;
                    $fileName = $file->file_name;
                    \Storage::put($finalPath.'/'.$fileName, $file->getContents());
                    $valueToExport =  [
                        'type' => 'file',
                        'name' => $fileName,
                        'savePath' =>  $finalPath,
                        'value' => $this->$key
                    ];
            }
            else if ($exportConfig == 'files' && $this->$key) {
                    //trace_log('IL FAUT EXPORTER DES FILE');
                    //trace_log($this->$key);
                    $finalPath = $path.'/uploads'.'/'.$code;
                    \Storage::makeDirectory($finalPath);
                    $files = $this->$key;
                    $valueToExport = [];
                    foreach($files as $file) {
                        $fileName = $file->file_name;
                        \Storage::put($finalPath.'/'.$fileName, $file->getContents());
                        $file = [
                            'type' => 'file',
                            'name' => $fileName,
                            'savePath' =>  $finalPath,
                        ];
                        array_push($valueToExport, $file);
                    }
            }
            else if (method_exists($this, $exportConfig)) {
                    //trace_log($valueToExport);
                    
            }
               
            $datas[$key] = $valueToExport;
        }
        //trace_log($datas);
        return $datas;
    }
    public function prepareImport($path, $datas) {
        $importConfigs =  $this->importExportConfig;
        //trace_log('prepareImport------------');
        //trace_log($importConfigs);
        //trace_log($datas);
        if(!$importConfigs) {
            return $datas;
        }
        foreach($importConfigs as $key=>$importConfig) {
            //trace_log("importConfig key : ".$key);;
            //trace_log($importConfig);;
            //trace_log($datas[$key]);;
            
            $valueFromData = $datas[$key] ?? null;
            if($importConfig == 'media' && $valueFromData) {
                $path = $valueFromData['savePath'].'/'.$valueFromData['name'];
                $fileSaved = \Storage::get($path);
                \System\Classes\MediaLibrary::instance()->put($valueFromData['value'], $fileSaved);
                $datas[$key] = $valueFromData['value'];
            }
            if($importConfig == 'mediaFolder' && $valueFromData) {
                //$folderName = $valueFromData['folderName'];
                $path = $valueFromData['savePath'];
                $files = \Storage::listContents($path);
                
                foreach($files as $file) {
                    //$fileToSave = \Storage::get($file->path);
                    $content = \File::get(storage_path('app/'.$file['path']));
                    $path = $valueFromData['value'].'/'.$file['basename'];
                    \System\Classes\MediaLibrary::instance()->put($path, $content);
                }
                
                $datas[$key] = $valueFromData['value'];
            }
            else if($importConfig == 'file' && $valueFromData) {
                $path = $valueFromData['savePath'].'/'.$valueFromData['name'];
                $fileSaved = \Storage::get($path);
                $file = (new \System\Models\File)->fromData($fileSaved, $valueFromData['name']);
                $this->$key()->add($file);
                $datas[$key] = $valueFromData['value'];
                 
            }
            else if ($importConfig == 'files' && $valueFromData) {
                $files = $valueFromData;
                //trace_log($valueFromData);
                foreach($files as $fileData) {
                    $path = $fileData['savePath'].'/'.$fileData['name'];
                    $fileSaved = \Storage::get($path);
                    $file = (new \System\Models\File)->fromData($fileSaved, $fileData['name']);
                    $this->$key()->add($file);
                }
            }

        }
        return $datas;
    }
}
