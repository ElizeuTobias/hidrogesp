<?php
/**
 * Gestão de usuários, só para administradores. Quem cria ou redefine a senha é o administrador.
 * GET: lista os usuários.
 * POST { acao: "criar", nome, email, papel, senha }   cria o usuário com a senha informada.
 * POST { acao: "senha", id, senha }                    define uma nova senha e derruba as sessões dele.
 * POST { acao: "ativo", id, ativo }                    ativa ou desativa (desativar derruba as sessões).
 * POST { acao: "papel", id, papel }                    muda entre admin e equipe.
 * Ninguém altera a própria conta por aqui (para isso existe "Trocar senha"), o que também garante
 * que sempre sobra pelo menos um administrador ativo.
 */

declare(strict_types=1);

require dirname(__DIR__, 3) . '/config-leads/nucleo.php';

iniciarApi();
$metodo = exigirMetodo('GET', 'POST');
$pdo = db();

if ($metodo === 'GET') {
    exigirAdmin($pdo);
    $lista = $pdo->query('SELECT id, nome, email, papel, ativo, ultimo_login, criado_em FROM auth_users ORDER BY nome')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($lista as &$u) {
        $u['id'] = (int) $u['id'];
        $u['ativo'] = (int) $u['ativo'] === 1;
    }
    unset($u);
    responder(200, ['ok' => true, 'usuarios' => $lista]);
}

exigirOrigem();
$admin = exigirAdmin($pdo);
exigirCsrf();
$dados = corpoJson();
$acao = $dados['acao'] ?? '';

if ($acao === 'criar') {
    $nome = texto($dados, 'nome', 120);
    $email = strtolower(texto($dados, 'email', 160));
    $papel = $dados['papel'] ?? '';
    $senha = senhaDoCorpo($dados, 'senha');
    if ($nome === '' || !emailValido($email) || !in_array($papel, PAPEIS, true)) {
        responder(422, ['ok' => false, 'erro' => 'Preencha nome, e-mail válido e papel.']);
    }
    $problema = problemaNaSenha($senha, $email);
    if ($problema !== null) {
        responder(422, ['ok' => false, 'erro' => $problema]);
    }
    $existe = $pdo->prepare('SELECT COUNT(*) FROM auth_users WHERE email = ?');
    $existe->execute([$email]);
    if ((int) $existe->fetchColumn() > 0) {
        responder(409, ['ok' => false, 'erro' => 'Já existe um usuário com este e-mail.']);
    }

    $pdo->prepare('INSERT INTO auth_users (nome, email, senha_hash, papel) VALUES (?, ?, ?, ?)')
        ->execute([$nome, $email, hashSenha($senha), $papel]);
    registrarLog($pdo, (int) $admin['id'], 'usuario_criado', (int) $pdo->lastInsertId(), $papel);
    responder(201, ['ok' => true]);
}

// As demais ações mexem em um usuário existente, que não pode ser o próprio administrador.
$id = is_int($dados['id'] ?? null) ? $dados['id'] : 0;
if ($id === (int) $admin['id']) {
    responder(422, ['ok' => false, 'erro' => 'Use "Trocar senha" para a sua conta. Outro administrador precisa alterar o seu acesso.']);
}
$consulta = $pdo->prepare('SELECT id, email FROM auth_users WHERE id = ?');
$consulta->execute([$id]);
$alvo = $consulta->fetch(PDO::FETCH_ASSOC);
if (!$alvo) {
    responder(404, ['ok' => false, 'erro' => 'Usuário não encontrado.']);
}

if ($acao === 'senha') {
    $senha = senhaDoCorpo($dados, 'senha');
    $problema = problemaNaSenha($senha, $alvo['email']);
    if ($problema !== null) {
        responder(422, ['ok' => false, 'erro' => $problema]);
    }
    $pdo->prepare('UPDATE auth_users SET senha_hash = ?, sessao_versao = sessao_versao + 1 WHERE id = ?')
        ->execute([hashSenha($senha), $id]);
    $pdo->prepare('DELETE FROM auth_tentativas WHERE email = ?')->execute([$alvo['email']]);
    registrarLog($pdo, (int) $admin['id'], 'senha_redefinida', $id);
    responder(200, ['ok' => true]);
}

if ($acao === 'ativo') {
    if (!is_bool($dados['ativo'] ?? null)) {
        responder(422, ['ok' => false, 'erro' => 'Dados inválidos.']);
    }
    $ativo = $dados['ativo'];
    $pdo->prepare('UPDATE auth_users SET ativo = ?, sessao_versao = sessao_versao + 1 WHERE id = ?')->execute([$ativo ? 1 : 0, $id]);
    registrarLog($pdo, (int) $admin['id'], $ativo ? 'usuario_ativado' : 'usuario_desativado', $id);
    responder(200, ['ok' => true]);
}

if ($acao === 'papel') {
    $papel = $dados['papel'] ?? '';
    if (!in_array($papel, PAPEIS, true)) {
        responder(422, ['ok' => false, 'erro' => 'Papel inválido.']);
    }
    $pdo->prepare('UPDATE auth_users SET papel = ? WHERE id = ?')->execute([$papel, $id]);
    registrarLog($pdo, (int) $admin['id'], 'usuario_papel', $id, $papel);
    responder(200, ['ok' => true]);
}

responder(422, ['ok' => false, 'erro' => 'Ação desconhecida.']);
