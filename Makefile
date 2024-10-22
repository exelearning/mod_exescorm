# Makefile to facilitate the use of Docker in the exelearning-web project

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
up: check-docker
	docker compose up --build

# Start Docker containers in background mode (daemon)
# This target builds and starts the Docker containers in the background.
upd: check-docker
	docker compose up -d    

# Stop and remove Docker containers
# This target stops and removes all running Docker containers.
down: check-docker
	docker compose down

# Pull the latest images from the registry
# This target pulls the latest Docker images from the registry.
pull: check-docker
	docker compose -f docker-compose.yml pull

# Build or rebuild Docker containers
# This target builds or rebuilds the Docker containers.
build: check-docker
	docker compose build

# Open a shell inside the moodle container
# This target opens an interactive shell session inside the running Moodle container.
shell: check-docker
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
# Display help with available commands
# This target lists all available Makefile commands with a brief description.
help:
	@echo "Available commands:"
	@echo "  up                - Start Docker containers in interactive mode"
	@echo "  upd               - Start Docker containers in background mode (daemon)"
	@echo "  down              - Stop and remove Docker containers"
	@echo "  build             - Build or rebuild Docker containers"
	@echo "  pull              - Pull the latest images from the registry"
	@echo "  clean             - Clean up and stop Docker containers, removing volumes and orphan containers"
	@echo "  shell             - Open a shell inside the exelearning-web container"
	@echo "  install-deps      - Install PHP dependencies using Composer"
	@echo "  lint              - Run code linting using Composer"
	@echo "  fix               - Automatically fix code style issues using Composer"
	@echo "  test              - Run tests using Composer"
	@echo "  phpmd             - Run PHP Mess Detector using Composer"
	@echo "  behat             - Run Behat tests using Composer"
	@echo "  help              - Display this help with available commands"


# Set help as the default goal if no target is specified
.DEFAULT_GOAL := help
