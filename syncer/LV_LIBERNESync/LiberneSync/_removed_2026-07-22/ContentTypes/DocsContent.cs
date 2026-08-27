using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using LIBERNE.Models;
using Newtonsoft.Json;

namespace LIBERNE.ContentTypes
{
    public class DocsContent
    {
        [JsonProperty("document")]
        public List<Document> Documents { get; set; }
    }
}
