import { computed, unref, withCtx, createTextVNode, createVNode, toDisplayString, openBlock, createBlock, Fragment, renderList, useSSRContext } from "vue";
import { ssrRenderComponent, ssrInterpolate, ssrRenderList } from "vue/server-renderer";
import { Head, Link } from "@inertiajs/vue3";
import { _ as _sfc_main$1 } from "./PublicShell-w1n6S5Xb.js";
import "./CookieBanner-Cf0YSWpg.js";
const _sfc_main = {
  __name: "Cookies",
  __ssrInlineRender: true,
  props: {
    page: Object
  },
  setup(__props) {
    const props = __props;
    const content = computed(() => props.page?.conteudo || {});
    const paragraphs = computed(() => (content.value.corpo || "").split(/\n\s*\n/).map((item) => item.trim()).filter(Boolean));
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<!--[-->`);
      _push(ssrRenderComponent(unref(Head), {
        title: `${__props.page?.titulo || "Política de Cookies"} | ARDC Santana`
      }, null, _parent));
      _push(ssrRenderComponent(_sfc_main$1, null, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<main class="py-20 bg-amber-50"${_scopeId}><article class="mx-auto max-w-4xl px-5 lg:px-8"${_scopeId}><p class="text-xs font-bold uppercase tracking-[0.2em] text-amber-700"${_scopeId}>Legal</p><h1 class="mt-3 text-4xl font-bold text-stone-800"${_scopeId}>${ssrInterpolate(content.value.hero_titulo || "Política de Cookies")}</h1><p class="mt-3 text-sm font-semibold text-stone-400"${_scopeId}>${ssrInterpolate(content.value.hero_subtitulo || "Última atualização: 15/06/2026")}</p><div class="mt-8 space-y-5 leading-relaxed text-stone-600"${_scopeId}><!--[-->`);
            ssrRenderList(paragraphs.value, (paragraph) => {
              _push2(`<p${_scopeId}>${ssrInterpolate(paragraph)}</p>`);
            });
            _push2(`<!--]--></div><div class="mt-10 rounded-xl border border-amber-200 bg-white p-6"${_scopeId}><h2 class="text-2xl font-bold text-stone-800"${_scopeId}>Privacidade</h2><p class="mt-3 text-stone-600"${_scopeId}>Consulte também a `);
            _push2(ssrRenderComponent(unref(Link), {
              href: _ctx.route("legal.privacidade"),
              class: "font-semibold text-amber-700 hover:text-amber-900 transition underline"
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`Política de Privacidade`);
                } else {
                  return [
                    createTextVNode("Política de Privacidade")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`.</p></div></article></main>`);
          } else {
            return [
              createVNode("main", { class: "py-20 bg-amber-50" }, [
                createVNode("article", { class: "mx-auto max-w-4xl px-5 lg:px-8" }, [
                  createVNode("p", { class: "text-xs font-bold uppercase tracking-[0.2em] text-amber-700" }, "Legal"),
                  createVNode("h1", { class: "mt-3 text-4xl font-bold text-stone-800" }, toDisplayString(content.value.hero_titulo || "Política de Cookies"), 1),
                  createVNode("p", { class: "mt-3 text-sm font-semibold text-stone-400" }, toDisplayString(content.value.hero_subtitulo || "Última atualização: 15/06/2026"), 1),
                  createVNode("div", { class: "mt-8 space-y-5 leading-relaxed text-stone-600" }, [
                    (openBlock(true), createBlock(Fragment, null, renderList(paragraphs.value, (paragraph) => {
                      return openBlock(), createBlock("p", { key: paragraph }, toDisplayString(paragraph), 1);
                    }), 128))
                  ]),
                  createVNode("div", { class: "mt-10 rounded-xl border border-amber-200 bg-white p-6" }, [
                    createVNode("h2", { class: "text-2xl font-bold text-stone-800" }, "Privacidade"),
                    createVNode("p", { class: "mt-3 text-stone-600" }, [
                      createTextVNode("Consulte também a "),
                      createVNode(unref(Link), {
                        href: _ctx.route("legal.privacidade"),
                        class: "font-semibold text-amber-700 hover:text-amber-900 transition underline"
                      }, {
                        default: withCtx(() => [
                          createTextVNode("Política de Privacidade")
                        ]),
                        _: 1
                      }, 8, ["href"]),
                      createTextVNode(".")
                    ])
                  ])
                ])
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Legal/Cookies.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
