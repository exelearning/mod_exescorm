# Makefile to facilitate the use of Docker in the exelearning-web project

# Detect the operating system
ifeq ($(OS),Windows_NT)
	# We are on Windows
	ifdef MSYSTEM
		# MSYSTEM is defined, we are in MinGW or MSYS
		SYSTEM_OS := unix
	else ifdef CYGWIN
		# CYGWIN is defined, we are in Cygwin
		SYSTEM_OS := unix
	else
		# Not in MinGW or Cygwin
		SYSTEM_OS := windows

	endif
else
	# Not Windows, assuming Unix
	SYSTEM_OS := unix
endif

# Check if Docker is running
check-docker:
ifeq ($(SYSTEM_OS),windows)
	@echo "Detected system: Windows (cmd, powershell)"
	@docker version > NUL 2>&1 || (echo. & echo Error: Docker is not running. Please make sure Docker is installed and running. & echo. & exit 1)
else
	@echo "Detected system: Unix (Linux/macOS/Cygwin/MinGW)"    
	@docker version > /dev/null 2>&1 || (echo "" && echo "Error: Docker is not running. Please make sure Docker is installed and running." && echo "" && exit 1)
endif

# Check if the .env file exists, if not, copy from .env.dist
check-env:
ifeq ($(SYSTEM_OS),windows)
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
up: check-docker
	docker compose up --build

# Start Docker containers in background mode (daemon)
upd: check-docker
	docker compose up -d    

# Stop and remove Docker containers
down: check-docker
	docker compose down

# Pull the latest images from the registry
pull: check-docker
	docker compose -f docker-compose.yml pull

# Build or rebuild Docker containers
build: check-docker
	docker compose build

# Open a shell inside the moodle container
shell: check-docker
	docker compose exec moodle sh

# Clean up and stop Docker containers, removing volumes and orphan containers
clean: check-docker
	docker compose down -v --remove-orphans

install-deps:
	COMPOSER_ALLOW_SUPERUSER=1 composer install --no-interaction --prefer-dist --optimize-autoloader --no-progress

lint:
	composer lint

fix:
	composer fix

test:
	composer test

phpmd:
	composer phpmd

behat:
	composer behat
# Display help with available commands
help:
	@echo "Available commands:"
	@echo "  up                - Start Docker containers in interactive mode"
	@echo "  upd               - Start Docker containers in background mode (daemon)"
	@echo "  down              - Stop and remove Docker containers"
	@echo "  build             - Build or rebuild Docker containers"
	@echo "  pull              - Pull the latest images from the registry"
	@echo "  clean             - Clean up and stop Docker containers, removing volumes and orphan containers"
	@echo "  shell             - Open a shell inside the exelearning-web container"
	@echo "  help              - Display this help with available commands"


# Set help as the default goal if no target is specified
.DEFAULT_GOAL := help
