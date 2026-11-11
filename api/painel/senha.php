<?php
/**
 * Troca opcional da própria senha. Exige a senha atual.
 * Depois da troca, as outras sessões abertas do usuário caem.
 */

declare(strict_types=1);

require dirname(__DIR__, 3) . '/config-leads/nucleo.php';

iniciarApi();
exigirMetodo('POST');
exigirOrigem();

$pdo = db();
$usuario = exigirLogin($pdo);
exigirCsrf();

$dados = corpoJson();
$atual = senhaDoCorpo($dados, 'atual');
$nova = senhaDoCorpo($dados, 'nova');

if (tentativasBloqueadas($pdo, $usuario['email'])) {
    responder(429, ['ok' => false, 'erro' => 'Muitas tentativas. Aguarde ' . LOGIN_JANELA_MINUTOS . ' minutos.']);
}

$consulta = $pdo->prepare('SELECT senha_hash FROM auth_users WHERE id = ?');
$consulta->execute([$usuario['id']]);
if ($atual === '' || strlen($atual) > 200 || preg_match('/[\x00-\x1F\x7F]/', $atual)
    || !password_verify($atual, (string) $consulta->fetchColumn())) {
    registrarTentativa($pdo, $usuario['email']);
    responder(422, ['ok' => false, 'erro' => 'A senha atual está incorreta.']);
}

$problema = problemaNaSenha($nova, $usuario['email']);
if ($problema === null && hash_equals($atual, $nova)) {
    $problema = 'A nova senha precisa ser diferente da atual.';
}
if ($problema !== null) {
    responder(422, ['ok' => false, 'erro' => $problema]);
}

$pdo->prepare('UPDATE auth_users SET senha_hash = ?, sessao_versao = sessao_versao + 1 WHERE id = ?')
    ->execute([hashSenha($nova), $usuario['id']]);
registrarLog($pdo, (int) $usuario['id'], 'senha_alterada');

$usuario['sessao_versao'] = (int) $usuario['sessao_versao'] + 1;
abrirSessao($usuario);

responder(200, ['ok' => true, 'usuario' => usuarioPublico($usuario), 'csrf' => $_SESSION['csrf']]);
