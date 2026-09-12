import { withCtx, createVNode, openBlock, createBlock, Fragment, renderList, toDisplayString, useSSRContext } from "vue";
import { ssrRenderComponent, ssrRenderAttr, ssrRenderList, ssrInterpolate } from "vue/server-renderer";
import { _ as _sfc_main$1 } from "./AppLayout-BWDcck4N.js";
import "@inertiajs/vue3";
const _sfc_main = {
  __name: "EmAtraso",
  __ssrInlineRender: true,
  props: { socios: Array },
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(_sfc_main$1, _attrs, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="mb-6 flex items-center justify-between"${_scopeId}><h1 class="text-2xl font-bold"${_scopeId}>Sócios em atraso</h1><a${ssrRenderAttr("href", _ctx.route("socios.pdf"))} class="rounded-md bg-slate-900 px-3 py-2 text-sm text-white"${_scopeId}>Exportar PDF</a></div><div class="overflow-x-auto rounded-lg bg-white shadow-sm"${_scopeId}><table class="w-full min-w-[440px] text-left text-sm"${_scopeId}><thead class="bg-slate-50"${_scopeId}><tr${_scopeId}><th class="p-3"${_scopeId}>Sócio</th><th${_scopeId}>Meses</th><th${_scopeId}>Dívida</th></tr></thead><tbody${_scopeId}><!--[-->`);
            ssrRenderList(__props.socios, (socio) => {
              _push2(`<tr class="border-t"${_scopeId}><td class="p-3"${_scopeId}>${ssrInterpolate(socio.numero_socio)} · ${ssrInterpolate(socio.nome)}</td><td${_scopeId}>${ssrInterpolate(socio.meses_atraso)}</td><td${_scopeId}>${ssrInterpolate(Number(socio.valor_divida).toFixed(2))}€</td></tr>`);
            });
            _push2(`<!--]--></tbody></table></div>`);
          } else {
            return [
              createVNode("div", { class: "mb-6 flex items-center justify-between" }, [
                createVNode("h1", { class: "text-2xl font-bold" }, "Sócios em atraso"),
                createVNode("a", {
                  href: _ctx.route("socios.pdf"),
                  class: "rounded-md bg-slate-900 px-3 py-2 text-sm text-white"
                }, "Exportar PDF", 8, ["href"])
              ]),
              createVNode("div", { class: "overflow-x-auto rounded-lg bg-white shadow-sm" }, [
                createVNode("table", { class: "w-full min-w-[440px] text-left text-sm" }, [
                  createVNode("thead", { class: "bg-slate-50" }, [
                    createVNode("tr", null, [
                      createVNode("th", { class: "p-3" }, "Sócio"),
                      createVNode("th", null, "Meses"),
                      createVNode("th", null, "Dívida")
                    ])
                  ]),
                  createVNode("tbody", null, [
                    (openBlock(true), createBlock(Fragment, null, renderList(__props.socios, (socio) => {
                      return openBlock(), createBlock("tr", {
                        key: socio.id,
                        class: "border-t"
                      }, [
                        createVNode("td", { class: "p-3" }, toDisplayString(socio.numero_socio) + " · " + toDisplayString(socio.nome), 1),
                        createVNode("td", null, toDisplayString(socio.meses_atraso), 1),
                        createVNode("td", null, toDisplayString(Number(socio.valor_divida).toFixed(2)) + "€", 1)
                      ]);
                    }), 128))
                  ])
                ])
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Cotas/EmAtraso.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
