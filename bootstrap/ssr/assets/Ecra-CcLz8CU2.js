import { ref, computed, watch, onMounted, onBeforeUnmount, mergeProps, useSSRContext } from "vue";
import { ssrRenderAttrs, ssrInterpolate, ssrRenderList, ssrRenderClass } from "vue/server-renderer";
import { router } from "@inertiajs/vue3";
const _sfc_main = {
  __name: "Ecra",
  __ssrInlineRender: true,
  props: { titulo: String, itemsPorMesa: Array, tem_urgentes: Boolean, modoBar: Boolean },
  setup(__props) {
    const props = __props;
    const aAtualizar = ref(false);
    const ultimaAtualizacao = ref(/* @__PURE__ */ new Date());
    const novosItems = ref(/* @__PURE__ */ new Set());
    const idsConhecidos = ref(/* @__PURE__ */ new Set());
    let intervalo = null;
    let limparDestaque = null;
    const totalItems = computed(() => (props.itemsPorMesa ?? []).reduce((total, grupo) => total + grupo.items.length, 0));
    const idsAtuais = () => new Set((props.itemsPorMesa ?? []).flatMap((grupo) => grupo.items.map((item) => item.id)));
    const atualizar = () => {
      aAtualizar.value = true;
      router.reload({ only: ["itemsPorMesa", "tem_urgentes"], preserveScroll: true, onFinish: () => {
        aAtualizar.value = false;
        ultimaAtualizacao.value = /* @__PURE__ */ new Date();
      } });
    };
    watch(() => props.itemsPorMesa, () => {
      const atuais = idsAtuais();
      const novos = [...atuais].filter((id) => !idsConhecidos.value.has(id));
      if (idsConhecidos.value.size && novos.length) {
        novosItems.value = new Set(novos);
        clearTimeout(limparDestaque);
        limparDestaque = setTimeout(() => {
          novosItems.value = /* @__PURE__ */ new Set();
        }, 12e3);
      }
      idsConhecidos.value = atuais;
    }, { deep: true, immediate: true });
    onMounted(() => {
      intervalo = setInterval(atualizar, 5e3);
    });
    onBeforeUnmount(() => {
      clearInterval(intervalo);
      clearTimeout(limparDestaque);
    });
    const hora = computed(() => ultimaAtualizacao.value.toLocaleTimeString("pt-PT", { hour: "2-digit", minute: "2-digit", second: "2-digit" }));
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<main${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-[#1a1a2e] p-5 text-white sm:p-8" }, _attrs))}>`);
      if (__props.tem_urgentes) {
        _push(`<div class="mb-5 animate-pulse rounded-2xl bg-amber-600 p-5 text-center text-2xl font-black text-white shadow-lg">ATENÇÃO — HÁ MESAS A TERMINAR</div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<header class="mb-8 flex flex-wrap items-center justify-between gap-4"><div><h1 class="text-5xl font-black tracking-normal">${ssrInterpolate(__props.titulo)}</h1><p class="mt-2 text-sm text-white/70">${ssrInterpolate(totalItems.value)} pedidos pendentes · atualização automática</p></div><div class="rounded-lg bg-white/10 px-4 py-3 text-right"><div class="text-xs font-semibold uppercase text-white/60">${ssrInterpolate(aAtualizar.value ? "A procurar novos pedidos" : "Último refresh")}</div><div class="text-2xl font-bold">${ssrInterpolate(hora.value)}</div></div></header>`);
      if (!__props.itemsPorMesa?.length) {
        _push(`<div class="rounded-lg border border-white/10 bg-white/10 p-10 text-center text-2xl font-bold text-white/80">Sem pedidos pendentes</div>`);
      } else {
        _push(`<div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3"><!--[-->`);
        ssrRenderList(__props.itemsPorMesa, (grupo) => {
          _push(`<section class="${ssrRenderClass([grupo.urgente ? "border-4 border-amber-500 bg-amber-950/70" : "bg-white/10", "relative rounded-lg p-6"])}">`);
          if (grupo.urgente) {
            _push(`<span class="absolute right-4 top-4 rounded-full bg-amber-600 px-3 py-1 text-sm font-black">A TERMINAR</span>`);
          } else {
            _push(`<!---->`);
          }
          _push(`<h2 class="mb-4 text-3xl font-bold">${ssrInterpolate(grupo.mesa)}</h2><div class="mb-4 rounded-md bg-white/10 px-3 py-2 text-sm font-black text-white/80">Operador: ${ssrInterpolate(grupo.operador ?? "Sem operador")}</div><!--[-->`);
          ssrRenderList(grupo.items, (item) => {
            _push(`<div class="${ssrRenderClass([item.prioridade ? "bg-amber-900 text-white ring-4 ring-amber-500" : item.observacoes ? "bg-amber-100 ring-4 ring-amber-400" : novosItems.value.has(item.id) ? "bg-amber-200 ring-4 ring-amber-400" : "bg-white", "mb-3 flex items-center justify-between gap-4 rounded-md p-4 text-slate-900 transition-all duration-500"])}"><div><span class="block text-[1.2rem] font-bold">${ssrInterpolate(item.quantidade)}x ${ssrInterpolate(item.produto?.nome)}</span>`);
            if (item.prioridade) {
              _push(`<span class="mt-1 block text-sm font-black text-amber-200">A TERMINAR</span>`);
            } else if (novosItems.value.has(item.id)) {
              _push(`<span class="mt-1 block text-sm font-bold text-amber-800">Novo pedido</span>`);
            } else {
              _push(`<!---->`);
            }
            if (item.observacoes) {
              _push(`<span class="mt-3 block rounded-md bg-red-600 px-3 py-2 text-lg font-black text-white"> ATENÇÃO: ${ssrInterpolate(item.observacoes)}</span>`);
            } else {
              _push(`<!---->`);
            }
            _push(`</div></div>`);
          });
          _push(`<!--]-->`);
          if (__props.modoBar && grupo.pedido_id) {
            _push(`<button class="mt-3 w-full rounded-md bg-emerald-600 px-5 py-4 text-xl font-black text-white shadow-lg shadow-emerald-950/30"> RETIRAR PEDIDO </button>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</section>`);
        });
        _push(`<!--]--></div>`);
      }
      _push(`<div class="fixed bottom-3 right-4 text-xs font-bold text-white/50">Último refresh: ${ssrInterpolate(hora.value)}</div></main>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Secao/Ecra.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
