using System;
using System.Collections.Generic;
using System.Text;
using System.Threading.Tasks;
using System.Net.Http;
using Newtonsoft.Json;
using Newtonsoft.Json.Linq;
using System.Data;
using System.IO;
using ErpBS100;
using AdmBS100;
using StdBE100;
using WooCommerceNET;
using VariationAttribute = WooCommerceNET.WooCommerce.v3.VariationAttribute;
using Variation = WooCommerceNET.WooCommerce.v3.Variation;
using WCObject = WooCommerceNET.WooCommerce.v3.WCObject;
using System.Linq;
using WCv3 = WooCommerceNET.WooCommerce.v3;
using WooCommerceNET.WooCommerce.v2;
using Product = WooCommerceNET.WooCommerce.v3.Product;
using RestSharp;
using System.Threading;
using System.Net;
using System.Net.Mail;
using System.Diagnostics;
using System.Text.RegularExpressions;


namespace LIBERNE
{
    class Program
    {
        #region Variáveis Globais
        // Configuração (App.config → <appSettings>). Sem segredos no código-fonte.
        internal static string Cfg(string key, string fallback = "")
        {
            string v = System.Configuration.ConfigurationManager.AppSettings[key];
            return string.IsNullOrWhiteSpace(v) ? fallback : v;
        }

        public static string wc_key = Cfg("WooKey");
        public static string wc_secret = Cfg("WooSecret");
        static readonly string wooApiUrl = Cfg("WooBaseUrl", "https://lojaamster.com/wp-json/wc/v3/");
        static protected RestAPI restAPI = new RestAPI(wooApiUrl, wc_key, wc_secret);
        // Endpoints custom
        static readonly string wpBase = Cfg("WpBaseUrl", "https://lojaamster.com");
        static readonly string credentialsAdmin = Convert.ToBase64String(Encoding.UTF8.GetBytes(Cfg("WpAdminUser") + ":" + Cfg("WpAdminAppPassword")));

        static List<ExecRow> ExecLog = new List<ExecRow>();
        static string _lockFilePath;

        // Estado da sincronização para o /progress
        static DateTime _syncStartedAt;
        static string _currentBrand;
        static bool _fullScan;
        static bool _onlyPublishedWithStock;
        static HashSet<string> _skuFilterList = null;

        // Cache global SKU → entry (product / product_variation)
        static readonly Dictionary<string, SkuEntry> _skuMap =
            new Dictionary<string, SkuEntry>(StringComparer.OrdinalIgnoreCase);

        // Cache por pai → variações (SKU → Variation)
        static readonly Dictionary<ulong, Dictionary<string, Variation>> _parentVarCache =
            new Dictionary<ulong, Dictionary<string, Variation>>();
        #endregion

        #region DTOs (WP sku-map)
        class SkuEntry
        {
            public ulong id { get; set; }
            public string sku { get; set; }
            public string type { get; set; } // "product" | "product_variation"
            public ulong parent_id { get; set; } // 0 se product
        }

        public class SkuFilterResponse
        {
            [JsonProperty("raw")]
            public string Raw { get; set; }

            [JsonProperty("items")]
            public List<string> Items { get; set; }

            [JsonProperty("count")]
            public int Count { get; set; }

            [JsonProperty("updated_at")]
            public string UpdatedAt { get; set; }
        }
        class SkuMapPage
        {
            public int total { get; set; }
            public int per_page { get; set; }
            public int page { get; set; }
            public string generated_at { get; set; }
            public List<SkuEntry> items { get; set; }
        }
        public class WooProductAttribute
        {
            public int id { get; set; }
            public string name { get; set; }
            public string slug { get; set; }
            public string type { get; set; }          // select, etc.
            public bool variation { get; set; }       // se é usado para variações
            public bool visible { get; set; }
        }

        public class WooProductAttributeTerm
        {
            public int id { get; set; }
            public string name { get; set; }
            public string slug { get; set; }
        }
        #endregion

        #region Ponto de Entrada
        static async Task Main(string[] args)
        {
            AppDomain.CurrentDomain.AssemblyResolve += CurrentDomain_AssemblyResolve;

            bool lockAcquired = false;
            string errorLogPath = null;

            try
            {
                // 1) baseDir
                string baseDir = AppDomain.CurrentDomain.BaseDirectory ?? AppDomain.CurrentDomain.FriendlyName;
                if (string.IsNullOrWhiteSpace(baseDir))
                    baseDir = Directory.GetCurrentDirectory();

                // 2) logsDirectory
                string logsDirectory = Path.Combine(baseDir, "logs");
                Directory.CreateDirectory(logsDirectory);

                // 3) limpar logs > 15 dias
                PurgeOldLogs(logsDirectory, 15);

                // 4) paths (hoje)
                string logPath = Path.Combine(logsDirectory, string.Format("Log_{0:yyyy_MM_dd}.txt", DateTime.Now));
                errorLogPath = Path.Combine(logsDirectory, string.Format("ErrorLog_{0:yyyy_MM_dd}.txt", DateTime.Now));
                LogHelper.Initialize(logPath, errorLogPath);

                // 4b) modo --check-remote: agendado a cada X minutos no cliente; só avança
                // se alguém tiver carregado em "Correr agora" no backup-manager.
                // Sem pedido pendente sai imediatamente, sem tocar no ERP nem no site.
                if (args != null && args.Any(a => string.Equals(a, "--check-remote", StringComparison.OrdinalIgnoreCase)))
                {
                    if (!await SyncReporter.ShouldRunAsync())
                        return;

                    LogHelper.LogInfo("[REMOTE] Pedido 'Correr agora' recebido do backup-manager — a iniciar sincronização.", true);
                }

                // 5) lockfile: se já houver uma execução a decorrer, aborta
                var locksDir = Path.Combine(baseDir, "locks");
                Directory.CreateDirectory(locksDir);
                _lockFilePath = Path.Combine(locksDir, "sync.lock");

                if (IsAnotherInstanceRunning(_lockFilePath))
                {
                    LogHelper.LogError("[LOCK] Já existe outra execução do sincronizador a decorrer (locks/sync.lock). Esta execução vai terminar sem fazer nada.");
                    Environment.ExitCode = 3;
                    return;
                }

                try
                {
                    var pid = Process.GetCurrentProcess().Id;
                    File.WriteAllText(_lockFilePath, string.Format("PID={0}; START={1:yyyy-MM-dd HH:mm:ss}", pid, DateTime.Now));
                    lockAcquired = true;
                }
                catch (Exception ex)
                {
                    Console.WriteLine("[WARN] Falha ao escrever lockfile: " + ex.Message);
                }

                // 5b) relatório para o backup-manager (best-effort; silencioso sem config)
                SyncReporter.Start(errorLogPath);
                await SyncReporter.StartRunAsync();

                // 6) manutenção
                await WaitIfInMaintenance("startup");

                // 7) WC client
                WCObject wc = new WCObject(restAPI);

                // 8) Construir mapa de SKUs (rápido e 1–2 chamadas)
                await BuildSkuMapAsync();

                // 9) run
                string produtosCsv, promocoesCsv, execCsv, problemasCsv;
                (produtosCsv, promocoesCsv, execCsv, problemasCsv) = await IniciarAplicacao(wc);

                Console.WriteLine("Processamento concluído com sucesso!");

                // 10) enviar apenas ficheiros desta execução (LOG NORMAL → noreplay@ateneya.com)
                var anexos = ColetarAnexosSomenteDaExecucao(logPath, errorLogPath, produtosCsv, promocoesCsv, execCsv, problemasCsv);
                EmailHelper.SendSyncLogEmail(Cfg("SiteName", "lojaamster.com"), DateTime.Now, anexos);

                // 11) Se houver problemas (órfãos, erros, saltados, apagados por pai anulado) → email para geral@lojaamster.com
                if (!string.IsNullOrWhiteSpace(problemasCsv) && File.Exists(problemasCsv))
                {
                    EmailHelper.SendProblemsEmail(Cfg("SiteName", "lojaamster.com"), DateTime.Now, problemasCsv);
                }
            }
            catch (Exception ex)
            {
                LogHelper.LogException("A aplicação terminou com erro no Main()", ex);
                SyncReporter.MarkFailed(ex);
                Environment.ExitCode = 1;
            }
            finally
            {
                // Relatório final ao backup-manager (nunca lança; silencioso sem config)
                await SyncReporter.SendAsync(errorLogPath);

                if (lockAcquired)
                {
                    try { File.Delete(_lockFilePath); }
                    catch (Exception ex) { Console.WriteLine("[WARN] Falha ao apagar lockfile: " + ex.Message); }
                }
            }
        }

        /// <summary>
        /// Verifica se o lockfile pertence a um processo deste sincronizador ainda vivo.
        /// Lock órfão (processo morto) é ignorado e será substituído.
        /// </summary>
        static bool IsAnotherInstanceRunning(string lockPath)
        {
            try
            {
                if (!File.Exists(lockPath)) return false;

                string txt = File.ReadAllText(lockPath);
                Match m = Regex.Match(txt ?? "", @"PID=(\d+)");
                if (!m.Success) return false;

                int pid;
                if (!int.TryParse(m.Groups[1].Value, out pid)) return false;
                if (pid == Process.GetCurrentProcess().Id) return false;

                try
                {
                    Process proc = Process.GetProcessById(pid);
                    string myName = Process.GetCurrentProcess().ProcessName;
                    return string.Equals(proc.ProcessName, myName, StringComparison.OrdinalIgnoreCase);
                }
                catch (ArgumentException)
                {
                    return false; // processo já não existe → lock órfão
                }
            }
            catch
            {
                return false;
            }
        }
        #endregion

        #region Inicialização
        static async Task<(string produtosCsv, string promocoesCsv, string execCsv, string problemasCsv)> IniciarAplicacao(WCObject wc)
        {
            ErpBS erpBS = InicializarERP();
            string empresa = Properties.Settings.Default.EmpPrincipal;
            string user = Properties.Settings.Default.UserPrimavera;
            string password = Properties.Settings.Default.PwdPrimavera;

            string produtosCsv = null, promocoesCsv = null, execCsv = null, problemasCsv = null;

            try
            {
                AbrirEmpresaPrimavera(erpBS, empresa, user, password);

                // 1) ler flag "forçar todos" (já faz reset interno para 0 se vier a 1)
                bool forcarTodos = await VerificarFlagWordPress();

                // 1b) ler flag only-published-stock (e resetar para 0 logo a seguir)
                _onlyPublishedWithStock = await ObterOnlyPublishedStockWordPress();
                if (_onlyPublishedWithStock)
                {
                    LogHelper.LogInfo("[ONLY-PUBLISHED-STOCK] Ativo: só vão ser considerados artigos com stock > 0.", true);
                }

                // se only-published-stock estiver ativo, faz sentido forçar full scan
                if (_onlyPublishedWithStock && !forcarTodos)
                {
                    forcarTodos = true;
                    LogHelper.LogInfo("[ONLY-PUBLISHED-STOCK] Full scan forçado: a data de última sincronização será ignorada.", true);
                }

                // 2) ler MARCA do WP (e limpar no WP logo a seguir)
                string marcaFiltro = await ObterMarcaFiltroWordPress();
                if (!string.IsNullOrWhiteSpace(marcaFiltro))
                {
                    // quando há marca, fazemos "full scan" dessa marca
                    forcarTodos = true;
                    LogHelper.LogInfo(string.Format("[BRAND] Filtro de marca ativo: \"{0}\" (full scan; ignora última data).", marcaFiltro), true);
                }

                // Guardar info para o /progress
                _currentBrand = marcaFiltro;
                _fullScan = forcarTodos;
                _syncStartedAt = DateTime.Now;

                //------------------------------------------------------------
                // 2c) Ler lista de SKUs filtrados (novo filtro “SKU-list”)
                //------------------------------------------------------------
                HashSet<string> skuFiltro = null;

                using (var http = new HttpClient())
                {
                    try
                    {
                        skuFiltro = await LoadSkuFilterAsync(http, wpBase);
                    }
                    catch (Exception ex)
                    {
                        LogHelper.LogException("[SKU-FILTER] Falha ao ler SKUs filtrados do WordPress", ex);
                    }
                }

                // Se existir lista de SKUs filtrados ⇒ ignoramos data da última sync
                if (skuFiltro != null && skuFiltro.Count > 0)
                {
                    forcarTodos = true;
                    _skuFilterList = skuFiltro;
                    LogHelper.LogInfo("[SKU-FILTER] Existe produtos a sincronizar com SKU (filtro ativo).", true);

                    //------------------------------------------------------------
                    //  LIMPAR A LISTA DE SKUs NO WORDPRESS APÓS LER
                    //------------------------------------------------------------
                    try
                    {
                        RestClient client = new RestClient(wpBase + "/wp-json/custom-sync/v1/sku-filter");
                        RestRequest req = new RestRequest("", Method.Post);
                        req.AddHeader("Authorization", "Basic " + credentialsAdmin);
                        req.AddHeader("Content-Type", "application/json");
                        req.AddJsonBody(new { raw = "" });  // limpa o campo

                        RestResponse resp = await client.ExecuteAsync(req);
                        if (!resp.IsSuccessful)
                        {
                            LogHelper.LogError("[SKU-FILTER] Falha ao limpar lista de SKUs: "
                                               + resp.StatusCode + " - " + resp.Content);
                        }
                        else
                        {
                            LogHelper.LogInfo("[SKU-FILTER] Lista de SKUs limpa no WordPress.");
                        }
                    }
                    catch (Exception ex)
                    {
                        LogHelper.LogException("[SKU-FILTER] Erro ao limpar SKUs", ex);
                    }
                }

                //------------------------------------------------------------
                // 3) decidir data de corte (ajustado com SKU-filter)
                //------------------------------------------------------------
                DateTime ultimaDataSincronizacao = forcarTodos
                    ? DateTime.MinValue
                    : ObterDataUltimaSincronizacao(erpBS, "ARTIGOS");

                LogHelper.LogInfo("[SYNC] Data última sincronização: " + ultimaDataSincronizacao, true);
                // 4) obter dados (com brand opcional + filtro de stock se _onlyPublishedWithStock)
                StdBELista produtos = ObterProdutosAtualizados(erpBS, ultimaDataSincronizacao, marcaFiltro);

                var logsDirectory = Path.Combine(AppDomain.CurrentDomain.BaseDirectory ?? Directory.GetCurrentDirectory(), "logs");
                var queriesDir = Path.Combine(logsDirectory, "queries");
                Directory.CreateDirectory(queriesDir);

                produtosCsv = Path.Combine(queriesDir, string.Format("Query_ARTIGOS_{0:yyyy_MM_dd_HH-mm-ss}.csv", DateTime.Now));
                QueryLogHelper.DumpListaToCsv(
                    produtos, produtosCsv,
                    "Artigo", "Descricao", "ArtigoPai", "STKActual", "PVP1", "PVP2",
                    "TipoDim1", "RubDim1", "TipoDim2", "RubDim2", "Peso", "PesoLiquido",
                    "CDU_WebNaoEnviar", "CDU_WebNaoVender", "Marca", "DescricaoComercial",
                    "Caracteristicas", "DataUltimaActualizacao", "ArtigoAnulado"
                );
                LogHelper.LogInfo("[SYNC] Produtos - CSV gerado.", true);

                var produtosLista = Snapshot(produtos);
                (execCsv, problemasCsv) = await ProcessarProdutosLista(wc, produtosLista, erpBS, queriesDir);

                var promocoes = ObterPromocoesAtualizadas(erpBS, ultimaDataSincronizacao, marcaFiltro);
                promocoesCsv = Path.Combine(queriesDir, string.Format("Query_PROMOCOES_{0:yyyy_MM_dd_HH-mm-ss}.csv", DateTime.Now));
                QueryLogHelper.DumpListaToCsv(
                    promocoes, promocoesCsv,
                    "Artigo", "ArtigoPai", "DataInicial", "DataFinal", "Desconto", "Descontouni", "DataUltimaActualizacao"
                );
                LogHelper.LogInfo("[SYNC] Promocoes - CSV gerado.", true);

                await AtualizarPromocaoProduto(wc, promocoes);
            }
            catch (Exception ex)
            {
                LogHelper.LogException("Erro durante o processamento em IniciarAplicacao", ex);
                throw;
            }
            finally
            {
                FecharEmpresaPrimavera(erpBS);
            }

            return (produtosCsv, promocoesCsv, execCsv, problemasCsv);
        }



        #endregion

        #region ERP
        static ErpBS InicializarERP()
        {
            return new ErpBS();
        }

        static void AbrirEmpresaPrimavera(ErpBS erpBS, string empresa, string user, string password)
        {
            try
            {
                erpBS.AbreEmpresaTrabalho(StdBETipos.EnumTipoPlataforma.tpEvolution, empresa, user, password, null, "DEFAULT");
            }
            catch (Exception ex)
            {
                throw new Exception("Erro ao abrir a empresa no Primavera: " + ex.Message);
            }
        }

