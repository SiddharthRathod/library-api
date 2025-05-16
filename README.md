# Library Management API

## Overview

The Library Management API is a robust RESTful service built with Laravel that provides comprehensive functionality for managing a library system. It handles user authentication, book management, and book borrowing operations with features like caching for performance optimization and event-driven notifications.

### Key Features

- User authentication with role-based access control
- Book management (CRUD operations)
- Book borrowing and returning system
- Caching implementation for improved performance
- Event-driven notifications for book operations
- Comprehensive API documentation using Swagger/OpenAPI
- Automated testing suite

## Installation

### Prerequisites

- PHP >= 8.1
- Composer
- MySQL/MariaDB
- Laravel CLI

### Setup Steps

1. Clone the repository:
   ```bash
   git clone <repository-url>
   cd library-api
   ```

2. Install dependencies:
   ```bash
   composer install
   ```

3. Configure environment:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. Configure database in `.env`:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=library_api
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

5. Run migrations and seeders:
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

6. Start the development server:
   ```bash
   php artisan serve
   ```

## API Documentation

The API documentation is available through Swagger UI. After starting the server, visit:
```
http://localhost:8000/api/documentation
```

## Testing

### Running Tests

Running tests is essential to ensure that our project works correctly. Here’s how you can do it step by step:

1. **Configure the Testing Environment**: First, you need to set up the testing environment by copying the environment file. Open your terminal and run the following command:
   
   ```bash
   cp .env .env.testing
   ```
   This command creates a new configuration file specifically for testing.

2. **Run the Test Suite**: After configuring the environment, you can run the tests by using the following command:
   
   ```bash
   php artisan test
   ```
   This command will execute all the tests in the project.

   If you want to run specific tests, you can use the following commands:
   - To run tests from a specific class:
     ```bash
     php artisan test --filter ClassName
     ```
   - To run a specific method in a test class:
     ```bash
     php artisan test --filter methodName
     ```
   - To run multiple methods that match a pattern:
     ```bash
     php artisan test --filter '/(methodName1|methodName2)/'
     ```

3. **Check the Results**: Once the tests are completed, the terminal will display the results. Look for messages that indicate if the tests passed or if there were any errors. If everything is working correctly, you should see "All tests passed!". If there are issues, the terminal will provide details about what went wrong.

4. **Seek Help if Needed**: If you encounter any errors or have questions about the results, feel free to ask for assistance from a team member or refer to the project documentation for more information.

By following these steps, you can easily run the tests and verify that our project is functioning as intended.

### Available Test Suites

- Feature Tests: Test API endpoints and integration scenarios
- Unit Tests: Test individual components and business logic

## Architecture and Design

### Project Structure

- `app/Http/Controllers/API`: API controllers with Swagger documentation
- `app/Models`: Eloquent models with relationships
- `app/Events`: Event classes for book operations
- `app/Listeners`: Event listeners for notifications
- `tests`: Feature and Unit tests

### Key Design Decisions

1. **Authentication**:
   - Using Laravel Sanctum for API authentication
   - Role-based access control with Spatie Permissions

2. **Performance Optimization**:
   - Implemented caching for book listings
   - Pagination for large datasets

3. **Event-Driven Architecture**:
   - Events for book borrowing and returning
   - Separate listeners for notification handling

4. **API Design**:
   - RESTful endpoints with consistent response structure
   - Comprehensive input validation
   - Detailed error responses

5. **Documentation**:
   - OpenAPI/Swagger documentation
   - Inline code documentation
   - Comprehensive README

## License

This project is licensed under the MIT License. See the LICENSE file for details.
