using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using Newtonsoft.Json;
namespace LIBERNE.Models
{
    public class Unit
    {
        [JsonProperty("codigo")]
        public long Codigo { get; set; }

        [JsonProperty("descricao")]
        public string Descricao { get; set; }

        [JsonProperty("lastupdate")]
        public System.DateTime Lastupdate { get; set; }

        [JsonProperty("loja")]
        public long Loja { get; set; }

        [JsonProperty("_errors")]
        public List<object> Errors { get; set; }
    }
}
