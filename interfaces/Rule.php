<?php namespace Waka\Utils\Interfaces;

/**
 * This contract represents a notification rule.
 */
interface Rule
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
     * Resolve  rule a besoin du modèle.
     * @return string
     */
    public function resolve($modelSrc, $context = 'twig', $dataForTwig = []);

    /**
     * Triggers this rule.
     * @param array $params
     * @return void
     */
    public function triggerSubForm($params);
}
