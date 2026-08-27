using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using Newtonsoft.Json;

namespace LIBERNE.Models
{
    public class DataLinhasVenda
    {

        
        [JsonProperty("date")]
        public string date { get; set; }

        [JsonProperty("timezone_type")]
        public string timezone_type { get; set; }

        [JsonProperty("timezone")]
        public string timezone { get; set; }

        



    }
}
