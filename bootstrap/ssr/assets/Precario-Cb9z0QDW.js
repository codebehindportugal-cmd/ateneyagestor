import { ref, computed, withCtx, unref, createTextVNode, createVNode, openBlock, createBlock, Fragment, renderList, toDisplayString, useSSRContext } from "vue";
import { ssrRenderComponent, ssrRenderStyle, ssrRenderList, ssrInterpolate } from "vue/server-renderer";
import { Link } from "@inertiajs/vue3";
import { _ as _sfc_main$1 } from "./PublicShell-w1n6S5Xb.js";
import "./CookieBanner-Cf0YSWpg.js";
const _sfc_main = {
  __name: "Precario",
  __ssrInlineRender: true,
  props: {
    produtos: Object
  },
  setup(__props) {
    const props = __props;
    const categoriaAtual = ref("todos");
    const categorias = computed(() => Object.keys(props.produtos ?? {}));
    const secoes = computed(() => ["todos", ...categorias.value]);
    const produtosVisiveis = computed(() => {
      if (categoriaAtual.value === "todos") return props.produtos ?? {};
      return { [categoriaAtual.value]: props.produtos?.[categoriaAtual.value] ?? [] };
    });
    const euros = (valor) => `${Number(valor ?? 0).toFixed(2)} EUR`;
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(_sfc_main$1, _attrs, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<main class="min-h-screen px-4 py-10" style="${ssrRenderStyle({ "background": "#0d0a05", "color": "#fffdf8" })}"${_scopeId}><section class="mx-auto max-w-2xl"${_scopeId}><header class="mb-6 flex items-center justify-between gap-4"${_scopeId}><div${_scopeId}><p class="text-xs font-bold uppercase tracking-[0.2em]" style="${ssrRenderStyle({ "color": "#C9A84C" })}"${_scopeId}>ARDC Santana</p><h1 class="mt-1 text-3xl font-bold" style="${ssrRenderStyle({ "color": "#fffdf8" })}"${_scopeId}>Precario</h1></div>`);
            _push2(ssrRenderComponent(unref(Link), {
              href: "/",
              class: "rounded-md px-3 py-2 text-sm font-semibold shadow-sm transition",
              style: { "border": "1px solid rgba(212,175,55,0.25)", "background": "rgba(212,175,55,0.08)", "color": "#C9A84C" }
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` Inicio `);
                } else {
                  return [
                    createTextVNode(" Inicio ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`</header><div class="sticky top-[84px] z-10 -mx-4 mb-5 overflow-x-auto px-4 py-3 backdrop-blur" style="${ssrRenderStyle({ "background": "rgba(13,10,5,0.88)", "border-block": "1px solid rgba(212,175,55,0.08)" })}"${_scopeId}><div class="flex min-w-max gap-2"${_scopeId}><!--[-->`);
            ssrRenderList(secoes.value, (secao) => {
              _push2(`<button type="button" class="rounded-full px-4 py-2 text-sm font-bold transition" style="${ssrRenderStyle(categoriaAtual.value === secao ? "background:#D4AF37;color:#0d0a05;box-shadow:0 12px 30px rgba(212,175,55,0.22)" : "border:1px solid rgba(212,175,55,0.2);background:rgba(255,253,248,0.05);color:rgba(255,253,248,0.72)")}"${_scopeId}>${ssrInterpolate(secao === "todos" ? "Todos" : secao)}</button>`);
            });
            _push2(`<!--]--></div></div>`);
            if (!categorias.value.length) {
              _push2(`<div class="rounded-xl p-8 text-center font-semibold shadow-sm" style="${ssrRenderStyle({ "border": "1px solid rgba(212,175,55,0.18)", "background": "rgba(255,253,248,0.05)", "color": "rgba(255,253,248,0.55)" })}"${_scopeId}> Ainda nao existem produtos disponiveis. </div>`);
            } else {
              _push2(`<div class="space-y-4"${_scopeId}><!--[-->`);
              ssrRenderList(produtosVisiveis.value, (items, categoria) => {
                _push2(`<section class="overflow-hidden rounded-xl shadow-sm" style="${ssrRenderStyle({ "border": "1px solid rgba(212,175,55,0.16)", "background": "rgba(255,253,248,0.055)", "backdrop-filter": "blur(12px)" })}"${_scopeId}><div class="px-5 py-3" style="${ssrRenderStyle({ "border-bottom": "1px solid rgba(212,175,55,0.12)", "background": "rgba(212,175,55,0.06)" })}"${_scopeId}><h2 class="text-lg font-bold" style="${ssrRenderStyle({ "color": "#fffdf8" })}"${_scopeId}>${ssrInterpolate(categoria)}</h2></div><div class="px-5"${_scopeId}><!--[-->`);
                ssrRenderList(items, (produto) => {
                  _push2(`<div class="flex items-center justify-between gap-4 py-3.5" style="${ssrRenderStyle({ "border-bottom": "1px solid rgba(212,175,55,0.06)" })}"${_scopeId}><div class="min-w-0"${_scopeId}><div class="font-semibold" style="${ssrRenderStyle({ "color": "#fffdf8" })}"${_scopeId}>${ssrInterpolate(produto.nome)}</div><div class="mt-0.5 text-xs font-medium uppercase tracking-wide" style="${ssrRenderStyle({ "color": "rgba(255,253,248,0.38)" })}"${_scopeId}>${ssrInterpolate(produto.categoria?.secao || "produto")}</div></div><div class="shrink-0 rounded-full px-3 py-1 text-sm font-bold" style="${ssrRenderStyle({ "background": "#D4AF37", "color": "#0d0a05" })}"${_scopeId}>${ssrInterpolate(euros(produto.preco))}</div></div>`);
                });
                _push2(`<!--]--></div></section>`);
              });
              _push2(`<!--]--></div>`);
            }
            _push2(`</section></main>`);
          } else {
            return [
              createVNode("main", {
                class: "min-h-screen px-4 py-10",
                style: { "background": "#0d0a05", "color": "#fffdf8" }
              }, [
                createVNode("section", { class: "mx-auto max-w-2xl" }, [
                  createVNode("header", { class: "mb-6 flex items-center justify-between gap-4" }, [
                    createVNode("div", null, [
                      createVNode("p", {
                        class: "text-xs font-bold uppercase tracking-[0.2em]",
                        style: { "color": "#C9A84C" }
                      }, "ARDC Santana"),
                      createVNode("h1", {
                        class: "mt-1 text-3xl font-bold",
                        style: { "color": "#fffdf8" }
                      }, "Precario")
                    ]),
                    createVNode(unref(Link), {
                      href: "/",
                      class: "rounded-md px-3 py-2 text-sm font-semibold shadow-sm transition",
                      style: { "border": "1px solid rgba(212,175,55,0.25)", "background": "rgba(212,175,55,0.08)", "color": "#C9A84C" }
                    }, {
                      default: withCtx(() => [
                        createTextVNode(" Inicio ")
                      ]),
                      _: 1
                    })
                  ]),
                  createVNode("div", {
                    class: "sticky top-[84px] z-10 -mx-4 mb-5 overflow-x-auto px-4 py-3 backdrop-blur",
                    style: { "background": "rgba(13,10,5,0.88)", "border-block": "1px solid rgba(212,175,55,0.08)" }
                  }, [
                    createVNode("div", { class: "flex min-w-max gap-2" }, [
                      (openBlock(true), createBlock(Fragment, null, renderList(secoes.value, (secao) => {
                        return openBlock(), createBlock("button", {
                          key: secao,
                          type: "button",
                          class: "rounded-full px-4 py-2 text-sm font-bold transition",
                          style: categoriaAtual.value === secao ? "background:#D4AF37;color:#0d0a05;box-shadow:0 12px 30px rgba(212,175,55,0.22)" : "border:1px solid rgba(212,175,55,0.2);background:rgba(255,253,248,0.05);color:rgba(255,253,248,0.72)",
                          onClick: ($event) => categoriaAtual.value = secao
                        }, toDisplayString(secao === "todos" ? "Todos" : secao), 13, ["onClick"]);
                      }), 128))
                    ])
                  ]),
                  !categorias.value.length ? (openBlock(), createBlock("div", {
                    key: 0,
                    class: "rounded-xl p-8 text-center font-semibold shadow-sm",
                    style: { "border": "1px solid rgba(212,175,55,0.18)", "background": "rgba(255,253,248,0.05)", "color": "rgba(255,253,248,0.55)" }
                  }, " Ainda nao existem produtos disponiveis. ")) : (openBlock(), createBlock("div", {
                    key: 1,
                    class: "space-y-4"
                  }, [
                    (openBlock(true), createBlock(Fragment, null, renderList(produtosVisiveis.value, (items, categoria) => {
                      return openBlock(), createBlock("section", {
                        key: categoria,
                        class: "overflow-hidden rounded-xl shadow-sm",
                        style: { "border": "1px solid rgba(212,175,55,0.16)", "background": "rgba(255,253,248,0.055)", "backdrop-filter": "blur(12px)" }
                      }, [
                        createVNode("div", {
                          class: "px-5 py-3",
                          style: { "border-bottom": "1px solid rgba(212,175,55,0.12)", "background": "rgba(212,175,55,0.06)" }
                        }, [
                          createVNode("h2", {
                            class: "text-lg font-bold",
                            style: { "color": "#fffdf8" }
                          }, toDisplayString(categoria), 1)
                        ]),
                        createVNode("div", { class: "px-5" }, [
                          (openBlock(true), createBlock(Fragment, null, renderList(items, (produto) => {
                            return openBlock(), createBlock("div", {
                              key: produto.id,
                              class: "flex items-center justify-between gap-4 py-3.5",
                              style: { "border-bottom": "1px solid rgba(212,175,55,0.06)" }
                            }, [
                              createVNode("div", { class: "min-w-0" }, [
                                createVNode("div", {
                                  class: "font-semibold",
                                  style: { "color": "#fffdf8" }
                                }, toDisplayString(produto.nome), 1),
                                createVNode("div", {
                                  class: "mt-0.5 text-xs font-medium uppercase tracking-wide",
                                  style: { "color": "rgba(255,253,248,0.38)" }
                                }, toDisplayString(produto.categoria?.secao || "produto"), 1)
                              ]),
                              createVNode("div", {
                                class: "shrink-0 rounded-full px-3 py-1 text-sm font-bold",
                                style: { "background": "#D4AF37", "color": "#0d0a05" }
                              }, toDisplayString(euros(produto.preco)), 1)
                            ]);
                          }), 128))
                        ])
                      ]);
                    }), 128))
                  ]))
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Public/Precario.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
