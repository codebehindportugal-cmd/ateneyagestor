import { mergeProps, unref, withCtx, createTextVNode, useSSRContext } from "vue";
import { ssrRenderAttrs, ssrInterpolate, ssrRenderList, ssrRenderComponent } from "vue/server-renderer";
import { Link } from "@inertiajs/vue3";
const _sfc_main = {
  __name: "Confirmacao",
  __ssrInlineRender: true,
  props: {
    token: String,
    pedido: Object,
    items: Array
  },
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<main${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-slate-950 px-4 py-6 text-white" }, _attrs))}><section class="mx-auto max-w-xl"><div class="rounded-3xl bg-white p-6 text-center text-slate-950 shadow-sm"><div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-xl font-black text-emerald-700">OK</div><h1 class="text-2xl font-black">O seu pedido foi enviado para a cozinha</h1><p class="mt-2 text-sm font-semibold text-slate-500">Mesa ${ssrInterpolate(__props.pedido.mesa)}</p></div><div class="mt-5 rounded-3xl bg-white p-5 text-slate-950 shadow-sm"><h2 class="mb-3 text-lg font-black">Produtos enviados</h2><div class="mb-3 rounded-2xl bg-amber-100 p-4 text-sm font-black text-amber-900"> Se se enganou no pedido, chame um funcionário para ajudar. </div>`);
      if (!__props.items?.length) {
        _push(`<div class="rounded-2xl bg-slate-100 p-4 text-center text-sm font-semibold text-slate-500"> Ainda não foram enviados produtos. </div>`);
      } else {
        _push(`<div class="grid gap-2"><!--[-->`);
        ssrRenderList(__props.items, (item) => {
          _push(`<div class="flex items-center justify-between gap-3 rounded-2xl bg-slate-100 p-3"><div><div class="font-black">${ssrInterpolate(item.nome)}</div><div class="text-xs font-semibold text-slate-500">${ssrInterpolate(item.hora)}</div>`);
          if (item.observacoes) {
            _push(`<div class="mt-1 text-sm font-semibold text-slate-700">${ssrInterpolate(item.observacoes)}</div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div><div class="rounded-full bg-slate-950 px-3 py-1 text-sm font-black text-white">${ssrInterpolate(item.quantidade)}x</div></div>`);
        });
        _push(`<!--]--></div>`);
      }
      _push(`</div>`);
      if (__props.pedido.disponivel) {
        _push(ssrRenderComponent(unref(Link), {
          href: _ctx.route("cliente.mesa", __props.token),
          class: "mt-5 block rounded-2xl bg-emerald-500 px-5 py-4 text-center text-lg font-black text-slate-950"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(` Adicionar mais itens `);
            } else {
              return [
                createTextVNode(" Adicionar mais itens ")
              ];
            }
          }),
          _: 1
        }, _parent));
      } else {
        _push(`<div class="mt-5 rounded-2xl bg-amber-500/10 p-4 text-center text-sm font-bold text-amber-100"> Este pedido já não permite adicionar mais itens. </div>`);
      }
      _push(`</section></main>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Cliente/Confirmacao.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
