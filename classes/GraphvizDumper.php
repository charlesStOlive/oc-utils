<?php namespace Waka\Utils\Classes;

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use Symfony\Component\Workflow\Definition;
use Symfony\Component\Workflow\Dumper\DumperInterface;
use Symfony\Component\Workflow\Marking;

/**
 * GraphvizDumper dumps a workflow as a graphviz file.
 *
 * You can convert the generated dot file with the dot utility (https://graphviz.org/):
 *
 *   dot -Tpng workflow.dot > workflow.png
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Grégoire Pineau <lyrixx@lyrixx.info>
 */
class GraphvizDumper implements DumperInterface
{
    public $wordWrap = 40;
    // All values should be strings
    protected static $defaultOptions = [
        'graph' => ['compress' => 'auto', 'rankdir' => 'TD', 'splines' => 'ortho', 'nodesep' => '0.2', 'ranksep' => '0.2', 'concentrate' => true],
        'node' => ['fontsize' => '10', 'fontname' => 'Arial', 'color' => '#333333', 'fillcolor' => 'white', 'fixedsize' => 'false', 'width' => '1'],
        'edge' => ['fontsize' => '10', 'fontname' => 'Arial', 'color' => '#333333', 'arrowhead' => 'normal', 'arrowsize' => '0.5'],
    ];

    /**
     * {@inheritdoc}
     *
     * Dumps the workflow as a graphviz graph.
     *
     * Available options:
     *
     *  * graph: The default options for the whole graph
     *  * node: The default options for nodes (places + transitions)
     *  * edge: The default options for edges
     */
    public function dump(Definition $definition, Marking $marking = null, array $options = []): string
    {
        //trace_log($options);
        $this->wordWrap = $options['graph']['rankdir'] == 'TB' ? 30 : 15;

        //trace_log($options['graph']['rankdir']);
        //trace_log($this->wordWrap);

        $places = $this->findPlaces($definition, $marking);
        $transitions = $this->findTransitions($definition);
        //trace_log($transitions);
        $edges = $this->findEdges($definition);

        //trace_log($options);

        $options = array_replace_recursive(self::$defaultOptions, $options);

        return $this->startDot($options)
        . $this->addPlaces($places)
        . $this->addTransitions($transitions)
        . $this->addEdges($edges)
        . $this->endDot();
    }

    /**
     * @internal
     */
    protected function findPlaces(Definition $definition, Marking $marking = null): array
    {
        $workflowMetadata = $definition->getMetadataStore();

        $places = [];

        foreach ($definition->getPlaces() as $key=>$place) {
            //trace_log($place);
            
            $attributes = [
                'style' => 'filled',
                'fillcolor' => '#999999',
                'color' => '#FFFFFF',
                'fontcolor' => '#FFFFFF',
                'shape' => 'Mrecord',
            ];
            if (\in_array($place, $definition->getInitialPlaces(), true)) {
                $attributes['fillcolor'] = 'black';  
            }
            $label = \Lang::get($workflowMetadata->getMetadata('label', $place));
            $label = wordwrap($label, $this->wordWrap, "|");
            if (null !== $label) {
                $attributes['name'] = $label;
            }
            $must_trans = $workflowMetadata->getMetadata('must_trans', $place);
            if ($must_trans !== null) {
                $must_trans = 'transition obligatoire';
            }
            $cron_auto = $workflowMetadata->getMetadata('cron_auto', $place);
            if (null !== $cron_auto) {
                $cron_auto = "CRON AUTO : ".implode(',',$cron_auto);
            }
            $noroles = $workflowMetadata->getMetadata('noroles', $place);
            if (null !== $noroles) {
                $noroles = "Exclusions : ".implode(',',$noroles);
            }
            $hidden_fields = $workflowMetadata->getMetadata('hidden_fields', $place);
            if (null !== $hidden_fields) {
                $hidden_fields = "Champs cachés : ".implode(',',$hidden_fields);
            }
            $form_auto = $workflowMetadata->getMetadata('form_auto', $place);
            if (null !== $form_auto) {
                $form_auto =  "FORM AUTO : ".implode(',',$form_auto);
            }
            $new_workflow = $workflowMetadata->getMetadata('new_workflow', $place);
            if (null !== $new_workflow) {
                $attributes['shape'] = 'Mdiamond';
            }
            $places[$key] = [
                'attributes' => $attributes,
                'label' => $label,
                'must_trans' => $must_trans,
                'cron_auto' => $cron_auto,
                'form_auto' => $form_auto,
                'hidden_fields' => $hidden_fields,
                'noroles' => $noroles,
            ];
        }
        trace_log($places);

        return $places;
    }