        static void FecharEmpresaPrimavera(ErpBS erpBS)
        {
            try
            {
                if (erpBS != null)
                    erpBS.FechaEmpresaTrabalho();
            }
            catch (Exception ex)
            {
                // Nunca relançar aqui: isto corre no finally e mascararia a exceção original
                // (ex.: se o AbreEmpresaTrabalho falhou, o erro real era esse e não o do fecho).
                LogHelper.LogError("Erro ao fechar a empresa no Primavera: " + ex.Message);
            }
        }
        #endregion

        #region Sincronização (datas)
        static DateTime ObterDataUltimaSincronizacao(ErpBS erpBS, string tipoSincronizacao)
        {
            try
            {
                string query = string.Format("SELECT MAX(CDU_DataUltimaSincronizacaoArtigo) AS CDU_DataUltimaSincronizacaoArtigo FROM TDU_RegistosSincronizacao WHERE CDU_TipoSincronizacao = '{0}'", tipoSincronizacao);
                StdBELista resultado = erpBS.Consulta(query);
                if (resultado.Vazia())
                    return DateTime.MinValue;

                // MAX() numa tabela vazia devolve UMA linha com NULL — Vazia() é false e o
                // Convert.ToDateTime(DBNull) rebentava na primeira execução numa BD limpa.
                object valor = resultado.Valor("CDU_DataUltimaSincronizacaoArtigo");
                if (valor == null || valor is DBNull)
                    return DateTime.MinValue;

                return Convert.ToDateTime(valor);
            }
            catch (Exception ex)
            {
                throw new Exception("Erro ao obter a última data de sincronização: " + ex.Message);
            }
        }

        static void RegistrarProdutoSincronizado(ErpBS erpBS, string sku, DateTime ini, DateTime fim, string logResposta)
        {
            try
            {
                StdBECampos campos = new StdBECampos();
                StdBECampo campo;

                campo = new StdBECampo();
                campo.Nome = "CDU_TipoSincronizacao";
                campo.Valor = "ARTIGOS";
                campo.Tipo = StdBETipos.EnumTipoCampo.tcNVarchar;
                campos.Insere(campo);

                campo = new StdBECampo();
                campo.Nome = "CDU_DataInicioSincronizacao";
                campo.Valor = ini;
                campo.Tipo = StdBETipos.EnumTipoCampo.tcDateTime;
                campos.Insere(campo);

                campo = new StdBECampo();
                campo.Nome = "CDU_DataFimSincronizacao";
                campo.Valor = fim;
                campo.Tipo = StdBETipos.EnumTipoCampo.tcDateTime;
                campos.Insere(campo);

                campo = new StdBECampo();
                campo.Nome = "CDU_NrRegistoSincronizados";
                campo.Valor = 1;
                campo.Tipo = StdBETipos.EnumTipoCampo.tcInt;
                campos.Insere(campo);

                campo = new StdBECampo();
                campo.Nome = "CDU_LogResposta";
                campo.Valor = logResposta;
                campo.Tipo = StdBETipos.EnumTipoCampo.tcNVarchar;
                campos.Insere(campo);

                campo = new StdBECampo();
                campo.Nome = "CDU_DataUltimaSincronizacaoArtigo";
                campo.Valor = fim;
                campo.Tipo = StdBETipos.EnumTipoCampo.tcDateTime;
                campos.Insere(campo);

                StdBERegistoUtil registo = new StdBERegistoUtil();
                registo.Campos = campos;

                erpBS.TabelasUtilizador.Actualiza("TDU_RegistosSincronizacao", registo);
                LogHelper.LogInfo(string.Format("[RegistrarProdutoSincronizado] Registro inserido para SKU: {0}", sku));
            }
            catch (Exception ex)
            {
                throw new Exception(string.Format("Erro ao registrar produto sincronizado (SKU: {0}): {1}", sku, ex.Message));
            }
        }
        #endregion

        #region Queries ERP
        static StdBELista ObterProdutosAtualizados(ErpBS erpBS, DateTime ultimaDataSincronizacao, string marcaFiltro)
        {
            try
            {
                string ultima = ultimaDataSincronizacao.ToString("yyyy-MM-dd HH:mm:ss", System.Globalization.CultureInfo.InvariantCulture);
                string brand = (marcaFiltro ?? "").Trim().Replace("'", "''");

                string filtroMarca = "";
                if (!string.IsNullOrWhiteSpace(brand))
                {
                    filtroMarca = $@"
                AND UPPER(LTRIM(RTRIM(COALESCE(M.Descricao, MP.Descricao)))) = UPPER('{brand}')
            ";
                }

                // --------- Filtro SKU inteligente (pai ⇄ filhos/siblings) ----------
                string filtroSku = "";
                if (_skuFilterList != null && _skuFilterList.Count > 0)
                {
                    // sanitizar e montar lista 'IN'
                    var sane = _skuFilterList
                        .Where(s => !string.IsNullOrWhiteSpace(s))
                        .Select(s => "'" + s.Replace("'", "''").Trim() + "'");

                    string inList = string.Join(",", sane);

                    // Regras:
                    // - A.Artigo IN (lista)               → o próprio SKU (pai ou filho)
                    // - A.ArtigoPai IN (lista)            → todos os filhos de um pai listado
                    // - A.Artigo IN (pais de filhos listados)
                    // - A.Artigo IN (irmãos dos filhos listados)
                    filtroSku = $@"
                AND (
                    A.Artigo IN ({inList})
                    OR A.ArtigoPai IN ({inList})
                    OR A.Artigo IN (
                        SELECT A2.ArtigoPai
                        FROM Artigo A2
                        WHERE A2.Artigo IN ({inList}) AND A2.ArtigoPai IS NOT NULL
                    )
                    OR A.Artigo IN (
                        SELECT A3.Artigo
                        FROM Artigo A3
                        WHERE A3.ArtigoPai IN (
                            SELECT A4.ArtigoPai
                            FROM Artigo A4
                            WHERE A4.Artigo IN ({inList}) AND A4.ArtigoPai IS NOT NULL
                        )
                    )
                )
            ";
                }

                string query = @"
;WITH Price AS (
    SELECT AM.Artigo,
           MAX(CASE WHEN AM.Moeda='EUR' THEN AM.PVP1 END) AS PVP1_EUR,
           MAX(CASE WHEN AM.Moeda='EUR' THEN AM.PVP2 END) AS PVP2_EUR,
           MAX(AM.PVP1) AS PVP1_MAX,
           MAX(AM.PVP2) AS PVP2_MAX
    FROM ArtigoMoeda AM
    GROUP BY AM.Artigo
),
Stock AS (
    SELECT Artigo, SUM(Stock) AS STK
    FROM INV_ValoresActuaisStock
    GROUP BY Artigo
)
SELECT 
    A.Artigo,
    MAX(A.Descricao) AS Descricao,
    MAX(A.ArtigoPai) AS ArtigoPai,
    MAX(ISNULL(S.STK,0)) AS STKActual,
    MAX(COALESCE(P.PVP1_EUR, P.PVP1_MAX,0)) AS PVP1,
    MAX(COALESCE(P.PVP2_EUR, P.PVP2_MAX,0)) AS PVP2,
    MAX(A.TipoDim1) AS TipoDim1, MAX(A.TipoDim2) AS TipoDim2,
    MAX(A.RubDim1) AS RubDim1, MAX(A.RubDim2) AS RubDim2,
    MAX(A.PesoLiquido) AS PesoLiquido, MAX(A.Peso) AS Peso,
    MAX(CAST(ISNULL(AP.CDU_WebNaoEnviar, A.CDU_WebNaoEnviar) AS INT)) AS CDU_WebNaoEnviar,
    MAX(CAST(ISNULL(AP.CDU_WebNaoVender, A.CDU_WebNaoVender) AS INT)) AS CDU_WebNaoVender,
    MAX(COALESCE(M.Descricao, MP.Descricao)) AS Marca,
    MAX(CAST(AI.DescricaoComercial AS nvarchar(4000))) AS DescricaoComercial,
    MAX(CAST(AI.Caracteristicas AS nvarchar(4000))) AS Caracteristicas,
    MAX(A.DataUltimaActualizacao) AS DataUltimaActualizacao,
    A.ArtigoAnulado
FROM Artigo A
LEFT JOIN Artigo AP  ON AP.Artigo = A.ArtigoPai
LEFT JOIN Price P ON P.Artigo = A.Artigo
LEFT JOIN ArtigoIdioma AI ON A.Artigo = AI.Artigo
LEFT JOIN Familias F ON F.Familia = A.Familia
LEFT JOIN SubFamilias SF ON SF.SubFamilia = A.SubFamilia AND F.Familia = A.Familia
LEFT JOIN Marcas M  ON M.Marca  = A.Marca
LEFT JOIN Marcas MP ON MP.Marca = AP.Marca
LEFT JOIN Stock S ON S.Artigo = A.Artigo
WHERE
    (A.CDU_WEB = 1 OR (A.ArtigoPai IS NOT NULL AND ISNULL(AP.CDU_WEB,0) = 1))
    AND (
        '{ULTIMA}'='0001-01-01 00:00:00'
        OR A.DataUltimaActualizacao >= CONVERT(DATETIME,'{ULTIMA}',120)
        OR (A.ArtigoPai IS NOT NULL AND AP.DataUltimaActualizacao >= CONVERT(DATETIME,'{ULTIMA}',120))
    )
    {FILTRO_MARCA}
    {FILTRO_SKU}
GROUP BY A.Artigo, A.ArtigoAnulado
HAVING MAX(COALESCE(P.PVP2_EUR, P.PVP2_MAX, 0)) > 0
ORDER BY A.Artigo;";

                query = query
                    .Replace("{ULTIMA}", ultima)
                    .Replace("{FILTRO_MARCA}", filtroMarca)
                    .Replace("{FILTRO_SKU}", filtroSku);

                // Stock-only: restringe no HAVING
                if (_onlyPublishedWithStock)
                {
                    query = query.Replace(
                        "HAVING MAX(COALESCE(P.PVP2_EUR, P.PVP2_MAX, 0)) > 0",
                        "HAVING MAX(COALESCE(P.PVP2_EUR, P.PVP2_MAX, 0)) > 0 AND MAX(ISNULL(S.STK,0)) > 0"
                    );
                }

                return erpBS.Consulta(query);
            }
            catch (Exception ex)
            {
                throw new Exception("Erro ao obter produtos atualizados: " + ex.Message);
            }
        }


        static StdBELista ObterPromocoesAtualizadas(ErpBS erpBS, DateTime ultimaDataSincronizacao, string marcaFiltro)
        {
            try
            {
                string d = ultimaDataSincronizacao.ToString("yyyy-MM-dd HH:mm:ss", System.Globalization.CultureInfo.InvariantCulture);
                string brand = (marcaFiltro ?? "").Trim().Replace("'", "''");

                string dateClause;
                if (ultimaDataSincronizacao == DateTime.MinValue)
                    dateClause = "1=1";
                else
                    dateClause = string.Format("r.DataUltimaActualizacao >= CONVERT(DATETIME,'{0}',120)", d);

                string brandClause;
                if (string.IsNullOrEmpty(brand))
                    brandClause = "1=1";
                else
                    brandClause = string.Format("m.Descricao = '{0}'", brand);

                string query = @"
SELECT
    r.Campo1 AS Artigo,
    a.ArtigoPai,
    r.DataInicial,
    r.DataFinal,
    r.Desconto,
    r.Preco AS Descontouni,
    r.DataUltimaActualizacao
FROM RegrasDescPrec r
LEFT JOIN Artigo a ON a.Artigo = r.Campo1
LEFT JOIN Marcas m ON m.Marca = a.Marca
WHERE
    r.DataFinal = (SELECT MAX(DataFinal) FROM RegrasDescPrec WHERE Campo1 = r.Campo1)
    AND {DATE_CLAUSE}
    AND {BRAND_CLAUSE}
ORDER BY r.Campo1;";

                query = query.Replace("{DATE_CLAUSE}", dateClause).Replace("{BRAND_CLAUSE}", brandClause);

                return erpBS.Consulta(query);
            }
            catch (Exception ex)
            {
                throw new Exception("Erro ao obter promoções atualizadas: " + ex.Message);
            }
        }
        #endregion

        #region Modelos + Snapshot
        class ProdutoRow
        {
            public string Artigo;
            public string Descricao;
            public string ArtigoPai;
            public string TipoDim1;
            public string RubDim1;
            public string TipoDim2;
            public string RubDim2;
            public string Marca;
            public decimal? PVP1;
            public decimal? PVP2;
            public decimal? Peso;
            public decimal? PesoLiquido;
            public int STKActual;
            public int? CDU_WebNaoEnviar;
            public int? CDU_WebNaoVender;
            public int? ArtigoAnulado;
            public DateTime? DataUltimaActualizacao;
        }

        class ExecRow
        {
            public string SKU;
            public string Nome;
            public string ArtigoPai;
            public string Tipo;
            public string Acao;
            public string Mensagem;
            public int? Stock;
            public decimal? PVP;
            public string Marca;
            public ulong? WooId;
        }

        static List<ProdutoRow> Snapshot(StdBELista lista)
        {
            List<ProdutoRow> outList = new List<ProdutoRow>();
            if (lista == null || lista.Vazia()) return outList;

            lista.Inicio();
            while (!lista.NoFim())
            {
                ProdutoRow r = new ProdutoRow();
                r.Artigo = Convert.ToString(lista.Valor("Artigo")).Trim();
                r.Descricao = Convert.ToString(lista.Valor("Descricao"));
                r.ArtigoPai = lista.Valor("ArtigoPai") is DBNull ? null : Convert.ToString(lista.Valor("ArtigoPai")).Trim();
                r.STKActual = lista.Valor("STKActual") is DBNull ? 0 : Convert.ToInt32(lista.Valor("STKActual"));
                r.PVP1 = lista.Valor("PVP1") is DBNull ? (decimal?)null : Convert.ToDecimal(lista.Valor("PVP1"));
                r.PVP2 = lista.Valor("PVP2") is DBNull ? (decimal?)null : Convert.ToDecimal(lista.Valor("PVP2"));
                r.TipoDim1 = lista.Valor("TipoDim1") is DBNull ? null : Convert.ToString(lista.Valor("TipoDim1"));
                r.RubDim1 = lista.Valor("RubDim1") is DBNull ? null : Convert.ToString(lista.Valor("RubDim1"));
                r.TipoDim2 = lista.Valor("TipoDim2") is DBNull ? null : Convert.ToString(lista.Valor("TipoDim2"));
                r.RubDim2 = lista.Valor("RubDim2") is DBNull ? null : Convert.ToString(lista.Valor("RubDim2"));
                r.Peso = lista.Valor("Peso") is DBNull ? (decimal?)null : Convert.ToDecimal(lista.Valor("Peso"));
                r.PesoLiquido = lista.Valor("PesoLiquido") is DBNull ? (decimal?)null : Convert.ToDecimal(lista.Valor("PesoLiquido"));
                r.CDU_WebNaoEnviar = lista.Valor("CDU_WebNaoEnviar") is DBNull ? (int?)null : Convert.ToInt32(lista.Valor("CDU_WebNaoEnviar"));
                r.CDU_WebNaoVender = lista.Valor("CDU_WebNaoVender") is DBNull ? (int?)null : Convert.ToInt32(lista.Valor("CDU_WebNaoVender"));
                r.Marca = lista.Valor("Marca") is DBNull ? null : Convert.ToString(lista.Valor("Marca"));
                r.DataUltimaActualizacao = lista.Valor("DataUltimaActualizacao") is DBNull ? (DateTime?)null : Convert.ToDateTime(lista.Valor("DataUltimaActualizacao"));
                r.ArtigoAnulado = lista.Valor("ArtigoAnulado") is DBNull ? (int?)null : Convert.ToInt32(lista.Valor("ArtigoAnulado"));

                outList.Add(r);
                lista.Seguinte();
            }
            return outList;
        }
        #endregion

        #region Execução Pai/Filhos (MODO C + CSV PROBLEMAS)
        static bool IsProblemExecRow(ExecRow r)
        {
            if (r == null) return false;
            string acao = (r.Acao ?? "").Trim().ToLowerInvariant();
            if (acao == "erro" || acao == "orfao" || acao == "órfão" || acao == "orfa" || acao == "saltado")
                return true;

            if (acao == "apagado")
            {
                string msg = (r.Mensagem ?? "").ToLowerInvariant();
                if (msg.Contains("anulado")) return true; // pai ou artigo anulado → problema para o cliente
            }

            return false;
        }

