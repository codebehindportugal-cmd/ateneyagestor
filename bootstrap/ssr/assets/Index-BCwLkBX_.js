import { computed, ref, reactive, withCtx, unref, createVNode, toDisplayString, withModifiers, withDirectives, vModelText, openBlock, createBlock, Fragment, renderList, vModelSelect, createCommentVNode, useSSRContext } from "vue";
import { ssrRenderComponent, ssrInterpolate, ssrRenderAttr, ssrRenderList, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual, ssrRenderClass } from "vue/server-renderer";
import { _ as _sfc_main$1 } from "./AppLayout-BWDcck4N.js";
import { useForm, router } from "@inertiajs/vue3";
const _sfc_main = {
  __name: "Index",
  __ssrInlineRender: true,
  props: {
    produtosOptions: Array,
    faturasRecentes: Array
  },
  setup(__props) {
    const props = __props;
    const hoje = (/* @__PURE__ */ new Date()).toISOString().slice(0, 10);
    const linhaNova = () => ({
      produto_id: props.produtosOptions?.[0]?.id ?? "",
      quantidade: 1,
      preco_unitario: ""
    });
    const form = useForm({
      fornecedor: "",
      numero: "",
      data_fatura: hoje,
      items: [linhaNova()]
    });
    const totalFatura = computed(() => form.items.reduce((total, item) => {
      const quantidade = Number(item.quantidade) || 0;
      const preco = Number(item.preco_unitario) || 0;
      return total + quantidade * preco;
    }, 0));
    const adicionarLinha = () => {
      form.items.push(linhaNova());
    };
    const removerLinha = (index) => {
      if (form.items.length > 1) {
        form.items.splice(index, 1);
      }
    };
    const registarFatura = () => {
      form.transform((dados) => ({
        ...dados,
        data: dados.data_fatura
      })).post(route("faturas-compras.store"), {
        preserveScroll: true,
        onSuccess: () => {
          form.reset("fornecedor", "numero");
          form.data_fatura = hoje;
          form.items = [linhaNova()];
        },
        onFinish: () => form.transform((dados) => dados)
      });
    };
    const toggling = ref({});
    const togglePago = (fatura) => {
      if (toggling.value[fatura.id]) return;
      toggling.value[fatura.id] = true;
      router.patch(route("faturas-compras.pagar", fatura.id), {}, {
        preserveScroll: true,
        onFinish: () => {
          toggling.value[fatura.id] = false;
        }
      });
    };
    const paineisAbertos = reactive({});
    const devolucoesState = reactive({});
    const submitting = ref({});
    const abrirPainelDevolucao = (fatura) => {
      if (!paineisAbertos[fatura.id]) {
        devolucoesState[fatura.id] = {};
        fatura.items.forEach((item) => {
          devolucoesState[fatura.id][item.id] = Number(item.quantidade_devolvida) || 0;
        });
      }
      paineisAbertos[fatura.id] = !paineisAbertos[fatura.id];
    };
    const submeterDevolucao = (fatura) => {
      if (submitting.value[fatura.id]) return;
      submitting.value[fatura.id] = true;
      const items = fatura.items.map((item) => ({
        id: item.id,
        quantidade_devolvida: Number(devolucoesState[fatura.id]?.[item.id] ?? 0)
      }));
      router.post(route("faturas-compras.devolver", fatura.id), { items }, {
        preserveScroll: true,
        onSuccess: () => {
          paineisAbertos[fatura.id] = false;
        },
        onFinish: () => {
          submitting.value[fatura.id] = false;
        }
      });
    };
    const formatarMoeda = (valor, casas = 2) => Number(valor || 0).toLocaleString("pt-PT", {
      style: "currency",
      currency: "EUR",
      minimumFractionDigits: casas,
      maximumFractionDigits: casas
    });
    const formatarQuantidade = (valor) => Number(valor || 0).toLocaleString("pt-PT", {
      maximumFractionDigits: 3
    });
    const formatarData = (data) => {
      const dataNormalizada = String(data).split("T")[0];
      return (/* @__PURE__ */ new Date(`${dataNormalizada}T00:00:00`)).toLocaleDateString("pt-PT");
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(_sfc_main$1, _attrs, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="mb-6"${_scopeId}><h1 class="text-2xl font-bold"${_scopeId}>Faturas e stock</h1><p class="mt-1 text-sm text-slate-500"${_scopeId}>Registe compras e atualize o stock.</p></div><section class="mb-6 rounded-lg bg-white p-5 shadow-sm"${_scopeId}><div class="mb-4 flex flex-wrap items-end justify-between gap-3"${_scopeId}><div${_scopeId}><h2 class="text-lg font-bold"${_scopeId}>Nova fatura</h2><p class="text-sm text-slate-500"${_scopeId}>Ao gravar, as quantidades entram no stock e contam para as Contas Festa.</p></div><div class="text-right"${_scopeId}><p class="text-xs uppercase text-slate-500"${_scopeId}>Total</p><p class="text-xl font-bold"${_scopeId}>${ssrInterpolate(formatarMoeda(totalFatura.value))}</p></div></div><form class="space-y-4"${_scopeId}><div class="grid gap-3 md:grid-cols-[1fr_160px_160px]"${_scopeId}><input${ssrRenderAttr("value", unref(form).fornecedor)} class="rounded-md border-slate-300 text-sm" placeholder="Fornecedor"${_scopeId}><input${ssrRenderAttr("value", unref(form).numero)} class="rounded-md border-slate-300 text-sm" placeholder="N. da fatura"${_scopeId}><input${ssrRenderAttr("value", unref(form).data_fatura)} type="date" class="rounded-md border-slate-300 text-sm"${_scopeId}></div><div class="space-y-2"${_scopeId}><!--[-->`);
            ssrRenderList(unref(form).items, (item, index) => {
              _push2(`<div class="grid gap-2 rounded-md border border-slate-200 p-3 md:grid-cols-[1fr_130px_150px_90px]"${_scopeId}><select class="rounded-md border-slate-300 text-sm"${_scopeId}><!--[-->`);
              ssrRenderList(__props.produtosOptions, (produto) => {
                _push2(`<option${ssrRenderAttr("value", produto.id)}${ssrIncludeBooleanAttr(Array.isArray(item.produto_id) ? ssrLooseContain(item.produto_id, produto.id) : ssrLooseEqual(item.produto_id, produto.id)) ? " selected" : ""}${_scopeId}>${ssrInterpolate(produto.nome)} - stock ${ssrInterpolate(formatarQuantidade(produto.stock_atual))}</option>`);
              });
              _push2(`<!--]--></select><input${ssrRenderAttr("value", item.quantidade)} type="number" min="0.001" step="0.001" class="rounded-md border-slate-300 text-sm" placeholder="Qtd."${_scopeId}><input${ssrRenderAttr("value", item.preco_unitario)} type="number" min="0" step="0.01" class="rounded-md border-slate-300 text-sm" placeholder="Preco unit."${_scopeId}><button type="button" class="rounded-md border border-red-200 px-3 py-2 text-sm font-semibold text-red-700 disabled:opacity-40"${ssrIncludeBooleanAttr(unref(form).items.length === 1) ? " disabled" : ""}${_scopeId}> Remover </button></div>`);
            });
            _push2(`<!--]--></div>`);
            if (Object.keys(unref(form).errors).length) {
              _push2(`<div class="rounded-md bg-red-50 p-3 text-sm text-red-700"${_scopeId}><!--[-->`);
              ssrRenderList(unref(form).errors, (erro) => {
                _push2(`<div${_scopeId}>${ssrInterpolate(erro)}</div>`);
              });
              _push2(`<!--]--></div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`<div class="flex flex-wrap justify-between gap-3"${_scopeId}><button type="button" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700"${_scopeId}> Adicionar linha </button><button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white disabled:opacity-60"${ssrIncludeBooleanAttr(unref(form).processing || !__props.produtosOptions?.length) ? " disabled" : ""}${_scopeId}>${ssrInterpolate(unref(form).processing ? "A registar..." : "Registar fatura")}</button></div></form></section><section class="rounded-lg bg-white p-5 shadow-sm"${_scopeId}><h2 class="mb-4 text-lg font-bold"${_scopeId}>Faturas recentes</h2>`);
            if (!__props.faturasRecentes?.length) {
              _push2(`<div class="rounded-md bg-slate-50 p-6 text-center text-sm text-slate-500"${_scopeId}> Ainda nao ha faturas registadas. </div>`);
            } else {
              _push2(`<div class="grid gap-3 lg:grid-cols-2"${_scopeId}><!--[-->`);
              ssrRenderList(__props.faturasRecentes, (fatura) => {
                _push2(`<div class="rounded-md border border-slate-200 text-sm"${_scopeId}><div class="flex flex-wrap items-start justify-between gap-2 p-4"${_scopeId}><div class="min-w-0"${_scopeId}><p class="font-semibold"${_scopeId}>${ssrInterpolate(fatura.fornecedor || "Sem fornecedor")}</p><p class="mt-0.5 text-xs text-slate-500"${_scopeId}>${ssrInterpolate(fatura.numero || "Sem numero")} · ${ssrInterpolate(formatarData(fatura.data))}</p><p class="mt-2 text-xs text-slate-600"${_scopeId}>${ssrInterpolate(fatura.items.map((item) => `${item.produto?.nome} (${formatarQuantidade(item.quantidade)})`).join(", "))}</p></div><div class="flex shrink-0 flex-col items-end gap-2"${_scopeId}><p class="font-bold"${_scopeId}>${ssrInterpolate(formatarMoeda(fatura.total))}</p><button class="${ssrRenderClass([fatura.pago ? "bg-green-100 text-green-700" : "bg-amber-100 text-amber-700", "rounded-full px-2.5 py-0.5 text-xs font-semibold transition-opacity disabled:opacity-60"])}"${ssrIncludeBooleanAttr(toggling.value[fatura.id]) ? " disabled" : ""}${_scopeId}>${ssrInterpolate(fatura.pago ? "Pago" : "Por pagar")}</button></div></div><div class="border-t border-slate-100 px-4 py-2"${_scopeId}><button class="text-xs font-semibold text-slate-500 hover:text-slate-800"${_scopeId}>${ssrInterpolate(paineisAbertos[fatura.id] ? "Fechar devolucoes" : "Registar devolucoes ao fornecedor")}</button></div>`);
                if (paineisAbertos[fatura.id]) {
                  _push2(`<div class="border-t border-slate-100 bg-slate-50 p-4"${_scopeId}><p class="mb-3 text-xs text-slate-500"${_scopeId}>Indique a quantidade devolvida por linha. O stock sera reduzido correspondentemente.</p><div class="space-y-2"${_scopeId}><!--[-->`);
                  ssrRenderList(fatura.items, (item) => {
                    _push2(`<div class="flex items-center gap-3"${_scopeId}><span class="flex-1 truncate text-xs"${_scopeId}>${ssrInterpolate(item.produto?.nome)}</span><span class="shrink-0 text-xs text-slate-500"${_scopeId}>comprado: ${ssrInterpolate(formatarQuantidade(item.quantidade))}</span>`);
                    if (devolucoesState[fatura.id]) {
                      _push2(`<input${ssrRenderAttr("value", devolucoesState[fatura.id][item.id])} type="number" min="0"${ssrRenderAttr("max", Number(item.quantidade))} step="0.001" class="w-24 rounded-md border-slate-300 text-xs" placeholder="Devolvido"${_scopeId}>`);
                    } else {
                      _push2(`<!---->`);
                    }
                    _push2(`</div>`);
                  });
                  _push2(`<!--]--></div><div class="mt-3 flex justify-end"${_scopeId}><button class="rounded-md bg-slate-700 px-3 py-1.5 text-xs font-semibold text-white disabled:opacity-60"${ssrIncludeBooleanAttr(submitting.value[fatura.id]) ? " disabled" : ""}${_scopeId}>${ssrInterpolate(submitting.value[fatura.id] ? "A guardar..." : "Guardar devolucoes")}</button></div></div>`);
                } else {
                  _push2(`<!---->`);
                }
                _push2(`</div>`);
              });
              _push2(`<!--]--></div>`);
            }
            _push2(`</section>`);
          } else {
            return [
              createVNode("div", { class: "mb-6" }, [
                createVNode("h1", { class: "text-2xl font-bold" }, "Faturas e stock"),
                createVNode("p", { class: "mt-1 text-sm text-slate-500" }, "Registe compras e atualize o stock.")
              ]),
              createVNode("section", { class: "mb-6 rounded-lg bg-white p-5 shadow-sm" }, [
                createVNode("div", { class: "mb-4 flex flex-wrap items-end justify-between gap-3" }, [
                  createVNode("div", null, [
                    createVNode("h2", { class: "text-lg font-bold" }, "Nova fatura"),
                    createVNode("p", { class: "text-sm text-slate-500" }, "Ao gravar, as quantidades entram no stock e contam para as Contas Festa.")
                  ]),
                  createVNode("div", { class: "text-right" }, [
                    createVNode("p", { class: "text-xs uppercase text-slate-500" }, "Total"),
                    createVNode("p", { class: "text-xl font-bold" }, toDisplayString(formatarMoeda(totalFatura.value)), 1)
                  ])
                ]),
                createVNode("form", {
                  class: "space-y-4",
                  onSubmit: withModifiers(registarFatura, ["prevent"])
                }, [
                  createVNode("div", { class: "grid gap-3 md:grid-cols-[1fr_160px_160px]" }, [
                    withDirectives(createVNode("input", {
                      "onUpdate:modelValue": ($event) => unref(form).fornecedor = $event,
                      class: "rounded-md border-slate-300 text-sm",
                      placeholder: "Fornecedor"
                    }, null, 8, ["onUpdate:modelValue"]), [
                      [vModelText, unref(form).fornecedor]
                    ]),
                    withDirectives(createVNode("input", {
                      "onUpdate:modelValue": ($event) => unref(form).numero = $event,
                      class: "rounded-md border-slate-300 text-sm",
                      placeholder: "N. da fatura"
                    }, null, 8, ["onUpdate:modelValue"]), [
                      [vModelText, unref(form).numero]
                    ]),
                    withDirectives(createVNode("input", {
                      "onUpdate:modelValue": ($event) => unref(form).data_fatura = $event,
                      type: "date",
                      class: "rounded-md border-slate-300 text-sm"
                    }, null, 8, ["onUpdate:modelValue"]), [
                      [vModelText, unref(form).data_fatura]
                    ])
                  ]),
                  createVNode("div", { class: "space-y-2" }, [
                    (openBlock(true), createBlock(Fragment, null, renderList(unref(form).items, (item, index) => {
                      return openBlock(), createBlock("div", {
                        key: index,
                        class: "grid gap-2 rounded-md border border-slate-200 p-3 md:grid-cols-[1fr_130px_150px_90px]"
                      }, [
                        withDirectives(createVNode("select", {
                          "onUpdate:modelValue": ($event) => item.produto_id = $event,
                          class: "rounded-md border-slate-300 text-sm"
                        }, [
                          (openBlock(true), createBlock(Fragment, null, renderList(__props.produtosOptions, (produto) => {
                            return openBlock(), createBlock("option", {
                              key: produto.id,
                              value: produto.id
                            }, toDisplayString(produto.nome) + " - stock " + toDisplayString(formatarQuantidade(produto.stock_atual)), 9, ["value"]);
                          }), 128))
                        ], 8, ["onUpdate:modelValue"]), [
                          [vModelSelect, item.produto_id]
                        ]),
                        withDirectives(createVNode("input", {
                          "onUpdate:modelValue": ($event) => item.quantidade = $event,
                          type: "number",
                          min: "0.001",
                          step: "0.001",
                          class: "rounded-md border-slate-300 text-sm",
                          placeholder: "Qtd."
                        }, null, 8, ["onUpdate:modelValue"]), [
                          [vModelText, item.quantidade]
                        ]),
                        withDirectives(createVNode("input", {
                          "onUpdate:modelValue": ($event) => item.preco_unitario = $event,
                          type: "number",
                          min: "0",
                          step: "0.01",
                          class: "rounded-md border-slate-300 text-sm",
                          placeholder: "Preco unit."
                        }, null, 8, ["onUpdate:modelValue"]), [
                          [vModelText, item.preco_unitario]
                        ]),
                        createVNode("button", {
                          type: "button",
                          class: "rounded-md border border-red-200 px-3 py-2 text-sm font-semibold text-red-700 disabled:opacity-40",
                          disabled: unref(form).items.length === 1,
                          onClick: ($event) => removerLinha(index)
                        }, " Remover ", 8, ["disabled", "onClick"])
                      ]);
                    }), 128))
                  ]),
                  Object.keys(unref(form).errors).length ? (openBlock(), createBlock("div", {
                    key: 0,
                    class: "rounded-md bg-red-50 p-3 text-sm text-red-700"
                  }, [
                    (openBlock(true), createBlock(Fragment, null, renderList(unref(form).errors, (erro) => {
                      return openBlock(), createBlock("div", { key: erro }, toDisplayString(erro), 1);
                    }), 128))
                  ])) : createCommentVNode("", true),
                  createVNode("div", { class: "flex flex-wrap justify-between gap-3" }, [
                    createVNode("button", {
                      type: "button",
                      class: "rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700",
                      onClick: adicionarLinha
                    }, " Adicionar linha "),
                    createVNode("button", {
                      type: "submit",
                      class: "rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white disabled:opacity-60",
                      disabled: unref(form).processing || !__props.produtosOptions?.length
                    }, toDisplayString(unref(form).processing ? "A registar..." : "Registar fatura"), 9, ["disabled"])
                  ])
                ], 32)
              ]),
              createVNode("section", { class: "rounded-lg bg-white p-5 shadow-sm" }, [
                createVNode("h2", { class: "mb-4 text-lg font-bold" }, "Faturas recentes"),
                !__props.faturasRecentes?.length ? (openBlock(), createBlock("div", {
                  key: 0,
                  class: "rounded-md bg-slate-50 p-6 text-center text-sm text-slate-500"
                }, " Ainda nao ha faturas registadas. ")) : (openBlock(), createBlock("div", {
                  key: 1,
                  class: "grid gap-3 lg:grid-cols-2"
                }, [
                  (openBlock(true), createBlock(Fragment, null, renderList(__props.faturasRecentes, (fatura) => {
                    return openBlock(), createBlock("div", {
                      key: fatura.id,
                      class: "rounded-md border border-slate-200 text-sm"
                    }, [
                      createVNode("div", { class: "flex flex-wrap items-start justify-between gap-2 p-4" }, [
                        createVNode("div", { class: "min-w-0" }, [
                          createVNode("p", { class: "font-semibold" }, toDisplayString(fatura.fornecedor || "Sem fornecedor"), 1),
                          createVNode("p", { class: "mt-0.5 text-xs text-slate-500" }, toDisplayString(fatura.numero || "Sem numero") + " · " + toDisplayString(formatarData(fatura.data)), 1),
                          createVNode("p", { class: "mt-2 text-xs text-slate-600" }, toDisplayString(fatura.items.map((item) => `${item.produto?.nome} (${formatarQuantidade(item.quantidade)})`).join(", ")), 1)
                        ]),
                        createVNode("div", { class: "flex shrink-0 flex-col items-end gap-2" }, [
                          createVNode("p", { class: "font-bold" }, toDisplayString(formatarMoeda(fatura.total)), 1),
                          createVNode("button", {
                            class: ["rounded-full px-2.5 py-0.5 text-xs font-semibold transition-opacity disabled:opacity-60", fatura.pago ? "bg-green-100 text-green-700" : "bg-amber-100 text-amber-700"],
                            disabled: toggling.value[fatura.id],
                            onClick: ($event) => togglePago(fatura)
                          }, toDisplayString(fatura.pago ? "Pago" : "Por pagar"), 11, ["disabled", "onClick"])
                        ])
                      ]),
                      createVNode("div", { class: "border-t border-slate-100 px-4 py-2" }, [
                        createVNode("button", {
                          class: "text-xs font-semibold text-slate-500 hover:text-slate-800",
                          onClick: ($event) => abrirPainelDevolucao(fatura)
                        }, toDisplayString(paineisAbertos[fatura.id] ? "Fechar devolucoes" : "Registar devolucoes ao fornecedor"), 9, ["onClick"])
                      ]),
                      paineisAbertos[fatura.id] ? (openBlock(), createBlock("div", {
                        key: 0,
                        class: "border-t border-slate-100 bg-slate-50 p-4"
                      }, [
                        createVNode("p", { class: "mb-3 text-xs text-slate-500" }, "Indique a quantidade devolvida por linha. O stock sera reduzido correspondentemente."),
                        createVNode("div", { class: "space-y-2" }, [
                          (openBlock(true), createBlock(Fragment, null, renderList(fatura.items, (item) => {
                            return openBlock(), createBlock("div", {
                              key: item.id,
                              class: "flex items-center gap-3"
                            }, [
                              createVNode("span", { class: "flex-1 truncate text-xs" }, toDisplayString(item.produto?.nome), 1),
                              createVNode("span", { class: "shrink-0 text-xs text-slate-500" }, "comprado: " + toDisplayString(formatarQuantidade(item.quantidade)), 1),
                              devolucoesState[fatura.id] ? withDirectives((openBlock(), createBlock("input", {
                                key: 0,
                                "onUpdate:modelValue": ($event) => devolucoesState[fatura.id][item.id] = $event,
                                type: "number",
                                min: "0",
                                max: Number(item.quantidade),
                                step: "0.001",
                                class: "w-24 rounded-md border-slate-300 text-xs",
                                placeholder: "Devolvido"
                              }, null, 8, ["onUpdate:modelValue", "max"])), [
                                [
                                  vModelText,
                                  devolucoesState[fatura.id][item.id],
                                  void 0,
                                  { number: true }
                                ]
                              ]) : createCommentVNode("", true)
                            ]);
                          }), 128))
                        ]),
                        createVNode("div", { class: "mt-3 flex justify-end" }, [
                          createVNode("button", {
                            class: "rounded-md bg-slate-700 px-3 py-1.5 text-xs font-semibold text-white disabled:opacity-60",
                            disabled: submitting.value[fatura.id],
                            onClick: ($event) => submeterDevolucao(fatura)
                          }, toDisplayString(submitting.value[fatura.id] ? "A guardar..." : "Guardar devolucoes"), 9, ["disabled", "onClick"])
                        ])
                      ])) : createCommentVNode("", true)
                    ]);
                  }), 128))
                ]))
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/FaturasCompras/Index.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
