import { ref, computed, unref, withCtx, createTextVNode, createVNode, toDisplayString, openBlock, createBlock, createCommentVNode, Fragment, renderList, withModifiers, useSSRContext } from "vue";
import { ssrRenderComponent, ssrInterpolate, ssrRenderAttr, ssrRenderList } from "vue/server-renderer";
import { _ as _sfc_main$1 } from "./AppLayout-BWDcck4N.js";
import { Head, Link, router } from "@inertiajs/vue3";
const _sfc_main = {
  __name: "Show",
  __ssrInlineRender: true,
  props: {
    evento: Object
  },
  setup(__props) {
    const props = __props;
    const activeMedia = ref(null);
    const mediaList = computed(() => props.evento.media ?? []);
    const activeMediaIndex = computed(() => mediaList.value.findIndex((media) => media.id === activeMedia.value?.id));
    const dataEvento = (evento) => {
      if (!evento.data_inicio) return evento.periodo || "Sem data definida";
      if (evento.data_fim && evento.data_fim !== evento.data_inicio) return `${evento.data_inicio} a ${evento.data_fim}`;
      return evento.data_inicio;
    };
    const uploadMedia = (event) => {
      const ficheiros = Array.from(event.target.files ?? []);
      if (!ficheiros.length) return;
      const data = new FormData();
      ficheiros.forEach((ficheiro) => data.append("ficheiros[]", ficheiro));
      router.post(route("eventos.media.store", props.evento.id), data, {
        preserveScroll: true,
        onFinish: () => {
          event.target.value = "";
        }
      });
    };
    const apagarMedia = (media) => {
      router.delete(route("eventos.media.destroy", media.id), { preserveScroll: true });
    };
    const abrirMedia = (media) => {
      activeMedia.value = media;
    };
    const fecharMedia = () => {
      activeMedia.value = null;
    };
    const mediaAnterior = () => {
      if (!mediaList.value.length) return;
      const index = activeMediaIndex.value <= 0 ? mediaList.value.length - 1 : activeMediaIndex.value - 1;
      activeMedia.value = mediaList.value[index];
    };
    const mediaSeguinte = () => {
      if (!mediaList.value.length) return;
      const index = activeMediaIndex.value >= mediaList.value.length - 1 ? 0 : activeMediaIndex.value + 1;
      activeMedia.value = mediaList.value[index];
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<!--[-->`);
      _push(ssrRenderComponent(unref(Head), {
        title: __props.evento.titulo
      }, null, _parent));
      _push(ssrRenderComponent(_sfc_main$1, null, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="mb-6 flex flex-wrap items-end justify-between gap-3"${_scopeId}><div${_scopeId}><p class="text-sm font-black uppercase tracking-[0.14em] text-slate-400"${_scopeId}>Ficha do evento</p><h1 class="text-2xl font-black"${_scopeId}>${ssrInterpolate(__props.evento.titulo)}</h1></div><div class="flex flex-wrap gap-2"${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Link), {
              href: _ctx.route("eventos.edit", __props.evento.id),
              class: "rounded-md bg-slate-900 px-4 py-2 text-sm font-bold text-white"
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`Editar`);
                } else {
                  return [
                    createTextVNode("Editar")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(ssrRenderComponent(unref(Link), {
              href: _ctx.route("eventos.index"),
              class: "rounded-md border border-slate-300 px-4 py-2 text-sm font-bold hover:bg-white"
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`Voltar`);
                } else {
                  return [
                    createTextVNode("Voltar")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`</div></div><section class="overflow-hidden rounded-lg bg-slate-950 text-white shadow-sm"${_scopeId}><div class="grid lg:grid-cols-[360px_1fr]"${_scopeId}>`);
            if (__props.evento.cartaz) {
              _push2(`<img${ssrRenderAttr("src", __props.evento.cartaz)}${ssrRenderAttr("alt", __props.evento.titulo)} class="h-full max-h-[620px] w-full object-cover"${_scopeId}>`);
            } else {
              _push2(`<div class="grid min-h-96 place-items-center bg-slate-900 text-sm font-bold text-white/50"${_scopeId}>Sem cartaz</div>`);
            }
            _push2(`<div class="p-6 lg:p-8"${_scopeId}><div class="flex flex-wrap gap-2"${_scopeId}><span class="rounded bg-white px-3 py-1 text-xs font-black uppercase text-slate-950"${_scopeId}>${ssrInterpolate(__props.evento.estado)}</span>`);
            if (__props.evento.destaque) {
              _push2(`<span class="rounded bg-amber-300 px-3 py-1 text-xs font-black uppercase text-slate-950"${_scopeId}>Destaque</span>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`<span class="rounded bg-white/10 px-3 py-1 text-xs font-black uppercase text-white"${_scopeId}>${ssrInterpolate(__props.evento.badge || "Evento")}</span></div><h2 class="mt-5 text-4xl font-black"${_scopeId}>${ssrInterpolate(__props.evento.titulo)}</h2><p class="mt-2 text-lg font-bold text-amber-200"${_scopeId}>${ssrInterpolate(__props.evento.subtitulo || __props.evento.localizacao)}</p><p class="mt-5 max-w-3xl leading-relaxed text-slate-200"${_scopeId}>${ssrInterpolate(__props.evento.descricao || "Sem descricao registada.")}</p>`);
            if (__props.evento.facebook_post_url) {
              _push2(`<a${ssrRenderAttr("href", __props.evento.facebook_post_url)} target="_blank" rel="noreferrer" class="mt-5 inline-flex rounded-md bg-blue-500 px-4 py-2 text-sm font-black text-white hover:bg-blue-400"${_scopeId}> Ver post do Facebook </a>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`<div class="mt-8 grid gap-3 sm:grid-cols-3"${_scopeId}><div class="rounded-md bg-white/10 p-4"${_scopeId}><p class="text-xs font-black uppercase text-slate-400"${_scopeId}>Data</p><p class="mt-1 font-black"${_scopeId}>${ssrInterpolate(dataEvento(__props.evento))}</p></div><div class="rounded-md bg-white/10 p-4"${_scopeId}><p class="text-xs font-black uppercase text-slate-400"${_scopeId}>Local</p><p class="mt-1 font-black"${_scopeId}>${ssrInterpolate(__props.evento.localizacao || "Por definir")}</p></div><div class="rounded-md bg-white/10 p-4"${_scopeId}><p class="text-xs font-black uppercase text-slate-400"${_scopeId}>Galeria</p><p class="mt-1 font-black"${_scopeId}>${ssrInterpolate(__props.evento.media?.length || 0)} ficheiros</p></div></div>`);
            if (__props.evento.programa?.length) {
              _push2(`<div class="mt-8"${_scopeId}><h3 class="font-black"${_scopeId}>Programa</h3><div class="mt-3 grid gap-3 sm:grid-cols-2"${_scopeId}><!--[-->`);
              ssrRenderList(__props.evento.programa, (grupo) => {
                _push2(`<div class="rounded-md bg-white/10 p-4"${_scopeId}><p class="text-xs font-black uppercase text-amber-200"${_scopeId}>${ssrInterpolate(grupo.label || grupo.day)}</p><ul class="mt-2 space-y-1 text-sm text-slate-100"${_scopeId}><!--[-->`);
                ssrRenderList(grupo.items, (item) => {
                  _push2(`<li${_scopeId}>${ssrInterpolate(item)}</li>`);
                });
                _push2(`<!--]--></ul></div>`);
              });
              _push2(`<!--]--></div></div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div></div></section><section class="mt-6 rounded-lg bg-white p-5 shadow-sm"${_scopeId}><div class="mb-4 flex flex-wrap items-center justify-between gap-3"${_scopeId}><div${_scopeId}><h2 class="text-xl font-black"${_scopeId}>Como correu o evento</h2><p class="text-sm text-slate-500"${_scopeId}>Guarda aqui as fotos e videos recolhidos durante o evento.</p></div><label class="cursor-pointer rounded-md bg-slate-900 px-4 py-2 text-sm font-black text-white"${_scopeId}> Adicionar fotos/videos <input type="file" multiple accept="image/*,video/mp4,video/webm,video/quicktime" class="hidden"${_scopeId}></label></div>`);
            if (__props.evento.media?.length) {
              _push2(`<div class="grid gap-5 lg:grid-cols-2"${_scopeId}><!--[-->`);
              ssrRenderList(__props.evento.media, (media) => {
                _push2(`<figure class="overflow-hidden rounded-xl border border-slate-200 bg-slate-50 shadow-sm"${_scopeId}><button type="button" class="block w-full bg-slate-950 text-left"${_scopeId}>`);
                if (media.tipo === "foto") {
                  _push2(`<img${ssrRenderAttr("src", media.caminho)}${ssrRenderAttr("alt", media.titulo)} class="aspect-[16/10] w-full object-cover transition duration-500 hover:scale-[1.02]"${_scopeId}>`);
                } else {
                  _push2(`<video${ssrRenderAttr("src", media.caminho)} class="aspect-[16/10] w-full bg-black object-cover"${_scopeId}></video>`);
                }
                _push2(`</button><figcaption class="flex items-center justify-between gap-2 p-3 text-sm"${_scopeId}><span class="truncate font-bold"${_scopeId}>${ssrInterpolate(media.titulo || "Ficheiro do evento")}</span><div class="flex items-center gap-3"${_scopeId}><button type="button" class="text-xs font-bold text-slate-700 underline"${_scopeId}>Ver grande</button><button type="button" class="text-xs font-bold text-rose-700 underline"${_scopeId}>Remover</button></div></figcaption></figure>`);
              });
              _push2(`<!--]--></div>`);
            } else {
              _push2(`<div class="rounded-md bg-slate-50 p-8 text-center"${_scopeId}><p class="font-black text-slate-700"${_scopeId}>Ainda nao ha registos deste evento.</p><p class="mt-1 text-sm text-slate-500"${_scopeId}>Quando tiveres fotos ou videos, carrega-os aqui para criar a memoria do evento.</p></div>`);
            }
            _push2(`</section>`);
            if (activeMedia.value) {
              _push2(`<div class="fixed inset-0 z-50 grid place-items-center bg-slate-950/95 p-4"${_scopeId}><button type="button" class="absolute right-4 top-4 rounded-md bg-white px-4 py-2 text-sm font-black text-slate-950"${_scopeId}>Fechar</button>`);
              if (mediaList.value.length > 1) {
                _push2(`<button type="button" class="absolute left-4 top-1/2 rounded-full border border-white/20 bg-white/10 px-4 py-3 text-3xl font-black text-white hover:bg-white hover:text-slate-950"${_scopeId}>‹</button>`);
              } else {
                _push2(`<!---->`);
              }
              if (mediaList.value.length > 1) {
                _push2(`<button type="button" class="absolute right-4 top-1/2 rounded-full border border-white/20 bg-white/10 px-4 py-3 text-3xl font-black text-white hover:bg-white hover:text-slate-950"${_scopeId}>›</button>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`<div class="w-full max-w-6xl"${_scopeId}>`);
              if (activeMedia.value.tipo === "foto") {
                _push2(`<img${ssrRenderAttr("src", activeMedia.value.caminho)}${ssrRenderAttr("alt", activeMedia.value.titulo)} class="mx-auto max-h-[82vh] w-auto rounded-lg object-contain shadow-2xl"${_scopeId}>`);
              } else {
                _push2(`<video${ssrRenderAttr("src", activeMedia.value.caminho)} controls autoplay class="mx-auto max-h-[82vh] w-full rounded-lg bg-black shadow-2xl"${_scopeId}></video>`);
              }
              _push2(`<div class="mt-4 flex flex-wrap items-center justify-between gap-3 text-white"${_scopeId}><div${_scopeId}><p class="font-black"${_scopeId}>${ssrInterpolate(activeMedia.value.titulo || "Ficheiro do evento")}</p><p class="text-sm font-semibold text-white/60"${_scopeId}>${ssrInterpolate(activeMediaIndex.value + 1)} / ${ssrInterpolate(mediaList.value.length)}</p></div><a${ssrRenderAttr("href", activeMedia.value.caminho)} target="_blank" rel="noreferrer" class="rounded-md border border-white/25 px-4 py-2 text-sm font-black hover:bg-white hover:text-slate-950"${_scopeId}>Abrir ficheiro</a></div></div></div>`);
            } else {
              _push2(`<!---->`);
            }
          } else {
            return [
              createVNode("div", { class: "mb-6 flex flex-wrap items-end justify-between gap-3" }, [
                createVNode("div", null, [
                  createVNode("p", { class: "text-sm font-black uppercase tracking-[0.14em] text-slate-400" }, "Ficha do evento"),
                  createVNode("h1", { class: "text-2xl font-black" }, toDisplayString(__props.evento.titulo), 1)
                ]),
                createVNode("div", { class: "flex flex-wrap gap-2" }, [
                  createVNode(unref(Link), {
                    href: _ctx.route("eventos.edit", __props.evento.id),
                    class: "rounded-md bg-slate-900 px-4 py-2 text-sm font-bold text-white"
                  }, {
                    default: withCtx(() => [
                      createTextVNode("Editar")
                    ]),
                    _: 1
                  }, 8, ["href"]),
                  createVNode(unref(Link), {
                    href: _ctx.route("eventos.index"),
                    class: "rounded-md border border-slate-300 px-4 py-2 text-sm font-bold hover:bg-white"
                  }, {
                    default: withCtx(() => [
                      createTextVNode("Voltar")
                    ]),
                    _: 1
                  }, 8, ["href"])
                ])
              ]),
              createVNode("section", { class: "overflow-hidden rounded-lg bg-slate-950 text-white shadow-sm" }, [
                createVNode("div", { class: "grid lg:grid-cols-[360px_1fr]" }, [
                  __props.evento.cartaz ? (openBlock(), createBlock("img", {
                    key: 0,
                    src: __props.evento.cartaz,
                    alt: __props.evento.titulo,
                    class: "h-full max-h-[620px] w-full object-cover"
                  }, null, 8, ["src", "alt"])) : (openBlock(), createBlock("div", {
                    key: 1,
                    class: "grid min-h-96 place-items-center bg-slate-900 text-sm font-bold text-white/50"
                  }, "Sem cartaz")),
                  createVNode("div", { class: "p-6 lg:p-8" }, [
                    createVNode("div", { class: "flex flex-wrap gap-2" }, [
                      createVNode("span", { class: "rounded bg-white px-3 py-1 text-xs font-black uppercase text-slate-950" }, toDisplayString(__props.evento.estado), 1),
                      __props.evento.destaque ? (openBlock(), createBlock("span", {
                        key: 0,
                        class: "rounded bg-amber-300 px-3 py-1 text-xs font-black uppercase text-slate-950"
                      }, "Destaque")) : createCommentVNode("", true),
                      createVNode("span", { class: "rounded bg-white/10 px-3 py-1 text-xs font-black uppercase text-white" }, toDisplayString(__props.evento.badge || "Evento"), 1)
                    ]),
                    createVNode("h2", { class: "mt-5 text-4xl font-black" }, toDisplayString(__props.evento.titulo), 1),
                    createVNode("p", { class: "mt-2 text-lg font-bold text-amber-200" }, toDisplayString(__props.evento.subtitulo || __props.evento.localizacao), 1),
                    createVNode("p", { class: "mt-5 max-w-3xl leading-relaxed text-slate-200" }, toDisplayString(__props.evento.descricao || "Sem descricao registada."), 1),
                    __props.evento.facebook_post_url ? (openBlock(), createBlock("a", {
                      key: 0,
                      href: __props.evento.facebook_post_url,
                      target: "_blank",
                      rel: "noreferrer",
                      class: "mt-5 inline-flex rounded-md bg-blue-500 px-4 py-2 text-sm font-black text-white hover:bg-blue-400"
                    }, " Ver post do Facebook ", 8, ["href"])) : createCommentVNode("", true),
                    createVNode("div", { class: "mt-8 grid gap-3 sm:grid-cols-3" }, [
                      createVNode("div", { class: "rounded-md bg-white/10 p-4" }, [
                        createVNode("p", { class: "text-xs font-black uppercase text-slate-400" }, "Data"),
                        createVNode("p", { class: "mt-1 font-black" }, toDisplayString(dataEvento(__props.evento)), 1)
                      ]),
                      createVNode("div", { class: "rounded-md bg-white/10 p-4" }, [
                        createVNode("p", { class: "text-xs font-black uppercase text-slate-400" }, "Local"),
                        createVNode("p", { class: "mt-1 font-black" }, toDisplayString(__props.evento.localizacao || "Por definir"), 1)
                      ]),
                      createVNode("div", { class: "rounded-md bg-white/10 p-4" }, [
                        createVNode("p", { class: "text-xs font-black uppercase text-slate-400" }, "Galeria"),
                        createVNode("p", { class: "mt-1 font-black" }, toDisplayString(__props.evento.media?.length || 0) + " ficheiros", 1)
                      ])
                    ]),
                    __props.evento.programa?.length ? (openBlock(), createBlock("div", {
                      key: 1,
                      class: "mt-8"
                    }, [
                      createVNode("h3", { class: "font-black" }, "Programa"),
                      createVNode("div", { class: "mt-3 grid gap-3 sm:grid-cols-2" }, [
                        (openBlock(true), createBlock(Fragment, null, renderList(__props.evento.programa, (grupo) => {
                          return openBlock(), createBlock("div", {
                            key: grupo.label || grupo.day,
                            class: "rounded-md bg-white/10 p-4"
                          }, [
                            createVNode("p", { class: "text-xs font-black uppercase text-amber-200" }, toDisplayString(grupo.label || grupo.day), 1),
                            createVNode("ul", { class: "mt-2 space-y-1 text-sm text-slate-100" }, [
                              (openBlock(true), createBlock(Fragment, null, renderList(grupo.items, (item) => {
                                return openBlock(), createBlock("li", { key: item }, toDisplayString(item), 1);
                              }), 128))
                            ])
                          ]);
                        }), 128))
                      ])
                    ])) : createCommentVNode("", true)
                  ])
                ])
              ]),
              createVNode("section", { class: "mt-6 rounded-lg bg-white p-5 shadow-sm" }, [
                createVNode("div", { class: "mb-4 flex flex-wrap items-center justify-between gap-3" }, [
                  createVNode("div", null, [
                    createVNode("h2", { class: "text-xl font-black" }, "Como correu o evento"),
                    createVNode("p", { class: "text-sm text-slate-500" }, "Guarda aqui as fotos e videos recolhidos durante o evento.")
                  ]),
                  createVNode("label", { class: "cursor-pointer rounded-md bg-slate-900 px-4 py-2 text-sm font-black text-white" }, [
                    createTextVNode(" Adicionar fotos/videos "),
                    createVNode("input", {
                      type: "file",
                      multiple: "",
                      accept: "image/*,video/mp4,video/webm,video/quicktime",
                      class: "hidden",
                      onChange: uploadMedia
                    }, null, 32)
                  ])
                ]),
                __props.evento.media?.length ? (openBlock(), createBlock("div", {
                  key: 0,
                  class: "grid gap-5 lg:grid-cols-2"
                }, [
                  (openBlock(true), createBlock(Fragment, null, renderList(__props.evento.media, (media) => {
                    return openBlock(), createBlock("figure", {
                      key: media.id,
                      class: "overflow-hidden rounded-xl border border-slate-200 bg-slate-50 shadow-sm"
                    }, [
                      createVNode("button", {
                        type: "button",
                        class: "block w-full bg-slate-950 text-left",
                        onClick: ($event) => abrirMedia(media)
                      }, [
                        media.tipo === "foto" ? (openBlock(), createBlock("img", {
                          key: 0,
                          src: media.caminho,
                          alt: media.titulo,
                          class: "aspect-[16/10] w-full object-cover transition duration-500 hover:scale-[1.02]"
                        }, null, 8, ["src", "alt"])) : (openBlock(), createBlock("video", {
                          key: 1,
                          src: media.caminho,
                          class: "aspect-[16/10] w-full bg-black object-cover"
                        }, null, 8, ["src"]))
                      ], 8, ["onClick"]),
                      createVNode("figcaption", { class: "flex items-center justify-between gap-2 p-3 text-sm" }, [
                        createVNode("span", { class: "truncate font-bold" }, toDisplayString(media.titulo || "Ficheiro do evento"), 1),
                        createVNode("div", { class: "flex items-center gap-3" }, [
                          createVNode("button", {
                            type: "button",
                            class: "text-xs font-bold text-slate-700 underline",
                            onClick: ($event) => abrirMedia(media)
                          }, "Ver grande", 8, ["onClick"]),
                          createVNode("button", {
                            type: "button",
                            class: "text-xs font-bold text-rose-700 underline",
                            onClick: ($event) => apagarMedia(media)
                          }, "Remover", 8, ["onClick"])
                        ])
                      ])
                    ]);
                  }), 128))
                ])) : (openBlock(), createBlock("div", {
                  key: 1,
                  class: "rounded-md bg-slate-50 p-8 text-center"
                }, [
                  createVNode("p", { class: "font-black text-slate-700" }, "Ainda nao ha registos deste evento."),
                  createVNode("p", { class: "mt-1 text-sm text-slate-500" }, "Quando tiveres fotos ou videos, carrega-os aqui para criar a memoria do evento.")
                ]))
              ]),
              activeMedia.value ? (openBlock(), createBlock("div", {
                key: 0,
                class: "fixed inset-0 z-50 grid place-items-center bg-slate-950/95 p-4",
                onClick: withModifiers(fecharMedia, ["self"])
              }, [
                createVNode("button", {
                  type: "button",
                  class: "absolute right-4 top-4 rounded-md bg-white px-4 py-2 text-sm font-black text-slate-950",
                  onClick: fecharMedia
                }, "Fechar"),
                mediaList.value.length > 1 ? (openBlock(), createBlock("button", {
                  key: 0,
                  type: "button",
                  class: "absolute left-4 top-1/2 rounded-full border border-white/20 bg-white/10 px-4 py-3 text-3xl font-black text-white hover:bg-white hover:text-slate-950",
                  onClick: mediaAnterior
                }, "‹")) : createCommentVNode("", true),
                mediaList.value.length > 1 ? (openBlock(), createBlock("button", {
                  key: 1,
                  type: "button",
                  class: "absolute right-4 top-1/2 rounded-full border border-white/20 bg-white/10 px-4 py-3 text-3xl font-black text-white hover:bg-white hover:text-slate-950",
                  onClick: mediaSeguinte
                }, "›")) : createCommentVNode("", true),
                createVNode("div", { class: "w-full max-w-6xl" }, [
                  activeMedia.value.tipo === "foto" ? (openBlock(), createBlock("img", {
                    key: 0,
                    src: activeMedia.value.caminho,
                    alt: activeMedia.value.titulo,
                    class: "mx-auto max-h-[82vh] w-auto rounded-lg object-contain shadow-2xl"
                  }, null, 8, ["src", "alt"])) : (openBlock(), createBlock("video", {
                    key: 1,
                    src: activeMedia.value.caminho,
                    controls: "",
                    autoplay: "",
                    class: "mx-auto max-h-[82vh] w-full rounded-lg bg-black shadow-2xl"
                  }, null, 8, ["src"])),
                  createVNode("div", { class: "mt-4 flex flex-wrap items-center justify-between gap-3 text-white" }, [
                    createVNode("div", null, [
                      createVNode("p", { class: "font-black" }, toDisplayString(activeMedia.value.titulo || "Ficheiro do evento"), 1),
                      createVNode("p", { class: "text-sm font-semibold text-white/60" }, toDisplayString(activeMediaIndex.value + 1) + " / " + toDisplayString(mediaList.value.length), 1)
                    ]),
                    createVNode("a", {
                      href: activeMedia.value.caminho,
                      target: "_blank",
                      rel: "noreferrer",
                      class: "rounded-md border border-white/25 px-4 py-2 text-sm font-black hover:bg-white hover:text-slate-950"
                    }, "Abrir ficheiro", 8, ["href"])
                  ])
                ])
              ])) : createCommentVNode("", true)
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`<!--]-->`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Eventos/Show.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
