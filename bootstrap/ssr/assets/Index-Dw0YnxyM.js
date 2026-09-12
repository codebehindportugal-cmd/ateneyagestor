import { ref, onMounted, onBeforeUnmount, mergeProps, unref, withCtx, createVNode, toDisplayString, createTextVNode, useSSRContext } from "vue";
import { ssrRenderAttrs, ssrInterpolate, ssrRenderComponent } from "vue/server-renderer";
import { router, Link } from "@inertiajs/vue3";
const _sfc_main = {
  __name: "Index",
  __ssrInlineRender: true,
  props: { sociosEmAtraso: Number, cobradosHoje: [Number, String], cotasHoje: Number },
  setup(__props) {
    const agora = ref(/* @__PURE__ */ new Date());
    let timer = null;
    let refresh = null;
    const euros = (v) => Number(v ?? 0).toFixed(2) + "€";
    onMounted(() => {
      timer = setInterval(() => agora.value = /* @__PURE__ */ new Date(), 1e3);
      refresh = setInterval(() => router.reload({ preserveScroll: true }), 6e4);
    });
    onBeforeUnmount(() => {
      clearInterval(timer);
      clearInterval(refresh);
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<main${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-gray-900 p-5 text-white" }, _attrs))}><header class="mb-6"><div class="flex items-center justify-between gap-3"><h1 class="text-xl font-black sm:text-3xl">💳 TESOURARIA</h1><div class="flex items-center gap-3"><span class="hidden font-black sm:block">${ssrInterpolate(agora.value.toLocaleTimeString("pt-PT"))}</span><button class="rounded-lg bg-red-600 px-4 py-3 font-black">LOGOUT</button></div></div><p class="mt-1 text-sm font-bold text-gray-400">Associação de Santana · ${ssrInterpolate(agora.value.toLocaleTimeString("pt-PT", { hour: "2-digit", minute: "2-digit" }))}</p></header><section class="grid gap-4 md:grid-cols-3"><div class="rounded-lg bg-emerald-700 p-5"><div class="font-bold">💰 Cobrado Hoje</div><div class="text-4xl font-black">${ssrInterpolate(euros(__props.cobradosHoje))}</div></div><div class="rounded-lg bg-blue-700 p-5"><div class="font-bold">📋 Cotas Hoje</div><div class="text-4xl font-black">${ssrInterpolate(__props.cotasHoje)}</div></div>`);
      _push(ssrRenderComponent(unref(Link), {
        href: _ctx.route("pos.cotas.em-atraso"),
        class: "rounded-lg bg-red-700 p-5"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="font-bold"${_scopeId}>⚠️ Sócios em Atraso</div><div class="text-4xl font-black"${_scopeId}>${ssrInterpolate(__props.sociosEmAtraso)}</div>`);
          } else {
            return [
              createVNode("div", { class: "font-bold" }, "⚠️ Sócios em Atraso"),
              createVNode("div", { class: "text-4xl font-black" }, toDisplayString(__props.sociosEmAtraso), 1)
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</section>`);
      _push(ssrRenderComponent(unref(Link), {
        href: _ctx.route("pos.cotas.socio.pesquisa"),
        class: "mt-8 block rounded-lg bg-blue-600 p-8 text-center text-3xl font-black"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`🔍 PESQUISAR SÓCIO`);
          } else {
            return [
              createTextVNode("🔍 PESQUISAR SÓCIO")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`<section class="mt-4 grid gap-3 md:grid-cols-2">`);
      _push(ssrRenderComponent(unref(Link), {
        href: _ctx.route("pos.cotas.em-atraso"),
        class: "rounded-lg bg-red-600 p-5 text-center font-black"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`📋 LISTA EM ATRASO`);
          } else {
            return [
              createTextVNode("📋 LISTA EM ATRASO")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(unref(Link), {
        href: _ctx.route("pos.cotas.socio.novo.form"),
        class: "rounded-lg bg-emerald-600 p-5 text-center font-black"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`👤 NOVO SÓCIO`);
          } else {
            return [
              createTextVNode("👤 NOVO SÓCIO")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(unref(Link), {
        href: _ctx.route("pos.cotas.resumo-dia"),
        class: "rounded-lg bg-gray-700 p-5 text-center font-black"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`📊 RESUMO DO DIA`);
          } else {
            return [
              createTextVNode("📊 RESUMO DO DIA")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`<button class="rounded-lg bg-gray-700 p-5 font-black">🖨️ IMPRIMIR RESUMO</button></section></main>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/PosCotas/Index.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
