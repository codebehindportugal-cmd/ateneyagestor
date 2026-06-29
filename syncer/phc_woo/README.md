# PHC -> WooCommerce Sync

Pacote gerado pelo Backup Manager para instalar no servidor do cliente.

## Instalar

1. Extrair este ZIP numa pasta do cliente, por exemplo `C:\syncers\phc_woo`.
2. Instalar Python 3.11+.
3. Abrir PowerShell nessa pasta.
4. Criar ambiente virtual:

```powershell
python -m venv .venv
.\.venv\Scripts\python.exe -m pip install -r requirements.txt
```

## Configuracao

O ficheiro `.env` ja vem preenchido pelo painel com:

- URL e token do Backup Manager
- URL e chaves WooCommerce
- URL/chave/utilizador PHC
- base de dados e empresa PHC

Confirma apenas os valores que dependem da maquina do cliente.

## Executar

```powershell
run-sync.bat
```

Ou diretamente:

```powershell
.\.venv\Scripts\python.exe main.py
```

## Nota PHC

O ficheiro `src/phc_client.py` tem o ponto de ligacao ao PHC. Se o cliente usar SQL direto, API propria ou exportacao por ficheiro, adapta apenas esse ficheiro e mantem o resto do pacote igual.
