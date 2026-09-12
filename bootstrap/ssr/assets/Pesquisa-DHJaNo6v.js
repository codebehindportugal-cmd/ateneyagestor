import { ref, watch, onMounted, mergeProps, unref, withCtx, createTextVNode, createVNode, toDisplayString, useSSRContext } from "vue";
import { ssrRenderAttrs, ssrRenderComponent, ssrRenderAttr, ssrRenderList, ssrInterpolate, ssrRenderClass } from "vue/server-renderer";
import { Link, router } from "@inertiajs/vue3";
const _sfc_main = {
  __name: "Pesquisa",
  __ssrInlineRender: true,
  props: { socios: Array, query: String },
  setup(__props) {
    const props = __props;
    const q = ref(props.query || "");
    let timeout = null;
    const teclas = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789".split("");
    const pesquisar = () => router.get(route("pos.cotas.socio.pesquisa"), { q: q.value }, { preserveState: true, replace: true });
    watch(q, () => {
      clearTimeout(timeout);
      timeout = setTimeout(pesquisar, 300);
    });
    onMounted(() => document.querySelector("#pesquisa-socio")?.focus());
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<main${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-gray-900 p-5 text-white" }, _attrs))}><header class="mb-4 flex items-center gap-3">`);
      _push(ssrRenderComponent(unref(Link), {
        href: _ctx.route("pos.cotas.index"),
        class: "rounded-lg bg-gray-800 px-4 py-3 font-black"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`←`);
          } else {
            return [
              createTextVNode("←")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`<input id="pesquisa-socio"${ssrRenderAttr("value", q.value)} class="min-w-0 flex-1 rounded-lg border-gray-700 bg-gray-800 p-4 text-2xl font-black text-white" placeholder="Pesquisar sócio"></header><div class="mb-2 grid grid-cols-7 gap-2 md:grid-cols-10"><!--[-->`);
      ssrRenderList(unref(teclas), (t) => {
        _push(`<button class="rounded bg-gray-800 p-3 text-sm font-black sm:text-base">${ssrInterpolate(t)}</button>`);
      });
      _push(`<!--]--></div><div class="mb-5 grid grid-cols-2 gap-2"><button class="rounded bg-gray-800 p-3 font-black">ESPAÇO</button><button class="rounded bg-red-700 p-3 font-black">APAGAR</button></div>`);
      if (!__props.socios.length) {
        _push(`<div class="rounded-lg bg-gray-800 p-6 text-center"><div class="mb-4 font-black">Nenhum sócio encontrado</div>`);
        _push(ssrRenderComponent(unref(Link), {
          href: _ctx.route("pos.cotas.socio.novo.form"),
          class: "rounded bg-emerald-600 px-5 py-3 font-black"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`+ NOVO SÓCIO`);
            } else {
              return [
                createTextVNode("+ NOVO SÓCIO")
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(`</div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<div class="grid gap-3"><!--[-->`);
      ssrRenderList(__props.socios, (socio) => {
        _push(ssrRenderComponent(unref(Link), {
          key: socio.id,
          href: _ctx.route("pos.cotas.socio", socio.id),
          class: "rounded-lg bg-gray-800 p-4"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<div class="text-2xl font-black"${_scopeId}>${ssrInterpolate(socio.nome)}</div><div class="font-bold text-gray-300"${_scopeId}>N.º ${ssrInterpolate(socio.numero_socio)}</div><div class="${ssrRenderClass([socio.cota_em_dia ? "bg-emerald-600" : "bg-red-600", "mt-2 inline-flex rounded px-3 py-1 font-black"])}"${_scopeId}>${ssrInterpolate(socio.cota_em_dia ? "✅ EM DIA" : `⚠️ ${socio.meses_em_atraso} meses em atraso`)}</div>`);
            } else {
              return [
                createVNode("div", { class: "text-2xl font-black" }, toDisplayString(socio.nome), 1),
                createVNode("div", { class: "font-bold text-gray-300" }, "N.º " + toDisplayString(socio.numero_socio), 1),
                createVNode("div", {
                  class: ["mt-2 inline-flex rounded px-3 py-1 font-black", socio.cota_em_dia ? "bg-emerald-600" : "bg-red-600"]
                }, toDisplayString(socio.cota_em_dia ? "✅ EM DIA" : `⚠️ ${socio.meses_em_atraso} meses em atraso`), 3)
              ];
            }
          }),
          _: 2
        }, _parent));
      });
      _push(`<!--]--></div></main>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/PosCotas/Pesquisa.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
