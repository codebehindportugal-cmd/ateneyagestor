import { computed, onMounted, mergeProps, unref, withCtx, createTextVNode, useSSRContext } from "vue";
import { ssrRenderAttrs, ssrInterpolate, ssrRenderList, ssrRenderComponent } from "vue/server-renderer";
import { Link } from "@inertiajs/vue3";
const _sfc_main = {
  __name: "Talao",
  __ssrInlineRender: true,
  props: { pedido: Object },
  setup(__props) {
    const props = __props;
    const operador = computed(() => props.pedido.operador_nome ?? props.pedido.user?.name ?? props.pedido.pos?.nome ?? "Sem operador");
    const mesaLabel = computed(() => props.pedido.mesa?.designacao ?? "Para levar");
    const euros = (v) => Number(v ?? 0).toFixed(2) + " EUR";
    const items = computed(() => Object.values((props.pedido.items ?? []).reduce((grupos, item) => {
      const chave = [item.produto?.id, item.produto?.nome, item.preco_unitario, item.secao].join("|");
      grupos[chave] ??= { ...item, id: chave, quantidade: 0 };
      grupos[chave].quantidade += Number(item.quantidade ?? 0);
      return grupos;
    }, {})));
    onMounted(() => setTimeout(() => window.print(), 300));
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<main${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-gray-200 p-5 text-gray-950 print:bg-white" }, _attrs))}><section class="mx-auto max-w-sm bg-white p-5 pb-12 font-mono"><h1 class="text-center text-lg font-black">Associacao de Santana - Restaurante</h1><div class="my-3 border-y py-2 text-center text-sm font-black uppercase">Este documento nao serve de fatura</div><div class="my-3 border-b pb-2"> Mesa: ${ssrInterpolate(mesaLabel.value)}<br> Operador: ${ssrInterpolate(operador.value)}</div><!--[-->`);
      ssrRenderList(items.value, (item) => {
        _push(`<div class="flex justify-between gap-2"><span>${ssrInterpolate(item.quantidade)}x ${ssrInterpolate(item.produto?.nome)}</span><strong>${ssrInterpolate(euros(item.quantidade * item.preco_unitario))}</strong></div>`);
      });
      _push(`<!--]--><div class="mt-3 border-t pt-2 text-right"><div>Total: ${ssrInterpolate(euros(__props.pedido.total))}</div><div>Recebido: ${ssrInterpolate(euros(__props.pedido.valor_recebido))}</div><div>Troco: ${ssrInterpolate(euros(__props.pedido.troco))}</div><div>Metodo: ${ssrInterpolate(__props.pedido.metodo_pagamento)}</div></div><div class="h-8"></div></section><div class="mx-auto mt-5 grid max-w-sm gap-2 print:hidden"><button class="rounded bg-gray-900 p-3 font-black text-white">IMPRIMIR</button>`);
      _push(ssrRenderComponent(unref(Link), {
        href: _ctx.route("pos.rest.mesas"),
        class: "rounded bg-emerald-600 p-3 text-center font-black text-white"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`VER MESAS`);
          } else {
            return [
              createTextVNode("VER MESAS")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(unref(Link), {
        href: _ctx.route("pos.rest.index"),
        class: "rounded bg-blue-600 p-3 text-center font-black text-white"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`ECRA PRINCIPAL`);
          } else {
            return [
              createTextVNode("ECRA PRINCIPAL")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div></main>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/PosRest/Talao.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