        static async Task<(string execCsv, string problemasCsv)> ProcessarProdutosLista(WCObject wc, List<ProdutoRow> itens, ErpBS erpBS, string queriesDir)
        {
            ExecLog.Clear();
            _parentVarCache.Clear();
            _attrTermCache.Clear();
            _paiAttrsGarantidos.Clear();
            _paisConfirmadosVariable.Clear();

            // Marca “Modo C” — com deteção de órfãos ativa
            LogHelper.LogInfo("[MODO C] Deteção de filhos órfãos ativada (WooCommerce + Primavera).");

            int total = itens.Count;
            int criados = 0, atualizados = 0, apagados = 0, saltados = 0, erros = 0, processados = 0;

            // Enviar apenas 1 vez o estado inicial para o WP
            await ReportStatusStart(total);

            // throttling de status
            Stopwatch swTick = Stopwatch.StartNew();
            const int TICK_EVERY_ITEMS = 100;
            TimeSpan TICK_INTERVAL = TimeSpan.FromSeconds(10);
            string lastSku = null, lastType = null, lastStage = null;

            if (total == 0)
            {
                LogHelper.LogInfo("Nenhum produto para atualizar.");
                await ReportStatusFinish(processados, criados, atualizados, apagados, saltados, erros, total, "Sem itens");
                return (null, null);
            }

            List<ProdutoRow> pais = itens.Where(p => string.IsNullOrEmpty(p.ArtigoPai)).ToList();
            List<ProdutoRow> filhos = itens.Where(p => !string.IsNullOrEmpty(p.ArtigoPai)).ToList();

            // Índice dos pais por Artigo (antes: FirstOrDefault dentro do loop de filhos = O(n²))
            Dictionary<string, ProdutoRow> paisPorArtigo = new Dictionary<string, ProdutoRow>(StringComparer.OrdinalIgnoreCase);
            foreach (ProdutoRow paiRow in pais)
            {
                if (!string.IsNullOrWhiteSpace(paiRow.Artigo) && !paisPorArtigo.ContainsKey(paiRow.Artigo.Trim()))
                    paisPorArtigo[paiRow.Artigo.Trim()] = paiRow;
            }

            // Pais cujo stock/status será recalculado no FIM, uma vez cada
            // (antes reliam-se todas as variações do pai a cada filho processado)
            HashSet<ulong> paisComStockPorAtualizar = new HashSet<ulong>();

            // Travão opcional entre itens (ThrottleMs no App.config) para não sobrecarregar o site
            int throttleMs;
            int.TryParse(Cfg("ThrottleMs", "0"), out throttleMs);

            int loteTamanho = 500;
            int loteAtual = 0;

            // PAIS
            for (int start = 0; start < pais.Count; start += loteTamanho)
            {
                int end = Math.Min(start + loteTamanho, pais.Count);
                loteAtual++;
                LogHelper.LogInfo(string.Format("[PAI] Lote {0}: {1}..{2} de {3}", loteAtual, start + 1, end, pais.Count));

                for (int i = start; i < end; i++)
                {
                    ProdutoRow p = pais[i];
                    lastSku = p.Artigo;
                    lastType = "pai";
                    lastStage = "pais";

                    try
                    {
                        await WaitIfInMaintenance("produto-pai");
                        DateTime t0 = DateTime.Now;

                        if ((p.ArtigoAnulado ?? 0) == 1)
                        {
                            await EliminarProdutoWooCommerceFast(wc, p.Artigo, null);
                            apagados++;
                            ExecLog.Add(new ExecRow { SKU = p.Artigo, Nome = p.Descricao, ArtigoPai = p.ArtigoPai, Tipo = "pai", Acao = "Apagado", Mensagem = "Artigo anulado" });
                            RegistrarProdutoSincronizado(erpBS, p.Artigo, t0, DateTime.Now, "Apagado (anulado)");
                        }
                        else
                        {
                            SkuEntry e;
                            bool ok;
                            if (TryGetBySku(p.Artigo, out e) && e.type == "product")
                            {
                                WCv3.Product existente = await wc.Product.Get(e.id);
                                ok = await AtualizarProdutoSomenteSePai(wc, existente, p);
                                if (ok)
                                {
                                    atualizados++;
                                    ExecLog.Add(new ExecRow { SKU = p.Artigo, Nome = p.Descricao, Tipo = "pai", Acao = "Atualizado", Stock = p.STKActual, PVP = p.PVP2, Marca = p.Marca, WooId = e.id, Mensagem = "OK" });
                                }
                            }
                            else
                            {
                                ok = await CriarProdutoPai(wc, p);
                                if (ok)
                                {
                                    criados++;
                                    await TouchSkuMapAfterCreate(wc, p.Artigo, false, 0);
                                    ExecLog.Add(new ExecRow { SKU = p.Artigo, Nome = p.Descricao, Tipo = "pai", Acao = "Criado", Stock = p.STKActual, PVP = p.PVP2, Marca = p.Marca, Mensagem = "OK(draft)" });
                                }
                            }

                            if (ok)
                            {
                                // Só regista como sincronizado no ERP quando correu mesmo bem —
                                // um produto falhado volta a ser apanhado na próxima execução.
                                RegistrarProdutoSincronizado(erpBS, p.Artigo, t0, DateTime.Now,
                                    string.Format("PAI OK: Stock {0} PVP {1}", p.STKActual, p.PVP2));
                            }
                            else
                            {
                                erros++;
                                ExecLog.Add(new ExecRow { SKU = p.Artigo, Nome = p.Descricao, Tipo = "pai", Acao = "Erro", Mensagem = "Falha ao criar/atualizar após tentativas (ver error log)" });
                            }
                        }
                    }
                    catch (Exception ex)
                    {
                        erros++;
                        LogHelper.LogException(string.Format("[PAI] Erro no SKU {0}", p.Artigo), ex);
                        ExecLog.Add(new ExecRow { SKU = p.Artigo, Nome = p.Descricao, Tipo = "pai", Acao = "Erro", Mensagem = ex.Message });
                    }
                    finally
                    {
                        processados++;
                        if (processados % TICK_EVERY_ITEMS == 0 || swTick.Elapsed >= TICK_INTERVAL)
                        {
                            await ReportStatusTick(lastStage, lastSku, lastType, processados, criados, atualizados, apagados, saltados, erros, total, null);
                            swTick.Restart();
                        }

                        if (throttleMs > 0)
                            await Task.Delay(throttleMs); // travão configurável p/ aliviar o site
                    }
                }
            }

            // VARIAÇÕES
            loteAtual = 0;
            for (int start = 0; start < filhos.Count; start += loteTamanho)
            {
                int end = Math.Min(start + loteTamanho, filhos.Count);
                loteAtual++;
                LogHelper.LogInfo(string.Format("[VAR] Lote {0}: {1}..{2} de {3}", loteAtual, start + 1, end, filhos.Count));

                for (int i = start; i < end; i++)
                {
                    ProdutoRow p = filhos[i];
                    lastSku = p.Artigo;
                    lastType = "variacao";
                    lastStage = "variacoes";

                    try
                    {
                        await WaitIfInMaintenance("variacao");
                        DateTime t0 = DateTime.Now;

                        // --------------------------------------------------
                        // 1) Procurar o PAI no snapshot (Primavera)
                        // --------------------------------------------------
                        ProdutoRow parentRow = null;
                        bool parentInErp = false;
                        bool parentAnulado = false;

                        if (!string.IsNullOrWhiteSpace(p.ArtigoPai))
                        {
                            paisPorArtigo.TryGetValue(p.ArtigoPai.Trim(), out parentRow);
                            parentInErp = parentRow != null;
                            parentAnulado = parentInErp && (parentRow.ArtigoAnulado ?? 0) == 1;
                        }

                        // --------------------------------------------------
                        // 2) Se PAI EXISTE MAS ESTÁ ANULADO → ELIMINAR FILHO
                        //    (isto é um “problema” para o cliente, entra no CSV/Email problemas)
                        // --------------------------------------------------
                        if (parentAnulado)
                        {
                            await EliminarProdutoWooCommerceFast(wc, p.Artigo, p.ArtigoPai);
                            apagados++;
                            ExecLog.Add(new ExecRow
                            {
                                SKU = p.Artigo,
                                Nome = p.Descricao,
                                ArtigoPai = p.ArtigoPai,
                                Tipo = "variacao",
                                Acao = "Apagado",
                                Mensagem = "Pai anulado no ERP"
                            });
                            RegistrarProdutoSincronizado(erpBS, p.Artigo, t0, DateTime.Now, "Apagado (pai anulado)");
                            goto NextVar;
                        }

                        // --------------------------------------------------
                        // 3) Se o próprio filho está anulado → eliminar
                        // --------------------------------------------------
                        if ((p.ArtigoAnulado ?? 0) == 1)
                        {
                            await EliminarProdutoWooCommerceFast(wc, p.Artigo, p.ArtigoPai);
                            apagados++;
                            ExecLog.Add(new ExecRow { SKU = p.Artigo, Nome = p.Descricao, ArtigoPai = p.ArtigoPai, Tipo = "variacao", Acao = "Apagado", Mensagem = "Artigo anulado" });
                            RegistrarProdutoSincronizado(erpBS, p.Artigo, t0, DateTime.Now, "Apagado (anulado)");
                        }
                        else
                        {
                            // PAI no WooCommerce
                            SkuEntry pe;
                            if (!TryGetBySku(p.ArtigoPai, out pe) || pe.type != "product")
                            {
                                List<Product> paisProdutos = await wc.Product.GetAll(new Dictionary<string, string> { { "sku", p.ArtigoPai } });

                                // Pai não existe no WooCommerce
                                if (paisProdutos.Count == 0)
                                {
                                    if (!parentInErp)
                                    {
                                        // ORFÃO em Woo + Primavera
                                        string msgLog = string.Format("[ORFÃO] SKU={0} — ArtigoPai={1} — Pai não encontrado no WooCommerce nem no Primavera.",
                                              p.Artigo, p.ArtigoPai);
                                        LogHelper.LogError(msgLog);

                                        ExecLog.Add(new ExecRow
                                        {
                                            SKU = p.Artigo,
                                            Nome = p.Descricao,
                                            ArtigoPai = p.ArtigoPai,
                                            Tipo = "variacao",
                                            Acao = "Orfao",
                                            Mensagem = "Pai não encontrado no WooCommerce nem no Primavera"
                                        });
                                    }
                                    else
                                    {
                                        // Existe no ERP mas ainda não no Woo (pai “órfão” no Woo)
                                        LogHelper.LogError(string.Format("[VAR] Pai '{0}' não encontrado no WooCommerce (existe no Primavera).", p.ArtigoPai));
                                        ExecLog.Add(new ExecRow
                                        {
                                            SKU = p.Artigo,
                                            Nome = p.Descricao,
                                            ArtigoPai = p.ArtigoPai,
                                            Tipo = "variacao",
                                            Acao = "Saltado",
                                            Mensagem = "Pai não encontrado no WooCommerce"
                                        });
                                    }

                                    saltados++;
                                    goto NextVar;
                                }

                                Product paiFound = paisProdutos[0];
                                _skuMap[p.ArtigoPai] = new SkuEntry
                                {
                                    id = (ulong)paiFound.id,
                                    sku = p.ArtigoPai,
                                    type = "product",
                                    parent_id = 0
                                };
                                pe = _skuMap[p.ArtigoPai];
                            }

                            ulong parentId = pe.id;

                            Dictionary<string, Variation> varCache = await GetParentVariationCache(wc, parentId);

                            Variation variacaoExistente;
                            bool okVar;
                            if (!varCache.TryGetValue(NormalizarSku(p.Artigo), out variacaoExistente))
                            {
                                await GarantirQueFilhoNaoEhProdutoSolto(wc, p, parentId);
                                await VerificarOuAdicionarAtributosProdutoPai(wc, p, parentId);
                                okVar = await CriarVariacao(wc, p, parentId);
                                await RefreshParentVariationCache(wc, parentId);

                                varCache = await GetParentVariationCache(wc, parentId);
                                if (okVar)
                                {
                                    criados++;
                                    ExecLog.Add(new ExecRow { SKU = p.Artigo, Nome = p.Descricao, ArtigoPai = p.ArtigoPai, Tipo = "variacao", Acao = "Criado", Stock = p.STKActual, PVP = p.PVP2, Marca = p.Marca, Mensagem = "OK" });
                                }
                            }
                            else
                            {
                                await VerificarOuAdicionarAtributosProdutoPai(wc, p, parentId);
                                okVar = await AtualizarVariacao(wc, p, (ulong)variacaoExistente.id, parentId);

                                _parentVarCache[parentId][NormalizarSku(p.Artigo)] = await wc.Product.Variations.Get((ulong)variacaoExistente.id, parentId);

                                if (okVar)
                                {
                                    atualizados++;
                                    ExecLog.Add(new ExecRow
                                    {
                                        SKU = p.Artigo,
                                        Nome = p.Descricao,
                                        ArtigoPai = p.ArtigoPai,
                                        Tipo = "variacao",
                                        Acao = "Atualizado",
                                        Stock = p.STKActual,
                                        PVP = p.PVP2,
                                        Marca = p.Marca,
                                        WooId = (ulong?)variacaoExistente.id,
                                        Mensagem = "OK"
                                    });
                                }
                            }

                            // Stock do pai recalculado UMA vez no fim (ver paisComStockPorAtualizar)
                            paisComStockPorAtualizar.Add(parentId);

                            if (okVar)
                            {
                                // Só regista no ERP quando correu mesmo bem — falhados voltam na próxima execução.
                                RegistrarProdutoSincronizado(erpBS, p.Artigo, t0, DateTime.Now,
                                    string.Format("VAR OK: Stock {0} PVP {1}", p.STKActual, p.PVP2));
                            }
                            else
                            {
                                erros++;
                                ExecLog.Add(new ExecRow { SKU = p.Artigo, Nome = p.Descricao, ArtigoPai = p.ArtigoPai, Tipo = "variacao", Acao = "Erro", Mensagem = "Falha ao criar/atualizar variação após tentativas (ver error log)" });
                            }
                        }
                    }
                    catch (Exception ex)
                    {
                        erros++;
                        LogHelper.LogException(string.Format("[VAR] Erro no SKU {0}", p.Artigo), ex);
                        ExecLog.Add(new ExecRow { SKU = p.Artigo, Nome = p.Descricao, ArtigoPai = p.ArtigoPai, Tipo = "variacao", Acao = "Erro", Mensagem = ex.Message });
                    }
                    finally
                    {
                        processados++;
                        if (processados % TICK_EVERY_ITEMS == 0 || swTick.Elapsed >= TICK_INTERVAL)
                        {
                            await ReportStatusTick(lastStage, lastSku, lastType, processados, criados, atualizados, apagados, saltados, erros, total, null);
                            swTick.Restart();
                        }

                        if (throttleMs > 0)
                            await Task.Delay(throttleMs); // travão configurável p/ aliviar o site
                    }

                NextVar:
                    ;
                }
            }

            // Recalcular stock/status dos pais UMA vez cada (antes era refeito por cada filho,
            // com releitura paginada de todas as variações — grande carga no WooCommerce)
            if (paisComStockPorAtualizar.Count > 0)
            {
                LogHelper.LogInfo(string.Format("[PAI-STOCK] A recalcular stock de {0} pais no fim da execução...", paisComStockPorAtualizar.Count), true);
                foreach (ulong pid in paisComStockPorAtualizar)
                    await AtualizarEstoqueProdutoPai(wc, pid);
            }

            LogHelper.LogInfo(string.Format("Resumo Execução: Total={0} | Criados={1} | Atualizados={2} | Apagados={3} | Saltados={4} | Erros={5}",
                total, criados, atualizados, apagados, saltados, erros));

            // Alimentar o relatório para o backup-manager
            SyncReporter.SetProductCounts(criados, atualizados, apagados, saltados, erros, total);
            SyncReporter.SetContext(_currentBrand, _fullScan, _onlyPublishedWithStock,
                _skuFilterList != null ? _skuFilterList.Count : 0);
            foreach (ExecRow r in ExecLog)
                SyncReporter.RecordItem(
                    r.SKU,
                    string.IsNullOrWhiteSpace(r.Nome) ? r.Tipo : r.Nome, // nome do produto (fallback: pai/variacao)
                    (r.Acao ?? "").ToLowerInvariant(),
                    string.IsNullOrWhiteSpace(r.Mensagem) ? r.Tipo : (r.Tipo + " — " + r.Mensagem));

            // CSV completo
            string execCsv = Path.Combine(queriesDir, string.Format("Execucao_ARTIGOS_{0:yyyy_MM_dd_HH-mm-ss}.csv", DateTime.Now));
            using (StreamWriter sw = new StreamWriter(execCsv, false, Encoding.UTF8))
            {
                sw.WriteLine("SKU;ArtigoPai;Tipo;Acao;Mensagem;Stock;PVP;Marca;WooId");
                foreach (ExecRow r in ExecLog)
                {
                    sw.WriteLine(string.Format("{0};{1};{2};{3};{4};{5};{6};{7};{8}",
                        CsvSafe(r.SKU),
                        CsvSafe(r.ArtigoPai),
                        CsvSafe(r.Tipo),
                        CsvSafe(r.Acao),
                        CsvSafe(r.Mensagem),
                        r.Stock,
                        r.PVP,
                        CsvSafe(r.Marca),
                        r.WooId));
                }
            }

            // CSV apenas de problemas (para o email do cliente)
            string problemasCsv = null;
            var problemRows = ExecLog.Where(IsProblemExecRow).ToList();
            if (problemRows.Count > 0)
            {
                problemasCsv = Path.Combine(queriesDir, string.Format("Execucao_PROBLEMAS_ARTIGOS_{0:yyyy_MM_dd_HH-mm-ss}.csv", DateTime.Now));
                using (StreamWriter sw = new StreamWriter(problemasCsv, false, Encoding.UTF8))
                {
                    sw.WriteLine("SKU;ArtigoPai;Tipo;Acao;Mensagem;Stock;PVP;Marca;WooId");
                    foreach (ExecRow r in problemRows)
                    {
                        sw.WriteLine(string.Format("{0};{1};{2};{3};{4};{5};{6};{7};{8}",
                            CsvSafe(r.SKU),
                            CsvSafe(r.ArtigoPai),
                            CsvSafe(r.Tipo),
                            CsvSafe(r.Acao),
                            CsvSafe(r.Mensagem),
                            r.Stock,
                            r.PVP,
                            CsvSafe(r.Marca),
                            r.WooId));
                    }
                }
            }

            await ReportStatusFinish(processados, criados, atualizados, apagados, saltados, erros, total, "Fim da execução");
            return (execCsv, problemasCsv);
        }

