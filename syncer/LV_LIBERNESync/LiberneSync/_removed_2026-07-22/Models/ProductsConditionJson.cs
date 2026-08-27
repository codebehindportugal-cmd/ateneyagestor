using Newtonsoft.Json;

namespace LIBERNE.Models
{
    public class ProductsConditionJson
    {
        [JsonProperty("condition")]
        public string Condition { get; set; }

        [JsonProperty("limit")]
        public int Limit { get; set; }

        [JsonProperty("offset")]
        public int Offset { get; set; }
    }
}