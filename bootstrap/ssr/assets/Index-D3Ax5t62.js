import { reactive, ref, withCtx, unref, createVNode, withModifiers, withDirectives, vModelText, toDisplayString, openBlock, createBlock, Fragment, renderList, vModelSelect, createTextVNode, createCommentVNode, useSSRContext } from "vue";
import { ssrRenderComponent, ssrRenderAttr, ssrInterpolate, ssrRenderClass, ssrRenderList, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual } from "vue/server-renderer";
import { _ as _sfc_main$1 } from "./AppLayout-BWDcck4N.js";
import { useForm, router } from "@inertiajs/vue3";
const _sfc_main = {
  __name: "Index",
  __ssrInlineRender: true,
  props: {
    filters: Object,
    custos: Array,
    receitas: Array,
    movimentos: Array,
    resumo: Object,
    categoriasCusto: Array,
    categoriasReceita: Array
  },
  setup(__props) {
    const props = __props;
    const filtros = reactive({ ...props.filters });
    const edicaoId = ref(null);
    const form = useForm({
      tipo: "custo",
      categoria: props.categoriasCusto?.[0]?.valor ?? "outros",
      descricao: "",
      data: (/* @__PURE__ */ new Date()).toISOString().slice(0, 10),
      valor: "",
      observacoes: ""
    });
    const editForm = useForm({
      tipo: "custo",
      categoria: "outros",
      descricao: "",
      data: "",
      valor: "",
      observacoes: ""
    });
    const euros = (valor) => Number(valor || 0).toLocaleString("pt-PT", {
      style: "currency",
      currency: "EUR"
    });
    const filtrar = () => router.get(route("contas-festa.index"), filtros, {
      preserveState: true,
      preserveScroll: true
    });
    const categoriasParaTipo = (tipo) => tipo === "receita" ? props.categoriasReceita : props.categoriasCusto;
    const ajustarCategoria = (formulario) => {
      const categorias = categoriasParaTipo(formulario.tipo);
      if (!categorias?.some((categoria) => categoria.valor === formulario.categoria)) {
        formulario.categoria = categorias?.[0]?.valor ?? "outros";
      }
    };
    const criarMovimento = () => {
      form.post(route("contas-festa.store"), {
        preserveScroll: true,
        onSuccess: () => {
          form.reset("descricao", "valor", "observacoes");
          form.data = (/* @__PURE__ */ new Date()).toISOString().slice(0, 10);
        }
      });
    };
    const editar = (movimento) => {
      edicaoId.value = movimento.id;
      editForm.tipo = movimento.tipo;
      editForm.categoria = movimento.categoria;
      editForm.descricao = movimento.descricao;
      editForm.data = movimento.data ? String(movimento.data).slice(0, 10) : "";
      editForm.valor = movimento.valor;
      editForm.observacoes = movimento.observacoes || "";
    };
    const guardarEdicao = (movimento) => {
      editForm.put(route("contas-festa.update", movimento.id), {
        preserveScroll: true,
        onSuccess: () => {
          edicaoId.value = null;
        }
      });
    };
    const apagar = (movimento) => {
      if (confirm('Apagar "' + movimento.descricao + '"?')) {
        router.delete(route("contas-festa.destroy", movimento.id), { preserveScroll: true });
      }
    };
    const dataCurta = (data) => data ? (/* @__PURE__ */ new Date(String(data).slice(0, 10) + "T00:00:00")).toLocaleDateString("pt-PT") : "-";
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(_sfc_main$1, _attrs, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="mb-6 flex flex-wrap items-end justify-between gap-3"${_scopeId}><div${_scopeId}><h1 class="text-2xl font-black"${_scopeId}>Contas da Festa</h1><p class="mt-1 text-sm text-slate-500"${_scopeId}>Custos, aquisicoes, vendas e resultado final.</p></div><form class="grid gap-2 rounded-lg bg-white p-3 shadow-sm sm:grid-cols-[150px_150px_auto]"${_scopeId}><input${ssrRenderAttr("value", filtros.data_inicio)} type="date" class="rounded-md border-slate-300 text-sm"${_scopeId}><input${ssrRenderAttr("value", filtros.data_fim)} type="date" class="rounded-md border-slate-300 text-sm"${_scopeId}><button class="rounded-md bg-slate-900 px-4 py-2 text-sm font-bold text-white"${_scopeId}>Filtrar</button></form></div><section class="mb-6 grid gap-4 md:grid-cols-3"${_scopeId}><div class="rounded-lg bg-white p-5 shadow-sm"${_scopeId}><div class="text-sm font-bold text-slate-500"${_scopeId}>Total receitas</div><div class="mt-2 text-3xl font-black text-emerald-700"${_scopeId}>${ssrInterpolate(euros(__props.resumo.total_receitas))}</div></div><div class="rounded-lg bg-white p-5 shadow-sm"${_scopeId}><div class="text-sm font-bold text-slate-500"${_scopeId}>Total custos</div><div class="mt-2 text-3xl font-black text-red-700"${_scopeId}>${ssrInterpolate(euros(__props.resumo.total_custos))}</div></div><div class="rounded-lg bg-white p-5 shadow-sm"${_scopeId}><div class="text-sm font-bold text-slate-500"${_scopeId}>Contas feitas</div><div class="${ssrRenderClass([Number(__props.resumo.resultado) >= 0 ? "text-emerald-700" : "text-red-700", "mt-2 text-3xl font-black"])}"${_scopeId}>${ssrInterpolate(euros(__props.resumo.resultado))}</div></div></section><div class="mb-6 grid gap-6 xl:grid-cols-2"${_scopeId}><section class="rounded-lg bg-white p-5 shadow-sm"${_scopeId}><h2 class="mb-4 text-lg font-black"${_scopeId}>Compras e aquisicoes</h2><!--[-->`);
            ssrRenderList(__props.custos, (linha) => {
              _push2(`<div class="flex justify-between border-t border-slate-100 py-3"${_scopeId}><span class="font-bold"${_scopeId}>${ssrInterpolate(linha.label)}</span><strong class="text-red-700"${_scopeId}>${ssrInterpolate(euros(linha.valor))}</strong></div>`);
            });
            _push2(`<!--]--></section><section class="rounded-lg bg-white p-5 shadow-sm"${_scopeId}><h2 class="mb-4 text-lg font-black"${_scopeId}>Vendas e receitas</h2><!--[-->`);
            ssrRenderList(__props.receitas, (linha) => {
              _push2(`<div class="flex justify-between border-t border-slate-100 py-3"${_scopeId}><span class="font-bold"${_scopeId}>${ssrInterpolate(linha.label)}</span><strong class="text-emerald-700"${_scopeId}>${ssrInterpolate(euros(linha.valor))}</strong></div>`);
            });
            _push2(`<!--]--></section></div><section class="mb-6 rounded-lg bg-white p-5 shadow-sm"${_scopeId}><h2 class="mb-4 text-lg font-black"${_scopeId}>Adicionar movimento</h2><form class="grid gap-3 lg:grid-cols-[110px_170px_1fr_150px_130px_auto]"${_scopeId}><select class="rounded-md border-slate-300 text-sm"${_scopeId}><option value="custo"${ssrIncludeBooleanAttr(Array.isArray(unref(form).tipo) ? ssrLooseContain(unref(form).tipo, "custo") : ssrLooseEqual(unref(form).tipo, "custo")) ? " selected" : ""}${_scopeId}>Custo</option><option value="receita"${ssrIncludeBooleanAttr(Array.isArray(unref(form).tipo) ? ssrLooseContain(unref(form).tipo, "receita") : ssrLooseEqual(unref(form).tipo, "receita")) ? " selected" : ""}${_scopeId}>Receita</option></select><select class="rounded-md border-slate-300 text-sm"${_scopeId}><!--[-->`);
            ssrRenderList(categoriasParaTipo(unref(form).tipo), (categoria) => {
              _push2(`<option${ssrRenderAttr("value", categoria.valor)}${ssrIncludeBooleanAttr(Array.isArray(unref(form).categoria) ? ssrLooseContain(unref(form).categoria, categoria.valor) : ssrLooseEqual(unref(form).categoria, categoria.valor)) ? " selected" : ""}${_scopeId}>${ssrInterpolate(categoria.label)}</option>`);
            });
            _push2(`<!--]--></select><input${ssrRenderAttr("value", unref(form).descricao)} required class="rounded-md border-slate-300 text-sm" placeholder="Ex.: Banda X, luz, seguro, patrocinador"${_scopeId}><input${ssrRenderAttr("value", unref(form).data)} type="date" class="rounded-md border-slate-300 text-sm"${_scopeId}><input${ssrRenderAttr("value", unref(form).valor)} required type="number" min="0" step="0.01" class="rounded-md border-slate-300 text-sm" placeholder="Valor"${_scopeId}><button class="rounded-md bg-slate-900 px-4 py-2 text-sm font-bold text-white"${ssrIncludeBooleanAttr(unref(form).processing) ? " disabled" : ""}${_scopeId}>${ssrInterpolate(unref(form).processing ? "A guardar..." : "Adicionar")}</button></form><textarea class="mt-3 w-full rounded-md border-slate-300 text-sm" rows="2" placeholder="Observacoes"${_scopeId}>${ssrInterpolate(unref(form).observacoes)}</textarea>`);
            if (Object.keys(unref(form).errors).length) {
              _push2(`<div class="mt-2 rounded-md bg-red-50 p-3 text-sm text-red-700"${_scopeId}><!--[-->`);
              ssrRenderList(unref(form).errors, (erro, campo) => {
                _push2(`<div${_scopeId}><strong${_scopeId}>${ssrInterpolate(campo)}:</strong> ${ssrInterpolate(erro)}</div>`);
              });
              _push2(`<!--]--></div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</section><section class="rounded-lg bg-white p-5 shadow-sm"${_scopeId}><h2 class="mb-4 text-lg font-black"${_scopeId}>Lancamentos manuais</h2>`);
            if (!__props.movimentos.length) {
              _push2(`<div class="rounded-md bg-slate-50 p-6 text-center text-sm font-bold text-slate-500"${_scopeId}> Ainda nao ha movimentos manuais. </div>`);
            } else {
              _push2(`<div class="overflow-x-auto"${_scopeId}><table class="w-full min-w-[760px] text-left text-sm"${_scopeId}><thead class="text-xs uppercase text-slate-500"${_scopeId}><tr${_scopeId}><th class="py-2"${_scopeId}>Data</th><th${_scopeId}>Tipo</th><th${_scopeId}>Categoria</th><th${_scopeId}>Descricao</th><th class="text-right"${_scopeId}>Valor</th><th${_scopeId}></th></tr></thead><tbody${_scopeId}><!--[-->`);
              ssrRenderList(__props.movimentos, (movimento) => {
                _push2(`<tr class="border-t border-slate-100"${_scopeId}>`);
                if (edicaoId.value === movimento.id) {
                  _push2(`<!--[--><td class="py-2"${_scopeId}><input${ssrRenderAttr("value", unref(editForm).data)} type="date" class="w-36 rounded-md border-slate-300 text-sm"${_scopeId}></td><td${_scopeId}><select class="w-28 rounded-md border-slate-300 text-sm"${_scopeId}><option value="custo"${ssrIncludeBooleanAttr(Array.isArray(unref(editForm).tipo) ? ssrLooseContain(unref(editForm).tipo, "custo") : ssrLooseEqual(unref(editForm).tipo, "custo")) ? " selected" : ""}${_scopeId}>Custo</option><option value="receita"${ssrIncludeBooleanAttr(Array.isArray(unref(editForm).tipo) ? ssrLooseContain(unref(editForm).tipo, "receita") : ssrLooseEqual(unref(editForm).tipo, "receita")) ? " selected" : ""}${_scopeId}>Receita</option></select></td><td${_scopeId}><select class="w-40 rounded-md border-slate-300 text-sm"${_scopeId}><!--[-->`);
                  ssrRenderList(categoriasParaTipo(unref(editForm).tipo), (categoria) => {
                    _push2(`<option${ssrRenderAttr("value", categoria.valor)}${ssrIncludeBooleanAttr(Array.isArray(unref(editForm).categoria) ? ssrLooseContain(unref(editForm).categoria, categoria.valor) : ssrLooseEqual(unref(editForm).categoria, categoria.valor)) ? " selected" : ""}${_scopeId}>${ssrInterpolate(categoria.label)}</option>`);
                  });
                  _push2(`<!--]--></select></td><td${_scopeId}><input${ssrRenderAttr("value", unref(editForm).descricao)} class="w-full rounded-md border-slate-300 text-sm"${_scopeId}></td><td${_scopeId}><input${ssrRenderAttr("value", unref(editForm).valor)} type="number" min="0" step="0.01" class="w-28 rounded-md border-slate-300 text-right text-sm"${_scopeId}></td><td class="text-right"${_scopeId}><button type="button" class="font-bold text-emerald-700"${_scopeId}>Guardar</button><button type="button" class="ml-3 font-bold text-slate-500"${_scopeId}>Cancelar</button></td><!--]-->`);
                } else {
                  _push2(`<!--[--><td class="py-3"${_scopeId}>${ssrInterpolate(dataCurta(movimento.data))}</td><td class="${ssrRenderClass([movimento.tipo === "receita" ? "text-emerald-700" : "text-red-700", "font-bold"])}"${_scopeId}>${ssrInterpolate(movimento.tipo)}</td><td${_scopeId}>${ssrInterpolate(movimento.categoria)}</td><td${_scopeId}><strong${_scopeId}>${ssrInterpolate(movimento.descricao)}</strong>`);
                  if (movimento.observacoes) {
                    _push2(`<div class="text-xs text-slate-500"${_scopeId}>${ssrInterpolate(movimento.observacoes)}</div>`);
                  } else {
                    _push2(`<!---->`);
                  }
                  _push2(`</td><td class="text-right font-black"${_scopeId}>${ssrInterpolate(euros(movimento.valor))}</td><td class="text-right"${_scopeId}><button type="button" class="font-bold text-amber-700"${_scopeId}>Editar</button><button type="button" class="ml-3 font-bold text-red-700"${_scopeId}>Apagar</button></td><!--]-->`);
                }
                _push2(`</tr>`);
              });
              _push2(`<!--]--></tbody></table></div>`);
            }
            _push2(`</section>`);
          } else {
            return [
              createVNode("div", { class: "mb-6 flex flex-wrap items-end justify-between gap-3" }, [
                createVNode("div", null, [
                  createVNode("h1", { class: "text-2xl font-black" }, "Contas da Festa"),
                  createVNode("p", { class: "mt-1 text-sm text-slate-500" }, "Custos, aquisicoes, vendas e resultado final.")
                ]),
                createVNode("form", {
                  class: "grid gap-2 rounded-lg bg-white p-3 shadow-sm sm:grid-cols-[150px_150px_auto]",
                  onSubmit: withModifiers(filtrar, ["prevent"])
                }, [
                  withDirectives(createVNode("input", {
                    "onUpdate:modelValue": ($event) => filtros.data_inicio = $event,
                    type: "date",
                    class: "rounded-md border-slate-300 text-sm"
                  }, null, 8, ["onUpdate:modelValue"]), [
                    [vModelText, filtros.data_inicio]
                  ]),
                  withDirectives(createVNode("input", {
                    "onUpdate:modelValue": ($event) => filtros.data_fim = $event,
                    type: "date",
                    class: "rounded-md border-slate-300 text-sm"
                  }, null, 8, ["onUpdate:modelValue"]), [
                    [vModelText, filtros.data_fim]
                  ]),
                  createVNode("button", { class: "rounded-md bg-slate-900 px-4 py-2 text-sm font-bold text-white" }, "Filtrar")
                ], 32)
              ]),
              createVNode("section", { class: "mb-6 grid gap-4 md:grid-cols-3" }, [
                createVNode("div", { class: "rounded-lg bg-white p-5 shadow-sm" }, [
                  createVNode("div", { class: "text-sm font-bold text-slate-500" }, "Total receitas"),
                  createVNode("div", { class: "mt-2 text-3xl font-black text-emerald-700" }, toDisplayString(euros(__props.resumo.total_receitas)), 1)
                ]),
                createVNode("div", { class: "rounded-lg bg-white p-5 shadow-sm" }, [
                  createVNode("div", { class: "text-sm font-bold text-slate-500" }, "Total custos"),
                  createVNode("div", { class: "mt-2 text-3xl font-black text-red-700" }, toDisplayString(euros(__props.resumo.total_custos)), 1)
                ]),
                createVNode("div", { class: "rounded-lg bg-white p-5 shadow-sm" }, [
                  createVNode("div", { class: "text-sm font-bold text-slate-500" }, "Contas feitas"),
                  createVNode("div", {
                    class: ["mt-2 text-3xl font-black", Number(__props.resumo.resultado) >= 0 ? "text-emerald-700" : "text-red-700"]
                  }, toDisplayString(euros(__props.resumo.resultado)), 3)
                ])
              ]),
              createVNode("div", { class: "mb-6 grid gap-6 xl:grid-cols-2" }, [
                createVNode("section", { class: "rounded-lg bg-white p-5 shadow-sm" }, [
                  createVNode("h2", { class: "mb-4 text-lg font-black" }, "Compras e aquisicoes"),
                  (openBlock(true), createBlock(Fragment, null, renderList(__props.custos, (linha) => {
                    return openBlock(), createBlock("div", {
                      key: linha.categoria + "-" + linha.origem,
                      class: "flex justify-between border-t border-slate-100 py-3"
                    }, [
                      createVNode("span", { class: "font-bold" }, toDisplayString(linha.label), 1),
                      createVNode("strong", { class: "text-red-700" }, toDisplayString(euros(linha.valor)), 1)
                    ]);
                  }), 128))
                ]),
                createVNode("section", { class: "rounded-lg bg-white p-5 shadow-sm" }, [
                  createVNode("h2", { class: "mb-4 text-lg font-black" }, "Vendas e receitas"),
                  (openBlock(true), createBlock(Fragment, null, renderList(__props.receitas, (linha) => {
                    return openBlock(), createBlock("div", {
                      key: linha.categoria + "-" + linha.origem,
                      class: "flex justify-between border-t border-slate-100 py-3"
                    }, [
                      createVNode("span", { class: "font-bold" }, toDisplayString(linha.label), 1),
                      createVNode("strong", { class: "text-emerald-700" }, toDisplayString(euros(linha.valor)), 1)
                    ]);
                  }), 128))
                ])
              ]),
              createVNode("section", { class: "mb-6 rounded-lg bg-white p-5 shadow-sm" }, [
                createVNode("h2", { class: "mb-4 text-lg font-black" }, "Adicionar movimento"),
                createVNode("form", {
                  class: "grid gap-3 lg:grid-cols-[110px_170px_1fr_150px_130px_auto]",
                  onSubmit: withModifiers(criarMovimento, ["prevent"])
                }, [
                  withDirectives(createVNode("select", {
                    "onUpdate:modelValue": ($event) => unref(form).tipo = $event,
                    class: "rounded-md border-slate-300 text-sm",
                    onChange: ($event) => ajustarCategoria(unref(form))
                  }, [
                    createVNode("option", { value: "custo" }, "Custo"),
                    createVNode("option", { value: "receita" }, "Receita")
                  ], 40, ["onUpdate:modelValue", "onChange"]), [
                    [vModelSelect, unref(form).tipo]
                  ]),
                  withDirectives(createVNode("select", {
                    "onUpdate:modelValue": ($event) => unref(form).categoria = $event,
                    class: "rounded-md border-slate-300 text-sm"
                  }, [
                    (openBlock(true), createBlock(Fragment, null, renderList(categoriasParaTipo(unref(form).tipo), (categoria) => {
                      return openBlock(), createBlock("option", {
                        key: categoria.valor,
                        value: categoria.valor
                      }, toDisplayString(categoria.label), 9, ["value"]);
                    }), 128))
                  ], 8, ["onUpdate:modelValue"]), [
                    [vModelSelect, unref(form).categoria]
                  ]),
                  withDirectives(createVNode("input", {
                    "onUpdate:modelValue": ($event) => unref(form).descricao = $event,
                    required: "",
                    class: "rounded-md border-slate-300 text-sm",
                    placeholder: "Ex.: Banda X, luz, seguro, patrocinador"
                  }, null, 8, ["onUpdate:modelValue"]), [
                    [vModelText, unref(form).descricao]
                  ]),
                  withDirectives(createVNode("input", {
                    "onUpdate:modelValue": ($event) => unref(form).data = $event,
                    type: "date",
                    class: "rounded-md border-slate-300 text-sm"
                  }, null, 8, ["onUpdate:modelValue"]), [
                    [vModelText, unref(form).data]
                  ]),
                  withDirectives(createVNode("input", {
                    "onUpdate:modelValue": ($event) => unref(form).valor = $event,
                    required: "",
                    type: "number",
                    min: "0",
                    step: "0.01",
                    class: "rounded-md border-slate-300 text-sm",
                    placeholder: "Valor"
                  }, null, 8, ["onUpdate:modelValue"]), [
                    [vModelText, unref(form).valor]
                  ]),
                  createVNode("button", {
                    class: "rounded-md bg-slate-900 px-4 py-2 text-sm font-bold text-white",
                    disabled: unref(form).processing
                  }, toDisplayString(unref(form).processing ? "A guardar..." : "Adicionar"), 9, ["disabled"])
                ], 32),
                withDirectives(createVNode("textarea", {
                  "onUpdate:modelValue": ($event) => unref(form).observacoes = $event,
                  class: "mt-3 w-full rounded-md border-slate-300 text-sm",
                  rows: "2",
                  placeholder: "Observacoes"
                }, null, 8, ["onUpdate:modelValue"]), [
                  [vModelText, unref(form).observacoes]
                ]),
                Object.keys(unref(form).errors).length ? (openBlock(), createBlock("div", {
                  key: 0,
                  class: "mt-2 rounded-md bg-red-50 p-3 text-sm text-red-700"
                }, [
                  (openBlock(true), createBlock(Fragment, null, renderList(unref(form).errors, (erro, campo) => {
                    return openBlock(), createBlock("div", { key: campo }, [
                      createVNode("strong", null, toDisplayString(campo) + ":", 1),
                      createTextVNode(" " + toDisplayString(erro), 1)
                    ]);
                  }), 128))
                ])) : createCommentVNode("", true)
              ]),
              createVNode("section", { class: "rounded-lg bg-white p-5 shadow-sm" }, [
                createVNode("h2", { class: "mb-4 text-lg font-black" }, "Lancamentos manuais"),
                !__props.movimentos.length ? (openBlock(), createBlock("div", {
                  key: 0,
                  class: "rounded-md bg-slate-50 p-6 text-center text-sm font-bold text-slate-500"
                }, " Ainda nao ha movimentos manuais. ")) : (openBlock(), createBlock("div", {
                  key: 1,
                  class: "overflow-x-auto"
                }, [
                  createVNode("table", { class: "w-full min-w-[760px] text-left text-sm" }, [
                    createVNode("thead", { class: "text-xs uppercase text-slate-500" }, [
                      createVNode("tr", null, [
                        createVNode("th", { class: "py-2" }, "Data"),
                        createVNode("th", null, "Tipo"),
                        createVNode("th", null, "Categoria"),
                        createVNode("th", null, "Descricao"),
                        createVNode("th", { class: "text-right" }, "Valor"),
                        createVNode("th")
                      ])
                    ]),
                    createVNode("tbody", null, [
                      (openBlock(true), createBlock(Fragment, null, renderList(__props.movimentos, (movimento) => {
                        return openBlock(), createBlock("tr", {
                          key: movimento.id,
                          class: "border-t border-slate-100"
                        }, [
                          edicaoId.value === movimento.id ? (openBlock(), createBlock(Fragment, { key: 0 }, [
                            createVNode("td", { class: "py-2" }, [
                              withDirectives(createVNode("input", {
                                "onUpdate:modelValue": ($event) => unref(editForm).data = $event,
                                type: "date",
                                class: "w-36 rounded-md border-slate-300 text-sm"
                              }, null, 8, ["onUpdate:modelValue"]), [
                                [vModelText, unref(editForm).data]
                              ])
                            ]),
                            createVNode("td", null, [
                              withDirectives(createVNode("select", {
                                "onUpdate:modelValue": ($event) => unref(editForm).tipo = $event,
                                class: "w-28 rounded-md border-slate-300 text-sm",
                                onChange: ($event) => ajustarCategoria(unref(editForm))
                              }, [
                                createVNode("option", { value: "custo" }, "Custo"),
                                createVNode("option", { value: "receita" }, "Receita")
                              ], 40, ["onUpdate:modelValue", "onChange"]), [
                                [vModelSelect, unref(editForm).tipo]
                              ])
                            ]),
                            createVNode("td", null, [
                              withDirectives(createVNode("select", {
                                "onUpdate:modelValue": ($event) => unref(editForm).categoria = $event,
                                class: "w-40 rounded-md border-slate-300 text-sm"
                              }, [
                                (openBlock(true), createBlock(Fragment, null, renderList(categoriasParaTipo(unref(editForm).tipo), (categoria) => {
                                  return openBlock(), createBlock("option", {
                                    key: categoria.valor,
                                    value: categoria.valor
                                  }, toDisplayString(categoria.label), 9, ["value"]);
                                }), 128))
                              ], 8, ["onUpdate:modelValue"]), [
                                [vModelSelect, unref(editForm).categoria]
                              ])
                            ]),
                            createVNode("td", null, [
                              withDirectives(createVNode("input", {
                                "onUpdate:modelValue": ($event) => unref(editForm).descricao = $event,
                                class: "w-full rounded-md border-slate-300 text-sm"
                              }, null, 8, ["onUpdate:modelValue"]), [
                                [vModelText, unref(editForm).descricao]
                              ])
                            ]),
                            createVNode("td", null, [
                              withDirectives(createVNode("input", {
                                "onUpdate:modelValue": ($event) => unref(editForm).valor = $event,
                                type: "number",
                                min: "0",
                                step: "0.01",
                                class: "w-28 rounded-md border-slate-300 text-right text-sm"
                              }, null, 8, ["onUpdate:modelValue"]), [
                                [vModelText, unref(editForm).valor]
                              ])
                            ]),
                            createVNode("td", { class: "text-right" }, [
                              createVNode("button", {
                                type: "button",
                                class: "font-bold text-emerald-700",
                                onClick: ($event) => guardarEdicao(movimento)
                              }, "Guardar", 8, ["onClick"]),
                              createVNode("button", {
                                type: "button",
                                class: "ml-3 font-bold text-slate-500",
                                onClick: ($event) => edicaoId.value = null
                              }, "Cancelar", 8, ["onClick"])
                            ])
                          ], 64)) : (openBlock(), createBlock(Fragment, { key: 1 }, [
                            createVNode("td", { class: "py-3" }, toDisplayString(dataCurta(movimento.data)), 1),
                            createVNode("td", {
                              class: ["font-bold", movimento.tipo === "receita" ? "text-emerald-700" : "text-red-700"]
                            }, toDisplayString(movimento.tipo), 3),
                            createVNode("td", null, toDisplayString(movimento.categoria), 1),
                            createVNode("td", null, [
                              createVNode("strong", null, toDisplayString(movimento.descricao), 1),
                              movimento.observacoes ? (openBlock(), createBlock("div", {
                                key: 0,
                                class: "text-xs text-slate-500"
                              }, toDisplayString(movimento.observacoes), 1)) : createCommentVNode("", true)
                            ]),
                            createVNode("td", { class: "text-right font-black" }, toDisplayString(euros(movimento.valor)), 1),
                            createVNode("td", { class: "text-right" }, [
                              createVNode("button", {
                                type: "button",
                                class: "font-bold text-amber-700",
                                onClick: ($event) => editar(movimento)
                              }, "Editar", 8, ["onClick"]),
                              createVNode("button", {
                                type: "button",
                                class: "ml-3 font-bold text-red-700",
                                onClick: ($event) => apagar(movimento)
                              }, "Apagar", 8, ["onClick"])
                            ])
                          ], 64))
                        ]);
                      }), 128))
                    ])
                  ])
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/ContasFesta/Index.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
