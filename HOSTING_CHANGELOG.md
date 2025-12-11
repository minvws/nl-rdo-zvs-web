# Hosting changelog

Dit bestand bevat wijzigingen waarmee rekening moet worden gehouden bij het uitrollen van een release op de hostingomgeving. Dit kunnen nieuwe omgevingsvariabelen zijn, actieve scripts enz.

## Initiële stappen:

Bij een installatie op een nieuwe omgeving zullen de volgende stappen altijd uitgevoerd moeten worden:

- verwerk migraties in `database/sql` om de database-tabellen te maken
- zet de environment variabelen conform de eisen van de omgeving: zie de [voorbeeld `.env` file](./.env.example)
- maak een unieke applicatie key aan: `php artisan key:generate`
- voeg de cron-task toe (zie [Cron](#cron))
- (her)start de worker processen (zie [Workers](#workers))
- maak een admin-user met de naam en het email adres van de applicatiebeheerder (op non-productie omgevingen: de Scrum Master van het project): `php artisan app:create-admin-user`

### Cron

Daarnaast is er een cron-task nodig, zodat we taken kunnen inplannen. De cronjob moet elke minuut het commando `php artisan schedule:run` starten. Voor meer info, zie https://laravel.com/docs/11.x/scheduling#running-the-scheduler.

## Workers

Het verwerkingsregister moet gebruik maken van workers voor het uitvoeren van jobs. Na iedere release zullen workers opnieuw opgestart moeten worden opdat ze de code van de meest recente release uitvoeren.

## Voor iedere release

Na iedere deployment moeten:

- De bestaande caches verwijderd worden: `php artisan optimize:clear`
- Alle worker processen opnieuw opgestart worden

## Changelog per Tag:

### DEVELOP

- Verwerk migraties in `database/sql` v0.0.x

### v1.16.0
- Geen bijzonderheden

### v1.15.0
- Verwerk migraties in `database/sql` v0.0.43

### v1.14.0
- Verwerk migraties in `database/sql` v0.0.42
- Wegens een fount in de semantic versioning:
  - Controleer of de migraties uitgevoerd zijn in v0.0.41, zo niet, voer deze dan alsnog uit.
- 

### v1.13.3 (Hotfix)
- Geen bijzonderheden.

### v1.13.2 (Hotfix)
- Geen bijzonderheden.

### v1.13.1 (Hotfix)
- Geen bijzonderheden.

### v1.13.0
- Verwerk migraties in `database/sql` v0.0.41

### v1.12.0
- Verwerk migraties in `database/sql` v0.0.40

### v1.11.2 (Hotfix)
- Geen bijzonderheden.

### v1.11.1 (Hotfix)
- Geen bijzonderheden.

### v1.11.0
- Maak een database backup voorafgaand aan de migraties
- Verwerk migraties in `database/sql` v0.0.39

### v1.10.3 (Hotfix)
- Geen bijzonderheden.

### v1.10.2 (Hotfix)
- Geen bijzonderheden.

### v1.10.1

- Maak een database backup voorafgaand aan de migraties
- Verwerk migraties in `database/sql` v0.0.38

### v1.10.0

- Maak een database backup voorafgaand aan de migraties
- Verwerk migraties in `database/sql` v0.0.38

### v1.9.3 (Hotfix)

- Geen bijzonderheden.

### v1.9.2

- Geen bijzonderheden.

### v1.9.1

- Geen bijzonderheden.

### v1.9.0

- Verwerk migraties in `database/sql` v0.0.37


### v1.8.3 (hotfix)

- Geen bijzonderheden.

### v1.8.2

- Geen bijzonderheden.

### v1.8.1

- Geen bijzonderheden.

### v1.8.0

- Verwerk migraties in `database/sql` v0.0.36

### v1.7.0

- Verwerk migraties in `database/sql` v0.0.35

### v1.6.1 (hotfix)

- Geen bijzonderheden.

### v1.6.0

- Verwerk migraties in `database/sql` v0.0.34
- Upgrade de server naar PHP 8.4

### v1.5.0

- Verwerk migraties in `database/sql` v0.0.33
- Controleer de env-var `ALLOWED_USER_EMAIL_DOMAINS` (moet een komma-gescheiden list van domeinen zijn)
- Controleer de env-var `TRUSTED_HOSTS` (moet een komma-gescheiden list van domeinen zijn)


### v1.4.1

- Geen bijzonderheden

### v1.4.0

- Verwerk migraties in `database/sql` v0.0.32

### v1.3.0

- Verwerk migraties in `database/sql` v0.0.31

### v1.2.3

- Geen bijzonderheden

### v1.2.2

- Geen bijzonderheden

### v1.2.1

- Geen bijzonderheden

### v1.2.0

- Verwerk migraties in `database/sql` v0.0.30

### v1.1.2

- Geen bijzonderheden

### v1.1.1

- Verwerk migraties in `database/sql` v0.0.29

### v1.1.0

- Verwerk migraties in `database/sql` v0.0.28
- Voer eenmalig het commando `php artisan app:migrate-timeline-items` uit
- Voer eenmalig het sql-bestand uit: `database/sql-data/env-config/v0.0.28-update-department-term-type-settings.sql`
- Voer eenmalig het sql-bestand uit: `database/sql-data/env-config/v0.0.28-update-petition-type-custom-dates-labels.sql`

### v1.0.1

- Geen bijzonderheden.

### v1.0.0

- Verwerk migraties in `database/sql` v0.0.27
- Voer eenmalig het sql-bestand uit: `database/sql-data/env-config/v0.0.27-add-department-term-type-settings.sql`
- Voer eenmalig het sql-bestand uit: `database/sql-data/env-config/v0.0.27-petition-status-per-type.sql`

### v0.22.0

- Verwerk migraties in `database/sql` v0.0.26
- Voer eenmalig het sql-bestand uit: `database/sql-data/env-config/v0.0.26-add-statuses.sql`

### v0.21.0

- verwerk migraties in `database/sql` v0.0.25
- voer eenmalig het sql-bestand uit: `database/sql-data/env-config/v0.0.25-insert-default-substatus.sql`

### v0.20.0

- Verwerk migraties in `database/sql` v0.0.24

### v0.19.0

- Verwerk migraties in `database/sql` v0.0.23

### v0.18.1

- Geen bijzonderheden.

### v0.18.0

- Verwerk migraties in `database/sql` v0.0.22

### v0.17.0

- Verwerk migraties in `database/sql` v0.0.21

### v0.16.0

- Verwerk migraties in `database/sql` v0.0.20

### v0.15.0

- Verwerk migraties in `database/sql` v0.0.19

### v0.14.1

- Geen bijzonderheden.

### v0.14.0

- Verwerk migraties in `database/sql` v0.0.18
- Voer eenmalig het sql-bestand uit: `database/sql-data/env-config/v0.0.18-add-policy-departments.sql`

### v0.13.0

- Verwerk migraties in `database/sql` v0.0.17

### v0.12.2

- Verwerk migraties in `database/sql` v0.0.16

### v0.12.1

- Geen bijzonderheden.

### v0.12.0

- Verwerk migraties in `database/sql` v0.0.15

### v0.11.0

- Verwerk migraties in `database/sql` v0.0.14

### v0.10.0

- Verwerk migraties in `database/sql` v0.0.13
- Voer `/database/sql-data/env-config/v0.0.13-add-departments.sql` uit om de afdelingen aan te maken

### v0.9.0

- Verwerk migraties in `database/sql` v0.0.12

### v0.8.1

- Geen bijzonderheden.

### v0.8.0

- Verwerk migraties in `database/sql` v0.0.11

### v0.7.1

- Verwerk migraties in `database/sql` v0.0.10
- Voor de scanning van documenten is clamav nodig, evt is de locatie van de socket aan te passen dmv de env-var
  `VIRUSSCANNER_SOCKET` (standaard staat deze ingesteld op `unix:///var/run/clamav/clamd.ctl`). Voor meer info over de
  socket-configuratie, zie: https://github.com/clue/socket-raw?tab=readme-ov-file#createclient

### v0.7.0

- Verwerk migraties in `database/sql` v0.0.10
- Voor de scanning van documenten is clamav nodig, evt is de locatie van de socket aan te passen dmv de env-var
  `VIRUSSCANNER_SOCKET` (standaard staat deze ingesteld op `unix:///var/run/clamav/clamd.ctl`). Voor meer info over de
  socket-configuratie, zie: https://github.com/clue/socket-raw?tab=readme-ov-file#createclient

### v0.6.1

- Verwerk migraties in `database/sql` v0.0.9

### v0.6.0

- Geen bijzonderheden.

### v0.5.0

- Verwerk migraties in `database/sql` v0.0.8
- Stel de trusted hosts in via de env-var `TRUSTED_HOSTS`

### v0.4.1

- Verwerk migraties in `database/sql` v0.0.7

### v0.4.0

- Verwerk migraties in `database/sql` v0.0.6
- Na de deployment het volgende command uitvoeren om tenminste 1 admin-user de admin-role toe te kennen: `php artisan app:add-user-role`

### v0.3.0

- Verwerk migraties in `database/sql` v0.0.5

### v0.2.1

- Verwerk migraties in `database/sql` v0.0.4

### v0.1.5

- Geen bijzonderheden.

### v0.1.4

- Verwerk migraties in `database/sql` v0.0.3

### v0.1.0 - v0.1.3

- Verwerk migraties in `database/sql` om de database-tabellen te maken
- Zet de environment variabelen conform de eisen van de omgeving: zie de [voorbeeld `.env` file](./.env.example)
- Maak een unieke applicatie key aan: `php artisan key:generate`
- Voeg de cron-task toe (zie [Cron](#cron))
- (Her)start de worker processen (zie [Workers](#workers))
- Maak een admin-user met de naam en het email adres van de applicatiebeheerder (op non-productie omgevingen: de Scrum Master van het project): `php artisan app:create-admin-user`
