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