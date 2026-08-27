using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using Newtonsoft.Json;

namespace LIBERNE.Models
{
    public class _AuthHash
    {
        [JsonProperty("auth_hash")]
        public string Auth_Hash { get; set; }

    }
}
