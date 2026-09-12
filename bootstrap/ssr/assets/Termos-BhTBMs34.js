import { computed, unref, withCtx, createVNode, toDisplayString, openBlock, createBlock, Fragment, renderList, useSSRContext } from "vue";
import { ssrRenderComponent, ssrInterpolate, ssrRenderList } from "vue/server-renderer";
import { Head } from "@inertiajs/vue3";
import { _ as _sfc_main$1 } from "./PublicShell-w1n6S5Xb.js";
import "./CookieBanner-Cf0YSWpg.js";
const _sfc_main = {
  __name: "Termos",
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
        title: `${__props.page?.titulo || "Termos e Condições"} | ARDC Santana`
      }, null, _parent));
      _push(ssrRenderComponent(_sfc_main$1, null, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<main class="py-20 bg-amber-50"${_scopeId}><article class="mx-auto max-w-4xl px-5 lg:px-8"${_scopeId}><p class="text-xs font-bold uppercase tracking-[0.2em] text-amber-700"${_scopeId}>Legal</p><h1 class="mt-3 text-4xl font-bold text-stone-800"${_scopeId}>${ssrInterpolate(content.value.hero_titulo || "Termos e Condições")}</h1><p class="mt-3 text-sm font-semibold text-stone-400"${_scopeId}>${ssrInterpolate(content.value.hero_subtitulo || "Última atualização: 15/06/2026")}</p><div class="mt-8 space-y-5 leading-relaxed text-stone-600"${_scopeId}><!--[-->`);
            ssrRenderList(paragraphs.value, (paragraph) => {
              _push2(`<p${_scopeId}>${ssrInterpolate(paragraph)}</p>`);
            });
            _push2(`<!--]--></div></article></main>`);
          } else {
            return [
              createVNode("main", { class: "py-20 bg-amber-50" }, [
                createVNode("article", { class: "mx-auto max-w-4xl px-5 lg:px-8" }, [
                  createVNode("p", { class: "text-xs font-bold uppercase tracking-[0.2em] text-amber-700" }, "Legal"),
                  createVNode("h1", { class: "mt-3 text-4xl font-bold text-stone-800" }, toDisplayString(content.value.hero_titulo || "Termos e Condições"), 1),
                  createVNode("p", { class: "mt-3 text-sm font-semibold text-stone-400" }, toDisplayString(content.value.hero_subtitulo || "Última atualização: 15/06/2026"), 1),
                  createVNode("div", { class: "mt-8 space-y-5 leading-relaxed text-stone-600" }, [
                    (openBlock(true), createBlock(Fragment, null, renderList(paragraphs.value, (paragraph) => {
                      return openBlock(), createBlock("p", { key: paragraph }, toDisplayString(paragraph), 1);
                    }), 128))
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Legal/Termos.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
