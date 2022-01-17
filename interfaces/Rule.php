<?php namespace Waka\Utils\Interfaces;

/**
 * This contract represents a notification rule.
 */
interface Rule
{
    /**
     * Returns array  subFormDetails.
     * @return array
     */
    public function subFormDetails();

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
