#!/bin/bash

# Docker Test Runner for Laracord Live Chat
# This script provides easy commands to run tests in Docker

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

print_usage() {
    echo "Docker Test Runner for Laracord Live Chat"
    echo "========================================"
    echo ""
    echo "Usage: $0 [command]"
    echo ""
    echo "Commands:"
    echo "  test           Run all tests"
    echo "  test-coverage  Run tests with coverage report"
    echo "  shell          Open interactive shell in test container"
    echo "  build          Build the test container"
    echo "  clean          Remove containers and images"
    echo "  unit           Run only unit tests"
    echo "  feature        Run only feature tests"
    echo "  help           Show this help message"
    echo ""
    echo "Examples:"
    echo "  $0 test                    # Run all tests"
    echo "  $0 test-coverage          # Generate coverage report"
    echo "  $0 shell                  # Interactive debugging"
}

print_status() {
    echo -e "${GREEN}✓${NC} $1"
}

print_info() {
    echo -e "${BLUE}ℹ${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    print_error "Docker is not running. Please start Docker and try again."
    exit 1
fi

# Check if docker-compose is available
if ! command -v docker-compose &> /dev/null; then
    print_error "docker-compose is not installed. Please install it and try again."
    exit 1
fi

case "${1:-test}" in
    "test")
        print_info "Running tests in Docker container..."
        docker-compose run --rm tests
        ;;
        
    "test-coverage")
        print_info "Running tests with coverage in Docker container..."
        docker-compose run --rm tests-coverage
        print_info "Coverage report will be available in ./coverage directory"
        ;;
        
    "shell")
        print_info "Opening interactive shell in test container..."
        docker-compose run --rm tests-shell
        ;;
        
    "build")
        print_info "Building test container..."
        docker-compose build tests
        print_status "Container built successfully"
        ;;
        
    "clean")
        print_info "Cleaning up Docker containers and images..."
        docker-compose down --rmi all --volumes --remove-orphans
        print_status "Cleanup completed"
        ;;
        
    "unit")
        print_info "Running unit tests only..."
        docker-compose run --rm tests ./vendor/bin/phpunit --testsuite=Unit --colors=always
        ;;
        
    "feature")
        print_info "Running feature tests only..."
        docker-compose run --rm tests ./vendor/bin/phpunit --testsuite=Feature --colors=always
        ;;
        
    "help"|"-h"|"--help")
        print_usage
        ;;
        
    *)
        print_error "Unknown command: $1"
        echo ""
        print_usage
        exit 1
        ;;
esac
