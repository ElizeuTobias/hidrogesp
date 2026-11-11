<?php
/**
 * GET: lista os leads (filtros tipo, situacao, busca, lixeira e pagina) e os totais.
 * POST { acao: "situacao", id, situacao }   muda a situação.
 * POST { acao: "excluir", id }              manda para a lixeira (some da lista, dos totais e do Excel).
 * POST { acao: "restaurar", id }            tira da lixeira.
 * POST { acao: "apagar", id }               apaga de vez, só administradores e só o que já está na lixeira.
 */

declare(strict_types=1);

require dirname(__DIR__, 3) . '/config-leads/nucleo.php';

const POR_PAGINA = 50;

iniciarApi();
$metodo = exigirMetodo('GET', 'POST');
$pdo = db();

if ($metodo === 'POST') {
    exigirOrigem();
    $usuario = exigirLogin($pdo);
    exigirCsrf();

    $dados = corpoJson();
    $acao = $dados['acao'] ?? 'situacao';
    $id = is_int($dados['id'] ?? null) ? $dados['id'] : 0;
    if ($id < 1) {
        responder(422, ['ok' => false, 'erro' => 'Dados inválidos.']);
    }
    $quem = (int) $usuario['id'];

    if ($acao === 'situacao') {
        $situacao = $dados['situacao'] ?? '';
        if (!in_array($situacao, SITUACOES_LEAD, true)) {
            responder(422, ['ok' => false, 'erro' => 'Dados inválidos.']);
        }
        $pdo->prepare('UPDATE leads SET situacao = ? WHERE id = ?')->execute([$situacao, $id]);
        registrarLog($pdo, $quem, 'lead_situacao', $id, $situacao);
        responder(200, ['ok' => true]);
    }

    if ($acao === 'excluir') {
        $alterar = $pdo->prepare('UPDATE leads SET excluido_em = NOW(), excluido_por = ? WHERE id = ? AND excluido_em IS NULL');
        $alterar->execute([$quem, $id]);
        if ($alterar->rowCount() === 0) {
            responder(404, ['ok' => false, 'erro' => 'Lead não encontrado ou já excluído.']);
        }
        registrarLog($pdo, $quem, 'lead_excluido', $id);
        responder(200, ['ok' => true]);
    }

    if ($acao === 'restaurar') {
        $alterar = $pdo->prepare('UPDATE leads SET excluido_em = NULL, excluido_por = NULL WHERE id = ? AND excluido_em IS NOT NULL');
        $alterar->execute([$id]);
        if ($alterar->rowCount() === 0) {
            responder(404, ['ok' => false, 'erro' => 'Lead não encontrado na lixeira.']);
        }
        registrarLog($pdo, $quem, 'lead_restaurado', $id);
        responder(200, ['ok' => true]);
    }

    if ($acao === 'apagar') {
        if ($usuario['papel'] !== 'admin') {
            responder(403, ['ok' => false, 'erro' => 'Apenas administradores podem apagar de vez.']);
        }
        // Só o que já está na lixeira: apagar de vez é sempre um segundo passo.
        $apagar = $pdo->prepare('DELETE FROM leads WHERE id = ? AND excluido_em IS NOT NULL');
        $apagar->execute([$id]);
        if ($apagar->rowCount() === 0) {
            responder(404, ['ok' => false, 'erro' => 'Lead não encontrado na lixeira.']);
        }
        registrarLog($pdo, $quem, 'lead_apagado', $id);
        responder(200, ['ok' => true]);
    }

    responder(422, ['ok' => false, 'erro' => 'Ação desconhecida.']);
}

exigirLogin($pdo);

[$onde, $valores] = filtrosLeads($_GET);

$contar = $pdo->prepare("SELECT COUNT(*) FROM leads $onde");
$contar->execute($valores);
$total = (int) $contar->fetchColumn();
$paginas = max(1, (int) ceil($total / POR_PAGINA));
$pagina = min($paginas, max(1, (int) ($_GET['pagina'] ?? 1)));

$lista = $pdo->prepare(
    'SELECT id, tipo, nome, empresa, cnpj, cidade, uf, telefone, email, campanha, assunto, situacao, email_enviado, criado_em, excluido_em
     FROM leads ' . $onde . ' ORDER BY criado_em DESC, id DESC LIMIT ' . POR_PAGINA . ' OFFSET ' . (($pagina - 1) * POR_PAGINA)
);
$lista->execute($valores);

// Totais sem os excluídos, mais quantos estão na lixeira.
$resumo = $pdo->query(
    "SELECT COALESCE(SUM(excluido_em IS NULL), 0) AS total,
            COALESCE(SUM(excluido_em IS NULL AND tipo = 'PJ'), 0) AS pj,
            COALESCE(SUM(excluido_em IS NULL AND tipo = 'PF'), 0) AS pf,
            COALESCE(SUM(excluido_em IS NULL AND situacao = 'novo'), 0) AS novos,
            COALESCE(SUM(excluido_em IS NOT NULL), 0) AS lixeira
     FROM leads"
)->fetch(PDO::FETCH_ASSOC);

responder(200, [
    'ok'      => true,
    'resumo'  => array_map('intval', $resumo),
    'lista'   => $lista->fetchAll(PDO::FETCH_ASSOC),
    'pagina'  => $pagina,
    'paginas' => $paginas,
    'total'   => $total,
]);
