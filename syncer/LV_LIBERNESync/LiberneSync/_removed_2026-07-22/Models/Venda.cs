using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using Newtonsoft.Json;

namespace LIBERNE.Models
{
    public class Venda
    {
        [JsonProperty("loja")]
        public long Loja { get; set; }

        [JsonProperty("numero")]
        public long Numero { get; set; }

        [JsonProperty("doc")]
        public string Doc { get; set; }

        [JsonProperty("id")]
        public long Id { get; set; }

        [JsonProperty("data")]
        public DateTime Data { get; set; }

        [JsonProperty("codigo")]
        public long Codigo { get; set; }

        [JsonProperty("descricao")]
        public string Descricao { get; set; }

        [JsonProperty("iva")]
        public long Iva { get; set; }

        [JsonProperty("qtd")]
        public float Qtd { get; set; }

        [JsonProperty("punit")]
        public double Punit { get; set; }

        [JsonProperty("valor")]
        public double Valor { get; set; }

        [JsonProperty("desconto")]
        public float Desconto { get; set; }

        [JsonProperty("desconto2")]
        public long Desconto2 { get; set; }

        [JsonProperty("total")]
        public double Total { get; set; }

        [JsonProperty("hideqtd")]
        public long Hideqtd { get; set; }

        [JsonProperty("posto")]
        public long Posto { get; set; }

        [JsonProperty("empid")]
        public long Empid { get; set; }

        [JsonProperty("datahora")]
        public string Datahora { get; set; }

        [JsonProperty("armazem")]
        public long Armazem { get; set; }

        [JsonProperty("qtdstock")]
        public long Qtdstock { get; set; }

        [JsonProperty("prodstock")]
        public long Prodstock { get; set; }

        [JsonProperty("origem")]
        public long Origem { get; set; }

        [JsonProperty("codprom")]
        public long Codprom { get; set; }

        [JsonProperty("serie")]
        public string Serie { get; set; }

        [JsonProperty("lote")]
        public string Lote { get; set; }

        [JsonProperty("desperdicio")]
        public string Desperdicio { get; set; }

        [JsonProperty("ljorigem")]
        public long Ljorigem { get; set; }

        [JsonProperty("armorigem")]
        public long Armorigem { get; set; }

        [JsonProperty("ljdestino")]
        public long Ljdestino { get; set; }

        [JsonProperty("armdestino")]
        public long Armdestino { get; set; }

        [JsonProperty("unidade")]
        public long Unidade { get; set; }

        [JsonProperty("motivo_isencao")]
        public string MotivoIsencao { get; set; }

        [JsonProperty("isencao")]
        public string Isencao { get; set; }

        [JsonProperty("descforn")]
        public string Descforn { get; set; }

        [JsonProperty("referencia")]
        public string Referencia { get; set; }

        [JsonProperty("refforn")]
        public string Refforn { get; set; }

        [JsonProperty("ddoc")]
        public long Ddoc { get; set; }

        [JsonProperty("dpercent")]
        public long Dpercent { get; set; }

        [JsonProperty("dvalor")]
        public long Dvalor { get; set; }

        [JsonProperty("dextenso")]
        public long Dextenso { get; set; }

        [JsonProperty("obs")]
        public string Obs { get; set; }

        [JsonProperty("pvp")]
        public long Pvp { get; set; }

        [JsonProperty("validade")]
        public DateTime Validade { get; set; }

        [JsonProperty("qtdunidades")]
        public long Qtdunidades { get; set; }

        [JsonProperty("PrecoLiquido")]
        public long PrecoLiquido { get; set; }

        [JsonProperty("uid_caracteristica")]
        public long UidCaracteristica { get; set; }

        [JsonProperty("uid_propriedade")]
        public long UidPropriedade { get; set; }

        [JsonProperty("tipo")]
        public long Tipo { get; set; }

        [JsonProperty("prodorigem")]
        public long Prodorigem { get; set; }
    }
}
