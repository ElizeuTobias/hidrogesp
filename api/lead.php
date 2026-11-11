<?php
/**
 * Recebe um lead em JSON (POST), grava no banco e avisa por e-mail.
 *
 * O lead é gravado antes do e-mail: se o aviso falhar, o lead continua salvo e o
 * visitante recebe sucesso. A coluna email_enviado mostra o que não foi avisado.
 *
 * Só "type" (PF|PJ) é obrigatório. Todo o resto é opcional e cada formulário decide o que exige:
 * name, company, city, phone, email, consent, role, cnpj, state, segment, volume, timeline,
 * topic, campaign, page, gclid. E-mail e telefone, quando vierem, precisam ter formato válido.
 * O campo "website" é uma armadilha para robôs: fica oculto e deve chegar vazio.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

const TAMANHO_MAXIMO = 20000;
const ERRO_GENERICO = 'Não foi possível enviar agora. Tente novamente.';

function responder(int $status, array $dados): void
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

/** Texto de uma linha: sem caracteres de controle, o que também impede injeção de cabeçalho no e-mail. */
function texto(array $dados, string $campo, int $maximo): string
{
    $valor = $dados[$campo] ?? '';
    if (!is_string($valor)) {
        return '';
    }
    $limpo = (string) preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $valor);
    return mb_substr(trim($limpo), 0, $maximo, 'UTF-8');
}

function ouNulo(string $valor): ?string
{
    return $valor === '' ? null : $valor;
}

function enviarAviso(array $config, array $lead): bool
{
    $rotulos = [
        'tipo' => 'Tipo', 'nome' => 'Nome', 'empresa' => 'Empresa', 'cargo' => 'Cargo', 'cnpj' => 'CNPJ',
        'cidade' => 'Cidade', 'uf' => 'UF', 'telefone' => 'Telefone', 'email' => 'E-mail',
        'segmento' => 'Segmento', 'volume' => 'Volume', 'prazo' => 'Prazo',
        'assunto' => 'Assunto', 'campanha' => 'Campanha', 'pagina' => 'Página',
    ];

    $linhas = [];
    foreach ($rotulos as $campo => $rotulo) {
        if (!empty($lead[$campo])) {
            $linhas[] = $rotulo . ': ' . $lead[$campo];
        }
    }

    $titulo = 'Novo lead ' . $lead['tipo'] . ' - ' . ($lead['empresa'] ?: $lead['nome'] ?: $lead['telefone'] ?: 'sem nome');
    $cabecalhos = ['From: Site Hidrogesp <' . $config['remetente'] . '>'];
    // Responder ao e-mail só faz sentido quando o lead deixou um e-mail (já validado).
    if ($lead['email'] !== '') {
        $cabecalhos[] = 'Reply-To: ' . $lead['email'];
    }
    $cabecalhos[] = 'MIME-Version: 1.0';
    $cabecalhos[] = 'Content-Type: text/plain; charset=UTF-8';
    $cabecalhos = implode("\r\n", $cabecalhos);

    return mail(
        $config['notificar'],
        '=?UTF-8?B?' . base64_encode($titulo) . '?=',
        implode("\n", $linhas),
        $cabecalhos
    );
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    responder(405, ['ok' => false, 'erro' => 'Método não permitido.']);
}

// Em public_html/api, dois níveis acima é a pasta do usuário, fora do alcance do navegador.
$arquivoConfig = dirname(__DIR__, 2) . '/config-leads/banco.php';
if (!is_file($arquivoConfig)) {
    error_log('[lead] arquivo de configuração não encontrado');
    responder(500, ['ok' => false, 'erro' => ERRO_GENERICO]);
}
$config = require $arquivoConfig;

$origem = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origem !== '' && !in_array($origem, $config['origens'], true)) {
    responder(403, ['ok' => false, 'erro' => 'Origem não permitida.']);
}

$corpo = (string) file_get_contents('php://input', false, null, 0, TAMANHO_MAXIMO + 1);
if (strlen($corpo) > TAMANHO_MAXIMO) {
    responder(413, ['ok' => false, 'erro' => 'Envio grande demais.']);
}

$dados = json_decode($corpo, true);
if (!is_array($dados)) {
    responder(400, ['ok' => false, 'erro' => 'Dados inválidos.']);
}

// Robô preencheu o campo oculto: finge sucesso e não grava nada.
if (texto($dados, 'website', 200) !== '') {
    responder(200, ['ok' => true]);
}

