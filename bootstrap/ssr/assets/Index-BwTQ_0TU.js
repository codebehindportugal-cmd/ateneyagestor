import { ref, watch, unref, withCtx, createVNode, withModifiers, withDirectives, vModelText, vModelCheckbox, createTextVNode, openBlock, createBlock, createCommentVNode, Fragment, renderList, toDisplayString, useSSRContext } from "vue";
import { ssrRenderComponent, ssrRenderAttr, ssrIncludeBooleanAttr, ssrLooseContain, ssrRenderList, ssrInterpolate } from "vue/server-renderer";
import { _ as _sfc_main$1 } from "./AppLayout-BWDcck4N.js";
import { useForm, Head, router } from "@inertiajs/vue3";
const _sfc_main = {
  __name: "Index",
  __ssrInlineRender: true,
  props: {
    patrocinadores: {
      type: Array,
      default: () => []
    }
  },
  setup(__props) {
    const props = __props;
    const logoNovo = ref(null);
    const items = ref([]);
    const novo = useForm({
      empresa: "",
      website: "",
      descricao: "",
      ordem: 0,
      mostrar_no_slider: true,
      ativo: true,
      logotipo: null
    });
    const normalizar = (sponsor) => ({
      ...sponsor,
      mostrar_no_slider: Boolean(sponsor.mostrar_no_slider),
      ativo: Boolean(sponsor.ativo),
      novoLogo: null,
      novaImagem: null,
      imagemOrdem: 0
    });
    watch(
      () => props.patrocinadores,
      (patrocinadores) => {
        items.value = (patrocinadores || []).map(normalizar);
      },
      { immediate: true }
    );
    const criar = () => {
      novo.post(route("patrocinadores.store"), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
          novo.reset();
          novo.mostrar_no_slider = true;
          novo.ativo = true;
          novo.ordem = 0;
          if (logoNovo.value) logoNovo.value.value = "";
        }
      });
    };
    const atualizar = (sponsor) => {
      router.post(route("patrocinadores.update", sponsor.id), {
        empresa: sponsor.empresa,
        website: sponsor.website || "",
        descricao: sponsor.descricao || "",
        ordem: sponsor.ordem || 0,
        mostrar_no_slider: sponsor.mostrar_no_slider ? 1 : 0,
        ativo: sponsor.ativo ? 1 : 0,
        logotipo: sponsor.novoLogo || null,
        _method: "put"
      }, {
        forceFormData: true,
        preserveScroll: true
      });
    };
    const apagar = (sponsor) => {
      if (!confirm(`Apagar o patrocinador "${sponsor.empresa}" e todas as suas imagens?`)) return;
      router.delete(route("patrocinadores.destroy", sponsor.id), { preserveScroll: true });
    };
    const adicionarImagem = (sponsor) => {
      if (!sponsor.novaImagem) return;
      router.post(route("patrocinadores.imagens.store", sponsor.id), {
        imagem: sponsor.novaImagem,
        ordem: sponsor.imagemOrdem || 0
      }, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
          sponsor.novaImagem = null;
          sponsor.imagemOrdem = 0;
        }
      });
    };
    const apagarImagem = (imagem) => {
      if (!confirm("Remover esta imagem?")) return;
      router.delete(route("patrocinadores.imagens.destroy", imagem.id), { preserveScroll: true });
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<!--[-->`);
      _push(ssrRenderComponent(unref(Head), { title: "Patrocinadores" }, null, _parent));
      _push(ssrRenderComponent(_sfc_main$1, null, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="mb-6 flex flex-wrap items-end justify-between gap-3"${_scopeId}><div${_scopeId}><h1 class="text-2xl font-black"${_scopeId}>Patrocinadores</h1><p class="mt-1 text-sm text-slate-500"${_scopeId}>Gere logos, fotos e visibilidade dos patrocinadores no site.</p></div><div class="flex flex-wrap gap-2"${_scopeId}><a${ssrRenderAttr("href", _ctx.route("patrocinios.ecra"))} target="_blank" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-bold text-white hover:bg-slate-800"${_scopeId}>Ecrã da festa</a><a${ssrRenderAttr("href", _ctx.route("patrocinios.index"))} target="_blank" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-bold hover:bg-white"${_scopeId}>Ver página pública</a></div></div><form class="mb-8 rounded-lg bg-white p-5 shadow-sm"${_scopeId}><h2 class="mb-4 font-black"${_scopeId}>Novo patrocinador</h2><div class="grid gap-3 md:grid-cols-4"${_scopeId}><input${ssrRenderAttr("value", unref(novo).empresa)} required placeholder="Empresa" class="rounded-md border-slate-300 md:col-span-2"${_scopeId}><input${ssrRenderAttr("value", unref(novo).website)} type="url" placeholder="Website com https://" class="rounded-md border-slate-300 md:col-span-2"${_scopeId}><input${ssrRenderAttr("value", unref(novo).descricao)} placeholder="Descrição curta" class="rounded-md border-slate-300 md:col-span-2"${_scopeId}><input${ssrRenderAttr("value", unref(novo).ordem)} type="number" min="0" placeholder="Ordem" class="rounded-md border-slate-300"${_scopeId}><input type="file" accept="image/*,.svg" class="rounded-md border border-slate-300 p-2 text-sm"${_scopeId}><label class="flex items-center gap-2 rounded-md border border-slate-300 px-3 py-2 text-sm font-bold"${_scopeId}><input${ssrIncludeBooleanAttr(Array.isArray(unref(novo).mostrar_no_slider) ? ssrLooseContain(unref(novo).mostrar_no_slider, null) : unref(novo).mostrar_no_slider) ? " checked" : ""} type="checkbox" class="rounded border-slate-300"${_scopeId}> Slider </label><label class="flex items-center gap-2 rounded-md border border-slate-300 px-3 py-2 text-sm font-bold"${_scopeId}><input${ssrIncludeBooleanAttr(Array.isArray(unref(novo).ativo) ? ssrLooseContain(unref(novo).ativo, null) : unref(novo).ativo) ? " checked" : ""} type="checkbox" class="rounded border-slate-300"${_scopeId}> Ativo </label></div><button class="mt-4 rounded-md bg-slate-900 px-5 py-3 font-black text-white disabled:opacity-60"${ssrIncludeBooleanAttr(unref(novo).processing) ? " disabled" : ""}${_scopeId}>Criar patrocinador</button></form>`);
            if (!items.value.length) {
              _push2(`<div class="rounded-lg bg-white p-6 text-center font-bold text-slate-500 shadow-sm"${_scopeId}> Ainda não existem patrocinadores. </div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`<div class="grid gap-4"${_scopeId}><!--[-->`);
            ssrRenderList(items.value, (sponsor) => {
              _push2(`<article class="rounded-lg bg-white p-5 shadow-sm"${_scopeId}><div class="grid gap-4 lg:grid-cols-[180px_1fr_auto]"${_scopeId}><div class="grid min-h-32 place-items-center rounded-md bg-slate-50 p-4"${_scopeId}><img${ssrRenderAttr("src", sponsor.logo_url)}${ssrRenderAttr("alt", sponsor.empresa)} class="max-h-24 max-w-full object-contain"${_scopeId}></div><div class="grid gap-3 md:grid-cols-4"${_scopeId}><input${ssrRenderAttr("value", sponsor.empresa)} class="rounded-md border-slate-300 md:col-span-2"${_scopeId}><input${ssrRenderAttr("value", sponsor.website)} type="url" class="rounded-md border-slate-300 md:col-span-2" placeholder="Website"${_scopeId}><input${ssrRenderAttr("value", sponsor.descricao)} class="rounded-md border-slate-300 md:col-span-2" placeholder="Descrição"${_scopeId}><input${ssrRenderAttr("value", sponsor.ordem)} type="number" min="0" class="rounded-md border-slate-300"${_scopeId}><input type="file" accept="image/*,.svg" class="rounded-md border border-slate-300 p-2 text-sm"${_scopeId}><label class="flex items-center gap-2 rounded-md border border-slate-300 px-3 py-2 text-sm font-bold"${_scopeId}><input${ssrIncludeBooleanAttr(Array.isArray(sponsor.mostrar_no_slider) ? ssrLooseContain(sponsor.mostrar_no_slider, null) : sponsor.mostrar_no_slider) ? " checked" : ""} type="checkbox" class="rounded border-slate-300"${_scopeId}> Slider </label><label class="flex items-center gap-2 rounded-md border border-slate-300 px-3 py-2 text-sm font-bold"${_scopeId}><input${ssrIncludeBooleanAttr(Array.isArray(sponsor.ativo) ? ssrLooseContain(sponsor.ativo, null) : sponsor.ativo) ? " checked" : ""} type="checkbox" class="rounded border-slate-300"${_scopeId}> Ativo </label></div><div class="flex flex-row gap-2 lg:flex-col"${_scopeId}><button type="button" class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-bold text-white"${_scopeId}>Guardar</button><button type="button" class="rounded-md border border-rose-200 px-4 py-2 text-sm font-bold text-rose-700 hover:bg-rose-50"${_scopeId}>Apagar</button></div></div><div class="mt-5 border-t border-slate-100 pt-5"${_scopeId}><h3 class="mb-3 text-sm font-black uppercase tracking-wide text-slate-500"${_scopeId}> Fotos para o ecrã <span class="ml-2 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-600"${_scopeId}>${ssrInterpolate((sponsor.images || []).length)}</span></h3>`);
              if (sponsor.images?.length) {
                _push2(`<div class="mb-4 flex flex-wrap gap-3"${_scopeId}><!--[-->`);
                ssrRenderList(sponsor.images, (img) => {
                  _push2(`<div class="group relative h-24 w-36 overflow-hidden rounded-md border border-slate-200 bg-slate-50"${_scopeId}><img${ssrRenderAttr("src", img.url)}${ssrRenderAttr("alt", sponsor.empresa)} class="h-full w-full object-contain p-1"${_scopeId}><button type="button" class="absolute right-1 top-1 hidden rounded bg-rose-600 p-1 text-white group-hover:flex" title="Remover imagem"${_scopeId}><svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"${_scopeId}><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"${_scopeId}></path></svg></button><div class="absolute bottom-0 inset-x-0 bg-black/40 px-1.5 py-0.5 text-center text-xs text-white/80"${_scopeId}> #${ssrInterpolate(img.ordem)}</div></div>`);
                });
                _push2(`<!--]--></div>`);
              } else {
                _push2(`<div class="mb-4 rounded-md bg-slate-50 p-3 text-sm text-slate-400"${_scopeId}> Sem fotos adicionadas — no ecrã usa o logótipo. </div>`);
              }
              _push2(`<div class="flex flex-wrap items-end gap-3"${_scopeId}><div class="flex-1 min-w-48"${_scopeId}><label class="mb-1 block text-xs font-bold text-slate-500"${_scopeId}>Nova foto</label><input type="file" accept="image/*,.svg" class="w-full rounded-md border border-slate-300 p-2 text-sm"${_scopeId}></div><div class="w-24"${_scopeId}><label class="mb-1 block text-xs font-bold text-slate-500"${_scopeId}>Ordem</label><input${ssrRenderAttr("value", sponsor.imagemOrdem)} type="number" min="0" class="w-full rounded-md border-slate-300 text-sm"${_scopeId}></div><button type="button" class="rounded-md bg-slate-700 px-4 py-2 text-sm font-bold text-white hover:bg-slate-800 disabled:opacity-40"${ssrIncludeBooleanAttr(!sponsor.novaImagem) ? " disabled" : ""}${_scopeId}> Adicionar </button></div></div></article>`);
            });
            _push2(`<!--]--></div>`);
          } else {
            return [
              createVNode("div", { class: "mb-6 flex flex-wrap items-end justify-between gap-3" }, [
                createVNode("div", null, [
                  createVNode("h1", { class: "text-2xl font-black" }, "Patrocinadores"),
                  createVNode("p", { class: "mt-1 text-sm text-slate-500" }, "Gere logos, fotos e visibilidade dos patrocinadores no site.")
                ]),
                createVNode("div", { class: "flex flex-wrap gap-2" }, [
                  createVNode("a", {
                    href: _ctx.route("patrocinios.ecra"),
                    target: "_blank",
                    class: "rounded-md bg-slate-900 px-4 py-2 text-sm font-bold text-white hover:bg-slate-800"
                  }, "Ecrã da festa", 8, ["href"]),
                  createVNode("a", {
                    href: _ctx.route("patrocinios.index"),
                    target: "_blank",
                    class: "rounded-md border border-slate-300 px-4 py-2 text-sm font-bold hover:bg-white"
                  }, "Ver página pública", 8, ["href"])
                ])
              ]),
              createVNode("form", {
                class: "mb-8 rounded-lg bg-white p-5 shadow-sm",
                onSubmit: withModifiers(criar, ["prevent"])
              }, [
                createVNode("h2", { class: "mb-4 font-black" }, "Novo patrocinador"),
                createVNode("div", { class: "grid gap-3 md:grid-cols-4" }, [
                  withDirectives(createVNode("input", {
                    "onUpdate:modelValue": ($event) => unref(novo).empresa = $event,
                    required: "",
                    placeholder: "Empresa",
                    class: "rounded-md border-slate-300 md:col-span-2"
                  }, null, 8, ["onUpdate:modelValue"]), [
                    [vModelText, unref(novo).empresa]
                  ]),
                  withDirectives(createVNode("input", {
                    "onUpdate:modelValue": ($event) => unref(novo).website = $event,
                    type: "url",
                    placeholder: "Website com https://",
                    class: "rounded-md border-slate-300 md:col-span-2"
                  }, null, 8, ["onUpdate:modelValue"]), [
                    [vModelText, unref(novo).website]
                  ]),
                  withDirectives(createVNode("input", {
                    "onUpdate:modelValue": ($event) => unref(novo).descricao = $event,
                    placeholder: "Descrição curta",
                    class: "rounded-md border-slate-300 md:col-span-2"
                  }, null, 8, ["onUpdate:modelValue"]), [
                    [vModelText, unref(novo).descricao]
                  ]),
                  withDirectives(createVNode("input", {
                    "onUpdate:modelValue": ($event) => unref(novo).ordem = $event,
                    type: "number",
                    min: "0",
                    placeholder: "Ordem",
                    class: "rounded-md border-slate-300"
                  }, null, 8, ["onUpdate:modelValue"]), [
                    [
                      vModelText,
                      unref(novo).ordem,
                      void 0,
                      { number: true }
                    ]
                  ]),
                  createVNode("input", {
                    ref_key: "logoNovo",
                    ref: logoNovo,
                    type: "file",
                    accept: "image/*,.svg",
                    class: "rounded-md border border-slate-300 p-2 text-sm",
                    onChange: ($event) => unref(novo).logotipo = $event.target.files[0]
                  }, null, 40, ["onChange"]),
                  createVNode("label", { class: "flex items-center gap-2 rounded-md border border-slate-300 px-3 py-2 text-sm font-bold" }, [
                    withDirectives(createVNode("input", {
                      "onUpdate:modelValue": ($event) => unref(novo).mostrar_no_slider = $event,
                      type: "checkbox",
                      class: "rounded border-slate-300"
                    }, null, 8, ["onUpdate:modelValue"]), [
                      [vModelCheckbox, unref(novo).mostrar_no_slider]
                    ]),
                    createTextVNode(" Slider ")
                  ]),
                  createVNode("label", { class: "flex items-center gap-2 rounded-md border border-slate-300 px-3 py-2 text-sm font-bold" }, [
                    withDirectives(createVNode("input", {
                      "onUpdate:modelValue": ($event) => unref(novo).ativo = $event,
                      type: "checkbox",
                      class: "rounded border-slate-300"
                    }, null, 8, ["onUpdate:modelValue"]), [
                      [vModelCheckbox, unref(novo).ativo]
                    ]),
                    createTextVNode(" Ativo ")
                  ])
                ]),
                createVNode("button", {
                  class: "mt-4 rounded-md bg-slate-900 px-5 py-3 font-black text-white disabled:opacity-60",
                  disabled: unref(novo).processing
                }, "Criar patrocinador", 8, ["disabled"])
              ], 32),
              !items.value.length ? (openBlock(), createBlock("div", {
                key: 0,
                class: "rounded-lg bg-white p-6 text-center font-bold text-slate-500 shadow-sm"
              }, " Ainda não existem patrocinadores. ")) : createCommentVNode("", true),
              createVNode("div", { class: "grid gap-4" }, [
                (openBlock(true), createBlock(Fragment, null, renderList(items.value, (sponsor) => {
                  return openBlock(), createBlock("article", {
                    key: sponsor.id,
                    class: "rounded-lg bg-white p-5 shadow-sm"
                  }, [
                    createVNode("div", { class: "grid gap-4 lg:grid-cols-[180px_1fr_auto]" }, [
                      createVNode("div", { class: "grid min-h-32 place-items-center rounded-md bg-slate-50 p-4" }, [
                        createVNode("img", {
                          src: sponsor.logo_url,
                          alt: sponsor.empresa,
                          class: "max-h-24 max-w-full object-contain"
                        }, null, 8, ["src", "alt"])
                      ]),
                      createVNode("div", { class: "grid gap-3 md:grid-cols-4" }, [
                        withDirectives(createVNode("input", {
                          "onUpdate:modelValue": ($event) => sponsor.empresa = $event,
                          class: "rounded-md border-slate-300 md:col-span-2"
                        }, null, 8, ["onUpdate:modelValue"]), [
                          [vModelText, sponsor.empresa]
                        ]),
                        withDirectives(createVNode("input", {
                          "onUpdate:modelValue": ($event) => sponsor.website = $event,
                          type: "url",
                          class: "rounded-md border-slate-300 md:col-span-2",
                          placeholder: "Website"
                        }, null, 8, ["onUpdate:modelValue"]), [
                          [vModelText, sponsor.website]
                        ]),
                        withDirectives(createVNode("input", {
                          "onUpdate:modelValue": ($event) => sponsor.descricao = $event,
                          class: "rounded-md border-slate-300 md:col-span-2",
                          placeholder: "Descrição"
                        }, null, 8, ["onUpdate:modelValue"]), [
                          [vModelText, sponsor.descricao]
                        ]),
                        withDirectives(createVNode("input", {
                          "onUpdate:modelValue": ($event) => sponsor.ordem = $event,
                          type: "number",
                          min: "0",
                          class: "rounded-md border-slate-300"
                        }, null, 8, ["onUpdate:modelValue"]), [
                          [
                            vModelText,
                            sponsor.ordem,
                            void 0,
                            { number: true }
                          ]
                        ]),
                        createVNode("input", {
                          type: "file",
                          accept: "image/*,.svg",
                          class: "rounded-md border border-slate-300 p-2 text-sm",
                          onChange: ($event) => sponsor.novoLogo = $event.target.files[0]
                        }, null, 40, ["onChange"]),
                        createVNode("label", { class: "flex items-center gap-2 rounded-md border border-slate-300 px-3 py-2 text-sm font-bold" }, [
                          withDirectives(createVNode("input", {
                            "onUpdate:modelValue": ($event) => sponsor.mostrar_no_slider = $event,
                            type: "checkbox",
                            class: "rounded border-slate-300"
                          }, null, 8, ["onUpdate:modelValue"]), [
                            [vModelCheckbox, sponsor.mostrar_no_slider]
                          ]),
                          createTextVNode(" Slider ")
                        ]),
                        createVNode("label", { class: "flex items-center gap-2 rounded-md border border-slate-300 px-3 py-2 text-sm font-bold" }, [
                          withDirectives(createVNode("input", {
                            "onUpdate:modelValue": ($event) => sponsor.ativo = $event,
                            type: "checkbox",
                            class: "rounded border-slate-300"
                          }, null, 8, ["onUpdate:modelValue"]), [
                            [vModelCheckbox, sponsor.ativo]
                          ]),
                          createTextVNode(" Ativo ")
                        ])
                      ]),
                      createVNode("div", { class: "flex flex-row gap-2 lg:flex-col" }, [
                        createVNode("button", {
                          type: "button",
                          class: "rounded-md bg-emerald-700 px-4 py-2 text-sm font-bold text-white",
                          onClick: ($event) => atualizar(sponsor)
                        }, "Guardar", 8, ["onClick"]),
                        createVNode("button", {
                          type: "button",
                          class: "rounded-md border border-rose-200 px-4 py-2 text-sm font-bold text-rose-700 hover:bg-rose-50",
                          onClick: ($event) => apagar(sponsor)
                        }, "Apagar", 8, ["onClick"])
                      ])
                    ]),
                    createVNode("div", { class: "mt-5 border-t border-slate-100 pt-5" }, [
                      createVNode("h3", { class: "mb-3 text-sm font-black uppercase tracking-wide text-slate-500" }, [
                        createTextVNode(" Fotos para o ecrã "),
                        createVNode("span", { class: "ml-2 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-600" }, toDisplayString((sponsor.images || []).length), 1)
                      ]),
                      sponsor.images?.length ? (openBlock(), createBlock("div", {
                        key: 0,
                        class: "mb-4 flex flex-wrap gap-3"
                      }, [
                        (openBlock(true), createBlock(Fragment, null, renderList(sponsor.images, (img) => {
                          return openBlock(), createBlock("div", {
                            key: img.id,
                            class: "group relative h-24 w-36 overflow-hidden rounded-md border border-slate-200 bg-slate-50"
                          }, [
                            createVNode("img", {
                              src: img.url,
                              alt: sponsor.empresa,
                              class: "h-full w-full object-contain p-1"
                            }, null, 8, ["src", "alt"]),
                            createVNode("button", {
                              type: "button",
                              class: "absolute right-1 top-1 hidden rounded bg-rose-600 p-1 text-white group-hover:flex",
                              title: "Remover imagem",
                              onClick: ($event) => apagarImagem(img)
                            }, [
                              (openBlock(), createBlock("svg", {
                                xmlns: "http://www.w3.org/2000/svg",
                                class: "h-3 w-3",
                                viewBox: "0 0 20 20",
                                fill: "currentColor"
                              }, [
                                createVNode("path", {
                                  "fill-rule": "evenodd",
                                  d: "M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z",
                                  "clip-rule": "evenodd"
                                })
                              ]))
                            ], 8, ["onClick"]),
                            createVNode("div", { class: "absolute bottom-0 inset-x-0 bg-black/40 px-1.5 py-0.5 text-center text-xs text-white/80" }, " #" + toDisplayString(img.ordem), 1)
                          ]);
                        }), 128))
                      ])) : (openBlock(), createBlock("div", {
                        key: 1,
                        class: "mb-4 rounded-md bg-slate-50 p-3 text-sm text-slate-400"
                      }, " Sem fotos adicionadas — no ecrã usa o logótipo. ")),
                      createVNode("div", { class: "flex flex-wrap items-end gap-3" }, [
                        createVNode("div", { class: "flex-1 min-w-48" }, [
                          createVNode("label", { class: "mb-1 block text-xs font-bold text-slate-500" }, "Nova foto"),
                          createVNode("input", {
                            type: "file",
                            accept: "image/*,.svg",
                            class: "w-full rounded-md border border-slate-300 p-2 text-sm",
                            onChange: ($event) => sponsor.novaImagem = $event.target.files[0]
                          }, null, 40, ["onChange"])
                        ]),
                        createVNode("div", { class: "w-24" }, [
                          createVNode("label", { class: "mb-1 block text-xs font-bold text-slate-500" }, "Ordem"),
                          withDirectives(createVNode("input", {
                            "onUpdate:modelValue": ($event) => sponsor.imagemOrdem = $event,
                            type: "number",
                            min: "0",
                            class: "w-full rounded-md border-slate-300 text-sm"
                          }, null, 8, ["onUpdate:modelValue"]), [
                            [
                              vModelText,
                              sponsor.imagemOrdem,
                              void 0,
                              { number: true }
                            ]
                          ])
                        ]),
                        createVNode("button", {
                          type: "button",
                          class: "rounded-md bg-slate-700 px-4 py-2 text-sm font-bold text-white hover:bg-slate-800 disabled:opacity-40",
                          disabled: !sponsor.novaImagem,
                          onClick: ($event) => adicionarImagem(sponsor)
                        }, " Adicionar ", 8, ["disabled", "onClick"])
                      ])
                    ])
                  ]);
                }), 128))
              ])
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Patrocinadores/Index.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
