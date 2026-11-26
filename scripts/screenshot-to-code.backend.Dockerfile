FROM python:3.12-slim-bookworm

WORKDIR /app
COPY poetry.lock pyproject.toml ./
RUN pip install --no-cache-dir poetry==2.4.1 \
    && poetry config virtualenvs.create false \
    && poetry install --only main \
    && playwright install --with-deps chromium

COPY . .
