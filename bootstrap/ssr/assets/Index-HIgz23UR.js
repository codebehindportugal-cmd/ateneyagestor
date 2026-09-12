import { unref, withCtx, createTextVNode, createVNode, openBlock, createBlock, Fragment, renderList, toDisplayString, createCommentVNode, useSSRContext } from "vue";
import { ssrRenderComponent, ssrRenderAttr, ssrRenderList, ssrInterpolate } from "vue/server-renderer";
import { _ as _sfc_main$1 } from "./AppLayout-BWDcck4N.js";
import { Head, Link } from "@inertiajs/vue3";
const _sfc_main = {
  __name: "Index",
  __ssrInlineRender: true,
  props: {
    paginas: {
      type: Array,
      default: () => []
    }
  },
  setup(__props) {
    const urlPublica = (slug) => ({
      "sobre-nos": route("pages.sobre-nos"),
      patrocinios: route("patrocinios.index"),
      privacidade: route("legal.privacidade"),
      termos: route("legal.termos"),
      cookies: route("legal.cookies")
    })[slug] || route("home");
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<!--[-->`);
      _push(ssrRenderComponent(unref(Head), { title: "Páginas do site" }, null, _parent));
      _push(ssrRenderComponent(_sfc_main$1, null, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="mb-6 flex flex-wrap items-end justify-between gap-3"${_scopeId}><div${_scopeId}><h1 class="text-2xl font-black"${_scopeId}>Páginas do site</h1><p class="mt-1 text-sm text-slate-500"${_scopeId}>Edita os textos públicos sem mexer no código.</p></div><a${ssrRenderAttr("href", _ctx.route("home"))} target="_blank" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-bold hover:bg-white"${_scopeId}>Ver site</a></div><div class="grid gap-4 lg:grid-cols-2"${_scopeId}><!--[-->`);
            ssrRenderList(__props.paginas, (pagina) => {
              _push2(`<article class="rounded-lg bg-white p-5 shadow-sm"${_scopeId}><div class="flex items-start justify-between gap-4"${_scopeId}><div${_scopeId}><p class="text-xs font-black uppercase tracking-[0.14em] text-slate-400"${_scopeId}>${ssrInterpolate(pagina.slug)}</p><h2 class="mt-2 text-xl font-black"${_scopeId}>${ssrInterpolate(pagina.titulo)}</h2>`);
              if (pagina.updated_at) {
                _push2(`<p class="mt-1 text-sm text-slate-500"${_scopeId}>Atualizada em ${ssrInterpolate(pagina.updated_at)}</p>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`</div><span class="rounded bg-slate-100 px-2 py-1 text-xs font-bold text-slate-500"${_scopeId}>Site</span></div><div class="mt-5 flex flex-wrap gap-2"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Link), {
                href: _ctx.route("paginas.edit", pagina.id),
                class: "rounded-md bg-slate-900 px-4 py-2 text-sm font-bold text-white"
              }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(`Editar`);
                  } else {
                    return [
                      createTextVNode("Editar")
                    ];
                  }
                }),
                _: 2
              }, _parent2, _scopeId));
              _push2(`<a${ssrRenderAttr("href", urlPublica(pagina.slug))} target="_blank" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-bold hover:bg-slate-50"${_scopeId}>Ver página</a></div></article>`);
            });
            _push2(`<!--]--></div>`);
          } else {
            return [
              createVNode("div", { class: "mb-6 flex flex-wrap items-end justify-between gap-3" }, [
                createVNode("div", null, [
                  createVNode("h1", { class: "text-2xl font-black" }, "Páginas do site"),
                  createVNode("p", { class: "mt-1 text-sm text-slate-500" }, "Edita os textos públicos sem mexer no código.")
                ]),
                createVNode("a", {
                  href: _ctx.route("home"),
                  target: "_blank",
                  class: "rounded-md border border-slate-300 px-4 py-2 text-sm font-bold hover:bg-white"
                }, "Ver site", 8, ["href"])
              ]),
              createVNode("div", { class: "grid gap-4 lg:grid-cols-2" }, [
                (openBlock(true), createBlock(Fragment, null, renderList(__props.paginas, (pagina) => {
                  return openBlock(), createBlock("article", {
                    key: pagina.id,
                    class: "rounded-lg bg-white p-5 shadow-sm"
                  }, [
                    createVNode("div", { class: "flex items-start justify-between gap-4" }, [
                      createVNode("div", null, [
                        createVNode("p", { class: "text-xs font-black uppercase tracking-[0.14em] text-slate-400" }, toDisplayString(pagina.slug), 1),
                        createVNode("h2", { class: "mt-2 text-xl font-black" }, toDisplayString(pagina.titulo), 1),
                        pagina.updated_at ? (openBlock(), createBlock("p", {
                          key: 0,
                          class: "mt-1 text-sm text-slate-500"
                        }, "Atualizada em " + toDisplayString(pagina.updated_at), 1)) : createCommentVNode("", true)
                      ]),
                      createVNode("span", { class: "rounded bg-slate-100 px-2 py-1 text-xs font-bold text-slate-500" }, "Site")
                    ]),
                    createVNode("div", { class: "mt-5 flex flex-wrap gap-2" }, [
                      createVNode(unref(Link), {
                        href: _ctx.route("paginas.edit", pagina.id),
                        class: "rounded-md bg-slate-900 px-4 py-2 text-sm font-bold text-white"
                      }, {
                        default: withCtx(() => [
                          createTextVNode("Editar")
                        ]),
                        _: 1
                      }, 8, ["href"]),
                      createVNode("a", {
                        href: urlPublica(pagina.slug),
                        target: "_blank",
                        class: "rounded-md border border-slate-300 px-4 py-2 text-sm font-bold hover:bg-slate-50"
                      }, "Ver página", 8, ["href"])
                    ])
                  ]);
                }), 128))
              ])
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Paginas/Index.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
