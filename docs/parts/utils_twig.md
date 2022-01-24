### Les balises de bases les plus utiles : 
|nom| utilisation | link | 
|---|---|---|
abs|Retourne une valeur absolue|[lien](https://twig.symfony.com/doc/2.x/filters/abs.html)
batch|Permet de faire un chunck sur une esemble de données. idéales pour des colonnes pair et impaires par exemple ou pour la pagination.|[lien](https://twig.symfony.com/doc/2.x/filters/batch)
capitalize|Majuscule sur string|[lien](https://twig.symfony.com/doc/2.x/filters/capitalize.html)
escape|echape un >String|[lien](https://twig.symfony.com/doc/2.x/filters/escape.html)
join| Permet de joindre un tableau de données|[lien](https://twig.symfony.com/doc/2.x/filters/join.html)
json_encode|encodage en json|[lien](https://twig.symfony.com/doc/2.x/filters/json_encode.html)
length|Calcul le nombre d'élements d'un objet itératif|[lien](https://twig.symfony.com/doc/2.x/filters/length.html)
lower|Minuscule sur string|[lien](https://twig.symfony.com/doc/2.x/filters/lower.html)
nl2br|Ajoute des sauts de ligne html à la place des retour chariot \n|[lien](https://twig.symfony.com/doc/2.x/filters/nl2br.html)
striptags| enleve les balises html, vous pouvez en conserver : {{ some_html\|striptags('<\br\><\p\>') }} gardera les balise br et p| [lien](https://twig.symfony.com/doc/2.x/filters/striptags.html)
url_encode| encode en url une valeur|[lien](https://twig.symfony.com/doc/2.x/filters/url_encode.html)

### Les balises de tests : 
|nom| utilisation | link | 
|---|---|---|
iterable|Savoir si la variable est itérative (un tableau ou un objet)|[lien](https://twig.symfony.com/doc/2.x/tests/iterable.html)
odd|Savoir si la variable est un nombre impaire|[lien](https://twig.symfony.com/doc/2.x/tests/odd.html)
even|Savoir si la variable est un nombre paire|[lien](https://twig.symfony.com/doc/2.x/tests/even.html)
empty|Savoir si une variable (string, array, obj) est vide|[lien](https://twig.symfony.com/doc/2.x/tests/empty.html)
null|savoir si une variable est nulle|[lien](https://twig.symfony.com/doc/2.x/tests/null.html)
divisible by|Savoir si une variable est divisible|[lien](https://twig.symfony.com/doc/2.x/tests/divisibleby.html)

### Les balises Notilac de base
|nom| utilisation | exemple  | 
|---|---|---|
localeDate|Permet de localiser une date à un format définis en fonction dtimezone bu backend. | {{ date \| localeDate('date-tiny', timezone = null)}}
workflow| Retourne le nom traduit de l'état. La variable doit etre un modèle étendu par le trait Waka\Utils\Classes\Traits\WakaWorkflowTrait | {{ objetavecworkflow \| workflow }};
camelCase| Retourne la var en camelCase | {{ 'foo_bar' \| camelCase }} ... fooBar
snakeCase| Retourne la var en snakeCase, inverse camelCase | - |
defaultConfig | retourne une config dans wcli.wconfig | {{ defaultConfig('code') }} => va recercher \Config('wcli.wconfig::' . $config_name);
getContent| Recherche un contenu d'un modèle qui a le trait  Waka\Utils\Classes\Traits\WakaContent | {{ objettraitcontent \| getContent($code,$column)}}
getRecursiveContent| Recherche récursivemement un contenu d'un modèle qui a le trait  Waka\Utils\Classes\Traits\WakaContent et le trait nestedTree| {{ objettraitcontent \| getRecursiveContent($code,$column)}}


#### LocaleDate
Exploite la classe Waka\Utils\Classes\WakaDate et la fonction localedate.<br>
Arguments : $format, $timezone, format par défaut **date-medium**<br>
* date-tiny | iso format : D/M/YY
* date-short | iso format : DD/MM/YY
* date-medium | iso format : DD MMM YYYY
* date | iso format : DD MMM YYYY
* date-full | iso format : dddd DD MMMM YYYY
* date-time | iso format : DD/MM/YY à HH:mm
* date-time-full | iso format : LLLL

#### Les fonctions Notilac de base
|nom| utilisation | exemple  | 
|---|---|---|
getColor| voir ci dessous | {{ get_color('#ff00', 'string', 'lighten', 0.3)}}
stubCreator| uniquement pour usage interne | $template, $allData, $secificData, $dataName = null |

#### getColor
Exploite la classe Mexitek\PHPColors\Color.

Arguments : $color, $mode = "rgba", $transform = null, $factor = 0.1

* $color| Une couleur format hex
* $mode : 2 possibilité
  * rgba : retourne un objet rgba avec [R,G,B,H,S,L] pour [red,green,blue,hue, staturation,lightness]
  * string
* $transform : trois possibilité
  * complementary : retourne la couleur complémentaire
  * lighten : retourne une couleur plus clair via factor
  * darken : reoturne une couleur plus sombre via factor
