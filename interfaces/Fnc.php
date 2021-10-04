<?php namespace Waka\Utils\Interfaces;

/**
 * This contract represents a notification fnc.
 */
interface Fnc
{
    /**
     * Déclaration des relations entre le fnc et le datasource
     * @return string
     */
    public function fncBridges();
    /**
     * Returns a fnc text summary when displaying to the user.
     * @return string
     */
    public function getText();

    /**
     * Returns a fnc title for displaying in the fnc settings form.
     * @return string
     */
    public function getTitle();

    /**
     * Resolve  fnc a besoin du modèle.
     * @return string
     */
    public function resolve($modelSrc, $poductorDs);

    /**
     * Liste les champs de sortie
     * @return string
     */
    public function output();

    /**
     * Returns information about this fnc, including name and description.
     */
    public function fncDetails();

    /**
     * Triggers this fnc.
     * @param array $params
     * @return void
     */
    public function triggerFnc($params);
}
