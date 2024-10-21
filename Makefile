# Makefile to facilitate the use of Docker in the exelearning-web project

# Get the host IP (works for Unix/macOS, adjust for Windows if needed)
# HOST_IP = $(shell hostname -I | awk '{print $$1}')
# Get the host IP using ifconfig (for macOS/Linux)
HOST_IP = $(shell ifconfig | grep 'inet ' | grep -v '127.0.0.1' | awk '{print $$2}'

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

# Get the host IP based on the operating system
ifeq ($(SYSTEM_OS),windows)
	# For Windows, use ipconfig and findstr to extract the IP
	HOST_IP := $(shell for /F "tokens=2 delims=[]" %i in ('ping -n 1 -4 %COMPUTERNAME%') do @echo %i)
else
	# For Unix-like systems, use ifconfig or ip (adjust for macOS/Linux)
	ifeq ($(shell uname), Darwin)
		# For macOS, use ipconfig
		HOST_IP := $(shell ipconfig getifaddr en0)
	else
		# For Linux, use ifconfig or ip to get the IP address
		HOST_IP := $(shell hostname -I | cut -d' ' -f1)
	endif
endif

# Check if Docker is running
# This target verifies if Docker is installed and running on the system.
check-docker:
ifeq ($(SYSTEM_OS),windows)
	@echo "Detected system: Windows (cmd, powershell)"
	@docker version > NUL 2>&1 || (echo. & echo Error: Docker is not running. Please make sure Docker is installed and running. & echo. & exit 1)
else
	@echo "Detected system: Unix (Linux/macOS/Cygwin/MinGW)"    
	@docker version > /dev/null 2>&1 || (echo "" && echo "Error: Docker is not running. Please make sure Docker is installed and running." && echo "" && exit 1)
endif

# Check if the .env file exists, if not, copy from .env.dist
# This target ensures that the .env file is present by copying it from .env.dist if it doesn't exist.
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

# Show the host ip address
ip:
	@echo "The host ip address is: ${HOST_IP}"

# Start Docker containers in interactive mode
# This target builds and starts the Docker containers, allowing interaction with the terminal.
up: check-docker
	HOST_IP=$(HOST_IP) docker compose up --build

# Start Docker containers in background mode (daemon)
# This target builds and starts the Docker containers in the background.
upd: check-docker
	HOST_IP=$(HOST_IP) docker compose up -d    

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
