# Hotsite VPS CLOUD para MK-Auth

Tema responsivo para o hotsite público do MK-Auth, com dados do banco local.

## Instalação ou reparação

Execute como root:

```sh
curl -fsSL https://raw.githubusercontent.com/brsxdlols/mk-auth-hotsite-vpscloud/main/install.sh | sh
```

O mesmo comando instala, atualiza e repõe arquivos ausentes, incluindo o endpoint dos planos. Preserva o modo de cadastro escolhido e faz backup dos arquivos substituídos em `/opt/mk-auth/backups/vpscloud-hotsite`.

Antes de ativar o tema, o instalador verifica o pacote, PHP, conexão local, leitura dos planos e resposta JSON pelo servidor web. Se ocorrer uma falha após começar a alteração, restaura o backup e termina com erro. Não declara sucesso apenas por copiar o layout.

## Compatibilidade automática dos dados

- Usa a conexão nativa em `/opt/mk-auth/include/conexao.php`, sem credenciais fixas.
- Descobre as colunas existentes em `sis_provedor` e `sis_plano`.
- Nome usa `nome` ou `razao`; telefone usa `fone` ou `telefone`.
- Ausência de WhatsApp, celular, e-mail ou descrição não invalida a consulta.
- Lista todos os planos visíveis, ordenados pelo valor, sem o antigo limite de 18.
- Aceita valores como `99.90`, `99,90` e `1.299,90`.
- Respeita `oculto` quando presente; nunca revela planos ocultos como alternativa a uma lista vazia.
- Logo relativa em `/mkfiles/logo.jpg`; os links usam os dados de cada provedor.
- Falha de conexão aparece como erro de carregamento, distinta de ausência de planos.

Requer PHP 7.3+ com mysqli no terminal e no servidor web, banco nativo com as tabelas acima e campos de nome/valor do plano. A seleção de tema usa `sis_opcao(nome,valor)`. Instalações com estruturas diferentes dessas recebem erro explícito; não se presume compatibilidade com toda versão já lançada do MK-Auth.

O diretório padrão é `/var/www`. Em instalações personalizadas, `VPSCLOUD_WEBROOT` pode indicar um subdiretório de `/var/www`; `VPSCLOUD_CHECK_URL` deve indicar a URL local que serve esse diretório. `VPSCLOUD_PHP` permite indicar outro executável PHP. O instalador não altera a configuração global do PHP ou do Apache.

## Modo de cadastro

```sh
vpscloud-cadastro-modo whatsapp
vpscloud-cadastro-modo sistema
```

O modo sistema registra uma solicitação aberta em `sis_solic`; não cria nem ativa automaticamente um cliente. Esse modo requer os campos nativos utilizados pelo formulário. A adaptação de campos opcionais descrita acima se refere à leitura do provedor e dos planos.

## Validação

`php tests/compatibility.php` testa campos opcionais ausentes, nome/telefone alternativos, planos ocultos, valores e mais de 18 planos. Usa tabelas temporárias restritas à conexão de teste, sem modificar tabelas persistentes.

Correção de setembro de 2026 validada em PHP 8.0 com sete planos reais, resposta HTTP e renderização no navegador. Reinstalação preserva a configuração existente.
