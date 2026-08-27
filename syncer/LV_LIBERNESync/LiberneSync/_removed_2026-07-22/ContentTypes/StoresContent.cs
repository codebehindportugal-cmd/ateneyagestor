using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using LIBERNE.Models;
using Newtonsoft.Json;


namespace LIBERNE.ContentTypes
{
    class StoresContent
    {
        [JsonProperty("store")]
        public List<Store> Stores { get; set; }
    }
}
