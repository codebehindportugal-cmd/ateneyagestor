import { withCtx, createVNode, toDisplayString, openBlock, createBlock, Fragment, renderList, useSSRContext } from "vue";
import { ssrRenderComponent, ssrInterpolate, ssrRenderClass, ssrRenderList } from "vue/server-renderer";
import { _ as _sfc_main$1 } from "./AppLayout-BWDcck4N.js";
import "@inertiajs/vue3";
const _sfc_main = {
  __name: "Show",
  __ssrInlineRender: true,
  props: { socio: Object },
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(_sfc_main$1, _attrs, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<h1 class="mb-6 text-2xl font-bold"${_scopeId}>${ssrInterpolate(__props.socio.nome)}</h1><div class="grid gap-6 lg:grid-cols-[320px_1fr]"${_scopeId}><div class="rounded-lg bg-white p-6 shadow-sm"${_scopeId}><div class="text-sm text-slate-500"${_scopeId}>${ssrInterpolate(__props.socio.numero_socio)}</div><div class="mt-2 text-lg font-semibold"${_scopeId}>${ssrInterpolate(__props.socio.telefone)}</div><div class="${ssrRenderClass([__props.socio.cota_em_dia ? "bg-emerald-100 text-emerald-800" : "bg-red-100 text-red-800", "mt-4 rounded-md p-4 text-center font-bold"])}"${_scopeId}>${ssrInterpolate(__props.socio.cota_em_dia ? "COTA EM DIA" : "EM ATRASO")}</div></div><div class="overflow-x-auto rounded-lg bg-white shadow-sm"${_scopeId}><table class="w-full min-w-[360px] text-left text-sm"${_scopeId}><thead class="bg-slate-50"${_scopeId}><tr${_scopeId}><th class="p-3"${_scopeId}>Ano</th><th${_scopeId}>Mês</th><th${_scopeId}>Valor</th><th${_scopeId}>Estado</th></tr></thead><tbody${_scopeId}><!--[-->`);
            ssrRenderList(__props.socio.cotas, (cota) => {
              _push2(`<tr class="border-t"${_scopeId}><td class="p-3"${_scopeId}>${ssrInterpolate(cota.ano)}</td><td${_scopeId}>${ssrInterpolate(cota.mes)}</td><td${_scopeId}>${ssrInterpolate(cota.valor)}€</td><td${_scopeId}>${ssrInterpolate(cota.estado)}</td></tr>`);
            });
            _push2(`<!--]--></tbody></table></div></div>`);
          } else {
            return [
              createVNode("h1", { class: "mb-6 text-2xl font-bold" }, toDisplayString(__props.socio.nome), 1),
              createVNode("div", { class: "grid gap-6 lg:grid-cols-[320px_1fr]" }, [
                createVNode("div", { class: "rounded-lg bg-white p-6 shadow-sm" }, [
                  createVNode("div", { class: "text-sm text-slate-500" }, toDisplayString(__props.socio.numero_socio), 1),
                  createVNode("div", { class: "mt-2 text-lg font-semibold" }, toDisplayString(__props.socio.telefone), 1),
                  createVNode("div", {
                    class: ["mt-4 rounded-md p-4 text-center font-bold", __props.socio.cota_em_dia ? "bg-emerald-100 text-emerald-800" : "bg-red-100 text-red-800"]
                  }, toDisplayString(__props.socio.cota_em_dia ? "COTA EM DIA" : "EM ATRASO"), 3)
                ]),
                createVNode("div", { class: "overflow-x-auto rounded-lg bg-white shadow-sm" }, [
                  createVNode("table", { class: "w-full min-w-[360px] text-left text-sm" }, [
                    createVNode("thead", { class: "bg-slate-50" }, [
                      createVNode("tr", null, [
                        createVNode("th", { class: "p-3" }, "Ano"),
                        createVNode("th", null, "Mês"),
                        createVNode("th", null, "Valor"),
                        createVNode("th", null, "Estado")
                      ])
                    ]),
                    createVNode("tbody", null, [
                      (openBlock(true), createBlock(Fragment, null, renderList(__props.socio.cotas, (cota) => {
                        return openBlock(), createBlock("tr", {
                          key: cota.id,
                          class: "border-t"
                        }, [
                          createVNode("td", { class: "p-3" }, toDisplayString(cota.ano), 1),
                          createVNode("td", null, toDisplayString(cota.mes), 1),
                          createVNode("td", null, toDisplayString(cota.valor) + "€", 1),
                          createVNode("td", null, toDisplayString(cota.estado), 1)
                        ]);
                      }), 128))
                    ])
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Socios/Show.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
