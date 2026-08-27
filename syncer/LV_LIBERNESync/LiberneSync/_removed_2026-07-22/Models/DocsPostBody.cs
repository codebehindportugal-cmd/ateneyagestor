using Newtonsoft.Json;
using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;

namespace LIBERNE.Models
{
    class DocsPostBody
    {
        [JsonProperty("auth_hash")]
        public string authHash;

        [JsonProperty("document")]
        public DocumentsConditionJson Document { get; set; }
    }
}
