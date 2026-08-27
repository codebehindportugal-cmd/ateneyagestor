using System;
using System.Collections.Generic;
using System.IO;
using System.Threading.Tasks;
using Newtonsoft.Json.Linq;
using RestSharp;

namespace LIBERNE
{
    /// <summary>
    /// Reporta o resultado de cada execução ao backup-manager (gestao.ateneya.com),
    /// no mesmo modelo dos reporters Python (wintouch_woo / phc_woo):
    ///   POST {BackupManagerUrl}/api/sync/runs  com  Authorization: Bearer {BackupManagerToken}
    ///
    /// Configuração no App.config (appSettings):
    ///   BackupManagerUrl   = https://gestao.ateneya.com
    ///   BackupManagerToken = token gerado no admin &gt; Sincronizadores &gt; "Gerar token"
    ///
    /// Sem URL/token configurados fica silencioso. Nunca lança exceções —
    /// o relatório é best-effort e não pode bloquear a sincronização.
    /// </summary>
    internal static class SyncReporter
    {
        private const int MAX_ITEMS = 2000;
        private const int MAX_LOG_CHARS = 60000;

        private static DateTime _startedAt = DateTime.MinValue;
        private static bool _started;                 // Start() foi chamado → há execução real a reportar
        private static long _runId;                   // run criado via /runs/start (0 = nenhum → usa endpoint legado)
        private static DateTime _lastProgress = DateTime.MinValue;
        private const int PROGRESS_MIN_SECONDS = 30;  // intervalo mínimo entre posts de progresso

        private static int _criados, _atualizados, _apagados, _saltados, _erros, _total;
        private static string _brand;
        private static bool _fullScan;
        private static bool _onlyPublishedWithStock;
        private static int _skuFilterCount;

        private static bool _failed;
        private static string _failMessage;
        private static int _extraErrors; // erros fora do loop de produtos (ex.: promoções)

        private static readonly List<Dictionary<string, object>> _items = new List<Dictionary<string, object>>();

        private static long _logStartOffset; // tamanho do ErrorLog no arranque → o relatório só leva o log DESTA execução

        public static void Start(string errorLogPath = null)
        {
            _startedAt = DateTime.Now;
            _started = true;

            // O ErrorLog é diário e partilhado por todas as execuções do dia.
            // Guardamos o offset atual para o relatório conter apenas o que
            // esta execução escreveu (antes ia o dia inteiro — confuso no painel).
            _logStartOffset = 0;
            try
            {
                if (!string.IsNullOrWhiteSpace(errorLogPath) && File.Exists(errorLogPath))
                    _logStartOffset = new FileInfo(errorLogPath).Length;
            }
            catch { }
        }

        private static bool TryGetConfig(out string url, out string token)
        {
            url = Program.Cfg("BackupManagerUrl").TrimEnd('/');
            token = Program.Cfg("BackupManagerToken");
            return !string.IsNullOrWhiteSpace(url) && !string.IsNullOrWhiteSpace(token);
        }

        /// <summary>
        /// GET /api/sync/should-run — usado pelo modo --check-remote: devolve true se
        /// alguém carregou em "Correr agora" no backup-manager. Em erro devolve false.
        /// </summary>
        public static async Task<bool> ShouldRunAsync()
        {
            try
            {
                string url, token;
                if (!TryGetConfig(out url, out token)) return false;

                var options = new RestClientOptions(url) { Timeout = TimeSpan.FromSeconds(15) };
                using (var client = new RestClient(options))
                {
                    var request = new RestRequest("api/sync/should-run", Method.Get);
                    request.AddHeader("Authorization", "Bearer " + token);
                    request.AddHeader("Accept", "application/json");

                    RestResponse response = await client.ExecuteAsync(request);
                    if (!response.IsSuccessful || string.IsNullOrWhiteSpace(response.Content))
                        return false;

                    JObject json = JObject.Parse(response.Content);
                    return json.Value<bool?>("run_requested") ?? false;
                }
            }
            catch
            {
                return false;
            }
        }

