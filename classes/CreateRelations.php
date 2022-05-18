<?php namespace Waka\Utils\Classes;

use Brick\VarExporter\VarExporter;
use Winter\Storm\Support\Collection;

class CreateRelations
{
    private $parent;
    public $relations;


    public function __construct($parent, $relations) {
        $this->parent = $parent;
        $this->relations = new Collection();
        $this->yamlRead = false;
        $this->typeCode = ['many','belong', 'hasOne', 'oneThrough', 'belongsMany', 'manyThrough', 'morphMany' , 'morphOne', 'attachOne', 'attachMany', 'morphTo' ];
        foreach($relations as $relation) {
            $relationParsed = $this->createRelation($relation);
            $this->relations->push($relationParsed);
        }
    }
   
    public function getModelRelations() {
        //trace_log($this->relations->toArray());
        $modelRelations = [];
        foreach($this->typeCode as $code) {
            $modelRelations[$code] = $this->relations->where('type', '==', $code);
        }
        return $modelRelations;
    }

    public function getControllerRelations() {
        //trace_log($this->relations->toArray());
        $controllerRelations = $this->relations->where('createYamls', true)->toArray();
        return $controllerRelations;
    }

    public function getOneRelation($var) {
        //trace_log($this->relations->toArray());
        return $this->relations->where('var', $var)->first();
    }

    public function isBehaviorRelationNeeded() {
        return count($this->getControllerRelations());
    }

    public function createRelation($item)
    {
        $relation = $item['var'] ?? null;
        if (!$relation) {
            return $item;
        }
        
        $var = $item['var'];
        $type = $item['type'];
        $dotedClass = $item['class'];
        $options = $item['options'] ?? null;
        $columns = $item['columns'] ?? 'auto';
        $fields =  $item['fields'] ?? 'auto';
        $record_url =  $item['record_url'] ?? null;
        $createYamls =  $item['yamls'] ?? false;
        //
        $filter = $item['filters'] ?? null;
        if($filter == '') {
            $filter = null;
        }
        //trace_log("filtre : ".$filter);
        $filters = [];
        $filterName = $filter ?  'config_filters_for_'.$var.'.yaml' : null;
        if($filter == 'manage' || $filter == 'all') {
            $filters['manage'] = $filterName;
        }
        if($filter == 'view' || $filter == 'all') {
            $filters['view'] = $filterName;
        }
        if($filter && !in_array($filter, ['manage', 'view', 'all'])) {
            throw new \ApplicationException('Filter doit avoir pour valeur NULL(vide), manage, view, all '.$filter);
        }
        //
        
        //'/\s/' prmet d'enlever les espaces en trop
        $removeFields = explode('|', preg_replace('/\s/', '', $item['remove_fields']));
        $removeColumns = explode('|', preg_replace('/\s/', '', $item['remove_columns']));
        $fieldsExport = explode('|', preg_replace('/\s/', '', $item['fields_export']));

        //
        $manage_opt = null;
        if($item['manage_opt']) {
            $manage_opt = explode('|', $item['manage_opt']);
        }
        $view_opt = null;
        if($item['view_opt']) {
            $view_opt = explode('|', $item['view_opt']);
        }
        
        
        //
        $relationClass = $this->getRelationClass($dotedClass, $var);
        $relationPath = $this->getRelationPath($dotedClass, $var, $createYamls); 
        $relationDetail = $this->getRelationDetail($dotedClass, $var);
        $options = $this->getRelationOptions($options);
        if($columns == 'auto') {
            $columns = $this->getRelationPathConfig($dotedClass, $var,'columns', $createYamls);
        }
        if($fields == 'auto') {
            $fields = $this->getRelationPathConfig($dotedClass, $var,'fields', $createYamls);
        }
        if($record_url == 1 ) {
            $record_url = $this->getRecordUrl($dotedClass, $var);
        }
        
        $userRelation = $relationClass == 'Backend\Models\User' ? true : false;
        
        //Création des relations pour le modèles
        $relationarray[] = $relationClass;

        if($options) {
            foreach($options as $key=>$option) {
                if(str_contains($option, ',')) {
                    $option = explode(',', $option);
                }
                $relationarray[$key] = $option;
            }
        }
        //trace_log( $relationarray);
        $relationarray =  VarExporter::export($relationarray,VarExporter::INLINE_NUMERIC_SCALAR_ARRAY,2);
        $relationarray = str_replace('0 => ', '',$relationarray);
        
        //Suppresion des doubles antiSlash
        $relationarray = str_replace('\\\\', '\\', $relationarray); 
        
        $relation = $item ;     

        $addToRelation =  [
            'var' => $var,
            'type' => $type,
            'class'  =>$relationClass,
            'path' =>$relationPath,
            'detail' =>$relationDetail,
            'columns' =>$columns,
            'fields' =>$fields,
            'relationarray' => $relationarray,
            'userRelation' => $userRelation,
            'createYamls' => $createYamls,
            'remove_fields' => $removeFields,
            'remove_columns' => $removeColumns,
            'fields_export' => $fieldsExport,
            'filters' => $filters,
            'manage_opt' => $manage_opt,
            'view_opt' => $view_opt,
            'record_url' => $record_url,
        ];

        
        $relation = array_merge($relation, $addToRelation);
        $relationItem = $this->createConfig($var, $type, $item);
        
        $relation = array_merge($relation, $relationItem);

        return $relation;

    }

