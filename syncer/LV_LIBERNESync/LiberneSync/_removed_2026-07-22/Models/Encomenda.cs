using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using Newtonsoft.Json;

namespace LIBERNE.Models
{
    public class Encomenda
    {

        [JsonProperty("Nome")]
        public string Nome { get; set; }

        [JsonProperty("NomeFac")]
        public string NomeFac { get; set; }

        [JsonProperty("NumContribuinte")]
        public string NumContribuinte { get; set; }

        [JsonProperty("NumContribuinteFac")]
        public string NumContribuinteFac { get; set; }

        [JsonProperty("Morada")]
        public string Morada { get; set; }

        [JsonProperty("Morada2")]
        public string Morada2 { get; set; }

        [JsonProperty("Data")]
        public string Data { get; set; }

        [JsonProperty("CodPostal")]
        public string CodPostal { get; set; }

        [JsonProperty("CodPostalLocalidade")]
        public string CodPostalLocalidade { get; set; }

        [JsonProperty("LinhasVenda")]
        public List<LinhasVenda> LinhasVenda { get; set; }

        public Encomenda()
        {
            LinhasVenda = new List<LinhasVenda>();
        }





    }
}
