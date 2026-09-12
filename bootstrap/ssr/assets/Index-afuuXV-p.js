import { withCtx, unref, createTextVNode, createVNode, openBlock, createBlock, Fragment, renderList, toDisplayString, useSSRContext } from "vue";
import { ssrRenderComponent, ssrRenderList, ssrInterpolate, ssrRenderClass } from "vue/server-renderer";
import { _ as _sfc_main$1 } from "./AppLayout-BWDcck4N.js";
import { Link, router } from "@inertiajs/vue3";
const _sfc_main = {
  __name: "Index",
  __ssrInlineRender: true,
  props: { socios: Object, filters: Object },
  setup(__props) {
    const eliminarSocio = (socio) => {
      if (confirm(`Eliminar o sócio ${socio.nome}?`)) {
        router.delete(route("socios.destroy", socio.id), {
          preserveScroll: true
        });
      }
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(_sfc_main$1, _attrs, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="mb-6 flex items-center justify-between"${_scopeId}><h1 class="text-2xl font-bold"${_scopeId}>Sócios</h1>`);
            _push2(ssrRenderComponent(unref(Link), {
              href: _ctx.route("socios.create"),
              class: "rounded-md bg-slate-900 px-3 py-2 text-sm text-white"
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`Novo sócio`);
                } else {
                  return [
                    createTextVNode("Novo sócio")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`</div><div class="overflow-x-auto rounded-lg bg-white shadow-sm"${_scopeId}><table class="w-full min-w-[580px] text-left text-sm"${_scopeId}><thead class="bg-slate-50"${_scopeId}><tr${_scopeId}><th class="p-3"${_scopeId}>Número</th><th${_scopeId}>Nome</th><th${_scopeId}>Telefone</th><th${_scopeId}>Cota</th><th${_scopeId}></th></tr></thead><tbody${_scopeId}><!--[-->`);
            ssrRenderList(__props.socios.data, (socio) => {
              _push2(`<tr class="border-t"${_scopeId}><td class="p-3"${_scopeId}>${ssrInterpolate(socio.numero_socio)}</td><td${_scopeId}>${ssrInterpolate(socio.nome)}</td><td${_scopeId}>${ssrInterpolate(socio.telefone)}</td><td${_scopeId}><span class="${ssrRenderClass([socio.cota_em_dia ? "bg-emerald-100 text-emerald-800" : "bg-red-100 text-red-800", "rounded px-2 py-1 text-xs"])}"${_scopeId}>${ssrInterpolate(socio.cota_em_dia ? "Em dia" : "Em atraso")}</span></td><td class="space-x-3"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Link), {
                href: _ctx.route("socios.show", socio.id),
                class: "font-semibold text-emerald-700"
              }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(`Ver`);
                  } else {
                    return [
                      createTextVNode("Ver")
                    ];
                  }
                }),
                _: 2
              }, _parent2, _scopeId));
              _push2(ssrRenderComponent(unref(Link), {
                href: _ctx.route("socios.edit", socio.id),
                class: "font-semibold text-slate-700"
              }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(`Editar`);
                  } else {
                    return [
                      createTextVNode("Editar")
                    ];
                  }
                }),
                _: 2
              }, _parent2, _scopeId));
              _push2(`<button type="button" class="font-semibold text-red-700"${_scopeId}>Eliminar</button></td></tr>`);
            });
            _push2(`<!--]--></tbody></table></div>`);
          } else {
            return [
              createVNode("div", { class: "mb-6 flex items-center justify-between" }, [
                createVNode("h1", { class: "text-2xl font-bold" }, "Sócios"),
                createVNode(unref(Link), {
                  href: _ctx.route("socios.create"),
                  class: "rounded-md bg-slate-900 px-3 py-2 text-sm text-white"
                }, {
                  default: withCtx(() => [
                    createTextVNode("Novo sócio")
                  ]),
                  _: 1
                }, 8, ["href"])
              ]),
              createVNode("div", { class: "overflow-x-auto rounded-lg bg-white shadow-sm" }, [
                createVNode("table", { class: "w-full min-w-[580px] text-left text-sm" }, [
                  createVNode("thead", { class: "bg-slate-50" }, [
                    createVNode("tr", null, [
                      createVNode("th", { class: "p-3" }, "Número"),
                      createVNode("th", null, "Nome"),
                      createVNode("th", null, "Telefone"),
                      createVNode("th", null, "Cota"),
                      createVNode("th")
                    ])
                  ]),
                  createVNode("tbody", null, [
                    (openBlock(true), createBlock(Fragment, null, renderList(__props.socios.data, (socio) => {
                      return openBlock(), createBlock("tr", {
                        key: socio.id,
                        class: "border-t"
                      }, [
                        createVNode("td", { class: "p-3" }, toDisplayString(socio.numero_socio), 1),
                        createVNode("td", null, toDisplayString(socio.nome), 1),
                        createVNode("td", null, toDisplayString(socio.telefone), 1),
                        createVNode("td", null, [
                          createVNode("span", {
                            class: ["rounded px-2 py-1 text-xs", socio.cota_em_dia ? "bg-emerald-100 text-emerald-800" : "bg-red-100 text-red-800"]
                          }, toDisplayString(socio.cota_em_dia ? "Em dia" : "Em atraso"), 3)
                        ]),
                        createVNode("td", { class: "space-x-3" }, [
                          createVNode(unref(Link), {
                            href: _ctx.route("socios.show", socio.id),
                            class: "font-semibold text-emerald-700"
                          }, {
                            default: withCtx(() => [
                              createTextVNode("Ver")
                            ]),
                            _: 1
                          }, 8, ["href"]),
                          createVNode(unref(Link), {
                            href: _ctx.route("socios.edit", socio.id),
                            class: "font-semibold text-slate-700"
                          }, {
                            default: withCtx(() => [
                              createTextVNode("Editar")
                            ]),
                            _: 1
                          }, 8, ["href"]),
                          createVNode("button", {
                            type: "button",
                            class: "font-semibold text-red-700",
                            onClick: ($event) => eliminarSocio(socio)
                          }, "Eliminar", 8, ["onClick"])
                        ])
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Socios/Index.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