    public function createConfig($var, $type, $item) {
        //trace_log($type);
        if(!in_array($type, ['belong', 'oneThrough', 'belongsMany', 'hasOne', 'morphMany', 'morphOne', 'many', 'manyThrough', 'attachMany', 'attachOne', 'morphTo' ])) {
            //trace_log("il y aune erreur");
            throw new \ApplicationException('verifier la colone type de relation ');
        }

        if ($type == 'belong') {
            return [
                'name' => $var,
                'singular_name' => str_singular(camel_case($var)),
                'view_list' => false,
                'view_form' => !$item['yamls_read'],
                'view_form_read' => $item['yamls_read'],
                'manage_form' => !($item['record_url'] && !str_contains($item['toolbar'], 'create')),
                'manage_list' => str_contains($item['toolbar'], 'link'),
                'show_check' => str_contains($item['toolbar'], 'delete') or str_contains($item['toolbar'], 'unlink'),
            ];
        }
        if ($type == 'hasOne') {
            return [
                'name' => $var,
                'singular_name' => str_singular(camel_case($var)),
                'view_list' => false,
                'view_form' => !$item['yamls_read'],
                'view_form_read' => $item['yamls_read'],
                'manage_form' => !($item['record_url'] && !str_contains($item['toolbar'], 'create')),
                'manage_list' => str_contains($item['toolbar'], 'link'),
            ];
        }
        if ($type == 'oneThrough') {
            return [
                'name' => $var,
                'singular_name' => str_singular(camel_case($var)),
                'view_list' => false,
                'view_form' => !$item['yamls_read'],
                'view_form_read' => $item['yamls_read'],
                'manage_form' => !($item['record_url'] && !str_contains($item['toolbar'], 'create')),
                'manage_list' => str_contains($item['toolbar'], 'link'),
            ];
        }
        if ($type == 'belongsMany') {
            return [
                'name' => $var,
                'singular_name' => str_singular(camel_case($var)),
                'view_list' => true,
                'view_form' => false,
                'manage_form' => !($item['record_url'] && !str_contains($item['toolbar'], 'create')),
                'manage_list' => str_contains($item['toolbar'], 'add') or str_contains($item['toolbar'], 'link'),
                'show_check' => str_contains($item['toolbar'], 'delete') or str_contains($item['toolbar'], 'unlink'),
            ];
        }
        if ($type == 'morphMany') {
            return [
                'name' => $var,
                'singular_name' => str_singular(camel_case($var)),
                'view_list' => true,
                'view_form' => false,
                'manage_form' => !($item['record_url'] && !str_contains($item['toolbar'], 'create')),
                'manage_list' => str_contains($item['toolbar'], 'add') or str_contains($item['toolbar'], 'link'),
                'show_check' => str_contains($item['toolbar'], 'delete') or str_contains($item['toolbar'], 'unlink'),
            ];
        }
        if ($type == 'morphOne') {
            return [
                'name' => $var,
                'singular_name' => str_singular(camel_case($var)),
                'view_list' => false,
                'view_form' => !$item['yamls_read'],
                'view_form_read' => $item['yamls_read'],
                'manage_form' => !($item['record_url'] && !str_contains($item['toolbar'], 'create')),
                'manage_list' => str_contains($item['toolbar'], 'link'),
            ];
        }
        if ($type == 'many') {
            return [
                'name' => $var,
                'singular_name' => str_singular(camel_case($var)),
                'view_list' => true,
                'view_form' => false,
                'manage_form' => !($item['record_url'] && !str_contains($item['toolbar'], 'create')),
                'manage_list' => str_contains($item['toolbar'], 'add') or str_contains($item['toolbar'], 'link'),
                'show_check' => str_contains($item['toolbar'], 'delete') or str_contains($item['toolbar'], 'unlink'),
            ];
        }
        if ($type == 'manyThrough') {
            return [
                'name' => $var,
                'singular_name' => str_singular(camel_case($var)),
                'view_list' => true,
                'view_form' => false,
                'manage_form' => !($item['record_url'] && !str_contains($item['toolbar'], 'create')),
                'manage_list' => str_contains($item['toolbar'], 'add') or str_contains($item['toolbar'], 'link'),
                'show_check' => str_contains($item['toolbar'], 'delete') or str_contains($item['toolbar'], 'unlink'),
            ];
        }
        if ($type == 'attachMany') {
            return [
                'name' => $var,
            ];
        }
        if ($type == 'attachOne') {
            return [
                'name' => $var,
            ];
        }
        if ($type == 'morphTo') {
            return [
                'name' => $var,
            ];
        }


    }