        static string CsvSafe(string s)
        {
            if (string.IsNullOrEmpty(s)) return "";
            s = s.Replace("\r\n", " ").Replace("\n", " ").Replace("\r", " ");
            if (s.Contains(";") || s.Contains("\""))
                s = "\"" + s.Replace("\"", "\"\"") + "\"";
            return s;
        }
        #endregion

        #region Logs
        public static class LogHelper
        {
            private static string logPath;
            private static string errorLogPath;
            private static readonly object _lock = new object();
            public static bool OnlyUpdatedInfo = true;

            public static void Initialize(string logFilePath, string errorFilePath)
            {
                logPath = logFilePath;
                errorLogPath = errorFilePath;
            }

            public static void LogInfo(string message)
            {
                if (OnlyUpdatedInfo && !IsUpdateMessage(message)) return;
                EscreverLog(message, logPath);
            }

            /// <summary>
            /// force=true escreve sempre no log NORMAL (para mensagens operacionais que
            /// antes iam parar ao error log só para não serem filtradas).
            /// </summary>
            public static void LogInfo(string message, bool force)
            {
                if (force) { EscreverLog(message, logPath); return; }
                LogInfo(message);
            }

            public static void LogError(string message)
            {
                EscreverLog("[ERRO] " + message, errorLogPath);
            }

            public static void LogException(string context, Exception ex)
            {
                EscreverLog(string.Format("[EXCEPTION] {0}\r\n{1}", context, ex), errorLogPath);
            }

            private static bool IsUpdateMessage(string message)
            {
                if (string.IsNullOrWhiteSpace(message)) return false;
                string m = message.ToLowerInvariant();
                return m.Contains(" atualizado ") || m.EndsWith(" atualizado.")
                    || m.Contains(" atualizada ") || m.EndsWith(" atualizada.")
                    || (m.Contains("promo") && m.Contains("atualiz"))
                    || m.Contains("criado com sucesso")
                    || m.Contains("produto")
                    || m.Contains("variaç")
                    || m.Contains("órfão") || m.Contains("orfão") || m.Contains("orfa"); // garante logs de órfãos
            }

            private static void EscreverLog(string message, string path)
            {
                if (string.IsNullOrWhiteSpace(path)) return;
                lock (_lock)
                {
                    int maxTentativas = 5, tentativa = 0;
                    bool sucesso = false;
                    while (tentativa < maxTentativas && !sucesso)
                    {
                        try
                        {
                            using (StreamWriter sw = new StreamWriter(path, true))
                            {
                                sw.WriteLine(string.Format("[{0:yyyy-MM-dd HH:mm:ss}] {1}", DateTime.Now, message));
                                sucesso = true;
                            }
                        }
                        catch (IOException)
                        {
                            tentativa++;
                            Thread.Sleep(100);
                        }
                        catch (Exception ex)
                        {
                            Console.WriteLine("[LOG ERROR] " + ex);
                            break;
                        }
                    }
                    if (!sucesso)
                        Console.WriteLine(string.Format("[ERRO] Não foi possível escrever no log após {0} tentativas.", maxTentativas));
                }
            }
        }
        #endregion

        #region Utils Texto
        static string RemoveDiacritics(string text)
        {
            if (string.IsNullOrWhiteSpace(text)) return text;
            string norm = text.Normalize(NormalizationForm.FormD);
            StringBuilder sb = new StringBuilder(norm.Length);
            foreach (char ch in norm)
            {
                var uc = System.Globalization.CharUnicodeInfo.GetUnicodeCategory(ch);
                if (uc != System.Globalization.UnicodeCategory.NonSpacingMark) sb.Append(ch);
            }
            return sb.ToString().Normalize(NormalizationForm.FormC);
        }

        static string Slugify(string s)
        {
            if (string.IsNullOrWhiteSpace(s)) return "";

            s = s.Trim().ToLowerInvariant();
            var sb = new StringBuilder();

            foreach (char c in s)
            {
                if ((c >= 'a' && c <= 'z') || (c >= '0' && c <= '9'))
                    sb.Append(c);
                else
                    sb.Append('-');
            }

            var tmp = sb.ToString();
            while (tmp.Contains("--"))
                tmp = tmp.Replace("--", "-");

            return tmp.Trim('-');
        }
        #endregion

        #region Woo Helpers - Status & Atributos (RestSharp 106: Execute síncrono)
        static async Task<int?> BuscarProdutoIdPorSkuAsync(string sku)
        {
            try
            {
                RestClient client = new RestClient(wpBase);
                RestRequest req = new RestRequest("/wp-json/custom-sync/v1/buscar-por-sku/", Method.Get);
                req.AddParameter("sku", (sku ?? "").Trim());

                RestResponse resp = await client.ExecuteAsync(req);
                if (!resp.IsSuccessful || string.IsNullOrWhiteSpace(resp.Content))
                {
                    LogHelper.LogError(string.Format("[BUSCAR-SKU] Falha HTTP para '{0}': {1} - {2}",
                        sku, resp.StatusCode, resp.Content));
                    return null;
                }

                JObject json = JObject.Parse(resp.Content);
                bool found = json["found"] != null && json["found"].ToObject<bool>();
                if (!found) return null;

                int id = json["product_id"] != null ? json["product_id"].ToObject<int>() : 0;
                return id > 0 ? (int?)id : null;
            }
            catch (Exception ex)
            {
                LogHelper.LogException("[BUSCAR-SKU] Exceção para '" + sku + "'", ex);
                return null;
            }
        }

        static async Task<bool> EliminarProdutoPorIdAsync(int id)
        {
            try
            {
                RestClient client = new RestClient(wpBase + "/wp-json/custom-sync/v1/eliminar-produto/");
                RestRequest req = new RestRequest("", Method.Post);
                req.AddHeader("Content-Type", "application/json");
                req.AddJsonBody(new { id = id });

                RestResponse resp = await client.ExecuteAsync(req);
                if (!resp.IsSuccessful)
                {
                    LogHelper.LogError(string.Format("[DEL-ID] Falha eliminar id {0}: {1} - {2}",
                        id, resp.StatusCode, resp.Content));
                    return false;
                }

                LogHelper.LogInfo(string.Format("[DEL-ID] Produto/variação id {0} eliminado com sucesso.", id));
                return true;
            }
            catch (Exception ex)
            {
                LogHelper.LogException("[DEL-ID] Exceção ao eliminar id " + id, ex);
                return false;
            }
        }

        /// <summary>
        /// Apaga TODOS os posts (produto ou variação) que tenham este SKU em todo o site.
        /// Usa /buscar-por-sku + /eliminar-produto em loop, com limite de 10 por segurança.
        /// </summary>
        static async Task<bool> DeleteAllPostsBySkuAsync(string sku)
        {
            bool removedSomething = false;
            string cleanSku = (sku ?? "").Trim();

            if (string.IsNullOrEmpty(cleanSku))
                return false;

            for (int i = 0; i < 10; i++)
            {
                int? id = await BuscarProdutoIdPorSkuAsync(cleanSku);
                if (!id.HasValue || id.Value <= 0)
                    break;

                bool ok = await EliminarProdutoPorIdAsync(id.Value);
                if (!ok)
                    break;

                removedSomething = true;

                LogHelper.LogInfo(string.Format(
                    "[DEL-SKU] Produto/variação com SKU '{0}' (ID {1}) eliminado com sucesso.",
                    cleanSku, id.Value));
            }

            if (!removedSomething)
            {
                LogHelper.LogInfo(string.Format(
                    "[DEL-SKU] Nenhum produto/variação encontrado com SKU '{0}'.",
                    cleanSku));
            }

            return removedSomething;
        }






        static async Task PostProgressAsync(object payload)
        {
            try
            {
                RestClient client = new RestClient(wpBase);
                RestRequest req = new RestRequest("/wp-json/custom-sync/v1/progress", Method.Post);
                req.AddHeader("Authorization", "Basic " + credentialsAdmin);
                req.AddHeader("Content-Type", "application/json");
                req.AddJsonBody(payload);
                RestResponse res = client.Execute(req);
                if (!res.IsSuccessful)
                    LogHelper.LogError(string.Format("[STATUS] Falha progress: {0} - {1}", res.StatusCode, res.Content));
            }
            catch (Exception ex)
            {
                LogHelper.LogException("[STATUS] Exceção progress", ex);
            }
        }

        static async Task ReportStatusTick(
     string stage,
     string sku,
     string itemType,
     int processed,
     int created,
     int updated,
     int deleted,
     int skipped,
     int errors,
     int total,
     string message)
        {
            // Já não enviamos nada para o WP aqui.
            // Se quiseres, podes só registar em log local:
            LogHelper.LogInfo(string.Format(
                "[TICK] stage={0} sku={1} type={2} processed={3}/{4} created={5} updated={6} deleted={7} skipped={8} errors={9}",
                stage, sku, itemType, processed, total, created, updated, deleted, skipped, errors
            ));

            // Progresso para o backup-manager (throttled internamente; best-effort)
            await SyncReporter.ProgressAsync(stage, processed, total, created, updated, deleted, skipped, errors);
        }

        static async Task ReportStatusStart(int total)
        {
            var payload = new
            {
                running = true,
                pid = Process.GetCurrentProcess().Id.ToString(),

                // info de contexto para o PHP
                brand = _currentBrand,
                full_scan = _fullScan,
                started_at = _syncStartedAt.ToString("yyyy-MM-dd HH:mm:ss"),
                finished_at = (string)null,

                stage = "start",
                current_sku = (string)null,
                current_type = (string)null,

                // totais iniciais
                total = total,
                processed = 0,
                created = 0,
                updated = 0,
                deleted = 0,
                skipped = 0,
                errors = 0,
                message = "Início da sincronização"
            };

            await PostProgressAsync(payload);
        }


        static async Task ReportStatusFinish(
    int processed,
    int created,
    int updated,
    int deleted,
    int skipped,
    int errors,
    int total,
    string message)
        {
            var payload = new
            {
                running = false,
                pid = Process.GetCurrentProcess().Id.ToString(),

                // datas coerentes com o início registado
                started_at = _syncStartedAt.ToString("yyyy-MM-dd HH:mm:ss"),
                finished_at = DateTime.Now.ToString("yyyy-MM-dd HH:mm:ss"),

                // info de contexto
                brand = _currentBrand,
                full_scan = _fullScan,

                // estes campos são mapeados em 'totals' pelo PHP
                total = total,
                processed = processed,
                created = created,
                updated = updated,
                deleted = deleted,
                skipped = skipped,
                errors = errors,

                stage = "finish",
                message = message ?? "Fim da execução",
                current_sku = (string)null,
                current_type = (string)null
            };

            await PostProgressAsync(payload);
        }


        static async Task<List<WCv3.ProductAttributeTerm>> GetAllAttributeTermsPaged(WCObject wc, ulong attrId)
        {
            List<WCv3.ProductAttributeTerm> all = new List<WCv3.ProductAttributeTerm>();
            int page = 1;
            while (true)
            {
                List<WCv3.ProductAttributeTerm> pageTerms =
                    await wc.Attribute.Terms.GetAll(attrId, new Dictionary<string, string>
                    {
                        { "per_page", "100" },
                        { "page", page.ToString() }
                    });

                if (pageTerms == null || pageTerms.Count == 0) break;
                all.AddRange(pageTerms);
                if (pageTerms.Count < 100) break;
                page++;
            }
            return all;
        }

        // Cache por execução: (atributo|termo) → resultado. Evita reler TODOS os atributos
        // e TODOS os termos do WooCommerce por cada variação — era a maior fonte de
        // chamadas redundantes e de carga no site.
        static readonly Dictionary<string, (WCv3.ProductAttribute attr, WCv3.ProductAttributeTerm term)> _attrTermCache =
            new Dictionary<string, (WCv3.ProductAttribute, WCv3.ProductAttributeTerm)>(StringComparer.OrdinalIgnoreCase);

        static async Task<(WCv3.ProductAttribute attr, WCv3.ProductAttributeTerm term)> EnsureAttributeAndTerm(
            WCObject wc,
            string attrName,
            string termValue)
        {
            string cacheKey = (attrName ?? "").Trim() + "|" + (termValue ?? "").Trim();

            (WCv3.ProductAttribute attr, WCv3.ProductAttributeTerm term) hit;
            if (_attrTermCache.TryGetValue(cacheKey, out hit))
                return hit;

            var result = await EnsureAttributeAndTermCore(wc, attrName, termValue);
            _attrTermCache[cacheKey] = result;
            return result;
        }

        static async Task<(WCv3.ProductAttribute attr, WCv3.ProductAttributeTerm term)> EnsureAttributeAndTermCore(
    WCObject wc,
    string attrName,
    string termValue)
        {
            if (string.IsNullOrWhiteSpace(attrName) || string.IsNullOrWhiteSpace(termValue))
                throw new ArgumentException("Nome do atributo e valor do termo são obrigatórios.");

            // Normalização simples para comparar nomes
            string wantedName = termValue.Trim();
            string wantedSlug = Slugify(wantedName);

            Func<string, string> NormalizeName = s =>
            {
                return RemoveDiacritics((s ?? "").Trim()).ToLowerInvariant();
            };

            // 1) Obter / criar atributo global
            List<WCv3.ProductAttribute> allAttrs = await wc.Attribute.GetAll();
            WCv3.ProductAttribute attr = allAttrs.FirstOrDefault(a =>
                string.Equals((a.name ?? "").Trim(), attrName.Trim(), StringComparison.OrdinalIgnoreCase));

            if (attr == null)
            {
                attr = await wc.Attribute.Add(new WCv3.ProductAttribute
                {
                    name = attrName.Trim(),
                    type = "select",
                    has_archives = true,
                    order_by = "menu_order"
                });
            }
            else if (attr.has_archives != true)
            {
                // Garante que aparece nas archives / filtros
                await wc.Attribute.Update((ulong)attr.id, new WCv3.ProductAttribute
                {
                    has_archives = true
                });

                // Recarrega para ter o estado atual
                allAttrs = await wc.Attribute.GetAll();
                attr = allAttrs.First(a => a.id == attr.id);
            }

            // 2) Obter / criar termo desse atributo
            List<WCv3.ProductAttributeTerm> terms = await GetAllAttributeTermsPaged(wc, (ulong)attr.id);

            WCv3.ProductAttributeTerm term = terms.FirstOrDefault(t =>
                string.Equals(t.slug, wantedSlug, StringComparison.OrdinalIgnoreCase) ||
                string.Equals(NormalizeName(t.name), NormalizeName(wantedName), StringComparison.OrdinalIgnoreCase));

            if (term != null)
                return (attr, term);

            try
            {
                term = await wc.Attribute.Terms.Add(new WCv3.ProductAttributeTerm
                {
                    name = wantedName,
                    slug = wantedSlug
                }, (ulong)attr.id);

                return (attr, term);
            }
            catch (Exception ex)
            {
                // Se for "term_exists", tentamos descobrir qual é
                string msg = ex.Message ?? "";
                if (msg.IndexOf("term_exists", StringComparison.OrdinalIgnoreCase) >= 0)
                {
                    try
                    {
                        // Às vezes vem o resource_id no JSON do erro
                        Match m = Regex.Match(msg, "\"resource_id\"\\s*:\\s*(\\d+)");
                        if (m.Success)
                        {
                            if (ulong.TryParse(m.Groups[1].Value, out var ridVal))
                            {
                                var existing = await wc.Attribute.Terms.Get(ridVal, (ulong)attr.id);
                                if (existing != null) return (attr, existing);
                            }
                        }

                        // Caso contrário recarregamos a lista
                        terms = await GetAllAttributeTermsPaged(wc, (ulong)attr.id);
                        term = terms.FirstOrDefault(t =>
                            string.Equals(t.slug, wantedSlug, StringComparison.OrdinalIgnoreCase) ||
                            string.Equals(NormalizeName(t.name), NormalizeName(wantedName), StringComparison.OrdinalIgnoreCase));

                        if (term != null) return (attr, term);
                    }
                    catch
                    {
                        // Se correr mal, atiramos o erro original abaixo
                    }
                }

                throw new Exception(
                    $"EnsureAttributeAndTerm falhou para '{attrName}' / '{termValue}': {ex.Message}", ex);
            }
        }


