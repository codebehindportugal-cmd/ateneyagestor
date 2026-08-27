using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using Newtonsoft.Json;

namespace LIBERNE.Models
{
    public class Client
    {

        [JsonProperty("email")]
        public string Email { get; set; }

        [JsonProperty("Nome")]
        public string Nome { get; set; }

        [JsonProperty("NumContrib")]
        public string NumContrib { get; set; }

        [JsonProperty("Pais")]
        public string Pais { get; set; }

        [JsonProperty("Fac_Tel")]
        public string Fac_Tel { get; set; }

        [JsonProperty("Fac_Mor")]
        public string Fac_Mor { get; set; }

        [JsonProperty("Fac_Mor2")]
        public string Fac_Mor2 { get; set; }

        [JsonProperty("Local")]
        public string Local { get; set; }

        [JsonProperty("Fac_Cp")]
        public string Fac_Cp { get; set; }

        [JsonProperty("Fac_CpLoc")]
        public string Fac_CpLoc { get; set; }

        [JsonProperty("Revenda")]
        public string Revenda { get; set; }


}
}
