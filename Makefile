# Makefile for mod_exescorm Moodle plugin

# Define SED_INPLACE based on the operating system
ifeq ($(shell uname), Darwin)
  SED_INPLACE = sed -i ''
else
  SED_INPLACE = sed -i
endif

# Detect the operating system and shell environment
ifeq ($(OS),Windows_NT)
    # Initially assume Windows shell
    SHELLTYPE := windows
    # Check if we are in Cygwin or MSYS (e.g., Git Bash)
    ifdef MSYSTEM
        SHELLTYPE := unix
    else ifdef CYGWIN
        SHELLTYPE := unix
    endif
else
    SHELLTYPE := unix
endif

# Check if Docker is running
# This target verifies if Docker is installed and running on the system.
check-docker:
ifeq ($(SHELLTYPE),windows)
	@echo "Detected system: Windows (cmd, powershell)"
	@docker version > NUL 2>&1 || (echo. & echo Error: Docker is not running. Please make sure Docker is installed and running. & echo. & exit 1)
else
	@echo "Detected system: Unix (Linux/macOS/Cygwin/MinGW)"
	@docker version > /dev/null 2>&1 || (echo "" && echo "Error: Docker is not running. Please make sure Docker is installed and running." && echo "" && exit 1)
endif

# Check if the .env file exists, if not, copy from .env.dist
# This target ensures that the .env file is present by copying it from .env.dist if it doesn't exist.
check-env:
ifeq ($(SHELLTYPE),windows)
	@if not exist .env ( \
		echo The .env file does not exist. Copying from .env.dist... && \
		copy .env.dist .env \
	) 2>nul
else
	@if [ ! -f .env ]; then \
		echo "The .env file does not exist. Copying from .env.dist..."; \
		cp .env.dist .env; \
	fi
endif

# Start Docker containers in interactive mode
# This target builds and starts the Docker containers, allowing interaction with the terminal.
up: check-docker check-env
	docker compose up

# Start Docker containers in background mode (daemon)
# This target builds and starts the Docker containers in the background.
upd: check-docker check-env
	docker compose up -d

# Stop and remove Docker containers
# This target stops and removes all running Docker containers.
down: check-docker check-env
	docker compose down

# Pull the latest images from the registry
# This target pulls the latest Docker images from the registry.
pull: check-docker check-env
	docker compose -f docker-compose.yml pull

# Build or rebuild Docker containers
# This target builds or rebuilds the Docker containers.
build: check-docker check-env
	@if [ -z "$$(grep ^EXELEARNING_WEB_SOURCECODE_PATH .env | cut -d '=' -f2)" ]; then \
		echo "Error: EXELEARNING_WEB_SOURCECODE_PATH is not defined or empty in the .env file"; \
		exit 1; \
	fi
	docker compose build

# Open a shell inside the moodle container
# This target opens an interactive shell session inside the running Moodle container.
shell: check-docker check-env
	docker compose exec moodle sh

# Clean up and stop Docker containers, removing volumes and orphan containers
# This target stops all containers and removes them along with their volumes and any orphan containers.
clean: check-docker
	docker compose down -v --remove-orphans

# Install PHP dependencies using Composer
install-deps:
	COMPOSER_ALLOW_SUPERUSER=1 composer install --no-interaction --prefer-dist --optimize-autoloader --no-progress

# Run code linting using Composer
lint:
	composer lint

# Automatically fix code style issues using Composer
fix:
	composer fix

# Run tests using Composer
test:
	composer test

# Run PHP Mess Detector using Composer
phpmd:
	composer phpmd

# Run Behat tests using Composer
behat:
	composer behat
# -------------------------------------------------------
# Embedded static editor build targets
# -------------------------------------------------------

EDITOR_SUBMODULE_PATH = exelearning
EDITOR_DIST_PATH = dist/static

# Check if bun is installed
check-bun:
	@command -v bun > /dev/null 2>&1 || (echo "Error: bun is not installed. Please install bun: https://bun.sh" && exit 1)

# Initialize submodule if not present
update-submodule:
	@if [ ! -f $(EDITOR_SUBMODULE_PATH)/.gitignore ]; then \
		echo "Initializing submodule..."; \
		git submodule update --init $(EDITOR_SUBMODULE_PATH); \
	fi

# Force update submodule to configured branch
force-update-submodule:
	git submodule update --init --remote $(EDITOR_SUBMODULE_PATH)

