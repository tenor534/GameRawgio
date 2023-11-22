<?php

namespace App\Controller;

use App\Entity\Platform;
use App\Entity\VideoGame;
use App\Form\SearchType;
use App\Repository\PlatformRepository;
use App\Repository\UserRepository;
use App\Repository\VideoGameRepository;
use App\Service\VideoGameService;

use App\Store\GameCsvDataStore;
use App\Store\GameCvsDBFusionDataStore;
use App\Store\GameDBDataStore;
use Doctrine\ORM\EntityManagerInterface;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;

use Symfony\Component\Serializer\SerializerInterface;
use League\Csv\Reader;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

use Symfony\Component\HttpFoundation\StreamedResponse;


#[Route('//video-game')]
#[IsGranted('ROLE_USER')]
class VideoGameController extends AbstractController
{

    private $serializer; //
    private $jeuRepository;
    private $entityManager;
    private $parameterBag;

    //Instance
    public function __construct(SerializerInterface $serializer, VideoGameRepository $jeuRepository, EntityManagerInterface $entityManager, ParameterBagInterface $parameterBag)
    {
        $this->serializer       = $serializer;
        $this->jeuRepository    = $jeuRepository;
        $this->entityManager    = $entityManager;
        $this->parameterBag     = $parameterBag;
    }

    #[Route('/', name: 'app_video_game')]
    public function index(): Response
    {
        $search = $this->createForm(SearchType::class);
        return $this->render('video_game/index.html.twig', [
            'user' => $this->getUser(),
            'search' => $search
        ]);
    }

    #[Route('/search', name: 'app_video_game_search', methods: ['POST'])]
    public function search(Request $request, VideoGameService $videoGameService)
    {
        $search = $request->request->all()['search']['search'];
        $games = $videoGameService->searchByName($search);

        return $this->render('video_game/_search.html.twig', [
            'games' => $games['results']
        ]);
    }

    #[Route('/add-video-game/{id}', name: 'app_video_game_add', methods: ['POST'])]
    public function add(VideoGameService $videoGameService, VideoGameRepository $videoGameRepository,
                        EntityManagerInterface $em, UserRepository $userRepository,
                        PlatformRepository $platformRepository, int $id)
    {

        $game = $videoGameService->searchById($id);
        $gameExit = $videoGameRepository->findOneBy(['apiId' => $id]);
        $platformMane = $game['platforms'][0]['platform']['name'];
        $platformExit = $platformRepository->findOneBy(['name' => $platformMane]);
        // if video game exit in database set video game at user
        if (!$gameExit) {
            //If platform not exit
            if (!$platformExit) {
                $platform = new Platform();
                $platform->setName($platformMane);
            } else {
                $platform = $platformExit;
            }
            $videoGame = new VideoGame();
            $videoGame
                ->setName($game['name'])
                ->setImgUrl($game['background_image'])
                ->setRating($game['rating'])
                ->setReleased(new \DateTime($game['released']))
                ->setApiId($game['id'])
                ->addPlatfomr($platform);
        } else {
            $videoGame = $gameExit;
        }

        $user = $this->getUser();
        // Check if the user already has the game
        $userAddVideoGame = $userRepository->findVideoGameByUser($user, $id);
        if (empty($userAddVideoGame)) {
            $user->addVideoGame($videoGame);
            $em->persist($user);
            $em->flush();
        }

        return $this->render('video_game/_listVideoGames.html.twig', [
            'user' => $user
        ]);
    }

    /**
     * @param VideoGame $videoGame
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route('/delete/{id}', name: 'app_video_game_delete', methods: ['POST'])]
    public function delete(VideoGame $videoGame, EntityManagerInterface $em)
    {
        $user = $this->getUser();
        $user->removeVideoGame($videoGame);
        $em->flush();

        return $this->render('video_game/_listVideoGames.html.twig', [
            'user' => $user
        ]);
    }

    /**
     * @param EntityManagerInterface $entityManager
     * @return JsonResponse
     *
     *  Chaque jeu de la base de données doit apparaître dans le résultat du Json.
     * en utilisant createQueryBuilder()
     */
    #[Route('/api/games', name: 'api_video_game_list', methods: ['GET'])]
    public function listGames(EntityManagerInterface $entityManager): JsonResponse
    {
        $repository = $entityManager->getRepository('App\Entity\VideoGame'); // Supposons que votre entité de jeu soit 'Game'
        
        $games = $repository->createQueryBuilder('g')
            ->select(
                'g.id',
                'g.name',
                'g.released',
                'g.rating',
                'g.imgUrl',
                'g.apiId'
            )
            ->getQuery()
            ->getResult();

        return $this->json($games);
    }

    /**
     * @param EntityManagerInterface $entityManager
     * @return JsonResponse
     *
     *  Chaque jeu de la base de données doit apparaître dans le résultat du Json.
     */
    #[Route('/api/games/db', name: 'api_video_game_db_list', methods: ['GET'])]
    public function listDbGames(EntityManagerInterface $entityManager): JsonResponse
    {
        //Génération from DB Table video_game
        $gameDBDataStore    = new GameDBDataStore();
        $gameDBDatas        = $gameDBDataStore->fetchAll($this->entityManager);

        return $this->json($gameDBDatas);
    }

    /**
     * @param EntityManagerInterface $entityManager
     * @return JsonResponse
     * @throws \Exception
     *
     *  Chaque jeu du Csv doit apparaître dans le résultat en Json.
     */
    #[Route('/api/games/csv', name: 'api_video_game_csv_list', methods: ['GET'])]
    public function listCsvGames(EntityManagerInterface $entityManager): JsonResponse
    {
        //Génération from Cvs file
        $gameCsvDataStore   = new GameCsvDataStore($this->parameterBag->get('API_CSV_FILE_PATH'));
        $gameCsvDatas       = $gameCsvDataStore->fetchAll();

        return $this->json($gameCsvDatas);
    }

    /**
     * @return StreamedResponse
     * Si des données du Csv et de la base concernent le même jeu, les données doivent être fusionées.
     */
    #[Route('/api/games/fusion/json', name: 'api_video_game_csv_json', methods: ['GET'])]
    public function fusionnerGamesToJSON(
    ): StreamedResponse
    {
        $response = new StreamedResponse(function () {
        //Génération from Cvs file
        $gameCsvDataStore   = new GameCsvDataStore($this->parameterBag->get('API_CSV_FILE_PATH'));
        $gameCsvDatas       = $gameCsvDataStore->fetchAll();

        //Génération from DB Table video_game
        $gameDBDataStore    = new GameDBDataStore();
        $gameDBDatas        = $gameDBDataStore->fetchAll($this->entityManager);

        //Fusion Csv et DB
        $fusionGamesGenerators = new GameCvsDBFusionDataStore();
        $gameDBCsvDatas = $fusionGamesGenerators->fusionGamesGenerators($gameDBDatas, $gameCsvDatas);

        echo '['; // Open the JSON array.
        $first = true;
        foreach ($gameDBCsvDatas as $gameDBCsvData) {
            if (!$first) {
                echo ','; // Add a comma between each JSON object.
            }
            echo json_encode($gameDBCsvData);
            $first = false;
        }
        echo ']'; // Close the JSON array.
        });

        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
}