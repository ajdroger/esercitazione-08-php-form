<?php
declare(strict_types=1);

namespace Test;

use App\Http\Request;
use PHPUnit\Framework\TestCase;

final class RequestTest extends TestCase
{
    /**
     * B.1 - method() con REQUEST_METHOD=GET → "GET"
     */
    public function testMethodReturnsGet(): void
    {
        $request = new Request([], [], ['REQUEST_METHOD' => 'GET']);
        $this->assertSame('GET', $request->method());
    }

    /**
     * B.1 - method() con REQUEST_METHOD=post → "POST" (uppercase)
     */
    public function testMethodReturnsPostUppercase(): void
    {
        $request = new Request([], [], ['REQUEST_METHOD' => 'post']);
        $this->assertSame('POST', $request->method());
    }

    /**
     * B.1 - method() se mancante → default "GET"
     */
    public function testMethodDefaultsToGet(): void
    {
        $request = new Request([], [], []);
        $this->assertSame('GET', $request->method());
    }

    /**
     * B.2 - path() deve estrarre solo il path da URI completi
     * /submit?x=1 → /submit
     */
    public function testPathExtractsPathFromQueryString(): void
    {
        $request = new Request([], [], ['REQUEST_URI' => '/submit?x=1']);
        $this->assertSame('/submit', $request->path());
    }

    /**
     * B.2 - path() con URI semplice
     */
    public function testPathReturnsSimplePath(): void
    {
        $request = new Request([], [], ['REQUEST_URI' => '/test']);
        $this->assertSame('/test', $request->path());
    }

    /**
     * B.2 - path() se parse fallisce → default /
     */
    public function testPathDefaultsToRootWhenMissing(): void
    {
        $request = new Request([], [], []);
        $this->assertSame('/', $request->path());
    }

    /**
     * B.2 - path() con REQUEST_URI=/ → /
     */
    public function testPathReturnsRootForSlash(): void
    {
        $request = new Request([], [], ['REQUEST_URI' => '/']);
        $this->assertSame('/', $request->path());
    }

    /**
     * B.2 - path() con URI complesso con anchor
     */
    public function testPathIgnoresAnchor(): void
    {
        $request = new Request([], [], ['REQUEST_URI' => '/page?x=1#section']);
        $this->assertSame('/page', $request->path());
    }

    /**
     * B.3 - post() deve restituire l'array post passato al costruttore
     */
    public function testPostReturnsPostData(): void
    {
        $postData = ['name' => 'Mario', 'email' => 'mario@test.com'];
        $request = new Request([], $postData, []);
        
        $this->assertSame($postData, $request->post());
    }

    /**
     * B.3 - post() con array vuoto
     */
    public function testPostReturnsEmptyArrayWhenNoData(): void
    {
        $request = new Request([], [], []);
        $this->assertSame([], $request->post());
    }

    /**
     * B - Verifica immutabilità: modificare il risultato di post()
     * non deve influenzare richieste successive
     */
    public function testPostDataIsImmutable(): void
    {
        $postData = ['name' => 'Mario'];
        $request = new Request([], $postData, []);
        
        $result = $request->post();
        $result['name'] = 'Luigi';
        
        // La richiesta originale non deve essere modificata
        $this->assertSame(['name' => 'Mario'], $request->post());
    }

    /**
     * B.1 - method() con diversi metodi HTTP
     */
    public function testMethodWithDifferentHttpMethods(): void
    {
        $methods = ['PUT', 'DELETE', 'PATCH', 'OPTIONS', 'HEAD'];
        
        foreach ($methods as $method) {
            $request = new Request([], [], ['REQUEST_METHOD' => $method]);
            $this->assertSame($method, $request->method());
        }
    }

    /**
     * B.2 - path() con URI vuoto → default /
     */
    public function testPathWithEmptyUri(): void
    {
        $request = new Request([], [], ['REQUEST_URI' => '']);
        $this->assertSame('/', $request->path());
    }

    /**
     * B - Test fromGlobals() per coverage completo
     */
    public function testFromGlobalsCreatesRequestFromGlobalVariables(): void
    {
        // Salva le variabili globali originali
        $originalGet = $_GET;
        $originalPost = $_POST;
        $originalServer = $_SERVER;

        // Imposta variabili di test
        $_GET = ['test' => 'value'];
        $_POST = ['name' => 'Mario'];
        $_SERVER = ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/test'];

        $request = Request::fromGlobals();

        // Verifica che il metodo funzioni
        $this->assertInstanceOf(Request::class, $request);
        $this->assertSame('POST', $request->method());
        $this->assertSame('/test', $request->path());
        $this->assertSame(['name' => 'Mario'], $request->post());

        // Ripristina le variabili globali
        $_GET = $originalGet;
        $_POST = $originalPost;
        $_SERVER = $originalServer;
    }
}