    /**
     * @internal
     */
    protected function findTransitions(Definition $definition): array
    {
        $workflowMetadata = $definition->getMetadataStore();

        $transitions = [];

        foreach ($definition->getTransitions() as $transition) {
            $attributes = ['shape' => 'box', 'regular' => false];

            $code = $transition->getName();
            $name = $workflowMetadata->getMetadata('label', $transition) ?? $code;
            $color = $workflowMetadata->getMetadata('color', $transition) ?? null;
            $name = wordwrap(\Lang::get($name), $this->wordWrap, '|');

            $button = $workflowMetadata->getMetadata('button', $transition) ?? null;
            if($button) {
                $button = 'BTN : '.wordwrap(\Lang::get($button), $this->wordWrap+15, '|');
            }
            
            $functions = $workflowMetadata->getMetadata('fncs', $transition) ?? null;
            $completeNameWithFunction = "";
            $gard = null;
            $prod = null;
            $trait = null;

            if ($functions) {
                $attributes['style'] = 'filled';
                foreach ($functions as $fncKeyName => $function) {
                    
                    $type = $function['type'] ?? null;
                    //trace_log($type);
                    if ($type == 'gard') {
                        $gard = "Gard : " . $fncKeyName;
                    }
                    else if ($type == 'prod') {
                        $prod = "Prod : " . $fncKeyName;
                    }
                    else {
                        $trait = "Trait : " . $fncKeyName;
                    }
                }
            }
            $hidden = $workflowMetadata->getMetadata('hidden', $transition);
            if (null !== $hidden) {
                $attributes['style'] = 'dashed';
                $attributes['fillcolor'] = '#cccccc';
            }
            
            $rulesSet = $workflowMetadata->getMetadata('rulesSet', $transition) ?? null;
            $rules = null;
            if ($rulesSet) {
                $rules = "Validations : ".$rulesSet;
            }
               
            $transitions[] = [
                'attributes' => $attributes,
                'code' => 'Code : '.$code,
                'name' => $name,
                'button' => $button,
                'color' => $color,
                'gard' => $gard,
                'prod' => $prod,
                'trait' => $trait,
                'rules' => $rules,
                
            ];
        }

        return $transitions;
    }

    /**
     * @internal
     */
    protected function addPlaces(array $places): string
    {
        $code = '';

        foreach ($places as  $id => $place) {
            //trace_log($place);
            $code .= sprintf("  place_%s [label=%s, %s];\n", $this->dotize($id), $this->createPlaceLabelTab($place), $this->addAttributes($place['attributes']));
            //trace_log($code);
        }

        return $code;
    }

    public function createPlaceLabelTab($place)
    {
        $label = $place['label'];
        $cron_auto = $this->createRow($place['cron_auto'] ?? null);
        $form_auto = $this->createRow($place['form_auto'] ?? null);
        $must_trans = $this->createRow($place['must_trans'] ?? null);
        $hidden_fields = $this->createRow($place['hidden_fields'] ?? null);
        $noroles = $this->createRow($place['noroles']?? null);
        $label = $this->escape($label);
        $label = str_replace('|', '<BR/>', $label);
        $text = sprintf('<<table border="0"  cellborder="0"><tr><td><b><font point-size="16"> %s</font></b></td></tr>%s %s %s %s %s</table>>', $label, $cron_auto, $form_auto,$must_trans, $noroles,$hidden_fields);
        return $text;
    }

    

    

