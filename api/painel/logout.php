<?php
/** Encerra a sessão do painel. */

declare(strict_types=1);

require dirname(__DIR__, 3) . '/config-leads/nucleo.php';

iniciarApi();
exigirMetodo('POST');
exigirOrigem();

$usuario = usuarioLogado(db());
if ($usuario) {
    exigirCsrf();
    registrarLog(db(), (int) $usuario['id'], 'logout');
}
encerrarSessao();

responder(200, ['ok' => true]);
