# Architectuurdocumentatie Zaakvolgsysteem (ZVS)

Deze map bevat de architectuurdocumentatie van het Zaakvolgsysteem. De documenten zijn in Markdown
geschreven en diagrammen in [Mermaid](https://mermaid.js.org/), zodat ze naast de code worden
onderhouden, gereviewd via pull requests en direct op GitHub worden weergegeven.

## Overzicht

| Eis                                                                  | Document                           |
|----------------------------------------------------------------------|------------------------------------|
| Architectuurdiagram aanwezig (componenten, datastromen, integraties) | [architectuur.md](architectuur.md) |
| Project Start Architectuur (PSA) beschikbaar                         | [psa.md](psa.md)                   |
| Beslissingen en afwegingen gedocumenteerd (ADR's of vergelijkbaar)   | [adr/](adr/README.md)              |
| (ondersteunend) Begrippenlijst Nederlands–Engels                     | [begrippen.md](begrippen.md)       |

## Onderhoud

- Wijzigt een component, datastroom of integratie? Werk dan [architectuur.md](architectuur.md) bij in dezelfde pull request.
- Wordt een architectuurbeslissing genomen? Leg deze vast als ADR (zie [adr/README.md](adr/README.md)).
- De PSA wordt minimaal eens per jaar, of bij een wijziging in scope of hosting, herzien.

## Gerelateerde documentatie

- [README.md](../../README.md) – lokale ontwikkelomgeving
- [HOSTING_CHANGELOG.md](../../HOSTING_CHANGELOG.md) – deploymentinstructies per release
- [API-manual.md](../API-manual.md) – gebruik van de API
- [manual/](../../manual/index.rst) – gebruikershandleiding
