import { withCtx, unref, createVNode, withModifiers, withDirectives, openBlock, createBlock, Fragment, renderList, toDisplayString, vModelSelect, vModelText, vModelCheckbox, createTextVNode, useSSRContext } from "vue";
import { ssrRenderComponent, ssrRenderList, ssrRenderAttr, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual, ssrInterpolate } from "vue/server-renderer";
import { _ as _sfc_main$1 } from "./AppLayout-BWDcck4N.js";
import { useForm, router } from "@inertiajs/vue3";
const _sfc_main = {
  __name: "Index",
  __ssrInlineRender: true,
  props: {
    categorias: Array,
    categoriasOptions: Array
  },
  setup(__props) {
    const props = __props;
    const form = useForm({
      categoria_id: props.categoriasOptions?.[0]?.id ?? "",
      nome: "",
      preco: "",
      stock_atual: 0,
      disponivel: true,
      disponivel_restaurante: true,
      disponivel_bar: true
    });
    const criarProduto = () => {
      form.post(route("produtos.store"), {
        preserveScroll: true,
        onSuccess: () => form.reset("nome", "preco", "stock_atual")
      });
    };
    const atualizarProduto = (produto) => {
      router.put(route("produtos.update", produto.id), {
        categoria_id: produto.categoria_id,
        nome: produto.nome,
        preco: produto.preco,
        stock_atual: produto.stock_atual,
        disponivel: produto.disponivel,
        disponivel_restaurante: produto.disponivel_restaurante,
        disponivel_bar: produto.disponivel_bar
      }, { preserveScroll: true });
    };
    const eliminarProduto = (produto) => {
      if (confirm(`Eliminar o produto ${produto.nome}?`)) {
        router.delete(route("produtos.destroy", produto.id), { preserveScroll: true });
      }
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(_sfc_main$1, _attrs, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="mb-6"${_scopeId}><h1 class="text-2xl font-bold"${_scopeId}>Produtos</h1><p class="mt-1 text-sm text-slate-500"${_scopeId}>Lista de precos, stock e disponibilidade.</p></div><form class="mb-6 grid gap-3 rounded-lg bg-white p-5 shadow-sm md:grid-cols-[1fr_2fr_120px_120px_1fr_auto]"${_scopeId}><select class="rounded-md border-slate-300 text-sm"${_scopeId}><!--[-->`);
            ssrRenderList(__props.categoriasOptions, (categoria) => {
              _push2(`<option${ssrRenderAttr("value", categoria.id)}${ssrIncludeBooleanAttr(Array.isArray(unref(form).categoria_id) ? ssrLooseContain(unref(form).categoria_id, categoria.id) : ssrLooseEqual(unref(form).categoria_id, categoria.id)) ? " selected" : ""}${_scopeId}>${ssrInterpolate(categoria.nome)}</option>`);
            });
            _push2(`<!--]--></select><input${ssrRenderAttr("value", unref(form).nome)} class="rounded-md border-slate-300 text-sm" placeholder="Nome do produto"${_scopeId}><input${ssrRenderAttr("value", unref(form).preco)} type="number" min="0" step="0.01" class="rounded-md border-slate-300 text-sm" placeholder="Preco"${_scopeId}><input${ssrRenderAttr("value", unref(form).stock_atual)} type="number" min="0" step="0.001" class="rounded-md border-slate-300 text-sm" placeholder="Stock"${_scopeId}><div class="grid gap-2 text-sm font-medium text-slate-700 sm:grid-cols-3"${_scopeId}><label class="flex min-h-10 items-center gap-2 rounded-md border border-slate-200 px-3 py-2"${_scopeId}><input${ssrIncludeBooleanAttr(Array.isArray(unref(form).disponivel) ? ssrLooseContain(unref(form).disponivel, null) : unref(form).disponivel) ? " checked" : ""} type="checkbox" class="rounded border-slate-300 text-slate-900"${_scopeId}> Ativo </label><label class="flex min-h-10 items-center gap-2 rounded-md border border-slate-200 px-3 py-2"${_scopeId}><input${ssrIncludeBooleanAttr(Array.isArray(unref(form).disponivel_restaurante) ? ssrLooseContain(unref(form).disponivel_restaurante, null) : unref(form).disponivel_restaurante) ? " checked" : ""} type="checkbox" class="rounded border-slate-300 text-slate-900"${_scopeId}> Restaurante </label><label class="flex min-h-10 items-center gap-2 rounded-md border border-slate-200 px-3 py-2"${_scopeId}><input${ssrIncludeBooleanAttr(Array.isArray(unref(form).disponivel_bar) ? ssrLooseContain(unref(form).disponivel_bar, null) : unref(form).disponivel_bar) ? " checked" : ""} type="checkbox" class="rounded border-slate-300 text-slate-900"${_scopeId}> Bar </label></div><button class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white"${_scopeId}>Adicionar</button></form><div class="space-y-6"${_scopeId}><!--[-->`);
            ssrRenderList(__props.categorias, (categoria) => {
              _push2(`<section class="rounded-lg bg-white shadow-sm"${_scopeId}><div class="flex items-center justify-between border-b border-slate-100 px-5 py-4"${_scopeId}><div${_scopeId}><h2 class="font-bold"${_scopeId}>${ssrInterpolate(categoria.nome)}</h2><p class="text-xs uppercase text-slate-500"${_scopeId}>${ssrInterpolate(categoria.secao)}</p></div><span class="text-sm text-slate-500"${_scopeId}>${ssrInterpolate(categoria.produtos.length)} produtos</span></div><div class="divide-y divide-slate-100"${_scopeId}><!--[-->`);
              ssrRenderList(categoria.produtos, (produto) => {
                _push2(`<div class="grid gap-3 px-5 py-3 md:grid-cols-[1fr_120px_120px_1fr_auto]"${_scopeId}><input${ssrRenderAttr("value", produto.nome)} class="rounded-md border-slate-300 text-sm"${_scopeId}><input${ssrRenderAttr("value", produto.preco)} type="number" min="0" step="0.01" class="rounded-md border-slate-300 text-sm"${_scopeId}><input${ssrRenderAttr("value", produto.stock_atual)} type="number" min="0" step="0.001" class="rounded-md border-slate-300 text-sm"${_scopeId}><div class="grid gap-2 text-sm text-slate-700 sm:grid-cols-3"${_scopeId}><label class="flex min-h-10 items-center gap-2 rounded-md border border-slate-200 px-3 py-2"${_scopeId}><input${ssrIncludeBooleanAttr(Array.isArray(produto.disponivel) ? ssrLooseContain(produto.disponivel, null) : produto.disponivel) ? " checked" : ""} type="checkbox" class="rounded border-slate-300 text-slate-900"${_scopeId}> Ativo </label><label class="flex min-h-10 items-center gap-2 rounded-md border border-slate-200 px-3 py-2"${_scopeId}><input${ssrIncludeBooleanAttr(Array.isArray(produto.disponivel_restaurante) ? ssrLooseContain(produto.disponivel_restaurante, null) : produto.disponivel_restaurante) ? " checked" : ""} type="checkbox" class="rounded border-slate-300 text-slate-900"${_scopeId}> Restaurante </label><label class="flex min-h-10 items-center gap-2 rounded-md border border-slate-200 px-3 py-2"${_scopeId}><input${ssrIncludeBooleanAttr(Array.isArray(produto.disponivel_bar) ? ssrLooseContain(produto.disponivel_bar, null) : produto.disponivel_bar) ? " checked" : ""} type="checkbox" class="rounded border-slate-300 text-slate-900"${_scopeId}> Bar </label></div><div class="flex gap-3"${_scopeId}><button type="button" class="font-semibold text-emerald-700"${_scopeId}>Guardar</button><button type="button" class="font-semibold text-red-700"${_scopeId}>Eliminar</button></div></div>`);
              });
              _push2(`<!--]--></div></section>`);
            });
            _push2(`<!--]--></div>`);
          } else {
            return [
              createVNode("div", { class: "mb-6" }, [
                createVNode("h1", { class: "text-2xl font-bold" }, "Produtos"),
                createVNode("p", { class: "mt-1 text-sm text-slate-500" }, "Lista de precos, stock e disponibilidade.")
              ]),
              createVNode("form", {
                class: "mb-6 grid gap-3 rounded-lg bg-white p-5 shadow-sm md:grid-cols-[1fr_2fr_120px_120px_1fr_auto]",
                onSubmit: withModifiers(criarProduto, ["prevent"])
              }, [
                withDirectives(createVNode("select", {
                  "onUpdate:modelValue": ($event) => unref(form).categoria_id = $event,
                  class: "rounded-md border-slate-300 text-sm"
                }, [
                  (openBlock(true), createBlock(Fragment, null, renderList(__props.categoriasOptions, (categoria) => {
                    return openBlock(), createBlock("option", {
                      key: categoria.id,
                      value: categoria.id
                    }, toDisplayString(categoria.nome), 9, ["value"]);
                  }), 128))
                ], 8, ["onUpdate:modelValue"]), [
                  [vModelSelect, unref(form).categoria_id]
                ]),
                withDirectives(createVNode("input", {
                  "onUpdate:modelValue": ($event) => unref(form).nome = $event,
                  class: "rounded-md border-slate-300 text-sm",
                  placeholder: "Nome do produto"
                }, null, 8, ["onUpdate:modelValue"]), [
                  [vModelText, unref(form).nome]
                ]),
                withDirectives(createVNode("input", {
                  "onUpdate:modelValue": ($event) => unref(form).preco = $event,
                  type: "number",
                  min: "0",
                  step: "0.01",
                  class: "rounded-md border-slate-300 text-sm",
                  placeholder: "Preco"
                }, null, 8, ["onUpdate:modelValue"]), [
                  [vModelText, unref(form).preco]
                ]),
                withDirectives(createVNode("input", {
                  "onUpdate:modelValue": ($event) => unref(form).stock_atual = $event,
                  type: "number",
                  min: "0",
                  step: "0.001",
                  class: "rounded-md border-slate-300 text-sm",
                  placeholder: "Stock"
                }, null, 8, ["onUpdate:modelValue"]), [
                  [vModelText, unref(form).stock_atual]
                ]),
                createVNode("div", { class: "grid gap-2 text-sm font-medium text-slate-700 sm:grid-cols-3" }, [
                  createVNode("label", { class: "flex min-h-10 items-center gap-2 rounded-md border border-slate-200 px-3 py-2" }, [
                    withDirectives(createVNode("input", {
                      "onUpdate:modelValue": ($event) => unref(form).disponivel = $event,
                      type: "checkbox",
                      class: "rounded border-slate-300 text-slate-900"
                    }, null, 8, ["onUpdate:modelValue"]), [
                      [vModelCheckbox, unref(form).disponivel]
                    ]),
                    createTextVNode(" Ativo ")
                  ]),
                  createVNode("label", { class: "flex min-h-10 items-center gap-2 rounded-md border border-slate-200 px-3 py-2" }, [
                    withDirectives(createVNode("input", {
                      "onUpdate:modelValue": ($event) => unref(form).disponivel_restaurante = $event,
                      type: "checkbox",
                      class: "rounded border-slate-300 text-slate-900"
                    }, null, 8, ["onUpdate:modelValue"]), [
                      [vModelCheckbox, unref(form).disponivel_restaurante]
                    ]),
                    createTextVNode(" Restaurante ")
                  ]),
                  createVNode("label", { class: "flex min-h-10 items-center gap-2 rounded-md border border-slate-200 px-3 py-2" }, [
                    withDirectives(createVNode("input", {
                      "onUpdate:modelValue": ($event) => unref(form).disponivel_bar = $event,
                      type: "checkbox",
                      class: "rounded border-slate-300 text-slate-900"
                    }, null, 8, ["onUpdate:modelValue"]), [
                      [vModelCheckbox, unref(form).disponivel_bar]
                    ]),
                    createTextVNode(" Bar ")
                  ])
                ]),
                createVNode("button", { class: "rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white" }, "Adicionar")
              ], 32),
              createVNode("div", { class: "space-y-6" }, [
                (openBlock(true), createBlock(Fragment, null, renderList(__props.categorias, (categoria) => {
                  return openBlock(), createBlock("section", {
                    key: categoria.id,
                    class: "rounded-lg bg-white shadow-sm"
                  }, [
                    createVNode("div", { class: "flex items-center justify-between border-b border-slate-100 px-5 py-4" }, [
                      createVNode("div", null, [
                        createVNode("h2", { class: "font-bold" }, toDisplayString(categoria.nome), 1),
                        createVNode("p", { class: "text-xs uppercase text-slate-500" }, toDisplayString(categoria.secao), 1)
                      ]),
                      createVNode("span", { class: "text-sm text-slate-500" }, toDisplayString(categoria.produtos.length) + " produtos", 1)
                    ]),
                    createVNode("div", { class: "divide-y divide-slate-100" }, [
                      (openBlock(true), createBlock(Fragment, null, renderList(categoria.produtos, (produto) => {
                        return openBlock(), createBlock("div", {
                          key: produto.id,
                          class: "grid gap-3 px-5 py-3 md:grid-cols-[1fr_120px_120px_1fr_auto]"
                        }, [
                          withDirectives(createVNode("input", {
                            "onUpdate:modelValue": ($event) => produto.nome = $event,
                            class: "rounded-md border-slate-300 text-sm"
                          }, null, 8, ["onUpdate:modelValue"]), [
                            [vModelText, produto.nome]
                          ]),
                          withDirectives(createVNode("input", {
                            "onUpdate:modelValue": ($event) => produto.preco = $event,
                            type: "number",
                            min: "0",
                            step: "0.01",
                            class: "rounded-md border-slate-300 text-sm"
                          }, null, 8, ["onUpdate:modelValue"]), [
                            [vModelText, produto.preco]
                          ]),
                          withDirectives(createVNode("input", {
                            "onUpdate:modelValue": ($event) => produto.stock_atual = $event,
                            type: "number",
                            min: "0",
                            step: "0.001",
                            class: "rounded-md border-slate-300 text-sm"
                          }, null, 8, ["onUpdate:modelValue"]), [
                            [vModelText, produto.stock_atual]
                          ]),
                          createVNode("div", { class: "grid gap-2 text-sm text-slate-700 sm:grid-cols-3" }, [
                            createVNode("label", { class: "flex min-h-10 items-center gap-2 rounded-md border border-slate-200 px-3 py-2" }, [
                              withDirectives(createVNode("input", {
                                "onUpdate:modelValue": ($event) => produto.disponivel = $event,
                                type: "checkbox",
                                class: "rounded border-slate-300 text-slate-900"
                              }, null, 8, ["onUpdate:modelValue"]), [
                                [vModelCheckbox, produto.disponivel]
                              ]),
                              createTextVNode(" Ativo ")
                            ]),
                            createVNode("label", { class: "flex min-h-10 items-center gap-2 rounded-md border border-slate-200 px-3 py-2" }, [
                              withDirectives(createVNode("input", {
                                "onUpdate:modelValue": ($event) => produto.disponivel_restaurante = $event,
                                type: "checkbox",
                                class: "rounded border-slate-300 text-slate-900"
                              }, null, 8, ["onUpdate:modelValue"]), [
                                [vModelCheckbox, produto.disponivel_restaurante]
                              ]),
                              createTextVNode(" Restaurante ")
                            ]),
                            createVNode("label", { class: "flex min-h-10 items-center gap-2 rounded-md border border-slate-200 px-3 py-2" }, [
                              withDirectives(createVNode("input", {
                                "onUpdate:modelValue": ($event) => produto.disponivel_bar = $event,
                                type: "checkbox",
                                class: "rounded border-slate-300 text-slate-900"
                              }, null, 8, ["onUpdate:modelValue"]), [
                                [vModelCheckbox, produto.disponivel_bar]
                              ]),
                              createTextVNode(" Bar ")
                            ])
                          ]),
                          createVNode("div", { class: "flex gap-3" }, [
                            createVNode("button", {
                              type: "button",
                              class: "font-semibold text-emerald-700",
                              onClick: ($event) => atualizarProduto(produto)
                            }, "Guardar", 8, ["onClick"]),
                            createVNode("button", {
                              type: "button",
                              class: "font-semibold text-red-700",
                              onClick: ($event) => eliminarProduto(produto)
                            }, "Eliminar", 8, ["onClick"])
                          ])
                        ]);
                      }), 128))
                    ])
                  ]);
                }), 128))
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Produtos/Index.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
