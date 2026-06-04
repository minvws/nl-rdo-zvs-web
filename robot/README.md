# Robotframework Tests

## Setup

Copy the example environment file and adjust it to your needs:

```bash
cp .env.robot.example .env.robot
```

Now replace the `APP_KEY` variable in `.env.robot` with your application key:
```bash
sail artisan key:generate --show
```

> **Note:** Only follow these steps if you want to run tests natively (via Python) or run tests individually. You can skip this setup if you plan to run all tests containerized via Docker.

If you want to run the tests natively, you need to set up the environment first:

- Make sure to [install](https://docs.astral.sh/uv/getting-started/installation/) `uv` first.
- Install the dependencies:
    - `uv sync`
    - `uv run rfbrowser init`

## Run Tests

There are two ways to run the tests:

- Containerized via Docker
- Directly via Python (and Docker Compose for services; allows for opening the browser and actively looking at the tests running)

After the tests run, you can open the report `report.html` in your browser to see the results in more detail.

### Run via Docker

> **Note:** Since the `e2e-tests` service depends on an GHCR image, make sure you are logged in to GHCR first. See [this](https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/managing-your-personal-access-tokens) for managing GitHub access tokens and then use `docker login` to authenticate to the ghcr.io registry.


To make sure the application runs correctly, we bootstrap it via Sail. We also need to set both the `APP_ENV` and `APP_SERVICE` environment variables to make sure the test application container is running.

To run all tests use the following command:
```bash
APP_ENV=robot APP_SERVICE=laravel.robot vendor/bin/sail up e2e-tests
```

You can also run a specific test suite by spinning up a temporary shell inside the `e2e-tests` container. For example, to run the `Exports.robot` test suite:

```bash
docker compose run --rm e2e-tests bash
```
And from there you can run e.g.:
```bash
uv run robot tests/Exports.robot
```

### Run via Python

Make sure the Docker Compose project is running. You need all the available services:

```bash
docker compose up -d e2e-tests
```

To run all tests, use the following command within the `robot/` directory:

```bash
uv run robot tests/
```

Or run a specific test suite, for example:

```bash
uv run robot tests/Exports.robot
```
```

Add the HEADLESS:False variable to the command to have chromium actually open so you can follow what the tests are doing.

```bash
uv run robot --variable HEADLESS:False tests/
```
