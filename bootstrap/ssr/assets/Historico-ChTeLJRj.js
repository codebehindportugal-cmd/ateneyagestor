import { computed, mergeProps, unref, withCtx, createTextVNode, useSSRContext } from "vue";
import { ssrRenderAttrs, ssrRenderComponent, ssrRenderList, ssrInterpolate } from "vue/server-renderer";
import { Link } from "@inertiajs/vue3";
const _sfc_main = {
  __name: "Historico",
  __ssrInlineRender: true,
  props: { pedidos: Array },
  setup(__props) {
    const props = __props;
    const euros = (v) => Number(v ?? 0).toFixed(2) + "€";
    const hora = (d) => new Date(d).toLocaleTimeString("pt-PT", { hour: "2-digit", minute: "2-digit" });
    const totalDia = computed(() => (props.pedidos ?? []).reduce((s, p) => s + Number(p.total ?? 0), 0));
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<main${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-gray-900 p-5 text-white" }, _attrs))}><header class="mb-5 flex items-center justify-between"><h1 class="text-3xl font-black">HISTORICO DO DIA</h1>`);
      _push(ssrRenderComponent(unref(Link), {
        href: _ctx.route("pos.rest.index"),
        class: "rounded-lg bg-gray-800 px-4 py-3 font-black"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`VOLTAR`);
          } else {
            return [
              createTextVNode("VOLTAR")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</header><div class="space-y-3"><!--[-->`);
      ssrRenderList(__props.pedidos, (pedido) => {
        _push(`<div class="rounded-lg bg-gray-800 p-4"><div class="flex justify-between gap-3"><strong>${ssrInterpolate(hora(pedido.created_at))} · Mesa ${ssrInterpolate(pedido.mesa?.numero)}</strong><strong class="text-emerald-400">${ssrInterpolate(euros(pedido.total))}</strong></div><div class="text-gray-300">${ssrInterpolate(pedido.items.map((i) => `${i.quantidade}x ${i.produto?.nome}`).join(", "))}</div><div class="text-sm uppercase text-gray-400">${ssrInterpolate(pedido.metodo_pagamento)}</div></div>`);
      });
      _push(`<!--]--></div><div class="mt-5 rounded-lg bg-emerald-700 p-5 text-right text-3xl font-black">${ssrInterpolate(euros(totalDia.value))}</div></main>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/PosRest/Historico.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
