<?php
declare(strict_types=1);

namespace App\Controller;

use App\Http\Request;
use App\Http\Response;
use App\Validation\ContactFormValidator;
use Laminas\Escaper\Escaper;

final class FormController
{
    private Escaper $esc;

    public function __construct(
        private readonly ContactFormValidator $validator
    ) {
        $this->esc = new Escaper('utf-8');
    }

    public function showForm(Request $request): Response
    {
        return Response::html($this->renderForm(
            title: 'Form contatti',
            errors: [],
            old: ['name' => '', 'email' => '', 'message' => '']
        ));
    }

    public function handleSubmit(Request $request): Response
    {
        $post = $request->post();
        $result = $this->validator->validate($post);

        if ($result['errors'] !== []) {
            return Response::html($this->renderForm(
                title: 'Correggi gli errori',
                errors: $result['errors'],
                old: $result['data']
            ), 422);
        }

        $safeName = $this->esc->escapeHtml($result['data']['name']);

        $html = <<<HTML
<!doctype html>
<html lang="it">
<head><meta charset="utf-8"><title>Successo</title></head>
<body style="font-family: system-ui, sans-serif; max-width: 720px; margin: 40px auto;">
  <h1>Grazie, {$safeName} ✅</h1>
  <p>Il form è stato inviato correttamente (in questa versione non salviamo nulla).</p>
  <p><a href="/">Torna alla form</a></p>
</body>
</html>
HTML;

        return Response::html($html, 200);
    }

    /**
     * @param array<string,string> $errors
     * @param array{name:string,email:string,message:string} $old
     */
    private function renderForm(string $title, array $errors, array $old): string
    {
        $e = fn(string $v) => $this->esc->escapeHtml($v);

        $err = function (string $key) use ($errors, $e): string {
            return isset($errors[$key])
                ? '<div style="color:#b00020;">' . $e($errors[$key]) . '</div>'
                : '';
        };

        return <<<HTML
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <title>{$e($title)}</title>
</head>
<body style="font-family: system-ui, sans-serif; max-width: 720px; margin: 40px auto;">
  <h1>{$e($title)}</h1>
  <p>Compila e invia. Il backend valida e risponde con una pagina HTML.</p>

  <form method="post" action="/submit" novalidate>
    <label>Nome</label><br>
    <input type="text" name="name" value="{$e($old['name'])}" style="width:100%; padding:8px;">
    {$err('name')}
    <br><br>

    <label>Email</label><br>
    <input type="email" name="email" value="{$e($old['email'])}" style="width:100%; padding:8px;">
    {$err('email')}
    <br><br>

    <label>Messaggio</label><br>
    <textarea name="message" rows="6" style="width:100%; padding:8px;">{$e($old['message'])}</textarea>
    {$err('message')}
    <br><br>

    <button type="submit" style="padding:10px 16px;">Invia</button>
  </form>
</body>
</html>
HTML;
    }
}
