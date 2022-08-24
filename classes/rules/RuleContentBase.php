<?php namespace Waka\Utils\Classes\Rules;

use System\Classes\PluginManager;
use Waka\Utils\Classes\DataSource;
use View;

/**
 * Notification rule base class
 *
 * @package waka\utils
 * @author Alexey Bobkov, Samuel Georges
 */
class RuleContentBase extends SubForm
{
    

    protected $morphName;

    public function __construct($host = null)
    {
        $this->morphName = 'contenteable';
        $this->init('/waka/utils/models/rules/fields_content.yaml');
        if (!$this->host = $host) {
            return;
        }
        $this->boot($host);
    }
    public function defaultExport() {
        return [
            'photo' => 'file',
            'photos' => 'files',
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
    public function makeView($view = null, $ds = []): string {
        $view = $this->getConfig('view');
        //trace_log($view);
        if(!View::exists($view)) {
            \Log::error('la vue '.$view.' n \'exite pas');
        }
        return \View::make($view)->withData($this->resolve($ds))->render();
    }
    //objectAskingRendering est un composant ou un controller.
    public function resolveForHtml($objectAskingRendering, $ds = [], $htmlEmplacement = null) {
        if($this->host->placement != $htmlEmplacement) {
                //Le ruleContent n'est pas dans le placement souhaité on ne le traite pas. 
                //Si la vue est oublié on l'enlève
                return null;
            }
            if(!$this->host->view) {
                //Il manque le nom de la vue on ne le gère pas. 
                \Log::error($this->host->code." Il manque la vue à utiliser");
                return null;
            }

            if($this->host->view == 'partial') {
                //Le comportement spécial PARTIAL 
                //trace_log('view est un partial');
                // trace_log(['data' => $this->host->resolve()]);
                return $objectAskingRendering->renderPartial($this->host->partial, ['data' => $this->host->resolve($ds)]);
                
            } else if($this->host->view != 'code') {
                //Le comportement classique le ruleContent genère la vue
                return  $this->makeView($ds);
            }
        //ici on recupère les configs et le champs json datas...
        return null;
    }

    

    
}
