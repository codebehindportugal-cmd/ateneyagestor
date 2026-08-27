using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using Newtonsoft.Json;

namespace LIBERNE.Models
{
    public class Document
    {
        [JsonProperty("ATDocCodeID")]
        public string AtDocCodeId { get; set; }

        [JsonProperty("ATDocCodeSource")]
        public string AtDocCodeSource { get; set; }

        [JsonProperty("CashVATScheme", NullValueHandling = NullValueHandling.Ignore)]
        public long CashVatScheme { get; set; }

        [JsonProperty("anulado", NullValueHandling = NullValueHandling.Ignore)]
        public long Anulado { get; set; }

        [JsonProperty("app_origem", NullValueHandling = NullValueHandling.Ignore)]
        public long AppOrigem { get; set; }

        [JsonProperty("armdestino", NullValueHandling = NullValueHandling.Ignore)]
        public long Armdestino { get; set; }

        [JsonProperty("armorigem", NullValueHandling = NullValueHandling.Ignore)]
        public long Armorigem { get; set; }

        [JsonProperty("arredondamento", NullValueHandling = NullValueHandling.Ignore)]
        public long Arredondamento { get; set; }

        [JsonProperty("carga", NullValueHandling = NullValueHandling.Ignore)]
        public string Carga { get; set; }

        [JsonProperty("carga_codigo_postal", NullValueHandling = NullValueHandling.Ignore)]
        public string CargaCodigoPostal { get; set; }

        [JsonProperty("carga_distrito", NullValueHandling = NullValueHandling.Ignore)]
        public string CargaDistrito { get; set; }

        [JsonProperty("carga_localidade", NullValueHandling = NullValueHandling.Ignore)]
        public string CargaLocalidade { get; set; }

        [JsonProperty("cartao", NullValueHandling = NullValueHandling.Ignore)]
        public long Cartao { get; set; }

        [JsonProperty("cliente")]
        public long Cliente { get; set; }

        [JsonProperty("compdoc", NullValueHandling = NullValueHandling.Ignore)]
        public long Compdoc { get; set; }

        [JsonProperty("compensacoes", NullValueHandling = NullValueHandling.Ignore)]
        public List<Compensacao> Compensacoes { get; set; }

        [JsonProperty("contribuinte")]
        public string Contribuinte { get; set; }

        [JsonProperty("countrycode")]
        public string Countrycode { get; set; }

        [JsonProperty("data")]
        public DateTimeOffset Data { get; set; }

        [JsonProperty("dataDoc")]
        public DateTimeOffset DataDoc { get; set; }

        [JsonProperty("dataPagamento", NullValueHandling = NullValueHandling.Ignore)]
        public DateTimeOffset DataPagamento { get; set; }

        [JsonProperty("data_alteracao", NullValueHandling = NullValueHandling.Ignore)]
        public string DataAlteracao { get; set; }

        [JsonProperty("datacarga", NullValueHandling = NullValueHandling.Ignore)]
        public DateTimeOffset Datacarga { get; set; }

        [JsonProperty("datadescarga", NullValueHandling = NullValueHandling.Ignore)]
        public DateTimeOffset Datadescarga { get; set; }

        [JsonProperty("datadoc")]
        public DateTimeOffset Datadoc { get; set; }

        [JsonProperty("dataentrega", NullValueHandling = NullValueHandling.Ignore)]
        public string Dataentrega { get; set; }

        [JsonProperty("datahora")]
        public DateTime Datahora { get; set; }

        [JsonProperty("datapag", NullValueHandling = NullValueHandling.Ignore)]
        public DateTimeOffset Datapag { get; set; }

        [JsonProperty("datapagamento", NullValueHandling = NullValueHandling.Ignore)]
        public DateTimeOffset Datapagamento { get; set; }

        [JsonProperty("descanulado", NullValueHandling = NullValueHandling.Ignore)]
        public string Descanulado { get; set; }

        [JsonProperty("descarga", NullValueHandling = NullValueHandling.Ignore)]
        public string Descarga { get; set; }

        [JsonProperty("descarga_codigo_postal", NullValueHandling = NullValueHandling.Ignore)]
        public string DescargaCodigoPostal { get; set; }

        [JsonProperty("descarga_distrito", NullValueHandling = NullValueHandling.Ignore)]
        public string DescargaDistrito { get; set; }

        [JsonProperty("descarga_localidade", NullValueHandling = NullValueHandling.Ignore)]
        public string DescargaLocalidade { get; set; }

        [JsonProperty("descontos", NullValueHandling = NullValueHandling.Ignore)]
        public long Descontos { get; set; }

        [JsonProperty("descricao", NullValueHandling = NullValueHandling.Ignore)]
        public string Descricao { get; set; }

        [JsonProperty("deve", NullValueHandling = NullValueHandling.Ignore)]
        public long Deve { get; set; }

        [JsonProperty("diasPagamento", NullValueHandling = NullValueHandling.Ignore)]
        public long? DiasPagamento { get; set; }

        [JsonProperty("doc")]
        public string Doc { get; set; }

        [JsonProperty("doccomp", NullValueHandling = NullValueHandling.Ignore) ]
        public string Doccomp { get; set; }

        [JsonProperty("docext", NullValueHandling = NullValueHandling.Ignore)]
        public long Docext { get; set; }

        [JsonProperty("docforn", NullValueHandling = NullValueHandling.Ignore)]
        public string Docforn { get; set; }

        [JsonProperty("documentos_pagamento", NullValueHandling = NullValueHandling.Ignore)]
        public List<DocumentosPagamento> DocumentosPagamento { get; set; }