    public function getRelationKeyVar($value, $key)
    {
        $parts = explode('.', $value);
        $r_author = $parts[0];
        $r_plugin = $parts[1];
        $r_model = $parts[2] ?? camel_case(str_singular($key));
        return $r_model;
    }

    public function getRelationClass($value, $key)
    {
        if ($value == 'self') {
            return ucfirst($this->parent->w_author) . '\\' . ucfirst($this->parent->w_plugin) . '\\Models\\' . ucfirst(camel_case(str_singular($key)));
        } elseif ($value == 'user') {
            return 'Backend\Models\User';
        } elseif ($value == 'cloudi') {
            return 'Waka\Cloudis\Models\CloudiFile';
        } elseif ($value == 'file') {
            return 'System\Models\File';
        } else {
            $parts = explode('.', $value);
            if(count($parts) <2) {
                throw new \ApplicationException('verifier la colone class. veleur self, cloudi, file ou wcli.xxx.yyy yyy optionel ');
            }
            $r_author = $parts[0];
            $r_plugin = $parts[1];
            $r_model = $parts[2] ?? camel_case(str_singular($key));
            return ucfirst($r_author) . '\\' . ucfirst($r_plugin) . '\\Models\\' . ucfirst($r_model);
        }
    }

    public function getRelationDetail($value, $key)
    {
        if ($value == 'self') {
            return [
                'author' => strtolower($this->parent->w_author),
                'plugin' => strtolower($this->parent->w_plugin),
                'model' => strtolower(camel_case($key)),
            ];
        } elseif ($value == 'user') {
            return [
                'author' => null,
                'Backend' => 'Backend',
                'model' => 'user',
            ];
        } elseif ($value == 'cloudi') {
            return [
                'author' => 'waka',
                'Backend' => 'cloudi',
                'model' => 'cloudifile',
            ];
        } elseif ($value == 'file') {
            return [
                'author' => null,
                'Backend' => 'system',
                'model' => 'file',
            ];
        } else {
            $parts = explode('.', $value);
            $r_model = $parts[2] ?? $key;
            return [
                'author' => strtolower($parts[0]),
                'plugin' => strtolower($parts[1]),
                'model' => strtolower($r_model),
            ];
        }
    }

