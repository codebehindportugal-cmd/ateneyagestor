import { computed, withCtx, unref, createTextVNode, toDisplayString, createVNode, openBlock, createBlock, Fragment, renderList, useSSRContext } from "vue";
import { ssrRenderComponent, ssrInterpolate, ssrRenderList, ssrRenderClass } from "vue/server-renderer";
import { _ as _sfc_main$1 } from "./AppLayout-BWDcck4N.js";
import { Link } from "@inertiajs/vue3";
const _sfc_main = {
  __name: "Index",
  __ssrInlineRender: true,
  props: { pedidos: Object, filters: Object, mesas: Array, resumo: Object },
  setup(__props) {
    const props = __props;
    const estados = [
      ["abertos", "Abertos"],
      ["pronto", "Prontos"],
      ["entregue", "Fechados"],
      ["todos", "Todos"]
    ];
    const pedidosLista = computed(() => props.pedidos?.data ?? []);
    const totalPedido = (pedido) => Number(pedido.total ?? pedido.total_calculado ?? (pedido.items ?? []).reduce((soma, item) => soma + Number(item.preco_unitario) * Number(item.quantidade), 0));
    const formatarPreco = (valor) => `${Number(valor ?? 0).toFixed(2)}€`;
    const criadoPor = (pedido) => pedido.operador_nome ?? pedido.user?.name ?? pedido.pos?.nome ?? "Sem utilizador";
    const estadoClass = (estado) => ({
      pendente: "bg-orange-100 text-orange-800",
      preparacao: "bg-sky-100 text-sky-800",
      pronto: "bg-emerald-100 text-emerald-800",
      entregue: "bg-slate-100 text-slate-700",
      cancelado: "bg-red-100 text-red-800"
    })[estado] ?? "bg-slate-100 text-slate-700";
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(_sfc_main$1, _attrs, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="mb-5 flex flex-wrap items-end justify-between gap-3"${_scopeId}><div${_scopeId}><h1 class="text-2xl font-bold"${_scopeId}>Tesouraria de pedidos</h1><p class="mt-1 text-sm text-slate-500"${_scopeId}>Contas abertas, fecho e talões para cliente.</p></div>`);
            _push2(ssrRenderComponent(unref(Link), {
              href: _ctx.route("pedidos.create"),
              class: "rounded-md bg-slate-900 px-3 py-2 text-sm font-bold text-white"
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`Novo pedido`);
                } else {
                  return [
                    createTextVNode("Novo pedido")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`</div><div class="mb-4 grid gap-3 md:grid-cols-3"${_scopeId}><div class="rounded-md bg-white p-4 shadow-sm"${_scopeId}><div class="text-sm font-semibold text-slate-500"${_scopeId}>Contas abertas</div><div class="mt-1 text-3xl font-black"${_scopeId}>${ssrInterpolate(__props.resumo?.abertos ?? 0)}</div></div><div class="rounded-md bg-white p-4 shadow-sm"${_scopeId}><div class="text-sm font-semibold text-slate-500"${_scopeId}>Prontos a receber</div><div class="mt-1 text-3xl font-black text-emerald-700"${_scopeId}>${ssrInterpolate(__props.resumo?.pronto ?? 0)}</div></div><div class="rounded-md bg-white p-4 shadow-sm"${_scopeId}><div class="text-sm font-semibold text-slate-500"${_scopeId}>Fechados hoje</div><div class="mt-1 text-3xl font-black"${_scopeId}>${ssrInterpolate(__props.resumo?.fechados_hoje ?? 0)}</div></div></div><div class="mb-4 flex flex-wrap gap-2"${_scopeId}><!--[-->`);
            ssrRenderList(estados, ([valor, label]) => {
              _push2(ssrRenderComponent(unref(Link), {
                key: valor,
                href: _ctx.route("pedidos.index", { estado: valor }),
                class: ["rounded-md border px-3 py-2 text-sm font-bold", (__props.filters?.estado ?? "abertos") === valor ? "border-slate-900 bg-slate-900 text-white" : "border-slate-300 bg-white text-slate-700"]
              }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(`${ssrInterpolate(label)}`);
                  } else {
                    return [
                      createTextVNode(toDisplayString(label), 1)
                    ];
                  }
                }),
                _: 2
              }, _parent2, _scopeId));
            });
            _push2(`<!--]--></div>`);
            if (pedidosLista.value.length) {
              _push2(`<div class="grid gap-3 lg:grid-cols-2 2xl:grid-cols-3"${_scopeId}><!--[-->`);
              ssrRenderList(pedidosLista.value, (pedido) => {
                _push2(`<article class="rounded-lg bg-white p-4 shadow-sm"${_scopeId}><div class="mb-3 flex items-start justify-between gap-3"${_scopeId}><div${_scopeId}><div class="text-2xl font-black"${_scopeId}>${ssrInterpolate(pedido.mesa?.designacao ?? "Para levar")}</div><div class="mt-1 text-sm text-slate-500"${_scopeId}>Pedido #${ssrInterpolate(pedido.id)} · ${ssrInterpolate(criadoPor(pedido))}</div></div><span class="${ssrRenderClass([estadoClass(pedido.estado), "rounded-full px-3 py-1 text-xs font-black uppercase"])}"${_scopeId}>${ssrInterpolate(pedido.estado)}</span></div><div class="mb-4 flex items-end justify-between rounded-md bg-slate-50 p-3"${_scopeId}><div class="text-sm font-semibold text-slate-500"${_scopeId}>${ssrInterpolate(pedido.items?.length ?? 0)} artigos</div><div class="text-3xl font-black"${_scopeId}>${ssrInterpolate(formatarPreco(totalPedido(pedido)))}</div></div><div class="grid grid-cols-2 gap-2"${_scopeId}>`);
                _push2(ssrRenderComponent(unref(Link), {
                  href: _ctx.route("pedidos.show", pedido.id),
                  class: "rounded-md bg-slate-900 px-3 py-3 text-center text-sm font-black text-white"
                }, {
                  default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                    if (_push3) {
                      _push3(` Receber / Ver `);
                    } else {
                      return [
                        createTextVNode(" Receber / Ver ")
                      ];
                    }
                  }),
                  _: 2
                }, _parent2, _scopeId));
                _push2(ssrRenderComponent(unref(Link), {
                  href: _ctx.route("pedidos.talao", pedido.id),
                  class: "rounded-md border border-slate-300 px-3 py-3 text-center text-sm font-black"
                }, {
                  default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                    if (_push3) {
                      _push3(` Talão `);
                    } else {
                      return [
                        createTextVNode(" Talão ")
                      ];
                    }
                  }),
                  _: 2
                }, _parent2, _scopeId));
                _push2(`</div></article>`);
              });
              _push2(`<!--]--></div>`);
            } else {
              _push2(`<div class="rounded-lg bg-white p-10 text-center text-slate-500 shadow-sm"${_scopeId}> Não há pedidos nesta vista. </div>`);
            }
          } else {
            return [
              createVNode("div", { class: "mb-5 flex flex-wrap items-end justify-between gap-3" }, [
                createVNode("div", null, [
                  createVNode("h1", { class: "text-2xl font-bold" }, "Tesouraria de pedidos"),
                  createVNode("p", { class: "mt-1 text-sm text-slate-500" }, "Contas abertas, fecho e talões para cliente.")
                ]),
                createVNode(unref(Link), {
                  href: _ctx.route("pedidos.create"),
                  class: "rounded-md bg-slate-900 px-3 py-2 text-sm font-bold text-white"
                }, {
                  default: withCtx(() => [
                    createTextVNode("Novo pedido")
                  ]),
                  _: 1
                }, 8, ["href"])
              ]),
              createVNode("div", { class: "mb-4 grid gap-3 md:grid-cols-3" }, [
                createVNode("div", { class: "rounded-md bg-white p-4 shadow-sm" }, [
                  createVNode("div", { class: "text-sm font-semibold text-slate-500" }, "Contas abertas"),
                  createVNode("div", { class: "mt-1 text-3xl font-black" }, toDisplayString(__props.resumo?.abertos ?? 0), 1)
                ]),
                createVNode("div", { class: "rounded-md bg-white p-4 shadow-sm" }, [
                  createVNode("div", { class: "text-sm font-semibold text-slate-500" }, "Prontos a receber"),
                  createVNode("div", { class: "mt-1 text-3xl font-black text-emerald-700" }, toDisplayString(__props.resumo?.pronto ?? 0), 1)
                ]),
                createVNode("div", { class: "rounded-md bg-white p-4 shadow-sm" }, [
                  createVNode("div", { class: "text-sm font-semibold text-slate-500" }, "Fechados hoje"),
                  createVNode("div", { class: "mt-1 text-3xl font-black" }, toDisplayString(__props.resumo?.fechados_hoje ?? 0), 1)
                ])
              ]),
              createVNode("div", { class: "mb-4 flex flex-wrap gap-2" }, [
                (openBlock(), createBlock(Fragment, null, renderList(estados, ([valor, label]) => {
                  return createVNode(unref(Link), {
                    key: valor,
                    href: _ctx.route("pedidos.index", { estado: valor }),
                    class: ["rounded-md border px-3 py-2 text-sm font-bold", (__props.filters?.estado ?? "abertos") === valor ? "border-slate-900 bg-slate-900 text-white" : "border-slate-300 bg-white text-slate-700"]
                  }, {
                    default: withCtx(() => [
                      createTextVNode(toDisplayString(label), 1)
                    ]),
                    _: 2
                  }, 1032, ["href", "class"]);
                }), 64))
              ]),
              pedidosLista.value.length ? (openBlock(), createBlock("div", {
                key: 0,
                class: "grid gap-3 lg:grid-cols-2 2xl:grid-cols-3"
              }, [
                (openBlock(true), createBlock(Fragment, null, renderList(pedidosLista.value, (pedido) => {
                  return openBlock(), createBlock("article", {
                    key: pedido.id,
                    class: "rounded-lg bg-white p-4 shadow-sm"
                  }, [
                    createVNode("div", { class: "mb-3 flex items-start justify-between gap-3" }, [
                      createVNode("div", null, [
                        createVNode("div", { class: "text-2xl font-black" }, toDisplayString(pedido.mesa?.designacao ?? "Para levar"), 1),
                        createVNode("div", { class: "mt-1 text-sm text-slate-500" }, "Pedido #" + toDisplayString(pedido.id) + " · " + toDisplayString(criadoPor(pedido)), 1)
                      ]),
                      createVNode("span", {
                        class: ["rounded-full px-3 py-1 text-xs font-black uppercase", estadoClass(pedido.estado)]
                      }, toDisplayString(pedido.estado), 3)
                    ]),
                    createVNode("div", { class: "mb-4 flex items-end justify-between rounded-md bg-slate-50 p-3" }, [
                      createVNode("div", { class: "text-sm font-semibold text-slate-500" }, toDisplayString(pedido.items?.length ?? 0) + " artigos", 1),
                      createVNode("div", { class: "text-3xl font-black" }, toDisplayString(formatarPreco(totalPedido(pedido))), 1)
                    ]),
                    createVNode("div", { class: "grid grid-cols-2 gap-2" }, [
                      createVNode(unref(Link), {
                        href: _ctx.route("pedidos.show", pedido.id),
                        class: "rounded-md bg-slate-900 px-3 py-3 text-center text-sm font-black text-white"
                      }, {
                        default: withCtx(() => [
                          createTextVNode(" Receber / Ver ")
                        ]),
                        _: 1
                      }, 8, ["href"]),
                      createVNode(unref(Link), {
                        href: _ctx.route("pedidos.talao", pedido.id),
                        class: "rounded-md border border-slate-300 px-3 py-3 text-center text-sm font-black"
                      }, {
                        default: withCtx(() => [
                          createTextVNode(" Talão ")
                        ]),
                        _: 1
                      }, 8, ["href"])
                    ])
                  ]);
                }), 128))
              ])) : (openBlock(), createBlock("div", {
                key: 1,
                class: "rounded-lg bg-white p-10 text-center text-slate-500 shadow-sm"
              }, " Não há pedidos nesta vista. "))
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Pedidos/Index.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
