import { ref, computed, unref, withCtx, createVNode, toDisplayString, openBlock, createBlock, Fragment, renderList, createCommentVNode, withModifiers, useSSRContext } from "vue";
import { ssrRenderComponent, ssrInterpolate, ssrRenderList, ssrRenderAttr } from "vue/server-renderer";
import { Head } from "@inertiajs/vue3";
import { _ as _sfc_main$1 } from "./PublicShell-w1n6S5Xb.js";
import "./CookieBanner-Cf0YSWpg.js";
const _sfc_main = {
  __name: "SobreNos",
  __ssrInlineRender: true,
  props: {
    page: Object
  },
  setup(__props) {
    const props = __props;
    const lightbox = ref(null);
    const content = computed(() => props.page?.conteudo || {});
    const paragraphs = computed(() => (content.value.corpo || "").split(/\n\s*\n/).map((item) => item.trim()).filter(Boolean));
    const stats = computed(() => (content.value.extra || "").split("\n").map((line) => line.split("|").map((part) => part.trim())).filter((parts) => parts[0] && parts[1]));
    const gallery = [
      ["/images/santana-logo.png", "Símbolo da associação"],
      ["/images/santa-ana.png", "Santa Ana"],
      ["/images/santana-logo.png", "Momentos da festa"],
      ["/images/santa-ana.png", "Comunidade"]
    ];
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<!--[-->`);
      _push(ssrRenderComponent(unref(Head), {
        title: `${__props.page?.titulo || "Sobre Nós"} | ARDC Santana`
      }, null, _parent));
      _push(ssrRenderComponent(_sfc_main$1, null, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<main${_scopeId}><section class="relative isolate overflow-hidden bg-stone-800 px-5 py-24 text-white lg:px-8"${_scopeId}><img src="/images/santa-ana.png" alt="" class="absolute inset-0 -z-10 h-full w-full object-cover opacity-20"${_scopeId}><div class="absolute inset-0 -z-10 bg-gradient-to-r from-stone-900/90 to-stone-800/60"${_scopeId}></div><div class="mx-auto max-w-5xl"${_scopeId}><p class="text-xs font-bold uppercase tracking-[0.2em] text-amber-400"${_scopeId}>Sobre Nós</p><h1 class="mt-4 max-w-3xl text-5xl font-bold leading-tight text-white sm:text-6xl"${_scopeId}>${ssrInterpolate(content.value.hero_titulo || "A nossa história, a nossa gente")}</h1><p class="mt-6 max-w-2xl text-lg leading-relaxed text-stone-200"${_scopeId}>${ssrInterpolate(content.value.hero_subtitulo || "A ARDC Santana é uma casa de cultura, desporto e convívio, construída pela dedicação de várias gerações.")}</p></div></section><div class="h-0.5 bg-gradient-to-r from-transparent via-amber-400 to-transparent"${_scopeId}></div><section class="py-20 bg-amber-50"${_scopeId}><div class="mx-auto grid max-w-7xl gap-12 px-5 lg:grid-cols-[0.9fr_1.1fr] lg:items-start lg:px-8"${_scopeId}><div${_scopeId}><p class="text-xs font-bold uppercase tracking-[0.2em] text-amber-700"${_scopeId}>História</p><h2 class="mt-3 text-4xl font-bold text-stone-800 leading-tight"${_scopeId}>${ssrInterpolate(content.value.introducao || "Uma associação com raízes locais.")}</h2></div><div class="space-y-5 text-lg leading-relaxed text-stone-600"${_scopeId}><!--[-->`);
            ssrRenderList(paragraphs.value, (paragraph) => {
              _push2(`<p${_scopeId}>${ssrInterpolate(paragraph)}</p>`);
            });
            _push2(`<!--]-->`);
            if (!paragraphs.value.length) {
              _push2(`<p${_scopeId}> A ARDC Santana nasceu da vontade de criar um ponto de encontro para a comunidade de Santana, e continua ativa desde 1991 com eventos culturais, desportivos e momentos de convívio que aproximam gerações. </p>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div></div></section>`);
            if (stats.value.length) {
              _push2(`<section class="border-y border-amber-200 bg-white py-14"${_scopeId}><div class="mx-auto grid max-w-7xl gap-4 px-5 sm:grid-cols-2 lg:grid-cols-4 lg:px-8"${_scopeId}><!--[-->`);
              ssrRenderList(stats.value, (stat) => {
                _push2(`<article class="rounded-xl border border-amber-200 bg-amber-50 p-6 text-center shadow-sm"${_scopeId}><div class="text-4xl font-bold text-amber-700"${_scopeId}>${ssrInterpolate(stat[0])}</div><p class="mt-2 text-sm font-medium text-stone-600"${_scopeId}>${ssrInterpolate(stat[1])}</p></article>`);
              });
              _push2(`<!--]--></div></section>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`<section class="py-20 bg-white"${_scopeId}><div class="mx-auto max-w-7xl px-5 lg:px-8"${_scopeId}><div class="mb-10"${_scopeId}><p class="text-xs font-bold uppercase tracking-[0.2em] text-amber-700"${_scopeId}>Galeria</p><h2 class="mt-3 text-4xl font-bold text-stone-800"${_scopeId}>Memórias da Festa de Santa Ana.</h2></div><div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4"${_scopeId}><!--[-->`);
            ssrRenderList(gallery, (item) => {
              _push2(`<button type="button" class="group overflow-hidden rounded-xl border border-amber-200 bg-amber-50 shadow-sm transition hover:border-amber-400 hover:shadow-md"${_scopeId}><img${ssrRenderAttr("src", item[0])}${ssrRenderAttr("alt", item[1])} class="aspect-[4/3] w-full object-contain p-6 transition duration-300 group-hover:scale-105" loading="lazy"${_scopeId}></button>`);
            });
            _push2(`<!--]--></div></div></section><section class="border-t border-amber-100 bg-amber-50 py-20"${_scopeId}><div class="mx-auto max-w-7xl px-5 lg:px-8"${_scopeId}><div class="mb-10"${_scopeId}><p class="text-xs font-bold uppercase tracking-[0.2em] text-amber-700"${_scopeId}>Equipa diretiva</p><h2 class="mt-3 text-4xl font-bold text-stone-800"${_scopeId}>Pessoas ao serviço da associação.</h2></div><div class="overflow-hidden rounded-xl border border-amber-200 shadow-md"${_scopeId}><img src="/images/grupo-recortado.jpg" alt="Grupo ao serviço da associação" class="aspect-[16/9] w-full object-cover" loading="lazy"${_scopeId}></div></div></section></main>`);
            if (lightbox.value) {
              _push2(`<div class="fixed inset-0 z-50 grid place-items-center bg-stone-900/85 p-5 backdrop-blur-sm"${_scopeId}><div class="max-w-3xl w-full overflow-hidden rounded-xl border border-amber-200 bg-white shadow-2xl"${_scopeId}><img${ssrRenderAttr("src", lightbox.value[0])}${ssrRenderAttr("alt", lightbox.value[1])} class="max-h-[70vh] w-full object-contain bg-amber-50 p-4"${_scopeId}><div class="flex items-center justify-between p-4 border-t border-amber-100"${_scopeId}><p class="font-semibold text-stone-700"${_scopeId}>${ssrInterpolate(lightbox.value[1])}</p><button type="button" class="rounded-md border border-amber-200 px-4 py-2 text-sm font-semibold text-stone-700 hover:bg-amber-50 transition"${_scopeId}>Fechar</button></div></div></div>`);
            } else {
              _push2(`<!---->`);
            }
          } else {
            return [
              createVNode("main", null, [
                createVNode("section", { class: "relative isolate overflow-hidden bg-stone-800 px-5 py-24 text-white lg:px-8" }, [
                  createVNode("img", {
                    src: "/images/santa-ana.png",
                    alt: "",
                    class: "absolute inset-0 -z-10 h-full w-full object-cover opacity-20"
                  }),
                  createVNode("div", { class: "absolute inset-0 -z-10 bg-gradient-to-r from-stone-900/90 to-stone-800/60" }),
                  createVNode("div", { class: "mx-auto max-w-5xl" }, [
                    createVNode("p", { class: "text-xs font-bold uppercase tracking-[0.2em] text-amber-400" }, "Sobre Nós"),
                    createVNode("h1", { class: "mt-4 max-w-3xl text-5xl font-bold leading-tight text-white sm:text-6xl" }, toDisplayString(content.value.hero_titulo || "A nossa história, a nossa gente"), 1),
                    createVNode("p", { class: "mt-6 max-w-2xl text-lg leading-relaxed text-stone-200" }, toDisplayString(content.value.hero_subtitulo || "A ARDC Santana é uma casa de cultura, desporto e convívio, construída pela dedicação de várias gerações."), 1)
                  ])
                ]),
                createVNode("div", { class: "h-0.5 bg-gradient-to-r from-transparent via-amber-400 to-transparent" }),
                createVNode("section", { class: "py-20 bg-amber-50" }, [
                  createVNode("div", { class: "mx-auto grid max-w-7xl gap-12 px-5 lg:grid-cols-[0.9fr_1.1fr] lg:items-start lg:px-8" }, [
                    createVNode("div", null, [
                      createVNode("p", { class: "text-xs font-bold uppercase tracking-[0.2em] text-amber-700" }, "História"),
                      createVNode("h2", { class: "mt-3 text-4xl font-bold text-stone-800 leading-tight" }, toDisplayString(content.value.introducao || "Uma associação com raízes locais."), 1)
                    ]),
                    createVNode("div", { class: "space-y-5 text-lg leading-relaxed text-stone-600" }, [
                      (openBlock(true), createBlock(Fragment, null, renderList(paragraphs.value, (paragraph) => {
                        return openBlock(), createBlock("p", { key: paragraph }, toDisplayString(paragraph), 1);
                      }), 128)),
                      !paragraphs.value.length ? (openBlock(), createBlock("p", { key: 0 }, " A ARDC Santana nasceu da vontade de criar um ponto de encontro para a comunidade de Santana, e continua ativa desde 1991 com eventos culturais, desportivos e momentos de convívio que aproximam gerações. ")) : createCommentVNode("", true)
                    ])
                  ])
                ]),
                stats.value.length ? (openBlock(), createBlock("section", {
                  key: 0,
                  class: "border-y border-amber-200 bg-white py-14"
                }, [
                  createVNode("div", { class: "mx-auto grid max-w-7xl gap-4 px-5 sm:grid-cols-2 lg:grid-cols-4 lg:px-8" }, [
                    (openBlock(true), createBlock(Fragment, null, renderList(stats.value, (stat) => {
                      return openBlock(), createBlock("article", {
                        key: stat[1],
                        class: "rounded-xl border border-amber-200 bg-amber-50 p-6 text-center shadow-sm"
                      }, [
                        createVNode("div", { class: "text-4xl font-bold text-amber-700" }, toDisplayString(stat[0]), 1),
                        createVNode("p", { class: "mt-2 text-sm font-medium text-stone-600" }, toDisplayString(stat[1]), 1)
                      ]);
                    }), 128))
                  ])
                ])) : createCommentVNode("", true),
                createVNode("section", { class: "py-20 bg-white" }, [
                  createVNode("div", { class: "mx-auto max-w-7xl px-5 lg:px-8" }, [
                    createVNode("div", { class: "mb-10" }, [
                      createVNode("p", { class: "text-xs font-bold uppercase tracking-[0.2em] text-amber-700" }, "Galeria"),
                      createVNode("h2", { class: "mt-3 text-4xl font-bold text-stone-800" }, "Memórias da Festa de Santa Ana.")
                    ]),
                    createVNode("div", { class: "grid gap-3 sm:grid-cols-2 lg:grid-cols-4" }, [
                      (openBlock(), createBlock(Fragment, null, renderList(gallery, (item) => {
                        return createVNode("button", {
                          key: item[1],
                          type: "button",
                          class: "group overflow-hidden rounded-xl border border-amber-200 bg-amber-50 shadow-sm transition hover:border-amber-400 hover:shadow-md",
                          onClick: ($event) => lightbox.value = item
                        }, [
                          createVNode("img", {
                            src: item[0],
                            alt: item[1],
                            class: "aspect-[4/3] w-full object-contain p-6 transition duration-300 group-hover:scale-105",
                            loading: "lazy"
                          }, null, 8, ["src", "alt"])
                        ], 8, ["onClick"]);
                      }), 64))
                    ])
                  ])
                ]),
                createVNode("section", { class: "border-t border-amber-100 bg-amber-50 py-20" }, [
                  createVNode("div", { class: "mx-auto max-w-7xl px-5 lg:px-8" }, [
                    createVNode("div", { class: "mb-10" }, [
                      createVNode("p", { class: "text-xs font-bold uppercase tracking-[0.2em] text-amber-700" }, "Equipa diretiva"),
                      createVNode("h2", { class: "mt-3 text-4xl font-bold text-stone-800" }, "Pessoas ao serviço da associação.")
                    ]),
                    createVNode("div", { class: "overflow-hidden rounded-xl border border-amber-200 shadow-md" }, [
                      createVNode("img", {
                        src: "/images/grupo-recortado.jpg",
                        alt: "Grupo ao serviço da associação",
                        class: "aspect-[16/9] w-full object-cover",
                        loading: "lazy"
                      })
                    ])
                  ])
                ])
              ]),
              lightbox.value ? (openBlock(), createBlock("div", {
                key: 0,
                class: "fixed inset-0 z-50 grid place-items-center bg-stone-900/85 p-5 backdrop-blur-sm",
                onClick: withModifiers(($event) => lightbox.value = null, ["self"])
              }, [
                createVNode("div", { class: "max-w-3xl w-full overflow-hidden rounded-xl border border-amber-200 bg-white shadow-2xl" }, [
                  createVNode("img", {
                    src: lightbox.value[0],
                    alt: lightbox.value[1],
                    class: "max-h-[70vh] w-full object-contain bg-amber-50 p-4"
                  }, null, 8, ["src", "alt"]),
                  createVNode("div", { class: "flex items-center justify-between p-4 border-t border-amber-100" }, [
                    createVNode("p", { class: "font-semibold text-stone-700" }, toDisplayString(lightbox.value[1]), 1),
                    createVNode("button", {
                      type: "button",
                      class: "rounded-md border border-amber-200 px-4 py-2 text-sm font-semibold text-stone-700 hover:bg-amber-50 transition",
                      onClick: ($event) => lightbox.value = null
                    }, "Fechar", 8, ["onClick"])
                  ])
                ])
              ], 8, ["onClick"])) : createCommentVNode("", true)
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`<!--]-->`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Public/SobreNos.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