        #endregion

        #region Woo Fixes / Conflicts / Maintenance
        static (string code, string message, ulong? resourceId, string uniqueSku) ParseWooError(Exception ex)
        {
            try
            {
                JObject obj = JObject.Parse(ex.Message ?? "{}");
                string code = (string)obj["code"];
                string message = (string)obj["message"];
                ulong? rid = null;
                if (obj["data"] != null && obj["data"]["resource_id"] != null)
                {
                    ulong ridVal;
                    if (ulong.TryParse(obj["data"]["resource_id"].ToString(), out ridVal))
                        rid = ridVal;
                }
                string unique = (string)(obj["data"] != null ? obj["data"]["unique_sku"] : null) ?? (string)obj["unique_sku"];
                return (code, message, rid, unique);
            }
            catch
            {
                string txt = ex.Message ?? "";
                Match mc = Regex.Match(txt, "\"code\"\\s*:\\s*\"([^\"]+)\"");
                string code = mc.Success ? mc.Groups[1].Value : null;
                Match mm = Regex.Match(txt, "\"message\"\\s*:\\s*\"([^\"]+)\"");
                string message = mm.Success ? mm.Groups[1].Value : null;
                ulong? rid = null;
                Match mr = Regex.Match(txt, "\"resource_id\"\\s*:\\s*(\\d+)");
                if (mr.Success)
                {
                    ulong ridVal2;
                    if (ulong.TryParse(mr.Groups[1].Value, out ridVal2))
                        rid = ridVal2;
                }
                Match mu = Regex.Match(txt, "\"unique_sku\"\\s*:\\s*\"([^\"]+)\"");
                string unique = mu.Success ? mu.Groups[1].Value : null;
                return (code, message, rid, unique);
            }
        }

        static async Task<bool> MaybeTransientWait(Exception ex, int attempt, string context)
        {
            string msg = (ex.Message ?? "").ToLowerInvariant();
            if (msg.Contains("database error") ||
                msg.Contains("maintenance") ||
                msg.Contains("temporarily unavailable") ||
                msg.Contains("502") || msg.Contains("503") || msg.Contains("504") ||
                msg.Contains("429"))
            {
                await WaitIfInMaintenance(context);
                int seconds = (int)Math.Min(120, Math.Pow(2, attempt) * 5);
                TimeSpan delay = TimeSpan.FromSeconds(seconds);
                LogHelper.LogError(string.Format("[{0}] Erro transitório. Aguardar {1}s antes do retry...", context, delay.TotalSeconds));
                await Task.Delay(delay);
                return true;
            }
            return false;
        }

        static async Task WaitIfInMaintenance(string context)
        {
            try
            {
                RestClient client = new RestClient(wpBase);
                RestRequest req = new RestRequest("/wp-json/custom-sync/v1/health", Method.Get);
                RestResponse res = client.Execute(req);
                if (!res.IsSuccessful) return;

                Dictionary<string, object> json = JsonConvert.DeserializeObject<Dictionary<string, object>>(res.Content ?? "{}");
                if (json != null && json.ContainsKey("maintenance") && (json["maintenance"] != null && json["maintenance"].ToString() == "True"))
                {
                    LogHelper.LogError(string.Format("[{0}] Site em manutenção. A aguardar 60s...", context));
                    await Task.Delay(TimeSpan.FromSeconds(60));
                }
            }
            catch
            {
            }
        }
        #endregion

        #region Corrigir “filho solto” / Resolver SKU
        static async Task GarantirQueFilhoNaoEhProdutoSolto(WCObject wc, ProdutoRow filho, ulong parentId)
        {
            string sku = (filho.Artigo ?? "").Trim();
            if (string.IsNullOrEmpty(sku)) return;

            bool ok = await ResolveSkuServerSide(sku, parentId, "delete");
            if (ok)
            {
                LogHelper.LogInfo(string.Format("[FIX] SKU '{0}' liberto via endpoint.", sku));
                await RefreshParentVariationCache(wc, parentId);
                return;
            }

            try
            {
                List<Variation> vars = await wc.Product.Variations.GetAll(parentId);
                Variation varNoPai = vars.FirstOrDefault(v => NormalizarSku(v.sku) == NormalizarSku(sku));
                if (varNoPai != null)
                {
                    await wc.Product.Variations.Delete((ulong)varNoPai.id, parentId, true);
                    LogHelper.LogInfo(string.Format("[FIX] VAR {0} removida do pai {1} (fallback).", varNoPai.id, parentId));
                    await RefreshParentVariationCache(wc, parentId);
                }
            }
            catch (Exception ex)
            {
                LogHelper.LogException(string.Format("[FIX] Fallback no pai {0} falhou para SKU '{1}'", parentId, sku), ex);
            }
        }

        static async Task<bool> ResolveSkuServerSide(string sku, ulong? intendedParentId, string action)
        {
            try
            {
                RestClient client = new RestClient(wpBase + "/wp-json/custom-sync/v1/resolve-sku");
                RestRequest req = new RestRequest("", Method.Post);
                req.AddHeader("Authorization", "Basic " + credentialsAdmin);
                req.AddHeader("Content-Type", "application/json");

                var body = new
                {
                    sku = (sku ?? "").Trim(),
                    intended_parent_id = intendedParentId.HasValue ? (long?)intendedParentId.Value : null,
                    action = action
                };

                req.AddJsonBody(body);
                RestResponse res = client.Execute(req);

                if (!res.IsSuccessful)
                {
                    LogHelper.LogError(string.Format("[RESOLVE-SKU] Falha HTTP para '{0}': {1} - {2}", sku, res.StatusCode, res.Content));
                    return false;
                }

                Dictionary<string, object> json = JsonConvert.DeserializeObject<Dictionary<string, object>>(res.Content ?? "{}");
                bool ok = json != null && json.ContainsKey("ok") && json["ok"] != null && json["ok"].ToString() == "True";
                string status = json != null && json.ContainsKey("status") && json["status"] != null ? json["status"].ToString() : "unknown";
                LogHelper.LogInfo(string.Format("[RESOLVE-SKU] {0} → {1}", sku, status));
                return ok;
            }
            catch (Exception ex)
            {
                LogHelper.LogException(string.Format("[RESOLVE-SKU] Exceção ao resolver '{0}'", sku), ex);
                return false;
            }
        }
        #endregion

        #region CRUD Produto & Variação
        // PRODUTO PAI — devolve true só quando a operação foi mesmo concluída
        static async Task<bool> CriarProdutoPai(WCObject wc, ProdutoRow p)
        {
            if (!string.IsNullOrEmpty(p.ArtigoPai))
                throw new InvalidOperationException("CriarProdutoPai chamado para filho: " + p.Artigo);

            int attempt = 0;
        RETRY_CREATE_PAI:
            try
            {
                await WaitIfInMaintenance("CriarProdutoPai");

                decimal precoRegular = p.PVP2 ?? 0m;
                decimal? peso = p.Peso;
                List<WCv3.ProductAttributeLine> atributos = new List<WCv3.ProductAttributeLine>();

                bool temDim = (!string.IsNullOrWhiteSpace(p.TipoDim1) && !string.IsNullOrWhiteSpace(p.RubDim1))
                           || (!string.IsNullOrWhiteSpace(p.TipoDim2) && !string.IsNullOrWhiteSpace(p.RubDim2));

                if (!string.IsNullOrWhiteSpace(p.TipoDim1) && !string.IsNullOrWhiteSpace(p.RubDim1))
                {
                    WCv3.ProductAttribute attr;
                    WCv3.ProductAttributeTerm term;
                    (attr, term) = await EnsureAttributeAndTerm(wc, p.TipoDim1.Trim(), p.RubDim1.Trim());
                    atributos.Add(new WCv3.ProductAttributeLine
                    {
                        id = attr.id,
                        options = new List<string> { term.name },
                        visible = true,
                        variation = true
                    });
                }

                if (!string.IsNullOrWhiteSpace(p.TipoDim2) && !string.IsNullOrWhiteSpace(p.RubDim2))
                {
                    WCv3.ProductAttribute attr2;
                    WCv3.ProductAttributeTerm term2;
                    (attr2, term2) = await EnsureAttributeAndTerm(wc, p.TipoDim2.Trim(), p.RubDim2.Trim());
                    atributos.Add(new WCv3.ProductAttributeLine
                    {
                        id = attr2.id,
                        options = new List<string> { term2.name },
                        visible = true,
                        variation = true
                    });
                }

                List<ProductMeta> productMeta = new List<ProductMeta>();
                decimal metaPrice = (p.PVP1 == null || p.PVP1 == 0m) ? precoRegular : p.PVP1.Value;
                productMeta.Add(new ProductMeta
                {
                    key = "wholesale_customer_wholesale_price",
                    value = metaPrice
                });

                Product novoProduto = new Product
                {
                    sku = p.Artigo,
                    name = p.Descricao,
                    stock_quantity = p.STKActual,
                    regular_price = precoRegular,
                    meta_data = productMeta,
                    manage_stock = true,
                    type = temDim ? "variable" : "simple",
                    status = "draft",
                    attributes = atributos,
                    weight = peso
                };

                Product produtoCriado = await wc.Product.Add(novoProduto);

                if (!string.IsNullOrWhiteSpace(p.Marca))
                {
                    string nomeMarca = p.Marca.Trim();
                    await VerificarOuCriarMarca(nomeMarca);
                    await PostWpCustomAsync("/wp-json/custom-sync/v1/atribuir-marca/",
                        new { id = produtoCriado.id.ToString(), marca = nomeMarca },
                        "atribuir-marca produto " + produtoCriado.id);
                }

                bool bloquearCompra = (p.CDU_WebNaoVender ?? 0) != 0;
                bool bloquearEnvio = (p.CDU_WebNaoEnviar ?? 0) != 0;
                await AtualizarCamposBloqueioWooCommerce(produtoCriado.id.ToString(), bloquearCompra, bloquearEnvio);

                LogHelper.LogInfo("Produto PAI " + p.Artigo + " criado com sucesso.");
                return true;
            }
            catch (Exception ex)
            {
                string code;
                string msg;
                ulong? resourceId;
                string uniqueSku;
                (code, msg, resourceId, uniqueSku) = ParseWooError(ex);
                string lower = (ex.Message ?? "").ToLowerInvariant();

                if ((code == "woocommerce_rest_product_not_created" && lower.Contains("lookup")) || code == "product_invalid_sku")
                {
                    Product existente = await ResolveProductByConflict(wc, p.Artigo, resourceId);
                    if (existente != null)
                    {
                        LogHelper.LogInfo(string.Format("[CREATE→UPDATE] Conflito de SKU '{0}'. Atualizar existente (ID {1}).", p.Artigo, existente.id));
                        return await AtualizarProdutoSomenteSePai(wc, existente, p);
                    }
                }

                if (await MaybeTransientWait(ex, attempt++, "CriarProdutoPai " + p.Artigo))
                {
                    wc = await ReiniciarConexaoWooCommerce();
                    goto RETRY_CREATE_PAI;
                }

                LogHelper.LogException("Erro ao criar produto PAI " + p.Artigo, ex);
                return false;
            }
        }

        static async Task<Product> ResolveProductByConflict(WCObject wc, string sku, ulong? resourceId)
        {
            try
            {
                if (resourceId.HasValue)
                {
                    Product byId = await wc.Product.Get(resourceId.Value);
                    if (byId != null) return byId;
                }
            }
            catch
            {
            }

            try
            {
                List<Product> list = await wc.Product.GetAll(new Dictionary<string, string> { { "sku", sku } });
                if (list != null && list.Count > 0) return list[0];
            }
            catch
            {
            }

            return null;
        }

        static async Task<bool> AtualizarProdutoSomenteSePai(WCObject wc, WCv3.Product produtoExistente, ProdutoRow p)
        {
            if (!string.IsNullOrEmpty(p.ArtigoPai))
                throw new InvalidOperationException("AtualizarProdutoSomenteSePai não aceita filhos. SKU: " + p.Artigo);

            int attempt = 0;

        RETRY_UPDATE_PAI:
            try
            {
                await WaitIfInMaintenance("AtualizarProduto(Pai)");

                // 1) Ler o PAI “fresco”
                var paiAtual = await wc.Product.Get((ulong)produtoExistente.id);

                // 2) Merge de atributos (como já fazias)
                var atributosAtuais = paiAtual.attributes ?? new List<WCv3.ProductAttributeLine>();

                async Task MergeAttr(string tipo, string valor)
                {
                    if (string.IsNullOrWhiteSpace(tipo) || string.IsNullOrWhiteSpace(valor)) return;

                    var (attr, term) = await EnsureAttributeAndTerm(wc, tipo.Trim(), valor.Trim());

                    var existente = atributosAtuais.FirstOrDefault(a => a.id == attr.id);
                    if (existente == null)
                    {
                        atributosAtuais.Add(new WCv3.ProductAttributeLine
                        {
                            id = attr.id,
                            name = null,
                            options = new List<string> { term.name },
                            visible = true,
                            variation = true
                        });
                    }
                    else
                    {
                        if (existente.options == null) existente.options = new List<string>();
                        if (!existente.options.Contains(term.name, StringComparer.OrdinalIgnoreCase))
                            existente.options.Add(term.name);

                        existente.options = existente.options
                            .Distinct(StringComparer.OrdinalIgnoreCase)
                            .OrderBy(x => x, StringComparer.OrdinalIgnoreCase)
                            .ToList();

                        existente.visible = true;
                        existente.variation = true;
                        existente.name = null; // quando há id, deixa nulo
                    }
                }

                await MergeAttr(p.TipoDim1, p.RubDim1);
                await MergeAttr(p.TipoDim2, p.RubDim2);

                // 3) Decidir se o pai precisa ficar 'variable'
                bool temAttrDeVariacao = atributosAtuais.Any(a => a.variation == true && (a.options?.Count > 0));
                bool vaiVirarVariable = temAttrDeVariacao && !string.Equals(paiAtual.type, "variable", StringComparison.OrdinalIgnoreCase);

                // 4) Montar o PATCH
                decimal precoRegular = p.PVP2 ?? 0m;
                decimal precoRevendedor = (p.PVP1 == null || p.PVP1 == 0m) ? precoRegular : p.PVP1.Value;

                var upd = new WCv3.Product
                {
                    name = p.Descricao,
                    attributes = atributosAtuais.Select(a => new WCv3.ProductAttributeLine
                    {
                        id = a.id,
                        name = a.id.HasValue ? null : a.name,
                        visible = true,
                        variation = true,
                        options = (a.options ?? new List<string>()).ToList()
                    }).ToList(),
                    type = vaiVirarVariable ? "variable" : null
                };

                // 5) Se continuar/for SIMPLES, enviar preço/wholesale/stock
                bool paiContinuaSimples = !vaiVirarVariable && !string.Equals(paiAtual.type, "variable", StringComparison.OrdinalIgnoreCase);
                if (paiContinuaSimples)
                {
                    upd.manage_stock = true;
                    upd.stock_quantity = p.STKActual;
                    upd.stock_status = (p.STKActual > 0) ? "instock" : "outofstock";
                    upd.regular_price = precoRegular;
                    upd.meta_data = new List<ProductMeta>
            {
                new ProductMeta { key = "wholesale_customer_wholesale_price", value = precoRevendedor }
            };
                }

                await wc.Product.Update((ulong)paiAtual.id, upd);

                // 6) Marca (via endpoint existente) e bloqueios — igual ao teu fluxo
                if (!string.IsNullOrWhiteSpace(p.Marca))
                {
                    string nomeMarca = p.Marca.Trim();
                    await VerificarOuCriarMarca(nomeMarca);
                    await PostWpCustomAsync("/wp-json/custom-sync/v1/atribuir-marca/",
                        new { id = paiAtual.id.ToString(), marca = nomeMarca },
                        "atribuir-marca produto " + paiAtual.id);
                }

                bool bloquearCompra = (p.CDU_WebNaoVender ?? 0) != 0;
                bool bloquearEnvio = (p.CDU_WebNaoEnviar ?? 0) != 0;
                await AtualizarCamposBloqueioWooCommerce(paiAtual.id.ToString(), bloquearCompra, bloquearEnvio);

                LogHelper.LogInfo("Produto PAI " + p.Artigo + " atualizado (merge de atributos + preço/wholesale/stock se simples).");
                return true;
            }
            catch (Exception ex)
            {
                var (code, _, resourceId, _) = ParseWooError(ex);
                var lower = (ex.Message ?? "").ToLowerInvariant();

                if (code == "product_invalid_sku" ||
                    (code == "woocommerce_rest_product_not_created" && lower.Contains("lookup")))
                {
                    var alvo = await ResolveProductByConflict(wc, p.Artigo, resourceId);
                    if (alvo != null && alvo.id != produtoExistente.id)
                    {
                        LogHelper.LogInfo($"[UPDATE→SWITCH] Conflito de SKU '{p.Artigo}'. Mudar update para ID {alvo.id}.");
                        produtoExistente = alvo;
                        attempt = 0;
                        goto RETRY_UPDATE_PAI;
                    }
                }

                if (await MaybeTransientWait(ex, attempt++, "AtualizarProdutoPai " + p.Artigo))
                {
                    wc = await ReiniciarConexaoWooCommerce();
                    goto RETRY_UPDATE_PAI;
                }

                LogHelper.LogException("Erro ao atualizar produto PAI " + p.Artigo, ex);
                return false;
            }
        }


