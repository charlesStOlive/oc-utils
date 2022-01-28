# Comandes waka
Le plugin utils étend les comandes disponibles. 
### Création de plugin
Commande permettant de créer un modèle à partir d'un fichier excel. 
#### Préalable: 
Vous devez mettre dans votre fihier env, la source des fichiers excels. 
```
# Exemple adresse des fichiers sources excel d'un  module Waka
SRC_WAKA="C:\\Users\\charl\\Google Drive\\Archives non git\\waka\\src"

# Exemple adresse des fichiers sources excel d'un  module Wcli
SRC_WCLI="C:\\Users\\charl\\Google Drive\\Archives non git\\packcrm\\src"

```

Si un fichier worder_/_document.xlsx éxiste dans le répertoire pointé (voir ci dessus) et qu'il dispose de 3 onglets document_data, document_config, document_relations alors vous pouvez lancer la commande suivante : 
```
php artisan waka:mc waka.worder docuement worder_document --option
```
Cela va créer le modèle, les fichiers yaml de fields et de colonnes, le controller, les updates et les fichiers de langues. en ajoutant --option vous pouvez choisir ce que vous voulez créer. 

### MAJ des couleurs
Pour mettre à jour les couleurs vous devez au préalable ajouter cette information dans le tableau de config. 
```php
'brand_data' => [
        'logoPath' => themes_path('wakatailwind/assets/images/logo.png'),
        'faviconPath' => themes_path('wakatailwind/assets/images/logo.png'),
        'appName' => 'NOTILAC',
        'tagline' => 'Automatisez vos documents',
        'primaryColor' => "#143d59",
        'secondaryColor' => "#787978",
        'accentColor' => "#f4b41a",
        'gd' => '#787978',
        'gl' => '#E0E0E0',
        'dark' => '#252525',
        'oldColors' => [
            // //Uniquement si changement de couleurs ne pas utiliser si les css sont reintialise par composer
            // 'primaryColor' => "#143d59",
            // 'secondaryColor' => "#787978",
            // 'accentColor' => "#f4b41a",
        ],
    ],
```
Si la config est ok, lancer la commande suivante :
```
php artisan waka:uiColor wcli.wconfig
```
Cela va : 
* Modifier les couleurs de l'UI
* Modifier les thèmes css des mails et pdfs

En opition : 
* vous pouvez recréer le fichier var.less pour cela il suffit de valider lorsque le système pose la question
* Modifier d'anciennes couleurs déjà injecté. **Si et seulement si**, entre temps les couleurs **n'ont pas été réinitialisé** par la MAJ de l'UI via composer par exemple. En effet le système va chercher la correspondande des anciennes couleurs pour tenter une modification.  

### Nettoyage des modèles et des images inutiles
Pour tester le nettoyage lancer la commande suivante : 
```
php artisan waka:cleanModel
```

Vous pouvez ajouter le test sur les images du disque à éffacer, en ajoutant l'option
```
php artisan waka:cleanModel --cleanFile
```

Enfin pour executer le nettoyage lancer l'option executeClean : 
```
php artisan waka:cleanModel --executeClean
# ou
php artisan waka:cleanModel --cleanFile --executeClean
```