import { withCtx, unref, createVNode, toDisplayString, withModifiers, withDirectives, vModelText, vModelSelect, useSSRContext } from "vue";
import { ssrRenderComponent, ssrInterpolate, ssrRenderAttr, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual } from "vue/server-renderer";
import { _ as _sfc_main$1 } from "./AppLayout-BWDcck4N.js";
import { useForm } from "@inertiajs/vue3";
const _sfc_main = {
  __name: "Form",
  __ssrInlineRender: true,
  props: { socio: Object },
  setup(__props) {
    const props = __props;
    const form = useForm(props.socio ?? { numero_socio: "", nome: "", email: "", telefone: "", morada: "", data_nascimento: "", data_inscricao: (/* @__PURE__ */ new Date()).toISOString().slice(0, 10), estado: "ativo" });
    const submit = () => props.socio ? form.put(route("socios.update", props.socio.id)) : form.post(route("socios.store"));
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(_sfc_main$1, _attrs, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<h1 class="mb-6 text-2xl font-bold"${_scopeId}>${ssrInterpolate(__props.socio ? "Editar sócio" : "Novo sócio")}</h1><form class="grid max-w-3xl gap-4 rounded-lg bg-white p-6 shadow-sm md:grid-cols-2"${_scopeId}><input${ssrRenderAttr("value", unref(form).numero_socio)} class="rounded-md border-slate-300 bg-white text-stone-900" placeholder="Número"${_scopeId}><input${ssrRenderAttr("value", unref(form).nome)} class="rounded-md border-slate-300 bg-white text-stone-900" placeholder="Nome"${_scopeId}><input${ssrRenderAttr("value", unref(form).email)} class="rounded-md border-slate-300 bg-white text-stone-900" placeholder="Email"${_scopeId}><input${ssrRenderAttr("value", unref(form).telefone)} class="rounded-md border-slate-300 bg-white text-stone-900" placeholder="Telefone"${_scopeId}><input${ssrRenderAttr("value", unref(form).data_nascimento)} type="date" class="rounded-md border-slate-300 bg-white text-stone-900"${_scopeId}><input${ssrRenderAttr("value", unref(form).data_inscricao)} type="date" class="rounded-md border-slate-300 bg-white text-stone-900"${_scopeId}><select class="rounded-md border-slate-300 bg-white text-stone-900"${_scopeId}><option${ssrIncludeBooleanAttr(Array.isArray(unref(form).estado) ? ssrLooseContain(unref(form).estado, null) : ssrLooseEqual(unref(form).estado, null)) ? " selected" : ""}${_scopeId}>ativo</option><option${ssrIncludeBooleanAttr(Array.isArray(unref(form).estado) ? ssrLooseContain(unref(form).estado, null) : ssrLooseEqual(unref(form).estado, null)) ? " selected" : ""}${_scopeId}>inativo</option></select><textarea class="rounded-md border-slate-300 bg-white text-stone-900 md:col-span-2" placeholder="Morada"${_scopeId}>${ssrInterpolate(unref(form).morada)}</textarea><button class="rounded-md bg-slate-900 px-4 py-2 text-white md:col-span-2"${_scopeId}>Guardar</button></form>`);
          } else {
            return [
              createVNode("h1", { class: "mb-6 text-2xl font-bold" }, toDisplayString(__props.socio ? "Editar sócio" : "Novo sócio"), 1),
              createVNode("form", {
                class: "grid max-w-3xl gap-4 rounded-lg bg-white p-6 shadow-sm md:grid-cols-2",
                onSubmit: withModifiers(submit, ["prevent"])
              }, [
                withDirectives(createVNode("input", {
                  "onUpdate:modelValue": ($event) => unref(form).numero_socio = $event,
                  class: "rounded-md border-slate-300 bg-white text-stone-900",
                  placeholder: "Número"
                }, null, 8, ["onUpdate:modelValue"]), [
                  [vModelText, unref(form).numero_socio]
                ]),
                withDirectives(createVNode("input", {
                  "onUpdate:modelValue": ($event) => unref(form).nome = $event,
                  class: "rounded-md border-slate-300 bg-white text-stone-900",
                  placeholder: "Nome"
                }, null, 8, ["onUpdate:modelValue"]), [
                  [vModelText, unref(form).nome]
                ]),
                withDirectives(createVNode("input", {
                  "onUpdate:modelValue": ($event) => unref(form).email = $event,
                  class: "rounded-md border-slate-300 bg-white text-stone-900",
                  placeholder: "Email"
                }, null, 8, ["onUpdate:modelValue"]), [
                  [vModelText, unref(form).email]
                ]),
                withDirectives(createVNode("input", {
                  "onUpdate:modelValue": ($event) => unref(form).telefone = $event,
                  class: "rounded-md border-slate-300 bg-white text-stone-900",
                  placeholder: "Telefone"
                }, null, 8, ["onUpdate:modelValue"]), [
                  [vModelText, unref(form).telefone]
                ]),
                withDirectives(createVNode("input", {
                  "onUpdate:modelValue": ($event) => unref(form).data_nascimento = $event,
                  type: "date",
                  class: "rounded-md border-slate-300 bg-white text-stone-900"
                }, null, 8, ["onUpdate:modelValue"]), [
                  [vModelText, unref(form).data_nascimento]
                ]),
                withDirectives(createVNode("input", {
                  "onUpdate:modelValue": ($event) => unref(form).data_inscricao = $event,
                  type: "date",
                  class: "rounded-md border-slate-300 bg-white text-stone-900"
                }, null, 8, ["onUpdate:modelValue"]), [
                  [vModelText, unref(form).data_inscricao]
                ]),
                withDirectives(createVNode("select", {
                  "onUpdate:modelValue": ($event) => unref(form).estado = $event,
                  class: "rounded-md border-slate-300 bg-white text-stone-900"
                }, [
                  createVNode("option", null, "ativo"),
                  createVNode("option", null, "inativo")
                ], 8, ["onUpdate:modelValue"]), [
                  [vModelSelect, unref(form).estado]
                ]),
                withDirectives(createVNode("textarea", {
                  "onUpdate:modelValue": ($event) => unref(form).morada = $event,
                  class: "rounded-md border-slate-300 bg-white text-stone-900 md:col-span-2",
                  placeholder: "Morada"
                }, null, 8, ["onUpdate:modelValue"]), [
                  [vModelText, unref(form).morada]
                ]),
                createVNode("button", { class: "rounded-md bg-slate-900 px-4 py-2 text-white md:col-span-2" }, "Guardar")
              ], 32)
            ];
          }
        }),
        _: 1
      }, _parent));
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Socios/Form.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
