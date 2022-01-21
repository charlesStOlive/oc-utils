# Installation
Pour l'installation de l'application, veuillez suivre les recomendation de [winter.cms](https://wintercms.com/docs/setup/installation). Les recomendations sur le serveur à utiliser sont les mêmes. 

Votre projet est une instance winter.cms préconfiguré : 
1. Les variables sont dans un fichier .env
2. Le projet est normalement dans un répertoire privé et les fichiers publics dans un répertoire /public. 
   
## Installation du repo et des librairies
Votre système fonctione avec des sous modules et un fichier env.
### Le fichier env
Ce fichier emabarque l'intégralité des codes d'accès à vos API, Base de données,etc. 
Il ne faut jamais communiquer ces informations ni laisser ces informations dans un repo. Avant tout e installation verifiez que les informations du fichier .env sont complètes. Le plus simple est de copier celui en place sur l'ancien serveur et de modifier éventuellement les données. 
```
# Données de base d'un fichier .env
APP_DEBUG=false #Se surtout pas activer APP_DEBUG en prod
APP_URL=https://votreurl.com
APP_KEY={une clef ex : leJfWCTWMd6w3w0AfWEj2A7mmZk1INtoYRnm}

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE={nom base de données}
DB_USERNAME={nom utilisateur}
DB_PASSWORD="{ password }"


CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_DRIVER=database

ROUTES_CACHE=false
ASSET_CACHE=false
DATABASE_TEMPLATES=false
LINK_POLICY=detect
ENABLE_CSRF=true

```
<!--includepart[env]-->

### Lancer l'installation
1. Clonez votre repo ou ouvrez le fichier zip qui vous à été fournis
1. executez les commande suivantes dans votre terminal  pour installer et mettre à jours vos sous modules
```
git submodule init
git submodule update
```
4. Mettre à jours et ou instaler les librairies
```
composer install
```
5. Effectuez la migration ou simplement mettre à jour les tables
```
php artisan queue:restart
```
6. Mirroring (vous devez choisir un répertoire publice ex: public dans le parametrage de votre serveur)
```
php artisan winter:mirror public
```
