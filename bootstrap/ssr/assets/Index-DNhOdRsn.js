import { ref, computed, watch, withCtx, unref, createTextVNode, toDisplayString, createVNode, openBlock, createBlock, createCommentVNode, Fragment, renderList, withModifiers, withDirectives, vModelText, vModelSelect, useSSRContext } from "vue";
import { ssrRenderComponent, ssrInterpolate, ssrRenderList, ssrRenderClass, ssrRenderStyle, ssrRenderAttr, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual } from "vue/server-renderer";
import { _ as _sfc_main$1 } from "./AppLayout-BWDcck4N.js";
import { useForm, Link, router } from "@inertiajs/vue3";
const _sfc_main = {
  __name: "Index",
  __ssrInlineRender: true,
  props: { mesas: Array, zonas: Array },
  setup(__props) {
    const props = __props;
    const mesasMapa = ref(props.mesas.map((mesa) => ({ ...mesa })));
    const zonasMapa = ref((props.zonas ?? []).map((zona) => ({ ...zona })));
    const editarMapa = ref(false);
    const mesaSelecionadaId = ref(null);
    const zonaSelecionadaId = ref(null);
    const drag = ref(null);
    const lugaresOcupados = ref("");
    const letraSubmesaNova = ref("");
    const mesasGrupo = ref("");
    const lugaresSubmesa = ref({});
    const submesaLetras = ["A", "B", "C", "D"];
    const tiposZona = [
      ["zona", "Zona"],
      ["texto", "Texto"],
      ["entrada", "Entrada"],
      ["porta", "Porta"],
      ["wc", "WC"],
      ["palco", "Palco"],
      ["balcao", "Balcao"],
      ["cozinha", "Cozinha"]
    ];
    const zonaForm = useForm({ nome: "", tipo: "zona", mapa_x: 45, mapa_y: 45, mapa_largura: 10, mapa_altura: 8 });
    const zonaEditForm = useForm({ nome: "", tipo: "zona", mapa_x: 45, mapa_y: 45, mapa_largura: 10, mapa_altura: 8 });
    const estadoClass = {
      livre: "border-emerald-300 bg-emerald-50 text-emerald-950",
      grupo: "border-violet-300 bg-violet-50 text-violet-950",
      ocupada: "border-red-300 bg-red-50 text-red-950",
      reservada: "border-amber-300 bg-amber-50 text-amber-950"
    };
    const estadoDot = {
      livre: "bg-emerald-500",
      grupo: "bg-violet-600",
      ocupada: "bg-red-500",
      reservada: "bg-amber-500"
    };
    const segmentoClass = {
      livre: "bg-emerald-500/85",
      grupo: "bg-violet-600/90",
      ocupada: "bg-red-600/90",
      reservada: "bg-amber-400/90"
    };
    const estadoVisual = (mesa) => {
      if (mesa?.pedidos_grupo?.length) {
        return "grupo";
      }
      if (!mesa?.submesas?.length) {
        return pedidosAtivos(mesa).length && mesaGrande(mesa) ? "grupo" : mesa?.estado ?? "livre";
      }
      if (mesa.submesas.some((submesa) => estadoSubmesa(submesa) === "grupo")) {
        return "grupo";
      }
      if (mesa.submesas.some((submesa) => submesa.estado === "ocupada")) {
        return "ocupada";
      }
      if (mesa.estado === "reservada" || mesa.submesas.some((submesa) => submesa.estado === "reservada")) {
        return "reservada";
      }
      return "livre";
    };
    const mesaGrande = (mesa) => Number(mesa?.capacidade ?? 0) > 10;
    const estadoSubmesa = (submesa) => {
      if (submesa?.pedidos_grupo?.length) {
        return "grupo";
      }
      if (pedidosAtivos(submesa).length && mesaGrande(submesa)) {
        return "grupo";
      }
      return pedidosAtivos(submesa).length ? "ocupada" : submesa?.estado ?? "livre";
    };
    const segmentosMesa = (mesa) => {
      if (mesa?.submesas?.length) {
        return mesa.submesas.map((submesa) => ({
          id: submesa.id,
          label: letraSubmesa(submesa),
          estado: estadoSubmesa(submesa),
          capacidade: Number(submesa.capacidade || 1)
        }));
      }
      const estado = mesa?.pedidos_grupo?.length ? "grupo" : pedidosAtivos(mesa).length ? mesaGrande(mesa) ? "grupo" : "ocupada" : mesa?.estado ?? "livre";
      return [{
        id: mesa.id,
        label: mesa.numero,
        estado,
        capacidade: Number(mesa?.capacidade || 1)
      }];
    };
    const mesaParcial = (mesa) => {
      const estados = new Set(segmentosMesa(mesa).map((segmento) => segmento.estado));
      return estados.size > 1;
    };
    const mesaLivre = (mesa) => estadoVisual(mesa) === "livre";
    const podeAbrirPedido = (mesa) => mesa && (mesaLivre(mesa) || estadoVisual(mesa) === "reservada");
    const podeMarcarLivre = (mesa) => mesa && !mesaLivre(mesa) && !pedidosAtivosDaMesa(mesa).length;
    const mesaPrincipalDividida = (mesa) => mesa && !mesa.mesa_principal_id && mesa.submesas?.length > 0;
    const mesaDivididaLivre = (mesa) => mesaPrincipalDividida(mesa) && mesaLivre(mesa);
    const podeAbrirPedidoMesaCompleta = (mesa) => podeAbrirPedido(mesa) && (!mesaPrincipalDividida(mesa) || mesaDivididaLivre(mesa));
    const lugaresOcupadosNumero = computed(() => Number(lugaresOcupados.value || 0));
    const precisaSubmesaSelecionada = computed(() => mesaSelecionada.value && !mesaSelecionada.value.mesa_principal_id && lugaresOcupadosNumero.value > 0 && lugaresOcupadosNumero.value < Number(mesaSelecionada.value.capacidade || 0));
    const precisaMesasGrupoSelecionada = computed(() => mesaSelecionada.value && lugaresOcupadosNumero.value > Number(mesaSelecionada.value.capacidade || 0));
    const podeAbrirPedidoSelecionado = computed(() => podeAbrirPedidoMesaCompleta(mesaSelecionada.value) && lugaresOcupadosNumero.value > 0 && (!precisaSubmesaSelecionada.value || letraSubmesaNova.value.trim()) && (!precisaMesasGrupoSelecionada.value || mesasGrupo.value.trim()));
    const pedidosAtivos = (mesa) => [
      ...mesa?.pedidos ?? [],
      ...mesa?.pedidos_grupo ?? []
    ];
    const pedidosAtivosDaMesa = (mesa) => [
      ...pedidosAtivos(mesa),
      ...(mesa?.submesas ?? []).flatMap((submesa) => pedidosAtivos(submesa))
    ];
    const pedidosAtivosDaMesaDetalhados = (mesa) => [
      ...pedidosAtivos(mesa).map((pedido) => ({ pedido, local: mesa.designacao })),
      ...(mesa?.submesas ?? []).flatMap((submesa) => pedidosAtivos(submesa).map((pedido) => ({ pedido, local: letraSubmesa(submesa) })))
    ];
    const mesaSelecionada = computed(() => mesasMapa.value.find((mesa) => mesa.id === mesaSelecionadaId.value) ?? (zonaSelecionadaId.value ? null : mesasMapa.value[0]));
    const zonaSelecionada = computed(() => zonasMapa.value.find((zona) => zona.id === zonaSelecionadaId.value) ?? null);
    watch(() => props.zonas, (zonas) => {
      zonasMapa.value = (zonas ?? []).map((zona) => ({ ...zona }));
    }, { deep: true });
    watch(zonaSelecionada, (zona) => {
      if (!zona) {
        return;
      }
      zonaEditForm.nome = zona.nome;
      zonaEditForm.tipo = zona.tipo;
      zonaEditForm.mapa_x = zona.mapa_x;
      zonaEditForm.mapa_y = zona.mapa_y;
      zonaEditForm.mapa_largura = zona.mapa_largura;
      zonaEditForm.mapa_altura = zona.mapa_altura;
    });
    const mesaStyle = (mesa) => ({
      left: `${mesa.mapa_x}%`,
      top: `${mesa.mapa_y}%`,
      width: `${mesa.mapa_largura}%`,
      height: `${mesa.mapa_altura}%`
    });
    const zonaStyle = (zona) => ({
      left: `${zona.mapa_x}%`,
      top: `${zona.mapa_y}%`,
      width: `${zona.mapa_largura}%`,
      height: `${zona.mapa_altura}%`
    });
    const zonaVertical = (zona) => Number(zona.mapa_altura || 0) > Number(zona.mapa_largura || 0);
    const zonaClasse = (zona) => ["entrada", "porta"].includes(zona.tipo) ? "border-transparent bg-white/95 text-slate-700 shadow-sm" : zona.tipo === "wc" ? "border-slate-700/80 bg-sky-50/70 text-slate-900" : zona.tipo === "palco" ? "border-slate-700/80 bg-amber-50/70 text-slate-900" : "border-slate-700/80 bg-white/40 text-slate-900";
    const selecionarMesa = (mesa) => {
      mesaSelecionadaId.value = mesa.id;
      zonaSelecionadaId.value = null;
      lugaresOcupados.value = "";
      letraSubmesaNova.value = "";
      mesasGrupo.value = "";
    };
    const selecionarZona = (zona) => {
      zonaSelecionadaId.value = zona.id;
      mesaSelecionadaId.value = null;
      lugaresOcupados.value = "";
      letraSubmesaNova.value = "";
      mesasGrupo.value = "";
    };
    const limitar = (valor, minimo, maximo) => Math.min(maximo, Math.max(minimo, Math.round(valor)));
    const iniciarDrag = (event, mesa) => {
      selecionarMesa(mesa);
      if (!editarMapa.value) {
        return;
      }
      const mapa = event.currentTarget.closest("[data-sala-mapa]");
      const rect = mapa.getBoundingClientRect();
      drag.value = {
        id: mesa.id,
        tipo: "mesa",
        rect,
        startX: event.clientX,
        startY: event.clientY,
        mesaX: mesa.mapa_x,
        mesaY: mesa.mapa_y
      };
      window.addEventListener("mousemove", moverMesa);
      window.addEventListener("mouseup", pararDrag);
    };
    const moverMesa = (event) => {
      if (!drag.value) {
        return;
      }
      const mesa = mesasMapa.value.find((item) => item.id === drag.value.id);
      const elemento = drag.value.tipo === "zona" ? zonasMapa.value.find((item) => item.id === drag.value.id) : mesa;
      if (!elemento) {
        return;
      }
      const dx = (event.clientX - drag.value.startX) / drag.value.rect.width * 100;
      const dy = (event.clientY - drag.value.startY) / drag.value.rect.height * 100;
      elemento.mapa_x = limitar(drag.value.mesaX + dx, 0, 100 - elemento.mapa_largura);
      elemento.mapa_y = limitar(drag.value.mesaY + dy, 0, 100 - elemento.mapa_altura);
    };
    const iniciarDragZona = (event, zona) => {
      selecionarZona(zona);
      if (!editarMapa.value) {
        return;
      }
      const mapa = event.currentTarget.closest("[data-sala-mapa]");
      const rect = mapa.getBoundingClientRect();
      drag.value = {
        id: zona.id,
        tipo: "zona",
        rect,
        startX: event.clientX,
        startY: event.clientY,
        mesaX: zona.mapa_x,
        mesaY: zona.mapa_y
      };
      window.addEventListener("mousemove", moverMesa);
      window.addEventListener("mouseup", pararDrag);
    };
    const pararDrag = () => {
      drag.value = null;
      window.removeEventListener("mousemove", moverMesa);
      window.removeEventListener("mouseup", pararDrag);
    };
    const alterarTamanho = (elemento, campo, valor, minimo = 4, maximo = 40) => {
      elemento[campo] = limitar(Number(valor), minimo, maximo);
      elemento.mapa_x = limitar(elemento.mapa_x, 0, 100 - elemento.mapa_largura);
      elemento.mapa_y = limitar(elemento.mapa_y, 0, 100 - elemento.mapa_altura);
    };
    const guardarMapa = () => {
      router.patch(route("mesas.mapa.guardar"), {
        mesas: mesasMapa.value.map((mesa) => ({
          id: mesa.id,
          mapa_x: mesa.mapa_x,
          mapa_y: mesa.mapa_y,
          mapa_largura: mesa.mapa_largura,
          mapa_altura: mesa.mapa_altura
        })),
        zonas: zonasMapa.value.map((zona) => ({
          id: zona.id,
          mapa_x: zona.mapa_x,
          mapa_y: zona.mapa_y,
          mapa_largura: zona.mapa_largura,
          mapa_altura: zona.mapa_altura
        }))
      }, {
        preserveScroll: true,
        onSuccess: () => {
          editarMapa.value = false;
        }
      });
    };
    const criarZona = () => {
      zonaForm.post(route("zonas.store"), {
        preserveScroll: true,
        onSuccess: () => {
          zonaForm.reset();
          zonaForm.tipo = "zona";
          zonaForm.mapa_x = 45;
          zonaForm.mapa_y = 45;
          zonaForm.mapa_largura = 10;
          zonaForm.mapa_altura = 8;
          router.reload({ only: ["mesas", "zonas"], preserveScroll: true });
        }
      });
    };
    const atualizarZona = () => {
      if (!zonaSelecionada.value) {
        return;
      }
      zonaEditForm.transform((dados) => ({
        ...dados,
        mapa_x: zonaSelecionada.value.mapa_x,
        mapa_y: zonaSelecionada.value.mapa_y,
        mapa_largura: zonaSelecionada.value.mapa_largura,
        mapa_altura: zonaSelecionada.value.mapa_altura
      })).patch(route("zonas.update", zonaSelecionada.value.id), {
        preserveScroll: true,
        onSuccess: () => router.reload({ only: ["mesas", "zonas"], preserveScroll: true }),
        onFinish: () => zonaEditForm.transform((dados) => dados)
      });
    };
    const apagarZona = () => {
      if (!zonaSelecionada.value || !confirm(`Apagar ${zonaSelecionada.value.nome}?`)) {
        return;
      }
      router.delete(route("zonas.destroy", zonaSelecionada.value.id), {
        preserveScroll: true,
        onSuccess: () => {
          zonaSelecionadaId.value = null;
          router.reload({ only: ["mesas", "zonas"], preserveScroll: true });
        }
      });
    };
    const criarPedido = (mesa, lugares = "", letra = "") => {
      router.post(route("pedidos.store"), {
        mesa_id: mesa.id,
        lugares_ocupados: lugares || null,
        submesa_letra: lugares ? letra ? letra.toUpperCase() : null : null,
        mesas_grupo: mesasGrupo.value || null,
        observacoes: ""
      });
    };
    const juntarMesa = (mesa) => {
      if (confirm(`Juntar novamente a ${mesa.designacao}?`)) {
        router.delete(route("mesas.juntar", mesa.id), { preserveScroll: true });
      }
    };
    const abrirPedidoSelecionado = () => {
      criarPedido(mesaSelecionada.value, lugaresOcupados.value, letraSubmesaNova.value);
    };
    const abrirPedidoSubmesa = (submesa) => {
      criarPedido(submesa, lugaresSubmesa.value[submesa.id] || 1);
    };
    const libertarMesa = (mesa) => {
      router.patch(route("mesas.libertar", mesa.id), {}, { preserveScroll: true });
    };
    const apagarMesa = (mesa) => {
      if (confirm(`Apagar ${mesa.designacao}? Esta ação remove a mesa do mapa.`)) {
        router.delete(route("mesas.destroy", mesa.id), { preserveScroll: true });
      }
    };
    const letraSubmesa = (submesa) => submesa.designacao.replace(/^Mesa\s*/i, "");
    const lugaresVazios = (mesa) => {
      if (mesa.submesas?.length) {
        return mesa.submesas.filter((submesa) => submesa.estado === "livre").reduce((total, submesa) => total + Number(submesa.capacidade || 0), 0);
      }
      return mesaLivre(mesa) ? Number(mesa.capacidade || 0) : 0;
    };
    const textoLugaresVazios = (mesa) => {
      const vazios = lugaresVazios(mesa);
      return `${vazios} ${vazios === 1 ? "lugar vazio" : "lugares vazios"}`;
    };
    const textoLugaresVaziosCurto = (mesa) => `${lugaresVazios(mesa)} livres`;
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(_sfc_main$1, _attrs, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="mb-5 flex flex-wrap items-center justify-between gap-3"${_scopeId}><div${_scopeId}><h1 class="text-2xl font-bold"${_scopeId}>Mapa da sala</h1><p class="mt-1 text-sm text-slate-500"${_scopeId}>Salão com 41 mesas. Distribui as mesas no mapa e divide cada mesa em submesas quando precisares.</p></div><div class="flex flex-wrap gap-2"${_scopeId}><button type="button" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold"${_scopeId}>${ssrInterpolate(editarMapa.value ? "Sair da edição" : "Editar mapa")}</button>`);
            if (editarMapa.value) {
              _push2(`<button type="button" class="rounded-md bg-slate-900 px-3 py-2 text-sm font-semibold text-white"${_scopeId}>Guardar mapa</button>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(ssrRenderComponent(unref(Link), {
              href: _ctx.route("mesas.create"),
              class: "rounded-md bg-slate-900 px-3 py-2 text-sm font-semibold text-white"
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`Nova mesa`);
                } else {
                  return [
                    createTextVNode("Nova mesa")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`</div></div><div class="mb-4 grid gap-3 rounded-lg bg-white p-4 text-sm shadow-sm md:grid-cols-4"${_scopeId}><div class="flex items-center gap-2"${_scopeId}><span class="h-3 w-3 rounded-full bg-emerald-500"${_scopeId}></span>Livre</div><div class="flex items-center gap-2"${_scopeId}><span class="h-3 w-3 rounded-full bg-violet-600"${_scopeId}></span>Mesa grande / grupo</div><div class="flex items-center gap-2"${_scopeId}><span class="h-3 w-3 rounded-full bg-red-500"${_scopeId}></span>Ocupada</div><div class="flex items-center gap-2"${_scopeId}><span class="h-3 w-3 rounded-full bg-amber-500"${_scopeId}></span>Reservada</div><div class="text-slate-500"${_scopeId}>${ssrInterpolate(editarMapa.value ? "Arrasta as mesas no mapa." : "Clica numa mesa para gerir.")}</div></div><div class="grid gap-5 xl:grid-cols-[1fr_340px]"${_scopeId}><section class="rounded-lg border border-slate-300 bg-slate-100 p-3"${_scopeId}><div data-sala-mapa class="relative h-[78vh] min-h-[720px] w-full overflow-hidden rounded-lg bg-[#f7f5ef] shadow-inner ring-1 ring-slate-300"${_scopeId}><div class="absolute inset-x-[2%] inset-y-[4%] rounded-md border-[3px] border-slate-800/70"${_scopeId}></div><div class="absolute left-[2%] top-[38%] h-[18%] w-[2px] bg-[#f7f5ef]"${_scopeId}></div><div class="absolute left-[2%] top-[63%] h-[14%] w-[2px] bg-[#f7f5ef]"${_scopeId}></div><div class="absolute right-[2%] top-[12%] h-[16%] w-[2px] bg-[#f7f5ef]"${_scopeId}></div><div class="absolute right-[2%] bottom-[8%] h-[12%] w-[2px] bg-[#f7f5ef]"${_scopeId}></div><!--[-->`);
            ssrRenderList(zonasMapa.value, (zona) => {
              _push2(`<button type="button" class="${ssrRenderClass([[zonaClasse(zona), zonaSelecionada.value?.id === zona.id ? "ring-4 ring-slate-900/25" : "", editarMapa.value ? "cursor-move hover:bg-white/70" : ""], "absolute flex items-center justify-center rounded-sm border-[3px] p-1 text-center text-xs font-black uppercase transition"])}" style="${ssrRenderStyle(zonaStyle(zona))}"${_scopeId}><span class="${ssrRenderClass(zonaVertical(zona) ? "[writing-mode:vertical-rl]" : "")}"${_scopeId}>${ssrInterpolate(zona.nome)}</span></button>`);
            });
            _push2(`<!--]--><!--[-->`);
            ssrRenderList(mesasMapa.value, (mesa) => {
              _push2(`<button type="button" class="${ssrRenderClass([[mesaSelecionada.value?.id === mesa.id ? "ring-4 ring-slate-900/25" : "", mesaParcial(mesa) ? "ring-2 ring-amber-500" : "", editarMapa.value ? "cursor-move" : "hover:scale-[1.01]"], "absolute overflow-hidden rounded-md border-2 border-slate-900 bg-white text-left shadow-sm transition"])}" style="${ssrRenderStyle(mesaStyle(mesa))}"${_scopeId}><div class="${ssrRenderClass([mesa.mapa_altura > mesa.mapa_largura ? "flex-col" : "flex-row", "absolute inset-0 flex"])}"${_scopeId}><!--[-->`);
              ssrRenderList(segmentosMesa(mesa), (segmento) => {
                _push2(`<div class="${ssrRenderClass([[segmentoClass[segmento.estado] ?? segmentoClass.livre, mesa.mapa_altura > mesa.mapa_largura ? "border-b last:border-b-0" : "border-r last:border-r-0"], "min-h-0 min-w-0 border-white/60"])}" style="${ssrRenderStyle({ flex: segmento.capacidade })}"${_scopeId}></div>`);
              });
              _push2(`<!--]--></div><div class="relative z-10 flex h-full flex-col justify-between p-1 text-center text-white drop-shadow"${_scopeId}><div class="flex items-start justify-between gap-1"${_scopeId}><span class="rounded bg-slate-950/55 px-1 text-[10px] font-black"${_scopeId}>${ssrInterpolate(mesa.numero)}</span><span class="${ssrRenderClass([estadoDot[estadoVisual(mesa)], "h-2.5 w-2.5 rounded-full ring-1 ring-white"])}"${_scopeId}></span></div>`);
              if (mesa.submesas.length) {
                _push2(`<div class="space-y-0.5"${_scopeId}><div class="${ssrRenderClass([mesa.submesas.length > 3 ? "grid-cols-3" : "grid-cols-2", "grid gap-0.5"])}"${_scopeId}><!--[-->`);
                ssrRenderList(segmentosMesa(mesa), (segmento) => {
                  _push2(`<span class="rounded bg-slate-950/45 px-1 py-0.5 text-[9px] font-black"${_scopeId}>${ssrInterpolate(segmento.label)}</span>`);
                });
                _push2(`<!--]--></div>`);
                if (lugaresVazios(mesa) > 0) {
                  _push2(`<span class="inline-block rounded bg-slate-950/55 px-1 py-0.5 text-[9px] font-bold"${_scopeId}>${ssrInterpolate(textoLugaresVaziosCurto(mesa))}</span>`);
                } else {
                  _push2(`<!---->`);
                }
                _push2(`</div>`);
              } else {
                _push2(`<div${_scopeId}><span class="rounded bg-slate-950/45 px-1 py-0.5 text-[9px] font-bold"${_scopeId}>${ssrInterpolate(textoLugaresVaziosCurto(mesa))}</span></div>`);
              }
              _push2(`</div></button>`);
            });
            _push2(`<!--]--></div></section>`);
            if (editarMapa.value) {
              _push2(`<aside class="space-y-4 rounded-lg border border-slate-200 bg-white p-5 shadow-sm"${_scopeId}><form class="space-y-3 rounded-md border border-dashed border-slate-300 bg-white p-4"${_scopeId}><h2 class="text-lg font-black"${_scopeId}>Novo elemento</h2><div${_scopeId}><label class="text-xs font-semibold uppercase text-slate-500"${_scopeId}>Nome</label><input${ssrRenderAttr("value", unref(zonaForm).nome)} type="text" class="mt-1 w-full rounded-md border-slate-300 text-sm" placeholder="Ex.: Porta lateral"${_scopeId}>`);
              if (unref(zonaForm).errors.nome) {
                _push2(`<p class="mt-1 text-xs font-semibold text-red-600"${_scopeId}>${ssrInterpolate(unref(zonaForm).errors.nome)}</p>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`</div><div${_scopeId}><label class="text-xs font-semibold uppercase text-slate-500"${_scopeId}>Tipo</label><select class="mt-1 w-full rounded-md border-slate-300 text-sm"${_scopeId}><!--[-->`);
              ssrRenderList(tiposZona, ([valor, label]) => {
                _push2(`<option${ssrRenderAttr("value", valor)}${ssrIncludeBooleanAttr(Array.isArray(unref(zonaForm).tipo) ? ssrLooseContain(unref(zonaForm).tipo, valor) : ssrLooseEqual(unref(zonaForm).tipo, valor)) ? " selected" : ""}${_scopeId}>${ssrInterpolate(label)}</option>`);
              });
              _push2(`<!--]--></select>`);
              if (unref(zonaForm).errors.tipo) {
                _push2(`<p class="mt-1 text-xs font-semibold text-red-600"${_scopeId}>${ssrInterpolate(unref(zonaForm).errors.tipo)}</p>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`</div><button type="submit" class="w-full rounded-md bg-slate-900 px-3 py-2 text-sm font-semibold text-white disabled:opacity-60"${ssrIncludeBooleanAttr(unref(zonaForm).processing) ? " disabled" : ""}${_scopeId}> Criar elemento </button></form>`);
              if (zonaSelecionada.value) {
                _push2(`<div class="space-y-3 rounded-md bg-slate-50 p-4"${_scopeId}><div${_scopeId}><h2 class="text-xl font-bold"${_scopeId}>${ssrInterpolate(zonaSelecionada.value.nome)}</h2><p class="text-sm text-slate-500"${_scopeId}>Elemento da sala · ${ssrInterpolate(zonaSelecionada.value.tipo)}</p></div><div${_scopeId}><label class="text-xs font-semibold uppercase text-slate-500"${_scopeId}>Nome</label><input${ssrRenderAttr("value", unref(zonaEditForm).nome)} type="text" class="mt-1 w-full rounded-md border-slate-300 text-sm"${_scopeId}>`);
                if (unref(zonaEditForm).errors.nome) {
                  _push2(`<p class="mt-1 text-xs font-semibold text-red-600"${_scopeId}>${ssrInterpolate(unref(zonaEditForm).errors.nome)}</p>`);
                } else {
                  _push2(`<!---->`);
                }
                _push2(`</div><div${_scopeId}><label class="text-xs font-semibold uppercase text-slate-500"${_scopeId}>Tipo</label><select class="mt-1 w-full rounded-md border-slate-300 text-sm"${_scopeId}><!--[-->`);
                ssrRenderList(tiposZona, ([valor, label]) => {
                  _push2(`<option${ssrRenderAttr("value", valor)}${ssrIncludeBooleanAttr(Array.isArray(unref(zonaEditForm).tipo) ? ssrLooseContain(unref(zonaEditForm).tipo, valor) : ssrLooseEqual(unref(zonaEditForm).tipo, valor)) ? " selected" : ""}${_scopeId}>${ssrInterpolate(label)}</option>`);
                });
                _push2(`<!--]--></select>`);
                if (unref(zonaEditForm).errors.tipo) {
                  _push2(`<p class="mt-1 text-xs font-semibold text-red-600"${_scopeId}>${ssrInterpolate(unref(zonaEditForm).errors.tipo)}</p>`);
                } else {
                  _push2(`<!---->`);
                }
                _push2(`</div><div class="grid grid-cols-2 gap-3"${_scopeId}><label${_scopeId}><span class="text-xs font-semibold uppercase text-slate-500"${_scopeId}>X</span><input${ssrRenderAttr("value", zonaSelecionada.value.mapa_x)} type="number" min="0" max="100" class="mt-1 w-full rounded-md border-slate-300 text-sm"${_scopeId}></label><label${_scopeId}><span class="text-xs font-semibold uppercase text-slate-500"${_scopeId}>Y</span><input${ssrRenderAttr("value", zonaSelecionada.value.mapa_y)} type="number" min="0" max="100" class="mt-1 w-full rounded-md border-slate-300 text-sm"${_scopeId}></label></div><div${_scopeId}><label class="text-xs font-semibold uppercase text-slate-500"${_scopeId}>Largura</label><input${ssrRenderAttr("value", zonaSelecionada.value.mapa_largura)} type="number" min="1" max="60" class="mt-1 w-full rounded-md border-slate-300 text-sm"${_scopeId}></div><div${_scopeId}><label class="text-xs font-semibold uppercase text-slate-500"${_scopeId}>Altura</label><input${ssrRenderAttr("value", zonaSelecionada.value.mapa_altura)} type="number" min="1" max="60" class="mt-1 w-full rounded-md border-slate-300 text-sm"${_scopeId}></div><div class="grid grid-cols-2 gap-2"${_scopeId}><button type="button" class="rounded-md bg-slate-900 px-3 py-2 text-sm font-semibold text-white disabled:opacity-60"${ssrIncludeBooleanAttr(unref(zonaEditForm).processing) ? " disabled" : ""}${_scopeId}> Guardar elemento </button><button type="button" class="rounded-md border border-red-300 px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-50"${_scopeId}> Apagar </button></div></div>`);
              } else if (mesaSelecionada.value) {
                _push2(`<div class="space-y-3 rounded-md bg-slate-50 p-4"${_scopeId}><h2 class="text-xl font-bold"${_scopeId}>${ssrInterpolate(mesaSelecionada.value.designacao)}</h2><div${_scopeId}><label class="text-xs font-semibold uppercase text-slate-500"${_scopeId}>Largura</label><input${ssrRenderAttr("value", mesaSelecionada.value.mapa_largura)} type="number" min="4" max="40" class="mt-1 w-full rounded-md border-slate-300 text-sm"${_scopeId}></div><div${_scopeId}><label class="text-xs font-semibold uppercase text-slate-500"${_scopeId}>Altura</label><input${ssrRenderAttr("value", mesaSelecionada.value.mapa_altura)} type="number" min="4" max="40" class="mt-1 w-full rounded-md border-slate-300 text-sm"${_scopeId}></div></div>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`</aside>`);
            } else {
              _push2(`<!---->`);
            }
            if (!editarMapa.value && zonaSelecionada.value) {
              _push2(`<aside class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"${_scopeId}><div class="mb-4"${_scopeId}><h2 class="text-xl font-bold"${_scopeId}>${ssrInterpolate(zonaSelecionada.value.nome)}</h2><p class="text-sm text-slate-500"${_scopeId}>Elemento da sala · ${ssrInterpolate(zonaSelecionada.value.tipo)}</p></div>`);
              if (editarMapa.value) {
                _push2(`<div class="space-y-3 rounded-md bg-slate-50 p-4"${_scopeId}><div${_scopeId}><label class="text-xs font-semibold uppercase text-slate-500"${_scopeId}>Nome</label><input${ssrRenderAttr("value", unref(zonaEditForm).nome)} type="text" class="mt-1 w-full rounded-md border-slate-300 text-sm"${_scopeId}>`);
                if (unref(zonaEditForm).errors.nome) {
                  _push2(`<p class="mt-1 text-xs font-semibold text-red-600"${_scopeId}>${ssrInterpolate(unref(zonaEditForm).errors.nome)}</p>`);
                } else {
                  _push2(`<!---->`);
                }
                _push2(`</div><div${_scopeId}><label class="text-xs font-semibold uppercase text-slate-500"${_scopeId}>Tipo</label><select class="mt-1 w-full rounded-md border-slate-300 text-sm"${_scopeId}><!--[-->`);
                ssrRenderList(tiposZona, ([valor, label]) => {
                  _push2(`<option${ssrRenderAttr("value", valor)}${ssrIncludeBooleanAttr(Array.isArray(unref(zonaEditForm).tipo) ? ssrLooseContain(unref(zonaEditForm).tipo, valor) : ssrLooseEqual(unref(zonaEditForm).tipo, valor)) ? " selected" : ""}${_scopeId}>${ssrInterpolate(label)}</option>`);
                });
                _push2(`<!--]--></select>`);
                if (unref(zonaEditForm).errors.tipo) {
                  _push2(`<p class="mt-1 text-xs font-semibold text-red-600"${_scopeId}>${ssrInterpolate(unref(zonaEditForm).errors.tipo)}</p>`);
                } else {
                  _push2(`<!---->`);
                }
                _push2(`</div><div class="grid grid-cols-2 gap-3"${_scopeId}><label${_scopeId}><span class="text-xs font-semibold uppercase text-slate-500"${_scopeId}>X</span><input${ssrRenderAttr("value", zonaSelecionada.value.mapa_x)} type="number" min="0" max="100" class="mt-1 w-full rounded-md border-slate-300 text-sm"${_scopeId}></label><label${_scopeId}><span class="text-xs font-semibold uppercase text-slate-500"${_scopeId}>Y</span><input${ssrRenderAttr("value", zonaSelecionada.value.mapa_y)} type="number" min="0" max="100" class="mt-1 w-full rounded-md border-slate-300 text-sm"${_scopeId}></label></div><div${_scopeId}><label class="text-xs font-semibold uppercase text-slate-500"${_scopeId}>Largura</label><input${ssrRenderAttr("value", zonaSelecionada.value.mapa_largura)} type="number" min="1" max="60" class="mt-1 w-full rounded-md border-slate-300 text-sm"${_scopeId}></div><div${_scopeId}><label class="text-xs font-semibold uppercase text-slate-500"${_scopeId}>Altura</label><input${ssrRenderAttr("value", zonaSelecionada.value.mapa_altura)} type="number" min="1" max="60" class="mt-1 w-full rounded-md border-slate-300 text-sm"${_scopeId}></div><div class="grid grid-cols-2 gap-2"${_scopeId}><button type="button" class="rounded-md bg-slate-900 px-3 py-2 text-sm font-semibold text-white disabled:opacity-60"${ssrIncludeBooleanAttr(unref(zonaEditForm).processing) ? " disabled" : ""}${_scopeId}> Guardar elemento </button><button type="button" class="rounded-md border border-red-300 px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-50"${_scopeId}> Apagar </button></div></div>`);
              } else {
                _push2(`<div class="rounded-md bg-amber-50 p-3 text-sm font-semibold text-amber-800"${_scopeId}> Ativa “Editar mapa” para mover ou redimensionar este elemento. </div>`);
              }
              if (editarMapa.value) {
                _push2(`<form class="mt-4 space-y-3 rounded-md border border-dashed border-slate-300 bg-white p-4"${_scopeId}><h3 class="text-sm font-black uppercase text-slate-600"${_scopeId}>Novo elemento</h3><div${_scopeId}><label class="text-xs font-semibold uppercase text-slate-500"${_scopeId}>Nome</label><input${ssrRenderAttr("value", unref(zonaForm).nome)} type="text" class="mt-1 w-full rounded-md border-slate-300 text-sm" placeholder="Ex.: Porta lateral"${_scopeId}>`);
                if (unref(zonaForm).errors.nome) {
                  _push2(`<p class="mt-1 text-xs font-semibold text-red-600"${_scopeId}>${ssrInterpolate(unref(zonaForm).errors.nome)}</p>`);
                } else {
                  _push2(`<!---->`);
                }
                _push2(`</div><div${_scopeId}><label class="text-xs font-semibold uppercase text-slate-500"${_scopeId}>Tipo</label><select class="mt-1 w-full rounded-md border-slate-300 text-sm"${_scopeId}><!--[-->`);
                ssrRenderList(tiposZona, ([valor, label]) => {
                  _push2(`<option${ssrRenderAttr("value", valor)}${ssrIncludeBooleanAttr(Array.isArray(unref(zonaForm).tipo) ? ssrLooseContain(unref(zonaForm).tipo, valor) : ssrLooseEqual(unref(zonaForm).tipo, valor)) ? " selected" : ""}${_scopeId}>${ssrInterpolate(label)}</option>`);
                });
                _push2(`<!--]--></select></div><button type="submit" class="w-full rounded-md bg-slate-900 px-3 py-2 text-sm font-semibold text-white disabled:opacity-60"${ssrIncludeBooleanAttr(unref(zonaForm).processing) ? " disabled" : ""}${_scopeId}> Criar elemento </button></form>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`</aside>`);
            } else {
              _push2(`<!---->`);
            }
            if (!editarMapa.value && mesaSelecionada.value) {
              _push2(`<aside class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"${_scopeId}><div class="mb-4 flex items-start justify-between gap-3"${_scopeId}><div${_scopeId}><h2 class="text-xl font-bold"${_scopeId}>${ssrInterpolate(mesaSelecionada.value.designacao)}</h2><p class="text-sm text-slate-500"${_scopeId}>${ssrInterpolate(mesaSelecionada.value.capacidade)} lugares · ${ssrInterpolate(mesaSelecionada.value.submesas.length ? `${mesaSelecionada.value.submesas.length} submesas` : "mesa inteira")}</p><p class="mt-1 text-sm font-bold text-emerald-700"${_scopeId}>${ssrInterpolate(textoLugaresVazios(mesaSelecionada.value))}</p></div><span class="${ssrRenderClass([estadoClass[estadoVisual(mesaSelecionada.value)], "rounded-full border px-3 py-1 text-xs font-bold uppercase"])}"${_scopeId}>${ssrInterpolate(estadoVisual(mesaSelecionada.value))}</span></div>`);
              if (editarMapa.value) {
                _push2(`<div class="mb-5 space-y-3 rounded-md bg-slate-50 p-4"${_scopeId}><div${_scopeId}><label class="text-xs font-semibold uppercase text-slate-500"${_scopeId}>Largura</label><input${ssrRenderAttr("value", mesaSelecionada.value.mapa_largura)} type="number" min="4" max="40" class="mt-1 w-full rounded-md border-slate-300 text-sm"${_scopeId}></div><div${_scopeId}><label class="text-xs font-semibold uppercase text-slate-500"${_scopeId}>Altura</label><input${ssrRenderAttr("value", mesaSelecionada.value.mapa_altura)} type="number" min="4" max="40" class="mt-1 w-full rounded-md border-slate-300 text-sm"${_scopeId}></div><form class="space-y-3 rounded-md border border-dashed border-slate-300 bg-white p-4"${_scopeId}><h3 class="text-sm font-black uppercase text-slate-600"${_scopeId}>Novo elemento da sala</h3><div${_scopeId}><label class="text-xs font-semibold uppercase text-slate-500"${_scopeId}>Nome</label><input${ssrRenderAttr("value", unref(zonaForm).nome)} type="text" class="mt-1 w-full rounded-md border-slate-300 text-sm" placeholder="Ex.: Porta, bar, palco"${_scopeId}>`);
                if (unref(zonaForm).errors.nome) {
                  _push2(`<p class="mt-1 text-xs font-semibold text-red-600"${_scopeId}>${ssrInterpolate(unref(zonaForm).errors.nome)}</p>`);
                } else {
                  _push2(`<!---->`);
                }
                _push2(`</div><div${_scopeId}><label class="text-xs font-semibold uppercase text-slate-500"${_scopeId}>Tipo</label><select class="mt-1 w-full rounded-md border-slate-300 text-sm"${_scopeId}><!--[-->`);
                ssrRenderList(tiposZona, ([valor, label]) => {
                  _push2(`<option${ssrRenderAttr("value", valor)}${ssrIncludeBooleanAttr(Array.isArray(unref(zonaForm).tipo) ? ssrLooseContain(unref(zonaForm).tipo, valor) : ssrLooseEqual(unref(zonaForm).tipo, valor)) ? " selected" : ""}${_scopeId}>${ssrInterpolate(label)}</option>`);
                });
                _push2(`<!--]--></select></div><button type="submit" class="w-full rounded-md bg-slate-900 px-3 py-2 text-sm font-semibold text-white disabled:opacity-60"${ssrIncludeBooleanAttr(unref(zonaForm).processing) ? " disabled" : ""}${_scopeId}> Criar elemento </button></form></div>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`<div class="mb-5 grid gap-2"${_scopeId}>`);
              if (pedidosAtivosDaMesa(mesaSelecionada.value).length) {
                _push2(`<div class="grid gap-2 rounded-md bg-slate-50 p-3"${_scopeId}><div class="text-xs font-semibold uppercase text-slate-500"${_scopeId}>Pedidos ativos</div><!--[-->`);
                ssrRenderList(pedidosAtivosDaMesaDetalhados(mesaSelecionada.value), (item) => {
                  _push2(ssrRenderComponent(unref(Link), {
                    key: item.pedido.id,
                    href: _ctx.route("pedidos.show", item.pedido.id),
                    class: "rounded-md bg-slate-900 px-3 py-2 text-center text-sm font-semibold text-white"
                  }, {
                    default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                      if (_push3) {
                        _push3(` Ver pedido #${ssrInterpolate(item.pedido.id)} · ${ssrInterpolate(item.local)}`);
                      } else {
                        return [
                          createTextVNode(" Ver pedido #" + toDisplayString(item.pedido.id) + " · " + toDisplayString(item.local), 1)
                        ];
                      }
                    }),
                    _: 2
                  }, _parent2, _scopeId));
                });
                _push2(`<!--]--></div>`);
              } else {
                _push2(`<!---->`);
              }
              if (podeAbrirPedido(mesaSelecionada.value) && !mesaSelecionada.value.submesas.length && !mesaSelecionada.value.mesa_principal_id) {
                _push2(`<div class="rounded-md bg-slate-50 p-4"${_scopeId}><label class="text-xs font-semibold uppercase text-slate-500"${_scopeId}>Lugares ocupados</label><input${ssrRenderAttr("value", lugaresOcupados.value)} type="number" min="1" max="80" class="mt-1 w-full rounded-md border-slate-300 text-sm" placeholder="Obrigatorio"${_scopeId}>`);
                if (precisaSubmesaSelecionada.value) {
                  _push2(`<label class="mt-3 block text-xs font-semibold uppercase text-slate-500"${_scopeId}> Letra da submesa <select class="mt-1 w-full rounded-md border-slate-300 text-sm uppercase"${_scopeId}><option value=""${ssrIncludeBooleanAttr(Array.isArray(letraSubmesaNova.value) ? ssrLooseContain(letraSubmesaNova.value, "") : ssrLooseEqual(letraSubmesaNova.value, "")) ? " selected" : ""}${_scopeId}>Escolher letra</option><!--[-->`);
                  ssrRenderList(submesaLetras, (letra) => {
                    _push2(`<option${ssrRenderAttr("value", letra)}${ssrIncludeBooleanAttr(Array.isArray(letraSubmesaNova.value) ? ssrLooseContain(letraSubmesaNova.value, letra) : ssrLooseEqual(letraSubmesaNova.value, letra)) ? " selected" : ""}${_scopeId}>${ssrInterpolate(letra)}</option>`);
                  });
                  _push2(`<!--]--></select></label>`);
                } else {
                  _push2(`<!---->`);
                }
                if (precisaMesasGrupoSelecionada.value) {
                  _push2(`<label class="mt-3 block text-xs font-semibold uppercase text-slate-500"${_scopeId}> Mesas do grupo <input${ssrRenderAttr("value", mesasGrupo.value)} type="text" class="mt-1 w-full rounded-md border-slate-300 text-sm" placeholder="Ex.: 32 33 34"${_scopeId}></label>`);
                } else {
                  _push2(`<!---->`);
                }
                _push2(`<p class="mt-2 text-xs text-slate-500"${_scopeId}>Ex.: 5 divide a mesa. Acima da capacidade, indica as mesas do grupo.</p></div>`);
              } else {
                _push2(`<!---->`);
              }
              if (podeAbrirPedidoMesaCompleta(mesaSelecionada.value)) {
                _push2(`<button type="button" class="rounded-md bg-slate-900 px-3 py-2 text-sm font-semibold text-white disabled:opacity-50"${ssrIncludeBooleanAttr(!podeAbrirPedidoSelecionado.value) ? " disabled" : ""}${_scopeId}>${ssrInterpolate(mesaDivididaLivre(mesaSelecionada.value) ? "Abrir pedido mesa completa" : "Abrir pedido")}</button>`);
              } else if (mesaSelecionada.value.submesas.length) {
                _push2(`<div class="rounded-md bg-amber-50 p-3 text-sm font-semibold text-amber-800"${_scopeId}> Esta mesa está dividida. Abre o pedido numa das submesas abaixo. </div>`);
              } else {
                _push2(`<!---->`);
              }
              if (editarMapa.value && mesaLivre(mesaSelecionada.value) && mesaSelecionada.value.submesas.length) {
                _push2(`<button type="button" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold"${_scopeId}>Juntar mesa</button>`);
              } else {
                _push2(`<!---->`);
              }
              if (podeMarcarLivre(mesaSelecionada.value)) {
                _push2(`<button type="button" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold"${_scopeId}>Marcar livre</button>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(ssrRenderComponent(unref(Link), {
                href: _ctx.route("mesas.edit", mesaSelecionada.value.id),
                class: "rounded-md border border-slate-300 px-3 py-2 text-center text-sm font-semibold"
              }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(`Editar mesa`);
                  } else {
                    return [
                      createTextVNode("Editar mesa")
                    ];
                  }
                }),
                _: 1
              }, _parent2, _scopeId));
              if (!pedidosAtivosDaMesa(mesaSelecionada.value).length) {
                _push2(`<button type="button" class="rounded-md border border-red-300 px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-50"${_scopeId}> Remover mesa </button>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`</div>`);
              if (mesaSelecionada.value.submesas.length) {
                _push2(`<div class="space-y-2"${_scopeId}><div class="text-xs font-semibold uppercase text-slate-500"${_scopeId}>Submesas</div><!--[-->`);
                ssrRenderList(mesaSelecionada.value.submesas, (submesa) => {
                  _push2(`<div class="${ssrRenderClass([estadoClass[estadoSubmesa(submesa)], "rounded-md border p-3"])}"${_scopeId}><div class="flex items-center justify-between gap-3"${_scopeId}><div${_scopeId}><div class="font-black"${_scopeId}>${ssrInterpolate(letraSubmesa(submesa))}</div><div class="text-xs"${_scopeId}>${ssrInterpolate(submesa.capacidade)} pessoas · lugares ${ssrInterpolate(submesa.lugares)}</div></div>`);
                  if (!mesaLivre(mesaSelecionada.value) && podeAbrirPedido(submesa)) {
                    _push2(`<button type="button" class="rounded-md bg-slate-900 px-3 py-2 text-xs font-semibold text-white disabled:opacity-50"${ssrIncludeBooleanAttr(submesa.capacidade > 1 && !lugaresSubmesa.value[submesa.id]) ? " disabled" : ""}${_scopeId}> Abrir </button>`);
                  } else {
                    _push2(`<!---->`);
                  }
                  _push2(`</div>`);
                  if (!mesaLivre(mesaSelecionada.value) && podeAbrirPedido(submesa) && submesa.capacidade > 1) {
                    _push2(`<label class="mt-3 block text-xs font-semibold uppercase text-slate-600"${_scopeId}> Lugares ocupados <input${ssrRenderAttr("value", lugaresSubmesa.value[submesa.id])} type="number" min="1"${ssrRenderAttr("max", submesa.capacidade - 1)} class="mt-1 w-full rounded-md border-slate-300 text-sm" placeholder="Vazio = submesa completa"${_scopeId}></label>`);
                  } else {
                    _push2(`<!---->`);
                  }
                  if (pedidosAtivos(submesa).length) {
                    _push2(`<div class="mt-3 grid gap-2"${_scopeId}><!--[-->`);
                    ssrRenderList(pedidosAtivos(submesa), (pedido) => {
                      _push2(ssrRenderComponent(unref(Link), {
                        key: pedido.id,
                        href: _ctx.route("pedidos.show", pedido.id),
                        class: "rounded-md bg-slate-900 px-3 py-2 text-center text-xs font-semibold text-white"
                      }, {
                        default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                          if (_push3) {
                            _push3(` Ver pedido #${ssrInterpolate(pedido.id)}`);
                          } else {
                            return [
                              createTextVNode(" Ver pedido #" + toDisplayString(pedido.id), 1)
                            ];
                          }
                        }),
                        _: 2
                      }, _parent2, _scopeId));
                    });
                    _push2(`<!--]--></div>`);
                  } else {
                    _push2(`<!---->`);
                  }
                  _push2(`</div>`);
                });
                _push2(`<!--]--></div>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`</aside>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div>`);
          } else {
            return [
              createVNode("div", { class: "mb-5 flex flex-wrap items-center justify-between gap-3" }, [
                createVNode("div", null, [
                  createVNode("h1", { class: "text-2xl font-bold" }, "Mapa da sala"),
                  createVNode("p", { class: "mt-1 text-sm text-slate-500" }, "Salão com 41 mesas. Distribui as mesas no mapa e divide cada mesa em submesas quando precisares.")
                ]),
                createVNode("div", { class: "flex flex-wrap gap-2" }, [
                  createVNode("button", {
                    type: "button",
                    class: "rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold",
                    onClick: ($event) => editarMapa.value = !editarMapa.value
                  }, toDisplayString(editarMapa.value ? "Sair da edição" : "Editar mapa"), 9, ["onClick"]),
                  editarMapa.value ? (openBlock(), createBlock("button", {
                    key: 0,
                    type: "button",
                    class: "rounded-md bg-slate-900 px-3 py-2 text-sm font-semibold text-white",
                    onClick: guardarMapa
                  }, "Guardar mapa")) : createCommentVNode("", true),
                  createVNode(unref(Link), {
                    href: _ctx.route("mesas.create"),
                    class: "rounded-md bg-slate-900 px-3 py-2 text-sm font-semibold text-white"
                  }, {
                    default: withCtx(() => [
                      createTextVNode("Nova mesa")
                    ]),
                    _: 1
                  }, 8, ["href"])
                ])
              ]),
              createVNode("div", { class: "mb-4 grid gap-3 rounded-lg bg-white p-4 text-sm shadow-sm md:grid-cols-4" }, [
                createVNode("div", { class: "flex items-center gap-2" }, [
                  createVNode("span", { class: "h-3 w-3 rounded-full bg-emerald-500" }),
                  createTextVNode("Livre")
                ]),
                createVNode("div", { class: "flex items-center gap-2" }, [
                  createVNode("span", { class: "h-3 w-3 rounded-full bg-violet-600" }),
                  createTextVNode("Mesa grande / grupo")
                ]),
                createVNode("div", { class: "flex items-center gap-2" }, [
                  createVNode("span", { class: "h-3 w-3 rounded-full bg-red-500" }),
                  createTextVNode("Ocupada")
                ]),
                createVNode("div", { class: "flex items-center gap-2" }, [
                  createVNode("span", { class: "h-3 w-3 rounded-full bg-amber-500" }),
                  createTextVNode("Reservada")
                ]),
                createVNode("div", { class: "text-slate-500" }, toDisplayString(editarMapa.value ? "Arrasta as mesas no mapa." : "Clica numa mesa para gerir."), 1)
              ]),
              createVNode("div", { class: "grid gap-5 xl:grid-cols-[1fr_340px]" }, [
                createVNode("section", { class: "rounded-lg border border-slate-300 bg-slate-100 p-3" }, [
                  createVNode("div", {
                    "data-sala-mapa": "",
                    class: "relative h-[78vh] min-h-[720px] w-full overflow-hidden rounded-lg bg-[#f7f5ef] shadow-inner ring-1 ring-slate-300"
                  }, [
                    createVNode("div", { class: "absolute inset-x-[2%] inset-y-[4%] rounded-md border-[3px] border-slate-800/70" }),
                    createVNode("div", { class: "absolute left-[2%] top-[38%] h-[18%] w-[2px] bg-[#f7f5ef]" }),
                    createVNode("div", { class: "absolute left-[2%] top-[63%] h-[14%] w-[2px] bg-[#f7f5ef]" }),
                    createVNode("div", { class: "absolute right-[2%] top-[12%] h-[16%] w-[2px] bg-[#f7f5ef]" }),
                    createVNode("div", { class: "absolute right-[2%] bottom-[8%] h-[12%] w-[2px] bg-[#f7f5ef]" }),
                    (openBlock(true), createBlock(Fragment, null, renderList(zonasMapa.value, (zona) => {
                      return openBlock(), createBlock("button", {
                        key: `zona-${zona.id}`,
                        type: "button",
                        class: ["absolute flex items-center justify-center rounded-sm border-[3px] p-1 text-center text-xs font-black uppercase transition", [zonaClasse(zona), zonaSelecionada.value?.id === zona.id ? "ring-4 ring-slate-900/25" : "", editarMapa.value ? "cursor-move hover:bg-white/70" : ""]],
                        style: zonaStyle(zona),
                        onMousedown: ($event) => iniciarDragZona($event, zona),
                        onClick: ($event) => selecionarZona(zona)
                      }, [
                        createVNode("span", {
                          class: zonaVertical(zona) ? "[writing-mode:vertical-rl]" : ""
                        }, toDisplayString(zona.nome), 3)
                      ], 46, ["onMousedown", "onClick"]);
                    }), 128)),
                    (openBlock(true), createBlock(Fragment, null, renderList(mesasMapa.value, (mesa) => {
                      return openBlock(), createBlock("button", {
                        key: mesa.id,
                        type: "button",
                        class: ["absolute overflow-hidden rounded-md border-2 border-slate-900 bg-white text-left shadow-sm transition", [mesaSelecionada.value?.id === mesa.id ? "ring-4 ring-slate-900/25" : "", mesaParcial(mesa) ? "ring-2 ring-amber-500" : "", editarMapa.value ? "cursor-move" : "hover:scale-[1.01]"]],
                        style: mesaStyle(mesa),
                        onMousedown: ($event) => iniciarDrag($event, mesa),
                        onClick: ($event) => selecionarMesa(mesa)
                      }, [
                        createVNode("div", {
                          class: ["absolute inset-0 flex", mesa.mapa_altura > mesa.mapa_largura ? "flex-col" : "flex-row"]
                        }, [
                          (openBlock(true), createBlock(Fragment, null, renderList(segmentosMesa(mesa), (segmento) => {
                            return openBlock(), createBlock("div", {
                              key: segmento.id,
                              class: ["min-h-0 min-w-0 border-white/60", [segmentoClass[segmento.estado] ?? segmentoClass.livre, mesa.mapa_altura > mesa.mapa_largura ? "border-b last:border-b-0" : "border-r last:border-r-0"]],
                              style: { flex: segmento.capacidade }
                            }, null, 6);
                          }), 128))
                        ], 2),
                        createVNode("div", { class: "relative z-10 flex h-full flex-col justify-between p-1 text-center text-white drop-shadow" }, [
                          createVNode("div", { class: "flex items-start justify-between gap-1" }, [
                            createVNode("span", { class: "rounded bg-slate-950/55 px-1 text-[10px] font-black" }, toDisplayString(mesa.numero), 1),
                            createVNode("span", {
                              class: ["h-2.5 w-2.5 rounded-full ring-1 ring-white", estadoDot[estadoVisual(mesa)]]
                            }, null, 2)
                          ]),
                          mesa.submesas.length ? (openBlock(), createBlock("div", {
                            key: 0,
                            class: "space-y-0.5"
                          }, [
                            createVNode("div", {
                              class: ["grid gap-0.5", mesa.submesas.length > 3 ? "grid-cols-3" : "grid-cols-2"]
                            }, [
                              (openBlock(true), createBlock(Fragment, null, renderList(segmentosMesa(mesa), (segmento) => {
                                return openBlock(), createBlock("span", {
                                  key: segmento.id,
                                  class: "rounded bg-slate-950/45 px-1 py-0.5 text-[9px] font-black"
                                }, toDisplayString(segmento.label), 1);
                              }), 128))
                            ], 2),
                            lugaresVazios(mesa) > 0 ? (openBlock(), createBlock("span", {
                              key: 0,
                              class: "inline-block rounded bg-slate-950/55 px-1 py-0.5 text-[9px] font-bold"
                            }, toDisplayString(textoLugaresVaziosCurto(mesa)), 1)) : createCommentVNode("", true)
                          ])) : (openBlock(), createBlock("div", { key: 1 }, [
                            createVNode("span", { class: "rounded bg-slate-950/45 px-1 py-0.5 text-[9px] font-bold" }, toDisplayString(textoLugaresVaziosCurto(mesa)), 1)
                          ]))
                        ])
                      ], 46, ["onMousedown", "onClick"]);
                    }), 128))
                  ])
                ]),
                editarMapa.value ? (openBlock(), createBlock("aside", {
                  key: 0,
                  class: "space-y-4 rounded-lg border border-slate-200 bg-white p-5 shadow-sm"
                }, [
                  createVNode("form", {
                    class: "space-y-3 rounded-md border border-dashed border-slate-300 bg-white p-4",
                    onSubmit: withModifiers(criarZona, ["prevent"])
                  }, [
                    createVNode("h2", { class: "text-lg font-black" }, "Novo elemento"),
                    createVNode("div", null, [
                      createVNode("label", { class: "text-xs font-semibold uppercase text-slate-500" }, "Nome"),
                      withDirectives(createVNode("input", {
                        "onUpdate:modelValue": ($event) => unref(zonaForm).nome = $event,
                        type: "text",
                        class: "mt-1 w-full rounded-md border-slate-300 text-sm",
                        placeholder: "Ex.: Porta lateral"
                      }, null, 8, ["onUpdate:modelValue"]), [
                        [vModelText, unref(zonaForm).nome]
                      ]),
                      unref(zonaForm).errors.nome ? (openBlock(), createBlock("p", {
                        key: 0,
                        class: "mt-1 text-xs font-semibold text-red-600"
                      }, toDisplayString(unref(zonaForm).errors.nome), 1)) : createCommentVNode("", true)
                    ]),
                    createVNode("div", null, [
                      createVNode("label", { class: "text-xs font-semibold uppercase text-slate-500" }, "Tipo"),
                      withDirectives(createVNode("select", {
                        "onUpdate:modelValue": ($event) => unref(zonaForm).tipo = $event,
                        class: "mt-1 w-full rounded-md border-slate-300 text-sm"
                      }, [
                        (openBlock(), createBlock(Fragment, null, renderList(tiposZona, ([valor, label]) => {
                          return createVNode("option", {
                            key: valor,
                            value: valor
                          }, toDisplayString(label), 9, ["value"]);
                        }), 64))
                      ], 8, ["onUpdate:modelValue"]), [
                        [vModelSelect, unref(zonaForm).tipo]
                      ]),
                      unref(zonaForm).errors.tipo ? (openBlock(), createBlock("p", {
                        key: 0,
                        class: "mt-1 text-xs font-semibold text-red-600"
                      }, toDisplayString(unref(zonaForm).errors.tipo), 1)) : createCommentVNode("", true)
                    ]),
                    createVNode("button", {
                      type: "submit",
                      class: "w-full rounded-md bg-slate-900 px-3 py-2 text-sm font-semibold text-white disabled:opacity-60",
                      disabled: unref(zonaForm).processing
                    }, " Criar elemento ", 8, ["disabled"])
                  ], 32),
                  zonaSelecionada.value ? (openBlock(), createBlock("div", {
                    key: 0,
                    class: "space-y-3 rounded-md bg-slate-50 p-4"
                  }, [
                    createVNode("div", null, [
                      createVNode("h2", { class: "text-xl font-bold" }, toDisplayString(zonaSelecionada.value.nome), 1),
                      createVNode("p", { class: "text-sm text-slate-500" }, "Elemento da sala · " + toDisplayString(zonaSelecionada.value.tipo), 1)
                    ]),
                    createVNode("div", null, [
                      createVNode("label", { class: "text-xs font-semibold uppercase text-slate-500" }, "Nome"),
                      withDirectives(createVNode("input", {
                        "onUpdate:modelValue": ($event) => unref(zonaEditForm).nome = $event,
                        type: "text",
                        class: "mt-1 w-full rounded-md border-slate-300 text-sm"
                      }, null, 8, ["onUpdate:modelValue"]), [
                        [vModelText, unref(zonaEditForm).nome]
                      ]),
                      unref(zonaEditForm).errors.nome ? (openBlock(), createBlock("p", {
                        key: 0,
                        class: "mt-1 text-xs font-semibold text-red-600"
                      }, toDisplayString(unref(zonaEditForm).errors.nome), 1)) : createCommentVNode("", true)
                    ]),
                    createVNode("div", null, [
                      createVNode("label", { class: "text-xs font-semibold uppercase text-slate-500" }, "Tipo"),
                      withDirectives(createVNode("select", {
                        "onUpdate:modelValue": ($event) => unref(zonaEditForm).tipo = $event,
                        class: "mt-1 w-full rounded-md border-slate-300 text-sm"
                      }, [
                        (openBlock(), createBlock(Fragment, null, renderList(tiposZona, ([valor, label]) => {
                          return createVNode("option", {
                            key: valor,
                            value: valor
                          }, toDisplayString(label), 9, ["value"]);
                        }), 64))
                      ], 8, ["onUpdate:modelValue"]), [
                        [vModelSelect, unref(zonaEditForm).tipo]
                      ]),
                      unref(zonaEditForm).errors.tipo ? (openBlock(), createBlock("p", {
                        key: 0,
                        class: "mt-1 text-xs font-semibold text-red-600"
                      }, toDisplayString(unref(zonaEditForm).errors.tipo), 1)) : createCommentVNode("", true)
                    ]),
                    createVNode("div", { class: "grid grid-cols-2 gap-3" }, [
                      createVNode("label", null, [
                        createVNode("span", { class: "text-xs font-semibold uppercase text-slate-500" }, "X"),
                        createVNode("input", {
                          value: zonaSelecionada.value.mapa_x,
                          type: "number",
                          min: "0",
                          max: "100",
                          class: "mt-1 w-full rounded-md border-slate-300 text-sm",
                          onInput: ($event) => zonaSelecionada.value.mapa_x = limitar(Number($event.target.value), 0, 100 - zonaSelecionada.value.mapa_largura)
                        }, null, 40, ["value", "onInput"])
                      ]),
                      createVNode("label", null, [
                        createVNode("span", { class: "text-xs font-semibold uppercase text-slate-500" }, "Y"),
                        createVNode("input", {
                          value: zonaSelecionada.value.mapa_y,
                          type: "number",
                          min: "0",
                          max: "100",
                          class: "mt-1 w-full rounded-md border-slate-300 text-sm",
                          onInput: ($event) => zonaSelecionada.value.mapa_y = limitar(Number($event.target.value), 0, 100 - zonaSelecionada.value.mapa_altura)
                        }, null, 40, ["value", "onInput"])
                      ])
                    ]),
                    createVNode("div", null, [
                      createVNode("label", { class: "text-xs font-semibold uppercase text-slate-500" }, "Largura"),
                      createVNode("input", {
                        value: zonaSelecionada.value.mapa_largura,
                        type: "number",
                        min: "1",
                        max: "60",
                        class: "mt-1 w-full rounded-md border-slate-300 text-sm",
                        onInput: ($event) => alterarTamanho(zonaSelecionada.value, "mapa_largura", $event.target.value, 1, 60)
                      }, null, 40, ["value", "onInput"])
                    ]),
                    createVNode("div", null, [
                      createVNode("label", { class: "text-xs font-semibold uppercase text-slate-500" }, "Altura"),
                      createVNode("input", {
                        value: zonaSelecionada.value.mapa_altura,
                        type: "number",
                        min: "1",
                        max: "60",
                        class: "mt-1 w-full rounded-md border-slate-300 text-sm",
                        onInput: ($event) => alterarTamanho(zonaSelecionada.value, "mapa_altura", $event.target.value, 1, 60)
                      }, null, 40, ["value", "onInput"])
                    ]),
                    createVNode("div", { class: "grid grid-cols-2 gap-2" }, [
                      createVNode("button", {
                        type: "button",
                        class: "rounded-md bg-slate-900 px-3 py-2 text-sm font-semibold text-white disabled:opacity-60",
                        disabled: unref(zonaEditForm).processing,
                        onClick: atualizarZona
                      }, " Guardar elemento ", 8, ["disabled"]),
                      createVNode("button", {
                        type: "button",
                        class: "rounded-md border border-red-300 px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-50",
                        onClick: apagarZona
                      }, " Apagar ")
                    ])
                  ])) : mesaSelecionada.value ? (openBlock(), createBlock("div", {
                    key: 1,
                    class: "space-y-3 rounded-md bg-slate-50 p-4"
                  }, [
                    createVNode("h2", { class: "text-xl font-bold" }, toDisplayString(mesaSelecionada.value.designacao), 1),
                    createVNode("div", null, [
                      createVNode("label", { class: "text-xs font-semibold uppercase text-slate-500" }, "Largura"),
                      createVNode("input", {
                        value: mesaSelecionada.value.mapa_largura,
                        type: "number",
                        min: "4",
                        max: "40",
                        class: "mt-1 w-full rounded-md border-slate-300 text-sm",
                        onInput: ($event) => alterarTamanho(mesaSelecionada.value, "mapa_largura", $event.target.value)
                      }, null, 40, ["value", "onInput"])
                    ]),
                    createVNode("div", null, [
                      createVNode("label", { class: "text-xs font-semibold uppercase text-slate-500" }, "Altura"),
                      createVNode("input", {
                        value: mesaSelecionada.value.mapa_altura,
                        type: "number",
                        min: "4",
                        max: "40",
                        class: "mt-1 w-full rounded-md border-slate-300 text-sm",
                        onInput: ($event) => alterarTamanho(mesaSelecionada.value, "mapa_altura", $event.target.value)
                      }, null, 40, ["value", "onInput"])
                    ])
                  ])) : createCommentVNode("", true)
                ])) : createCommentVNode("", true),
                !editarMapa.value && zonaSelecionada.value ? (openBlock(), createBlock("aside", {
                  key: 1,
                  class: "rounded-lg border border-slate-200 bg-white p-5 shadow-sm"
                }, [
                  createVNode("div", { class: "mb-4" }, [
                    createVNode("h2", { class: "text-xl font-bold" }, toDisplayString(zonaSelecionada.value.nome), 1),
                    createVNode("p", { class: "text-sm text-slate-500" }, "Elemento da sala · " + toDisplayString(zonaSelecionada.value.tipo), 1)
                  ]),
                  editarMapa.value ? (openBlock(), createBlock("div", {
                    key: 0,
                    class: "space-y-3 rounded-md bg-slate-50 p-4"
                  }, [
                    createVNode("div", null, [
                      createVNode("label", { class: "text-xs font-semibold uppercase text-slate-500" }, "Nome"),
                      withDirectives(createVNode("input", {
                        "onUpdate:modelValue": ($event) => unref(zonaEditForm).nome = $event,
                        type: "text",
                        class: "mt-1 w-full rounded-md border-slate-300 text-sm"
                      }, null, 8, ["onUpdate:modelValue"]), [
                        [vModelText, unref(zonaEditForm).nome]
                      ]),
                      unref(zonaEditForm).errors.nome ? (openBlock(), createBlock("p", {
                        key: 0,
                        class: "mt-1 text-xs font-semibold text-red-600"
                      }, toDisplayString(unref(zonaEditForm).errors.nome), 1)) : createCommentVNode("", true)
                    ]),
                    createVNode("div", null, [
                      createVNode("label", { class: "text-xs font-semibold uppercase text-slate-500" }, "Tipo"),
                      withDirectives(createVNode("select", {
                        "onUpdate:modelValue": ($event) => unref(zonaEditForm).tipo = $event,
                        class: "mt-1 w-full rounded-md border-slate-300 text-sm"
                      }, [
                        (openBlock(), createBlock(Fragment, null, renderList(tiposZona, ([valor, label]) => {
                          return createVNode("option", {
                            key: valor,
                            value: valor
                          }, toDisplayString(label), 9, ["value"]);
                        }), 64))
                      ], 8, ["onUpdate:modelValue"]), [
                        [vModelSelect, unref(zonaEditForm).tipo]
                      ]),
                      unref(zonaEditForm).errors.tipo ? (openBlock(), createBlock("p", {
                        key: 0,
                        class: "mt-1 text-xs font-semibold text-red-600"
                      }, toDisplayString(unref(zonaEditForm).errors.tipo), 1)) : createCommentVNode("", true)
                    ]),
                    createVNode("div", { class: "grid grid-cols-2 gap-3" }, [
                      createVNode("label", null, [
                        createVNode("span", { class: "text-xs font-semibold uppercase text-slate-500" }, "X"),
                        createVNode("input", {
                          value: zonaSelecionada.value.mapa_x,
                          type: "number",
                          min: "0",
                          max: "100",
                          class: "mt-1 w-full rounded-md border-slate-300 text-sm",
                          onInput: ($event) => zonaSelecionada.value.mapa_x = limitar(Number($event.target.value), 0, 100 - zonaSelecionada.value.mapa_largura)
                        }, null, 40, ["value", "onInput"])
                      ]),
                      createVNode("label", null, [
                        createVNode("span", { class: "text-xs font-semibold uppercase text-slate-500" }, "Y"),
                        createVNode("input", {
                          value: zonaSelecionada.value.mapa_y,
                          type: "number",
                          min: "0",
                          max: "100",
                          class: "mt-1 w-full rounded-md border-slate-300 text-sm",
                          onInput: ($event) => zonaSelecionada.value.mapa_y = limitar(Number($event.target.value), 0, 100 - zonaSelecionada.value.mapa_altura)
                        }, null, 40, ["value", "onInput"])
                      ])
                    ]),
                    createVNode("div", null, [
                      createVNode("label", { class: "text-xs font-semibold uppercase text-slate-500" }, "Largura"),
                      createVNode("input", {
                        value: zonaSelecionada.value.mapa_largura,
                        type: "number",
                        min: "1",
                        max: "60",
                        class: "mt-1 w-full rounded-md border-slate-300 text-sm",
                        onInput: ($event) => alterarTamanho(zonaSelecionada.value, "mapa_largura", $event.target.value, 1, 60)
                      }, null, 40, ["value", "onInput"])
                    ]),
                    createVNode("div", null, [
                      createVNode("label", { class: "text-xs font-semibold uppercase text-slate-500" }, "Altura"),
                      createVNode("input", {
                        value: zonaSelecionada.value.mapa_altura,
                        type: "number",
                        min: "1",
                        max: "60",
                        class: "mt-1 w-full rounded-md border-slate-300 text-sm",
                        onInput: ($event) => alterarTamanho(zonaSelecionada.value, "mapa_altura", $event.target.value, 1, 60)
                      }, null, 40, ["value", "onInput"])
                    ]),
                    createVNode("div", { class: "grid grid-cols-2 gap-2" }, [
                      createVNode("button", {
                        type: "button",
                        class: "rounded-md bg-slate-900 px-3 py-2 text-sm font-semibold text-white disabled:opacity-60",
                        disabled: unref(zonaEditForm).processing,
                        onClick: atualizarZona
                      }, " Guardar elemento ", 8, ["disabled"]),
                      createVNode("button", {
                        type: "button",
                        class: "rounded-md border border-red-300 px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-50",
                        onClick: apagarZona
                      }, " Apagar ")
                    ])
                  ])) : (openBlock(), createBlock("div", {
                    key: 1,
                    class: "rounded-md bg-amber-50 p-3 text-sm font-semibold text-amber-800"
                  }, " Ativa “Editar mapa” para mover ou redimensionar este elemento. ")),
                  editarMapa.value ? (openBlock(), createBlock("form", {
                    key: 2,
                    class: "mt-4 space-y-3 rounded-md border border-dashed border-slate-300 bg-white p-4",
                    onSubmit: withModifiers(criarZona, ["prevent"])
                  }, [
                    createVNode("h3", { class: "text-sm font-black uppercase text-slate-600" }, "Novo elemento"),
                    createVNode("div", null, [
                      createVNode("label", { class: "text-xs font-semibold uppercase text-slate-500" }, "Nome"),
                      withDirectives(createVNode("input", {
                        "onUpdate:modelValue": ($event) => unref(zonaForm).nome = $event,
                        type: "text",
                        class: "mt-1 w-full rounded-md border-slate-300 text-sm",
                        placeholder: "Ex.: Porta lateral"
                      }, null, 8, ["onUpdate:modelValue"]), [
                        [vModelText, unref(zonaForm).nome]
                      ]),
                      unref(zonaForm).errors.nome ? (openBlock(), createBlock("p", {
                        key: 0,
                        class: "mt-1 text-xs font-semibold text-red-600"
                      }, toDisplayString(unref(zonaForm).errors.nome), 1)) : createCommentVNode("", true)
                    ]),
                    createVNode("div", null, [
                      createVNode("label", { class: "text-xs font-semibold uppercase text-slate-500" }, "Tipo"),
                      withDirectives(createVNode("select", {
                        "onUpdate:modelValue": ($event) => unref(zonaForm).tipo = $event,
                        class: "mt-1 w-full rounded-md border-slate-300 text-sm"
                      }, [
                        (openBlock(), createBlock(Fragment, null, renderList(tiposZona, ([valor, label]) => {
                          return createVNode("option", {
                            key: valor,
                            value: valor
                          }, toDisplayString(label), 9, ["value"]);
                        }), 64))
                      ], 8, ["onUpdate:modelValue"]), [
                        [vModelSelect, unref(zonaForm).tipo]
                      ])
                    ]),
                    createVNode("button", {
                      type: "submit",
                      class: "w-full rounded-md bg-slate-900 px-3 py-2 text-sm font-semibold text-white disabled:opacity-60",
                      disabled: unref(zonaForm).processing
                    }, " Criar elemento ", 8, ["disabled"])
                  ], 32)) : createCommentVNode("", true)
                ])) : createCommentVNode("", true),
                !editarMapa.value && mesaSelecionada.value ? (openBlock(), createBlock("aside", {
                  key: 2,
                  class: "rounded-lg border border-slate-200 bg-white p-5 shadow-sm"
                }, [
                  createVNode("div", { class: "mb-4 flex items-start justify-between gap-3" }, [
                    createVNode("div", null, [
                      createVNode("h2", { class: "text-xl font-bold" }, toDisplayString(mesaSelecionada.value.designacao), 1),
                      createVNode("p", { class: "text-sm text-slate-500" }, toDisplayString(mesaSelecionada.value.capacidade) + " lugares · " + toDisplayString(mesaSelecionada.value.submesas.length ? `${mesaSelecionada.value.submesas.length} submesas` : "mesa inteira"), 1),
                      createVNode("p", { class: "mt-1 text-sm font-bold text-emerald-700" }, toDisplayString(textoLugaresVazios(mesaSelecionada.value)), 1)
                    ]),
                    createVNode("span", {
                      class: ["rounded-full border px-3 py-1 text-xs font-bold uppercase", estadoClass[estadoVisual(mesaSelecionada.value)]]
                    }, toDisplayString(estadoVisual(mesaSelecionada.value)), 3)
                  ]),
                  editarMapa.value ? (openBlock(), createBlock("div", {
                    key: 0,
                    class: "mb-5 space-y-3 rounded-md bg-slate-50 p-4"
                  }, [
                    createVNode("div", null, [
                      createVNode("label", { class: "text-xs font-semibold uppercase text-slate-500" }, "Largura"),
                      createVNode("input", {
                        value: mesaSelecionada.value.mapa_largura,
                        type: "number",
                        min: "4",
                        max: "40",
                        class: "mt-1 w-full rounded-md border-slate-300 text-sm",
                        onInput: ($event) => alterarTamanho(mesaSelecionada.value, "mapa_largura", $event.target.value)
                      }, null, 40, ["value", "onInput"])
                    ]),
                    createVNode("div", null, [
                      createVNode("label", { class: "text-xs font-semibold uppercase text-slate-500" }, "Altura"),
                      createVNode("input", {
                        value: mesaSelecionada.value.mapa_altura,
                        type: "number",
                        min: "4",
                        max: "40",
                        class: "mt-1 w-full rounded-md border-slate-300 text-sm",
                        onInput: ($event) => alterarTamanho(mesaSelecionada.value, "mapa_altura", $event.target.value)
                      }, null, 40, ["value", "onInput"])
                    ]),
                    createVNode("form", {
                      class: "space-y-3 rounded-md border border-dashed border-slate-300 bg-white p-4",
                      onSubmit: withModifiers(criarZona, ["prevent"])
                    }, [
                      createVNode("h3", { class: "text-sm font-black uppercase text-slate-600" }, "Novo elemento da sala"),
                      createVNode("div", null, [
                        createVNode("label", { class: "text-xs font-semibold uppercase text-slate-500" }, "Nome"),
                        withDirectives(createVNode("input", {
                          "onUpdate:modelValue": ($event) => unref(zonaForm).nome = $event,
                          type: "text",
                          class: "mt-1 w-full rounded-md border-slate-300 text-sm",
                          placeholder: "Ex.: Porta, bar, palco"
                        }, null, 8, ["onUpdate:modelValue"]), [
                          [vModelText, unref(zonaForm).nome]
                        ]),
                        unref(zonaForm).errors.nome ? (openBlock(), createBlock("p", {
                          key: 0,
                          class: "mt-1 text-xs font-semibold text-red-600"
                        }, toDisplayString(unref(zonaForm).errors.nome), 1)) : createCommentVNode("", true)
                      ]),
                      createVNode("div", null, [
                        createVNode("label", { class: "text-xs font-semibold uppercase text-slate-500" }, "Tipo"),
                        withDirectives(createVNode("select", {
                          "onUpdate:modelValue": ($event) => unref(zonaForm).tipo = $event,
                          class: "mt-1 w-full rounded-md border-slate-300 text-sm"
                        }, [
                          (openBlock(), createBlock(Fragment, null, renderList(tiposZona, ([valor, label]) => {
                            return createVNode("option", {
                              key: valor,
                              value: valor
                            }, toDisplayString(label), 9, ["value"]);
                          }), 64))
                        ], 8, ["onUpdate:modelValue"]), [
                          [vModelSelect, unref(zonaForm).tipo]
                        ])
                      ]),
                      createVNode("button", {
                        type: "submit",
                        class: "w-full rounded-md bg-slate-900 px-3 py-2 text-sm font-semibold text-white disabled:opacity-60",
                        disabled: unref(zonaForm).processing
                      }, " Criar elemento ", 8, ["disabled"])
                    ], 32)
                  ])) : createCommentVNode("", true),
                  createVNode("div", { class: "mb-5 grid gap-2" }, [
                    pedidosAtivosDaMesa(mesaSelecionada.value).length ? (openBlock(), createBlock("div", {
                      key: 0,
                      class: "grid gap-2 rounded-md bg-slate-50 p-3"
                    }, [
                      createVNode("div", { class: "text-xs font-semibold uppercase text-slate-500" }, "Pedidos ativos"),
                      (openBlock(true), createBlock(Fragment, null, renderList(pedidosAtivosDaMesaDetalhados(mesaSelecionada.value), (item) => {
                        return openBlock(), createBlock(unref(Link), {
                          key: item.pedido.id,
                          href: _ctx.route("pedidos.show", item.pedido.id),
                          class: "rounded-md bg-slate-900 px-3 py-2 text-center text-sm font-semibold text-white"
                        }, {
                          default: withCtx(() => [
                            createTextVNode(" Ver pedido #" + toDisplayString(item.pedido.id) + " · " + toDisplayString(item.local), 1)
                          ]),
                          _: 2
                        }, 1032, ["href"]);
                      }), 128))
                    ])) : createCommentVNode("", true),
                    podeAbrirPedido(mesaSelecionada.value) && !mesaSelecionada.value.submesas.length && !mesaSelecionada.value.mesa_principal_id ? (openBlock(), createBlock("div", {
                      key: 1,
                      class: "rounded-md bg-slate-50 p-4"
                    }, [
                      createVNode("label", { class: "text-xs font-semibold uppercase text-slate-500" }, "Lugares ocupados"),
                      withDirectives(createVNode("input", {
                        "onUpdate:modelValue": ($event) => lugaresOcupados.value = $event,
                        type: "number",
                        min: "1",
                        max: "80",
                        class: "mt-1 w-full rounded-md border-slate-300 text-sm",
                        placeholder: "Obrigatorio"
                      }, null, 8, ["onUpdate:modelValue"]), [
                        [vModelText, lugaresOcupados.value]
                      ]),
                      precisaSubmesaSelecionada.value ? (openBlock(), createBlock("label", {
                        key: 0,
                        class: "mt-3 block text-xs font-semibold uppercase text-slate-500"
                      }, [
                        createTextVNode(" Letra da submesa "),
                        withDirectives(createVNode("select", {
                          "onUpdate:modelValue": ($event) => letraSubmesaNova.value = $event,
                          class: "mt-1 w-full rounded-md border-slate-300 text-sm uppercase"
                        }, [
                          createVNode("option", { value: "" }, "Escolher letra"),
                          (openBlock(), createBlock(Fragment, null, renderList(submesaLetras, (letra) => {
                            return createVNode("option", {
                              key: letra,
                              value: letra
                            }, toDisplayString(letra), 9, ["value"]);
                          }), 64))
                        ], 8, ["onUpdate:modelValue"]), [
                          [vModelSelect, letraSubmesaNova.value]
                        ])
                      ])) : createCommentVNode("", true),
                      precisaMesasGrupoSelecionada.value ? (openBlock(), createBlock("label", {
                        key: 1,
                        class: "mt-3 block text-xs font-semibold uppercase text-slate-500"
                      }, [
                        createTextVNode(" Mesas do grupo "),
                        withDirectives(createVNode("input", {
                          "onUpdate:modelValue": ($event) => mesasGrupo.value = $event,
                          type: "text",
                          class: "mt-1 w-full rounded-md border-slate-300 text-sm",
                          placeholder: "Ex.: 32 33 34"
                        }, null, 8, ["onUpdate:modelValue"]), [
                          [vModelText, mesasGrupo.value]
                        ])
                      ])) : createCommentVNode("", true),
                      createVNode("p", { class: "mt-2 text-xs text-slate-500" }, "Ex.: 5 divide a mesa. Acima da capacidade, indica as mesas do grupo.")
                    ])) : createCommentVNode("", true),
                    podeAbrirPedidoMesaCompleta(mesaSelecionada.value) ? (openBlock(), createBlock("button", {
                      key: 2,
                      type: "button",
                      class: "rounded-md bg-slate-900 px-3 py-2 text-sm font-semibold text-white disabled:opacity-50",
                      disabled: !podeAbrirPedidoSelecionado.value,
                      onClick: abrirPedidoSelecionado
                    }, toDisplayString(mesaDivididaLivre(mesaSelecionada.value) ? "Abrir pedido mesa completa" : "Abrir pedido"), 9, ["disabled"])) : mesaSelecionada.value.submesas.length ? (openBlock(), createBlock("div", {
                      key: 3,
                      class: "rounded-md bg-amber-50 p-3 text-sm font-semibold text-amber-800"
                    }, " Esta mesa está dividida. Abre o pedido numa das submesas abaixo. ")) : createCommentVNode("", true),
                    editarMapa.value && mesaLivre(mesaSelecionada.value) && mesaSelecionada.value.submesas.length ? (openBlock(), createBlock("button", {
                      key: 4,
                      type: "button",
                      class: "rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold",
                      onClick: ($event) => juntarMesa(mesaSelecionada.value)
                    }, "Juntar mesa", 8, ["onClick"])) : createCommentVNode("", true),
                    podeMarcarLivre(mesaSelecionada.value) ? (openBlock(), createBlock("button", {
                      key: 5,
                      type: "button",
                      class: "rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold",
                      onClick: ($event) => libertarMesa(mesaSelecionada.value)
                    }, "Marcar livre", 8, ["onClick"])) : createCommentVNode("", true),
                    createVNode(unref(Link), {
                      href: _ctx.route("mesas.edit", mesaSelecionada.value.id),
                      class: "rounded-md border border-slate-300 px-3 py-2 text-center text-sm font-semibold"
                    }, {
                      default: withCtx(() => [
                        createTextVNode("Editar mesa")
                      ]),
                      _: 1
                    }, 8, ["href"]),
                    !pedidosAtivosDaMesa(mesaSelecionada.value).length ? (openBlock(), createBlock("button", {
                      key: 6,
                      type: "button",
                      class: "rounded-md border border-red-300 px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-50",
                      onClick: ($event) => apagarMesa(mesaSelecionada.value)
                    }, " Remover mesa ", 8, ["onClick"])) : createCommentVNode("", true)
                  ]),
                  mesaSelecionada.value.submesas.length ? (openBlock(), createBlock("div", {
                    key: 1,
                    class: "space-y-2"
                  }, [
                    createVNode("div", { class: "text-xs font-semibold uppercase text-slate-500" }, "Submesas"),
                    (openBlock(true), createBlock(Fragment, null, renderList(mesaSelecionada.value.submesas, (submesa) => {
                      return openBlock(), createBlock("div", {
                        key: submesa.id,
                        class: ["rounded-md border p-3", estadoClass[estadoSubmesa(submesa)]]
                      }, [
                        createVNode("div", { class: "flex items-center justify-between gap-3" }, [
                          createVNode("div", null, [
                            createVNode("div", { class: "font-black" }, toDisplayString(letraSubmesa(submesa)), 1),
                            createVNode("div", { class: "text-xs" }, toDisplayString(submesa.capacidade) + " pessoas · lugares " + toDisplayString(submesa.lugares), 1)
                          ]),
                          !mesaLivre(mesaSelecionada.value) && podeAbrirPedido(submesa) ? (openBlock(), createBlock("button", {
                            key: 0,
                            type: "button",
                            class: "rounded-md bg-slate-900 px-3 py-2 text-xs font-semibold text-white disabled:opacity-50",
                            disabled: submesa.capacidade > 1 && !lugaresSubmesa.value[submesa.id],
                            onClick: ($event) => abrirPedidoSubmesa(submesa)
                          }, " Abrir ", 8, ["disabled", "onClick"])) : createCommentVNode("", true)
                        ]),
                        !mesaLivre(mesaSelecionada.value) && podeAbrirPedido(submesa) && submesa.capacidade > 1 ? (openBlock(), createBlock("label", {
                          key: 0,
                          class: "mt-3 block text-xs font-semibold uppercase text-slate-600"
                        }, [
                          createTextVNode(" Lugares ocupados "),
                          withDirectives(createVNode("input", {
                            "onUpdate:modelValue": ($event) => lugaresSubmesa.value[submesa.id] = $event,
                            type: "number",
                            min: "1",
                            max: submesa.capacidade - 1,
                            class: "mt-1 w-full rounded-md border-slate-300 text-sm",
                            placeholder: "Vazio = submesa completa"
                          }, null, 8, ["onUpdate:modelValue", "max"]), [
                            [vModelText, lugaresSubmesa.value[submesa.id]]
                          ])
                        ])) : createCommentVNode("", true),
                        pedidosAtivos(submesa).length ? (openBlock(), createBlock("div", {
                          key: 1,
                          class: "mt-3 grid gap-2"
                        }, [
                          (openBlock(true), createBlock(Fragment, null, renderList(pedidosAtivos(submesa), (pedido) => {
                            return openBlock(), createBlock(unref(Link), {
                              key: pedido.id,
                              href: _ctx.route("pedidos.show", pedido.id),
                              class: "rounded-md bg-slate-900 px-3 py-2 text-center text-xs font-semibold text-white"
                            }, {
                              default: withCtx(() => [
                                createTextVNode(" Ver pedido #" + toDisplayString(pedido.id), 1)
                              ]),
                              _: 2
                            }, 1032, ["href"]);
                          }), 128))
                        ])) : createCommentVNode("", true)
                      ], 2);
                    }), 128))
                  ])) : createCommentVNode("", true)
                ])) : createCommentVNode("", true)
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Mesas/Index.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
