import { reactive, watch, withCtx, unref, createTextVNode, createVNode, withModifiers, withDirectives, vModelText, vModelSelect, vModelCheckbox, toDisplayString, openBlock, createBlock, createCommentVNode, Fragment, renderList, useSSRContext } from "vue";
import { ssrRenderComponent, ssrRenderAttr, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual, ssrInterpolate, ssrRenderList } from "vue/server-renderer";
import { useForm, Link, router } from "@inertiajs/vue3";
import { _ as _sfc_main$1 } from "./AppLayout-BWDcck4N.js";
const _sfc_main = {
  __name: "Limpeza",
  __ssrInlineRender: true,
  props: {
    filters: Object,
    preview: Object
  },
  setup(__props) {
    const props = __props;
    const filtros = reactive({
      data_inicio: props.filters?.data_inicio,
      data_fim: props.filters?.data_fim,
      tipo: props.filters?.tipo ?? "ambos",
      manter_pedido_id: props.filters?.manter_pedido_id ?? "",
      apenas_relatorios: props.filters?.apenas_relatorios ?? true
    });
    const form = useForm({
      ...filtros,
      confirmacao: ""
    });
    watch(filtros, () => {
      form.data_inicio = filtros.data_inicio;
      form.data_fim = filtros.data_fim;
      form.tipo = filtros.tipo;
      form.manter_pedido_id = filtros.manter_pedido_id;
      form.apenas_relatorios = filtros.apenas_relatorios;
    }, { deep: true });
    const euros = (valor) => Number(valor ?? 0).toLocaleString("pt-PT", { style: "currency", currency: "EUR" });
    const atualizarPreview = () => {
      router.get(route("manutencao.limpeza.index"), filtros, {
        preserveState: true,
        preserveScroll: true
      });
    };
    const apagar = () => {
      form.delete(route("manutencao.limpeza.destroy"), {
        preserveScroll: true,
        onSuccess: () => {
          form.confirmacao = "";
          atualizarPreview();
        }
      });
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(_sfc_main$1, _attrs, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="mb-6 flex flex-wrap items-start justify-between gap-4"${_scopeId}><div${_scopeId}><h1 class="text-3xl font-black text-slate-950"${_scopeId}>Limpeza de dados</h1><p class="mt-2 max-w-3xl text-sm text-slate-600"${_scopeId}> Remove dados operacionais que alimentam relatórios. Usa primeiro a pré-visualização e mantém o pedido correto pelo número. </p></div>`);
            _push2(ssrRenderComponent(unref(Link), {
              href: _ctx.route("manutencao.logs.index"),
              class: "rounded-md bg-slate-900 px-4 py-2 text-sm font-black text-white"
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` Ver logs `);
                } else {
                  return [
                    createTextVNode(" Ver logs ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`</div><div class="mb-6 border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950"${_scopeId}> Esta ação é destrutiva em produção. Faz backup antes de apagar e confirma que o pedido correto está indicado no campo “Manter pedido”. </div><section class="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]"${_scopeId}><form class="space-y-4 bg-white p-5 shadow-sm ring-1 ring-slate-200"${_scopeId}><h2 class="text-lg font-black"${_scopeId}>Filtros</h2><div class="grid gap-4 sm:grid-cols-2"${_scopeId}><label class="block"${_scopeId}><span class="mb-1 block text-sm font-bold text-slate-700"${_scopeId}>Data início</span><input${ssrRenderAttr("value", filtros.data_inicio)} type="date" class="w-full rounded-md border-slate-300"${_scopeId}></label><label class="block"${_scopeId}><span class="mb-1 block text-sm font-bold text-slate-700"${_scopeId}>Data fim</span><input${ssrRenderAttr("value", filtros.data_fim)} type="date" class="w-full rounded-md border-slate-300"${_scopeId}></label></div><label class="block"${_scopeId}><span class="mb-1 block text-sm font-bold text-slate-700"${_scopeId}>O que apagar</span><select class="w-full rounded-md border-slate-300"${_scopeId}><option value="ambos"${ssrIncludeBooleanAttr(Array.isArray(filtros.tipo) ? ssrLooseContain(filtros.tipo, "ambos") : ssrLooseEqual(filtros.tipo, "ambos")) ? " selected" : ""}${_scopeId}>Pedidos e caixas</option><option value="pedidos"${ssrIncludeBooleanAttr(Array.isArray(filtros.tipo) ? ssrLooseContain(filtros.tipo, "pedidos") : ssrLooseEqual(filtros.tipo, "pedidos")) ? " selected" : ""}${_scopeId}>Só pedidos</option><option value="caixas"${ssrIncludeBooleanAttr(Array.isArray(filtros.tipo) ? ssrLooseContain(filtros.tipo, "caixas") : ssrLooseEqual(filtros.tipo, "caixas")) ? " selected" : ""}${_scopeId}>Só caixas</option></select></label><label class="block"${_scopeId}><span class="mb-1 block text-sm font-bold text-slate-700"${_scopeId}>Manter pedido</span><input${ssrRenderAttr("value", filtros.manter_pedido_id)} type="number" min="1" placeholder="Ex.: 23" class="w-full rounded-md border-slate-300"${_scopeId}><span class="mt-1 block text-xs text-slate-500"${_scopeId}>Este pedido não será apagado, mesmo estando dentro do período.</span></label><label class="flex items-start gap-3 rounded-md bg-slate-50 p-3 text-sm"${_scopeId}><input${ssrIncludeBooleanAttr(Array.isArray(filtros.apenas_relatorios) ? ssrLooseContain(filtros.apenas_relatorios, null) : filtros.apenas_relatorios) ? " checked" : ""} type="checkbox" class="mt-1 rounded border-slate-300"${_scopeId}><span${_scopeId}><strong class="block text-slate-900"${_scopeId}>Apagar apenas pedidos que entram nos relatórios</strong><span class="text-slate-600"${_scopeId}>Recomendado: limita aos pedidos entregues ou pré-pagos.</span></span></label><button type="submit" class="w-full rounded-md bg-slate-900 px-4 py-3 font-black text-white"${_scopeId}> Atualizar pré-visualização </button></form><div class="space-y-6"${_scopeId}><div class="grid gap-4 md:grid-cols-2"${_scopeId}><article class="bg-white p-5 shadow-sm ring-1 ring-slate-200"${_scopeId}><div class="text-sm font-bold uppercase text-slate-500"${_scopeId}>Pedidos a apagar</div><div class="mt-2 text-4xl font-black"${_scopeId}>${ssrInterpolate(__props.preview?.pedidos?.count ?? 0)}</div><div class="mt-2 text-sm text-slate-600"${_scopeId}>${ssrInterpolate(__props.preview?.pedidos?.items ?? 0)} itens · ${ssrInterpolate(euros(__props.preview?.pedidos?.total))}</div></article><article class="bg-white p-5 shadow-sm ring-1 ring-slate-200"${_scopeId}><div class="text-sm font-bold uppercase text-slate-500"${_scopeId}>Caixas a apagar</div><div class="mt-2 text-4xl font-black"${_scopeId}>${ssrInterpolate(__props.preview?.caixas?.count ?? 0)}</div><div class="mt-2 text-sm text-slate-600"${_scopeId}>Fundo de maneio: ${ssrInterpolate(euros(__props.preview?.caixas?.fundo_maneio))}</div></article></div><section class="bg-white p-5 shadow-sm ring-1 ring-slate-200"${_scopeId}><h2 class="mb-3 text-lg font-black"${_scopeId}>Pedidos encontrados</h2>`);
            if (!__props.preview?.pedidos?.samples?.length) {
              _push2(`<div class="text-sm text-slate-500"${_scopeId}>Nenhum pedido neste filtro.</div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`<!--[-->`);
            ssrRenderList(__props.preview?.pedidos?.samples, (pedido) => {
              _push2(`<div class="grid gap-2 border-t py-3 text-sm md:grid-cols-[5rem_1fr_1fr_1fr]"${_scopeId}><strong${_scopeId}>#${ssrInterpolate(pedido.id)}</strong><span${_scopeId}>${ssrInterpolate(pedido.created_at)} · ${ssrInterpolate(pedido.ponto)}</span><span${_scopeId}>${ssrInterpolate(pedido.tipo)} · ${ssrInterpolate(pedido.estado)}</span><strong class="md:text-right"${_scopeId}>${ssrInterpolate(euros(pedido.total))}</strong></div>`);
            });
            _push2(`<!--]--></section><section class="bg-white p-5 shadow-sm ring-1 ring-slate-200"${_scopeId}><h2 class="mb-3 text-lg font-black"${_scopeId}>Caixas encontradas</h2>`);
            if (!__props.preview?.caixas?.samples?.length) {
              _push2(`<div class="text-sm text-slate-500"${_scopeId}>Nenhuma caixa neste filtro.</div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`<!--[-->`);
            ssrRenderList(__props.preview?.caixas?.samples, (caixa) => {
              _push2(`<div class="grid gap-2 border-t py-3 text-sm md:grid-cols-[1fr_1fr_1fr_1fr]"${_scopeId}><strong${_scopeId}>${ssrInterpolate(caixa.data)}</strong><span${_scopeId}>${ssrInterpolate(caixa.ponto)}</span><span${_scopeId}>${ssrInterpolate(caixa.estado)}</span><strong class="md:text-right"${_scopeId}>${ssrInterpolate(euros(caixa.fundo_maneio))}</strong></div>`);
            });
            _push2(`<!--]--></section><form class="border border-red-300 bg-red-50 p-5"${_scopeId}><h2 class="text-lg font-black text-red-950"${_scopeId}>Confirmar apagamento</h2><p class="mt-2 text-sm text-red-900"${_scopeId}> Para apagar os dados desta pré-visualização, escreve exatamente <strong${_scopeId}>APAGAR DADOS</strong>. </p><input${ssrRenderAttr("value", unref(form).confirmacao)} type="text" class="mt-4 w-full rounded-md border-red-300" placeholder="APAGAR DADOS"${_scopeId}>`);
            if (unref(form).errors.confirmacao) {
              _push2(`<div class="mt-2 text-sm font-bold text-red-700"${_scopeId}>${ssrInterpolate(unref(form).errors.confirmacao)}</div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`<button type="submit" class="mt-4 w-full rounded-md bg-red-700 px-4 py-3 font-black text-white disabled:opacity-50"${ssrIncludeBooleanAttr(unref(form).processing) ? " disabled" : ""}${_scopeId}>${ssrInterpolate(unref(form).processing ? "A apagar..." : "Apagar dados selecionados")}</button></form></div></section>`);
          } else {
            return [
              createVNode("div", { class: "mb-6 flex flex-wrap items-start justify-between gap-4" }, [
                createVNode("div", null, [
                  createVNode("h1", { class: "text-3xl font-black text-slate-950" }, "Limpeza de dados"),
                  createVNode("p", { class: "mt-2 max-w-3xl text-sm text-slate-600" }, " Remove dados operacionais que alimentam relatórios. Usa primeiro a pré-visualização e mantém o pedido correto pelo número. ")
                ]),
                createVNode(unref(Link), {
                  href: _ctx.route("manutencao.logs.index"),
                  class: "rounded-md bg-slate-900 px-4 py-2 text-sm font-black text-white"
                }, {
                  default: withCtx(() => [
                    createTextVNode(" Ver logs ")
                  ]),
                  _: 1
                }, 8, ["href"])
              ]),
              createVNode("div", { class: "mb-6 border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950" }, " Esta ação é destrutiva em produção. Faz backup antes de apagar e confirma que o pedido correto está indicado no campo “Manter pedido”. "),
              createVNode("section", { class: "grid gap-6 xl:grid-cols-[0.9fr_1.1fr]" }, [
                createVNode("form", {
                  class: "space-y-4 bg-white p-5 shadow-sm ring-1 ring-slate-200",
                  onSubmit: withModifiers(atualizarPreview, ["prevent"])
                }, [
                  createVNode("h2", { class: "text-lg font-black" }, "Filtros"),
                  createVNode("div", { class: "grid gap-4 sm:grid-cols-2" }, [
                    createVNode("label", { class: "block" }, [
                      createVNode("span", { class: "mb-1 block text-sm font-bold text-slate-700" }, "Data início"),
                      withDirectives(createVNode("input", {
                        "onUpdate:modelValue": ($event) => filtros.data_inicio = $event,
                        type: "date",
                        class: "w-full rounded-md border-slate-300"
                      }, null, 8, ["onUpdate:modelValue"]), [
                        [vModelText, filtros.data_inicio]
                      ])
                    ]),
                    createVNode("label", { class: "block" }, [
                      createVNode("span", { class: "mb-1 block text-sm font-bold text-slate-700" }, "Data fim"),
                      withDirectives(createVNode("input", {
                        "onUpdate:modelValue": ($event) => filtros.data_fim = $event,
                        type: "date",
                        class: "w-full rounded-md border-slate-300"
                      }, null, 8, ["onUpdate:modelValue"]), [
                        [vModelText, filtros.data_fim]
                      ])
                    ])
                  ]),
                  createVNode("label", { class: "block" }, [
                    createVNode("span", { class: "mb-1 block text-sm font-bold text-slate-700" }, "O que apagar"),
                    withDirectives(createVNode("select", {
                      "onUpdate:modelValue": ($event) => filtros.tipo = $event,
                      class: "w-full rounded-md border-slate-300"
                    }, [
                      createVNode("option", { value: "ambos" }, "Pedidos e caixas"),
                      createVNode("option", { value: "pedidos" }, "Só pedidos"),
                      createVNode("option", { value: "caixas" }, "Só caixas")
                    ], 8, ["onUpdate:modelValue"]), [
                      [vModelSelect, filtros.tipo]
                    ])
                  ]),
                  createVNode("label", { class: "block" }, [
                    createVNode("span", { class: "mb-1 block text-sm font-bold text-slate-700" }, "Manter pedido"),
                    withDirectives(createVNode("input", {
                      "onUpdate:modelValue": ($event) => filtros.manter_pedido_id = $event,
                      type: "number",
                      min: "1",
                      placeholder: "Ex.: 23",
                      class: "w-full rounded-md border-slate-300"
                    }, null, 8, ["onUpdate:modelValue"]), [
                      [vModelText, filtros.manter_pedido_id]
                    ]),
                    createVNode("span", { class: "mt-1 block text-xs text-slate-500" }, "Este pedido não será apagado, mesmo estando dentro do período.")
                  ]),
                  createVNode("label", { class: "flex items-start gap-3 rounded-md bg-slate-50 p-3 text-sm" }, [
                    withDirectives(createVNode("input", {
                      "onUpdate:modelValue": ($event) => filtros.apenas_relatorios = $event,
                      type: "checkbox",
                      class: "mt-1 rounded border-slate-300"
                    }, null, 8, ["onUpdate:modelValue"]), [
                      [vModelCheckbox, filtros.apenas_relatorios]
                    ]),
                    createVNode("span", null, [
                      createVNode("strong", { class: "block text-slate-900" }, "Apagar apenas pedidos que entram nos relatórios"),
                      createVNode("span", { class: "text-slate-600" }, "Recomendado: limita aos pedidos entregues ou pré-pagos.")
                    ])
                  ]),
                  createVNode("button", {
                    type: "submit",
                    class: "w-full rounded-md bg-slate-900 px-4 py-3 font-black text-white"
                  }, " Atualizar pré-visualização ")
                ], 32),
                createVNode("div", { class: "space-y-6" }, [
                  createVNode("div", { class: "grid gap-4 md:grid-cols-2" }, [
                    createVNode("article", { class: "bg-white p-5 shadow-sm ring-1 ring-slate-200" }, [
                      createVNode("div", { class: "text-sm font-bold uppercase text-slate-500" }, "Pedidos a apagar"),
                      createVNode("div", { class: "mt-2 text-4xl font-black" }, toDisplayString(__props.preview?.pedidos?.count ?? 0), 1),
                      createVNode("div", { class: "mt-2 text-sm text-slate-600" }, toDisplayString(__props.preview?.pedidos?.items ?? 0) + " itens · " + toDisplayString(euros(__props.preview?.pedidos?.total)), 1)
                    ]),
                    createVNode("article", { class: "bg-white p-5 shadow-sm ring-1 ring-slate-200" }, [
                      createVNode("div", { class: "text-sm font-bold uppercase text-slate-500" }, "Caixas a apagar"),
                      createVNode("div", { class: "mt-2 text-4xl font-black" }, toDisplayString(__props.preview?.caixas?.count ?? 0), 1),
                      createVNode("div", { class: "mt-2 text-sm text-slate-600" }, "Fundo de maneio: " + toDisplayString(euros(__props.preview?.caixas?.fundo_maneio)), 1)
                    ])
                  ]),
                  createVNode("section", { class: "bg-white p-5 shadow-sm ring-1 ring-slate-200" }, [
                    createVNode("h2", { class: "mb-3 text-lg font-black" }, "Pedidos encontrados"),
                    !__props.preview?.pedidos?.samples?.length ? (openBlock(), createBlock("div", {
                      key: 0,
                      class: "text-sm text-slate-500"
                    }, "Nenhum pedido neste filtro.")) : createCommentVNode("", true),
                    (openBlock(true), createBlock(Fragment, null, renderList(__props.preview?.pedidos?.samples, (pedido) => {
                      return openBlock(), createBlock("div", {
                        key: pedido.id,
                        class: "grid gap-2 border-t py-3 text-sm md:grid-cols-[5rem_1fr_1fr_1fr]"
                      }, [
                        createVNode("strong", null, "#" + toDisplayString(pedido.id), 1),
                        createVNode("span", null, toDisplayString(pedido.created_at) + " · " + toDisplayString(pedido.ponto), 1),
                        createVNode("span", null, toDisplayString(pedido.tipo) + " · " + toDisplayString(pedido.estado), 1),
                        createVNode("strong", { class: "md:text-right" }, toDisplayString(euros(pedido.total)), 1)
                      ]);
                    }), 128))
                  ]),
                  createVNode("section", { class: "bg-white p-5 shadow-sm ring-1 ring-slate-200" }, [
                    createVNode("h2", { class: "mb-3 text-lg font-black" }, "Caixas encontradas"),
                    !__props.preview?.caixas?.samples?.length ? (openBlock(), createBlock("div", {
                      key: 0,
                      class: "text-sm text-slate-500"
                    }, "Nenhuma caixa neste filtro.")) : createCommentVNode("", true),
                    (openBlock(true), createBlock(Fragment, null, renderList(__props.preview?.caixas?.samples, (caixa) => {
                      return openBlock(), createBlock("div", {
                        key: caixa.id,
                        class: "grid gap-2 border-t py-3 text-sm md:grid-cols-[1fr_1fr_1fr_1fr]"
                      }, [
                        createVNode("strong", null, toDisplayString(caixa.data), 1),
                        createVNode("span", null, toDisplayString(caixa.ponto), 1),
                        createVNode("span", null, toDisplayString(caixa.estado), 1),
                        createVNode("strong", { class: "md:text-right" }, toDisplayString(euros(caixa.fundo_maneio)), 1)
                      ]);
                    }), 128))
                  ]),
                  createVNode("form", {
                    class: "border border-red-300 bg-red-50 p-5",
                    onSubmit: withModifiers(apagar, ["prevent"])
                  }, [
                    createVNode("h2", { class: "text-lg font-black text-red-950" }, "Confirmar apagamento"),
                    createVNode("p", { class: "mt-2 text-sm text-red-900" }, [
                      createTextVNode(" Para apagar os dados desta pré-visualização, escreve exatamente "),
                      createVNode("strong", null, "APAGAR DADOS"),
                      createTextVNode(". ")
                    ]),
                    withDirectives(createVNode("input", {
                      "onUpdate:modelValue": ($event) => unref(form).confirmacao = $event,
                      type: "text",
                      class: "mt-4 w-full rounded-md border-red-300",
                      placeholder: "APAGAR DADOS"
                    }, null, 8, ["onUpdate:modelValue"]), [
                      [vModelText, unref(form).confirmacao]
                    ]),
                    unref(form).errors.confirmacao ? (openBlock(), createBlock("div", {
                      key: 0,
                      class: "mt-2 text-sm font-bold text-red-700"
                    }, toDisplayString(unref(form).errors.confirmacao), 1)) : createCommentVNode("", true),
                    createVNode("button", {
                      type: "submit",
                      class: "mt-4 w-full rounded-md bg-red-700 px-4 py-3 font-black text-white disabled:opacity-50",
                      disabled: unref(form).processing
                    }, toDisplayString(unref(form).processing ? "A apagar..." : "Apagar dados selecionados"), 9, ["disabled"])
                  ], 32)
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Manutencao/Limpeza.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