        [JsonProperty("dpercent", NullValueHandling = NullValueHandling.Ignore)]
        public long Dpercent { get; set; }

        [JsonProperty("emp", NullValueHandling = NullValueHandling.Ignore)]
        public long Emp { get; set; }

        [JsonProperty("empanulado", NullValueHandling = NullValueHandling.Ignore)]
        public long Empanulado { get; set; }

        [JsonProperty("freebeecupao", NullValueHandling = NullValueHandling.Ignore)]
        public string Freebeecupao { get; set; }

        [JsonProperty("hash")]
        public string Hash { get; set; }

        [JsonProperty("hashcontrol")]
        public long Hashcontrol { get; set; }

        [JsonProperty("hashcontrol2")]
        public string Hashcontrol2 { get; set; }

        [JsonProperty("horacarga", NullValueHandling = NullValueHandling.Ignore)]
        public DateTimeOffset Horacarga { get; set; }

        [JsonProperty("horadescarga", NullValueHandling = NullValueHandling.Ignore)]
        public DateTimeOffset Horadescarga { get; set; }

        [JsonProperty("idcx", NullValueHandling = NullValueHandling.Ignore)]
        public long Idcx { get; set; }

        [JsonProperty("impressao", NullValueHandling = NullValueHandling.Ignore)]
        public long Impressao { get; set; }

        [JsonProperty("isencao", NullValueHandling = NullValueHandling.Ignore)]
        public string Isencao { get; set; }

        [JsonProperty("ivaincluido", NullValueHandling = NullValueHandling.Ignore)]
        public long Ivaincluido { get; set; }

        [JsonProperty("lastupdate", NullValueHandling = NullValueHandling.Ignore)]
        public DateTimeOffset Lastupdate { get; set; }

        [JsonProperty("latitude", NullValueHandling = NullValueHandling.Ignore)]
        public string Latitude { get; set; }

        [JsonProperty("levantamento", NullValueHandling = NullValueHandling.Ignore)]
        public string Levantamento { get; set; }

        [JsonProperty("liquido", NullValueHandling = NullValueHandling.Ignore)]
        public double Liquido { get; set; }

        [JsonProperty("ljdestino", NullValueHandling = NullValueHandling.Ignore)]
        public long Ljdestino { get; set; }

        [JsonProperty("ljorigem", NullValueHandling = NullValueHandling.Ignore)]
        public long Ljorigem { get; set; }

        [JsonProperty("loja", NullValueHandling = NullValueHandling.Ignore)]
        public long Loja { get; set; }

        [JsonProperty("longitude", NullValueHandling = NullValueHandling.Ignore)]
        public string Longitude { get; set; }

        [JsonProperty("lugar", NullValueHandling = NullValueHandling.Ignore)]
        public long Lugar { get; set; }

        [JsonProperty("mesa", NullValueHandling = NullValueHandling.Ignore)]
        public long Mesa { get; set; }

        [JsonProperty("mesaidx", NullValueHandling = NullValueHandling.Ignore)]
        public long Mesaidx { get; set; }

        [JsonProperty("morada", NullValueHandling = NullValueHandling.Ignore)]
        public string Morada { get; set; }

        [JsonProperty("motivo_isencao", NullValueHandling = NullValueHandling.Ignore)]
        public string MotivoIsencao { get; set; }

        [JsonProperty("movimentospropriedades", NullValueHandling = NullValueHandling.Ignore)]
        public List<object> Movimentospropriedades { get; set; }

        [JsonProperty("nome", NullValueHandling = NullValueHandling.Ignore)]
        public string Nome { get; set; }

        [JsonProperty("numero")]
        public long Numero { get; set; }

        [JsonProperty("numero_manual")]
        public long NumeroManual { get; set; }

        [JsonProperty("numpag", NullValueHandling = NullValueHandling.Ignore)]
        public string Numpag { get; set; }

        [JsonProperty("observacoes", NullValueHandling = NullValueHandling.Ignore)]
        public string Observacoes { get; set; }

        [JsonProperty("pagamento", NullValueHandling = NullValueHandling.Ignore)]
        public long Pagamento { get; set; }

        [JsonProperty("pago", NullValueHandling = NullValueHandling.Ignore)]
        public long Pago { get; set; }

        [JsonProperty("peso", NullValueHandling = NullValueHandling.Ignore)]
        public string Peso { get; set; }

        [JsonProperty("referencia_pagamento", NullValueHandling = NullValueHandling.Ignore)]
        public string ReferenciaPagamento { get; set; }

        [JsonProperty("serie")]
        public string Serie { get; set; }

        [JsonProperty("serie_manual")]
        public string SerieManual { get; set; }

        [JsonProperty("sync", NullValueHandling = NullValueHandling.Ignore)]
        public long Sync { get; set; }

        [JsonProperty("sync_at", NullValueHandling = NullValueHandling.Ignore)]
        public long SyncAt { get; set; }

        [JsonProperty("telefone", NullValueHandling = NullValueHandling.Ignore)]
        public string Telefone { get; set; }

        [JsonProperty("tipo")]
        public long Tipo { get; set; }

        [JsonProperty("tipodoc")]
        public long Tipodoc { get; set; }

        [JsonProperty("total")]
        public double Total { get; set; }

        [JsonProperty("vendas")]
        public List<Venda> Vendas { get; set; }

        [JsonProperty("viatura", NullValueHandling = NullValueHandling.Ignore)]
        public string Viatura { get; set; }

        [JsonProperty("_errors", NullValueHandling = NullValueHandling.Ignore)]
        public List<object> Errors { get; set; }
    }
}

