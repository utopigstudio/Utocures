import { usePage } from '@inertiajs/vue3'

export function useTableQuery() {
  const page = usePage()

  function tableQuery(): Record<string, string> {
    const params = new URL(page.url, 'http://localhost').searchParams
    params.delete('page')
    params.delete('export')

    return Object.fromEntries(params.entries())
  }

  return { tableQuery }
}
