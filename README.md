# Hotsite VPS CLOUD para MK-Auth

Tema responsivo e reutilizável para o hotsite público do MK-Auth. Preserva os temas existentes, usa `/mkfiles/logo.jpg` e sincroniza automaticamente provedor e planos do banco local.

## Instalação

Execute como `root`:

```sh
curl -fsSL https://raw.githubusercontent.com/brsxdlols/mk-auth-hotsite-vpscloud/main/install.sh | sh
```

O instalador cria backup em `/opt/mk-auth/backups/vpscloud-hotsite`, instala os diretórios `layout/vpscloud`, `midias_vpscloud` e `vpscloud-api`, configura a sincronização local e seleciona `layhotsite=vpscloud`.

É idempotente: uma reinstalação preserva a configuração local e cria um novo backup antes de atualizar os arquivos.

## Segurança e portabilidade

- Não contém IP, domínio de teste, SSH ou senha do banco do MK-Auth.
- Lê a conexão da instalação local em `/opt/mk-auth/include/conexao.php`.
- O sincronizador usa a conexão oficial do MK-Auth somente pelo PHP CLI local.
- Publica apenas nome, contatos, redes e planos em um cache JSON sem credenciais.
- Atualiza os dados automaticamente a cada cinco minutos.
