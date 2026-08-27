using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using LIBERNE.Models;
using Newtonsoft.Json;


namespace LIBERNE.ContentTypes
{
    class ClientsContent
    {
        [JsonProperty("users")]
        public List<Client> Clients { get; set; }

        [JsonProperty("success")]
        public string success { get; }
    }
}
