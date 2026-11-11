<?php
/** Login do painel: confere e-mail e senha e abre a sessão. */

declare(strict_types=1);

require dirname(__DIR__, 3) . '/config-leads/nucleo.php';

iniciarApi();
exigirMetodo('POST');
exigirOrigem();

$dados = corpoJson();
$email = strtolower(texto($dados, 'email', 160));
$senha = senhaDoCorpo($dados, 'senha');
$recusa = ['ok' => false, 'erro' => 'E-mail ou senha incorretos.'];
if ($email === '' || $senha === '' || strlen($senha) > 200) {
    responder(401, $recusa);
}

$pdo = db();
// Nenhuma senha válida tem caracteres de controle; no PHP 8.4 o hash daria erro e revelaria o e-mail.
if (preg_match('/[\x00-\x1F\x7F]/', $senha)) {
    registrarTentativa($pdo, $email);
    responder(401, $recusa);
}
if (tentativasBloqueadas($pdo, $email)) {
    responder(429, ['ok' => false, 'erro' => 'Muitas tentativas. Aguarde ' . LOGIN_JANELA_MINUTOS . ' minutos.']);
}

$consulta = $pdo->prepare('SELECT id, nome, email, papel, senha_hash, sessao_versao FROM auth_users WHERE email = ? AND ativo = 1');
$consulta->execute([$email]);
$usuario = $consulta->fetch(PDO::FETCH_ASSOC);

if ($usuario) {
    $senhaCorreta = password_verify($senha, $usuario['senha_hash']);
} else {
    // Gasta o mesmo tempo de um usuário real, para o tempo de resposta não revelar quais e-mails existem.
    hashSenha($senha);
    $senhaCorreta = false;
}

if (!$senhaCorreta) {
    registrarTentativa($pdo, $email);
    registrarLog($pdo, $usuario ? (int) $usuario['id'] : null, 'login_falhou', null, $usuario ? null : 'e-mail inexistente');
    responder(401, $recusa);
}

$pdo->prepare('DELETE FROM auth_tentativas WHERE email = ? AND ip = ?')->execute([$email, ip()]);
if (password_needs_rehash($usuario['senha_hash'], PASSWORD_BCRYPT, ['cost' => CUSTO_BCRYPT])) {
    $pdo->prepare('UPDATE auth_users SET senha_hash = ? WHERE id = ?')->execute([hashSenha($senha), $usuario['id']]);
}
$pdo->prepare('UPDATE auth_users SET ultimo_login = NOW() WHERE id = ?')->execute([$usuario['id']]);
registrarLog($pdo, (int) $usuario['id'], 'login');

abrirSessao($usuario);
responder(200, ['ok' => true, 'usuario' => usuarioPublico($usuario), 'csrf' => $_SESSION['csrf']]);
