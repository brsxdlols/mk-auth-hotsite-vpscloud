# Hotsite VPS CLOUD para MK-Auth

Tema moderno, responsivo e reutilizável para o hotsite público do MK-Auth.

## Instalação

Execute como `root`:

```sh
curl -fsSL https://raw.githubusercontent.com/brsxdlols/mk-auth-hotsite-vpscloud/main/install.sh | sh
```

O instalador cria backup em `/opt/mk-auth/backups/vpscloud-hotsite`, preserva os demais temas, instala os arquivos dinâmicos e seleciona `vpscloud` como tema principal. Reinstalações preservam o modo de cadastro escolhido.

## Modo de cadastro

```sh
vpscloud-cadastro-modo whatsapp
vpscloud-cadastro-modo sistema
```

O modo sistema registra uma solicitação aberta em `sis_solic`; ele não cria nem ativa automaticamente um cliente.

## Portabilidade

- Logo relativa em `/mkfiles/logo.jpg`.
- Nome, contatos e planos lidos do banco local do MK-Auth.
- Sem IP, domínio, senha SSH ou credenciais fixas.
- Layout responsivo para desktop e mobile.
