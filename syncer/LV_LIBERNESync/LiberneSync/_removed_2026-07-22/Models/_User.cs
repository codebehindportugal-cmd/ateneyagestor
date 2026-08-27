using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using Newtonsoft.Json;

namespace LIBERNE
{
    public class User
    {
        [JsonProperty("Nome")]
        private string Nome;

        [JsonProperty("Pais")]
        private string Pais;

        [JsonProperty("NumContrib")]
        private int NumContrib;

        [JsonProperty("Fac_Tel")]
        private int Fac_Tel;

        [JsonProperty("Fac_Mor")]
        private String Fac_Mor;

        [JsonProperty("Fac_Mor2")]
        private String Fac_Mor2;

        [JsonProperty("Local")]
        private String Local;

        [JsonProperty("Fac_Cp")]
        private String Fac_Cp;
                
        [JsonProperty("Fac_CpLoc")]
        private String Fac_CpLoc;

        [JsonProperty("email")]
        private String email;
       
    }
}
