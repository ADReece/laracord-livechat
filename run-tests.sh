#!/bin/bash

# Laravel Laracord Live Chat - Test Runner Script
# This script runs the comprehensive test suite for the package

set -e

echo "🧪 Running Laracord Live Chat Test Suite"
echo "========================================"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}✓${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

# Check if vendor directory exists
if [ ! -d "vendor" ]; then
    print_error "Vendor directory not found. Please run 'composer install' first."
    exit 1
fi

# Check if PHPUnit is available
if [ ! -f "vendor/bin/phpunit" ]; then
    print_error "PHPUnit not found. Please run 'composer install' first."
    exit 1
fi

print_status "Starting test suite..."

# Run unit tests
echo ""
echo "🔬 Running Unit Tests"
echo "--------------------"
if ./vendor/bin/phpunit --testsuite=Unit --colors=always; then
    print_status "Unit tests passed"
else
    print_error "Unit tests failed"
    exit 1
fi

# Run feature tests
echo ""
echo "🔧 Running Feature Tests"
echo "------------------------"
if ./vendor/bin/phpunit --testsuite=Feature --colors=always; then
    print_status "Feature tests passed"
else
    print_error "Feature tests failed"
    exit 1
fi

# Run all tests with coverage (if requested)
if [ "$1" = "--coverage" ]; then
    echo ""
    echo "📊 Generating Test Coverage Report"
    echo "----------------------------------"
    if ./vendor/bin/phpunit --coverage-html coverage --colors=always; then
        print_status "Coverage report generated in ./coverage directory"
    else
        print_warning "Coverage report generation failed"
    fi
fi

# Run static analysis (if Psalm/PHPStan is available)
if [ -f "vendor/bin/psalm" ]; then
    echo ""
    echo "🔍 Running Static Analysis (Psalm)"
    echo "----------------------------------"
    if ./vendor/bin/psalm; then
        print_status "Static analysis passed"
    else
        print_warning "Static analysis found issues"
    fi
elif [ -f "vendor/bin/phpstan" ]; then
    echo ""
    echo "🔍 Running Static Analysis (PHPStan)"
    echo "------------------------------------"
    if ./vendor/bin/phpstan analyse; then
        print_status "Static analysis passed"
    else
        print_warning "Static analysis found issues"
    fi
fi

# Test summary
echo ""
echo "📋 Test Summary"
echo "==============="
print_status "All tests completed successfully!"

# Count test files
UNIT_TESTS=$(find tests/Unit -name "*Test.php" | wc -l)
FEATURE_TESTS=$(find tests/Feature -name "*Test.php" | wc -l)
TOTAL_TESTS=$((UNIT_TESTS + FEATURE_TESTS))

echo "📈 Test Statistics:"
echo "  - Unit tests: $UNIT_TESTS files"
echo "  - Feature tests: $FEATURE_TESTS files"
echo "  - Total test files: $TOTAL_TESTS"

# Test coverage areas
echo ""
echo "🎯 Test Coverage Areas:"
echo "  ✓ Models (ChatSession, ChatMessage)"
echo "  ✓ Services (ChatService, DiscordService, DiscordMessageMonitor)"
echo "  ✓ HTTP Controllers (ChatController, DiscordController)"
echo "  ✓ Jobs (MonitorDiscordMessages, CleanupChatSessions)"
echo "  ✓ Commands (Install, Discord Bot, Monitor, Schedule Status)"
echo "  ✓ Events (MessageSent, SessionStarted, SessionClosed)"
echo "  ✓ HTTP Requests (StartSessionRequest, SendMessageRequest)"
echo "  ✓ Service Provider Registration"
echo "  ✓ End-to-End Integration Tests"

echo ""
print_status "Test suite completed! 🎉"

# Display usage help
if [ "$1" = "--help" ] || [ "$1" = "-h" ]; then
    echo ""
    echo "Usage: ./run-tests.sh [options]"
    echo ""
    echo "Options:"
    echo "  --coverage    Generate HTML coverage report"
    echo "  --help, -h    Show this help message"
    echo ""
    echo "Examples:"
    echo "  ./run-tests.sh              # Run all tests"
    echo "  ./run-tests.sh --coverage   # Run tests with coverage"
fi
