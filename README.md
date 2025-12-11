# Zaakvolgsysteem (ZVS)

The Ministry of Health, Welfare and Sport (VWS) processes Woo requests as well as objections and appeals through the Directorate for Legislation and Legal Affairs (WJZ) and the Program Directorate for Transparency (PDO).

iRealisatie is developing a modern case management system to support these processes. It will handle workflows for Woo, objections, and appeals, automatically monitor deadlines, enable document search and reuse, and provide dashboards on workload and processing times. This secure web application will give management better insight, improve efficiency and compliance, and ensure timely and accurate reporting for VWS.

## Getting started

### Prerequisites

-   An up-to-date [Docker (Desktop)](https://www.docker.com/products/docker-desktop/) installation

### Setup

1. Clone this repository: git clone git@github.com:minvws/nl-rdo-zvs-web-private.git zvs
2. Cd into the newly created `zvs` folder: `cd zvs`
3. Open a new terminal at the root of this folder
4. Create an `.env` file by copying the `./.env.example` to `./.env`
5. Setup docker using laravel/sail by running:

    ```
    docker run --rm \
        -u "$(id -u):$(id -g)" \
        -v "$(pwd):/var/www/html" \
        -w /var/www/html \
        laravelsail/php84-composer:latest \
        composer install --ignore-platform-reqs
    ```

   For more information see: https://laravel.com/docs/11.x/sail#installing-composer-dependencies-for-existing-projects

   (The steps below assume you have an alias for `./vendor/bin/sail`, if not run: `alias sail=./vendor/bin/sail`)

6. Start the container by running `sail up -d laravel.test`
7. Run `sail npm install && sail npm run build` to build the node environment
8. Run `sail artisan key:generate` to generate a new application key
9. Run `sail artisan migrate:fresh --seed` to (re)run all migrations and default seeder
10. Browse to [http://localhost/](http://localhost/) and see the login screen
11. The default seeded user/password combination is `admin@minvws.nl` / `admin`

#### Development .env vars

For development, the following env var settings wil be helpful:

```
# dev env vars:
OUTBOX_SMTP_FROM=foo@bar.nl    # the local env uses mailpit, any from email adres will do
ONE_TIME_PASSWORD_DRIVER=fake  # the application will accept any OTP when prompted
QUEUE_CONNECTION=sync          # jobs will by executed synchronously, no need to listen to any queues
CSP_ENABLED=false              # allows telescope from rendreing css (/telescope)
```

#### Running tests in your dev-env
In order to run the test suite in your dev env. Simply use sail to run the quality assurance tests on your
docker container:

```bash
# start by creating a test database (for phpunit tests in non-parallel mode)
$ sail composer reset-test-db
```

Then run the test suite. Beware that the tests will run in parrallel mode. This means that the first time, you need to run
`sail composer test-reset` which will recreate the test databases. This is only needed once, afterwards you can run the tests

```bash
$ sail composer quality
# Or just the PHPUnit tests?
$ sail composer test
```

#### Running end-to-end tests

See the [robot/README.md](./robot/README.md) for instructions on setting up and running the end-to-end tests.

#### Building the manual

The manual is located in the `manual` folder and is built with [Sphinx](https://www.sphinx-doc.org/).

## License

The source code is released under the [EUPL license](./LICENSES/EUPL-1.2.txt).
The documentation is released under the [CC0 license](./LICENSES/CC0-1.0.txt).
The EUPL 1.2 and the CC0 do not apply to photos, videos, infographics, fonts or other forms of media.

This repository follows the [REUSE Specfication v3.3](https://reuse.software/spec/).
Please see [REUSE.toml](./REUSE.toml) and the individual `*.license` files for copyright and license information.
