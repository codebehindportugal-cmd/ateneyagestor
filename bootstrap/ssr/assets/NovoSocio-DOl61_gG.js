import { mergeProps, unref, withCtx, createTextVNode, useSSRContext } from "vue";
import { ssrRenderAttrs, ssrRenderAttr, ssrInterpolate, ssrRenderComponent } from "vue/server-renderer";
import { useForm, Link } from "@inertiajs/vue3";
const _sfc_main = {
  __name: "NovoSocio",
  __ssrInlineRender: true,
  props: { proximoNumero: String },
  setup(__props) {
    const props = __props;
    const form = useForm({ numero_socio: props.proximoNumero, nome: "", telefone: "", email: "" });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<main${ssrRenderAttrs(mergeProps({ class: "flex min-h-screen items-center justify-center bg-gray-900 p-5 text-white" }, _attrs))}><form class="w-full max-w-lg rounded-lg bg-gray-800 p-5"><h1 class="mb-5 text-3xl font-black">NOVO SÓCIO</h1><label class="mb-3 block font-black">Número de sócio<input${ssrRenderAttr("value", unref(form).numero_socio)} class="mt-1 w-full rounded bg-gray-900 p-4 text-xl text-white"></label><label class="mb-3 block font-black">Nome<input${ssrRenderAttr("value", unref(form).nome)} class="mt-1 w-full rounded bg-gray-900 p-4 text-xl text-white"></label><label class="mb-3 block font-black">Telefone<input${ssrRenderAttr("value", unref(form).telefone)} class="mt-1 w-full rounded bg-gray-900 p-4 text-xl text-white"></label><label class="mb-3 block font-black">Email<input${ssrRenderAttr("value", unref(form).email)} class="mt-1 w-full rounded bg-gray-900 p-4 text-xl text-white"></label>`);
      if (Object.keys(unref(form).errors).length) {
        _push(`<div class="mb-3 rounded bg-red-700 p-3 font-bold">${ssrInterpolate(Object.values(unref(form).errors)[0])}</div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<button class="w-full rounded bg-emerald-600 p-5 text-xl font-black">✅ CRIAR SÓCIO</button>`);
      _push(ssrRenderComponent(unref(Link), {
        href: _ctx.route("pos.cotas.index"),
        class: "mt-3 block rounded bg-gray-700 p-4 text-center font-black"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`← CANCELAR`);
          } else {
            return [
              createTextVNode("← CANCELAR")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</form></main>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/PosCotas/NovoSocio.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
