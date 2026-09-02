<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * O backlog do estágio de 2026, tal como estava no quadro do Trello.
 *
 * Vive num projecto próprio ("Estágio 2026") e não espalhado pelos projectos
 * reais de propósito: as tarefas dos projectos são o estado de cada projecto,
 * e misturar lá dentro um backlog de formação estragava essa leitura.
 *
 * As tarefas nascem **sem responsável** — é assim que aparecem no "Tarefas por
 * escolher" e que cada pessoa fica com as que quiser.
 */
return new class extends Migration
{
    private const REPOS = [
        'painel'   => 'https://github.com/codebehindportugal-cmd/ateneyagestor.git',
        'agricola' => 'https://github.com/codebehindportugal-cmd/gestao-agricola.git',
        'entregas' => 'https://github.com/codebehindportugal-cmd/entregas.git',
        'santana'  => 'https://github.com/codebehindportugal-cmd/associacaosantana.git',
        'mordfocas' => 'https://github.com/codebehindportugal-cmd/discord-party-bot.git',
        'ehlab'    => 'https://github.com/andremendes92-ateneya/ehlab.git',
    ];

    public function up(): void
    {
        $now = now();

        $projectId = DB::table('projects')->where('slug', 'estagio-2026')->value('id');

        if ($projectId) {
            // Correr outra vez refresca as regras, sem tocar nas tarefas já escolhidas.
            DB::table('projects')->where('id', $projectId)->update([
                'notes'      => $this->regrasDoQuadro(),
                'updated_at' => $now,
            ]);
        }

        if (! $projectId) {
            $projectId = DB::table('projects')->insertGetId([
                'name'        => 'Estágio 2026',
                'slug'        => 'estagio-2026',
                'is_internal' => true,
                'type'        => 'other',
                'status'      => 'active',
                'code_source' => 'none',
                'notes'       => $this->regrasDoQuadro(),
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);
        }

        $andreId = DB::table('users')->where('email', 'andre.f.mendes92@gmail.com')->value('id');

        $position = (int) DB::table('project_tasks')->where('project_id', $projectId)->max('position');

        foreach ($this->tarefas($andreId) as $tarefa) {
            if (DB::table('project_tasks')
                ->where('project_id', $projectId)
                ->where('title', $tarefa['title'])
                ->exists()) {
                continue;
            }

            DB::table('project_tasks')->insert([
                'project_id'       => $projectId,
                'assigned_user_id' => $tarefa['assigned_user_id'] ?? null,
                'title'            => $tarefa['title'],
                'description'      => $tarefa['description'],
                'status'           => 'pending',
                'position'         => ++$position,
                'created_by'       => $andreId,
                'created_at'       => $now,
                'updated_at'       => $now,
            ]);
        }
    }

    public function down(): void
    {
        $projectId = DB::table('projects')->where('slug', 'estagio-2026')->value('id');

        if ($projectId) {
            DB::table('project_tasks')->where('project_id', $projectId)->delete();
            DB::table('projects')->where('id', $projectId)->delete();
        }
    }

    /**
     * O que fica em cima da lista de tarefas, para toda a gente ler antes de
     * escolher trabalho. Vive nas notas do projecto e não numa tarefa, para
     * ninguém a poder "escolher" nem marcar como feita.
     */
    private function regrasDoQuadro(): string
    {
        $repos = self::REPOS;

        return <<<TXT
        Backlog de formação dos estagiários. As tarefas nascem sem dono: cada um escolhe as que quiser, no botão "Ficar com esta" (aqui ou no painel inicial, em "Tarefas por escolher").

        COMO SE TRABALHA
        1. Trabalho em branch própria (feat/… ou fix/…) e Pull Request aberto. Nada directo em main.
        2. Testado localmente antes de pedir revisão. Print ou vídeo do resultado no comentário da tarefa.
        3. Nada muda em produção sem cópia de segurança prévia e sem o aval do André.
        4. Ao acabar, comenta na tarefa: o que foi feito, o que ficou por fazer, o que correu mal. Só depois marcar como Feita.
        5. Bloqueado mais de 1h no mesmo problema — comenta e pergunta. Não se fica preso em silêncio.

        ONDE ESTÁ O CÓDIGO (git clone)
        • Painel de gestão (gestao.ateneya.com): {$repos['painel']}
        • Horta da Maria — gestão agrícola: {$repos['agricola']}
        • Horta da Maria — entregas: {$repos['entregas']}
        • Associação Santana: {$repos['santana']}
        • Mord Focas (bot Discord): {$repos['mordfocas']}
        • EH Lab: {$repos['ehlab']}

        Trela Amarela e taxi.codebehind.pt ainda não têm repositório no GitHub — falar com o André antes de começar essas.

        Acesso ao GitHub: organização codebehindportugal-cmd, leitura + abrir PR, sem merge. Quem ainda não tiver convite, diga.
        Ambiente local: Laragon (PHP 8.2+, MySQL). composer install, cp .env.example .env, php artisan key:generate, php artisan migrate --seed.
        TXT;
    }

    /**
     * @return array<int, array{title: string, description: string, assigned_user_id?: int|null}>
     */
    private function tarefas(?int $andreId): array
    {
        $repos = self::REPOS;

        return [
            [
                'title' => 'WP-00 · Criar contas de acesso para os estagiários em todos os sites',
                'assigned_user_id' => $andreId,
                'description' => <<<TXT
                Infra · Documentação — Estimativa 4h — bloqueia quase tudo o resto. Esta é do André, não dos estagiários.

                JÁ FEITO: contas no painel de gestão (a23399, a29132, a27720), papel Estagiário, com página de perfil para mudarem a password.

                FALTA:
                • Por cada site WordPress: um utilizador nominal por pessoa — nunca uma conta partilhada. Papel Editor por omissão; Administrator só onde a tarefa o exigir, e registar porquê.
                • Aplicações Laravel: utilizador no ambiente de desenvolvimento primeiro. Produção só se for mesmo preciso, e em leitura.
                • GitHub (organização codebehindportugal-cmd): leitura + poder abrir PR, sem merge.
                • Gestor de passwords partilhado. Passwords entregues por lá, nunca por chat ou email. 2FA onde o site suportar.

                CRITÉRIOS DE ACEITAÇÃO
                • Tabela de acessos: site/serviço × pessoa × papel × data de criação.
                • Cada estagiário confirma por comentário que entrou em todos os acessos que lhe cabem.
                • Data de fim do estágio marcada e tarefa de revogação criada desde já.
                TXT,
            ],

            [
                'title' => 'WP-01 · Inventário de plugins, temas e versões em todos os sites',
                'description' => <<<TXT
                WordPress · Documentação — Estimativa 6h — Dificuldade baixa

                CONTEXTO: temos sites espalhados por várias VPS e não há uma vista única do que está instalado onde.

                O QUE FAZER
                • Por cada site: domínio, servidor, versão do WP, tema activo (e se é filho), plugins activos com versão, plugins inactivos, plugins sem actualização há mais de 12 meses.
                • Marcar a vermelho: plugins abandonados ou removidos do repositório, versões de WP ou PHP desactualizadas, plugins nulled.
                • Onde houver WP-CLI, usar wp plugin list / wp theme list / wp core version em vez de ir ao painel à mão.

                CRITÉRIOS DE ACEITAÇÃO
                • Folha com uma linha por site, sem campos vazios.
                • Lista final "top 10 riscos" ordenada por gravidade, com uma frase de justificação por item.
                TXT,
            ],

            [
                'title' => 'WP-02 · Auditoria Core Web Vitals + plano de correções (ateneya.com)',
                'description' => <<<TXT
                WordPress · Melhoria — Estimativa 8h — Dificuldade média

                CONTEXTO: já houve um esforço de modernização do site; queremos medir onde estamos hoje.

                O QUE FAZER
                • PageSpeed Insights e Lighthouse (mobile e desktop) em: homepage, uma página de serviço, um artigo.
                • Registar LCP, CLS, INP e TBT ANTES de qualquer alteração.
                • Identificar as 5 maiores causas concretas (imagens sem dimensões, CSS/JS do Elementor a bloquear render, fontes sem font-display, ausência de cache…).
                • Escrever plano de correcção priorizado por esforço/impacto. NÃO aplicar nada ainda.

                CRITÉRIOS DE ACEITAÇÃO
                • Tabela de métricas antes, com data e URL de cada relatório.
                • Plano com 5 a 8 acções, cada uma com estimativa e risco associado.
                TXT,
            ],

            [
                'title' => 'WP-03 · Otimização de imagens e lazy loading (aplicar o plano do WP-02)',
                'description' => <<<TXT
                WordPress · Melhoria — Estimativa 6h — Dificuldade média — DEPENDE DE: WP-02

                O QUE FAZER
                • Converter imagens pesadas para WebP, garantir width/height no HTML e lazy loading fora do primeiro ecrã.
                • A imagem principal acima da dobra fica SEM lazy loading — é o LCP.
                • Repetir a medição do WP-02 e comparar.

                CRITÉRIOS DE ACEITAÇÃO
                • Nenhuma imagem servida acima de 300 KB sem justificação.
                • Tabela antes/depois das métricas; LCP melhorado ou explicação do porquê de não ter melhorado.
                • Zero regressões visuais — comparação lado a lado em desktop e mobile.
                TXT,
            ],

            [
                'title' => 'WP-04 · Hardening de login e rotação de passwords de administração',
                'description' => <<<TXT
                WordPress · Infra — Estimativa 5h — Dificuldade média
                Só quando já houver confiança no trabalho feito — mexe em contas de produção.

                CONTEXTO: tivemos um pico de pedidos de recuperação de password num site de cliente; assumimos que outros podem ser alvo do mesmo.

                O QUE FAZER
                • Por site: listar utilizadores com papel administrator, identificar contas antigas ou não usadas e PROPOR remoção. Não remover sem aval.
                • Gerar passwords novas e fortes para as contas de administração nossas, guardadas no gestor de passwords. Nunca em texto na tarefa nem no chat.
                • Activar limitação de tentativas de login e 2FA onde o site suportar.
                • Confirmar que o XML-RPC e a enumeração de utilizadores (/?author=1) estão fechados.

                CRITÉRIOS DE ACEITAÇÃO
                • Checklist por site com o antes/depois de cada item.
                • Nenhuma password escrita na tarefa.
                • Um login de teste feito depois da alteração em cada site — não deixar nada partido.
                TXT,
            ],

            [
                'title' => 'WP-05 · Auditoria SEO on-page dos sites de cliente',
                'description' => <<<TXT
                WordPress · Melhoria — Estimativa 8h — Dificuldade baixa

                O QUE FAZER
                • Por site: títulos e meta descrições duplicados ou em falta, hierarquia de headings (um h1 por página), alt nas imagens, sitemap XML acessível, robots.txt sensato, dados estruturados Schema.org.
                • Correr um crawler (Screaming Frog gratuito até 500 URLs) e exportar os erros.

                CRITÉRIOS DE ACEITAÇÃO
                • Relatório por site com contagem de problemas por categoria.
                • As 10 páginas mais importantes de cada site corrigidas; o resto fica em lista.
                TXT,
            ],

            [
                'title' => 'WP-06 · Auditoria de responsividade e links partidos',
                'description' => <<<TXT
                WordPress · Bug — Estimativa 6h — Dificuldade baixa

                O QUE FAZER
                • Testar cada site a 360px, 768px e 1440px: overflow horizontal, texto cortado, botões pequenos demais para o dedo, menus que não fecham.
                • Verificação de links partidos (internos e externos) e de imagens em falta.
                • Testar formulários de contacto — o email chega mesmo?

                CRITÉRIOS DE ACEITAÇÃO
                • Um screenshot por problema, com URL e largura de ecrã.
                • Lista separada entre "corrigido" e "precisa de decisão".
                TXT,
            ],

            [
                'title' => 'WP-07 · Procedimento documentado de atualização segura de WordPress',
                'description' => <<<TXT
                WordPress · Documentação — Estimativa 5h — Dificuldade média

                CONTEXTO: o painel já actualiza sites sozinho, com cópia e reposição automática. Queremos o procedimento manual escrito e testado para quando for preciso fazer à mão — e para se perceber o que o painel faz.

                O QUE FAZER
                • Passo a passo: cópia de ficheiros + base de dados → actualizar plugins → verificar → actualizar tema → verificar → actualizar core → verificar → como repor SÓ o item que partiu.
                • Lista de verificações pós-actualização: homepage, login, checkout se houver loja, formulários, consola do browser sem erros.
                • Testar de ponta a ponta num site de teste, incluindo uma reposição propositada.

                CRITÉRIOS DE ACEITAÇÃO
                • Documento que outra pessoa consegue seguir sem perguntar nada.
                • Prova de que a reposição foi testada — não basta descrever.
                TXT,
            ],

            [
                'title' => 'WP-08 · Testes de checkout e zonas de envio (WooCommerce)',
                'description' => <<<TXT
                WordPress · Bug — Estimativa 6h — Dificuldade média

                CONTEXTO: já houve uma encomenda que passou por cima das restrições geográficas de envio.

                O QUE FAZER
                • Matriz de teste de códigos postais: dentro da zona, fora da zona, fronteira, formato inválido, ilhas.
                • Encomendas de teste em modo sandbox para cada caso, registando o método de envio oferecido e o custo.
                • Verificar em especial a zona "Localizações não cobertas" e se algum método de taxa fixa está lá activo.

                CRITÉRIOS DE ACEITAÇÃO
                • Matriz preenchida com resultado esperado vs. obtido.
                • Qualquer divergência aberta como tarefa de bug própria, com passos de reprodução.
                TXT,
            ],

            [
                'title' => 'WP-09 · Revisão do estado geral de cada site (exceto Loja Amster)',
                'description' => <<<TXT
                WordPress · Documentação — Estimativa 8h — Dificuldade baixa
                Boa para arrancar: percorre-se tudo o que temos, ganha-se contexto, e o risco é nulo.

                CONTEXTO: queremos uma vista honesta de como cada site está hoje — aspecto, conteúdo e funcionamento — antes de decidir onde investir.
                FORA DO ÂMBITO: lojaamster.com — não mexer nem avaliar.

                O QUE FAZER
                • Por site: percorrer as páginas principais como visitante e registar aspecto desactualizado, conteúdo velho (anos, preços, equipa, notícias paradas), imagens de baixa qualidade ou esticadas, textos de exemplo esquecidos ("Lorem ipsum", "Sample Page"), erros PHP visíveis, consola do browser com erros.
                • Screenshot da homepage de cada site, em desktop e mobile.
                • Nota de 1 a 5 em quatro eixos: aspecto, conteúdo actual, velocidade percebida, funcionamento.

                CRITÉRIOS DE ACEITAÇÃO
                • Uma ficha por site com as notas, os screenshots e 3 a 5 observações concretas. Nada de "está feio" — dizer o quê e onde.
                • Ranking final dos sites que mais precisam de atenção, com uma frase de justificação.
                • Loja Amster não aparece no relatório.
                TXT,
            ],

            [
                'title' => 'WP-10 · Auditoria de conformidade legal dos sites',
                'description' => <<<TXT
                WordPress · Documentação — Estimativa 10h — Dificuldade média

                CONTEXTO: muitos sites nossos e de clientes podem não ter os textos legais obrigatórios. As coimas da ASAE vão de milhares de euros por infracção, e o responsável é o cliente — mas somos nós que fazemos os sites.

                O QUE FAZER: por cada site, preencher a checklist com Sim / Não / Não aplicável e o link directo para a página onde está.
                 1. Identificação completa da empresa — nome, NIF, morada da sede, contactos, n.º de registo comercial (habitualmente no rodapé)
                 2. Política de Privacidade (RGPD) — que dados recolhe, finalidade, fundamento, prazos, direitos do titular, contacto
                 3. Política de Cookies + banner de consentimento — aceitar e recusar com o mesmo destaque; nada de cookies não essenciais antes do consentimento
                 4. Termos e Condições — obrigatório sempre que há venda, registo ou área de cliente
                 5. Custo das chamadas junto de cada número — "chamada para a rede fixa nacional" / "rede móvel nacional", na homepage e junto do número onde ele aparecer (DL 59/2021)
                 6. Livro de Reclamações Electrónico — link visível para livroreclamacoes.pt
                 7. Entidade de RAL + plataforma ODR europeia — obrigatório para quem vende a consumidores
                 8. Direito de livre resolução — 14 dias (só lojas online): texto e formulário de devolução
                 9. Preço mais baixo dos 30 dias anteriores (só lojas online, onde haja promoções)
                10. Formulário de contacto com consentimento — checkbox não pré-marcada + link para a política de privacidade

                REGRAS DE EXECUÇÃO
                • Verificar sempre na página em produção, não no painel. Link que exista mas dê 404 conta como "Não".
                • NÃO escrever textos legais novos. Os textos são responsabilidade do cliente ou do advogado; aqui só se levanta o que falta.
                • Onde já exista texto, ler e assinalar se está manifestamente desactualizado (legislação revogada, nome de empresa errado, ainda fala na "Directiva 95/46/CE").

                CRITÉRIOS DE ACEITAÇÃO
                • Folha final com uma linha por site e uma coluna por item.
                • Separador "prioridade" com os sites que falham nos itens 1 a 6 — são os mais graves e os mais fáceis de corrigir.
                • Email-tipo redigido, um só e genérico, para avisar clientes do que lhes falta.
                TXT,
            ],

            [
                'title' => 'LR-01 · Trela Amarela — mostrar o nome do cão e do cliente nas marcações',
                'description' => <<<TXT
                Laravel · Bug — Estimativa 4h — Dificuldade baixa
                Repositório: ainda não está no GitHub. Falar com o André antes de começar.

                CONTEXTO: pedido do cliente. Hoje a marcação mostra só o serviço ("hotel cão"), sem se saber de quem é.

                O QUE FAZER
                • Na listagem e no detalhe da marcação, mostrar o nome do cão e o nome do dono.
                • Cuidado com o N+1: carregar as relações com eager loading.
                • Tornar o campo pesquisável, se a listagem já tiver pesquisa.

                CRITÉRIOS DE ACEITAÇÃO
                • Nome visível na lista e no detalhe, em desktop e mobile.
                • Debugbar ou Telescope sem query duplicada por linha.
                • Marcações antigas sem cliente associado não rebentam a página — mostram "—".
                TXT,
            ],

            [
                'title' => 'LR-02 · Horta da Maria — encomendas canceladas aparecem na lista de preparação',
                'description' => <<<TXT
                Laravel · Bug — Estimativa 3h — Dificuldade baixa
                Repositório: {$repos['agricola']}
                Boa primeira tarefa de código: pequena, fechada, e dá o ciclo branch → PR → revisão.

                O QUE FAZER
                • Reproduzir: cancelar uma encomenda e confirmar que continua na lista de preparação.
                • Corrigir o filtro/scope da consulta e verificar se o mesmo problema existe noutros ecrãs (guias, contagens do dashboard).
                • Escrever um teste que falhe antes da correcção e passe depois.

                CRITÉRIOS DE ACEITAÇÃO
                • Encomenda cancelada desaparece da preparação e das contagens.
                • Teste automatizado incluído no PR.
                • Confirmado que nenhuma encomenda válida foi escondida por engano.
                TXT,
            ],

            [
                'title' => 'LR-03 · Modelar assinaturas B2C — casos de pausa e renovação (só análise + testes)',
                'description' => <<<TXT
                Laravel · Documentação — Estimativa 6h — Dificuldade média
                Repositório: {$repos['entregas']} (confirmar com o André se é aqui ou em {$repos['agricola']})

                CONTEXTO: as regras já estão definidas; falta garantir que o código as cumpre.
                REGRAS: uma assinatura B2C são sempre 4 entregas — semanal corre 4 semanas, de 15 em 15 dias corre 8 semanas. "De 15 em 15 dias" é de duas em duas semanas no dia fixo do cliente. Dias de entrega possíveis: segunda, quarta e sábado. Pausar não perde entregas — empurra-as para a frente.

                O QUE FAZER
                • Tabela de casos: início a uma segunda semanal, início a um sábado quinzenal, pausa de 1 semana, pausa de 3 semanas, pausa sobre feriado, etc., com as datas esperadas das 4 entregas.
                • Testes automatizados para cada caso contra o código actual.
                • NÃO corrigir nada — apenas listar quais falham.

                CRITÉRIOS DE ACEITAÇÃO
                • Mínimo 10 casos de teste, com as datas esperadas calculadas à mão ANTES de correr o código.
                • Relatório: casos que passam / casos que falham, com o comportamento observado em cada falha.
                TXT,
            ],

            [
                'title' => 'LR-04 · Cobertura de testes num módulo à escolha (Pest/PHPUnit)',
                'description' => <<<TXT
                Laravel · Melhoria — Estimativa 8h — Dificuldade média
                Repositório: à escolha, combinado com o André.

                O QUE FAZER
                • Escolher um módulo com o André, criar factories e seeders em falta.
                • Feature tests para o caminho feliz e pelo menos 3 casos de erro (validação, permissões, registo inexistente).
                • Deixar a suite a correr limpa: php artisan test verde do princípio ao fim.

                CRITÉRIOS DE ACEITAÇÃO
                • Testes independentes: correm em qualquer ordem e não dependem da base de dados local.
                • Nenhum teste a tocar em serviços externos reais — usar fakes.
                TXT,
            ],

            [
                'title' => 'LR-05 · Filament — listagem com filtros, pesquisa e ações em massa',
                'description' => <<<TXT
                Laravel · Melhoria — Estimativa 6h — Dificuldade média
                Repositório: {$repos['painel']}

                O QUE FAZER
                • Sobre um recurso Filament existente: filtros por estado e intervalo de datas, pesquisa nas colunas relevantes, e uma acção em massa útil (exportar CSV, marcar como tratado…).
                • Garantir que as acções destrutivas pedem confirmação.

                CRITÉRIOS DE ACEITAÇÃO
                • Filtros combináveis entre si sem erros.
                • Listagem com mais de 1.000 registos de teste continua rápida — sem N+1, com paginação.
                TXT,
            ],

            [
                'title' => 'LR-06 · Seeders e dados de demonstração para ambiente local',
                'description' => <<<TXT
                Laravel · Melhoria — Estimativa 5h — Dificuldade baixa
                Repositório: à escolha, entre os projectos Laravel.

                CONTEXTO: montar um projecto do zero em Laragon leva demasiado tempo por falta de dados.

                O QUE FAZER
                • Factories e um DemoSeeder que gere um cenário completo e realista em português: clientes, encomendas em vários estados, utilizadores com papéis diferentes.
                • Nunca usar dados reais de clientes.

                CRITÉRIOS DE ACEITAÇÃO
                • php artisan migrate:fresh --seed deixa a aplicação utilizável, com login de demonstração documentado.
                • Documentado no README do projecto.
                TXT,
            ],

            [
                'title' => 'LR-07 · README de setup local por projeto',
                'description' => <<<TXT
                Documentação — Estimativa 5h — Dificuldade baixa
                Boa para arrancar: obriga a montar cada projecto de raiz, que é o que precisam de saber fazer.

                Repositórios:
                • {$repos['painel']}
                • {$repos['agricola']}
                • {$repos['entregas']}
                • {$repos['santana']}
                • {$repos['mordfocas']}
                • {$repos['ehlab']}

                O QUE FAZER
                • Para cada projecto Laravel activo: README de arranque com requisitos, .env.example comentado, passos de instalação, comandos úteis, como correr testes, onde vive a produção.
                • Validar seguindo o próprio README numa pasta limpa. Se falhar num passo, o README está errado — não a máquina.

                CRITÉRIOS DE ACEITAÇÃO
                • Cada README testado por outra pessoa que não o escreveu.
                • Zero credenciais reais commitadas.
                TXT,
            ],

            [
                'title' => 'LR-08 · Normalizar validação e mensagens de erro em português',
                'description' => <<<TXT
                Laravel · Melhoria — Estimativa 6h — Dificuldade média
                Repositório: à escolha, entre os projectos Laravel.

                O QUE FAZER
                • Passar validação inline dos controllers para Form Requests.
                • Traduzir e uniformizar mensagens de validação em lang/pt, incluindo os nomes dos campos.
                • Verificar formatos portugueses: NIF, código postal 0000-000, telefone, IBAN onde aplicável.

                CRITÉRIOS DE ACEITAÇÃO
                • Nenhuma mensagem de validação em inglês visível ao utilizador.
                • Testes para as regras de formato português.
                TXT,
            ],

            [
                'title' => 'LR-09 · gestao.ateneya — proposta de separação Servidor vs. Site (só desenho)',
                'description' => <<<TXT
                Laravel · Documentação — Estimativa 6h — Dificuldade média-alta
                Repositório: {$repos['painel']}
                Só quando já houver confiança no trabalho feito.

                CONTEXTO: hoje cada registo de "servidor" é na prática um domínio, e há vários domínios na mesma VPS. Antes de mexer, queremos o desenho.

                O QUE FAZER
                • Levantar o modelo actual e mapear que campos pertencem à VPS e quais pertencem ao site.
                • Propor o novo esquema (servers ↔ sites, um-para-muitos), com o rascunho das migrations e o plano de migração dos dados existentes.
                • Listar todo o código que vai partir com a mudança.

                CRITÉRIOS DE ACEITAÇÃO
                • Diagrama do modelo antes e depois.
                • Plano de migração reversível, com rollback descrito.
                • NADA é aplicado sem o aval do André.
                TXT,
            ],

            [
                'title' => 'LR-10 · taxi.codebehind.pt — diagnosticar a lentidão nas APIs',
                'description' => <<<TXT
                Laravel · Bug — Estimativa 8h — Dificuldade média-alta — Servidor: Contabo D (45.10.154.155)
                Repositório: ainda não está no GitHub. Falar com o André antes de começar.
                A tarefa mais exigente da lista — para quem tiver mais jeito para depuração.

                CONTEXTO: o site do táxi está lento nos pedidos às APIs. Antes de optimizar seja o que for, é preciso saber ONDE se perde o tempo. A maior parte destes casos é uma de três coisas: consultas N+1, chamadas a serviços externos em série, ou ausência de cache.

                O QUE FAZER — por esta ordem, sem saltar passos
                1. MEDIR. Listar os endpoints mais usados e cronometrar cada um (aba Network do browser ou curl -w "%{time_total}"), 5 medições por endpoint. Tabela com mínimo, máximo e mediana.
                2. SEPARAR. Para cada endpoint lento, dividir o tempo em: tempo de servidor, tempo à espera de APIs externas, tempo de transferência. Sem esta separação não há diagnóstico.
                3. INSTRUMENTAR. Debugbar ou Telescope em desenvolvimento, registando por endpoint: número de queries, queries mais lentas, queries repetidas, memória, e cada chamada HTTP externa com a sua duração.
                4. VERIFICAR O ÓBVIO. N+1 sem eager loading; falta de índices nas colunas usadas em where/join; chamadas externas em série que podiam ser concorrentes ou em fila; respostas de API externa que podiam ser cacheadas; APP_DEBUG=true ou falta de cache de config e rotas em produção; versão de PHP e se o OPcache está ligado.
                5. PROPOR. Lista de correcções ordenada por ganho estimado vs. esforço.

                CRITÉRIOS DE ACEITAÇÃO
                • Relatório com a tabela de medições e a repartição do tempo por endpoint.
                • Causa provável identificada e PROVADA com dados, não com palpites, para os 3 endpoints mais lentos.
                • Plano de correcção com o ganho esperado por item.
                • NADA é alterado em produção nesta tarefa. As correcções saem daqui como tarefas novas.
                TXT,
            ],
        ];
    }
};
