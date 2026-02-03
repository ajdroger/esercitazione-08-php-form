<?php
declare(strict_types=1);

namespace Test;

use App\Validation\ContactFormValidator;
use PHPUnit\Framework\TestCase;

final class ContactFormValidatorTest extends TestCase
{
    private ContactFormValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new ContactFormValidator();
    }

    /**
     * D.1-3 - Input valido deve restituire nessun errore
     */
    public function testValidInputReturnsNoErrors(): void
    {
        $input = [
            'name' => 'Mario Rossi',
            'email' => 'mario@example.com',
            'message' => 'Questo è un messaggio di test'
        ];

        $result = $this->validator->validate($input);

        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertEmpty($result['errors']);
        $this->assertSame('Mario Rossi', $result['data']['name']);
        $this->assertSame('mario@example.com', $result['data']['email']);
        $this->assertSame('Questo è un messaggio di test', $result['data']['message']);
    }

    /**
     * D.1 - name obbligatorio e minimo 2 caratteri
     */
    public function testNameIsRequired(): void
    {
        $input = [
            'name' => '',
            'email' => 'test@example.com',
            'message' => 'Messaggio valido'
        ];

        $result = $this->validator->validate($input);

        $this->assertArrayHasKey('name', $result['errors']);
        $this->assertStringContainsString('obbligatorio', $result['errors']['name']);
    }

    /**
     * D.1 - name con un solo carattere
     */
    public function testNameTooShort(): void
    {
        $input = [
            'name' => 'A',
            'email' => 'test@example.com',
            'message' => 'Messaggio valido'
        ];

        $result = $this->validator->validate($input);

        $this->assertArrayHasKey('name', $result['errors']);
    }

    /**
     * D.1 - name con esattamente 2 caratteri (valido)
     */
    public function testNameWithTwoCharactersIsValid(): void
    {
        $input = [
            'name' => 'Al',
            'email' => 'test@example.com',
            'message' => 'Messaggio valido con almeno 10 caratteri'
        ];

        $result = $this->validator->validate($input);

        $this->assertArrayNotHasKey('name', $result['errors']);
    }

    /**
     * D.2 - email obbligatoria
     */
    public function testEmailIsRequired(): void
    {
        $input = [
            'name' => 'Mario',
            'email' => '',
            'message' => 'Messaggio valido'
        ];

        $result = $this->validator->validate($input);

        $this->assertArrayHasKey('email', $result['errors']);
        $this->assertStringContainsString('valida', $result['errors']['email']);
    }

    /**
     * D.2 - email deve rispettare FILTER_VALIDATE_EMAIL
     */
    public function testEmailMustBeValid(): void
    {
        $input = [
            'name' => 'Mario',
            'email' => 'invalid-email',
            'message' => 'Messaggio valido'
        ];

        $result = $this->validator->validate($input);

        $this->assertArrayHasKey('email', $result['errors']);
    }

    /**
     * D.2 - email valide diverse
     */
    public function testValidEmails(): void
    {
        $validEmails = [
            'test@example.com',
            'user.name@example.com',
            'user+tag@example.co.uk',
            'test123@test-domain.com'
        ];

        foreach ($validEmails as $email) {
            $input = [
                'name' => 'Mario',
                'email' => $email,
                'message' => 'Messaggio valido con almeno 10 caratteri'
            ];

            $result = $this->validator->validate($input);
            $this->assertArrayNotHasKey('email', $result['errors'], "Email $email dovrebbe essere valida");
        }
    }

    /**
     * D.3 - message obbligatorio
     */
    public function testMessageIsRequired(): void
    {
        $input = [
            'name' => 'Mario',
            'email' => 'test@example.com',
            'message' => ''
        ];

        $result = $this->validator->validate($input);

        $this->assertArrayHasKey('message', $result['errors']);
        $this->assertStringContainsString('obbligatorio', $result['errors']['message']);
    }

    /**
     * D.3 - message minimo 10 caratteri dopo trim
     */
    public function testMessageTooShort(): void
    {
        $input = [
            'name' => 'Mario',
            'email' => 'test@example.com',
            'message' => 'Ciao'
        ];

        $result = $this->validator->validate($input);

        $this->assertArrayHasKey('message', $result['errors']);
    }

    /**
     * D.3 - message con esattamente 10 caratteri (valido)
     */
    public function testMessageWithTenCharactersIsValid(): void
    {
        $input = [
            'name' => 'Mario',
            'email' => 'test@example.com',
            'message' => '1234567890'
        ];

        $result = $this->validator->validate($input);

        $this->assertArrayNotHasKey('message', $result['errors']);
    }

    /**
     * D.4-5 - Output sempre nella forma corretta con 3 chiavi in data
     */
    public function testOutputStructure(): void
    {
        $input = ['name' => 'A', 'email' => 'invalid', 'message' => 'short'];

        $result = $this->validator->validate($input);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('name', $result['data']);
        $this->assertArrayHasKey('email', $result['data']);
        $this->assertArrayHasKey('message', $result['data']);
    }

    /**
     * D.6 - errors deve contenere solo chiavi per campi invalidi
     */
    public function testErrorsContainOnlyInvalidFields(): void
    {
        $input = [
            'name' => 'Mario',  // Valido
            'email' => 'invalid',  // Invalido
            'message' => 'Messaggio valido con più di 10 caratteri'  // Valido
        ];

        $result = $this->validator->validate($input);

        $this->assertArrayHasKey('email', $result['errors']);
        $this->assertArrayNotHasKey('name', $result['errors']);
        $this->assertArrayNotHasKey('message', $result['errors']);
    }

    /**
     * D.7 - Sanitizzazione: deve rimuovere HTML
     */
    public function testStripTagsRemovesHtml(): void
    {
        $input = [
            'name' => '<b>Mario</b>',
            'email' => 'test@example.com',
            'message' => '<script>alert("XSS")</script>Messaggio valido'
        ];

        $result = $this->validator->validate($input);

        $this->assertSame('Mario', $result['data']['name']);
        $this->assertStringNotContainsString('<b>', $result['data']['name']);
        $this->assertStringNotContainsString('<script>', $result['data']['message']);
    }

    /**
     * D.8 - Sanitizzazione: deve normalizzare spazi multipli
     */
    public function testNormalizeMultipleSpaces(): void
    {
        $input = [
            'name' => 'Mario    Rossi',
            'email' => 'test@example.com',
            'message' => 'Messaggio    con    spazi    multipli'
        ];

        $result = $this->validator->validate($input);

        $this->assertSame('Mario Rossi', $result['data']['name']);
        $this->assertSame('Messaggio con spazi multipli', $result['data']['message']);
    }

    /**
     * D.9 - Sanitizzazione: deve fare trim
     */
    public function testTrimsWhitespace(): void
    {
        $input = [
            'name' => '  Mario Rossi  ',
            'email' => '  test@example.com  ',
            'message' => '  Messaggio valido con spazi  '
        ];

        $result = $this->validator->validate($input);

        $this->assertSame('Mario Rossi', $result['data']['name']);
        $this->assertSame('test@example.com', $result['data']['email']);
        $this->assertSame('Messaggio valido con spazi', $result['data']['message']);
    }

    /**
     * D.10 - Casi limite: input mancanti → 3 errori
     */
    public function testMissingInputsReturnThreeErrors(): void
    {
        $result = $this->validator->validate([]);

        $this->assertCount(3, $result['errors']);
        $this->assertArrayHasKey('name', $result['errors']);
        $this->assertArrayHasKey('email', $result['errors']);
        $this->assertArrayHasKey('message', $result['errors']);
    }

    /**
     * D.11 - Casi limite: input non-stringa (array) → cast a string
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    #[\PHPUnit\Framework\Attributes\PreserveGlobalState(false)]
    public function testNonStringInputDoesNotCrash(): void
    {
        // Sopprimiamo i warning PHP per array-to-string conversion
        set_error_handler(function() { return true; }, E_WARNING);
        
        $input = [
            'name' => ['array', 'value'],
            'email' => 123,
            'message' => true
        ];

        // Non deve crashare
        $result = $this->validator->validate($input);

        // Ripristina error handler
        restore_error_handler();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('errors', $result);
        // Tutti i campi dovrebbero avere errori perché i valori sono invalidi
        $this->assertNotEmpty($result['errors']);
    }

    /**
     * D.12 - Caratteri Unicode (accenti) → non deve corrompere il testo
     */
    public function testUnicodeCharactersArePreserved(): void
    {
        $input = [
            'name' => 'José María',
            'email' => 'jose@example.com',
            'message' => 'Messaggio con àccénti e ùmlàut €'
        ];

        $result = $this->validator->validate($input);

        $this->assertSame('José María', $result['data']['name']);
        $this->assertStringContainsString('àccénti', $result['data']['message']);
        $this->assertStringContainsString('€', $result['data']['message']);
    }

    /**
     * D.9 - Message con newline e spazi → deve passare se ≥ 10 caratteri dopo trim
     */
    public function testMessageWithNewlinesAndSpaces(): void
    {
        $input = [
            'name' => 'Mario',
            'email' => 'test@example.com',
            'message' => "  Messaggio\n con\n newline  "
        ];

        $result = $this->validator->validate($input);

        // Dopo normalizzazione dovrebbe essere valido (≥ 10 caratteri)
        $this->assertArrayNotHasKey('message', $result['errors']);
        // Gli spazi multipli vengono normalizzati
        $this->assertSame('Messaggio con newline', $result['data']['message']);
    }

    /**
     * Data Provider per test multipli di validazione email
     */
    public static function invalidEmailProvider(): array
    {
        return [
            ['plaintext'],
            ['@example.com'],
            ['user@'],
            ['user space@example.com'],
            ['user@@example.com'],
        ];
    }

    /**
     * D.2 - Test multipli email invalide con data provider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('invalidEmailProvider')]
    public function testInvalidEmailsWithDataProvider(string $email): void
    {
        $input = [
            'name' => 'Mario',
            'email' => $email,
            'message' => 'Messaggio valido'
        ];

        $result = $this->validator->validate($input);

        $this->assertArrayHasKey('email', $result['errors']);
    }

    /**
     * D - Tutti i campi invalidi contemporaneamente
     */
    public function testAllFieldsInvalid(): void
    {
        $input = [
            'name' => 'A',
            'email' => 'invalid',
            'message' => 'short'
        ];

        $result = $this->validator->validate($input);

        $this->assertCount(3, $result['errors']);
        $this->assertArrayHasKey('name', $result['errors']);
        $this->assertArrayHasKey('email', $result['errors']);
        $this->assertArrayHasKey('message', $result['errors']);
    }

    /**
     * D - Verifica che i messaggi di errore siano stringhe non vuote
     */
    public function testErrorMessagesAreNonEmptyStrings(): void
    {
        $input = [
            'name' => '',
            'email' => 'invalid',
            'message' => 'short'
        ];

        $result = $this->validator->validate($input);

        foreach ($result['errors'] as $key => $message) {
            $this->assertIsString($message, "Errore per campo $key non è una stringa");
            $this->assertNotEmpty($message, "Messaggio errore per campo $key è vuoto");
        }
    }
}
