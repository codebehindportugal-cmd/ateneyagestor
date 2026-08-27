using System;
using System.Collections.Generic;
using Newtonsoft.Json;

namespace LIBERNE.Models
{
    public class Productlib
    {
        [JsonProperty("armazem")]
        public long Armazem { get; set; }

        [JsonProperty("autoquebra")]
        public long Autoquebra { get; set; }

        [JsonProperty("balanca")]
        public long Balanca { get; set; }

        [JsonProperty("caracteristicas")]
        public List<object> Caracteristicas { get; set; }

        [JsonProperty("categoria")]
        public long Categoria { get; set; }

        [JsonProperty("codbarras")]
        public string Codbarras { get; set; }

        [JsonProperty("codigo")]
        public long Codigo { get; set; }

        [JsonProperty("codigopp")]
        public long Codigopp { get; set; }

        [JsonProperty("codigosbarras")]
        public string Codigosbarras { get; set; }

        [JsonProperty("complementares")]
        public List<object> Complementares { get; set; }

        [JsonProperty("composto")]
        public long Composto { get; set; }

        [JsonProperty("compra")]
        public long Compra { get; set; }

        [JsonProperty("consumominimo")]
        public long Consumominimo { get; set; }

        [JsonProperty("cozinha", NullValueHandling = NullValueHandling.Ignore)]
        public long? Cozinha { get; set; }

        [JsonProperty("datacriacao")]
        public System.DateTime Datacriacao { get; set; }

        [JsonProperty("dataultcompra")]
        public System.DateTime Dataultcompra { get; set; }

        [JsonProperty("descricao")]
        public string Descricao { get; set; }

        [JsonProperty("descricaocurta")]
        public string Descricaocurta { get; set; }

        [JsonProperty("dosedesc")]
        public string Dosedesc { get; set; }

        [JsonProperty("excluirdescontos")]
        public long Excluirdescontos { get; set; }

        [JsonProperty("familia")]
        public long Familia { get; set; }

        [JsonProperty("fornecedor")]
        public long Fornecedor { get; set; }

        [JsonProperty("foto")]
        public object Foto { get; set; }

        [JsonProperty("fundo")]
        public string Fundo { get; set; }

        [JsonProperty("grupo")]
        public long Grupo { get; set; }

        [JsonProperty("isencao")]
        public string Isencao { get; set; }

        [JsonProperty("iva")]
        public long Iva { get; set; }

        [JsonProperty("iva2")]
        public long Iva2 { get; set; }

        [JsonProperty("ivacompra")]
        public long Ivacompra { get; set; }

        [JsonProperty("ivarevenda")]
        public long Ivarevenda { get; set; }

        [JsonProperty("lastupdate")]
        public System.DateTime Lastupdate { get; set; }

        [JsonProperty("letra")]
        public string Letra { get; set; }

        [JsonProperty("listseparado")]
        public long Listseparado { get; set; }

        [JsonProperty("loja")]
        public long Loja { get; set; }

        [JsonProperty("margembruta")]
        public long Margembruta { get; set; }

        [JsonProperty("max_complementos")]
        public string MaxComplementos { get; set; }

        [JsonProperty("maxopcoes")]
        public long Maxopcoes { get; set; }

        [JsonProperty("meiadose")]
        public long Meiadose { get; set; }

        [JsonProperty("meiadosedesc")]
        public string Meiadosedesc { get; set; }

        [JsonProperty("min_complementos")]
        public string MinComplementos { get; set; }

        [JsonProperty("niveismenu")]
        public List<object> Niveismenu { get; set; }

        [JsonProperty("obs")]
        public string Obs { get; set; }

        [JsonProperty("ordem")]
        public long Ordem { get; set; }

        [JsonProperty("ordemlocal")]
        public long Ordemlocal { get; set; }

        [JsonProperty("ordempedido")]
        public long Ordempedido { get; set; }

        [JsonProperty("ordemtop")]
        public long Ordemtop { get; set; }

        [JsonProperty("percentagemretencao")]
        public long Percentagemretencao { get; set; }

        [JsonProperty("percentprom")]
        public long Percentprom { get; set; }

        [JsonProperty("precocompra")]
        public long Precocompra { get; set; }

        [JsonProperty("precomeia")]
        public long Precomeia { get; set; }

        [JsonProperty("precominimo")]
        public long Precominimo { get; set; }

        [JsonProperty("precorevenda")]
        public long Precorevenda { get; set; }

        [JsonProperty("precovenda")]
        public long Precovenda { get; set; }

        [JsonProperty("prepagamento")]
        public long Prepagamento { get; set; }

        [JsonProperty("prodstock")]
        public long Prodstock { get; set; }

        [JsonProperty("produtos_propriedades")]
        public List<object> ProdutosPropriedades { get; set; }

        [JsonProperty("promocao")]
        public long Promocao { get; set; }

        [JsonProperty("pvp10")]
        public long Pvp10 { get; set; }

        [JsonProperty("pvp10siva")]
        public long Pvp10Siva { get; set; }

