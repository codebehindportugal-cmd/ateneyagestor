using Newtonsoft.Json;
using System;
using System.Collections.Generic;

namespace LIBERNE.Models
{
    public class Compensacao
    {

        [JsonProperty("docType")]
        public string DocType { get; set; }

        [JsonProperty("docNum")]
        public string DocNum { get; set; }

        [JsonProperty("docSerie")]
        public string DocSerie { get; set; }

        [JsonProperty("valor")]
        public double Valor { get; set; }

        [JsonProperty("data")]
        public DateTimeOffset Data { get; set; }

        [JsonProperty("_errors")]
        public List<object> Errors { get; set; }
    }
}