        // VARIAÇÃO — devolve true só quando a operação foi mesmo concluída
        static async Task<bool> CriarVariacao(WCObject wc, ProdutoRow p, ulong parentId)
        {
            int attempt = 0;
        RETRY_CREATE_VAR:
            try
            {
                string sku = (p.Artigo ?? "").Trim().ToUpperInvariant();

                // GARANTIR que o PAI tem os atributos certos antes de criar a variação
                await VerificarOuAdicionarAtributosProdutoPai(wc, p, parentId);

                List<VariationAttribute> atributosVariacao = new List<VariationAttribute>();

                if (!string.IsNullOrWhiteSpace(p.TipoDim1) && !string.IsNullOrWhiteSpace(p.RubDim1))
                {
                    WCv3.ProductAttribute attr;
                    WCv3.ProductAttributeTerm term;
                    (attr, term) = await EnsureAttributeAndTerm(wc, p.TipoDim1.Trim(), p.RubDim1.Trim());
                    atributosVariacao.Add(new VariationAttribute
                    {
                        id = (ulong?)attr.id,
                        name = null,
                        option = term.name
                    });
                }

                if (!string.IsNullOrWhiteSpace(p.TipoDim2) && !string.IsNullOrWhiteSpace(p.RubDim2))
                {
                    WCv3.ProductAttribute attr2;
                    WCv3.ProductAttributeTerm term2;
                    (attr2, term2) = await EnsureAttributeAndTerm(wc, p.TipoDim2.Trim(), p.RubDim2.Trim());
                    atributosVariacao.Add(new VariationAttribute
                    {
                        id = (ulong?)attr2.id,
                        name = null,
                        option = term2.name
                    });
                }

                decimal precoRegular = p.PVP2 ?? 0m;

                List<VariationMeta> productMetaVariation = new List<VariationMeta>();
                // InvariantCulture: em máquinas pt-PT o ToString() daria "12,5" e o Woo interpretava mal o preço
                string valMeta = (p.PVP1 == null || p.PVP1 == 0m)
                    ? (p.PVP2 ?? 0m).ToString(System.Globalization.CultureInfo.InvariantCulture)
                    : p.PVP1.Value.ToString(System.Globalization.CultureInfo.InvariantCulture);

                productMetaVariation.Add(new VariationMeta
                {
                    key = "wholesale_customer_wholesale_price",
                    value = valMeta
                });

                Variation novaVariacao = new Variation
                {
                    sku = sku,
                    regular_price = precoRegular,
                    stock_quantity = p.STKActual,
                    manage_stock = true,
                    attributes = atributosVariacao,
                    meta_data = productMetaVariation
                };

                Variation variacaoCriada = await wc.Product.Variations.Add(novaVariacao, parentId);

                // garantir que o pai está como "variable" (1× por pai — cache interna)
                await GarantirTipoVariavel(wc, parentId, "ID " + parentId);

                bool bloquearCompra = (p.CDU_WebNaoVender ?? 0) != 0;
                bool bloquearEnvio = (p.CDU_WebNaoEnviar ?? 0) != 0;
                await AtualizarCamposBloqueioWooCommerce(variacaoCriada.id.ToString(), bloquearCompra, bloquearEnvio);

                // refresh cache de variações do pai
                await RefreshParentVariationCache(wc, parentId);

                LogHelper.LogInfo("Variação " + sku + " criada com sucesso.");
                return true;
            }
            catch (Exception ex)
            {
                string code;
                string msg;
                ulong? resourceId;
                string uniqueSku;
                (code, msg, resourceId, uniqueSku) = ParseWooError(ex);
                string lower = (ex.Message ?? "").ToLowerInvariant();

                bool isSkuDup = code == "product_invalid_sku"
                                || lower.Contains("unique_sku")
                                || (lower.Contains("sku") && lower.Contains("exists"));

                if (isSkuDup)
                {
                    attempt++;

                    if (attempt <= 2)
                    {
                        LogHelper.LogError(string.Format(
                            "[VAR/CREATE] Duplicado de SKU '{0}' (tentativa {1}). Vou APAGAR TODOS os posts com este SKU e recriar…",
                            p.Artigo, attempt));

                        // apaga TUDO o que tenha este SKU no site (produto ou variação)
                        await DeleteAllPostsBySkuAsync(p.Artigo);

                        // limpa cache local do pai e reinicia ligação Woo
                        await RefreshParentVariationCache(wc, parentId);
                        wc = await ReiniciarConexaoWooCommerce();

                        goto RETRY_CREATE_VAR;
                    }

                    LogHelper.LogError(string.Format(
                        "[VAR/CREATE] SKU '{0}' continua a dar duplicado após {1} tentativas. " +
                        "A variação NÃO será criada para evitar loop infinito.",
                        p.Artigo, attempt));

                    return false;
                }

                if (await MaybeTransientWait(ex, attempt++, "CriarVariacao " + p.Artigo))
                {
                    wc = await ReiniciarConexaoWooCommerce();
                    goto RETRY_CREATE_VAR;
                }

                LogHelper.LogException("Erro ao criar variação " + p.Artigo, ex);
                return false;
            }
        }



        static async Task<bool> AtualizarVariacao(WCObject wc, ProdutoRow p, ulong variacaoId, ulong parentId)
        {
            int attempt = 0;
        RETRY_UPDATE_VAR:
            try
            {
                decimal precoRegular = p.PVP2 ?? 0m;
                int stockQuantity = p.STKActual;

                // Garante atributos no PAI antes de mexer na variação
                await VerificarOuAdicionarAtributosProdutoPai(wc, p, parentId);

                var attrs = new List<VariationAttribute>();

                if (!string.IsNullOrWhiteSpace(p.TipoDim1) && !string.IsNullOrWhiteSpace(p.RubDim1))
                {
                    var (attr, term) = await EnsureAttributeAndTerm(wc, p.TipoDim1.Trim(), p.RubDim1.Trim());
                    attrs.Add(new VariationAttribute
                    {
                        id = (ulong?)attr.id,
                        name = null,          // usar id -> pa_xxx (consistente com criação)
                        option = term.name
                    });
                }

                if (!string.IsNullOrWhiteSpace(p.TipoDim2) && !string.IsNullOrWhiteSpace(p.RubDim2))
                {
                    var (attr2, term2) = await EnsureAttributeAndTerm(wc, p.TipoDim2.Trim(), p.RubDim2.Trim());
                    attrs.Add(new VariationAttribute
                    {
                        id = (ulong?)attr2.id,
                        name = null,          // idem
                        option = term2.name
                    });
                }

                var meta = new List<VariationMeta>
        {
            new VariationMeta {
                key = "wholesale_customer_wholesale_price",
                // InvariantCulture: evita "12,5" em máquinas pt-PT
                value = (p.PVP1 == null || p.PVP1 == 0m ? (p.PVP2 ?? 0m) : p.PVP1.Value).ToString(System.Globalization.CultureInfo.InvariantCulture)
            }
        };

                var patch = new Variation
                {
                    id = variacaoId,
                    sku = p.Artigo,
                    regular_price = precoRegular,
                    stock_quantity = stockQuantity,
                    manage_stock = true,
                    attributes = attrs,
                    meta_data = meta
                };

                await wc.Product.Variations.Update(variacaoId, patch, parentId);

                // garante que o PAI está como 'variable' (1× por pai — cache interna)
                await GarantirTipoVariavel(wc, parentId, "ID " + parentId);

                bool bloquearCompra = (p.CDU_WebNaoVender ?? 0) != 0;
                bool bloquearEnvio = (p.CDU_WebNaoEnviar ?? 0) != 0;
                await AtualizarCamposBloqueioWooCommerce(patch.id.ToString(), bloquearCompra, bloquearEnvio);

                LogHelper.LogInfo("Variação " + p.Artigo + " atualizada com sucesso.");
                return true;
            }
            catch (Exception ex)
            {
                if (await MaybeTransientWait(ex, attempt++, "AtualizarVariacao " + p.Artigo))
                {
                    wc = await ReiniciarConexaoWooCommerce();
                    goto RETRY_UPDATE_VAR;
                }
                LogHelper.LogException("Erro ao atualizar variação " + p.Artigo, ex);
                return false;
            }
        }



        #endregion

        #region Promoções
        static async Task AtualizarPromocaoProduto(WCObject wc, StdBELista promocoes)
        {
            if (promocoes == null || promocoes.Vazia())
            {
                LogHelper.LogInfo("Nenhuma promoção para atualizar.");
                return;
            }

            promocoes.Inicio();
            while (!promocoes.NoFim())
            {
                string sku = null;
                try
                {
                    await WaitIfInMaintenance("Promocoes");

                    // Leituras defensivas: NULL da BD não pode rebentar o loop, e os casts
                    // diretos ((decimal)/(DateTime)) falhavam se o driver devolvesse outro tipo.
                    object skuVal = promocoes.Valor("Artigo");
                    sku = (skuVal == null || skuVal is DBNull) ? null : skuVal.ToString().Trim();
                    if (string.IsNullOrEmpty(sku))
                    {
                        promocoes.Seguinte();
                        continue;
                    }

                    object paiVal = promocoes.Valor("ArtigoPai");
                    string artigoPai = (paiVal == null || paiVal is DBNull) ? null : paiVal.ToString().Trim();

                    object descVal = promocoes.Valor("Desconto");
                    decimal desconto = (descVal == null || descVal is DBNull) ? 0 : Convert.ToDecimal(descVal);

                    object descUniVal = promocoes.Valor("Descontouni");
                    decimal? descontouni = (descUniVal == null || descUniVal is DBNull) ? (decimal?)null : Convert.ToDecimal(descUniVal);

                    object dataIniVal = promocoes.Valor("DataInicial");
                    DateTime? dataIni = (dataIniVal == null || dataIniVal is DBNull) ? (DateTime?)null : Convert.ToDateTime(dataIniVal);

                    object dataFimVal = promocoes.Valor("DataFinal");
                    DateTime? dataFim = (dataFimVal == null || dataFimVal is DBNull) ? (DateTime?)null : Convert.ToDateTime(dataFimVal);

                    if (!string.IsNullOrEmpty(artigoPai))
                    {
                        // pai
                        SkuEntry pe;
                        if (!TryGetBySku(artigoPai, out pe) || pe.type != "product")
                        {
                            List<Product> paisProdutos = await wc.Product.GetAll(new Dictionary<string, string> { { "sku", artigoPai } });
                            if (paisProdutos.Count == 0)
                            {
                                LogHelper.LogError(string.Format("[PROMO] Produto pai '{0}' não encontrado para variação '{1}'.", artigoPai, sku));
                                promocoes.Seguinte();
                                continue;
                            }
                            _skuMap[artigoPai] = new SkuEntry { id = (ulong)paisProdutos[0].id, sku = artigoPai, type = "product", parent_id = 0 };
                            pe = _skuMap[artigoPai];
                        }

                        ulong parentId = pe.id;
                        Dictionary<string, Variation> varCache = await GetParentVariationCache(wc, parentId);

                        Variation varTarget;
                        if (!varCache.TryGetValue(NormalizarSku(sku), out varTarget))
                        {
                            LogHelper.LogError(string.Format("[PROMO] Variação '{0}' não encontrada dentro do pai '{1}'.", sku, artigoPai));
                            promocoes.Seguinte();
                            continue;
                        }

                        decimal precoRegularVar = varTarget.regular_price ?? 0m;
                        if (precoRegularVar <= 0m)
                        {
                            LogHelper.LogInfo(string.Format("[PROMO] Saltada variação {0}: regular_price=0.", sku));
                            promocoes.Seguinte();
                            continue;
                        }

                        decimal? sale = null;
                        if (descontouni.HasValue && descontouni.Value > 0)
                            sale = descontouni.Value;
                        else if (desconto > 0)
                            sale = precoRegularVar * (1 - desconto / 100m);

                        if (sale.HasValue && sale.Value > 0 && sale.Value < precoRegularVar)
                        {
                            varTarget.sale_price = sale.Value;
                            varTarget.date_on_sale_from = dataIni;
                            varTarget.date_on_sale_to = dataFim;
                        }
                        else
                        {
                            varTarget.sale_price = null;
                            varTarget.date_on_sale_from = null;
                            varTarget.date_on_sale_to = null;
                        }

                        await wc.Product.Variations.Update((ulong)varTarget.id, varTarget, parentId);
                        LogHelper.LogInfo(string.Format("Promoção da variação {0} atualizada com sucesso.", sku));

                        await RefreshParentVariationCache(wc, parentId);
                        promocoes.Seguinte();
                        continue;
                    }

                    // produto simples/pai
                    Product produto = null;
                    SkuEntry e;
                    if (TryGetBySku(sku, out e) && e.type == "product")
                    {
                        produto = await wc.Product.Get(e.id);
                    }
                    else
                    {
                        List<Product> produtos = await wc.Product.GetAll(new Dictionary<string, string> { { "sku", sku } });
                        if (produtos.Count == 0)
                        {
                            LogHelper.LogError(string.Format("[PROMO] Produto com SKU '{0}' não encontrado.", sku));
                            promocoes.Seguinte();
                            continue;
                        }
                        produto = produtos[0];
                        _skuMap[sku] = new SkuEntry { id = (ulong)produto.id, sku = sku, type = "product", parent_id = 0 };
                    }

                    decimal precoRegularProd = produto.regular_price ?? 0m;
                    if (precoRegularProd <= 0m)
                    {
                        LogHelper.LogInfo(string.Format("[PROMO] Saltado produto {0}: regular_price=0.", sku));
                        promocoes.Seguinte();
                        continue;
                    }

                    decimal? saleProd = null;
                    if (descontouni.HasValue && descontouni.Value > 0)
                        saleProd = descontouni.Value;
                    else if (desconto > 0)
                        saleProd = precoRegularProd * (1 - desconto / 100m);

                    if (saleProd.HasValue && saleProd.Value > 0 && saleProd.Value < precoRegularProd)
                    {
                        produto.sale_price = saleProd.Value;
                        produto.date_on_sale_from = dataIni;
                        produto.date_on_sale_to = dataFim;
                    }
                    else
                    {
                        produto.sale_price = null;
                        produto.date_on_sale_from = null;
                        produto.date_on_sale_to = null;
                    }

                    await wc.Product.Update((ulong)produto.id, produto);
                    LogHelper.LogInfo(string.Format("Promoção do produto {0} atualizada com sucesso.", sku));
                }
                catch (Exception ex)
                {
                    // Não voltar a chamar promocoes.Valor(...) aqui — podia lançar segunda exceção dentro do catch
                    SyncReporter.AddError();
                    LogHelper.LogException("Erro ao atualizar promoção para " + (sku ?? "(sku desconhecido)"), ex);
                }

                promocoes.Seguinte();
            }
        }
        #endregion

        #region Assembly Resolve
        static System.Reflection.Assembly CurrentDomain_AssemblyResolve(object sender, ResolveEventArgs args)
        {
            System.Reflection.AssemblyName assemblyName = new System.Reflection.AssemblyName(args.Name);

            // Se a variável de ambiente do Primavera não existir nesta máquina,
            // devolve null (erro claro de assembly em falta) em vez de ArgumentNullException.
            string percursoPrimavera = Environment.GetEnvironmentVariable("PERCURSOSGV100", EnvironmentVariableTarget.Machine);
            if (string.IsNullOrEmpty(percursoPrimavera))
                return null;

            string assemblyFullName = Path.Combine(percursoPrimavera, assemblyName.Name + ".dll");

            if (File.Exists(assemblyFullName) && !assemblyName.Name.Contains("Newtonsoft.Json"))
                return System.Reflection.Assembly.LoadFile(assemblyFullName);
            else
                return null;
        }
        #endregion

        #region Atributos no Pai
        // Cache por execução: combinações pai+dimensões já garantidas — evita reler e
        // reprocessar o pai por cada filho com as mesmas dimensões (era chamado 2-3× por variação)
        static readonly HashSet<string> _paiAttrsGarantidos = new HashSet<string>(StringComparer.OrdinalIgnoreCase);

