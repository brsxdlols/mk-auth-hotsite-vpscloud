# Hotsite VPS CLOUD para MK-Auth

Tema responsivo para o hotsite público do MK-Auth, com dados do banco local.

## Instalação ou atualização

Execute como root:

```sh
curl -fsSL https://raw.githubusercontent.com/brsxdlols/mk-auth-hotsite-vpscloud/main/install.sh | sh
```

Instalações novas e migrações do antigo tema `vpscloud` selecionam **layout-vpscloud-sistema**. Atualizações preservam uma escolha já feita entre os dois novos layouts.

## Escolha do cadastro dentro do MK-Auth

Abra **Hotsite → Layout**, selecione o tema e salve:

| Layout | Destino de “Selecionar plano” |
| --- | --- |
| `layout-vpscloud-sistema` | Formulário que envia uma solicitação ao sistema do provedor. |
| `layout-vpscloud-whatsapp` | Formulário que prepara o contato pelo WhatsApp do provedor. |

Os dois layouts têm o mesmo visual e os mesmos planos. O endpoint lê a opção nativa `sis_opcao.layhotsite` a cada consulta; essa escolha tem prioridade sobre o arquivo de configuração antigo. Não é necessário executar comandos SSH para alternar.

O cadastro pelo sistema registra uma solicitação aberta em `sis_solic` para análise. Não cria nem ativa automaticamente um cliente. A tabela precisa conter os campos nativos utilizados pelo formulário.

O antigo layout `vpscloud` permanece disponível por compatibilidade. Seu arquivo de configuração continua sendo usado apenas como alternativa quando nenhum dos dois novos layouts está selecionado.

Para administração por terminal, estes comandos selecionam os mesmos layouts no banco e atualizam a página inicial:

```sh
vpscloud-cadastro-modo sistema
vpscloud-cadastro-modo whatsapp
```

## Instalação verificada

O instalador repõe arquivos ausentes e salva backup dos arquivos substituídos em `/opt/mk-auth/backups/vpscloud-hotsite`.

Antes de ativar o tema, verifica pacote, PHP, conexão local, leitura dos planos e resposta JSON pelo servidor web. Depois de ativar, confirma que o modo retornado corresponde ao layout selecionado. Uma falha após começar a alteração restaura o backup e termina com erro.

## Compatibilidade dos dados

- Conexão nativa em `/opt/mk-auth/include/conexao.php`, sem credenciais fixas.
- Descobre colunas existentes em `sis_provedor` e `sis_plano`.
- Nome usa `nome` ou `razao`; telefone usa `fone` ou `telefone`.
- Campos opcionais ausentes não invalidam a consulta.
- Lista todos os planos visíveis e respeita `oculto` quando presente.
- Aceita valores como `99.90`, `99,90` e `1.299,90`.
- Logo relativa em `/mkfiles/logo.jpg` e contatos de cada provedor.
- Erro de carregamento é diferente de ausência de planos.

Requer PHP 7.3+ com mysqli no terminal e no servidor web, tabelas nativas acima e campos de nome/valor do plano. A seleção de tema usa `sis_opcao(nome,valor)`. Estruturas incompatíveis recebem erro explícito.

O diretório padrão é `/var/www`. `VPSCLOUD_WEBROOT` permite um subdiretório de `/var/www`; `VPSCLOUD_CHECK_URL` deve apontar para a URL local que serve esse diretório. `VPSCLOUD_PHP` permite indicar outro executável PHP.

## Validação

- `php tests/compatibility.php`: campos opcionais, valores, planos ocultos e mais de 18 planos.
- `php tests/layouts.php`: modo padrão, migração, precedência da seleção nativa e preservação da escolha em atualizações.

Os testes usam tabelas temporárias restritas à conexão de teste, sem modificar tabelas persistentes.
