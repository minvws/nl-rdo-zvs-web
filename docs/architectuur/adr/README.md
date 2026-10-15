# Architecture Decision Records (ADR's)

Een ADR legt één significante architectuurbeslissing vast: de context, de overwogen opties, de beslissing en de
gevolgen.

**Wanneer een ADR?** Bij keuzes die lastig terug te draaien zijn of grote impact hebben: frameworks, opslag,
integraties, beveiligingsmechanismen, hosting, principes voor het datamodel, afwijkingen van de PSA.

## Templates

We gebruiken [templates/template.md](templates/template.md) als template voor al onze ADR's. Die is gebaseerd op
MADR (zie hieronder).

### MADR

Voor nieuwe ADR's gebruik je een van de volgende templates als startpunt:

* [adr-template.md](templates/adr-template.md) bevat alle secties, met uitleg per onderdeel.
* [adr-template-minimal.md](templates/adr-template-minimal.md) bevat alleen de verplichte secties, met uitleg.
  *Gevolgen* is opgenomen maar gemarkeerd als optioneel.
* [adr-template-bare.md](templates/adr-template-bare.md) bevat alle secties, leeg (zonder uitleg).
* [adr-template-bare-minimal.md](templates/adr-template-bare-minimal.md) bevat de verplichte secties, zonder uitleg.
  *Gevolgen* is opgenomen maar gemarkeerd als optioneel.

## Werkwijze

1. Maak een ADR aan door een template te kopiëren naar `NNNN-korte-titel.md`.
2. Zet de status op **Voorgesteld** en open een pull request. De ADR wordt besproken in de review.
3. Na akkoord wordt de status **Geaccepteerd**.
4. Een geaccepteerde ADR wordt inhoudelijk niet meer gewijzigd. Wordt een beslissing herzien, schrijf dan een
   nieuwe ADR en zet de oude op **Vervangen door [NNNN](...)**.

Statussen: `Voorgesteld` · `Geaccepteerd` · `Afgewezen` · `Vervallen` · `Vervangen door NNNN`
