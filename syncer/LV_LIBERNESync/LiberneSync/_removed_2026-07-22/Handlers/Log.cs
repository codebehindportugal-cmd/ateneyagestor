using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Net;
using System.Net.Mail;
using System.Text;
using System.Threading.Tasks;
using System.Windows.Forms;

namespace LIBERNE.Handlers
{
    public class Log
    {
        //Caminhos dos log's
        static string logbasePath = Application.StartupPath + "//" + Properties.Settings.Default.LogFile + DateTime.Now.ToString("yyyy_MM_dd");
        static string errorLogPath = Application.StartupPath + "//" + Properties.Settings.Default.errorLogFile + DateTime.Now.ToString("yyyy_MM_dd");

        public Log()
        {

            //Verifica se existe o ficheiro do Log
            if (!File.Exists(logbasePath))
            {
                FileStream f = File.Create(logbasePath);
                f.Close();

                using (StreamWriter file = new StreamWriter(logbasePath, true))
                {
                    file.WriteLine("LOG FILE CREATED AT:" + DateTime.Now);
                    file.WriteLine(Environment.NewLine);


                }
            }

            //Verifica se existe o ficheiro para os erros
            if (!File.Exists(errorLogPath))
            {
                FileStream f = File.Create(errorLogPath);
                f.Close();

                using (StreamWriter file = new StreamWriter(errorLogPath, true))
                {
                    file.WriteLine("LOG FILE CREATED AT:" + DateTime.Now);
                    file.WriteLine(Environment.NewLine);

                }
            }
        }

        public static void NewEntry(string message)
        {
            using (StreamWriter file = new StreamWriter(logbasePath, true))
            {
                file.WriteLine("(" + DateTime.Now + ") - " + message);

            }
        }

        public static void SendEmail(string body)
        {
            string result = "Message Sent Successfully..!!";
            string senderID = "alertas@tecdoor.com";// use sender’s email id here..
            string senderPassword = Properties.Settings.Default.emailPWD; // sender password here…
            try
            {
                SmtpClient smtp = new SmtpClient
                {
                    Host = Properties.Settings.Default.smtpServer, // smtp server address here…
                    Port = Properties.Settings.Default.SmtpPort,
                    EnableSsl = false,
                    DeliveryMethod = SmtpDeliveryMethod.Network,
                    Credentials = new NetworkCredential(senderID, senderPassword),
                    Timeout = 30000,
                };

                body = "Ocorreu o seguinte erro no sincronizador \r\n" + body + Environment.NewLine + Environment.NewLine + " Por Favor contacte o administrador de sistemas";
                MailMessage message = new MailMessage(senderID, "pedro.costa@tecdoor.com", "Erro LIBERNE", body);
                message.To.Add("alertas@tecdoor.com");
                //message.To.Add("luis.correia@elgalego.pt");
                //message.To.Add("sergio.sousa@tecdoor.com");
                //message.To.Add("fatima.santos@elgalego.pt");
                smtp.Send(message);
            }
            catch (Exception ex)
            {
                Log.NewEntry(ex.ToString());
                Application.Exit();
            }

        }
    }
}
