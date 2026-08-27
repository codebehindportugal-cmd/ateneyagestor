using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using Newtonsoft.Json;

namespace LIBERNE.Models
{
    public class LinhasVenda
    {

        [JsonProperty("Artigo")]
        public string Artigo { get; set; }

        [JsonProperty("Data")]
        public DataLinhasVenda Data { get; set; }

        [JsonProperty("Descricao")]
        public string Descricao { get; set; }

        [JsonProperty("PrecUnit")]
        public double PrecUnit { get; set; }

        [JsonProperty("Quantidade")]
        public double Quantidade { get; set; }



    }
}
