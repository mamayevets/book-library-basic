#!/usr/bin/env bash
#
# Book Library API — one-shot setup script.
# Verifies prerequisites, installs Composer deps, starts Sail, runs
# migrations + seed, and generates Swagger docs.
#

set -euo pipefail

# --- pretty output ---
if [ -t 1 ]; then
    GREEN='\033[0;32m'
    YELLOW='\033[1;33m'
    RED='\033[0;31m'
    BOLD='\033[1m'
    NC='\033[0m'
else
    GREEN=''; YELLOW=''; RED=''; BOLD=''; NC=''
fi

info() { printf "${GREEN}==>${NC} %s\n" "$*"; }
warn() { printf "${YELLOW}!${NC}  %s\n" "$*"; }
fail() { printf "${RED}✗${NC}  %s\n" "$*" >&2; exit 1; }

printf "\n${BOLD}Book Library API — automatic setup${NC}\n\n"

# --- 1. prerequisites ---
info "Checking prerequisites…"

command -v docker >/dev/null 2>&1 || fail \
"Docker is not installed.
  • macOS / Windows: install Docker Desktop (https://www.docker.com/products/docker-desktop/)
  • Linux: install docker-ce via your package manager"

if ! docker info >/dev/null 2>&1; then
    fail "Docker daemon is not running.
  • macOS / Windows: open Docker Desktop and wait for it to start
  • Linux: 'sudo systemctl start docker'"
fi

if [ "$(id -u)" -eq 0 ]; then
    SUDO=""
elif command -v sudo >/dev/null 2>&1; then
    SUDO="sudo"
else
    SUDO=""
fi

ensure_compose() {
    if docker compose version >/dev/null 2>&1; then
        echo "plugin"; return 0
    fi
    if command -v docker-compose >/dev/null 2>&1; then
        echo "legacy"; return 0
    fi
    return 1
}

COMPOSE_KIND="$(ensure_compose || true)"

if [ -z "$COMPOSE_KIND" ]; then
    warn "Docker Compose not found — attempting to install automatically…"
    if command -v apt-get >/dev/null 2>&1; then
        $SUDO apt-get update -qq >/dev/null 2>&1 || true
        $SUDO apt-get install -y -qq docker-compose-v2 >/dev/null 2>&1 \
            || $SUDO apt-get install -y -qq docker-compose-plugin >/dev/null 2>&1 \
            || $SUDO apt-get install -y -qq docker-compose >/dev/null 2>&1 \
            || true
    elif command -v dnf >/dev/null 2>&1; then
        $SUDO dnf install -y docker-compose-plugin >/dev/null 2>&1 \
            || $SUDO dnf install -y docker-compose >/dev/null 2>&1 \
            || true
    elif command -v brew >/dev/null 2>&1; then
        brew install docker-compose >/dev/null 2>&1 || true
    fi
    COMPOSE_KIND="$(ensure_compose || true)"
fi

if [ -z "$COMPOSE_KIND" ]; then
    fail "Docker Compose is not installed and could not be auto-installed.
Please install one of:
  • Debian / Ubuntu:      sudo apt install -y docker-compose-v2
  • Fedora / RHEL:        sudo dnf install -y docker-compose-plugin
  • Arch:                 sudo pacman -S docker-compose
  • macOS Homebrew:       brew install docker-compose
  • Windows / macOS:      install Docker Desktop (includes Compose)
Then re-run ./setup.sh"
fi

info "Docker $(docker --version | awk '{print $3}' | tr -d ',') ready"
if [ "$COMPOSE_KIND" = "plugin" ]; then
    info "Compose plugin $(docker compose version --short 2>/dev/null || echo 'unknown') ready"
else
    info "Compose legacy $(docker-compose --version | awk '{print $3}' | tr -d ',') ready (Sail will use this as fallback)"
fi

# --- 2. .env ---
if [ ! -f .env ]; then
    info "Creating .env from .env.example"
    cp .env.example .env
else
    info ".env already exists — keeping it"
fi

# --- 3. composer install ---
if [ ! -d vendor ] || [ ! -f vendor/bin/sail ]; then
    info "Installing PHP dependencies via one-shot Docker container (~1-2 min on first run)…"
    # --security-opt seccomp=unconfined is defensive: lets the container
    # spawn DNS-resolution threads on hosts with strict seccomp profiles
    # (e.g. SteamOS). On macOS / Windows / standard Linux it is a no-op.
    docker run --rm \
        --security-opt seccomp=unconfined \
        -u "$(id -u):$(id -g)" \
        -v "$(pwd):/var/www/html" \
        -w /var/www/html \
        laravelsail/php84-composer:latest \
        composer install --ignore-platform-reqs --no-interaction --no-progress
else
    info "vendor/ already populated — skipping composer install"
fi

# --- 4. sail up ---
info "Starting Sail containers (slow on first run while images build)…"
./vendor/bin/sail up -d

# --- 5. wait for mysql ---
info "Waiting for MySQL to be ready…"
ATTEMPTS=0
MAX_ATTEMPTS=60
until docker exec book-library-basic-mysql-1 mysqladmin ping -u root -ppassword --silent >/dev/null 2>&1; do
    ATTEMPTS=$((ATTEMPTS + 1))
    if [ $ATTEMPTS -ge $MAX_ATTEMPTS ]; then
        fail "MySQL did not become ready after 2 minutes. Inspect with: docker compose logs mysql"
    fi
    sleep 2
done
info "MySQL is healthy"

# --- 6. app key ---
info "Generating Laravel application key"
./vendor/bin/sail artisan key:generate --force --no-interaction

# --- 7. migrate + seed ---
info "Running migrations and seeding 25 fake books"
./vendor/bin/sail artisan migrate --seed --force --no-interaction

# --- 8. swagger ---
info "Generating Swagger / OpenAPI documentation"
./vendor/bin/sail artisan l5-swagger:generate

# --- done ---
printf "\n${BOLD}${GREEN}✓ All set. Your API is live.${NC}\n\n"
printf "  ${BOLD}API root${NC}        →  http://localhost\n"
printf "  ${BOLD}Books endpoint${NC}  →  http://localhost/api/books\n"
printf "  ${BOLD}Swagger UI${NC}      →  http://localhost/api/documentation\n\n"

printf "Useful next commands:\n"
printf "  ./vendor/bin/sail test                  # run PHPUnit (19 tests)\n"
printf "  ./vendor/bin/sail artisan tinker        # interactive REPL\n"
printf "  ./vendor/bin/sail logs -f               # tail container logs\n"
printf "  ./vendor/bin/sail down                  # stop and remove containers\n\n"

# --- 9. open browser (best-effort, no failure) ---
URL="http://localhost/api/documentation"
if command -v open >/dev/null 2>&1; then
    open "$URL" >/dev/null 2>&1 || true
elif command -v xdg-open >/dev/null 2>&1; then
    xdg-open "$URL" >/dev/null 2>&1 || true
elif command -v wslview >/dev/null 2>&1; then
    wslview "$URL" >/dev/null 2>&1 || true
fi