    /**
     * @internal
     */
    protected function addTransitions(array $transitions): string
    {
        $code = '';

        foreach ($transitions as $i => $trans) {
            $code .= sprintf("  transition_%s [label=%s,%s];\n", $this->dotize($i), $this->createTransitionLabelTab($trans), $this->addAttributes($trans['attributes']));
            //trace_log($code);
        }

        return $code;
    }
    public function createTransitionLabelTab($trans)
    {
        $transName = $trans['name'];
        trace_log($transName);
        trace_log($trans);
        $code = $this->createRow($trans['code']);
        $button = $this->createButtonRow($trans['button'] ?? null, $trans['color'] ?? null);
        $rules = $this->createRow($trans['rules'] ?? null);
        $gard = $this->createRow($trans['gard'] ?? null, 'right');
        $prod = $this->createRow($trans['prod'] ?? null, 'right');
        $trait = $this->createRow($trans['trait'] ?? null, 'right');
        $transName = $this->escape($transName);
        $transName = str_replace('|', '<BR/>', $transName);
        $text = sprintf('<<table border="0"  cellborder="0"><tr><td><b><font point-size="14"> %s</font></b></td></tr>%s %s %s %s %s %s</table>>', $transName, $code, $button,$rules, $gard, $prod, $trait,);
        return $text;
    }

    public function createRow($name, $align="center")
    {
        
        if (!$name) {
            return null;
        } else {
            // trace_log($name);
            $name = str_replace('|', '<BR/>', $name);
            return sprintf('<tr><td align="%s">%s</td></tr>', $align, $name);
        }
    }

    public function createButtonRow($name, $color = 'gray') {
       
        if (!$name) {
            return null;
        } 
        if(empty($color)) {
            $color = 'gray';
        }
        switch ($color) {
            case "success":
                $color = "darkGreen";
                break;
            case "danger":
                $color = "darkRed";
                break;
            case "info":
                $color = "blue";
                break;
            default:
                $color = "gray";
        }
        $name = str_replace('|', '<BR/>', $name);
        return sprintf('<tr><td  bgcolor="%s" align="left"><font color="white">%s</font></td></tr>', $color, $name);
    }

    /**
     * @internal
     */
    protected function findEdges(Definition $definition): array
    {
        $workflowMetadata = $definition->getMetadataStore();

        $dotEdges = [];

        foreach ($definition->getTransitions() as $i => $transition) {
            $transitionName = $workflowMetadata->getMetadata('label', $transition) ?? $transition->getName();

            foreach ($transition->getFroms() as $from) {
                $dotEdges[] = [
                    'from' => $from,
                    'to' => $transitionName,
                    'direction' => 'from',
                    'transition_number' => $i,
                ];
            }
            foreach ($transition->getTos() as $to) {
                $dotEdges[] = [
                    'from' => $transitionName,
                    'to' => $to,
                    'direction' => 'to',
                    'transition_number' => $i,
                ];
            }
        }

        return $dotEdges;
    }

    /**
     * @internal
     */
    protected function addEdges(array $edges): string
    {
        $code = '';

        foreach ($edges as $edge) {
            if ('from' === $edge['direction']) {
                $code .= sprintf(
                    "  place_%s -> transition_%s [style=\"solid\"];\n",
                    $this->dotize($edge['from']),
                    $this->dotize($edge['transition_number'])
                );
            } else {
                $code .= sprintf(
                    "  transition_%s -> place_%s [style=\"solid\"];\n",
                    $this->dotize($edge['transition_number']),
                    $this->dotize($edge['to'])
                );
            }
        }

        return $code;
    }

    /**
     * @internal
     */
    protected function startDot(array $options): string
    {
        return sprintf(
            "digraph workflow {\n  %s\n  node [%s];\n  edge [%s];\n\n",
            $this->addOptions($options['graph']),
            $this->addOptions($options['node']),
            $this->addOptions($options['edge'])
        );
    }

    /**
     * @internal
     */
    protected function endDot(): string
    {
        return "}\n";
    }

    /**
     * @internal
     */
    protected function dotize(string $id): string
    {
        return hash('sha1', $id);
    }

    /**
     * @internal
     */
    protected function escape($value): string
    {
        return \is_bool($value) ? ($value ? '1' : '0') : addslashes($value);
    }

    protected function addAttributes(array $attributes): string
    {
        $code = [];

        foreach ($attributes as $k => $v) {
            $code[] = sprintf('%s="%s"', $k, $this->escape($v));
        }

        return $code ? ' ' . implode(' ', $code) : '';
    }

    private function addOptions(array $options): string
    {
        $code = [];

        foreach ($options as $k => $v) {
            $code[] = sprintf('%s="%s"', $k, $v);
        }

        return implode(' ', $code);
    }
}
