using Newtonsoft.Json;
using System.Collections.Generic;

namespace LIBERNE.Models
{
    public class DocumentosPagamento
    {
        [JsonProperty("adiantamento")]
        public long Adiantamento { get; set; }

        [JsonProperty("cartao")]
        public long Cartao { get; set; }

        [JsonProperty("doc")]
        public string Doc { get; set; }

        [JsonProperty("loja")]
        public long Loja { get; set; }

        [JsonProperty("numero")]
        public long Numero { get; set; }

        [JsonProperty("serie")]
        public string Serie { get; set; }

        [JsonProperty("tipo")]
        public long Tipo { get; set; }

        [JsonProperty("valor")]
        public double Valor { get; set; }

        [JsonProperty("_errors")]
        public List<object> Errors { get; set; }

    }
}