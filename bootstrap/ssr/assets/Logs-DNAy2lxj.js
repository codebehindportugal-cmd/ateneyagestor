import { reactive, withCtx, unref, createTextVNode, createVNode, withModifiers, withDirectives, vModelText, vModelSelect, openBlock, createBlock, Fragment, renderList, toDisplayString, createCommentVNode, useSSRContext } from "vue";
import { ssrRenderComponent, ssrRenderAttr, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual, ssrRenderList, ssrInterpolate, ssrRenderClass } from "vue/server-renderer";
import { Link, router } from "@inertiajs/vue3";
import { _ as _sfc_main$1 } from "./AppLayout-BWDcck4N.js";
const _sfc_main = {
  __name: "Logs",
  __ssrInlineRender: true,
  props: {
    filters: Object,
    logs: Array,
    modelos: Array
  },
  setup(__props) {
    const props = __props;
    const filtros = reactive({
      data_inicio: props.filters?.data_inicio,
      data_fim: props.filters?.data_fim,
      acao: props.filters?.acao ?? "todas",
      modelo: props.filters?.modelo ?? "todos",
      funcionario: props.filters?.funcionario ?? ""
    });
    const carregar = () => {
      router.get(route("manutencao.logs.index"), filtros, {
        preserveState: true,
        preserveScroll: true
      });
    };
    const valor = (item) => {
      if (item === null || item === void 0 || item === "") return "-";
      if (typeof item === "boolean") return item ? "Sim" : "Não";
      return String(item);
    };
    const campos = (log) => Array.from(/* @__PURE__ */ new Set([
      ...Object.keys(log.old_values ?? {}),
      ...Object.keys(log.new_values ?? {})
    ]));
    const acaoClasses = {
      criado: "bg-emerald-100 text-emerald-800",
      alterado: "bg-amber-100 text-amber-900",
      apagado: "bg-red-100 text-red-800"
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(_sfc_main$1, _attrs, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="mb-6 flex flex-wrap items-start justify-between gap-4"${_scopeId}><div${_scopeId}><h1 class="text-3xl font-black text-slate-950"${_scopeId}>Logs de alterações</h1><p class="mt-2 max-w-3xl text-sm text-slate-600"${_scopeId}> Histórico de dados criados, alterados ou apagados por funcionários no backoffice e no POS. </p></div>`);
            _push2(ssrRenderComponent(unref(Link), {
              href: _ctx.route("manutencao.limpeza.index"),
              class: "rounded-md bg-slate-900 px-4 py-2 text-sm font-black text-white"
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` Limpeza de dados `);
                } else {
                  return [
                    createTextVNode(" Limpeza de dados ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`</div><form class="mb-6 grid gap-4 bg-white p-5 shadow-sm ring-1 ring-slate-200 lg:grid-cols-[repeat(5,minmax(0,1fr))_auto]"${_scopeId}><label${_scopeId}><span class="mb-1 block text-sm font-bold text-slate-700"${_scopeId}>Data início</span><input${ssrRenderAttr("value", filtros.data_inicio)} type="date" class="w-full rounded-md border-slate-300"${_scopeId}></label><label${_scopeId}><span class="mb-1 block text-sm font-bold text-slate-700"${_scopeId}>Data fim</span><input${ssrRenderAttr("value", filtros.data_fim)} type="date" class="w-full rounded-md border-slate-300"${_scopeId}></label><label${_scopeId}><span class="mb-1 block text-sm font-bold text-slate-700"${_scopeId}>Ação</span><select class="w-full rounded-md border-slate-300"${_scopeId}><option value="todas"${ssrIncludeBooleanAttr(Array.isArray(filtros.acao) ? ssrLooseContain(filtros.acao, "todas") : ssrLooseEqual(filtros.acao, "todas")) ? " selected" : ""}${_scopeId}>Todas</option><option value="criado"${ssrIncludeBooleanAttr(Array.isArray(filtros.acao) ? ssrLooseContain(filtros.acao, "criado") : ssrLooseEqual(filtros.acao, "criado")) ? " selected" : ""}${_scopeId}>Criado</option><option value="alterado"${ssrIncludeBooleanAttr(Array.isArray(filtros.acao) ? ssrLooseContain(filtros.acao, "alterado") : ssrLooseEqual(filtros.acao, "alterado")) ? " selected" : ""}${_scopeId}>Alterado</option><option value="apagado"${ssrIncludeBooleanAttr(Array.isArray(filtros.acao) ? ssrLooseContain(filtros.acao, "apagado") : ssrLooseEqual(filtros.acao, "apagado")) ? " selected" : ""}${_scopeId}>Apagado</option></select></label><label${_scopeId}><span class="mb-1 block text-sm font-bold text-slate-700"${_scopeId}>Dados</span><select class="w-full rounded-md border-slate-300"${_scopeId}><option value="todos"${ssrIncludeBooleanAttr(Array.isArray(filtros.modelo) ? ssrLooseContain(filtros.modelo, "todos") : ssrLooseEqual(filtros.modelo, "todos")) ? " selected" : ""}${_scopeId}>Todos</option><!--[-->`);
            ssrRenderList(__props.modelos, (modelo) => {
              _push2(`<option${ssrRenderAttr("value", modelo.value)}${ssrIncludeBooleanAttr(Array.isArray(filtros.modelo) ? ssrLooseContain(filtros.modelo, modelo.value) : ssrLooseEqual(filtros.modelo, modelo.value)) ? " selected" : ""}${_scopeId}>${ssrInterpolate(modelo.label)}</option>`);
            });
            _push2(`<!--]--></select></label><label${_scopeId}><span class="mb-1 block text-sm font-bold text-slate-700"${_scopeId}>Funcionário</span><input${ssrRenderAttr("value", filtros.funcionario)} type="search" class="w-full rounded-md border-slate-300" placeholder="Nome"${_scopeId}></label><button type="submit" class="self-end rounded-md bg-slate-900 px-4 py-2.5 font-black text-white"${_scopeId}> Filtrar </button></form><section class="space-y-4"${_scopeId}><!--[-->`);
            ssrRenderList(__props.logs, (log) => {
              _push2(`<article class="bg-white p-5 shadow-sm ring-1 ring-slate-200"${_scopeId}><div class="flex flex-wrap items-start justify-between gap-3"${_scopeId}><div${_scopeId}><div class="flex flex-wrap items-center gap-2"${_scopeId}><span class="${ssrRenderClass([acaoClasses[log.action] ?? "bg-slate-100 text-slate-700", "rounded-full px-3 py-1 text-xs font-black uppercase"])}"${_scopeId}>${ssrInterpolate(log.action)}</span><strong${_scopeId}>${ssrInterpolate(log.model)} #${ssrInterpolate(log.auditable_id)}</strong>`);
              if (log.auditable_label) {
                _push2(`<span class="text-slate-600"${_scopeId}>· ${ssrInterpolate(log.auditable_label)}</span>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`</div><div class="mt-2 text-sm text-slate-500"${_scopeId}>${ssrInterpolate(log.created_at)} · ${ssrInterpolate(log.actor_name)} · ${ssrInterpolate(log.actor_type)} · ${ssrInterpolate(log.ip)}</div></div>`);
              if (log.url) {
                _push2(`<a${ssrRenderAttr("href", log.url)} class="text-sm font-bold text-slate-700 hover:text-slate-950" target="_blank" rel="noreferrer"${_scopeId}> Abrir origem </a>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`</div><div class="mt-4 overflow-x-auto"${_scopeId}><table class="w-full min-w-[720px] text-left text-sm"${_scopeId}><thead${_scopeId}><tr class="border-b text-xs uppercase text-slate-500"${_scopeId}><th class="py-2 pr-4"${_scopeId}>Campo</th><th class="py-2 pr-4"${_scopeId}>Antes</th><th class="py-2"${_scopeId}>Depois</th></tr></thead><tbody${_scopeId}><!--[-->`);
              ssrRenderList(campos(log), (campo) => {
                _push2(`<tr class="border-b last:border-0"${_scopeId}><td class="py-2 pr-4 font-bold text-slate-700"${_scopeId}>${ssrInterpolate(campo)}</td><td class="max-w-md py-2 pr-4 text-slate-600"${_scopeId}>${ssrInterpolate(valor(log.old_values?.[campo]))}</td><td class="max-w-md py-2 text-slate-900"${_scopeId}>${ssrInterpolate(valor(log.new_values?.[campo]))}</td></tr>`);
              });
              _push2(`<!--]--></tbody></table></div></article>`);
            });
            _push2(`<!--]-->`);
            if (!__props.logs?.length) {
              _push2(`<div class="bg-white p-8 text-center text-sm text-slate-500 shadow-sm ring-1 ring-slate-200"${_scopeId}> Ainda não existem alterações registadas para este filtro. </div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</section>`);
          } else {
            return [
              createVNode("div", { class: "mb-6 flex flex-wrap items-start justify-between gap-4" }, [
                createVNode("div", null, [
                  createVNode("h1", { class: "text-3xl font-black text-slate-950" }, "Logs de alterações"),
                  createVNode("p", { class: "mt-2 max-w-3xl text-sm text-slate-600" }, " Histórico de dados criados, alterados ou apagados por funcionários no backoffice e no POS. ")
                ]),
                createVNode(unref(Link), {
                  href: _ctx.route("manutencao.limpeza.index"),
                  class: "rounded-md bg-slate-900 px-4 py-2 text-sm font-black text-white"
                }, {
                  default: withCtx(() => [
                    createTextVNode(" Limpeza de dados ")
                  ]),
                  _: 1
                }, 8, ["href"])
              ]),
              createVNode("form", {
                class: "mb-6 grid gap-4 bg-white p-5 shadow-sm ring-1 ring-slate-200 lg:grid-cols-[repeat(5,minmax(0,1fr))_auto]",
                onSubmit: withModifiers(carregar, ["prevent"])
              }, [
                createVNode("label", null, [
                  createVNode("span", { class: "mb-1 block text-sm font-bold text-slate-700" }, "Data início"),
                  withDirectives(createVNode("input", {
                    "onUpdate:modelValue": ($event) => filtros.data_inicio = $event,
                    type: "date",
                    class: "w-full rounded-md border-slate-300"
                  }, null, 8, ["onUpdate:modelValue"]), [
                    [vModelText, filtros.data_inicio]
                  ])
                ]),
                createVNode("label", null, [
                  createVNode("span", { class: "mb-1 block text-sm font-bold text-slate-700" }, "Data fim"),
                  withDirectives(createVNode("input", {
                    "onUpdate:modelValue": ($event) => filtros.data_fim = $event,
                    type: "date",
                    class: "w-full rounded-md border-slate-300"
                  }, null, 8, ["onUpdate:modelValue"]), [
                    [vModelText, filtros.data_fim]
                  ])
                ]),
                createVNode("label", null, [
                  createVNode("span", { class: "mb-1 block text-sm font-bold text-slate-700" }, "Ação"),
                  withDirectives(createVNode("select", {
                    "onUpdate:modelValue": ($event) => filtros.acao = $event,
                    class: "w-full rounded-md border-slate-300"
                  }, [
                    createVNode("option", { value: "todas" }, "Todas"),
                    createVNode("option", { value: "criado" }, "Criado"),
                    createVNode("option", { value: "alterado" }, "Alterado"),
                    createVNode("option", { value: "apagado" }, "Apagado")
                  ], 8, ["onUpdate:modelValue"]), [
                    [vModelSelect, filtros.acao]
                  ])
                ]),
                createVNode("label", null, [
                  createVNode("span", { class: "mb-1 block text-sm font-bold text-slate-700" }, "Dados"),
                  withDirectives(createVNode("select", {
                    "onUpdate:modelValue": ($event) => filtros.modelo = $event,
                    class: "w-full rounded-md border-slate-300"
                  }, [
                    createVNode("option", { value: "todos" }, "Todos"),
                    (openBlock(true), createBlock(Fragment, null, renderList(__props.modelos, (modelo) => {
                      return openBlock(), createBlock("option", {
                        key: modelo.value,
                        value: modelo.value
                      }, toDisplayString(modelo.label), 9, ["value"]);
                    }), 128))
                  ], 8, ["onUpdate:modelValue"]), [
                    [vModelSelect, filtros.modelo]
                  ])
                ]),
                createVNode("label", null, [
                  createVNode("span", { class: "mb-1 block text-sm font-bold text-slate-700" }, "Funcionário"),
                  withDirectives(createVNode("input", {
                    "onUpdate:modelValue": ($event) => filtros.funcionario = $event,
                    type: "search",
                    class: "w-full rounded-md border-slate-300",
                    placeholder: "Nome"
                  }, null, 8, ["onUpdate:modelValue"]), [
                    [vModelText, filtros.funcionario]
                  ])
                ]),
                createVNode("button", {
                  type: "submit",
                  class: "self-end rounded-md bg-slate-900 px-4 py-2.5 font-black text-white"
                }, " Filtrar ")
              ], 32),
              createVNode("section", { class: "space-y-4" }, [
                (openBlock(true), createBlock(Fragment, null, renderList(__props.logs, (log) => {
                  return openBlock(), createBlock("article", {
                    key: log.id,
                    class: "bg-white p-5 shadow-sm ring-1 ring-slate-200"
                  }, [
                    createVNode("div", { class: "flex flex-wrap items-start justify-between gap-3" }, [
                      createVNode("div", null, [
                        createVNode("div", { class: "flex flex-wrap items-center gap-2" }, [
                          createVNode("span", {
                            class: ["rounded-full px-3 py-1 text-xs font-black uppercase", acaoClasses[log.action] ?? "bg-slate-100 text-slate-700"]
                          }, toDisplayString(log.action), 3),
                          createVNode("strong", null, toDisplayString(log.model) + " #" + toDisplayString(log.auditable_id), 1),
                          log.auditable_label ? (openBlock(), createBlock("span", {
                            key: 0,
                            class: "text-slate-600"
                          }, "· " + toDisplayString(log.auditable_label), 1)) : createCommentVNode("", true)
                        ]),
                        createVNode("div", { class: "mt-2 text-sm text-slate-500" }, toDisplayString(log.created_at) + " · " + toDisplayString(log.actor_name) + " · " + toDisplayString(log.actor_type) + " · " + toDisplayString(log.ip), 1)
                      ]),
                      log.url ? (openBlock(), createBlock("a", {
                        key: 0,
                        href: log.url,
                        class: "text-sm font-bold text-slate-700 hover:text-slate-950",
                        target: "_blank",
                        rel: "noreferrer"
                      }, " Abrir origem ", 8, ["href"])) : createCommentVNode("", true)
                    ]),
                    createVNode("div", { class: "mt-4 overflow-x-auto" }, [
                      createVNode("table", { class: "w-full min-w-[720px] text-left text-sm" }, [
                        createVNode("thead", null, [
                          createVNode("tr", { class: "border-b text-xs uppercase text-slate-500" }, [
                            createVNode("th", { class: "py-2 pr-4" }, "Campo"),
                            createVNode("th", { class: "py-2 pr-4" }, "Antes"),
                            createVNode("th", { class: "py-2" }, "Depois")
                          ])
                        ]),
                        createVNode("tbody", null, [
                          (openBlock(true), createBlock(Fragment, null, renderList(campos(log), (campo) => {
                            return openBlock(), createBlock("tr", {
                              key: campo,
                              class: "border-b last:border-0"
                            }, [
                              createVNode("td", { class: "py-2 pr-4 font-bold text-slate-700" }, toDisplayString(campo), 1),
                              createVNode("td", { class: "max-w-md py-2 pr-4 text-slate-600" }, toDisplayString(valor(log.old_values?.[campo])), 1),
                              createVNode("td", { class: "max-w-md py-2 text-slate-900" }, toDisplayString(valor(log.new_values?.[campo])), 1)
                            ]);
                          }), 128))
                        ])
                      ])
                    ])
                  ]);
                }), 128)),
                !__props.logs?.length ? (openBlock(), createBlock("div", {
                  key: 0,
                  class: "bg-white p-8 text-center text-sm text-slate-500 shadow-sm ring-1 ring-slate-200"
                }, " Ainda não existem alterações registadas para este filtro. ")) : createCommentVNode("", true)
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Manutencao/Logs.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
