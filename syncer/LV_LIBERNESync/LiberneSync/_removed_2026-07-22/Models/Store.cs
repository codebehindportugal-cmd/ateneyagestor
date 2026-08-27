using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using Newtonsoft.Json;

namespace LIBERNE.Models
{
    public class Store
    {
        [JsonProperty("CashVATScheme")]
        public string CashVatScheme { get; set; }

        [JsonProperty("CashVATSchemeBegin")]
        public System.DateTime CashVatSchemeBegin { get; set; }

        [JsonProperty("CashVATSchemeEnd")]
        public System.DateTime CashVatSchemeEnd { get; set; }

        [JsonProperty("CurrentTimestamp")]
        public object CurrentTimestamp { get; set; }

        [JsonProperty("StoreSettings")]
        public List<object> StoreSettings { get; set; }

        [JsonProperty("cae")]
        public string Cae { get; set; }

        [JsonProperty("cod_postal")]
        public string CodPostal { get; set; }

        [JsonProperty("codigo")]
        public long Codigo { get; set; }

        [JsonProperty("contribuinte")]
        public string Contribuinte { get; set; }

        [JsonProperty("data_suspensao")]
        public System.DateTime DataSuspensao { get; set; }

        [JsonProperty("datalimite")]
        public System.DateTime Datalimite { get; set; }

        [JsonProperty("descricao")]
        public string Descricao { get; set; }

        [JsonProperty("designacao")]
        public string Designacao { get; set; }

        [JsonProperty("flag100")]
        public long Flag100 { get; set; }

        [JsonProperty("flag16")]
        public long Flag16 { get; set; }

        [JsonProperty("fullsync")]
        public object Fullsync { get; set; }

        [JsonProperty("hd_uid")]
        public string HdUid { get; set; }

        [JsonProperty("hdserialnum")]
        public object Hdserialnum { get; set; }

        [JsonProperty("ip")]
        public string Ip { get; set; }

        [JsonProperty("isStationUnique")]
        public long IsStationUnique { get; set; }

        [JsonProperty("isStockAtive")]
        public long IsStockAtive { get; set; }

        [JsonProperty("lastupdate")]
        public object Lastupdate { get; set; }

        [JsonProperty("localidade")]
        public string Localidade { get; set; }

        [JsonProperty("logotipo")]
        public object Logotipo { get; set; }

        [JsonProperty("loja")]
        public long Loja { get; set; }

        [JsonProperty("morada")]
        public string Morada { get; set; }

        [JsonProperty("numero_porta")]
        public string NumeroPorta { get; set; }

        [JsonProperty("pais")]
        public string Pais { get; set; }

        [JsonProperty("regime_iva")]
        public string RegimeIva { get; set; }

        [JsonProperty("saft")]
        public System.DateTime Saft { get; set; }

        [JsonProperty("sync")]
        public long Sync { get; set; }

        [JsonProperty("telefone")]
        public string Telefone { get; set; }

        [JsonProperty("tipo_lic")]
        public long TipoLic { get; set; }

        [JsonProperty("tipo_soft")]
        public long TipoSoft { get; set; }

        [JsonProperty("typeFO")]
        public long TypeFo { get; set; }

        [JsonProperty("_errors")]
        public List<object> Errors { get; set; }
    }
}
