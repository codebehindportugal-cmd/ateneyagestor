import { withCtx, unref, openBlock, createBlock, createVNode, toDisplayString, createTextVNode, createCommentVNode, useSSRContext } from "vue";
import { ssrRenderComponent, ssrInterpolate, ssrRenderClass } from "vue/server-renderer";
import { _ as _sfc_main$1 } from "./AppLayout-BWDcck4N.js";
import { usePage, Link } from "@inertiajs/vue3";
const _sfc_main = {
  __name: "Index",
  __ssrInlineRender: true,
  props: { totais: Object },
  setup(__props) {
    const page = usePage();
    const userName = page.props.auth?.user?.name ?? "";
    const firstName = userName.split(" ")[0];
    const hora = (/* @__PURE__ */ new Date()).getHours();
    const saudacao = hora < 12 ? "Bom dia" : hora < 19 ? "Boa tarde" : "Boa noite";
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(_sfc_main$1, _attrs, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="mb-6"${_scopeId}><p class="text-sm text-stone-400"${_scopeId}>${ssrInterpolate(unref(saudacao))}, <span class="font-semibold text-stone-600"${_scopeId}>${ssrInterpolate(unref(firstName))}</span></p><h1 class="mt-0.5 text-2xl font-bold text-stone-800"${_scopeId}>Dashboard</h1></div><div class="grid grid-cols-2 gap-4 lg:grid-cols-5"${_scopeId}><div class="flex flex-col rounded-xl bg-white p-5 shadow-sm ring-1 ring-stone-100"${_scopeId}><div class="flex items-start justify-between"${_scopeId}><p class="text-xs font-semibold uppercase tracking-wide text-stone-400"${_scopeId}>Mesas livres</p><div class="rounded-lg bg-stone-50 p-1.5"${_scopeId}><svg class="h-4 w-4 text-stone-400" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"${_scopeId}><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"${_scopeId}></path></svg></div></div><p class="mt-3 text-3xl font-bold text-stone-800"${_scopeId}>${ssrInterpolate(__props.totais.mesas_livres)}</p><p class="mt-1 text-xs text-stone-400"${_scopeId}>disponíveis agora</p></div><div class="flex flex-col rounded-xl bg-white p-5 shadow-sm ring-1 ring-stone-100"${_scopeId}><div class="flex items-start justify-between"${_scopeId}><p class="text-xs font-semibold uppercase tracking-wide text-amber-600"${_scopeId}>Pedidos ativos</p><div class="rounded-lg bg-amber-50 p-1.5"${_scopeId}><svg class="h-4 w-4 text-amber-500" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"${_scopeId}><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"${_scopeId}></path></svg></div></div><p class="mt-3 text-3xl font-bold text-stone-800"${_scopeId}>${ssrInterpolate(__props.totais.pedidos_ativos)}</p><p class="mt-1 text-xs text-amber-500"${_scopeId}>em curso</p></div><div class="${ssrRenderClass([__props.totais.socios_em_atraso ? "bg-red-50 ring-red-100" : "bg-white ring-stone-100", "flex flex-col rounded-xl p-5 shadow-sm ring-1 transition-colors"])}"${_scopeId}><div class="flex items-start justify-between"${_scopeId}><p class="${ssrRenderClass([__props.totais.socios_em_atraso ? "text-red-500" : "text-stone-400", "text-xs font-semibold uppercase tracking-wide"])}"${_scopeId}>Sócios em atraso</p><div class="${ssrRenderClass([__props.totais.socios_em_atraso ? "bg-red-100" : "bg-stone-50", "rounded-lg p-1.5"])}"${_scopeId}><svg class="${ssrRenderClass([__props.totais.socios_em_atraso ? "text-red-500" : "text-stone-400", "h-4 w-4"])}" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"${_scopeId}><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"${_scopeId}></path></svg></div></div><p class="${ssrRenderClass([__props.totais.socios_em_atraso ? "text-red-700" : "text-stone-800", "mt-3 text-3xl font-bold"])}"${_scopeId}>${ssrInterpolate(__props.totais.socios_em_atraso)}</p><p class="${ssrRenderClass([__props.totais.socios_em_atraso ? "text-red-400" : "text-stone-400", "mt-1 text-xs"])}"${_scopeId}>cotas em falta</p></div><div class="flex flex-col rounded-xl bg-white p-5 shadow-sm ring-1 ring-stone-100"${_scopeId}><div class="flex items-start justify-between"${_scopeId}><p class="text-xs font-semibold uppercase tracking-wide text-emerald-600"${_scopeId}>Fechados hoje</p><div class="rounded-lg bg-emerald-50 p-1.5"${_scopeId}><svg class="h-4 w-4 text-emerald-500" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"${_scopeId}><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"${_scopeId}></path></svg></div></div><p class="mt-3 text-3xl font-bold text-stone-800"${_scopeId}>${ssrInterpolate(__props.totais.pedidos_fechados_hoje)}</p><p class="mt-1 text-xs text-emerald-500"${_scopeId}>pedidos concluídos</p></div><div class="flex flex-col rounded-xl bg-emerald-50 p-5 shadow-sm ring-1 ring-emerald-100"${_scopeId}><div class="flex items-start justify-between"${_scopeId}><p class="text-xs font-semibold uppercase tracking-wide text-emerald-700"${_scopeId}>Bar hoje</p><div class="rounded-lg bg-emerald-100 p-1.5"${_scopeId}><svg class="h-4 w-4 text-emerald-600" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"${_scopeId}><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"${_scopeId}></path></svg></div></div><p class="mt-3 text-3xl font-bold text-emerald-800"${_scopeId}>${ssrInterpolate(__props.totais.pedidos_bar_hoje)}</p><p class="mt-1 text-xs text-emerald-600"${_scopeId}>pedidos de bar</p></div></div>`);
            if (__props.totais.socios_em_atraso) {
              _push2(ssrRenderComponent(unref(Link), {
                href: _ctx.route("socios.emAtraso"),
                class: "mt-5 flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-semibold text-red-700 transition hover:border-red-300 hover:bg-red-100"
              }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(`<svg class="h-5 w-5 shrink-0 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"${_scopeId2}><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"${_scopeId2}></path></svg><span class="flex-1"${_scopeId2}><strong${_scopeId2}>${ssrInterpolate(__props.totais.socios_em_atraso)}</strong> ${ssrInterpolate(__props.totais.socios_em_atraso === 1 ? "sócio" : "sócios")} com cotas em atraso </span><svg class="h-4 w-4 shrink-0 text-red-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"${_scopeId2}><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"${_scopeId2}></path></svg>`);
                  } else {
                    return [
                      (openBlock(), createBlock("svg", {
                        class: "h-5 w-5 shrink-0 text-red-500",
                        fill: "none",
                        stroke: "currentColor",
                        "stroke-width": "2",
                        viewBox: "0 0 24 24"
                      }, [
                        createVNode("path", {
                          "stroke-linecap": "round",
                          "stroke-linejoin": "round",
                          d: "M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                        })
                      ])),
                      createVNode("span", { class: "flex-1" }, [
                        createVNode("strong", null, toDisplayString(__props.totais.socios_em_atraso), 1),
                        createTextVNode(" " + toDisplayString(__props.totais.socios_em_atraso === 1 ? "sócio" : "sócios") + " com cotas em atraso ", 1)
                      ]),
                      (openBlock(), createBlock("svg", {
                        class: "h-4 w-4 shrink-0 text-red-400",
                        fill: "none",
                        stroke: "currentColor",
                        "stroke-width": "2",
                        viewBox: "0 0 24 24"
                      }, [
                        createVNode("path", {
                          "stroke-linecap": "round",
                          "stroke-linejoin": "round",
                          d: "M9 5l7 7-7 7"
                        })
                      ]))
                    ];
                  }
                }),
                _: 1
              }, _parent2, _scopeId));
            } else {
              _push2(`<!---->`);
            }
            _push2(`<div class="mt-8"${_scopeId}><h2 class="mb-3 text-xs font-bold uppercase tracking-widest text-stone-400"${_scopeId}>Acesso rápido</h2><div class="grid grid-cols-2 gap-3 sm:grid-cols-4"${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Link), {
              href: _ctx.route("pedidos.index"),
              class: "group flex items-center gap-3 rounded-xl border border-stone-100 bg-white p-4 text-sm font-semibold text-stone-700 shadow-sm transition hover:border-amber-200 hover:bg-amber-50 hover:text-amber-800"
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`<div class="rounded-lg bg-amber-50 p-2 transition group-hover:bg-amber-100"${_scopeId2}><svg class="h-4 w-4 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"${_scopeId2}><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"${_scopeId2}></path></svg></div> Pedidos `);
                } else {
                  return [
                    createVNode("div", { class: "rounded-lg bg-amber-50 p-2 transition group-hover:bg-amber-100" }, [
                      (openBlock(), createBlock("svg", {
                        class: "h-4 w-4 text-amber-600",
                        fill: "none",
                        stroke: "currentColor",
                        "stroke-width": "2",
                        viewBox: "0 0 24 24"
                      }, [
                        createVNode("path", {
                          "stroke-linecap": "round",
                          "stroke-linejoin": "round",
                          d: "M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                        })
                      ]))
                    ]),
                    createTextVNode(" Pedidos ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(ssrRenderComponent(unref(Link), {
              href: _ctx.route("reservas.index"),
              class: "group flex items-center gap-3 rounded-xl border border-stone-100 bg-white p-4 text-sm font-semibold text-stone-700 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-800"
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`<div class="rounded-lg bg-blue-50 p-2 transition group-hover:bg-blue-100"${_scopeId2}><svg class="h-4 w-4 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"${_scopeId2}><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"${_scopeId2}></path></svg></div> Reservas `);
                } else {
                  return [
                    createVNode("div", { class: "rounded-lg bg-blue-50 p-2 transition group-hover:bg-blue-100" }, [
                      (openBlock(), createBlock("svg", {
                        class: "h-4 w-4 text-blue-600",
                        fill: "none",
                        stroke: "currentColor",
                        "stroke-width": "2",
                        viewBox: "0 0 24 24"
                      }, [
                        createVNode("path", {
                          "stroke-linecap": "round",
                          "stroke-linejoin": "round",
                          d: "M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                        })
                      ]))
                    ]),
                    createTextVNode(" Reservas ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(ssrRenderComponent(unref(Link), {
              href: _ctx.route("socios.index"),
              class: "group flex items-center gap-3 rounded-xl border border-stone-100 bg-white p-4 text-sm font-semibold text-stone-700 shadow-sm transition hover:border-violet-200 hover:bg-violet-50 hover:text-violet-800"
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`<div class="rounded-lg bg-violet-50 p-2 transition group-hover:bg-violet-100"${_scopeId2}><svg class="h-4 w-4 text-violet-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"${_scopeId2}><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"${_scopeId2}></path></svg></div> Sócios `);
                } else {
                  return [
                    createVNode("div", { class: "rounded-lg bg-violet-50 p-2 transition group-hover:bg-violet-100" }, [
                      (openBlock(), createBlock("svg", {
                        class: "h-4 w-4 text-violet-600",
                        fill: "none",
                        stroke: "currentColor",
                        "stroke-width": "2",
                        viewBox: "0 0 24 24"
                      }, [
                        createVNode("path", {
                          "stroke-linecap": "round",
                          "stroke-linejoin": "round",
                          d: "M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"
                        })
                      ]))
                    ]),
                    createTextVNode(" Sócios ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(ssrRenderComponent(unref(Link), {
              href: _ctx.route("relatorios.index"),
              class: "group flex items-center gap-3 rounded-xl border border-stone-100 bg-white p-4 text-sm font-semibold text-stone-700 shadow-sm transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-800"
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`<div class="rounded-lg bg-emerald-50 p-2 transition group-hover:bg-emerald-100"${_scopeId2}><svg class="h-4 w-4 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"${_scopeId2}><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"${_scopeId2}></path></svg></div> Relatórios `);
                } else {
                  return [
                    createVNode("div", { class: "rounded-lg bg-emerald-50 p-2 transition group-hover:bg-emerald-100" }, [
                      (openBlock(), createBlock("svg", {
                        class: "h-4 w-4 text-emerald-600",
                        fill: "none",
                        stroke: "currentColor",
                        "stroke-width": "2",
                        viewBox: "0 0 24 24"
                      }, [
                        createVNode("path", {
                          "stroke-linecap": "round",
                          "stroke-linejoin": "round",
                          d: "M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                        })
                      ]))
                    ]),
                    createTextVNode(" Relatórios ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`</div></div>`);
          } else {
            return [
              createVNode("div", { class: "mb-6" }, [
                createVNode("p", { class: "text-sm text-stone-400" }, [
                  createTextVNode(toDisplayString(unref(saudacao)) + ", ", 1),
                  createVNode("span", { class: "font-semibold text-stone-600" }, toDisplayString(unref(firstName)), 1)
                ]),
                createVNode("h1", { class: "mt-0.5 text-2xl font-bold text-stone-800" }, "Dashboard")
              ]),
              createVNode("div", { class: "grid grid-cols-2 gap-4 lg:grid-cols-5" }, [
                createVNode("div", { class: "flex flex-col rounded-xl bg-white p-5 shadow-sm ring-1 ring-stone-100" }, [
                  createVNode("div", { class: "flex items-start justify-between" }, [
                    createVNode("p", { class: "text-xs font-semibold uppercase tracking-wide text-stone-400" }, "Mesas livres"),
                    createVNode("div", { class: "rounded-lg bg-stone-50 p-1.5" }, [
                      (openBlock(), createBlock("svg", {
                        class: "h-4 w-4 text-stone-400",
                        fill: "none",
                        stroke: "currentColor",
                        "stroke-width": "1.75",
                        viewBox: "0 0 24 24"
                      }, [
                        createVNode("path", {
                          "stroke-linecap": "round",
                          "stroke-linejoin": "round",
                          d: "M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"
                        })
                      ]))
                    ])
                  ]),
                  createVNode("p", { class: "mt-3 text-3xl font-bold text-stone-800" }, toDisplayString(__props.totais.mesas_livres), 1),
                  createVNode("p", { class: "mt-1 text-xs text-stone-400" }, "disponíveis agora")
                ]),
                createVNode("div", { class: "flex flex-col rounded-xl bg-white p-5 shadow-sm ring-1 ring-stone-100" }, [
                  createVNode("div", { class: "flex items-start justify-between" }, [
                    createVNode("p", { class: "text-xs font-semibold uppercase tracking-wide text-amber-600" }, "Pedidos ativos"),
                    createVNode("div", { class: "rounded-lg bg-amber-50 p-1.5" }, [
                      (openBlock(), createBlock("svg", {
                        class: "h-4 w-4 text-amber-500",
                        fill: "none",
                        stroke: "currentColor",
                        "stroke-width": "1.75",
                        viewBox: "0 0 24 24"
                      }, [
                        createVNode("path", {
                          "stroke-linecap": "round",
                          "stroke-linejoin": "round",
                          d: "M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                        })
                      ]))
                    ])
                  ]),
                  createVNode("p", { class: "mt-3 text-3xl font-bold text-stone-800" }, toDisplayString(__props.totais.pedidos_ativos), 1),
                  createVNode("p", { class: "mt-1 text-xs text-amber-500" }, "em curso")
                ]),
                createVNode("div", {
                  class: ["flex flex-col rounded-xl p-5 shadow-sm ring-1 transition-colors", __props.totais.socios_em_atraso ? "bg-red-50 ring-red-100" : "bg-white ring-stone-100"]
                }, [
                  createVNode("div", { class: "flex items-start justify-between" }, [
                    createVNode("p", {
                      class: ["text-xs font-semibold uppercase tracking-wide", __props.totais.socios_em_atraso ? "text-red-500" : "text-stone-400"]
                    }, "Sócios em atraso", 2),
                    createVNode("div", {
                      class: ["rounded-lg p-1.5", __props.totais.socios_em_atraso ? "bg-red-100" : "bg-stone-50"]
                    }, [
                      (openBlock(), createBlock("svg", {
                        class: ["h-4 w-4", __props.totais.socios_em_atraso ? "text-red-500" : "text-stone-400"],
                        fill: "none",
                        stroke: "currentColor",
                        "stroke-width": "1.75",
                        viewBox: "0 0 24 24"
                      }, [
                        createVNode("path", {
                          "stroke-linecap": "round",
                          "stroke-linejoin": "round",
                          d: "M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                        })
                      ], 2))
                    ], 2)
                  ]),
                  createVNode("p", {
                    class: ["mt-3 text-3xl font-bold", __props.totais.socios_em_atraso ? "text-red-700" : "text-stone-800"]
                  }, toDisplayString(__props.totais.socios_em_atraso), 3),
                  createVNode("p", {
                    class: ["mt-1 text-xs", __props.totais.socios_em_atraso ? "text-red-400" : "text-stone-400"]
                  }, "cotas em falta", 2)
                ], 2),
                createVNode("div", { class: "flex flex-col rounded-xl bg-white p-5 shadow-sm ring-1 ring-stone-100" }, [
                  createVNode("div", { class: "flex items-start justify-between" }, [
                    createVNode("p", { class: "text-xs font-semibold uppercase tracking-wide text-emerald-600" }, "Fechados hoje"),
                    createVNode("div", { class: "rounded-lg bg-emerald-50 p-1.5" }, [
                      (openBlock(), createBlock("svg", {
                        class: "h-4 w-4 text-emerald-500",
                        fill: "none",
                        stroke: "currentColor",
                        "stroke-width": "1.75",
                        viewBox: "0 0 24 24"
                      }, [
                        createVNode("path", {
                          "stroke-linecap": "round",
                          "stroke-linejoin": "round",
                          d: "M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                        })
                      ]))
                    ])
                  ]),
                  createVNode("p", { class: "mt-3 text-3xl font-bold text-stone-800" }, toDisplayString(__props.totais.pedidos_fechados_hoje), 1),
                  createVNode("p", { class: "mt-1 text-xs text-emerald-500" }, "pedidos concluídos")
                ]),
                createVNode("div", { class: "flex flex-col rounded-xl bg-emerald-50 p-5 shadow-sm ring-1 ring-emerald-100" }, [
                  createVNode("div", { class: "flex items-start justify-between" }, [
                    createVNode("p", { class: "text-xs font-semibold uppercase tracking-wide text-emerald-700" }, "Bar hoje"),
                    createVNode("div", { class: "rounded-lg bg-emerald-100 p-1.5" }, [
                      (openBlock(), createBlock("svg", {
                        class: "h-4 w-4 text-emerald-600",
                        fill: "none",
                        stroke: "currentColor",
                        "stroke-width": "1.75",
                        viewBox: "0 0 24 24"
                      }, [
                        createVNode("path", {
                          "stroke-linecap": "round",
                          "stroke-linejoin": "round",
                          d: "M9 5l7 7-7 7"
                        })
                      ]))
                    ])
                  ]),
                  createVNode("p", { class: "mt-3 text-3xl font-bold text-emerald-800" }, toDisplayString(__props.totais.pedidos_bar_hoje), 1),
                  createVNode("p", { class: "mt-1 text-xs text-emerald-600" }, "pedidos de bar")
                ])
              ]),
              __props.totais.socios_em_atraso ? (openBlock(), createBlock(unref(Link), {
                key: 0,
                href: _ctx.route("socios.emAtraso"),
                class: "mt-5 flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-semibold text-red-700 transition hover:border-red-300 hover:bg-red-100"
              }, {
                default: withCtx(() => [
                  (openBlock(), createBlock("svg", {
                    class: "h-5 w-5 shrink-0 text-red-500",
                    fill: "none",
                    stroke: "currentColor",
                    "stroke-width": "2",
                    viewBox: "0 0 24 24"
                  }, [
                    createVNode("path", {
                      "stroke-linecap": "round",
                      "stroke-linejoin": "round",
                      d: "M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                    })
                  ])),
                  createVNode("span", { class: "flex-1" }, [
                    createVNode("strong", null, toDisplayString(__props.totais.socios_em_atraso), 1),
                    createTextVNode(" " + toDisplayString(__props.totais.socios_em_atraso === 1 ? "sócio" : "sócios") + " com cotas em atraso ", 1)
                  ]),
                  (openBlock(), createBlock("svg", {
                    class: "h-4 w-4 shrink-0 text-red-400",
                    fill: "none",
                    stroke: "currentColor",
                    "stroke-width": "2",
                    viewBox: "0 0 24 24"
                  }, [
                    createVNode("path", {
                      "stroke-linecap": "round",
                      "stroke-linejoin": "round",
                      d: "M9 5l7 7-7 7"
                    })
                  ]))
                ]),
                _: 1
              }, 8, ["href"])) : createCommentVNode("", true),
              createVNode("div", { class: "mt-8" }, [
                createVNode("h2", { class: "mb-3 text-xs font-bold uppercase tracking-widest text-stone-400" }, "Acesso rápido"),
                createVNode("div", { class: "grid grid-cols-2 gap-3 sm:grid-cols-4" }, [
                  createVNode(unref(Link), {
                    href: _ctx.route("pedidos.index"),
                    class: "group flex items-center gap-3 rounded-xl border border-stone-100 bg-white p-4 text-sm font-semibold text-stone-700 shadow-sm transition hover:border-amber-200 hover:bg-amber-50 hover:text-amber-800"
                  }, {
                    default: withCtx(() => [
                      createVNode("div", { class: "rounded-lg bg-amber-50 p-2 transition group-hover:bg-amber-100" }, [
                        (openBlock(), createBlock("svg", {
                          class: "h-4 w-4 text-amber-600",
                          fill: "none",
                          stroke: "currentColor",
                          "stroke-width": "2",
                          viewBox: "0 0 24 24"
                        }, [
                          createVNode("path", {
                            "stroke-linecap": "round",
                            "stroke-linejoin": "round",
                            d: "M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                          })
                        ]))
                      ]),
                      createTextVNode(" Pedidos ")
                    ]),
                    _: 1
                  }, 8, ["href"]),
                  createVNode(unref(Link), {
                    href: _ctx.route("reservas.index"),
                    class: "group flex items-center gap-3 rounded-xl border border-stone-100 bg-white p-4 text-sm font-semibold text-stone-700 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-800"
                  }, {
                    default: withCtx(() => [
                      createVNode("div", { class: "rounded-lg bg-blue-50 p-2 transition group-hover:bg-blue-100" }, [
                        (openBlock(), createBlock("svg", {
                          class: "h-4 w-4 text-blue-600",
                          fill: "none",
                          stroke: "currentColor",
                          "stroke-width": "2",
                          viewBox: "0 0 24 24"
                        }, [
                          createVNode("path", {
                            "stroke-linecap": "round",
                            "stroke-linejoin": "round",
                            d: "M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                          })
                        ]))
                      ]),
                      createTextVNode(" Reservas ")
                    ]),
                    _: 1
                  }, 8, ["href"]),
                  createVNode(unref(Link), {
                    href: _ctx.route("socios.index"),
                    class: "group flex items-center gap-3 rounded-xl border border-stone-100 bg-white p-4 text-sm font-semibold text-stone-700 shadow-sm transition hover:border-violet-200 hover:bg-violet-50 hover:text-violet-800"
                  }, {
                    default: withCtx(() => [
                      createVNode("div", { class: "rounded-lg bg-violet-50 p-2 transition group-hover:bg-violet-100" }, [
                        (openBlock(), createBlock("svg", {
                          class: "h-4 w-4 text-violet-600",
                          fill: "none",
                          stroke: "currentColor",
                          "stroke-width": "2",
                          viewBox: "0 0 24 24"
                        }, [
                          createVNode("path", {
                            "stroke-linecap": "round",
                            "stroke-linejoin": "round",
                            d: "M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"
                          })
                        ]))
                      ]),
                      createTextVNode(" Sócios ")
                    ]),
                    _: 1
                  }, 8, ["href"]),
                  createVNode(unref(Link), {
                    href: _ctx.route("relatorios.index"),
                    class: "group flex items-center gap-3 rounded-xl border border-stone-100 bg-white p-4 text-sm font-semibold text-stone-700 shadow-sm transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-800"
                  }, {
                    default: withCtx(() => [
                      createVNode("div", { class: "rounded-lg bg-emerald-50 p-2 transition group-hover:bg-emerald-100" }, [
                        (openBlock(), createBlock("svg", {
                          class: "h-4 w-4 text-emerald-600",
                          fill: "none",
                          stroke: "currentColor",
                          "stroke-width": "2",
                          viewBox: "0 0 24 24"
                        }, [
                          createVNode("path", {
                            "stroke-linecap": "round",
                            "stroke-linejoin": "round",
                            d: "M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                          })
                        ]))
                      ]),
                      createTextVNode(" Relatórios ")
                    ]),
                    _: 1
                  }, 8, ["href"])
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Dashboard/Index.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
