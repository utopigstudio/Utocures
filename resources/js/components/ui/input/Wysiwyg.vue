<script setup lang="ts">
import { ref, watch, onBeforeUnmount, computed, onMounted } from 'vue'
import { useVModel } from '@vueuse/core'
import { Editor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Placeholder from '@tiptap/extension-placeholder'
import { Label } from '@/components/ui/label'
import InputError from '@/components/ui/input/InputError.vue';

type Props = {
  label?: string
  name: string
  modelValue?: string
  defaultValue?: string
  placeholder?: string
  disabled?: boolean
  class?: string
  error?: string
  required?: boolean
}
const props = defineProps<Props>()
const emit = defineEmits<{ (e:'update:modelValue', v:string):void }>()

const value = useVModel(props, 'modelValue', emit, {
  passive: true,
  defaultValue: props.defaultValue ?? ''
})

const editor = ref<Editor>()
const isActive = (name: string, attrs?: any) => editor.value?.isActive(name, attrs) ?? false
const btnBase = 'h-8 px-2 rounded-lg text-lg bg-white disabled:opacity-50'
const btnActive = '!bg-blue-gray-200 border-blue-gray-100'
const wrapClass = computed(() =>
  ['rounded-xl border border-slate-200 bg-white', props.class].filter(Boolean).join(' ')
)

onMounted(() => {
  const initialContent = value.value?.trim() || props.defaultValue || ''

  editor.value = new Editor({
    extensions: [
      StarterKit.configure({ heading: { levels: [1,2,3] } }),
      Placeholder.configure({
        placeholder: props.placeholder ?? 'Escribe aquí...',
      }),
    ],
    content: initialContent,
    editable: !props.disabled,
    editorProps: {
      attributes: {
        class: 'ProseMirror prose max-w-none focus:outline-none [&_h1]:text-4xl [&_h2]:text-3xl [&_h3]:text-2xl',
      },
    },
    onUpdate: ({ editor }) => {
      const html = editor.getHTML()
      if (html !== value.value) value.value = html
    },
  })

  if (!value.value && props.defaultValue) {
    value.value = props.defaultValue
  }
})

watch(() => props.disabled, (d) => editor.value?.setEditable(!d))

watch(value, (newVal) => {
  const current = editor.value?.getHTML()
  if (newVal !== current) {
    editor.value?.commands.setContent(newVal || '', { emitUpdate: false })
  }
})

onBeforeUnmount(() => editor.value?.destroy())

function clearFormatting() {
  editor.value?.chain().focus().clearNodes().unsetAllMarks().run()
}
</script>

<template>
  <Label v-if="props.label" :for="props.name">{{ props.label }}</Label>
  <div :class="wrapClass">
    <div class="flex flex-wrap items-center gap-3 p-6 border-b border-slate-200">
      <button :class="[btnBase, isActive('bold') && btnActive]" @click="editor?.chain().focus().toggleBold().run()" type="button"><strong>B</strong></button>
      <button :class="[btnBase, isActive('italic') && btnActive]" @click="editor?.chain().focus().toggleItalic().run()" type="button"><em>I</em></button>
      <button :class="[btnBase, isActive('underline') && btnActive]" @click="editor?.chain().focus().toggleUnderline().run()" type="button"><u>U</u></button>
      <button :class="[btnBase, isActive('strike') && btnActive]" @click="editor?.chain().focus().toggleStrike().run()" type="button">S</button>

      <span class="mx-1 w-px h-5 bg-slate-200"></span>

      <button :class="[btnBase, isActive('heading', { level: 1 }) && btnActive]" @click="editor?.chain().focus().toggleHeading({ level:1 }).run()" type="button">H1</button>
      <button :class="[btnBase, isActive('heading', { level: 2 }) && btnActive]" @click="editor?.chain().focus().toggleHeading({ level:2 }).run()" type="button">H2</button>
      <button :class="[btnBase, isActive('heading', { level: 3 }) && btnActive]" @click="editor?.chain().focus().toggleHeading({ level:3 }).run()" type="button">H3</button>

      <span class="mx-1 w-px h-5 bg-slate-200"></span>

      <button :class="[btnBase, isActive('bulletList') && btnActive]" @click="editor?.chain().focus().toggleBulletList().run()" type="button">• Lista</button>
      <button :class="[btnBase, isActive('orderedList') && btnActive]" @click="editor?.chain().focus().toggleOrderedList().run()" type="button">1. Lista</button>

      <span class="mx-1 w-px h-5 bg-slate-200"></span>

      <button :class="btnBase" @click="editor?.chain().focus().undo().run()" type="button">↶</button>
      <button :class="btnBase" @click="editor?.chain().focus().redo().run()" type="button">↷</button>
      <button :class="btnBase" @click="clearFormatting" type="button">Limpiar</button>
    </div>

    <div class="px-6 py-8">
      <EditorContent :editor="editor" />
    </div>

    <input v-if="name" type="hidden" :name="name" :value="value" />
  </div>
  <InputError class="mt-2" :message="props.error" />
</template>