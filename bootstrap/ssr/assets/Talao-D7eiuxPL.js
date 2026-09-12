import { computed, onMounted, mergeProps, unref, withCtx, createTextVNode, useSSRContext } from "vue";
import { ssrRenderAttrs, ssrInterpolate, ssrRenderList, ssrRenderComponent } from "vue/server-renderer";
import { Link } from "@inertiajs/vue3";
const _sfc_main = {
  __name: "Talao",
  __ssrInlineRender: true,
  props: { pedido: Object },
  setup(__props) {
    const props = __props;
    const total = computed(() => Number(props.pedido?.total ?? props.pedido?.total_calculado ?? 0));
    const valorRecebido = computed(() => Number(props.pedido?.valor_recebido ?? total.value));
    const troco = computed(() => Number(props.pedido?.troco ?? 0));
    const doacao = computed(() => Number(props.pedido?.doacao ?? 0));
    const operador = computed(() => props.pedido?.operador_nome ?? props.pedido?.user?.name ?? props.pedido?.pos?.nome ?? "Sem operador");
    const mesaLabel = computed(() => props.pedido?.mesa?.designacao ?? "Para levar");
    const agruparItens = (items) => Object.values((items ?? []).reduce((grupos, item) => {
      const chave = [item.produto?.id, item.produto?.nome, item.preco_unitario, item.observacoes ?? ""].join("|");
      grupos[chave] ??= { ...item, id: chave, quantidade: 0 };
      grupos[chave].quantidade += Number(item.quantidade ?? 0);
      return grupos;
    }, {}));
    const itensAgrupados = computed(() => agruparItens(props.pedido?.items ?? []));
    const itensComValor = computed(() => itensAgrupados.value.filter((item) => Number(item.preco_unitario) > 0));
    const servicos = computed(() => itensAgrupados.value.filter((item) => Number(item.preco_unitario) === 0));
    const formatarPreco = (valor) => `${Number(valor ?? 0).toFixed(2)} EUR`;
    const subtotal = (item) => formatarPreco(Number(item.preco_unitario) * Number(item.quantidade));
    const agora = (/* @__PURE__ */ new Date()).toLocaleString("pt-PT");
    onMounted(() => {
      setTimeout(() => window.print(), 300);
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<main${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-slate-100 p-4 text-slate-900 print:bg-white print:p-0" }, _attrs))}><div class="mx-auto max-w-sm rounded-lg bg-white p-5 pb-12 shadow-sm print:shadow-none"><div class="mb-4 text-center"><h1 class="text-lg font-black uppercase">Associacao de Santana</h1><div class="mt-1 text-sm">${ssrInterpolate(__props.pedido.mesa ? "Talao de mesa" : "Talao para levar")}</div><div class="mt-2 border-y border-slate-300 py-2 text-sm font-bold uppercase"> Este documento nao serve de fatura </div></div><div class="mb-4 text-sm"><div class="flex justify-between"><span>${ssrInterpolate(__props.pedido.mesa ? "Mesa" : "Tipo")}</span><strong>${ssrInterpolate(mesaLabel.value)}</strong></div><div class="flex justify-between"><span>Data</span><strong>${ssrInterpolate(unref(agora))}</strong></div><div class="flex justify-between"><span>Operador</span><strong>${ssrInterpolate(operador.value)}</strong></div><div class="flex justify-between"><span>Estado</span><strong>${ssrInterpolate(__props.pedido.estado)}</strong></div></div><div class="border-t border-slate-300 py-3"><!--[-->`);
      ssrRenderList(itensComValor.value, (item) => {
        _push(`<div class="mb-2 text-sm"><div class="flex justify-between gap-3"><span>${ssrInterpolate(item.quantidade)}x ${ssrInterpolate(item.produto?.nome)}</span><strong>${ssrInterpolate(subtotal(item))}</strong></div><div class="text-xs text-slate-500">${ssrInterpolate(formatarPreco(item.preco_unitario))} cada</div></div>`);
      });
      _push(`<!--]-->`);
      if (!itensComValor.value.length) {
        _push(`<div class="py-3 text-center text-sm text-slate-500">Sem artigos cobrados.</div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
      if (servicos.value.length) {
        _push(`<div class="border-t border-slate-300 py-3 text-sm"><div class="mb-2 font-bold">Servico</div><!--[-->`);
        ssrRenderList(servicos.value, (item) => {
          _push(`<div class="flex justify-between"><span>${ssrInterpolate(item.quantidade)}x ${ssrInterpolate(item.produto?.nome)}</span><span>0.00 EUR</span></div>`);
        });
        _push(`<!--]--></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<div class="border-t border-slate-300 pt-3"><div class="flex justify-between text-xl font-black"><span>Total</span><span>${ssrInterpolate(formatarPreco(total.value))}</span></div><div class="mt-3 space-y-1 text-sm"><div class="flex justify-between"><span>Recebido</span><strong>${ssrInterpolate(formatarPreco(valorRecebido.value))}</strong></div><div class="flex justify-between"><span>Troco</span><strong>${ssrInterpolate(formatarPreco(troco.value))}</strong></div>`);
      if (doacao.value > 0) {
        _push(`<div class="flex justify-between"><span>Doacao associacao</span><strong>${ssrInterpolate(formatarPreco(doacao.value))}</strong></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div><div class="mt-8 text-center text-xs text-slate-500"> Obrigado pela preferencia. </div></div><div class="mx-auto mt-4 flex max-w-sm gap-2 print:hidden"><button class="flex-1 rounded-md bg-slate-900 px-4 py-2 text-sm font-bold text-white">Imprimir</button>`);
      _push(ssrRenderComponent(unref(Link), {
        href: _ctx.route("pedidos.show", __props.pedido.id),
        class: "rounded-md border border-slate-300 px-4 py-2 text-sm font-bold"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`Voltar`);
          } else {
            return [
              createTextVNode("Voltar")
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Pedidos/Talao.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
