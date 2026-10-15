# 3. Foreign key constraints afdwingen in de database

Datum: 2025-09-25

## Status

Geaccepteerd

Retroactief vastgelegd op 2026-09-29. De beslissing is geïmplementeerd in commit `3a22f893` (2025-09-25) en
uitgerold met de SQL-migraties in `database/sql/v0.0.45` (release v1.19.0).

## Context en probleemstelling

Een analyse van het databaseschema liet zien dat veel foreign key constraints ontbraken in de PostgreSQL-database.
De Eloquent-relaties in de applicatie waren wel correct gedefinieerd, maar op databaseniveau werd de referentiële
integriteit niet afgedwongen. Daardoor:

* konden er verweesde records (orphaned records) ontstaan;
* was de data-integriteit niet gegarandeerd op databaseniveau;
* was het gedrag bij het verwijderen van een bovenliggend record niet vastgelegd.

Hoe borgen we de referentiële integriteit van de gegevens, onafhankelijk van de applicatielogica?

## Beslissingsfactoren

* Data-integriteit moet op databaseniveau gegarandeerd zijn, ook bij fouten in de applicatie of bij handmatige
  wijzigingen door beheer.
* Geen risico op onbedoeld gegevensverlies. Zaakgegevens zijn archiefbescheiden (Archiefwet).
* Bestaande data moet intact blijven bij het toevoegen van de constraints.
* De applicatielogica hoeft niet te worden aangepast: de Eloquent-relaties zijn al correct.
* Wijzigingen lopen via de SQL-migraties in `database/sql`, die door de hosting worden uitgevoerd.

## Overwogen opties

* Foreign keys toevoegen met `ON DELETE RESTRICT` voor alle relaties
* Foreign keys toevoegen met cascadegedrag per relatie (`CASCADE`, `SET NULL` of `RESTRICT`)
* Geen constraints: integriteit alleen via de Eloquent-relaties in de applicatie (huidige situatie)

## Beslissing

Gekozen optie: "Foreign keys toevoegen met `ON DELETE RESTRICT` voor alle relaties". Deze optie garandeert de
integriteit op databaseniveau zonder risico dat een verwijderactie onbedoeld afhankelijke gegevens meeneemt.

De constraints zijn toegevoegd met één migratie per tabel of relatie (`database/sql/v0.0.45/0003` t/m `0027`), met de
naamconventie `fk_<tabel>_<kolom>`:

| Groep                 | Relaties                                                                                                   |
|-----------------------|------------------------------------------------------------------------------------------------------------|
| Kernentiteiten        | `contacts.department_id`, `custom_costs.petition_id`, `decisions.department_id`, `petition_categories.department_id`, `petition_types.department_id`, `petition_statuses.petition_type_id` |
| Zaken (`petitions`)   | `petition_type_id`, `assigned_to`, `petition_status_id`, `department_id`                                   |
| Pivottabellen         | `custom_petition_property_petition`, `decision_petition`, `department_user`, `petition_petition`, `petition_policy_department` (beide kolommen) |
| Overige tabellen      | `custom_petition_properties.petition_type_id`, `petition_exports.department_id`, `petition_exports.petition_type_id`, `petition_exports.petition_category_id`, `petition_number.department_id`, `petition_type_custom_dates_labels.petition_type_id`, `processing_steps.decision_id`, `processing_steps.assigned_to`, `timeline_items.user_id`, `user_global_roles.user_id` |

Al eerder bestaande constraints zijn ongewijzigd gebleven, waaronder die op `contact_petition` (`CASCADE`), de
`petition_*`-tabellen die aan een zaak hangen, `sessions.user_id` en `users.last_visited_department_id`.

Buiten scope blijft de polymorfe relatie `timeline_items.timelineable_*`. Een polymorfe relatie kan niet met een
foreign key worden afgedwongen.

### Gevolgen

* Goed, omdat verweesde records niet meer kunnen ontstaan in de betreffende tabellen.
* Goed, omdat verwijderen nooit ongemerkt afhankelijke gegevens meeneemt. Een verwijderactie op een record waar nog
  naar verwezen wordt, faalt.
* Slecht, omdat de applicatie afhankelijke records eerst zelf moet verwijderen of loskoppelen voordat een bovenliggend
  record kan worden verwijderd. Anders volgt een databasefout.
* Slecht, omdat de indexes op de FK-kolommen die in de oorspronkelijke aanpak waren voorzien niet zijn toegevoegd.
  PostgreSQL maakt die niet automatisch aan. Joins en `RESTRICT`-controles bij verwijderen doen daardoor een
  sequentiële scan op de verwijzende tabel. Alleen `petition_petition` heeft indexes op beide kolommen.
* Neutraal, omdat de pivottabellen (bijv. `department_user`, `decision_petition`) nog steeds geen primary key of
  unieke constraint hebben. Dubbele koppelingen blijven technisch mogelijk.

### Bevestiging

* De constraints zijn aanwezig in de SQL-migraties en worden uitgevoerd bij elke release.

## Voor- en nadelen van de opties

### Foreign keys met `ON DELETE RESTRICT` voor alle relaties

* Goed, omdat één uniform beleid eenvoudig te begrijpen en te reviewen is.
* Goed, omdat het geen risico op gegevensverlies door cascades geeft.
* Slecht, omdat verwijderlogica expliciet in de applicatie moet staan.

### Foreign keys met cascadegedrag per relatie

* Goed, omdat verwijderen van bijvoorbeeld een zaak automatisch de gekoppelde pivotrecords opruimt.
* Slecht, omdat een verkeerd gekozen `CASCADE` tot gegevensverlies kan leiden. Dat was het grootste risico in de analyse.
* Slecht, omdat het per relatie een afweging vraagt en foutgevoeliger is bij review.

### Geen constraints, alleen Eloquent-relaties

* Goed, omdat het geen migratie- of performancerisico geeft.
* Slecht, omdat integriteit afhangt van correcte applicatiecode en fouten of handmatige wijzigingen niet worden
  tegengehouden.

## Meer informatie

Aanpak volgens de oorspronkelijke analyse:

1. Data-audit op verweesde records vóór het toevoegen van de constraints. Een constraint kan alleen worden
   toegevoegd als de bestaande data er al aan voldoet.
2. Back-up van de productiedatabase, en testen op een kopie van productiedata.
3. Migraties per constraint, met prioriteit voor de kernentiteiten en pivottabellen.
