<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function fail_signup(string $message, int $status = 400): void {
    http_response_code($status);
    echo json_encode(['ok' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail_signup('Método não permitido.', 405);
if (!empty($_POST['website'] ?? '')) fail_signup('Solicitação inválida.');
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin && parse_url($origin, PHP_URL_HOST) !== ($_SERVER['HTTP_HOST'] ?? '')) fail_signup('Origem inválida.', 403);

$get = static function (string $key, int $max = 255): string {
    $value = trim((string)($_POST[$key] ?? ''));
    return mb_substr($value, 0, $max, 'UTF-8');
};
$required = ['plano','venc','login','senha','nome','email','documento','telefone','cep','endereco','numero','bairro','cidade','estado'];
foreach ($required as $field) if ($get($field) === '') fail_signup('Preencha todos os campos obrigatórios.');
if (!filter_var($get('email'), FILTER_VALIDATE_EMAIL)) fail_signup('Informe um e-mail válido.');
if (!preg_match('/^[A-Za-z0-9._-]{3,64}$/', $get('login', 64))) fail_signup('O login deve ter entre 3 e 64 caracteres, sem espaços.');
if (strlen($get('senha', 32)) < 4) fail_signup('A senha deve ter pelo menos 4 caracteres.');
if (!preg_match('/^[A-Z]{2}$/', $get('estado', 2))) fail_signup('Selecione um estado válido.');

$ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? ($_SERVER['REMOTE_ADDR'] ?? ''))[0]);
$rateFile = sys_get_temp_dir() . '/vpscloud-signup-' . hash('sha256', $ip) . '.txt';
$last = is_file($rateFile) ? (int)file_get_contents($rateFile) : 0;
if ($last && time() - $last < 30) fail_signup('Aguarde alguns segundos antes de enviar novamente.', 429);

require '/opt/mk-auth/include/conexao.php';
$dryRun = PHP_SAPI === 'cli' && getenv('VPSCLOUD_SIGNUP_DRY_RUN') === '1';
if ($dryRun) $LOADMYSQL->begin_transaction();
$plan = $get('plano');
$stmt = $LOADMYSQL->prepare("SELECT nome FROM sis_plano WHERE nome=? AND COALESCE(oculto,'nao') <> 'sim' LIMIT 1");
$stmt->bind_param('s', $plan); $stmt->execute();
if (!$stmt->get_result()->fetch_assoc()) fail_signup('O plano selecionado não está disponível.');
$login = $get('login', 64);
$stmt = $LOADMYSQL->prepare("SELECT 1 FROM sis_cliente WHERE login=? UNION SELECT 1 FROM sis_solic WHERE login=? AND status IN ('aberto','pendente') LIMIT 1");
$stmt->bind_param('ss', $login, $login); $stmt->execute();
if ($stmt->get_result()->fetch_row()) fail_signup('Este login já está em uso. Escolha outro.');

$uuid = sprintf('%08s-%04s-%04s-%04s-%012s', bin2hex(random_bytes(4)), bin2hex(random_bytes(2)), bin2hex(random_bytes(2)), bin2hex(random_bytes(2)), bin2hex(random_bytes(6)));
$contract = bin2hex(random_bytes(16));
$obsParts = array_filter(['Referência: '.$get('referencia'), $get('mensagem', 2000)]);
$obs = implode("\n", $obsParts);
$sql = "INSERT INTO sis_solic (uuid_solic,login,senha,email,nome,data_nasc,cpf,endereco,numero,bairro,cidade,estado,cep,telefone,vencimento,plano,complemento,rg,celular,obs,tipo,ipcadastro,status,concluido,visitado,instalado,promocod,contrato,endereco_res,numero_res,bairro_res,cidade_res,cep_res,estado_res,complemento_res) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'aberto','nao','nao','nao',?,?,?,?,?,?,?,?,?)";
$stmt = $LOADMYSQL->prepare($sql);
if (!$stmt) fail_signup('Não foi possível preparar o cadastro.'.($dryRun ? ' '.$LOADMYSQL->error : ''), 500);
$senha=$get('senha',32);$email=$get('email');$nome=$get('nome');$nasc=$get('nascimento',20);$cpf=$get('documento',20);$end=$get('endereco');$numero=$get('numero',20);$bairro=$get('bairro');$cidade=$get('cidade');$estado=$get('estado',2);$cep=$get('cep',20);$fone=$get('fone',50);$venc=$get('venc',2);$comp=$get('complemento');$rg=$get('rg');$cel=$get('telefone',50);$tipo='assinatura';$promo=$get('promocional',50) ?: 'nao';
$stmt->bind_param(str_repeat('s', 31),$uuid,$login,$senha,$email,$nome,$nasc,$cpf,$end,$numero,$bairro,$cidade,$estado,$cep,$fone,$venc,$plan,$comp,$rg,$cel,$obs,$tipo,$ip,$promo,$contract,$end,$numero,$bairro,$cidade,$cep,$estado,$comp);
if (!$stmt->execute()) fail_signup('Não foi possível registrar a solicitação.', 500);
$newId = (int)$LOADMYSQL->insert_id;
if ($dryRun) $LOADMYSQL->rollback();
@file_put_contents($rateFile, (string)time(), LOCK_EX);
echo json_encode(['ok'=>true,'id'=>$newId,'validation'=>$dryRun], JSON_UNESCAPED_UNICODE);
