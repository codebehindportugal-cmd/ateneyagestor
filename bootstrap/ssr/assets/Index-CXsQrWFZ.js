import { ref, computed, onMounted, onBeforeUnmount, mergeProps, unref, withCtx, createTextVNode, useSSRContext } from "vue";
import { ssrRenderAttrs, ssrInterpolate, ssrRenderComponent } from "vue/server-renderer";
import { useForm, router, Link } from "@inertiajs/vue3";
const _sfc_main = {
  __name: "Index",
  __ssrInlineRender: true,
  props: { posNome: String, vendasHoje: [Number, String], mesasLivres: Number, mesasOcupadas: Number, mesas: Array, zonas: Array },
  setup(__props) {
    const props = __props;
    const agora = ref(/* @__PURE__ */ new Date());
    let relogio = null;
    let refresh = null;
    ref(false);
    ref(null);
    useForm({ zonas: [] });
    const euros = (v) => Number(v ?? 0).toFixed(2) + "€";
    computed(() => Object.groupBy(props.mesas ?? [], (m) => m.localizacao || "Sala"));
    onMounted(() => {
      relogio = setInterval(() => agora.value = /* @__PURE__ */ new Date(), 1e3);
      refresh = setInterval(() => router.reload({ preserveScroll: true }), 3e4);
    });
    onBeforeUnmount(() => {
      clearInterval(relogio);
      clearInterval(refresh);
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<main${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-gray-900 p-5 text-white" }, _attrs))}><header class="mb-6 flex items-center justify-between gap-3"><div><h1 class="text-xl font-black sm:text-3xl">POS RESTAURANTE</h1><p class="font-bold text-gray-300">${ssrInterpolate(__props.posNome)} · ${ssrInterpolate(agora.value.toLocaleTimeString("pt-PT"))}</p></div><div class="flex gap-2"><button class="rounded-lg bg-red-600 px-4 py-2 font-black sm:px-5 sm:py-3">LOGOUT</button></div></header><section class="mb-6 grid gap-4 md:grid-cols-3"><div class="rounded-lg bg-emerald-700 p-5"><div class="font-bold">Mesas Livres</div><div class="text-5xl font-black">${ssrInterpolate(__props.mesasLivres)}</div></div><div class="rounded-lg bg-red-700 p-5"><div class="font-bold">Mesas Ocupadas</div><div class="text-5xl font-black">${ssrInterpolate(__props.mesasOcupadas)}</div></div><div class="rounded-lg bg-blue-700 p-5"><div class="font-bold">Vendas Hoje</div><div class="text-4xl font-black">${ssrInterpolate(euros(__props.vendasHoje))}</div></div></section><div class="mt-6 grid gap-3">`);
      _push(ssrRenderComponent(unref(Link), {
        href: _ctx.route("pos.rest.mesas"),
        class: "block rounded-lg bg-emerald-600 p-8 text-center text-3xl font-black"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`VER MESAS EM LISTA`);
          } else {
            return [
              createTextVNode("VER MESAS EM LISTA")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div></main>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/PosRest/Index.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
