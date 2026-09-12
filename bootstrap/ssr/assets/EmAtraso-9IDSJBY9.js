import { computed, mergeProps, unref, withCtx, createTextVNode, useSSRContext } from "vue";
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate, ssrRenderList } from "vue/server-renderer";
import { Link } from "@inertiajs/vue3";
const _sfc_main = {
  __name: "EmAtraso",
  __ssrInlineRender: true,
  props: { socios: Array },
  setup(__props) {
    const props = __props;
    const euros = (v) => Number(v ?? 0).toFixed(2) + "€";
    const total = computed(() => (props.socios ?? []).reduce((s, socio) => s + Number(socio.valor_em_divida ?? 0), 0));
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<main${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-gray-900 p-5 text-white" }, _attrs))}><header class="mb-5 flex items-center justify-between"><h1 class="text-3xl font-black">⚠️ SÓCIOS COM COTAS EM ATRASO</h1>`);
      _push(ssrRenderComponent(unref(Link), {
        href: _ctx.route("pos.cotas.index"),
        class: "rounded-lg bg-gray-800 px-4 py-3 font-black"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`← VOLTAR`);
          } else {
            return [
              createTextVNode("← VOLTAR")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</header><div class="mb-5 rounded-lg bg-red-700 p-5 text-3xl font-black">${ssrInterpolate(euros(total.value))}</div><div class="grid gap-3"><!--[-->`);
      ssrRenderList(__props.socios, (socio) => {
        _push(`<div class="grid gap-3 rounded-lg bg-gray-800 p-4 md:grid-cols-[1fr_auto_auto_auto] md:items-center"><div><div class="text-xl font-black">${ssrInterpolate(socio.nome)}</div><div class="font-bold text-gray-300">N.º ${ssrInterpolate(socio.numero_socio)}</div></div><div class="font-black">${ssrInterpolate(socio.meses_em_atraso)} meses</div><div class="font-mono text-xl font-black text-red-400">${ssrInterpolate(euros(socio.valor_em_divida))}</div>`);
        _push(ssrRenderComponent(unref(Link), {
          href: _ctx.route("pos.cotas.socio", socio.id),
          class: "rounded bg-emerald-600 px-5 py-3 text-center font-black"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`COBRAR`);
            } else {
              return [
                createTextVNode("COBRAR")
              ];
            }
          }),
          _: 2
        }, _parent));
        _push(`</div>`);
      });
      _push(`<!--]--></div></main>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/PosCotas/EmAtraso.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
