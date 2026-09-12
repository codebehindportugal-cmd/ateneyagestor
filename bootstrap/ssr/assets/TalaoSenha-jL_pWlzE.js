import { ref, computed, onMounted, onBeforeUnmount, mergeProps, unref, withCtx, createTextVNode, nextTick, useSSRContext } from "vue";
import { ssrRenderAttrs, ssrInterpolate, ssrRenderList, ssrRenderComponent } from "vue/server-renderer";
import { Link } from "@inertiajs/vue3";
const _sfc_main = {
  __name: "TalaoSenha",
  __ssrInlineRender: true,
  props: { pedido: Object },
  setup(__props) {
    const props = __props;
    const ticketRef = ref(null);
    const data = () => new Date(props.pedido.created_at).toLocaleString("pt-PT");
    const euros = (valor) => Number(valor ?? 0).toFixed(2) + "€";
    const operador = computed(() => props.pedido.operador_nome ?? props.pedido.user?.name ?? props.pedido.pos?.nome ?? "Sem operador");
    const itemsImpressao = computed(
      () => (props.pedido.items || []).flatMap((item) => {
        const quantidade = Math.max(1, Math.floor(Number(item.quantidade || 1)));
        return Array.from({ length: quantidade }, (_, index) => ({
          ...item,
          printKey: `${item.id}-${index}`
        }));
      })
    );
    const updatePrintPageSize = () => {
      if (!ticketRef.value) return;
      const heightPx = ticketRef.value.getBoundingClientRect().height;
      const heightMm = Math.max(35, Math.ceil(heightPx * 25.4 / 96) + 2);
      let style = document.getElementById("thermal-ticket-page-size");
      if (!style) {
        style = document.createElement("style");
        style.id = "thermal-ticket-page-size";
        document.head.appendChild(style);
      }
      style.textContent = `
        @page { size: 80mm ${heightMm}mm; margin: 0; }
        @media print {
            html, body, #app, .thermal-ticket-page {
                height: ${heightMm}mm !important;
                min-height: 0 !important;
                max-height: ${heightMm}mm !important;
            }
        }
    `;
    };
    const printTicket = async () => {
      await nextTick();
      updatePrintPageSize();
      window.print();
    };
    onMounted(() => {
      window.addEventListener("beforeprint", updatePrintPageSize);
      setTimeout(printTicket, 500);
    });
    onBeforeUnmount(() => window.removeEventListener("beforeprint", updatePrintPageSize));
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<main${ssrRenderAttrs(mergeProps({ class: "thermal-ticket-page min-h-screen bg-slate-100 p-4 text-slate-950 print:min-h-0 print:bg-white print:p-0" }, _attrs))}><section class="thermal-ticket mx-auto max-w-[300px] bg-white p-4 font-mono shadow print:shadow-none"><h1 class="text-center text-lg font-black">Associação de Santana</h1><div class="text-center font-black">BAR</div><div class="text-center text-xs font-bold">Operador: ${ssrInterpolate(operador.value)}</div><div class="ticket-token my-4 border-y border-dashed border-slate-400 py-4 text-center"><div class="text-xs uppercase">Número da senha</div><div class="ticket-number text-5xl font-black">#${ssrInterpolate(__props.pedido.numero_senha || __props.pedido.id)}</div></div><div class="ticket-items space-y-2 text-lg font-black"><!--[-->`);
      ssrRenderList(itemsImpressao.value, (item) => {
        _push(`<div class="flex justify-between gap-2"><span>${ssrInterpolate(item.produto?.nome)}</span><span>1 un.</span></div>`);
      });
      _push(`<!--]--></div><div class="ticket-totals mt-4 border-t border-dashed border-slate-400 pt-3 text-sm"><div class="flex justify-between"><span>Total</span><strong>${ssrInterpolate(euros(__props.pedido.total))}</strong></div><div class="flex justify-between"><span>Recebido</span><strong>${ssrInterpolate(euros(__props.pedido.valor_recebido))}</strong></div><div class="flex justify-between"><span>Troco</span><strong>${ssrInterpolate(euros(__props.pedido.troco))}</strong></div>`);
      if (Number(__props.pedido.doacao || 0) > 0) {
        _push(`<div class="flex justify-between"><span>Doação</span><strong>${ssrInterpolate(euros(__props.pedido.doacao))}</strong></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div class="ticket-date mt-4 text-center text-xs">${ssrInterpolate(data())}</div><div class="ticket-thanks mt-3 text-center font-black">Obrigado!</div></section><div class="no-print mx-auto mt-4 flex max-w-[300px] gap-2 print:hidden"><button class="flex-1 rounded-xl bg-slate-900 px-4 py-3 font-black text-white">Imprimir</button>`);
      _push(ssrRenderComponent(unref(Link), {
        href: _ctx.route("bar.prepago"),
        class: "flex-1 rounded-xl bg-emerald-600 px-4 py-3 text-center font-black text-white"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`Novo Pedido`);
          } else {
            return [
              createTextVNode("Novo Pedido")
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Bar/TalaoSenha.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
