<?php

namespace App\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

#use App\Twig\Runtime\GameExtensionRuntime;
use Twig\TwigFunction;

class GameExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            // If your filter generates SAFE HTML, you should add a third
            // parameter: ['is_safe' => ['html']]
            // Reference: https://twig.symfony.com/doc/3.x/advanced.html#automatic-escaping

            new TwigFilter('nbre_chaine_de_caractere', [$this, 'nbre_caractere']),
            new TwigFilter('array_values', [$this, 'arrayValuesFilter']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('nbre_chaine_de_caractere', [$this, 'nbre_caractere']),
            new TwigFunction('array_values', [$this, 'arrayValuesFilter']),
        ];
    }

    public function nbre_caractere($value)
    {
        // Nous utilisons la fonction PHP strlen
        // Elle renvoie le nombre de caractères d'un chaîne

        return strlen($value);
    }

    public function arrayValuesFilter($array)
    {
        if (!is_array($array)) {
            return $array;
        }
        return array_values($array);
    }

}
