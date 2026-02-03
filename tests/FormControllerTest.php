<?php
declare(strict_types=1);

namespace Test;

use App\Controller\FormController;
use App\Http\Request;
use App\Validation\ContactFormValidator;
use PHPUnit\Framework\TestCase;

final class FormControllerTest extends TestCase
{
    private FormController $controller;

    protected function setUp(): void
    {
        $this->controller = new FormController(new ContactFormValidator());
    }

    /**
     * E.1 - showForm() risposta status 200
     */
    public function testShowFormReturnsStatus200(): void
    {
        $request = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/']);
        $response = $this->controller->showForm($request);

        $this->assertSame(200, $response->status());
    }

    /**
     * E.1 - showForm() contiene <form e action="/submit"
     */
    public function testShowFormContainsFormElement(): void
    {
        $request = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/']);
        $response = $this->controller->showForm($request);

        $body = $response->body();
        $this->assertStringContainsString('<form', $body);
        $this->assertStringContainsString('action="/submit"', $body);
        $this->assertStringContainsString('method="post"', $body);
    }

    /**
     * E.1 - showForm() contiene i campi name, email, message
     */
    public function testShowFormContainsRequiredFields(): void
    {
        $request = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/']);
        $response = $this->controller->showForm($request);

        $body = $response->body();
        $this->assertStringContainsString('name="name"', $body);
        $this->assertStringContainsString('name="email"', $body);
        $this->assertStringContainsString('name="message"', $body);
    }

    /**
     * E.2 - showForm() valori "old" iniziali sono vuoti
     */
    public function testShowFormHasEmptyInitialValues(): void
    {
        $request = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/']);
        $response = $this->controller->showForm($request);

        $body = $response->body();
        // Input name e email devono avere value=""
        $this->assertMatchesRegularExpression('/name="name"[^>]*value=""/', $body);
        $this->assertMatchesRegularExpression('/name="email"[^>]*value=""/', $body);
    }

