PROJET MERCURE
==============

# Manipulation d'ajout d'une collection en base donnée

## Collection ERC20
- Ajouter la nouvelle collection en BDD
```
  php bin/console app:collection:add [blockchain] [project] [identifier] [ipfs] [extension-picture]
  // php bin/console app:collection:add  ethereum WeAbove 0xd0aaac09e7f9b794fafa9020a34ad5b906566a5c bafybeiani764y53yslrmu2lpyf7ek3idtabvlxvdjbco4sdygtezedagui gif -s 0 -l 1549
```
 
- Télécharger les fichiers de metadata via IPFS
- Mettre les fichiers dans le dossier data/[smartcontract]
- Lancer la commande pour renommer au même format de nom tous les fichiers
```
  php bin/console app:metadata:rename [smartcontract] [extension fichier]
  # exemple : php bin/console app:metadata:rename QmbcXpWty1S2VmxUdGW json
```


# TODO
- Initialiser une API
- API - token : get  by tokenId
- API - token : get tokens by criteria
- Init React project
- afficher l'image du NFT sur sa ficher
- liste les NFT
- ajouter un champ de recherche
- template
- trouver l'algo de ranking de TraitSniper
- ajouter le nombre de trait par NFT
- mettre à null les traits non défini
- afficher le prix des NFT par plateforme
- factoriser l'import de NFT d'une collection

