import { cva, type VariantProps } from 'class-variance-authority'

export { default as Button } from './Button.vue'
export { default as ButtonDelete } from './ButtonDelete.vue'

export const buttonVariants = cva(
  'cursor-pointer inline-flex items-center justify-center gap-2 rounded-md text-sm font-medium transition-all disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*=\'size-\'])]:size-4 shrink-0 [&_svg]:shrink-0 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 aria-invalid:border-destructive',
  {
    variants: {
      variant: {
        default:
          'bg-blue-600 text-white shadow-xs hover:bg-blue-700',
        destructive:
          'bg-destructive text-white shadow-xs hover:bg-destructive/90 focus-visible:ring-destructive/20',
        outline:
          'border border-blue-gray-500 bg-background shadow-xs hover:bg-accent hover:text-accent-foreground',
        secondary:
          'bg-blue-gray-200 text-blue-gray-600 shadow-xs hover:bg-blue-gray-200/80 ',
        ghost:
          'hover:bg-accent hover:text-accent-foreground',
        link: 
          'text-blue-gray-600 underline underline-offset-2 hover:underline',
        icon:
          'bg-transparent hover:bg-accent hover:text-accent-foreground p-0',
      },
      size: {
        default: 'rounded-lg h-9 px-6 py-2 has-[>svg]:px-3 font-medium',
        xs: 'h-6 rounded-md gap-1.5 px-3 has-[>svg]:px-1.5 text-xs leading-4 font-medium',
        sm: 'h-8 rounded-md gap-1.5 px-6 has-[>svg]:px-2.5',
        lg: 'h-10 rounded-lg px-6 has-[>svg]:px-4',
        icon: '',
      },
    },
    defaultVariants: {
      variant: 'default',
      size: 'default',
    },
  },
)

export type ButtonVariants = VariantProps<typeof buttonVariants>
