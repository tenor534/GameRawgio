<?php

namespace App\Controller;

use App\Entity\Platform;
use App\Entity\VideoGame;
use App\Form\SearchType;
use App\Repository\PlatformRepository;
use App\Repository\UserRepository;
use App\Repository\VideoGameRepository;
use App\Service\VideoGameService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;

use Symfony\Component\Serializer\SerializerInterface;
use League\Csv\Reader;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;



#[Route('//video-game')]
#[IsGranted('ROLE_USER')]
class VideoGameController extends AbstractController
{

    private $serializer; //
    private $jeuRepository;

    //Instance
    public function __construct(SerializerInterface $serializer, VideoGameRepository $jeuRepository)
    {
        $this->serializer = $serializer;
        $this->jeuRepository = $jeuRepository;
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

    #[Route('/api/games', name: 'api_video_game_list', methods: ['GET'])]
    public function listGames(EntityManagerInterface $entityManager): JsonResponse
    {
        $repository = $entityManager->getRepository('App\Entity\VideoGame'); // Supposons que votre entité de jeu soit 'Game'
        
        $games = $repository->createQueryBuilder('g')
            ->select('g.id', 'g.name')
            ->getQuery()
            ->getResult();

        //dd($this->json($games));
        return $this->json($games);
    }

    #[Route('/api/games/fusion/json', name: 'api_video_game_csv_json', methods: ['GET'])]
    public function fusionnerGames(EntityManagerInterface $entityManager): JsonResponse
    {
        // Lire le CSV
        /*
        $csv = Reader::createFromPath('/laragon/www/SymfonyILoveGamer/src/Datas/games.csv', 'r');
        $csv->setHeaderOffset(0);
        $jeuxCsv = $csv->getRecords(); // Récupérer les jeux du CSV
        */

        $csvFile = "/laragon/www/SymfonyILoveGamer/src/Datas/games.csv";

        $csvData = [];
        
        if (($handle = fopen($csvFile, 'r')) !== false) {
            $headers = fgetcsv($handle); // Récupérer les entêtes du fichier CSV
            while (($row = fgetcsv($handle)) !== false) {
                $rowData = [];
                foreach ($headers as $i => $header) {
                    $rowData[$header] = $row[$i];
                }
                $csvData[] = $rowData;
            }
            fclose($handle);
        }

        //dd($csvData);

        $jeuxCsv = $csvData;
        
        // Récupérer les jeux de la base de données
        $repository = $entityManager->getRepository('App\Entity\VideoGame');
        $jeuxBDD    = $repository->findAll();
        //$jeuxBDD = $this->VideoGameRepository->findAll();
        
        //dd($jeuxBDD);
        // Fusionner les jeux du CSV avec ceux de la base de données
        $jeuxFusionnes = [];
        $id = 0;

        //Première table : jeux du csv qui ne matchent pas dans la base
        foreach ($jeuxCsv as $jeuCsv) {

            //dd($jeuCsv);
            //$id = $jeuCsv['id']; // Identifier les jeux par un champ (id par exemple)

            /*
                VideoGameController.php on line 177:
                array:4 [▼
                "Game Title" => "The Legend of Zelda: Breath of the Wild"
                "Release Year" => "2017"
                "Age Minimum Recommended" => "16+"
                "Solo Mode" => "Yes"
                ]


                $jeuxFusionnes[$id]["name"]     = $jeuCsv['Game Title']; 
                $jeuxFusionnes[$id]["released"] = $jeuCsv['Release Year']; 
               

            */

            $name = $jeuCsv['Game Title'];            

            // Recherche du jeu correspondant dans la base de données
            //$jeuBDD = $this->jeuRepository->find($id);
            //dd($repository->findBynameField($name));           

            // Fusionner les données du CSV avec celles de la BDD s'il existe
            //$jeuxFusionnes[$id] = $jeuBDD ? array_merge($jeuBDD->toArray(), $jeuCsv) : $jeuCsv;
            //$jeuxFusionnes[$id] = count($repository->findBynameField($name))? array_merge($jeuBDD->toArray(), $jeuCsv) : $jeuCsv;
            
            if(count($repository->findBynameField($name)) == 0){
                $jeuxFusionnes[$id]["name"]     = $jeuCsv['Game Title']; 
                $jeuxFusionnes[$id]["released"] = $jeuCsv['Release Year']; 
                $id++;
            }            
        }

        //dd($jeuxFusionnes);

        foreach ($jeuxBDD as $jeuBDD) {
            $name = $jeuBDD->getName();

            //dd($name);
            // Si le jeu de la BDD n'a pas été fusionné précédemment
            if (!isset($jeuxFusionnes[$name])) {
                /*
                    App\Entity\VideoGame {#860 ▼
                    -id: 1
                    -name: "DOOM (2016)"
                    -released: DateTime @1463097600 {#864 ▶}
                    -rating: 4.38
                    -imgUrl: "https://media.rawg.io/media/games/c4b/c4b0cab189e73432de3a250d8cf1c84e.jpg"
                    -platfomrs: Doctrine\ORM\PersistentCollection {#848 ▶}
                    -apiId: 2454
                }

                */
                //dd( $jeuBDD);
                //$jeuxFusionnes[$id] = (array) $jeuBDD;     
                
                $jeuxFusionnes[$id]["name"]     = $jeuBDD->getName(); 
                $jeuxFusionnes[$id]["released"] = $jeuBDD->getReleased(); 
                
                
                $id++;
            }
        }
        //dd($jeuxFusionnes);

        // Retourner la réponse JSON contenant tous les jeux fusionnés
        /*
        $response = $this->serializer->serialize(
            array_values($jeuxFusionnes),
            'json',
            [AbstractNormalizer::IGNORED_ATTRIBUTES => ['propriete_a_exclure_si_necessaire']]
        );*/

        //return new JsonResponse($response, Response::HTTP_OK, [], true);
        return new JsonResponse($jeuxFusionnes);
    }

}
