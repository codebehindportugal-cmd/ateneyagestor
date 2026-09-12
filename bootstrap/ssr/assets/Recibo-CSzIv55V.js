import { onMounted, mergeProps, unref, withCtx, createTextVNode, useSSRContext } from "vue";
import { ssrRenderAttrs, ssrInterpolate, ssrRenderComponent } from "vue/server-renderer";
import { Link } from "@inertiajs/vue3";
const _sfc_main = {
  __name: "Recibo",
  __ssrInlineRender: true,
  props: { cota: Object },
  setup(__props) {
    const meses = ["Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"];
    const euros = (v) => Number(v ?? 0).toFixed(2) + "€";
    const periodo = (c) => c.tipo === "anual" ? `Anual ${c.ano}` : `${meses[c.mes - 1]} ${c.ano}`;
    onMounted(() => setTimeout(() => window.print(), 300));
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<main${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-gray-200 p-5 text-gray-950 print:bg-white" }, _attrs))}><section class="mx-auto max-w-sm bg-white p-5 text-center font-mono"><h1 class="font-black">Associação de Santana</h1><h2 class="mb-3 font-black">RECIBO DE PAGAMENTO DE COTA</h2><div class="border-y py-3 text-left">Recibo: #${ssrInterpolate(__props.cota.id)}<br>Nome: ${ssrInterpolate(__props.cota.socio?.nome)}<br>Sócio: ${ssrInterpolate(__props.cota.socio?.numero_socio)}<br>Período: ${ssrInterpolate(periodo(__props.cota))}<br>Valor: ${ssrInterpolate(euros(__props.cota.valor))}<br>Método: ${ssrInterpolate(__props.cota.metodo_pagamento)}<br>Data: ${ssrInterpolate(new Date(__props.cota.data_pagamento).toLocaleString("pt-PT"))}</div><p class="mt-3">Obrigado pela sua contribuição!</p></section><div class="mx-auto mt-5 grid max-w-sm gap-2 print:hidden"><button class="rounded bg-gray-900 p-3 font-black text-white">🖨️ IMPRIMIR RECIBO</button>`);
      _push(ssrRenderComponent(unref(Link), {
        href: _ctx.route("pos.cotas.index"),
        class: "rounded bg-blue-600 p-3 text-center font-black text-white"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`OUTRO SÓCIO`);
          } else {
            return [
              createTextVNode("OUTRO SÓCIO")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(unref(Link), {
        href: _ctx.route("pos.cotas.socio", __props.cota.socio_id),
        class: "rounded bg-emerald-600 p-3 text-center font-black text-white"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`MESMO SÓCIO`);
          } else {
            return [
              createTextVNode("MESMO SÓCIO")
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/PosCotas/Recibo.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
