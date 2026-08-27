using Newtonsoft.Json;
using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;

namespace LIBERNE.Models
{
    class UnitsPostBody
    {
        [JsonProperty("auth_hash")]
        public string authHash;

        [JsonProperty("unit")]
        public UnitsConditionJson Unit { get; set; }
    }

    
}
