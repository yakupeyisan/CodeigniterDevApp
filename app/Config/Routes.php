<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

// Swagger routes
$routes->get('swagger.json', 'SwaggerController::index');
$routes->get('swagger', 'SwaggerController::ui');
$routes->get('api-docs', 'SwaggerController::ui');

// API Routes
$routes->group('api', ['namespace' => 'App\Controllers\Api'], function($routes) {
    // Users
    $routes->get('Users', 'UsersController::index');
    $routes->get('Users/(:num)', 'UsersController::show/$1');
    $routes->get('Users/(:num)/full', 'UsersController::showFull/$1');
    $routes->post('Users', 'UsersController::create');
    $routes->put('Users/(:num)', 'UsersController::update/$1');
    $routes->delete('Users/(:num)', 'UsersController::delete/$1');
    $routes->post('Users/(:num)/soft-delete', 'UsersController::softDelete/$1');
    $routes->post('Users/(:num)/restore', 'UsersController::restore/$1');
    $routes->post('Users/(:num)/departments', 'UsersController::addDepartment/$1');
    $routes->post('Users/(:num)/authorizations', 'UsersController::addAuthorization/$1');
    $routes->post('Users/(:num)/operation-claims', 'UsersController::addOperationClaim/$1');

    // Companies
    $routes->get('Companies', 'CompaniesController::index');
    $routes->get('Companies/(:num)', 'CompaniesController::show/$1');
    $routes->post('Companies', 'CompaniesController::create');
    $routes->put('Companies/(:num)', 'CompaniesController::update/$1');
    $routes->delete('Companies/(:num)', 'CompaniesController::delete/$1');

    // Departments
    $routes->get('Departments', 'DepartmentsController::index');
    $routes->get('Departments/(:num)', 'DepartmentsController::show/$1');
    $routes->post('Departments', 'DepartmentsController::create');
    $routes->put('Departments/(:num)', 'DepartmentsController::update/$1');
    $routes->delete('Departments/(:num)', 'DepartmentsController::delete/$1');

    // Operation Claims
    $routes->get('OperationClaims', 'OperationClaimsController::index');
    $routes->get('OperationClaims/(:num)', 'OperationClaimsController::show/$1');
    $routes->post('OperationClaims', 'OperationClaimsController::create');
    $routes->put('OperationClaims/(:num)', 'OperationClaimsController::update/$1');
    $routes->delete('OperationClaims/(:num)', 'OperationClaimsController::delete/$1');

    // Authorizations
    $routes->get('Authorizations', 'AuthorizationsController::index');
    $routes->get('Authorizations/(:num)', 'AuthorizationsController::show/$1');
    $routes->post('Authorizations', 'AuthorizationsController::create');
    $routes->put('Authorizations/(:num)', 'AuthorizationsController::update/$1');
    $routes->delete('Authorizations/(:num)', 'AuthorizationsController::delete/$1');
});
