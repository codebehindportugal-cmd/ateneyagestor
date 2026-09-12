import { ref, computed, onMounted, onBeforeUnmount, mergeProps, unref, withCtx, createTextVNode, createVNode, toDisplayString, openBlock, createBlock, createCommentVNode, useSSRContext } from "vue";
import { ssrRenderAttrs, ssrRenderComponent, ssrRenderList, ssrInterpolate, ssrRenderAttr } from "vue/server-renderer";
import { router, Link } from "@inertiajs/vue3";
import "qrcode";
const _sfc_main = {
  __name: "Mesas",
  __ssrInlineRender: true,
  props: { mesas: Array },
  setup(__props) {
    const props = __props;
    let refresh = null;
    const qrAberto = ref(false);
    const qrDataUrl = ref("");
    const grupos = computed(() => Object.groupBy(props.mesas ?? [], (m) => m.localizacao || "Sala"));
    const precarioUrl = computed(() => route("precario"));
    const pedidosAtivos = (mesa) => [
      ...mesa.pedidos ?? [],
      ...mesa.pedidos_grupo ?? [],
      ...(mesa.submesas ?? []).flatMap((submesa) => submesa.pedidos ?? []),
      ...(mesa.submesas ?? []).flatMap((submesa) => submesa.pedidos_grupo ?? [])
    ];
    const coresGrupo = [
      "bg-violet-600",
      "bg-cyan-600",
      "bg-fuchsia-600",
      "bg-lime-500 text-gray-950",
      "bg-amber-500 text-gray-950",
      "bg-blue-600",
      "bg-rose-600",
      "bg-teal-600"
    ];
    const pedidoGrupo = (mesa) => (mesa.pedidos_grupo ?? [])[0] ?? (mesa.submesas ?? []).flatMap((submesa) => submesa.pedidos_grupo ?? [])[0] ?? null;
    const estadoVisual = (mesa) => pedidoGrupo(mesa) ? "grupo" : pedidosAtivos(mesa).length ? "ocupada" : mesa.estado;
    const total = (mesa) => Number(pedidosAtivos(mesa).reduce((soma, pedido) => soma + Number(pedido.total_calculado ?? pedido.total ?? 0), 0)).toFixed(2) + "€";
    const minutos = (mesa) => Math.max(0, Math.floor((Date.now() - new Date(pedidosAtivos(mesa)[0]?.created_at || Date.now())) / 6e4)) + "min";
    const mesaLivre = (mesa) => !pedidosAtivos(mesa).length && (mesa?.estado ?? "livre") === "livre";
    const lugaresLivres = (mesa) => {
      if (mesa.submesas?.length) {
        return mesa.submesas.filter((submesa) => mesaLivre(submesa)).reduce((total2, submesa) => total2 + Number(submesa.capacidade || 0), 0);
      }
      return mesaLivre(mesa) ? Number(mesa.capacidade || 0) : 0;
    };
    const textoLugaresLivres = (mesa) => {
      const livres = lugaresLivres(mesa);
      return `${livres} ${livres === 1 ? "lugar livre" : "lugares livres"}`;
    };
    const cor = (mesa) => {
      const grupo = pedidoGrupo(mesa);
      if (grupo) {
        return coresGrupo[Number(grupo.id ?? 0) % coresGrupo.length];
      }
      return estadoVisual(mesa) === "ocupada" ? "bg-red-600" : estadoVisual(mesa) === "reservada" ? "bg-yellow-500 text-gray-950" : "bg-emerald-600";
    };
    onMounted(() => {
      refresh = setInterval(() => router.reload({ only: ["mesas"], preserveScroll: true }), 2e4);
    });
    onBeforeUnmount(() => clearInterval(refresh));
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<main${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-gray-900 p-5 text-white" }, _attrs))}><header class="mb-5 flex items-center gap-4">`);
      _push(ssrRenderComponent(unref(Link), {
        href: _ctx.route("pos.rest.index"),
        class: "rounded-lg bg-gray-800 px-4 py-3 font-black"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`←`);
          } else {
            return [
              createTextVNode("←")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`<h1 class="text-4xl font-black">MESAS</h1></header><div class="mb-5 flex justify-end"><button type="button" class="rounded-lg bg-emerald-600 px-4 py-3 font-black">QR PREÇÁRIO</button></div><!--[-->`);
      ssrRenderList(grupos.value, (lista, local) => {
        _push(`<section class="mb-7"><h2 class="mb-3 text-xl font-black uppercase text-gray-300">${ssrInterpolate(local)}</h2><div class="grid grid-cols-2 gap-3 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8"><!--[-->`);
        ssrRenderList(lista, (mesa) => {
          _push(ssrRenderComponent(unref(Link), {
            key: mesa.id,
            href: _ctx.route("pos.rest.mesa", mesa.id),
            class: ["flex min-h-[116px] flex-col items-center justify-center rounded-lg p-3 text-center font-black shadow", cor(mesa)]
          }, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(`<span class="text-2xl sm:text-3xl"${_scopeId}>${ssrInterpolate(mesa.numero)}</span><span class="text-xs"${_scopeId}>Cap. ${ssrInterpolate(mesa.capacidade)}</span><span class="mt-1 rounded bg-black/15 px-2 py-0.5 text-xs"${_scopeId}>${ssrInterpolate(textoLugaresLivres(mesa))}</span>`);
                if (mesa.submesas?.length) {
                  _push2(`<span class="mt-1 text-xs"${_scopeId}>${ssrInterpolate(mesa.submesas.length)} submesas</span>`);
                } else {
                  _push2(`<!---->`);
                }
                if (estadoVisual(mesa) === "grupo") {
                  _push2(`<span class="mt-1 text-xs uppercase"${_scopeId}>Grupo</span>`);
                } else {
                  _push2(`<!---->`);
                }
                if (["grupo", "ocupada"].includes(estadoVisual(mesa))) {
                  _push2(`<span class="mt-1 text-sm"${_scopeId}>${ssrInterpolate(total(mesa))} · ${ssrInterpolate(minutos(mesa))}</span>`);
                } else {
                  _push2(`<!---->`);
                }
              } else {
                return [
                  createVNode("span", { class: "text-2xl sm:text-3xl" }, toDisplayString(mesa.numero), 1),
                  createVNode("span", { class: "text-xs" }, "Cap. " + toDisplayString(mesa.capacidade), 1),
                  createVNode("span", { class: "mt-1 rounded bg-black/15 px-2 py-0.5 text-xs" }, toDisplayString(textoLugaresLivres(mesa)), 1),
                  mesa.submesas?.length ? (openBlock(), createBlock("span", {
                    key: 0,
                    class: "mt-1 text-xs"
                  }, toDisplayString(mesa.submesas.length) + " submesas", 1)) : createCommentVNode("", true),
                  estadoVisual(mesa) === "grupo" ? (openBlock(), createBlock("span", {
                    key: 1,
                    class: "mt-1 text-xs uppercase"
                  }, "Grupo")) : createCommentVNode("", true),
                  ["grupo", "ocupada"].includes(estadoVisual(mesa)) ? (openBlock(), createBlock("span", {
                    key: 2,
                    class: "mt-1 text-sm"
                  }, toDisplayString(total(mesa)) + " · " + toDisplayString(minutos(mesa)), 1)) : createCommentVNode("", true)
                ];
              }
            }),
            _: 2
          }, _parent));
        });
        _push(`<!--]--></div></section>`);
      });
      _push(`<!--]-->`);
      if (qrAberto.value) {
        _push(`<div class="fixed inset-0 z-50 overflow-auto bg-gray-950 p-5"><div class="mx-auto max-w-md rounded-2xl bg-white p-5 text-center text-slate-950"><h2 class="text-2xl font-black">Preçário</h2><p class="mt-1 text-sm font-semibold text-slate-500">Produtos e preços disponíveis</p>`);
        if (qrDataUrl.value) {
          _push(`<img${ssrRenderAttr("src", qrDataUrl.value)} alt="QR code do preçário" class="mx-auto my-5 h-72 w-72 rounded-xl border p-3">`);
        } else {
          _push(`<!---->`);
        }
        _push(`<input${ssrRenderAttr("value", precarioUrl.value)} readonly class="w-full rounded-lg border-slate-300 text-xs"><button class="mt-3 w-full rounded-lg bg-slate-900 p-3 font-black text-white">COPIAR LINK</button><button class="mt-3 w-full rounded-lg bg-gray-200 p-3 font-black text-slate-950">FECHAR</button></div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</main>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/PosRest/Mesas.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