    public function getRelationPath($value, $key, $createYamlRelation)
    {
        //trace_log('getRelationPath : '.$value.' key '.$key.' createYamlRelation : '.$createYamlRelation);
        if ($value == 'self') {
            //trace_log('self');
            return '$/' . strtolower($this->parent->w_author) . '/' . strtolower($this->parent->w_plugin) . '/models/' . strtolower(camel_case(str_singular($key)));
        } else if ($value == 'user') {
             //trace_log('user');
            return '$/' . strtolower($this->parent->w_author) . '/' . strtolower($this->parent->w_plugin) . '/models/' . strtolower(str_singular($key));
        } 
        else {
            //trace_log('plugin externe-------------------');
            $parts = explode('.', $value);
            $r_author = $parts[0] ?? 'NON GERE 324';
            $r_plugin = $parts[1] ?? 'NON GERE 324';
            $r_model = $parts[2] ?? $key;
            return '$/' . strtolower($r_author) . '/' . strtolower($r_plugin) . '/models/' . strtolower(camel_case(str_singular($r_model)));
        }
    }

    public function getRelationPathConfig($value, $key, $columnOrField, $createYamlRelation)
    {
        //trace_log('getRelationPath : '.$value.' key '.$key.' createYamlRelation : '.$createYamlRelation);
        if ($value == 'self') {
            //trace_log('self');
            return '$/' . strtolower($this->parent->w_author) . '/' . strtolower($this->parent->w_plugin) . '/models/' . strtolower(camel_case(str_singular($key))).'/'.$columnOrField.'_for_'.strtolower(camel_case(str_singular($this->parent->w_model))).'.yaml';
        } else if ($value == 'user') {
             //trace_log('user');
            return '$/' . strtolower($this->parent->w_author) . '/' . strtolower($this->parent->w_plugin) . '/models/user/'.$columnOrField.'_for_'.strtolower(camel_case(str_singular($this->parent->w_model))).'.yaml';
        } 
        else {
            //trace_log('plugin externe-------------------');
            $parts = explode('.', $value);
            //trace_log($parts);
            $r_author = $parts[0] ?? 'NON GERE 324';
            $r_plugin = $parts[1] ?? 'NON GERE 324';
            $r_model = $parts[2] ?? $key;
            return '$/' . strtolower($this->parent->w_author) . '/' . strtolower($this->parent->w_plugin) . '/models/' . strtolower(camel_case(str_singular($r_model))).'/'.$columnOrField.'_for_'.strtolower(camel_case(str_singular($this->parent->w_model))).'.yaml';
        }
    }

    public function getRecordUrl($value, $key)
    {
        if ($value == 'self') {
            return strtolower($this->parent->w_author) . '/' . strtolower($this->parent->w_plugin) . '/' . strtolower(camel_case($key)).'/update/:id';
        } else if ($value == 'user') {
            return 'backend/users/update/:id';
        } 
        else {
            $parts = explode('.', $value);
            $r_author = $parts[0] ?? 'NON GERE 324';
            $r_plugin = $parts[1] ?? 'NON GERE 324';
            $r_model = $parts[2] ?? $key;
            return strtolower($r_author) . '/' . strtolower($r_plugin) . '/' . strtolower(camel_case($r_model)).'s/update/:id';
        }
    }

    public function getRelationOptions($value)
    {
        if (!$value) {
            return null;
        }
        $parts = explode('|', $value);

        $options = [];

        //travail sur les deifferents coules key attribute
        foreach ($parts as $part) {
            $key_att = explode('=', $part);
            if($key_att[1] ?? false) {
                if($key_att[1] == 'true') {
                    $key_att[1] = true;
                } elseif($key_att[1] == 'false') {
                    $key_att[1] = false;
                } 
                $options[$key_att[0]] = $key_att[1];
            } else {
                $options[$key_att[0]] = null;
            }
            
        }
        return $options;
    }

    
}
