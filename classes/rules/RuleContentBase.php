<?php namespace Waka\Utils\Classes\Rules;

use System\Classes\PluginManager;
use Winter\Storm\Extension\ExtensionBase;
use Waka\Utils\Classes\DataSource;
use Waka\Utils\Interfaces\RuleContent as RuleContentInterface;
use View;

/**
 * Notification rule base class
 *
 * @package waka\utils
 * @author Alexey Bobkov, Samuel Georges
 */
class RuleContentBase extends RuleBase implements RuleContentInterface
{
    
    /**
     * Returns information about this rule, including name and description.
     */
    public function ruleDetails()
    {
        return [
            'name'        => 'Contenu',
            'description' => 'Contenu description',
            'icon'        => 'icon-dot-circle-o',
        ];
    }

    public function transformClassToDotedView() {
        $class = get_class($this);
        //trace_log($class);
        $viewPath = $this->guessViewPath();
        $class = explode('\\', $class);
        $author = strtolower($class[0]);
        $plugin = strtolower($class[1]);
        $contentName = strtolower($class[4]);
        return [
            'author' => $author,
            'plugin' => $plugin,
            'contentName' => $contentName,
            'viewPathCode' => $author.'.'.$plugin.'::wakacontents.'.$contentName,
            'viewPathUrl' => plugins_path($author.'/'.$plugin.'/views/wakacontents/'.$contentName),
            'themePathUrl' => themes_path('wakatailwind/partials/wakacontents/'.$contentName)
        ];
    }


    public function listViews() {
        $viewObject = $this->transformClassToDotedView();
        //trace_log($viewObject);
        $views = [];
        $configViews = $viewObject['viewPathUrl'];
        //trace_log($configViews);
        if (file_exists($configViews)) {
            $filesInFolder = \File::files($configViews);
            if($filesInFolder) {
                foreach($filesInFolder as $file) { 
                //trace_log($file);
                $fileBase = ltrim($file->getBasename('.htm'), '_');
                $viewPath = $viewObject['viewPathCode'].'.'.$fileBase;
                $viewName = "Interne :: ".$file->getFilename();
                $views[$viewPath] = $viewName;
                }
            }
        }
        $views['code'] = "pas de vue, code seulement";
        $views['partial'] = "Un partial du theme";
        return $views;
    }

    public function __construct($host = null)
    {
        /*
         * Paths
         */
        //trace_log($this);
        $this->viewPath = $this->configPath = $this->guessConfigPathFrom($this);
        //trace_log($this->viewPath);

        /*
         * Parse the config, if available
         */
        if ($formFields = $this->defineFormFields()) {
            $baseConfig = \Yaml::parseFile(plugins_path('/waka/utils/models/rules/fields_content.yaml'));
            if(!$this->getEditableOption()) {
                unset($baseConfig['fields']['ask_emit']);
            }
            $askConfig = \Yaml::parseFile($this->configPath.'/'.$formFields);
            $mergeConfig = array_merge_recursive($baseConfig, $askConfig);
            $this->fieldConfig = $this->makeConfig($mergeConfig);
        }

        if (!$this->host = $host) {
            return;
        }

        $this->boot($host);
    }

    public function resolve() {

    }
    public function makeView($view = null) {
        $view = $this->getConfig('view');
        //trace_log($view);
        if(!View::exists($view)) {
            \Log::error('la vue '.$view.' n \'exite pas');
        }
        return \View::make($view)->withData($this->resolve());
    }

    /**
     * Boot method called when the condition class is first loaded
     * with an existing model.
     * @return array
     */

    public function getModel() {
        return $this->host->contenteable;
    }
}
