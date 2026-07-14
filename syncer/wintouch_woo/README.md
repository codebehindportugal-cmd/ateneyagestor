🧾 Wintouch Woo Sync
Este projeto sincroniza encomendas do WooCommerce para o sistema Wintouch ERP.

📦 Funcionalidades
Sincroniza encomendas com estado "processing" do WooCommerce para o Wintouch.

Adiciona uma linha de envio como produto no documento.

Marca a encomenda como sincronizada após sucesso.

Gera logs locais e envia por email.

Apaga logs com mais de 30 dias automaticamente.

🚀 Instalação
Clonar o repositório

bash
Copiar
Editar
git clone https://seurepositorio.com/wintouch_woo_sync.git
cd wintouch_woo_sync
Criar e ativar ambiente virtual

bash
Copiar
Editar
python3 -m venv venv
source venv/bin/activate
Instalar dependências

bash
Copiar
Editar
pip install -r requirements.txt
⚙️ Configuração
Edite o ficheiro config.yaml com os dados de acesso ao WooCommerce, Wintouch e SMTP para envio de logs:

yaml
Copiar
Editar
woocommerce:
  base_url: "https://seusite.com"
  consumer_key: "ck_..."
  consumer_secret: "cs_..."

wintouch:
  base_url: "https://api.wintouchcloud.com"
  api_key: "..."
  login_email: "..."
  login_password: "..."

sync:
  batch_size: 50
  default_currency: "EUR"
  download_images: true

logging:
  level: "INFO"
  file: "logs/sync-%Y%m%d.log"

smtp:
  host: "smtp.gmail.com"
  port: 587
  username: "seu@email.com"
  password: "suapalavra-chave"
  from: "seu@email.com"
  to: ["destinatario@email.com"]
  subject: "Logs Wintouch Woo Sync"
🔐 Não comite dados sensíveis! Use .env ou variáveis de ambiente para ambientes de produção.

▶️ Utilização
Para sincronizar encomendas:

bash
Copiar
Editar
python main.py
Este comando irá:

Ligar ao WooCommerce

Obter encomendas pendentes

Criar documentos no Wintouch

Marcar as encomendas como sincronizadas

📝 Logs
Os logs são guardados em logs/wintouch_sync.log e também enviados por email após cada execução.

Um mecanismo automático apaga os logs com mais de 30 dias.

📂 Estrutura do Projeto
bash
Copiar
Editar
wintouch_woo_sync/
│
├── logs/                     # Ficheiros de log
├── main.py                  # Script principal
├── config.yaml              # Configuração da app
├── requirements.txt         # Dependências
└── src/
    └── wintouch_woocommerce_sync/
        ├── apis/            # Lógica para WooCommerce e Wintouch
        ├── config.py        # Carregamento da config
        ├── utils.py         # Logging e helpers
        └── sync.py          # Função de sincronização
💡 Notas
O produto de envio é representado por um ID fixo (1ae4bdc6-7f1d-4883-bd55-7b459ccfde2f).

Todos os valores são enviados ao Wintouch com IVA incluído.

Logs de erro são capturados com stack trace e enviados por email.

📬 Suporte
Em caso de dúvidas ou erros, contacte a equipa técnica ou abra uma issue neste repositório.

