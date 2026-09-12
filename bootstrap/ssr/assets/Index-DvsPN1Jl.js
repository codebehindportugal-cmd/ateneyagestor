import { ref, computed, onMounted, onBeforeUnmount, mergeProps, unref, useSSRContext } from "vue";
import { ssrRenderAttrs, ssrInterpolate, ssrRenderClass, ssrRenderAttr, ssrRenderList, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual } from "vue/server-renderer";
import { useForm, router } from "@inertiajs/vue3";
import { _ as _export_sfc } from "./_plugin-vue_export-helper-1tPrXgE0.js";
const _sfc_main = {
  __name: "Index",
  __ssrInlineRender: true,
  props: {
    posNome: String,
    operadorNome: String,
    hoje: String,
    reservasHoje: Array,
    proximasReservas: Array
  },
  setup(__props) {
    const props = __props;
    const agora = ref(/* @__PURE__ */ new Date());
    const reservaEmEdicao = ref(null);
    const pesquisa = ref("");
    const filtroEstado = ref("por-sentar");
    const pesquisaProximas = ref("");
    const filtroProximas = ref("todas");
    let relogio = null;
    let refresh = null;
    const form = useForm({
      nome: "",
      data_reserva: props.hoje,
      hora: "20:00",
      pessoas: 2,
      observacoes: ""
    });
    const reservasPendentes = computed(() => (props.reservasHoje ?? []).filter((reserva) => reserva.estado !== "sentada"));
    const reservasChamadas = computed(() => reservasPendentes.value.filter((reserva) => reserva.chamada_em));
    const reservasSentadas = computed(() => (props.reservasHoje ?? []).filter((reserva) => reserva.estado === "sentada"));
    const normalizar = (valor) => String(valor ?? "").normalize("NFD").replace(new RegExp("[\\u0300-\\u036f]", "g"), "").toLowerCase();
    const reservasPorEstado = computed(() => {
      const lista = props.reservasHoje ?? [];
      switch (filtroEstado.value) {
        case "chamadas":
          return lista.filter((reserva) => reserva.estado !== "sentada" && reserva.chamada_em);
        case "sentadas":
          return lista.filter((reserva) => reserva.estado === "sentada");
        case "por-sentar":
          return lista.filter((reserva) => reserva.estado !== "sentada");
        default:
          return lista;
      }
    });
    const reservasFiltradas = computed(() => {
      const termo = normalizar(pesquisa.value.trim());
      if (!termo) {
        return reservasPorEstado.value;
      }
      return reservasPorEstado.value.filter((reserva) => normalizar(reserva.nome).includes(termo) || normalizar(horaReserva(reserva)).includes(termo) || normalizar(reserva.pessoas).includes(termo) || normalizar(reserva.observacoes).includes(termo));
    });
    const hojeData = computed(() => /* @__PURE__ */ new Date(`${props.hoje}T00:00:00`));
    const diasAtePartirDeHoje = (data) => {
      const alvo = /* @__PURE__ */ new Date(`${String(data).split("T")[0]}T00:00:00`);
      return Math.round((alvo - hojeData.value) / 864e5);
    };
    const proximasFiltradas = computed(() => {
      let lista = props.proximasReservas ?? [];
      if (filtroProximas.value === "amanha") {
        lista = lista.filter((reserva) => diasAtePartirDeHoje(reserva.data) === 1);
      } else if (filtroProximas.value === "semana") {
        lista = lista.filter((reserva) => diasAtePartirDeHoje(reserva.data) <= 7);
      }
      const termo = normalizar(pesquisaProximas.value.trim());
      if (termo) {
        lista = lista.filter((reserva) => normalizar(reserva.nome).includes(termo) || normalizar(horaReserva(reserva)).includes(termo) || normalizar(reserva.pessoas).includes(termo));
      }
      return lista;
    });
    const horasDisponiveis = Array.from({ length: 24 * 4 }, (_, index) => {
      const hora = String(Math.floor(index / 4)).padStart(2, "0");
      const minuto = String(index % 4 * 15).padStart(2, "0");
      return `${hora}:${minuto}`;
    });
    const editForm = useForm({
      hora: "",
      pessoas: 1
    });
    const horaReserva = (reserva) => reserva.hora?.slice(0, 5) ?? "--:--";
    const horaData = (data) => {
      if (!data) {
        return "";
      }
      return new Date(data).toLocaleTimeString("pt-PT", {
        hour: "2-digit",
        minute: "2-digit"
      });
    };
    const dia = (data) => (/* @__PURE__ */ new Date(`${String(data).split("T")[0]}T00:00:00`)).toLocaleDateString("pt-PT", {
      weekday: "short",
      day: "2-digit",
      month: "2-digit"
    });
    onMounted(() => {
      relogio = setInterval(() => agora.value = /* @__PURE__ */ new Date(), 1e3);
      refresh = setInterval(() => router.reload({ preserveScroll: true }), 15e3);
    });
    onBeforeUnmount(() => {
      clearInterval(relogio);
      clearInterval(refresh);
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<main${ssrRenderAttrs(mergeProps({ class: "pos-reservas flex h-screen overflow-hidden bg-gray-900 p-3 text-white sm:p-4" }, _attrs))} data-v-f093e32c><div class="flex min-h-0 w-full flex-col" data-v-f093e32c><header class="mb-3 flex shrink-0 flex-wrap items-center justify-between gap-3" data-v-f093e32c><div data-v-f093e32c><h1 class="text-2xl font-black sm:text-3xl" data-v-f093e32c>POS RESERVAS</h1><p class="font-bold text-gray-300" data-v-f093e32c>${ssrInterpolate(__props.operadorNome || __props.posNome)} - ${ssrInterpolate(agora.value.toLocaleTimeString("pt-PT"))}</p></div><div class="flex flex-wrap gap-2" data-v-f093e32c><button class="rounded-lg bg-red-600 px-3 py-1.5 text-sm font-black" data-v-f093e32c>LOGOUT</button></div></header><section class="mb-3 grid shrink-0 gap-2 md:grid-cols-4" data-v-f093e32c><button type="button" class="${ssrRenderClass([filtroEstado.value === "todas" ? "ring-2 ring-white" : "opacity-90 hover:opacity-100", "rounded-lg bg-blue-700 p-2.5 text-left transition"])}" data-v-f093e32c><div class="text-sm font-bold" data-v-f093e32c>Hoje</div><div class="text-2xl font-black" data-v-f093e32c>${ssrInterpolate(__props.reservasHoje.length)}</div></button><button type="button" class="${ssrRenderClass([filtroEstado.value === "chamadas" ? "ring-2 ring-white" : "opacity-90 hover:opacity-100", "rounded-lg bg-amber-600 p-2.5 text-left transition"])}" data-v-f093e32c><div class="text-sm font-bold" data-v-f093e32c>Chamadas</div><div class="text-2xl font-black" data-v-f093e32c>${ssrInterpolate(reservasChamadas.value.length)}</div></button><button type="button" class="${ssrRenderClass([filtroEstado.value === "sentadas" ? "ring-2 ring-white" : "opacity-90 hover:opacity-100", "rounded-lg bg-emerald-700 p-2.5 text-left transition"])}" data-v-f093e32c><div class="text-sm font-bold" data-v-f093e32c>Sentadas</div><div class="text-2xl font-black" data-v-f093e32c>${ssrInterpolate(reservasSentadas.value.length)}</div></button><button type="button" class="${ssrRenderClass([filtroEstado.value === "por-sentar" ? "ring-2 ring-white" : "opacity-90 hover:opacity-100", "rounded-lg bg-gray-800 p-2.5 text-left transition"])}" data-v-f093e32c><div class="text-sm font-bold" data-v-f093e32c>Por sentar</div><div class="text-2xl font-black" data-v-f093e32c>${ssrInterpolate(reservasPendentes.value.length)}</div></button></section><div class="grid min-h-0 flex-1 gap-3 xl:grid-cols-[minmax(0,1fr)_420px]" data-v-f093e32c><section class="flex min-h-0 flex-col rounded-lg bg-gray-800 p-3 sm:p-4" data-v-f093e32c><div class="mb-2 flex shrink-0 flex-wrap items-center justify-between gap-2" data-v-f093e32c><h2 class="text-lg font-black sm:text-xl" data-v-f093e32c>RESERVAS DE HOJE</h2><span class="text-sm font-bold text-gray-400" data-v-f093e32c>${ssrInterpolate(reservasFiltradas.value.length)} de ${ssrInterpolate(__props.reservasHoje.length)}</span></div><div class="mb-2 shrink-0" data-v-f093e32c><input${ssrRenderAttr("value", pesquisa.value)} type="search" class="w-full rounded-lg border-gray-700 bg-gray-900 p-2.5 font-bold text-white placeholder:text-gray-500" placeholder="Pesquisar por nome, hora ou nº de pessoas..." data-v-f093e32c></div><div class="mb-3 flex shrink-0 flex-wrap gap-1.5" data-v-f093e32c><!--[-->`);
      ssrRenderList([
        { valor: "por-sentar", rotulo: "Por sentar" },
        { valor: "chamadas", rotulo: "Chamadas" },
        { valor: "sentadas", rotulo: "Sentadas" },
        { valor: "todas", rotulo: "Todas" }
      ], (opcao) => {
        _push(`<button class="${ssrRenderClass([filtroEstado.value === opcao.valor ? "bg-blue-600 text-white" : "bg-gray-700 text-gray-300 hover:bg-gray-600", "rounded-full px-3 py-1 text-sm font-bold transition"])}" data-v-f093e32c>${ssrInterpolate(opcao.rotulo)}</button>`);
      });
      _push(`<!--]--></div><div class="min-h-0 flex-1 overflow-y-auto pr-1" data-v-f093e32c>`);
      if (!reservasFiltradas.value.length) {
        _push(`<div class="rounded-lg bg-gray-900 p-6 text-center text-lg font-black text-gray-300" data-v-f093e32c>${ssrInterpolate(pesquisa.value.trim() ? "Nenhuma reserva encontrada." : "Sem reservas nesta categoria.")}</div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<!--[-->`);
      ssrRenderList(reservasFiltradas.value, (reserva) => {
        _push(`<article class="${ssrRenderClass([reserva.estado === "sentada" ? "border-emerald-500 bg-emerald-900/40" : reserva.chamada_em ? "border-amber-500 bg-amber-900/30" : "border-gray-700 bg-gray-900", "mb-2 grid gap-2 rounded-lg border-2 p-2.5 md:grid-cols-[80px_minmax(0,1fr)_auto]"])}" data-v-f093e32c><div data-v-f093e32c><div class="text-2xl font-black" data-v-f093e32c>${ssrInterpolate(horaReserva(reserva))}</div><div class="mt-1 rounded bg-gray-800 px-2 py-0.5 text-center text-xs font-black" data-v-f093e32c>${ssrInterpolate(reserva.pessoas)} PAX </div></div><div class="min-w-0" data-v-f093e32c><div class="truncate text-lg font-black" data-v-f093e32c>${ssrInterpolate(reserva.nome)}</div><div class="mt-1 flex flex-wrap gap-1.5 text-xs font-bold text-gray-300" data-v-f093e32c>`);
        if (reserva.chamada_em) {
          _push(`<span class="rounded bg-amber-500 px-1.5 py-0.5 text-gray-950" data-v-f093e32c>CHAMADA ${ssrInterpolate(horaData(reserva.chamada_em))}</span>`);
        } else {
          _push(`<!---->`);
        }
        if (reserva.sentada_em) {
          _push(`<span class="rounded bg-emerald-500 px-1.5 py-0.5 text-gray-950" data-v-f093e32c>SENTADA ${ssrInterpolate(horaData(reserva.sentada_em))}</span>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
        if (reserva.observacoes) {
          _push(`<p class="mt-1.5 rounded bg-gray-800 p-1.5 text-xs font-bold text-gray-200" data-v-f093e32c>${ssrInterpolate(reserva.observacoes)}</p>`);
        } else {
          _push(`<!---->`);
        }
        if (reservaEmEdicao.value === reserva.id) {
          _push(`<div class="mt-2 grid max-w-md grid-cols-2 gap-2" data-v-f093e32c><select class="rounded-lg border-gray-700 bg-gray-950 p-2 font-black text-white" data-v-f093e32c><!--[-->`);
          ssrRenderList(unref(horasDisponiveis), (hora) => {
            _push(`<option${ssrRenderAttr("value", hora)} data-v-f093e32c${ssrIncludeBooleanAttr(Array.isArray(unref(editForm).hora) ? ssrLooseContain(unref(editForm).hora, hora) : ssrLooseEqual(unref(editForm).hora, hora)) ? " selected" : ""}>${ssrInterpolate(hora)}</option>`);
          });
          _push(`<!--]--></select><input${ssrRenderAttr("value", unref(editForm).pessoas)} type="number" min="1" class="rounded-lg border-gray-700 bg-gray-950 p-2 font-black text-white" data-v-f093e32c></div>`);
        } else {
          _push(`<!---->`);
        }
        if (reservaEmEdicao.value === reserva.id && Object.keys(unref(editForm).errors).length) {
          _push(`<div class="mt-2 rounded bg-red-700 p-2 text-sm font-bold" data-v-f093e32c><!--[-->`);
          ssrRenderList(unref(editForm).errors, (erro) => {
            _push(`<div data-v-f093e32c>${ssrInterpolate(erro)}</div>`);
          });
          _push(`<!--]--></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
        if (reservaEmEdicao.value === reserva.id) {
          _push(`<div class="grid min-w-40 grid-cols-2 gap-1.5 self-start" data-v-f093e32c><button class="rounded-lg bg-blue-600 px-3 py-1.5 text-sm font-black disabled:opacity-40"${ssrIncludeBooleanAttr(unref(editForm).processing) ? " disabled" : ""} data-v-f093e32c> GRAVAR </button><button class="rounded-lg bg-gray-700 px-3 py-1.5 text-sm font-black disabled:opacity-40"${ssrIncludeBooleanAttr(unref(editForm).processing) ? " disabled" : ""} data-v-f093e32c> FECHAR </button></div>`);
        } else {
          _push(`<div class="grid min-w-40 grid-cols-2 gap-1.5 self-start" data-v-f093e32c><button class="rounded-lg bg-blue-600 px-3 py-1.5 text-sm font-black disabled:opacity-40"${ssrIncludeBooleanAttr(reserva.estado === "sentada") ? " disabled" : ""} data-v-f093e32c> EDITAR </button><button class="rounded-lg bg-amber-500 px-3 py-1.5 text-sm font-black text-gray-950 disabled:opacity-40"${ssrIncludeBooleanAttr(reserva.estado === "sentada") ? " disabled" : ""} data-v-f093e32c> CHAMAR </button><button class="rounded-lg bg-emerald-600 px-3 py-1.5 text-sm font-black disabled:opacity-40"${ssrIncludeBooleanAttr(reserva.estado === "sentada") ? " disabled" : ""} data-v-f093e32c> SENTADA </button><button class="rounded-lg bg-gray-700 px-3 py-1.5 text-sm font-black disabled:opacity-40"${ssrIncludeBooleanAttr(reserva.estado === "sentada") ? " disabled" : ""} data-v-f093e32c> CANCELAR </button></div>`);
        }
        _push(`</article>`);
      });
      _push(`<!--]--></div></section><aside class="flex min-h-0 flex-col gap-3" data-v-f093e32c><section class="shrink-0 rounded-lg bg-gray-800 p-3 sm:p-4" data-v-f093e32c><h2 class="mb-3 text-xl font-black" data-v-f093e32c>NOVA RESERVA</h2><form class="grid gap-2" data-v-f093e32c><input${ssrRenderAttr("value", unref(form).nome)} class="rounded-lg border-gray-700 bg-gray-900 p-3 text-lg font-black text-white" placeholder="Nome" data-v-f093e32c><div class="grid grid-cols-3 gap-2" data-v-f093e32c><input${ssrRenderAttr("value", unref(form).data_reserva)} type="date" class="rounded-lg border-gray-700 bg-gray-900 p-3 font-black text-white" data-v-f093e32c><input${ssrRenderAttr("value", unref(form).hora)} type="time" class="rounded-lg border-gray-700 bg-gray-900 p-3 font-black text-white" data-v-f093e32c><input${ssrRenderAttr("value", unref(form).pessoas)} type="number" min="1" class="rounded-lg border-gray-700 bg-gray-900 p-3 font-black text-white" data-v-f093e32c></div><textarea rows="2" class="rounded-lg border-gray-700 bg-gray-900 p-3 font-bold text-white" placeholder="Observacoes" data-v-f093e32c>${ssrInterpolate(unref(form).observacoes)}</textarea>`);
      if (Object.keys(unref(form).errors).length) {
        _push(`<div class="rounded bg-red-700 p-2 font-bold" data-v-f093e32c><!--[-->`);
        ssrRenderList(unref(form).errors, (erro) => {
          _push(`<div data-v-f093e32c>${ssrInterpolate(erro)}</div>`);
        });
        _push(`<!--]--></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<button class="rounded-lg bg-blue-600 p-4 text-lg font-black disabled:opacity-50"${ssrIncludeBooleanAttr(unref(form).processing) ? " disabled" : ""} data-v-f093e32c> CRIAR RESERVA </button></form></section><section class="flex min-h-0 flex-1 flex-col rounded-lg bg-gray-800 p-3 sm:p-4" data-v-f093e32c><h2 class="mb-2 shrink-0 text-lg font-black" data-v-f093e32c>PROXIMAS</h2><div class="mb-2 shrink-0" data-v-f093e32c><input${ssrRenderAttr("value", pesquisaProximas.value)} type="search" class="w-full rounded-lg border-gray-700 bg-gray-900 p-2 text-sm font-bold text-white placeholder:text-gray-500" placeholder="Pesquisar..." data-v-f093e32c></div><div class="mb-2 flex shrink-0 flex-wrap gap-1.5" data-v-f093e32c><!--[-->`);
      ssrRenderList([
        { valor: "todas", rotulo: "Todas" },
        { valor: "amanha", rotulo: "Amanha" },
        { valor: "semana", rotulo: "Esta semana" }
      ], (opcao) => {
        _push(`<button class="${ssrRenderClass([filtroProximas.value === opcao.valor ? "bg-blue-600 text-white" : "bg-gray-700 text-gray-300 hover:bg-gray-600", "rounded-full px-2.5 py-1 text-xs font-bold transition"])}" data-v-f093e32c>${ssrInterpolate(opcao.rotulo)}</button>`);
      });
      _push(`<!--]--></div><div class="min-h-0 flex-1 overflow-y-auto pr-1" data-v-f093e32c>`);
      if (!proximasFiltradas.value.length) {
        _push(`<div class="rounded-lg bg-gray-900 p-4 text-center text-sm font-bold text-gray-300" data-v-f093e32c> Nenhuma reserva encontrada. </div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<!--[-->`);
      ssrRenderList(proximasFiltradas.value, (reserva) => {
        _push(`<div class="mb-2 rounded-lg bg-gray-900 p-2.5" data-v-f093e32c><div class="flex items-center justify-between gap-3" data-v-f093e32c><strong class="truncate text-base" data-v-f093e32c>${ssrInterpolate(reserva.nome)}</strong><span class="font-black text-blue-300" data-v-f093e32c>${ssrInterpolate(dia(reserva.data))}</span></div><div class="mt-1 text-sm font-bold text-gray-300" data-v-f093e32c>${ssrInterpolate(horaReserva(reserva))} - ${ssrInterpolate(reserva.pessoas)} PAX</div></div>`);
      });
      _push(`<!--]--></div></section></aside></div></div></main>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/PosReservas/Index.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const Index = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-f093e32c"]]);
export {
  Index as default
};
