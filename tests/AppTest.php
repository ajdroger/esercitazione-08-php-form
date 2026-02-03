<?php
declare(strict_types=1);

namespace Test;

use App\App;
use App\Http\Request;
use PHPUnit\Framework\TestCase;

final class AppTest extends TestCase
{
    private App $app;

    protected function setUp(): void
    {
        $this->app = new App();
    }

    /**
     * A.1 - GET / deve invocare showForm
     * Status atteso: 200, contiene <form
     */
    public function testGetRootReturnsFormPage(): void
    {
        $request = new Request([], [], [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/'
        ]);

        $response = $this->app->handle($request);

        $this->assertSame(200, $response->status());
        $this->assertStringContainsString('<form', $response->body());
        $this->assertStringContainsString('action="/submit"', $response->body());
    }

    /**
     * A.2 - POST /submit deve invocare handleSubmit
     * Verifica indiretta: con dati validi, status 200 e "Grazie"
     */
    public function testPostSubmitWithValidDataReturnsSuccess(): void
    {
        $request = new Request([], [
            'name' => 'Mario Rossi',
            'email' => 'mario@example.com',
            'message' => 'Questo è un messaggio di test'
        ], [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/submit'
        ]);

        $response = $this->app->handle($request);

        $this->assertSame(200, $response->status());
        $this->assertStringContainsString('Grazie', $response->body());
    }

    /**
     * A.2 - POST /submit con dati invalidi deve restituire 422
     */
    public function testPostSubmitWithInvalidDataReturns422(): void
    {
        $request = new Request([], [
            'name' => 'A',
            'email' => 'invalid',
            'message' => 'Ciao'
        ], [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/submit'
        ]);

        $response = $this->app->handle($request);

        $this->assertSame(422, $response->status());
    }

    /**
     * A.3 - Qualsiasi altra rotta deve restituire 404
     */
    public function testUnknownRouteReturns404(): void
    {
        $request = new Request([], [], [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/nope'
        ]);

        $response = $this->app->handle($request);

        $this->assertSame(404, $response->status());
        $this->assertStringContainsString('404 Not Found', $response->body());
    }

    /**
     * A - Casi limite: REQUEST_URI con querystring
     * /??x=1 deve comunque risolvere path /
     */
    public function testRootWithQueryStringStillReturnsForm(): void
    {
        $request = new Request([], [], [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/?x=1'
        ]);

        $response = $this->app->handle($request);

        $this->assertSame(200, $response->status());
        $this->assertStringContainsString('<form', $response->body());
    }

    /**
     * A - Casi limite: REQUEST_URI mancante → default /
     */
    public function testMissingRequestUriDefaultsToRoot(): void
    {
        $request = new Request([], [], [
            'REQUEST_METHOD' => 'GET'
        ]);

        $response = $this->app->handle($request);

        $this->assertSame(200, $response->status());
        $this->assertStringContainsString('<form', $response->body());
    }

    /**
     * A - Test POST su rotta sconosciuta
     */
    public function testPostToUnknownRouteReturns404(): void
    {
        $request = new Request([], ['test' => 'data'], [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/unknown'
        ]);

        $response = $this->app->handle($request);

        $this->assertSame(404, $response->status());
    }
}
