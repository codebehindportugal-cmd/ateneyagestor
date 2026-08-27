using Newtonsoft.Json;
using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;


namespace LIBERNE.Models
{
    public class StoreConditionJson

    {
        [JsonProperty("condition")]
        public string Condition { get; set; }

        
    }
}
