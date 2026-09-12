import { ref, onMounted, mergeProps, unref, withCtx, createTextVNode, useSSRContext } from "vue";
import { ssrRenderAttrs, ssrRenderComponent } from "vue/server-renderer";
import { Link } from "@inertiajs/vue3";
const _sfc_main = {
  __name: "CookieBanner",
  __ssrInlineRender: true,
  setup(__props) {
    const visible = ref(false);
    onMounted(() => {
      visible.value = !localStorage.getItem("cookie_consent");
    });
    return (_ctx, _push, _parent, _attrs) => {
      if (visible.value) {
        _push(`<div${ssrRenderAttrs(mergeProps({ class: "public-theme fixed inset-x-0 bottom-0 z-50 bg-slate-950 p-4 text-white shadow-2xl" }, _attrs))}><div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 md:flex-row"><p class="text-sm text-slate-200"> Utilizamos cookies para melhorar a sua experiência. `);
        _push(ssrRenderComponent(unref(Link), {
          href: _ctx.route("legal.cookies"),
          class: "font-semibold underline"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`Saiba mais`);
            } else {
              return [
                createTextVNode("Saiba mais")
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(`. </p><div class="flex gap-3"><button type="button" class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700"> Aceitar </button><button type="button" class="rounded-md bg-slate-700 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-600"> Só essenciais </button></div></div></div>`);
      } else {
        _push(`<!---->`);
      }
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/CookieBanner.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as _
};
