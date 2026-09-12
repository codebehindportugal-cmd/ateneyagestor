import { withCtx, unref, createTextVNode, createVNode, toDisplayString, openBlock, createBlock, Fragment, renderList, createCommentVNode, useSSRContext } from "vue";
import { ssrRenderComponent, ssrInterpolate, ssrRenderList } from "vue/server-renderer";
import { _ as _sfc_main$1 } from "./AppLayout-BWDcck4N.js";
import { Link } from "@inertiajs/vue3";
const _sfc_main = {
  __name: "Index",
  __ssrInlineRender: true,
  props: { resumo: Object, top_produtos_hoje: Array, vendas_bar_por_ponto: Array, caixas_por_ponto: Array },
  setup(__props) {
    const euros = (v) => Number(v ?? 0).toFixed(2) + "€";
    const hoje = (/* @__PURE__ */ new Date()).toLocaleDateString("pt-PT");
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(_sfc_main$1, _attrs, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="mb-6 flex items-center justify-between"${_scopeId}><h1 class="text-2xl font-black"${_scopeId}>Relatórios</h1>`);
            _push2(ssrRenderComponent(unref(Link), {
              href: _ctx.route("relatorios.periodo"),
              class: "rounded-md bg-slate-900 px-4 py-2 font-bold text-white"
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`Relatório por período`);
                } else {
                  return [
                    createTextVNode("Relatório por período")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`</div><h2 class="mb-4 font-black"${_scopeId}>HOJE — ${ssrInterpolate(unref(hoje))}</h2><div class="grid gap-4 md:grid-cols-4"${_scopeId}><!--[-->`);
            ssrRenderList([["Total Vendido", __props.resumo.total_vendas_hoje], ["Custo Estimado", __props.resumo.custo_estimado_hoje], ["Margem Estimada", __props.resumo.margem_estimada_hoje], ["Nº Pedidos", __props.resumo.total_pedidos_hoje]], (card) => {
              _push2(`<div class="rounded-lg bg-white p-5 shadow-sm"${_scopeId}><div class="text-sm text-slate-500"${_scopeId}>${ssrInterpolate(card[0])}</div><div class="mt-2 text-3xl font-black"${_scopeId}>${ssrInterpolate(card[0] === "Nº Pedidos" ? card[1] : euros(card[1]))}</div>`);
              if (card[0] === "Margem Estimada") {
                _push2(`<div class="mt-1 text-sm font-bold text-emerald-700"${_scopeId}>${ssrInterpolate(Number(__props.resumo.margem_percentagem_hoje || 0).toFixed(1))}%</div>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`</div>`);
            });
            _push2(`<!--]--></div><div class="mt-4 grid gap-4 md:grid-cols-3"${_scopeId}><div class="rounded-lg bg-white p-5 shadow-sm"${_scopeId}>Restaurante <strong class="float-right"${_scopeId}>${ssrInterpolate(euros(__props.resumo.vendas_restaurante_hoje))}</strong></div><div class="rounded-lg bg-white p-5 shadow-sm"${_scopeId}>Bar Conta <strong class="float-right"${_scopeId}>${ssrInterpolate(euros(__props.resumo.vendas_bar_hoje))}</strong></div><div class="rounded-lg bg-white p-5 shadow-sm"${_scopeId}>Bar Pré-pago <strong class="float-right"${_scopeId}>${ssrInterpolate(euros(__props.resumo.vendas_prepago_hoje))}</strong></div></div><section class="mt-6 rounded-lg bg-white p-5 shadow-sm"${_scopeId}><h2 class="mb-3 font-black"${_scopeId}>Dinheiro do Bar por ponto</h2>`);
            if (!__props.vendas_bar_por_ponto?.length) {
              _push2(`<div class="text-sm text-slate-500"${_scopeId}>Sem vendas de bar neste período.</div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`<!--[-->`);
            ssrRenderList(__props.vendas_bar_por_ponto, (linha) => {
              _push2(`<div class="flex justify-between border-t py-2"${_scopeId}><span class="font-bold"${_scopeId}>${ssrInterpolate(linha.ponto)}</span><strong${_scopeId}>${ssrInterpolate(euros(linha.total))} · ${ssrInterpolate(linha.pedidos)} pedidos</strong></div>`);
            });
            _push2(`<!--]--></section><section class="mt-6 rounded-lg bg-white p-5 shadow-sm"${_scopeId}><h2 class="mb-3 font-black"${_scopeId}>Caixa e fundo de maneio</h2>`);
            if (!__props.caixas_por_ponto?.length) {
              _push2(`<div class="text-sm text-slate-500"${_scopeId}>Ainda não há caixas abertas.</div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`<!--[-->`);
            ssrRenderList(__props.caixas_por_ponto, (linha) => {
              _push2(`<div class="grid gap-2 border-t py-3 text-sm md:grid-cols-6"${_scopeId}><strong${_scopeId}>${ssrInterpolate(linha.ponto)}</strong><span${_scopeId}>Fundo: ${ssrInterpolate(euros(linha.fundo_maneio))}</span><span${_scopeId}>Vendas: ${ssrInterpolate(euros(linha.vendas))}</span><strong class="text-emerald-700"${_scopeId}>Esperado: ${ssrInterpolate(euros(linha.esperado_caixa))}</strong><span${_scopeId}>Contado: ${ssrInterpolate(euros(linha.valor_contado))}</span><strong${_scopeId}>Dif.: ${ssrInterpolate(euros(linha.diferenca))}</strong></div>`);
            });
            _push2(`<!--]--></section><section class="mt-6 rounded-lg bg-white p-5 shadow-sm"${_scopeId}><h2 class="mb-3 font-black"${_scopeId}>Top 5 Produtos Hoje</h2><table class="w-full text-left text-sm"${_scopeId}><tbody${_scopeId}><!--[-->`);
            ssrRenderList(__props.top_produtos_hoje, (p) => {
              _push2(`<tr class="border-t"${_scopeId}><td class="py-2 font-bold"${_scopeId}>${ssrInterpolate(p.nome)}</td><td${_scopeId}>${ssrInterpolate(p.quantidade)}</td><td${_scopeId}>${ssrInterpolate(euros(p.custo_estimado))} custo</td><td class="font-bold text-emerald-700"${_scopeId}>${ssrInterpolate(euros(p.margem_estimada))} · ${ssrInterpolate(Number(p.margem_percentagem || 0).toFixed(1))}%</td><td class="text-right"${_scopeId}>${ssrInterpolate(euros(p.total))}</td></tr>`);
            });
            _push2(`<!--]--></tbody></table></section>`);
          } else {
            return [
              createVNode("div", { class: "mb-6 flex items-center justify-between" }, [
                createVNode("h1", { class: "text-2xl font-black" }, "Relatórios"),
                createVNode(unref(Link), {
                  href: _ctx.route("relatorios.periodo"),
                  class: "rounded-md bg-slate-900 px-4 py-2 font-bold text-white"
                }, {
                  default: withCtx(() => [
                    createTextVNode("Relatório por período")
                  ]),
                  _: 1
                }, 8, ["href"])
              ]),
              createVNode("h2", { class: "mb-4 font-black" }, "HOJE — " + toDisplayString(unref(hoje)), 1),
              createVNode("div", { class: "grid gap-4 md:grid-cols-4" }, [
                (openBlock(true), createBlock(Fragment, null, renderList([["Total Vendido", __props.resumo.total_vendas_hoje], ["Custo Estimado", __props.resumo.custo_estimado_hoje], ["Margem Estimada", __props.resumo.margem_estimada_hoje], ["Nº Pedidos", __props.resumo.total_pedidos_hoje]], (card) => {
                  return openBlock(), createBlock("div", {
                    key: card[0],
                    class: "rounded-lg bg-white p-5 shadow-sm"
                  }, [
                    createVNode("div", { class: "text-sm text-slate-500" }, toDisplayString(card[0]), 1),
                    createVNode("div", { class: "mt-2 text-3xl font-black" }, toDisplayString(card[0] === "Nº Pedidos" ? card[1] : euros(card[1])), 1),
                    card[0] === "Margem Estimada" ? (openBlock(), createBlock("div", {
                      key: 0,
                      class: "mt-1 text-sm font-bold text-emerald-700"
                    }, toDisplayString(Number(__props.resumo.margem_percentagem_hoje || 0).toFixed(1)) + "%", 1)) : createCommentVNode("", true)
                  ]);
                }), 128))
              ]),
              createVNode("div", { class: "mt-4 grid gap-4 md:grid-cols-3" }, [
                createVNode("div", { class: "rounded-lg bg-white p-5 shadow-sm" }, [
                  createTextVNode("Restaurante "),
                  createVNode("strong", { class: "float-right" }, toDisplayString(euros(__props.resumo.vendas_restaurante_hoje)), 1)
                ]),
                createVNode("div", { class: "rounded-lg bg-white p-5 shadow-sm" }, [
                  createTextVNode("Bar Conta "),
                  createVNode("strong", { class: "float-right" }, toDisplayString(euros(__props.resumo.vendas_bar_hoje)), 1)
                ]),
                createVNode("div", { class: "rounded-lg bg-white p-5 shadow-sm" }, [
                  createTextVNode("Bar Pré-pago "),
                  createVNode("strong", { class: "float-right" }, toDisplayString(euros(__props.resumo.vendas_prepago_hoje)), 1)
                ])
              ]),
              createVNode("section", { class: "mt-6 rounded-lg bg-white p-5 shadow-sm" }, [
                createVNode("h2", { class: "mb-3 font-black" }, "Dinheiro do Bar por ponto"),
                !__props.vendas_bar_por_ponto?.length ? (openBlock(), createBlock("div", {
                  key: 0,
                  class: "text-sm text-slate-500"
                }, "Sem vendas de bar neste período.")) : createCommentVNode("", true),
                (openBlock(true), createBlock(Fragment, null, renderList(__props.vendas_bar_por_ponto, (linha) => {
                  return openBlock(), createBlock("div", {
                    key: linha.ponto,
                    class: "flex justify-between border-t py-2"
                  }, [
                    createVNode("span", { class: "font-bold" }, toDisplayString(linha.ponto), 1),
                    createVNode("strong", null, toDisplayString(euros(linha.total)) + " · " + toDisplayString(linha.pedidos) + " pedidos", 1)
                  ]);
                }), 128))
              ]),
              createVNode("section", { class: "mt-6 rounded-lg bg-white p-5 shadow-sm" }, [
                createVNode("h2", { class: "mb-3 font-black" }, "Caixa e fundo de maneio"),
                !__props.caixas_por_ponto?.length ? (openBlock(), createBlock("div", {
                  key: 0,
                  class: "text-sm text-slate-500"
                }, "Ainda não há caixas abertas.")) : createCommentVNode("", true),
                (openBlock(true), createBlock(Fragment, null, renderList(__props.caixas_por_ponto, (linha) => {
                  return openBlock(), createBlock("div", {
                    key: linha.ponto,
                    class: "grid gap-2 border-t py-3 text-sm md:grid-cols-6"
                  }, [
                    createVNode("strong", null, toDisplayString(linha.ponto), 1),
                    createVNode("span", null, "Fundo: " + toDisplayString(euros(linha.fundo_maneio)), 1),
                    createVNode("span", null, "Vendas: " + toDisplayString(euros(linha.vendas)), 1),
                    createVNode("strong", { class: "text-emerald-700" }, "Esperado: " + toDisplayString(euros(linha.esperado_caixa)), 1),
                    createVNode("span", null, "Contado: " + toDisplayString(euros(linha.valor_contado)), 1),
                    createVNode("strong", null, "Dif.: " + toDisplayString(euros(linha.diferenca)), 1)
                  ]);
                }), 128))
              ]),
              createVNode("section", { class: "mt-6 rounded-lg bg-white p-5 shadow-sm" }, [
                createVNode("h2", { class: "mb-3 font-black" }, "Top 5 Produtos Hoje"),
                createVNode("table", { class: "w-full text-left text-sm" }, [
                  createVNode("tbody", null, [
                    (openBlock(true), createBlock(Fragment, null, renderList(__props.top_produtos_hoje, (p) => {
                      return openBlock(), createBlock("tr", {
                        key: p.nome,
                        class: "border-t"
                      }, [
                        createVNode("td", { class: "py-2 font-bold" }, toDisplayString(p.nome), 1),
                        createVNode("td", null, toDisplayString(p.quantidade), 1),
                        createVNode("td", null, toDisplayString(euros(p.custo_estimado)) + " custo", 1),
                        createVNode("td", { class: "font-bold text-emerald-700" }, toDisplayString(euros(p.margem_estimada)) + " · " + toDisplayString(Number(p.margem_percentagem || 0).toFixed(1)) + "%", 1),
                        createVNode("td", { class: "text-right" }, toDisplayString(euros(p.total)), 1)
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Relatorios/Index.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
