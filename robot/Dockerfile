FROM python:3.13-slim
COPY --from=ghcr.io/astral-sh/uv:latest /uv /uvx /bin/

RUN apt-get update \
    && apt-get install -y --no-install-recommends curl gnupg \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs docker.io docker-compose \
    && npx playwright install-deps \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY robot/pyproject.toml robot/uv.lock /opt/project/

ENV UV_LINK_MODE=copy
ENV UV_PROJECT_ENVIRONMENT=/opt/venv
ENV UV_PROJECT=/opt/project

WORKDIR /app/robot

#    --mount=type=bind,source=robot/uv.lock,target=uv.lock \
#    --mount=type=bind,source=robot/pyproject.toml,target=pyproject.toml \

RUN --mount=type=cache,target=/root/.cache/uv \
    uv sync --no-group dev --locked --no-install-project && uv run rfbrowser init

ENV PYTHONPATH=resources/
CMD ["uv", "run", "robot", "tests/"]
