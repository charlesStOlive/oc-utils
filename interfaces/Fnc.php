<?php namespace Waka\Utils\Interfaces;

/**
 * This contract represents a notification fnc.
 */
interface Fnc
{
    /**
     * Returns information about this fnc, including name and description.
     */
    public function subFormDetails();

    /**
     * Triggers this fnc.
     * @param array $params
     * @return void
     */
    
    /**
     * Déclaration des relations entre le fnc et le datasource
     * @return string
     */
    public function fncBridges();

    /**
     * Resolve  fnc a besoin du modèle.
     * @return string
     */
    public function resolve($modelSrc, $poductorDs);
}
