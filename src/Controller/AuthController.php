<?php declare(strict_types=1);

namespace Kniebes\MovieTracker\Controller;

use Kniebes\MovieTracker\Http\Response;
use Kniebes\MovieTracker\Service\Auth;
use Kniebes\MovieTracker\View\Template;

class AuthController
{
    public function loginForm(): never
    {
        if ((new Auth())->isAuthenticated()) {
            Response::redirect('/movies');
        }

        Response::html(Template::render(template: 'login.html.php', variables: ['error' => null]));
    }

    public function login(): never
    {
        $auth = new Auth();
        $username = trim(strval($_POST['username'] ?? ''));
        $password = strval($_POST['password'] ?? '');

        if ($auth->isThrottled()) {
            Response::html(
                content: Template::render(template: 'login.html.php', variables: [
                    'error' => 'Zu viele Fehlversuche. Bitte in 15 Minuten erneut versuchen.',
                ]),
                statusCode: 429
            );
        }

        if ($username !== '' && $password !== '' && $auth->login(username: $username, password: $password)) {
            Response::redirect('/movies');
        }

        Response::html(
            content: Template::render(template: 'login.html.php', variables: [
                'error' => 'Anmeldung fehlgeschlagen. Benutzername oder Passwort stimmen nicht.',
            ]),
            statusCode: 401
        );
    }

    public function logout(): never
    {
        (new Auth())->logout();
        Response::redirect('/login');
    }
}
