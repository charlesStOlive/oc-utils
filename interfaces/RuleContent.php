<?php namespace Waka\Utils\Interfaces;

/**
 * This contract represents a notification rule.
 */
interface RuleContent
{
    /**
     * Returns a rule text summary when displaying to the user.
     * @return string
     */
    public function getText();

    /**
     * Returns a rule title for displaying in the rule settings form.
     * @return string
     */
    public function getTitle();

    /**
     * Resolve  rule.
     * @return string
     */
    public function resolve();

    /**
     * Crée la vue à partir de la config view et de resolve
     * @return string
     */
    public function makeView($view = null);

    /**
     * Returns information about this rule, including name and description.
     */
    public function ruleDetails();

    /**
     * Triggers this rule.
     * @param array $params
     * @return void
     */
    public function triggerRule($params);
}
