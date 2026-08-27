using Newtonsoft.Json;

namespace LIBERNE.Models
{
    public class UnitsConditionJson
    {
        [JsonProperty("condition")]
        public string Condition { get; set; }
    }
}