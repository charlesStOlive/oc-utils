<?php namespace Waka\Utils\Behaviors;

use Lang;
use Backend;
use ApplicationException;
use Backend\Classes\ControllerBehavior;

/**
 * Used for reordering and sorting records.
 *
 * This behavior is implemented in the controller like so:
 *
 *     public $implement = [
 *         \Backend\Behaviors\ReorderController::class,
 *     ];
 *
 *     public $reorderConfig = 'config_reorder.yaml';
 *
 * The `$reorderConfig` property makes reference to the configuration
 * values as either a YAML file, located in the controller view directory,
 * or directly as a PHP array.
 *
 * @package winter\wn-backend-module
 * @author Alexey Bobkov, Samuel Georges
 */
class WakaReorderController extends ControllerBehavior
{
    /**
     * @var array Configuration values that must exist when applying the primary config file.
     */
    protected $requiredConfig = ['modelClass'];

    /**
     * @var array Visible actions in context of the controller
     */
    protected $actions = ['reorder'];

    /**
     * @var Model Import model
     */
    public $model;

    /**
     * @var string Model attribute to use for the display name
     */
    public $nameFrom = 'name';

    
    /**
     * @var string config à utiliser dans la liste childsConfig
     */
    public $relationConfig = [];

    /**
     * @var string parentid pointe l'id du modèle parent si on réordonne les enfants.
     */
    public $parentid = [];

    /**
     * @var bool Display parent/child relationships in the list.
     */
    protected $showTree = false;

    /**
     * @var string Reordering mode:
     * - simple: Winter\Storm\Database\Traits\Sortable
     * - nested: Winter\Storm\Database\Traits\NestedTree
     */
    protected $sortMode;

    /**
     * @var boolean in pivot ??:
     */
    protected $usePivot;

    /**
     * @var Backend\Classes\WidgetBase Reference to the widget used for the toolbar.
     */
    protected $toolbarWidget;

    /**
     * @var mixed Configuration for this behaviour
     */
    public $reorderConfig = 'config_reorder.yaml';

    /**
     * Behavior constructor
     * @param Backend\Classes\Controller $controller
     */
    public function __construct($controller)
    {
        parent::__construct($controller);

        /*
         * Build configuration
         */
        $this->config = $this->makeConfig($controller->reorderConfig ?: $this->reorderConfig, $this->requiredConfig);

        /*
         * Widgets
         */
        if ($this->toolbarWidget = $this->makeToolbarWidget()) {
            $this->toolbarWidget->bindToController();
        }

        /*
         * Populate from config
         */
        $this->nameFrom = $this->getConfig('nameFrom', $this->nameFrom);
    }

    //
    // Controller actions
    //

    public function reorder()
    {
        $this->addJs('js/winter.reorder.js', 'core');
        $this->controller->pageTitle = $this->controller->pageTitle
        ?: Lang::get($this->getConfig('title', 'backend::lang.reorder.default_title'));
        $this->validateModel();
        $this->prepareVars();
    }

    //
    // AJAX
    //

    public function onLoadReorder()
    {
        $this->relationConfig = post('relationConfig');
        $this->parentid = post('manageId');
        $this->vars['manageId'] = $this->parentid;
        $this->vars['relationConfig'] = $this->relationConfig;
        $this->reorder();
        return $this->makePartial('popup');
    }

    public function onReorder()
    {
        $this->relationConfig = post('relationConfig');
        $model = $this->validateModel();
        /*
         * Simple
         */
        if ($this->sortMode == 'simple_pivot') {
            if (
                (!$ids = post('record_ids')) ||
                (!$orders = post('sort_orders'))
            ) {
                return;
            }
            $modelId = post('manageId');
            $relationConfig = post('relationConfig');
            //trace_log($relationConfig);
            $config = $this->getConfig('relationConfig')[$this->relationConfig];
            $modelClass = $this->getConfig('modelClass');
            $childClass = $config['childName'];
            $models = $modelClass::find($modelId)->{$childClass};
            foreach($models as $model) {
                $keyIndex = array_search($model->getKey(), $ids);
                $sortorder = $orders[$keyIndex];
                $model->pivot->sort_order = $sortorder;
                $model->pivot->save();
            }
        }else if ($this->sortMode == 'simple') {
            if (
                (!$ids = post('record_ids')) ||
                (!$orders = post('sort_orders'))
            ) {
                return;
            }

            $model->setSortableOrder($ids, $orders);
        }
        /*
         * Nested set
         */
        elseif ($this->sortMode == 'nested') {
            $sourceNode = $model->find(post('sourceNode'));
            $targetNode = post('targetNode') ? $model->find(post('targetNode')) : null;

            if ($sourceNode == $targetNode) {
                return;
            }

            switch (post('position')) {
                case 'before':
                    $sourceNode->moveBefore($targetNode);
                    break;

                case 'after':
                    $sourceNode->moveAfter($targetNode);
                    break;

                case 'child':
                    $sourceNode->makeChildOf($targetNode);
                    break;

                default:
                    $sourceNode->makeRoot();
                    break;
            }
        }
    }

