<?php
declare(strict_types=1);

namespace App\Validation;

final class ContactFormValidator
{
    /**
     * @param array<string, mixed> $input
     * @return array{data: array{name:string,email:string,message:string}, errors: array<string,string>}
     */
    public function validate(array $input): array
    {
        $name = trim((string)($input['name'] ?? ''));
        $email = trim((string)($input['email'] ?? ''));
        $message = trim((string)($input['message'] ?? ''));

        $errors = [];

        if ($name === '' || mb_strlen($name) < 2) {
            $errors['name'] = 'Il nome è obbligatorio (min 2 caratteri).';
        }

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'Inserisci una email valida.';
        }

        if ($message === '' || mb_strlen($message) < 10) {
            $errors['message'] = 'Il messaggio è obbligatorio (min 10 caratteri).';
        }

        $data = [
            'name' => $this->stripTagsAndNormalize($name),
            'email' => $this->stripTagsAndNormalize($email),
            'message' => $this->stripTagsAndNormalize($message),
        ];

        return ['data' => $data, 'errors' => $errors];
    }

    private function stripTagsAndNormalize(string $value): string
    {
        $value = strip_tags($value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        return trim($value);
    }
}
