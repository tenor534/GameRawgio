# SymfonyILoveGamer

Z10 - Création d'une Gateway
Quentin CERNY
•
15 nov. (Modification : 15 nov.)
100 points
Date limite : 23:59
# Exercice 1 : Api Hello World

Créez une zone dans l'application qui répond à une url de votre choix.
La réponse devra être au format .json et générée de la manière la plus optimisée selon vous.
Le json renvoyé devra contenir {hello:"world"}

=> #[Route('/hello', name: 'hello_world')]
=> class HelloController

# Exercice 2 : Api Jeux

À la place de renvoyer hello world, effectuez une requête renvoyant la liste des jeux enregistrée dans la base de donnée au format json. N'affichez que l'identifiant et le nom du jeu.

=> #[Route('/api/games/db', name: 'api_video_game_db_list', methods: ['GET'])]

=> class VideoGameController 

# Exercice 3 : Parsing et conversion d'un Csv

Créer une nouvelle Api qui expose en Json les données d'un fichier .Csv fourni.
=> #[Route('/api/games/csv', name: 'api_video_game_csv_list', methods: ['GET'])]

=> class VideoGameController 

# Exercice 4 : Création d'une Gateway

Reprenez l'Api jeux en intégrant les données de l'Api issue du Csv à la liste des jeux.

Chaque jeu du Csv doit apparaître dans le résultat en Json.
Chaque jeu de la base de données doit apparaître dans le résultat du Json.

Si des données du Csv et de la base concernent le même jeu, les données doivent être fusionées.

=> #[Route('/api/games/fusion/json', name: 'api_video_game_csv_json', methods: ['GET'])]

=> class VideoGameController 

# Exercice 5 :

Utilisez le Csv games2.csv comme source de données pour la seconde Api.
Attention, la structure de l'Api originale ne doit pas changer, mais il doit aussi être possible de lire le fichier au nouveau format.

=> #[Route('/api/games/fusion/json', name: 'api_video_game_csv_json', methods: ['GET'])]

=> class VideoGameController 

=> .env (changer la variable d'environnement avec le bon fichier csv) : 

API_CSV_FILE_PATH='/Users/srako/PhpstormProjects/symfony/GameRawgio/src/Datas/games.csv'
API_CSV_FILE_PATH='/Users/srako/PhpstormProjects/symfony/GameRawgio/src/Datas/games2.csv'
