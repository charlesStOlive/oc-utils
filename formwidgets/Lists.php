<?php namespace Waka\Utils\FormWidgets;

use Backend\Classes\FormWidgetBase;

/**
 * lists Form Widget
 */
class Lists extends FormWidgetBase
{
    /**
     * @inheritDoc
     */
    protected $defaultAlias = 'waka_utils_lists';

    public $targetsListWidget;
    public $targetsFilterWidget;
    public $scopeDatas;
    public $ds;


    public function __construct($controller, $formField, $configuration = [])
    {
        
        parent::__construct($controller, $formField, $configuration);
        $classQuery = post('classQuery');
        $this->scopeDatas = json_decode(post('scopeDatas'));
        
        if($classQuery) {
            $this->targetsListWidget = $this->createTargetsListWidget($classQuery);
        }
        
    }

    /**
     * @inheritDoc
     */
    public function init()
    {
    }

    /**
     * @inheritDoc
     */
    public function render()
    {
        $this->prepareVars();
        return $this->makePartial('lists');
    }

    /**
     * Prepares the form widget view data
     */
    public function prepareVars()
    {
        $ds = \DataSOurces::find($this->model->data_source);
        $this->vars['name'] = $this->formField->getName();
        $this->vars['modeQuery'] = $this->model->selection_mode;
        $this->vars['classQuery'] = str_replace('\\', '\\\\', $ds->class);
        $this->vars['scopeDatas'] = $this->getScopeDatas();
        $this->vars['values'] = $this->processValues($this->getLoadValue());
        $this->vars['model'] = $this->model;
    }

    /**
     * @inheritDoc
     */
    public function loadAssets()
    {
        $this->addCss('css/lists.css', 'waka.utils');
        $this->addJs('js/lists.js', 'waka.utils');
    }

    public function getScopeDatas() {
        return  [
            'mode' =>$this->model->selection_mode,
            'name' => $this->model->selection_name,
        ];
    }

    private function processValues($values) {
        $ds = \DataSOurces::find($this->model->data_source);
        $dbValues = $ds->class::whereIn('id', $values)->select('id', 'email')->get();
        if($dbValues) {
            trace_log($dbValues->toArray());
            return $dbValues->toArray();
        } else {
            return [];
        }
        
    }

    /**
     * @inheritDoc
     */
    public function getSaveValue($values)
    {
        trace_log(post());
        trace_log('getSaveValue');
        trace_log($values);
        return $values;
    }

    public function onCallTargets() {
        $ds = \DataSOurces::find($this->model->data_source);
        $manageId = $this->model->id;
        $this->vars['manageId'] = $manageId;
        $this->vars['targetsListWidget'] = $this->targetsListWidget;
        $this->vars['classQuery'] = str_replace('\\', '\\\\', $ds->class);
        #Variable necessary for the Filter funcionality
        $this->vars['targetsFilterWidget'] = $this->targetsFilterWidget;
        #Process the custom list partial, The name you choose here will be the partials file name
        return $this->makePartial('targets_list');
    }

    public function targetApplyScope($query)
    {
        $scopeName = null;
        $attribut = null;
        if($this->scopeDatas) {
            $scopeName = $this->scopeDatas->mode ?? null;
            $attribut = $this->scopeDatas->name ?? null;
        } else {
            $scopeName = $this->model->selection_mode;
            $attribut =  $this->model->selection_name;
        }
        $attribut = $attribut == 'no' ? null : $attribut;
        if($scopeName) {
            $query->$scopeName($attribut);
        } else {
            $query;
        }
        // get_class($query);
        // trace_log($query->count());
        // $query->{$scopeName}();
    }

    public function onAddTests() {
        $checked = post('checked');
        $this->vars['values'] = $this->processValues($checked);
        $this->vars['name'] = $this->formField->getName();
        $id = '#'.$this->getId('input').'exemples';
        return [
            $id => $this->makePartial('exemples'),
        ];
    }

    protected function createTargetsListWidget($classQuery)
    {

        $ds = \DataSOurces::find($this->model->data_source);
        $configColumns = '$/'.$ds->modelPath.'columns.yaml';
        //trace_log($configColumns);

        #First we need config for the list, as described in video tutorials mentioned at the beginning.
        # Specify which list configuration file use for this list
        $config = $this->makeConfig($configColumns);

        # Specify the List model
        $config->model = new $classQuery;

        # Lets configure some more things like report per page and lets show checkboxes on the list.
        # Most of the options mentioned in https://octobercms.com/docs/backend/lists#configuring-list # will work
        $config->recordsPerPage = '30';
        $config->showCheckboxes = 'true';
        # Here we will actually make the list using Lists Widget
        $widget_targets = $this->makeWidget('Backend\Widgets\Lists', $config);

        #For the optional Step 4. Alter product list query before displaying it.
        # We will bind to list.extendQuery event and assign a function that should be executed to extend
        # the query (the function is defined in this very same controller file)
        $widget_targets->bindEvent('list.extendQueryBefore', function ($query) use ($config) {
                $this->targetApplyScope($query, $config->model);
            });

        # Step 3. The filter part, we must define the config, really similar to the Product list widget config
        # Filter configuration file
        $filterConfig = $this->makeConfig('$/waka/programer/controllers/campagnes/config_filters_targets.yaml');

        # Use Filter widgets to make the widget and bind it to the controller
        $filterWidget = $this->makeWidget('Backend\Widgets\Filter', $filterConfig);
        $filterWidget->bindToController();

        # We need to bind to filter.update event in order to refresh the list after selecting
        # the desired filters.
        $filterWidget->bindEvent('filter.update', function () use ($widget_targets, $filterWidget) {
            return $widget_targets->onRefresh();
        });

        // #Finally we are attaching The Filter widget to the Product widget.
        $widget_targets->addFilter([$filterWidget, 'applyAllScopesToQuery']);

        $this->targetsFilterWidget = $filterWidget;

        # Dont forget to bind the whole thing to the controller
        $widget_targets->bindToController();

        #Return the prepared widget object
        return $widget_targets;
    }
}
