using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using Newtonsoft.Json;

namespace LIBERNE
{
    public class Credenciais
    {
        [JsonProperty("user")]
        public User user;

    public Credenciais (User usr) {
            user = usr;
        
        }


        
    }

    
}