        [JsonProperty("pvp1siva")]
        public long Pvp1Siva { get; set; }

        [JsonProperty("pvp2")]
        public long Pvp2 { get; set; }

        [JsonProperty("pvp2siva")]
        public long Pvp2Siva { get; set; }

        [JsonProperty("pvp3")]
        public long Pvp3 { get; set; }

        [JsonProperty("pvp3siva")]
        public long Pvp3Siva { get; set; }

        [JsonProperty("pvp4")]
        public long Pvp4 { get; set; }

        [JsonProperty("pvp4siva")]
        public long Pvp4Siva { get; set; }

        [JsonProperty("pvp5")]
        public long Pvp5 { get; set; }

        [JsonProperty("pvp5siva")]
        public long Pvp5Siva { get; set; }

        [JsonProperty("pvp6")]
        public long Pvp6 { get; set; }

        [JsonProperty("pvp6siva")]
        public long Pvp6Siva { get; set; }

        [JsonProperty("pvp7")]
        public long Pvp7 { get; set; }

        [JsonProperty("pvp7siva")]
        public long Pvp7Siva { get; set; }

        [JsonProperty("pvp8")]
        public long Pvp8 { get; set; }

        [JsonProperty("pvp8siva")]
        public long Pvp8Siva { get; set; }

        [JsonProperty("pvp9")]
        public long Pvp9 { get; set; }

        [JsonProperty("pvp9siva")]
        public long Pvp9Siva { get; set; }

        [JsonProperty("pvpmeia10")]
        public long Pvpmeia10 { get; set; }

        [JsonProperty("pvpmeia10siva")]
        public long Pvpmeia10Siva { get; set; }

        [JsonProperty("pvpmeia1siva")]
        public long Pvpmeia1Siva { get; set; }

        [JsonProperty("pvpmeia2")]
        public long Pvpmeia2 { get; set; }

        [JsonProperty("pvpmeia2siva")]
        public long Pvpmeia2Siva { get; set; }

        [JsonProperty("pvpmeia3")]
        public long Pvpmeia3 { get; set; }

        [JsonProperty("pvpmeia3siva")]
        public long Pvpmeia3Siva { get; set; }

        [JsonProperty("pvpmeia4")]
        public long Pvpmeia4 { get; set; }

        [JsonProperty("pvpmeia4siva")]
        public long Pvpmeia4Siva { get; set; }

        [JsonProperty("pvpmeia5")]
        public long Pvpmeia5 { get; set; }

        [JsonProperty("pvpmeia5siva")]
        public long Pvpmeia5Siva { get; set; }

        [JsonProperty("pvpmeia6")]
        public long Pvpmeia6 { get; set; }

        [JsonProperty("pvpmeia6siva")]
        public long Pvpmeia6Siva { get; set; }

        [JsonProperty("pvpmeia7")]
        public long Pvpmeia7 { get; set; }

        [JsonProperty("pvpmeia7siva")]
        public long Pvpmeia7Siva { get; set; }

        [JsonProperty("pvpmeia8")]
        public long Pvpmeia8 { get; set; }

        [JsonProperty("pvpmeia8siva")]
        public long Pvpmeia8Siva { get; set; }

        [JsonProperty("pvpmeia9")]
        public long Pvpmeia9 { get; set; }

        [JsonProperty("pvpmeia9siva")]
        public long Pvpmeia9Siva { get; set; }

        [JsonProperty("qtdmeia")]
        public long Qtdmeia { get; set; }

        [JsonProperty("qtdstock")]
        public long Qtdstock { get; set; }

        [JsonProperty("referencia")]
        public string Referencia { get; set; }

        [JsonProperty("restricted")]
        public long Restricted { get; set; }

        [JsonProperty("retalho")]
        public long Retalho { get; set; }

        [JsonProperty("retencao")]
        public long Retencao { get; set; }

        [JsonProperty("revenda")]
        public long Revenda { get; set; }

        [JsonProperty("stocks")]
        public long Stocks { get; set; }

        [JsonProperty("subcategoria")]
        public long Subcategoria { get; set; }

        [JsonProperty("subfam")]
        public long Subfam { get; set; }

        [JsonProperty("tara")]
        public long Tara { get; set; }

        [JsonProperty("tempoprep")]
        public System.DateTime Tempoprep { get; set; }

        [JsonProperty("tiposaft")]
        public string Tiposaft { get; set; }

        [JsonProperty("topo")]
        public long Topo { get; set; }

        [JsonProperty("ultprecocompra")]
        public long Ultprecocompra { get; set; }

        [JsonProperty("ultprecovenda")]
        public long Ultprecovenda { get; set; }

        [JsonProperty("uncompra")]
        public long Uncompra { get; set; }

        [JsonProperty("unidade")]
        public long Unidade { get; set; }

        [JsonProperty("uninventario")]
        public long Uninventario { get; set; }

        [JsonProperty("vendersemstock")]
        public long Vendersemstock { get; set; }

        [JsonProperty("_errors")]
        public List<object> Errors { get; set; }
    }
}