$lead = [
    'tipo'     => strtoupper(texto($dados, 'type', 2)),
    'nome'     => texto($dados, 'name', 120),
    'cidade'   => texto($dados, 'city', 120),
    'telefone' => texto($dados, 'phone', 20),
    'email'    => texto($dados, 'email', 160),
    'empresa'  => texto($dados, 'company', 160),
    'cargo'    => texto($dados, 'role', 120),
    'cnpj'     => texto($dados, 'cnpj', 18),
    'uf'       => strtoupper(texto($dados, 'state', 2)),
    'segmento' => texto($dados, 'segment', 120),
    'volume'   => texto($dados, 'volume', 120),
    'prazo'    => texto($dados, 'timeline', 120),
    'assunto'  => texto($dados, 'topic', 160),
    'campanha' => texto($dados, 'campaign', 120),
    'pagina'   => texto($dados, 'page', 500),
    'gclid'    => texto($dados, 'gclid', 255),
];

// Só o tipo é obrigatório. Os demais campos são opcionais, mas e-mail e telefone precisam ter
// formato válido quando vierem (o telefone aceita código de país: até 15 dígitos).
$erros = [];
if (!in_array($lead['tipo'], ['PF', 'PJ'], true)) {
    $erros['type'] = 'Tipo inválido.';
}
$digitos = strlen((string) preg_replace('/\D/', '', $lead['telefone']));
if ($lead['telefone'] !== '' && ($digitos < 8 || $digitos > 15)) {
    $erros['phone'] = 'Telefone inválido.';
}
if ($lead['email'] !== '' && filter_var($lead['email'], FILTER_VALIDATE_EMAIL) === false) {
    $erros['email'] = 'E-mail inválido.';
}
$consentimento = ($dados['consent'] ?? false) === true ? 1 : 0;
if ($erros) {
    responder(422, ['ok' => false, 'erros' => $erros]);
}

try {
    $pdo = new PDO(
        'mysql:host=' . $config['host'] . ';dbname=' . $config['banco'] . ';charset=utf8mb4',
        $config['usuario'],
        $config['senha'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]
    );

    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

    $recentes = $pdo->prepare('SELECT COUNT(*) FROM leads WHERE ip = ? AND criado_em > (NOW() - INTERVAL 1 HOUR)');
    $recentes->execute([$ip]);
    if ((int) $recentes->fetchColumn() >= (int) $config['limite_por_hora']) {
        responder(429, ['ok' => false, 'erro' => 'Muitos envios. Tente novamente mais tarde.']);
    }

    $inserir = $pdo->prepare(
        'INSERT INTO leads (tipo, nome, cidade, telefone, email, empresa, cargo, cnpj, uf, segmento, volume, prazo,
                            assunto, campanha, pagina, gclid, consentimento, ip)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    // Nome, cidade, telefone e e-mail são NOT NULL na tabela: quando não vêm, ficam como texto vazio.
    $inserir->execute([
        $lead['tipo'], $lead['nome'], $lead['cidade'], $lead['telefone'], $lead['email'],
        ouNulo($lead['empresa']), ouNulo($lead['cargo']), ouNulo($lead['cnpj']), ouNulo($lead['uf']),
        ouNulo($lead['segmento']), ouNulo($lead['volume']), ouNulo($lead['prazo']),
        ouNulo($lead['assunto']), ouNulo($lead['campanha']), ouNulo($lead['pagina']), ouNulo($lead['gclid']),
        $consentimento, $ip,
    ]);
    $id = (int) $pdo->lastInsertId();
} catch (Throwable $erro) {
    // Só o tipo do erro vai para o log, para não registrar dados pessoais.
    error_log('[lead] falha ao gravar: ' . get_class($erro));
    responder(500, ['ok' => false, 'erro' => ERRO_GENERICO]);
}

// Daqui em diante o lead já está salvo: nenhuma falha pode virar erro para o visitante.
try {
    if (enviarAviso($config, $lead)) {
        $pdo->prepare('UPDATE leads SET email_enviado = 1 WHERE id = ?')->execute([$id]);
    } else {
        error_log('[lead] aviso por e-mail não aceito pelo servidor, id ' . $id);
    }
} catch (Throwable $erro) {
    error_log('[lead] falha no aviso, id ' . $id . ': ' . get_class($erro));
}

responder(201, ['ok' => true]);
