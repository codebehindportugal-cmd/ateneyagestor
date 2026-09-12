import { ref, computed, mergeProps, unref, withCtx, createTextVNode, useSSRContext } from "vue";
import { ssrRenderAttrs, ssrInterpolate, ssrRenderClass, ssrRenderList, ssrRenderAttr, ssrIncludeBooleanAttr, ssrLooseContain, ssrRenderComponent } from "vue/server-renderer";
import { useForm, Link } from "@inertiajs/vue3";
const _sfc_main = {
  __name: "Socio",
  __ssrInlineRender: true,
  props: { socio: Object, cotasRecentes: Array, mesesEmAtraso: Number, valorEmDivida: Number },
  setup(__props) {
    const props = __props;
    const tipo = ref("mensal");
    const ano = ref((/* @__PURE__ */ new Date()).getFullYear());
    const metodo = ref("dinheiro");
    const recebido = ref("");
    const meses = ["Jan", "Fev", "Mar", "Abr", "Mai", "Jun", "Jul", "Ago", "Set", "Out", "Nov", "Dez"];
    const pagos = computed(() => new Set((props.cotasRecentes ?? []).filter((c) => c.estado === "pago" && c.ano === ano.value && c.mes).map((c) => c.mes)));
    const selecionados = ref(Array.from({ length: (/* @__PURE__ */ new Date()).getMonth() + 1 }, (_, i) => i + 1).filter((m) => !pagos.value.has(m)));
    const total = computed(() => tipo.value === "anual" ? 50 : selecionados.value.length * 5);
    const troco = computed(() => Math.max(0, Number(recebido.value || total.value) - total.value));
    useForm({ tipo: "mensal", meses: [], ano: ano.value, valor: 0, metodo_pagamento: "dinheiro", valor_recebido: null });
    const euros = (v) => Number(v ?? 0).toFixed(2) + "€";
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<main${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-gray-900 p-5 text-white" }, _attrs))}><div class="grid gap-5 lg:grid-cols-2"><section class="rounded-lg bg-gray-800 p-5"><h1 class="text-3xl font-black">${ssrInterpolate(__props.socio.nome)}</h1><p class="font-bold text-gray-300">Sócio N.º ${ssrInterpolate(__props.socio.numero_socio)} · ${ssrInterpolate(__props.socio.telefone)} · ${ssrInterpolate(__props.socio.email)}</p><div class="${ssrRenderClass([__props.socio.cota_em_dia ? "bg-emerald-600" : "bg-red-600", "my-5 rounded-lg p-4 text-xl font-black"])}">${ssrInterpolate(__props.socio.cota_em_dia ? "✅ COTA EM DIA" : `⚠️ ${__props.mesesEmAtraso} MESES EM ATRASO - ${euros(__props.valorEmDivida)} em dívida`)}</div><table class="w-full text-left text-sm"><tbody><!--[-->`);
      ssrRenderList(__props.cotasRecentes.slice(0, 12), (cota) => {
        _push(`<tr class="border-b border-gray-700"><td class="py-2">${ssrInterpolate(cota.mes ? meses[cota.mes - 1] : "Anual")}/${ssrInterpolate(cota.ano)}</td><td>${ssrInterpolate(cota.tipo)}</td><td>${ssrInterpolate(euros(cota.valor))}</td><td>${ssrInterpolate(cota.estado)}</td><td>${ssrInterpolate(cota.metodo_pagamento)}</td></tr>`);
      });
      _push(`<!--]--></tbody></table></section><form class="rounded-lg bg-gray-800 p-5"><h2 class="mb-4 text-2xl font-black">REGISTAR PAGAMENTO</h2><div class="mb-4 grid grid-cols-2 gap-2"><button type="button" class="${ssrRenderClass([tipo.value === "mensal" ? "bg-blue-600" : "bg-gray-700", "rounded p-4 font-black"])}">MENSAL</button><button type="button" class="${ssrRenderClass([tipo.value === "anual" ? "bg-blue-600" : "bg-gray-700", "rounded p-4 font-black"])}">ANUAL</button></div><input${ssrRenderAttr("value", ano.value)} type="number" class="mb-4 w-full rounded bg-gray-900 p-3 font-black text-white">`);
      if (tipo.value === "mensal") {
        _push(`<div class="grid grid-cols-3 gap-2"><!--[-->`);
        ssrRenderList(meses, (nome, i) => {
          _push(`<label class="${ssrRenderClass([pagos.value.has(i + 1) ? "bg-emerald-700" : selecionados.value.includes(i + 1) ? "bg-red-700" : "bg-gray-700", "rounded p-3 text-center font-black"])}"><input${ssrIncludeBooleanAttr(Array.isArray(selecionados.value) ? ssrLooseContain(selecionados.value, i + 1) : selecionados.value) ? " checked" : ""} class="hidden" type="checkbox"${ssrRenderAttr("value", i + 1)}${ssrIncludeBooleanAttr(pagos.value.has(i + 1)) ? " disabled" : ""}>${ssrInterpolate(nome)}</label>`);
        });
        _push(`<!--]--></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<div class="my-4 grid grid-cols-3 gap-2"><!--[-->`);
      ssrRenderList(["dinheiro", "mbway", "transferencia"], (m) => {
        _push(`<button type="button" class="${ssrRenderClass([metodo.value === m ? "bg-emerald-600" : "bg-gray-700", "rounded p-3 font-black uppercase"])}">${ssrInterpolate(m)}</button>`);
      });
      _push(`<!--]--></div>`);
      if (metodo.value === "dinheiro") {
        _push(`<input${ssrRenderAttr("value", recebido.value)} class="w-full rounded bg-gray-900 p-3 text-xl font-black text-white" placeholder="Recebido">`);
      } else {
        _push(`<!---->`);
      }
      _push(`<div class="my-4 text-3xl font-black text-emerald-400">${ssrInterpolate(euros(total.value))} `);
      if (metodo.value === "dinheiro") {
        _push(`<span class="text-lg">Troco ${ssrInterpolate(euros(troco.value))}</span>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><button class="w-full rounded-lg bg-emerald-600 p-5 text-xl font-black">✅ REGISTAR PAGAMENTO</button>`);
      _push(ssrRenderComponent(unref(Link), {
        href: _ctx.route("pos.cotas.socio.pesquisa"),
        class: "mt-3 block rounded-lg bg-gray-700 p-4 text-center font-black"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`← VOLTAR À PESQUISA`);
          } else {
            return [
              createTextVNode("← VOLTAR À PESQUISA")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</form></div></main>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/PosCotas/Socio.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
