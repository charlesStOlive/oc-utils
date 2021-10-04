<?php namespace Waka\Utils\Interfaces;

/**
 * This contract represents a notification ask.
 */
interface Ask
{
    /**
     * Returns a ask text summary when displaying to the user.
     * @return string
     */
    public function getText();

    /**
     * Returns a ask title for displaying in the ask settings form.
     * @return string
     */
    public function getTitle();

    /**
     * Resolve  ask a besoin du modèle.
     * @return string
     */
    public function resolve($modelSrc, $context = 'twig', $dataForTwig = []);

    /**
     * Returns information about this ask, including name and description.
     */
    public function askDetails();

    /**
     * Triggers this ask.
     * @param array $params
     * @return void
     */
    public function triggerAsk($params);
}
