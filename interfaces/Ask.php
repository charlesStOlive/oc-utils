<?php namespace Waka\Utils\Interfaces;

/**
 * This contract represents a notification ask.
 */
interface Ask
{
    /**
     * Returns array  subFormDetails.
     * @return string
     */
    public function subFormDetails();
    /**
     * Resolve  ask a besoin du modèle.
     * @return string
     */
    public function resolve($modelSrc, $context = 'twig', $dataForTwig = []);
}
