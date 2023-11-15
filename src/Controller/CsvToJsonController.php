<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class CsvToJsonController extends AbstractController
{
    #[Route('/csv/to/json', name: 'app_csv_to_json')]
    public function index(): Response
    {
        return $this->render('csv_to_json/index.html.twig', [
            'controller_name' => 'CsvToJsonController',
        ]);
    }

    #[Route('/csv-to-json', name: 'api_csv_to_json', methods: ['GET'])]
    public function convertCsvToJson(ParameterBagInterface $parameterBag): JsonResponse
    {
        //fichier csv
        //$csvFile = $parameterBag->get('API_CSV_FILE_PATH');
        //$csvFile = $this->getParameter('API_CSV_FILE_PATH');
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

        return new JsonResponse($csvData);
    }


}
