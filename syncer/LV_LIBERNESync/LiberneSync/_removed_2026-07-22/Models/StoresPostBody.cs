using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using Newtonsoft.Json;

namespace LIBERNE.Models
{
    class StoresPostBody
    {
        [JsonProperty("auth_hash")]
        public string authHash;

        [JsonProperty("store")]
        public StoreConditionJson Store { get; set; }
    }
}
