<?php
/** Diz ao painel quem está logado e entrega o token CSRF da sessão. */

declare(strict_types=1);

require dirname(__DIR__, 3) . '/config-leads/nucleo.php';

iniciarApi();
exigirMetodo('GET');

$usuario = exigirLogin(db());
responder(200, ['ok' => true, 'usuario' => usuarioPublico($usuario), 'csrf' => $_SESSION['csrf'] ?? '']);
