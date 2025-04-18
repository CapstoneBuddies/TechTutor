# TechTutor Advanced Testing Techniques

This guide covers advanced testing techniques for the TechTutor application, including mocking external services and writing integration tests.

## Table of Contents

1. [Using Mocks in Testing](#using-mocks-in-testing)
2. [Integration Testing](#integration-testing)
3. [Data Providers](#data-providers)
4. [Test Doubles](#test-doubles)
5. [Testing Database Operations](#testing-database-operations)
6. [Handling Test Dependencies](#handling-test-dependencies)

## Using Mocks in Testing

Mocks are simulated objects that mimic the behavior of real objects in controlled ways. They're particularly useful for testing components that depend on external services like payment gateways.

### Example: Payment Gateway Mock

The PaymentGateway mock in `test/mocks/PaymentGateway.php` simulates a payment processing service without making actual API calls. This allows you to test payment-related functionality without connecting to a real payment gateway.

#### How to Use the Payment Gateway Mock

```php
// Include the mock
require_once __DIR__ . '/../mocks/PaymentGateway.php';

// Create an instance
$paymentGateway = new PaymentGateway();

// Use it in your tests
$result = $paymentGateway->processPayment(100.00, 'PHP', [
    'card_number' => '4111111111111111',
    'expiry_month' => '12',
    'expiry_year' => '2025',
    'cvv' => '123'
]);

// Verify the results
$this->assertTrue($result['success']);
```

### When to Use Mocks

Use mocks when testing code that interacts with:

1. **External APIs** - Payment gateways, email services, SMS providers
2. **Resource-intensive services** - Database operations, file system operations
3. **Non-deterministic services** - Services with varying response times or results

## Integration Testing

Integration tests verify that different components of your application work correctly together. They test the interactions between components rather than isolated functionality.

### Writing Effective Integration Tests

1. **Focus on component interactions** - Test how your components work together
2. **Use realistic test data** - Create scenarios that reflect real use cases
3. **Clean up after tests** - Remove any test data created during the test

### Example: Payment Processing Integration Test

See `test/integration/PaymentProcessingTest.php` for an example of an integration test that tests the complete payment workflow, including:

1. Creating a user in the database
2. Processing a payment through the payment gateway
3. Recording the transaction in the database
4. Refunding the payment
5. Updating the transaction status

## Data Providers

Data providers allow you to run the same test with different inputs. This is useful for testing boundary cases and multiple scenarios without duplicating test code.

### Example: Testing Card Validation with Different Cards

```php
/**
 * Test card validation with different card numbers
 * @dataProvider cardNumberProvider
 */
public function testCardValidation($cardNumber, $expectedResult)
{
    $cardDetails = [
        'card_number' => $cardNumber,
        'expiry_month' => '12',
        'expiry_year' => date('Y') + 1,
        'cvv' => '123'
    ];
    
    $result = $this->paymentGateway->processPayment(100.00, 'PHP', $cardDetails);
    $this->assertEquals($expectedResult, $result['success']);
}

public function cardNumberProvider()
{
    return [
        'Valid Visa' => ['4111111111111111', true],
        'Valid Mastercard' => ['5555555555554444', true],
        'Invalid - too short' => ['41111', false],
        'Invalid - non-numeric' => ['41111111111ABCDE', false],
        'Invalid prefix' => ['1111111111111111', false]
    ];
}
```

## Test Doubles

PHPUnit provides several types of test doubles:

1. **Dummy** - Objects passed around but never used
2. **Stub** - Objects that provide predefined responses to calls
3. **Mock** - Objects that verify expected method calls
4. **Spy** - Objects that record method calls for later verification
5. **Fake** - Objects with simplified working implementations

### Creating a Mock with PHPUnit

```php
// Create a mock of the PaymentGateway class
$paymentGateway = $this->createMock(PaymentGateway::class);

// Configure the mock to return a specific result for processPayment
$paymentGateway->method('processPayment')
    ->willReturn([
        'success' => true,
        'transaction_id' => 'test_123',
        'amount' => 100.00
    ]);

// Use the mock in your test
$result = $paymentGateway->processPayment(100.00, 'PHP', []);
$this->assertTrue($result['success']);
```

## Testing Database Operations

When testing database operations, you should:

1. Use a separate test database
2. Reset the database to a known state before each test
3. Clean up any test data after tests

### Example: Database Transaction Test

```php
protected function setUp(): void
{
    $this->db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    
    // Reset the test database to a known state
    $this->db->query("TRUNCATE TABLE test_transactions");
    
    // Add some standard test data
    $this->db->query("
        INSERT INTO test_transactions (user_id, amount, status) 
        VALUES (1, 100.00, 'completed'), (2, 200.00, 'pending')
    ");
}

public function testGetTransactionsByUser()
{
    $transactions = getTransactionsByUser(1);
    $this->assertCount(1, $transactions);
    $this->assertEquals(100.00, $transactions[0]['amount']);
}
```

## Handling Test Dependencies

Some tests depend on the results of previous tests. PHPUnit allows you to specify these dependencies.

### Example: Testing a Sequence of Operations

```php
/**
 * @return string Transaction ID for use in subsequent tests
 */
public function testCreateTransaction()
{
    $transaction = createTransaction(1, 100.00);
    $this->assertNotNull($transaction);
    $this->assertArrayHasKey('id', $transaction);
    
    return $transaction['id'];
}

/**
 * @depends testCreateTransaction
 */
public function testUpdateTransactionStatus($transactionId)
{
    $success = updateTransactionStatus($transactionId, 'refunded');
    $this->assertTrue($success);
    
    $transaction = getTransaction($transactionId);
    $this->assertEquals('refunded', $transaction['status']);
}
```

## Advanced Assertion Techniques

PHPUnit provides many assertion methods beyond the basic ones:

### File and Directory Assertions

```php
$this->assertFileExists('/path/to/file');
$this->assertDirectoryExists('/path/to/directory');
```

### String Assertions

```php
$this->assertStringContainsString('needle', 'haystack');
$this->assertStringStartsWith('prefix', 'string');
$this->assertStringEndsWith('suffix', 'string');
```

### Array Assertions

```php
$this->assertArrayHasKey('key', $array);
$this->assertCount(3, $array);
$this->assertContains('value', $array);
```

### Exception Assertions

```php
$this->expectException(InvalidArgumentException::class);
$this->expectExceptionMessage('Invalid amount');
someMethodThatThrowsException();
```

---

For more detailed information about PHPUnit and testing techniques, refer to the [PHPUnit Documentation](https://phpunit.readthedocs.io/).