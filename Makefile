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
EDITOR_REPO_DEFAULT = https://github.com/exelearning/exelearning.git
EDITOR_REF_DEFAULT = main

# Check if bun is installed
check-bun:
	@command -v bun > /dev/null 2>&1 || (echo "Error: bun is not installed. Please install bun: https://bun.sh" && exit 1)

# Fetch editor source code from remote repository (branch/tag, shallow clone)
fetch-editor-source:
	@set -e; \
	get_env() { \
		if [ -f .env ]; then \
			grep -E "^$$1=" .env | tail -n1 | cut -d '=' -f2-; \
		fi; \
	}; \
	REPO_URL="$${EXELEARNING_EDITOR_REPO_URL:-$$(get_env EXELEARNING_EDITOR_REPO_URL)}"; \
	REF="$${EXELEARNING_EDITOR_REF:-$$(get_env EXELEARNING_EDITOR_REF)}"; \
	REF_TYPE="$${EXELEARNING_EDITOR_REF_TYPE:-$$(get_env EXELEARNING_EDITOR_REF_TYPE)}"; \
	if [ -z "$$REPO_URL" ]; then REPO_URL="$(EDITOR_REPO_DEFAULT)"; fi; \
	if [ -z "$$REF" ]; then REF="$${EXELEARNING_EDITOR_DEFAULT_BRANCH:-$$(get_env EXELEARNING_EDITOR_DEFAULT_BRANCH)}"; fi; \
	if [ -z "$$REF" ]; then REF="$(EDITOR_REF_DEFAULT)"; fi; \
	if [ -z "$$REF_TYPE" ]; then REF_TYPE="auto"; fi; \
	echo "Fetching editor source from $$REPO_URL (ref=$$REF, type=$$REF_TYPE)"; \
	rm -rf $(EDITOR_SUBMODULE_PATH); \
	git init -q $(EDITOR_SUBMODULE_PATH); \
	git -C $(EDITOR_SUBMODULE_PATH) remote add origin "$$REPO_URL"; \
	case "$$REF_TYPE" in \
		tag) \
			git -C $(EDITOR_SUBMODULE_PATH) fetch --depth 1 origin "refs/tags/$$REF:refs/tags/$$REF"; \
			git -C $(EDITOR_SUBMODULE_PATH) checkout -q "tags/$$REF"; \
			;; \
		branch) \
			git -C $(EDITOR_SUBMODULE_PATH) fetch --depth 1 origin "$$REF"; \
			git -C $(EDITOR_SUBMODULE_PATH) checkout -q FETCH_HEAD; \
			;; \
		auto) \
			if git -C $(EDITOR_SUBMODULE_PATH) fetch --depth 1 origin "refs/tags/$$REF:refs/tags/$$REF" > /dev/null 2>&1; then \
				echo "Resolved $$REF as tag"; \
				git -C $(EDITOR_SUBMODULE_PATH) checkout -q "tags/$$REF"; \
			else \
				echo "Resolved $$REF as branch"; \
				git -C $(EDITOR_SUBMODULE_PATH) fetch --depth 1 origin "$$REF"; \
				git -C $(EDITOR_SUBMODULE_PATH) checkout -q FETCH_HEAD; \
			fi; \
			;; \
		*) \
			echo "Error: EXELEARNING_EDITOR_REF_TYPE must be one of: auto, branch, tag"; \
			exit 1; \
			;; \
	esac

# Build static editor to dist/static/
build-editor: check-bun fetch-editor-source
	cd $(EDITOR_SUBMODULE_PATH) && bun install && bun run build:static
	@mkdir -p $(EDITOR_DIST_PATH)
	@rm -rf $(EDITOR_DIST_PATH)/*
	cp -r $(EDITOR_SUBMODULE_PATH)/dist/static/* $(EDITOR_DIST_PATH)/

# Backward-compatible alias
build-editor-no-update: build-editor

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
	@if command -v rsync > /dev/null 2>&1 && command -v zip > /dev/null 2>&1; then \
		rm -rf /tmp/exescorm-package; \
		mkdir -p /tmp/exescorm-package/exescorm; \
		rsync -av --exclude-from=.distignore ./ /tmp/exescorm-package/exescorm/; \
		cd /tmp/exescorm-package && zip -qr "$(CURDIR)/$(PLUGIN_NAME)-$(RELEASE).zip" exescorm; \
		rm -rf /tmp/exescorm-package; \
	else \
		PYTHON=; \
		for cmd in python3 py python; do \
			if $$cmd --version > /dev/null 2>&1; then PYTHON=$$cmd; break; fi; \
		done; \
		if [ -z "$$PYTHON" ]; then \
			echo "Error: Python 3 not found. Please install Python 3."; \
			exit 1; \
		fi; \
		$$PYTHON scripts/package.py $(RELEASE) $(PLUGIN_NAME); \
	fi
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
	@echo "  build-editor           - Build embedded static editor"
	@echo "  build-editor-no-update - Alias of build-editor"
	@echo "  clean-editor           - Remove editor build artifacts"
	@echo "  fetch-editor-source    - Download editor source from configured repo/ref"
	@echo "  package                - Create distributable ZIP (RELEASE=X.Y.Z required)"
	@echo "  help                   - Display this help with available commands"


# Set help as the default goal if no target is specified
.DEFAULT_GOAL := help
