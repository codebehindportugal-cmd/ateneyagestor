import { ref, computed, onMounted, onBeforeUnmount, mergeProps, unref, useSSRContext } from "vue";
import { ssrRenderAttrs, ssrInterpolate, ssrRenderList, ssrRenderClass, ssrIncludeBooleanAttr, ssrRenderAttr } from "vue/server-renderer";
import { useForm, router } from "@inertiajs/vue3";
import { _ as _export_sfc } from "./_plugin-vue_export-helper-1tPrXgE0.js";
const _sfc_main = {
  __name: "Index",
  __ssrInlineRender: true,
  props: {
    posNome: String,
    pontoBar: String,
    caixaAberta: Boolean,
    produtos: Array,
    senhasHoje: Array
  },
  setup(__props) {
    const props = __props;
    const agora = ref(/* @__PURE__ */ new Date());
    const carrinho = ref([]);
    const recebido = ref("");
    const trocoEntregue = ref("");
    const form = useForm({ items: [], valor_recebido: 0, troco: 0 });
    let relogio = null;
    let refresh = null;
    const secoes = computed(() => {
      const map = /* @__PURE__ */ new Map();
      (props.produtos ?? []).forEach((p) => {
        const nome = p.categoria?.nome ?? "Outros";
        if (!map.has(nome)) map.set(nome, { nome, produtos: [] });
        map.get(nome).produtos.push(p);
      });
      return [...map.values()];
    });
    const secaoAtiva = ref(null);
    const secaoAtivaKey = computed(() => secaoAtiva.value ?? secoes.value[0]?.nome ?? null);
    const produtosVisiveis = computed(() => {
      if (!secaoAtivaKey.value) return props.produtos ?? [];
      return secoes.value.find((s) => s.nome === secaoAtivaKey.value)?.produtos ?? [];
    });
    const tituloAtivo = computed(() => secaoAtivaKey.value ?? "Produtos");
    const secaoClasse = (produto) => ({
      bebidas: "bg-blue-600",
      frango: "bg-red-700",
      cozinha: "bg-orange-600",
      comida: "bg-orange-600",
      acompanhamentos: "bg-emerald-700",
      sobremesas: "bg-purple-600"
    })[produto.categoria?.secao] || "bg-gray-700";
    const total = computed(() => carrinho.value.reduce((soma, item) => soma + Number(item.preco) * item.quantidade, 0));
    const troco = computed(() => Math.max(0, Number(recebido.value || 0) - total.value));
    const trocoRegistado = computed(() => trocoEntregue.value === "" ? troco.value : Number(trocoEntregue.value || 0));
    const doacao = computed(() => Math.max(0, troco.value - trocoRegistado.value));
    const euros = (valor) => Number(valor ?? 0).toFixed(2) + "€";
    const hora = (data) => new Date(data).toLocaleTimeString("pt-PT", { hour: "2-digit", minute: "2-digit" });
    onMounted(() => {
      relogio = setInterval(() => agora.value = /* @__PURE__ */ new Date(), 1e3);
      refresh = setInterval(() => router.reload({ only: ["caixaAberta", "senhasHoje"], preserveScroll: true }), 2e4);
    });
    onBeforeUnmount(() => {
      clearInterval(relogio);
      clearInterval(refresh);
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<main${ssrRenderAttrs(mergeProps({ class: "pos-screen flex h-screen overflow-hidden bg-gray-900 p-3 text-white sm:p-4" }, _attrs))} data-v-e4c553c9><div class="flex min-h-0 w-full flex-col" data-v-e4c553c9><header class="pos-header mb-3 flex shrink-0 flex-wrap items-center justify-between gap-3" data-v-e4c553c9><div data-v-e4c553c9><h1 class="pos-title text-2xl font-black sm:text-3xl" data-v-e4c553c9>POS ${ssrInterpolate(__props.pontoBar)}</h1><p class="pos-subtitle font-bold text-gray-300" data-v-e4c553c9>${ssrInterpolate(__props.posNome)} · ${ssrInterpolate(agora.value.toLocaleTimeString("pt-PT"))}</p></div><button class="pos-logout rounded-lg bg-red-600 px-4 py-2 font-black sm:px-5 sm:py-3" data-v-e4c553c9>LOGOUT</button></header>`);
      if (!__props.caixaAberta) {
        _push(`<div class="pos-alert mb-3 shrink-0 rounded-lg bg-red-700 p-3 text-center text-lg font-black sm:p-4" data-v-e4c553c9> Caixa fechada para ${ssrInterpolate(__props.pontoBar)}. Abre a caixa no backoffice antes de vender. </div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<div class="pos-layout grid min-h-0 flex-1 gap-3 lg:grid-cols-[minmax(0,1fr)_380px] xl:grid-cols-[minmax(0,1fr)_410px]" data-v-e4c553c9><div class="pos-left flex min-h-0 flex-col gap-3" data-v-e4c553c9><section class="pos-panel flex min-h-0 flex-1 flex-col rounded-lg bg-gray-800 p-3 sm:p-4" data-v-e4c553c9>`);
      if (secoes.value.length > 1) {
        _push(`<div class="pos-tabs mb-3 flex shrink-0 gap-2 overflow-x-auto pb-1" data-v-e4c553c9><!--[-->`);
        ssrRenderList(secoes.value, (s) => {
          _push(`<button class="${ssrRenderClass([secaoAtivaKey.value === s.nome ? "bg-emerald-600 text-white" : "bg-gray-700 text-gray-300 hover:bg-gray-600", "pos-tab shrink-0 rounded-lg px-4 py-2 text-sm font-black uppercase tracking-wide transition"])}" data-v-e4c553c9>${ssrInterpolate(s.nome)}</button>`);
        });
        _push(`<!--]--></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<h2 class="pos-section-title mb-3 shrink-0 text-xl font-black sm:text-2xl" data-v-e4c553c9>${ssrInterpolate(tituloAtivo.value.toUpperCase())}</h2>`);
      if (produtosVisiveis.value.length === 0) {
        _push(`<div class="flex flex-1 items-center justify-center text-gray-400 font-bold" data-v-e4c553c9> Sem produtos nesta secção. </div>`);
      } else {
        _push(`<div class="pos-product-grid grid min-h-0 flex-1 auto-rows-min grid-cols-2 gap-2 overflow-y-auto pr-1 md:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5" data-v-e4c553c9><!--[-->`);
        ssrRenderList(produtosVisiveis.value, (produto) => {
          _push(`<button class="${ssrRenderClass([secaoClasse(produto), "pos-product-btn min-h-20 rounded-lg p-3 text-left font-black disabled:opacity-50 sm:min-h-24"])}"${ssrIncludeBooleanAttr(!__props.caixaAberta) ? " disabled" : ""} data-v-e4c553c9><span class="pos-product-name block text-lg" data-v-e4c553c9>${ssrInterpolate(produto.nome)}</span><span class="pos-product-price mt-1 block text-xl sm:text-2xl" data-v-e4c553c9>${ssrInterpolate(euros(produto.preco))}</span></button>`);
        });
        _push(`<!--]--></div>`);
      }
      _push(`</section><section class="pos-sales-panel shrink-0 rounded-lg bg-gray-800 p-3" data-v-e4c553c9><h2 class="pos-sales-title mb-2 text-lg font-black" data-v-e4c553c9>ÚLTIMAS SENHAS</h2><div class="pos-sales-grid grid max-h-32 gap-2 overflow-y-auto md:grid-cols-3 xl:grid-cols-4" data-v-e4c553c9><!--[-->`);
      ssrRenderList(__props.senhasHoje, (pedido) => {
        _push(`<div class="pos-sale-card rounded-lg bg-gray-900 p-2" data-v-e4c553c9><div class="pos-sale-number text-xl font-black" data-v-e4c553c9>#${ssrInterpolate(pedido.numero_senha)}</div><div class="pos-sale-time text-xs text-gray-400" data-v-e4c553c9>${ssrInterpolate(hora(pedido.created_at))}</div><div class="pos-sale-items mt-1 truncate text-xs" data-v-e4c553c9>${ssrInterpolate(pedido.items.map((item) => `${item.quantidade}x ${item.produto?.nome}`).join(", "))}</div></div>`);
      });
      _push(`<!--]--></div></section></div><aside class="pos-cart flex min-h-0 flex-col rounded-lg bg-gray-800 p-3 sm:p-4" data-v-e4c553c9><h2 class="pos-section-title mb-2 shrink-0 text-xl font-black sm:text-2xl" data-v-e4c553c9>SENHA</h2><div class="pos-cart-list min-h-0 flex-1 overflow-y-auto pr-1" data-v-e4c553c9>`);
      if (!carrinho.value.length) {
        _push(`<div class="pos-empty rounded-lg bg-gray-900 p-5 text-center font-bold text-gray-300" data-v-e4c553c9>Escolhe os produtos.</div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<!--[-->`);
      ssrRenderList(carrinho.value, (item) => {
        _push(`<div class="pos-cart-item mb-2 rounded-lg bg-gray-900 p-3" data-v-e4c553c9><div class="pos-cart-name font-black" data-v-e4c553c9>${ssrInterpolate(item.nome)}</div><div class="pos-cart-row mt-2 flex items-center justify-between gap-3" data-v-e4c553c9><div class="flex items-center gap-2" data-v-e4c553c9><button class="pos-qty-btn h-11 w-11 rounded bg-gray-700 text-xl font-black" data-v-e4c553c9>-</button><strong class="pos-qty text-xl" data-v-e4c553c9>${ssrInterpolate(item.quantidade)}</strong><button class="pos-qty-btn h-11 w-11 rounded bg-emerald-600 text-xl font-black" data-v-e4c553c9>+</button></div><strong class="pos-line-total font-mono text-xl" data-v-e4c553c9>${ssrInterpolate(euros(item.preco * item.quantidade))}</strong></div></div>`);
      });
      _push(`<!--]--></div><div class="pos-total my-3 shrink-0 rounded-lg bg-emerald-700 p-3" data-v-e4c553c9><div class="pos-total-label font-bold" data-v-e4c553c9>Total</div><div class="pos-total-value text-3xl font-black sm:text-4xl" data-v-e4c553c9>${ssrInterpolate(euros(total.value))}</div></div><input${ssrRenderAttr("value", recebido.value)} inputmode="decimal" class="pos-input w-full shrink-0 rounded-lg border-gray-700 bg-gray-900 p-3 text-xl font-black text-white" placeholder="Recebido" data-v-e4c553c9><input${ssrRenderAttr("value", trocoEntregue.value)} inputmode="decimal" class="pos-input mt-2 w-full shrink-0 rounded-lg border-gray-700 bg-gray-900 p-3 text-xl font-black text-white"${ssrRenderAttr("placeholder", `Troco entregue ${euros(troco.value)}`)} data-v-e4c553c9><div class="pos-change-grid mt-2 grid shrink-0 grid-cols-2 gap-2 text-base font-black" data-v-e4c553c9><div class="rounded-lg bg-gray-900 p-2 text-emerald-400" data-v-e4c553c9>Troco: ${ssrInterpolate(euros(trocoRegistado.value))}</div><div class="rounded-lg bg-gray-900 p-2 text-amber-300" data-v-e4c553c9>Doação: ${ssrInterpolate(euros(doacao.value))}</div></div><button type="button" class="pos-donate mt-2 w-full shrink-0 rounded-lg bg-amber-500 p-3 font-black text-gray-950" data-v-e4c553c9>CLIENTE DOA O TROCO</button>`);
      if (unref(form).errors.ponto_bar || unref(form).errors.valor_recebido || unref(form).errors.troco) {
        _push(`<div class="pos-error mt-2 shrink-0 rounded bg-red-700 p-2 font-bold" data-v-e4c553c9>${ssrInterpolate(unref(form).errors.ponto_bar || unref(form).errors.valor_recebido || unref(form).errors.troco)}</div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<button class="pos-pay mt-3 w-full shrink-0 rounded-lg bg-emerald-600 p-4 text-lg font-black disabled:opacity-50"${ssrIncludeBooleanAttr(!__props.caixaAberta || !carrinho.value.length || unref(form).processing) ? " disabled" : ""} data-v-e4c553c9> COBRAR E TIRAR SENHA </button></aside></div></div></main>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Pos/Index.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const Index = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-e4c553c9"]]);
export {
  Index as default
};
