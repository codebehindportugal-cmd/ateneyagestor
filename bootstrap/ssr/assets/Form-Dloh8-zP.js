import { withCtx, unref, createVNode, toDisplayString, withModifiers, withDirectives, vModelText, vModelSelect, useSSRContext } from "vue";
import { ssrRenderComponent, ssrInterpolate, ssrRenderAttr, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual } from "vue/server-renderer";
import { _ as _sfc_main$1 } from "./AppLayout-BWDcck4N.js";
import { useForm } from "@inertiajs/vue3";
const _sfc_main = {
  __name: "Form",
  __ssrInlineRender: true,
  props: { mesa: Object },
  setup(__props) {
    const props = __props;
    const form = useForm(props.mesa ?? { numero: "", nome: "", capacidade: 10, localizacao: "sala", estado: "livre" });
    const submit = () => props.mesa ? form.put(route("mesas.update", props.mesa.id)) : form.post(route("mesas.store"));
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(_sfc_main$1, _attrs, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<h1 class="mb-6 text-2xl font-bold"${_scopeId}>${ssrInterpolate(__props.mesa ? "Editar mesa" : "Nova mesa")}</h1><form class="max-w-xl space-y-4 rounded-lg bg-white p-6 shadow-sm"${_scopeId}><input${ssrRenderAttr("value", unref(form).numero)} class="w-full rounded-md border-slate-300 bg-white text-stone-900" placeholder="Número"${_scopeId}><input${ssrRenderAttr("value", unref(form).nome)} class="w-full rounded-md border-slate-300 bg-white text-stone-900" placeholder="Nome"${_scopeId}><input${ssrRenderAttr("value", unref(form).capacidade)} type="number" min="1" max="10" class="w-full rounded-md border-slate-300 bg-white text-stone-900" placeholder="Capacidade fisica ate 10"${_scopeId}><select class="w-full rounded-md border-slate-300 bg-white text-stone-900"${_scopeId}><option${ssrIncludeBooleanAttr(Array.isArray(unref(form).localizacao) ? ssrLooseContain(unref(form).localizacao, null) : ssrLooseEqual(unref(form).localizacao, null)) ? " selected" : ""}${_scopeId}>sala</option><option${ssrIncludeBooleanAttr(Array.isArray(unref(form).localizacao) ? ssrLooseContain(unref(form).localizacao, null) : ssrLooseEqual(unref(form).localizacao, null)) ? " selected" : ""}${_scopeId}>interior</option><option${ssrIncludeBooleanAttr(Array.isArray(unref(form).localizacao) ? ssrLooseContain(unref(form).localizacao, null) : ssrLooseEqual(unref(form).localizacao, null)) ? " selected" : ""}${_scopeId}>exterior</option><option${ssrIncludeBooleanAttr(Array.isArray(unref(form).localizacao) ? ssrLooseContain(unref(form).localizacao, null) : ssrLooseEqual(unref(form).localizacao, null)) ? " selected" : ""}${_scopeId}>bar</option></select><select class="w-full rounded-md border-slate-300 bg-white text-stone-900"${_scopeId}><option${ssrIncludeBooleanAttr(Array.isArray(unref(form).estado) ? ssrLooseContain(unref(form).estado, null) : ssrLooseEqual(unref(form).estado, null)) ? " selected" : ""}${_scopeId}>livre</option><option${ssrIncludeBooleanAttr(Array.isArray(unref(form).estado) ? ssrLooseContain(unref(form).estado, null) : ssrLooseEqual(unref(form).estado, null)) ? " selected" : ""}${_scopeId}>ocupada</option><option${ssrIncludeBooleanAttr(Array.isArray(unref(form).estado) ? ssrLooseContain(unref(form).estado, null) : ssrLooseEqual(unref(form).estado, null)) ? " selected" : ""}${_scopeId}>reservada</option></select><button class="rounded-md bg-slate-900 px-4 py-2 text-white"${_scopeId}>Guardar</button></form>`);
          } else {
            return [
              createVNode("h1", { class: "mb-6 text-2xl font-bold" }, toDisplayString(__props.mesa ? "Editar mesa" : "Nova mesa"), 1),
              createVNode("form", {
                class: "max-w-xl space-y-4 rounded-lg bg-white p-6 shadow-sm",
                onSubmit: withModifiers(submit, ["prevent"])
              }, [
                withDirectives(createVNode("input", {
                  "onUpdate:modelValue": ($event) => unref(form).numero = $event,
                  class: "w-full rounded-md border-slate-300 bg-white text-stone-900",
                  placeholder: "Número"
                }, null, 8, ["onUpdate:modelValue"]), [
                  [vModelText, unref(form).numero]
                ]),
                withDirectives(createVNode("input", {
                  "onUpdate:modelValue": ($event) => unref(form).nome = $event,
                  class: "w-full rounded-md border-slate-300 bg-white text-stone-900",
                  placeholder: "Nome"
                }, null, 8, ["onUpdate:modelValue"]), [
                  [vModelText, unref(form).nome]
                ]),
                withDirectives(createVNode("input", {
                  "onUpdate:modelValue": ($event) => unref(form).capacidade = $event,
                  type: "number",
                  min: "1",
                  max: "10",
                  class: "w-full rounded-md border-slate-300 bg-white text-stone-900",
                  placeholder: "Capacidade fisica ate 10"
                }, null, 8, ["onUpdate:modelValue"]), [
                  [vModelText, unref(form).capacidade]
                ]),
                withDirectives(createVNode("select", {
                  "onUpdate:modelValue": ($event) => unref(form).localizacao = $event,
                  class: "w-full rounded-md border-slate-300 bg-white text-stone-900"
                }, [
                  createVNode("option", null, "sala"),
                  createVNode("option", null, "interior"),
                  createVNode("option", null, "exterior"),
                  createVNode("option", null, "bar")
                ], 8, ["onUpdate:modelValue"]), [
                  [vModelSelect, unref(form).localizacao]
                ]),
                withDirectives(createVNode("select", {
                  "onUpdate:modelValue": ($event) => unref(form).estado = $event,
                  class: "w-full rounded-md border-slate-300 bg-white text-stone-900"
                }, [
                  createVNode("option", null, "livre"),
                  createVNode("option", null, "ocupada"),
                  createVNode("option", null, "reservada")
                ], 8, ["onUpdate:modelValue"]), [
                  [vModelSelect, unref(form).estado]
                ]),
                createVNode("button", { class: "rounded-md bg-slate-900 px-4 py-2 text-white" }, "Guardar")
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Mesas/Form.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
