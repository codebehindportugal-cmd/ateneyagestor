using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using Newtonsoft.Json;

namespace LIBERNE.Models
{
    public class ProdutosUpdated

    {

        [JsonProperty("Artigo")]
        public string Artigo { get; set; }

        [JsonProperty("Descricao")]
        public string Descricao { get; set; }

        [JsonProperty("ArtigoPai")]
        public string ArtigoPai { get; set; }

        [JsonProperty("STKActual")]
        public double STKActual { get; set; }

        [JsonProperty("PVP1")]
        public double PVP1 { get; set; }

        [JsonProperty("PVP2")]
        public double PVP2 { get; set; }

        [JsonProperty("PVP3")]
        public double PVP3 { get; set; }

        [JsonProperty("PVP4")]
        public double PVP4 { get; set; }

        [JsonProperty("TipoDIM1")]
        public string TipoDIM1 { get; set; }

        [JsonProperty("TipoDIM2")]
        public string TipoDIM2 { get; set; }

        [JsonProperty("DIM1")]
        public string DIM1 { get; set; }

        [JsonProperty("DIM2")]
        public string DIM2 { get; set; }

        [JsonProperty("RubDim1")]
        public string RubDim1 { get; set; }

        [JsonProperty("RubDim2")]
        public string RubDim2 { get; set; }

        [JsonProperty("PesoLiquido")]
        public double PesoLiquido { get; set; }

        [JsonProperty("Peso")]
        public double Peso { get; set; }

        [JsonProperty("Volume")]
        public double Volume { get; set; }

        [JsonProperty("CDU_Calibre")]
        public string CDU_Calibre { get; set; }

        [JsonProperty("DataUltimaActualizacao")]
        public string DataUltimaActualizacao { get; set; }

        [JsonProperty("Familia")]
        public string Familia { get; set; }

        [JsonProperty("SubFamilia")]
        public string SubFamilia { get; set; }

        [JsonProperty("Marca")]
        public string Marca { get; set; }

        [JsonProperty("DescricaoComercial")]
        public string DescricaoComercial { get; set; }

        [JsonProperty("Caracteristicas")]
        public string Caracteristicas { get; set; }
    }
}
