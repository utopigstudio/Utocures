import { cva, type VariantProps } from 'class-variance-authority'

export { default as InputText } from './InputText.vue'
export { default as InputNumber } from './InputNumber.vue'
export { default as InputCheckbox } from './InputCheckbox.vue'
export { default as InputCheckboxGroup } from './InputCheckboxGroup.vue'
export { default as Select } from './Select.vue'
export { default as SelectClients } from './SelectClients.vue'
export { default as Wysiwyg } from './Wysiwyg.vue'
export { default as InputTextarea } from './InputTextarea.vue'
export { default as SelectIcons } from './SelectIcons.vue'
export { default as Datepicker } from './Datepicker.vue'
export { default as Timepicker } from './Timepicker.vue'
export { default as RadioButtons } from './RadioButtons.vue'
export { default as SelectTemplate } from './SelectTemplate.vue'

export const checkboxVariants = {
  label: cva('',
    {
      variants: {
        variant: {
          default: '',
          pilled: 'px-3 py-1.5 rounded-full text-blue-gray-700 bg-blue-gray-50 text-base leading-6 font-normal hover:border-blue-400 hover:text-blue-600 has-[[data-state=checked]]:bg-blue-600 has-[[data-state=checked]]:text-white has-[[data-state=checked]]:border-blue-600',
        },
      },
      defaultVariants: {
        variant: 'default',
      },
    }
  ),
  input: cva(
    [
      'peer border-input size-[20px] shrink-0 rounded-[4px] border shadow-xs transition-shadow outline-none focus-visible:ring-[3px] focus-visible:border-ring',
      'focus-visible:ring-ring/50 data-[state=checked]:bg-blue-600 data-[state=checked]:border-blue-600 disabled:cursor-not-allowed disabled:opacity-50',
      '[&_span_svg]:w-4 [&_span_svg]:h-4 cursor-pointer',
    ],
    {
      variants: {
        variant: {
          default: '',
          pilled: 'data-[state=checked]:bg-white data-[state=checked]:border-blue-600 [&_span_svg]:text-transparent data-[state=checked]:[&_span_svg]:text-blue-600',
        },
      },
      defaultVariants: {
        variant: 'default',
      },
    }
  ),
}

export const checkboxGroupVariants = cva('gap-2',
  {
    variants: {
      variant: {
        grid: 'grid grid-cols-1 grid-cols-2 md:grid-cols-4',
        flex: 'flex flex-row flex-wrap',
      },
    },
    defaultVariants: {
      variant: 'grid',
    },
  }
)

export const inputVariants = cva(
  [
    'placeholder:text-blue-gray-600 selection:bg-primary selection:text-primary-foreground flex w-full min-w-0 shrink-0 items-center',
    'gap-2 border border-blue-gray-200 bg-white shadow-xs transition-[color,box-shadow] outline-none',
    'disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50',
    'focus-visible:border-blue-900 focus-visible:ring-blue-900/50 focus-visible:ring-blue-900-[3px]',
    'aria-invalid:ring-destructive/20 aria-invalid:border-destructive'
  ],
  {
    variants: {
      variant: {
        default:
          'h-12 mb-4 text-base md:text-sm px-4 py-3 rounded file:inline-flex file:text-blue-gray-600 file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium',
        filter:
          'text-sm leading-5 px-3 py-2 rounded-lg mb-0',
      },
    },
    defaultVariants: {
      variant: 'default',
    },
  },
)

export const selectVariants = cva(
  [
    'flex w-full items-center justify-between border border-blue-gray-200 bg-white shadow-xs transition',
    'focus-visible:border-blue-900 focus-visible:ring-2 focus-visible:ring-blue-900/50 outline-none disabled:opacity-50 text-left cursor-pointer'
  ],
  {
    variants: {
      variant: {
        default:
          'text-base px-4 py-3 h-12 rounded',
        filter:
          'px-3 py-2 text-sm leading-5 rounded-lg',
      },
    },
    defaultVariants: {
      variant: 'default',
    },
  },
)

export const datepickerVariants = cva(
  [
    'w-full border-blue-gray-200 cursor-pointer border rounded-lg text-sm bg-white shadow-xs'
  ],
  {
    variants: {
      variant: {
        default:
          'px-4 py-3 h-12',
        filter:
          'px-3 py-0 h-9.5 text-sm',
      },
    },
    defaultVariants: {
      variant: 'default',
    },
  },
)

export type CheckboxGroupVariants = VariantProps<typeof checkboxGroupVariants>

export type CheckboxVariants = VariantProps<typeof checkboxVariants.input> & VariantProps<typeof checkboxVariants.label>

export type InputVariants = VariantProps<typeof inputVariants>

export type SelectVariants = VariantProps<typeof selectVariants>

export type DatepickerVariants = VariantProps<typeof datepickerVariants>