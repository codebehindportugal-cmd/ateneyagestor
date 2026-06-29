# Gestao Ateneya - Monitor de Produtividade

Agente Windows transparente para computadores de trabalho da empresa.

## O que recolhe

- Aplicacao/processo ativo.
- Tempo ativo/inativo.
- Heartbeat tecnico para confirmar que o computador esta a comunicar.

## O que nao recolhe

- Teclas digitadas.
- Screenshots.
- Passwords.
- Mensagens privadas.
- Conteudo de paginas.
- Ficheiros pessoais.

## Gerar instalador

1. Abrir `Gestao Ateneya > Agentes`.
2. Criar um agente do tipo `Computador - produtividade`.
3. Preencher computador, pessoa que utiliza, departamento e horario.
4. Guardar.
5. Clicar em `Download instalador`.

O ZIP gerado ja inclui `config.json` com URL, chave API, identificador do computador e politica de recolha.

## Instalar no computador

1. Extrair o ZIP no computador da empresa.
2. Executar `install.bat`.
3. O monitor inicia minimizado no proximo inicio de sessao do Windows.
4. Se alguem clicar no `X`, a janela minimiza e o monitor continua ativo.

Nota: a recolha de aplicacoes e sites precisa de uma sessao Windows iniciada. Por isso o arranque correto e `ONLOGON`, nao antes do utilizador entrar.

Para iniciar imediatamente:

```bat
run-minimized.bat
```

Para abrir a janela de estado:

```bat
run-visible.bat
```

## Verificar

Na plataforma, consultar:

- `Agentes`: ultimo contacto e estado online/offline.
- `Produtividade`: eventos recebidos por computador.

## Remover

```bat
uninstall.bat
```
