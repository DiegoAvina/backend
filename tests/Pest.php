<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Encabezados que envía el servidor de Next.js en cada formulario.
 * La llave es la de phpunit.xml (valor ficticio, solo para pruebas).
 *
 * @param  array<string, string>  $extra
 * @return array<string, string>
 */
function serviceHeaders(array $extra = []): array
{
    return [
        'Accept' => 'application/json',
        'X-Service-Key' => 'test-service-key-not-a-real-secret',
        'X-Client-IP' => '203.0.113.10',
        'X-Client-User-Agent' => 'Mozilla/5.0 (Pruebas)',
        ...$extra,
    ];
}

/**
 * Datos comunes válidos de un prospecto.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function leadPayload(array $overrides = []): array
{
    return [
        'name' => 'Ana Pérez',
        'email' => 'ana.perez@example.com',
        'phone' => '55 1234 5678',
        'company' => 'Empresa de Prueba',
        'consent' => true,
        ...$overrides,
    ];
}
