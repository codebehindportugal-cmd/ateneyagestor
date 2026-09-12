import { ref, computed, onMounted, onBeforeUnmount, mergeProps, unref, withCtx, createTextVNode, createVNode, toDisplayString, openBlock, createBlock, createCommentVNode, useSSRContext } from "vue";
import { ssrRenderAttrs, ssrRenderComponent, ssrRenderList, ssrInterpolate, ssrRenderClass, ssrRenderAttr, ssrRenderSlot, ssrRenderStyle } from "vue/server-renderer";
import { usePage, router, Link } from "@inertiajs/vue3";
function usePermissions() {
  const page = usePage();
  const permissions = () => page.props.auth?.permissions ?? [];
  const roles = () => page.props.auth?.roles ?? [];
  return {
    can: (permission) => permissions().includes(permission),
    hasRole: (role) => roles().includes(role)
  };
}
const _sfc_main = {
  __name: "AppLayout",
  __ssrInlineRender: true,
  setup(__props) {
    const page = usePage();
    const { can, hasRole } = usePermissions();
    const drawerAberto = ref(false);
    let polling = null;
    const year = (/* @__PURE__ */ new Date()).getFullYear();
    const podeGerir = () => hasRole("admin") || hasRole("gerente");
    const itemVisivel = (perm) => perm ? can(perm) : podeGerir();
    const urgentes = () => page.props.urgentes_count ?? 0;
    const ativo = (nome) => route().current(nome) || route().current(nome.replace(".index", ".*"));
    const grupos = [
      { label: "Restaurante", items: [["Sala", "sala.index", "mesas.ver"], ["Mesas", "mesas.index", "restaurante.ver"], ["Pedidos", "pedidos.index", "pedidos.ver"], ["Caixas", "caixa.index", "caixa.ver"], ["Impressoras", "impressoras.index", null]] },
      { label: "Produtos", items: [["Produtos", "produtos.index", "produtos.ver"], ["Faturas/Stock", "faturas-compras.index", "produtos.ver"]] },
      { label: "Eventos & Reservas", items: [["Reservas", "reservas.index", "reservas.ver"], ["Eventos", "eventos.index", null]] },
      { label: "Site", items: [["Páginas", "paginas.index", null], ["Patrocinadores", "patrocinadores.index", null]] },
      { label: "Sócios", items: [["Sócios", "socios.index", "socios.ver"], ["Cotas", "cotas.index", "cotas.ver"]] },
      { label: "Relatórios", items: [["Relatórios", "relatorios.index", "relatorios.ver"], ["Contas da Festa", "contas-festa.index", "relatorios.ver"]] },
      { label: "Sistema", items: [["Limpeza", "manutencao.limpeza.index", null], ["Logs", "manutencao.logs.index", null], ["Utilizadores", "users.index", "users.ver"]] }
    ];
    const gruposVisiveis = computed(
      () => grupos.map((g) => ({ ...g, items: g.items.filter(([, , perm]) => itemVisivel(perm)) })).filter((g) => g.items.length > 0)
    );
    const bottomLinks = computed(
      () => [["Início", "dashboard", "dashboard.ver", "🏠"], ["Pedidos", "pedidos.index", "pedidos.ver", "🍽️"], ["Reservas", "reservas.index", "reservas.ver", "📋"], ["Sala", "sala.index", "mesas.ver", "🪑"]].filter(([, , perm]) => itemVisivel(perm))
    );
    onMounted(() => {
      polling = setInterval(() => router.reload({ only: ["urgentes_count"], preserveScroll: true }), 3e4);
    });
    onBeforeUnmount(() => clearInterval(polling));
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-amber-50 pb-20 text-stone-800 md:pb-0" }, _attrs))}><aside class="fixed inset-y-0 left-0 hidden w-56 flex-col border-r border-amber-200 bg-white md:flex xl:w-64"><div class="shrink-0 border-b border-amber-200 px-5 py-5">`);
      _push(ssrRenderComponent(unref(Link), {
        href: _ctx.route("dashboard"),
        class: "text-sm font-bold text-stone-800 xl:text-base hover:text-amber-700 transition"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`Associação de Santana`);
          } else {
            return [
              createTextVNode("Associação de Santana")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`<div class="mt-1 text-xs text-stone-400">Gestão interna</div></div><nav class="min-h-0 flex-1 overflow-y-auto overscroll-contain p-3 pb-5 xl:p-4">`);
      if (itemVisivel("dashboard.ver")) {
        _push(ssrRenderComponent(unref(Link), {
          href: _ctx.route("dashboard"),
          class: ["mb-3 flex min-h-9 items-center rounded-md px-3 py-2 text-xs font-bold text-stone-600 transition hover:bg-amber-50 hover:text-stone-900 xl:text-sm", { "bg-amber-600 text-white hover:bg-amber-600 hover:text-white": ativo("dashboard") }]
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(` Dashboard `);
            } else {
              return [
                createTextVNode(" Dashboard ")
              ];
            }
          }),
          _: 1
        }, _parent));
      } else {
        _push(`<!---->`);
      }
      _push(`<!--[-->`);
      ssrRenderList(gruposVisiveis.value, (grupo) => {
        _push(`<div class="mb-3"><p class="mb-1 px-3 text-[10px] font-semibold uppercase tracking-widest text-stone-400 xl:text-[11px]">${ssrInterpolate(grupo.label)}</p><!--[-->`);
        ssrRenderList(grupo.items, ([nome, rota]) => {
          _push(ssrRenderComponent(unref(Link), {
            key: rota,
            href: _ctx.route(rota),
            class: ["flex min-h-9 items-center justify-between rounded-md px-3 py-1.5 text-xs font-medium text-stone-600 transition hover:bg-amber-50 hover:text-stone-900 xl:text-sm", { "bg-amber-600 text-white hover:bg-amber-600 hover:text-white": ativo(rota) }]
          }, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(`<span${_scopeId}>${ssrInterpolate(nome)}</span>`);
                if (rota === "pedidos.index" && urgentes()) {
                  _push2(`<span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-800"${_scopeId}>${ssrInterpolate(urgentes())}</span>`);
                } else {
                  _push2(`<!---->`);
                }
              } else {
                return [
                  createVNode("span", null, toDisplayString(nome), 1),
                  rota === "pedidos.index" && urgentes() ? (openBlock(), createBlock("span", {
                    key: 0,
                    class: "rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-800"
                  }, toDisplayString(urgentes()), 1)) : createCommentVNode("", true)
                ];
              }
            }),
            _: 2
          }, _parent));
        });
        _push(`<!--]--></div>`);
      });
      _push(`<!--]--></nav></aside>`);
      if (drawerAberto.value) {
        _push(`<div class="fixed inset-0 z-40 bg-stone-900/40 md:hidden"></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<aside class="${ssrRenderClass([drawerAberto.value ? "translate-x-0" : "-translate-x-full", "fixed inset-y-0 left-0 z-[60] flex w-72 transform flex-col bg-white shadow-xl transition md:hidden"])}"><div class="shrink-0 border-b border-amber-200 p-4"><div class="flex items-center justify-between"><strong class="text-stone-800">Menu</strong><button type="button" class="rounded-md border border-amber-200 px-3 py-2 text-sm font-medium text-stone-600">Fechar</button></div></div><nav class="min-h-0 flex-1 overflow-y-auto overscroll-contain p-4 pb-24">`);
      if (itemVisivel("dashboard.ver")) {
        _push(ssrRenderComponent(unref(Link), {
          href: _ctx.route("dashboard"),
          class: ["mb-3 flex min-h-12 items-center rounded-lg px-3 py-3 font-bold text-stone-700 hover:bg-amber-50 transition", { "bg-amber-600 text-white hover:bg-amber-600": ativo("dashboard") }],
          onClick: ($event) => drawerAberto.value = false
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(` Dashboard `);
            } else {
              return [
                createTextVNode(" Dashboard ")
              ];
            }
          }),
          _: 1
        }, _parent));
      } else {
        _push(`<!---->`);
      }
      _push(`<!--[-->`);
      ssrRenderList(gruposVisiveis.value, (grupo) => {
        _push(`<div class="mb-4"><p class="mb-1.5 px-3 text-[11px] font-semibold uppercase tracking-widest text-stone-400">${ssrInterpolate(grupo.label)}</p><!--[-->`);
        ssrRenderList(grupo.items, ([nome, rota]) => {
          _push(ssrRenderComponent(unref(Link), {
            key: rota,
            href: _ctx.route(rota),
            class: ["mb-1 flex min-h-12 items-center justify-between rounded-lg px-3 py-3 font-medium text-stone-700 hover:bg-amber-50 transition", { "bg-amber-600 text-white hover:bg-amber-600": ativo(rota) }],
            onClick: ($event) => drawerAberto.value = false
          }, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(`<span${_scopeId}>${ssrInterpolate(nome)}</span>`);
                if (rota === "pedidos.index" && urgentes()) {
                  _push2(`<span class="text-amber-300"${_scopeId}>${ssrInterpolate(urgentes())} urgentes</span>`);
                } else {
                  _push2(`<!---->`);
                }
              } else {
                return [
                  createVNode("span", null, toDisplayString(nome), 1),
                  rota === "pedidos.index" && urgentes() ? (openBlock(), createBlock("span", {
                    key: 0,
                    class: "text-amber-300"
                  }, toDisplayString(urgentes()) + " urgentes", 1)) : createCommentVNode("", true)
                ];
              }
            }),
            _: 2
          }, _parent));
        });
        _push(`<!--]--></div>`);
      });
      _push(`<!--]-->`);
      if (podeGerir()) {
        _push(`<div class="mt-2 border-t border-amber-100 pt-4"><p class="mb-2 px-3 text-[11px] font-semibold uppercase tracking-widest text-stone-400">Ecrãs de secção</p><div class="grid grid-cols-2 gap-2 px-1"><a class="rounded-lg border border-amber-100 p-2 text-center text-xs font-semibold text-amber-700 hover:bg-amber-50 transition"${ssrRenderAttr("href", _ctx.route("secao.bebidas"))} target="_blank">Bebidas</a><a class="rounded-lg border border-amber-100 p-2 text-center text-xs font-semibold text-amber-700 hover:bg-amber-50 transition"${ssrRenderAttr("href", _ctx.route("secao.frango"))} target="_blank">Frango</a><a class="rounded-lg border border-amber-100 p-2 text-center text-xs font-semibold text-amber-700 hover:bg-amber-50 transition"${ssrRenderAttr("href", _ctx.route("secao.comida"))} target="_blank">Comida</a><a class="rounded-lg border border-amber-100 p-2 text-center text-xs font-semibold text-amber-700 hover:bg-amber-50 transition"${ssrRenderAttr("href", _ctx.route("secao.sobremesas"))} target="_blank">Sobremesas</a><a class="rounded-lg border border-amber-100 p-2 text-center text-xs font-semibold text-amber-700 hover:bg-amber-50 transition"${ssrRenderAttr("href", _ctx.route("secao.acompanhamentos"))} target="_blank">Acompanhamentos</a><a class="rounded-lg border border-amber-100 p-2 text-center text-xs font-semibold text-amber-700 hover:bg-amber-50 transition"${ssrRenderAttr("href", _ctx.route("secao.bar"))} target="_blank">Bar</a><a class="col-span-2 rounded-lg border border-amber-100 p-2 text-center text-xs font-semibold text-amber-700 hover:bg-amber-50 transition"${ssrRenderAttr("href", _ctx.route("pos.reservas.index"))} target="_blank">Reservas POS</a></div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</nav></aside><main class="backoffice-main md:pl-56 xl:pl-64"><header class="flex items-center justify-between border-b border-amber-200 bg-white px-4 py-3.5 lg:px-8"><button type="button" class="rounded-md border border-amber-200 px-3 py-2 text-sm font-semibold text-stone-700 hover:bg-amber-50 md:hidden">Menu</button><div class="hidden text-sm font-medium text-stone-500 md:block">${ssrInterpolate(unref(page).props.auth?.user?.name)}</div>`);
      _push(ssrRenderComponent(unref(Link), {
        href: _ctx.route("logout"),
        method: "post",
        as: "button",
        class: "rounded-md border border-amber-200 bg-white px-3 py-2 text-sm font-semibold text-stone-700 transition hover:bg-amber-50"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`Logout`);
          } else {
            return [
              createTextVNode("Logout")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</header><section class="p-4 lg:p-8">`);
      if (unref(page).props.flash?.success) {
        _push(`<div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">${ssrInterpolate(unref(page).props.flash.success)}</div>`);
      } else {
        _push(`<!---->`);
      }
      ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent);
      _push(`</section><footer class="px-4 pb-24 text-center text-xs text-stone-400 md:pb-6 lg:px-8"><span>Copyright © ${ssrInterpolate(unref(year))} Associação de Santana.</span><span class="mx-2">·</span><a href="https://ateneya.com/" target="_blank" rel="noopener" class="font-semibold text-stone-500 hover:text-amber-700 transition">#CreatingDevelopingImproving4you</a></footer></main><nav class="fixed inset-x-0 bottom-0 z-50 grid border-t border-amber-200 bg-white p-2 md:hidden" style="${ssrRenderStyle(`grid-template-columns: repeat(${bottomLinks.value.length + 1}, 1fr)`)}"><!--[-->`);
      ssrRenderList(bottomLinks.value, ([label, rota, , icon]) => {
        _push(ssrRenderComponent(unref(Link), {
          key: rota,
          href: _ctx.route(rota),
          class: ["relative flex min-h-12 flex-col items-center justify-center gap-0.5 rounded-lg px-1 py-1 text-[10px] font-bold transition", ativo(rota) ? "bg-amber-600 text-white" : "text-stone-600 hover:bg-amber-50"]
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<span class="text-base leading-none"${_scopeId}>${ssrInterpolate(icon)}</span><span${_scopeId}>${ssrInterpolate(label)}</span>`);
              if (rota === "pedidos.index" && urgentes()) {
                _push2(`<span class="absolute -right-0.5 -top-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[9px] font-black text-white"${_scopeId}>${ssrInterpolate(urgentes())}</span>`);
              } else {
                _push2(`<!---->`);
              }
            } else {
              return [
                createVNode("span", { class: "text-base leading-none" }, toDisplayString(icon), 1),
                createVNode("span", null, toDisplayString(label), 1),
                rota === "pedidos.index" && urgentes() ? (openBlock(), createBlock("span", {
                  key: 0,
                  class: "absolute -right-0.5 -top-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[9px] font-black text-white"
                }, toDisplayString(urgentes()), 1)) : createCommentVNode("", true)
              ];
            }
          }),
          _: 2
        }, _parent));
      });
      _push(`<!--]--><button type="button" class="flex min-h-12 flex-col items-center justify-center gap-0.5 rounded-lg px-1 py-1 text-[10px] font-bold text-stone-600 hover:bg-amber-50"><span class="text-base leading-none">☰</span><span>Menu</span></button></nav></div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Layouts/AppLayout.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as _
};
