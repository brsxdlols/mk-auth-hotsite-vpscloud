<?php
// Temporary tables shadow native tables only inside this CLI connection.
if (PHP_SAPI !== 'cli') exit;
require __DIR__.'/../theme/root/vpscloud-db.php';
$db = vpscloud_db();
function check($ok, $label) { if (!$ok) throw new RuntimeException($label); echo "PASS ".$label."\n"; }
function sql($db, $sql) { if (!$db->query($sql)) throw new RuntimeException($db->error); }
sql($db, "CREATE TEMPORARY TABLE sis_provedor (nome TEXT, razao TEXT, fone TEXT, celular TEXT, email TEXT, whatsapp TEXT)");
sql($db, "CREATE TEMPORARY TABLE sis_plano (nome TEXT, valor TEXT, descricao TEXT, oculto TEXT)");
sql($db, "INSERT INTO sis_provedor VALUES ('','Provedor Teste','1133334444','','teste@example.invalid',NULL)");
sql($db, "INSERT INTO sis_plano VALUES ('Plano A','99.90','Fibra','nao'),('Plano B','1.299,90',NULL,NULL),('Oculto','1','Interno',' SIM '),('Oculto booleano','2','','1')");
$data = vpscloud_data($db);
check($data['empresa']['nome'] === 'Provedor Teste', 'nome vazio usa razão social');
check(count($data['planos']) === 2, 'ocultos respeitados, null visível');
check($data['planos'][0]['valor'] === 99.9 && $data['planos'][1]['valor'] === 1299.9, 'valores decimal e brasileiro');
check($data['planos'][1]['descricao'] === '', 'descrição nula');
sql($db, "UPDATE sis_plano SET oculto='sim'");
check(count(vpscloud_data($db)['planos']) === 0, 'nenhum fallback expõe planos ocultos');
sql($db, "DROP TEMPORARY TABLE sis_provedor");
sql($db, "DROP TEMPORARY TABLE sis_plano");
sql($db, "CREATE TEMPORARY TABLE sis_provedor (razao TEXT, telefone TEXT)");
sql($db, "INSERT INTO sis_provedor VALUES ('Provedor Legado','1199999999')");
sql($db, "CREATE TEMPORARY TABLE sis_plano (nome TEXT, valor TEXT)");
for ($i=0; $i<25; $i++) sql($db, "INSERT INTO sis_plano VALUES ('Plano ".$i."','10')");
$data = vpscloud_data($db);
check($data['empresa']['nome'] === 'Provedor Legado' && $data['empresa']['fone'] === '1199999999', 'campos alternativos');
check($data['empresa']['whatsapp'] === '' && $data['empresa']['email'] === '', 'campos opcionais ausentes');
check(count($data['planos']) === 25, 'todos os planos, sem limite artificial de 18');
check($data['planos'][0]['descricao'] === '', 'schema sem descrição e sem oculto');
sql($db, "DROP TEMPORARY TABLE sis_plano");
sql($db, "CREATE TEMPORARY TABLE sis_plano (nome TEXT)");
$failed = false;
try { vpscloud_data($db); } catch (RuntimeException $e) { $failed = true; }
check($failed, 'schema sem preço falha explicitamente');