        static async Task VerificarOuAdicionarAtributosProdutoPai(WCObject wc, ProdutoRow p, ulong parentId)
        {
            string attrsKey = string.Format("{0}|{1}|{2}|{3}|{4}",
                parentId, (p.TipoDim1 ?? "").Trim(), (p.RubDim1 ?? "").Trim(), (p.TipoDim2 ?? "").Trim(), (p.RubDim2 ?? "").Trim());
            if (_paiAttrsGarantidos.Contains(attrsKey))
                return;

            Func<string, string> Normalize = s =>
            {
                string tmp = (s ?? "").Trim().ToLowerInvariant();
                string n = tmp.Normalize(NormalizationForm.FormD);
                var sb = new StringBuilder();
                foreach (char c in n)
                {
                    if (System.Globalization.CharUnicodeInfo.GetUnicodeCategory(c) != System.Globalization.UnicodeCategory.NonSpacingMark)
                        sb.Append(c);
                }
                return sb.ToString().Normalize(NormalizationForm.FormC);
            };

            try
            {
                // 1) Ler o produto pai atual (não vamos perder nada que já exista)
                var produtoPai = await wc.Product.Get(parentId);

                // Garantir lista existente (NUNCA null) e clonar para trabalhar em memória
                var atributosAtuais = (produtoPai.attributes ?? new List<WCv3.ProductAttributeLine>())
                    .Select(a => new WCv3.ProductAttributeLine
                    {
                        id = a.id,
                        name = a.name,                 // manter se for custom; vamos forçar null apenas quando id existir (taxonomia global)
                visible = a.visible,
                        variation = a.variation,
                        options = (a.options ?? new List<string>()).ToList()
                    })
                    .ToList();

                bool changed = false;

                // 2) Garante que (TipoDim, RubDim) existem como atributos globais no PAI
                async Task EnsureOnParent(string tipo, string valor)
                {
                    if (string.IsNullOrWhiteSpace(tipo) || string.IsNullOrWhiteSpace(valor)) return;

                    // Cria/garante a taxonomia e o termo no Woo (função existente no teu código)
                    var (attr, term) = await EnsureAttributeAndTerm(wc, tipo.Trim(), valor.Trim());

                    // Procura o atributo pelo ID global (prioridade) ou por nome normalizado se for custom
                    WCv3.ProductAttributeLine existente = null;

                    if (attr.id.HasValue && attr.id.Value > 0)
                        existente = atributosAtuais.FirstOrDefault(a => a.id == attr.id);
                    if (existente == null)
                        existente = atributosAtuais.FirstOrDefault(a => a.id == null &&
                                                                        !string.IsNullOrEmpty(a.name) &&
                                                                        Normalize(a.name) == Normalize(attr.name));

                    // Se não existe no pai, adiciona um novo, ligado à taxonomia global (id) e com o termo por nome
                    if (existente == null)
                    {
                        atributosAtuais.Add(new WCv3.ProductAttributeLine
                        {
                            id = attr.id,              // usar taxonomia global
                            name = null,               // garantir que NÃO fica custom quando há id
                            options = new List<string> { term.name },
                            visible = true,
                            variation = true
                        });
                        changed = true;
                        return;
                    }

                    // Existe: normalizar flags e options
                    if (existente.options == null) existente.options = new List<string>();

                    // Adicionar o termo (se faltar) — Woo usa o NOME do termo em ProductAttributeLine.options
                    bool hasTerm = existente.options.Any(o => Normalize(o) == Normalize(term.name));
                    if (!hasTerm)
                    {
                        existente.options.Add(term.name);
                        changed = true;
                    }

                    // Deduplicar e ordenar suavemente (evita updates em loop por ordem)
                    var dedup = existente.options
                        .Where(o => !string.IsNullOrWhiteSpace(o))
                        .Distinct(StringComparer.OrdinalIgnoreCase)
                        .OrderBy(x => x, StringComparer.OrdinalIgnoreCase)
                        .ToList();

                    if (existente.options.Count != dedup.Count || !existente.options.SequenceEqual(dedup, StringComparer.OrdinalIgnoreCase))
                    {
                        existente.options = dedup;
                        changed = true;
                    }

                    // Atributo deve ser visível e usado para variação
                    if (existente.visible != true) { existente.visible = true; changed = true; }
                    if (existente.variation != true) { existente.variation = true; changed = true; }

                    // Se tem id (taxonomia global), name deve ser null para não cair em "custom attribute"
                    if (existente.id.HasValue && existente.id.Value > 0 && !string.IsNullOrEmpty(existente.name))
                    {
                        existente.name = null;
                        changed = true;
                    }
                }

                await EnsureOnParent(p.TipoDim1, p.RubDim1);
                await EnsureOnParent(p.TipoDim2, p.RubDim2);

                // 3) Se houve alterações, fazer UPDATE "parcial" e seguro
                if (changed)
                {
                    // Forçar type=variable apenas se não estiver correto e existirem atributos de variação
                    bool temAttrDeVariacao = atributosAtuais.Any(a => a.variation == true && (a.options?.Count > 0));
                    string novoTipo = produtoPai.type;
                    if (temAttrDeVariacao && !string.Equals(produtoPai.type, "variable", StringComparison.OrdinalIgnoreCase))
                        novoTipo = "variable";

                    // Construir payload limpo (não enviar lixo que possa limpar outras props)
                    var up = new WCv3.Product
                    {
                        type = novoTipo,
                        attributes = atributosAtuais.Select(a => new WCv3.ProductAttributeLine
                        {
                            id = a.id,                                 // mantém ligação à taxonomia global
                            name = a.id.HasValue ? null : a.name,      // se global, name=null; se custom (sem id), manter name
                            visible = true,
                            variation = true,
                            options = (a.options ?? new List<string>()).ToList()
                        }).ToList()
                    };

                    await wc.Product.Update(parentId, up);

                    LogHelper.LogInfo($"[PAI-ATTR] Atributos do pai {produtoPai.sku} (ID {parentId}) verificados/atualizados. Total atributos: {up.attributes.Count}.");
                }
                else
                {
                    LogHelper.LogInfo($"[PAI-ATTR] Atributos do pai {produtoPai.sku} já estavam corretos. Nenhuma alteração.");
                }

                _paiAttrsGarantidos.Add(attrsKey); // só marca como garantido quando correu bem
            }
            catch (Exception ex)
            {
                LogHelper.LogException($"Erro ao verificar/adicionar atributos no produto pai {parentId}", ex);
            }
        }


        #endregion

        #region Outros Utils Woo
        private static string NormalizarSku(string sku)
        {
            return (sku ?? "").Trim().ToLowerInvariant();
        }



        static async Task<WCObject> ReiniciarConexaoWooCommerce()
        {
            LogHelper.LogInfo("Reiniciando conexão com WooCommerce...");
            restAPI = new RestAPI(wooApiUrl, wc_key, wc_secret);
            return new WCObject(restAPI);
        }

        static async Task AtualizarEstoqueProdutoPai(WCObject wc, ulong produtoPaiId)
        {
            try
            {
                // 1) Ler TODAS as variações com paginação
                List<Variation> todas = new List<Variation>();
                int page = 1;
                const int PER_PAGE = 100;

                while (true)
                {
                    var parms = new Dictionary<string, string>
            {
                { "per_page", PER_PAGE.ToString() },
                { "page", page.ToString() }
            };

                    List<Variation> varsPage = await wc.Product.Variations.GetAll(produtoPaiId, parms);

                    if (varsPage == null || varsPage.Count == 0)
                        break;

                    todas.AddRange(varsPage);

                    if (varsPage.Count < PER_PAGE)
                        break;

                    page++;
                }

                if (todas.Count == 0)
                {
                    // Sem variações → pai sem stock
                    Product paiSemVar = new Product
                    {
                        manage_stock = false,
                        stock_quantity = null,
                        stock_status = "outofstock"
                    };

                    await wc.Product.Update(produtoPaiId, paiSemVar);
                    return;
                }

                // 2) Verificar se alguma variação tem stock
                bool hasStock = false;

                foreach (Variation v in todas)
                {
                    bool manageStock = v.manage_stock is bool && (bool)v.manage_stock;
                    int qty = v.stock_quantity ?? 0;
                    bool inStockByStatus =
                        v.stock_status is string &&
                        ((string)v.stock_status).Equals("instock", StringComparison.OrdinalIgnoreCase);

                    if (manageStock)
                    {
                        if (qty > 0)
                        {
                            hasStock = true;
                            break;
                        }
                    }
                    else
                    {
                        if (inStockByStatus)
                        {
                            hasStock = true;
                            break;
                        }
                    }
                }

                // 3) Atualizar o pai: stock é apenas “indicativo”
                Product updatePai = new Product
                {
                    manage_stock = false,
                    stock_quantity = null,
                    stock_status = hasStock ? "instock" : "outofstock"
                };

                await wc.Product.Update(produtoPaiId, updatePai);

                LogHelper.LogInfo(string.Format(
                    "[PAI-STOCK] Pai {0} atualizado para {1} (variações com stock: {2})",
                    produtoPaiId,
                    hasStock ? "instock" : "outofstock",
                    todas.Count
                ));
            }
            catch (Exception ex)
            {
                LogHelper.LogException("Erro ao atualizar o estoque do produto pai " + produtoPaiId, ex);
            }
        }


        /// <summary>
        /// POST genérico aos endpoints custom do WordPress (custom-sync), com autenticação
        /// Basic, ExecuteAsync e retry com backoff. Devolve true em caso de sucesso.
        /// Antes: cada endpoint fazia uma tentativa única, síncrona e sem auth — uma falha
        /// pontual deixava marca/bloqueios errados no site sem retry.
        /// </summary>
        static async Task<bool> PostWpCustomAsync(string path, object body, string label, int tentativas = 3)
        {
            for (int i = 1; i <= tentativas; i++)
            {
                try
                {
                    RestClient client = new RestClient(wpBase + path);
                    RestRequest request = new RestRequest("", Method.Post);
                    request.AddHeader("Authorization", "Basic " + credentialsAdmin);
                    request.AddHeader("Content-Type", "application/json");
                    request.AddJsonBody(body);

                    RestResponse response = await client.ExecuteAsync(request);
                    if (response.IsSuccessful)
                    {
                        LogHelper.LogInfo(string.Format("[WP] {0} OK.", label));
                        return true;
                    }

                    LogHelper.LogError(string.Format("[WP] {0} falhou (tentativa {1}/{2}): {3} - {4}",
                        label, i, tentativas, response.StatusCode, response.Content));
                }
                catch (Exception ex)
                {
                    LogHelper.LogError(string.Format("[WP] {0} exceção (tentativa {1}/{2}): {3}",
                        label, i, tentativas, ex.Message));
                }

                if (i < tentativas)
                    await Task.Delay(1000 * i);
            }

            return false;
        }

        static async Task AtualizarCamposBloqueioWooCommerce(string productId, bool bloquearCompra, bool bloquearEnvio)
        {
            var body = new
            {
                id = productId,
                block_purchase = bloquearCompra ? "yes" : "no",
                pickup_only = bloquearEnvio ? "yes" : "no"
            };

            await PostWpCustomAsync("/wp-json/custom-sync/v1/update-meta/", body, "update-meta produto " + productId);
        }

        static async Task VerificarOuCriarMarca(string nomeMarca)
        {
            await PostWpCustomAsync("/wp-json/custom-sync/v1/criar-marca/", new { nome = nomeMarca }, "criar-marca " + nomeMarca);
        }

        static async Task EliminarProdutoWooCommerceFast(WCObject wc, string sku, string artigoPai)
        {
            try
            {
                if (!string.IsNullOrEmpty(artigoPai))
                {
                    SkuEntry pe;
                    if (!TryGetBySku(artigoPai, out pe) || pe.type != "product")
                    {
                        List<Product> pais = await wc.Product.GetAll(new Dictionary<string, string> { { "sku", artigoPai } });
                        if (pais.Any())
                        {
                            _skuMap[artigoPai] = new SkuEntry
                            {
                                id = (ulong)pais[0].id,
                                sku = artigoPai,
                                type = "product",
                                parent_id = 0
                            };
                        }
                        else
                        {
                            return;
                        }
                        pe = _skuMap[artigoPai];
                    }
                    ulong parentId = pe.id;
                    Dictionary<string, Variation> cache = await GetParentVariationCache(wc, parentId);
                    Variation v;
                    if (cache.TryGetValue(NormalizarSku(sku), out v))
                    {
                        await wc.Product.Variations.Delete((ulong)v.id, parentId, true);
                        await RefreshParentVariationCache(wc, parentId);
                        LogHelper.LogInfo("Variação " + sku + " removida com sucesso.");
                    }
                }
                else
                {
                    SkuEntry e;
                    if (TryGetBySku(sku, out e) && e.type == "product")
                    {
                        await wc.Product.Delete(e.id, true);
                        _skuMap.Remove(sku);
                        LogHelper.LogInfo("Produto " + sku + " removido com sucesso.");
                    }
                    else
                    {
                        List<Product> produtos = await wc.Product.GetAll(new Dictionary<string, string> { { "sku", sku } });
                        if (produtos.Any())
                        {
                            await wc.Product.Delete((ulong)produtos.First().id, true);
                            _skuMap.Remove(sku);
                            LogHelper.LogInfo("Produto " + sku + " removido com sucesso.");
                        }
                    }
                }
            }
            catch (Exception ex)
            {
                LogHelper.LogException("Erro ao eliminar produto/variação " + sku, ex);
            }
        }
        #endregion

        #region E-mail e endpoints custom (DOIS DESTINATÁRIOS)
        public static class EmailHelper
        {
            // Email NORMAL: logs + CSVs → noreplay@ateneya.com (mantido)
            public static void SendSyncLogEmail(string site, DateTime when, IEnumerable<string> attachments)
            {
                SendSyncLogEmail(site, when, attachments, null, null);
            }

            public static void SendSyncLogEmail(string site, DateTime when, IEnumerable<string> attachments, string subjectOverride, string bodyOverride)
            {
                try
                {
                    ServicePointManager.SecurityProtocol = SecurityProtocolType.Tls12;

                    // Só ignora erros de certificado se explicitamente pedido no App.config —
                    // o callback antigo desativava a validação TLS para TODO o processo.
                    if (string.Equals(Cfg("SmtpIgnoreCertErrors", "false"), "true", StringComparison.OrdinalIgnoreCase))
                        ServicePointManager.ServerCertificateValidationCallback = delegate { return true; };

                    string subject = !string.IsNullOrEmpty(subjectOverride)
                        ? subjectOverride
                        : string.Format("[Sync Log] {0} - {1:yyyy-MM-dd HH:mm}", site, when);

                    string body = !string.IsNullOrEmpty(bodyOverride)
                        ? bodyOverride
                        : string.Format(@"✅ Sincronização finalizada para o site:
{0}

🗓 Data: {1:yyyy-MM-dd HH:mm}
📎 Ficheiros (consulta + execução + logs) em anexo.

-- Este e-mail foi gerado automaticamente.", site, when);

                    MailMessage mail = new MailMessage();
                    mail.From = new MailAddress(Cfg("EmailFrom", "noreplay@ateneya.com"));
                    mail.Subject = subject;
                    mail.Body = body;
                    mail.To.Add(Cfg("EmailLogsTo", "noreplay@ateneya.com"));

                    if (attachments != null)
                    {
                        foreach (string path in attachments)
                        {
                            if (!string.IsNullOrWhiteSpace(path) && File.Exists(path))
                            {
                                FileStream fs = new FileStream(path, FileMode.Open, FileAccess.Read, FileShare.ReadWrite);
                                Attachment att = new Attachment(fs, Path.GetFileName(path));
                                mail.Attachments.Add(att);

                                Console.WriteLine("Anexado: " + path);
                                Program.LogHelper.LogInfo("Anexado: " + path);
                            }
                        }
                    }

                    SmtpClient smtpClient = new SmtpClient(Cfg("SmtpHost", "smtp-pt.securemail.pro"), int.Parse(Cfg("SmtpPortCustom", "587")));
                    smtpClient.Credentials = new NetworkCredential(Cfg("SmtpUser"), Cfg("SmtpPassword"));
                    smtpClient.EnableSsl = true;

                    smtpClient.Send(mail);
                    Console.WriteLine("Email NORMAL enviado com sucesso (logs e csv).");
                }
                catch (Exception ex)
                {
                    Program.LogHelper.LogException("Erro ao enviar email NORMAL", ex);
                    Console.WriteLine("Erro ao enviar email NORMAL: " + ex);
                }
            }

            // Email de PROBLEMAS: órfãos, erros, saltados, apagados por pai anulado → geral@lojaamster.com
            public static void SendProblemsEmail(string site, DateTime when, string problemCsvPath)
            {
                try
                {
                    if (string.IsNullOrWhiteSpace(problemCsvPath) || !File.Exists(problemCsvPath))
                        return;

                    ServicePointManager.SecurityProtocol = SecurityProtocolType.Tls12;

                    // Só ignora erros de certificado se explicitamente pedido no App.config —
                    // o callback antigo desativava a validação TLS para TODO o processo.
                    if (string.Equals(Cfg("SmtpIgnoreCertErrors", "false"), "true", StringComparison.OrdinalIgnoreCase))
                        ServicePointManager.ServerCertificateValidationCallback = delegate { return true; };

                    string subject = string.Format("[Sync Problemas] {0} - {1:yyyy-MM-dd HH:mm}", site, when);

                    string body = string.Format(
@"⚠ Foram detetados produtos com problemas na sincronização do site {0}.

Em anexo segue o ficheiro CSV com:
- Produtos órfãos
- Produtos com erro
- Produtos saltados (pai não encontrado no WooCommerce)
- Produtos apagados devido a pai anulado no ERP

Por favor verifique e corrija no ERP/WooCommerce conforme necessário.

-- Gerado automaticamente pelo sincronizador.",
                        site);

                    MailMessage mail = new MailMessage();
                    mail.From = new MailAddress(Cfg("EmailFrom", "noreplay@ateneya.com"));
                    mail.Subject = subject;
                    mail.Body = body;
                    mail.To.Add(Cfg("EmailProblemsTo", "geral@lojaamster.com"));

                    FileStream fs = new FileStream(problemCsvPath, FileMode.Open, FileAccess.Read, FileShare.ReadWrite);
                    Attachment att = new Attachment(fs, Path.GetFileName(problemCsvPath));
                    mail.Attachments.Add(att);

                    SmtpClient smtpClient = new SmtpClient(Cfg("SmtpHost", "smtp-pt.securemail.pro"), int.Parse(Cfg("SmtpPortCustom", "587")));
                    smtpClient.Credentials = new NetworkCredential(Cfg("SmtpUser"), Cfg("SmtpPassword"));
                    smtpClient.EnableSsl = true;

                    smtpClient.Send(mail);
                    Console.WriteLine("Email de PROBLEMAS enviado com sucesso para geral@lojaamster.com.");
                }
                catch (Exception ex)
                {
                    Program.LogHelper.LogException("Erro ao enviar email de PROBLEMAS", ex);
                    Console.WriteLine("Erro ao enviar email de PROBLEMAS: " + ex);
                }
            }
        }
        #endregion

