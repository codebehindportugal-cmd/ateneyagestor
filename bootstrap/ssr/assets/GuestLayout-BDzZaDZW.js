import { mergeProps, unref, withCtx, createVNode, createTextVNode, useSSRContext } from "vue";
import { ssrRenderAttrs, ssrRenderComponent, ssrRenderAttr, ssrRenderStyle, ssrRenderSlot } from "vue/server-renderer";
import { Link } from "@inertiajs/vue3";
import { _ as _export_sfc } from "./_plugin-vue_export-helper-1tPrXgE0.js";
const logo = "/images/santana-logo.png";
const _sfc_main = {
  __name: "GuestLayout",
  __ssrInlineRender: true,
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "guest-wrapper" }, _attrs))} data-v-65ab688a><div class="pointer-events-none absolute inset-0 overflow-hidden" data-v-65ab688a><div class="orb orb-1" data-v-65ab688a></div><div class="orb orb-2" data-v-65ab688a></div></div><div class="relative z-10 flex min-h-screen flex-col items-center justify-center px-4 py-12" data-v-65ab688a><div class="mb-8 text-center" data-v-65ab688a>`);
      _push(ssrRenderComponent(unref(Link), {
        href: _ctx.route("home"),
        class: "guest-logo-link inline-block"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<img${ssrRenderAttr("src", logo)} alt="ARDC Santana" class="guest-logo mx-auto h-20 w-auto" data-v-65ab688a${_scopeId}>`);
          } else {
            return [
              createVNode("img", {
                src: logo,
                alt: "ARDC Santana",
                class: "guest-logo mx-auto h-20 w-auto"
              })
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`<p class="mt-3 text-sm font-semibold" style="${ssrRenderStyle({ "color": "#C9A84C" })}" data-v-65ab688a>Associação de Santana</p></div><div class="guest-card w-full max-w-md rounded-2xl p-8 shadow-2xl" data-v-65ab688a><div class="guest-card-accent" data-v-65ab688a></div>`);
      ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent);
      _push(`</div><div class="mt-8 flex flex-wrap justify-center gap-4 text-xs" style="${ssrRenderStyle({ "color": "rgba(255,253,248,0.3)" })}" data-v-65ab688a>`);
      _push(ssrRenderComponent(unref(Link), {
        href: _ctx.route("legal.privacidade"),
        class: "guest-footer-link transition"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`Privacidade`);
          } else {
            return [
              createTextVNode("Privacidade")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(unref(Link), {
        href: _ctx.route("legal.termos"),
        class: "guest-footer-link transition"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`Termos`);
          } else {
            return [
              createTextVNode("Termos")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(unref(Link), {
        href: _ctx.route("legal.cookies"),
        class: "guest-footer-link transition"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`Cookies`);
          } else {
            return [
              createTextVNode("Cookies")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div></div></div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Layouts/GuestLayout.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const GuestLayout = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-65ab688a"]]);
export {
  GuestLayout as G
};
