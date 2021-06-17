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
        $this->typeCode = ['many','belong', 'oneThrough', 'belongsMany', 'manyThrough', 'morphMany' , 'morphOne', 'attachOne', 'attachMany' ];
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
        $columns = $item['columns'] ?? 0;
        $fields =  $item['fields'] ?? 0;
        $createYamls =  $item['yamls'] ?? false;
        //
        
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
        
        $userRelation = $relationClass == 'Backend\Models\User' ? true : false;
        
        //Création des relations pour le modèles
        $relationarray = null;
        if($options) {
            $relationarray =  VarExporter::export([$relationClass, $options],VarExporter::NO_CLOSURES,2);
        } else {
            $relationarray =  VarExporter::export([$relationClass],VarExporter::NO_CLOSURES,2);
        }
        
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
        ];
        $relation = array_merge($relation, $addToRelation);
        $relationItem = $this->createConfig($var, $type, $item);
        $relation = array_merge($relation, $relationItem);
        
        //trace_log($relation);
        //     [var] => clients
        //     [type] => many
        //     [class] => Wcli\Crm\Models\Client
        //     [options] => 
        //     [columns] => $/wcli/crm/models/client/columns_for_secteur.yaml
        //     [fields] => $/wcli/crm/models/client/fields_for_secteur.yaml
        //     [yamls] => 1
        //     [yamls_read] => 0
        //     [toolbar] => link|unlink
        //     [search] => 
        //     [show_search] => 1
        //     [sort_column] => 
        //     [sort_mode] => 
        //     [filters] => 
        //     [path] => $/wcli/crm/models/client
        //     [detail] => Array
        //         (
        //             [author] => wcli
        //             [plugin] => crm
        //             [model] => clients
        //         )

        //     [relationarray] => [
        //     'Wcli\Crm\Models\Client',
        //     null
        // ]
        //     [userRelation] => 
        //     [createYamls] => 1
        //     [name] => clients
        //     [singular_name] => client
        //     [views] => Array
        //         (
        //             [view_list] => 1
        //             [manage_form] => 1
        //             [manage_list] => 1
        //         )

        // )
        return $relation;

    }

    public function createConfig($var, $type, $item) {
        if ($type == 'belong') {
            return [
                'name' => $var,
                'singular_name' => str_singular(camel_case($var)),
                'view_form' => !$item['yamls_read'],
                'view_form_read' => $item['yamls_read'],
                'manage_form' => true,
                'manage_list' => str_contains($item['toolbar'], 'link'),
            ];
        }
        if ($type == 'oneThrough') {
            return [
                'name' => $var,
                'singular_name' => str_singular(camel_case($var)),
                'view_form' => !$item['yamls_read'],
                'view_form_read' => $item['yamls_read'],
                'manage_form' => true,
                'manage_list' => str_contains($item['toolbar'], 'link'),
            ];
        }
        if ($type == 'belongsMany') {
            return [
                'name' => $var,
                'singular_name' => str_singular(camel_case($var)),
                'view_list' => true,
                'manage_form' => true,
                'manage_list' => true,
            ];
        }
        if ($type == 'morphMany') {
            return [
                'name' => $var,
                'singular_name' => str_singular(camel_case($var)),
                'view_list' => true,
                'manage_form' => true,
                'manage_list' => true,
            ];
        }
        if ($type == 'morphOne') {
            return [
                'name' => $this->getRelationKeyVar($type, $var),
                'singular_name' => str_singular(camel_case($var)),
                'view_form' => !$item['yamls_read'],
                'view_form_read' => $item['yamls_read'],
                'manage_form' => true,
                'manage_list' => str_contains($item['toolbar'], 'link'),
            ];
        }
        if ($type == 'many') {
            return [
                'name' => $var,
                'singular_name' => str_singular(camel_case($var)),
                'view_list' => true,
                'manage_form' => true,
                'manage_list' => true,
            ];
        }
        if ($type == 'manyThrough') {
            return [
                'name' => $var,
                'singular_name' => str_singular(camel_case($var)),
                'view_list' => true,
                'manage_form' => true,
                'manage_list' => true,
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
            $r_plugin = array_pop($parts);
            $r_author = array_pop($parts);
            return '$/' . strtolower($r_author) . '/' . strtolower($r_plugin) . '/models/' . strtolower(camel_case(str_singular($key)));
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
            return '$/' . strtolower($this->parent->w_author) . '/' . strtolower($this->parent->w_plugin) . '/models/' . strtolower($this->parent->w_model).'/'.$columnOrField.'_for_user.yaml';
        } 
        else {
            //trace_log('plugin externe-------------------');
            $parts = explode('.', $value);
            $r_plugin = array_pop($parts);
            $r_author = array_pop($parts);
            return '$/' . strtolower($r_author) . '/' . strtolower($r_plugin) . '/models/' . strtolower(camel_case(str_singular($key))).'/'.$columnOrField.'_for_'.strtolower(camel_case(str_singular($this->parent->w_model))).'.yaml';
        }
    }

    public function getRelationOptions($value)
    {
        if (!$value) {
            return null;
        }
        $parts = explode(',', $value);

        $options = [];

        //travail sur les deifferents coules key attribute
        foreach ($parts as $part) {
            $key_att = explode('=', $part);
            //trace_log($key_att);
            $options[$key_att[0]] = $key_att[1];
        }
        return $options;
    }

    
}