        #region QueryLogHelper
        public static class QueryLogHelper
        {
            private static readonly object _lock = new object();

            public static void DumpListaToCsv(StdBELista lista, string filePath, params string[] campos)
            {
                if (lista == null || lista.Vazia()) return;
                if (string.IsNullOrWhiteSpace(filePath))
                {
                    Program.LogHelper.LogError("[DumpListaToCsv] filePath nulo/vazio.");
                    return;
                }

                string dir = Path.GetDirectoryName(filePath);
                if (!string.IsNullOrEmpty(dir))
                    Directory.CreateDirectory(dir);

                lock (_lock)
                {
                    using (StreamWriter sw = new StreamWriter(filePath, false, Encoding.UTF8))
                    {
                        sw.WriteLine(string.Join(";", campos));
                        lista.Inicio();
                        while (!lista.NoFim())
                        {
                            List<string> valores = new List<string>();
                            foreach (string c in campos)
                            {
                                object v;
                                try { v = lista.Valor(c); }
                                catch { v = null; }
                                valores.Add(CsvSafe(v));
                            }
                            sw.WriteLine(string.Join(";", valores));
                            lista.Seguinte();
                        }
                        lista.Inicio();
                    }
                }
            }

            private static string CsvSafe(object valor)
            {
                if (valor == null || valor is DBNull) return "";
                string s = Convert.ToString(valor);
                if (s == null) return "";
                s = s.Replace("\r\n", " ").Replace("\n", " ").Replace("\r", " ");
                if (s.Contains(";") || s.Contains("\""))
                    s = "\"" + s.Replace("\"", "\"\"") + "\"";
                return s;
            }
        }
        #endregion

        #region Auxiliares (Email anexos, Purge, Brand, SKU Map & Var Cache)
        static IEnumerable<string> ColetarAnexosSomenteDaExecucao(
            string logPath,
            string errorLogPath,
            string produtosCsv,
            string promocoesCsv,
            string execCsv,
            string problemasCsv)
        {
            List<string> anexos = new List<string>();

            Action<string> AddIfExists = delegate (string p)
            {
                if (!string.IsNullOrWhiteSpace(p))
                {
                    string abs = Path.GetFullPath(p);
                    if (File.Exists(abs))
                        anexos.Add(abs);
                }
            };

            AddIfExists(logPath);
            AddIfExists(errorLogPath);
            AddIfExists(produtosCsv);
            AddIfExists(promocoesCsv);
            AddIfExists(execCsv);
            AddIfExists(problemasCsv);

            Program.LogHelper.LogInfo("[EMAIL] Anexos (somente execução): " + string.Join(" | ", anexos));
            return anexos;
        }

        static void PurgeOldLogs(string logsDirectory, int keepDays)
        {
            try
            {
                if (string.IsNullOrWhiteSpace(logsDirectory) || !Directory.Exists(logsDirectory)) return;

                DateTime cutoff = DateTime.Now.AddDays(-keepDays);
                List<string> dirs = new List<string>();
                dirs.Add(logsDirectory);

                string queriesDir = Path.Combine(logsDirectory, "queries");
                if (Directory.Exists(queriesDir)) dirs.Add(queriesDir);

                foreach (string dir in dirs)
                {
                    foreach (string file in Directory.EnumerateFiles(dir, "*.*", SearchOption.TopDirectoryOnly))
                    {
                        try
                        {
                            FileInfo info = new FileInfo(file);
                            DateTime lastWrite = info.LastWriteTime;
                            if (lastWrite < cutoff)
                            {
                                info.IsReadOnly = false;
                                File.Delete(file);
                                Program.LogHelper.LogInfo(string.Format("[PURGE] Apagado: {0} (modificado em {1:yyyy-MM-dd HH:mm})", info.FullName, lastWrite));
                            }
                        }
                        catch (Exception exFile)
                        {
                            Program.LogHelper.LogError(string.Format("[PURGE] Falha ao apagar '{0}': {1}", file, exFile.Message));
                        }
                    }
                }
            }
            catch (Exception ex)
            {
                Program.LogHelper.LogException("[PURGE] Exceção na limpeza de logs", ex);
            }
        }


        static async Task<bool> ObterOnlyPublishedStockWordPress()
        {
            try
            {
                RestClient client = new RestClient(wpBase);
                RestRequest req = new RestRequest("/wp-json/custom-sync/v1/only-published-stock", Method.Get);
                req.AddHeader("Authorization", "Basic " + credentialsAdmin);

                RestResponse res = client.Execute(req);
                if (!res.IsSuccessful || string.IsNullOrWhiteSpace(res.Content))
                {
                    LogHelper.LogError(string.Format("[ONLY-PUBLISHED-STOCK] Erro ao obter flag: {0} - {1}", res.StatusCode, res.Content));
                    return false;
                }

                Dictionary<string, object> json = JsonConvert.DeserializeObject<Dictionary<string, object>>(res.Content);
                if (json != null && json.ContainsKey("only_published_with_stock") && json["only_published_with_stock"] != null)
                {
                    string val = json["only_published_with_stock"].ToString();
                    if (val == "1")
                    {
                        LogHelper.LogInfo("[ONLY-PUBLISHED-STOCK] Flag ativa recebida. Resetando para 0 no WordPress...");

                        RestRequest resetReq = new RestRequest("/wp-json/custom-sync/v1/only-published-stock", Method.Post);
                        resetReq.AddHeader("Authorization", "Basic " + credentialsAdmin);
                        resetReq.AddHeader("Content-Type", "application/json");
                        resetReq.AddJsonBody(new { valor = "0" });

                        RestResponse resetRes = client.Execute(resetReq);
                        if (!resetRes.IsSuccessful)
                        {
                            LogHelper.LogError(string.Format("[ONLY-PUBLISHED-STOCK] Falha ao resetar flag: {0} - {1}", resetRes.StatusCode, resetRes.Content));
                        }

                        return true; // estava ativa
                    }
                }
            }
            catch (Exception ex)
            {
                LogHelper.LogException("[ONLY-PUBLISHED-STOCK] Exceção ao obter/resetar flag", ex);
            }

            return false; // não estava ativa
        }

        public static async Task<HashSet<string>> LoadSkuFilterAsync(HttpClient client, string baseWpUrl)
        {
            // Garantir formatação
            if (!baseWpUrl.EndsWith("/"))
                baseWpUrl += "/";

            var url = baseWpUrl + "wp-json/custom-sync/v1/sku-filter";

            // 🔑 ADICIONAR AUTENTICAÇÃO ADMIN
            var request = new HttpRequestMessage(HttpMethod.Get, url);
            request.Headers.Add("Authorization", "Basic " + credentialsAdmin);

            var response = await client.SendAsync(request);
            if (!response.IsSuccessStatusCode)
                throw new HttpRequestException("Erro WP: " + response.StatusCode);

            var json = await response.Content.ReadAsStringAsync();
            var data = JsonConvert.DeserializeObject<SkuFilterResponse>(json);

            if (data == null || data.Items == null || data.Items.Count == 0)
                return null;

            var set = new HashSet<string>(StringComparer.OrdinalIgnoreCase);
            foreach (var sku in data.Items)
            {
                var s = (sku ?? "").Trim();
                if (!string.IsNullOrEmpty(s))
                    set.Add(s);
            }

            return set.Count > 0 ? set : null;
        }



        static async Task<string> ObterMarcaFiltroWordPress()
        {
            try
            {
                RestClient client = new RestClient(wpBase);
                RestRequest req = new RestRequest("/wp-json/custom-sync/v1/brand", Method.Get);
                req.AddHeader("Authorization", "Basic " + credentialsAdmin);
                RestResponse res = client.Execute(req);

                if (!res.IsSuccessful || string.IsNullOrWhiteSpace(res.Content))
                {
                    LogHelper.LogError(string.Format("[BRAND] Falha ao obter brand: {0} - {1}", res.StatusCode, res.Content));
                    return null;
                }

                Dictionary<string, object> json = JsonConvert.DeserializeObject<Dictionary<string, object>>(res.Content);
                if (json != null && json.ContainsKey("brand"))
                {
                    string brand = (json["brand"] != null ? json["brand"].ToString() : "").Trim();
                    if (!string.IsNullOrEmpty(brand))
                    {
                        // 👇 Assim que começamos a sincronizar, limpamos a marca no WP
                        LogHelper.LogInfo(string.Format("[BRAND] Marca recebida para sincronização: '{0}'. Limpando no WordPress...", brand));

                        RestRequest resetReq = new RestRequest("/wp-json/custom-sync/v1/brand", Method.Post);
                        resetReq.AddHeader("Authorization", "Basic " + credentialsAdmin);
                        resetReq.AddHeader("Content-Type", "application/json");
                        resetReq.AddJsonBody(new { brand = "" });

                        RestResponse resetRes = client.Execute(resetReq);
                        if (!resetRes.IsSuccessful)
                        {
                            LogHelper.LogError(string.Format("[BRAND] Falha ao limpar marca no WP: {0} - {1}", resetRes.StatusCode, resetRes.Content));
                        }

                        return brand;
                    }
                }
            }
            catch (Exception ex)
            {
                LogHelper.LogException("[BRAND] Exceção ao obter brand", ex);
            }
            return null;
        }


        static async Task BuildSkuMapAsync()
        {
            _skuMap.Clear();
            try
            {
                RestClient client = new RestClient(wpBase);
                int page = 1;
                int per = 2000;
                int totalRecebidos = 0;

                while (true)
                {
                    RestRequest req = new RestRequest("/wp-json/custom-sync/v1/sku-map", Method.Get);
                    req.AddHeader("Authorization", "Basic " + credentialsAdmin);
                    req.AddParameter("per_page", per);
                    req.AddParameter("page", page);

                    RestResponse res = await client.ExecuteAsync(req);
                    if (!res.IsSuccessful || string.IsNullOrWhiteSpace(res.Content))
                    {
                        LogHelper.LogError(string.Format("[SKU-MAP] Falha a obter página {0}: {1} - {2}", page, res.StatusCode, res.Content));
                        break;
                    }

                    SkuMapPage data = JsonConvert.DeserializeObject<SkuMapPage>(res.Content);
                    if (data == null || data.items == null || data.items.Count == 0) break;

                    foreach (SkuEntry it in data.items)
                    {
                        if (!string.IsNullOrWhiteSpace(it.sku))
                            _skuMap[it.sku.Trim()] = it;
                    }

                    // Contar o que foi RECEBIDO (o servidor pode limitar per_page a menos do
                    // que o pedido — o cálculo antigo page*per terminava cedo e deixava o mapa incompleto)
                    totalRecebidos += data.items.Count;
                    if (totalRecebidos >= data.total) break;
                    page++;
                }

                LogHelper.LogInfo("[SKU-MAP] Cache carregada: " + _skuMap.Count + " SKUs.");
            }
            catch (Exception ex)
            {
                LogHelper.LogException("[SKU-MAP] Exceção a construir mapa", ex);
            }
        }

        static bool TryGetBySku(string sku, out SkuEntry entry)
        {
            entry = null;
            if (string.IsNullOrWhiteSpace(sku)) return false;
            return _skuMap.TryGetValue(sku.Trim(), out entry);
        }

        static async Task TouchSkuMapAfterCreate(WCObject wc, string sku, bool isVariation, ulong parentId)
        {
            try
            {
                SkuEntry dummy;
                if (TryGetBySku(sku, out dummy)) return;

                List<Product> list = await wc.Product.GetAll(new Dictionary<string, string> { { "sku", sku } });
                if (list != null && list.Count > 0)
                {
                    _skuMap[sku] = new SkuEntry
                    {
                        id = (ulong)list[0].id,
                        sku = sku,
                        type = isVariation ? "product_variation" : "product",
                        parent_id = parentId
                    };
                }
            }
            catch
            {
            }
        }

        static async Task<Dictionary<string, Variation>> GetParentVariationCache(WCObject wc, ulong parentId)
        {
            Dictionary<string, Variation> cache;
            if (_parentVarCache.TryGetValue(parentId, out cache))
                return cache;

            List<Variation> vars = await wc.Product.Variations.GetAll(parentId);
            cache = new Dictionary<string, Variation>(StringComparer.OrdinalIgnoreCase);

            if (vars != null)
            {
                foreach (var v in vars)
                {
                    string key = NormalizarSku(v.sku);
                    if (string.IsNullOrEmpty(key)) continue;

                    if (cache.ContainsKey(key))
                    {
                        LogHelper.LogError(
                            string.Format("[VAR/CACHE] SKU duplicado '{0}' no pai {1}. Mantendo ID {2}, ignorando ID {3}.",
                                v.sku, parentId, cache[key].id, v.id)
                        );
                        continue;
                    }

                    cache[key] = v;
                }
            }

            _parentVarCache[parentId] = cache;
            return cache;
        }


        static async Task<Dictionary<string, Variation>> RefreshParentVariationCache(WCObject wc, ulong parentId)
        {
            _parentVarCache.Remove(parentId);
            return await GetParentVariationCache(wc, parentId);
        }

        static async Task<bool> VerificarFlagWordPress()
        {
            try
            {
                RestClient client = new RestClient(wpBase);
                RestRequest request = new RestRequest("/wp-json/custom-sync/v1/flag/", Method.Get);
                request.AddHeader("Authorization", "Basic " + credentialsAdmin);

                RestResponse response = client.Execute(request);
                if (!response.IsSuccessful || string.IsNullOrWhiteSpace(response.Content))
                {
                    LogHelper.LogError(string.Format("[FLAG] Erro ao acessar /flag/: {0} - {1}", response.StatusCode, response.Content));
                    return false;
                }

                Dictionary<string, object> json = JsonConvert.DeserializeObject<Dictionary<string, object>>(response.Content);
                if (json != null && json.ContainsKey("sincronizar_todos_produtos") &&
                    json["sincronizar_todos_produtos"] != null &&
                    json["sincronizar_todos_produtos"].ToString() == "1")
                {
                    LogHelper.LogInfo("[FLAG] Flag de sincronização ativa. Resetando...");

                    RestRequest resetRequest = new RestRequest("/wp-json/custom-sync/v1/flag/", Method.Post);
                    resetRequest.AddHeader("Authorization", "Basic " + credentialsAdmin);
                    resetRequest.AddHeader("Content-Type", "application/json");
                    resetRequest.AddJsonBody(new { valor = "0" });

                    RestResponse resetResponse = client.Execute(resetRequest);
                    if (!resetResponse.IsSuccessful)
                        LogHelper.LogError(string.Format("[FLAG] Falha ao resetar flag: {0} - {1}", resetResponse.StatusCode, resetResponse.Content));

                    return true;
                }
            }
            catch (Exception ex)
            {
                LogHelper.LogException("[FLAG] Exceção ao verificar flag", ex);
            }
            return false;
        }

        // Pais já confirmados como 'variable' nesta execução — evita GET+verificação por cada filho
        static readonly HashSet<ulong> _paisConfirmadosVariable = new HashSet<ulong>();

        static async Task GarantirTipoVariavel(WCObject wc, ulong produtoId, string sku)
        {
            if (_paisConfirmadosVariable.Contains(produtoId))
                return;

            Product p = await wc.Product.Get(produtoId);
            if (!string.Equals(p.type, "variable", StringComparison.OrdinalIgnoreCase))
            {
                await wc.Product.Update(produtoId, new Product { type = "variable" });
                LogHelper.LogInfo(string.Format("Tipo do produto pai {0} atualizado para 'variable'.", sku));
            }

            _paisConfirmadosVariable.Add(produtoId);
        }

        #endregion
    }
}
