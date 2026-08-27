using Newtonsoft.Json;

namespace LIBERNE.Models
{
    public class ClientsConditionJson
    {
        [JsonProperty("condition")]
        public string Condition { get; set; }

        [JsonProperty("limit")]
        public int Limit { get; set; }

        [JsonProperty("offset")]
        public int Offset { get; set; }

    }
}