     public function onCloseReorder()
        {
            //trace_log(post());
            $modelId = post('manageId');
            $relationConfig = post('relationConfig');
            //trace_log($relationConfig);
            $modelClass = $this->getConfig('modelClass');
            $model = $modelClass::find($modelId);
            //trace_log($model->name);
            $this->controller->initForm($model);
            $this->controller->initRelation($model, $relationConfig);
            return $this->controller->relationRefresh($relationConfig);
        }

    //
    // Reordering
    //

    /**
     * Prepares common form data
     */
    protected function prepareVars()
    {
        $this->vars['relationConfig'] = $this->relationConfig;
        $this->vars['reorderRecords'] = $this->getRecords();
        $this->vars['reorderModel'] = $this->model;
        $this->vars['reorderSortMode'] = $this->sortMode;
        $this->vars['reorderShowTree'] = $this->showTree;
        $this->vars['reorderToolbarWidget'] = $this->toolbarWidget;
    }

    public function reorderRender()
    {
        return $this->reorderMakePartial('container');
    }

    public function reorderGetModel()
    {
        if ($this->model !== null) {
            //trace_log("model existe déjà reorderGetModel");
            return $this->model;
        }
        
        if($this->relationConfig) {
            $config = $this->getConfig('relationConfig')[$this->relationConfig];
            $modelClass = $this->getConfig('modelClass');
            $childClass = $config['childName'];
            $this->nameFrom = $config['nameFrom'];
            $this->usePivot = $config['usePivot'] ?? false;
            return $this->model = $modelClass::{$childClass}()->getRelated();
           
           
        } else {
            $modelClass = $this->getConfig('modelClass');
            if (!$modelClass) {
                throw new ApplicationException('Please specify the modelClass property for reordering');
            }
            return $this->model = new $modelClass;

        }
    }

    /**
     * Returns the display name for a record.
     * @return string
     */
    public function reorderGetRecordName($record)
    {
        return $record->{$this->nameFrom};
    }

    /**
     * Validate the supplied form model.
     * @return void
     */
    protected function validateModel($relationmodel = null)
    {
        $model = $this->controller->reorderGetModel();
        //trace_log(get_class($model));
        $modelTraits = class_uses($model);
        if (
            isset($modelTraits[\Winter\Storm\Database\Traits\Sortable::class]) ||
            $model->isClassExtendedWith(\Winter\Storm\Database\Behaviors\Sortable::class) ||
            isset($modelTraits[\October\Rain\Database\Traits\Sortable::class]) ||
            $model->isClassExtendedWith(\October\Rain\Database\Behaviors\Sortable::class)
        ) {
            $this->sortMode = 'simple';
        }
        elseif (
            isset($modelTraits[\Winter\Storm\Database\Traits\NestedTree::class]) ||
            isset($modelTraits[\October\Rain\Database\Traits\NestedTree::class])
        ) {
            $this->sortMode = 'nested';
            $this->showTree = true;
        }
        else if($this->usePivot) {
            $this->sortMode = 'simple_pivot';
        } else {
            throw new ApplicationException('The model must implement the Sortable trait/behavior or the NestedTree trait.');
        }

        return $model;
    }

    /**
     * Returns all the records from the supplied model.
     * @return Collection
     */
    protected function getRecords()
    {
        $records = null;
        $query = null;
        $model = $this->controller->reorderGetModel();
        $query = $model->newQuery();
        

        if($this->relationConfig) {
            $model = $this->controller->reorderGetModel();
            $config = $this->getConfig('relationConfig')[$this->relationConfig];
            $modelClass = $this->getConfig('modelClass');
            $childClass = $config['childName'];;
            $query = $modelClass::find($this->parentid)->{$childClass}();
        } else {
            $model = $this->controller->reorderGetModel();
            $query = $model->newQuery();
            $this->controller->reorderExtendQuery($query);
        }
        if ($this->sortMode == 'simple_pivot') {
            $records = $query
                ->orderByPivot('sort_order','asc')
                ->get();
        }
        elseif ($this->sortMode == 'simple') {
            $records = $query
                ->orderBy($model->getSortOrderColumn())
                ->get()
            ;
        }
        elseif ($this->sortMode == 'nested') {
            $records = $query->getNested();
        }


        return $records;
    }

    /**
     * Extend the query used for finding reorder records. Extra conditions
     * can be applied to the query, for example, $query->withTrashed();
     * @param Winter\Storm\Database\Builder $query
     * @return void
     */
    public function reorderExtendQuery($query)
    {
    }

    //
    // Widgets
    //

    protected function makeToolbarWidget()
    {
        if ($toolbarConfig = $this->getConfig('toolbar')) {
            $toolbarConfig = $this->makeConfig($toolbarConfig);
            $toolbarWidget = $this->makeWidget('Backend\Widgets\Toolbar', $toolbarConfig);
        }
        else {
            $toolbarWidget = null;
        }

        return $toolbarWidget;
    }

    //
    // Helpers
    //

    /**
     * Controller accessor for making partials within this behavior.
     * @param string $partial
     * @param array $params
     * @return string Partial contents
     */
    public function reorderMakePartial($partial, $params = [])
    {
        $contents = $this->controller->makePartial(
            'reorder_' . $partial,
            $params + $this->vars,
            false
        );

        if (!$contents) {
            $contents = $this->makePartial($partial, $params);
        }

        return $contents;
    }
}
