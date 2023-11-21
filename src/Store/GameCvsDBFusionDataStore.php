<?php

namespace App\Store;

use App\Store\GameDBDataStore;
use App\Store\GameCsvDataStore;

class GameCvsDBFusionDataStore
{
    /**
     * @param $generator1
     * @param $generator2
     * @return \Generator
     */
    function fusionGamesGenerators($generator1, $generator2) {
        $games = [];

        foreach ($generator1 as $game) {
            $name = $game['name'];
            if (!isset($games[$name])) {
                $games[$name] = $game;
                yield $game;
            }
        }

        foreach ($generator2 as $game) {
            $name = $game['name'];
            if (!isset($games[$name])) {
                $games[$name] = $game;
                yield $game;
            } else {
                // Si le jeu existe déjà, fusionnez les données ici
                $games[$name] = array_merge($games[$name], $game);
                yield $games[$name];
            }
        }
    }
}