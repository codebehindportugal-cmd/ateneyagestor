using Newtonsoft.Json;

namespace LIBERNE

{
    public class Response<TContent>
    {
       

        [JsonProperty("Operation")]
        public string Operation { get; set; }


        [JsonProperty("Results")]
        public TContent Content { get; set; }
    }
}