# 1. Styling Menubalk de Afdeling-switcher: afwijken van design

Datum: 2024-10-21

## Status

Geaccepteerd

## Context en probleemstelling

* We moeten een afdeling-switcher maken. 
* Het design komt niet overeen met wat manon biedt.
* De implementatie is niet te gebruiken voor het design, want een select kan niet gekleurde blokken voor text tonen.
* Zowel het design als de implementatie zijn niet te gebruiken voor de no-js versie.

## Overwogen opties

1. Design pixel-perfect maken met complete restyling en Alpinejs
2. Afwijken van design met minimale design overrides en een form met select gebruiken voor de no-js versie
3. Afwijken van design met minimale design overrides, zowel yes-js als no-js met links werken.

## Besluit
Gekozen optie: "Afwijken van design met minimale design overrides, zowel yes-js als no-js met links werken", omdat we dan zoveel mogelijk in lijn blijven met zowel de Manon eisen als de no-js eisen zonder op 2 verschillende implementaties te leunen. Deze optie is tevens het meeste in lijn met de manier hoe we elders htmx gebruiken.
