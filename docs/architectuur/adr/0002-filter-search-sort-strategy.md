# 2. Opzet van het filteren, doorzoeken en sorteren van afdeling items

Datum: 2024-11-07

## Status

Geaccepteerd

## Context en probleemstelling

Om afdeling-items (in eerste instantie vooral zaken) gemakkelijk vindbaar te maken is het wenselijk om zoek, filter en sorteermogelijkheden te bieden aan de eindgebruikers. 
Met deze nieuwe UI interactie features moeten gebruikers door het invullen van een aantal filters, zoektermen of sorteringen snel de gewenste afdeling-items kunnen terugvinden. 

## Beslissingsfactoren

* Er is een beperkte hoeveelheid tijd om deze feature te bouwen

## Overwogen opties

* Zelf bouwen
* Purity library gebruiken

## Besluit
Gekozen optie: "Library gebruiken", omdat hierdoor geen extra query filter logica meer geschreven hoeft te worden. Ook voorziet de library in het kunnen uitvoeren van geavanceerde filter- en vergelijkingslogica.

## Gevolgen

* De gekozen library dwingt een bepaalde opbouw van de zoekparameters af. Deze zouden idealiter direct uit de formulier componenten gegenereerd kunnen worden.
* Dat is niet wenselijk, we willen de hoeveelheid zoeklogica die in de formuliercomponenten (blade templates) verstopt wordt voorkomen.
* Daarom is het nodig om in de service laag een vertaling te maken tussen het formulier en de zoek/filter en sorteer library. 

## Voor- en nadelen van de opties

Voordelen: 
 - we hoeven in de repository niet/geen extra werk te verrichten
 - de bestaande rudimentaire zoek oplossing kan verwijderd worden
 - de frontend kan wat zoeken, filteren en sorteren betreft no-js blijven functioneren

Nadelen:
 - we worden afhankelijk van een 3rd party library 

## Meer Informatie

Laravel Purity: (https://abbasudo.github.io/laravel-purity/)
