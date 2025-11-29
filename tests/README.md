# Test Suite Documentation

This directory contains comprehensive tests for the Headless WordPress e-commerce system with Location-Based Products plugin.

## Overview

The test suite includes:
- **Unit Tests**: Test individual functions and methods in isolation
- **Integration Tests**: Test component interactions with database
- **Mock Tests**: Test external API integrations without real HTTP calls

## Test Structure

```
tests/
├── Unit/                                      # Unit tests (no database dependencies)
│   ├── LBP_HelpersDistanceTest.php           # Distance calculation tests (11 tests)
│   ├── LBP_HelpersLocationDetectionTest.php  # Location detection logic tests (10 tests)
│   ├── LBP_HelpersProductAvailabilityTest.php # Product availability tests (18 tests)
│   ├── LBP_REST_API_ValidatorsTest.php       # REST API validation tests (15 tests)
│   └── LBP_HelpersExternalAPITest.php        # External API mocking tests (15 tests)
├── Integration/                               # Integration tests (database required)
│   └── LocationBasedProductsIntegrationTest.php # Full system integration tests
├── Fixtures/                                  # Test data and fixtures
├── bootstrap.php                              # Test environment setup
└── README.md                                  # This file
```

## Installation

### 1. Install Dependencies

```bash
cd /home/user/hedless-wordpress
composer install
```

This will install:
- PHPUnit 9.5
- Yoast PHPUnit Polyfills
- Mockery for mocking

### 2. Verify Installation

```bash
composer test --help
```

## Running Tests

### Run All Tests

```bash
composer test
```

### Run Only Unit Tests

```bash
composer test:unit
```

### Run Only Integration Tests

```bash
composer test:integration
```

### Run Specific Test File

```bash
vendor/bin/phpunit tests/Unit/LBP_HelpersDistanceTest.php
```

### Run Specific Test Method

```bash
vendor/bin/phpunit --filter test_calculate_distance_delhi_to_noida tests/Unit/LBP_HelpersDistanceTest.php
```

### Run Tests with Coverage Report

```bash
composer test:coverage
```

This generates an HTML coverage report in `coverage/` directory.

## Test Categories

### Unit Tests (69 tests)

#### Distance Calculation Tests (11 tests)
- Basic distance calculations
- Same coordinates (0 distance)
- Equator crossing
- Prime meridian crossing
- International date line crossing
- Antipodal points (maximum distance)
- Polar regions
- Symmetry verification
- Very small distances
- Return type validation

#### Location Detection Tests (10 tests)
- Postal code normalization
- Postal code edge cases (SQL injection, empty, long)
- Coordinate validation (latitude/longitude bounds)
- City name matching (case-insensitive, partial)
- IP address validation (IPv4, IPv6, invalid)
- Delivery radius logic (within, outside, exact boundary)
- Zero and negative delivery radius
- Null location handling

#### Product Availability Tests (18 tests)
- Availability type 'all'
- Availability type 'specific' (matching and non-matching)
- Availability type 'exclude' (excluded and non-excluded)
- Empty selected locations handling
- Stock availability (in stock, out of stock, insufficient, exact match)
- Null stock quantity (unlimited)
- Delivery options (standard, express, pickup)
- Location-specific pricing (sale price, regular price, disabled, not in list)

#### REST API Validator Tests (15 tests)
- IP address validation (IPv4, IPv6, invalid formats)
- Client IP extraction from various headers
- Coordinate parameter validation
- Pagination parameter validation
- Quantity parameter validation
- Postal code parameter validation
- Search parameter validation
- SQL injection protection

#### External API Tests (15 tests)
- API response parsing (success, failure, missing fields)
- Localhost IP handling
- API URL construction
- Network error handling
- Timeout error handling
- Rate limit response handling
- Private vs public IP detection
- Geolocation caching logic
- Location detection priority
- JSON parsing errors
- Special characters in responses

### Integration Tests

Integration tests require WordPress environment and demonstrate:
- Creating and retrieving locations
- Finding locations by coordinates and postal codes
- Product availability with database
- Location-specific stock and pricing
- REST API endpoints
- Service areas
- Location priority
- Database isolation

## Code Coverage

Target coverage goals:
- **Overall**: 80%+
- **Core helpers**: 90%+
- **REST API**: 85%+
- **Product integration**: 80%+

View coverage report:
```bash
composer test:coverage
open coverage/index.html
```

## Writing New Tests

### Unit Test Example

```php
<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class MyNewTest extends TestCase {
    public function test_my_function() {
        $result = my_function('input');
        $this->assertEquals('expected', $result);
    }
}
```

### Integration Test Example

```php
<?php
namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

class MyIntegrationTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        // Create test data
    }

    protected function tearDown(): void {
        // Cleanup is automatic with transactions
        parent::tearDown();
    }

    public function test_database_operation() {
        // Test with real database
    }
}
```

## Best Practices

1. **Test Isolation**: Each test should be independent
2. **Descriptive Names**: Use `test_<method>_<scenario>_<expected_result>`
3. **One Assertion Focus**: Test one thing per test method
4. **Mock External Calls**: Don't make real HTTP requests in tests
5. **Clean Up**: Use setUp/tearDown for test data
6. **Edge Cases**: Always test boundary conditions
7. **Error Cases**: Test what happens when things go wrong

## Continuous Integration

### GitHub Actions Example

Create `.github/workflows/tests.yml`:

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v2

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '7.4'

      - name: Install dependencies
        run: composer install

      - name: Run tests
        run: composer test
```

## Test Results

After running tests, you'll see output like:

```
PHPUnit 9.5.x by Sebastian Bergmann

Unit Tests
...........                                                       11 / 11 (100%)

Time: 00:00.123, Memory: 10.00 MB

OK (69 tests, 150 assertions)
```

## Troubleshooting

### Error: "Class LBP_Helpers not found"

Solution: Check that `bootstrap.php` is loading the correct files.

### Error: "Call to undefined function wp_insert_post"

Solution: WordPress functions are not available in unit tests. Use integration tests or mock the functions.

### Tests are slow

Solution:
- Run only unit tests: `composer test:unit`
- Check for real HTTP calls that should be mocked
- Optimize database queries in integration tests

## Resources

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [WordPress Plugin Testing](https://make.wordpress.org/cli/handbook/misc/plugin-unit-tests/)
- [Mockery Documentation](http://docs.mockery.io/)

## Contributing

When adding new features:
1. Write tests first (TDD)
2. Ensure all tests pass
3. Maintain or improve code coverage
4. Document new test scenarios in this README
