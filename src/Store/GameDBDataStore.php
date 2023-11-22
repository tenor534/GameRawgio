<?php

namespace App\Store;

use Doctrine\ORM\EntityManagerInterface;

class GameDBDataStore
{
    /**
     * @return \Generator|true
     */
    public function fetchAll(EntityManagerInterface $entityManager)
    {

        $repository = $entityManager->getRepository('App\Entity\VideoGame');
        $games = $repository->findAll();

        return $this->getGamesGenerator($games);
    }

    /**
     * @param $games
     * @return \Generator
     */
    private function getGamesGenerator($games)
    {
        foreach ($games as $game) {
            yield [
                'id'        => $game->getId(),
                'name'      => $game->getName(),
                'released'  => $game->getReleased(),
                'rating'    => $game->getRating(),
                'img_url'   => $game->getImgUrl(),
                'api_id'    => $game->getApiId()
            ];
        }
    }
}