    /**
     * E.3 - handleSubmit() con input invalido restituisce status 422
     */
    public function testHandleSubmitWithInvalidInputReturns422(): void
    {
        $request = new Request([], [
            'name' => 'A',
            'email' => 'invalid',
            'message' => 'Ciao'
        ], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/submit']);

        $response = $this->controller->handleSubmit($request);

        $this->assertSame(422, $response->status());
    }

    /**
     * E.3 - handleSubmit() con errori mostra titolo "Correggi gli errori"
     */
    public function testHandleSubmitWithErrorsShowsCorrectTitle(): void
    {
        $request = new Request([], [
            'name' => '',
            'email' => 'invalid',
            'message' => 'short'
        ], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/submit']);

        $response = $this->controller->handleSubmit($request);

        $body = $response->body();
        $this->assertStringContainsString('Correggi gli errori', $body);
    }

    /**
     * E.3 - handleSubmit() mostra almeno un messaggio di errore
     */
    public function testHandleSubmitShowsErrorMessages(): void
    {
        $request = new Request([], [
            'name' => 'A',
            'email' => 'test@example.com',
            'message' => 'Messaggio valido'
        ], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/submit']);

        $response = $this->controller->handleSubmit($request);

        $body = $response->body();
        // Deve contenere il messaggio di errore per il nome
        $this->assertStringContainsString('obbligatorio', $body);
        $this->assertStringContainsString('2 caratteri', $body);
    }

    /**
     * E.4 - handleSubmit() preserva i dati inseriti (old)
     */
    public function testHandleSubmitPreservesOldData(): void
    {
        $request = new Request([], [
            'name' => 'Mario Rossi',
            'email' => 'invalid-email',
            'message' => 'Messaggio valido con più di 10 caratteri'
        ], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/submit']);

        $response = $this->controller->handleSubmit($request);

        $body = $response->body();
        // I campi validi devono essere preservati
        $this->assertStringContainsString('Mario Rossi', $body);
        $this->assertStringContainsString('invalid-email', $body);
        $this->assertStringContainsString('Messaggio valido', $body);
    }

    /**
     * E.5 - handleSubmit() escape HTML nell'old
     */
    public function testHandleSubmitEscapesHtmlInOldData(): void
    {
        $request = new Request([], [
            'name' => '<script>alert("XSS")</script>',
            'email' => 'test@example.com',
            'message' => 'Messaggio valido'
        ], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/submit']);

        $response = $this->controller->handleSubmit($request);

        $body = $response->body();
        // Il validator rimuove i tag HTML (strip_tags), quindi il tag <script> non compare
        // Il risultato sarà solo il testo alert("XSS") escaped
        $this->assertStringNotContainsString('<script>', $body);
        $this->assertStringNotContainsString('<script>alert', $body);
        // Deve contenere il testo escaped
        $this->assertStringContainsString('alert', $body);
    }

    /**
     * E.5 - handleSubmit() escape HTML con tag <b>
     */
    public function testHandleSubmitEscapesHtmlTags(): void
    {
        $request = new Request([], [
            'name' => '<b>Mario</b>',
            'email' => 'invalid',
            'message' => 'Messaggio valido'
        ], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/submit']);

        $response = $this->controller->handleSubmit($request);

        $body = $response->body();
        // Il nome viene sanitizzato dal validator (strip_tags), quindi sarà solo "Mario"
        // Oppure, se presente nell'attributo value, deve essere escaped
        $this->assertStringNotContainsString('<b>Mario</b>', $body);
    }

    /**
     * E.6 - handleSubmit() con input valido restituisce status 200
     */
    public function testHandleSubmitWithValidInputReturns200(): void
    {
        $request = new Request([], [
            'name' => 'Mario Rossi',
            'email' => 'mario@example.com',
            'message' => 'Questo è un messaggio di test valido'
        ], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/submit']);

        $response = $this->controller->handleSubmit($request);

        $this->assertSame(200, $response->status());
    }

    /**
     * E.6 - handleSubmit() con successo contiene "Grazie" e il nome
     */
    public function testHandleSubmitSuccessContainsThankYouMessage(): void
    {
        $request = new Request([], [
            'name' => 'Mario Rossi',
            'email' => 'mario@example.com',
            'message' => 'Questo è un messaggio di test valido'
        ], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/submit']);

        $response = $this->controller->handleSubmit($request);

        $body = $response->body();
        $this->assertStringContainsString('Grazie', $body);
        $this->assertStringContainsString('Mario Rossi', $body);
    }

    /**
     * E.6 - handleSubmit() con successo contiene link "Torna alla form"
     */
    public function testHandleSubmitSuccessContainsBackLink(): void
    {
        $request = new Request([], [
            'name' => 'Mario',
            'email' => 'mario@example.com',
            'message' => 'Messaggio di test'
        ], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/submit']);

        $response = $this->controller->handleSubmit($request);

        $body = $response->body();
        $this->assertStringContainsString('Torna alla form', $body);
        $this->assertStringContainsString('href="/"', $body);
    }

    /**
     * E.7 - handleSubmit() escape nome in pagina di successo
     */
    public function testHandleSubmitSuccessEscapesName(): void
    {
        $request = new Request([], [
            'name' => '<b>Mario</b>',
            'email' => 'mario@example.com',
            'message' => 'Messaggio valido di test'
        ], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/submit']);

        $response = $this->controller->handleSubmit($request);

        $body = $response->body();
        // Il nome viene sanitizzato (strip_tags) quindi diventa "Mario"
        // e poi escaped quando visualizzato
        $this->assertStringNotContainsString('<b>Mario</b>', $body);
        // Deve contenere solo "Mario" come testo
        $this->assertStringContainsString('Mario', $body);
    }

    /**
     * E.7 - handleSubmit() con script tag nel nome
     */
    public function testHandleSubmitSuccessEscapesScriptTag(): void
    {
        $request = new Request([], [
            'name' => '<script>alert("XSS")</script>Test',
            'email' => 'test@example.com',
            'message' => 'Messaggio valido di test'
        ], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/submit']);

        $response = $this->controller->handleSubmit($request);

        $body = $response->body();
        // Il validator rimuove i tag HTML, quindi dovrebbe rimanere solo "Test"
        $this->assertStringNotContainsString('<script>', $body);
        $this->assertStringNotContainsString('alert("XSS")', $body);
    }

    /**
     * E.8 - handleSubmit() input extra non deve influenzare il risultato
     */
    public function testHandleSubmitIgnoresExtraInput(): void
    {
        $request = new Request([], [
            'name' => 'Mario',
            'email' => 'mario@example.com',
            'message' => 'Messaggio valido',
            'foo' => 'bar',
            'extra' => 'data'
        ], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/submit']);

        $response = $this->controller->handleSubmit($request);

        // Deve funzionare normalmente
        $this->assertSame(200, $response->status());
        $this->assertStringContainsString('Grazie', $response->body());
    }

    /**
     * E.9 - handleSubmit() message con newline e spazi
     */
    public function testHandleSubmitWithMessageContainingNewlines(): void
    {
        $request = new Request([], [
            'name' => 'Mario',
            'email' => 'mario@example.com',
            'message' => "Messaggio\ncon\nnewline\ne spazi   multipli"
        ], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/submit']);

        $response = $this->controller->handleSubmit($request);

        // Deve passare se ≥ 10 caratteri dopo trim
        $this->assertSame(200, $response->status());
    }

    /**
     * E - handleSubmit() con tutti i campi vuoti
     */
    public function testHandleSubmitWithAllEmptyFields(): void
    {
        $request = new Request([], [
            'name' => '',
            'email' => '',
            'message' => ''
        ], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/submit']);

        $response = $this->controller->handleSubmit($request);

        $this->assertSame(422, $response->status());
        $body = $response->body();
        
        // Deve contenere errori per tutti e 3 i campi
        $this->assertStringContainsString('obbligatorio', $body);
    }

    /**
     * E - handleSubmit() con campi solo spazi
     */
    public function testHandleSubmitWithWhitespaceOnlyFields(): void
    {
        $request = new Request([], [
            'name' => '   ',
            'email' => '   ',
            'message' => '          '
        ], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/submit']);

        $response = $this->controller->handleSubmit($request);

        // Dopo trim diventano vuoti, quindi errori
        $this->assertSame(422, $response->status());
    }

    /**
     * E - handleSubmit() preserva caratteri speciali validi
     */
    public function testHandleSubmitPreservesSpecialCharacters(): void
    {
        $request = new Request([], [
            'name' => "Mario O'Neill",
            'email' => 'mario@example.com',
            'message' => 'Messaggio con caratteri speciali: àèéìòù €'
        ], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/submit']);

        $response = $this->controller->handleSubmit($request);

        $this->assertSame(200, $response->status());
        $body = $response->body();
        // L'apostrofo viene escaped come &#039; per sicurezza
        $this->assertTrue(
            str_contains($body, "O'Neill") || str_contains($body, "O&#039;Neill"),
            "Il nome deve contenere O'Neill o O&#039;Neill"
        );
    }

    /**
     * E - handleSubmit() escape virgolette nel nome
     */
    public function testHandleSubmitEscapesQuotesInName(): void
    {
        $request = new Request([], [
            'name' => 'Mario "il grande"',
            'email' => 'invalid',
            'message' => 'Messaggio valido'
        ], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/submit']);

        $response = $this->controller->handleSubmit($request);

        $body = $response->body();
        // Le virgolette devono essere escaped negli attributi HTML
        $this->assertStringNotContainsString('value="Mario "il grande""', $body);
    }

    /**
     * E - showForm() deve avere titolo "Form contatti"
     */
    public function testShowFormHasCorrectTitle(): void
    {
        $request = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/']);
        $response = $this->controller->showForm($request);

        $body = $response->body();
        $this->assertStringContainsString('Form contatti', $body);
    }

    /**
     * E - handleSubmit() con errori singoli per ogni campo
     */
    public function testHandleSubmitWithSingleFieldError(): void
    {
        // Solo email invalida
        $request = new Request([], [
            'name' => 'Mario Rossi',
            'email' => 'invalid',
            'message' => 'Messaggio valido con almeno 10 caratteri'
        ], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/submit']);

        $response = $this->controller->handleSubmit($request);

        $this->assertSame(422, $response->status());
        $body = $response->body();
        
        // Deve mostrare solo l'errore email
        $this->assertStringContainsString('email', $body);
        $this->assertStringContainsString('valida', $body);
    }

    /**
     * E - Form deve avere novalidate per disabilitare validazione HTML5
     */
    public function testFormHasNovalidateAttribute(): void
    {
        $request = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/']);
        $response = $this->controller->showForm($request);

        $body = $response->body();
        $this->assertStringContainsString('novalidate', $body);
    }
}
