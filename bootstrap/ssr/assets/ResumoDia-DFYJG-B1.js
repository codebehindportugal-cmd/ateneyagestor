import { computed, mergeProps, unref, withCtx, createTextVNode, useSSRContext } from "vue";
import { ssrRenderAttrs, ssrInterpolate, ssrRenderComponent, ssrRenderList } from "vue/server-renderer";
import { Link } from "@inertiajs/vue3";
const _sfc_main = {
  __name: "ResumoDia",
  __ssrInlineRender: true,
  props: { cotas: Array },
  setup(__props) {
    const props = __props;
    const euros = (v) => Number(v ?? 0).toFixed(2) + "€";
    const total = computed(() => (props.cotas ?? []).reduce((s, c) => s + Number(c.valor ?? 0), 0));
    const porMetodo = computed(() => (props.cotas ?? []).reduce((acc, c) => {
      acc[c.metodo_pagamento || "outro"] = (acc[c.metodo_pagamento || "outro"] || 0) + Number(c.valor);
      return acc;
    }, {}));
    const meses = ["Jan", "Fev", "Mar", "Abr", "Mai", "Jun", "Jul", "Ago", "Set", "Out", "Nov", "Dez"];
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<main${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-gray-900 p-5 text-white" }, _attrs))}><header class="mb-5 flex items-center justify-between"><h1 class="text-3xl font-black">📊 RESUMO DO DIA - ${ssrInterpolate((/* @__PURE__ */ new Date()).toLocaleDateString("pt-PT"))}</h1>`);
      _push(ssrRenderComponent(unref(Link), {
        href: _ctx.route("pos.cotas.index"),
        class: "rounded-lg bg-gray-800 px-4 py-3 font-black print:hidden"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`← VOLTAR`);
          } else {
            return [
              createTextVNode("← VOLTAR")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</header><section class="mb-5 grid gap-3 md:grid-cols-4"><div class="rounded bg-emerald-700 p-4"><div>Total cobrado</div><strong class="text-3xl">${ssrInterpolate(euros(total.value))}</strong></div><div class="rounded bg-blue-700 p-4"><div>N.º cotas</div><strong class="text-3xl">${ssrInterpolate(__props.cotas.length)}</strong></div><!--[-->`);
      ssrRenderList(porMetodo.value, (valor, metodo) => {
        _push(`<div class="rounded bg-gray-700 p-4"><div class="uppercase">${ssrInterpolate(metodo)}</div><strong class="text-2xl">${ssrInterpolate(euros(valor))}</strong></div>`);
      });
      _push(`<!--]--></section><table class="w-full rounded bg-gray-800 text-left"><thead><tr class="border-b border-gray-700"><th class="p-3">Hora</th><th>Sócio</th><th>Período</th><th>Valor</th><th>Método</th></tr></thead><tbody><!--[-->`);
      ssrRenderList(__props.cotas, (cota) => {
        _push(`<tr class="border-b border-gray-700"><td class="p-3">${ssrInterpolate(new Date(cota.updated_at).toLocaleTimeString("pt-PT", { hour: "2-digit", minute: "2-digit" }))}</td><td>${ssrInterpolate(cota.socio?.nome)}</td><td>${ssrInterpolate(cota.tipo === "anual" ? "Anual" : meses[cota.mes - 1])} ${ssrInterpolate(cota.ano)}</td><td>${ssrInterpolate(euros(cota.valor))}</td><td>${ssrInterpolate(cota.metodo_pagamento)}</td></tr>`);
      });
      _push(`<!--]--></tbody></table><button class="mt-4 rounded bg-gray-700 px-5 py-3 font-black print:hidden">🖨️ IMPRIMIR RESUMO</button></main>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/PosCotas/ResumoDia.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
