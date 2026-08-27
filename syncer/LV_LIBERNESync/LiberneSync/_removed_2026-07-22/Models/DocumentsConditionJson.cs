using Newtonsoft.Json;
using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;

namespace LIBERNE.Models
{
    class DocumentsConditionJson
    {
        [JsonProperty("condition")]
        public string Condition { get; set; }

        [JsonProperty("limit")]
        public int Limit { get; set; }

        [JsonProperty("offset")]
        public int Offset { get; set; }

        [JsonProperty("order")]
        public string Order { get; set; }
    }
}