# Build static editor to dist/static/
build-editor: check-bun update-submodule
	cd $(EDITOR_SUBMODULE_PATH) && bun install && bun run build:static
	@mkdir -p $(EDITOR_DIST_PATH)
	@rm -rf $(EDITOR_DIST_PATH)/*
	cp -r $(EDITOR_SUBMODULE_PATH)/dist/static/* $(EDITOR_DIST_PATH)/

# Build without submodule update (for CI/CD)
build-editor-no-update: check-bun
	cd $(EDITOR_SUBMODULE_PATH) && bun install && bun run build:static
	@mkdir -p $(EDITOR_DIST_PATH)
	@rm -rf $(EDITOR_DIST_PATH)/*
	cp -r $(EDITOR_SUBMODULE_PATH)/dist/static/* $(EDITOR_DIST_PATH)/

# Remove build artifacts
clean-editor:
	rm -rf $(EDITOR_DIST_PATH)

# -------------------------------------------------------
# Packaging
# -------------------------------------------------------

PLUGIN_NAME = mod_exescorm


# Create a distributable ZIP package
# Usage: make package RELEASE=0.0.2
# VERSION (YYYYMMDDXX) is auto-generated from current date
package:
	@if [ -z "$(RELEASE)" ]; then \
		echo "Error: RELEASE not specified. Use 'make package RELEASE=0.0.2'"; \
		exit 1; \
	fi
	$(eval DATE_VERSION := $(shell date +%Y%m%d)00)
	@echo "Packaging release $(RELEASE) (version $(DATE_VERSION))..."
	$(SED_INPLACE) "s/\(plugin->version[[:space:]]*=[[:space:]]*\)[0-9]*/\1$(DATE_VERSION)/" version.php
	$(SED_INPLACE) "s/\(plugin->release[[:space:]]*=[[:space:]]*'\)[^']*/\1$(RELEASE)/" version.php
	@echo "Creating ZIP archive: $(PLUGIN_NAME)-$(RELEASE).zip..."
	rm -rf /tmp/exescorm-package
	mkdir -p /tmp/exescorm-package/exescorm
	rsync -av --exclude-from=.distignore ./ /tmp/exescorm-package/exescorm/
	cd /tmp/exescorm-package && zip -qr "$(CURDIR)/$(PLUGIN_NAME)-$(RELEASE).zip" exescorm
	rm -rf /tmp/exescorm-package
	@echo "Restoring development values in version.php..."
	$(SED_INPLACE) "s/\(plugin->version[[:space:]]*=[[:space:]]*\)[0-9]*/\19999999999/" version.php
	$(SED_INPLACE) "s/\(plugin->release[[:space:]]*=[[:space:]]*'\)[^']*/\1dev/" version.php
	@echo "Package created: $(PLUGIN_NAME)-$(RELEASE).zip"
# -------------------------------------------------------

# Display help with available commands
# This target lists all available Makefile commands with a brief description.
help:
	@echo "Available commands:"
	@echo "  up                     - Start Docker containers in interactive mode"
	@echo "  upd                    - Start Docker containers in background mode (daemon)"
	@echo "  down                   - Stop and remove Docker containers"
	@echo "  build                  - Build or rebuild Docker containers"
	@echo "  pull                   - Pull the latest images from the registry"
	@echo "  clean                  - Clean up and stop Docker containers, removing volumes and orphan containers"
	@echo "  shell                  - Open a shell inside the exelearning-web container"
	@echo "  install-deps           - Install PHP dependencies using Composer"
	@echo "  lint                   - Run code linting using Composer"
	@echo "  fix                    - Automatically fix code style issues using Composer"
	@echo "  test                   - Run tests using Composer"
	@echo "  phpmd                  - Run PHP Mess Detector using Composer"
	@echo "  behat                  - Run Behat tests using Composer"
	@echo "  package                - Create distributable ZIP (RELEASE=X.Y.Z required)"
	@echo "  build-editor-no-update - Build editor without submodule update (CI/CD)"
	@echo "  clean-editor           - Remove editor build artifacts"
	@echo "  update-submodule       - Initialize editor submodule"
	@echo "  force-update-submodule - Force update editor submodule to latest"
	@echo "  help                   - Display this help with available commands"


# Set help as the default goal if no target is specified
.DEFAULT_GOAL := help
