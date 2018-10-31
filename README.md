# sycoraxweb
PHP tool for Prospero dictionary editing 
To share and collective works on Prospero dictionaries

création
ajouter un EF : remplir le champ avec le nom de l'EF (par exemple UNIVERSITE@) et cliquer sur +
supprimer un EF : sélectionner un EF et cliquer sur -
modifier un EF : sélectionner un EF et cliquer sur M

les autres niveaux fonctionnent sur cette base (+ - M), selon ce qui a été sélectionné :
ajouter un type : sélectionner un EF, remplir le champ avec le nom du type (par exemple ...) et cliquer sur +
ajouter un représentant : sélectionner un type, emplir le champ avec l'expression (par exemple Université) et cliquer sur +

tout effacer : cliquer sur le symbole ensemble vide
récupérer le dictionnaire : bouton exporter, choisir un nom de fichier (par exemple monTest.fic) et enregistrer le fichier

On peut partir d'un dictionnaire stocké sur le serveur en le sélectionnant dans le menu déroulant

comparaison
Le principe : moitié gauche pour les concepts sur le serveur, moitié droite pour les concepts de la machine locale

On peut comparer avec un dictionnaire local en l'important : cliquer sur parcourir, sélectionner le fichier local, cliquer sur importer
Les éléments présents seulement sur le dico serveur sont coloriés en rouge, ceux seulement sur le dico local sont en vert

pour rechercher un élément, remplir le champ central et cliquer sur chercher (attention sensible à la casse)

en cliquant sur D, on efface du dico local tout ce qui est présent dans le dico serveur

fusion
l'incorporation d'éléments du dico local dans le dico serveur se fait via le bouton <-fusion
Les représentants déjà existant côté serveur ne sont pas ajoutés

1. rien n'est sélectionné à gauche, un EF sélectionné à droite : il importe l'EF et sa structure (types et représentants)
2. un EF sélectionné à gauche, un EF sélectionné à droite : il importe le contenu de l'EF local et sa structure dans l'EF serveur
3. un EF sélectionné à gauche, un type sélectionné à droite : si le type existe à gauche, il y importe les représentants, sinon il crée le type côté serveur
4. un EF sélectionné à gauche, un représentant sélectionné à droite : message d'erreur demandant une destination
5. un type sélectionné à gauche, un représentant sélectionné à droite : il importe le représentant dans le type
6. un type sélectionné à gauche, un type à droite : il importe les représentants dans le type
7. un type sélectionné à gauche, un EF sélectionné à droite : il importe les représentants dans le type serveur, sans tenir compte des types côté local
8. aucune sélection : il importe l'intégralité du dico local, en fusionnant EF et types quand ils existent
