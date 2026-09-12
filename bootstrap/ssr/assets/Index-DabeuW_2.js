import { ref, computed, watch, onMounted, onBeforeUnmount, mergeProps, useSSRContext } from "vue";
import { ssrRenderAttrs, ssrRenderList, ssrRenderClass, ssrRenderStyle, ssrInterpolate, ssrRenderAttr } from "vue/server-renderer";
import { router } from "@inertiajs/vue3";
const _sfc_main = {
  __name: "Index",
  __ssrInlineRender: true,
  props: { mesas: Array, zonas: Array },
  setup(__props) {
    const props = __props;
    const mesasMapa = ref([...props.mesas ?? []]);
    const zonasMapa = ref([...props.zonas ?? []]);
    let polling = null;
    const estadoDot = {
      livre: "bg-emerald-500",
      por_receber: "bg-orange-500",
      grupo: "bg-violet-600",
      ocupada: "bg-red-600",
      reservada: "bg-sky-500"
    };
    const segmentoClass = {
      livre: "bg-emerald-500/90",
      por_receber: "bg-orange-500/90",
      grupo: "bg-violet-600/95",
      ocupada: "bg-red-600/95",
      reservada: "bg-sky-500/90"
    };
    const coresGrupo = [
      "bg-violet-600/95",
      "bg-cyan-600/95",
      "bg-fuchsia-600/95",
      "bg-lime-500/95",
      "bg-amber-500/95",
      "bg-blue-600/95",
      "bg-rose-600/95",
      "bg-teal-600/95"
    ];
    const estadoLabel = {
      livre: "Livre",
      por_receber: "Pedido por receber",
      grupo: "Mesa grande",
      ocupada: "Ocupada",
      reservada: "Reservada"
    };
    const pedidosAtivos = (mesa) => [
      ...mesa?.pedidos ?? [],
      ...mesa?.pedidos_grupo ?? []
    ];
    const pedidoGrupo = (mesa) => (mesa?.pedidos_grupo ?? [])[0] ?? null;
    const mesaGrande = (mesa) => Number(mesa?.capacidade ?? 0) > 10;
    const estadoOperacional = (mesa) => {
      const pedidos = pedidosAtivos(mesa);
      if (mesa?.pedidos_grupo?.length) {
        return "grupo";
      }
      if (pedidos.some((pedido) => pedido.estado === "pendente")) {
        return "por_receber";
      }
      if (pedidos.length && mesaGrande(mesa)) {
        return "grupo";
      }
      if (pedidos.length || mesa?.estado === "ocupada") {
        return "ocupada";
      }
      if (mesa?.estado === "reservada") {
        return "reservada";
      }
      return "livre";
    };
    const segmentosMesa = (mesa) => {
      if (mesa?.submesas?.length) {
        return mesa.submesas.map((submesa) => ({
          id: submesa.id,
          label: letraSubmesa(submesa),
          estado: estadoOperacional(submesa),
          grupoId: pedidoGrupo(submesa)?.id ?? null,
          capacidade: Number(submesa.capacidade || 1),
          pedidos: pedidosAtivos(submesa)
        }));
      }
      return [{
        id: mesa.id,
        label: mesa.numero,
        estado: estadoOperacional(mesa),
        grupoId: pedidoGrupo(mesa)?.id ?? null,
        capacidade: Number(mesa?.capacidade || 1),
        pedidos: pedidosAtivos(mesa)
      }];
    };
    const segmentoClasse = (segmento) => {
      if (segmento.estado === "grupo" && segmento.grupoId) {
        return coresGrupo[Number(segmento.grupoId) % coresGrupo.length];
      }
      return segmentoClass[segmento.estado] ?? segmentoClass.livre;
    };
    const estadoMesa = (mesa) => {
      const estados = segmentosMesa(mesa).map((segmento) => segmento.estado);
      if (estados.includes("por_receber")) {
        return "por_receber";
      }
      if (estados.includes("grupo")) {
        return "grupo";
      }
      if (estados.includes("ocupada")) {
        return "ocupada";
      }
      if (estados.includes("reservada")) {
        return "reservada";
      }
      return "livre";
    };
    computed(() => mesasMapa.value.reduce((total, mesa) => {
      total[estadoMesa(mesa)] += 1;
      return total;
    }, {
      livre: 0,
      por_receber: 0,
      grupo: 0,
      ocupada: 0,
      reservada: 0
    }));
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
    const letraSubmesa = (submesa) => submesa.designacao.replace(/^Mesa\s*/i, "");
    const pedidosMesa = (mesa) => [
      ...pedidosAtivos(mesa),
      ...(mesa?.submesas ?? []).flatMap((submesa) => pedidosAtivos(submesa))
    ];
    const textoPedidos = (mesa) => {
      const pedidos = pedidosMesa(mesa);
      if (!pedidos.length) {
        return "Sem pedidos ativos";
      }
      return pedidos.map((pedido) => `#${pedido.id}${pedido.operador_nome ? ` - ${pedido.operador_nome}` : ""}`).join(" | ");
    };
    const horaPedido = (pedido) => new Date(pedido.created_at).toLocaleTimeString("pt-PT", {
      hour: "2-digit",
      minute: "2-digit"
    });
    watch(() => props.mesas, (mesas) => {
      mesasMapa.value = [...mesas ?? []];
    });
    watch(() => props.zonas, (zonas) => {
      zonasMapa.value = [...zonas ?? []];
    });
    onMounted(() => {
      polling = setInterval(() => {
        router.reload({ only: ["mesas", "zonas"], preserveScroll: true });
      }, 3e3);
    });
    onBeforeUnmount(() => {
      clearInterval(polling);
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<main${ssrRenderAttrs(mergeProps({ class: "h-screen overflow-hidden bg-slate-950 p-3" }, _attrs))}><section class="h-full rounded-lg border border-slate-600 bg-slate-900 p-3 shadow-sm"><div data-sala-mapa class="relative h-full w-full overflow-hidden rounded-lg bg-[#f7f5ef] shadow-inner ring-1 ring-slate-500"><div class="absolute inset-x-[2%] inset-y-[4%] rounded-md border-[3px] border-slate-800/70"></div><div class="absolute left-[2%] top-[38%] h-[18%] w-[2px] bg-[#f7f5ef]"></div><div class="absolute left-[2%] top-[63%] h-[14%] w-[2px] bg-[#f7f5ef]"></div><div class="absolute right-[2%] top-[12%] h-[16%] w-[2px] bg-[#f7f5ef]"></div><div class="absolute right-[2%] bottom-[8%] h-[12%] w-[2px] bg-[#f7f5ef]"></div><!--[-->`);
      ssrRenderList(zonasMapa.value, (zona) => {
        _push(`<div class="${ssrRenderClass([zonaClasse(zona), "absolute flex items-center justify-center rounded-sm border-[3px] p-1 text-center text-xs font-black uppercase"])}" style="${ssrRenderStyle(zonaStyle(zona))}"><span class="${ssrRenderClass(zonaVertical(zona) ? "[writing-mode:vertical-rl]" : "")}">${ssrInterpolate(zona.nome)}</span></div>`);
      });
      _push(`<!--]--><!--[-->`);
      ssrRenderList(mesasMapa.value, (mesa) => {
        _push(`<div class="absolute overflow-hidden rounded-md border-2 border-slate-900 bg-white text-left shadow-sm" style="${ssrRenderStyle(mesaStyle(mesa))}"${ssrRenderAttr("title", textoPedidos(mesa))}><div class="${ssrRenderClass([mesa.mapa_altura > mesa.mapa_largura ? "flex-col" : "flex-row", "absolute inset-0 flex"])}"><!--[-->`);
        ssrRenderList(segmentosMesa(mesa), (segmento) => {
          _push(`<div class="${ssrRenderClass([[segmentoClasse(segmento), mesa.mapa_altura > mesa.mapa_largura ? "border-b last:border-b-0" : "border-r last:border-r-0"], "min-h-0 min-w-0 border-white/70"])}" style="${ssrRenderStyle({ flex: segmento.capacidade })}"></div>`);
        });
        _push(`<!--]--></div><div class="relative z-10 flex h-full flex-col justify-between p-1 text-center text-white drop-shadow"><div class="flex items-start justify-between gap-1"><span class="rounded bg-slate-950/60 px-1 text-[10px] font-black">${ssrInterpolate(mesa.numero)}</span><span class="${ssrRenderClass([estadoDot[estadoMesa(mesa)], "h-2.5 w-2.5 rounded-full ring-1 ring-white"])}"></span></div>`);
        if (mesa.submesas.length) {
          _push(`<div class="${ssrRenderClass([mesa.submesas.length > 3 ? "grid-cols-3" : "grid-cols-2", "grid gap-0.5"])}"><!--[-->`);
          ssrRenderList(segmentosMesa(mesa), (segmento) => {
            _push(`<span class="rounded bg-slate-950/50 px-1 py-0.5 text-[9px] font-black">${ssrInterpolate(segmento.label)}</span>`);
          });
          _push(`<!--]--></div>`);
        } else {
          _push(`<div class="grid gap-0.5"><span class="rounded bg-slate-950/50 px-1 py-0.5 text-[9px] font-black">${ssrInterpolate(estadoLabel[estadoMesa(mesa)])}</span>`);
          if (pedidosAtivos(mesa)[0]) {
            _push(`<span class="rounded bg-slate-950/50 px-1 py-0.5 text-[9px] font-bold"> #${ssrInterpolate(pedidosAtivos(mesa)[0].id)} - ${ssrInterpolate(horaPedido(pedidosAtivos(mesa)[0]))}</span>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div>`);
        }
        _push(`</div></div>`);
      });
      _push(`<!--]--></div></section></main>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Sala/Index.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
