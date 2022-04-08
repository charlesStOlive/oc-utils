<?php namespace Waka\Utils\Interfaces;

/**
 * This contract represents a notification rule.
 */
interface RuleContent
{
    /**
     * Returns array  subFormDetails.
     * @return string
     */
    public function subFormDetails();
    
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
    public function resolve($ds);

    /**
     * Crée la vue à partir de la config view et de resolve
     * @return string
     */
    public function makeView($view = null);

    /**
     * Triggers this rule.
     * @param array $params
     * @return void
     */
    public function triggerSubForm($params);
}
