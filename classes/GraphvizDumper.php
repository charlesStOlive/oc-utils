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
        'graph' => ['compress' => 'auto', 'rankdir' => 'TD', 'splines' => 'ortho', 'nodesep' => '0.2', 'ranksep' => '0.2'],
        'node' => ['fontsize' => '12', 'fontname' => 'Arial', 'color' => '#333333', 'fillcolor' => 'white', 'fixedsize' => 'false', 'width' => '1'],
        'edge' => ['fontsize' => '12', 'fontname' => 'Arial', 'color' => '#333333', 'arrowhead' => 'normal', 'arrowsize' => '0.5'],
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
    public function dump(Definition $definition, Marking $marking = null, array $options = [])
    {
        //trace_log($options);
        $this->wordWrap = $options['graph']['rankdir'] == 'TB' ? 40 : 15;

        //trace_log($options['graph']['rankdir']);
        //trace_log($this->wordWrap);

        $places = $this->findPlaces($definition, $marking);
        $transitions = $this->findTransitions($definition);
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

        foreach ($definition->getPlaces() as $place) {
            $attributes = [
                'style' => 'filled',
                'fillcolor' => '#999999',
                'color' => '#FFFFFF',
                'fontcolor' => '#FFFFFF',

            ];
            if (\in_array($place, $definition->getInitialPlaces(), true)) {
                $attributes['style'] = 'filled';
                $attributes['fillcolor'] = 'black';
            }
            if ($marking && $marking->has($place)) {
                $attributes['color'] = '#FF0000';
                $attributes['shape'] = 'doublecircle';
            }
            $backgroundColor = $workflowMetadata->getMetadata('bg_color', $place);
            if (null !== $backgroundColor) {
                $attributes['style'] = 'filled';
                $attributes['fillcolor'] = $backgroundColor;
            }
            $label = \Lang::get($workflowMetadata->getMetadata('label', $place));
            $label = wordwrap($label, $this->wordWrap, "|");
            if (null !== $label) {
                $attributes['name'] = $label;
            }
            $must_trans = $workflowMetadata->getMetadata('must_trans', $place);
            if ($must_trans !== null) {
                $attributes['must_trans'] = true;
            }
            $automatisations = $workflowMetadata->getMetadata('automatisations', $place);
            if (null !== $automatisations) {
                $attributes['automatisations'] = "Automatisation !";
            }
            $places[$place] = [
                'attributes' => $attributes,
            ];
        }

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

            $hidden = $workflowMetadata->getMetadata('hidden', $transition);
            if (null !== $hidden) {
                $attributes['style'] = 'dashed';
                $attributes['fillcolor'] = '#cccccc';
            }
            $name = $workflowMetadata->getMetadata('label', $transition) ?? $transition->getName();
            $name = wordwrap(\Lang::get($name), $this->wordWrap, '|');

            $functions = $workflowMetadata->getMetadata('fncs', $transition) ?? null;
            $completeNameWithFunction = "";
            $gard = null;
            $prod = null;
            $trait = null;

            if ($functions) {
                $attributes['style'] = 'filled';
                foreach ($functions as $fncKeyName => $function) {
                    $type = $function['type'] ?? null;
                    if ($type == 'gard') {
                        $gard = "Gard : " . $fncKeyName;
                    }
                    if ($type == 'prod') {
                        $prod = "Prod : " . $fncKeyName;
                    }
                    if ($type == 'trait') {
                        $trait = "Trait : " . $fncKeyName;
                    }
                }
            }
            $rulesSet = $workflowMetadata->getMetadata('rulesSet', $transition) ?? null;
            $rules = null;
            if ($rulesSet) {
                $rules = "Règles : ".$rulesSet;
            }
               
            $transitions[] = [
                'attributes' => $attributes,
                'name' => $name,
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

        foreach ($places as $id => $place) {
            //trace_log($place);
            if (isset($place['attributes']['name'])) {
                $placeName = $place['attributes']['name'];
                $must_trans = $place['attributes']['must_trans'] ?? null;
                $must_trans = $must_trans ? 'Transition obligatoire' : null;
                unset($place['attributes']['name']);
            } else {
                $placeName = $id;
            }

            $code .= sprintf("  place_%s [label=%s, %s];\n", $this->dotize($id), $this->createPlaceLabelTab($placeName, $must_trans), $this->addAttributes($place['attributes']));
            //trace_log($code);
        }

        return $code;
    }

    public function createPlaceLabelTab($placeName, $automatisations)
    {
        //trace_log($automatisations);
        $automatisations = $this->createRow($automatisations);
        $placeName = $this->escape($placeName);
        $placeName = str_replace('|', '<BR/>', $placeName);
        $text = sprintf('<<table border="0"  cellborder="0"><tr><td><b>%s</b></td></tr>%s</table>>', $placeName, $automatisations);
        return $text;
    }

    public function createRow($name)
    {
        if (!$name) {
            return '';
        } else {
            $name = str_replace('|', '<BR>', $name);
            return sprintf('<tr><td align="left" port="r0">%s</td></tr>', $name);
        }
    }

    /**
     * @internal
     */
    protected function addTransitions(array $transitions): string
    {
        $code = '';

        foreach ($transitions as $i => $place) {
            $code .= sprintf("  transition_%s [label=%s,%s];\n", $this->dotize($i), $this->createTransitionLabelTab($place), $this->addAttributes($place['attributes']));
            //trace_log($code);
        }

        return $code;
    }
    public function createTransitionLabelTab($place)
    {
        $placeName = $place['name'];
        $gard = $this->createRow($place['gard'] ?? null);
        $prod = $this->createRow($place['prod'] ?? null);
        $trait = $this->createRow($place['trait'] ?? null);
        $rules = $this->createRow($place['rules'] ?? null);
        $placeName = $this->escape($placeName);
        $placeName = str_replace('|', '<BR/>', $placeName);
        $text = sprintf('<<table border="0" style="width:100px" cellborder="0"><tr><td><b>%s</b></td></tr>%s %s %s %s</table>>', $placeName, $gard, $prod, $trait,$rules);
        return $text;
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