        /// <summary>
        /// POST /api/sync/runs/start — anuncia o arranque: o run aparece logo como
        /// "Em curso" no painel. Guarda o run_id para /progress e /finish.
        /// Best-effort: se falhar, o relatório final usa o endpoint legado.
        /// </summary>
        public static async Task StartRunAsync()
        {
            try
            {
                string url, token;
                if (!TryGetConfig(out url, out token)) return;

                var metadata = new Dictionary<string, object>
                {
                    { "syncer", "LV_LIBERNESync" },
                    { "site", Program.Cfg("SiteName", "lojaamster.com") }
                };

                var options = new RestClientOptions(url) { Timeout = TimeSpan.FromSeconds(15) };
                using (var client = new RestClient(options))
                {
                    var request = new RestRequest("api/sync/runs/start", Method.Post);
                    request.AddHeader("Authorization", "Bearer " + token);
                    request.AddHeader("Accept", "application/json");
                    request.AddJsonBody(new Dictionary<string, object> { { "metadata", metadata } });

                    RestResponse response = await client.ExecuteAsync(request);
                    if (response.IsSuccessful && !string.IsNullOrWhiteSpace(response.Content))
                    {
                        JObject json = JObject.Parse(response.Content);
                        _runId = json.Value<long?>("run_id") ?? 0;
                        if (_runId > 0)
                            Program.LogHelper.LogInfo("[REPORTER] Execução anunciada ao backup-manager (run " + _runId + " em curso).", true);
                    }
                    else
                    {
                        Program.LogHelper.LogError("[REPORTER] Falha ao anunciar início ao backup-manager: " +
                            response.StatusCode + " - " + (response.Content ?? ""));
                    }
                }
            }
            catch (Exception ex)
            {
                try { Program.LogHelper.LogError("[REPORTER] Aviso (start): " + ex.Message); } catch { }
            }
        }

        /// <summary>
        /// POST /api/sync/runs/{id}/progress — progresso periódico (máx. 1 post / 30s).
        /// </summary>
        public static async Task ProgressAsync(string stage, int processed, int total,
            int created, int updated, int deleted, int skipped, int errors)
        {
            try
            {
                if (_runId <= 0) return;
                if ((DateTime.Now - _lastProgress).TotalSeconds < PROGRESS_MIN_SECONDS) return;

                string url, token;
                if (!TryGetConfig(out url, out token)) return;

                _lastProgress = DateTime.Now;

                var payload = new Dictionary<string, object>
                {
                    { "processed", processed },
                    { "total", total },
                    { "stage", stage ?? "" },
                    { "counts", new Dictionary<string, object>
                        {
                            { "criados", created },
                            { "atualizados", updated },
                            { "apagados", deleted },
                            { "saltados", skipped },
                            { "erros", errors }
                        }
                    }
                };

                var options = new RestClientOptions(url) { Timeout = TimeSpan.FromSeconds(10) };
                using (var client = new RestClient(options))
                {
                    var request = new RestRequest("api/sync/runs/" + _runId + "/progress", Method.Post);
                    request.AddHeader("Authorization", "Bearer " + token);
                    request.AddHeader("Accept", "application/json");
                    request.AddJsonBody(payload);
                    await client.ExecuteAsync(request);
                }
            }
            catch
            {
                // progresso é opcional — nunca interrompe a sync
            }
        }

        public static void SetProductCounts(int criados, int atualizados, int apagados, int saltados, int erros, int total)
        {
            _criados = criados;
            _atualizados = atualizados;
            _apagados = apagados;
            _saltados = saltados;
            _erros = erros;
            _total = total;
        }

        public static void SetContext(string brand, bool fullScan, bool onlyPublishedWithStock, int skuFilterCount)
        {
            _brand = brand;
            _fullScan = fullScan;
            _onlyPublishedWithStock = onlyPublishedWithStock;
            _skuFilterCount = skuFilterCount;
        }

        public static void RecordItem(string sku, string tipo, string acao, string mensagem)
        {
            if (_items.Count >= MAX_ITEMS) return;
            _items.Add(new Dictionary<string, object>
            {
                { "sku", sku ?? "" },
                { "name", tipo ?? "" },
                { "action", acao ?? "" },
                { "message", mensagem ?? "" }
            });
        }

        /// <summary>Erro avulso fora dos contadores de produtos (ex.: promoções).</summary>
        public static void AddError(int n = 1)
        {
            _extraErrors += n;
        }

