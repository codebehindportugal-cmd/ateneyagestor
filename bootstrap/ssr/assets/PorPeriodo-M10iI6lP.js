import { reactive, computed, ref, withCtx, createVNode, withModifiers, withDirectives, vModelText, vModelSelect, openBlock, createBlock, Fragment, renderList, toDisplayString, createCommentVNode, createTextVNode, useSSRContext } from "vue";
import { ssrRenderComponent, ssrRenderAttr, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual, ssrRenderList, ssrInterpolate, ssrRenderClass, ssrRenderStyle } from "vue/server-renderer";
import { _ as _sfc_main$1 } from "./AppLayout-BWDcck4N.js";
import { router } from "@inertiajs/vue3";
const _sfc_main = {
  __name: "PorPeriodo",
  __ssrInlineRender: true,
  props: {
    filters: Object,
    resumo: Object,
    vendas_por_dia: Array,
    vendas_por_tipo: Array,
    vendas_bar_por_ponto: Array,
    caixas_por_ponto: Array,
    top_produtos: Array,
    todos_produtos: Array,
    top_categorias: Array,
    vendas_por_hora: Array,
    vendas_por_secao: Array,
    metodos_pagamento: Array,
    festa_receitas: Array,
    festa_custos: Array
  },
  setup(__props) {
    const props = __props;
    const filtros = reactive({ ...props.filters });
    computed(() => Math.max(1, ...(props.vendas_por_dia ?? []).map((d) => Number(d.total))));
    computed(() => Math.max(1, ...(props.vendas_por_hora ?? []).map((h) => Number(h.total))));
    const euros = (v) => Number(v ?? 0).toLocaleString("pt-PT", { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + "€";
    const filtrar = () => router.get(route("relatorios.periodo"), filtros, { preserveState: true });
    const pdf = () => {
      window.location = route("relatorios.pdf", filtros);
    };
    const mostrarTodosProdutos = ref(false);
    const produtosVisiveis = computed(() => mostrarTodosProdutos.value ? props.todos_produtos ?? [] : props.top_produtos ?? []);
    const totalFestaReceitas = computed(() => (props.festa_receitas ?? []).reduce((s, r) => s + Number(r.valor), 0));
    const totalFestaCustos = computed(() => (props.festa_custos ?? []).reduce((s, c) => s + Number(c.valor), 0));
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(_sfc_main$1, _attrs, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 space-y-6"${_scopeId}><h1 class="text-2xl font-black"${_scopeId}>Relatório por Período</h1><form class="grid gap-3 rounded-lg bg-white p-4 shadow-sm md:grid-cols-4"${_scopeId}><input${ssrRenderAttr("value", filtros.data_inicio)} type="date" class="rounded-md border-slate-300"${_scopeId}><input${ssrRenderAttr("value", filtros.data_fim)} type="date" class="rounded-md border-slate-300"${_scopeId}><select class="rounded-md border-slate-300"${_scopeId}><option value="todos"${ssrIncludeBooleanAttr(Array.isArray(filtros.tipo) ? ssrLooseContain(filtros.tipo, "todos") : ssrLooseEqual(filtros.tipo, "todos")) ? " selected" : ""}${_scopeId}>Todos</option><option value="restaurante"${ssrIncludeBooleanAttr(Array.isArray(filtros.tipo) ? ssrLooseContain(filtros.tipo, "restaurante") : ssrLooseEqual(filtros.tipo, "restaurante")) ? " selected" : ""}${_scopeId}>Restaurante</option><option value="bar"${ssrIncludeBooleanAttr(Array.isArray(filtros.tipo) ? ssrLooseContain(filtros.tipo, "bar") : ssrLooseEqual(filtros.tipo, "bar")) ? " selected" : ""}${_scopeId}>Bar Conta</option><option value="bar_prepago"${ssrIncludeBooleanAttr(Array.isArray(filtros.tipo) ? ssrLooseContain(filtros.tipo, "bar_prepago") : ssrLooseEqual(filtros.tipo, "bar_prepago")) ? " selected" : ""}${_scopeId}>Bar Pré-pago</option></select><button class="rounded-md bg-slate-900 px-4 py-2 font-bold text-white"${_scopeId}>Filtrar</button></form><div class="grid gap-4 md:grid-cols-4"${_scopeId}><!--[-->`);
            ssrRenderList([["Total Vendas", __props.resumo.total_periodo], ["Custo Estimado", __props.resumo.custo_estimado], ["Margem Estimada", __props.resumo.margem_estimada], ["N Pedidos", __props.resumo.total_pedidos]], (card) => {
              _push2(`<div class="rounded-lg bg-white p-5 shadow-sm"${_scopeId}><div class="text-sm text-slate-500"${_scopeId}>${ssrInterpolate(card[0])}</div><div class="text-3xl font-black"${_scopeId}>${ssrInterpolate(card[0] === "N Pedidos" ? card[1] : euros(card[1]))}</div>`);
              if (card[0] === "Margem Estimada") {
                _push2(`<div class="mt-1 text-sm font-bold text-emerald-700"${_scopeId}>${ssrInterpolate(Number(__props.resumo.margem_percentagem || 0).toFixed(1))}%</div>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`</div>`);
            });
            _push2(`<!--]--></div>`);
            if (__props.resumo.lucro_liquido !== void 0) {
              _push2(`<section class="rounded-lg bg-white p-5 shadow-sm"${_scopeId}><div class="mb-4 flex items-center justify-between"${_scopeId}><h2 class="font-black"${_scopeId}>Resultado da Festa</h2><a${ssrRenderAttr("href", _ctx.route("contas-festa.index", { data_inicio: __props.filters.data_inicio, data_fim: __props.filters.data_fim }))} class="text-sm font-semibold text-amber-700 hover:underline"${_scopeId}>Ver lancamentos</a></div><div class="mb-5 grid gap-3 sm:grid-cols-3"${_scopeId}><div class="rounded-lg bg-emerald-50 border border-emerald-200 p-4"${_scopeId}><div class="text-xs font-semibold uppercase tracking-wide text-slate-500"${_scopeId}>Total Receitas</div><div class="text-2xl font-black text-emerald-700"${_scopeId}>${ssrInterpolate(euros(totalFestaReceitas.value))}</div></div><div class="rounded-lg bg-red-50 border border-red-200 p-4"${_scopeId}><div class="text-xs font-semibold uppercase tracking-wide text-slate-500"${_scopeId}>Total Custos</div><div class="text-2xl font-black text-red-700"${_scopeId}>${ssrInterpolate(euros(totalFestaCustos.value))}</div></div><div class="${ssrRenderClass([__props.resumo.lucro_liquido >= 0 ? "bg-emerald-100 border-emerald-400" : "bg-red-100 border-red-400", "rounded-lg border-2 p-4"])}"${_scopeId}><div class="text-xs font-semibold uppercase tracking-wide text-slate-500"${_scopeId}>Lucro Liquido</div><div class="${ssrRenderClass([__props.resumo.lucro_liquido >= 0 ? "text-emerald-800" : "text-red-800", "text-2xl font-black"])}"${_scopeId}>${ssrInterpolate(euros(__props.resumo.lucro_liquido || 0))}</div></div></div><div class="grid gap-4 md:grid-cols-2"${_scopeId}><div${_scopeId}><h3 class="mb-2 text-sm font-bold text-emerald-700"${_scopeId}>Receitas</h3>`);
              if (!__props.festa_receitas?.length) {
                _push2(`<div class="text-sm text-slate-400"${_scopeId}>Sem receitas neste periodo.</div>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`<!--[-->`);
              ssrRenderList(__props.festa_receitas, (r) => {
                _push2(`<div class="flex justify-between border-t border-slate-100 py-2 text-sm"${_scopeId}><span${_scopeId}>${ssrInterpolate(r.label)}</span><strong class="text-emerald-700"${_scopeId}>${ssrInterpolate(euros(r.valor))}</strong></div>`);
              });
              _push2(`<!--]--></div><div${_scopeId}><h3 class="mb-2 text-sm font-bold text-red-700"${_scopeId}>Despesas</h3>`);
              if (!__props.festa_custos?.length) {
                _push2(`<div class="text-sm text-slate-400"${_scopeId}>Sem despesas neste periodo.</div>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`<!--[-->`);
              ssrRenderList(__props.festa_custos, (c) => {
                _push2(`<div class="flex justify-between border-t border-slate-100 py-2 text-sm"${_scopeId}><span${_scopeId}>${ssrInterpolate(c.label)}</span><strong class="text-red-700"${_scopeId}>${ssrInterpolate(euros(c.valor))}</strong></div>`);
              });
              _push2(`<!--]--></div></div></section>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`<section class="rounded-lg bg-white p-5 shadow-sm"${_scopeId}><div class="mb-3 flex justify-between"${_scopeId}><h2 class="font-black"${_scopeId}>Vendas por dia</h2><button type="button" class="rounded-md bg-emerald-600 px-4 py-2 font-bold text-white"${_scopeId}>Exportar PDF</button></div><div class="flex h-64 items-end gap-3 overflow-x-auto"${_scopeId}><!--[-->`);
            ssrRenderList(__props.vendas_por_dia, (dia) => {
              _push2(`<div class="flex min-w-20 flex-1 flex-col items-center"${_scopeId}><strong class="text-xs"${_scopeId}>${ssrInterpolate(euros(dia.total))}</strong><div class="w-full rounded-t bg-blue-600" style="${ssrRenderStyle({ height: "210px" })}"${_scopeId}></div><span class="mt-2 text-xs"${_scopeId}>${ssrInterpolate(dia.data)}</span></div>`);
            });
            _push2(`<!--]--></div></section>`);
            if (__props.vendas_por_hora?.length) {
              _push2(`<section class="rounded-lg bg-white p-5 shadow-sm"${_scopeId}><h2 class="mb-3 font-black"${_scopeId}>Vendas por hora</h2><div class="flex h-48 items-end gap-1 overflow-x-auto"${_scopeId}><!--[-->`);
              ssrRenderList(__props.vendas_por_hora, (h) => {
                _push2(`<div class="flex min-w-12 flex-1 flex-col items-center"${_scopeId}><strong class="text-xs"${_scopeId}>${ssrInterpolate(h.pedidos)}</strong><div class="w-full rounded-t bg-violet-500" style="${ssrRenderStyle({ height: "160px" })}"${_scopeId}></div><span class="mt-1 text-xs text-slate-500"${_scopeId}>${ssrInterpolate(String(h.hora).padStart(2, "0"))}h</span></div>`);
              });
              _push2(`<!--]--></div><div class="mt-3 flex flex-wrap gap-4 text-sm"${_scopeId}><!--[-->`);
              ssrRenderList(__props.vendas_por_hora, (h) => {
                _push2(`<div class="text-slate-600"${_scopeId}><strong${_scopeId}>${ssrInterpolate(String(h.hora).padStart(2, "0"))}h:</strong> ${ssrInterpolate(euros(h.total))}</div>`);
              });
              _push2(`<!--]--></div></section>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`<section class="rounded-lg bg-white p-5 shadow-sm"${_scopeId}><h2 class="mb-3 font-black"${_scopeId}>Dinheiro do Bar por ponto</h2>`);
            if (!__props.vendas_bar_por_ponto?.length) {
              _push2(`<div class="text-sm text-slate-500"${_scopeId}>Sem vendas de bar neste periodo.</div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`<!--[-->`);
            ssrRenderList(__props.vendas_bar_por_ponto, (linha) => {
              _push2(`<div class="flex justify-between border-t py-2"${_scopeId}><span class="font-bold"${_scopeId}>${ssrInterpolate(linha.ponto)}</span><strong${_scopeId}>${ssrInterpolate(euros(linha.total))} - ${ssrInterpolate(linha.pedidos)} pedidos - ${ssrInterpolate(Number(linha.percentagem).toFixed(1))}%</strong></div>`);
            });
            _push2(`<!--]--></section><section class="rounded-lg bg-white p-5 shadow-sm"${_scopeId}><h2 class="mb-3 font-black"${_scopeId}>Caixa e fundo de maneio</h2>`);
            if (!__props.caixas_por_ponto?.length) {
              _push2(`<div class="text-sm text-slate-500"${_scopeId}>Sem caixas abertas neste periodo.</div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`<!--[-->`);
            ssrRenderList(__props.caixas_por_ponto, (linha) => {
              _push2(`<div class="grid gap-2 border-t py-3 text-sm md:grid-cols-8"${_scopeId}><strong${_scopeId}>${ssrInterpolate(linha.ponto)}</strong><span${_scopeId}>Dias: ${ssrInterpolate(linha.dias_abertos)}</span><span${_scopeId}>Fechados: ${ssrInterpolate(linha.dias_fechados)}</span><span${_scopeId}>Fundo: ${ssrInterpolate(euros(linha.fundo_maneio))}</span><span${_scopeId}>Vendas: ${ssrInterpolate(euros(linha.vendas))}</span><strong class="text-emerald-700"${_scopeId}>Esperado: ${ssrInterpolate(euros(linha.esperado_caixa))}</strong><span${_scopeId}>Contado: ${ssrInterpolate(euros(linha.valor_contado))}</span><strong class="${ssrRenderClass(Number(linha.diferenca) >= 0 ? "text-emerald-700" : "text-red-700")}"${_scopeId}>Dif.: ${ssrInterpolate(euros(linha.diferenca))}</strong></div>`);
            });
            _push2(`<!--]--></section><div class="grid gap-6 lg:grid-cols-2"${_scopeId}><section class="rounded-lg bg-white p-5 shadow-sm"${_scopeId}><h2 class="mb-3 font-black"${_scopeId}>Vendas por Tipo</h2><!--[-->`);
            ssrRenderList(__props.vendas_por_tipo, (r) => {
              _push2(`<div class="flex justify-between border-t py-2"${_scopeId}><span${_scopeId}>${ssrInterpolate(r.tipo)}</span><strong${_scopeId}>${ssrInterpolate(euros(r.total))} - ${ssrInterpolate(Number(r.percentagem).toFixed(1))}%</strong></div>`);
            });
            _push2(`<!--]--></section><section class="rounded-lg bg-white p-5 shadow-sm"${_scopeId}><h2 class="mb-3 font-black"${_scopeId}>Metodo de Pagamento</h2>`);
            if (!__props.metodos_pagamento?.length) {
              _push2(`<div class="text-sm text-slate-500"${_scopeId}>Sem dados.</div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`<!--[-->`);
            ssrRenderList(__props.metodos_pagamento, (m) => {
              _push2(`<div class="flex justify-between border-t py-2"${_scopeId}><span class="capitalize"${_scopeId}>${ssrInterpolate(m.metodo)}</span><strong${_scopeId}>${ssrInterpolate(euros(m.total))} - ${ssrInterpolate(m.pedidos)} pedidos</strong></div>`);
            });
            _push2(`<!--]--></section><section class="rounded-lg bg-white p-5 shadow-sm"${_scopeId}><h2 class="mb-3 font-black"${_scopeId}>Por Seccao (cozinha/bar)</h2>`);
            if (!__props.vendas_por_secao?.length) {
              _push2(`<div class="text-sm text-slate-500"${_scopeId}>Sem dados.</div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`<!--[-->`);
            ssrRenderList(__props.vendas_por_secao, (s) => {
              _push2(`<div class="flex justify-between border-t py-2"${_scopeId}><span class="capitalize"${_scopeId}>${ssrInterpolate(s.secao)}</span><strong${_scopeId}>${ssrInterpolate(s.quantidade)}x - ${ssrInterpolate(euros(s.total))}</strong></div>`);
            });
            _push2(`<!--]--></section><section class="rounded-lg bg-white p-5 shadow-sm"${_scopeId}><h2 class="mb-3 font-black"${_scopeId}>Por Categoria</h2><!--[-->`);
            ssrRenderList(__props.top_categorias, (c) => {
              _push2(`<div class="flex justify-between border-t py-2"${_scopeId}><span${_scopeId}>${ssrInterpolate(c.categoria)}</span><strong${_scopeId}>${ssrInterpolate(euros(c.total))}</strong></div>`);
            });
            _push2(`<!--]--></section></div><section class="rounded-lg bg-white p-5 shadow-sm"${_scopeId}><div class="mb-3 flex items-center justify-between"${_scopeId}><h2 class="font-black"${_scopeId}>Produtos Vendidos</h2><button type="button" class="text-sm text-blue-600 hover:underline"${_scopeId}>${ssrInterpolate(mostrarTodosProdutos.value ? "Ver top 10" : "Ver todos (" + (__props.todos_produtos?.length ?? 0) + ")")}</button></div><div class="overflow-x-auto"${_scopeId}><table class="w-full text-sm"${_scopeId}><thead class="border-b border-slate-200 bg-slate-50"${_scopeId}><tr${_scopeId}><th class="px-3 py-2 text-left font-semibold text-slate-700"${_scopeId}>Produto</th><th class="px-3 py-2 text-left font-semibold text-slate-700"${_scopeId}>Categoria</th><th class="px-3 py-2 text-right font-semibold text-slate-700"${_scopeId}>Qtd</th><th class="px-3 py-2 text-right font-semibold text-slate-700"${_scopeId}>Total</th><th class="px-3 py-2 text-right font-semibold text-slate-700"${_scopeId}>Custo</th><th class="px-3 py-2 text-right font-semibold text-slate-700"${_scopeId}>Margem</th><th class="px-3 py-2 text-right font-semibold text-slate-700"${_scopeId}>%</th></tr></thead><tbody class="divide-y divide-slate-100"${_scopeId}><!--[-->`);
            ssrRenderList(produtosVisiveis.value, (p) => {
              _push2(`<tr class="hover:bg-slate-50"${_scopeId}><td class="px-3 py-2 font-medium"${_scopeId}>${ssrInterpolate(p.nome)}</td><td class="px-3 py-2 text-slate-500"${_scopeId}>${ssrInterpolate(p.categoria || "-")}</td><td class="px-3 py-2 text-right font-bold"${_scopeId}>${ssrInterpolate(p.quantidade)}</td><td class="px-3 py-2 text-right"${_scopeId}>${ssrInterpolate(euros(p.total))}</td><td class="px-3 py-2 text-right text-slate-500"${_scopeId}>${ssrInterpolate(euros(p.custo_estimado))}</td><td class="px-3 py-2 text-right text-emerald-700 font-semibold"${_scopeId}>${ssrInterpolate(euros(p.margem_estimada))}</td><td class="${ssrRenderClass([Number(p.margem_percentagem) > 0 ? "text-emerald-700" : "text-red-600", "px-3 py-2 text-right"])}"${_scopeId}>${ssrInterpolate(Number(p.margem_percentagem || 0).toFixed(1))}% </td></tr>`);
            });
            _push2(`<!--]--></tbody></table></div></section></div>`);
          } else {
            return [
              createVNode("div", { class: "mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 space-y-6" }, [
                createVNode("h1", { class: "text-2xl font-black" }, "Relatório por Período"),
                createVNode("form", {
                  class: "grid gap-3 rounded-lg bg-white p-4 shadow-sm md:grid-cols-4",
                  onSubmit: withModifiers(filtrar, ["prevent"])
                }, [
                  withDirectives(createVNode("input", {
                    "onUpdate:modelValue": ($event) => filtros.data_inicio = $event,
                    type: "date",
                    class: "rounded-md border-slate-300"
                  }, null, 8, ["onUpdate:modelValue"]), [
                    [vModelText, filtros.data_inicio]
                  ]),
                  withDirectives(createVNode("input", {
                    "onUpdate:modelValue": ($event) => filtros.data_fim = $event,
                    type: "date",
                    class: "rounded-md border-slate-300"
                  }, null, 8, ["onUpdate:modelValue"]), [
                    [vModelText, filtros.data_fim]
                  ]),
                  withDirectives(createVNode("select", {
                    "onUpdate:modelValue": ($event) => filtros.tipo = $event,
                    class: "rounded-md border-slate-300"
                  }, [
                    createVNode("option", { value: "todos" }, "Todos"),
                    createVNode("option", { value: "restaurante" }, "Restaurante"),
                    createVNode("option", { value: "bar" }, "Bar Conta"),
                    createVNode("option", { value: "bar_prepago" }, "Bar Pré-pago")
                  ], 8, ["onUpdate:modelValue"]), [
                    [vModelSelect, filtros.tipo]
                  ]),
                  createVNode("button", { class: "rounded-md bg-slate-900 px-4 py-2 font-bold text-white" }, "Filtrar")
                ], 32),
                createVNode("div", { class: "grid gap-4 md:grid-cols-4" }, [
                  (openBlock(true), createBlock(Fragment, null, renderList([["Total Vendas", __props.resumo.total_periodo], ["Custo Estimado", __props.resumo.custo_estimado], ["Margem Estimada", __props.resumo.margem_estimada], ["N Pedidos", __props.resumo.total_pedidos]], (card) => {
                    return openBlock(), createBlock("div", {
                      key: card[0],
                      class: "rounded-lg bg-white p-5 shadow-sm"
                    }, [
                      createVNode("div", { class: "text-sm text-slate-500" }, toDisplayString(card[0]), 1),
                      createVNode("div", { class: "text-3xl font-black" }, toDisplayString(card[0] === "N Pedidos" ? card[1] : euros(card[1])), 1),
                      card[0] === "Margem Estimada" ? (openBlock(), createBlock("div", {
                        key: 0,
                        class: "mt-1 text-sm font-bold text-emerald-700"
                      }, toDisplayString(Number(__props.resumo.margem_percentagem || 0).toFixed(1)) + "%", 1)) : createCommentVNode("", true)
                    ]);
                  }), 128))
                ]),
                __props.resumo.lucro_liquido !== void 0 ? (openBlock(), createBlock("section", {
                  key: 0,
                  class: "rounded-lg bg-white p-5 shadow-sm"
                }, [
                  createVNode("div", { class: "mb-4 flex items-center justify-between" }, [
                    createVNode("h2", { class: "font-black" }, "Resultado da Festa"),
                    createVNode("a", {
                      href: _ctx.route("contas-festa.index", { data_inicio: __props.filters.data_inicio, data_fim: __props.filters.data_fim }),
                      class: "text-sm font-semibold text-amber-700 hover:underline"
                    }, "Ver lancamentos", 8, ["href"])
                  ]),
                  createVNode("div", { class: "mb-5 grid gap-3 sm:grid-cols-3" }, [
                    createVNode("div", { class: "rounded-lg bg-emerald-50 border border-emerald-200 p-4" }, [
                      createVNode("div", { class: "text-xs font-semibold uppercase tracking-wide text-slate-500" }, "Total Receitas"),
                      createVNode("div", { class: "text-2xl font-black text-emerald-700" }, toDisplayString(euros(totalFestaReceitas.value)), 1)
                    ]),
                    createVNode("div", { class: "rounded-lg bg-red-50 border border-red-200 p-4" }, [
                      createVNode("div", { class: "text-xs font-semibold uppercase tracking-wide text-slate-500" }, "Total Custos"),
                      createVNode("div", { class: "text-2xl font-black text-red-700" }, toDisplayString(euros(totalFestaCustos.value)), 1)
                    ]),
                    createVNode("div", {
                      class: ["rounded-lg border-2 p-4", __props.resumo.lucro_liquido >= 0 ? "bg-emerald-100 border-emerald-400" : "bg-red-100 border-red-400"]
                    }, [
                      createVNode("div", { class: "text-xs font-semibold uppercase tracking-wide text-slate-500" }, "Lucro Liquido"),
                      createVNode("div", {
                        class: ["text-2xl font-black", __props.resumo.lucro_liquido >= 0 ? "text-emerald-800" : "text-red-800"]
                      }, toDisplayString(euros(__props.resumo.lucro_liquido || 0)), 3)
                    ], 2)
                  ]),
                  createVNode("div", { class: "grid gap-4 md:grid-cols-2" }, [
                    createVNode("div", null, [
                      createVNode("h3", { class: "mb-2 text-sm font-bold text-emerald-700" }, "Receitas"),
                      !__props.festa_receitas?.length ? (openBlock(), createBlock("div", {
                        key: 0,
                        class: "text-sm text-slate-400"
                      }, "Sem receitas neste periodo.")) : createCommentVNode("", true),
                      (openBlock(true), createBlock(Fragment, null, renderList(__props.festa_receitas, (r) => {
                        return openBlock(), createBlock("div", {
                          key: r.label,
                          class: "flex justify-between border-t border-slate-100 py-2 text-sm"
                        }, [
                          createVNode("span", null, toDisplayString(r.label), 1),
                          createVNode("strong", { class: "text-emerald-700" }, toDisplayString(euros(r.valor)), 1)
                        ]);
                      }), 128))
                    ]),
                    createVNode("div", null, [
                      createVNode("h3", { class: "mb-2 text-sm font-bold text-red-700" }, "Despesas"),
                      !__props.festa_custos?.length ? (openBlock(), createBlock("div", {
                        key: 0,
                        class: "text-sm text-slate-400"
                      }, "Sem despesas neste periodo.")) : createCommentVNode("", true),
                      (openBlock(true), createBlock(Fragment, null, renderList(__props.festa_custos, (c) => {
                        return openBlock(), createBlock("div", {
                          key: c.label,
                          class: "flex justify-between border-t border-slate-100 py-2 text-sm"
                        }, [
                          createVNode("span", null, toDisplayString(c.label), 1),
                          createVNode("strong", { class: "text-red-700" }, toDisplayString(euros(c.valor)), 1)
                        ]);
                      }), 128))
                    ])
                  ])
                ])) : createCommentVNode("", true),
                createVNode("section", { class: "rounded-lg bg-white p-5 shadow-sm" }, [
                  createVNode("div", { class: "mb-3 flex justify-between" }, [
                    createVNode("h2", { class: "font-black" }, "Vendas por dia"),
                    createVNode("button", {
                      type: "button",
                      class: "rounded-md bg-emerald-600 px-4 py-2 font-bold text-white",
                      onClick: pdf
                    }, "Exportar PDF")
                  ]),
                  createVNode("div", { class: "flex h-64 items-end gap-3 overflow-x-auto" }, [
                    (openBlock(true), createBlock(Fragment, null, renderList(__props.vendas_por_dia, (dia) => {
                      return openBlock(), createBlock("div", {
                        key: dia.data,
                        class: "flex min-w-20 flex-1 flex-col items-center"
                      }, [
                        createVNode("strong", { class: "text-xs" }, toDisplayString(euros(dia.total)), 1),
                        createVNode("div", {
                          class: "w-full rounded-t bg-blue-600",
                          style: { height: "210px" }
                        }),
                        createVNode("span", { class: "mt-2 text-xs" }, toDisplayString(dia.data), 1)
                      ]);
                    }), 128))
                  ])
                ]),
                __props.vendas_por_hora?.length ? (openBlock(), createBlock("section", {
                  key: 1,
                  class: "rounded-lg bg-white p-5 shadow-sm"
                }, [
                  createVNode("h2", { class: "mb-3 font-black" }, "Vendas por hora"),
                  createVNode("div", { class: "flex h-48 items-end gap-1 overflow-x-auto" }, [
                    (openBlock(true), createBlock(Fragment, null, renderList(__props.vendas_por_hora, (h) => {
                      return openBlock(), createBlock("div", {
                        key: h.hora,
                        class: "flex min-w-12 flex-1 flex-col items-center"
                      }, [
                        createVNode("strong", { class: "text-xs" }, toDisplayString(h.pedidos), 1),
                        createVNode("div", {
                          class: "w-full rounded-t bg-violet-500",
                          style: { height: "160px" }
                        }),
                        createVNode("span", { class: "mt-1 text-xs text-slate-500" }, toDisplayString(String(h.hora).padStart(2, "0")) + "h", 1)
                      ]);
                    }), 128))
                  ]),
                  createVNode("div", { class: "mt-3 flex flex-wrap gap-4 text-sm" }, [
                    (openBlock(true), createBlock(Fragment, null, renderList(__props.vendas_por_hora, (h) => {
                      return openBlock(), createBlock("div", {
                        key: "t" + h.hora,
                        class: "text-slate-600"
                      }, [
                        createVNode("strong", null, toDisplayString(String(h.hora).padStart(2, "0")) + "h:", 1),
                        createTextVNode(" " + toDisplayString(euros(h.total)), 1)
                      ]);
                    }), 128))
                  ])
                ])) : createCommentVNode("", true),
                createVNode("section", { class: "rounded-lg bg-white p-5 shadow-sm" }, [
                  createVNode("h2", { class: "mb-3 font-black" }, "Dinheiro do Bar por ponto"),
                  !__props.vendas_bar_por_ponto?.length ? (openBlock(), createBlock("div", {
                    key: 0,
                    class: "text-sm text-slate-500"
                  }, "Sem vendas de bar neste periodo.")) : createCommentVNode("", true),
                  (openBlock(true), createBlock(Fragment, null, renderList(__props.vendas_bar_por_ponto, (linha) => {
                    return openBlock(), createBlock("div", {
                      key: linha.ponto,
                      class: "flex justify-between border-t py-2"
                    }, [
                      createVNode("span", { class: "font-bold" }, toDisplayString(linha.ponto), 1),
                      createVNode("strong", null, toDisplayString(euros(linha.total)) + " - " + toDisplayString(linha.pedidos) + " pedidos - " + toDisplayString(Number(linha.percentagem).toFixed(1)) + "%", 1)
                    ]);
                  }), 128))
                ]),
                createVNode("section", { class: "rounded-lg bg-white p-5 shadow-sm" }, [
                  createVNode("h2", { class: "mb-3 font-black" }, "Caixa e fundo de maneio"),
                  !__props.caixas_por_ponto?.length ? (openBlock(), createBlock("div", {
                    key: 0,
                    class: "text-sm text-slate-500"
                  }, "Sem caixas abertas neste periodo.")) : createCommentVNode("", true),
                  (openBlock(true), createBlock(Fragment, null, renderList(__props.caixas_por_ponto, (linha) => {
                    return openBlock(), createBlock("div", {
                      key: linha.ponto,
                      class: "grid gap-2 border-t py-3 text-sm md:grid-cols-8"
                    }, [
                      createVNode("strong", null, toDisplayString(linha.ponto), 1),
                      createVNode("span", null, "Dias: " + toDisplayString(linha.dias_abertos), 1),
                      createVNode("span", null, "Fechados: " + toDisplayString(linha.dias_fechados), 1),
                      createVNode("span", null, "Fundo: " + toDisplayString(euros(linha.fundo_maneio)), 1),
                      createVNode("span", null, "Vendas: " + toDisplayString(euros(linha.vendas)), 1),
                      createVNode("strong", { class: "text-emerald-700" }, "Esperado: " + toDisplayString(euros(linha.esperado_caixa)), 1),
                      createVNode("span", null, "Contado: " + toDisplayString(euros(linha.valor_contado)), 1),
                      createVNode("strong", {
                        class: Number(linha.diferenca) >= 0 ? "text-emerald-700" : "text-red-700"
                      }, "Dif.: " + toDisplayString(euros(linha.diferenca)), 3)
                    ]);
                  }), 128))
                ]),
                createVNode("div", { class: "grid gap-6 lg:grid-cols-2" }, [
                  createVNode("section", { class: "rounded-lg bg-white p-5 shadow-sm" }, [
                    createVNode("h2", { class: "mb-3 font-black" }, "Vendas por Tipo"),
                    (openBlock(true), createBlock(Fragment, null, renderList(__props.vendas_por_tipo, (r) => {
                      return openBlock(), createBlock("div", {
                        key: r.tipo,
                        class: "flex justify-between border-t py-2"
                      }, [
                        createVNode("span", null, toDisplayString(r.tipo), 1),
                        createVNode("strong", null, toDisplayString(euros(r.total)) + " - " + toDisplayString(Number(r.percentagem).toFixed(1)) + "%", 1)
                      ]);
                    }), 128))
                  ]),
                  createVNode("section", { class: "rounded-lg bg-white p-5 shadow-sm" }, [
                    createVNode("h2", { class: "mb-3 font-black" }, "Metodo de Pagamento"),
                    !__props.metodos_pagamento?.length ? (openBlock(), createBlock("div", {
                      key: 0,
                      class: "text-sm text-slate-500"
                    }, "Sem dados.")) : createCommentVNode("", true),
                    (openBlock(true), createBlock(Fragment, null, renderList(__props.metodos_pagamento, (m) => {
                      return openBlock(), createBlock("div", {
                        key: m.metodo,
                        class: "flex justify-between border-t py-2"
                      }, [
                        createVNode("span", { class: "capitalize" }, toDisplayString(m.metodo), 1),
                        createVNode("strong", null, toDisplayString(euros(m.total)) + " - " + toDisplayString(m.pedidos) + " pedidos", 1)
                      ]);
                    }), 128))
                  ]),
                  createVNode("section", { class: "rounded-lg bg-white p-5 shadow-sm" }, [
                    createVNode("h2", { class: "mb-3 font-black" }, "Por Seccao (cozinha/bar)"),
                    !__props.vendas_por_secao?.length ? (openBlock(), createBlock("div", {
                      key: 0,
                      class: "text-sm text-slate-500"
                    }, "Sem dados.")) : createCommentVNode("", true),
                    (openBlock(true), createBlock(Fragment, null, renderList(__props.vendas_por_secao, (s) => {
                      return openBlock(), createBlock("div", {
                        key: s.secao,
                        class: "flex justify-between border-t py-2"
                      }, [
                        createVNode("span", { class: "capitalize" }, toDisplayString(s.secao), 1),
                        createVNode("strong", null, toDisplayString(s.quantidade) + "x - " + toDisplayString(euros(s.total)), 1)
                      ]);
                    }), 128))
                  ]),
                  createVNode("section", { class: "rounded-lg bg-white p-5 shadow-sm" }, [
                    createVNode("h2", { class: "mb-3 font-black" }, "Por Categoria"),
                    (openBlock(true), createBlock(Fragment, null, renderList(__props.top_categorias, (c) => {
                      return openBlock(), createBlock("div", {
                        key: c.categoria,
                        class: "flex justify-between border-t py-2"
                      }, [
                        createVNode("span", null, toDisplayString(c.categoria), 1),
                        createVNode("strong", null, toDisplayString(euros(c.total)), 1)
                      ]);
                    }), 128))
                  ])
                ]),
                createVNode("section", { class: "rounded-lg bg-white p-5 shadow-sm" }, [
                  createVNode("div", { class: "mb-3 flex items-center justify-between" }, [
                    createVNode("h2", { class: "font-black" }, "Produtos Vendidos"),
                    createVNode("button", {
                      type: "button",
                      class: "text-sm text-blue-600 hover:underline",
                      onClick: ($event) => mostrarTodosProdutos.value = !mostrarTodosProdutos.value
                    }, toDisplayString(mostrarTodosProdutos.value ? "Ver top 10" : "Ver todos (" + (__props.todos_produtos?.length ?? 0) + ")"), 9, ["onClick"])
                  ]),
                  createVNode("div", { class: "overflow-x-auto" }, [
                    createVNode("table", { class: "w-full text-sm" }, [
                      createVNode("thead", { class: "border-b border-slate-200 bg-slate-50" }, [
                        createVNode("tr", null, [
                          createVNode("th", { class: "px-3 py-2 text-left font-semibold text-slate-700" }, "Produto"),
                          createVNode("th", { class: "px-3 py-2 text-left font-semibold text-slate-700" }, "Categoria"),
                          createVNode("th", { class: "px-3 py-2 text-right font-semibold text-slate-700" }, "Qtd"),
                          createVNode("th", { class: "px-3 py-2 text-right font-semibold text-slate-700" }, "Total"),
                          createVNode("th", { class: "px-3 py-2 text-right font-semibold text-slate-700" }, "Custo"),
                          createVNode("th", { class: "px-3 py-2 text-right font-semibold text-slate-700" }, "Margem"),
                          createVNode("th", { class: "px-3 py-2 text-right font-semibold text-slate-700" }, "%")
                        ])
                      ]),
                      createVNode("tbody", { class: "divide-y divide-slate-100" }, [
                        (openBlock(true), createBlock(Fragment, null, renderList(produtosVisiveis.value, (p) => {
                          return openBlock(), createBlock("tr", {
                            key: p.nome,
                            class: "hover:bg-slate-50"
                          }, [
                            createVNode("td", { class: "px-3 py-2 font-medium" }, toDisplayString(p.nome), 1),
                            createVNode("td", { class: "px-3 py-2 text-slate-500" }, toDisplayString(p.categoria || "-"), 1),
                            createVNode("td", { class: "px-3 py-2 text-right font-bold" }, toDisplayString(p.quantidade), 1),
                            createVNode("td", { class: "px-3 py-2 text-right" }, toDisplayString(euros(p.total)), 1),
                            createVNode("td", { class: "px-3 py-2 text-right text-slate-500" }, toDisplayString(euros(p.custo_estimado)), 1),
                            createVNode("td", { class: "px-3 py-2 text-right text-emerald-700 font-semibold" }, toDisplayString(euros(p.margem_estimada)), 1),
                            createVNode("td", {
                              class: ["px-3 py-2 text-right", Number(p.margem_percentagem) > 0 ? "text-emerald-700" : "text-red-600"]
                            }, toDisplayString(Number(p.margem_percentagem || 0).toFixed(1)) + "% ", 3)
                          ]);
                        }), 128))
                      ])
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Relatorios/PorPeriodo.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
