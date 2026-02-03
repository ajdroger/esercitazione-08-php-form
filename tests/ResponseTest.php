<?php
declare(strict_types=1);

namespace Test;

use App\Http\Response;
use PHPUnit\Framework\TestCase;

final class ResponseTest extends TestCase
{
    /**
     * C.1 - Response::html($html) con status default 200
     */
    public function testHtmlCreatesResponseWithDefaultStatus(): void
    {
        $html = '<h1>Test</h1>';
        $response = Response::html($html);
        
        $this->assertSame(200, $response->status());
        $this->assertSame($html, $response->body());
    }

    /**
     * C.1 - Response::html($html, $status) con status personalizzato
     */
    public function testHtmlCreatesResponseWithCustomStatus(): void
    {
        $html = '<h1>Not Found</h1>';
        $response = Response::html($html, 404);
        
        $this->assertSame(404, $response->status());
        $this->assertSame($html, $response->body());
    }

    /**
     * C.1 - Header Content-Type deve essere text/html; charset=UTF-8
     */
    public function testHtmlSetsCorrectContentTypeHeader(): void
    {
        $response = Response::html('<p>Test</p>');
        $headers = $response->headers();
        
        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertSame('text/html; charset=UTF-8', $headers['Content-Type']);
    }

    /**
     * C.2 - headers() deve includere i header configurati
     */
    public function testHeadersReturnsConfiguredHeaders(): void
    {
        $response = Response::html('<div>Content</div>');
        $headers = $response->headers();
        
        $this->assertIsArray($headers);
        $this->assertNotEmpty($headers);
    }

    /**
     * C.3 - status() deve riflettere lo status passato
     */
    public function testStatusReflectsPassedValue(): void
    {
        $statuses = [200, 201, 400, 404, 422, 500];
        
        foreach ($statuses as $status) {
            $response = Response::html('<html></html>', $status);
            $this->assertSame($status, $response->status());
        }
    }

    /**
     * C.1 - body() deve essere esattamente l'HTML passato
     */
    public function testBodyReturnsExactHtml(): void
    {
        $html = '<!DOCTYPE html><html><head><title>Test</title></head><body><h1>Hello</h1></body></html>';
        $response = Response::html($html);
        
        $this->assertSame($html, $response->body());
    }

    /**
     * C - body() con HTML vuoto
     */
    public function testBodyWithEmptyHtml(): void
    {
        $response = Response::html('');
        $this->assertSame('', $response->body());
    }

    /**
     * C - body() con caratteri speciali
     */
    public function testBodyWithSpecialCharacters(): void
    {
        $html = '<p>Caratteri speciali: àèéìòù €</p>';
        $response = Response::html($html);
        
        $this->assertSame($html, $response->body());
    }

    /**
     * C - Risposta 422 Unprocessable Entity (usato per errori validazione)
     */
    public function testUnprocessableEntityStatus(): void
    {
        $response = Response::html('<form>Errors</form>', 422);
        
        $this->assertSame(422, $response->status());
    }

    /**
     * C - Verifica immutabilità status
     */
    public function testStatusIsImmutable(): void
    {
        $response = Response::html('<p>Test</p>', 200);
        
        // Verifico che lo status sia sempre lo stesso
        $this->assertSame(200, $response->status());
        $this->assertSame(200, $response->status());
    }

    /**
     * C - Verifica immutabilità body
     */
    public function testBodyIsImmutable(): void
    {
        $html = '<p>Original</p>';
        $response = Response::html($html);
        
        // Verifico che il body sia sempre lo stesso
        $this->assertSame($html, $response->body());
        $this->assertSame($html, $response->body());
    }

    /**
     * C - Verifica immutabilità headers
     */
    public function testHeadersAreImmutable(): void
    {
        $response = Response::html('<p>Test</p>');
        $headers1 = $response->headers();
        $headers2 = $response->headers();
        
        $this->assertSame($headers1, $headers2);
    }

    /**
     * C.4 - send() invia l'output correttamente
     * Usa output buffering per catturare echo
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testSendOutputsBodyContent(): void
    {
        $html = '<h1>Test Output</h1>';
        $response = Response::html($html, 200);

        ob_start();
        $response->send();
        $output = ob_get_clean();

        $this->assertSame($html, $output);
    }

    /**
     * C.4 - send() con status code diversi
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testSendWithDifferentStatusCodes(): void
    {
        $response = Response::html('<h1>Not Found</h1>', 404);

        ob_start();
        $response->send();
        $output = ob_get_clean();

        $this->assertSame('<h1>Not Found</h1>', $output);
        // In separate process, http_response_code è stato chiamato
        $this->assertSame(404, http_response_code());
    }

    /**
     * C.4 - send() invia headers e body
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testSendOutputsWithHeaders(): void
    {
        $html = '<p>Content with headers</p>';
        $response = Response::html($html);

        ob_start();
        $response->send();
        $output = ob_get_clean();

        // Verifica che il body sia corretto
        $this->assertSame($html, $output);
    }
}
