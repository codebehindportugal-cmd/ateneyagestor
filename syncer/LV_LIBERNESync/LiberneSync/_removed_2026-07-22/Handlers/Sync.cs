using LIBERNE.ContentTypes;
using LIBERNE.Models;
using Newtonsoft.Json;
using System;
using System.Collections.Generic;
using System.Diagnostics;
using System.IO;
using System.Linq;
using System.Net;
using System.Net.Http;
using System.Text;
using System.Threading.Tasks;
using ErpBS100;

namespace LIBERNE.Handlers
{
    public static class Sync
    {
        public static bool IsSync = false;

        async static Task<string> ClientsPost(HttpClient httpClient, string data)
        {
            ClientsPostBody clientsPostBody = new ClientsPostBody
            {

                timestamp = data

            };

            string body = JsonConvert.SerializeObject(clientsPostBody);

            Debug.WriteLine(body);

            StringContent postBody = new StringContent(JsonConvert.SerializeObject(clientsPostBody), Encoding.UTF8, "application/json");

            try
            {
                string url = Properties.Settings.Default.GetClients;
                ServicePointManager.SecurityProtocol = SecurityProtocolType.Ssl3 | SecurityProtocolType.Tls | SecurityProtocolType.Tls12 | SecurityProtocolType.Tls11;
                HttpResponseMessage response = await httpClient.PostAsync(url, postBody);

                response.EnsureSuccessStatusCode();

                if (response.StatusCode != HttpStatusCode.NoContent)
                {
                    HttpContent content = response.Content;
                    Task<string> responseContent = content.ReadAsStringAsync();

                    return await responseContent;
                }
                else
                {
                    return null;
                }

            }
            catch (Exception ex)
            {
                Log.NewEntry(ex.ToString());
                Log.SendEmail(ex.Message.ToString());
                throw ex;
            }
        }


        public static bool WebsiteToPrimavera_Clients(ErpBS ErpBS, DateTime datadateToSync)
        {
            bool res = false;
            if (IsSync) return res;

            IsSync = true;

            //Aqui é que realmente inicia o tratamento de clientes
            try
            {
                using (var httpClient = new HttpClient())
                {
                    //Vai buscar a lista de clientes, ao site, desde a dataSincro
                    string clientsString = ClientsPost(httpClient, datadateToSync.ToString("yyyy-MM-dd HH:mm:ss")).Result;

                    if (string.IsNullOrWhiteSpace(clientsString) || !clientsString.Contains("email"))
                    {
                        Log.NewEntry("WebsiteToPrimavera - Não foram encontrados clientes a sincronizar");
                        return true;
                    }

                    Response<ClientsContent> clientsCallbak = JsonConvert.DeserializeObject<Response<ClientsContent>>(clientsString);
                    var clients = clientsCallbak.Content.Clients;
                    if (clients == null || clients.Count == 0)
                    {
                        Log.NewEntry("WebsiteToPrimavera - Não foram encontrados clientes a sincronizar");
                        return true;
                    }

                    Log.NewEntry("Foram obtidos " + clients.Count + " clientes");

                    //Para todos os clientes na lista de clientes
                    foreach (Client client in clients)
                    {
                        ErpBS.IniciaTransaccao();

                        BasBE100.BasBECliente cliente = new BasBE100.BasBECliente();

                        string existeContribuinte = ErpBS.Base.Clientes.ExisteContribuinte(client.NumContrib);
                        if (string.IsNullOrEmpty(existeContribuinte))
                        {
                            try
                            {
                                cliente.Cliente = "Web2";

                                StdBE100.StdBECampos _vars_cli = new StdBE100.StdBECampos();
                                StdBE100.StdBECampo _cli_site = new StdBE100.StdBECampo();
                                _cli_site.Nome = "CDU_Clientesite";
                                _cli_site.Valor = "1";
                                _cli_site.Tipo = StdBE100.StdBETipos.EnumTipoCampo.tcInt;
                                _vars_cli.Insere(_cli_site);
                                cliente.CamposUtil.Add(_vars_cli);

                                //cliente.set_TipoTerceiro(client.TipoTerceiro);

                                cliente.Morada = client.Fac_Mor;
                                cliente.Morada2 = client.Fac_Mor2;
                                cliente.Localidade = client.Local;


                                cliente.CodigoPostal = client.Fac_Cp;
                                cliente.LocalidadeCodigoPostal = client.Fac_CpLoc;

                                cliente.B2BEnderecoMail = client.Email;

                                cliente.NumContribuinte = client.NumContrib;

                                if (client.Nome.Length >= 50)
                                {

                                    cliente.Nome = client.Nome.Substring(0, 50);
                                    cliente.NomeFiscal = client.Nome.Substring(0, 50);

                                }
                                else
                                {
                                    cliente.Nome = client.Nome;
                                    cliente.NomeFiscal = client.Nome;
                                }


                                cliente.Pais = client.Pais;
                                cliente.Moeda = "EUR";

                                //ErpBS.Comercial.Clientes.Actualiza(cliente);
                                //ErpBS.TerminaTransaccao();
                                Log.NewEntry("O cliente " + client.Nome + " foi criado com sucesso");
                            }
                            catch (Exception ex)
                            {

                                ErpBS.DesfazTransaccao();

                                var st = new StackTrace(ex, true);

                                // Get the top stack frame
                                var frame = st.GetFrame(0);

                                // Get the line number from the stack frame
                                var line = frame.GetFileLineNumber();

                                //MessageBox.Show(ex.Message.ToString() + " : " + "Linha: " + line);
                                Log.NewEntry(ex.Message.ToString() + " : " + "Linha: " + line);


                                // ??????????????????????????????
                                //FecharEmpresaPrimavera(ErpBS);
                                //AbrirEmpresaPrimavera(ErpBS, plataforma, Properties.Settings.Default.EmpPrincipal, Properties.Settings.Default.UserPrimavera, Properties.Settings.Default.PwdPrimavera);

                                StdBE100.StdBECamposChave campoDataExecucaoFalha = new StdBE100.StdBECamposChave();
                                campoDataExecucaoFalha.AddCampoChave("CDU_Parametro", "dataUltimaExecucaoFalhada");
                                ErpBS.TabelasUtilizador.ActualizaValorAtributo("TDU_Parametros", campoDataExecucaoFalha, "CDU_Valor", DateTime.Now.ToString("yyyy-MM-dd HH:mm:ss"));

                                //FecharEmpresaPrimavera(ErpBS);

                                Log.SendEmail(ex.Message.ToString());
                                //Application.Exit();
                                throw;
                            }
                        }
                        else
                        {
                            Log.NewEntry("Já existe um cliente com o NIF " + client.NumContrib + " no Primavera");
                        }
                    }

                    res = true;
                }
            }
            catch (Exception _ex)
            {
                res = false;
                throw _ex;
            }

            return res;
        }
    }
}
