<?php
/** Baixa os leads em CSV para o Excel, com os mesmos filtros da lista, inclusive a busca. */

declare(strict_types=1);

require dirname(__DIR__, 3) . '/config-leads/nucleo.php';

iniciarApi();
exigirMetodo('GET');
$pdo = db();
$usuario = exigirLogin($pdo);

[$onde, $valores] = filtrosLeads($_GET);

$colunas = [
    'id' => 'ID', 'criado_em' => 'Data', 'tipo' => 'Tipo', 'situacao' => 'Situação', 'nome' => 'Nome',
    'empresa' => 'Empresa', 'cargo' => 'Cargo', 'cnpj' => 'CNPJ', 'cidade' => 'Cidade', 'uf' => 'UF',
    'telefone' => 'Telefone', 'email' => 'E-mail', 'segmento' => 'Segmento', 'volume' => 'Volume',
    'prazo' => 'Prazo', 'assunto' => 'Assunto', 'campanha' => 'Campanha', 'pagina' => 'Página',
    'gclid' => 'GCLID', 'ip' => 'IP',
];
$consulta = $pdo->prepare('SELECT ' . implode(', ', array_keys($colunas)) . " FROM leads $onde ORDER BY criado_em DESC, id DESC");
$consulta->execute($valores);

registrarLog($pdo, (int) $usuario['id'], 'exportacao', null, $onde === '' ? 'tudo' : 'com filtro');

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="leads-' . date('Y-m-d') . '.csv"');

$saida = fopen('php://output', 'w');
fwrite($saida, "\xEF\xBB\xBF"); // Faz o Excel reconhecer os acentos.
fputcsv($saida, array_values($colunas), ';', '"', '');
while ($linha = $consulta->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($saida, array_map('celulaSegura', $linha), ';', '"', '');
}
fclose($saida);