        public static void MarkFailed(Exception ex)
        {
            _failed = true;
            _failMessage = ex != null ? ex.ToString() : "Erro desconhecido";
            if (_failMessage.Length > 4000)
                _failMessage = _failMessage.Substring(0, 4000);
        }

        /// <summary>
        /// Envia o relatório. Chamar UMA vez, no finally do Main.
        /// errorLogPath: caminho do ErrorLog do dia (o conteúdo segue no campo "log").
        /// </summary>
        public static async Task SendAsync(string errorLogPath)
        {
            try
            {
                if (!_started)
                    return; // nada correu (ex.: --check-remote sem pedido pendente, ou lock ativo)

                string url, token;
                if (!TryGetConfig(out url, out token))
                    return; // não configurado → silencioso

                int errosTotais = _erros + _extraErrors;
                string status = _failed ? "failed" : (errosTotais > 0 ? "partial" : "success");

                string logText = null;
                try
                {
                    if (!string.IsNullOrWhiteSpace(errorLogPath) && File.Exists(errorLogPath))
                    {
                        logText = File.ReadAllText(errorLogPath);

                        // Apenas o que ESTA execução escreveu (o ficheiro é diário/partilhado)
                        if (_logStartOffset > 0 && _logStartOffset < logText.Length)
                            logText = logText.Substring((int)_logStartOffset);
                        if (string.IsNullOrWhiteSpace(logText))
                            logText = "(sem erros nesta execução)";

                        if (logText.Length > MAX_LOG_CHARS)
                            logText = "[...truncado...]\r\n" + logText.Substring(logText.Length - MAX_LOG_CHARS);
                    }
                }
                catch { /* log é opcional */ }

                var metadata = new Dictionary<string, object>
                {
                    { "syncer", "LV_LIBERNESync" },
                    { "site", Program.Cfg("SiteName", "lojaamster.com") },
                    { "brand_filter", _brand },
                    { "full_scan", _fullScan },
                    { "only_published_with_stock", _onlyPublishedWithStock },
                    { "sku_filter_count", _skuFilterCount },
                    { "counts", new Dictionary<string, object>
                        {
                            { "total", _total },
                            { "criados", _criados },
                            { "atualizados", _atualizados },
                            { "apagados", _apagados },
                            { "saltados", _saltados },
                            { "erros", _erros },
                            { "erros_promocoes", _extraErrors }
                        }
                    }
                };
                if (_items.Count > 0) metadata["items"] = _items;
                if (_failed) metadata["exception"] = _failMessage;

                DateTime startedAt = _startedAt == DateTime.MinValue ? DateTime.Now : _startedAt;

                var payload = new Dictionary<string, object>
                {
                    { "status", status },
                    { "products_synced", _criados + _atualizados + _apagados },
                    { "orders_synced", 0 },
                    { "errors_count", errosTotais },
                    { "started_at", startedAt.ToString("yyyy-MM-dd'T'HH:mm:ss") },
                    { "finished_at", DateTime.Now.ToString("yyyy-MM-dd'T'HH:mm:ss") },
                    { "log", logText },
                    { "metadata", metadata }
                };

                // Se o arranque foi anunciado (/runs/start), fecha esse run; senão usa o endpoint legado
                string endpoint = _runId > 0 ? "api/sync/runs/" + _runId + "/finish" : "api/sync/runs";

                var options = new RestClientOptions(url) { Timeout = TimeSpan.FromSeconds(15) };
                using (var client = new RestClient(options))
                {
                    var request = new RestRequest(endpoint, Method.Post);
                    request.AddHeader("Authorization", "Bearer " + token);
                    request.AddHeader("Accept", "application/json");
                    request.AddJsonBody(payload);

                    RestResponse response = await client.ExecuteAsync(request);
                    if (response.IsSuccessful)
                        Program.LogHelper.LogInfo("[REPORTER] Execução reportada ao backup-manager (" + status + ").");
                    else
                        Program.LogHelper.LogError("[REPORTER] Falha ao reportar ao backup-manager: " +
                            response.StatusCode + " - " + (response.Content ?? ""));
                }
            }
            catch (Exception ex)
            {
                // Best-effort: nunca deixa rebentar o sync por causa do relatório
                try { Program.LogHelper.LogError("[REPORTER] Aviso: " + ex.Message); }
                catch { Console.WriteLine("[REPORTER] Aviso: " + ex.Message); }
            }
        }
    }
}
